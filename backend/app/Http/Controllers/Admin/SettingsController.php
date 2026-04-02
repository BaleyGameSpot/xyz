<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show settings page.
     * GET /admin/settings
     */
    public function index(): View
    {
        $settings = [
            // General
            'app_name'          => config('app.name'),
            'support_email'     => env('APP_SUPPORT_EMAIL', ''),
            'telegram_channel'  => env('TELEGRAM_CHANNEL', ''),
            'maintenance_mode'  => (bool) env('MAINTENANCE_MODE', false),

            // Wallet addresses
            'wallet_usdt_trc20' => env('BINANCE_PAYMENT_WALLET_USDT', ''),
            'wallet_usdt_erc20' => env('WALLET_USDT_ERC20', ''),
            'wallet_btc'        => env('BINANCE_PAYMENT_WALLET_BTC', ''),
            'wallet_eth'        => env('WALLET_ETH', ''),
            'wallet_bnb'        => env('WALLET_BNB', ''),

            // Signal settings
            'min_confidence'      => config('trading.signals.min_confidence', 60),
            'max_daily_signals'   => (int) env('MAX_DAILY_SIGNALS', 20),
            'active_pairs'        => env('ACTIVE_PAIRS', ''),
            'cron_schedule'       => env('SIGNAL_CRON_SCHEDULE', '*/15 * * * *'),
            'signals_enabled'     => (bool) env('SIGNALS_ENABLED', true),
            'auto_close_signals'  => (bool) env('AUTO_CLOSE_SIGNALS', false),
            'atr_multiplier'      => config('trading.signals.atr_sl_multiplier'),
            'risk_reward'         => config('trading.signals.risk_reward_ratio'),
            'signal_expiry_hours' => config('trading.signals.expiry_hours'),

            // Notifications
            'firebase_server_key'       => env('FCM_SERVER_KEY', ''),
            'firebase_project_id'       => env('FIREBASE_PROJECT_ID', ''),
            'notify_new_signal'         => (bool) env('NOTIFY_NEW_SIGNAL', true),
            'notify_signal_result'      => (bool) env('NOTIFY_SIGNAL_RESULT', true),
            'notify_payment_verified'   => (bool) env('NOTIFY_PAYMENT_VERIFIED', true),

            // Security
            'jwt_secret_preview' => env('JWT_SECRET') ? substr(env('JWT_SECRET'), 0, 8) . '…' : '(not set)',
            'alpha_vantage_key_set' => ! empty(config('trading.alpha_vantage.api_key')) && config('trading.alpha_vantage.api_key') !== 'demo',
            'twelve_data_key_set'   => ! empty(config('trading.twelve_data.api_key')) && config('trading.twelve_data.api_key') !== 'demo',
            'fcm_key_set'           => ! empty(env('FCM_SERVER_KEY')),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings (writes to .env file).
     * PUT /admin/settings
     */
    public function update(Request $request): RedirectResponse
    {
        $section = $request->input('section', 'signals');

        switch ($section) {

            case 'general':
                $validated = $request->validate([
                    'app_name'         => ['required', 'string', 'max:100'],
                    'support_email'    => ['nullable', 'email'],
                    'telegram_channel' => ['nullable', 'string', 'max:100'],
                    'maintenance_mode' => ['nullable'],
                ]);
                $this->updateEnvFile([
                    'APP_NAME'          => '"' . ($validated['app_name'] ?? 'Chinar Signals') . '"',
                    'APP_SUPPORT_EMAIL' => $validated['support_email'] ?? '',
                    'TELEGRAM_CHANNEL'  => $validated['telegram_channel'] ?? '',
                    'MAINTENANCE_MODE'  => $request->boolean('maintenance_mode') ? 'true' : 'false',
                ]);
                break;

            case 'wallets':
                $validated = $request->validate([
                    'wallet_usdt_trc20' => ['nullable', 'string', 'max:255'],
                    'wallet_usdt_erc20' => ['nullable', 'string', 'max:255'],
                    'wallet_btc'        => ['nullable', 'string', 'max:255'],
                    'wallet_eth'        => ['nullable', 'string', 'max:255'],
                    'wallet_bnb'        => ['nullable', 'string', 'max:255'],
                ]);
                $this->updateEnvFile([
                    'BINANCE_PAYMENT_WALLET_USDT' => $validated['wallet_usdt_trc20'] ?? '',
                    'WALLET_USDT_ERC20'           => $validated['wallet_usdt_erc20'] ?? '',
                    'BINANCE_PAYMENT_WALLET_BTC'  => $validated['wallet_btc'] ?? '',
                    'WALLET_ETH'                  => $validated['wallet_eth'] ?? '',
                    'WALLET_BNB'                  => $validated['wallet_bnb'] ?? '',
                ]);
                break;

            case 'signals':
                $validated = $request->validate([
                    'min_confidence'     => ['required', 'integer', 'min:0', 'max:100'],
                    'max_daily_signals'  => ['required', 'integer', 'min:1', 'max:500'],
                    'active_pairs'       => ['nullable', 'string'],
                    'cron_schedule'      => ['nullable', 'string', 'max:100'],
                    'signals_enabled'    => ['nullable'],
                    'auto_close_signals' => ['nullable'],
                ]);
                $this->updateEnvFile([
                    'SIGNAL_MIN_CONFIDENCE' => $validated['min_confidence'],
                    'MAX_DAILY_SIGNALS'     => $validated['max_daily_signals'],
                    'ACTIVE_PAIRS'          => $validated['active_pairs'] ?? '',
                    'SIGNAL_CRON_SCHEDULE'  => $validated['cron_schedule'] ?? '*/15 * * * *',
                    'SIGNALS_ENABLED'       => $request->boolean('signals_enabled') ? 'true' : 'false',
                    'AUTO_CLOSE_SIGNALS'    => $request->boolean('auto_close_signals') ? 'true' : 'false',
                ]);
                break;

            case 'notifications':
                $validated = $request->validate([
                    'firebase_server_key'     => ['nullable', 'string'],
                    'firebase_project_id'     => ['nullable', 'string', 'max:100'],
                    'notify_new_signal'       => ['nullable'],
                    'notify_signal_result'    => ['nullable'],
                    'notify_payment_verified' => ['nullable'],
                ]);
                $this->updateEnvFile([
                    'FCM_SERVER_KEY'          => $validated['firebase_server_key'] ?? '',
                    'FIREBASE_PROJECT_ID'     => $validated['firebase_project_id'] ?? '',
                    'NOTIFY_NEW_SIGNAL'       => $request->boolean('notify_new_signal') ? 'true' : 'false',
                    'NOTIFY_SIGNAL_RESULT'    => $request->boolean('notify_signal_result') ? 'true' : 'false',
                    'NOTIFY_PAYMENT_VERIFIED' => $request->boolean('notify_payment_verified') ? 'true' : 'false',
                ]);
                break;

            default:
                return back()->with('error', 'Unknown settings section.');
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Change admin password.
     * PUT /admin/settings/change-password
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth('admin')->user();

        if (! $user) {
            return back()->withErrors(['current_password' => 'Not authenticated.']);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }

    /**
     * Send a test FCM notification to the admin.
     * POST /admin/settings/test-notification
     */
    public function testNotification(Request $request, FcmService $fcmService): RedirectResponse
    {
        $user = auth('admin')->user();

        if (empty($user->fcm_token)) {
            return back()->with('error', 'No FCM token found for your account. Please set it via the mobile app.');
        }

        $sent = $fcmService->sendToUser(
            $user,
            'Test Notification',
            'Trading Signals FCM is working correctly!',
            ['type' => 'test']
        );

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'Test notification sent successfully.'
            : 'Failed to send notification. Check FCM server key.'
        );
    }

    /**
     * Regenerate JWT secret (invalidates all tokens).
     * POST /admin/settings/regenerate-token
     */
    public function regenerateToken(Request $request): RedirectResponse
    {
        \Illuminate\Support\Facades\Artisan::call('jwt:secret', ['--force' => true]);

        return back()->with('success', 'JWT secret regenerated. All users have been logged out.');
    }

    /**
     * Update .env file with new values.
     */
    private function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
