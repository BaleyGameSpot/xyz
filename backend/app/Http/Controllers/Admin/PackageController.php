<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    /**
     * List all packages.
     * GET /api/admin/packages
     */
    public function index(): JsonResponse
    {
        $packages = Package::withCount('subscriptions')->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data'    => $packages,
        ]);
    }

    /**
     * Get a single package.
     * GET /api/admin/packages/{id}
     */
    public function show(int $id): JsonResponse
    {
        $package = Package::withCount(['subscriptions', 'payments'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $package,
        ]);
    }

    /**
     * Create a new package.
     * POST /api/admin/packages
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'slug'                => ['required', 'string', 'max:50', 'unique:packages,slug', 'alpha_dash'],
            'price'               => ['required', 'numeric', 'min:0'],
            'promo_price'         => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'pairs_limit'         => ['nullable', 'integer', 'min:1'],
            'daily_signals_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'timeframes'          => ['required', 'array', 'min:1'],
            'timeframes.*'        => ['required', 'string', Rule::in(array_keys(config('trading.timeframes', [])))],
            'description'         => ['nullable', 'string', 'max:500'],
            'features'            => ['nullable', 'array'],
            'features.*'          => ['string', 'max:200'],
            'is_active'           => ['boolean'],
            'sort_order'          => ['integer', 'min:0'],
        ]);

        $package = Package::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully.',
            'data'    => $package,
        ], 201);
    }

    /**
     * Update a package.
     * PUT /api/admin/packages/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $package = Package::findOrFail($id);

        $validated = $request->validate([
            'name'                => ['sometimes', 'string', 'max:100'],
            'slug'                => ['sometimes', 'string', 'max:50', Rule::unique('packages', 'slug')->ignore($package->id), 'alpha_dash'],
            'price'               => ['sometimes', 'numeric', 'min:0'],
            'promo_price'         => ['nullable', 'numeric', 'min:0'],
            'pairs_limit'         => ['nullable', 'integer', 'min:1'],
            'daily_signals_limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'timeframes'          => ['sometimes', 'array', 'min:1'],
            'timeframes.*'        => ['string', Rule::in(array_keys(config('trading.timeframes', [])))],
            'description'         => ['nullable', 'string', 'max:500'],
            'features'            => ['nullable', 'array'],
            'is_active'           => ['boolean'],
            'sort_order'          => ['integer', 'min:0'],
        ]);

        $package->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully.',
            'data'    => $package->fresh(),
        ]);
    }

    /**
     * Toggle package active status.
     * POST /api/admin/packages/{id}/toggle
     */
    public function toggle(int $id): JsonResponse
    {
        $package    = Package::findOrFail($id);
        $newStatus  = ! $package->is_active;
        $package->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Package is now " . ($newStatus ? 'active' : 'inactive') . ".",
            'data'    => ['is_active' => $newStatus],
        ]);
    }

    /**
     * Delete a package (only if no active subscriptions).
     * DELETE /api/admin/packages/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $package = Package::findOrFail($id);

        $activeSubscriptions = $package->subscriptions()
            ->where('payment_status', 'confirmed')
            ->where('end_date', '>=', now()->toDateString())
            ->count();

        if ($activeSubscriptions > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete package with {$activeSubscriptions} active subscription(s). Deactivate it instead.",
            ], 422);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully.',
        ]);
    }

    /**
     * List pending payments awaiting admin review.
     * GET /api/admin/packages/pending-payments
     */
    public function pendingPayments(Request $request): JsonResponse
    {
        $perPage  = min((int) $request->get('per_page', 20), 100);
        $payments = \App\Models\Payment::with(['user', 'package'])
            ->where('status', 'pending')
            ->whereNotNull('tx_hash')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $payments->items(),
            'meta'    => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }

    /**
     * Confirm a payment.
     * POST /api/admin/payments/{id}/confirm
     */
    public function confirmPayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = \App\Models\Payment::with('subscription.package')->findOrFail($id);

        if (! $payment->isPending()) {
            return response()->json([
                'success' => false,
                'message' => "Payment is already {$payment->status}.",
            ], 422);
        }

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $subscription = $subscriptionService->confirmPayment($payment, $request->user(), $request->note ?? '');

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed and subscription activated.',
            'data'    => $subscription,
        ]);
    }

    /**
     * Reject a payment.
     * POST /api/admin/payments/{id}/reject
     */
    public function rejectPayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $payment = \App\Models\Payment::findOrFail($id);

        if (! $payment->isPending()) {
            return response()->json([
                'success' => false,
                'message' => "Payment is already {$payment->status}.",
            ], 422);
        }

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $subscriptionService->rejectPayment($payment, $request->user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Payment rejected.',
        ]);
    }

    // ── Web (Blade) specific methods ──────────────────────────────

    /**
     * Show package create form (web route).
     * GET /admin/packages/create
     */
    public function create(): \Illuminate\View\View
    {
        $timeframes = array_keys(config('trading.timeframes', []));
        return view('admin.packages.create', compact('timeframes'));
    }

    /**
     * Show package edit form (web route).
     * GET /admin/packages/{package}/edit
     */
    public function edit(Package $package): \Illuminate\View\View
    {
        $timeframes = array_keys(config('trading.timeframes', []));
        return view('admin.packages.edit', compact('package', 'timeframes'));
    }
}
