<?php

namespace App\Services;

use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentFinalizationService
{
    public function __construct(
        private InventoryService $inventory,
        private MoneyService $money,
    ) {}

    public function finalizeSuccessfulPayment(Payment $payment, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $payload) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'paid') {
                return $payment->refresh();
            }

            if ($order->payment_status === 'paid') {
                $payment->update([
                    'status' => 'review_required',
                    'result_description' => 'Order was already paid by another attempt.',
                    'metadata' => $this->mergeMetadata($payment, $payload),
                ]);

                return $payment->refresh();
            }

            if (! in_array($order->payment_status, ['unpaid', 'pending'], true) || $order->order_status !== 'pending') {
                return $this->markReviewRequired($payment, $payload, 'Order is no longer payable.');
            }

            if ($order->reservation_expires_at && $order->reservation_expires_at->isPast()) {
                return $this->markReviewRequired($payment, $payload, 'Successful payment arrived after reservation expiry.');
            }

            if ($this->money->decimalToCents($payment->amount) !== $this->money->decimalToCents($order->total)) {
                return $this->markReviewRequired($payment, $payload, 'Payment amount does not match order total.');
            }

            if (strtoupper($payment->currency) !== strtoupper($order->currency)) {
                return $this->markReviewRequired($payment, $payload, 'Payment currency does not match order currency.');
            }

            $activeReservations = InventoryReservation::query()
                ->where('order_id', $order->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            if ($activeReservations->isEmpty()) {
                return $this->markReviewRequired($payment, $payload, 'No active reservation remains to commit.');
            }

            foreach ($activeReservations as $reservation) {
                $this->inventory->commitReservation($reservation, $order, $payment->external_reference);
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => $this->mergeMetadata($payment, $payload),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'confirmed',
                'paid_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'type' => 'payment_confirmed',
                'from_status' => 'unpaid',
                'to_status' => 'paid',
                'notes' => 'Payment verified through PayHero and inventory committed.',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'external_reference' => $payment->external_reference,
                    'provider_reference' => $payment->provider_reference,
                ],
                'created_at' => now(),
            ]);

            return $payment->refresh();
        });
    }

    public function markFailed(Payment $payment, array $payload, string $status = 'failed'): Payment
    {
        return DB::transaction(function () use ($payment, $payload, $status) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'paid') {
                return $payment;
            }

            $payment->update([
                'status' => $status,
                'failed_at' => $status === 'failed' ? now() : $payment->failed_at,
                'cancelled_at' => $status === 'cancelled' ? now() : $payment->cancelled_at,
                'metadata' => $this->mergeMetadata($payment, $payload),
            ]);

            if ($order->payment_status === 'pending' && $order->payments()->whereIn('status', ['created', 'initiated', 'pending'])->doesntExist()) {
                $order->update(['payment_status' => 'unpaid']);
            }

            return $payment->refresh();
        });
    }

    public function markReviewRequired(Payment $payment, array $payload, string $reason): Payment
    {
        $payment->update([
            'status' => 'review_required',
            'result_description' => $reason,
            'metadata' => $this->mergeMetadata($payment, $payload + ['review_reason' => $reason]),
        ]);

        return $payment->refresh();
    }

    protected function mergeMetadata(Payment $payment, array $payload): array
    {
        return array_filter([
            'payhero' => $payload,
            'previous' => $payment->metadata,
        ]);
    }
}
