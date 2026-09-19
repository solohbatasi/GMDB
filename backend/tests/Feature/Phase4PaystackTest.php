<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\PaystackClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase4PaystackTest extends TestCase
{
    use RefreshDatabase;

    public function test_paystack_client_uses_bearer_token_and_initializes_transaction(): void
    {
        $this->configurePaystack();

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.test/pay', 'access_code' => 'ACCESS', 'reference' => 'REF'],
            ]),
        ]);

        $this->app->make(PaystackClient::class)->initializeTransaction([
            'email' => 'jane@example.com',
            'amount' => 100000,
            'reference' => 'REF',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.paystack.co/transaction/initialize'
            && $request->hasHeader('Authorization', 'Bearer secret-key')
            && $request['amount'] === 100000);
    }

    public function test_missing_paystack_secret_fails_safely(): void
    {
        config(['paystack.secret_key' => null]);

        $this->expectExceptionMessage('Paystack is not configured.');

        $this->app->make(PaystackClient::class)->verifyTransaction('REF');
    }

    public function test_order_payment_initialization_returns_hosted_checkout_url(): void
    {
        $this->configurePaystack();
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.test/pay',
                    'access_code' => 'ACCESS',
                    'reference' => 'PSTK-REF',
                ],
            ]),
        ]);
        $order = $this->payableOrder();

        $this->postJson("/api/store/orders/{$order->order_number}/payments/paystack", [
            'token' => $order->public_token,
        ])
            ->assertCreated()
            ->assertJsonPath('data.authorization_url', 'https://checkout.paystack.test/pay');

        $payment = Payment::firstOrFail();
        $this->assertSame('paystack', $payment->provider);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('PSTK-REF', $payment->provider_reference);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
    }

    public function test_return_verification_marks_payment_paid_and_commits_stock(): void
    {
        $this->configurePaystack();
        $order = $this->payableOrder();
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
            'external_reference' => 'GMD-VERIFY',
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/GMD-VERIFY' => Http::response($this->verifyPayload('GMD-VERIFY')),
        ]);

        $this->postJson('/api/store/payments/paystack/verify', ['reference' => 'GMD-VERIFY'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.channel', 'card');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'provider_reference' => 'GMD-VERIFY']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'order_status' => 'confirmed']);
        $this->assertDatabaseHas('inventory_reservations', ['order_id' => $order->id, 'status' => 'committed']);
    }

    public function test_paystack_webhook_requires_signature_and_is_idempotent(): void
    {
        $this->configurePaystack();
        $order = $this->payableOrder();
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
            'external_reference' => 'GMD-WEBHOOK',
        ]);
        Http::fake([
            'https://api.paystack.co/transaction/verify/GMD-WEBHOOK' => Http::response($this->verifyPayload('GMD-WEBHOOK')),
        ]);
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'GMD-WEBHOOK']]);

        $this->postJson('/api/payments/paystack/webhook', json_decode($body, true))->assertUnauthorized();

        $signature = hash_hmac('sha512', $body, 'secret-key');
        $this->call('POST', '/api/payments/paystack/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
        ], $body)->assertOk();
        $this->call('POST', '/api/payments/paystack/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
        ], $body)->assertOk();

        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_amount_mismatch_goes_to_review_required(): void
    {
        $this->configurePaystack();
        $order = $this->payableOrder();
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
            'external_reference' => 'GMD-REVIEW',
        ]);
        Http::fake([
            'https://api.paystack.co/transaction/verify/GMD-REVIEW' => Http::response($this->verifyPayload('GMD-REVIEW', amount: 90000)),
        ]);

        $this->postJson('/api/store/payments/paystack/verify', ['reference' => 'GMD-REVIEW'])->assertOk();

        $this->assertDatabaseHas('payments', ['external_reference' => 'GMD-REVIEW', 'status' => 'review_required']);
    }

    public function test_reconcile_command_verifies_pending_paystack_payments(): void
    {
        $this->configurePaystack();
        $order = $this->payableOrder();
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
            'external_reference' => 'GMD-REC',
            'created_at' => now()->subMinutes(10),
        ]);
        Http::fake([
            'https://api.paystack.co/transaction/verify/GMD-REC' => Http::response($this->verifyPayload('GMD-REC')),
        ]);

        $this->artisan('payments:reconcile-paystack')->assertSuccessful();

        $this->assertDatabaseHas('payments', ['external_reference' => 'GMD-REC', 'status' => 'paid']);
    }

    protected function configurePaystack(): void
    {
        config([
            'paystack.base_url' => 'https://api.paystack.co',
            'paystack.secret_key' => 'secret-key',
            'paystack.public_key' => 'public-key',
            'paystack.callback_url' => 'https://example.test/?p=payment-return',
            'paystack.currency' => 'KES',
            'paystack.channels' => '',
        ]);
    }

    protected function payableOrder(): Order
    {
        $book = Book::factory()->create(['status' => 'active', 'price' => '1000.00', 'currency' => 'KES']);
        InventoryItem::factory()->create(['book_id' => $book->id, 'quantity_on_hand' => 5, 'quantity_reserved' => 1, 'track_stock' => true]);
        $order = Order::factory()->create(['total' => '1000.00', 'subtotal' => '1000.00', 'currency' => 'KES']);
        OrderItem::factory()->create(['order_id' => $order->id, 'book_id' => $book->id, 'price_snapshot' => '1000.00', 'line_total' => '1000.00']);
        InventoryReservation::factory()->create(['order_id' => $order->id, 'book_id' => $book->id, 'quantity' => 1, 'status' => 'active']);

        return $order;
    }

    protected function verifyPayload(string $reference, int $amount = 100000, string $currency = 'KES', string $status = 'success'): array
    {
        return [
            'status' => true,
            'message' => 'Verification successful',
            'data' => [
                'id' => 123456,
                'status' => $status,
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'channel' => 'card',
                'gateway_response' => 'Successful',
                'paid_at' => now()->toIso8601String(),
            ],
        ];
    }
}
