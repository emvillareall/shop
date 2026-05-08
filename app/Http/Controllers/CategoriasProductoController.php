<?php

namespace App\Http\Controllers;

use App\Models\CategoriasProducto;
use App\Models\LineasRopa;
use App\Http\Requests\CategoriasProductoRequest;

/**
 * Class CategoriasProductoController
 * @package App\Http\Controllers
 */
class CategoriasProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categoriasProductos = CategoriasProducto::paginate();

        return view('categorias-producto.index', compact('categoriasProductos'))
            ->with('i', (request()->input('page', 1) - 1) * $categoriasProductos->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
public function create()
{
    $categoriasProducto = new CategoriasProducto();
    $lineasRopa = LineasRopa::all(); // Añadir esto
    return view('categorias-producto.create', compact('categoriasProducto', 'lineasRopa'));
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoriasProductoRequest $request)
    {
        CategoriasProducto::create($request->validated());

        return redirect()->route('categorias-productos.index')
            ->with('success', 'CategoriasProducto created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $categoriasProducto = CategoriasProducto::find($id);

        return view('categorias-producto.show', compact('categoriasProducto'));
    }

    /**
     * Show the form for editing the specified resource.
     */
public function edit($id)
{
    $categoriasProducto = CategoriasProducto::find($id);
    $lineasRopa = LineaRopa::all(); // Añadir esto
    return view('categorias-producto.edit', compact('categoriasProducto', 'lineasRopa'));
}

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoriasProductoRequest $request, CategoriasProducto $categoriasProducto)
    {
        $categoriasProducto->update($request->validated());

        return redirect()->route('categorias-productos.index')
            ->with('success', 'CategoriasProducto updated successfully');
    }

    public function destroy($id)
    {
        CategoriasProducto::find($id)->delete();

        return redirect()->route('categorias-productos.index')
            ->with('success', 'CategoriasProducto deleted successfully');
    }
}
