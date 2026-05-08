<?php

namespace App\Services\Pagos;

use App\Models\Pago;
use Illuminate\Support\Facades\Http;

class PaymentGatewayService
{
    private function fakeMode(): bool
    {
        return (bool) config('payments.fake_mode', false);
    }

    private function paypalAccessToken(): string
    {
        $base = (string) config('payments.paypal.base_url');
        $clientId = (string) config('payments.paypal.client_id');
        $secret = (string) config('payments.paypal.client_secret');

        if (!$clientId || !$secret || !$base) {
            if ($this->fakeMode()) {
                return 'fake-paypal-token';
            }
            throw new \RuntimeException('PayPal no esta configurado en el entorno.');
        }

        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post(rtrim($base, '/') . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$tokenResponse->successful()) {
            throw new \RuntimeException('No se pudo obtener token de PayPal.');
        }

        $token = (string) data_get($tokenResponse->json(), 'access_token', '');
        if (!$token) {
            throw new \RuntimeException('Token de PayPal invalido.');
        }

        return $token;
    }

    public function createPaypalOrder(Pago $pago, string $returnUrl, string $cancelUrl): array
    {
        if ($this->fakeMode()) {
            return [
                'provider_order_id' => 'PP-SIM-' . $pago->id . '-' . now()->timestamp,
                'redirect_url' => $returnUrl,
                'raw' => ['simulated' => true, 'cancel_url' => $cancelUrl],
            ];
        }

        $base = rtrim((string) config('payments.paypal.base_url'), '/');
        $token = $this->paypalAccessToken();

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $pago->id,
                'custom_id' => (string) $pago->pedido_id,
                'amount' => [
                    'currency_code' => $pago->moneda ?: 'USD',
                    'value' => number_format((float) $pago->monto, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        $orderResponse = Http::withToken($token)
            ->acceptJson()
            ->post($base . '/v2/checkout/orders', $payload);

        if (!$orderResponse->successful()) {
            throw new \RuntimeException('No se pudo crear la orden de PayPal.');
        }

        $data = $orderResponse->json();
        $approveLink = collect(data_get($data, 'links', []))->firstWhere('rel', 'approve');

        return [
            'provider_order_id' => data_get($data, 'id'),
            'redirect_url' => data_get($approveLink, 'href'),
            'raw' => $data,
        ];
    }

    public function createPayphoneCheckout(Pago $pago, string $returnUrl): array
    {
        if ($this->fakeMode()) {
            return [
                'provider_order_id' => 'PHP-SIM-' . $pago->id . '-' . now()->timestamp,
                'redirect_url' => $returnUrl,
                'raw' => ['simulated' => true],
            ];
        }

        $base = rtrim((string) config('payments.payphone.base_url'), '/');
        $token = (string) config('payments.payphone.token');
        $storeId = (string) config('payments.payphone.store_id');
        $currency = (string) config('payments.payphone.currency', 'USD');

        if (!$token || !$storeId) {
            throw new \RuntimeException('Payphone no esta configurado en el entorno.');
        }

        $amountCents = (int) round(((float) $pago->monto) * 100);
        $payload = [
            'amount' => $amountCents,
            'amountWithoutTax' => $amountCents,
            'amountWithTax' => 0,
            'tax' => 0,
            'clientTransactionId' => (string) $pago->id,
            'storeId' => $storeId,
            'currency' => $currency,
            'reference' => (string) $pago->pedido_id,
            'returnUrl' => $returnUrl,
        ];

        $response = Http::withToken($token)->acceptJson()->post($base . '/Sale', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('No se pudo crear la transaccion Payphone.');
        }

        $data = $response->json();
        $redirect = data_get($data, 'payWithCard') ?: data_get($data, 'paymentUrl');

        return [
            'provider_order_id' => data_get($data, 'transactionId') ?: data_get($data, 'id'),
            'redirect_url' => $redirect,
            'raw' => $data,
        ];
    }

    public function confirmPaypalFromWebhook(array $payload): array
    {
        $base = rtrim((string) config('payments.paypal.base_url'), '/');
        $token = $this->paypalAccessToken();
        $eventType = (string) data_get($payload, 'event_type', '');
        $resource = (array) data_get($payload, 'resource', []);

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $captureId = (string) data_get($resource, 'id', '');
            if (!$captureId) {
                return ['confirmed' => false, 'status' => 'MISSING_CAPTURE_ID'];
            }
            $resp = Http::withToken($token)->acceptJson()->get($base . '/v2/payments/captures/' . $captureId);
            if (!$resp->successful()) {
                return ['confirmed' => false, 'status' => 'CAPTURE_QUERY_FAILED'];
            }
            $status = strtoupper((string) data_get($resp->json(), 'status', ''));
            return [
                'confirmed' => $status === 'COMPLETED',
                'status' => $status ?: 'UNKNOWN',
                'reference' => $captureId,
                'raw' => $resp->json(),
            ];
        }

        $orderId = (string) data_get($resource, 'id', '');
        if (!$orderId) {
            return ['confirmed' => false, 'status' => 'MISSING_ORDER_ID'];
        }
        $resp = Http::withToken($token)->acceptJson()->get($base . '/v2/checkout/orders/' . $orderId);
        if (!$resp->successful()) {
            return ['confirmed' => false, 'status' => 'ORDER_QUERY_FAILED'];
        }
        $status = strtoupper((string) data_get($resp->json(), 'status', ''));
        $captureId = (string) data_get($resp->json(), 'purchase_units.0.payments.captures.0.id', '');
        return [
            'confirmed' => $status === 'COMPLETED' || $status === 'APPROVED',
            'status' => $status ?: 'UNKNOWN',
            'reference' => $captureId ?: $orderId,
            'raw' => $resp->json(),
        ];
    }

    public function confirmPayphoneFromWebhook(array $payload): array
    {
        $base = rtrim((string) config('payments.payphone.base_url'), '/');
        $token = (string) config('payments.payphone.token');
        $eventType = strtoupper((string) data_get($payload, 'transactionStatus', data_get($payload, 'status', 'UNKNOWN')));
        $transactionId = (string) data_get($payload, 'transactionId', data_get($payload, 'id', ''));

        if (!$token || !$transactionId) {
            return ['confirmed' => false, 'status' => 'MISSING_CONFIG_OR_TX'];
        }

        $verifyPath = (string) config('payments.payphone.verify_path', '/sale/{id}');
        $url = $base . '/' . ltrim(str_replace('{id}', $transactionId, $verifyPath), '/');

        $resp = Http::withToken($token)->acceptJson()->get($url);
        if (!$resp->successful()) {
            return [
                'confirmed' => in_array($eventType, ['APPROVED', 'PAID', 'COMPLETED'], true),
                'status' => 'VERIFY_HTTP_FAILED_' . $eventType,
            ];
        }

        $data = (array) $resp->json();
        $providerStatus = strtoupper((string) (data_get($data, 'transactionStatus')
            ?: data_get($data, 'status')
            ?: data_get($data, 'data.status')
            ?: 'UNKNOWN'));

        return [
            'confirmed' => in_array($providerStatus, ['APPROVED', 'PAID', 'COMPLETED'], true),
            'status' => $providerStatus,
            'reference' => $transactionId,
            'raw' => $data,
        ];
    }

    public function syncPaypalPayment(Pago $pago): array
    {
        $payload = [
            'event_type' => 'SYNC',
            'resource' => [
                'id' => (string) $pago->referencia_externa,
            ],
        ];

        return $this->confirmPaypalFromWebhook($payload);
    }

    public function syncPayphonePayment(Pago $pago): array
    {
        $payload = [
            'status' => 'SYNC',
            'transactionId' => (string) $pago->referencia_externa,
        ];

        return $this->confirmPayphoneFromWebhook($payload);
    }
}
