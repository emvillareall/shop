<?php

namespace App\Services\Pagos;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PayphoneLinkClient
{
    public function __construct(
        private readonly PaymentConfigService $configService
    ) {}

    public function create(array $payload): string
    {
        $config = $this->configService->get('payphone');
        $token = trim((string) ($config['secret_key'] ?? ''));
        $storeId = trim((string) ($config['store_id'] ?? $config['merchant_id'] ?? ''));
        $baseUrl = rtrim(trim((string) ($config['base_url'] ?? '')), '/');

        if (!(bool) ($config['is_active'] ?? false) || $token === '' || $storeId === '' || $baseUrl === '') {
            throw new PayphoneLinkException(
                'PayPhone no esta disponible en este momento.',
                503,
                'payphone_configuration_missing'
            );
        }

        if (!str_starts_with($baseUrl, 'https://')) {
            throw new PayphoneLinkException(
                'PayPhone no esta disponible en este momento.',
                503,
                'payphone_https_required'
            );
        }

        $endpoint = $baseUrl . '/Links';

        try {
            $response = Http::withToken($token)
                ->asJson()
                ->acceptJson()
                ->connectTimeout(max(1, (int) config('payments.payphone.tool.connect_timeout_seconds', 5)))
                ->timeout(max(1, (int) config('payments.payphone.tool.timeout_seconds', 15)))
                ->post($endpoint, $payload);
        } catch (ConnectionException) {
            throw new PayphoneLinkException(
                'No se pudo comunicar con PayPhone.',
                504,
                'payphone_connection_timeout'
            );
        } catch (\Throwable) {
            throw new PayphoneLinkException(
                'PayPhone no esta disponible en este momento.',
                503,
                'payphone_client_failure'
            );
        }

        if (!$response->successful()) {
            $providerError = $this->sanitizeProviderError($response->json());

            if ($response->status() === 429) {
                throw new PayphoneLinkException(
                    'PayPhone alcanzo temporalmente su limite de solicitudes.',
                    429,
                    'payphone_rate_limited',
                    $providerError
                );
            }

            $status = $response->serverError()
                ? 503
                : ($response->clientError() ? $response->status() : 502);

            throw new PayphoneLinkException(
                'PayPhone rechazo la solicitud.',
                $status,
                $response->serverError() ? 'payphone_server_error' : 'payphone_request_rejected',
                $providerError
            );
        }

        $link = null;

        try {
            $decoded = $response->json();
            if (is_string($decoded)) {
                $link = trim($decoded);
            }
        } catch (\Throwable) {
            // Some API Link responses are plain strings rather than JSON strings.
        }

        if ($link === null || $link === '') {
            $link = trim($response->body(), " \t\n\r\0\x0B\"");
        }

        $parts = is_string($link) ? parse_url($link) : false;
        if (!is_string($link) || filter_var($link, FILTER_VALIDATE_URL) === false || ($parts['scheme'] ?? '') !== 'https') {
            throw new PayphoneLinkException(
                'PayPhone devolvio una respuesta inesperada.',
                502,
                'payphone_invalid_link_response'
            );
        }

        return $link;
    }

    private function sanitizeProviderError(mixed $decoded): array
    {
        if (!is_array($decoded)) {
            return [];
        }

        $sanitized = [];

        if (array_key_exists('errorCode', $decoded)) {
            $errorCode = $this->sanitizeScalar($decoded['errorCode']);
            if ($errorCode !== null) {
                $sanitized['errorCode'] = $errorCode;
            }
        }

        if (array_key_exists('message', $decoded)) {
            $message = $this->sanitizeText($decoded['message']);
            if ($message !== null) {
                $sanitized['message'] = $message;
            }
        }

        $errors = [];
        foreach ((array) ($decoded['errors'] ?? []) as $error) {
            if (!is_array($error)) {
                continue;
            }

            $sanitizedError = [];

            if (array_key_exists('errorCode', $error)) {
                $errorCode = $this->sanitizeScalar($error['errorCode']);
                if ($errorCode !== null) {
                    $sanitizedError['errorCode'] = $errorCode;
                }
            }

            if (array_key_exists('message', $error)) {
                $message = $this->sanitizeText($error['message']);
                if ($message !== null) {
                    $sanitizedError['message'] = $message;
                }
            }

            if (array_key_exists('errorDescriptions', $error)) {
                $descriptions = [];
                foreach ((array) $error['errorDescriptions'] as $description) {
                    $description = $this->sanitizeText($description);
                    if ($description !== null) {
                        $descriptions[] = $description;
                    }
                }

                if ($descriptions !== []) {
                    $sanitizedError['errorDescriptions'] = $descriptions;
                }
            }

            if ($sanitizedError !== []) {
                $errors[] = $sanitizedError;
            }
        }

        if ($errors !== []) {
            $sanitized['errors'] = $errors;
        }

        return $sanitized;
    }

    private function sanitizeScalar(mixed $value): int|string|null
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\A[\w.-]{1,80}\z/u', $value)) {
            return $value;
        }

        return null;
    }

    private function sanitizeText(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $text = preg_replace(
            '/(authorization|bearer|token|secret(?:_key)?|storeid|merchantid|hash)(\s*[:=]\s*|\s+)[^\s,;]+/iu',
            '$1 [REDACTED]',
            $text
        ) ?: '';

        return mb_substr($text, 0, 500);
    }
}
