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
        'expires_at',
        'notification_sent',
    ];

    protected $casts = [
        'entry_price'      => 'decimal:8',
        'stop_loss'        => 'decimal:8',
        'take_profit'      => 'decimal:8',
        'result_percentage' => 'decimal:4',
        'close_price'      => 'decimal:8',
        'reason'           => 'array',
        'closed_at'        => 'datetime',
        'expires_at'       => 'datetime',
        'notification_sent' => 'boolean',
        'confidence_score' => 'integer',
    ];

    // Relationships
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForPair($query, int $pairId)
    {
        return $query->where('trading_pair_id', $pairId);
    }

    public function scopeHighConfidence($query, int $minScore = 60)
    {
        return $query->where('confidence_score', '>=', $minScore);
    }

    // Helpers
    public function isWin(): bool
    {
        return $this->status === 'win';
    }

    public function isLoss(): bool
    {
        return $this->status === 'loss';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBuy(): bool
    {
        return $this->signal_type === 'BUY';
    }

    public function isSell(): bool
    {
        return $this->signal_type === 'SELL';
    }

    public function getRiskRewardRatio(): float
    {
        $risk   = abs((float) $this->entry_price - (float) $this->stop_loss);
        $reward = abs((float) $this->take_profit - (float) $this->entry_price);
        if ($risk == 0) {
            return 0;
        }
        return round($reward / $risk, 2);
    }

    public function getPipsRisk(): float
    {
        return abs((float) $this->entry_price - (float) $this->stop_loss);
    }

    // Blade-friendly accessors
    public function getPairAttribute(): string
    {
        return $this->tradingPair->symbol ?? '';
    }

    public function getTypeAttribute(): string
    {
        return $this->signal_type ?? '';
    }

    public function getTakeProfitsAttribute(): array
    {
        return $this->take_profit !== null ? [(float) $this->take_profit] : [];
    }
}
