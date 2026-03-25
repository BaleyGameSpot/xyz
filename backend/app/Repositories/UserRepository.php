<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    /**
     * Get paginated list of users with optional filters.
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['subscription_type'])) {
            $query->where('subscription_type', $filters['subscription_type']);
        }

        if (! empty($filters['active_only'])) {
            $query->where('subscription_type', '!=', 'none')
                ->where('subscription_expiry', '>=', now());
        }

        $sortBy  = in_array($filters['sort_by'] ?? '', ['created_at', 'name', 'email', 'subscription_type'])
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * Get users with active subscriptions of a specific type.
     */
    public function getActiveSubscribers(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return User::active()
            ->where('subscription_type', $type)
            ->where('subscription_expiry', '>=', now())
            ->get();
    }

    /**
     * Get users who should receive signal notifications for a specific package access level.
     */
    public function getSubscribersForPackage(string $packageSlug): \Illuminate\Database\Eloquent\Collection
    {
        return User::active()
            ->where('subscription_type', $packageSlug)
            ->where('subscription_expiry', '>=', now())
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get(['id', 'fcm_token']);
    }

    /**
     * Get dashboard user statistics.
     */
    public function getDashboardStats(): array
    {
        return [
            'total'   => User::count(),
            'active'  => User::active()->count(),
            'blocked' => User::where('status', 'blocked')->count(),
            'subscribed' => User::where('subscription_type', '!=', 'none')
                ->where('subscription_expiry', '>=', now())
                ->count(),
            'new_today' => User::whereDate('created_at', today())->count(),
            'new_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'by_subscription' => User::where('subscription_type', '!=', 'none')
                ->where('subscription_expiry', '>=', now())
                ->selectRaw('subscription_type, COUNT(*) as count')
                ->groupBy('subscription_type')
                ->pluck('count', 'subscription_type')
                ->toArray(),
        ];
    }
}
