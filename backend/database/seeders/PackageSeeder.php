<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name'                => 'Pay As You Go',
                'slug'                => 'basic',
                'price'               => 11.00,
                'promo_price'         => null,
                'pairs_limit'         => 2,
                'daily_signals_limit' => 2,
                'timeframes'          => ['15m', '30m'],
                'description'         => 'Perfect for beginners. Access 2 trading pairs with essential timeframes.',
                'features'            => [
                    '2 Trading Pairs (EUR/USD, BTC/USDT)',
                    '15m & 30m Timeframes',
                    '2 Signals per Day',
                    'Market Structure Alerts',
                    'Basic Push Notifications',
                ],
                'is_active'           => true,
                'sort_order'          => 1,
            ],
            [
                'name'                => 'Best Package',
                'slug'                => 'best',
                'price'               => 16.00,
                'promo_price'         => 14.00,
                'pairs_limit'         => 5,
                'daily_signals_limit' => 4,
                'timeframes'          => ['1m', '3m', '5m', '15m', '1h', '4h'],
                'description'         => 'Best value. 5 pairs across multiple timeframes for serious traders.',
                'features'            => [
                    '5 Trading Pairs',
                    '1m, 3m, 5m, 15m, 1h & 4h Timeframes',
                    '4 Signals per Day',
                    'Full Market Structure Analysis',
                    'Order Block Detection',
                    'Fair Value Gap (FVG) Alerts',
                    'Multi-Timeframe EMA Trend',
                    'Priority Push Notifications',
                ],
                'is_active'           => true,
                'sort_order'          => 2,
            ],
            [
                'name'                => 'Premium',
                'slug'                => 'premium',
                'price'               => 25.00,
                'promo_price'         => null,
                'pairs_limit'         => null, // unlimited
                'daily_signals_limit' => 10,
                'timeframes'          => ['1m', '3m', '5m', '15m', '30m', '1h', '4h', '1D'],
                'description'         => 'Full access. All pairs, all timeframes, maximum signals for professional traders.',
                'features'            => [
                    'Unlimited Trading Pairs',
                    'All Timeframes (1m to Daily)',
                    '10 Signals per Day',
                    'Complete Chinar AllinOne Indicator Suite',
                    'Volumetric Order Blocks',
                    'EQH/EQL Liquidity Zones',
                    'Accumulation/Distribution Detection',
                    'Multi-Timeframe Confluence',
                    'Instant Push Notifications',
                    'Priority Support',
                ],
                'is_active'           => true,
                'sort_order'          => 3,
            ],
        ];

        foreach ($packages as $data) {
            Package::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command->info('Packages seeded successfully.');
    }
}
