<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const STATUSES = [
        'created',
        'initiated',
        'pending',
        'paid',
        'failed',
        'cancelled',
        'expired',
        'review_required',
    ];

    protected $fillable = [
        'order_id',
        'provider',
        'method',
        'source',
        'amount',
        'currency',
        'payer_phone',
        'status',
        'channel_id',
        'channel',
        'external_reference',
        'payhero_reference',
        'provider_reference',
        'provider_transaction_id',
        'transaction_id',
        'authorization_url',
        'access_code',
        'gateway_response',
        'failure_message',
        'result_code',
        'result_description',
        'initiated_at',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'initiated_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
