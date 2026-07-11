<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayHeroCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayHeroCallbackController extends Controller
{
    public function __invoke(Request $request, PayHeroCallbackService $callbacks): JsonResponse
    {
        $payload = $request->all();

        if ($payload === []) {
            return response()->json(['message' => 'Invalid callback payload.'], 422);
        }

        return match ($callbacks->handle($payload)) {
            'processed' => response()->json(['message' => 'Callback accepted.']),
            'deferred' => response()->json(['message' => 'Callback queued for verification.'], 202),
            default => response()->json(['message' => 'Payment reference was not found.'], 404),
        };
    }
}
