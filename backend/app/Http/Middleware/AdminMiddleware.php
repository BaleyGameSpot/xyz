<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // For web admin panel routes - check session-based admin guard
        if ($request->is('admin/*') || $request->is('admin')) {
            if (! auth('admin')->check()) {
                return redirect()->route('admin.login');
            }

            $admin = auth('admin')->user();
            if (! $admin->is_active) {
                auth('admin')->logout();
                return redirect()->route('admin.login')
                    ->withErrors(['email' => 'Your account has been disabled.']);
            }

            return $next($request);
        }

        // For API admin routes - check JWT api guard with admin role
        $user = $request->user('api');
        if (! $user || ! in_array($user->role ?? '', ['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Administrator privileges required.',
            ], 403);
        }

        return $next($request);
    }
}
