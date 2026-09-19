<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PayHeroCallbackService
{
    public function __construct(
        private PayHeroPayload $payload,
        private PaymentFinalizationService $finalizer,
    ) {}

    public function handle(array $payload): ?Payment
    {
        $payment = $this->findPayment($payload);

        if (! $payment) {
            Log::warning('PayHero callback could not be matched to a payment.', [
                'external_reference' => $this->payload->externalReference($payload),
                'payhero_reference' => $this->payload->payheroReference($payload),
                'provider_reference' => $this->payload->providerReference($payload),
                'transaction_id' => $this->payload->transactionId($payload),
            ]);

            return null;
        }

        $payment->fill([
            'payhero_reference' => $this->payload->payheroReference($payload) ?? $payment->payhero_reference,
            'provider_reference' => $this->payload->providerReference($payload) ?? $payment->provider_reference,
            'transaction_id' => $this->payload->transactionId($payload) ?? $payment->transaction_id,
            'result_code' => $this->payload->resultCode($payload) ?? $payment->result_code,
            'result_description' => $this->payload->description($payload) ?? $payment->result_description,
        ])->save();

        if ($this->hasConflict($payment, $payload)) {
            return $this->finalizer->markReviewRequired($payment, $payload, 'PayHero callback values conflict with the local payment attempt.');
        }

        if ($this->payload->success($payload)) {
            return $this->finalizer->finalizeSuccessfulPayment($payment, $payload);
        }

        if ($this->payload->failed($payload)) {
            return $this->finalizer->markFailed($payment, $payload, $this->payload->cancelled($payload) ? 'cancelled' : 'failed');
        }

        $payment->update([
            'status' => 'pending',
            'metadata' => array_filter(['callback' => $payload, 'previous' => $payment->metadata]),
        ]);

        return $payment->refresh();
    }

    protected function findPayment(array $payload): ?Payment
    {
        $external = $this->payload->externalReference($payload);
        $payhero = $this->payload->payheroReference($payload);
        $provider = $this->payload->providerReference($payload);
        $transaction = $this->payload->transactionId($payload);

        if (! $external && ! $payhero && ! $provider && ! $transaction) {
            return null;
        }

        return Payment::query()
            ->when($external, fn ($query) => $query->orWhere('external_reference', $external))
            ->when($payhero, fn ($query) => $query->orWhere('payhero_reference', $payhero))
            ->when($provider, fn ($query) => $query->orWhere('provider_reference', $provider))
            ->when($transaction, fn ($query) => $query->orWhere('transaction_id', $transaction))
            ->first();
    }

    protected function hasConflict(Payment $payment, array $payload): bool
    {
        $amount = $this->payload->amount($payload);
        $currency = $this->payload->currency($payload);
        $channel = $this->payload->channelId($payload);

        if ($amount !== null && $amount !== $payment->amount) {
            return true;
        }

        if ($currency !== null && strtoupper($currency) !== strtoupper($payment->currency)) {
            return true;
        }

        if ($channel !== null && $payment->channel_id !== null && $channel !== $payment->channel_id) {
            return true;
        }

        $providerReference = $this->payload->providerReference($payload);
        $transactionId = $this->payload->transactionId($payload);

        if ($providerReference && Payment::where('provider_reference', $providerReference)->whereKeyNot($payment->id)->exists()) {
            return true;
        }

        return $transactionId && Payment::where('transaction_id', $transactionId)->whereKeyNot($payment->id)->exists();
    }
}
