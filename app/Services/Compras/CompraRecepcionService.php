<?php

namespace App\Services\Compras;

use App\Models\ColoresProducto;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\CompraRecepcion;
use App\Models\CompraRecepcionItem;
use App\Services\Inventario\InventarioService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CompraRecepcionService
{
    public function __construct(
        private readonly InventarioService $inventarioService
    ) {}

    public function puedeRecibir(Compra $compra): bool
    {
        return strtoupper(trim((string) $compra->estado_compra)) !== 'ANULADA'
            && in_array((string) $compra->estado_recepcion, ['EN_TRANSITO', 'RECEPCION_PARCIAL'], true);
    }

    /**
     * Returns the physical accounting used by both the UI and the transaction.
     * No stock or reception data is changed by this read-only method.
     *
     * @return array<int, array{cantidad:int, recibida:int, aceptada:int, danada:int, incorrecta:int, faltante:int, pendiente:int, tiene_recepcion:bool, cerrada:bool}>
     */
    public function resumenRecepcion(Compra|int $compra): array
    {
        $compraId = $compra instanceof Compra ? (int) $compra->getKey() : (int) $compra;
        $compraEncontrada = Compra::query()->whereKey($compraId)->first();
        if (!$compraEncontrada) {
            throw new CompraRecepcionException('La compra no existe.');
        }

        $items = CompraItem::query()
            ->where('compra_id', $compraId)
            ->where('estado', 'ACTIVO')
            ->orderBy('id')
            ->get();

        return $this->obtenerContabilizacion($items);
    }

    public function recibirCompleta(
        Compra|int $compra,
        string $idempotencyKey,
        ?int $userId = null,
        ?DateTimeInterface $fechaRecepcion = null,
        ?string $observacion = null
    ): CompraRecepcion {
        return $this->registrarRecepcionInterna(
            $compra,
            null,
            $idempotencyKey,
            $userId,
            $fechaRecepcion,
            $observacion
        );
    }

    public function registrarRecepcion(
        Compra|int $compra,
        array $items,
        string $idempotencyKey,
        ?int $userId = null,
        ?DateTimeInterface $fechaRecepcion = null,
        ?string $observacion = null
    ): CompraRecepcion {
        return $this->registrarRecepcionInterna(
            $compra,
            $items,
            $idempotencyKey,
            $userId,
            $fechaRecepcion,
            $observacion
        );
    }

    private function registrarRecepcionInterna(
        Compra|int $compra,
        ?array $itemsPayload,
        string $idempotencyKey,
        ?int $userId,
        ?DateTimeInterface $fechaRecepcion,
        ?string $observacion
    ): CompraRecepcion {
        $idempotencyKey = trim($idempotencyKey);
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 36) {
            throw new CompraRecepcionException('La idempotency_key es obligatoria y no puede superar 36 caracteres.');
        }

        $compraId = $compra instanceof Compra ? (int) $compra->getKey() : (int) $compra;
        if ($compraId <= 0) {
            throw new CompraRecepcionException('La compra indicada no es válida.');
        }

        return DB::transaction(function () use (
            $compraId,
            $itemsPayload,
            $idempotencyKey,
            $userId,
            $fechaRecepcion,
            $observacion
        ): CompraRecepcion {
            $compraBloqueada = Compra::query()
                ->whereKey($compraId)
                ->lockForUpdate()
                ->first();

            if (!$compraBloqueada) {
                throw new CompraRecepcionException('La compra no existe.');
            }

            $existente = CompraRecepcion::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existente) {
                if ((int) $existente->compra_id !== $compraId) {
                    throw new CompraRecepcionException('La idempotency_key ya pertenece a otra compra.');
                }

                return $existente->load('items');
            }

            $this->validarCompraRecepcionable($compraBloqueada);

            $itemsBloqueados = CompraItem::query()
                ->where('compra_id', $compraId)
                ->where('estado', 'ACTIVO')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($itemsBloqueados->isEmpty()) {
                throw new CompraRecepcionException('La compra no tiene items activos para recibir.');
            }

            $itemsPorId = $itemsBloqueados->keyBy(fn (CompraItem $item): int => (int) $item->id);
            $lineasSolicitadas = $itemsPayload === null
                ? null
                : $this->normalizarPayload($itemsPayload, $itemsPorId);

            // Esta primera lectura permite determinar qué variantes se necesitan.
            // La lectura definitiva se repite después de obtener todos los locks.
            $contabilizacion = $this->obtenerContabilizacion($itemsBloqueados);
            $idsParaBloquear = $itemsPayload === null
                ? array_keys(array_filter(
                    $contabilizacion,
                    fn (array $datos): bool => $datos['pendiente'] > 0
                ))
                : array_keys(array_filter(
                    $lineasSolicitadas,
                    fn (array $linea): bool => $linea['cantidad_aceptada'] > 0
                ));

            $variantes = $this->bloquearVariantes(
                $itemsPorId,
                array_map('intval', $idsParaBloquear)
            );

            // Recalcular después de los locks para que el límite sea el real.
            $contabilizacion = $this->obtenerContabilizacion($itemsBloqueados);
            $lineas = $itemsPayload === null
                ? $this->construirLineasCompletas($itemsBloqueados, $contabilizacion)
                : $this->prepararLineasParciales(
                    $lineasSolicitadas,
                    $itemsPorId,
                    $contabilizacion
                );

            if ($lineas === []) {
                throw new CompraRecepcionException('La recepción no contiene cantidades pendientes para registrar.');
            }

            $this->validarVariantesAceptadas($lineas, $variantes);

            try {
                $recepcion = CompraRecepcion::query()->create([
                    'compra_id' => $compraId,
                    'user_id' => $userId,
                    'fecha_recepcion' => $fechaRecepcion
                        ? Carbon::instance($fechaRecepcion)
                        : now(),
                    'observacion' => $observacion,
                    'idempotency_key' => $idempotencyKey,
                ]);
            } catch (QueryException $exception) {
                if (str_contains(strtolower($exception->getMessage()), 'idempotency_key')) {
                    throw new CompraRecepcionException('La idempotency_key ya fue utilizada.', 0, $exception);
                }

                throw $exception;
            }

            foreach ($lineas as $linea) {
                /** @var CompraItem $item */
                $item = $linea['item'];
                $cantidadAceptada = $linea['cantidad_aceptada'];

                $recepcionItem = CompraRecepcionItem::query()->create([
                    'compra_recepcion_id' => $recepcion->id,
                    'compra_item_id' => $item->id,
                    'cantidad_recibida' => $linea['cantidad_recibida'],
                    'cantidad_aceptada' => $cantidadAceptada,
                    'cantidad_danada' => $linea['cantidad_danada'],
                    'cantidad_incorrecta' => $linea['cantidad_incorrecta'],
                    'cantidad_faltante_declarada' => $linea['cantidad_faltante_declarada'],
                    'cierra_item' => $linea['cierra_item'],
                    'observacion' => $linea['observacion'],
                ]);

                if ($cantidadAceptada <= 0) {
                    continue;
                }

                /** @var ColoresProducto $variante */
                $variante = $variantes[(int) $item->id];
                $this->inventarioService->registrarEntradaVariante(
                    (int) $variante->producto_id,
                    (int) $variante->colores_id,
                    (string) $variante->talla_por_color,
                    $cantidadAceptada,
                    $compraId,
                    (int) $item->id,
                    'Entrada por recepción de compra',
                    [
                        'compra_item_id' => (int) $item->id,
                        'compra_recepcion_id' => (int) $recepcion->id,
                        'compra_recepcion_item_id' => (int) $recepcionItem->id,
                    ]
                );

                $nuevoStockIngresado = (int) $item->stock_ingresado + $cantidadAceptada;
                if ($nuevoStockIngresado > (int) $item->cantidad) {
                    throw new CompraRecepcionException("El stock_ingresado del item {$item->id} superaría la cantidad comprada.");
                }

                $item->update(['stock_ingresado' => $nuevoStockIngresado]);
            }

            $contabilizacionFinal = $this->obtenerContabilizacion($itemsBloqueados);
            $nuevoEstado = $this->determinarEstado($contabilizacionFinal);
            $compraBloqueada->update(['estado_recepcion' => $nuevoEstado]);

            return $recepcion->load('items');
        }, 3);
    }

    private function validarCompraRecepcionable(Compra $compra): void
    {
        if (strtoupper(trim((string) $compra->estado_compra)) === 'ANULADA') {
            throw new CompraRecepcionException('No se puede recibir una compra anulada.');
        }

        if (!in_array((string) $compra->estado_recepcion, ['EN_TRANSITO', 'RECEPCION_PARCIAL'], true)) {
            throw new CompraRecepcionException('La compra no está en un estado recepcionable.');
        }
    }

    /**
     * @param Collection<int, CompraItem> $items
     * @return array<int, array{cantidad:int, recibida:int, aceptada:int, danada:int, incorrecta:int, faltante:int, pendiente:int, tiene_recepcion:bool, cerrada:bool}>
     */
    private function obtenerContabilizacion(Collection $items): array
    {
        $itemsPorId = $items->keyBy(fn (CompraItem $item): int => (int) $item->id);
        $resultado = [];

        foreach ($itemsPorId as $id => $item) {
            $cantidad = (int) $item->cantidad;
            $stockIngresado = (int) $item->stock_ingresado;

            if ($cantidad < 0 || $stockIngresado < 0 || $stockIngresado > $cantidad) {
                throw new CompraRecepcionException("El stock_ingresado del item {$id} es inconsistente.");
            }

            $resultado[(int) $id] = [
                'cantidad' => $cantidad,
                'recibida' => 0,
                'aceptada' => 0,
                'danada' => 0,
                'incorrecta' => 0,
                'faltante' => 0,
                'pendiente' => $cantidad,
                'tiene_recepcion' => false,
                'cerrada' => false,
            ];
        }

        if ($resultado === []) {
            return $resultado;
        }

        $recepcionItems = CompraRecepcionItem::query()
            ->whereIn('compra_item_id', array_keys($resultado))
            ->orderBy('id')
            ->get();

        foreach ($recepcionItems as $recepcionItem) {
            $itemId = (int) $recepcionItem->compra_item_id;
            if (!isset($resultado[$itemId])) {
                continue;
            }

            $recibida = (int) $recepcionItem->cantidad_recibida;
            $aceptada = (int) $recepcionItem->cantidad_aceptada;
            $danada = (int) $recepcionItem->cantidad_danada;
            $incorrecta = (int) $recepcionItem->cantidad_incorrecta;
            $faltante = (int) $recepcionItem->cantidad_faltante_declarada;

            if (
                $recibida < 0
                || $aceptada < 0
                || $danada < 0
                || $incorrecta < 0
                || $faltante < 0
                || $recibida !== $aceptada + $danada + $incorrecta
            ) {
                throw new CompraRecepcionException("La recepción del item {$itemId} es inconsistente.");
            }

            $resultado[$itemId]['recibida'] += $recibida;
            $resultado[$itemId]['aceptada'] += $aceptada;
            $resultado[$itemId]['danada'] += $danada;
            $resultado[$itemId]['incorrecta'] += $incorrecta;
            $resultado[$itemId]['faltante'] += $faltante;
            $resultado[$itemId]['tiene_recepcion'] = true;
            $resultado[$itemId]['cerrada'] = $resultado[$itemId]['cerrada'] || (bool) $recepcionItem->cierra_item;
        }

        foreach ($resultado as $itemId => &$datos) {
            $contabilizado = $datos['recibida'] + $datos['faltante'];
            if ($contabilizado > $datos['cantidad']) {
                throw new CompraRecepcionException("El item {$itemId} ya supera la cantidad comprada.");
            }

            $datos['pendiente'] = $datos['cantidad'] - $contabilizado;
            if ($datos['cerrada'] && $datos['pendiente'] > 0) {
                throw new CompraRecepcionException("El item {$itemId} fue cerrado con cantidades pendientes.");
            }
        }
        unset($datos);

        return $resultado;
    }

    /**
     * @param Collection<int, CompraItem> $itemsPorId
     * @return array<int, array{compra_item_id:int, cantidad_aceptada:int, cantidad_danada:int, cantidad_incorrecta:int, cantidad_faltante_declarada:int, cierra_item:bool, observacion:?string}>
     */
    private function normalizarPayload(array $items, Collection $itemsPorId): array
    {
        if ($items === []) {
            throw new CompraRecepcionException('La recepción debe contener al menos un item.');
        }

        $resultado = [];
        foreach ($items as $indice => $payload) {
            if (!is_array($payload)) {
                throw new CompraRecepcionException("La línea {$indice} de recepción no es válida.");
            }

            $itemId = $this->enteroNoNegativo($payload['compra_item_id'] ?? null, 'compra_item_id', $indice);
            if ($itemId <= 0 || !$itemsPorId->has($itemId)) {
                throw new CompraRecepcionException("El compra_item {$itemId} no pertenece a la compra o no está activo.");
            }

            if (isset($resultado[$itemId])) {
                throw new CompraRecepcionException("El compra_item {$itemId} está repetido en la recepción.");
            }

            $cantidadAceptada = $this->enteroNoNegativo($payload['cantidad_aceptada'] ?? null, 'cantidad_aceptada', $itemId);
            $cantidadDanada = $this->enteroNoNegativo($payload['cantidad_danada'] ?? 0, 'cantidad_danada', $itemId);
            $cantidadIncorrecta = $this->enteroNoNegativo($payload['cantidad_incorrecta'] ?? 0, 'cantidad_incorrecta', $itemId);
            $cantidadFaltante = $this->enteroNoNegativo($payload['cantidad_faltante_declarada'] ?? 0, 'cantidad_faltante_declarada', $itemId);
            $cierraItem = $this->booleanoEstricto($payload['cierra_item'] ?? false, $itemId);

            if ($cantidadAceptada + $cantidadDanada + $cantidadIncorrecta + $cantidadFaltante <= 0) {
                throw new CompraRecepcionException("El compra_item {$itemId} no contiene cantidades para registrar.");
            }

            $observacion = $payload['observacion'] ?? null;
            if ($observacion !== null && !is_string($observacion)) {
                throw new CompraRecepcionException("La observación del item {$itemId} no es válida.");
            }

            $resultado[$itemId] = [
                'compra_item_id' => $itemId,
                'cantidad_aceptada' => $cantidadAceptada,
                'cantidad_danada' => $cantidadDanada,
                'cantidad_incorrecta' => $cantidadIncorrecta,
                'cantidad_faltante_declarada' => $cantidadFaltante,
                'cantidad_recibida' => $cantidadAceptada + $cantidadDanada + $cantidadIncorrecta,
                'cierra_item' => $cierraItem,
                'observacion' => $observacion,
            ];
        }

        return $resultado;
    }

    /**
     * @param Collection<int, CompraItem> $items
     * @param array<int, array{cantidad:int, recibida:int, aceptada:int, danada:int, incorrecta:int, faltante:int, pendiente:int, tiene_recepcion:bool, cerrada:bool}> $contabilizacion
     * @return array<int, array{item:CompraItem, cantidad_aceptada:int, cantidad_danada:int, cantidad_incorrecta:int, cantidad_faltante_declarada:int, cantidad_recibida:int, cierra_item:bool, observacion:?string}>
     */
    private function construirLineasCompletas(Collection $items, array $contabilizacion): array
    {
        $lineas = [];
        foreach ($items as $item) {
            $itemId = (int) $item->id;
            $pendiente = $contabilizacion[$itemId]['pendiente'] ?? 0;
            if ($pendiente <= 0) {
                continue;
            }

            $lineas[$itemId] = [
                'item' => $item,
                'cantidad_aceptada' => $pendiente,
                'cantidad_danada' => 0,
                'cantidad_incorrecta' => 0,
                'cantidad_faltante_declarada' => 0,
                'cantidad_recibida' => $pendiente,
                'cierra_item' => true,
                'observacion' => null,
            ];
        }

        return $lineas;
    }

    /**
     * @param array<int, array{compra_item_id:int, cantidad_aceptada:int, cantidad_danada:int, cantidad_incorrecta:int, cantidad_faltante_declarada:int, cantidad_recibida:int, cierra_item:bool, observacion:?string}> $solicitadas
     * @param Collection<int, CompraItem> $itemsPorId
     * @param array<int, array{cantidad:int, recibida:int, aceptada:int, danada:int, incorrecta:int, faltante:int, pendiente:int, tiene_recepcion:bool, cerrada:bool}> $contabilizacion
     * @return array<int, array{item:CompraItem, cantidad_aceptada:int, cantidad_danada:int, cantidad_incorrecta:int, cantidad_faltante_declarada:int, cantidad_recibida:int, cierra_item:bool, observacion:?string}>
     */
    private function prepararLineasParciales(array $solicitadas, Collection $itemsPorId, array $contabilizacion): array
    {
        $lineas = [];
        foreach ($solicitadas as $itemId => $solicitada) {
            $datos = $contabilizacion[$itemId] ?? null;
            if ($datos === null) {
                throw new CompraRecepcionException("El compra_item {$itemId} no pertenece a la compra.");
            }

            $contabilizadoNuevo = $solicitada['cantidad_recibida'] + $solicitada['cantidad_faltante_declarada'];
            if ($contabilizadoNuevo > $datos['pendiente']) {
                throw new CompraRecepcionException("La recepción del item {$itemId} supera el pendiente disponible.");
            }

            $pendienteFinal = $datos['pendiente'] - $contabilizadoNuevo;
            if ($solicitada['cierra_item'] && $pendienteFinal > 0) {
                throw new CompraRecepcionException("El item {$itemId} no puede cerrarse con {$pendienteFinal} unidades pendientes.");
            }

            if (!$solicitada['cierra_item'] && $pendienteFinal === 0) {
                throw new CompraRecepcionException("El item {$itemId} debe cerrarse porque ya no quedan unidades pendientes.");
            }

            $lineas[$itemId] = [
                'item' => $itemsPorId->get($itemId),
                'cantidad_aceptada' => $solicitada['cantidad_aceptada'],
                'cantidad_danada' => $solicitada['cantidad_danada'],
                'cantidad_incorrecta' => $solicitada['cantidad_incorrecta'],
                'cantidad_faltante_declarada' => $solicitada['cantidad_faltante_declarada'],
                'cantidad_recibida' => $solicitada['cantidad_recibida'],
                'cierra_item' => $solicitada['cierra_item'],
                'observacion' => $solicitada['observacion'],
            ];
        }

        return $lineas;
    }

    /**
     * @param Collection<int, CompraItem> $itemsPorId
     * @return array<int, ColoresProducto>
     */
    private function bloquearVariantes(Collection $itemsPorId, array $ids): array
    {
        $candidatos = [];
        foreach ($ids as $id) {
            $item = $itemsPorId->get($id);
            if (!$item) {
                throw new CompraRecepcionException("El compra_item {$id} no pertenece a la compra.");
            }

            $productoId = (int) ($item->producto_id ?? 0);
            $colorId = (int) ($item->colores_id ?? 0);
            $talla = trim((string) ($item->talla ?? ''));
            if ($productoId <= 0 || $colorId <= 0 || $talla === '') {
                throw new CompraRecepcionException("El item {$id} no tiene una variante válida.");
            }

            $candidatos[] = [
                'item_id' => $id,
                'producto_id' => $productoId,
                'color_id' => $colorId,
                'talla' => $talla,
            ];
        }

        usort($candidatos, static function (array $left, array $right): int {
            return [$left['producto_id'], $left['color_id'], $left['talla'], $left['item_id']]
                <=> [$right['producto_id'], $right['color_id'], $right['talla'], $right['item_id']];
        });

        $variantes = [];
        foreach ($candidatos as $candidato) {
            $variante = ColoresProducto::query()
                ->where('producto_id', $candidato['producto_id'])
                ->where('colores_id', $candidato['color_id'])
                ->where('talla_por_color', $candidato['talla'])
                ->lockForUpdate()
                ->first();

            if (!$variante) {
                throw new CompraRecepcionException("No existe la variante del item {$candidato['item_id']}.");
            }

            $variantes[$candidato['item_id']] = $variante;
        }

        return $variantes;
    }

    /**
     * @param array<int, array{item:CompraItem, cantidad_aceptada:int, cantidad_danada:int, cantidad_incorrecta:int, cantidad_faltante_declarada:int, cantidad_recibida:int, cierra_item:bool, observacion:?string}> $lineas
     * @param array<int, ColoresProducto> $variantes
     */
    private function validarVariantesAceptadas(array $lineas, array $variantes): void
    {
        foreach ($lineas as $linea) {
            if ($linea['cantidad_aceptada'] > 0 && !isset($variantes[(int) $linea['item']->id])) {
                throw new CompraRecepcionException("No existe la variante del item {$linea['item']->id}.");
            }
        }
    }

    /**
     * @param array<int, array{cantidad:int, recibida:int, aceptada:int, danada:int, incorrecta:int, faltante:int, pendiente:int, tiene_recepcion:bool, cerrada:bool}> $contabilizacion
     */
    private function determinarEstado(array $contabilizacion): string
    {
        $hayPendiente = false;
        $hayIncidencia = false;

        foreach ($contabilizacion as $datos) {
            if ($datos['pendiente'] > 0) {
                $hayPendiente = true;
            }

            if ($datos['danada'] > 0 || $datos['incorrecta'] > 0 || $datos['faltante'] > 0) {
                $hayIncidencia = true;
            }
        }

        if ($hayPendiente) {
            return 'RECEPCION_PARCIAL';
        }

        return $hayIncidencia ? 'CON_INCIDENCIAS' : 'RECIBIDA';
    }

    private function enteroNoNegativo(mixed $value, string $campo, int $itemId): int
    {
        if (is_int($value)) {
            $entero = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $entero = (int) $value;
        } else {
            throw new CompraRecepcionException("{$campo} del item {$itemId} debe ser un entero no negativo.");
        }

        if ($entero < 0) {
            throw new CompraRecepcionException("{$campo} del item {$itemId} debe ser un entero no negativo.");
        }

        return $entero;
    }

    private function booleanoEstricto(mixed $value, int $itemId): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        if ($value === 1 || $value === '1') {
            return true;
        }

        throw new CompraRecepcionException("cierra_item del item {$itemId} debe ser booleano.");
    }
}
