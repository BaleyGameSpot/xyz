<?php

namespace App\Services;

class IndicatorService
{
    /**
     * Calculate pivot highs using a lookback period.
     * A pivot high at index i means highs[i] is higher than all highs within [i-lookback, i+lookback].
     *
     * @param  array $highs    Array of high prices (oldest to newest)
     * @param  int   $lookback Number of bars to look left and right
     * @return array           Array of [index => price] for each pivot high
     */
    public function calculatePivotHighs(array $highs, int $lookback): array
    {
        $pivots = [];
        $count  = count($highs);

        for ($i = $lookback; $i < $count - $lookback; $i++) {
            $isPivot = true;
            for ($j = $i - $lookback; $j <= $i + $lookback; $j++) {
                if ($j !== $i && $highs[$j] >= $highs[$i]) {
                    $isPivot = false;
                    break;
                }
            }
            if ($isPivot) {
                $pivots[$i] = $highs[$i];
            }
        }

        return $pivots;
    }

    /**
     * Calculate pivot lows using a lookback period.
     * A pivot low at index i means lows[i] is lower than all lows within [i-lookback, i+lookback].
     *
     * @param  array $lows     Array of low prices (oldest to newest)
     * @param  int   $lookback Number of bars to look left and right
     * @return array           Array of [index => price] for each pivot low
     */
    public function calculatePivotLows(array $lows, int $lookback): array
    {
        $pivots = [];
        $count  = count($lows);

        for ($i = $lookback; $i < $count - $lookback; $i++) {
            $isPivot = true;
            for ($j = $i - $lookback; $j <= $i + $lookback; $j++) {
                if ($j !== $i && $lows[$j] <= $lows[$i]) {
                    $isPivot = false;
                    break;
                }
            }
            if ($isPivot) {
                $pivots[$i] = $lows[$i];
            }
        }

        return $pivots;
    }

