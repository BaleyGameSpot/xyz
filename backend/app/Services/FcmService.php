<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private Client $httpClient;
    private string $serverKey;
    private string $fcmEndpoint = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 10]);
        $this->serverKey  = config('trading.fcm.server_key', '');
    }

    /**
     * Send a signal notification to all subscribed users.
     *
     * @param  Signal $signal
     * @return array  ['sent' => int, 'failed' => int]
     */
    public function sendSignalNotification(Signal $signal): array
    {
        $signal->load('tradingPair');
        $pair      = $signal->tradingPair;
        $direction = $signal->signal_type;
        $emoji     = $direction === 'BUY' ? '🟢' : '🔴';

        $title = "{$emoji} {$direction} Signal - {$pair->symbol}";
        $body  = sprintf(
            "%s | Timeframe: %s | Entry: %s | SL: %s | TP: %s | Confidence: %d%%",
            $pair->symbol,
            $signal->timeframe,
            number_format((float) $signal->entry_price, 5),
            number_format((float) $signal->stop_loss, 5),
            number_format((float) $signal->take_profit, 5),
            $signal->confidence_score
        );

        $data = [
            'signal_id'        => (string) $signal->id,
            'symbol'           => $pair->symbol,
            'signal_type'      => $signal->signal_type,
            'timeframe'        => $signal->timeframe,
            'entry_price'      => (string) $signal->entry_price,
            'stop_loss'        => (string) $signal->stop_loss,
            'take_profit'      => (string) $signal->take_profit,
            'confidence_score' => (string) $signal->confidence_score,
            'type'             => 'new_signal',
        ];

        // Get tokens of users who should receive this signal
        $tokens = $this->getTargetTokens($signal);

        if (empty($tokens)) {
            Log::debug("No FCM tokens for signal #{$signal->id}");
            return ['sent' => 0, 'failed' => 0];
        }

        $sent   = 0;
        $failed = 0;

        // Send in batches of 500 (FCM limit)
        $batches = array_chunk($tokens, 500);
        foreach ($batches as $batch) {
            $result = $this->sendToTokens($batch, $title, $body, $data);
            $sent  += $result['sent'];
            $failed += $result['failed'];
        }

        // Mark signal as notification sent
        $signal->update(['notification_sent' => true]);

        Log::info("FCM notifications sent for signal #{$signal->id}", [
            'sent'   => $sent,
            'failed' => $failed,
        ]);

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Send a notification to a single user's FCM token.
     *
     * @param  User   $user
     * @param  string $title
     * @param  string $body
     * @param  array  $data
     * @return bool
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (empty($user->fcm_token)) {
            return false;
        }

        $result = $this->sendToTokens([$user->fcm_token], $title, $body, $data);
        return $result['sent'] > 0;
    }

    /**
     * Send FCM message to an array of device tokens.
     */
    private function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($this->serverKey)) {
            Log::warning('FCM server key not configured');
            return ['sent' => 0, 'failed' => count($tokens)];
        }

        $payload = [
            'registration_ids' => $tokens,
            'notification'     => [
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
                'badge' => '1',
            ],
            'data'     => $data,
            'priority' => 'high',
        ];

        try {
            $response = $this->httpClient->post($this->fcmEndpoint, [
                'headers' => [
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $result    = json_decode($response->getBody()->getContents(), true);
            $success   = $result['success'] ?? 0;
            $failCount = $result['failure'] ?? 0;

            // Handle invalid tokens in results
            if (! empty($result['results'])) {
                $invalidTokens = [];
                foreach ($result['results'] as $idx => $res) {
                    if (isset($res['error']) && in_array($res['error'], [
                        'InvalidRegistration',
                        'NotRegistered',
                    ])) {
                        $invalidTokens[] = $tokens[$idx];
                    }
                }
                if (! empty($invalidTokens)) {
                    $this->removeInvalidTokens($invalidTokens);
                }
            }

            return ['sent' => (int) $success, 'failed' => (int) $failCount];
        } catch (RequestException $e) {
            Log::error('FCM send failed', ['error' => $e->getMessage()]);
            return ['sent' => 0, 'failed' => count($tokens)];
        }
    }

    /**
     * Get FCM tokens for users who should receive a signal notification.
     * Filters by: active subscription, package has access to this pair, has FCM token.
     */
    private function getTargetTokens(Signal $signal): array
    {
        $pair = $signal->tradingPair;
        $allowedPackages = $pair->package_access ?? [];

        $tokens = User::active()
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->whereIn('subscription_type', $allowedPackages)
            ->where('subscription_expiry', '>', now())
            ->pluck('fcm_token')
            ->toArray();

        return array_unique(array_filter($tokens));
    }

    /**
     * Remove invalid/expired FCM tokens from users.
     */
    private function removeInvalidTokens(array $tokens): void
    {
        User::whereIn('fcm_token', $tokens)->update(['fcm_token' => null]);
        Log::info('Removed invalid FCM tokens', ['count' => count($tokens)]);
    }
}
