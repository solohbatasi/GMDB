<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
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
            'book_id' => Book::factory(),
            'title_snapshot' => fake()->sentence(3),
            'sku_snapshot' => fake()->bothify('BK-####'),
            'price_snapshot' => '1000.00',
            'quantity' => 1,
            'line_total' => '1000.00',
        ];
    }
}
