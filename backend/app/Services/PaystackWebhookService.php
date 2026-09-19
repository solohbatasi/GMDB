<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PaystackWebhookService
{
    public function __construct(private PaystackVerificationService $verification) {}

    public function handle(string $rawBody, ?string $signature): bool
    {
        $secretKey = (string) config('paystack.secret_key');

        if ($secretKey === '' || ! is_string($signature) || ! hash_equals(hash_hmac('sha512', $rawBody, $secretKey), $signature)) {
            return false;
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return false;
        }

        if (($payload['event'] ?? '') !== 'charge.success') {
            return true;
        }

        $reference = (string) ($payload['data']['reference'] ?? '');

        if ($reference === '') {
            Log::warning('Paystack webhook missing reference.', ['event' => $payload['event'] ?? null]);

            return true;
        }

        return (bool) $this->verification->verifyByReference($reference);
    }
}
