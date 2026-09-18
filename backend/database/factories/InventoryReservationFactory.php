<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\InventoryReservation;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryReservation>
 */
class InventoryReservationFactory extends Factory
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
            'quantity' => 1,
            'status' => 'active',
            'expires_at' => now()->addMinutes(15),
            'released_at' => null,
            'committed_at' => null,
        ];
    }
}