    /**
     * Detect market structure: BOS (Break of Structure) and CHoCH (Change of Character).
     *
     * Logic translated from Chinar AllinOne PineScript:
     * - Bullish BOS: Price (close) breaks above a pivot high while trend is bullish
     * - Bearish BOS: Price (close) breaks below a pivot low while trend is bearish
     * - CHoCH: Price breaks structure against the current trend (reversal)
     *
     * @param  array $ohlcv    Array of OHLCV data ['open', 'high', 'low', 'close', 'volume']
     * @param  int   $lookback Lookback period for pivot detection
     * @return array           ['trend' => 'bullish'|'bearish'|'neutral', 'pattern' => ..., 'signal' => 'BUY'|'SELL'|null]
     */
    public function detectMarketStructure(array $ohlcv, int $lookback): array
    {
        $closes = array_column($ohlcv, 'close');
        $highs  = array_column($ohlcv, 'high');
        $lows   = array_column($ohlcv, 'low');
        $count  = count($closes);

        if ($count < $lookback * 2 + 5) {
            return ['trend' => 'neutral', 'pattern' => null, 'signal' => null, 'strength' => 0];
        }

        $pivotHighs = $this->calculatePivotHighs($highs, $lookback);
        $pivotLows  = $this->calculatePivotLows($lows, $lookback);

        // Determine trend by comparing recent pivot highs and lows
        $recentPivotHighs = array_slice($pivotHighs, -3, 3, true);
        $recentPivotLows  = array_slice($pivotLows, -3, 3, true);

        $trend = $this->determineTrend($recentPivotHighs, $recentPivotLows);

        // Get last few candles for structure break detection
        $currentClose = $closes[$count - 1];
        $prevClose    = $closes[$count - 2];

        // Get the last significant pivot high and low
        $lastPivotHigh = ! empty($pivotHighs) ? max($pivotHighs) : 0;
        $lastPivotLow  = ! empty($pivotLows) ? min($pivotLows) : PHP_FLOAT_MAX;

        // Detect the most recent pivot high/low index
        $lastPivotHighIdx = ! empty($pivotHighs) ? array_key_last($pivotHighs) : -1;
        $lastPivotLowIdx  = ! empty($pivotLows) ? array_key_last($pivotLows) : -1;

        $pattern = null;
        $signal  = null;
        $strength = 0;

        // BOS/CHoCH Detection
        if ($currentClose > $lastPivotHigh && $prevClose <= $lastPivotHigh) {
            // Price broke above last pivot high
            if ($trend === 'bullish') {
                $pattern  = 'BOS';
                $signal   = 'BUY';
                $strength = 80;
            } else {
                // Breaking above while bearish = Change of Character (bullish reversal)
                $pattern  = 'CHoCH';
                $signal   = 'BUY';
                $strength = 70;
            }
        } elseif ($currentClose < $lastPivotLow && $prevClose >= $lastPivotLow) {
            // Price broke below last pivot low
            if ($trend === 'bearish') {
                $pattern  = 'BOS';
                $signal   = 'SELL';
                $strength = 80;
            } else {
                // Breaking below while bullish = Change of Character (bearish reversal)
                $pattern  = 'CHoCH';
                $signal   = 'SELL';
                $strength = 70;
            }
        } else {
            // No fresh break – check for CHoCH+ (strong reversal confirmation)
            $recentHighs = array_slice($highs, -10);
            $recentLows  = array_slice($lows, -10);
            $priceAboveMid = $currentClose > (max($recentHighs) + min($recentLows)) / 2;

            if ($trend === 'bearish' && $priceAboveMid) {
                $pattern  = 'CHoCH+';
                $signal   = 'BUY';
                $strength = 60;
            } elseif ($trend === 'bullish' && ! $priceAboveMid) {
                $pattern  = 'CHoCH+';
                $signal   = 'SELL';
                $strength = 60;
            }
        }

        return [
            'trend'           => $trend,
            'pattern'         => $pattern,
            'signal'          => $signal,
            'strength'        => $strength,
            'last_pivot_high' => $lastPivotHigh,
            'last_pivot_low'  => $lastPivotLow !== PHP_FLOAT_MAX ? $lastPivotLow : 0,
        ];
    }

