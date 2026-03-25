<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'type',
        'exchange',
        'package_access',
        'is_active',
    ];

    protected $casts = [
        'package_access' => 'array',
        'is_active'      => 'boolean',
    ];

    // --- Relationships ---

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForex($query)
    {
        return $query->where('type', 'forex');
    }

    public function scopeCrypto($query)
    {
        return $query->where('type', 'crypto');
    }

    public function scopeForPackage($query, string $packageSlug)
    {
        return $query->whereJsonContains('package_access', $packageSlug);
    }

    // --- Helpers ---

    public function isCrypto(): bool
    {
        return $this->type === 'crypto';
    }

    public function isForex(): bool
    {
        return $this->type === 'forex';
    }

    /**
     * Returns symbol formatted for Binance API (e.g. BTCUSDT)
     */
    public function getBinanceSymbol(): string
    {
        return strtoupper($this->symbol);
    }

    /**
     * Returns from/to symbols for forex APIs (e.g. EUR/USD)
     */
    public function getForexParts(): array
    {
        $symbol = strtoupper($this->symbol);
        return [
            'from' => substr($symbol, 0, 3),
            'to'   => substr($symbol, 3, 3),
        ];
    }
}
