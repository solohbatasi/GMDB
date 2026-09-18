<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => null,
            'type' => 'order_created',
            'from_status' => null,
            'to_status' => 'pending',
            'notes' => fake()->sentence(),
            'metadata' => null,
            'created_at' => now(),
        ];
    }
}
