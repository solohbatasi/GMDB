<?php

namespace Tests\Feature;

use App\Exceptions\PayHeroRequestException;
use App\Models\Book;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\PayHeroClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase4PayHeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurePayHero();
    }

    public function test_client_uses_basic_auth_and_sends_required_stk_fields(): void
    {
        Http::fake([
            'https://payhero.test/api/v2/payments/initiate-stk-push' => Http::response($this->queuedPayload('ORDER-P1'), 201),
        ]);

        $this->app->make(PayHeroClient::class)->initiateStkPush([
            'amount' => 1000,
            'phone_number' => '254712345678',
            'provider' => 'm-pesa',
            'channel_id' => 123,
            'external_reference' => 'ORDER-P1',
            'callback_url' => 'https://example.test/backend/api/payments/payhero/callback',
        ]);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://payhero.test/api/v2/payments/initiate-stk-push'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('api-user:api-password'))
            && $request['amount'] === 1000
            && $request['phone_number'] === '254712345678'
            && $request['provider'] === 'm-pesa'
            && $request['channel_id'] === 123
            && $request['external_reference'] === 'ORDER-P1');
    }

    public function test_missing_credentials_fail_safely(): void
    {
        config(['payhero.username' => null]);

        $this->expectException(PayHeroRequestException::class);
        $this->expectExceptionMessage('M-Pesa is not configured.');

        $this->app->make(PayHeroClient::class)->transactionStatus('PH-REF');
    }

    public function test_checkout_starts_one_stk_prompt_and_duplicate_submit_reuses_attempt(): void
    {
        $book = $this->sellableBook();
        Http::fake(fn (Request $request) => Http::response($this->queuedPayload((string) $request['external_reference']), 201));
        $payload = $this->checkoutPayload($book, 'same-checkout-token');

        $first = $this->postJson('/api/store/checkout', $payload)
            ->assertCreated()
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.payhero_reference', 'PH-REF');
        $this->postJson('/api/store/checkout', $payload)
            ->assertCreated()
            ->assertJsonPath('payment.external_reference', $first->json('payment.external_reference'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_reservations', 1);
        Http::assertSentCount(1);
    }

    public function test_verified_callback_is_idempotent_and_commits_stock_once(): void
    {
        [$order, $payment] = $this->payableOrderAndPayment('CALLBACK-EXT');
        Http::fake([
            'https://status.payhero.test/api/global/transaction-status' => Http::response($this->successfulPayload($payment), 200),
        ]);
        $callback = [
            'status' => 'success',
            'success' => true,
            'reference' => $payment->payhero_reference,
            'external_reference' => $payment->external_reference,
        ];

        $this->postJson('/api/payments/payhero/callback', $callback)->assertOk();
        $this->postJson('/api/payments/payhero/callback', $callback)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'provider_reference' => 'SNL123ABC']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'order_status' => 'confirmed']);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'committed']);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    public function test_amount_mismatch_is_flagged_for_review_without_committing_stock(): void
    {
        [$order, $payment] = $this->payableOrderAndPayment('MISMATCH-EXT');
        $payload = $this->successfulPayload($payment);
        $payload['amount'] = 900;
        Http::fake(['https://status.payhero.test/*' => Http::response($payload)]);

        $this->postJson('/api/payments/payhero/callback', [
            'external_reference' => $payment->external_reference,
            'reference' => $payment->payhero_reference,
        ])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'review_required']);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'active']);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_failed_callback_keeps_reservation_active_and_allows_retry(): void
    {
        [$order, $payment] = $this->payableOrderAndPayment('FAILED-EXT');
        $failed = $this->successfulPayload($payment);
        $failed['status'] = 'failed';
        $failed['success'] = false;
        $failed['message'] = 'Customer cancelled the M-Pesa prompt.';
        Http::fake(['https://status.payhero.test/*' => Http::response($failed)]);

        $this->postJson('/api/payments/payhero/callback', [
            'external_reference' => $payment->external_reference,
            'reference' => $payment->payhero_reference,
        ])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'active']);
        $this->getJson("/api/store/payments/{$payment->external_reference}/status?token={$order->public_token}")
            ->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.can_retry', true);
    }

    public function test_definite_initiation_failure_can_retry_same_order(): void
    {
        $book = $this->sellableBook();
        $payload = $this->checkoutPayload($book, 'retry-token');
        $requests = 0;
        Http::fake(function (Request $request) use (&$requests) {
            $requests++;

            return $requests === 1
                ? Http::response(['message' => 'Request rejected'], 400)
                : Http::response($this->queuedPayload((string) $request['external_reference']), 201);
        });

        $this->postJson('/api/store/checkout', $payload)->assertStatus(502);
        $this->assertDatabaseHas('payments', ['status' => 'failed']);
        $this->assertDatabaseHas('orders', ['checkout_token' => 'retry-token', 'payment_status' => 'unpaid']);

        $this->postJson('/api/store/checkout', $payload)->assertCreated()->assertJsonPath('payment.status', 'pending');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('inventory_reservations', 1);
    }

    public function test_public_status_requires_order_token_and_reconciliation_updates_pending_payment(): void
    {
        [$order, $payment] = $this->payableOrderAndPayment('RECONCILE-EXT');
        $payment->forceFill(['created_at' => now()->subMinutes(10)])->save();
        Http::fake(['https://status.payhero.test/*' => Http::response($this->successfulPayload($payment))]);

        $this->getJson("/api/store/payments/{$payment->external_reference}/status?token=wrong")->assertNotFound();
        $this->assertSame(1, Payment::query()
            ->where('provider', 'payhero')
            ->whereIn('status', ['created', 'initiated', 'pending'])
            ->where('created_at', '<=', now()->subMinutes(2))
            ->count());
        $this->artisan('payments:reconcile-payhero')->expectsOutput('Reconciled PayHero payments: 1')->assertSuccessful();
        $this->getJson("/api/store/payments/{$payment->external_reference}/status?token={$order->public_token}")
            ->assertOk()
            ->assertJsonPath('data.status', 'successful');
    }

    public function test_unknown_callback_is_rejected_and_historical_paystack_row_remains_readable(): void
    {
        $this->postJson('/api/payments/payhero/callback', ['external_reference' => 'UNKNOWN'])->assertNotFound();

        $order = Order::factory()->create();
        Payment::factory()->create(['order_id' => $order->id, 'provider' => 'paystack', 'external_reference' => 'HISTORICAL-PAYSTACK']);
        $this->assertDatabaseHas('payments', ['provider' => 'paystack', 'external_reference' => 'HISTORICAL-PAYSTACK']);
    }

    private function configurePayHero(): void
    {
        config([
            'payhero.base_url' => 'https://payhero.test',
            'payhero.payments_path' => '/api/v2/payments/initiate-stk-push',
            'payhero.status_base_url' => 'https://status.payhero.test',
            'payhero.status_path' => '/api/global/transaction-status',
            'payhero.username' => 'api-user',
            'payhero.password' => 'api-password',
            'payhero.channel_id' => 123,
            'payhero.provider' => 'm-pesa',
            'payhero.callback_url' => 'https://example.test/backend/api/payments/payhero/callback',
            'payhero.reconcile_after_minutes' => 2,
        ]);
    }

    private function queuedPayload(string $externalReference): array
    {
        return [
            'success' => true,
            'status' => 'QUEUED',
            'reference' => 'PH-REF',
            'CheckoutRequestID' => 'ws_CO_123456',
            'external_reference' => $externalReference,
        ];
    }

    private function successfulPayload(Payment $payment): array
    {
        return [
            'status' => 'success',
            'success' => true,
            'reference' => $payment->payhero_reference,
            'external_reference' => $payment->external_reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'transaction_id' => 'TX-123',
            'transaction_type' => 'inbound_payment',
            'provider_reference' => 'SNL123ABC',
            'provider' => 'm-pesa',
        ];
    }

    private function sellableBook(): Book
    {
        $book = Book::factory()->create(['status' => 'active', 'price' => '1000.00', 'currency' => 'KES']);
        InventoryItem::factory()->create(['book_id' => $book->id, 'quantity_on_hand' => 5, 'quantity_reserved' => 0, 'track_stock' => true]);

        return $book;
    }

    private function checkoutPayload(Book $book, string $token): array
    {
        return [
            'checkout_token' => $token,
            'items' => [['slug' => $book->slug, 'quantity' => 1]],
            'customer' => ['name' => 'Jane Customer', 'email' => 'jane@example.com', 'phone' => '0712345678'],
            'payment' => ['phone' => '0712345678'],
            'fulfillment' => ['method' => 'delivery', 'address' => 'Moi Avenue', 'city' => 'Nairobi', 'county' => 'Nairobi'],
        ];
    }

    /** @return array{Order, Payment} */
    private function payableOrderAndPayment(string $externalReference): array
    {
        $book = $this->sellableBook();
        $book->inventoryItem()->update(['quantity_reserved' => 1]);
        $order = Order::factory()->create([
            'total' => '1000.00',
            'subtotal' => '1000.00',
            'currency' => 'KES',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'reservation_expires_at' => now()->addMinutes(10),
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'book_id' => $book->id, 'price_snapshot' => '1000.00', 'line_total' => '1000.00']);
        InventoryReservation::factory()->create(['order_id' => $order->id, 'book_id' => $book->id, 'quantity' => 1, 'status' => 'active']);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'payhero',
            'method' => 'mpesa_stk',
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
            'external_reference' => $externalReference,
            'payhero_reference' => 'PH-'.$externalReference,
            'provider_reference' => 'PH-'.$externalReference,
        ]);

        return [$order, $payment];
    }
}
