<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Get payment history for a user.
     */
    public function getUserPayments(User $user, int $perPage = 10)
    {
        return Payment::where('user_id', $user->id)
            ->with('package')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all pending payments needing admin review.
     */
    public function getPendingPayments(int $perPage = 20)
    {
        return Payment::where('status', 'pending')
            ->whereNotNull('tx_hash')
            ->with(['user', 'package'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get payment statistics for admin dashboard.
     */
    public function getPaymentStats(): array
    {
        return [
            'total_revenue'        => Payment::where('status', 'confirmed')->sum('amount'),
            'pending_count'        => Payment::where('status', 'pending')->whereNotNull('tx_hash')->count(),
            'confirmed_this_month' => Payment::where('status', 'confirmed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'by_currency' => Payment::where('status', 'confirmed')
                ->selectRaw('currency, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('currency')
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Expire payments that are past their payment window.
     */
    public function expireStalePayments(): int
    {
        $count = Payment::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->whereNull('tx_hash') // Only expire ones without tx_hash (no action from user)
            ->update(['status' => 'expired']);

        if ($count > 0) {
            Log::info("Expired {$count} stale payments");
        }

        return $count;
    }
}
