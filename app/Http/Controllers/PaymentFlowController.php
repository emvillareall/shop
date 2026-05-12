<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\PagoPayphone;
use App\Models\Pedido;
use App\Services\Pagos\PaymentGatewayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PaymentFlowController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGatewayService
    ) {}

    public function payphoneBox(Pago $pago)
    {
        if ($pago->metodo !== 'payphone') {
            abort(404);
        }

        if ((string) $pago->estado === 'APROBADO') {
            return redirect()->route('ecommerce.pedido.confirmado', $pago->pedido_id)
                ->with('success', 'Este pago ya fue aprobado anteriormente.');
        }

        try {
            $responseUrl = route('payments.return', ['gateway' => 'payphone', 'pago' => $pago->id]);
            $payload = $this->paymentGatewayService->buildPayphoneBoxPayload($pago, $responseUrl);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('ecommerce.checkout.index')
                ->with('error', $e->getMessage());
        }

        return view('payments.payphone-box', compact('pago', 'payload'));
    }

    public function providerReturn(string $gateway, Pago $pago)
    {
        if (!in_array($gateway, ['paypal', 'payphone'], true) || $pago->metodo !== $gateway) {
            abort(404);
        }

        if ($gateway === 'payphone') {
            $payphoneId = (string) request()->query('id', '');
            $clientTxId = (string) request()->query('clientTransactionId', '');
            if (!$payphoneId || !$clientTxId) {
                return redirect()
                    ->route('ecommerce.pedido.confirmado', $pago->pedido_id)
                    ->with('error', 'PayPhone no devolvio parametros completos para confirmar el pago.');
            }

            $pp = PagoPayphone::query()->where('pago_id', $pago->id)->first();
            if (!$pp || $pp->client_transaction_id !== $clientTxId) {
                return redirect()
                    ->route('ecommerce.pedido.confirmado', $pago->pedido_id)
                    ->with('error', 'Intento de pago invalido o no coincide con el pedido.');
            }

            if ($pago->estado === 'APROBADO') {
                return redirect()
                    ->route('ecommerce.pedido.confirmado', $pago->pedido_id)
                    ->with('success', 'Pago ya confirmado previamente.');
            }

            $result = $this->paymentGatewayService->confirmPayphoneTransaction($payphoneId, $clientTxId);

            DB::transaction(function () use ($pago, $pp, $payphoneId, $result) {
                $pp->update([
                    'payphone_id' => $payphoneId,
                    'transaction_id' => $payphoneId,
                    'authorization_code' => $result['authorization_code'] ?? null,
                    'payphone_status' => $result['ok'] ? 'APPROVED' : 'FAILED',
                    'status_code' => $result['status_code'] ?? null,
                    'transaction_status' => $result['transaction_status'] ?? null,
                    'raw_response_json' => $result['raw'] ?? null,
                    'confirmed_at' => now(),
                ]);

                if ($result['ok']) {
                    $pago->update([
                        'estado' => 'APROBADO',
                        'referencia_externa' => $payphoneId,
                        'aprobado_at' => now(),
                        'metadata' => array_merge((array) ($pago->metadata ?? []), [
                            'provider_confirmed' => true,
                            'payphone_confirm_raw' => $result['raw'] ?? null,
                        ]),
                    ]);

                    Pedido::query()->where('id', $pago->pedido_id)->update([
                        'estado_pago' => 'APROBADO',
                        'estado_pedido' => 'PAGADO',
                        'pagado_at' => now(),
                    ]);
                } else {
                    $newEstado = (($result['status_code'] ?? 0) === 2 || strcasecmp((string) ($result['transaction_status'] ?? ''), 'Canceled') === 0)
                        ? 'RECHAZADO'
                        : 'EN_REVISION';
                    $pedidoEstado = $newEstado === 'RECHAZADO' ? 'RECHAZADO' : 'PAGO_EN_REVISION';

                    $pago->update([
                        'estado' => $newEstado,
                        'referencia_externa' => $payphoneId,
                        'observacion' => 'PayPhone: ' . (($result['message'] ?? '') ?: 'No aprobado'),
                        'metadata' => array_merge((array) ($pago->metadata ?? []), [
                            'provider_confirmed' => false,
                            'payphone_confirm_raw' => $result['raw'] ?? null,
                        ]),
                    ]);

                    Pedido::query()->where('id', $pago->pedido_id)->update([
                        'estado_pago' => $newEstado === 'RECHAZADO' ? 'RECHAZADO' : 'EN_REVISION',
                        'estado_pedido' => $pedidoEstado,
                        'pagado_at' => null,
                    ]);
                }
            });

            return redirect()
                ->route('ecommerce.pedido.confirmado', $pago->pedido_id)
                ->with($result['ok'] ? 'success' : 'error', $result['ok']
                    ? 'PayPhone confirmado correctamente.'
                    : 'PayPhone no pudo confirmar el pago. Revisa el estado en Pagos.');
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
