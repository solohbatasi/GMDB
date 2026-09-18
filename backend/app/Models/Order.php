<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const PAYMENT_STATUSES = ['unpaid', 'pending', 'paid', 'failed', 'refunded', 'partially_refunded'];

    public const ORDER_STATUSES = ['pending', 'confirmed', 'cancelled', 'completed'];

    public const FULFILLMENT_STATUSES = ['pending', 'processing', 'ready_for_pickup', 'shipped', 'delivered', 'cancelled'];

    protected $fillable = [
        'order_number',
        'public_token',
        'checkout_token',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_method',
        'shipping_address',
        'shipping_city',
        'shipping_county',
        'pickup_location_id',
        'subtotal',
        'shipping_total',
        'discount_total',
        'tax_total',
        'total',
        'currency',
        'payment_status',
        'order_status',
        'fulfillment_status',
        'customer_notes',
        'admin_notes',
        'reservation_expires_at',
        'paid_at',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'reservation_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class);
    }
}
