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
        // 1. Validate and Authenticate credentials (Email or Mobile Number)
        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($e->errors() && isset($e->errors()['deactivated'])) {
                // Redirect deactivated users to the reactivation route with a notice
                return redirect()->route('reactivate.notice');
            }
            throw $e;
        }

        // 2. Security Check: Ensure the account hasn't been suspended by an Administrator
        if (! Auth::user()->is_active) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'This account has been disabled by the administrator.',
            ]);
        }

        // 3. Success: Regenerate session to prevent session fixation attacks
        $request->session()->regenerate();

        /**
         * 4. Redirect based on verification status
         * If the user has neither email nor phone verified, redirect them straight 
         * to the verification notice screen to complete account activation.
         */
        if (Auth::user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Clear stale intended URLs to ensure verified accounts land cleanly on the main portal
        $request->session()->forget('url.intended');

        return redirect()->route('main');
    }

    /**
     * Destroy an authenticated session (Logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::guard('web')->logout();

        // Clear verification and reactivation session states
        $request->session()->forget([
            'reactivate_user_id',
            'email_otp_code',
            'sms_otp_code',
            'sms_otp_phone'
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Allow custom redirect targets on logout to support smooth account transitions
        $redirectTo = $request->input('redirect_to', '/');

        return redirect($redirectTo);
    }
}