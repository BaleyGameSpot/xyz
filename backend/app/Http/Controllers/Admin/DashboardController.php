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
     * GET /api/admin/dashboard  (JSON)
     * GET /admin/dashboard      (Blade view)
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View
    {
        $now = now();

        // ── User stats ────────────────────────────────────────────
        $totalUsers        = User::count();
        $activeUsers       = User::where('status', 'active')->count();
        $blockedUsers      = User::where('status', 'blocked')->count();
        $newUsersToday     = User::whereDate('created_at', today())->count();
        $newUsersThisMonth = User::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();
        $newUsersWeek      = User::where('created_at', '>=', $now->copy()->subDays(7))->count();

        // ── Subscription stats ────────────────────────────────────
        $activeSubscriptions = UserSubscription::where('payment_status', 'confirmed')
            ->where('end_date', '>=', today()->toDateString())
            ->count();

        // ── Signal stats ──────────────────────────────────────────
        $totalSignalsToday = Signal::whereDate('created_at', today())->count();
        $signalsPending    = Signal::where('status', 'pending')->count();
        $totalWins         = Signal::where('status', 'win')->count();
        $totalLosses       = Signal::where('status', 'loss')->count();
        $totalClosed       = $totalWins + $totalLosses;
        $winRate           = $totalClosed > 0 ? round(($totalWins / $totalClosed) * 100, 1) : 0;

        // ── Revenue stats ─────────────────────────────────────────
        $revenueMonth    = Payment::where('status', 'confirmed')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')
            ->whereNotNull('tx_hash')
            ->count();

        // ── Chart: signals per day (last 30 days) ─────────────────
        $signalCountsByDate = Signal::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->pluck('count', 'date');

        $signalDates  = [];
        $signalCounts = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $signalDates[]  = $d->format('d M');
            $signalCounts[] = (int) ($signalCountsByDate[$d->format('Y-m-d')] ?? 0);
        }

        // ── Chart: signals by pair (top 6) ────────────────────────
        $pairData   = Signal::join('trading_pairs', 'signals.trading_pair_id', '=', 'trading_pairs.id')
            ->select('trading_pairs.symbol', DB::raw('COUNT(*) as count'))
            ->groupBy('trading_pairs.symbol', 'trading_pairs.id')
            ->orderByDesc('count')
            ->limit(6)
            ->pluck('count', 'symbol');

        $chartData = [
            'signal_dates'  => $signalDates,
            'signal_counts' => $signalCounts,
            'pair_labels'   => $pairData->keys()->values()->all(),
            'pair_counts'   => $pairData->values()->all(),
        ];

        // ── Stats array (Blade-friendly keys) ─────────────────────
        $stats = [
            'total_users'          => $totalUsers,
            'active_subscriptions' => $activeSubscriptions,
            'signals_today'        => $totalSignalsToday,
            'signals_pending'      => $signalsPending,
            'win_rate'             => $winRate,
            'total_wins'           => $totalWins,
            'total_losses'         => $totalLosses,
            'total_closed'         => $totalClosed,
            'revenue_month'        => round($revenueMonth, 2),
            'pending_payments'     => $pendingPayments,
            'new_users_week'       => $newUsersWeek,
        ];

        // ── Recent activity ───────────────────────────────────────
        $recentSignals = Signal::with('tradingPair')
            ->latest()
            ->limit(10)
            ->get();

        $recentUsers = User::with(['activeSubscription.package'])
            ->latest()
            ->limit(5)
            ->get();

        $recentPayments = Payment::with('user')
            ->whereNotNull('tx_hash')
            ->latest()
            ->limit(5)
            ->get();

        // ── Return: Blade for web, JSON for API ───────────────────
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'users'   => [
                        'total'                => $totalUsers,
                        'active'               => $activeUsers,
                        'blocked'              => $blockedUsers,
                        'new_today'            => $newUsersToday,
                        'new_this_month'       => $newUsersThisMonth,
                        'active_subscriptions' => $activeSubscriptions,
                    ],
                    'signals' => [
                        'total_today' => $totalSignalsToday,
                        'pending'     => $signalsPending,
                        'wins'        => $totalWins,
                        'losses'      => $totalLosses,
                        'win_rate'    => $winRate,
                    ],
                    'revenue' => [
                        'this_month'       => round($revenueMonth, 2),
                        'pending_payments' => $pendingPayments,
                    ],
                ],
            ]);
        }

        return view('admin.dashboard.index', compact(
            'stats',
            'chartData',
            'recentSignals',
            'recentUsers',
            'recentPayments'
        ));
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
                $closed = $row->wins + $row->losses;
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