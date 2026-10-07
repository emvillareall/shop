<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\InventarioMovimiento;
use App\Models\Proveedore;
use App\Services\Auditoria\AuditService;
use App\Services\Compras\CompraRecepcionService;
use App\Services\Compras\ProveedorReclamoService;
use App\Services\Inventario\InventarioService;
use Illuminate\Http\Request;
use DB;

/**
 * Class CompraController
 * @package App\Http\Controllers
 */
class CompraController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly AuditService $auditService,
        private readonly CompraRecepcionService $recepcionService
    ) {}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $compras = Compra::withCount('recepciones')->paginate();
        

        return view('compra.index', compact('compras'))
            ->with('i', (request()->input('page', 1) - 1) * $compras->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $compra = new Compra();
        $proveedor_id = Proveedore::select(DB::raw("tienda_proveedor as tienda_proveedor"),DB::raw("id as id"))->pluck('tienda_proveedor', 'id');
               

        return view('compra.create', compact('compra','proveedor_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_compra' => 'required|string|max:120',
            'descripcion_compra' => 'required|string|max:255',
            'envio_compra' => 'required|numeric',
            'importacion_compra' => 'nullable|numeric',
            'estado_compra' => 'nullable',
            'proveedor_id' => 'required|exists:proveedores,id',
        ]);

        $validated['estado_recepcion'] = 'EN_TRANSITO';

        $compra = Compra::create($validated);

        return redirect()->route('compras.index')
            ->with('success', 'Compra created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $compra = Compra::query()
            ->with('proveedor')
            ->findOrFail($id);
        $resumenRecepcion = $this->recepcionService->resumenRecepcion($compra);
        $gestionProveedor = app(ProveedorReclamoService::class)->resumenParaCompra($compra);

        $productos = DB::table('productos')
            ->where('compras_id',$id)
            ->paginate(300);

        $variantes = DB::table('colores_productos')
            ->join('productos', 'productos.id', '=', 'colores_productos.producto_id')
            ->leftJoin('colores', 'colores.id', '=', 'colores_productos.colores_id')
            ->where('productos.compras_id', $id)
            ->select(
                'colores_productos.producto_id',
                'colores_productos.colores_id',
                'colores_productos.talla_por_color',
                'colores_productos.stock_por_color',
                'productos.descripcion_producto',
                'colores.nombre_color'
            )
            ->orderBy('productos.descripcion_producto')
            ->orderBy('colores_productos.talla_por_color')
            ->get();

        $compraItems = CompraItem::query()
            ->leftJoin('productos', 'productos.id', '=', 'compra_items.producto_id')
            ->leftJoin('colores', 'colores.id', '=', 'compra_items.colores_id')
            ->where('compra_items.compra_id', $id)
            ->select(
                'compra_items.*',
                'productos.descripcion_producto',
                'colores.nombre_color'
            )
            ->orderBy('compra_items.id')
            ->get()
            ->map(function ($item) use ($resumenRecepcion) {
                $resumen = $resumenRecepcion[(int) $item->id] ?? [
                    'recibida' => 0,
                    'aceptada' => 0,
                    'danada' => 0,
                    'incorrecta' => 0,
                    'faltante' => 0,
                    'pendiente' => (int) $item->cantidad,
                ];

                $item->fisicamente_contabilizada = (int) $resumen['recibida'];
                $item->aceptada_acumulada = (int) $resumen['aceptada'];
                $item->danada_acumulada = (int) $resumen['danada'];
                $item->incorrecta_acumulada = (int) $resumen['incorrecta'];
                $item->faltante_acumulado = (int) $resumen['faltante'];
                $item->pendiente_recepcion = (int) $resumen['pendiente'];

                return $item;
            });

        $trazabilidad = CompraItem::query()
            ->leftJoin('colores_productos', function ($join) {
                $join->on('colores_productos.producto_id', '=', 'compra_items.producto_id')
                    ->on('colores_productos.colores_id', '=', 'compra_items.colores_id')
                    ->on('colores_productos.talla_por_color', '=', 'compra_items.talla');
            })
            ->where('compra_items.compra_id', $id)
            ->select(
                'compra_items.id',
                'compra_items.compra_id',
                'compra_items.producto_id',
                'compra_items.colores_id',
                'compra_items.talla',
                'compra_items.stock_ingresado',
                'compra_items.estado',
                'colores_productos.stock_por_color as stock_final'
            )
            ->orderBy('compra_items.id')
            ->get()
            ->map(function ($row) use ($id) {
                $movs = InventarioMovimiento::query()
                    ->where('compra_id', $id)
                    ->where('producto_id', $row->producto_id)
                    ->where(function ($q) use ($row) {
                        $q->whereJsonContains('metadata->compra_item_id', (int) $row->id)
                            ->orWhere(function ($q2) use ($row) {
                                $q2->where('metadata->color_id', (int) $row->colores_id)
                                    ->where('metadata->talla', (string) $row->talla);
                            });
                    })
                    ->orderBy('id')
                    ->get(['id', 'tipo_movimiento', 'cantidad', 'stock_antes', 'stock_despues', 'created_at']);

                $row->movimientos = $movs;
                $row->saldo_movimientos = (int) $movs->sum('cantidad');
                return $row;
            });

        $recepciones = $compra->recepciones()
            ->with(['user', 'items.compraItem.producto', 'items.compraItem.color'])
            ->orderByDesc('fecha_recepcion')
            ->orderByDesc('id')
            ->get();
        $recepcionHabilitada = $this->recepcionService->puedeRecibir($compra);

        return view('compra.show', compact(
            'compra',
            'productos',
            'variantes',
            'compraItems',
            'trazabilidad',
            'recepciones',
            'recepcionHabilitada',
            'resumenRecepcion',
            'gestionProveedor',
            'id'
        ));
    }

    public function ajustarInventario(Request $request, Compra $compra)
    {
        $validated = $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'colores_id' => 'required|integer|exists:colores,id',
            'talla_por_color' => 'required|string|max:50',
            'cantidad_ajuste' => 'required|integer|not_in:0|min:-10000|max:10000',
            'motivo' => 'nullable|string|max:180',
        ]);

        $variante = DB::table('colores_productos')
            ->join('productos', 'productos.id', '=', 'colores_productos.producto_id')
            ->where('productos.compras_id', $compra->id)
            ->where('colores_productos.producto_id', $validated['producto_id'])
            ->where('colores_productos.colores_id', $validated['colores_id'])
            ->where('colores_productos.talla_por_color', $validated['talla_por_color'])
            ->select('colores_productos.stock_por_color')
            ->first();

        if (!$variante) {
            return back()->with('danger', 'La variante no pertenece a esta compra.');
        }

        $before = (int) $variante->stock_por_color;

        DB::transaction(function () use ($validated, $compra) {
            $this->inventarioService->ajustarStockVariante(
                (int) $validated['producto_id'],
                (int) $validated['colores_id'],
                (string) $validated['talla_por_color'],
                (int) $validated['cantidad_ajuste'],
                (int) $compra->id,
                null,
                $validated['motivo'] ?: 'Ajuste manual desde modulo compras'
            );
        });

        $afterVariante = DB::table('colores_productos')
            ->where('producto_id', $validated['producto_id'])
            ->where('colores_id', $validated['colores_id'])
            ->where('talla_por_color', $validated['talla_por_color'])
            ->first();

        $this->auditService->log(
            'inventario.ajuste_manual_compra',
            'colores_productos',
            $validated['producto_id'] . ':' . $validated['colores_id'] . ':' . $validated['talla_por_color'],
            ['stock_por_color' => $before],
            ['stock_por_color' => (int) ($afterVariante->stock_por_color ?? $before)]
        );

        return back()->with('success', 'Inventario ajustado correctamente para la variante.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $compra = Compra::find($id);
        $proveedor_id = Proveedore::select(DB::raw("tienda_proveedor as tienda_proveedor"),DB::raw("id as id"))->pluck('tienda_proveedor', 'id');

        return view('compra.edit', compact('compra','proveedor_id','id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Compra $compra
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Compra $compra)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:compras,id',
            'codigo_compra' => 'required|string|max:120',
            'descripcion_compra' => 'required|string|max:255',
            'envio_compra' => 'required|numeric',
            'importacion_compra' => 'nullable|numeric',
            'estado_compra' => 'nullable',
            'proveedor_id' => 'required|exists:proveedores,id',
        ]);

        $recargo_paypal= DB::table('parametros')->where('nombre_parametro','recargo_paypal')->first();
        $cambio_moneda= DB::table('parametros')->where('nombre_parametro','cambio_moneda')->first();
        
        $compra = Compra::find($validated['id']);

        $total_final_compra=$compra->total_final_compra-$compra->importacion_compra;
        $precio_real_envio = $compra->envio_compra+($compra->envio_compra*$recargo_paypal->valor_parametro);
        $precio_envio_dolares = $precio_real_envio * $cambio_moneda->valor_parametro;

        $total_pesos_compra=$compra->total_pesos_compra-$precio_real_envio; //a usar
        $total_dolares_compra=$compra->total_dolares_compra-$precio_envio_dolares; // a usar
        $total_final=$compra->total_final_compra-$precio_envio_dolares-$compra->importacion_compra; // a usar



        $precio_real_envio_2 = $validated['envio_compra']+($validated['envio_compra']*$recargo_paypal->valor_parametro);
        $precio_envio_dolares_2 = $precio_real_envio_2 * $cambio_moneda->valor_parametro;

        $total_pesos_2= $total_pesos_compra + $precio_real_envio_2;

        $total_dolares_2= $total_dolares_compra + $precio_envio_dolares_2;

        $total_final_2=$total_final+$precio_envio_dolares_2 + ((float) ($validated['importacion_compra'] ?? 0));

        $compra->update($validated);

                DB::table('compras')
            ->where('id', $validated['id'])
            ->update(['total_pesos_compra' => $total_pesos_2,'total_dolares_compra' => $total_dolares_2,'total_final_compra' => $total_final_2]);

        return redirect()->route('compras.index')
            ->with('success', 'Compra updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $compra = Compra::find($id)->delete();

        return redirect()->route('compras.index')
            ->with('success', 'Compra deleted successfully');
    }

    public function anular(Compra $compra)
    {
        $resultado = DB::transaction(function () use ($compra): array {
            $compraBloqueada = Compra::query()
                ->whereKey($compra->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$compraBloqueada->puedeAnularse()) {
                return [
                    'anulada' => false,
                    'before' => null,
                    'after' => null,
                ];
            }

            $before = $compraBloqueada->toArray();

            // Una compra en tránsito no tiene inventario que revertir.
            // Los items permanecen ACTIVO para conservar la trazabilidad.
            $compraBloqueada->update([
                'estado_compra' => 'ANULADA',
            ]);

            return [
                'anulada' => true,
                'before' => $before,
                'after' => $compraBloqueada->fresh()?->toArray(),
            ];
        });

        if (!$resultado['anulada']) {
            return back()->with('danger', 'No se puede anular esta compra porque ya tiene una recepción registrada o no está en tránsito.');
        }

        $this->auditService->log(
            'compra.anulada',
            'compras',
            $compra->id,
            $resultado['before'],
            $resultado['after']
        );

        return back()->with('success', 'Compra anulada correctamente.');
    }
}
