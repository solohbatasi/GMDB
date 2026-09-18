<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\BookCategory;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportLegacyBooks extends Command
{
    protected $signature = 'books:import-legacy {--fresh-covers : Replace already copied cover images}';

    protected $description = 'Import legacy PHP book data into the Laravel bookstore tables.';

    public function handle(InventoryService $inventoryService): int
    {
        $legacyFile = base_path('../inc/books_data.php');

        if (! File::exists($legacyFile)) {
            $this->error("Legacy book data file not found: {$legacyFile}");

            return self::FAILURE;
        }

        $books = (static function (string $file): array {
            require $file;

            return $books ?? [];
        })($legacyFile);

        if (! is_array($books)) {
            $this->error('Legacy book data did not return an array.');

            return self::FAILURE;
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($books as $legacyBook) {
            if (empty($legacyBook['slug']) || empty($legacyBook['title'])) {
                $skipped++;

                continue;
            }

            $category = null;

            if (! empty($legacyBook['tag'])) {
                $category = BookCategory::firstOrCreate(
                    ['slug' => Str::slug($legacyBook['tag'])],
                    ['name' => $legacyBook['tag'], 'is_active' => true]
                );
            }

            $coverPath = $this->copyCover($legacyBook['cover'] ?? null, $this->option('fresh-covers'));
            $book = Book::updateOrCreate(
                ['slug' => $legacyBook['slug']],
                [
                    'book_category_id' => $category?->id,
                    'title' => $legacyBook['title'],
                    'author' => 'Duke Fitz-Theodore Randolph',
                    'short_description' => $legacyBook['summary'] ?? null,
                    'description' => $legacyBook['summary'] ?? null,
                    'cover_image' => $coverPath,
                    'price' => 0,
                    'currency' => 'KES',
                    'featured' => true,
                    'is_digital' => false,
                    'status' => 'active',
                    'published_at' => $this->parseDate($legacyBook['date'] ?? null),
                    'external_purchase_url' => $legacyBook['purchase_url'] ?? null,
                ]
            );

            if ($book->wasRecentlyCreated) {
                $imported++;
            } else {
                $updated++;
            }

            if (! $book->inventoryItem) {
                $inventoryService->open(
                    book: $book,
                    sku: 'LEGACY-'.Str::upper(Str::slug($book->slug, '')),
                    openingQuantity: 0,
                    reorderLevel: 5,
                    trackStock: true,
                    reference: 'legacy-import',
                    notes: 'Inventory opened during legacy book import.',
                );
            }
        }

        $this->info("Imported: {$imported}; Updated: {$updated}; Skipped: {$skipped}");

        return self::SUCCESS;
    }

    protected function copyCover(?string $legacyCover, bool $replace = false): ?string
    {
        if (! $legacyCover) {
            return null;
        }

        $source = base_path('../'.ltrim($legacyCover, '/\\'));

        if (! File::exists($source)) {
            return null;
        }

        $target = 'books/legacy-'.basename($legacyCover);

        if ($replace || ! Storage::disk('public')->exists($target)) {
            Storage::disk('public')->put($target, File::get($source));
        }

        return $target;
    }

    protected function parseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
