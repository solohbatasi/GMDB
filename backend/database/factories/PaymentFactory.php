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
            'provider' => 'paystack',
            'method' => 'hosted_checkout',
            'source' => 'paystack_checkout',
            'amount' => '1000.00',
            'currency' => 'KES',
            'status' => 'created',
            'channel' => 'card',
            'external_reference' => 'GMD-TEST-'.Str::upper(Str::random(8)),
        ];
    }
}
