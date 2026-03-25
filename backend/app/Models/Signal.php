<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signal extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_pair_id',
        'timeframe',
        'signal_type',
        'entry_price',
        'stop_loss',
        'take_profit',
        'confidence_score',
        'reason',
        'status',
        'result_percentage',
        'close_price',
        'closed_at',
    ];

    protected $casts = [
        'reason'            => 'array',
        'entry_price'       => 'float',
        'stop_loss'         => 'float',
        'take_profit'       => 'float',
        'result_percentage' => 'float',
        'close_price'       => 'float',
        'closed_at'         => 'datetime',
        'confidence_score'  => 'integer',
    ];

    // --- Relationships ---

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'active']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeForPair($query, int $pairId)
    {
        return $query->where('trading_pair_id', $pairId);
    }

    public function scopeByTimeframe($query, string $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function scopeWins($query)
    {
        return $query->where('status', 'win');
    }

    public function scopeLosses($query)
    {
        return $query->where('status', 'loss');
    }

    // --- Helpers ---

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'active'], true);
    }

    public function getRiskRewardRatio(): float
    {
        $risk   = abs($this->entry_price - $this->stop_loss);
        $reward = abs($this->take_profit - $this->entry_price);

        if ($risk == 0) {
            return 0;
        }

        return round($reward / $risk, 2);
    }

    /**
     * Calculate current P&L percentage based on current price.
     */
    public function calculatePnL(float $currentPrice): float
    {
        if ($this->entry_price == 0) {
            return 0;
        }

        if ($this->signal_type === 'BUY') {
            return (($currentPrice - $this->entry_price) / $this->entry_price) * 100;
        }

        return (($this->entry_price - $currentPrice) / $this->entry_price) * 100;
    }

    /**
     * Check if stop loss is hit.
     */
    public function isStopLossHit(float $currentPrice): bool
    {
        if ($this->signal_type === 'BUY') {
            return $currentPrice <= $this->stop_loss;
        }

        return $currentPrice >= $this->stop_loss;
    }

    /**
     * Check if take profit is hit.
     */
    public function isTakeProfitHit(float $currentPrice): bool
    {
        if ($this->signal_type === 'BUY') {
            return $currentPrice >= $this->take_profit;
        }

        return $currentPrice <= $this->take_profit;
    }
}
