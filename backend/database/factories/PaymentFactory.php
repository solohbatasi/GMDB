<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'payhero',
            'method' => 'mpesa_stk',
            'source' => 'stk',
            'amount' => '1000.00',
            'currency' => 'KES',
            'payer_phone' => '254712345678',
            'status' => 'created',
            'channel_id' => 'test-channel',
            'external_reference' => 'GMD-TEST-'.Str::upper(Str::random(8)),
        ];
    }
}
