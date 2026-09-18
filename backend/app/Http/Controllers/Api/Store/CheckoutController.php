<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\Store\OrderResource;
use App\Services\CheckoutService;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkout)
    {
        try {
            $order = $checkout->checkout($request->validated(), $request->validated('checkout_token'));

            return (new OrderResource($order->load('items', 'pickupLocation')))
                ->response()
                ->setStatusCode(201);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
