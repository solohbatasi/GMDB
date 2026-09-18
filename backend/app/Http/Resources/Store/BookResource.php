<?php

namespace App\Http\Resources\Store;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'author' => $this->author,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'price' => $this->price === null ? null : (float) $this->price,
            'compare_price' => $this->compare_price === null ? null : (float) $this->compare_price,
            'currency' => $this->currency,
            'cover_url' => $this->cover_url,
            'featured' => (bool) $this->featured,
            'availability' => $this->availability($this->whenLoaded('inventoryItem')),
            'available' => $this->isAvailable($this->whenLoaded('inventoryItem')),
            'external_purchase_url' => $this->external_purchase_url,
            'published_at' => $this->published_at?->toDateString(),
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
        ];
    }

    protected function availability(mixed $inventory): string
    {
        if (! $inventory instanceof InventoryItem || ! $inventory->track_stock) {
            return 'untracked';
        }

        if ($inventory->available_quantity === 0) {
            return 'out_of_stock';
        }

        if ($inventory->available_quantity <= $inventory->reorder_level) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    protected function isAvailable(mixed $inventory): bool
    {
        if (! $inventory instanceof InventoryItem || ! $inventory->track_stock) {
            return true;
        }

        return $inventory->available_quantity > 0;
    }
}
