<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;

class PaymentFlowController extends Controller
{
    public function providerReturn(string $gateway, Pago $pago)
    {
        if (!in_array($gateway, ['paypal', 'payphone'], true) || $pago->metodo !== $gateway) {
            abort(404);
        }

        return redirect()
            ->route('ecommerce.pedido.confirmado', $pago->pedido_id)
            ->with('success', strtoupper($gateway) . ': retorno recibido. Estamos validando tu pago.');
    }

    public function providerCancel(string $gateway, Pago $pago)
    {
        if (!in_array($gateway, ['paypal', 'payphone'], true) || $pago->metodo !== $gateway) {
            abort(404);
        }

        if (!in_array($pago->estado, ['PENDIENTE', 'EN_REVISION'], true)) {
            return redirect()
                ->route('ecommerce.pedido.confirmado', $pago->pedido_id)
                ->with('success', 'El pago ya fue procesado y no se puede cancelar desde esta URL.');
        }

        $pago->update([
            'estado' => 'PENDIENTE',
            'observacion' => strtoupper($gateway) . ': pago cancelado por el cliente.',
        ]);

        return redirect()
            ->route('ecommerce.checkout.index')
            ->with('error', strtoupper($gateway) . ': pago cancelado. Puedes intentarlo nuevamente.');
    }
}
