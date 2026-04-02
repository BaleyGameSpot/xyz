<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Initiate a subscription purchase.
     * Creates a pending payment and subscription record.
     *
     * @param  User    $user
     * @param  Package $package
     * @param  string  $currency USDT or BTC
     * @return array   ['payment' => Payment, 'subscription' => UserSubscription, 'wallet' => string]
     */
    public function initiatePurchase(User $user, Package $package, string $currency = 'USDT'): array
    {
        $wallets = config('trading.payment.wallets', []);
        $wallet  = $wallets[$currency] ?? null;

        if (empty($wallet)) {
            throw new \RuntimeException("Payment wallet not configured for {$currency}");
        }

        return DB::transaction(function () use ($user, $package, $currency, $wallet) {
            // Expire any previous pending payments for this user/package
            Payment::where('user_id', $user->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            // Create subscription record (pending)
            $startDate = now()->toDateString();
            $endDate   = now()->addMonth()->toDateString();

            $subscription = UserSubscription::create([
                'user_id'        => $user->id,
                'package_id'     => $package->id,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'payment_method' => 'crypto_' . strtolower($currency),
                'payment_status' => 'pending',
                'amount'         => $package->getEffectivePrice(),
                'currency'       => $currency,
            ]);

            // Create payment record
            $payment = Payment::create([
                'user_id'        => $user->id,
                'package_id'     => $package->id,
                'subscription_id' => $subscription->id,
                'amount'         => $package->getEffectivePrice(),
                'currency'       => $currency,
                'wallet_address' => $wallet,
                'status'         => 'pending',
                'expires_at'     => now()->addHours(24), // 24-hour payment window
            ]);

            Log::info('Subscription purchase initiated', [
                'user_id'         => $user->id,
                'package_id'      => $package->id,
                'payment_id'      => $payment->id,
                'subscription_id' => $subscription->id,
                'amount'          => $payment->amount,
                'currency'        => $currency,
            ]);

            return [
                'payment'      => $payment,
                'subscription' => $subscription,
                'wallet'       => $wallet,
            ];
        });
    }

    /**
     * Submit a transaction hash for payment verification.
     * Marks payment as pending admin review.
     *
     * @param  Payment $payment
     * @param  string  $txHash
     * @return Payment
     */
    public function submitTxHash(Payment $payment, string $txHash): Payment
    {
        if (! $payment->isPending()) {
            throw new \RuntimeException('Payment is not in pending status');
        }

        if ($payment->isExpired()) {
            throw new \RuntimeException('Payment window has expired');
        }

        // Check if tx_hash already used
        $existing = Payment::where('tx_hash', $txHash)
            ->where('id', '!=', $payment->id)
            ->first();

        if ($existing) {
            throw new \RuntimeException('Transaction hash already used');
        }

        $payment->update(['tx_hash' => $txHash]);

        Log::info('TX hash submitted for payment', [
            'payment_id' => $payment->id,
            'tx_hash'    => $txHash,
        ]);

        return $payment->fresh();
    }

    /**
     * Confirm a payment (admin action).
     * Activates the subscription and updates user subscription type.
     *
     * @param  Payment $payment
     * @param  User    $adminUser
     * @param  string  $note
     * @return UserSubscription
     */
    public function confirmPayment(Payment $payment, User $adminUser, string $note = ''): UserSubscription
    {
        return DB::transaction(function () use ($payment, $adminUser, $note) {
            // Update payment
            $payment->update(['status' => 'confirmed']);

            // Update subscription
            $subscription = $payment->subscription;
            $subscription->update([
                'payment_status' => 'confirmed',
                'confirmed_by'   => $adminUser->id,
                'confirmed_at'   => now(),
                'admin_note'     => $note,
            ]);

            // Update user subscription type and expiry
            $packageSlug = $subscription->package->slug;
            $subscription->user->update([
                'subscription_type'   => $packageSlug,
                'subscription_expiry' => $subscription->end_date->endOfDay(),
            ]);

            Log::info('Payment confirmed by admin', [
                'payment_id'      => $payment->id,
                'subscription_id' => $subscription->id,
                'user_id'         => $subscription->user_id,
                'admin_id'        => $adminUser->id,
                'package'         => $packageSlug,
            ]);

            return $subscription->fresh(['user', 'package']);
        });
    }

    /**
     * Reject a payment (admin action).
     *
     * @param  Payment $payment
     * @param  User    $adminUser
     * @param  string  $reason
     * @return Payment
     */
    public function rejectPayment(Payment $payment, User $adminUser, string $reason): Payment
    {
        DB::transaction(function () use ($payment, $adminUser, $reason) {
            $payment->update([
                'status'         => 'failed',
                'failure_reason' => $reason,
            ]);

            if ($payment->subscription) {
                $payment->subscription->update([
                    'payment_status' => 'failed',
                    'admin_note'     => $reason,
                    'confirmed_by'   => $adminUser->id,
                    'confirmed_at'   => now(),
                ]);
            }

            Log::info('Payment rejected by admin', [
                'payment_id' => $payment->id,
                'admin_id'   => $adminUser->id,
                'reason'     => $reason,
            ]);
        });

        return $payment->fresh();
    }

    /**
     * Manually assign a subscription to a user (admin action).
     *
     * @param  User    $user
     * @param  Package $package
     * @param  User    $adminUser
     * @param  int     $durationDays
     * @return UserSubscription
     */
    public function assignManualSubscription(
        User             $user,
        Package          $package,
        Authenticatable  $adminUser,
        int              $durationDays = 30
    ): UserSubscription {
        return DB::transaction(function () use ($user, $package, $adminUser, $durationDays) {
            $subscription = UserSubscription::create([
                'user_id'        => $user->id,
                'package_id'     => $package->id,
                'start_date'     => now()->toDateString(),
                'end_date'       => now()->addDays($durationDays)->toDateString(),
                'payment_method' => 'manual',
                'payment_status' => 'confirmed',
                'amount'         => 0,
                'currency'       => 'USDT',
                'confirmed_by'   => $adminUser->id,
                'confirmed_at'   => now(),
                'admin_note'     => 'Manually assigned by admin',
            ]);

            $user->update([
                'subscription_type'   => $package->slug,
                'subscription_expiry' => now()->addDays($durationDays)->endOfDay(),
            ]);

            Log::info('Manual subscription assigned', [
                'user_id'    => $user->id,
                'package_id' => $package->id,
                'admin_id'   => $adminUser->id,
                'days'       => $durationDays,
            ]);

            return $subscription;
        });
    }

    /**
     * Get the current active subscription status for a user.
     */
    public function getSubscriptionStatus(User $user): array
    {
        if (! $user->hasActiveSubscription()) {
            return [
                'active'      => false,
                'type'        => 'none',
                'expiry'      => null,
                'days_left'   => 0,
                'package'     => null,
                'pending'     => $this->hasPendingPayment($user),
            ];
        }

        $activeSubscription = $user->activeSubscription()->with('package')->first();

        return [
            'active'      => true,
            'type'        => $user->subscription_type,
            'expiry'      => $user->subscription_expiry?->toISOString(),
            'days_left'   => now()->diffInDays($user->subscription_expiry),
            'package'     => $activeSubscription?->package,
            'pending'     => false,
        ];
    }

    /**
     * Check if user has a pending payment.
     */
    public function hasPendingPayment(User $user): bool
    {
        return Payment::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get pending payment for user if exists.
     */
    public function getPendingPayment(User $user): ?Payment
    {
        return Payment::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('package')
            ->latest()
            ->first();
    }
}
