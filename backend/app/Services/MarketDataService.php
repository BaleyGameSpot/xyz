<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketDataService
{
    private Client $httpClient;
    private int $cacheSeconds = 60; // Cache for 60 seconds

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout'         => 15,
            'connect_timeout' => 10,
            'verify'          => true,
        ]);
    }

    /**
     * Get OHLCV data for a trading pair and timeframe.
     * Routes to the appropriate data source based on pair type.
     *
     * @param  string $symbol    e.g. BTCUSDT, EURUSD
     * @param  string $timeframe e.g. 15m, 1h
     * @param  int    $limit     Number of candles (default 200)
     * @return array             Array of OHLCV candles
     * @throws \RuntimeException When all data sources fail
     */
    public function getOHLCV(string $symbol, string $timeframe, int $limit = 200): array
    {
        $cacheKey = "ohlcv:{$symbol}:{$timeframe}:{$limit}";

        return Cache::remember($cacheKey, $this->cacheSeconds, function () use ($symbol, $timeframe, $limit) {
            if ($this->isCryptoPair($symbol)) {
                return $this->fetchBinanceKlines($symbol, $timeframe, $limit);
            }
            return $this->fetchForexData($symbol, $timeframe, $limit);
        });
    }

    /**
     * Get the current price for a symbol.
     *
     * @param  string $symbol Trading symbol
     * @return float          Current price
     */
    public function getCurrentPrice(string $symbol): float
    {
        $cacheKey = "price:{$symbol}";

        return Cache::remember($cacheKey, 10, function () use ($symbol) {
            if ($this->isCryptoPair($symbol)) {
                return $this->getBinanceCurrentPrice($symbol);
            }
            return $this->getForexCurrentPrice($symbol);
        });
    }

    /**
     * Fetch kline data from Binance API.
     *
     * @param  string $symbol    Binance symbol e.g. BTCUSDT
     * @param  string $timeframe Timeframe e.g. 15m
     * @param  int    $limit     Number of candles
     * @return array             Normalized OHLCV array
     */
    public function fetchBinanceKlines(string $symbol, string $timeframe, int $limit = 200): array
    {
        $binanceInterval = $this->getBinanceInterval($timeframe);
        $baseUrl         = config('trading.binance.base_url');
        $endpoint        = config('trading.binance.klines_endpoint');

        try {
            $response = $this->httpClient->get($baseUrl . $endpoint, [
                'query' => [
                    'symbol'   => strtoupper($symbol),
                    'interval' => $binanceInterval,
                    'limit'    => $limit,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data) || ! is_array($data)) {
                throw new \RuntimeException('Empty response from Binance API');
            }

            return $this->normalizeBinanceData($data);
        } catch (RequestException $e) {
            Log::error('Binance API error', [
                'symbol'    => $symbol,
                'timeframe' => $timeframe,
                'error'     => $e->getMessage(),
            ]);
            throw new \RuntimeException("Failed to fetch Binance data: " . $e->getMessage());
        }
    }

    /**
     * Fetch forex data from Alpha Vantage with TwelveData fallback.
     *
     * @param  string $symbol    Forex symbol e.g. EURUSD
     * @param  string $timeframe Timeframe e.g. 15m
     * @param  int    $limit     Number of candles
     * @return array             Normalized OHLCV array
     */
    public function fetchForexData(string $symbol, string $timeframe, int $limit = 200): array
    {
        try {
            return $this->fetchAlphaVantageData($symbol, $timeframe, $limit);
        } catch (\Exception $e) {
            Log::warning('Alpha Vantage failed, trying TwelveData', [
                'symbol' => $symbol,
                'error'  => $e->getMessage(),
            ]);
        }

        try {
            return $this->fetchTwelveData($symbol, $timeframe, $limit);
        } catch (\Exception $e) {
            Log::error('TwelveData also failed', [
                'symbol' => $symbol,
                'error'  => $e->getMessage(),
            ]);
            throw new \RuntimeException("All forex data sources failed for {$symbol}");
        }
    }

    /**
     * Fetch forex data from Alpha Vantage.
     */
    public function fetchAlphaVantageData(string $symbol, string $timeframe, int $limit = 200): array
    {
        $avInterval = $this->getAlphaVantageInterval($timeframe);
        if ($avInterval === null) {
            throw new \RuntimeException("Timeframe {$timeframe} not supported by Alpha Vantage");
        }

        $baseUrl = config('trading.alpha_vantage.base_url');
        $apiKey  = config('trading.alpha_vantage.api_key');
        [$from, $to] = $this->parseForexSymbol($symbol);

        try {
            $response = $this->httpClient->get($baseUrl . '/query', [
                'query' => [
                    'function'    => 'FX_INTRADAY',
                    'from_symbol' => $from,
                    'to_symbol'   => $to,
                    'interval'    => $avInterval,
                    'outputsize'  => 'full',
                    'apikey'      => $apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['Error Message'])) {
                throw new \RuntimeException($data['Error Message']);
            }

            $seriesKey = "Time Series FX ({$avInterval})";
            if (empty($data[$seriesKey])) {
                throw new \RuntimeException('No data in Alpha Vantage response');
            }

            return $this->normalizeAlphaVantageData($data[$seriesKey], $limit);
        } catch (RequestException $e) {
            throw new \RuntimeException("Alpha Vantage request failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch data from TwelveData API.
     */
    public function fetchTwelveData(string $symbol, string $timeframe, int $limit = 200): array
    {
        $tdInterval = $this->getTwelveDataInterval($timeframe);
        $baseUrl    = config('trading.twelve_data.base_url');
        $apiKey     = config('trading.twelve_data.api_key');

        // TwelveData uses slash for forex: EUR/USD
        $formattedSymbol = $this->isCryptoPair($symbol)
            ? strtoupper($symbol)
            : $this->formatForexSymbolWithSlash($symbol);

        try {
            $response = $this->httpClient->get($baseUrl . '/time_series', [
                'query' => [
                    'symbol'     => $formattedSymbol,
                    'interval'   => $tdInterval,
                    'outputsize' => $limit,
                    'apikey'     => $apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['status']) && $data['status'] === 'error') {
                throw new \RuntimeException($data['message'] ?? 'TwelveData error');
            }

            if (empty($data['values'])) {
                throw new \RuntimeException('No data in TwelveData response');
            }

            return $this->normalizeTwelveData($data['values'], $limit);
        } catch (RequestException $e) {
            throw new \RuntimeException("TwelveData request failed: " . $e->getMessage());
        }
    }

    /**
     * Get current price from Binance.
     */
    private function getBinanceCurrentPrice(string $symbol): float
    {
        $baseUrl  = config('trading.binance.base_url');
        $endpoint = config('trading.binance.ticker_endpoint');

        try {
            $response = $this->httpClient->get($baseUrl . $endpoint, [
                'query' => ['symbol' => strtoupper($symbol)],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return (float) ($data['price'] ?? 0);
        } catch (\Exception $e) {
            Log::error('Failed to get Binance price', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Get current forex price using latest candle from TwelveData.
     */
    private function getForexCurrentPrice(string $symbol): float
    {
        try {
            $candles = $this->fetchTwelveData($symbol, '1m', 1);
            return ! empty($candles) ? (float) $candles[0]['close'] : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Normalize Binance kline data to standard OHLCV format.
     * Binance kline format: [openTime, open, high, low, close, volume, closeTime, ...]
     */
    private function normalizeBinanceData(array $data): array
    {
        return array_map(function ($candle) {
            return [
                'timestamp' => (int) $candle[0],
                'open'      => (float) $candle[1],
                'high'      => (float) $candle[2],
                'low'       => (float) $candle[3],
                'close'     => (float) $candle[4],
                'volume'    => (float) $candle[5],
            ];
        }, $data);
    }

    /**
     * Normalize Alpha Vantage forex data.
     * Alpha Vantage format: ['2024-01-01 15:00:00' => ['1. open' => ..., ...]]
     */
    private function normalizeAlphaVantageData(array $series, int $limit): array
    {
        // Sort ascending (oldest first)
        ksort($series);
        $slice  = array_slice($series, -$limit, null, true);
        $result = [];

        foreach ($slice as $datetime => $values) {
            $result[] = [
                'timestamp' => strtotime($datetime) * 1000,
                'open'      => (float) $values['1. open'],
                'high'      => (float) $values['2. high'],
                'low'       => (float) $values['3. low'],
                'close'     => (float) $values['4. close'],
                'volume'    => 0.0, // Alpha Vantage forex doesn't provide volume
            ];
        }

        return $result;
    }

    /**
     * Normalize TwelveData response.
     * TwelveData format: [['datetime' => ..., 'open' => ..., 'high' => ..., ...]]
     */
    private function normalizeTwelveData(array $values, int $limit): array
    {
        // TwelveData returns newest first; reverse to oldest-first
        $reversed = array_reverse($values);
        $slice    = array_slice($reversed, -$limit);
        $result   = [];

        foreach ($slice as $candle) {
            $result[] = [
                'timestamp' => strtotime($candle['datetime']) * 1000,
                'open'      => (float) $candle['open'],
                'high'      => (float) $candle['high'],
                'low'       => (float) $candle['low'],
                'close'     => (float) $candle['close'],
                'volume'    => (float) ($candle['volume'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Convert standard timeframe to Binance interval format.
     */
    private function getBinanceInterval(string $timeframe): string
    {
        $map = config('trading.timeframes', []);
        return $map[$timeframe]['binance'] ?? $timeframe;
    }

    /**
     * Convert standard timeframe to Alpha Vantage interval.
     */
    private function getAlphaVantageInterval(string $timeframe): ?string
    {
        $map = config('trading.timeframes', []);
        return $map[$timeframe]['alpha_vantage'] ?? null;
    }

    /**
     * Convert standard timeframe to TwelveData interval.
     */
    private function getTwelveDataInterval(string $timeframe): string
    {
        $map = config('trading.timeframes', []);
        return $map[$timeframe]['twelve_data'] ?? $timeframe;
    }

    /**
     * Parse a forex symbol into from/to currency pair.
     * e.g. EURUSD => ['EUR', 'USD']
     */
    private function parseForexSymbol(string $symbol): array
    {
        $symbol = strtoupper($symbol);
        // Handle both EURUSD and EUR/USD formats
        if (str_contains($symbol, '/')) {
            return explode('/', $symbol);
        }
        return [substr($symbol, 0, 3), substr($symbol, 3, 3)];
    }

    /**
     * Format forex symbol with slash for TwelveData.
     * e.g. EURUSD => EUR/USD
     */
    private function formatForexSymbolWithSlash(string $symbol): string
    {
        if (str_contains($symbol, '/')) {
            return strtoupper($symbol);
        }
        $symbol = strtoupper($symbol);
        return substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
    }

    /**
     * Determine if a symbol is a crypto pair.
     */
    private function isCryptoPair(string $symbol): bool
    {
        $cryptoSuffixes = ['USDT', 'BTC', 'ETH', 'BNB', 'BUSD'];
        $symbol         = strtoupper($symbol);
        foreach ($cryptoSuffixes as $suffix) {
            if (str_ends_with($symbol, $suffix) && strlen($symbol) > strlen($suffix)) {
                return true;
            }
        }
        return false;
    }
}
