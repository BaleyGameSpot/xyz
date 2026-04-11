<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\SendSignalNotification;
use App\Models\Signal;
use App\Models\TradingPair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TradingViewWebhookController extends Controller
{
    /**
     * Handle an incoming TradingView webhook alert.
     *
     * Expected JSON payload (sent via Pine Script alert() function):
     * {
     *   "secret":    "your_webhook_secret",
     *   "event":     "OB_FORMED" | "OB_TOUCH",
     *   "type":      "BUY" | "SELL",
     *   "symbol":    "BTCUSDT",
     *   "timeframe": "60",        // TradingView format
     *   "ob_top":    66800.0,
     *   "ob_btm":    66544.0,
     *   "entry":     66800.0,     // ob_top for BUY, ob_btm for SELL
     *   "sl":        66544.0      // ob_btm for BUY, ob_top for SELL
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        // ── Authenticate ──────────────────────────────────────────────────────
        $expectedSecret = config('trading.webhook.secret');
        if ($request->input('secret') !== $expectedSecret) {
            Log::warning('TradingView webhook: invalid secret', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ── Validate fields ───────────────────────────────────────────────────
        $validated = $request->validate([
            'event'     => 'required|in:OB_FORMED,OB_TOUCH',
            'type'      => 'required|in:BUY,SELL',
            'symbol'    => 'required|string|max:20',
            'timeframe' => 'required|string|max:10',
            'ob_top'    => 'required|numeric|gt:0',
            'ob_btm'    => 'required|numeric|gt:0',
            'entry'     => 'required|numeric|gt:0',
            'sl'        => 'required|numeric|gt:0',
        ]);

        $event     = $validated['event'];
        $type      = $validated['type'];
        $symbol    = strtoupper(trim($validated['symbol']));
        $timeframe = $this->normalizeTimeframe($validated['timeframe']);
        $obTop     = (float) $validated['ob_top'];
        $obBtm     = (float) $validated['ob_btm'];
        $entry     = (float) $validated['entry'];
        $sl        = (float) $validated['sl'];

        // ── Sanity check: OB zone must make sense ─────────────────────────────
        if ($obTop <= $obBtm) {
            return response()->json(['error' => 'ob_top must be greater than ob_btm'], 422);
        }

        // ── Resolve trading pair ──────────────────────────────────────────────
        $pair = TradingPair::where('symbol', $symbol)
            ->orWhere('symbol', $symbol . 'USDT')   // bare base asset → append quote
            ->first();

        if (! $pair) {
            Log::info("TradingView webhook: unknown symbol {$symbol}");
            return response()->json(['error' => "Unknown symbol: {$symbol}"], 422);
        }

        // ── Suppress duplicate (same pair / tf / direction within 30 minutes) ─
        $recent = Signal::where('trading_pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->where('signal_type', $type)
            ->whereIn('status', ['pending', 'active'])
            ->where('created_at', '>=', now()->subMinutes(30))
            ->whereJsonContains('reason->source', 'tradingview_webhook')
            ->first();

        if ($recent) {
            return response()->json(['skipped' => true, 'reason' => 'duplicate', 'signal_id' => $recent->id]);
        }

        // ── Calculate Take Profit (2:1 R:R) ──────────────────────────────────
        $rrRatio = config('trading.signals.risk_reward_ratio', 2.0);
        $risk    = abs($entry - $sl);
        $tp      = $type === 'BUY'
            ? $entry + ($risk * $rrRatio)
            : $entry - ($risk * $rrRatio);

        // ── Determine signal status ────────────────────────────────────────────
        // OB_FORMED → pending (limit order waiting for price to arrive)
        // OB_TOUCH  → active  (price has just entered the OB zone)
        $status = $event === 'OB_FORMED' ? 'pending' : 'active';

        // ── Build reason ──────────────────────────────────────────────────────
        $direction  = $type === 'BUY' ? 'Bullish' : 'Bearish';
        $obLabel    = $type === 'BUY' ? 'Demand'  : 'Supply';
        $eventLabel = $event === 'OB_FORMED' ? 'OB Formation' : 'OB Touch';

        $reason = [
            'type'             => 'OB_LIMIT_ORDER',
            'event'            => $event,
            'ob_zone'          => ['high' => $obTop, 'low' => $obBtm],
            'source'           => 'tradingview_webhook',
            // Mobile display strings
            'market_structure' => "{$direction} {$eventLabel}",
            'order_block'      => sprintf('%s OB: %.6f – %.6f', $obLabel, $obBtm, $obTop),
            'fvg'              => null,
            'mtf_trend'        => null,
            'summary'          => sprintf(
                '%s limit order at %.6f. OB zone: %.6f – %.6f. R:R 1:%.1f.',
                $type,
                $entry,
                $obBtm,
                $obTop,
                $rrRatio
            ),
        ];

        // ── Create signal ──────────────────────────────────────────────────────
        try {
            $signal = DB::transaction(function () use (
                $pair, $timeframe, $type, $entry, $sl, $tp, $reason, $status
            ) {
                return Signal::create([
                    'trading_pair_id'  => $pair->id,
                    'timeframe'        => $timeframe,
                    'signal_type'      => $type,
                    'entry_price'      => round($entry, 8),
                    'stop_loss'        => round($sl, 8),
                    'take_profit'      => round($tp, 8),
                    'confidence_score' => 80,   // Directly from indicator — high confidence
                    'reason'           => $reason,
                    'status'           => $status,
                    'expires_at'       => now()->addHours(config('trading.signals.expiry_hours', 24)),
                ]);
            });

            SendSignalNotification::dispatch($signal)->onQueue('notifications');

            Log::info("TradingView webhook signal created: {$type} {$pair->symbol} {$timeframe}", [
                'event'  => $event,
                'entry'  => $entry,
                'sl'     => $sl,
                'tp'     => $tp,
                'status' => $status,
            ]);

            return response()->json([
                'success'   => true,
                'signal_id' => $signal->id,
                'status'    => $status,
            ], 201);

        } catch (\Exception $e) {
            Log::error('TradingView webhook signal creation failed', [
                'error'  => $e->getMessage(),
                'symbol' => $symbol,
                'type'   => $type,
            ]);
            return response()->json(['error' => 'Signal creation failed'], 500);
        }
    }

    /**
     * Convert TradingView timeframe string to our internal format.
     *
     * TradingView sends: "1", "3", "5", "15", "30", "60", "240", "D", "W", "M"
     * We store:          "1m", "3m", "5m", "15m", "30m", "1h",  "4h",  "1D"
     */
    private function normalizeTimeframe(string $tf): string
    {
        return match(strtoupper(trim($tf))) {
            '1'    => '1m',
            '3'    => '3m',
            '5'    => '5m',
            '15'   => '15m',
            '30'   => '30m',
            '45'   => '45m',
            '60'   => '1h',
            '120'  => '2h',
            '240'  => '4h',
            '720'  => '12h',
            'D'    => '1D',
            '1D'   => '1D',
            'W'    => '1W',
            default => $tf,    // Pass through if already normalised
        };
    }
}
