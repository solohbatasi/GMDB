<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PayHeroVerificationService
{
    public function __construct(
        private PayHeroClient $client,
        private MoneyService $money,
        private PaymentFinalizationService $finalizer,
    ) {}

    /** @param array<string, mixed> $payload */
    public function findForPayload(array $payload): ?Payment
    {
        $data = $this->data($payload);
        $references = array_values(array_filter(array_map(
            static fn ($value) => is_scalar($value) ? trim((string) $value) : '',
            [
                $data['external_reference'] ?? null,
                $data['reference'] ?? null,
                $data['CheckoutRequestID'] ?? $data['checkout_request_id'] ?? null,
            ],
        )));

        if ($references === []) {
            return null;
        }

        return Payment::query()
            ->where('provider', 'payhero')
            ->where(function ($query) use ($references) {
                $query->whereIn('external_reference', $references)
                    ->orWhereIn('payhero_reference', $references)
                    ->orWhereIn('provider_transaction_id', $references);
            })
            ->with('order')
            ->first();
    }

    public function verify(Payment $payment): Payment
    {
        $requestId = $payment->payhero_reference ?: $payment->external_reference;

        return $this->process($payment, $this->client->transactionStatus($requestId));
    }

    /** @param array<string, mixed> $response */
    public function process(Payment $payment, array $response): Payment
    {
        $storedPayHeroReference = $payment->payhero_reference;
        $data = $this->data($response);
        $status = strtolower(trim((string) ($data['status'] ?? '')));
        $externalReference = trim((string) ($data['external_reference'] ?? ''));
        $payHeroReference = trim((string) ($data['reference'] ?? ''));
        $checkoutRequestId = trim((string) ($data['CheckoutRequestID'] ?? $data['checkout_request_id'] ?? ''));
        $receipt = trim((string) ($data['provider_reference'] ?? ''));
        $transactionId = trim((string) ($data['transaction_id'] ?? ''));
        $description = trim((string) ($data['message'] ?? $data['description'] ?? $data['result_description'] ?? ''));

        $payment->update([
            'payhero_reference' => $payHeroReference ?: $payment->payhero_reference,
            'provider_reference' => $receipt ?: ($payHeroReference ?: $payment->provider_reference),
            'provider_transaction_id' => $checkoutRequestId ?: $payment->provider_transaction_id,
            'transaction_id' => $transactionId ?: ($receipt ?: $payment->transaction_id),
            'gateway_response' => json_encode($response, JSON_UNESCAPED_SLASHES),
            'result_description' => $description ?: $payment->result_description,
        ]);

        $payment = $payment->refresh();

        if ($externalReference !== '' && ! hash_equals($payment->external_reference, $externalReference)) {
            return $this->finalizer->markReviewRequired($payment, $response, 'PayHero external reference does not match the local payment.');
        }

        if ($payHeroReference !== '' && $storedPayHeroReference && ! hash_equals($storedPayHeroReference, $payHeroReference)) {
            return $this->finalizer->markReviewRequired($payment, $response, 'PayHero reference does not match the local payment.');
        }

        if (in_array($status, ['failed', 'failure', 'cancelled', 'canceled'], true) || ($data['success'] ?? null) === false) {
            $failedStatus = str_contains($status, 'cancel') ? 'cancelled' : 'failed';
            $payment->forceFill(['failure_message' => $description ?: 'The M-Pesa request was not completed.'])->save();

            return $this->finalizer->markFailed($payment->refresh(), $response, $failedStatus);
        }

        $successful = in_array($status, ['success', 'successful', 'completed', 'paid'], true)
            && ($data['success'] ?? true) !== false;

        if (! $successful) {
            if ($status !== '' && $payment->status !== 'paid') {
                $payment->forceFill(['status' => 'pending'])->save();
            }

            return $payment->refresh();
        }

        if ($externalReference === '' || $payHeroReference === '') {
            return $this->finalizer->markReviewRequired($payment, $response, 'Successful PayHero response is missing transaction references.');
        }

        if (! array_key_exists('amount', $data) || $this->money->decimalToCents($data['amount']) !== $this->money->decimalToCents($payment->amount)) {
            return $this->finalizer->markReviewRequired($payment, $response, 'PayHero amount does not match the local payment amount.');
        }

        if (strtoupper((string) ($data['currency'] ?? '')) !== strtoupper($payment->currency)) {
            return $this->finalizer->markReviewRequired($payment, $response, 'PayHero currency does not match the local payment currency.');
        }

        if (($data['transaction_type'] ?? 'inbound_payment') !== 'inbound_payment') {
            return $this->finalizer->markReviewRequired($payment, $response, 'PayHero transaction type is not an inbound payment.');
        }

        if (isset($data['provider']) && ! str_contains(preg_replace('/[^a-z0-9]/', '', strtolower((string) $data['provider'])), 'mpesa')) {
            return $this->finalizer->markReviewRequired($payment, $response, 'PayHero provider is not M-Pesa.');
        }

        return $this->finalizer->finalizeSuccessfulPayment($payment, $response);
    }

    public function safeVerify(Payment $payment): ?Payment
    {
        try {
            return $this->verify($payment);
        } catch (\Throwable $exception) {
            Log::warning('PayHero verification failed.', [
                'payment_id' => $payment->id,
                'external_reference' => $payment->external_reference,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function data(array $response): array
    {
        return is_array($response['data'] ?? null) ? $response['data'] : $response;
    }
}
