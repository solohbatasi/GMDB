<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartQuoteRequest;
use App\Services\CartQuoteService;
use InvalidArgumentException;

class CartController extends Controller
{
    public function quote(CartQuoteRequest $request, CartQuoteService $quotes)
    {
        try {
            return response()->json(['data' => $quotes->quote($request->validated('items'))]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
