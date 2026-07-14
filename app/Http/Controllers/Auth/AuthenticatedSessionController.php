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
         * 4. Redirect to Dashboard
         * Note: Email verification check is currently bypassed here 
         * to allow immediate access during development.
         */
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session (Logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}