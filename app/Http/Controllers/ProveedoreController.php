<?php

namespace App\Http\Controllers;

use App\Models\Proveedore;
use Illuminate\Http\Request;

/**
 * Class ProveedoreController
 * @package App\Http\Controllers
 */
class ProveedoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $proveedores = Proveedore::paginate();

        return view('proveedore.index', compact('proveedores'))
            ->with('i', (request()->input('page', 1) - 1) * $proveedores->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $proveedore = new Proveedore();
        return view('proveedore.create', compact('proveedore'));
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
            'tienda_proveedor' => 'required|string|max:120',
            'nombres_proveedor' => 'required|string|max:120',
            'apellidos_proveedor' => 'required|string|max:120',
            'cedula_proveedor' => 'nullable|string|max:40',
            'telefono_proveedor' => 'nullable|string|max:40',
            'direccion_proveedor' => 'nullable|string|max:255',
            'email_proveedor' => 'nullable|email|max:120',
            'estado_proveedor' => 'nullable',
        ]);

        $proveedore = Proveedore::create($validated);

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedore created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $proveedore = Proveedore::find($id);

        return view('proveedore.show', compact('proveedore'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $proveedore = Proveedore::find($id);

        return view('proveedore.edit', compact('proveedore'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Proveedore $proveedore
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Proveedore $proveedore)
    {
        $validated = $request->validate([
            'tienda_proveedor' => 'required|string|max:120',
            'nombres_proveedor' => 'required|string|max:120',
            'apellidos_proveedor' => 'required|string|max:120',
            'cedula_proveedor' => 'nullable|string|max:40',
            'telefono_proveedor' => 'nullable|string|max:40',
            'direccion_proveedor' => 'nullable|string|max:255',
            'email_proveedor' => 'nullable|email|max:120',
            'estado_proveedor' => 'nullable',
        ]);

        $proveedore->update($validated);

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedore updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $proveedore = Proveedore::find($id)->delete();

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedore deleted successfully');
    }
}
