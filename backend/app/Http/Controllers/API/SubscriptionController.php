<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Models\Package;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Get all active packages.
     * GET /api/packages
     */
    public function packages(): JsonResponse
    {
        $packages = Package::active()->get()->map(function ($pkg) {
            return [
                'id'                  => $pkg->id,
                'name'                => $pkg->name,
                'slug'                => $pkg->slug,
                'price'               => $pkg->price,
                'promo_price'         => $pkg->promo_price,
                'effective_price'     => $pkg->getEffectivePrice(),
                'pairs_limit'         => $pkg->pairs_limit,
                'pairs_unlimited'     => $pkg->hasUnlimitedPairs(),
                'daily_signals_limit' => $pkg->daily_signals_limit,
                'timeframes'          => $pkg->timeframes,
                'description'         => $pkg->description,
                'features'            => $pkg->features ?? [],
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $packages,
        ]);
    }

    /**
     * Initiate a subscription purchase.
     * POST /api/subscription/purchase
     */
    public function purchase(SubscriptionRequest $request): JsonResponse
    {
        $user    = $request->user();
        $package = Package::findOrFail($request->package_id);

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription. It will be renewed after expiry.',
                'data'    => [
                    'current_subscription' => $user->subscription_type,
                    'expiry'               => $user->subscription_expiry,
                ],
            ], 422);
        }

        // Check for pending payment
        if ($this->subscriptionService->hasPendingPayment($user)) {
            $pending = $this->subscriptionService->getPendingPayment($user);
            return response()->json([
                'success' => false,
                'message' => 'You have a pending payment. Please complete it or contact support.',
                'data'    => [
                    'pending_payment' => [
                        'id'          => $pending->id,
                        'amount'      => $pending->amount,
                        'crypto_type' => $pending->crypto_type,
                        'package'     => $pending->package->name,
                    ],
                ],
            ], 422);
        }

        try {
            $result = $this->subscriptionService->initiatePurchase($user, $package, $request->currency);

            $cryptoType = $result['payment']->crypto_type;
            $amount     = $result['payment']->amount;

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated. Please send the exact amount to the wallet address.',
                'data'    => [
                    'payment_id'     => $result['payment']->id,
                    'amount'         => $amount,
                    'currency'       => $cryptoType,
                    'wallet_address' => $result['wallet'],
                    'package'        => $package->name,
                    'instructions'   => [
                        'step_1' => "Send exactly {$amount} {$cryptoType} to the wallet address below.",
                        'step_2' => 'After payment, submit your transaction hash via the verify-payment endpoint.',
                        'step_3' => 'Our team will confirm your payment within 1-24 hours.',
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check current subscription status.
     * GET /api/subscription/status
     */
    public function status(Request $request): JsonResponse
    {
        $user   = $request->user();
        $status = $this->subscriptionService->getSubscriptionStatus($user);

        return response()->json([
            'success' => true,
            'data'    => $status,
        ]);
    }

    /**
     * Submit transaction hash for payment verification.
     * POST /api/subscription/verify-payment
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => ['required', 'integer'],
            'tx_hash'    => ['required', 'string', 'min:10', 'max:150'],
        ]);

        $user    = $request->user();
        $payment = Payment::where('id', $request->payment_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! $payment->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This payment is no longer pending.',
                'status'  => $payment->status,
            ], 422);
        }

        try {
            $payment = $this->subscriptionService->submitTxHash($payment, $request->tx_hash);

            return response()->json([
                'success' => true,
                'message' => 'Transaction hash submitted successfully. Our team will verify your payment within 1-24 hours.',
                'data'    => [
                    'payment_id' => $payment->id,
                    'tx_hash'    => $payment->tx_hash,
                    'status'     => $payment->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get payment history for the user.
     * GET /api/subscription/payments
     */
    public function paymentHistory(Request $request): JsonResponse
    {
        $user    = $request->user();
        $perPage = min((int) $request->get('per_page', 10), 50);

        $payments = Payment::where('user_id', $user->id)
            ->with('package')
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
}