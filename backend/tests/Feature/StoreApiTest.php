<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_books_api_returns_active_published_books(): void
    {
        $book = Book::factory()->create(['status' => 'active', 'published_at' => now()->subDay()]);

        $this->getJson('/api/store/books')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $book->slug);
    }

    public function test_draft_hidden_and_future_books_are_excluded(): void
    {
        Book::factory()->create(['status' => 'draft', 'slug' => 'draft-book']);
        Book::factory()->create(['status' => 'hidden', 'slug' => 'hidden-book']);
        Book::factory()->create(['status' => 'active', 'published_at' => now()->addDay(), 'slug' => 'future-book']);

        $this->getJson('/api/store/books')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'draft-book'])
            ->assertJsonMissing(['slug' => 'hidden-book'])
            ->assertJsonMissing(['slug' => 'future-book']);
    }

    public function test_single_book_endpoint_resolves_by_slug_and_unknown_slug_returns_404(): void
    {
        $book = Book::factory()->create(['status' => 'active', 'slug' => 'public-book']);

        $this->getJson('/api/store/books/public-book')
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);

        $this->getJson('/api/store/books/missing-book')
            ->assertNotFound()
            ->assertJsonPath('message', 'Book not found.');
    }

    public function test_category_information_is_returned(): void
    {
        $category = BookCategory::factory()->create(['name' => 'Prayer', 'slug' => 'prayer']);
        Book::factory()->create(['status' => 'active', 'book_category_id' => $category->id]);

        $this->getJson('/api/store/books')
            ->assertOk()
            ->assertJsonPath('data.0.category.name', 'Prayer')
            ->assertJsonPath('data.0.category.slug', 'prayer');
    }

    public function test_availability_status_is_returned_without_internal_inventory_details(): void
    {
        $book = Book::factory()->create(['status' => 'active']);
        InventoryItem::factory()->create([
            'book_id' => $book->id,
            'quantity_on_hand' => 2,
            'quantity_reserved' => 0,
            'reorder_level' => 5,
            'track_stock' => true,
        ]);

        $this->getJson("/api/store/books/{$book->slug}")
            ->assertOk()
            ->assertJsonPath('data.availability', 'low_stock')
            ->assertJsonPath('data.available', true)
            ->assertJsonMissingPath('data.quantity_reserved')
            ->assertJsonMissingPath('data.inventory_item')
            ->assertJsonMissingPath('data.stock_movements');
    }

    public function test_featured_filtering_and_search_work(): void
    {
        Book::factory()->create(['status' => 'active', 'title' => 'Prayer That Works', 'featured' => true]);
        Book::factory()->create(['status' => 'active', 'title' => 'Leadership Notes', 'featured' => false]);

        $this->getJson('/api/store/books?featured=1')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Prayer That Works'])
            ->assertJsonMissing(['title' => 'Leadership Notes']);

        $this->getJson('/api/store/books?search=Leadership')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Leadership Notes'])
            ->assertJsonMissing(['title' => 'Prayer That Works']);
    }

    public function test_api_limit_is_bounded(): void
    {
        Book::factory()->count(55)->create(['status' => 'active']);

        $this->getJson('/api/store/books?limit=500')
            ->assertOk()
            ->assertJsonCount(50, 'data');
    }
}
