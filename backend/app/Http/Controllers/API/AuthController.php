<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ], 201);
    }

    /**
     * Login with email and password.
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        $user = Auth::user();

        if ($user->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact support.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'       => $this->userResource($user),
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    /**
     * Google OAuth login / registration.
     * POST /api/auth/google
     * Body: { firebase_token: string }
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'firebase_token' => ['required', 'string'],
        ]);

        try {
            // Verify Firebase token
            $firebaseUser = $this->verifyFirebaseToken($request->firebase_token);

            if (! $firebaseUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Firebase token',
                ], 401);
            }

            // Find or create user
            $user = User::where('google_id', $firebaseUser['uid'])
                ->orWhere('email', $firebaseUser['email'])
                ->first();

            if (! $user) {
                $user = User::create([
                    'name'              => $firebaseUser['name'] ?? 'Google User',
                    'email'             => $firebaseUser['email'],
                    'google_id'         => $firebaseUser['uid'],
                    'avatar'            => $firebaseUser['picture'] ?? null,
                    'email_verified_at' => now(),
                    'password'          => null,
                ]);
            } else {
                // Update google_id and avatar if logging in via Google for first time
                $updates = [];
                if (! $user->google_id) {
                    $updates['google_id'] = $firebaseUser['uid'];
                }
                if (! $user->avatar && isset($firebaseUser['picture'])) {
                    $updates['avatar'] = $firebaseUser['picture'];
                }
                if (! empty($updates)) {
                    $user->update($updates);
                }
            }

            if ($user->isBlocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended.',
                ], 403);
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Google login successful',
                'data'    => [
                    'user'       => $this->userResource($user),
                    'token'      => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Google login failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Logout the current user.
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token already invalid or expired – ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get the current authenticated user.
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('activeSubscription.package');

        return response()->json([
            'success' => true,
            'data'    => $this->userResource($user),
        ]);
    }

    /**
     * Refresh the JWT token.
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'data'    => [
                    'token'      => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed. Please login again.',
            ], 401);
        }
    }

    /**
     * Verify a Firebase ID token and return user claims.
     *
     * @param  string $idToken
     * @return array|null
     */
    private function verifyFirebaseToken(string $idToken): ?array
    {
        $credentialsFile = config('trading.fcm.credentials_file');

        if (empty($credentialsFile) || ! file_exists($credentialsFile)) {
            // Development fallback: decode JWT payload without verification
            // DO NOT use this in production without Firebase credentials
            $parts   = explode('.', $idToken);
            if (count($parts) !== 3) {
                return null;
            }
            $payload = json_decode(base64_decode(str_pad(
                strtr($parts[1], '-_', '+/'),
                strlen($parts[1]) % 4 === 0 ? strlen($parts[1]) : strlen($parts[1]) + (4 - strlen($parts[1]) % 4),
                '='
            )), true);

            return $payload ? [
                'uid'     => $payload['user_id'] ?? $payload['sub'] ?? null,
                'email'   => $payload['email'] ?? null,
                'name'    => $payload['name'] ?? null,
                'picture' => $payload['picture'] ?? null,
            ] : null;
        }

        // Use Firebase Admin SDK for production verification
        try {
            $factory = (new \Kreait\Firebase\Factory())->withServiceAccount($credentialsFile);
            $auth    = $factory->createAuth();
            $token   = $auth->verifyIdToken($idToken);
            $claims  = $token->claims();

            return [
                'uid'     => $claims->get('sub'),
                'email'   => $claims->get('email'),
                'name'    => $claims->get('name'),
                'picture' => $claims->get('picture'),
            ];
        } catch (\Exception $e) {
            Log::error('Firebase token verification failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Format user data for API response.
     */
    private function userResource(User $user): array
    {
        $data = [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'avatar'              => $user->avatar,
            'role'                => $user->role,
            'subscription_type'   => $user->subscription_type,
            'subscription_expiry' => $user->subscription_expiry?->toISOString(),
            'has_active_subscription' => $user->hasActiveSubscription(),
            'status'              => $user->status,
            'created_at'          => $user->created_at->toISOString(),
        ];

        if ($user->relationLoaded('activeSubscription') && $user->activeSubscription) {
            $data['active_subscription'] = [
                'package' => $user->activeSubscription->package,
                'end_date' => $user->activeSubscription->end_date,
                'days_remaining' => $user->activeSubscription->daysRemaining(),
            ];
        }

        return $data;
    }
}
