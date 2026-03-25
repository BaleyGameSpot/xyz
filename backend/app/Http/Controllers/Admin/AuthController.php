<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the admin login form.
     * GET /admin/login
     */
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Process admin login.
     * POST /admin/login
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Use 'web' guard since this is a session-based admin panel
        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('web')->user();

            if (! $user->isAdmin()) {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'You do not have admin access.']);
            }

            if ($user->isBlocked()) {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'Your account has been suspended.']);
            }

            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
    }

    /**
     * Logout the admin user.
     * POST /admin/logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
