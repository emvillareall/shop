<?php

namespace App\Services\Pagos;

use App\Models\PaymentGatewayConfig;

class PaymentConfigService
{
    private const GATEWAYS = ['paypal', 'payphone'];

    private function filledValue(mixed $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    public function get(string $gateway): array
    {
        $gateway = strtolower($gateway);
        if (!in_array($gateway, self::GATEWAYS, true)) {
            return [];
        }

        $db = PaymentGatewayConfig::query()->where('gateway', $gateway)->first();
        if (!$db) {
            return $this->fromEnv($gateway);
        }

        $env = $this->fromEnv($gateway);

        $resolved = [
            'gateway' => $gateway,
            'environment' => $this->filledValue($db->environment) ? $db->environment : ($env['environment'] ?? 'sandbox'),
            'is_active' => (bool) $db->is_active,
            'base_url' => $this->filledValue($db->base_url) ? $db->base_url : ($env['base_url'] ?? null),
            'public_key' => $this->filledValue($db->public_key) ? $db->public_key : ($env['public_key'] ?? null),
            'secret_key' => $this->filledValue($db->secret_key) ? $db->secret_key : ($env['secret_key'] ?? null),
            'merchant_id' => $this->filledValue($db->merchant_id) ? $db->merchant_id : ($env['merchant_id'] ?? null),
            'store_id' => $this->filledValue(data_get($db->settings, 'store_id'))
                ? (string) data_get($db->settings, 'store_id')
                : ($this->filledValue($db->merchant_id) ? $db->merchant_id : ($env['merchant_id'] ?? null)),
            'currency' => $this->filledValue($db->currency) ? $db->currency : ($env['currency'] ?? 'USD'),
            'webhook_id' => $this->filledValue($db->webhook_id) ? $db->webhook_id : ($env['webhook_id'] ?? null),
            'webhook_secret' => $this->filledValue($db->webhook_secret) ? $db->webhook_secret : ($env['webhook_secret'] ?? null),
            'verify_path' => $this->filledValue($db->verify_path) ? $db->verify_path : ($env['verify_path'] ?? '/sale/{id}'),
            'strict_webhook' => (bool) $db->strict_webhook,
            'settings' => (array) ($db->settings ?? []),
        ];

        return $resolved;
    }

    public function isAvailable(string $gateway): bool
    {
        $cfg = $this->get($gateway);

        if (!($cfg['is_active'] ?? false)) {
            return false;
        }

        if ($gateway === 'paypal') {
            return !empty($cfg['public_key']) && !empty($cfg['secret_key']) && !empty($cfg['base_url']);
        }

        return !empty($cfg['secret_key']) && !empty(($cfg['store_id'] ?? $cfg['merchant_id'] ?? null)) && !empty($cfg['base_url']);
    }

    public function upsert(string $gateway, array $data): PaymentGatewayConfig
    {
        $gateway = strtolower($gateway);
        if (!in_array($gateway, self::GATEWAYS, true)) {
            throw new \InvalidArgumentException('Pasarela no soportada.');
        }

        $model = PaymentGatewayConfig::query()->firstOrNew(['gateway' => $gateway]);
        $model->environment = $data['environment'] ?? 'sandbox';
        $model->is_active = (bool) ($data['is_active'] ?? false);
        $model->base_url = $data['base_url'] ?? null;
        $model->public_key = $data['public_key'] ?? null;
        if (array_key_exists('secret_key', $data) && $data['secret_key'] !== null && $data['secret_key'] !== '') {
            $model->secret_key = $data['secret_key'];
        }
        $model->merchant_id = $data['merchant_id'] ?? null;
        $model->currency = $data['currency'] ?? 'USD';
        $model->webhook_id = $data['webhook_id'] ?? null;
        $model->webhook_secret = $data['webhook_secret'] ?? null;
        $model->verify_path = $data['verify_path'] ?? '/sale/{id}';
        $model->strict_webhook = (bool) ($data['strict_webhook'] ?? true);
        $settings = (array) ($model->settings ?? []);
        if (array_key_exists('settings', $data) && is_array($data['settings'])) {
            $settings = array_merge($settings, $data['settings']);
        }
        $model->settings = $settings;
        $model->save();

        return $model;
    }

    private function fromEnv(string $gateway): array
    {
        if ($gateway === 'paypal') {
            return [
                'gateway' => 'paypal',
                'environment' => str_contains((string) config('payments.paypal.base_url'), 'sandbox') ? 'sandbox' : 'production',
                'is_active' => (bool) (config('payments.paypal.client_id') && config('payments.paypal.client_secret')),
                'base_url' => (string) config('payments.paypal.base_url'),
                'public_key' => (string) config('payments.paypal.client_id'),
                'secret_key' => (string) config('payments.paypal.client_secret'),
                'merchant_id' => null,
                'currency' => 'USD',
                'webhook_id' => (string) config('payments.paypal.webhook_id'),
                'webhook_secret' => null,
                'verify_path' => '/v2/checkout/orders/{id}',
                'strict_webhook' => (bool) config('payments.webhooks.paypal_strict', true),
                'settings' => [],
            ];
        }

        return [
            'gateway' => 'payphone',
            'environment' => str_contains((string) config('payments.payphone.base_url'), 'sandbox') ? 'sandbox' : 'production',
            'is_active' => (bool) config('payments.payphone.enabled', false),
            'base_url' => (string) config('payments.payphone.base_url'),
            'public_key' => null,
            'secret_key' => (string) config('payments.payphone.token'),
            'merchant_id' => (string) config('payments.payphone.store_id'),
            'currency' => (string) config('payments.payphone.currency', 'USD'),
            'webhook_id' => null,
            'webhook_secret' => (string) config('payments.payphone.webhook_secret'),
            'verify_path' => (string) config('payments.payphone.verify_path', '/sale/{id}'),
            'strict_webhook' => (bool) config('payments.webhooks.payphone_strict', true),
            'settings' => [],
        ];
    }
}
