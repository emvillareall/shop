<?php

return [
    'fake_mode' => (bool) env('PAYMENTS_FAKE_MODE', false),
    'paypal' => [
        'base_url' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],
    'payphone' => [
        'enabled' => (bool) env('PAYPHONE_ENABLED', false),
        'base_url' => env('PAYPHONE_BASE_URL', 'https://pay.payphonetodoesposible.com/api'),
        'token' => env('PAYPHONE_TOKEN'),
        'store_id' => env('PAYPHONE_STORE_ID'),
        'response_url' => env('PAYPHONE_RESPONSE_URL'),
        'domain' => env('PAYPHONE_DOMAIN'),
        'environment' => env('PAYPHONE_ENV', 'testing'),
        'currency' => env('PAYPHONE_CURRENCY', 'USD'),
        'timezone' => env('PAYPHONE_TIMEZONE', '-5'),
        'default_method' => env('PAYPHONE_DEFAULT_METHOD', 'card'),
        'lat' => env('PAYPHONE_LAT'),
        'lng' => env('PAYPHONE_LNG'),
        'webhook_secret' => env('PAYPHONE_WEBHOOK_SECRET'),
        'verify_path' => env('PAYPHONE_VERIFY_PATH', '/sale/{id}'),
        'tool' => [
            'access_hash' => env('PAYPHONE_TOOL_ACCESS_HASH'),
            'surcharge_percent' => env('PAYPHONE_TOOL_SURCHARGE_PERCENT', '7'),
            'min_amount_cents' => (int) env('PAYPHONE_TOOL_MIN_AMOUNT_CENTS', 1),
            'max_amount_cents' => (int) env('PAYPHONE_TOOL_MAX_AMOUNT_CENTS', 0),
            'session_ttl_minutes' => (int) env('PAYPHONE_TOOL_SESSION_TTL_MINUTES', 480),
            'link_expire_hours' => (int) env('PAYPHONE_TOOL_LINK_EXPIRE_HOURS', 24),
            'default_reference' => env('PAYPHONE_TOOL_DEFAULT_REFERENCE', 'Compra BootyFitness'),
            'access_attempts_per_minute' => (int) env('PAYPHONE_TOOL_ACCESS_ATTEMPTS_PER_MINUTE', 5),
            'access_attempts_per_hour' => (int) env('PAYPHONE_TOOL_ACCESS_ATTEMPTS_PER_HOUR', 20),
            'links_per_minute' => (int) env('PAYPHONE_TOOL_LINKS_PER_MINUTE', 10),
            'links_per_hour' => (int) env('PAYPHONE_TOOL_LINKS_PER_HOUR', 60),
            'connect_timeout_seconds' => (int) env('PAYPHONE_TOOL_CONNECT_TIMEOUT_SECONDS', 5),
            'timeout_seconds' => (int) env('PAYPHONE_TOOL_TIMEOUT_SECONDS', 15),
            // Must be explicitly set to no_tax_service_fee before live generation.
            'tax_component_mode' => env('PAYPHONE_TOOL_TAX_COMPONENT_MODE', 'unresolved'),
            'modalities' => [
                'habitual' => [
                    'label' => 'Cobro habitual',
                    'months' => 1,
                    'enabled' => true,
                    'pending_activation' => false,
                    'min_amount_cents' => null,
                    'uses_tool_surcharge_percent' => true,
                ],
                'sin_intereses_3' => [
                    'label' => '3 meses sin intereses',
                    'months' => 3,
                    'enabled' => filter_var(
                        env('PAYPHONE_TOOL_THREE_MONTHS_ENABLED', true),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'pending_activation' => false,
                    'min_amount_cents' => (int) env('PAYPHONE_TOOL_THREE_MONTHS_MIN_AMOUNT_CENTS', 20000),
                    'uses_tool_surcharge_percent' => true,
                ],
                'sin_intereses_6' => [
                    'label' => '6 meses sin intereses',
                    'months' => 6,
                    'enabled' => filter_var(
                        env('PAYPHONE_TOOL_SIX_MONTHS_ENABLED', false),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'pending_activation' => filter_var(
                        env('PAYPHONE_TOOL_SIX_MONTHS_PENDING_ACTIVATION', true),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'min_amount_cents' => (int) env('PAYPHONE_TOOL_SIX_MONTHS_MIN_AMOUNT_CENTS', 45000),
                    'surcharge_percent' => env('PAYPHONE_TOOL_SIX_MONTHS_SURCHARGE_PERCENT', '9.2'),
                    'uses_tool_surcharge_percent' => false,
                ],
            ],
        ],
    ],
    'webhooks' => [
        'paypal_strict' => (bool) env('PAYPAL_WEBHOOK_STRICT', true),
        'payphone_strict' => (bool) env('PAYPHONE_WEBHOOK_STRICT', true),
        'max_retries' => (int) env('PAYMENT_WEBHOOK_MAX_RETRIES', 5),
        'replay_window_seconds' => (int) env('PAYMENT_WEBHOOK_REPLAY_WINDOW', 900),
    ],
];
