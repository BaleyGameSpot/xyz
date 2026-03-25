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
            'app_name'                => config('app.name'),
            'binance_wallet_usdt'     => config('trading.payment.wallets.USDT'),
            'binance_wallet_btc'      => config('trading.payment.wallets.BTC'),
            'min_confidence'          => config('trading.signals.min_confidence'),
            'atr_multiplier'          => config('trading.signals.atr_sl_multiplier'),
            'risk_reward'             => config('trading.signals.risk_reward_ratio'),
            'signal_expiry_hours'     => config('trading.signals.expiry_hours'),
            'alpha_vantage_key_set'   => ! empty(config('trading.alpha_vantage.api_key')) && config('trading.alpha_vantage.api_key') !== 'demo',
            'twelve_data_key_set'     => ! empty(config('trading.twelve_data.api_key')) && config('trading.twelve_data.api_key') !== 'demo',
            'fcm_key_set'             => ! empty(config('trading.fcm.server_key')),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings (writes to .env file).
     * PUT /admin/settings
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_confidence'      => ['required', 'integer', 'min:10', 'max:90'],
            'atr_multiplier'      => ['required', 'numeric', 'min:0.5', 'max:5'],
            'risk_reward'         => ['required', 'numeric', 'min:1', 'max:10'],
            'signal_expiry_hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);

        $this->updateEnvFile([
            'SIGNAL_MIN_CONFIDENCE' => $validated['min_confidence'],
            'ATR_SL_MULTIPLIER'     => $validated['atr_multiplier'],
            'RISK_REWARD_RATIO'     => $validated['risk_reward'],
            'SIGNAL_EXPIRY_HOURS'   => $validated['signal_expiry_hours'],
        ]);

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

        $user = $request->user();

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
        $user = $request->user();

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
        $request->validate([
            'confirm' => ['required', 'in:CONFIRM'],
        ]);

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
