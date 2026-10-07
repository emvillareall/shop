<?php

namespace App\Services\Compras;

use App\Models\ColoresProducto;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\CompraRecepcion;
use App\Models\CompraRecepcionItem;
use App\Models\ProveedorReclamo;
use App\Models\ProveedorReclamoEvento;
use App\Models\ProveedorReclamoItem;
use App\Models\ProveedorReclamoReposicion;
use App\Models\ProveedorReclamoResolucion;
use App\Models\User;
use App\Services\Inventario\InventarioService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProveedorReclamoService
{
    public function __construct(
        private readonly InventarioService $inventarioService
    ) {}

    /**
     * Create an empty-in-the-database claim only after all requested lines
     * have been validated and locked. A claim without lines is never persisted.
     *
     * @param array<int, array{compra_recepcion_item_id:mixed, tipo_incidencia:mixed, cantidad_reclamada:mixed, observacion?:mixed}> $lineas
     */
    public function crearBorrador(
        Compra $compra,
        User $actor,
        array $lineas,
        ?string $referenciaProveedor = null,
        ?string $observacion = null
    ): ProveedorReclamo {
        $actorId = $this->actorId($actor);
        $compraId = $this->modelId($compra, 'La compra indicada no es válida.');
        $lineasNormalizadas = $this->normalizarLineas($lineas);
        $referenciaProveedor = $this->textoOpcional($referenciaProveedor, 'referencia_proveedor');
        $observacion = $this->textoOpcional($observacion, 'observacion');

        return DB::transaction(function () use (
            $actorId,
            $compraId,
            $lineasNormalizadas,
            $referenciaProveedor,
            $observacion
        ): ProveedorReclamo {
            $compraBloqueada = Compra::query()
                ->whereKey($compraId)
                ->lockForUpdate()
                ->first();

            if (!$compraBloqueada) {
                throw new ProveedorReclamoException('La compra indicada no existe.');
            }

            $itemsBloqueados = $this->bloquearRecepcionItems(
                array_column($lineasNormalizadas, 'compra_recepcion_item_id')
            );

            $lineasValidadas = $this->validarSaldoYCompra(
                $compraBloqueada,
                $lineasNormalizadas,
                $itemsBloqueados
            );

            $reclamo = ProveedorReclamo::query()->create([
                'compra_id' => $compraId,
                'creado_por' => $actorId,
                'estado' => ProveedorReclamo::ESTADO_BORRADOR,
                'referencia_proveedor' => $referenciaProveedor,
                'observacion' => $observacion,
            ]);

            $this->registrarEvento(
                $reclamo,
                ProveedorReclamoEvento::ACCION_RECLAMO_CREADO,
                $actorId,
                null,
                ProveedorReclamo::ESTADO_BORRADOR,
                null,
                $observacion
            );

            foreach ($lineasValidadas as $linea) {
                $item = ProveedorReclamoItem::query()->create([
                    'proveedor_reclamo_id' => $reclamo->id,
                    'compra_recepcion_item_id' => $linea['compra_recepcion_item_id'],
                    'tipo_incidencia' => $linea['tipo_incidencia'],
                    'cantidad_reclamada' => $linea['cantidad_reclamada'],
                    'estado' => ProveedorReclamoItem::ESTADO_PENDIENTE,
                    'observacion' => $linea['observacion'],
                ]);

                $this->registrarEvento(
                    $reclamo,
                    ProveedorReclamoEvento::ACCION_LINEA_AGREGADA,
                    $actorId,
                    null,
                    ProveedorReclamoItem::ESTADO_PENDIENTE,
                    $item->id,
                    $linea['observacion']
                );
            }

            return $reclamo->fresh(['items', 'eventos']);
        }, 3);
    }

    /**
     * Add one partial-claim line to an existing draft.
     *
     * @param array{compra_recepcion_item_id:mixed, tipo_incidencia:mixed, cantidad_reclamada:mixed, observacion?:mixed} $linea
     */
    public function agregarLinea(
        ProveedorReclamo $reclamo,
        User $actor,
        array $linea
    ): ProveedorReclamoItem {
        $actorId = $this->actorId($actor);
        $reclamoId = $this->modelId($reclamo, 'El reclamo indicado no es válido.');
        $lineasNormalizadas = $this->normalizarLineas([$linea]);
        $lineaNormalizada = $lineasNormalizadas[0];

        return DB::transaction(function () use (
            $actorId,
            $reclamoId,
            $lineaNormalizada
        ): ProveedorReclamoItem {
            [$compra, $reclamoBloqueado] = $this->bloquearReclamoConCompra($reclamoId);
            $this->validarEstadoBorrador($reclamoBloqueado);

            $itemsBloqueados = $this->bloquearRecepcionItems([
                $lineaNormalizada['compra_recepcion_item_id'],
            ]);
            $lineasValidadas = $this->validarSaldoYCompra(
                $compra,
                [$lineaNormalizada],
                $itemsBloqueados
            );

            $lineaCreada = ProveedorReclamoItem::query()->create([
                'proveedor_reclamo_id' => $reclamoBloqueado->id,
                'compra_recepcion_item_id' => $lineasValidadas[0]['compra_recepcion_item_id'],
                'tipo_incidencia' => $lineasValidadas[0]['tipo_incidencia'],
                'cantidad_reclamada' => $lineasValidadas[0]['cantidad_reclamada'],
                'estado' => ProveedorReclamoItem::ESTADO_PENDIENTE,
                'observacion' => $lineasValidadas[0]['observacion'],
            ]);

            $this->registrarEvento(
                $reclamoBloqueado,
                ProveedorReclamoEvento::ACCION_LINEA_AGREGADA,
                $actorId,
                null,
                ProveedorReclamoItem::ESTADO_PENDIENTE,
                $lineaCreada->id,
                $lineasValidadas[0]['observacion']
            );

            return $lineaCreada->fresh();
        }, 3);
    }

    public function cantidadReclamable(
        CompraRecepcionItem|int $item,
        string $tipoIncidencia
    ): int {
        $itemModel = $this->recepcionItem($item);
        $tipo = $this->tipoIncidencia($tipoIncidencia);
        $incidenciaTotal = $this->incidenciaTotal($itemModel, $tipo);

        $consumido = (int) ProveedorReclamoItem::query()
            ->join(
                'proveedor_reclamos',
                'proveedor_reclamos.id',
                '=',
                'proveedor_reclamo_items.proveedor_reclamo_id'
            )
            ->where('proveedor_reclamo_items.compra_recepcion_item_id', $itemModel->id)
            ->where('proveedor_reclamo_items.tipo_incidencia', $tipo)
            ->where('proveedor_reclamo_items.estado', '!=', ProveedorReclamoItem::ESTADO_CANCELADO)
            ->where('proveedor_reclamos.estado', '!=', ProveedorReclamo::ESTADO_CANCELADO)
            ->sum('proveedor_reclamo_items.cantidad_reclamada');

        if ($incidenciaTotal < 0 || $consumido < 0) {
            throw new ProveedorReclamoException('Los datos de incidencia contienen cantidades negativas.');
        }

        $saldo = $incidenciaTotal - $consumido;
        if ($saldo < 0) {
            throw new ProveedorReclamoException(
                "La incidencia {$itemModel->id} tiene más cantidad reclamada que cantidad registrada."
            );
        }

        return $saldo;
    }

    /**
     * @return array{DANADA:int, INCORRECTA:int, FALTANTE:int}
     */
    public function saldosReclamables(CompraRecepcionItem|int $item): array
    {
        $itemModel = $this->recepcionItem($item);

        return [
            ProveedorReclamoItem::TIPO_DANADA => $this->cantidadReclamable(
                $itemModel,
                ProveedorReclamoItem::TIPO_DANADA
            ),
            ProveedorReclamoItem::TIPO_INCORRECTA => $this->cantidadReclamable(
                $itemModel,
                ProveedorReclamoItem::TIPO_INCORRECTA
            ),
            ProveedorReclamoItem::TIPO_FALTANTE => $this->cantidadReclamable(
                $itemModel,
                ProveedorReclamoItem::TIPO_FALTANTE
            ),
        ];
    }

    /**
     * Read-only data for the purchase detail. Business balances are calculated
     * here so Blade only renders the result and never reimplements the rules.
     *
     * @return array{incidencias_reclamables:Collection<int,array<string,mixed>>,reclamos:Collection<int,array<string,mixed>>}
     */
    public function resumenParaCompra(Compra $compra): array
    {
        $empty = [
            'incidencias_reclamables' => collect(),
            'reclamos' => collect(),
        ];

        if (!Schema::hasTable('proveedor_reclamos')) {
            return $empty;
        }

        $compra->loadMissing('proveedor');

        $recepcionItems = CompraRecepcionItem::query()
            ->with(['compraItem.producto', 'compraItem.color', 'compraRecepcion'])
            ->whereHas('compraItem', fn ($query) => $query->where('compra_id', $compra->id))
            ->whereHas('compraRecepcion', fn ($query) => $query->where('compra_id', $compra->id))
            ->orderBy('id')
            ->get();

        $incidencias = collect();
        foreach ($recepcionItems as $recepcionItem) {
            $compraItem = $recepcionItem->compraItem;
            $tiposDisponibles = collect();
            foreach (ProveedorReclamoItem::TIPOS_INCIDENCIA as $tipo) {
                $saldo = $this->cantidadReclamable($recepcionItem, $tipo);
                if ($saldo <= 0) {
                    continue;
                }

                $tiposDisponibles->push([
                    'tipo_incidencia' => $tipo,
                    'cantidad_reclamable' => $saldo,
                ]);
            }

            if ($tiposDisponibles->isNotEmpty()) {
                $incidencias->push([
                    'compra_recepcion_item_id' => (int) $recepcionItem->id,
                    'producto' => $compraItem?->producto?->descripcion_producto
                        ?? ('Producto #' . ($compraItem?->producto_id ?? '—')),
                    'color' => $compraItem?->color?->nombre_color
                        ?? ($compraItem?->colores_id ? 'Color #' . $compraItem->colores_id : '—'),
                    'talla' => $compraItem?->talla ?: '—',
                    'tipos_disponibles' => $tiposDisponibles,
                ]);
            }
        }

        $reclamos = ProveedorReclamo::query()
            ->where('compra_id', $compra->id)
            ->with($this->relacionesLecturaReclamo())
            ->orderByDesc('id')
            ->get()
            ->map(fn (ProveedorReclamo $reclamo): array => $this->formatearResumenReclamo($reclamo));

        return [
            'incidencias_reclamables' => $incidencias,
            'reclamos' => $reclamos,
        ];
    }

    /**
     * Read-only detail used by the administrative claim screen.
     *
     * @return array<string,mixed>|null
     */
    public function detalleParaReclamo(ProveedorReclamo|int $reclamo): ?array
    {
        if (!Schema::hasTable('proveedor_reclamos')) {
            return null;
        }

        $reclamoId = $reclamo instanceof ProveedorReclamo
            ? (int) $reclamo->getKey()
            : (int) $reclamo;

        if ($reclamoId <= 0) {
            return null;
        }

        $modelo = ProveedorReclamo::query()
            ->with($this->relacionesLecturaReclamo())
            ->find($reclamoId);

        return $modelo ? $this->formatearResumenReclamo($modelo) : null;
    }

    /** @return array<int,string> */
    private function relacionesLecturaReclamo(): array
    {
        $relations = [
            'compra.proveedor',
            'creadoPor',
            'items.compraRecepcionItem.compraItem.producto',
            'items.compraRecepcionItem.compraItem.color',
            'items.resoluciones.resueltoPor',
            'eventos.usuario',
        ];

        if (Schema::hasTable('proveedor_reclamo_reposiciones')) {
            $relations[] = 'items.resoluciones.reposiciones.recibidoPor';
        }

        return $relations;
    }

    /** @return array<string,mixed> */
    private function formatearResumenReclamo(ProveedorReclamo $reclamo): array
    {
        $lineas = $reclamo->items->map(function (ProveedorReclamoItem $linea): array {
            $resolucionesActivas = $linea->resoluciones
                ->where('estado', '!=', ProveedorReclamoResolucion::ESTADO_CANCELADA)
                ->values();
            $cantidadAsignada = (int) $resolucionesActivas->sum('cantidad');
            $cantidadFinalizada = $this->cantidadFinalizadaBloqueada($linea, $resolucionesActivas);
            $compraItem = $linea->compraRecepcionItem?->compraItem;

            $resoluciones = $linea->resoluciones->map(function (ProveedorReclamoResolucion $resolucion): array {
                $cantidadAceptada = (int) $resolucion->reposiciones->sum('cantidad_aceptada');

                return [
                    'modelo' => $resolucion,
                    'cantidad_aceptada' => $cantidadAceptada,
                    'cantidad_pendiente_fisica' => $resolucion->tipo_resolucion === ProveedorReclamoResolucion::TIPO_REPOSICION
                        ? max(0, (int) $resolucion->cantidad - $cantidadAceptada)
                        : 0,
                    'recepciones' => $resolucion->reposiciones->sortByDesc('fecha_recepcion')->values(),
                ];
            })->values();

            return [
                'modelo' => $linea,
                'producto' => $compraItem?->producto?->descripcion_producto
                    ?? ('Producto #' . ($compraItem?->producto_id ?? '—')),
                'color' => $compraItem?->color?->nombre_color
                    ?? ($compraItem?->colores_id ? 'Color #' . $compraItem->colores_id : '—'),
                'talla' => $compraItem?->talla ?: '—',
                'cantidad_reclamada' => (int) $linea->cantidad_reclamada,
                'cantidad_asignada' => $cantidadAsignada,
                'cantidad_pendiente_asignar' => max(0, (int) $linea->cantidad_reclamada - $cantidadAsignada),
                'cantidad_finalizada' => $cantidadFinalizada,
                'cantidad_pendiente_finalizar' => max(0, (int) $linea->cantidad_reclamada - $cantidadFinalizada),
                'resoluciones' => $resoluciones,
            ];
        })->values();

        return [
            'modelo' => $reclamo,
            'lineas' => $lineas,
            'eventos' => $reclamo->eventos->sortBy('fecha_evento')->values(),
        ];
    }

    public function cancelarLinea(
        ProveedorReclamoItem $linea,
        User $actor,
        string $motivo
    ): ProveedorReclamoItem {
        $actorId = $this->actorId($actor);
        $lineaId = $this->modelId($linea, 'La línea de reclamo indicada no es válida.');
        $motivo = $this->textoObligatorio($motivo, 'motivo');

        return DB::transaction(function () use ($actorId, $lineaId, $motivo): ProveedorReclamoItem {
            $lineaInicial = ProveedorReclamoItem::query()->whereKey($lineaId)->first();
            if (!$lineaInicial) {
                throw new ProveedorReclamoException('La línea de reclamo no existe.');
            }

            [$compra, $reclamo] = $this->bloquearReclamoConCompra(
                (int) $lineaInicial->proveedor_reclamo_id
            );
            $lineaBloqueada = ProveedorReclamoItem::query()
                ->whereKey($lineaId)
                ->lockForUpdate()
                ->first();

            if (!$lineaBloqueada) {
                throw new ProveedorReclamoException('La línea de reclamo no existe.');
            }

            $this->bloquearRecepcionItems([(int) $lineaBloqueada->compra_recepcion_item_id]);

            if ($lineaBloqueada->estado === ProveedorReclamoItem::ESTADO_CANCELADO) {
                return $lineaBloqueada->fresh();
            }

            if ($reclamo->estado === ProveedorReclamo::ESTADO_CANCELADO) {
                return $lineaBloqueada->fresh();
            }

            if ($lineaBloqueada->estado === ProveedorReclamoItem::ESTADO_RESUELTO) {
                throw new ProveedorReclamoException('No se puede cancelar una línea ya resuelta.');
            }

            $estadoAnterior = (string) $lineaBloqueada->estado;
            $lineaBloqueada->update([
                'estado' => ProveedorReclamoItem::ESTADO_CANCELADO,
            ]);

            $this->registrarEvento(
                $reclamo,
                ProveedorReclamoEvento::ACCION_LINEA_CANCELADA,
                $actorId,
                $estadoAnterior,
                ProveedorReclamoItem::ESTADO_CANCELADO,
                $lineaBloqueada->id,
                $motivo
            );

            return $lineaBloqueada->fresh();
        }, 3);
    }

    public function cancelarReclamo(
        ProveedorReclamo $reclamo,
        User $actor,
        string $motivo
    ): ProveedorReclamo {
        $actorId = $this->actorId($actor);
        $reclamoId = $this->modelId($reclamo, 'El reclamo indicado no es válido.');
        $motivo = $this->textoObligatorio($motivo, 'motivo');

        return DB::transaction(function () use ($actorId, $reclamoId, $motivo): ProveedorReclamo {
            [$compra, $reclamoBloqueado] = $this->bloquearReclamoConCompra($reclamoId);
            $itemsIds = ProveedorReclamoItem::query()
                ->where('proveedor_reclamo_id', $reclamoBloqueado->id)
                ->orderBy('compra_recepcion_item_id')
                ->pluck('compra_recepcion_item_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->bloquearRecepcionItems($itemsIds);

            if ($reclamoBloqueado->estado === ProveedorReclamo::ESTADO_CANCELADO) {
                return $reclamoBloqueado->fresh(['items', 'eventos']);
            }

            if ($reclamoBloqueado->estado === ProveedorReclamo::ESTADO_RESUELTO) {
                throw new ProveedorReclamoException('No se puede cancelar un reclamo ya resuelto.');
            }

            $estadoAnterior = (string) $reclamoBloqueado->estado;
            $reclamoBloqueado->update([
                'estado' => ProveedorReclamo::ESTADO_CANCELADO,
            ]);

            $this->registrarEvento(
                $reclamoBloqueado,
                ProveedorReclamoEvento::ACCION_RECLAMO_CANCELADO,
                $actorId,
                $estadoAnterior,
                ProveedorReclamo::ESTADO_CANCELADO,
                null,
                $motivo
            );

            return $reclamoBloqueado->fresh(['items', 'eventos']);
        }, 3);
    }

    public function marcarEnviado(
        ProveedorReclamo $reclamo,
        User $actor,
        ?string $observacion = null
    ): ProveedorReclamo {
        return $this->cambiarEstadoCabecera(
            $reclamo,
            $actor,
            ProveedorReclamo::ESTADO_BORRADOR,
            ProveedorReclamo::ESTADO_ENVIADO,
            ProveedorReclamoEvento::ACCION_RECLAMO_ENVIADO,
            $observacion,
            true
        );
    }

    public function marcarEnGestion(
        ProveedorReclamo $reclamo,
        User $actor,
        ?string $observacion = null
    ): ProveedorReclamo {
        return $this->cambiarEstadoCabecera(
            $reclamo,
            $actor,
            ProveedorReclamo::ESTADO_ENVIADO,
            ProveedorReclamo::ESTADO_EN_GESTION,
            ProveedorReclamoEvento::ACCION_RECLAMO_EN_GESTION,
            $observacion,
            false
        );
    }

    /**
     * Register a commercial resolution without creating inventory effects.
     *
     * @param mixed $cantidad Resolution quantity; it must be an integer > 0.
     * @param mixed $importe Optional documentary amount.
     */
    public function registrarResolucion(
        ProveedorReclamoItem $item,
        User $actor,
        string $tipoResolucion,
        mixed $cantidad,
        ?string $referencia = null,
        ?string $observacion = null,
        mixed $importe = null,
        ?string $moneda = null
    ): ProveedorReclamoResolucion {
        $actorId = $this->actorId($actor);
        $itemId = $this->modelId($item, 'La línea de reclamo indicada no es válida.');
        $tipo = $this->tipoResolucion($tipoResolucion);
        $cantidad = $this->enteroPositivo($cantidad, 'cantidad');
        $referencia = $this->textoOpcional($referencia, 'referencia');
        $observacion = $this->textoOpcional($observacion, 'observacion');
        $importe = $this->importeOpcional($importe);
        $moneda = $this->monedaOpcional($moneda);

        if ($tipo === ProveedorReclamoResolucion::TIPO_OTRO && $observacion === null) {
            throw new ProveedorReclamoException(
                'La observación es obligatoria para una resolución de tipo OTRO.'
            );
        }

        return DB::transaction(function () use (
            $actorId,
            $itemId,
            $tipo,
            $cantidad,
            $referencia,
            $observacion,
            $importe,
            $moneda
        ): ProveedorReclamoResolucion {
            [$reclamo, $linea] = $this->bloquearLineaConReclamo($itemId);
            $this->validarCabeceraParaResolucion($reclamo);
            $this->validarLineaParaNuevaResolucion($linea);

            $saldo = $this->cantidadPorResolverBloqueada($linea);
            if ($cantidad > $saldo) {
                throw new ProveedorReclamoException(
                    "La resolución solicita {$cantidad}, pero el saldo por resolver es {$saldo}."
                );
            }

            $this->asegurarReclamoEnGestion($reclamo, $actorId);
            $this->asegurarLineaEnGestion($reclamo, $linea, $actorId, $observacion);

            $resolucion = ProveedorReclamoResolucion::query()->create([
                'proveedor_reclamo_item_id' => $linea->id,
                'tipo_resolucion' => $tipo,
                'cantidad' => $cantidad,
                'estado' => ProveedorReclamoResolucion::ESTADO_PENDIENTE,
                'referencia' => $referencia,
                'observacion' => $observacion,
                'resuelto_por' => null,
                'importe' => $importe,
                'moneda' => $moneda,
            ]);

            $this->registrarEvento(
                $reclamo,
                ProveedorReclamoEvento::ACCION_RESOLUCION_REGISTRADA,
                $actorId,
                null,
                ProveedorReclamoResolucion::ESTADO_PENDIENTE,
                $linea->id,
                $observacion,
                $resolucion->id
            );

            return $resolucion->fresh();
        }, 3);
    }

    /**
     * Register one physical arrival for a REPOSICION resolution.
     *
     * The idempotency key is mandatory because a physical arrival is allowed
     * to be retried, while two different arrivals may legitimately contain
     * identical quantities.
     */
    public function recibirReposicion(
        ProveedorReclamoResolucion $resolucion,
        User $actor,
        mixed $cantidadAceptada,
        mixed $cantidadDanada,
        mixed $cantidadIncorrecta,
        mixed $idempotencyKey,
        ?string $observacion = null,
        ?DateTimeInterface $fechaRecepcion = null
    ): ProveedorReclamoReposicion {
        $actorId = $this->actorId($actor);
        $resolucionId = $this->modelId($resolucion, 'La resolución indicada no es válida.');
        $cantidadAceptada = $this->enteroNoNegativo($cantidadAceptada, 'cantidad_aceptada');
        $cantidadDanada = $this->enteroNoNegativo($cantidadDanada, 'cantidad_danada');
        $cantidadIncorrecta = $this->enteroNoNegativo($cantidadIncorrecta, 'cantidad_incorrecta');
        $cantidadRecibida = $cantidadAceptada + $cantidadDanada + $cantidadIncorrecta;
        $idempotencyKey = $this->idempotencyKey($idempotencyKey);
        $observacion = $this->textoOpcional($observacion, 'observacion');

        if ($cantidadRecibida <= 0) {
            throw new ProveedorReclamoException(
                'La recepción de reposición debe contener al menos una unidad.'
            );
        }

        return DB::transaction(function () use (
            $actorId,
            $resolucionId,
            $cantidadAceptada,
            $cantidadDanada,
            $cantidadIncorrecta,
            $cantidadRecibida,
            $idempotencyKey,
            $observacion,
            $fechaRecepcion
        ): ProveedorReclamoReposicion {
            [$reclamo, $linea, $resolucionBloqueada] = $this->bloquearResolucionConContexto($resolucionId);

            if ($reclamo->estado === ProveedorReclamo::ESTADO_CANCELADO) {
                throw new ProveedorReclamoException('No se puede recibir una reposición de un reclamo cancelado.');
            }

            if ($linea->estado === ProveedorReclamoItem::ESTADO_CANCELADO) {
                throw new ProveedorReclamoException('No se puede recibir una reposición de una línea cancelada.');
            }

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_CANCELADA) {
                throw new ProveedorReclamoException('No se puede recibir una reposición cancelada.');
            }

            $reposicionExistente = ProveedorReclamoReposicion::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($reposicionExistente) {
                if ((int) $reposicionExistente->proveedor_reclamo_resolucion_id !== $resolucionBloqueada->id) {
                    throw new ProveedorReclamoException(
                        'La idempotency_key ya pertenece a otra reposición.'
                    );
                }

                return $reposicionExistente->fresh(['resolucion', 'recibidoPor']);
            }

            $this->validarCabeceraParaResolucion($reclamo);
            $this->validarLineaParaRecepcionReposicion($linea);

            if ($resolucionBloqueada->tipo_resolucion !== ProveedorReclamoResolucion::TIPO_REPOSICION) {
                throw new ProveedorReclamoException(
                    'Solo las resoluciones REPOSICION pueden recibir llegadas físicas.'
                );
            }

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_RESUELTA) {
                throw new ProveedorReclamoException('La reposición ya está completamente resuelta.');
            }

            $origen = $this->bloquearOrigenReposicion($reclamo, $linea);
            $variante = $this->bloquearVarianteReposicion($origen);
            $cantidadAceptadaAcumulada = (int) ProveedorReclamoReposicion::query()
                ->where('proveedor_reclamo_resolucion_id', $resolucionBloqueada->id)
                ->lockForUpdate()
                ->sum('cantidad_aceptada');

            if ($cantidadAceptadaAcumulada + $cantidadAceptada > (int) $resolucionBloqueada->cantidad) {
                throw new ProveedorReclamoException(
                    'La recepción aceptada supera la cantidad prometida por la resolución.'
                );
            }

            $this->asegurarReclamoEnGestion($reclamo, $actorId);
            $this->asegurarLineaEnGestion($reclamo, $linea, $actorId, $observacion);

            $estadoResolucionAnterior = (string) $resolucionBloqueada->estado;
            if ($estadoResolucionAnterior === ProveedorReclamoResolucion::ESTADO_PENDIENTE) {
                $resolucionBloqueada->update([
                    'estado' => ProveedorReclamoResolucion::ESTADO_EN_GESTION,
                ]);

                $this->registrarEvento(
                    $reclamo,
                    ProveedorReclamoEvento::ACCION_RESOLUCION_EN_GESTION,
                    $actorId,
                    $estadoResolucionAnterior,
                    ProveedorReclamoResolucion::ESTADO_EN_GESTION,
                    $linea->id,
                    $observacion,
                    $resolucionBloqueada->id
                );
            }

            $reposicion = ProveedorReclamoReposicion::query()->create([
                'proveedor_reclamo_resolucion_id' => $resolucionBloqueada->id,
                'recibido_por' => $actorId,
                'idempotency_key' => $idempotencyKey,
                'cantidad_recibida' => $cantidadRecibida,
                'cantidad_aceptada' => $cantidadAceptada,
                'cantidad_danada' => $cantidadDanada,
                'cantidad_incorrecta' => $cantidadIncorrecta,
                'observacion' => $observacion,
                'fecha_recepcion' => $fechaRecepcion
                    ? Carbon::instance($fechaRecepcion)
                    : now(),
            ]);

            if ($cantidadAceptada > 0) {
                $this->inventarioService->registrarEntradaVariante(
                    (int) $variante->producto_id,
                    (int) $variante->colores_id,
                    (string) $variante->talla_por_color,
                    $cantidadAceptada,
                    null,
                    null,
                    'Entrada por reposición aceptada del proveedor',
                    [
                        'proveedor_reclamo_reposicion_id' => (int) $reposicion->id,
                    ],
                    'ENTRADA_REPOSICION_PROVEEDOR',
                    $actorId
                );
            }

            $this->registrarEvento(
                $reclamo,
                ProveedorReclamoEvento::ACCION_REPOSICION_RECIBIDA,
                $actorId,
                $estadoResolucionAnterior,
                (string) $resolucionBloqueada->estado,
                $linea->id,
                $observacion,
                $resolucionBloqueada->id
            );

            $aceptadaFinal = $cantidadAceptadaAcumulada + $cantidadAceptada;
            if ($aceptadaFinal === (int) $resolucionBloqueada->cantidad) {
                $resolucionBloqueada->update([
                    'estado' => ProveedorReclamoResolucion::ESTADO_RESUELTA,
                    'resuelto_por' => $actorId,
                ]);

                $this->registrarEvento(
                    $reclamo,
                    ProveedorReclamoEvento::ACCION_RESOLUCION_RESUELTA,
                    $actorId,
                    ProveedorReclamoResolucion::ESTADO_EN_GESTION,
                    ProveedorReclamoResolucion::ESTADO_RESUELTA,
                    $linea->id,
                    $observacion,
                    $resolucionBloqueada->id
                );
            }

            $this->sincronizarEstadosPorResoluciones($reclamo, $linea, $actorId, $observacion);

            return $reposicion->fresh(['resolucion', 'recibidoPor']);
        }, 3);
    }

    /**
     * Return the quantity of a claim line not covered by active resolutions.
     * CANCELADA resolutions do not consume this balance.
     */
    public function cantidadPorResolver(ProveedorReclamoItem|int $item): int
    {
        $linea = $this->reclamoItem($item);

        return $this->cantidadPorResolverBloqueada($linea);
    }

    public function marcarResolucionEnGestion(
        ProveedorReclamoResolucion $resolucion,
        User $actor,
        ?string $observacion = null
    ): ProveedorReclamoResolucion {
        $actorId = $this->actorId($actor);
        $resolucionId = $this->modelId($resolucion, 'La resolución indicada no es válida.');
        $observacion = $this->textoOpcional($observacion, 'observacion');

        return DB::transaction(function () use (
            $actorId,
            $resolucionId,
            $observacion
        ): ProveedorReclamoResolucion {
            [$reclamo, $linea, $resolucionBloqueada] = $this->bloquearResolucionConContexto($resolucionId);
            $this->validarCabeceraParaResolucion($reclamo);

            if ($linea->estado === ProveedorReclamoItem::ESTADO_CANCELADO) {
                throw new ProveedorReclamoException('No se puede gestionar una línea cancelada.');
            }

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_CANCELADA) {
                throw new ProveedorReclamoException('No se puede gestionar una resolución cancelada.');
            }

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_RESUELTA) {
                return $resolucionBloqueada->fresh();
            }

            if ($resolucionBloqueada->estado !== ProveedorReclamoResolucion::ESTADO_PENDIENTE) {
                throw new ProveedorReclamoException('La resolución no está pendiente de gestión.');
            }

            $this->asegurarReclamoEnGestion($reclamo, $actorId);
            $this->asegurarLineaEnGestion($reclamo, $linea, $actorId, $observacion);
            $resolucionBloqueada->update([
                'estado' => ProveedorReclamoResolucion::ESTADO_EN_GESTION,
            ]);

            $this->registrarEvento(
                $reclamo,
                ProveedorReclamoEvento::ACCION_RESOLUCION_EN_GESTION,
                $actorId,
                ProveedorReclamoResolucion::ESTADO_PENDIENTE,
                ProveedorReclamoResolucion::ESTADO_EN_GESTION,
                $linea->id,
                $observacion,
                $resolucionBloqueada->id
            );

            return $resolucionBloqueada->fresh();
        }, 3);
    }

    /**
     * Confirm a non-logistical resolution. REPOSICION remains pending until
     * its physical accepted quantity is received through recibirReposicion().
     */
    public function marcarResolucionResuelta(
        ProveedorReclamoResolucion $resolucion,
        User $actor,
        ?string $observacion = null
    ): ProveedorReclamoResolucion {
        $actorId = $this->actorId($actor);
        $resolucionId = $this->modelId($resolucion, 'La resolución indicada no es válida.');
        $observacion = $this->textoOpcional($observacion, 'observacion');

        return DB::transaction(function () use (
            $actorId,
            $resolucionId,
            $observacion
        ): ProveedorReclamoResolucion {
            [$reclamo, $linea, $resolucionBloqueada] = $this->bloquearResolucionConContexto($resolucionId);
            $this->validarCabeceraParaResolucion($reclamo);

            if ($linea->estado === ProveedorReclamoItem::ESTADO_CANCELADO) {
                throw new ProveedorReclamoException('No se puede resolver una línea cancelada.');
            }

            if ($resolucionBloqueada->tipo_resolucion === ProveedorReclamoResolucion::TIPO_REPOSICION) {
                throw new ProveedorReclamoException(
                    'Una REPOSICION solo puede resolverse después de la recepción física en RECLAMOS-1D-B.'
                );
            }

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_CANCELADA) {
                throw new ProveedorReclamoException('No se puede resolver una resolución cancelada.');
            }

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_RESUELTA) {
                return $resolucionBloqueada->fresh();
            }

            if (!in_array($resolucionBloqueada->estado, [
                ProveedorReclamoResolucion::ESTADO_PENDIENTE,
                ProveedorReclamoResolucion::ESTADO_EN_GESTION,
            ], true)) {
                throw new ProveedorReclamoException('La resolución no puede pasar a resuelta desde su estado actual.');
            }

            $this->asegurarReclamoEnGestion($reclamo, $actorId);
            $this->asegurarLineaEnGestion($reclamo, $linea, $actorId, $observacion);
            $estadoAnterior = (string) $resolucionBloqueada->estado;
            $resolucionBloqueada->update([
                'estado' => ProveedorReclamoResolucion::ESTADO_RESUELTA,
                'resuelto_por' => $actorId,
            ]);

            $this->registrarEvento(
                $reclamo,
                ProveedorReclamoEvento::ACCION_RESOLUCION_RESUELTA,
                $actorId,
                $estadoAnterior,
                ProveedorReclamoResolucion::ESTADO_RESUELTA,
                $linea->id,
                $observacion,
                $resolucionBloqueada->id
            );

            $this->sincronizarEstadosPorResoluciones($reclamo, $linea, $actorId, $observacion);

            return $resolucionBloqueada->fresh();
        }, 3);
    }

    public function cancelarResolucion(
        ProveedorReclamoResolucion $resolucion,
        User $actor,
        string $motivo
    ): ProveedorReclamoResolucion {
        $actorId = $this->actorId($actor);
        $resolucionId = $this->modelId($resolucion, 'La resolución indicada no es válida.');
        $motivo = $this->textoObligatorio($motivo, 'motivo');

        return DB::transaction(function () use ($actorId, $resolucionId, $motivo): ProveedorReclamoResolucion {
            [$reclamo, $linea, $resolucionBloqueada] = $this->bloquearResolucionConContexto($resolucionId);

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_CANCELADA) {
                return $resolucionBloqueada->fresh();
            }

            if ($reclamo->estado === ProveedorReclamo::ESTADO_CANCELADO) {
                throw new ProveedorReclamoException('No se puede cancelar una resolución de un reclamo cancelado.');
            }

            if ($linea->estado === ProveedorReclamoItem::ESTADO_CANCELADO) {
                throw new ProveedorReclamoException('No se puede cancelar una resolución de una línea cancelada.');
            }

            if ($resolucionBloqueada->estado === ProveedorReclamoResolucion::ESTADO_RESUELTA) {
                throw new ProveedorReclamoException('No se puede cancelar una resolución ya resuelta.');
            }

            if ($resolucionBloqueada->tipo_resolucion === ProveedorReclamoResolucion::TIPO_REPOSICION) {
                $cantidadAceptada = (int) ProveedorReclamoReposicion::query()
                    ->where('proveedor_reclamo_resolucion_id', $resolucionBloqueada->id)
                    ->lockForUpdate()
                    ->sum('cantidad_aceptada');

                $tieneMovimiento = DB::table('inventario_movimientos')
                    ->whereNotNull('proveedor_reclamo_reposicion_id')
                    ->whereIn(
                        'proveedor_reclamo_reposicion_id',
                        ProveedorReclamoReposicion::query()
                            ->where('proveedor_reclamo_resolucion_id', $resolucionBloqueada->id)
                            ->select('id')
                    )
                    ->lockForUpdate()
                    ->exists();

                if ($cantidadAceptada > 0 || $tieneMovimiento) {
                    throw new ProveedorReclamoException(
                        'No se puede cancelar una REPOSICION que ya produjo entrada de inventario.'
                    );
                }
            }

            $this->asegurarReclamoEnGestion($reclamo, $actorId);
            $estadoAnterior = (string) $resolucionBloqueada->estado;
            $resolucionBloqueada->update([
                'estado' => ProveedorReclamoResolucion::ESTADO_CANCELADA,
            ]);

            $this->registrarEvento(
                $reclamo,
                ProveedorReclamoEvento::ACCION_RESOLUCION_CANCELADA,
                $actorId,
                $estadoAnterior,
                ProveedorReclamoResolucion::ESTADO_CANCELADA,
                $linea->id,
                $motivo,
                $resolucionBloqueada->id
            );

            $this->sincronizarEstadosPorResoluciones($reclamo, $linea, $actorId, $motivo);

            return $resolucionBloqueada->fresh();
        }, 3);
    }

    /**
     * @param array<int, array{compra_recepcion_item_id:int, tipo_incidencia:string, cantidad_reclamada:int, observacion:?string}> $lineas
     * @return array<int, array{compra_recepcion_item_id:int, tipo_incidencia:string, cantidad_reclamada:int, observacion:?string}>
     */
    private function normalizarLineas(array $lineas): array
    {
        if ($lineas === []) {
            throw new ProveedorReclamoException('El reclamo debe contener al menos una línea.');
        }

        $normalizadas = [];
        $claves = [];
        foreach ($lineas as $indice => $linea) {
            if (!is_array($linea)) {
                throw new ProveedorReclamoException("La línea {$indice} no es válida.");
            }

            $itemId = $this->enteroPositivo(
                $linea['compra_recepcion_item_id'] ?? null,
                'compra_recepcion_item_id'
            );
            $tipo = $this->tipoIncidencia($linea['tipo_incidencia'] ?? null);
            $cantidad = $this->enteroPositivo(
                $linea['cantidad_reclamada'] ?? null,
                'cantidad_reclamada'
            );
            $observacion = $this->textoOpcional($linea['observacion'] ?? null, 'observacion');
            $clave = $itemId . ':' . $tipo;

            if (isset($claves[$clave])) {
                throw new ProveedorReclamoException(
                    "La incidencia {$itemId}/{$tipo} está repetida dentro del mismo reclamo."
                );
            }

            $claves[$clave] = true;
            $normalizadas[] = [
                'compra_recepcion_item_id' => $itemId,
                'tipo_incidencia' => $tipo,
                'cantidad_reclamada' => $cantidad,
                'observacion' => $observacion,
            ];
        }

        return $normalizadas;
    }

    /**
     * @param array<int, array{compra_recepcion_item_id:int, tipo_incidencia:string, cantidad_reclamada:int, observacion:?string}> $lineas
     * @return array<int, array{compra_recepcion_item_id:int, tipo_incidencia:string, cantidad_reclamada:int, observacion:?string}>
     */
    private function validarSaldoYCompra(
        Compra $compra,
        array $lineas,
        Collection $itemsBloqueados
    ): array {
        $validadas = [];
        foreach ($lineas as $linea) {
            $item = $itemsBloqueados->get($linea['compra_recepcion_item_id']);
            if (!$item instanceof CompraRecepcionItem) {
                throw new ProveedorReclamoException(
                    "El item de recepción {$linea['compra_recepcion_item_id']} no existe."
                );
            }

            $this->validarPertenenciaCompra($item, $compra);
            $saldo = $this->cantidadReclamable($item, $linea['tipo_incidencia']);
            if ($saldo <= 0) {
                throw new ProveedorReclamoException(
                    "La incidencia {$linea['tipo_incidencia']} del item {$item->id} no tiene saldo reclamable."
                );
            }

            if ($linea['cantidad_reclamada'] > $saldo) {
                throw new ProveedorReclamoException(
                    "La cantidad solicitada para {$linea['tipo_incidencia']} supera el saldo reclamable del item {$item->id}."
                );
            }

            $validadas[] = $linea;
        }

        return $validadas;
    }

    /**
     * @param array<int, int> $ids
     */
    private function bloquearRecepcionItems(array $ids): Collection
    {
        $ids = collect($ids)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($ids === []) {
            throw new ProveedorReclamoException('No hay items de recepción para bloquear.');
        }

        $items = CompraRecepcionItem::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (CompraRecepcionItem $item): int => (int) $item->id);

        if ($items->count() !== count($ids)) {
            $faltantes = array_values(array_diff($ids, $items->keys()->map(fn ($id): int => (int) $id)->all()));
            throw new ProveedorReclamoException(
                'No existen todos los items de recepción solicitados: ' . implode(', ', $faltantes)
            );
        }

        return $items;
    }

    private function validarPertenenciaCompra(CompraRecepcionItem $item, Compra $compra): void
    {
        $item->loadMissing(['compraItem', 'compraRecepcion']);
        $compraItem = $item->compraItem;
        $recepcion = $item->compraRecepcion;

        if (
            !$compraItem
            || (int) $compraItem->compra_id !== (int) $compra->id
            || !$recepcion
            || (int) $recepcion->compra_id !== (int) $compra->id
        ) {
            throw new ProveedorReclamoException(
                "El item de recepción {$item->id} no pertenece a la compra del reclamo."
            );
        }
    }

    private function incidenciaTotal(CompraRecepcionItem $item, string $tipo): int
    {
        return match ($tipo) {
            ProveedorReclamoItem::TIPO_DANADA => (int) $item->cantidad_danada,
            ProveedorReclamoItem::TIPO_INCORRECTA => (int) $item->cantidad_incorrecta,
            ProveedorReclamoItem::TIPO_FALTANTE => (int) $item->cantidad_faltante_declarada,
            default => throw new ProveedorReclamoException("Tipo de incidencia no permitido: {$tipo}."),
        };
    }

    private function cantidadPorResolverBloqueada(ProveedorReclamoItem $linea): int
    {
        $cantidadReclamada = (int) $linea->cantidad_reclamada;
        $cantidadAsignadaActiva = (int) ProveedorReclamoResolucion::query()
            ->where('proveedor_reclamo_item_id', $linea->id)
            ->where('estado', '!=', ProveedorReclamoResolucion::ESTADO_CANCELADA)
            ->sum('cantidad');

        if ($cantidadReclamada < 0 || $cantidadAsignadaActiva < 0) {
            throw new ProveedorReclamoException('Las cantidades de resolución no pueden ser negativas.');
        }

        $saldo = $cantidadReclamada - $cantidadAsignadaActiva;
        if ($saldo < 0) {
            throw new ProveedorReclamoException(
                "La línea {$linea->id} tiene más cantidad asignada que cantidad reclamada."
            );
        }

        return $saldo;
    }

    /**
     * @param Collection<int, ProveedorReclamoResolucion>|null $resolucionesActivas
     */
    private function cantidadFinalizadaBloqueada(
        ProveedorReclamoItem $linea,
        ?Collection $resolucionesActivas = null
    ): int {
        $resolucionesActivas ??= ProveedorReclamoResolucion::query()
            ->where('proveedor_reclamo_item_id', $linea->id)
            ->where('estado', '!=', ProveedorReclamoResolucion::ESTADO_CANCELADA)
            ->get();

        $cantidadFinalizada = 0;
        foreach ($resolucionesActivas as $resolucion) {
            if ($resolucion->tipo_resolucion === ProveedorReclamoResolucion::TIPO_REPOSICION) {
                $cantidadAceptada = (int) ProveedorReclamoReposicion::query()
                    ->where('proveedor_reclamo_resolucion_id', $resolucion->id)
                    ->sum('cantidad_aceptada');

                if ($cantidadAceptada > (int) $resolucion->cantidad) {
                    throw new ProveedorReclamoException(
                        "La reposición {$resolucion->id} tiene más unidades aceptadas que prometidas."
                    );
                }

                $cantidadFinalizada += $cantidadAceptada;
                continue;
            }

            if ($resolucion->estado === ProveedorReclamoResolucion::ESTADO_RESUELTA) {
                $cantidadFinalizada += (int) $resolucion->cantidad;
            }
        }

        if ($cantidadFinalizada > (int) $linea->cantidad_reclamada) {
            throw new ProveedorReclamoException(
                "La línea {$linea->id} tiene más cantidad finalizada que cantidad reclamada."
            );
        }

        return $cantidadFinalizada;
    }

    private function validarCabeceraParaResolucion(ProveedorReclamo $reclamo): void
    {
        if ($reclamo->estado === ProveedorReclamo::ESTADO_CANCELADO) {
            throw new ProveedorReclamoException('No se puede resolver un reclamo cancelado.');
        }

        if ($reclamo->estado === ProveedorReclamo::ESTADO_BORRADOR) {
            throw new ProveedorReclamoException(
                'El reclamo debe estar enviado antes de registrar una resolución.'
            );
        }

        if ($reclamo->estado === ProveedorReclamo::ESTADO_RESUELTO) {
            throw new ProveedorReclamoException('No se puede modificar un reclamo ya resuelto.');
        }

        if (!in_array($reclamo->estado, [
            ProveedorReclamo::ESTADO_ENVIADO,
            ProveedorReclamo::ESTADO_EN_GESTION,
        ], true)) {
            throw new ProveedorReclamoException('El reclamo no está en un estado resoluble.');
        }
    }

    private function validarLineaParaNuevaResolucion(ProveedorReclamoItem $linea): void
    {
        if ($linea->estado === ProveedorReclamoItem::ESTADO_CANCELADO) {
            throw new ProveedorReclamoException('No se puede resolver una línea cancelada.');
        }

        if ($linea->estado === ProveedorReclamoItem::ESTADO_RESUELTO) {
            throw new ProveedorReclamoException('No se puede agregar una resolución a una línea ya resuelta.');
        }
    }

    private function validarLineaParaRecepcionReposicion(ProveedorReclamoItem $linea): void
    {
        if ($linea->estado === ProveedorReclamoItem::ESTADO_CANCELADO) {
            throw new ProveedorReclamoException('No se puede recibir una reposición de una línea cancelada.');
        }
    }

    private function asegurarReclamoEnGestion(ProveedorReclamo $reclamo, int $actorId): void
    {
        if ($reclamo->estado !== ProveedorReclamo::ESTADO_ENVIADO) {
            return;
        }

        $reclamo->update(['estado' => ProveedorReclamo::ESTADO_EN_GESTION]);
        $this->registrarEvento(
            $reclamo,
            ProveedorReclamoEvento::ACCION_RECLAMO_EN_GESTION,
            $actorId,
            ProveedorReclamo::ESTADO_ENVIADO,
            ProveedorReclamo::ESTADO_EN_GESTION,
            null,
            null
        );
    }

    private function asegurarLineaEnGestion(
        ProveedorReclamo $reclamo,
        ProveedorReclamoItem $linea,
        int $actorId,
        ?string $observacion
    ): void {
        if ($linea->estado !== ProveedorReclamoItem::ESTADO_PENDIENTE) {
            return;
        }

        $linea->update(['estado' => ProveedorReclamoItem::ESTADO_EN_GESTION]);
        $this->registrarEvento(
            $reclamo,
            ProveedorReclamoEvento::ACCION_LINEA_EN_GESTION,
            $actorId,
            ProveedorReclamoItem::ESTADO_PENDIENTE,
            ProveedorReclamoItem::ESTADO_EN_GESTION,
            $linea->id,
            $observacion
        );
    }

    private function sincronizarEstadosPorResoluciones(
        ProveedorReclamo $reclamo,
        ProveedorReclamoItem $linea,
        int $actorId,
        ?string $observacion
    ): void {
        $resolucionesActivas = ProveedorReclamoResolucion::query()
            ->where('proveedor_reclamo_item_id', $linea->id)
            ->where('estado', '!=', ProveedorReclamoResolucion::ESTADO_CANCELADA)
            ->get();
        $cantidadFinalizada = $this->cantidadFinalizadaBloqueada($linea, $resolucionesActivas);
        $cantidadReclamada = (int) $linea->cantidad_reclamada;

        $estadoLinea = $cantidadFinalizada === $cantidadReclamada
            ? ProveedorReclamoItem::ESTADO_RESUELTO
            : ($resolucionesActivas->isNotEmpty()
                ? ProveedorReclamoItem::ESTADO_EN_GESTION
                : ProveedorReclamoItem::ESTADO_PENDIENTE);

        if ($linea->estado !== $estadoLinea) {
            $estadoAnterior = (string) $linea->estado;
            $linea->update(['estado' => $estadoLinea]);
            $accion = match ($estadoLinea) {
                ProveedorReclamoItem::ESTADO_PENDIENTE => ProveedorReclamoEvento::ACCION_LINEA_REABIERTA,
                ProveedorReclamoItem::ESTADO_EN_GESTION => ProveedorReclamoEvento::ACCION_LINEA_EN_GESTION,
                ProveedorReclamoItem::ESTADO_RESUELTO => ProveedorReclamoEvento::ACCION_LINEA_RESUELTA,
                default => throw new ProveedorReclamoException('Estado de línea no soportado para sincronización.'),
            };
            $this->registrarEvento(
                $reclamo,
                $accion,
                $actorId,
                $estadoAnterior,
                $estadoLinea,
                $linea->id,
                $observacion
            );
        }

        $lineasActivas = ProveedorReclamoItem::query()
            ->where('proveedor_reclamo_id', $reclamo->id)
            ->where('estado', '!=', ProveedorReclamoItem::ESTADO_CANCELADO)
            ->get();
        if ($lineasActivas->isEmpty()) {
            return;
        }

        $todasLasLineasResueltas = $lineasActivas->every(
            fn (ProveedorReclamoItem $item): bool =>
                $item->estado === ProveedorReclamoItem::ESTADO_RESUELTO
        );
        $hayGestionActiva = $lineasActivas->contains(
            fn (ProveedorReclamoItem $item): bool =>
                $item->estado === ProveedorReclamoItem::ESTADO_EN_GESTION
        ) || ProveedorReclamoResolucion::query()
            ->join(
                'proveedor_reclamo_items',
                'proveedor_reclamo_items.id',
                '=',
                'proveedor_reclamo_resoluciones.proveedor_reclamo_item_id'
            )
            ->where('proveedor_reclamo_items.proveedor_reclamo_id', $reclamo->id)
            ->where('proveedor_reclamo_items.estado', '!=', ProveedorReclamoItem::ESTADO_CANCELADO)
            ->where('proveedor_reclamo_resoluciones.estado', '!=', ProveedorReclamoResolucion::ESTADO_CANCELADA)
            ->exists();

        $estadoReclamo = $todasLasLineasResueltas
            ? ProveedorReclamo::ESTADO_RESUELTO
            : ($hayGestionActiva || $reclamo->estado === ProveedorReclamo::ESTADO_EN_GESTION
                || $reclamo->estado === ProveedorReclamo::ESTADO_RESUELTO
                ? ProveedorReclamo::ESTADO_EN_GESTION
                : $reclamo->estado);

        if ($reclamo->estado === $estadoReclamo) {
            return;
        }

        if ($reclamo->estado === ProveedorReclamo::ESTADO_CANCELADO) {
            return;
        }

        $estadoAnterior = (string) $reclamo->estado;
        $reclamo->update(['estado' => $estadoReclamo]);
        $this->registrarEvento(
            $reclamo,
            $estadoReclamo === ProveedorReclamo::ESTADO_RESUELTO
                ? ProveedorReclamoEvento::ACCION_RECLAMO_RESUELTO
                : ProveedorReclamoEvento::ACCION_RECLAMO_EN_GESTION,
            $actorId,
            $estadoAnterior,
            $estadoReclamo,
            null,
            $observacion
        );
    }

    private function cambiarEstadoCabecera(
        ProveedorReclamo $reclamo,
        User $actor,
        string $estadoEsperado,
        string $estadoNuevo,
        string $accion,
        ?string $observacion,
        bool $requiereLineaActiva
    ): ProveedorReclamo {
        $actorId = $this->actorId($actor);
        $reclamoId = $this->modelId($reclamo, 'El reclamo indicado no es válido.');
        $observacion = $this->textoOpcional($observacion, 'observacion');

        return DB::transaction(function () use (
            $actorId,
            $reclamoId,
            $estadoEsperado,
            $estadoNuevo,
            $accion,
            $observacion,
            $requiereLineaActiva
        ): ProveedorReclamo {
            [, $reclamoBloqueado] = $this->bloquearReclamoConCompra($reclamoId);
            if ($reclamoBloqueado->estado !== $estadoEsperado) {
                throw new ProveedorReclamoException(
                    "El reclamo debe estar en {$estadoEsperado} para pasar a {$estadoNuevo}."
                );
            }

            if (
                $requiereLineaActiva
                && !ProveedorReclamoItem::query()
                    ->where('proveedor_reclamo_id', $reclamoBloqueado->id)
                    ->where('estado', '!=', ProveedorReclamoItem::ESTADO_CANCELADO)
                    ->exists()
            ) {
                throw new ProveedorReclamoException('El reclamo debe tener al menos una línea activa.');
            }

            $reclamoBloqueado->update(['estado' => $estadoNuevo]);
            $this->registrarEvento(
                $reclamoBloqueado,
                $accion,
                $actorId,
                $estadoEsperado,
                $estadoNuevo,
                null,
                $observacion
            );

            return $reclamoBloqueado->fresh(['items', 'eventos']);
        }, 3);
    }

    private function bloquearOrigenReposicion(
        ProveedorReclamo $reclamo,
        ProveedorReclamoItem $linea
    ): CompraItem {
        $recepcionItem = CompraRecepcionItem::query()
            ->whereKey($linea->compra_recepcion_item_id)
            ->lockForUpdate()
            ->first();
        if (!$recepcionItem) {
            throw new ProveedorReclamoException('El item de recepción original no existe.');
        }

        $recepcion = CompraRecepcion::query()->whereKey($recepcionItem->compra_recepcion_id)->first();
        $compraItem = CompraItem::query()
            ->whereKey($recepcionItem->compra_item_id)
            ->lockForUpdate()
            ->first();

        if (
            !$recepcion
            || !$compraItem
            || (int) $recepcion->compra_id !== (int) $reclamo->compra_id
            || (int) $compraItem->compra_id !== (int) $reclamo->compra_id
        ) {
            throw new ProveedorReclamoException(
                'El origen de la reposición no pertenece a la compra del reclamo.'
            );
        }

        return $compraItem;
    }

    private function bloquearVarianteReposicion(CompraItem $compraItem): ColoresProducto
    {
        $productoId = (int) ($compraItem->producto_id ?? 0);
        $colorId = (int) ($compraItem->colores_id ?? 0);
        $talla = trim((string) ($compraItem->talla ?? ''));

        if ($productoId <= 0 || $colorId <= 0 || $talla === '') {
            throw new ProveedorReclamoException(
                "El compra_item {$compraItem->id} no tiene una variante de reposición válida."
            );
        }

        $variante = ColoresProducto::query()
            ->where('producto_id', $productoId)
            ->where('colores_id', $colorId)
            ->where('talla_por_color', $talla)
            ->lockForUpdate()
            ->first();

        if (!$variante) {
            throw new ProveedorReclamoException(
                "No existe la variante original del compra_item {$compraItem->id}."
            );
        }

        return $variante;
    }

    /**
     * @return array{0:ProveedorReclamo,1:ProveedorReclamoItem}
     */
    private function bloquearLineaConReclamo(int $lineaId): array
    {
        $referencia = ProveedorReclamoItem::query()->whereKey($lineaId)->first();
        if (!$referencia) {
            throw new ProveedorReclamoException('La línea de reclamo indicada no existe.');
        }

        [, $reclamo] = $this->bloquearReclamoConCompra((int) $referencia->proveedor_reclamo_id);
        $linea = ProveedorReclamoItem::query()
            ->whereKey($lineaId)
            ->lockForUpdate()
            ->first();

        if (!$linea) {
            throw new ProveedorReclamoException('La línea de reclamo indicada no existe.');
        }

        return [$reclamo, $linea];
    }

    /**
     * @return array{0:ProveedorReclamo,1:ProveedorReclamoItem,2:ProveedorReclamoResolucion}
     */
    private function bloquearResolucionConContexto(int $resolucionId): array
    {
        $referencia = ProveedorReclamoResolucion::query()->whereKey($resolucionId)->first();
        if (!$referencia) {
            throw new ProveedorReclamoException('La resolución indicada no existe.');
        }

        [$reclamo, $linea] = $this->bloquearLineaConReclamo(
            (int) $referencia->proveedor_reclamo_item_id
        );
        $resolucion = ProveedorReclamoResolucion::query()
            ->whereKey($resolucionId)
            ->lockForUpdate()
            ->first();

        if (!$resolucion) {
            throw new ProveedorReclamoException('La resolución indicada no existe.');
        }

        return [$reclamo, $linea, $resolucion];
    }

    /**
     * @return array{0:Compra,1:ProveedorReclamo}
     */
    private function bloquearReclamoConCompra(int $reclamoId): array
    {
        $referencia = ProveedorReclamo::query()->whereKey($reclamoId)->first();
        if (!$referencia) {
            throw new ProveedorReclamoException('El reclamo indicado no existe.');
        }

        $compra = Compra::query()
            ->whereKey($referencia->compra_id)
            ->lockForUpdate()
            ->first();
        if (!$compra) {
            throw new ProveedorReclamoException('La compra del reclamo no existe.');
        }

        $reclamo = ProveedorReclamo::query()
            ->whereKey($reclamoId)
            ->lockForUpdate()
            ->first();
        if (!$reclamo) {
            throw new ProveedorReclamoException('El reclamo indicado no existe.');
        }

        return [$compra, $reclamo];
    }

    private function validarEstadoBorrador(ProveedorReclamo $reclamo): void
    {
        if ($reclamo->estado !== ProveedorReclamo::ESTADO_BORRADOR) {
            throw new ProveedorReclamoException('Solo se pueden agregar líneas a un reclamo en borrador.');
        }
    }

    private function reclamoItem(ProveedorReclamoItem|int $item): ProveedorReclamoItem
    {
        if ($item instanceof ProveedorReclamoItem) {
            if (!$item->exists || !$item->getKey()) {
                throw new ProveedorReclamoException('La línea de reclamo indicada no es válida.');
            }

            return $item;
        }

        if ((int) $item <= 0) {
            throw new ProveedorReclamoException('La línea de reclamo indicada no es válida.');
        }

        $linea = ProveedorReclamoItem::query()->find((int) $item);
        if (!$linea) {
            throw new ProveedorReclamoException('La línea de reclamo indicada no existe.');
        }

        return $linea;
    }

    private function recepcionItem(CompraRecepcionItem|int $item): CompraRecepcionItem
    {
        if ($item instanceof CompraRecepcionItem) {
            if (!$item->exists || !$item->getKey()) {
                throw new ProveedorReclamoException('El item de recepción indicado no es válido.');
            }

            return $item;
        }

        if ((int) $item <= 0) {
            throw new ProveedorReclamoException('El item de recepción indicado no es válido.');
        }

        $recepcionItem = CompraRecepcionItem::query()->find((int) $item);
        if (!$recepcionItem) {
            throw new ProveedorReclamoException('El item de recepción indicado no existe.');
        }

        return $recepcionItem;
    }

    private function tipoResolucion(mixed $tipo): string
    {
        if (!is_string($tipo)) {
            throw new ProveedorReclamoException('El tipo de resolución no es válido.');
        }

        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, ProveedorReclamoResolucion::TIPOS_RESOLUCION, true)) {
            throw new ProveedorReclamoException("El tipo de resolución {$tipo} no está permitido.");
        }

        return $tipo;
    }

    private function importeOpcional(mixed $importe): ?string
    {
        if ($importe === null) {
            return null;
        }

        if (is_float($importe) && !is_finite($importe)) {
            throw new ProveedorReclamoException('El importe no es válido.');
        }

        if (!is_int($importe) && !is_float($importe) && !is_string($importe)) {
            throw new ProveedorReclamoException('El importe no es válido.');
        }

        $importe = trim((string) $importe);
        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $importe) !== 1) {
            throw new ProveedorReclamoException('El importe debe ser un número no negativo con hasta dos decimales.');
        }

        [$entero, $decimal] = array_pad(explode('.', $importe, 2), 2, '');
        if (strlen(ltrim($entero, '0')) > 10) {
            throw new ProveedorReclamoException('El importe excede la precisión permitida.');
        }

        return $entero . '.' . str_pad($decimal, 2, '0');
    }

    private function monedaOpcional(?string $moneda): ?string
    {
        if ($moneda === null) {
            return null;
        }

        $moneda = strtoupper(trim($moneda));
        if (strlen($moneda) !== 3 || preg_match('/^[A-Z]{3}$/', $moneda) !== 1) {
            throw new ProveedorReclamoException('La moneda debe ser un código de tres letras.');
        }

        return $moneda;
    }

    private function tipoIncidencia(mixed $tipo): string
    {
        if (!is_string($tipo)) {
            throw new ProveedorReclamoException('El tipo de incidencia no es válido.');
        }

        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, ProveedorReclamoItem::TIPOS_INCIDENCIA, true)) {
            throw new ProveedorReclamoException("El tipo de incidencia {$tipo} no está permitido.");
        }

        return $tipo;
    }

    private function enteroNoNegativo(mixed $value, string $campo): int
    {
        if (is_int($value)) {
            $entero = $value;
        } elseif (is_string($value) && preg_match('/^[0-9]+$/', $value) === 1) {
            $entero = (int) $value;
        } else {
            throw new ProveedorReclamoException("{$campo} debe ser un entero no negativo.");
        }

        if ($entero < 0) {
            throw new ProveedorReclamoException("{$campo} debe ser un entero no negativo.");
        }

        return $entero;
    }

    private function idempotencyKey(mixed $value): string
    {
        if (!is_string($value)) {
            throw new ProveedorReclamoException('La idempotency_key es obligatoria.');
        }

        $value = trim($value);
        if ($value === '' || strlen($value) > 36) {
            throw new ProveedorReclamoException(
                'La idempotency_key es obligatoria y no puede superar 36 caracteres.'
            );
        }

        return $value;
    }

    private function enteroPositivo(mixed $value, string $campo): int
    {
        if (is_int($value)) {
            $entero = $value;
        } elseif (is_string($value) && preg_match('/^[0-9]+$/', $value) === 1) {
            $entero = (int) $value;
        } else {
            throw new ProveedorReclamoException("{$campo} debe ser un entero positivo.");
        }

        if ($entero <= 0) {
            throw new ProveedorReclamoException("{$campo} debe ser mayor que cero.");
        }

        return $entero;
    }

    private function textoObligatorio(mixed $value, string $campo): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ProveedorReclamoException("{$campo} es obligatorio.");
        }

        return trim($value);
    }

    private function textoOpcional(mixed $value, string $campo): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new ProveedorReclamoException("{$campo} debe ser texto.");
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function actorId(User $actor): int
    {
        $id = (int) $actor->getKey();
        if (!$actor->exists || $id <= 0) {
            throw new ProveedorReclamoException('El usuario actor no es válido.');
        }

        return $id;
    }

    private function modelId(object $model, string $message): int
    {
        $id = (int) $model->getKey();
        if ($id <= 0) {
            throw new ProveedorReclamoException($message);
        }

        return $id;
    }

    private function registrarEvento(
        ProveedorReclamo $reclamo,
        string $accion,
        int $usuarioId,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?int $itemId = null,
        ?string $observacion = null,
        ?int $resolucionId = null
    ): ProveedorReclamoEvento {
        return ProveedorReclamoEvento::query()->create([
            'proveedor_reclamo_id' => $reclamo->id,
            'proveedor_reclamo_item_id' => $itemId,
            'proveedor_reclamo_resolucion_id' => $resolucionId,
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'usuario_id' => $usuarioId,
            'observacion' => $observacion,
            'fecha_evento' => now(),
        ]);
    }
}
