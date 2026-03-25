<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule here using the new Laravel 11 style (alternative to Kernel.php)
// These are also defined in Console/Kernel.php for compatibility
Schedule::command('signals:generate')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground();

Schedule::command('signals:update-results')
    ->everyFifteenMinutes()
    ->withoutOverlapping(5)
    ->runInBackground();
