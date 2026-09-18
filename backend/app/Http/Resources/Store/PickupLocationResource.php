<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'county' => $this->county,
            'instructions' => $this->instructions,
        ];
    }
}
