<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Payment;
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
                'pending_orders' => Order::where('order_status', 'pending')->count(),
                'awaiting_payment' => Order::whereIn('payment_status', ['unpaid', 'pending'])->count(),
                'confirmed_revenue' => Order::where('payment_status', 'paid')->sum('total'),
                'paid_orders' => Order::where('payment_status', 'paid')->count(),
                'pending_payments' => Payment::whereIn('status', ['created', 'initiated', 'pending'])->count(),
                'failed_payments' => Payment::whereIn('status', ['failed', 'cancelled'])->count(),
                'payments_requiring_review' => Payment::where('status', 'review_required')->count(),
                'active_reservations' => InventoryReservation::where('status', 'active')->count(),
                'pending_order_value' => Order::where('payment_status', 'unpaid')->sum('total'),
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
