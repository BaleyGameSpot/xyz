<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Services\SignalGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateSignalResults extends Command
{
    protected $signature   = 'signals:update-results
                              {--signal-id= : Update a specific signal by ID}';
    protected $description = 'Update active signals with current win/loss/expired status';

    public function handle(SignalGenerationService $signalService): int
    {
        $this->info('Updating signal results...');

        $query = Signal::where('status', 'active')->with('tradingPair');

        if ($signalId = $this->option('signal-id')) {
            $query->where('id', $signalId);
        }

        $signals   = $query->get();
        $updated   = 0;
        $unchanged = 0;
        $failed    = 0;

        if ($signals->isEmpty()) {
            $this->info('No active signals to update.');
            return Command::SUCCESS;
        }

        $this->info("Found {$signals->count()} active signals to check.");

        foreach ($signals as $signal) {
            try {
                $wasUpdated = $signalService->updateSignalResult($signal);
                if ($wasUpdated) {
                    $updated++;
                    $signal->refresh();
                    $this->line("  Signal #{$signal->id} {$signal->tradingPair->symbol}/{$signal->timeframe}: {$signal->status}");
                } else {
                    $unchanged++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  Error updating signal #{$signal->id}: " . $e->getMessage());
                Log::error("Signal update error", [
                    'signal_id' => $signal->id,
                    'error'     => $e->getMessage(),
                ]);
            }

            // Small delay between price fetches
            usleep(100000); // 100ms
        }

        $this->table(
            ['Updated', 'Unchanged', 'Failed'],
            [[$updated, $unchanged, $failed]]
        );

        $this->info('Signal result update complete.');
        return Command::SUCCESS;
    }
}
