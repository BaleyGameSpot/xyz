<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get user profile.
     * GET /api/user/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('activeSubscription.package');

        $activeSubscription = $user->activeSubscription;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'avatar'               => $user->avatar,
                'subscription_type'    => $user->subscription_type,
                'subscription_expiry'  => $user->subscription_expiry?->toISOString(),
                'has_active_subscription' => $user->hasActiveSubscription(),
                'allowed_timeframes'   => $user->getAllowedTimeframes(),
                'allowed_pairs_limit'  => $user->getAllowedPairsLimit(),
                'daily_signals_limit'  => $user->getDailySignalsLimit(),
                'active_subscription'  => $activeSubscription ? [
                    'package'        => $activeSubscription->package,
                    'start_date'     => $activeSubscription->start_date,
                    'end_date'       => $activeSubscription->end_date,
                    'days_remaining' => $activeSubscription->daysRemaining(),
                ] : null,
                'created_at'           => $user->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update user profile.
     * PUT /api/user/profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'min:2', 'max:100'],
            'avatar'   => ['sometimes', 'nullable', 'url', 'max:500'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    /**
     * Update the user's FCM push notification token.
     * PUT /api/user/fcm-token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'min:10', 'max:300'],
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully',
        ]);
    }
}
