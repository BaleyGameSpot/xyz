<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * List all users with filters and pagination.
     * GET /api/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('subscription_type')) {
            $query->where('subscription_type', $request->subscription_type);
        }

        if ($request->boolean('active_only')) {
            $query->where('subscription_type', '!=', 'none')
                ->where('subscription_expiry', '>=', now());
        }

        $sortBy  = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'name', 'email', 'subscription_type', 'subscription_expiry'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $users   = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $users->items(),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Get a single user with full details.
     * GET /api/admin/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with([
            'subscriptions.package',
            'payments.package',
            'activeSubscription.package',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * Block a user.
     * POST /api/admin/users/{id}/block
     */
    public function block(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot block an administrator account.',
            ], 422);
        }

        $user->update(['status' => 'blocked']);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been blocked.",
        ]);
    }

    /**
     * Unblock a user.
     * POST /api/admin/users/{id}/unblock
     */
    public function unblock(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been unblocked.",
        ]);
    }

    /**
     * Assign a subscription to a user manually.
     * POST /api/admin/users/{id}/assign-subscription
     */
    public function assignSubscription(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'package_id'    => ['required', 'integer', Rule::exists('packages', 'id')],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        $user        = User::findOrFail($id);
        $package     = Package::findOrFail($request->package_id);
        $adminUser   = $request->user();
        $duration    = $request->get('duration_days', 30);

        try {
            $subscription = $this->subscriptionService->assignManualSubscription(
                $user,
                $package,
                $adminUser,
                $duration
            );

            return response()->json([
                'success' => true,
                'message' => "Subscription assigned to {$user->name}.",
                'data'    => $subscription->load('package'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a user's details (admin).
     * PUT /api/admin/users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'   => ['sometimes', 'string', 'min:2', 'max:100'],
            'email'  => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'status' => ['sometimes', 'in:active,blocked'],
            'role'   => ['sometimes', 'in:user,admin'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => $user->fresh(),
        ]);
    }

    // ── Web (Blade) specific methods ───────────────────────────────

    /**
     * Toggle user block status (web route).
     * PATCH /admin/users/{user}/toggle-block
     */
    public function toggleBlock(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot block an administrator account.');
        }
        $user->update(['status' => $user->isBlocked() ? 'active' : 'blocked']);
        $action = $user->isBlocked() ? 'blocked' : 'unblocked';
        return back()->with('success', "User {$user->name} has been {$action}.");
    }

    /**
     * Revoke a user's subscription.
     * DELETE /admin/subscriptions/{subscription}/revoke
     */
    public function revokeSubscription(Request $request, \App\Models\UserSubscription $subscription): \Illuminate\Http\RedirectResponse
    {
        $user = $subscription->user;
        $subscription->update(['payment_status' => 'failed', 'admin_note' => 'Revoked by admin']);
        $user->update(['subscription_type' => 'none', 'subscription_expiry' => null]);
        return back()->with('success', "Subscription revoked for {$user->name}.");
    }

    /**
     * Bulk user operations (web route).
     * POST /admin/users/bulk
     */
    public function bulk(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'action'   => ['required', 'in:block,unblock,delete'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
        ]);

        $users  = User::whereIn('id', $request->user_ids)->where('role', '!=', 'admin');
        $count  = $users->count();

        match($request->action) {
            'block'   => $users->update(['status' => 'blocked']),
            'unblock' => $users->update(['status' => 'active']),
            'delete'  => $users->delete(),
        };

        return back()->with('success', "Bulk action '{$request->action}' applied to {$count} user(s).");
    }
}
