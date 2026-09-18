<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'GMD-'.now()->format('y').'-'.Str::upper(Str::random(6)),
            'public_token' => Str::random(48),
            'checkout_token' => Str::uuid()->toString(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '254712345678',
            'delivery_method' => 'delivery',
            'shipping_address' => fake()->streetAddress(),
            'shipping_city' => 'Nairobi',
            'shipping_county' => 'Nairobi',
            'subtotal' => '1000.00',
            'shipping_total' => '0.00',
            'discount_total' => '0.00',
            'tax_total' => '0.00',
            'total' => '1000.00',
            'currency' => 'KES',
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
            'fulfillment_status' => 'pending',
            'reservation_expires_at' => now()->addMinutes(15),
        ];
    }
}
