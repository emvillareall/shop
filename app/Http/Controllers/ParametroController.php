<?php

namespace App\Http\Controllers;

use App\Models\Parametro;
use Illuminate\Http\Request;

/**
 * Class ParametroController
 * @package App\Http\Controllers
 */
class ParametroController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parametros = Parametro::paginate();

        return view('parametro.index', compact('parametros'))
            ->with('i', (request()->input('page', 1) - 1) * $parametros->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parametro = new Parametro();
        return view('parametro.create', compact('parametro'));
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
            'nombre_parametro' => 'required|string|max:120',
            'valor_parametro' => 'required',
            'estado_parametro' => 'nullable',
        ]);

        $parametro = Parametro::create($validated);

        return redirect()->route('parametros.index')
            ->with('success', 'Parametro created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $parametro = Parametro::find($id);

        return view('parametro.show', compact('parametro'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $parametro = Parametro::find($id);

        return view('parametro.edit', compact('parametro'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Parametro $parametro
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Parametro $parametro)
    {
        $validated = $request->validate([
            'nombre_parametro' => 'required|string|max:120',
            'valor_parametro' => 'required',
            'estado_parametro' => 'nullable',
        ]);

        $parametro->update($validated);

        return redirect()->route('parametros.index')
            ->with('success', 'Parametro updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $parametro = Parametro::find($id)->delete();

        return redirect()->route('parametros.index')
            ->with('success', 'Parametro deleted successfully');
    }
}
