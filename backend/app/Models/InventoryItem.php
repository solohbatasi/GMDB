<?php

namespace App\Models;

use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'book_id',
        'sku',
        'quantity_on_hand',
        'quantity_reserved',
        'reorder_level',
        'track_stock',
    ];

    protected $appends = ['available_quantity', 'stock_status'];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'reorder_level' => 'integer',
            'track_stock' => 'boolean',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    public function getStockStatusAttribute(): string
    {
        if (! $this->track_stock) {
            return 'Stock Tracking Disabled';
        }

        if ($this->available_quantity === 0) {
            return 'Out of Stock';
        }

        if ($this->available_quantity <= $this->reorder_level) {
            return 'Low Stock';
        }

        return 'In Stock';
    }
}
