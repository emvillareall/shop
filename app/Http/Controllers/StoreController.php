<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\ColoresProducto;
use App\Models\Colores;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\LineasRopa;
use App\Models\CategoriasProducto;
use DB;
use Session;

class StoreController extends Controller
{

public function eliminarItem($index)
{
    $carrito = session()->get('carrito', []);
    if (isset($carrito[$index])) {
        unset($carrito[$index]);
        session()->put('carrito', array_values($carrito)); // Reindexar
        return redirect()->back()->with('success', 'Producto eliminado del carrito.');
    }
    return redirect()->back()->with('error', 'No se pudo eliminar el producto.');
}



public function catalogo($lineaId = null, $categoriaId = null)
{
    // Consultar productos activos
    $query = Producto::with(['coloresStock.color'])
        ->where('estado_producto', '1');

    // Si hay filtro por línea
    if ($lineaId) {
        // Validar que la línea existe y está activa
        $linea = LineasRopa::where('id', $lineaId)
            ->where('estado_linea', '1')
            ->firstOrFail();

        // Para filtrar productos, hay que hacer join o subquery con categorias_productos,
        // porque productos no tiene columna linea_ropa_id directo.

        // O filtrar productos que pertenezcan a categorías de esa línea:
        $categoriaIdsDeLinea = CategoriasProducto::where('linea_ropa_id', $lineaId)
            ->where('estado_categoria', '1')
            ->pluck('id');

        $query->whereIn('categoria_producto_id', $categoriaIdsDeLinea);
    } else {
        $linea = null;
    }

    // Si hay filtro por categoría
    if ($categoriaId) {
        // Validar que la categoría existe, está activa y pertenece a la línea (si línea filtrada)
        $categoriaQuery = CategoriasProducto::where('id', $categoriaId)
            ->where('estado_categoria', '1');

        if ($lineaId) {
            $categoriaQuery->where('linea_ropa_id', $lineaId);
        }

        $categoria = $categoriaQuery->firstOrFail();

        $query->where('categoria_producto_id', $categoriaId);
    } else {
        $categoria = null;
    }

    $productos = $query->get();

    // Procesar stock reservado en sesión carrito
    $reservado = [];
    foreach (session('carrito', []) as $item) {
        $key = $item['producto_id'] . '_' . $item['color_id'] . '_' . $item['talla'];
        $reservado[$key] = ($reservado[$key] ?? 0) + $item['cantidad'];
    }

    foreach ($productos as $producto) {
        $stockTotal = 0;
        foreach ($producto->coloresStock as $item) {
            $key = $item->producto_id . '_' . $item->colores_id . '_' . $item->talla_por_color;
            $stockReal = $item->stock_por_color - ($reservado[$key] ?? 0);
            $stockTotal += max($stockReal, 0);
        }
        $producto->agotado = $stockTotal <= 0;
    }

    // Pasar también línea y categoría para mostrar nombres en la vista
    return view('store.catalogo', compact('productos', 'reservado', 'linea', 'categoria'));
}



    public function vaciar()
{
    session()->forget('carrito');
    return redirect()->route('carrito.ver')->with('success', 'Carrito vaciado correctamente.');
}


public function agregarAlCarrito(Request $request)
{
    $validated = $request->validate([
        'producto_id' => 'required|exists:productos,id',
        'color_id' => 'required|exists:colores,id',
        'talla' => 'required|string',
        'cantidad' => 'required|integer|min:1'
    ]);

    // Buscar la combinación color + talla
    $coloresProducto = ColoresProducto::where([
        'producto_id' => $request->producto_id,
        'colores_id' => $request->color_id,
        'talla_por_color' => $request->talla
    ])->first();

    if (!$coloresProducto) {
        return back()->with('error', 'Combinación inválida de producto, color o talla.');
    }

    $stockDisponible = $coloresProducto->stock_por_color;

    // Validar stock acumulado considerando lo ya en el carrito
    $carrito = session()->get('carrito', []);
    $cantidadEnCarrito = collect($carrito)->filter(function ($item) use ($request) {
        return $item['producto_id'] == $request->producto_id &&
               $item['color_id'] == $request->color_id &&
               $item['talla'] == $request->talla;
    })->sum('cantidad');

    if ($cantidadEnCarrito + $request->cantidad > $stockDisponible) {
        return back()->with('error', 'No hay suficiente stock disponible para esta combinación (ya agregaste parte al carrito).');
    }

    // Obtener producto y color
    $producto = Producto::find($request->producto_id);
    $color = Colores::find($request->color_id);

    $item = [
        'producto_id' => $producto->id,
        'descripcion' => $producto->descripcion_producto,
        'color' => $color->nombre_color,
        'color_id' => $color->id,
        'talla' => $request->talla,
        'cantidad' => $request->cantidad,
        'precio' => $producto->precio_venta_producto
    ];

    $carrito[] = $item;
    session()->put('carrito', $carrito);

    return back()->with('success', 'Producto agregado al carrito.');
}


