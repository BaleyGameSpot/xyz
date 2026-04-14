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

    protected $appends = ['pair', 'reason_summary'];

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

    /**
     * Always returns a flat, string-only reason object for the mobile API.
     * Handles both old (complex nested) and new (pre-summarised) reason JSON.
     */
    public function getReasonSummaryAttribute(): array
    {
        $r = is_array($this->reason) ? $this->reason : [];

        // New format: summary keys are already plain strings
        if (isset($r['market_structure']) && is_string($r['market_structure'])) {
            return [
                'market_structure' => $r['market_structure'],
                'order_block'      => isset($r['order_block']) && is_string($r['order_block']) ? $r['order_block'] : null,
                'fvg'              => isset($r['fvg']) && is_string($r['fvg']) ? $r['fvg'] : null,
                'mtf_trend'        => isset($r['mtf_trend']) && is_string($r['mtf_trend']) ? $r['mtf_trend'] : null,
                'summary'          => isset($r['summary']) && is_string($r['summary']) ? $r['summary'] : null,
            ];
        }

        // Old format: derive simple strings from the complex nested structure
        $internal   = $r['internal_structure'] ?? [];
        $obData     = $r['order_blocks'] ?? [];
        $fvgData    = $r['fvg'] ?? [];
        $mtfData    = $r['mtf_trend_data'] ?? $r['mtf_trend'] ?? [];
        $confidence = $r['confidence_factors'] ?? [];

        $trend   = ucfirst($internal['trend'] ?? 'neutral');
        $pattern = $internal['pattern'] ?? 'structure detected';

        $obNear  = (bool) ($obData['price_nearby'] ?? false);
        $fvgNear = (bool) ($fvgData['price_in_fvg'] ?? false);

        $mtf1h = false;
        $mtf4h = false;
        if (is_array($mtfData)) {
            $dir   = strtolower($internal['trend'] ?? '');
            $mtf1h = isset($mtfData['1h']) && $mtfData['1h'] === $dir;
            $mtf4h = isset($mtfData['4h']) && $mtfData['4h'] === $dir;
        }

        $mtfText = ($mtf1h && $mtf4h)
            ? 'Multi-timeframe trend fully aligned'
            : (($mtf1h || $mtf4h) ? 'Partial multi-timeframe alignment' : 'No multi-timeframe confirmation');

        return [
            'market_structure' => "{$trend} — {$pattern}",
            'order_block'      => $obNear  ? 'Price is near a key order block zone' : null,
            'fvg'              => $fvgNear ? 'Price is within a fair value gap'     : null,
            'mtf_trend'        => $mtfText,
            'summary'          => sprintf(
                '%s signal with %d%% confidence. %s%s%s',
                $this->signal_type ?? 'Signal',
                $this->confidence_score ?? 0,
                $obNear  ? 'Order block active. ' : '',
                $fvgNear ? 'FVG present. '        : '',
                ($mtf1h || $mtf4h) ? 'MTF aligned.' : ''
            ),
        ];
    }
}