<?php

namespace App\Http\Controllers;

use App\Models\ColoresProducto;
use App\Models\Colores;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

/**
 * Class ColoresProductoController
 * @package App\Http\Controllers
 */
class ColoresProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $coloresProductos = ColoresProducto::paginate();

        return view('colores-producto.index', compact('coloresProductos'))
            ->with('i', (request()->input('page', 1) - 1) * $coloresProductos->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $id_producto=$request->id;

        $descripcion_producto = DB::table('productos')
        ->where('productos.id', '=', $id_producto)
        ->select('descripcion_producto')
        ->first();

        $cantidad_producto = DB::table('productos')
        ->where('productos.id', '=', $id_producto)
        ->select('cantidad_compra_producto')
        ->first();

        $cantidad_por_color = DB::table('colores_productos')
        ->where('producto_id', '=' , $id_producto)
        ->sum('cantidad_por_color');

        $cantidad_total = $cantidad_producto->cantidad_compra_producto - $cantidad_por_color;

        //dd($cantidad_producto);
        $coloresProducto = new ColoresProducto();
        $colores = Colores::paginate();

        return view('colores-producto.create', compact('coloresProducto','id_producto','cantidad_total','descripcion_producto','colores'));
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
        'producto_id' => 'required|exists:productos,id',
        'codigo_color' => 'required|string|max:30',
        'cantidad_por_color' => 'required|numeric|min:1',
        'cantidad_total' => 'required|numeric|min:0',
        'talla_por_color' => 'required|string|max:50',
    ]);

    // Obtener el color desde la base de datos
    $colores = DB::table('colores')
        ->where('codigo_color', $validated['codigo_color'])
        ->first();

    if (!$colores) {
        return back()->withErrors(['codigo_color' => 'El color seleccionado no es válido.']);
    }

    // Verificar que la cantidad a agregar no supere la cantidad disponible
    if ($validated['cantidad_por_color'] > $validated['cantidad_total']) {
        return back()->withErrors(['cantidad_por_color' => 'La cantidad excede el stock disponible.']);
    }

    // Insertar en la base de datos
    DB::table('colores_productos')->insert([
        'producto_id' => $validated['producto_id'],
        'colores_id' => $colores->id,
        'cantidad_por_color' => $validated['cantidad_por_color'],
        'stock_por_color' => $validated['cantidad_por_color'],
        'talla_por_color' => $validated['talla_por_color'],
        'created_at' => Carbon::now(), // Agregar timestamp
        'updated_at' => Carbon::now()
    ]);

    return redirect()->back()->with('success', 'Color agregado correctamente.');
}
 

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //dd($id);
        $coloresProducto = ColoresProducto::find($id);

        return view('colores-producto.show', compact('coloresProducto'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $coloresProducto = ColoresProducto::find($id);

        return view('colores-producto.edit', compact('coloresProducto'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  ColoresProducto $coloresProducto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ColoresProducto $coloresProducto)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'colores_id' => 'required|exists:colores,id',
            'cantidad_por_color' => 'required|numeric|min:0',
            'stock_por_color' => 'required|numeric|min:0',
            'talla_por_color' => 'required|string|max:50',
        ]);

        $coloresProducto->update($validated);

        return redirect()->route('colores-productos.index')
            ->with('success', 'ColoresProducto updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $coloresProducto = ColoresProducto::find($id)->delete();

        return redirect()->route('colores-productos.index')
            ->with('success', 'ColoresProducto deleted successfully');
    }
}
