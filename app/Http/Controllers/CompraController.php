<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\InventarioMovimiento;
use App\Models\Proveedore;
use App\Services\Auditoria\AuditService;
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
        private readonly AuditService $auditService
    ) {}
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $compras = Compra::paginate();
        

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
            ->get();

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

        return view('compra.show', compact('productos', 'variantes', 'compraItems', 'trazabilidad', 'id'));
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
        $before = $compra->toArray();

        DB::transaction(function () use ($compra) {
            if ((string) $compra->estado_compra === 'ANULADA') {
                return;
            }

            $this->inventarioService->revertirCompra((int) $compra->id);

            $compra->update([
                'estado_compra' => 'ANULADA',
            ]);
        });

        $this->auditService->log(
            'compra.anulada',
            'compras',
            $compra->id,
            $before,
            $compra->fresh()?->toArray()
        );

        return back()->with('success', 'Compra anulada y entradas de inventario revertidas correctamente.');
    }
}
