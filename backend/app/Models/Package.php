<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'promo_price',
        'pairs_limit',
        'daily_signals_limit',
        'timeframes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'timeframes'   => 'array',
        'price'        => 'float',
        'promo_price'  => 'float',
        'is_active'    => 'boolean',
    ];

    // --- Relationships ---

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // --- Helpers ---

    public function getEffectivePrice(): float
    {
        return $this->promo_price ?? $this->price;
    }

    public function isUnlimitedPairs(): bool
    {
        return $this->pairs_limit === null;
    }
}
