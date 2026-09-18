<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_quote_returns_authoritative_pricing_and_totals(): void
    {
        $book = $this->sellableBook(price: 1700, quantity: 5);

        $this->postJson('/api/store/cart/quote', ['items' => [['slug' => $book->slug, 'quantity' => 2, 'price' => 1]]])
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price', '1700.00')
            ->assertJsonPath('data.items.0.line_total', '3400.00')
            ->assertJsonPath('data.subtotal', '3400.00')
            ->assertJsonPath('data.valid', true);
    }

    public function test_quote_rejects_draft_and_reports_insufficient_stock(): void
    {
        $draft = Book::factory()->create(['status' => 'draft', 'price' => 1000]);
        $low = $this->sellableBook(price: 1000, quantity: 1);

        $this->postJson('/api/store/cart/quote', ['items' => [['slug' => $draft->slug, 'quantity' => 1]]])
            ->assertOk()
            ->assertJsonPath('data.valid', false);

        $this->postJson('/api/store/cart/quote', ['items' => [['slug' => $low->slug, 'quantity' => 2]]])
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.items.0.available_quantity', 1);
    }

    public function test_malformed_quantity_is_rejected(): void
    {
        $book = $this->sellableBook();

        $this->postJson('/api/store/cart/quote', ['items' => [['slug' => $book->slug, 'quantity' => 0]]])
            ->assertUnprocessable();
    }

    public function test_valid_delivery_checkout_creates_pending_order_and_reserves_stock(): void
    {
        $book = $this->sellableBook(price: 1500, quantity: 5);

        $this->postJson('/api/store/checkout', $this->checkoutPayload($book, fulfillment: 'delivery'))
            ->assertCreated()
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.order_status', 'pending')
            ->assertJsonPath('data.total', '3000.00');

        $this->assertDatabaseHas('orders', ['customer_phone' => '254712345678', 'total' => '3000.00']);
        $this->assertDatabaseHas('order_items', ['title_snapshot' => $book->title, 'price_snapshot' => '1500.00', 'quantity' => 2]);
        $this->assertDatabaseHas('inventory_reservations', ['book_id' => $book->id, 'quantity' => 2, 'status' => 'active']);
        $this->assertDatabaseHas('inventory_items', ['book_id' => $book->id, 'quantity_on_hand' => 5, 'quantity_reserved' => 2]);
        $this->assertDatabaseHas('order_status_histories', ['type' => 'order_created']);
    }

    public function test_valid_pickup_checkout_requires_active_location(): void
    {
        $book = $this->sellableBook();
        $inactive = PickupLocation::factory()->create(['is_active' => false]);

        $this->postJson('/api/store/checkout', $this->checkoutPayload($book, fulfillment: 'pickup', pickupLocationId: $inactive->id))
            ->assertUnprocessable();

        $active = PickupLocation::factory()->create(['is_active' => true]);

        $this->postJson('/api/store/checkout', $this->checkoutPayload($book, fulfillment: 'pickup', pickupLocationId: $active->id, token: 'pickup-token'))
            ->assertCreated()
            ->assertJsonPath('data.fulfillment.method', 'pickup');
    }

    public function test_delivery_requires_address_information_and_invalid_phone_is_rejected(): void
    {
        $book = $this->sellableBook();
        $payload = $this->checkoutPayload($book, fulfillment: 'delivery');
        unset($payload['fulfillment']['address']);

        $this->postJson('/api/store/checkout', $payload)->assertUnprocessable();

        $payload = $this->checkoutPayload($book, fulfillment: 'delivery', token: 'bad-phone');
        $payload['customer']['phone'] = '123';

        $this->postJson('/api/store/checkout', $payload)->assertUnprocessable();
    }

    public function test_current_book_price_is_used_and_idempotent_retry_does_not_duplicate_order(): void
    {
        $book = $this->sellableBook(price: 1000, quantity: 5);
        $book->update(['price' => 1800]);
        $payload = $this->checkoutPayload($book, token: 'same-token');

        $this->postJson('/api/store/checkout', $payload)->assertCreated()->assertJsonPath('data.total', '3600.00');
        $this->postJson('/api/store/checkout', $payload)->assertCreated()->assertJsonPath('data.total', '3600.00');

        $this->assertSame(1, Order::count());
        $this->assertSame(1, InventoryReservation::count());
    }

    public function test_over_reservation_is_rejected_and_stock_on_hand_remains(): void
    {
        $book = $this->sellableBook(quantity: 1);

        $this->postJson('/api/store/checkout', $this->checkoutPayload($book))
            ->assertUnprocessable();

        $this->assertDatabaseHas('inventory_items', ['book_id' => $book->id, 'quantity_on_hand' => 1, 'quantity_reserved' => 0]);
    }

    public function test_release_reservation_is_idempotent_and_expired_command_cancels_order(): void
    {
        $book = $this->sellableBook(quantity: 5);
        $this->postJson('/api/store/checkout', $this->checkoutPayload($book))->assertCreated();
        $order = Order::firstOrFail();
        $reservation = $order->reservations()->firstOrFail();

        app(InventoryService::class)->releaseReservation($reservation);
        app(InventoryService::class)->releaseReservation($reservation);
        $this->assertDatabaseHas('inventory_items', ['book_id' => $book->id, 'quantity_reserved' => 0]);

        $this->postJson('/api/store/checkout', $this->checkoutPayload($book, token: 'expires'))->assertCreated();
        $expired = Order::where('checkout_token', 'expires')->firstOrFail();
        $expired->update(['reservation_expires_at' => now()->subMinute()]);

        $this->artisan('orders:release-expired')->assertSuccessful();

        $this->assertDatabaseHas('orders', ['id' => $expired->id, 'order_status' => 'cancelled']);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $expired->id, 'type' => 'reservation_expired']);
    }

    public function test_public_order_lookup_requires_token_and_hides_internal_fields(): void
    {
        $book = $this->sellableBook(quantity: 5);
        $response = $this->postJson('/api/store/checkout', $this->checkoutPayload($book))->assertCreated();
        $number = $response->json('data.order_number');
        $token = $response->json('data.public_token');

        $this->getJson("/api/store/orders/{$number}?token=wrong")->assertNotFound();

        $this->getJson("/api/store/orders/{$number}?token={$token}")
            ->assertOk()
            ->assertJsonMissingPath('data.admin_notes')
            ->assertJsonMissingPath('data.id');
    }

    public function test_admin_order_and_pickup_routes_are_protected_and_accessible(): void
    {
        $this->get('/orders')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get('/orders')->assertOk();
        $this->post('/pickup-locations', [
            'name' => 'Office',
            'address' => 'Nairobi',
            'is_active' => true,
            'sort_order' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('pickup_locations', ['name' => 'Office']);
    }

    protected function sellableBook(int $price = 1000, int $quantity = 10): Book
    {
        $book = Book::factory()->create(['status' => 'active', 'price' => $price, 'currency' => 'KES']);
        InventoryItem::factory()->create(['book_id' => $book->id, 'quantity_on_hand' => $quantity, 'quantity_reserved' => 0, 'track_stock' => true]);

        return $book;
    }

    protected function checkoutPayload(Book $book, string $fulfillment = 'delivery', ?int $pickupLocationId = null, string $token = 'checkout-token'): array
    {
        return [
            'checkout_token' => $token,
            'items' => [['slug' => $book->slug, 'quantity' => 2]],
            'customer' => [
                'name' => 'Jane Customer',
                'email' => 'jane@example.com',
                'phone' => '0712345678',
            ],
            'fulfillment' => $fulfillment === 'pickup'
                ? ['method' => 'pickup', 'pickup_location_id' => $pickupLocationId]
                : ['method' => 'delivery', 'address' => 'Moi Avenue', 'city' => 'Nairobi', 'county' => 'Nairobi'],
            'customer_note' => 'Please confirm availability.',
        ];
    }
}
