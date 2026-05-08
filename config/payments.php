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
        'base_url' => env('PAYPHONE_BASE_URL', 'https://pay.payphonetodoesposible.com/api'),
        'token' => env('PAYPHONE_TOKEN'),
        'store_id' => env('PAYPHONE_STORE_ID'),
        'currency' => env('PAYPHONE_CURRENCY', 'USD'),
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
