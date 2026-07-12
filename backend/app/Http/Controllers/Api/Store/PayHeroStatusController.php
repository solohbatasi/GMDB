<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayHeroStatusController extends Controller
{
    public function __invoke(Request $request, string $externalReference): JsonResponse
    {
        $payment = Payment::query()
            ->where('provider', 'payhero')
            ->where('external_reference', $externalReference)
            ->whereHas('order', fn ($query) => $query->where('public_token', (string) $request->query('token')))
            ->with('order')
            ->firstOrFail();

        $order = Order::query()->findOrFail($payment->order_id);
        $storefrontUrl = rtrim((string) config('payhero.storefront_url'), '/');
        $state = match ($payment->status) {
            'paid' => 'successful',
            'failed', 'cancelled', 'expired', 'review_required' => 'failed',
            default => 'pending',
        };

        return response()->json([
            'data' => [
                'status' => $state,
                'payment_status' => $payment->status,
                'order_number' => $order->order_number,
                'external_reference' => $payment->external_reference,
                'message' => $this->message($payment->status, $payment->failure_message),
                'can_retry' => $order->payment_status !== 'paid'
                    && $order->order_status === 'pending'
                    && $order->reservation_expires_at !== null
                    && now()->lessThan($order->reservation_expires_at),
                'redirect_url' => $storefrontUrl.'/?p=order-confirmation&order='.rawurlencode($order->order_number)
                    .'&token='.rawurlencode($order->public_token),
            ],
        ]);
    }

    private function message(string $status, ?string $failure): string
    {
        return match ($status) {
            'paid' => 'Payment received. Your order is confirmed.',
            'failed', 'cancelled' => $failure ?: 'The M-Pesa payment was not completed. You can retry this order.',
            'review_required' => 'Payment needs verification. Please contact support with your order number.',
            default => 'Check your phone and enter your M-Pesa PIN to complete payment.',
        };
    }
}
