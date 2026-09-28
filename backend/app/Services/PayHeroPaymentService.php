<?php

namespace App\Services;

use App\Exceptions\PayHeroRequestException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PayHeroPaymentService
{
    public function __construct(
        private PayHeroClient $client,
        private PhoneNumberService $phones,
        private PaymentFinalizationService $finalization,
    ) {}

    public function initiate(Order $order, string $phone): Payment
    {
        $phone = $this->phones->normalizeKenyanMobile($phone);

        [$payment, $shouldInitiate] = DB::transaction(function () use ($order, $phone) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->payment_status === 'paid') {
                throw new InvalidArgumentException('This order is already paid.');
            }

            if ($order->order_status !== 'pending' || ($order->reservation_expires_at && now()->greaterThan($order->reservation_expires_at))) {
                throw new InvalidArgumentException('This order is no longer available for payment.');
            }

            $active = Payment::query()
                ->where('order_id', $order->id)
                ->where('provider', 'payhero')
                ->whereIn('status', ['created', 'initiated', 'pending'])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($active) {
                return [$active, false];
            }

            $attempt = Payment::query()->where('order_id', $order->id)->where('provider', 'payhero')->count() + 1;
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => 'payhero',
                'method' => 'mpesa_stk',
                'source' => 'store_checkout',
                'amount' => $order->total,
                'currency' => $order->currency,
                'payer_phone' => $phone,
                'status' => 'created',
                'channel_id' => (string) config('payhero.channel_id'),
                'channel' => 'mpesa',
                'external_reference' => $order->order_number.'-P'.$attempt.'-'.Str::upper(Str::random(8)),
                'metadata' => ['attempt' => $attempt],
            ]);

            $order->update(['payment_status' => 'pending']);

            return [$payment, true];
        });

        if (! $shouldInitiate) {
            return $payment->refresh();
        }

        $payload = [
            'amount' => (float) $payment->amount,
            'phone_number' => $phone,
            'provider' => (string) config('payhero.provider', 'm-pesa'),
            'channel_id' => (int) config('payhero.channel_id'),
            'external_reference' => $payment->external_reference,
            'callback_url' => (string) config('payhero.callback_url'),
        ];

        try {
            if ((int) config('payhero.channel_id') <= 0) {
                throw new PayHeroRequestException('M-Pesa is not configured. Please contact support.');
            }

            $response = $this->client->initiateStkPush($payload);
            $reference = trim((string) ($response['reference'] ?? ''));
            $checkoutRequestId = trim((string) ($response['CheckoutRequestID'] ?? $response['checkout_request_id'] ?? ''));
            $status = strtoupper((string) ($response['status'] ?? ''));

            if (($response['success'] ?? true) === false || $reference === '' || $checkoutRequestId === '' || ! in_array($status, ['QUEUED', 'PENDING', 'PROCESSING'], true)) {
                throw new PayHeroRequestException(
                    'PayHero returned an incomplete M-Pesa response. Please wait while we check it.',
                    true,
                    responseData: $response,
                );
            }

            DB::transaction(function () use ($payment, $response, $reference, $checkoutRequestId) {
                $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                $locked->update([
                    'status' => 'pending',
                    'payhero_reference' => $reference,
                    'provider_reference' => $reference,
                    'provider_transaction_id' => $checkoutRequestId,
                    'gateway_response' => json_encode($response, JSON_UNESCAPED_SLASHES),
                    'result_description' => (string) ($response['message'] ?? 'M-Pesa prompt queued.'),
                    'initiated_at' => now(),
                    'failure_message' => null,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $locked->order_id,
                    'type' => 'payment_initiated',
                    'from_status' => 'unpaid',
                    'to_status' => 'pending',
                    'notes' => 'PayHero M-Pesa STK prompt queued.',
                    'metadata' => [
                        'payment_id' => $locked->id,
                        'external_reference' => $locked->external_reference,
                        'payhero_reference' => $reference,
                        'checkout_request_id' => $checkoutRequestId,
                    ],
                    'created_at' => now(),
                ]);
            });

            return $payment->refresh();
        } catch (PayHeroRequestException $exception) {
            $payment->update([
                'failure_message' => $exception->getMessage(),
                'result_description' => $exception->getMessage(),
                'gateway_response' => $exception->responseData === [] ? null : json_encode($exception->responseData, JSON_UNESCAPED_SLASHES),
                'initiated_at' => now(),
                'status' => $exception->outcomeUnknown ? 'pending' : 'failed',
            ]);

            if (! $exception->outcomeUnknown) {
                $this->finalization->markFailed($payment->refresh(), $exception->responseData);
            }

            throw $exception;
        }
    }
}