    /**
     * Detect volumetric Order Blocks.
     *
     * Bullish OB: Last bearish candle before a bullish BOS/CHoCH
     * Bearish OB: Last bullish candle before a bearish BOS/CHoCH
     *
     * @param  array  $ohlcv     Array of OHLCV candles
     * @param  string $direction 'bullish' or 'bearish'
     * @return array             Order block zones ['high', 'low', 'mid', 'index', 'volume', 'valid']
     */
    /**
     * Detect Order Blocks — aligned with Chinar AllinOne Pine Script.
     *
     * How Pine Script creates OBs:
     * - Bullish OB: fires when close CROSSES ABOVE a pivot high (bullish BOS/CHoCH).
     *   The OB is the candle at the LOWEST LOW in the range between that pivot high
     *   and the breakout bar. top = hl2 of that candle (Precise mode), bottom = its low.
     * - Bearish OB: fires when close CROSSES BELOW a pivot low (bearish BOS/CHoCH).
     *   The OB is the candle at the HIGHEST HIGH in the same range.
     *   top = its high, bottom = hl2 of that candle.
     *
     * Mitigation (Absolute mode): bullish OB invalid if any subsequent close < ob.low;
     * bearish OB invalid if any subsequent close > ob.high.
     *
     * @param  array  $ohlcv     OHLCV candles oldest→newest
     * @param  string $direction 'bullish' | 'bearish'
     * @param  int    $lookback  Pivot lookback (Pine default iLen = 5)
     * @return array             Up to 3 valid OBs, most recent first
     */
    public function detectOrderBlocks(array $ohlcv, string $direction, int $lookback = 5): array
    {
        $orderBlocks = [];
        $count = count($ohlcv);

        if ($count < $lookback * 2 + 10) {
            return $orderBlocks;
        }

        $closes = array_column($ohlcv, 'close');
        $highs  = array_column($ohlcv, 'high');
        $lows   = array_column($ohlcv, 'low');

        $currentClose = (float) $closes[$count - 1];

        $pivotHighs = $this->calculatePivotHighs($highs, $lookback);
        $pivotLows  = $this->calculatePivotLows($lows, $lookback);

        if ($direction === 'bullish') {
            // For each pivot high, find the breakout bar (close crosses above pivot)
            foreach (array_reverse($pivotHighs, true) as $pivotIdx => $pivotPrice) {

                $breakoutIdx = null;
                for ($i = $pivotIdx + 1; $i < $count; $i++) {
                    if ((float) $closes[$i] > $pivotPrice && (float) $closes[$i - 1] <= $pivotPrice) {
                        $breakoutIdx = $i;
                        break;
                    }
                }
                if ($breakoutIdx === null) continue;

                // Find candle at lowest low in range [pivotIdx … breakoutIdx]
                $lowestLow = PHP_FLOAT_MAX;
                $obIdx     = $pivotIdx;
                for ($i = $pivotIdx; $i <= $breakoutIdx; $i++) {
                    if ((float) $lows[$i] < $lowestLow) {
                        $lowestLow = (float) $lows[$i];
                        $obIdx     = $i;
                    }
                }

                // OB dimensions — Precise mode: top = hl2 of OB candle, bottom = lowest low
                $obTop = ((float) $highs[$obIdx] + (float) $lows[$obIdx]) / 2.0;
                $obBot = $lowestLow;
                $obMid = ($obTop + $obBot) / 2.0;

                // OB must be below current price (it's a demand zone below market)
                if ($obTop >= $currentClose) continue;

                // Mitigation (Middle mode): invalidated only if a close goes below the OB midpoint.
                // More lenient than Absolute (close < obBot) — allows wicks through the bottom
                // without fully invalidating the zone, matching Pine Script's "Middle" mode.
                $mitigated = false;
                for ($i = $breakoutIdx + 1; $i < $count; $i++) {
                    if ((float) $closes[$i] < $obMid) { $mitigated = true; break; }
                }
                if ($mitigated) continue;

                $orderBlocks[] = [
                    'high'  => $obTop,
                    'low'   => $obBot,
                    'mid'   => ($obTop + $obBot) / 2.0,
                    'index' => $obIdx,
                    'volume'=> (float) ($ohlcv[$obIdx]['volume'] ?? 0),
                ];

                if (count($orderBlocks) >= 3) break;
            }

        } else {
            // For each pivot low, find the breakout bar (close crosses below pivot)
            foreach (array_reverse($pivotLows, true) as $pivotIdx => $pivotPrice) {

                $breakoutIdx = null;
                for ($i = $pivotIdx + 1; $i < $count; $i++) {
                    if ((float) $closes[$i] < $pivotPrice && (float) $closes[$i - 1] >= $pivotPrice) {
                        $breakoutIdx = $i;
                        break;
                    }
                }
                if ($breakoutIdx === null) continue;

                // Find candle at highest high in range [pivotIdx … breakoutIdx]
                $highestHigh = 0.0;
                $obIdx       = $pivotIdx;
                for ($i = $pivotIdx; $i <= $breakoutIdx; $i++) {
                    if ((float) $highs[$i] > $highestHigh) {
                        $highestHigh = (float) $highs[$i];
                        $obIdx       = $i;
                    }
                }

                // OB dimensions — top = highest high, bottom = hl2 of OB candle
                $obTop = $highestHigh;
                $obBot = ((float) $highs[$obIdx] + (float) $lows[$obIdx]) / 2.0;
                $obMid = ($obTop + $obBot) / 2.0;

                // OB must be above current price (it's a supply zone above market)
                if ($obBot <= $currentClose) continue;

                // Mitigation (Middle mode): invalidated only if a close goes above the OB midpoint.
                $mitigated = false;
                for ($i = $breakoutIdx + 1; $i < $count; $i++) {
                    if ((float) $closes[$i] > $obMid) { $mitigated = true; break; }
                }
                if ($mitigated) continue;

                $orderBlocks[] = [
                    'high'  => $obTop,
                    'low'   => $obBot,
                    'mid'   => ($obTop + $obBot) / 2.0,
                    'index' => $obIdx,
                    'volume'=> (float) ($ohlcv[$obIdx]['volume'] ?? 0),
                ];

                if (count($orderBlocks) >= 3) break;
            }
        }

        return $orderBlocks;
    }

