<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Market Data APIs
    |--------------------------------------------------------------------------
    */
    'binance' => [
        'base_url' => env('BINANCE_BASE_URL', 'https://api.binance.com'),
        'klines_endpoint' => '/api/v3/klines',
        'ticker_endpoint' => '/api/v3/ticker/price',
    ],

    'alpha_vantage' => [
        'base_url' => env('ALPHA_VANTAGE_BASE_URL', 'https://www.alphavantage.co'),
        'api_key' => env('ALPHA_VANTAGE_API_KEY', 'demo'),
    ],

    'twelve_data' => [
        'base_url' => env('TWELVE_DATA_BASE_URL', 'https://api.twelvedata.com'),
        'api_key' => env('TWELVE_DATA_API_KEY', 'demo'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Wallets
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'wallets' => [
            'USDT' => env('BINANCE_PAYMENT_WALLET_USDT', ''),
            'BTC'  => env('BINANCE_PAYMENT_WALLET_BTC', ''),
        ],
        'confirmation_blocks' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Indicator Parameters
    |--------------------------------------------------------------------------
    */
    'indicators' => [
        'internal_lookback' => 5,
        'swing_lookback' => 50,
        'ema_length' => 34,
        'atr_length' => 14,
        'candle_limit' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Signal Parameters
    |--------------------------------------------------------------------------
    */
    'signals' => [
        'atr_sl_multiplier' => 1.5,
        'risk_reward_ratio' => 2.0,
        'min_confidence' => 55,
        'expiry_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Timeframes
    |--------------------------------------------------------------------------
    */
    'timeframes' => [
        '1m'  => ['binance' => '1m',  'alpha_vantage' => '1min',  'twelve_data' => '1min'],
        '3m'  => ['binance' => '3m',  'alpha_vantage' => null,     'twelve_data' => '3min'],
        '5m'  => ['binance' => '5m',  'alpha_vantage' => '5min',  'twelve_data' => '5min'],
        '15m' => ['binance' => '15m', 'alpha_vantage' => '15min', 'twelve_data' => '15min'],
        '30m' => ['binance' => '30m', 'alpha_vantage' => '30min', 'twelve_data' => '30min'],
        '1h'  => ['binance' => '1h',  'alpha_vantage' => '60min', 'twelve_data' => '1h'],
        '4h'  => ['binance' => '4h',  'alpha_vantage' => null,     'twelve_data' => '4h'],
        '1D'  => ['binance' => '1d',  'alpha_vantage' => null,     'twelve_data' => '1day'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Definitions
    |--------------------------------------------------------------------------
    */
    'packages' => [
        'basic' => [
            'name'                => 'Pay As You Go',
            'price'               => 11.00,
            'promo_price'         => null,
            'pairs_limit'         => 2,
            'daily_signals_limit' => 2,
            'timeframes'          => ['15m', '30m'],
        ],
        'best' => [
            'name'                => 'Best Package',
            'price'               => 16.00,
            'promo_price'         => 14.00,
            'pairs_limit'         => 5,
            'daily_signals_limit' => 4,
            'timeframes'          => ['1m', '3m', '5m', '15m', '1h', '4h'],
        ],
        'premium' => [
            'name'                => 'Premium',
            'price'               => 25.00,
            'promo_price'         => null,
            'pairs_limit'         => null,
            'daily_signals_limit' => 10,
            'timeframes'          => ['1m', '3m', '5m', '15m', '30m', '1h', '4h', '1D'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | FCM
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'server_key'       => env('FCM_SERVER_KEY', ''),
        'credentials_file' => env('FIREBASE_CREDENTIALS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'auth'    => '10,1',
        'api'     => '60,1',
        'analyze' => '5,1',
    ],
];
