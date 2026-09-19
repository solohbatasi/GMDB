<?php

return [
    'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'currency' => env('PAYSTACK_CURRENCY', 'KES'),
    'callback_url' => env('PAYSTACK_CALLBACK_URL'),
    'channels' => env('PAYSTACK_CHANNELS', ''),
    'connect_timeout' => (int) env('PAYSTACK_CONNECT_TIMEOUT', 10),
    'timeout' => (int) env('PAYSTACK_TIMEOUT', 30),
    'verify_after_minutes' => (int) env('PAYSTACK_VERIFY_AFTER_MINUTES', 2),
];
