<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\TradingPair;
use App\Models\User;
use App\Services\SignalGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SignalController extends Controller
{
    public function __construct(
        private readonly SignalGenerationService $signalService
    ) {}

    /**
     * Get signals filtered by the user's subscription.
     * GET /api/signals
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'Active subscription required to view signals.',
                'code'    => 'subscription_required',
            ], 403);
        }

        $allowedTimeframes = $user->getAllowedTimeframes();
        $allowedPairIds    = $this->getAllowedPairIds($user);

        $query = Signal::with('tradingPair')
            ->whereIn('trading_pair_id', $allowedPairIds)
            ->whereIn('timeframe', $allowedTimeframes);

        // Filters
        if ($request->has('symbol')) {
            $pair = TradingPair::where('symbol', strtoupper($request->symbol))->first();
            if ($pair) {
                $query->where('trading_pair_id', $pair->id);
            }
        }

        if ($request->has('timeframe')) {
            $tf = $request->timeframe;
            if (in_array($tf, $allowedTimeframes)) {
                $query->where('timeframe', $tf);
            }
        }

        if ($request->has('signal_type')) {
            $query->where('signal_type', strtoupper($request->signal_type));
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('today')) {
            $query->whereDate('created_at', today());
        }

        if ($request->has('min_confidence')) {
            $query->where('confidence_score', '>=', (int) $request->min_confidence);
        }

        $perPage = min((int) $request->get('per_page', 20), 50);
        $signals = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $signals->items(),
            'meta'    => [
                'current_page' => $signals->currentPage(),
                'last_page'    => $signals->lastPage(),
                'per_page'     => $signals->perPage(),
                'total'        => $signals->total(),
            ],
        ]);
    }

    /**
     * Get a single signal by ID.
     * GET /api/signals/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user   = $request->user();
        $signal = Signal::with('tradingPair')->findOrFail($id);

        // Verify user has access to this signal
        $allowedPairIds    = $this->getAllowedPairIds($user);
        $allowedTimeframes = $user->getAllowedTimeframes();

        if (
            ! in_array($signal->trading_pair_id, $allowedPairIds) ||
            ! in_array($signal->timeframe, $allowedTimeframes)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This signal is not available in your subscription plan.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $signal,
        ]);
    }

    /**
     * Manually trigger signal analysis for a pair and timeframe.
     * POST /api/signals/analyze
     */
    public function analyze(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'Active subscription required.',
                'code'    => 'subscription_required',
            ], 403);
        }

        $allowedTimeframes = $user->getAllowedTimeframes();

        $request->validate([
            'symbol'    => ['required', 'string', Rule::exists('trading_pairs', 'symbol')->where('is_active', true)],
            'timeframe' => ['required', 'string', Rule::in($allowedTimeframes)],
        ]);

        $pair = TradingPair::where('symbol', strtoupper($request->symbol))->firstOrFail();

        // Verify user's subscription allows access to this pair
        if (! $pair->isAccessibleByPackage($user->subscription_type)) {
            return response()->json([
                'success' => false,
                'message' => 'This trading pair is not available in your subscription plan.',
            ], 403);
        }

        try {
            // Find the nearest active zone (Volumetric OB or FVG) and create a limit order.
            // BOS/CHoCH market-execution signals are intentionally excluded here.
            $result = $this->signalService->generateOBFVGSignal($pair, $request->timeframe);

            if ($result) {
                $result->load('tradingPair');
                $zoneType = $result->reason['zone_type'] ?? 'Limit Order';

                return response()->json([
                    'success' => true,
                    'message' => "{$result->signal_type} Limit generated. Zone Type: {$zoneType}",
                    'data'    => $result,
                ], 201);
            }

            // No new signal generated — return the most recent existing pending/active signal
            // that has not yet expired (guards against returning stale 8-day-old signals).
            $existing = Signal::with('tradingPair')
                ->where('trading_pair_id', $pair->id)
                ->where('timeframe', $request->timeframe)
                ->whereIn('status', ['pending', 'active'])
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'No new signal generated. Returning existing active signal for this pair.',
                    'data'    => $existing,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Analysis complete. No signal conditions met at this time.',
                'data'    => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed. Please try again later.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get today's signal statistics for the user.
     * GET /api/signals/today-stats
     */
    public function todayStats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasActiveSubscription()) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'total'      => 0,
                    'wins'       => 0,
                    'losses'     => 0,
                    'active'     => 0,
                    'win_rate'   => 0,
                    'daily_limit' => 0,
                ],
            ]);
        }

        $allowedPairIds    = $this->getAllowedPairIds($user);
        $allowedTimeframes = $user->getAllowedTimeframes();

        $todaySignals = Signal::whereIn('trading_pair_id', $allowedPairIds)
            ->whereIn('timeframe', $allowedTimeframes)
            ->whereDate('created_at', today())
            ->get();

        $total  = $todaySignals->count();
        $wins   = $todaySignals->where('status', 'win')->count();
        $losses = $todaySignals->where('status', 'loss')->count();
        $active = $todaySignals->whereIn('status', ['active', 'pending'])->count();
        $closed = $wins + $losses;

        return response()->json([
            'success' => true,
            'data'    => [
                'total'        => $total,
                'wins'         => $wins,
                'losses'       => $losses,
                'active'       => $active,
                'win_rate'     => $closed > 0 ? round(($wins / $closed) * 100, 1) : 0,
                'daily_limit'  => $user->getDailySignalsLimit(),
                'remaining'    => max(0, $user->getDailySignalsLimit() - $total),
            ],
        ]);
    }

    /**
     * Get IDs of trading pairs allowed for the user's subscription.
     */
    private function getAllowedPairIds(User $user): array
    {
        if (! $user->hasActiveSubscription()) {
            return [];
        }

        return TradingPair::active()
            ->forPackage($user->subscription_type)
            ->pluck('id')
            ->toArray();
    }
}