    /**
     * Detect Fair Value Gaps (FVG).
     *
     * Bullish FVG: low[i-2] > high[i]     => gap between candle 2 bars ago and current
     * Bearish FVG: high[i-2] < low[i]     => gap between candle 2 bars ago and current
     *
     * @param  array $ohlcv Array of OHLCV candles
     * @return array        ['bullish' => [...zones], 'bearish' => [...zones]]
     */
    public function detectFVG(array $ohlcv): array
    {
        $bullishFVGs = [];
        $bearishFVGs = [];
        $count       = count($ohlcv);

        if ($count < 3) {
            return ['bullish' => [], 'bearish' => []];
        }

        for ($i = 2; $i < $count; $i++) {
            $c0 = $ohlcv[$i];       // Current candle
            $c2 = $ohlcv[$i - 2];   // 2 candles ago

            // Bullish FVG: gap exists where low of 2-bar-ago > high of current
            if ((float) $c2['low'] > (float) $c0['high']) {
                $bullishFVGs[] = [
                    'top'    => (float) $c2['low'],
                    'bottom' => (float) $c0['high'],
                    'mid'    => ((float) $c2['low'] + (float) $c0['high']) / 2,
                    'index'  => $i,
                    'filled' => false,
                ];
            }

            // Bearish FVG: gap exists where high of 2-bar-ago < low of current
            if ((float) $c2['high'] < (float) $c0['low']) {
                $bearishFVGs[] = [
                    'top'    => (float) $c0['low'],
                    'bottom' => (float) $c2['high'],
                    'mid'    => ((float) $c0['low'] + (float) $c2['high']) / 2,
                    'index'  => $i,
                    'filled' => false,
                ];
            }
        }

        // Return most recent 3 FVGs of each type
        return [
            'bullish' => array_slice(array_reverse($bullishFVGs), 0, 3),
            'bearish' => array_slice(array_reverse($bearishFVGs), 0, 3),
        ];
    }

    /**
     * Calculate Exponential Moving Average (EMA).
     *
     * @param  array $closes Array of closing prices
     * @param  int   $length EMA period
     * @return array         Array of EMA values (same length as closes)
     */
    public function calculateEMA(array $closes, int $length): array
    {
        $count  = count($closes);
        $ema    = array_fill(0, $count, 0.0);
        $k      = 2.0 / ($length + 1);

        if ($count < $length) {
            return $ema;
        }

        // Seed with SMA
        $ema[$length - 1] = array_sum(array_slice($closes, 0, $length)) / $length;

        for ($i = $length; $i < $count; $i++) {
            $ema[$i] = $closes[$i] * $k + $ema[$i - 1] * (1 - $k);
        }

        return $ema;
    }

    /**
     * Calculate Smoothed Moving Average (SMMA / RMA).
     *
     * SMMA is used in the Chinar AllinOne indicator for trend.
     *
     * @param  array $closes Array of closing prices
     * @param  int   $length SMMA period
     * @return array         Array of SMMA values
     */
    public function calculateSMMA(array $closes, int $length): array
    {
        $count = count($closes);
        $smma  = array_fill(0, $count, 0.0);

        if ($count < $length) {
            return $smma;
        }

        // Seed with SMA
        $smma[$length - 1] = array_sum(array_slice($closes, 0, $length)) / $length;

        for ($i = $length; $i < $count; $i++) {
            $smma[$i] = ($smma[$i - 1] * ($length - 1) + $closes[$i]) / $length;
        }

        return $smma;
    }

