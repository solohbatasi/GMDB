<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayHeroClient
{
    public function paymentChannels(): array
    {
        return $this->get($this->path('payment_channels'));
    }

    public function initiateStkPush(array $payload): array
    {
        return $this->post($this->path('stk_push'), $payload);
    }

    public function transactionStatus(string $reference): array
    {
        $path = str_replace('{reference}', rawurlencode($reference), $this->path('transaction_status'));

        return $this->get($path);
    }

    public function accountTransactions(array $query = []): array
    {
        return $this->get($this->path('account_transactions'), $query);
    }

    public function configuredChannel(): ?array
    {
        $configured = (string) config('payhero.channel_id');

        if ($configured === '') {
            return null;
        }

        $response = $this->paymentChannels();
        $channels = $response['data'] ?? $response['payment_channels'] ?? $response['channels'] ?? $response;

        if (! is_array($channels)) {
            return null;
        }

        foreach ($channels as $channel) {
            if (! is_array($channel)) {
                continue;
            }

            $id = (string) ($channel['id'] ?? $channel['uuid'] ?? $channel['channel_id'] ?? '');

            if ($id === $configured) {
                return $channel;
            }
        }

        return null;
    }

    protected function get(string $path, array $query = []): array
    {
        return $this->decode($this->request()->get($path, $query)->json() ?? []);
    }

    protected function post(string $path, array $payload): array
    {
        return $this->decode($this->request()->post($path, $payload)->json() ?? []);
    }

    protected function request(): PendingRequest
    {
        $token = (string) config('payhero.auth_token');

        if ($token === '') {
            throw new RuntimeException('PayHero is not configured.');
        }

        return Http::baseUrl(rtrim((string) config('payhero.base_url'), '/'))
            ->withHeaders([
                'Authorization' => 'Basic '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout((int) config('payhero.connect_timeout', 10))
            ->timeout((int) config('payhero.timeout', 30))
            ->throw();
    }

    protected function path(string $key): string
    {
        return (string) config("payhero.paths.{$key}");
    }

    protected function decode(array $response): array
    {
        return $response;
    }
}
