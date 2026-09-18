<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'sku' => 'BK-'.Str::upper(Str::random(8)),
            'quantity_on_hand' => fake()->numberBetween(0, 50),
            'quantity_reserved' => 0,
            'reorder_level' => 5,
            'track_stock' => true,
        ];
    }
}
