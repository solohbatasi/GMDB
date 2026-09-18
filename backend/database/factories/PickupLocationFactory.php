<?php

namespace Database\Factories;

use App\Models\PickupLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupLocation>
 */
class PickupLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Pickup',
            'address' => fake()->streetAddress(),
            'city' => 'Nairobi',
            'county' => 'Nairobi',
            'instructions' => fake()->optional()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
