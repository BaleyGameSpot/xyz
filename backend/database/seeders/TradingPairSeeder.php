<?php

namespace Database\Seeders;

use App\Models\TradingPair;
use Illuminate\Database\Seeder;

class TradingPairSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            // ---- FOREX PAIRS ----
            [
                'symbol'         => 'EURUSD',
                'name'           => 'Euro / US Dollar',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['basic', 'best', 'premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'GBPUSD',
                'name'           => 'British Pound / US Dollar',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['best', 'premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'USDJPY',
                'name'           => 'US Dollar / Japanese Yen',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['best', 'premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],
            [
                'symbol'         => 'AUDUSD',
                'name'           => 'Australian Dollar / US Dollar',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'USDCHF',
                'name'           => 'US Dollar / Swiss Franc',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'USDCAD',
                'name'           => 'US Dollar / Canadian Dollar',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'NZDUSD',
                'name'           => 'New Zealand Dollar / US Dollar',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'EURGBP',
                'name'           => 'Euro / British Pound',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'EURJPY',
                'name'           => 'Euro / Japanese Yen',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],
            [
                'symbol'         => 'GBPJPY',
                'name'           => 'British Pound / Japanese Yen',
                'type'           => 'forex',
                'exchange'       => 'forex',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],

            // ---- CRYPTO PAIRS ----
            [
                'symbol'         => 'BTCUSDT',
                'name'           => 'Bitcoin / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['basic', 'best', 'premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],
            [
                'symbol'         => 'ETHUSDT',
                'name'           => 'Ethereum / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['best', 'premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],
            [
                'symbol'         => 'BNBUSDT',
                'name'           => 'BNB / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],
            [
                'symbol'         => 'XRPUSDT',
                'name'           => 'Ripple / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'ADAUSDT',
                'name'           => 'Cardano / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'SOLUSDT',
                'name'           => 'Solana / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],
            [
                'symbol'         => 'DOGEUSDT',
                'name'           => 'Dogecoin / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.00001,
            ],
            [
                'symbol'         => 'MATICUSDT',
                'name'           => 'Polygon / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.0001,
            ],
            [
                'symbol'         => 'LTCUSDT',
                'name'           => 'Litecoin / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.01,
            ],
            [
                'symbol'         => 'DOTUSDT',
                'name'           => 'Polkadot / Tether',
                'type'           => 'crypto',
                'exchange'       => 'binance',
                'package_access' => ['premium'],
                'is_active'      => true,
                'pip_size'       => 0.001,
            ],
        ];

        foreach ($pairs as $data) {
            TradingPair::updateOrCreate(['symbol' => $data['symbol']], $data);
        }

        $this->command->info('Trading pairs seeded successfully (' . count($pairs) . ' pairs).');
    }
}
