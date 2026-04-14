<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\Signal;
use App\Models\TradingPair;
use App\Services\SignalGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateSignals extends Command
{
    protected $signature   = 'signals:generate
                              {--pair= : Specific pair symbol to process}
                              {--timeframe= : Specific timeframe to process}
                              {--force : Skip daily limit check}';
    protected $description = 'Generate trading signals for all active pairs and timeframes';

    public function handle(SignalGenerationService $signalService): int
    {
        $this->info('Starting signal generation...');
        $generated = 0;
        $skipped   = 0;
        $failed    = 0;

        $pairs = TradingPair::active()->get();

        if ($pairFilter = $this->option('pair')) {
            $pairs = $pairs->where('symbol', strtoupper($pairFilter));
        }

        if ($pairs->isEmpty()) {
            $this->warn('No active trading pairs found.');
            return Command::SUCCESS;
        }

        // Collect all unique timeframes across packages
        $packages          = Package::active()->get();
        $allTimeframes     = $packages->flatMap(fn($p) => $p->timeframes)->unique()->values()->toArray();
        $dailyLimitByPkg   = $packages->pluck('daily_signals_limit', 'slug')->toArray();

        if ($tfFilter = $this->option('timeframe')) {
            $allTimeframes = [$tfFilter];
        }

        foreach ($pairs as $pair) {
            foreach ($allTimeframes as $timeframe) {
                // Check daily limit per package for this pair
                if (! $this->option('force') && ! $this->canGenerateSignal($pair, $timeframe, $dailyLimitByPkg)) {
                    $skipped++;
                    continue;
                }

                $this->line("Analyzing {$pair->symbol} / {$timeframe}...");

                try {
                    // Nearest Volumetric OB or FVG → pending limit order
                    $obFvgSignal = $signalService->generateOBFVGSignal($pair, $timeframe);
                    if ($obFvgSignal) {
                        $generated++;
                        $zoneType = $obFvgSignal->reason['zone_type'] ?? 'Zone';
                        $this->info("  ✓ [{$zoneType}] {$obFvgSignal->signal_type} Limit @ {$obFvgSignal->entry_price} (confidence: {$obFvgSignal->confidence_score}%)");
                    } else {
                        $this->line("  → No valid zone found");
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("  ✗ Error: " . $e->getMessage());
                    Log::error("Signal generation command error", [
                        'pair'      => $pair->symbol,
                        'timeframe' => $timeframe,
                        'error'     => $e->getMessage(),
                    ]);
                }

                // Small delay to avoid rate limiting
                usleep(200000); // 200ms
            }
        }

        $this->table(
            ['Generated', 'Skipped', 'Failed'],
            [[$generated, $skipped, $failed]]
        );

        $this->info('Signal generation complete.');
        return Command::SUCCESS;
    }

    /**
     * Check if we can generate another signal for this pair/timeframe.
     * Respects daily limits per package.
     */
    private function canGenerateSignal(TradingPair $pair, string $timeframe, array $dailyLimits): bool
    {
        // Find the most restrictive daily limit for this pair's accessible packages
        $accessiblePackages = $pair->package_access ?? [];

        if (empty($accessiblePackages)) {
            return false;
        }

        // Get the maximum daily limit allowed for this pair
        $maxLimit = 0;
        foreach ($accessiblePackages as $slug) {
            $limit    = $dailyLimits[$slug] ?? 0;
            $maxLimit = max($maxLimit, $limit);
        }

        if ($maxLimit === 0) {
            return false;
        }

        // Count today's signals for this pair and timeframe
        $todayCount = Signal::where('trading_pair_id', $pair->id)
            ->where('timeframe', $timeframe)
            ->whereDate('created_at', today())
            ->count();

        return $todayCount < $maxLimit;
    }
}
