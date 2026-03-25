<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Signal;
use App\Models\TradingPair;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     * GET /api/admin/dashboard
     */
    public function index(): JsonResponse
    {
        $now = now();

        // User stats
        $totalUsers         = User::count();
        $activeUsers        = User::active()->count();
        $blockedUsers       = User::where('status', 'blocked')->count();
        $newUsersToday      = User::whereDate('created_at', today())->count();
        $newUsersThisMonth  = User::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        // Subscription stats
        $activeSubscriptions = User::where('subscription_type', '!=', 'none')
            ->where('subscription_expiry', '>=', $now)
            ->count();

        $subscriptionsByType = User::where('subscription_type', '!=', 'none')
            ->where('subscription_expiry', '>=', $now)
            ->selectRaw('subscription_type, COUNT(*) as count')
            ->groupBy('subscription_type')
            ->pluck('count', 'subscription_type')
            ->toArray();

        // Signal stats
        $totalSignalsToday = Signal::whereDate('created_at', today())->count();
        $activeSignals     = Signal::where('status', 'active')->count();
        $winsToday         = Signal::where('status', 'win')->whereDate('closed_at', today())->count();
        $lossesToday       = Signal::where('status', 'loss')->whereDate('closed_at', today())->count();
        $closedToday       = $winsToday + $lossesToday;
        $winRateToday      = $closedToday > 0 ? round(($winsToday / $closedToday) * 100, 1) : 0;

        // Overall win rate (last 30 days)
        $totalWins   = Signal::where('status', 'win')->where('closed_at', '>=', $now->copy()->subDays(30))->count();
        $totalLosses = Signal::where('status', 'loss')->where('closed_at', '>=', $now->copy()->subDays(30))->count();
        $totalClosed = $totalWins + $totalLosses;
        $overallWinRate = $totalClosed > 0 ? round(($totalWins / $totalClosed) * 100, 1) : 0;

        // Revenue stats
        $totalRevenue      = Payment::where('status', 'confirmed')->sum('amount');
        $revenueThisMonth  = Payment::where('status', 'confirmed')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount');
        $pendingPayments   = Payment::where('status', 'pending')->whereNotNull('tx_hash')->count();

        // Recent activity
        $recentSignals = Signal::with('tradingPair')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'symbol'        => $s->tradingPair->symbol,
                'timeframe'     => $s->timeframe,
                'signal_type'   => $s->signal_type,
                'confidence'    => $s->confidence_score,
                'status'        => $s->status,
                'created_at'    => $s->created_at->toISOString(),
            ]);

        $recentUsers = User::latest()->limit(5)->get(['id', 'name', 'email', 'subscription_type', 'created_at']);

        $recentPayments = Payment::with(['user', 'package'])
            ->whereNotNull('tx_hash')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'user'       => $p->user->name,
                'package'    => $p->package->name,
                'amount'     => $p->amount,
                'currency'   => $p->currency,
                'created_at' => $p->created_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'users' => [
                    'total'            => $totalUsers,
                    'active'           => $activeUsers,
                    'blocked'          => $blockedUsers,
                    'new_today'        => $newUsersToday,
                    'new_this_month'   => $newUsersThisMonth,
                    'active_subscriptions' => $activeSubscriptions,
                    'by_subscription'  => $subscriptionsByType,
                ],
                'signals' => [
                    'total_today'      => $totalSignalsToday,
                    'active'           => $activeSignals,
                    'wins_today'       => $winsToday,
                    'losses_today'     => $lossesToday,
                    'win_rate_today'   => $winRateToday,
                    'win_rate_30d'     => $overallWinRate,
                ],
                'revenue' => [
                    'total'            => round($totalRevenue, 2),
                    'this_month'       => round($revenueThisMonth, 2),
                    'pending_payments' => $pendingPayments,
                ],
                'recent' => [
                    'signals'  => $recentSignals,
                    'users'    => $recentUsers,
                    'payments' => $recentPayments,
                ],
            ],
        ]);
    }

    /**
     * Get win rate breakdown by pair and timeframe.
     * GET /api/admin/dashboard/win-rate
     */
    public function winRateBreakdown(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 30), 90);

        $breakdown = Signal::with('tradingPair')
            ->select(
                'trading_pair_id',
                'timeframe',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "win" THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(CASE WHEN status = "loss" THEN 1 ELSE 0 END) as losses'),
                DB::raw('AVG(confidence_score) as avg_confidence'),
                DB::raw('AVG(CASE WHEN status IN ("win","loss") THEN result_percentage ELSE NULL END) as avg_result')
            )
            ->whereIn('status', ['win', 'loss'])
            ->where('closed_at', '>=', now()->subDays($days))
            ->groupBy('trading_pair_id', 'timeframe')
            ->get()
            ->map(function ($row) {
                $closed  = $row->wins + $row->losses;
                return [
                    'symbol'         => $row->tradingPair->symbol,
                    'timeframe'      => $row->timeframe,
                    'total'          => $row->total,
                    'wins'           => $row->wins,
                    'losses'         => $row->losses,
                    'win_rate'       => $closed > 0 ? round(($row->wins / $closed) * 100, 1) : 0,
                    'avg_confidence' => round($row->avg_confidence, 1),
                    'avg_result_pct' => round($row->avg_result, 2),
                ];
            })
            ->sortByDesc('win_rate')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $breakdown,
            'meta'    => ['days' => $days],
        ]);
    }
}
