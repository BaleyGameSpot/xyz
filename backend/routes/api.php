<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PairController;
use App\Http\Controllers\API\SignalController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\SignalController as AdminSignalController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Webhook\TradingViewWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TradingView Webhook (no JWT — authenticated by shared secret in payload)
|--------------------------------------------------------------------------
*/
Route::post('/webhook/tradingview', [TradingViewWebhookController::class, 'handle'])
    ->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Public Routes (no auth required)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware(['throttle:auth'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/google',   [AuthController::class, 'googleLogin']);
});

// Package listing is public
Route::get('/packages', [SubscriptionController::class, 'packages']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['jwt'])->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::get('/me',       [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // User
    Route::prefix('user')->group(function () {
        Route::get('/profile',    [UserController::class, 'profile']);
        Route::put('/profile',    [UserController::class, 'update']);
        Route::put('/fcm-token',  [UserController::class, 'updateFcmToken']);
    });

    // Trading Pairs
    Route::prefix('pairs')->group(function () {
        Route::get('/',          [PairController::class, 'index']);
        Route::get('/{symbol}',  [PairController::class, 'show']);
    });

    // Signals
    Route::prefix('signals')->middleware(['throttle:api'])->group(function () {
        Route::get('/',             [SignalController::class, 'index']);
        Route::get('/today-stats',  [SignalController::class, 'todayStats']);
        Route::get('/{id}',         [SignalController::class, 'show']);
        Route::post('/analyze',     [SignalController::class, 'analyze'])->middleware('throttle:analyze');
    });

    // Subscriptions
    Route::prefix('subscription')->group(function () {
        Route::get('/status',         [SubscriptionController::class, 'status']);
        Route::post('/purchase',      [SubscriptionController::class, 'purchase']);
        Route::post('/verify-payment',[SubscriptionController::class, 'verifyPayment']);
        Route::get('/payments',       [SubscriptionController::class, 'paymentHistory']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware(['admin'])->group(function () {

        // Dashboard
        Route::get('/dashboard',           [DashboardController::class, 'index']);
        Route::get('/dashboard/win-rate',  [DashboardController::class, 'winRateBreakdown']);

        // User Management
        Route::prefix('users')->group(function () {
            Route::get('/',                         [AdminUserController::class, 'index']);
            Route::get('/{id}',                     [AdminUserController::class, 'show']);
            Route::put('/{id}',                     [AdminUserController::class, 'update']);
            Route::post('/{id}/block',              [AdminUserController::class, 'block']);
            Route::post('/{id}/unblock',            [AdminUserController::class, 'unblock']);
            Route::post('/{id}/assign-subscription',[AdminUserController::class, 'assignSubscription']);
        });

        // Signal Management
        Route::prefix('signals')->group(function () {
            Route::get('/',         [AdminSignalController::class, 'index']);
            Route::get('/stats',    [AdminSignalController::class, 'stats']);
            Route::get('/{id}',     [AdminSignalController::class, 'show']);
            Route::post('/{id}/mark', [AdminSignalController::class, 'mark']);
            Route::delete('/{id}',  [AdminSignalController::class, 'destroy']);
        });

        // Package Management
        Route::prefix('packages')->group(function () {
            Route::get('/',           [PackageController::class, 'index']);
            Route::post('/',          [PackageController::class, 'store']);
            Route::get('/pending-payments',  [PackageController::class, 'pendingPayments']);
            Route::get('/{id}',       [PackageController::class, 'show']);
            Route::put('/{id}',       [PackageController::class, 'update']);
            Route::post('/{id}/toggle', [PackageController::class, 'toggle']);
            Route::delete('/{id}',    [PackageController::class, 'destroy']);
        });

        // Payment Management
        Route::prefix('payments')->group(function () {
            Route::post('/{id}/confirm', [PackageController::class, 'confirmPayment']);
            Route::post('/{id}/reject',  [PackageController::class, 'rejectPayment']);
        });
    });
});
