<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PayHeroCallbackService
{
    public function __construct(private PayHeroVerificationService $verification) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): string
    {
        Log::info('PayHero callback received.', [
            'status' => $payload['status'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'external_reference' => $payload['external_reference'] ?? null,
        ]);

        $payment = $this->verification->findForPayload($payload);

        if (! $payment) {
            Log::warning('PayHero callback payment was not found.', [
                'reference' => $payload['reference'] ?? null,
                'external_reference' => $payload['external_reference'] ?? null,
            ]);

            return 'unknown';
        }

        try {
            $this->verification->process($payment, $payload);
        } catch (\Throwable $exception) {
            Log::warning('PayHero callback processing failed.', [
                'payment_id' => $payment->id,
                'external_reference' => $payment->external_reference,
                'message' => $exception->getMessage(),
            ]);

            return 'deferred';
        }

        return 'processed';
    }
}
