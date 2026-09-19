<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient
{
    public function initializeTransaction(array $payload): array
    {
        return $this->request()
            ->post('transaction/initialize', $payload)
            ->throw()
            ->json();
    }

    public function verifyTransaction(string $reference): array
    {
        return $this->request()
            ->get('transaction/verify/'.rawurlencode($reference))
            ->throw()
            ->json();
    }

    protected function request(): PendingRequest
    {
        $secretKey = (string) config('paystack.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('Paystack is not configured.');
        }

        return Http::baseUrl(rtrim((string) config('paystack.base_url'), '/'))
            ->withToken($secretKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('paystack.connect_timeout', 10))
            ->timeout((int) config('paystack.timeout', 30));
    }
}
