<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'public_token' => $this->public_token,
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'fulfillment' => [
                'method' => $this->delivery_method,
                'status' => $this->fulfillment_status,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'county' => $this->shipping_county,
                'pickup_location' => $this->whenLoaded('pickupLocation', fn () => $this->pickupLocation ? [
                    'name' => $this->pickupLocation->name,
                    'address' => $this->pickupLocation->address,
                    'city' => $this->pickupLocation->city,
                    'county' => $this->pickupLocation->county,
                    'instructions' => $this->pickupLocation->instructions,
                ] : null),
            ],
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'title' => $item->title_snapshot,
                'sku' => $item->sku_snapshot,
                'unit_price' => $item->price_snapshot,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->values()),
            'subtotal' => $this->subtotal,
            'shipping_total' => $this->shipping_total,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'total' => $this->total,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'order_status' => $this->order_status,
            'reservation_expires_at' => $this->reservation_expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
