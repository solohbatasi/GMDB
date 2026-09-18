<?php

namespace App\Models;

use Database\Factories\PickupLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupLocation extends Model
{
    /** @use HasFactory<PickupLocationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'county',
        'instructions',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
