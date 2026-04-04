<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PairController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SignalController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes — Chinar Signals Admin Panel
|--------------------------------------------------------------------------
|
| All REST API routes are in routes/api.php.
| This file contains only the Blade admin panel routes.
|
*/

// Root redirect to admin login
Route::get('/', fn () => redirect()->route('admin.login'));

// ── Admin Panel ──────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Guest-only (redirects to dashboard if already authenticated)
    Route::middleware('admin.guest')->group(function () {
        Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    });

    // Authenticated admin routes
    Route::middleware(['admin.auth'])->group(function () {

        // Logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // ── Dashboard ────────────────────────────────────────────
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ── Users ────────────────────────────────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',                         [UserController::class, 'index'])             ->name('index');
            Route::get('/{user}',                   [UserController::class, 'show'])              ->name('show');
            Route::patch('/{user}/toggle-block',    [UserController::class, 'toggleBlock'])       ->name('toggle-block');
            Route::post('/assign-subscription',     [UserController::class, 'assignSubscription'])->name('assign-subscription');
            Route::post('/bulk',                    [UserController::class, 'bulk'])              ->name('bulk');
        });

        // Subscription revoke
        Route::delete(
            '/subscriptions/{subscription}/revoke',
            [UserController::class, 'revokeSubscription']
        )->name('subscriptions.revoke');

        // ── Trading Pairs ─────────────────────────────────────────
        Route::prefix('pairs')->name('pairs.')->group(function () {
            Route::get('/',                   [PairController::class, 'index'])  ->name('index');
            Route::post('/',                  [PairController::class, 'store'])  ->name('store');
            Route::put('/{pair}',             [PairController::class, 'update']) ->name('update');
            Route::delete('/{pair}',          [PairController::class, 'destroy'])->name('destroy');
            Route::patch('/{pair}/toggle',    [PairController::class, 'toggle']) ->name('toggle');
        });

        // ── Signals ──────────────────────────────────────────────
        Route::prefix('signals')->name('signals.')->group(function () {
            Route::get('/',                [SignalController::class, 'index'])->name('index');
            Route::get('/{signal}',        [SignalController::class, 'show']) ->name('show');
            Route::patch('/{signal}/mark', [SignalController::class, 'mark'])->name('mark');
            Route::post('/bulk',           [SignalController::class, 'bulk'])->name('bulk');
        });

        // ── Packages ─────────────────────────────────────────────
        Route::prefix('packages')->name('packages.')->group(function () {
            Route::get('/',                   [PackageController::class, 'index'])  ->name('index');
            Route::get('/create',             [PackageController::class, 'create']) ->name('create');
            Route::post('/',                  [PackageController::class, 'store'])  ->name('store');
            Route::get('/{package}/edit',     [PackageController::class, 'edit'])   ->name('edit');
            Route::put('/{package}',          [PackageController::class, 'update']) ->name('update');
            Route::delete('/{package}',       [PackageController::class, 'destroy'])->name('destroy');
            Route::patch('/{package}/toggle', [PackageController::class, 'toggle']) ->name('toggle');
        });

        // ── Payments ─────────────────────────────────────────────
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/',                   [PaymentController::class, 'index'])  ->name('index');
            Route::patch('/{payment}/verify', [PaymentController::class, 'verify']) ->name('verify');
            Route::patch('/{payment}/reject', [PaymentController::class, 'reject']) ->name('reject');
        });

        // ── Settings ─────────────────────────────────────────────
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::put('/',                   [SettingsController::class, 'update'])           ->name('update');
            Route::put('/change-password',    [SettingsController::class, 'changePassword'])  ->name('change-password');
            Route::post('/regenerate-token',  [SettingsController::class, 'regenerateToken']) ->name('regenerate-token');
            Route::post('/test-notification', [SettingsController::class, 'testNotification'])->name('test-notification');
        });

    }); // end auth:admin middleware

}); // end admin prefix
