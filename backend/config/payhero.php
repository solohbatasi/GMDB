<?php

return [
    'base_url' => env('PAYHERO_BASE_URL', 'https://backend.payhero.co.ke'),
    'payments_path' => env('PAYHERO_PAYMENTS_PATH', '/api/v2/payments/initiate-stk-push'),
    'status_base_url' => env('PAYHERO_STATUS_BASE_URL', 'https://api.payhero.africa'),
    'status_path' => env('PAYHERO_STATUS_PATH', '/api/global/transaction-status'),
    'username' => env('PAYHERO_USERNAME'),
    'password' => env('PAYHERO_PASSWORD'),
    'channel_id' => env('PAYHERO_CHANNEL_ID'),
    'provider' => env('PAYHERO_PROVIDER', 'm-pesa'),
    'callback_url' => env('PAYHERO_CALLBACK_URL') ?: rtrim((string) env('APP_URL'), '/').'/api/payments/payhero/callback',
    'storefront_url' => rtrim((string) env('STOREFRONT_URL', '/'), '/'),
    'connect_timeout' => (int) env('PAYHERO_CONNECT_TIMEOUT', 10),
    'timeout' => (int) env('PAYHERO_TIMEOUT', 30),
    'reconcile_after_minutes' => (int) env('PAYHERO_RECONCILE_AFTER_MINUTES', 2),
];
