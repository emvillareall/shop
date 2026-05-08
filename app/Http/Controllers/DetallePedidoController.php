<?php

namespace App\Http\Controllers;

use App\Models\Colores;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DB;

class DetallePedidoController extends Controller
{
    public function index()
    {
        $detallePedidos = DetallePedido::paginate();

        return view('detalle-pedido.index', compact('detallePedidos'))
            ->with('i', (request()->input('page', 1) - 1) * $detallePedidos->perPage());
    }

    public function create(Request $request)
    {
        $pedido_id = $request->id;
        $pedido = Pedido::findOrFail($pedido_id);
        $productos = DB::table('productos')->get();

        $producto_id = Producto::select(DB::raw('descripcion_producto as nombre_producto'), DB::raw('id as id'))
            ->pluck('nombre_producto', 'id');

        $variantesPorProducto = DB::table('colores_productos')
            ->leftJoin('colores', 'colores_productos.colores_id', '=', 'colores.id')
            ->whereNotNull('colores_productos.talla_por_color')
            ->where('colores_productos.talla_por_color', '!=', '')
            ->orderBy('colores_productos.producto_id')
            ->orderByRaw('COALESCE(colores.nombre_color, "") asc')
            ->orderBy('colores_productos.talla_por_color')
            ->select([
                'colores_productos.producto_id',
                'colores_productos.colores_id',
                'colores_productos.talla_por_color',
                'colores_productos.stock_por_color',
                DB::raw('COALESCE(colores.nombre_color, CONCAT("Color #", colores_productos.colores_id)) as nombre_color'),
                DB::raw('COALESCE(colores.codigo_color, "#94a3b8") as codigo_color'),
            ])
            ->get()
            ->groupBy('producto_id')
            ->map(function ($rows) {
                return $rows->values();
            })
            ->toArray();

        $detallePedido = new DetallePedido();

        return view('detalle-pedido.create', compact('detallePedido', 'pedido_id', 'producto_id', 'productos', 'variantesPorProducto', 'pedido'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $pedido = Pedido::findOrFail($request->pedido_id);

            $request->validate([
                'producto_id' => 'nullable|exists:productos,id',
                'pedido_id' => 'required|exists:pedidos,id',
                'return_to' => 'nullable|string|max:500',
                'lineas_json' => 'nullable|string',
                'descuento_extra' => 'nullable|numeric|min:0',
                'recargo_extra' => 'nullable|numeric|min:0',
                'accion' => 'nullable|string|in:guardar,emitir_imprimir',
            ]);

            $lineas = [];

            if ($request->filled('lineas_json')) {
                $lineasJson = json_decode($request->input('lineas_json'), true);
                if (is_array($lineasJson)) {
                    foreach ($lineasJson as $linea) {
                        $productoId = (int) ($linea['producto_id'] ?? 0);
                        $colorId = (int) ($linea['colores_id'] ?? 0);
                        $talla = trim((string) ($linea['talla_por_color'] ?? ''));
                        $cantidad = (int) ($linea['cantidad'] ?? 0);

                        if ($productoId > 0 && $colorId > 0 && $talla !== '' && $cantidad > 0) {
                            $lineas[] = [
                                'producto_id' => $productoId,
                                'color_id' => $colorId,
                                'talla' => $talla,
                                'cantidad' => $cantidad,
                            ];
                        }
                    }
                }
            }

            // Compatibilidad legacy: cantidad_{color}_{talla} para un solo producto.
            if (empty($lineas) && $request->filled('producto_id')) {
                $payload = $request->except(['_token', 'producto_id', 'pedido_id', 'return_to', 'lineas_json']);
                foreach ($payload as $key => $value) {
                    if (strpos($key, 'cantidad_') === 0 && (int) $value > 0) {
                        $parts = explode('_', $key);
                        $colorId = (int) ($parts[1] ?? 0);
                        $talla = trim((string) ($parts[2] ?? ''));
                        if ($colorId > 0 && $talla !== '') {
                            $lineas[] = [
                                'producto_id' => (int) $request->producto_id,
                                'color_id' => $colorId,
                                'talla' => $talla,
                                'cantidad' => (int) $value,
                            ];
                        }
                    }
                }
            }

            if (empty($lineas)) {
                throw new \Exception('Debes agregar al menos una línea para confirmar la venta.');
            }

            $subtotalAcumulado = 0.0;
            $totalesPorProducto = [];

            foreach ($lineas as $linea) {
                $producto = Producto::lockForUpdate()->find($linea['producto_id']);
                if (!$producto) {
                    throw new \Exception("No se encontró el producto #{$linea['producto_id']}.");
                }

                $colorTalla = DB::table('colores_productos')
                    ->where('producto_id', $linea['producto_id'])
                    ->where('colores_id', $linea['color_id'])
                    ->where('talla_por_color', $linea['talla'])
                    ->lockForUpdate()
                    ->first();

                if (!$colorTalla) {
                    throw new \Exception("No se encontró la variante de {$producto->descripcion_producto}.");
                }

                if ((int) $colorTalla->stock_por_color < (int) $linea['cantidad']) {
                    throw new \Exception("Stock insuficiente en {$producto->descripcion_producto} ({$linea['talla']}).");
                }

                DB::table('colores_productos')
                    ->where('producto_id', $linea['producto_id'])
                    ->where('colores_id', $linea['color_id'])
                    ->where('talla_por_color', $linea['talla'])
                    ->update([
                        'stock_por_color' => (int) $colorTalla->stock_por_color - (int) $linea['cantidad'],
                    ]);

                $precioUnitario = (float) $producto->precio_venta_producto;
                $cantidad = (int) $linea['cantidad'];
                $subtotalLinea = $precioUnitario * $cantidad;

                DetallePedido::create([
                    'cantidad_producto' => $cantidad,
                    'producto_id' => $linea['producto_id'],
                    'pedido_id' => $request->pedido_id,
                    'id_color_producto' => $linea['color_id'],
                    'talla_por_color' => $linea['talla'],
                    'nombre_producto_snapshot' => $producto->descripcion_producto,
                    'color_snapshot' => optional(Colores::find($linea['color_id']))->nombre_color,
                    'talla_snapshot' => $linea['talla'],
                    'precio_unitario_snapshot' => $precioUnitario,
                    'subtotal_linea' => $subtotalLinea,
                    'descuento_linea' => 0,
                    'impuesto_linea' => 0,
                    'estado_dtpedidos' => 1,
                ]);

                $subtotalAcumulado += $subtotalLinea;
                $totalesPorProducto[$linea['producto_id']] = ($totalesPorProducto[$linea['producto_id']] ?? 0) + $cantidad;
            }

            foreach ($totalesPorProducto as $productoId => $cantidadTotal) {
                $productoActual = Producto::lockForUpdate()->find($productoId);
                if (!$productoActual) {
                    throw new \Exception("No se encontró el producto #{$productoId}.");
                }
                if ((int) $productoActual->stock_venta_producto < (int) $cantidadTotal) {
                    throw new \Exception("Stock total insuficiente para {$productoActual->descripcion_producto}.");
                }

                DB::table('productos')
                    ->where('id', $productoId)
                    ->update([
                        'stock_venta_producto' => (int) $productoActual->stock_venta_producto - (int) $cantidadTotal,
                    ]);
            }

            DB::table('pedidos')
                ->where('id', $request->pedido_id)
                ->update([
                    'subtotal_pedido' => $pedido->subtotal_pedido + $subtotalAcumulado + (float)($request->input('recargo_extra', 0)),
                    'iva_pedido' => 0,
                    'descuentos_pedido' => $pedido->descuentos_pedido + (float)($request->input('descuento_extra', 0)),
                    'total_pedido' => ($pedido->subtotal_pedido + $subtotalAcumulado + (float)($request->input('recargo_extra', 0))) - ($pedido->descuentos_pedido + (float)($request->input('descuento_extra', 0))),
                ]);

            $pedidoActualizado = Pedido::findOrFail($request->pedido_id);
            Venta::query()->updateOrCreate(
                ['pedido_id' => $pedidoActualizado->id],
                [
                    'clientes_id' => $pedidoActualizado->clientes_id,
                    'tienda_id' => $pedidoActualizado->tienda_id,
                    'subtotal' => (float)($pedidoActualizado->subtotal_pedido ?? 0),
                    'descuento' => (float)($pedidoActualizado->descuentos_pedido ?? 0),
                    'total' => (float)($pedidoActualizado->total_pedido ?? 0),
                    'estado_venta' => (string)($pedidoActualizado->estado_pedido ?? 'PENDIENTE_PAGO'),
                    'fecha_venta' => $pedidoActualizado->confirmado_at ?? now(),
                ]
            );

            DB::commit();

            if ($request->input('accion') === 'emitir_imprimir') {
                return redirect()->route('getPDF_pedidos', $request->pedido_id)
                    ->with('success', 'Venta emitida correctamente.');
            }

            $returnTo = $request->input('return_to');
            if ($returnTo && Str::startsWith($returnTo, url('/'))) {
                return redirect()->to($returnTo)
                    ->with('success', 'Detalle(s) de pedido registrado(s) correctamente.');
            }

            return redirect()->route('pedidos.index')
                ->with('success', 'Detalle(s) de pedido registrado(s) correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            $returnTo = $request->input('return_to');
            if ($returnTo && Str::startsWith($returnTo, url('/'))) {
                return redirect()->to($returnTo)
                    ->with('danger', 'Error: ' . $e->getMessage());
            }

            return redirect()->route('pedidos.index')
                ->with('danger', 'Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $pedido = DB::table('pedidos')
            ->where('id', $id)
            ->first();

        $detalles = DB::table('detalle_pedidos as dp')
            ->join('productos as p', 'p.id', '=', 'dp.producto_id')
            ->leftJoin('colores_productos as cp', function ($join) {
                $join->on('cp.producto_id', '=', 'dp.producto_id')
                    ->on('cp.colores_id', '=', 'dp.id_color_producto')
                    ->on('cp.talla_por_color', '=', 'dp.talla_por_color');
            })
            ->leftJoin('colores as c', 'c.id', '=', 'dp.id_color_producto')
            ->select(
                'dp.*',
                'p.descripcion_producto',
                'p.precio_venta_producto',
                DB::raw('COALESCE(dp.color_snapshot, c.nombre_color) as nombre_color'),
                'c.codigo_color',
                DB::raw('COALESCE(dp.talla_snapshot, dp.talla_por_color) as talla_mostrada'),
                'cp.stock_por_color'
            )
            ->where('dp.pedido_id', $id)
            ->get();

        return view('detalle-pedido.show', compact('pedido', 'detalles'));
    }

    public function edit($id)
    {
        $detallePedido = DetallePedido::find($id);

        return view('detalle-pedido.edit', compact('detallePedido'));
    }

    public function update(Request $request, DetallePedido $detallePedido)
    {
        $validated = $request->validate([
            'cantidad_producto' => 'required|numeric|min:1',
            'producto_id' => 'required|exists:productos,id',
            'pedido_id' => 'required|exists:pedidos,id',
            'estado_dtpedidos' => 'nullable',
            'talla_por_color' => 'nullable|string|max:50',
            'id_color_producto' => 'nullable|integer|exists:colores,id',
        ]);
        $detallePedido->update($validated);

        return redirect()->route('detalle-pedidos.index')
            ->with('success', 'DetallePedido updated successfully');
    }

    public function destroy($id)
    {
        DetallePedido::find($id)->delete();

        return redirect()->route('detalle-pedidos.index')
            ->with('success', 'DetallePedido deleted successfully');
    }
}
