<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\TradingPair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignalController extends Controller
{
    /**
     * List all signals with filters.
     * GET /api/admin/signals  (JSON)
     * GET /admin/signals      (Blade view)
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View
    {
        $query = Signal::with('tradingPair');

        // Web blade uses 'pair'; API uses 'symbol'
        $symbolFilter = $request->get('pair') ?? $request->get('symbol');
        if ($symbolFilter) {
            $pair = TradingPair::where('symbol', strtoupper($symbolFilter))->first();
            if ($pair) {
                $query->where('trading_pair_id', $pair->id);
            }
        }

        if ($request->filled('timeframe')) {
            $query->where('timeframe', $request->timeframe);
        }

        // Web blade uses 'type'; API uses 'signal_type'
        $typeFilter = $request->get('type') ?? $request->get('signal_type');
        if ($typeFilter) {
            $query->where('signal_type', strtoupper($typeFilter));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Web blade uses 'date' for single date; API uses date_from/date_to
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('min_confidence')) {
            $query->where('confidence_score', '>=', (int) $request->min_confidence);
        }

        if ($request->boolean('today')) {
            $query->whereDate('created_at', today());
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $signals = $query->latest()->paginate($perPage)->withQueryString();

        // Summary stats (same for both web and API)
        $signalStats = [
            'total'   => Signal::count(),
            'pending' => Signal::where('status', 'pending')->count(),
            'wins'    => Signal::where('status', 'win')->count(),
            'losses'  => Signal::where('status', 'loss')->count(),
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $signals->items(),
                'meta'    => [
                    'current_page' => $signals->currentPage(),
                    'last_page'    => $signals->lastPage(),
                    'per_page'     => $signals->perPage(),
                    'total'        => $signals->total(),
                    'stats'        => $signalStats,
                ],
            ]);
        }

        // Available pairs for filter dropdown
        $pairs = TradingPair::where('is_active', true)
            ->orderBy('symbol')
            ->pluck('symbol')
            ->all();

        return view('admin.signals.index', compact('signals', 'signalStats', 'pairs'));
    }

    /**
     * Get a single signal.
     * GET /api/admin/signals/{id}  (JSON)
     * GET /admin/signals/{signal}  (Blade view)
     */
    public function show(Request $request, Signal $signal): JsonResponse|\Illuminate\View\View
    {
        $signal->load('tradingPair');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true, 'data' => $signal]);
        }

        return view('admin.signals.show', compact('signal'));
    }

    /**
     * Manually mark a signal as win or loss.
     * PATCH /api/admin/signals/{id}/mark  (JSON)
     * PATCH /admin/signals/{signal}/mark  (web — redirect back)
     */
    public function mark(Request $request, Signal $signal): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'status'            => ['required', 'in:win,loss,expired'],
            'close_price'       => ['sometimes', 'numeric', 'min:0'],
            'result_percentage' => ['sometimes', 'numeric'],
        ]);

        if (! in_array($signal->status, ['active', 'pending'])) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active or pending signals can be marked.',
                ], 422);
            }
            return back()->with('error', 'Only active or pending signals can be marked.');
        }

        $updates = [
            'status'    => $request->status,
            'closed_at' => now(),
        ];

        if ($request->has('close_price')) {
            $closePrice = (float) $request->close_price;
            $entry      = (float) $signal->entry_price;
            $updates['close_price'] = $closePrice;

            if (! $request->has('result_percentage')) {
                if ($signal->signal_type === 'BUY') {
                    $updates['result_percentage'] = round((($closePrice - $entry) / $entry) * 100, 4);
                } else {
                    $updates['result_percentage'] = round((($entry - $closePrice) / $entry) * 100, 4);
                }
            }
        }

        if ($request->has('result_percentage')) {
            $updates['result_percentage'] = $request->result_percentage;
        }

        $signal->update($updates);

        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return back()->with('success', "Signal #{$signal->id} marked as {$request->status}.");
        }

        return response()->json([
            'success' => true,
            'message' => "Signal #{$signal->id} marked as {$request->status}.",
            'data'    => $signal->fresh('tradingPair'),
        ]);
    }

    /**
     * Get signal performance statistics grouped by pair and timeframe.
     * GET /api/admin/signals/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 30), 365);

        $byPair = Signal::with('tradingPair')
            ->select(
                'trading_pair_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "win" THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(CASE WHEN status = "loss" THEN 1 ELSE 0 END) as losses'),
                DB::raw('AVG(confidence_score) as avg_confidence'),
                DB::raw('AVG(CASE WHEN result_percentage IS NOT NULL THEN result_percentage ELSE NULL END) as avg_return')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('trading_pair_id')
            ->get()
            ->map(function ($row) {
                $closed = $row->wins + $row->losses;
                return [
                    'symbol'         => $row->tradingPair->symbol,
                    'type'           => $row->tradingPair->type,
                    'total'          => $row->total,
                    'wins'           => $row->wins,
                    'losses'         => $row->losses,
                    'active'         => $row->total - $closed,
                    'win_rate'       => $closed > 0 ? round(($row->wins / $closed) * 100, 1) : 0,
                    'avg_confidence' => round($row->avg_confidence, 1),
                    'avg_return'     => $row->avg_return ? round($row->avg_return, 2) : null,
                ];
            })
            ->sortByDesc('win_rate')
            ->values();

        $byTimeframe = Signal::select(
                'timeframe',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "win" THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(CASE WHEN status = "loss" THEN 1 ELSE 0 END) as losses')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('timeframe')
            ->get()
            ->map(function ($row) {
                $closed = $row->wins + $row->losses;
                return [
                    'timeframe' => $row->timeframe,
                    'total'     => $row->total,
                    'wins'      => $row->wins,
                    'losses'    => $row->losses,
                    'win_rate'  => $closed > 0 ? round(($row->wins / $closed) * 100, 1) : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => [
                'by_pair'      => $byPair,
                'by_timeframe' => $byTimeframe,
                'period_days'  => $days,
            ],
        ]);
    }

    /**
     * Delete a signal (admin only).
     * DELETE /api/admin/signals/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $signal = Signal::findOrFail($id);
        $signal->delete();

        return response()->json([
            'success' => true,
            'message' => "Signal #{$id} deleted.",
        ]);
    }

    // ── Web (Blade) specific methods ──────────────────────────────

    /**
     * Bulk signal operations (web route).
     * POST /admin/signals/bulk
     */
    public function bulk(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'action'     => ['required', 'in:delete,expire'],
            'signal_ids' => ['required', 'array', 'min:1'],
            'signal_ids.*' => ['integer'],
        ]);

        $signals = Signal::whereIn('id', $request->signal_ids);
        $count   = $signals->count();

        match($request->action) {
            'delete' => $signals->delete(),
            'expire' => $signals->where('status', 'active')->update([
                'status'    => 'expired',
                'closed_at' => now(),
            ]),
        };

        return back()->with('success', "Bulk action '{$request->action}' applied to {$count} signal(s).");
    }
}