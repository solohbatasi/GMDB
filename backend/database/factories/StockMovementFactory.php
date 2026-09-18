<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $before = fake()->numberBetween(0, 50);
        $change = fake()->numberBetween(1, 10);

        return [
            'book_id' => Book::factory(),
            'order_id' => null,
            'user_id' => null,
            'type' => fake()->randomElement(StockMovement::TYPES),
            'quantity_change' => $change,
            'quantity_before' => $before,
            'quantity_after' => $before + $change,
            'reference' => fake()->optional()->bothify('REF-####'),
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }
}
