<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\Auditoria\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function index()
    {
        $pagos = Pago::query()
            ->join('pedidos', 'pedidos.id', '=', 'pagos.pedido_id')
            ->join('clientes', 'clientes.id', '=', 'pedidos.clientes_id')
            ->select(
                'pagos.*',
                'pedidos.codigo_pedido',
                'pedidos.estado_pedido',
                'clientes.nombres_clientes',
                'clientes.apellidos_clientes'
            )
            ->orderByDesc('pagos.id')
            ->paginate(30);

        return view('pago.index', compact('pagos'));
    }

    public function aprobar(Request $request, Pago $pago)
    {
        $providerConfirmed = (bool) data_get((array) ($pago->metadata ?? []), 'provider_confirmed', false);
        if (in_array($pago->metodo, ['paypal', 'payphone'], true) && !$providerConfirmed) {
            return back()->with('error', 'Este pago requiere confirmación del proveedor (webhook) antes de aprobar.');
        }

        $beforePago = $pago->fresh();
        $beforePedido = $pago->pedido()->first();

        DB::transaction(function () use ($pago) {
            $pago->update([
                'estado' => 'APROBADO',
                'revisado_por' => auth()->id(),
                'revisado_at' => now(),
                'aprobado_at' => now(),
                'rechazado_at' => null,
            ]);

            $pago->pedido()->update([
                'estado_pago' => 'APROBADO',
                'estado_pedido' => 'PAGADO',
                'pagado_at' => now(),
            ]);
        });

        $this->auditService->log(
            'pago.aprobado',
            'pagos',
            $pago->id,
            $beforePago ? $beforePago->toArray() : null,
            $pago->fresh()?->toArray()
        );
        $this->auditService->log(
            'pedido.estado_pago_actualizado',
            'pedidos',
            $pago->pedido_id,
            $beforePedido ? $beforePedido->toArray() : null,
            $pago->pedido()->first()?->toArray()
        );

        return back()->with('success', 'Pago aprobado correctamente.');
    }

    public function rechazar(Request $request, Pago $pago)
    {
        $validated = $request->validate([
            'observacion' => 'nullable|string|max:500',
        ]);

        $beforePago = $pago->fresh();
        $beforePedido = $pago->pedido()->first();

        DB::transaction(function () use ($pago, $validated) {
            $pago->update([
                'estado' => 'RECHAZADO',
                'observacion' => $validated['observacion'] ?? $pago->observacion,
                'revisado_por' => auth()->id(),
                'revisado_at' => now(),
                'rechazado_at' => now(),
            ]);

            $pago->pedido()->update([
                'estado_pago' => 'RECHAZADO',
                'estado_pedido' => 'RECHAZADO',
                'pagado_at' => null,
            ]);
        });

        $this->auditService->log(
            'pago.rechazado',
            'pagos',
            $pago->id,
            $beforePago ? $beforePago->toArray() : null,
            $pago->fresh()?->toArray()
        );
        $this->auditService->log(
            'pedido.estado_pago_actualizado',
            'pedidos',
            $pago->pedido_id,
            $beforePedido ? $beforePedido->toArray() : null,
            $pago->pedido()->first()?->toArray()
        );

        return back()->with('success', 'Pago rechazado correctamente.');
    }
}