    public function verCarrito()
    {
        $carrito = session()->get('carrito', []);
        $clientes = Cliente::where('estado_clientes', '1')->get();
        return view('store.carrito', compact('carrito', 'clientes'));
    }

    public function guardarPedido(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'cliente_id' => 'required|exists:clientes,id'
            ]);

            $pedido = Pedido::create([
                'clientes_id' => $request->cliente_id,
                'tienda_id' => 1,
                'descripcion' => 'Pedido en línea',
                'subtotal_pedido' => 0,
                'descuentos_pedido' => 0,
                'estado_pedidos' => 1,
                'estado_url' => 'EN ESPERA',
            ]);

            $total = 0;
            foreach (session('carrito', []) as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $total += $subtotal;

DetallePedido::create([
    'pedido_id' => $pedido->id,
    'producto_id' => $item['producto_id'],
    'cantidad_producto' => $item['cantidad'],
    'id_color_producto' => $item['color_id'],
    'talla_por_color' => $item['talla'],
    'estado_dtpedidos' => 1,
]);
                // Actualizar stock por combinación color/talla
$cp = ColoresProducto::where([
    'producto_id' => $item['producto_id'],
    'colores_id' => $item['color_id'],
    'talla_por_color' => $item['talla'],
])->lockForUpdate()->first();

if (!$cp || (int)$cp->stock_por_color < (int)$item['cantidad']) {
    throw new \Exception('Stock insuficiente para la combinaci�n seleccionada.');
}
                // Stock general del producto
                Producto::where('id', $item['producto_id'])->decrement('stock_venta_producto', $item['cantidad']);
            }

            $pedido->subtotal_pedido = $total;
            $pedido->total_pedido = $total;
            $pedido->save();

            session()->forget('carrito');
            DB::commit();

            return redirect()->route('store.catalogo')->with('success', 'Pedido registrado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'No se pudo registrar el pedido. Intenta nuevamente.');
        }
    }


    // Mostrar todas las líneas de ropa activas
    public function lineas_store()
    {
        $lineas = LineasRopa::where('estado_linea', 1)->get(); // estado_linea = 1 para activas
        return view('store.lineas_store', compact('lineas'));
    }
    
    // Mostrar categorías activas de una línea específica
public function categorias_store($id)
{
    $linea = LineasRopa::findOrFail($id);
    $categorias = CategoriasProducto::where('linea_ropa_id', $id)->where('estado_categoria', true)->get();

    return view('store.categorias_store', compact('linea', 'categorias'));
}
    
    // Mostrar productos activos de una categoría dentro de una línea

public function productos_store($lineaId, $categoriaId)
{
    // 1. Buscar la línea
    $linea = LineasRopa::findOrFail($lineaId);

    // 2. Buscar la categoría que pertenezca a esa línea
    $categoria = CategoriasProducto::where('id', $categoriaId)
        ->where('linea_ropa_id', $lineaId)
        ->where('estado_categoria', '1')
        ->firstOrFail();

    // 3. Buscar los productos activos de esa categoría
    $productos = Producto::where('categoria_producto_id', $categoriaId)
        ->where('estado_producto', '1')
        ->get();

    // 4. Retornar la vista con los datos
    return view('store.productos_store', compact('linea', 'categoria', 'productos'));
}



}


