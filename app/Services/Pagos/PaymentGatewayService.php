<?php

namespace App\Services\Pagos;

use App\Models\Pago;
use App\Models\PagoPayphone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function __construct(
        private readonly PaymentConfigService $configService
    ) {}

    private function fakeMode(): bool
    {
        return (bool) config('payments.fake_mode', false);
    }

    private function pickValue(mixed ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            if ($candidate !== null && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return '';
    }

    private function validatePayphoneRuntimeConfig(string $storeId, string $token, string $baseUrl): void
    {
        $errors = [];

        // PayPhone puede manejar store id numerico o UUID segun cuenta/configuracion.
        $isNumericStore = preg_match('/^[0-9]{6,25}$/', $storeId) === 1;
        $isUuidStore = preg_match('/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$/', $storeId) === 1;
        if (!$isNumericStore && !$isUuidStore) {
            $errors[] = 'store/merchant id invalido (usa el store id real de PayPhone: numerico o UUID)';
        }

        // Token largo tipo bearer emitido por PayPhone Developer.
        if (mb_strlen($token) < 80) {
            $errors[] = 'token secreto invalido o incompleto';
        }

        if (!str_starts_with($baseUrl, 'https://')) {
            $errors[] = 'base url api debe usar https';
        }

        if (!empty($errors)) {
            throw new \RuntimeException(
                'PayPhone configurado con errores: ' . implode(', ', $errors) . '.'
            );
        }
    }

    private function paypalAccessToken(): string
    {
        $cfg = $this->configService->get('paypal');
        $base = (string) ($cfg['base_url'] ?? config('payments.paypal.base_url'));
        $clientId = (string) ($cfg['public_key'] ?? config('payments.paypal.client_id'));
        $secret = (string) ($cfg['secret_key'] ?? config('payments.paypal.client_secret'));

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

        $cfg = $this->configService->get('paypal');
        $base = rtrim((string) ($cfg['base_url'] ?? config('payments.paypal.base_url')), '/');
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

        $cfg = $this->configService->get('payphone');
        $base = rtrim((string) ($cfg['base_url'] ?? config('payments.payphone.base_url')), '/');
        $token = (string) ($cfg['secret_key'] ?? config('payments.payphone.token'));
        $storeId = (string) ($cfg['merchant_id'] ?? config('payments.payphone.store_id'));
        $currency = (string) ($cfg['currency'] ?? config('payments.payphone.currency', 'USD'));

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

    public function buildPayphoneBoxPayload(Pago $pago, string $responseUrl): array
    {
        $cfg = $this->configService->get('payphone');
        $token = $this->pickValue($cfg['secret_key'] ?? null, config('payments.payphone.token'));
        $storeId = $this->pickValue($cfg['store_id'] ?? null, $cfg['merchant_id'] ?? null, config('payments.payphone.store_id'));
        $currency = $this->pickValue($cfg['currency'] ?? null, config('payments.payphone.currency', 'USD'));
        $baseUrl = $this->pickValue($cfg['base_url'] ?? null, config('payments.payphone.base_url'));

        $missing = [];
        if ($token === '') {
            $missing[] = 'token secreto';
        }
        if ($storeId === '') {
            $missing[] = 'store/merchant id';
        }
        if ($baseUrl === '') {
            $missing[] = 'base url api';
        }
        if (!empty($missing)) {
            throw new \RuntimeException('PayPhone no esta configurado correctamente. Falta: ' . implode(', ', $missing) . '.');
        }

        $this->validatePayphoneRuntimeConfig($storeId, $token, $baseUrl);

        $clientTxId = 'PP-' . $pago->pedido_id . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5));
        $amountCents = (int) round(((float) $pago->monto) * 100);
        if ($amountCents <= 0) {
            throw new \RuntimeException('Monto invalido para PayPhone.');
        }

        // Ajuste simple por ahora: todo como monto sin IVA para no romper flujo actual.
        $amountWithoutTax = $amountCents;
        $amountWithTax = 0;
        $tax = 0;
        $service = 0;
        $tip = 0;

        if ($amountCents !== ($amountWithoutTax + $amountWithTax + $tax + $service + $tip)) {
            throw new \RuntimeException('Los montos de PayPhone no cuadran.');
        }

        PagoPayphone::query()->updateOrCreate(
            ['pago_id' => $pago->id],
            [
                'client_transaction_id' => $clientTxId,
                'payphone_status' => 'CREATED',
                'status_code' => 1,
                'raw_request_json' => [
                    'amount' => $amountCents,
                    'amountWithoutTax' => $amountWithoutTax,
                    'amountWithTax' => $amountWithTax,
                    'tax' => $tax,
                    'service' => $service,
                    'tip' => $tip,
                    'currency' => $currency,
                    'storeId' => $storeId,
                    'environment' => (string) ($cfg['environment'] ?? ''),
                    'responseUrl' => $responseUrl,
                ],
            ]
        );

        $pago->update([
            'estado' => 'PENDIENTE',
            'metadata' => array_merge((array) ($pago->metadata ?? []), [
                'payphone_box' => true,
                'client_transaction_id' => $clientTxId,
            ]),
        ]);

        return [
            'token' => $token,
            'storeId' => $storeId,
            'currency' => $currency,
            'clientTransactionId' => $clientTxId,
            'amount' => $amountCents,
            'amountWithoutTax' => $amountWithoutTax,
            'amountWithTax' => $amountWithTax,
            'tax' => $tax,
            'service' => $service,
            'tip' => $tip,
            'reference' => (string) ($pago->pedido->codigo_pedido ?? $pago->pedido_id),
            'responseUrl' => $responseUrl,
            'environment' => (string) ($cfg['environment'] ?? ''),
        ];
    }

    public function confirmPayphoneTransaction(string $payphoneId, string $clientTxId): array
    {
        $cfg = $this->configService->get('payphone');
        $token = (string) ($cfg['secret_key'] ?? config('payments.payphone.token'));
        if (!$token) {
            throw new \RuntimeException('Token PayPhone no configurado.');
        }

        $url = 'https://paymentbox.payphonetodoesposible.com/api/confirm';

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, [
                'id' => (int) $payphoneId,
                'clientTxId' => $clientTxId,
            ]);

        if (!$response->successful()) {
            return [
                'ok' => false,
                'http_status' => $response->status(),
                'status_code' => null,
                'transaction_status' => 'HTTP_ERROR',
                'raw' => $response->json(),
            ];
        }

        $raw = (array) $response->json();
        $statusCode = (int) data_get($raw, 'statusCode', 0);
        $transactionStatus = (string) data_get($raw, 'transactionStatus', '');

        return [
            'ok' => ($statusCode === 3 && strcasecmp($transactionStatus, 'Approved') === 0),
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus,
            'authorization_code' => (string) data_get($raw, 'authorizationCode', ''),
            'card_brand' => (string) data_get($raw, 'cardBrand', ''),
            'message' => (string) data_get($raw, 'message', ''),
            'raw' => $raw,
        ];
    }

    public function confirmPaypalFromWebhook(array $payload): array
    {
        $cfg = $this->configService->get('paypal');
        $base = rtrim((string) ($cfg['base_url'] ?? config('payments.paypal.base_url')), '/');
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
        $cfg = $this->configService->get('payphone');
        $base = rtrim((string) ($cfg['base_url'] ?? config('payments.payphone.base_url')), '/');
        $token = (string) ($cfg['secret_key'] ?? config('payments.payphone.token'));
        $eventType = strtoupper((string) data_get($payload, 'transactionStatus', data_get($payload, 'status', 'UNKNOWN')));
        $transactionId = (string) data_get($payload, 'transactionId', data_get($payload, 'id', ''));

        if (!$token || !$transactionId) {
            return ['confirmed' => false, 'status' => 'MISSING_CONFIG_OR_TX'];
        }

        $verifyPath = (string) ($cfg['verify_path'] ?? config('payments.payphone.verify_path', '/sale/{id}'));
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
