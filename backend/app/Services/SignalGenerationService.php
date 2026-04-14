<?php

namespace App\Services;

use App\Jobs\SendSignalNotification;
use App\Models\Signal;
use App\Models\TradingPair;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SignalGenerationService
{
    public function __construct(
        private readonly MarketDataService $marketDataService,
        private readonly IndicatorService  $indicatorService,
    ) {}

    /**
     * Generate a signal for a given trading pair and timeframe.
     *
     * @param  TradingPair $pair
     * @param  string      $timeframe e.g. '15m', '1h'
     * @return Signal|null Signal model if generated, null if no signal
     */
    public function generateSignal(TradingPair $pair, string $timeframe): ?Signal
    {
        try {
            $limit  = config('trading.indicators.candle_limit', 200);
            $ohlcv  = $this->marketDataService->getOHLCV($pair->symbol, $timeframe, $limit);

            if (count($ohlcv) < 60) {
                Log::warning("Insufficient data for {$pair->symbol} {$timeframe}", ['count' => count($ohlcv)]);
                return null;
            }

            // Run all indicators
            $analysis = $this->runFullAnalysis($ohlcv, $pair, $timeframe);

            if ($analysis['signal'] === null) {
                return null;
            }

            // Check minimum confidence
            $minConfidence = config('trading.signals.min_confidence', 40);
            if ($analysis['confidence'] < $minConfidence) {
                Log::debug("Signal below minimum confidence for {$pair->symbol} {$timeframe}", [
                    'confidence' => $analysis['confidence'],
                    'minimum'    => $minConfidence,
                ]);
                return null;
            }

            // Check for duplicate recent signal (same pair, timeframe, direction within 2 hours)
            $recentSignal = Signal::where('trading_pair_id', $pair->id)
                ->where('timeframe', $timeframe)
                ->where('signal_type', $analysis['signal'])
                ->where('status', 'active')
                ->where('created_at', '>=', now()->subHours(2))
                ->first();

            if ($recentSignal) {
                Log::debug("Duplicate signal suppressed for {$pair->symbol} {$timeframe}");
                return null;
            }

            return DB::transaction(function () use ($pair, $timeframe, $analysis, $ohlcv) {
                $signal = Signal::create([
                    'trading_pair_id'  => $pair->id,
                    'timeframe'        => $timeframe,
                    'signal_type'      => $analysis['signal'],
                    'entry_price'      => $analysis['entry'],
                    'stop_loss'        => $analysis['sl'],
                    'take_profit'      => $analysis['tp'],
                    'confidence_score' => $analysis['confidence'],
                    'reason'           => $analysis['reason'],
                    'status'           => 'active',
                    'expires_at'       => now()->addHours(config('trading.signals.expiry_hours', 24)),
                ]);

                // Dispatch FCM notification job
                SendSignalNotification::dispatch($signal)->onQueue('notifications');

                Log::info("Signal generated: {$signal->signal_type} {$pair->symbol} {$timeframe}", [
                    'confidence' => $signal->confidence_score,
                    'entry'      => $signal->entry_price,
                    'sl'         => $signal->stop_loss,
                    'tp'         => $signal->take_profit,
                ]);

                return $signal;
            });
        } catch (\Exception $e) {
            Log::error("Signal generation failed for {$pair->symbol} {$timeframe}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Run full technical analysis on OHLCV data.
     *
     * @param  array       $ohlcv
     * @param  TradingPair $pair
     * @param  string      $timeframe
     * @return array       Analysis results
     */
    public function runFullAnalysis(array $ohlcv, TradingPair $pair, string $timeframe): array
    {
        $internalLookback = config('trading.indicators.internal_lookback', 5);
        $swingLookback    = config('trading.indicators.swing_lookback', 50);
        $atrLength        = config('trading.indicators.atr_length', 14);
        $rrRatio          = config('trading.signals.risk_reward_ratio', 2.0);
        $atrMultiplier    = config('trading.signals.atr_sl_multiplier', 1.5);

        $count        = count($ohlcv);
        $currentClose = (float) $ohlcv[$count - 1]['close'];

        // 1. Market structure (internal - faster)
        $internalStructure = $this->indicatorService->detectMarketStructure($ohlcv, $internalLookback);

        // 2. Market structure (swing - slower, higher timeframe confirmation)
        $swingStructure = $this->indicatorService->detectMarketStructure($ohlcv, $swingLookback);

        // Determine primary signal direction
        $primarySignal = $internalStructure['signal'] ?? $swingStructure['signal'] ?? null;

        // 3. ATR
        $atr = $this->indicatorService->calculateATR($ohlcv, $atrLength);

        // 4. Order blocks
        $direction    = $primarySignal === 'BUY' ? 'bullish' : 'bearish';
        $orderBlocks  = $this->indicatorService->detectOrderBlocks($ohlcv, $direction);

        // 5. FVG
        $fvgs = $this->indicatorService->detectFVG($ohlcv);

        // 6. EQHL
        $eqhl = $this->indicatorService->detectEQHL($ohlcv);

        // 7. MTF EMA trend
        $mtfTrend = $this->getMTFTrend($pair->symbol, $timeframe);

        // 8. EMA trend on current timeframe
        $closes     = array_map(fn($c) => (float) $c['close'], $ohlcv);
        $emaLength  = config('trading.indicators.ema_length', 34);
        $smma       = $this->indicatorService->calculateSMMA($closes, $emaLength);
        $zlema      = $this->indicatorService->calculateZLEMA($closes, $emaLength);
        $smmaLast   = $smma[$count - 1] ?? 0;
        $zlemaLast  = $zlema[$count - 1] ?? 0;

        $emaTrend = 'neutral';
        if ($currentClose > $smmaLast && $zlemaLast > $smmaLast) {
            $emaTrend = 'bullish';
        } elseif ($currentClose < $smmaLast && $zlemaLast < $smmaLast) {
            $emaTrend = 'bearish';
        }

        // 9. Confidence calculation inputs
        $isOBNear     = $this->indicatorService->isPriceNearOrderBlock($currentClose, $orderBlocks);
        $isFVGNear    = $this->indicatorService->isPriceNearFVG($currentClose, $fvgs, strtolower($direction));
        $mtf1hAgrees  = isset($mtfTrend['1h']) && $mtfTrend['1h'] === strtolower($direction);
        $mtf4hAgrees  = isset($mtfTrend['4h']) && $mtfTrend['4h'] === strtolower($direction);
        $emaAgrees    = $emaTrend === strtolower($direction);

        $confidence = $this->indicatorService->calculateConfidence([
            'market_structure' => $primarySignal !== null,
            'order_block_near' => $isOBNear,
            'fvg_near'         => $isFVGNear,
            'mtf_1h_agrees'    => $mtf1hAgrees,
            'mtf_4h_agrees'    => $mtf4hAgrees,
            'ema_trend'        => $emaAgrees,
            'pattern_strength' => $internalStructure['strength'] ?? 0,
        ]);

        // 10. Entry / SL / TP Calculation
        [$entry, $sl, $tp] = $this->calculateEntrySlTp(
            $ohlcv,
            $primarySignal,
            $currentClose,
            $atr,
            $atrMultiplier,
            $rrRatio
        );

        // 11. Reason JSON
        $reason = [
            'internal_structure' => [
                'trend'           => $internalStructure['trend'],
                'pattern'         => $internalStructure['pattern'],
                'last_pivot_high' => $internalStructure['last_pivot_high'] ?? null,
                'last_pivot_low'  => $internalStructure['last_pivot_low'] ?? null,
            ],
            'swing_structure' => [
                'trend'   => $swingStructure['trend'],
                'pattern' => $swingStructure['pattern'],
            ],
            'order_blocks' => [
                'count'        => count($orderBlocks),
                'price_nearby' => $isOBNear,
                'zones'        => array_slice($orderBlocks, 0, 2),
            ],
            'fvg' => [
                'bullish_count' => count($fvgs['bullish']),
                'bearish_count' => count($fvgs['bearish']),
                'price_in_fvg'  => $isFVGNear,
            ],
            'eqhl' => [
                'eqh_levels' => array_column($eqhl['eqh'], 'level'),
                'eql_levels' => array_column($eqhl['eql'], 'level'),
            ],
            'ema' => [
                'trend'     => $emaTrend,
                'smma_34'   => round($smmaLast, 8),
                'zlema_34'  => round($zlemaLast, 8),
                'current'   => round($currentClose, 8),
            ],
            'mtf_trend_data' => $mtfTrend,
            'atr'            => round($atr, 8),
            'confidence_factors' => [
                'market_structure' => $primarySignal !== null,
                'order_block_near' => $isOBNear,
                'fvg_near'         => $isFVGNear,
                'mtf_1h_agrees'    => $mtf1hAgrees,
                'mtf_4h_agrees'    => $mtf4hAgrees,
                'ema_trend'        => $emaAgrees,
            ],
            // Simplified summary fields for mobile app display
            'market_structure' => ucfirst($internalStructure['trend'] ?? 'neutral') . ' — ' . ($internalStructure['pattern'] ?? 'Structure detected'),
            'order_block'      => $isOBNear ? 'Price is near a key order block zone' : null,
            'fvg'              => $isFVGNear ? 'Price is within a fair value gap' : null,
            'mtf_trend'        => ($mtf1hAgrees && $mtf4hAgrees)
                                    ? 'Multi-timeframe trend fully aligned'
                                    : ($mtf1hAgrees || $mtf4hAgrees ? 'Partial multi-timeframe alignment' : 'No multi-timeframe confirmation'),
            'summary'          => sprintf(
                '%s signal with %d%% confidence. %s %s %s',
                $primarySignal ?? 'No',
                $confidence,
                $isOBNear ? 'Order block zone active.' : '',
                $isFVGNear ? 'FVG present.' : '',
                ($mtf1hAgrees || $mtf4hAgrees) ? 'MTF trend aligned.' : ''
            ),
        ];

        return [
            'signal'     => $primarySignal,
            'entry'      => $entry,
            'sl'         => $sl,
            'tp'         => $tp,
            'confidence' => $confidence,
            'reason'     => $reason,
        ];
    }

    /**
     * Calculate Entry, Stop Loss, and Take Profit levels.
     *
     * BUY:  Entry = current close, SL = recent swing low - (ATR * multiplier), TP = Entry + (Entry - SL) * rrRatio
     * SELL: Entry = current close, SL = recent swing high + (ATR * multiplier), TP = Entry - (SL - Entry) * rrRatio
     */
    private function calculateEntrySlTp(
        array  $ohlcv,
        ?string $signal,
        float  $currentClose,
        float  $atr,
        float  $atrMultiplier,
        float  $rrRatio
    ): array {
        if ($signal === null) {
            return [0, 0, 0];
        }

        $entry = $currentClose;

        if ($signal === 'BUY') {
            $swingLow = $this->indicatorService->getRecentSwingLow($ohlcv, 20);
            $sl       = $swingLow - ($atr * $atrMultiplier);
            $risk     = $entry - $sl;
            $tp       = $entry + ($risk * $rrRatio);
        } else { // SELL
            $swingHigh = $this->indicatorService->getRecentSwingHigh($ohlcv, 20);
            $sl        = $swingHigh + ($atr * $atrMultiplier);
            $risk      = $sl - $entry;
            $tp        = $entry - ($risk * $rrRatio);
        }

        return [
            round($entry, 8),
            round($sl, 8),
            round($tp, 8),
        ];
    }

    /**
     * Get multi-timeframe trend data. Fetches higher timeframes.
     *
     * @param  string $symbol
     * @param  string $currentTimeframe
     * @return array  ['1h' => 'bullish', '4h' => 'bearish', ...]
     */
    private function getMTFTrend(string $symbol, string $currentTimeframe): array
    {
        $higherTimeframes = ['1h', '4h'];
        $ohlcvByTf        = [];

        foreach ($higherTimeframes as $tf) {
            // Don't fetch current or lower timeframes as "higher"
            if ($this->timeframeToMinutes($tf) <= $this->timeframeToMinutes($currentTimeframe)) {
                continue;
            }
            try {
                $ohlcvByTf[$tf] = $this->marketDataService->getOHLCV($symbol, $tf, 60);
            } catch (\Exception $e) {
                Log::warning("MTF data fetch failed for {$symbol} {$tf}", ['error' => $e->getMessage()]);
                $ohlcvByTf[$tf] = [];
            }
        }

        return $this->indicatorService->getMTFTrend($ohlcvByTf);
    }

    /**
     * Convert timeframe string to minutes for comparison.
     */
    private function timeframeToMinutes(string $tf): int
    {
        $map = [
            '1m'  => 1,
            '3m'  => 3,
            '5m'  => 5,
            '15m' => 15,
            '30m' => 30,
            '1h'  => 60,
            '4h'  => 240,
            '1D'  => 1440,
        ];
        return $map[$tf] ?? 0;
    }

    /**
     * Generate an advance OB limit order signal — no TradingView required.
     *
     * Detects valid BOS/CHoCH-linked Order Blocks from live Binance data and
     * creates a PENDING signal at the OB zone level. Price does NOT need to
     * be touching the OB yet — the signal is an advance limit order.
     *
     * Status lifecycle:
     *   pending  → price has not yet returned to OB zone (limit order waiting)
     *   active   → price entered the OB zone (activatePendingSignals() promotes it)
     *   win/loss → SL or TP hit (updateSignalResult() handles)
     *
     * @param  TradingPair $pair
     * @param  string      $timeframe
     * @return Signal|null
     */
    public function generateOBFVGSignal(TradingPair $pair, string $timeframe): ?Signal
    {
        try {
            $ohlcv = $this->marketDataService->getOHLCV(
                $pair->symbol,
                $timeframe,
                config('trading.indicators.candle_limit', 200)
            );

            if (count($ohlcv) < 60) {
                Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: insufficient candles", ['count' => count($ohlcv)]);
                return null;
            }

            $count        = count($ohlcv);
            $currentClose = (float) $ohlcv[$count - 1]['close'];
            $atr          = $this->indicatorService->calculateATR($ohlcv, config('trading.indicators.atr_length', 14));
            $rrRatio      = config('trading.signals.risk_reward_ratio', 2.0);

            // ── MTF trend for direction filtering ────────────────────────────
            $mtfTrend = $this->getMTFTrend($pair->symbol, $timeframe);
            $h1Trend  = $mtfTrend['1h'] ?? 'neutral';

            Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: starting detection", [
                'candles'      => $count,
                'currentClose' => $currentClose,
                'h1Trend'      => $h1Trend,
                'mtfTrend'     => $mtfTrend,
                'atr'          => $atr,
            ]);

            // ── Detect valid BOS/CHoCH-linked OBs ────────────────────────────
            $bullishOBs = ($h1Trend !== 'bearish')
                ? $this->indicatorService->detectOrderBlocks($ohlcv, 'bullish')
                : [];
            $bearishOBs = ($h1Trend !== 'bullish')
                ? $this->indicatorService->detectOrderBlocks($ohlcv, 'bearish')
                : [];

            Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: OB detection result", [
                'bullishOBs' => count($bullishOBs),
                'bearishOBs' => count($bearishOBs),
                'bullish_skipped' => $h1Trend === 'bearish' ? 'yes (h1 bearish)' : 'no',
                'bearish_skipped' => $h1Trend === 'bullish' ? 'yes (h1 bullish)' : 'no',
                'first_bullish_ob' => $bullishOBs[0] ?? null,
                'first_bearish_ob' => $bearishOBs[0] ?? null,
            ]);

            // ── Build candidate limit-order setups ────────────────────────────
            $candidates = [];

            foreach ($bullishOBs as $ob) {
                $entry = $ob['high'];
                $sl    = $ob['low'] - ($atr * 0.3);
                $risk  = $entry - $sl;
                if ($risk <= 0) continue;
                $candidates[] = [
                    'type'      => 'BUY',
                    'ob'        => $ob,
                    'entry'     => round($entry, 8),
                    'sl'        => round($sl, 8),
                    'tp'        => round($entry + ($risk * $rrRatio), 8),
                    'dir'       => 'bullish',
                    'proximity' => abs($entry - $currentClose),
                ];
                break; // only the most-recent bullish OB
            }

            foreach ($bearishOBs as $ob) {
                $entry = $ob['low'];
                $sl    = $ob['high'] + ($atr * 0.3);
                $risk  = $sl - $entry;
                if ($risk <= 0) continue;
                $candidates[] = [
                    'type'      => 'SELL',
                    'ob'        => $ob,
                    'entry'     => round($entry, 8),
                    'sl'        => round($sl, 8),
                    'tp'        => round($entry - ($risk * $rrRatio), 8),
                    'dir'       => 'bearish',
                    'proximity' => abs($entry - $currentClose),
                ];
                break; // only the most-recent bearish OB
            }

            if (empty($candidates)) {
                Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: no valid candidates built from OBs");
                return null;
            }

            Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: candidates built", [
                'count'      => count($candidates),
                'candidates' => array_map(fn($c) => [
                    'type'      => $c['type'],
                    'entry'     => $c['entry'],
                    'proximity' => $c['proximity'],
                ], $candidates),
            ]);

            // Pick the OB closest to current price
            usort($candidates, fn($a, $b) => $a['proximity'] <=> $b['proximity']);
            $best = $candidates[0];

            // ── Duplicate guard: skip if pending/active signal already at this OB ─
            $tol      = $best['entry'] * 0.002; // 0.2% tolerance
            $existing = Signal::where('trading_pair_id', $pair->id)
                ->where('timeframe', $timeframe)
                ->where('signal_type', $best['type'])
                ->whereIn('status', ['pending', 'active'])
                ->whereBetween('entry_price', [$best['entry'] - $tol, $best['entry'] + $tol])
                ->exists();

            if ($existing) {
                Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: duplicate guard blocked signal", [
                    'type'  => $best['type'],
                    'entry' => $best['entry'],
                    'tol'   => $tol,
                ]);
                return null;
            }

            // ── Confidence scoring ────────────────────────────────────────────
            $closes    = array_map(fn($c) => (float) $c['close'], $ohlcv);
            $emaLength = config('trading.indicators.ema_length', 34);
            $smma      = $this->indicatorService->calculateSMMA($closes, $emaLength);
            $zlema     = $this->indicatorService->calculateZLEMA($closes, $emaLength);
            $smmaLast  = $smma[$count - 1] ?? 0;
            $zlemaLast = $zlema[$count - 1] ?? 0;

            $emaTrend = 'neutral';
            if ($currentClose > $smmaLast && $zlemaLast > $smmaLast) $emaTrend = 'bullish';
            elseif ($currentClose < $smmaLast && $zlemaLast < $smmaLast) $emaTrend = 'bearish';

            $signalDir  = $best['dir'];
            $confidence = 60;
            if (($mtfTrend['1h'] ?? null) === $signalDir) $confidence += 15;
            if (($mtfTrend['4h'] ?? null) === $signalDir) $confidence += 10;
            if ($emaTrend === $signalDir)                  $confidence += 10;

            // Optional FVG confluence overlapping OB
            $fvgs      = $this->indicatorService->detectFVG($ohlcv);
            $fvgKey    = $best['type'] === 'BUY' ? 'bearish' : 'bullish';
            $hasFvg    = false;
            foreach ($fvgs[$fvgKey] ?? [] as $fvg) {
                if ($fvg['bottom'] <= $best['ob']['high'] && $fvg['top'] >= $best['ob']['low']) {
                    $hasFvg = true;
                    $confidence += 5;
                    break;
                }
            }
            $confidence = min(100, $confidence);

            Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: confidence scored", [
                'type'       => $best['type'],
                'confidence' => $confidence,
                'emaTrend'   => $emaTrend,
                'signalDir'  => $signalDir,
                'hasFvg'     => $hasFvg,
                'minRequired'=> config('trading.signals.min_confidence', 40),
            ]);

            if ($confidence < config('trading.signals.min_confidence', 40)) {
                Log::debug("OB signal [{$pair->symbol}/{$timeframe}]: rejected — confidence {$confidence} below minimum");
                return null;
            }

            // ── Build reason JSON ─────────────────────────────────────────────
            $obLabel = $best['type'] === 'BUY' ? 'Demand' : 'Supply';
            $reason  = [
                'type'             => 'OB_FVG_RETEST',
                'ob_zone'          => ['high' => $best['ob']['high'], 'low' => $best['ob']['low']],
                'fvg_confluence'   => $hasFvg,
                'ema_trend'        => $emaTrend,
                'mtf_trend_data'   => $mtfTrend,
                'market_structure' => ($best['type'] === 'BUY' ? 'Bullish' : 'Bearish')
                                      . ' OB – Limit Order' . ($hasFvg ? ' + FVG' : ''),
                'order_block'      => sprintf('%s OB: %.6f – %.6f', $obLabel, $best['ob']['low'], $best['ob']['high']),
                'fvg'              => $hasFvg ? 'FVG confluence present' : null,
                'mtf_trend'        => isset($mtfTrend['1h'], $mtfTrend['4h'])
                                      ? "1h: {$mtfTrend['1h']}, 4h: {$mtfTrend['4h']}"
                                      : 'MTF data unavailable',
                'summary'          => sprintf(
                    '%s limit order at %.6f (OB zone %.6f–%.6f). Waiting for price to return to zone.',
                    $best['type'], $best['entry'], $best['ob']['low'], $best['ob']['high']
                ),
            ];

            return DB::transaction(function () use ($pair, $timeframe, $best, $confidence, $reason) {
                $signal = Signal::create([
                    'trading_pair_id'  => $pair->id,
                    'timeframe'        => $timeframe,
                    'signal_type'      => $best['type'],
                    'entry_price'      => $best['entry'],
                    'stop_loss'        => $best['sl'],
                    'take_profit'      => $best['tp'],
                    'confidence_score' => $confidence,
                    'reason'           => $reason,
                    'status'           => 'pending',
                    'expires_at'       => now()->addHours(config('trading.signals.expiry_hours', 24)),
                ]);

                SendSignalNotification::dispatch($signal)->onQueue('notifications');

                Log::info("OB limit order (pending): {$best['type']} {$pair->symbol} {$timeframe}", [
                    'entry'    => $best['entry'],
                    'ob_zone'  => "{$best['ob']['low']}–{$best['ob']['high']}",
                    'fvg'      => $hasFvg ? 'yes' : 'no',
                    'confidence' => $confidence,
                ]);

                return $signal;
            });

        } catch (\Exception $e) {
            Log::error("OB signal generation failed: {$pair->symbol} {$timeframe}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Activate pending OB limit-order signals when price enters the OB zone.
     *
     * Called by UpdateSignalResults command. Checks each pending signal and
     * promotes it to 'active' when:
     *   BUY : current price <= entry (ob.high) and >= ob.low  (entered demand zone)
     *   SELL: current price >= entry (ob.low)  and <= ob.high (entered supply zone)
     *
     * @return int Number of signals activated
     */
    public function activatePendingSignals(): int
    {
        $activated = 0;

        $pending = Signal::where('status', 'pending')
            ->with('tradingPair')
            ->get();

        foreach ($pending as $signal) {
            try {
                // Expire stale pending signals
                if ($signal->expires_at && $signal->expires_at->isPast()) {
                    $signal->update(['status' => 'expired', 'closed_at' => now()]);
                    continue;
                }

                $currentPrice = $this->marketDataService->getCurrentPrice(
                    $signal->tradingPair->symbol
                );

                if ($currentPrice <= 0) {
                    continue;
                }

                $entry  = (float) $signal->entry_price;
                $ob     = $signal->reason['ob_zone'] ?? null;
                if (! $ob) {
                    continue;
                }

                $obHigh = (float) $ob['high'];
                $obLow  = (float) $ob['low'];

                $triggered = $signal->signal_type === 'BUY'
                    // Price returned to demand OB from above
                    ? ($currentPrice <= $entry && $currentPrice >= $obLow)
                    // Price returned to supply OB from below
                    : ($currentPrice >= $entry && $currentPrice <= $obHigh);

                if ($triggered) {
                    $signal->update(['status' => 'active']);
                    $activated++;

                    Log::info("Pending signal activated: #{$signal->id} {$signal->signal_type} {$signal->tradingPair->symbol}", [
                        'entry'        => $entry,
                        'currentPrice' => $currentPrice,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Error activating pending signal #{$signal->id}", [
                    'error' => $e->getMessage(),
                ]);
            }

            usleep(100000); // 100ms
        }

        return $activated;
    }

    /**
     * Update signal result based on current price.
     * Called by the UpdateSignalResults command.
     *
     * @param  Signal $signal
     * @return bool   True if signal was updated (win/loss/expired)
     */
    public function updateSignalResult(Signal $signal): bool
    {
        try {
            $currentPrice = $this->marketDataService->getCurrentPrice(
                $signal->tradingPair->symbol
            );

            if ($currentPrice <= 0) {
                return false;
            }

            $entry = (float) $signal->entry_price;
            $sl    = (float) $signal->stop_loss;
            $tp    = (float) $signal->take_profit;

            // Check if signal has expired
            if ($signal->expires_at && $signal->expires_at->isPast()) {
                $signal->update([
                    'status'     => 'expired',
                    'close_price' => $currentPrice,
                    'closed_at'  => now(),
                ]);
                return true;
            }

            if ($signal->signal_type === 'BUY') {
                if ($currentPrice <= $sl) {
                    // Stop loss hit
                    $resultPct = (($currentPrice - $entry) / $entry) * 100;
                    $signal->update([
                        'status'            => 'loss',
                        'close_price'       => $currentPrice,
                        'result_percentage' => round($resultPct, 4),
                        'closed_at'         => now(),
                    ]);
                    return true;
                }
                if ($currentPrice >= $tp) {
                    // Take profit hit
                    $resultPct = (($currentPrice - $entry) / $entry) * 100;
                    $signal->update([
                        'status'            => 'win',
                        'close_price'       => $currentPrice,
                        'result_percentage' => round($resultPct, 4),
                        'closed_at'         => now(),
                    ]);
                    return true;
                }
            } else { // SELL
                if ($currentPrice >= $sl) {
                    // Stop loss hit
                    $resultPct = (($entry - $currentPrice) / $entry) * 100;
                    $signal->update([
                        'status'            => 'loss',
                        'close_price'       => $currentPrice,
                        'result_percentage' => round($resultPct, 4),
                        'closed_at'         => now(),
                    ]);
                    return true;
                }
                if ($currentPrice <= $tp) {
                    // Take profit hit
                    $resultPct = (($entry - $currentPrice) / $entry) * 100;
                    $signal->update([
                        'status'            => 'win',
                        'close_price'       => $currentPrice,
                        'result_percentage' => round($resultPct, 4),
                        'closed_at'         => now(),
                    ]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Signal result update failed for signal #{$signal->id}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
