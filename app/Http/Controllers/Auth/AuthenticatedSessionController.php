<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Validate and Authenticate credentials
        $request->authenticate();

        // 2. Security Check: Ensure the account hasn't been disabled by an Admin
        if (!Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'This account has been disabled by the administrator.',
            ]);
        }

        // 3. Success: Regenerate session to prevent fixation
        $request->session()->regenerate();

        /**
         * 4. Redirect based on verification status [19]
         * If the user is unverified, bypass the intended dashboard and redirect
         * them straight to the email verification notice screen.
         */
        if (Auth::user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Clear stale intended URLs to ensure newly verified accounts land cleanly on the dashboard [19]
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session (Logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Allow custom redirect targets on logout to support smooth account transitions [19]
        $redirectTo = $request->input('redirect_to', '/');

        return redirect($redirectTo);
    }
}