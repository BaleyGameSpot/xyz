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
        'pip_size',
    ];

    protected $casts = [
        'package_access' => 'array',
        'is_active'      => 'boolean',
        'pip_size'       => 'decimal:6',
    ];

    // Relationships
    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPackage($query, string $packageSlug)
    {
        return $query->whereJsonContains('package_access', $packageSlug);
    }

    public function scopeCrypto($query)
    {
        return $query->where('type', 'crypto');
    }

    public function scopeForex($query)
    {
        return $query->where('type', 'forex');
    }

    // Helpers
    public function isAccessibleByPackage(string $packageSlug): bool
    {
        return in_array($packageSlug, $this->package_access ?? []);
    }

    public function isCrypto(): bool
    {
        return $this->type === 'crypto';
    }

    public function isForex(): bool
    {
        return $this->type === 'forex';
    }

    /**
     * Get the base and quote currencies.
     * e.g. EURUSD => ['EUR', 'USD'], BTCUSDT => ['BTC', 'USDT']
     */
    public function getCurrencyPair(): array
    {
        $symbol = $this->symbol;
        if ($this->isCrypto()) {
            if (str_ends_with($symbol, 'USDT')) {
                return [substr($symbol, 0, -4), 'USDT'];
            }
            if (str_ends_with($symbol, 'BTC')) {
                return [substr($symbol, 0, -3), 'BTC'];
            }
        }
        // Forex: 6-char symbols like EURUSD
        return [substr($symbol, 0, 3), substr($symbol, 3, 3)];
    }
}
