<?php

namespace App\Services;

use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PickupLocation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private CartQuoteService $quotes,
        private InventoryService $inventory,
        private PhoneNumberService $phones,
        private OrderNumberService $orderNumbers,
    ) {}

    /** @param array<string, mixed> $payload */
    public function checkout(array $payload, string $checkoutToken): Order
    {
        $existing = Order::query()
            ->with('items', 'pickupLocation', 'reservations')
            ->where('checkout_token', $checkoutToken)
            ->first();

        if ($existing && ($this->isPayable($existing) || $existing->payment_status === 'paid')) {
            return $existing;
        }

        if ($existing) {
            $this->retireStaleCheckout($existing, $checkoutToken);
        }

        try {
            return DB::transaction(function () use ($payload, $checkoutToken) {
                $quote = $this->quotes->quote($payload['items']);

                if (! $quote['valid']) {
                    throw new InvalidArgumentException('One or more cart items are unavailable in the requested quantity.');
                }

                $books = $this->quotes->publicBooksBySlug(array_column($payload['items'], 'slug'));
                $phone = $this->phones->normalizeKenyanMobile($payload['customer']['phone']);
                $fulfillment = $payload['fulfillment'];
                $expiresAt = now()->addMinutes((int) config('store.reservation_minutes', 15));
                $pickupLocationId = null;

                if ($fulfillment['method'] === 'pickup') {
                    $pickupLocationId = PickupLocation::query()
                        ->whereKey($fulfillment['pickup_location_id'])
                        ->where('is_active', true)
                        ->value('id');

                    if (! $pickupLocationId) {
                        throw new InvalidArgumentException('Please choose a valid pickup location.');
                    }
                }

                $order = Order::create([
                    'order_number' => $this->orderNumbers->generate(),
                    'public_token' => Str::random(48),
                    'checkout_token' => $checkoutToken,
                    'customer_name' => $payload['customer']['name'],
                    'customer_email' => $payload['customer']['email'],
                    'customer_phone' => $phone,
                    'delivery_method' => $fulfillment['method'],
                    'shipping_address' => $fulfillment['method'] === 'delivery' ? ($fulfillment['address'] ?? null) : null,
                    'shipping_city' => $fulfillment['method'] === 'delivery' ? ($fulfillment['city'] ?? null) : null,
                    'shipping_county' => $fulfillment['method'] === 'delivery' ? ($fulfillment['county'] ?? null) : null,
                    'pickup_location_id' => $pickupLocationId,
                    'subtotal' => $quote['subtotal'],
                    'shipping_total' => '0.00',
                    'discount_total' => '0.00',
                    'tax_total' => '0.00',
                    'total' => $quote['total'],
                    'currency' => $quote['currency'],
                    'payment_status' => 'unpaid',
                    'order_status' => 'pending',
                    'fulfillment_status' => 'pending',
                    'customer_notes' => $payload['customer_note'] ?? null,
                    'reservation_expires_at' => $expiresAt,
                ]);

                foreach ($quote['items'] as $item) {
                    $book = $books->get($item['slug']);

                    $order->items()->create([
                        'book_id' => $book?->id,
                        'title_snapshot' => $item['title'],
                        'sku_snapshot' => $item['sku'] ?? null,
                        'price_option_snapshot' => $item['price_option'],
                        'price_snapshot' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['line_total'],
                    ]);

                    if ($book) {
                        $this->inventory->reserve($order, $book, (int) $item['quantity'], $expiresAt);
                    }
                }

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'type' => 'order_created',
                    'to_status' => 'pending',
                    'notes' => 'Order created and inventory reserved.',
                    'metadata' => ['checkout_token' => $checkoutToken],
                    'created_at' => now(),
                ]);

                return $order->load('items', 'pickupLocation', 'reservations');
            });
        } catch (QueryException $exception) {
            // A concurrent request may have inserted the same unique checkout token.
            $existing = Order::query()
                ->with('items', 'pickupLocation', 'reservations')
                ->where('checkout_token', $checkoutToken)
                ->first();

            if ($existing) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function isPayable(Order $order): bool
    {
        return $order->order_status === 'pending'
            && in_array($order->payment_status, ['unpaid', 'pending'], true)
            && (! $order->reservation_expires_at || now()->lessThan($order->reservation_expires_at));
    }

    private function retireStaleCheckout(Order $order, string $checkoutToken): void
    {
        DB::transaction(function () use ($order, $checkoutToken) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->checkout_token !== $checkoutToken || $locked->payment_status === 'paid' || $this->isPayable($locked)) {
                return;
            }

            $reservations = InventoryReservation::query()
                ->where('order_id', $locked->id)
                ->where('status', 'active')
                ->get();

            foreach ($reservations as $reservation) {
                $this->inventory->releaseReservation($reservation);
            }

            $updates = [
                'checkout_token' => substr($checkoutToken, 0, 80).':closed:'.$locked->id.':'.Str::lower(Str::random(6)),
            ];

            if ($locked->order_status === 'pending') {
                $updates += [
                    'order_status' => 'cancelled',
                    'fulfillment_status' => 'cancelled',
                    'cancelled_at' => $locked->cancelled_at ?: now(),
                ];
            }

            $locked->update($updates);

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'type' => 'checkout_restarted',
                'from_status' => $order->order_status,
                'to_status' => $updates['order_status'] ?? $locked->order_status,
                'notes' => 'Stale checkout session was retired so the customer could create a fresh order.',
                'created_at' => now(),
            ]);
        });
    }

    public function releaseExpired(): int
    {
        $count = 0;

        Order::query()
            ->whereIn('payment_status', ['unpaid', 'pending'])
            ->where('order_status', 'pending')
            ->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<=', now())
            ->with('reservations')
            ->chunkById(50, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$count) {
                        $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                        if ($order->order_status !== 'pending' || ! in_array($order->payment_status, ['unpaid', 'pending'], true)) {
                            return;
                        }

                        $reservations = InventoryReservation::query()
                            ->where('order_id', $order->id)
                            ->where('status', 'active')
                            ->get();

                        foreach ($reservations as $reservation) {
                            $this->inventory->releaseReservation($reservation);
                        }

                        $order->update([
                            'order_status' => 'cancelled',
                            'fulfillment_status' => 'cancelled',
                            'cancelled_at' => now(),
                        ]);

                        OrderStatusHistory::create([
                            'order_id' => $order->id,
                            'type' => 'reservation_expired',
                            'from_status' => 'pending',
                            'to_status' => 'cancelled',
                            'notes' => 'Unpaid reservation expired and was released.',
                            'created_at' => now(),
                        ]);

                        $count++;
                    });
                }
            });

        return $count;
    }
}
