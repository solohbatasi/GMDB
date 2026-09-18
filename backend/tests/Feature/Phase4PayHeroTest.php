<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PayHeroClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase4PayHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_payhero_client_uses_basic_token_base_url_and_configured_channel(): void
    {
        $this->configurePayHero();

        Http::fake([
            'https://backend.payhero.co.ke/api/v2/payment_channels' => Http::response([
                'data' => [['id' => 'channel-123', 'status' => 'active']],
            ]),
        ]);

        $this->app->make(PayHeroClient::class)->configuredChannel();

        Http::assertSent(fn ($request) => $request->url() === 'https://backend.payhero.co.ke/api/v2/payment_channels'
            && $request->header('Authorization')[0] === 'Basic secret-token');
    }

    public function test_missing_payhero_token_fails_safely(): void
    {
        config(['payhero.auth_token' => null]);

        $this->expectException(\RuntimeException::class);

        $this->app->make(PayHeroClient::class)->paymentChannels();
    }

    public function test_valid_unpaid_order_can_start_payment_without_marking_paid(): void
    {
        $this->configurePayHero();
        $order = $this->reservedOrder(total: '1500.00');
        $this->fakeSuccessfulInitiation();

        $this->postJson("/api/store/orders/{$order->order_number}/payments/payhero", [
            'token' => $order->public_token,
            'method' => 'mpesa_stk',
            'phone' => '0712345678',
            'amount' => '1.00',
        ])->assertAccepted()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => '1500.00',
            'payer_phone' => '254712345678',
            'status' => 'pending',
            'channel_id' => 'channel-123',
            'payhero_reference' => 'PH-123',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending', 'order_status' => 'pending', 'paid_at' => null]);
    }

    public function test_invalid_token_paid_and_expired_orders_are_rejected(): void
    {
        $this->configurePayHero();
        $order = $this->reservedOrder();

        $this->postJson("/api/store/orders/{$order->order_number}/payments/payhero", [
            'token' => 'wrong',
            'method' => 'mpesa_stk',
        ])->assertUnprocessable();

        $order->update(['payment_status' => 'paid']);
        $this->postJson("/api/store/orders/{$order->order_number}/payments/payhero", [
            'token' => $order->public_token,
            'method' => 'mpesa_stk',
        ])->assertUnprocessable();

        $expired = $this->reservedOrder(token: 'expired-token');
        $expired->update(['reservation_expires_at' => now()->subMinute()]);
        $this->postJson("/api/store/orders/{$expired->order_number}/payments/payhero", [
            'token' => $expired->public_token,
            'method' => 'mpesa_stk',
        ])->assertUnprocessable();
    }

    public function test_success_callback_marks_paid_commits_reservation_and_creates_sale_movement(): void
    {
        $order = $this->reservedOrder(total: '1000.00', quantity: 2, stock: 5);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => '1000.00',
            'currency' => 'KES',
            'status' => 'pending',
            'external_reference' => 'GMD-CB-1',
            'channel_id' => 'channel-123',
        ]);

        $this->postJson('/api/payments/payhero/callback', [
            'external_reference' => 'GMD-CB-1',
            'status' => 'paid',
            'amount' => '1000.00',
            'currency' => 'KES',
            'channel_id' => 'channel-123',
            'provider_reference' => 'MPESA123',
            'transaction_id' => 'TX123',
        ])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'provider_reference' => 'MPESA123']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'order_status' => 'confirmed']);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'committed']);
        $this->assertDatabaseHas('inventory_items', ['book_id' => $order->items->first()->book_id, 'quantity_on_hand' => 3, 'quantity_reserved' => 0]);
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'type' => 'sale', 'quantity_change' => -2]);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'type' => 'payment_confirmed']);
    }

    public function test_duplicate_success_callback_is_idempotent(): void
    {
        $order = $this->reservedOrder(total: '1000.00', quantity: 1, stock: 2);
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => '1000.00',
            'status' => 'pending',
            'external_reference' => 'GMD-DUP',
            'channel_id' => 'channel-123',
        ]);
        $payload = ['external_reference' => 'GMD-DUP', 'status' => 'paid', 'amount' => '1000.00', 'currency' => 'KES', 'channel_id' => 'channel-123'];

        $this->postJson('/api/payments/payhero/callback', $payload)->assertOk();
        $this->postJson('/api/payments/payhero/callback', $payload)->assertOk();

        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('type', 'sale')->count());
        $this->assertSame(1, InventoryReservation::where('order_id', $order->id)->where('status', 'committed')->count());
    }

    public function test_failed_payment_does_not_release_stock_and_retry_creates_new_attempt(): void
    {
        $this->configurePayHero();
        $order = $this->reservedOrder(total: '1000.00', quantity: 1, stock: 2);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => '1000.00',
            'status' => 'pending',
            'external_reference' => 'GMD-FAIL',
        ]);

        $this->postJson('/api/payments/payhero/callback', ['external_reference' => 'GMD-FAIL', 'status' => 'failed'])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'active']);

        $this->fakeSuccessfulInitiation(reference: 'PH-RETRY');
        $this->postJson("/api/store/orders/{$order->order_number}/payments/payhero", [
            'token' => $order->public_token,
            'method' => 'mpesa_stk',
        ])->assertAccepted();

        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
    }

    public function test_amount_mismatch_and_late_success_after_release_require_review(): void
    {
        $order = $this->reservedOrder(total: '1000.00');
        Payment::factory()->create(['order_id' => $order->id, 'amount' => '1000.00', 'status' => 'pending', 'external_reference' => 'GMD-REVIEW']);

        $this->postJson('/api/payments/payhero/callback', ['external_reference' => 'GMD-REVIEW', 'status' => 'paid', 'amount' => '900.00'])->assertOk();
        $this->assertDatabaseHas('payments', ['external_reference' => 'GMD-REVIEW', 'status' => 'review_required']);

        $late = $this->reservedOrder(token: 'late-token');
        $latePayment = Payment::factory()->create(['order_id' => $late->id, 'amount' => '1000.00', 'status' => 'pending', 'external_reference' => 'GMD-LATE']);
        $late->reservations()->update(['status' => 'released', 'released_at' => now()]);
        $late->update(['order_status' => 'cancelled']);

        $this->postJson('/api/payments/payhero/callback', ['external_reference' => 'GMD-LATE', 'status' => 'paid', 'amount' => '1000.00'])->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $latePayment->id, 'status' => 'review_required']);
    }

    public function test_public_order_response_exposes_safe_payment_state_not_secrets(): void
    {
        config(['payhero.auth_token' => 'secret-token']);
        $order = $this->reservedOrder();
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'pending', 'method' => 'mpesa_stk']);

        $this->getJson("/api/store/orders/{$order->order_number}?token={$order->public_token}")
            ->assertOk()
            ->assertJsonPath('data.latest_payment_status', 'pending')
            ->assertJsonPath('data.payment_method', 'mpesa_stk')
            ->assertJsonPath('data.can_retry_payment', true)
            ->assertJsonMissing(['secret-token']);
    }

    public function test_reconciliation_uses_transaction_status_to_finalize_payment(): void
    {
        $this->configurePayHero();
        $order = $this->reservedOrder();
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => '1000.00',
            'status' => 'pending',
            'external_reference' => 'GMD-REC',
            'payhero_reference' => 'PH-REC',
            'created_at' => now()->subMinutes(5),
        ]);

        Http::fake([
            'https://backend.payhero.co.ke/api/v2/transactions/PH-REC' => Http::response([
                'external_reference' => 'GMD-REC',
                'status' => 'paid',
                'amount' => '1000.00',
                'currency' => 'KES',
            ]),
        ]);

        $this->artisan('payments:reconcile-payhero')->assertSuccessful();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
    }

    public function test_admin_payments_are_protected_and_visible(): void
    {
        $order = $this->reservedOrder();
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'review_required']);

        $this->get('/payments')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get('/payments')->assertOk();
        $this->get('/orders/'.$order->id)->assertOk();
    }

    protected function configurePayHero(): void
    {
        config([
            'payhero.base_url' => 'https://backend.payhero.co.ke',
            'payhero.auth_token' => 'secret-token',
            'payhero.channel_id' => 'channel-123',
            'payhero.callback_url' => 'https://example.test/api/payments/payhero/callback',
        ]);
    }

    protected function fakeSuccessfulInitiation(string $reference = 'PH-123'): void
    {
        Http::fake([
            'https://backend.payhero.co.ke/api/v2/payment_channels' => Http::response([
                'data' => [['id' => 'channel-123', 'status' => 'active']],
            ]),
            'https://backend.payhero.co.ke/api/v2/payments' => Http::response([
                'reference' => $reference,
                'provider_reference' => 'PROVIDER-123',
                'message' => 'Accepted',
            ]),
        ]);
    }

    protected function reservedOrder(string $total = '1000.00', int $quantity = 1, int $stock = 5, string $token = 'public-token'): Order
    {
        $book = Book::factory()->create(['status' => 'active', 'price' => $total, 'currency' => 'KES']);
        InventoryItem::factory()->create(['book_id' => $book->id, 'quantity_on_hand' => $stock, 'quantity_reserved' => $quantity, 'track_stock' => true]);
        $order = Order::factory()->create([
            'public_token' => $token,
            'total' => $total,
            'subtotal' => $total,
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
            'reservation_expires_at' => now()->addMinutes(15),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'book_id' => $book->id,
            'price_snapshot' => $total,
            'quantity' => $quantity,
            'line_total' => $total,
        ]);
        InventoryReservation::factory()->create([
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => $quantity,
            'status' => 'active',
            'expires_at' => $order->reservation_expires_at,
        ]);

        return $order->load('items', 'reservations');
    }
}
