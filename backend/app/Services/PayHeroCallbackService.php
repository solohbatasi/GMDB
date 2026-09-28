<?php

namespace App\Services;

class PayHeroCallbackService
{
    public function __construct(private PayHeroVerificationService $verification) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): string
    {
        $payment = $this->verification->findForPayload($payload);

        if (! $payment) {
            return 'unknown';
        }

        $result = $this->verification->safeVerify($payment);

        return $result ? 'processed' : 'deferred';
    }
}
