<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\PickupLocationResource;
use App\Models\PickupLocation;

class PickupLocationController extends Controller
{
    public function index()
    {
        return PickupLocationResource::collection(
            PickupLocation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );
    }
}
