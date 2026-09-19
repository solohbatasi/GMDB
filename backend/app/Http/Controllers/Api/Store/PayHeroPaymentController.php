<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PayHeroPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PayHeroPaymentController extends Controller
{
    public function store(Request $request, string $orderNumber, PayHeroPaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'method' => ['required', 'string', 'in:mpesa_stk'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $order = Order::query()
            ->with('reservations', 'payments')
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        try {
            $payment = $payments->initiate($order, $validated['token'], $validated['method'], $validated['phone'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'We could not start your payment request. Please try again.'], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Payment confirmation is taking longer than expected. Your order is still reserved.'], 503);
        }

        return response()->json([
            'message' => 'Payment request sent. Please complete the payment on your phone.',
            'data' => [
                'status' => $payment->status,
                'payment_method' => $payment->method,
                'external_reference' => $payment->external_reference,
            ],
        ], 202);
    }
}
