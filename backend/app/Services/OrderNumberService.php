<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderNumberService
{
    public function generate(): string
    {
        do {
            $number = 'GMD-'.now()->format('y').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
