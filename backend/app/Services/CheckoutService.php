<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PickupLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private CartQuoteService $quotes,
        private InventoryService $inventory,
        private MoneyService $money,
        private PhoneNumberService $phones,
        private OrderNumberService $orderNumbers,
    ) {}

    public function checkout(array $payload, string $checkoutToken): Order
    {
        $existing = Order::query()
            ->with('items', 'pickupLocation', 'reservations')
            ->where('checkout_token', $checkoutToken)
            ->first();

        if ($existing) {
            return $existing;
        }

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

                        foreach ($order->reservations()->where('status', 'active')->get() as $reservation) {
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
