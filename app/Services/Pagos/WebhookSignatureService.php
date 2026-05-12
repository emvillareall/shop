<?php

namespace App\Services\Pagos;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebhookSignatureService
{
    public function __construct(
        private readonly PaymentConfigService $configService
    ) {}

    public function isWithinReplayWindow(?string $timestamp): bool
    {
        if (!$timestamp) {
            return false;
        }

        $window = (int) config('payments.webhooks.replay_window_seconds', 900);
        $eventTs = strtotime($timestamp);
        if ($eventTs === false) {
            return false;
        }

        return abs(time() - $eventTs) <= max(30, $window);
    }

    public function validatePaypal(Request $request, array $payload): array
    {
        $cfg = $this->configService->get('paypal');
        $strict = (bool) ($cfg['strict_webhook'] ?? config('payments.webhooks.paypal_strict', true));
        $webhookId = (string) ($cfg['webhook_id'] ?? config('payments.paypal.webhook_id'));
        $base = (string) ($cfg['base_url'] ?? config('payments.paypal.base_url'));
        $clientId = (string) ($cfg['public_key'] ?? config('payments.paypal.client_id'));
        $secret = (string) ($cfg['secret_key'] ?? config('payments.paypal.client_secret'));

        if (!$webhookId || !$clientId || !$secret || !$base) {
            return $this->strictFallback($strict, 'paypal_not_configured');
        }

        $transmissionId = (string) $request->header('Paypal-Transmission-Id', '');
        $transmissionTime = (string) $request->header('Paypal-Transmission-Time', '');
        $certUrl = (string) $request->header('Paypal-Cert-Url', '');
        $authAlgo = (string) $request->header('Paypal-Auth-Algo', '');
        $transmissionSig = (string) $request->header('Paypal-Transmission-Sig', '');

        if (!$transmissionId || !$transmissionTime || !$certUrl || !$authAlgo || !$transmissionSig) {
            return $this->strictFallback($strict, 'paypal_missing_headers');
        }

        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post(rtrim($base, '/') . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (!$tokenResponse->successful()) {
            return $this->strictFallback($strict, 'paypal_token_error');
        }

        $token = (string) data_get($tokenResponse->json(), 'access_token', '');
        if (!$token) {
            return $this->strictFallback($strict, 'paypal_token_missing');
        }

        $verifyResponse = Http::withToken($token)
            ->acceptJson()
            ->post(rtrim($base, '/') . '/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $authAlgo,
                'cert_url' => $certUrl,
                'transmission_id' => $transmissionId,
                'transmission_sig' => $transmissionSig,
                'transmission_time' => $transmissionTime,
                'webhook_id' => $webhookId,
                'webhook_event' => $payload,
            ]);

        if (!$verifyResponse->successful()) {
            return $this->strictFallback($strict, 'paypal_verify_http_error');
        }

        $status = strtoupper((string) data_get($verifyResponse->json(), 'verification_status', ''));
        if ($status !== 'SUCCESS') {
            return $this->strictFallback($strict, 'paypal_verification_failed');
        }

        return ['valid' => true, 'reason' => 'ok'];
    }

    public function validatePayphone(Request $request): array
    {
        $cfg = $this->configService->get('payphone');
        $strict = (bool) ($cfg['strict_webhook'] ?? config('payments.webhooks.payphone_strict', true));
        $secret = (string) ($cfg['webhook_secret'] ?? config('payments.payphone.webhook_secret'));

        if (!$secret) {
            return $this->strictFallback($strict, 'payphone_not_configured');
        }

        $signature = (string) ($request->header('X-Payphone-Signature')
            ?: $request->header('X-Signature')
            ?: '');

        if (!$signature) {
            return $this->strictFallback($strict, 'payphone_missing_signature');
        }

        $rawBody = (string) $request->getContent();
        $expected = hash_hmac('sha256', $rawBody, $secret);

        if (!hash_equals(strtolower($expected), strtolower(trim($signature)))) {
            return $this->strictFallback($strict, 'payphone_signature_mismatch');
        }

        return ['valid' => true, 'reason' => 'ok'];
    }

    private function strictFallback(bool $strict, string $reason): array
    {
        if ($strict) {
            return ['valid' => false, 'reason' => $reason];
        }

        return ['valid' => true, 'reason' => $reason . '_non_strict'];
    }
}
