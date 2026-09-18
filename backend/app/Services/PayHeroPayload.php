<?php

namespace App\Services;

class PayHeroPayload
{
    public function success(array $payload): bool
    {
        $status = strtolower((string) $this->first($payload, ['status', 'payment_status', 'transaction_status', 'ResultDesc']));
        $code = (string) $this->first($payload, ['result_code', 'ResultCode', 'response_code', 'code']);

        return in_array($status, ['success', 'successful', 'paid', 'completed', 'complete'], true) || $code === '0';
    }

    public function failed(array $payload): bool
    {
        $status = strtolower((string) $this->first($payload, ['status', 'payment_status', 'transaction_status']));

        return in_array($status, ['failed', 'failure', 'cancelled', 'canceled', 'timeout', 'expired'], true);
    }

    public function cancelled(array $payload): bool
    {
        $status = strtolower((string) $this->first($payload, ['status', 'payment_status', 'transaction_status']));
        $description = strtolower((string) $this->description($payload));

        return in_array($status, ['cancelled', 'canceled'], true) || str_contains($description, 'cancel');
    }

    public function amount(array $payload): ?string
    {
        $amount = $this->first($payload, ['amount', 'Amount', 'trans_amount', 'TransAmount']);

        return is_numeric($amount) ? number_format((float) $amount, 2, '.', '') : null;
    }

    public function currency(array $payload): ?string
    {
        $currency = $this->first($payload, ['currency', 'Currency']);

        return $currency ? strtoupper((string) $currency) : null;
    }

    public function externalReference(array $payload): ?string
    {
        return $this->string($this->first($payload, ['external_reference', 'externalReference', 'reference', 'Reference', 'account_reference', 'AccountReference']));
    }

    public function payheroReference(array $payload): ?string
    {
        return $this->string($this->first($payload, ['payhero_reference', 'payheroReference', 'reference_id', 'CheckoutRequestID']));
    }

    public function providerReference(array $payload): ?string
    {
        return $this->string($this->first($payload, ['provider_reference', 'providerReference', 'mpesa_receipt', 'MpesaReceiptNumber', 'receipt_number']));
    }

    public function transactionId(array $payload): ?string
    {
        return $this->string($this->first($payload, ['transaction_id', 'transactionId', 'TransactionID', 'TransID', 'id']));
    }

    public function channelId(array $payload): ?string
    {
        return $this->string($this->first($payload, ['channel_id', 'channelId', 'payment_channel_id']));
    }

    public function resultCode(array $payload): ?string
    {
        return $this->string($this->first($payload, ['result_code', 'ResultCode', 'response_code', 'code']));
    }

    public function description(array $payload): ?string
    {
        return $this->string($this->first($payload, ['result_description', 'ResultDesc', 'description', 'message']));
    }

    public function first(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $payload[$key];
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $nested = $this->first($value, $keys);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    protected function string(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 255);
    }
}
