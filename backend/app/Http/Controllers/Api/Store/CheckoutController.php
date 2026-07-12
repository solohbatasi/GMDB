<?php

namespace App\Http\Controllers\Api\Store;

use App\Exceptions\PayHeroRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\Store\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayHeroCheckoutService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, PayHeroCheckoutService $checkout): JsonResponse
    {
        try {
            $result = $checkout->checkoutAndPay($request->validated(), $request->validated('checkout_token'));
            $order = $result['order']->load('items', 'pickupLocation', 'payments', 'reservations');
            $payment = $result['payment'];
            $statusUrl = route('api.store.payments.payhero.status', [
                'externalReference' => $payment->external_reference,
                'token' => $order->public_token,
            ]);

            return response()->json([
                'data' => (new OrderResource($order))->resolve($request),
                'payment' => [
                    'status' => $payment->status,
                    'external_reference' => $payment->external_reference,
                    'payhero_reference' => $payment->payhero_reference,
                    'checkout_request_id' => $payment->provider_transaction_id,
                    'message' => 'Check your phone and enter your M-Pesa PIN to complete payment.',
                    'status_url' => $statusUrl,
                ],
            ], 201);
        } catch (PayHeroRequestException $exception) {
            $order = Order::query()
                ->where('checkout_token', $request->validated('checkout_token'))
                ->with('items', 'pickupLocation', 'payments', 'reservations')
                ->first();
            $payment = $order ? Payment::query()
                ->where('order_id', $order->id)
                ->where('provider', 'payhero')
                ->latest('id')
                ->first() : null;

            if ($exception->outcomeUnknown && $order && $payment) {
                return response()->json([
                    'data' => (new OrderResource($order))->resolve($request),
                    'payment' => [
                        'status' => $payment->status,
                        'external_reference' => $payment->external_reference,
                        'payhero_reference' => $payment->payhero_reference,
                        'checkout_request_id' => $payment->provider_transaction_id,
                        'message' => $exception->getMessage(),
                        'status_url' => route('api.store.payments.payhero.status', [
                            'externalReference' => $payment->external_reference,
                            'token' => $order->public_token,
                        ]),
                    ],
                ], 202);
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'outcome_unknown' => $exception->outcomeUnknown,
            ], $exception->outcomeUnknown ? 202 : 502);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