    /**
     * Calculate Zero Lag EMA (ZLEMA).
     *
     * ZLEMA reduces the lag of a standard EMA by adjusting the input.
     * zlema_input = close + (close - close[lag])  where lag = (length - 1) / 2
     *
     * @param  array $closes Array of closing prices
     * @param  int   $length ZLEMA period
     * @return array         Array of ZLEMA values
     */
    public function calculateZLEMA(array $closes, int $length): array
    {
        $count = count($closes);
        $lag   = (int) (($length - 1) / 2);
        $zlemaInputs = [];

        for ($i = 0; $i < $count; $i++) {
            if ($i < $lag) {
                $zlemaInputs[] = $closes[$i];
            } else {
                // Zero-lag adjustment: 2*close - close[lag]
                $zlemaInputs[] = 2.0 * $closes[$i] - $closes[$i - $lag];
            }
        }

        return $this->calculateEMA($zlemaInputs, $length);
    }

    /**
     * Calculate Average True Range (ATR).
     *
     * @param  array $ohlcv  Array of OHLCV candles
     * @param  int   $length ATR period (default 14)
     * @return float         Current ATR value
     */
    public function calculateATR(array $ohlcv, int $length = 14): float
    {
        $count = count($ohlcv);
        if ($count < $length + 1) {
            // Fallback: simple average of high-low range
            $ranges = array_map(fn($c) => (float) $c['high'] - (float) $c['low'], $ohlcv);
            return ! empty($ranges) ? array_sum($ranges) / count($ranges) : 0.0;
        }

        $trueRanges = [];
        for ($i = 1; $i < $count; $i++) {
            $hl  = (float) $ohlcv[$i]['high'] - (float) $ohlcv[$i]['low'];
            $hpc = abs((float) $ohlcv[$i]['high'] - (float) $ohlcv[$i - 1]['close']);
            $lpc = abs((float) $ohlcv[$i]['low'] - (float) $ohlcv[$i - 1]['close']);
            $trueRanges[] = max($hl, $hpc, $lpc);
        }

        // Calculate RMA (Wilder's smoothing) for ATR
        $atr = array_sum(array_slice($trueRanges, 0, $length)) / $length;
        for ($i = $length; $i < count($trueRanges); $i++) {
            $atr = ($atr * ($length - 1) + $trueRanges[$i]) / $length;
        }

        return (float) $atr;
    }

    /**
     * Get multi-timeframe EMA trend impulse.
     *
     * Uses SMMA(34) and ZLEMA(34) to determine impulse color:
     * - Bullish impulse: close > SMMA AND ZLEMA > SMMA (green)
     * - Bearish impulse: close < SMMA AND ZLEMA < SMMA (red)
     * - Neutral: mixed signals
     *
     * @param  array $ohlcvByTimeframe ['1h' => [...ohlcv], '4h' => [...ohlcv], ...]
     * @return array                    ['1h' => 'bullish'|'bearish'|'neutral', ...]
     */
    public function getMTFTrend(array $ohlcvByTimeframe): array
    {
        $trends = [];
        $length = config('trading.indicators.ema_length', 34);

        foreach ($ohlcvByTimeframe as $timeframe => $ohlcv) {
            if (empty($ohlcv)) {
                $trends[$timeframe] = 'neutral';
                continue;
            }

            $closes      = array_column($ohlcv, 'close');
            $closes      = array_map('floatval', $closes);
            $count       = count($closes);

            if ($count < $length + 5) {
                $trends[$timeframe] = 'neutral';
                continue;
            }

            $smma        = $this->calculateSMMA($closes, $length);
            $zlema       = $this->calculateZLEMA($closes, $length);
            $currentClose = $closes[$count - 1];
            $currentSMMA  = $smma[$count - 1];
            $currentZLEMA = $zlema[$count - 1];

            if ($currentClose > $currentSMMA && $currentZLEMA > $currentSMMA) {
                $trends[$timeframe] = 'bullish';
            } elseif ($currentClose < $currentSMMA && $currentZLEMA < $currentSMMA) {
                $trends[$timeframe] = 'bearish';
            } else {
                $trends[$timeframe] = 'neutral';
            }
        }

        return $trends;
    }

