<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class EventController extends Controller
{
    public function getLinkSubscribe(Request $request, $id)
    {
        if (!auth()->check()) {
            abort(403);
        }

        if (!Pedido::query()->whereKey((int) $id)->exists()) {
            return back()->with('danger', 'No se encontro el pedido para generar enlace firmado.');
        }

        $urlSigned = URL::temporarySignedRoute('event.subscribe', now()->addHours(6), ['id' => $id]);

        return view('pedido.index', [
            'url_signed' => $urlSigned,
            'id' => (int) $id,
        ]);
    }

    public function subscribe(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $cliente = new Cliente();
        return view('cliente.create', compact('cliente', 'id'));
    }

    public function storeSubscribe(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $validated = $request->validate([
            'nombres_clientes' => 'required|string|max:120',
            'apellidos_clientes' => 'required|string|max:120',
            'cedula_clientes' => 'required|string|max:40',
            'telefono_clientes' => 'required|string|max:40',
            'ciudad_clientes' => 'required|string|max:80',
            'direccion_clientes' => 'required|string|max:255',
            'email_clientes' => 'nullable|email|max:120',
        ]);

        DB::transaction(function () use ($validated, $id) {
            $cliente = Cliente::query()->firstOrCreate(
                ['cedula_clientes' => $validated['cedula_clientes']],
                $validated
            );

            if (!$cliente->wasRecentlyCreated) {
                $cliente->fill($validated);
                $cliente->save();
            }

            Pedido::query()->whereKey((int) $id)->update([
                'clientes_id' => $cliente->id,
                'estado_pedido' => 'EN_PREPARACION',
                'estado_envio' => 'PENDIENTE',
                'estado_pago' => 'SIN_PAGO',
                'estado_url' => 'EN ESPERA',
                'confirmado_at' => now(),
            ]);
        });

        return view('agradecimiento');
    }
}

