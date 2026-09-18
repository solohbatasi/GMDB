<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'book_category_id' => BookCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'isbn' => fake()->optional()->isbn13(),
            'author' => fake()->name(),
            'publisher' => fake()->optional()->company(),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'cover_image' => null,
            'price' => fake()->randomFloat(2, 0, 5000),
            'compare_price' => null,
            'currency' => 'KES',
            'featured' => false,
            'is_digital' => false,
            'status' => fake()->randomElement(Book::STATUSES),
            'published_at' => now(),
            'seo_title' => null,
            'seo_description' => null,
            'external_purchase_url' => fake()->optional()->url(),
        ];
    }
}