    /**
     * Detect Equal Highs (EQH) and Equal Lows (EQL).
     *
     * These are liquidity zones where price is likely to be swept.
     *
     * @param  array $ohlcv   OHLCV data
     * @param  float $tolerance Percentage tolerance for "equal" (default 0.1%)
     * @return array           ['eqh' => [...levels], 'eql' => [...levels]]
     */
    public function detectEQHL(array $ohlcv, float $tolerance = 0.001): array
    {
        $highs  = array_map(fn($c) => (float) $c['high'], $ohlcv);
        $lows   = array_map(fn($c) => (float) $c['low'], $ohlcv);
        $count  = count($ohlcv);
        $eqh    = [];
        $eql    = [];

        for ($i = 5; $i < $count - 1; $i++) {
            for ($j = $i - 5; $j < $i; $j++) {
                if ($highs[$j] > 0) {
                    $diff = abs($highs[$i] - $highs[$j]) / $highs[$j];
                    if ($diff <= $tolerance) {
                        $eqh[] = [
                            'level'   => ($highs[$i] + $highs[$j]) / 2,
                            'index_a' => $j,
                            'index_b' => $i,
                        ];
                    }
                }
                if ($lows[$j] > 0) {
                    $diff = abs($lows[$i] - $lows[$j]) / $lows[$j];
                    if ($diff <= $tolerance) {
                        $eql[] = [
                            'level'   => ($lows[$i] + $lows[$j]) / 2,
                            'index_a' => $j,
                            'index_b' => $i,
                        ];
                    }
                }
            }
        }

        return [
            'eqh' => array_slice(array_unique($eqh, SORT_REGULAR), 0, 5),
            'eql' => array_slice(array_unique($eql, SORT_REGULAR), 0, 5),
        ];
    }

    /**
     * Calculate confidence score based on indicator agreement.
     *
     * Checks: market_structure, order_block_near, fvg_near, mtf_1h_agrees, mtf_4h_agrees, ema_trend
     *
     * @param  array $indicators Array of indicator results
     * @return int               0-100 confidence score
     */
    public function calculateConfidence(array $indicators): int
    {
        $weights = [
            'market_structure' => 30,  // Most important
            'order_block_near' => 20,
            'fvg_near'         => 15,
            'mtf_1h_agrees'    => 15,
            'mtf_4h_agrees'    => 10,
            'ema_trend'        => 10,
        ];

        $score = 0;
        $total = array_sum($weights);

        foreach ($weights as $check => $weight) {
            if (! empty($indicators[$check]) && $indicators[$check] === true) {
                $score += $weight;
            }
        }

        // Bonus for pattern strength
        if (isset($indicators['pattern_strength'])) {
            $bonus = (int) ($indicators['pattern_strength'] * 0.1);
            $score = min(100, $score + $bonus);
        }

        return (int) min(100, max(0, ($score / $total) * 100));
    }

    /**
     * Find the recent swing low for stop loss calculation.
     *
     * @param  array $ohlcv   OHLCV data
     * @param  int   $lookback Number of recent candles to search
     * @return float           Swing low price
     */
    public function getRecentSwingLow(array $ohlcv, int $lookback = 20): float
    {
        $slice = array_slice($ohlcv, -$lookback);
        $lows  = array_column($slice, 'low');
        return ! empty($lows) ? (float) min($lows) : 0.0;
    }

