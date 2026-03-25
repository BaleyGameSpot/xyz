<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TradingPair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PairController extends Controller
{
    /**
     * Get trading pairs available for the user's subscription.
     * GET /api/pairs
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasActiveSubscription()) {
            // Return a preview of available pairs
            $pairs = TradingPair::active()
                ->select(['id', 'symbol', 'name', 'type', 'exchange'])
                ->orderBy('type')
                ->orderBy('symbol')
                ->get()
                ->map(fn($p) => array_merge($p->toArray(), ['accessible' => false]));

            return response()->json([
                'success' => true,
                'data'    => $pairs,
                'meta'    => [
                    'subscription_required' => true,
                    'message' => 'Subscribe to access trading pairs.',
                ],
            ]);
        }

        $query = TradingPair::active()
            ->forPackage($user->subscription_type);

        // Optional filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('symbol', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $pairs = $query->orderBy('type')->orderBy('symbol')->get();

        return response()->json([
            'success' => true,
            'data'    => $pairs,
            'meta'    => [
                'total'           => $pairs->count(),
                'subscription'    => $user->subscription_type,
                'pairs_limit'     => $user->getAllowedPairsLimit(),
                'allowed_timeframes' => $user->getAllowedTimeframes(),
            ],
        ]);
    }

    /**
     * Get a single trading pair details.
     * GET /api/pairs/{symbol}
     */
    public function show(Request $request, string $symbol): JsonResponse
    {
        $pair = TradingPair::where('symbol', strtoupper($symbol))
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();
        $accessible = $user->hasActiveSubscription() &&
            $pair->isAccessibleByPackage($user->subscription_type);

        return response()->json([
            'success' => true,
            'data'    => array_merge($pair->toArray(), ['accessible' => $accessible]),
        ]);
    }
}
