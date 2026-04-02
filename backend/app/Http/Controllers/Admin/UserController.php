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
     * GET /api/admin/users  (JSON)
     * GET /admin/users      (Blade view)
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View
    {
        $query = User::with(['activeSubscription.package'])->query();

        // Web blade uses 'search', API uses 'search' too
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Web blade sends 'subscription' filter
        if ($request->filled('subscription')) {
            match($request->subscription) {
                'active'  => $query->where('subscription_type', '!=', 'none')
                                   ->where('subscription_expiry', '>=', now()),
                'expired' => $query->where('subscription_type', '!=', 'none')
                                   ->where('subscription_expiry', '<', now()),
                'none'    => $query->where('subscription_type', 'none'),
                default   => null,
            };
        }

        if ($request->filled('subscription_type')) {
            $query->where('subscription_type', $request->subscription_type);
        }

        if ($request->boolean('active_only')) {
            $query->where('subscription_type', '!=', 'none')
                ->where('subscription_expiry', '>=', now());
        }

        // Web blade sends 'sort'; API sends 'sort_by'/'sort_dir'
        $webSort = $request->get('sort', 'newest');
        match($webSort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name'   => $query->orderBy('name', 'asc'),
            default  => $query->orderBy('created_at', 'desc'),
        };

        $sortBy  = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'name', 'email', 'subscription_type', 'subscription_expiry'];
        if ($request->filled('sort_by') && in_array($sortBy, $allowedSorts)) {
            $query->reorder($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $users   = $query->paginate($perPage)->withQueryString();

        if ($request->expectsJson() || $request->is('api/*')) {
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

        $packages = Package::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.users.index', compact('users', 'packages'));
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
     * POST /api/admin/users/{id}/assign-subscription  (API)
     * POST /admin/users/assign-subscription           (web form — user_id in body)
     */
    public function assignSubscription(Request $request, int $id = 0): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'package_id'    => ['required', 'integer', Rule::exists('packages', 'id')],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'days'          => ['sometimes', 'integer', 'min:1', 'max:365'],
            'user_id'       => ['sometimes', 'integer'],
        ]);

        $userId      = $id ?: (int) $request->input('user_id', 0);
        $user        = User::findOrFail($userId);
        $package     = Package::findOrFail($request->package_id);
        $adminUser   = auth('admin')->user() ?? $request->user();
        $duration    = $request->get('duration_days', $request->get('days', 30));

        $isWeb = ! $request->expectsJson() && ! $request->is('api/*');

        try {
            $subscription = $this->subscriptionService->assignManualSubscription(
                $user,
                $package,
                $adminUser,
                $duration
            );

            if ($isWeb) {
                return back()->with('success', "Subscription assigned to {$user->name}.");
            }

            return response()->json([
                'success' => true,
                'message' => "Subscription assigned to {$user->name}.",
                'data'    => $subscription->load('package'),
            ]);
        } catch (\Exception $e) {
            if ($isWeb) {
                return back()->with('error', $e->getMessage());
            }

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
