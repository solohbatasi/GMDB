<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Requests\RestockInventoryRequest;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'category' => $request->string('category')->toString(),
            'condition' => $request->string('condition')->toString(),
        ];

        $items = InventoryItem::query()
            ->with('book.category')
            ->when($filters['search'], fn ($query, $search) => $query->whereHas('book', fn ($bookQuery) => $bookQuery
                ->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
            )->orWhere('sku', 'like', "%{$search}%"))
            ->when($filters['category'], fn ($query, $category) => $query->whereHas('book', fn ($bookQuery) => $bookQuery->where('book_category_id', $category)))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        if ($filters['condition']) {
            $items->setCollection($items->getCollection()->filter(fn (InventoryItem $item) => match ($filters['condition']) {
                'low' => $item->track_stock && $item->available_quantity > 0 && $item->available_quantity <= $item->reorder_level,
                'out' => $item->track_stock && $item->available_quantity === 0,
                'disabled' => ! $item->track_stock,
                default => true,
            })->values());
        }

        return Inertia::render('Inventory/Index', [
            'filters' => $filters,
            'categories' => BookCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'items' => $items,
        ]);
    }

    public function show(Book $book): Response
    {
        $book->load('category', 'inventoryItem');

        return Inertia::render('Inventory/Show', [
            'book' => $book,
            'movements' => $book->stockMovements()
                ->with('user')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function restock(RestockInventoryRequest $request, Book $book, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validated();

        $inventoryService->restock(
            book: $book,
            quantity: (int) $validated['quantity'],
            user: $request->user(),
            reference: $validated['reference'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        return back()->with('success', 'Inventory restocked.');
    }

    public function adjust(AdjustInventoryRequest $request, Book $book, InventoryService $inventoryService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $inventoryService->adjust(
                book: $book,
                quantityChange: (int) $validated['quantity_change'],
                user: $request->user(),
                reference: $validated['reason'],
                notes: $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['quantity_change' => $exception->getMessage()]);
        }

        return back()->with('success', 'Inventory adjusted.');
    }

    public function movements(Request $request): Response
    {
        return Inertia::render('Inventory/Movements', [
            'movements' => StockMovement::query()
                ->with('book.category', 'user')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
