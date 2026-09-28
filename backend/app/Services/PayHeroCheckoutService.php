<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;

class PayHeroCheckoutService
{
    public function __construct(
        private CheckoutService $checkout,
        private PayHeroPaymentService $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{order: Order, payment: Payment}
     */
    public function checkoutAndPay(array $payload, string $checkoutToken): array
    {
        $order = $this->checkout->checkout($payload, $checkoutToken);

        if ($order->payment_status === 'paid') {
            $payment = Payment::query()
                ->where('order_id', $order->id)
                ->where('status', 'paid')
                ->latest('id')
                ->firstOrFail();

            return [
                'order' => $order->refresh()->load('items', 'pickupLocation'),
                'payment' => $payment,
            ];
        }

        $payment = $this->payments->initiate($order, $payload['payment']['phone']);

        return [
            'order' => $order->refresh()->load('items', 'pickupLocation'),
            'payment' => $payment->refresh(),
        ];
    }
}
