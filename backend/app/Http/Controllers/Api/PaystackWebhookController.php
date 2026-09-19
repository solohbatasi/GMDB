<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaystackWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackWebhookService $webhooks): JsonResponse
    {
        if (! $webhooks->handle($request->getContent(), $request->header('x-paystack-signature'))) {
            return response()->json(['message' => 'Invalid Paystack signature.'], 401);
        }

        return response()->json(['message' => 'Webhook accepted.']);
    }
}
