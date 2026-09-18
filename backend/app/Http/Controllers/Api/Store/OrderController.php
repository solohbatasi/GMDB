<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, string $orderNumber)
    {
        $order = Order::query()
            ->with('items', 'pickupLocation')
            ->where('order_number', $orderNumber)
            ->where('public_token', $request->query('token'))
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return new OrderResource($order);
    }
}
