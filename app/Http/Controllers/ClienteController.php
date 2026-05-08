<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use DB;

/**
 * Class ClienteController
 * @package App\Http\Controllers
 */
class ClienteController extends Controller
{


public function buscar($cedula)
{
    if (!auth()->check()) {
        abort(403);
    }

    if (!preg_match('/^[0-9A-Za-z\-]{5,20}$/', (string) $cedula)) {
        return response()->json(['exists' => false], 422);
    }

    $cliente = Cliente::where('cedula_clientes', $cedula)->first();

    if ($cliente) {
        return response()->json([
            'exists' => true,
            'id' => $cliente->id,
            'nombre' => $cliente->nombres_clientes . ' ' . $cliente->apellidos_clientes,
            'telefono' => $cliente->telefono_clientes,
            'ciudad' => $cliente->ciudad_clientes,
            'direccion' => $cliente->direccion_clientes,
            'email' => $cliente->email_clientes,
        ]);
    } else {
        return response()->json(['exists' => false]);
    }
}
public function registrar(Request $request)
{
    $validated = $request->validate([
        'cedula_clientes' => 'required|unique:clientes,cedula_clientes',
        'nombres_clientes' => 'required',
        'apellidos_clientes' => 'required',
        'telefono_clientes' => 'required',
        'ciudad_clientes' => 'required',
        'direccion_clientes' => 'required',
        'email_clientes' => 'required|email'
    ]);

    $cliente = Cliente::create(array_merge($validated, ['estado_clientes' => 1]));

    return response()->json([
        'success' => true,
        'cliente' => [
            'id' => $cliente->id,
            'nombre' => $cliente->nombres_clientes . ' ' . $cliente->apellidos_clientes
        ]
    ]);
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clientes = DB::table('clientes')->where('id','!=',1)->paginate(30);

        return view('cliente.index', compact('clientes'))
            ->with('i', (request()->input('page', 1) - 1) * $clientes->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $cliente = new Cliente();
        return view('cliente.create', compact('cliente','id'));
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
            'nombres_clientes' => 'required|string|max:120',
            'apellidos_clientes' => 'required|string|max:120',
            'cedula_clientes' => 'required|string|max:40',
            'telefono_clientes' => 'required|string|max:40',
            'ciudad_clientes' => 'required|string|max:80',
            'direccion_clientes' => 'required|string|max:255',
            'email_clientes' => 'nullable|email|max:120',
            'estado_clientes' => 'nullable',
            'id' => 'nullable|integer',
        ]);

        $payload = $validated;
        unset($payload['id']);
        $cliente = Cliente::create($payload);

        $cliente_ced = Cliente::where('cedula_clientes', $request->cedula_clientes)->first();

            $pedido = DB::table('pedidos')
            ->where('id', $request->id)
            ->update(['clientes_id' => $cliente_ced->id]);

        return view('agradecimiento');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $cliente = Cliente::find($id);

        return view('cliente.show', compact('cliente'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cliente = Cliente::find($id);

        return view('cliente.edit', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Cliente $cliente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nombres_clientes' => 'required|string|max:120',
            'apellidos_clientes' => 'required|string|max:120',
            'cedula_clientes' => 'required|string|max:40',
            'telefono_clientes' => 'required|string|max:40',
            'ciudad_clientes' => 'required|string|max:80',
            'direccion_clientes' => 'required|string|max:255',
            'email_clientes' => 'nullable|email|max:120',
            'estado_clientes' => 'nullable',
        ]);

        $cliente->update($validated);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $cliente = Cliente::find($id)->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente deleted successfully');
    }
}
