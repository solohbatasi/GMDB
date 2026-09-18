<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'active', 'hidden'];

    protected $fillable = [
        'book_category_id',
        'title',
        'slug',
        'isbn',
        'author',
        'publisher',
        'short_description',
        'description',
        'cover_image',
        'price',
        'compare_price',
        'currency',
        'featured',
        'is_digital',
        'status',
        'published_at',
        'seo_title',
        'seo_description',
        'external_purchase_url',
    ];

    protected $appends = ['cover_url'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'featured' => 'boolean',
            'is_digital' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'book_category_id');
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return Storage::disk('public')->url($this->cover_image);
    }
}
