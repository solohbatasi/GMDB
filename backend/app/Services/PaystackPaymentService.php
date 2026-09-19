<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackPaymentService
{
    public function __construct(
        private PaystackClient $client,
        private MoneyService $money,
    ) {}

    public function initialize(Order $order, string $token): Payment
    {
        if (! hash_equals((string) $order->public_token, $token)) {
            throw new RuntimeException('Order not found.');
        }

        if ($order->payment_status === 'paid') {
            throw new RuntimeException('This order has already been paid.');
        }

        if ($order->order_status !== 'pending' || ! $order->reservation_expires_at || $order->reservation_expires_at->isPast()) {
            throw new RuntimeException('The stock reservation for this order has expired.');
        }

        if ($order->reservations()->where('status', 'active')->doesntExist()) {
            throw new RuntimeException('No active stock reservation remains for this order.');
        }

        if ($this->money->decimalToCents($order->total) <= 0) {
            throw new RuntimeException('This order has no payable total.');
        }

        if (! filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid customer email is required for Paystack checkout.');
        }

        $callbackUrl = (string) config('paystack.callback_url');

        if ($callbackUrl === '') {
            throw new RuntimeException('Paystack callback URL is not configured.');
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'paystack',
            'method' => 'hosted_checkout',
            'source' => 'paystack_checkout',
            'amount' => $order->total,
            'currency' => strtoupper($order->currency ?: (string) config('paystack.currency', 'KES')),
            'status' => 'created',
            'external_reference' => $this->externalReference($order),
            'metadata' => [
                'order_number' => $order->order_number,
                'customer_email' => $order->customer_email,
            ],
        ]);

        $payload = array_filter([
            'email' => $order->customer_email,
            'amount' => $this->money->toPaystackSubunit($payment->amount, $payment->currency),
            'currency' => $payment->currency,
            'reference' => $payment->external_reference,
            'callback_url' => $callbackUrl,
            'metadata' => json_encode([
                'order_number' => $order->order_number,
                'payment_id' => $payment->id,
                'customer_name' => $order->customer_name,
            ]),
            'channels' => $this->channels(),
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->client->initializeTransaction($payload);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        if (($response['status'] ?? false) !== true || empty($data['authorization_url'])) {
            $payment->update([
                'status' => 'failed',
                'failure_message' => $response['message'] ?? 'Paystack did not return a checkout URL.',
                'metadata' => array_filter(['initialize_response' => $response]),
                'failed_at' => now(),
            ]);

            throw new RuntimeException($payment->failure_message);
        }

        $payment->update([
            'status' => 'pending',
            'provider_reference' => $data['reference'] ?? $payment->external_reference,
            'authorization_url' => $data['authorization_url'],
            'access_code' => $data['access_code'] ?? null,
            'initiated_at' => now(),
            'metadata' => array_filter([
                'initialize_response' => [
                    'status' => $response['status'] ?? null,
                    'message' => $response['message'] ?? null,
                    'reference' => $data['reference'] ?? null,
                ],
                'previous' => $payment->metadata,
            ]),
        ]);

        $order->update(['payment_status' => 'pending']);

        return $payment->refresh();
    }

    protected function externalReference(Order $order): string
    {
        $base = preg_replace('/[^A-Za-z0-9-]/', '', $order->order_number) ?: 'GDMB';
        $attempt = $order->payments()->count() + 1;

        do {
            $reference = $base.'-P'.$attempt.'-'.Str::upper(Str::random(6));
            $attempt++;
        } while (Payment::where('external_reference', $reference)->exists());

        return $reference;
    }

    protected function channels(): ?array
    {
        $channels = array_filter(array_map(
            fn (string $channel) => trim($channel),
            explode(',', (string) config('paystack.channels'))
        ));

        return $channels === [] ? null : array_values($channels);
    }
}
