<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaystackVerificationService
{
    public function __construct(
        private PaystackClient $client,
        private MoneyService $money,
        private PaymentFinalizationService $finalizer,
    ) {}

    public function verify(Payment $payment): Payment
    {
        return $this->process($payment, $this->client->verifyTransaction($payment->external_reference));
    }

    public function verifyByReference(string $reference): ?Payment
    {
        $payment = Payment::query()
            ->where('provider', 'paystack')
            ->where(function ($query) use ($reference) {
                $query->where('external_reference', $reference)
                    ->orWhere('provider_reference', $reference);
            })
            ->with('order')
            ->first();

        return $payment ? $this->verify($payment) : null;
    }

    public function process(Payment $payment, array $response): Payment
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $reference = (string) ($data['reference'] ?? '');
        $status = (string) ($data['status'] ?? '');

        $payment->update([
            'provider_reference' => $reference ?: $payment->provider_reference,
            'provider_transaction_id' => isset($data['id']) ? (string) $data['id'] : $payment->provider_transaction_id,
            'transaction_id' => isset($data['id']) ? (string) $data['id'] : $payment->transaction_id,
            'channel' => $data['channel'] ?? $payment->channel,
            'gateway_response' => $data['gateway_response'] ?? $payment->gateway_response,
            'result_description' => $data['gateway_response'] ?? $payment->result_description,
        ]);

        $payment = $payment->refresh();

        if (($response['status'] ?? false) !== true) {
            return $this->finalizer->markReviewRequired($payment, $response, 'Paystack verification did not return a valid response.');
        }

        if (! hash_equals($payment->external_reference, $reference)) {
            return $this->finalizer->markReviewRequired($payment, $response, 'Paystack reference does not match the local payment attempt.');
        }

        if ($status !== 'success') {
            if (in_array($status, ['failed', 'abandoned'], true)) {
                return $this->finalizer->markFailed($payment, $response + ['paystack_status' => $status]);
            }

            return $payment;
        }

        if ((int) ($data['amount'] ?? 0) !== $this->money->toPaystackSubunit($payment->amount, $payment->currency)) {
            return $this->finalizer->markReviewRequired($payment, $response, 'Paystack amount does not match the local payment amount.');
        }

        if (strtoupper((string) ($data['currency'] ?? '')) !== strtoupper($payment->currency)) {
            return $this->finalizer->markReviewRequired($payment, $response, 'Paystack currency does not match the local payment currency.');
        }

        if (! empty($data['paid_at']) && ! $payment->paid_at) {
            $payment->forceFill(['paid_at' => $data['paid_at']])->save();
        }

        return $this->finalizer->finalizeSuccessfulPayment($payment->refresh(), $response);
    }

    public function safeVerify(Payment $payment): ?Payment
    {
        try {
            return $this->verify($payment);
        } catch (\Throwable $exception) {
            Log::warning('Paystack verification failed.', [
                'payment_id' => $payment->id,
                'reference' => $payment->external_reference,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
