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
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'promo_price'         => 'decimal:2',
        'timeframes'          => 'array',
        'features'            => 'array',
        'is_active'           => 'boolean',
        'pairs_limit'         => 'integer',
        'daily_signals_limit' => 'integer',
        'sort_order'          => 'integer',
    ];

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // Helpers
    public function getEffectivePrice(): float
    {
        return (float) ($this->promo_price ?? $this->price);
    }

    public function hasUnlimitedPairs(): bool
    {
        return $this->pairs_limit === null;
    }
}
