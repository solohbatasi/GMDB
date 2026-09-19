<?php

return [
    'base_url' => env('PAYHERO_BASE_URL', 'https://backend.payhero.co.ke'),
    'auth_token' => env('PAYHERO_AUTH_TOKEN'),
    'channel_id' => env('PAYHERO_CHANNEL_ID'),
    'provider' => env('PAYHERO_PROVIDER', 'm-pesa'),
    'callback_url' => env('PAYHERO_CALLBACK_URL'),
    'connect_timeout' => (int) env('PAYHERO_CONNECT_TIMEOUT', 10),
    'timeout' => (int) env('PAYHERO_TIMEOUT', 30),
    'reconcile_after_minutes' => (int) env('PAYHERO_RECONCILE_AFTER_MINUTES', 2),
    'paths' => [
        'payment_channels' => env('PAYHERO_PAYMENT_CHANNELS_PATH', '/api/v2/payment_channels'),
        'stk_push' => env('PAYHERO_STK_PUSH_PATH', '/api/v2/payments'),
        'transaction_status' => env('PAYHERO_TRANSACTION_STATUS_PATH', '/api/v2/transactions/{reference}'),
        'account_transactions' => env('PAYHERO_ACCOUNT_TRANSACTIONS_PATH', '/api/v2/transactions'),
    ],
];
