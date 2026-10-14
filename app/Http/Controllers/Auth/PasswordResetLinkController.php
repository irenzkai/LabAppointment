<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $normalizedEmail = strtolower(trim($request->email));

        // 1. Server-Side Anti-Brute Force Protection (IP & Email Throttling)
        $throttleKey = 'forgot_password:' . Str::transliterate($normalizedEmail . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        // 2. Check if the email belongs to a deactivated (soft-deleted) account
        $trashedUser = User::onlyTrashed()->where('email', $normalizedEmail)->first();
        if ($trashedUser) {
            RateLimiter::hit($throttleKey, 600);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This account is currently deactivated. Please sign in to receive an account reactivation code.',
            ]);
        }

        // 3. Strict Verified-Email Enforcement
        $user = User::where('email', $normalizedEmail)->first();
        if ($user && is_null($user->email_verified_at)) {
            RateLimiter::hit($throttleKey, 600);

            if (! is_null($user->phone_verified_at)) {
                return back()->withInput($request->only('email'))->withErrors([
                    'email' => 'Password reset links can only be dispatched to verified email addresses. Since your mobile phone is verified, please sign in using your phone number.',
                ]);
            }

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Password reset links can only be dispatched to verified email addresses. Please verify your email address before requesting a reset.',
            ]);
        }

        // 4. Dispatch the password reset link via Laravel Password Broker
        $status = Password::sendResetLink(
            ['email' => $normalizedEmail]
        );

        if ($status == Password::RESET_LINK_SENT) {
            RateLimiter::hit($throttleKey, 600);

            if ($user) {
                ActivityLog::record('PASSWORD RESET LINK DISPATCHED', "Password reset link requested for {$user->email}", $user->name);
            }

            return back()->with('status', __($status));
        }

        RateLimiter::hit($throttleKey, 600);

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}