<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Services\PaystackVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PaystackReturnController extends Controller
{
    public function store(Request $request, PaystackVerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
        ]);

        try {
            $payment = $verification->verifyByReference($validated['reference']);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'We could not verify this payment yet. Please refresh in a moment.'], 422);
        }

        if (! $payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        return response()->json([
            'message' => $payment->status === 'paid' ? 'Payment verified.' : 'Payment is not complete yet.',
            'data' => [
                'status' => $payment->status,
                'reference' => $payment->external_reference,
                'provider_reference' => $payment->provider_reference,
                'channel' => $payment->channel,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'order_number' => $payment->order?->order_number,
                'can_retry' => $payment->order?->payment_status !== 'paid',
            ],
        ]);
    }
}
