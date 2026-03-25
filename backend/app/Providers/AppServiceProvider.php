<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind services as singletons
        $this->app->singleton(\App\Services\MarketDataService::class);
        $this->app->singleton(\App\Services\IndicatorService::class);
        $this->app->singleton(\App\Services\FcmService::class);
        $this->app->singleton(\App\Services\PaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for API routes.
     */
    private function configureRateLimiting(): void
    {
        // Auth endpoints: 10 attempts per minute
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // General API: 60 requests per minute per user
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

        // Signal analysis: 5 requests per minute
        RateLimiter::for('analyze', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(5)->by('analyze:' . $request->user()->id)
                : Limit::perMinute(1)->by('analyze:' . $request->ip());
        });
    }
}
