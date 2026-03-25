<?php

namespace App\Repositories;

use App\Models\Signal;
use App\Models\TradingPair;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SignalRepository
{
    /**
     * Get paginated signals for a user filtered by their subscription.
     */
    public function getForUser(
        array  $pairIds,
        array  $timeframes,
        array  $filters = [],
        int    $perPage = 20
    ): LengthAwarePaginator {
        $query = Signal::with('tradingPair')
            ->whereIn('trading_pair_id', $pairIds)
            ->whereIn('timeframe', $timeframes);

        if (! empty($filters['symbol'])) {
            $pair = TradingPair::where('symbol', strtoupper($filters['symbol']))->first();
            if ($pair) {
                $query->where('trading_pair_id', $pair->id);
            }
        }

        if (! empty($filters['timeframe'])) {
            $query->where('timeframe', $filters['timeframe']);
        }

        if (! empty($filters['signal_type'])) {
            $query->where('signal_type', strtoupper($filters['signal_type']));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['today'])) {
            $query->whereDate('created_at', today());
        }

        if (! empty($filters['min_confidence'])) {
            $query->where('confidence_score', '>=', (int) $filters['min_confidence']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get today's signal statistics.
     */
    public function getTodayStats(array $pairIds, array $timeframes): array
    {
        $signals = Signal::whereIn('trading_pair_id', $pairIds)
            ->whereIn('timeframe', $timeframes)
            ->whereDate('created_at', today())
            ->get();

        $wins   = $signals->where('status', 'win')->count();
        $losses = $signals->where('status', 'loss')->count();
        $closed = $wins + $losses;

        return [
            'total'    => $signals->count(),
            'wins'     => $wins,
            'losses'   => $losses,
            'active'   => $signals->whereIn('status', ['active', 'pending'])->count(),
            'win_rate' => $closed > 0 ? round(($wins / $closed) * 100, 1) : 0,
        ];
    }

    /**
     * Get active signals that need result checking.
     */
    public function getActiveForResultCheck(int $chunkSize = 100): Collection
    {
        return Signal::where('status', 'active')
            ->with('tradingPair')
            ->limit($chunkSize)
            ->get();
    }

    /**
     * Count today's signals for a pair and timeframe.
     */
    public function countTodaySignals(int $pairId, string $timeframe): int
    {
        return Signal::where('trading_pair_id', $pairId)
            ->where('timeframe', $timeframe)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get recent signals for a pair to check for duplicates.
     */
    public function findRecentDuplicate(int $pairId, string $timeframe, string $signalType, int $hours = 2): ?Signal
    {
        return Signal::where('trading_pair_id', $pairId)
            ->where('timeframe', $timeframe)
            ->where('signal_type', $signalType)
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subHours($hours))
            ->first();
    }

    /**
     * Get win rate statistics grouped by pair and timeframe.
     */
    public function getWinRateStats(int $days = 30): Collection
    {
        return Signal::with('tradingPair')
            ->selectRaw(
                'trading_pair_id, timeframe,
                 COUNT(*) as total,
                 SUM(CASE WHEN status = "win" THEN 1 ELSE 0 END) as wins,
                 SUM(CASE WHEN status = "loss" THEN 1 ELSE 0 END) as losses,
                 AVG(confidence_score) as avg_confidence'
            )
            ->whereIn('status', ['win', 'loss'])
            ->where('closed_at', '>=', now()->subDays($days))
            ->groupBy('trading_pair_id', 'timeframe')
            ->get();
    }
}