    /**
     * Find the recent swing high for stop loss calculation.
     *
     * @param  array $ohlcv   OHLCV data
     * @param  int   $lookback Number of recent candles to search
     * @return float           Swing high price
     */
    public function getRecentSwingHigh(array $ohlcv, int $lookback = 20): float
    {
        $slice  = array_slice($ohlcv, -$lookback);
        $highs  = array_column($slice, 'high');
        return ! empty($highs) ? (float) max($highs) : 0.0;
    }

    /**
     * Check if price is near an order block zone.
     *
     * @param  float $price       Current price
     * @param  array $orderBlocks Array of order blocks
     * @param  float $tolerance   How close to OB (default 0.2%)
     * @return bool
     */
    public function isPriceNearOrderBlock(float $price, array $orderBlocks, float $tolerance = 0.002): bool
    {
        foreach ($orderBlocks as $ob) {
            if ($price >= $ob['low'] * (1 - $tolerance) && $price <= $ob['high'] * (1 + $tolerance)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if price is near a Fair Value Gap.
     *
     * @param  float  $price     Current price
     * @param  array  $fvgs      Array of FVG zones (each has 'top' and 'bottom')
     * @param  string $direction 'bullish' or 'bearish'
     * @return bool
     */
    public function isPriceNearFVG(float $price, array $fvgs, string $direction): bool
    {
        $key = $direction === 'bullish' ? 'bullish' : 'bearish';
        foreach ($fvgs[$key] ?? [] as $fvg) {
            if ($price >= $fvg['bottom'] && $price <= $fvg['top']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect OB + FVG retest setups — aligned with Chinar AllinOne Pine Script.
     *
     * Primary trigger (obtouch alert in Pine Script):
     *   SELL: candle HIGH enters bearish supply OB from below → ta.crossover(high, ob.btm)
     *         Entry = ob.low (bottom of supply OB), SL = ob.high + ATR buffer
     *   BUY:  candle LOW  enters bullish demand OB from above → ta.crossunder(low, ob.top)
     *         Entry = ob.high (top of demand OB), SL = ob.low − ATR buffer
     *
     * FVG is optional confluence: if an FVG overlaps the OB, confidence gets a boost
     * but the signal fires purely on OB touch — no FVG required.
     *
     * @param  array $ohlcv   Full OHLCV array
     * @param  float $atr     Current ATR value (for SL buffer)
     * @param  float $rrRatio Risk:Reward ratio for TP calculation
     * @return array ['sell' => [...setups], 'buy' => [...setups]]
     */
    public function detectOBFVGSetup(array $ohlcv, float $atr, float $rrRatio = 2.0): array
    {
        if (count($ohlcv) < 15) {
            return ['sell' => [], 'buy' => []];
        }

        $count        = count($ohlcv);
        $lastCandle   = $ohlcv[$count - 1];
        $currentClose = (float) $lastCandle['close'];
        $currentHigh  = (float) $lastCandle['high'];
        $currentLow   = (float) $lastCandle['low'];

        $supplyOBs = $this->detectOrderBlocks($ohlcv, 'bearish');
        $demandOBs = $this->detectOrderBlocks($ohlcv, 'bullish');
        $fvgs      = $this->detectFVG($ohlcv);

        $sellSetups = [];
        $buySetups  = [];

        // ── SELL: candle high enters bearish supply OB (obtouch) ──────────────────────
        // Pine Script: ta.crossover(high, ob.btm) — high has reached or crossed ob.low
        // Entry = ob.low (limit sell at bottom edge of supply OB)
        foreach ($supplyOBs as $ob) {
            // Supply OB must be above current close
            if ($ob['low'] <= $currentClose) {
                continue;
            }

            // OB-touch trigger: current candle high reached/entered the OB
            if ($currentHigh < $ob['low']) {
                continue;
            }

            $entryPrice = $ob['low'];
            $slPrice    = $ob['high'] + ($atr * 0.3);
            $risk       = $slPrice - $entryPrice;
            if ($risk <= 0) {
                continue;
            }
            $tp = $entryPrice - ($risk * $rrRatio);

            // Optional FVG confluence: bullish FVG overlapping with or adjacent to OB
            $confluenceFvg = null;
            foreach ($fvgs['bullish'] as $fvg) {
                if ($fvg['bottom'] <= $ob['high'] && $fvg['top'] >= $ob['low']) {
                    $confluenceFvg = $fvg;
                    break;
                }
            }

            $sellSetups[] = [
                'ob'             => $ob,
                'fvg'            => $confluenceFvg,
                'fvg_confluence' => $confluenceFvg !== null,
                'fvg_dist_pct'   => 0.0,
                'entry'          => round($entryPrice, 8),
                'sl'             => round($slPrice, 8),
                'tp'             => round($tp, 8),
                'proximity'      => abs($entryPrice - $currentClose),
            ];
        }

        // ── BUY: candle low enters bullish demand OB (obtouch) ────────────────────────
        // Pine Script: ta.crossunder(low, ob.top) — low has reached or crossed ob.high
        // Entry = ob.high (limit buy at top edge of demand OB)
        foreach ($demandOBs as $ob) {
            // Demand OB must be below current close
            if ($ob['high'] >= $currentClose) {
                continue;
            }

            // OB-touch trigger: current candle low dipped into or touched the OB
            if ($currentLow > $ob['high']) {
                continue;
            }

            $entryPrice = $ob['high'];
            $slPrice    = $ob['low'] - ($atr * 0.3);
            $risk       = $entryPrice - $slPrice;
            if ($risk <= 0) {
                continue;
            }
            $tp = $entryPrice + ($risk * $rrRatio);

            // Optional FVG confluence: bearish FVG overlapping with or adjacent to OB
            $confluenceFvg = null;
            foreach ($fvgs['bearish'] as $fvg) {
                if ($fvg['bottom'] <= $ob['high'] && $fvg['top'] >= $ob['low']) {
                    $confluenceFvg = $fvg;
                    break;
                }
            }

            $buySetups[] = [
                'ob'             => $ob,
                'fvg'            => $confluenceFvg,
                'fvg_confluence' => $confluenceFvg !== null,
                'fvg_dist_pct'   => 0.0,
                'entry'          => round($entryPrice, 8),
                'sl'             => round($slPrice, 8),
                'tp'             => round($tp, 8),
                'proximity'      => abs($entryPrice - $currentClose),
            ];
        }

        usort($sellSetups, fn($a, $b) => $a['proximity'] <=> $b['proximity']);
        usort($buySetups,  fn($a, $b) => $a['proximity'] <=> $b['proximity']);

        return [
            'sell' => $sellSetups,
            'buy'  => $buySetups,
        ];
    }

    /**
     * Determine market trend from pivot highs and lows.
     */
    private function determineTrend(array $pivotHighs, array $pivotLows): string
    {
        if (count($pivotHighs) < 2 || count($pivotLows) < 2) {
            return 'neutral';
        }

        $highValues = array_values($pivotHighs);
        $lowValues  = array_values($pivotLows);

        $higherHighs = $highValues[count($highValues) - 1] > $highValues[count($highValues) - 2];
        $higherLows  = $lowValues[count($lowValues) - 1] > $lowValues[count($lowValues) - 2];
        $lowerHighs  = $highValues[count($highValues) - 1] < $highValues[count($highValues) - 2];
        $lowerLows   = $lowValues[count($lowValues) - 1] < $lowValues[count($lowValues) - 2];

        if ($higherHighs && $higherLows) {
            return 'bullish';
        }
        if ($lowerHighs && $lowerLows) {
            return 'bearish';
        }

        return 'neutral';
    }
}
