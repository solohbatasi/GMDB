<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PayHeroPaymentService
{
    public function __construct(
        private PayHeroClient $client,
        private PhoneNumberService $phones,
    ) {}

    public function initiate(Order $order, string $token, string $method, ?string $phone = null): Payment
    {
        if (! hash_equals($order->public_token, $token)) {
            throw new InvalidArgumentException('Invalid order token.');
        }

        if ($order->payment_status === 'paid') {
            throw new InvalidArgumentException('This order has already been paid.');
        }

        if ($order->order_status !== 'pending' || $order->payment_status !== 'unpaid') {
            throw new InvalidArgumentException('This order is not payable.');
        }

        if (! $order->reservation_expires_at || $order->reservation_expires_at->isPast()) {
            throw new InvalidArgumentException('The reservation for this order has expired.');
        }

        if ($order->reservations()->where('status', 'active')->doesntExist()) {
            throw new InvalidArgumentException('No active stock reservation remains for this order.');
        }

        if (! in_array($method, ['mpesa_stk'], true)) {
            throw new InvalidArgumentException('Please choose a supported payment method.');
        }

        $channelId = (string) config('payhero.channel_id');
        $callbackUrl = (string) config('payhero.callback_url');

        if ($channelId === '' || $callbackUrl === '') {
            throw new RuntimeException('PayHero payment channel is not configured.');
        }

        $channel = $this->client->configuredChannel();

        if (! $channel) {
            throw new RuntimeException('Configured PayHero payment channel could not be verified.');
        }

        $paymentPhone = $this->phones->normalizeKenyanMobile($phone ?: $order->customer_phone);
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'payhero',
            'method' => $method,
            'source' => 'stk',
            'amount' => $order->total,
            'currency' => $order->currency,
            'payer_phone' => $paymentPhone,
            'status' => 'created',
            'channel_id' => $channelId,
            'external_reference' => $this->externalReference($order),
            'metadata' => ['channel' => $this->sanitizeChannel($channel)],
        ]);

        $payload = [
            'amount' => $order->total,
            'phone_number' => $paymentPhone,
            'channel_id' => $channelId,
            'provider' => config('payhero.provider'),
            'external_reference' => $payment->external_reference,
            'callback_url' => $callbackUrl,
        ];

        $response = $this->client->initiateStkPush($payload);

        $payment->update([
            'status' => 'pending',
            'payhero_reference' => $response['reference'] ?? $response['payhero_reference'] ?? $response['CheckoutRequestID'] ?? null,
            'provider_reference' => $response['provider_reference'] ?? $response['MerchantRequestID'] ?? null,
            'transaction_id' => $response['transaction_id'] ?? $response['id'] ?? null,
            'result_code' => isset($response['code']) ? (string) $response['code'] : null,
            'result_description' => $response['message'] ?? $response['description'] ?? null,
            'initiated_at' => now(),
            'metadata' => array_filter([
                'channel' => $this->sanitizeChannel($channel),
                'initiation_payload' => $payload,
                'initiation_response' => $response,
            ]),
        ]);

        $order->update(['payment_status' => 'pending']);

        return $payment->refresh();
    }

    protected function externalReference(Order $order): string
    {
        do {
            $reference = $order->order_number.'-'.Str::upper(Str::random(4));
        } while (Payment::where('external_reference', $reference)->exists());

        return $reference;
    }

    protected function sanitizeChannel(array $channel): array
    {
        unset($channel['secret'], $channel['token'], $channel['password'], $channel['authorization']);

        return $channel;
    }
}
