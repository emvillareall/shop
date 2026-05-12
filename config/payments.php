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
    ],
    'webhooks' => [
        'paypal_strict' => (bool) env('PAYPAL_WEBHOOK_STRICT', true),
        'payphone_strict' => (bool) env('PAYPHONE_WEBHOOK_STRICT', true),
        'max_retries' => (int) env('PAYMENT_WEBHOOK_MAX_RETRIES', 5),
        'replay_window_seconds' => (int) env('PAYMENT_WEBHOOK_REPLAY_WINDOW', 900),
    ],
];
