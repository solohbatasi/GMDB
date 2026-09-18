<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\InventoryItem;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $inventory = InventoryItem::query()->where('track_stock', true)->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_books' => Book::count(),
                'active_books' => Book::where('status', 'active')->count(),
                'total_units_in_stock' => InventoryItem::sum('quantity_on_hand'),
                'low_stock_books' => $inventory->filter(fn (InventoryItem $item) => $item->available_quantity > 0 && $item->available_quantity <= $item->reorder_level)->count(),
                'out_of_stock_books' => $inventory->filter(fn (InventoryItem $item) => $item->available_quantity === 0)->count(),
            ],
            'recentBooks' => Book::query()
                ->with('category', 'inventoryItem')
                ->latest()
                ->limit(5)
                ->get(),
            'lowStockBooks' => InventoryItem::query()
                ->with('book.category')
                ->where('track_stock', true)
                ->get()
                ->filter(fn (InventoryItem $item) => $item->available_quantity > 0 && $item->available_quantity <= $item->reorder_level)
                ->values()
                ->take(5),
        ]);
    }
}
