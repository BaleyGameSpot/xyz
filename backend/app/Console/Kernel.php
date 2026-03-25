<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate signals every 5 minutes
        $schedule->command('signals:generate')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/signal-generation.log'));

        // Update signal results every 15 minutes
        $schedule->command('signals:update-results')
            ->everyFifteenMinutes()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/signal-results.log'));

        // Expire stale payments daily
        $schedule->call(function () {
            app(\App\Services\PaymentService::class)->expireStalePayments();
        })->daily();

        // Process queued jobs
        $schedule->command('queue:work --max-jobs=100 --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping(3)
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
