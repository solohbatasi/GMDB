<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayHeroCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PayHeroCallbackController extends Controller
{
    public function __invoke(Request $request, PayHeroCallbackService $callbacks): JsonResponse
    {
        try {
            $callbacks->handle($request->all());
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json(['status' => 'received']);
    }
}
