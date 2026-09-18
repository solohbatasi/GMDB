<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookstoreAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_bookstore_admin_pages(): void
    {
        $this->get('/books')->assertRedirect(route('login'));
        $this->get('/book-categories')->assertRedirect(route('login'));
        $this->get('/inventory')->assertRedirect(route('login'));
    }

    public function test_category_creation_works(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/book-categories', [
                'name' => 'Prayer',
                'slug' => 'prayer',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('book_categories', ['slug' => 'prayer']);
    }

    public function test_book_creation_opens_inventory_and_stock_movement(): void
    {
        $category = BookCategory::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post('/books', $this->bookPayload(['book_category_id' => $category->id, 'opening_quantity' => 7]))
            ->assertRedirect();

        $book = Book::where('slug', 'test-book')->firstOrFail();

        $this->assertDatabaseHas('inventory_items', [
            'book_id' => $book->id,
            'sku' => 'SKU-001',
            'quantity_on_hand' => 7,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'book_id' => $book->id,
            'type' => 'opening',
            'quantity_change' => 7,
            'quantity_before' => 0,
            'quantity_after' => 7,
        ]);
    }

    public function test_book_slug_must_be_unique(): void
    {
        Book::factory()->create(['slug' => 'test-book']);

        $this->actingAs(User::factory()->create())
            ->post('/books', $this->bookPayload())
            ->assertSessionHasErrors('slug');
    }

    public function test_restock_increases_stock_and_creates_history(): void
    {
        [$book] = $this->bookWithInventory(2);

        $this->actingAs(User::factory()->create())
            ->post("/inventory/{$book->id}/restock", ['quantity' => 5, 'reference' => 'PO-1'])
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_items', ['book_id' => $book->id, 'quantity_on_hand' => 7]);
        $this->assertDatabaseHas('stock_movements', ['book_id' => $book->id, 'type' => 'restock', 'quantity_change' => 5, 'quantity_before' => 2, 'quantity_after' => 7]);
    }

    public function test_negative_adjustment_works_when_stock_exists(): void
    {
        [$book] = $this->bookWithInventory(5);

        $this->actingAs(User::factory()->create())
            ->post("/inventory/{$book->id}/adjust", ['quantity_change' => -3, 'reason' => 'Stock count correction'])
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_items', ['book_id' => $book->id, 'quantity_on_hand' => 2]);
        $this->assertDatabaseHas('stock_movements', ['book_id' => $book->id, 'type' => 'adjustment', 'quantity_change' => -3]);
    }

    public function test_negative_adjustment_is_rejected_when_it_would_make_stock_negative(): void
    {
        [$book] = $this->bookWithInventory(2);

        $this->actingAs(User::factory()->create())
            ->from('/inventory')
            ->post("/inventory/{$book->id}/adjust", ['quantity_change' => -3, 'reason' => 'Missing'])
            ->assertSessionHasErrors('quantity_change');

        $this->assertDatabaseHas('inventory_items', ['book_id' => $book->id, 'quantity_on_hand' => 2]);
    }

    public function test_low_out_of_stock_and_disabled_stock_detection_work(): void
    {
        [, $low] = $this->bookWithInventory(3, 5);
        [, $out] = $this->bookWithInventory(0, 5);
        [, $disabled] = $this->bookWithInventory(0, 5, false);

        $this->assertSame('Low Stock', $low->fresh()->stock_status);
        $this->assertSame('Out of Stock', $out->fresh()->stock_status);
        $this->assertSame('Stock Tracking Disabled', $disabled->fresh()->stock_status);
    }

    public function test_book_cover_upload_validation_works(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/books', $this->bookPayload(['cover' => UploadedFile::fake()->create('cover.pdf', 20, 'application/pdf')]))
            ->assertSessionHasErrors('cover');
    }

    public function test_legacy_importer_is_idempotent(): void
    {
        Storage::fake('public');

        $this->artisan('books:import-legacy')->assertSuccessful();
        $firstCount = Book::count();
        $firstMovementCount = StockMovement::count();

        $this->artisan('books:import-legacy')->assertSuccessful();

        $this->assertSame($firstCount, Book::count());
        $this->assertSame($firstMovementCount, StockMovement::count());
    }

    protected function bookPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Test Book',
            'slug' => 'test-book',
            'author' => 'Duke Fitz-Theodore Randolph',
            'price' => 1000,
            'currency' => 'KES',
            'status' => 'active',
            'featured' => false,
            'is_digital' => false,
            'sku' => 'SKU-001',
            'opening_quantity' => 0,
            'reorder_level' => 5,
            'track_stock' => true,
        ], $overrides);
    }

    protected function bookWithInventory(int $quantity, int $reorderLevel = 5, bool $trackStock = true): array
    {
        $book = Book::factory()->create(['status' => 'active']);
        $inventory = InventoryItem::factory()->create([
            'book_id' => $book->id,
            'quantity_on_hand' => $quantity,
            'reorder_level' => $reorderLevel,
            'track_stock' => $trackStock,
        ]);

        return [$book, $inventory];
    }
}
