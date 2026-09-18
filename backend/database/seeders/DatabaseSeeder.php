<?php

namespace Database\Seeders;

use App\Models\PickupLocation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        PickupLocation::firstOrCreate(
            ['name' => 'Global Ministries Daily Bread Office'],
            [
                'address' => 'Nairobi',
                'city' => 'Nairobi',
                'county' => 'Nairobi',
                'instructions' => 'Pickup details will be confirmed by the ministry team.',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }
}
