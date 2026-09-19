<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaystackPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaystackPaymentController extends Controller
{
    public function store(Request $request, string $orderNumber, PaystackPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->with('reservations')
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        try {
            $payment = $payments->initialize($order, $validated['token']);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getMessage() === 'Order not found.' ? 404 : 422);
        }

        return response()->json([
            'message' => 'Redirecting to Paystack checkout.',
            'data' => [
                'status' => $payment->status,
                'reference' => $payment->external_reference,
                'authorization_url' => $payment->authorization_url,
            ],
        ], 201);
    }
}
