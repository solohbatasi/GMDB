<?php

namespace App\Services;

use App\Exceptions\PayHeroRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayHeroClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateStkPush(array $payload): array
    {
        try {
            $response = $this->request((string) config('payhero.base_url'))
                ->post(ltrim((string) config('payhero.payments_path'), '/'), $payload);
        } catch (ConnectionException $exception) {
            throw new PayHeroRequestException(
                'The M-Pesa request outcome could not be confirmed. Please wait while we check it.',
                true,
                previous: $exception,
            );
        }

        $data = is_array($response->json()) ? $response->json() : [];

        if (! $response->successful()) {
            Log::warning('PayHero initiation rejected.', [
                'status' => $response->status(),
                'external_reference' => $payload['external_reference'] ?? null,
            ]);

            throw new PayHeroRequestException(
                $this->safeMessage($data, 'M-Pesa could not be started. Please check the phone number and try again.'),
                false,
                $response->status(),
                $data,
            );
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function transactionStatus(string $requestId): array
    {
        try {
            $response = $this->request((string) config('payhero.status_base_url'))
                ->post(ltrim((string) config('payhero.status_path'), '/'), ['request_id' => $requestId]);
        } catch (ConnectionException $exception) {
            throw new PayHeroRequestException('PayHero status is temporarily unavailable.', true, previous: $exception);
        }

        $data = is_array($response->json()) ? $response->json() : [];

        if (! $response->successful()) {
            throw new PayHeroRequestException(
                $this->safeMessage($data, 'PayHero status is temporarily unavailable.'),
                false,
                $response->status(),
                $data,
            );
        }

        return $data;
    }

    private function request(string $baseUrl): PendingRequest
    {
        $username = (string) config('payhero.username');
        $password = (string) config('payhero.password');

        if ($username === '' || $password === '') {
            throw new PayHeroRequestException('M-Pesa is not configured. Please contact support.');
        }

        return Http::baseUrl(rtrim($baseUrl, '/').'/')
            ->acceptJson()
            ->asJson()
            ->withBasicAuth($username, $password)
            ->connectTimeout((int) config('payhero.connect_timeout', 10))
            ->timeout((int) config('payhero.timeout', 30));
    }

    /** @param array<string, mixed> $data */
    private function safeMessage(array $data, string $fallback): string
    {
        foreach (['message', 'error_message', 'description'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '') {
                return mb_substr(strip_tags($data[$key]), 0, 300);
            }
        }

        return $fallback;
    }
}
