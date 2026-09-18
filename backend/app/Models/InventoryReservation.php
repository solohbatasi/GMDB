<?php

namespace App\Models;

use Database\Factories\InventoryReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    /** @use HasFactory<InventoryReservationFactory> */
    use HasFactory;

    public const STATUSES = ['active', 'released', 'committed'];

    protected $fillable = [
        'order_id',
        'book_id',
        'quantity',
        'status',
        'expires_at',
        'released_at',
        'committed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'committed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
