<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\BookCategory;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'author' => $request->string('author')->toString(),
            'category' => $request->string('category')->toString(),
            'status' => $request->string('status')->toString(),
            'featured' => $request->string('featured')->toString(),
        ];

        return Inertia::render('Books/Index', [
            'filters' => $filters,
            'categories' => BookCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'books' => Book::query()
                ->with('category', 'inventoryItem')
                ->when($filters['search'], fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
                ->when($filters['author'], fn ($query, $author) => $query->where('author', 'like', "%{$author}%"))
                ->when($filters['category'], fn ($query, $category) => $query->where('book_category_id', $category))
                ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
                ->when($filters['featured'] !== '', fn ($query) => $query->where('featured', request()->boolean('featured')))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'statuses' => Book::STATUSES,
        ]);
    }

    public function store(StoreBookRequest $request, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validated();
        $cover = $request->file('cover')?->store('books', 'public');

        DB::transaction(function () use ($validated, $cover, $request, $inventoryService) {
            $book = Book::create($this->bookAttributes($validated, $cover));

            if (! $request->boolean('is_digital')) {
                $inventoryService->open(
                    book: $book,
                    sku: $validated['sku'],
                    openingQuantity: (int) ($validated['opening_quantity'] ?? 0),
                    reorderLevel: (int) ($validated['reorder_level'] ?? 5),
                    trackStock: $request->boolean('track_stock', true),
                    user: $request->user(),
                    reference: 'admin-opening',
                    notes: 'Opening stock created with book record.',
                );
            }
        });

        return back()->with('success', 'Book created.');
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();
        $oldCover = $book->cover_image;
        $cover = $request->file('cover')?->store('books', 'public');

        DB::transaction(function () use ($book, $validated, $cover, $request) {
            $book->update($this->bookAttributes($validated, $cover, $book->cover_image));

            if ($book->inventoryItem && ! $request->boolean('is_digital')) {
                $book->inventoryItem->update([
                    'sku' => $validated['sku'] ?? $book->inventoryItem->sku,
                    'reorder_level' => (int) ($validated['reorder_level'] ?? $book->inventoryItem->reorder_level),
                    'track_stock' => $request->boolean('track_stock', $book->inventoryItem->track_stock),
                ]);
            }
        });

        if ($cover && $oldCover) {
            Storage::disk('public')->delete($oldCover);
        }

        return back()->with('success', 'Book updated.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->update(['status' => 'hidden']);

        return back()->with('success', 'Book hidden.');
    }

    protected function bookAttributes(array $validated, ?string $cover, ?string $existingCover = null): array
    {
        return [
            'book_category_id' => $validated['book_category_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'isbn' => $validated['isbn'] ?? null,
            'author' => $validated['author'],
            'publisher' => $validated['publisher'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'cover_image' => $cover ?: $existingCover,
            'price' => $validated['price'],
            'compare_price' => $validated['compare_price'] ?? null,
            'currency' => strtoupper($validated['currency']),
            'featured' => request()->boolean('featured'),
            'is_digital' => request()->boolean('is_digital'),
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'external_purchase_url' => $validated['external_purchase_url'] ?? null,
        ];
    }
}
