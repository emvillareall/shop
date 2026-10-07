<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompraRecepcionRequest;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Services\Compras\CompraRecepcionException;
use App\Services\Compras\CompraRecepcionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CompraRecepcionController extends Controller
{
    public function __construct(
        private readonly CompraRecepcionService $recepcionService
    ) {}

    public function create(Compra $compra)
    {
        if (!$this->recepcionService->puedeRecibir($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('danger', $this->mensajeEstadoNoRecepcionable($compra));
        }

        $resumen = $this->recepcionService->resumenRecepcion($compra);
        $items = $this->itemsConResumen($compra, $resumen);
        $itemsPendientes = $items->filter(fn ($item): bool => (int) $item->pendiente_recepcion > 0);
        $idempotencyKey = (string) Str::uuid();
        $recepciones = $compra->recepciones()
            ->with(['user', 'items.compraItem.producto', 'items.compraItem.color'])
            ->orderByDesc('fecha_recepcion')
            ->orderByDesc('id')
            ->get();

        return view('compra.recepcion', compact(
            'compra',
            'items',
            'itemsPendientes',
            'idempotencyKey',
            'recepciones'
        ));
    }

    public function store(CompraRecepcionRequest $request, Compra $compra): RedirectResponse
    {
        $validated = $request->validated();

        if (!$this->recepcionService->puedeRecibir($compra)) {
            return redirect()
                ->route('compras.show', $compra)
                ->with('danger', $this->mensajeEstadoNoRecepcionable($compra));
        }

        try {
            $modo = $validated['modo'] ?? 'manual';
            if ($modo === 'todo') {
                $this->recepcionService->recibirCompleta(
                    $compra,
                    $validated['idempotency_key'],
                    auth()->id(),
                    null,
                    $validated['observacion'] ?? null
                );
            } else {
                $items = array_values(array_filter(
                    $validated['items'] ?? [],
                    static fn (array $item): bool => (int) ($item['cantidad_aceptada'] ?? 0)
                        + (int) ($item['cantidad_danada'] ?? 0)
                        + (int) ($item['cantidad_incorrecta'] ?? 0)
                        + (int) ($item['cantidad_faltante_declarada'] ?? 0) > 0
                        || filter_var($item['cierra_item'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ));

                $this->recepcionService->registrarRecepcion(
                    $compra,
                    $items,
                    $validated['idempotency_key'],
                    auth()->id(),
                    null,
                    $validated['observacion'] ?? null
                );
            }
        } catch (CompraRecepcionException $exception) {
            return back()
                ->withInput()
                ->with('danger', $exception->getMessage());
        }

        return redirect()
            ->route('compras.show', $compra)
            ->with('success', 'Recepción registrada correctamente.');
    }

    private function itemsConResumen(Compra $compra, array $resumen)
    {
        return CompraItem::query()
            ->leftJoin('productos', 'productos.id', '=', 'compra_items.producto_id')
            ->leftJoin('colores', 'colores.id', '=', 'compra_items.colores_id')
            ->where('compra_items.compra_id', $compra->id)
            ->where('compra_items.estado', 'ACTIVO')
            ->select(
                'compra_items.*',
                'productos.descripcion_producto',
                'colores.nombre_color'
            )
            ->orderBy('compra_items.id')
            ->get()
            ->map(function (CompraItem $item) use ($resumen) {
                $datos = $resumen[(int) $item->id] ?? [
                    'recibida' => 0,
                    'aceptada' => 0,
                    'danada' => 0,
                    'incorrecta' => 0,
                    'faltante' => 0,
                    'pendiente' => (int) $item->cantidad,
                ];

                $item->fisicamente_contabilizada = (int) $datos['recibida'];
                $item->aceptada_acumulada = (int) $datos['aceptada'];
                $item->danada_acumulada = (int) $datos['danada'];
                $item->incorrecta_acumulada = (int) $datos['incorrecta'];
                $item->faltante_acumulado = (int) $datos['faltante'];
                $item->pendiente_recepcion = (int) $datos['pendiente'];

                return $item;
            });
    }

    private function mensajeEstadoNoRecepcionable(Compra $compra): string
    {
        if (strtoupper(trim((string) $compra->estado_compra)) === 'ANULADA') {
            return 'La compra está anulada y no puede recibir mercadería.';
        }

        if ($compra->estado_recepcion === null) {
            return 'Esta compra histórica todavía no está habilitada para el flujo de recepción.';
        }

        if ($compra->estado_recepcion === 'RECIBIDA') {
            return 'La compra ya fue recibida.';
        }

        if ($compra->estado_recepcion === 'CON_INCIDENCIAS') {
            return 'La compra está cerrada con incidencias y no admite una nueva recepción normal.';
        }

        return 'La compra no está disponible para recibir mercadería.';
    }
}
