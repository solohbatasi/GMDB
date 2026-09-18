<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'payment_status' => $request->string('payment_status')->toString(),
            'order_status' => $request->string('order_status')->toString(),
            'delivery_method' => $request->string('delivery_method')->toString(),
        ];

        return Inertia::render('Orders/Index', [
            'filters' => $filters,
            'orders' => Order::query()
                ->withCount('items')
                ->when($filters['search'], fn ($query, $search) => $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                }))
                ->when($filters['payment_status'], fn ($query, $status) => $query->where('payment_status', $status))
                ->when($filters['order_status'], fn ($query, $status) => $query->where('order_status', $status))
                ->when($filters['delivery_method'], fn ($query, $method) => $query->where('delivery_method', $method))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('Orders/Show', [
            'order' => $order->load('items.book', 'pickupLocation', 'histories.user', 'reservations.book'),
        ]);
    }
}
