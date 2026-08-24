<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified (Link-based).
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            Auth::logout();
            return redirect()->route('login')->with('status', 'Your email is already verified. Please log in.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        Auth::logout();
        session()->forget('email_otp_code');

        return redirect()->route('login')->with('status', 'Your email has been successfully verified! Please log in to continue.');
    }

    /**
     * Verify the 6-digit email OTP for the authenticated user.
     */
    public function verifyOtp(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Check if user email is already verified
        if ($user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email is already verified.',
                    'email' => $user->email,
                    'redirect' => route('main', absolute: false)
                ]);
            }
            return redirect()->intended(route('main', absolute: false));
        }

        // Validate OTP format
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $sessionOtp = session()->get('email_otp_code');

        // Check against session OTP code
        if (!$sessionOtp || $request->input('otp') !== (string) $sessionOtp) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The entered verification code is incorrect or has expired.'
                ], 422);
            }
            return back()->withErrors(['otp' => 'The entered verification code is incorrect or has expired.']);
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            ActivityLog::record('EMAIL VERIFIED', 'User completed email verification via OTP.', $user->name);
        }

        session()->forget('email_otp_code');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your email has been successfully verified!',
                'email' => $user->email,
                'redirect' => route('main', absolute: false)
            ]);
        }

        return redirect()->intended(route('main', absolute: false))
            ->with('success', 'Your email address has been successfully verified!');
    }

    /**
     * Verify the reactivation OTP, restore the account, and log them in safely.
     */
    public function verifyReactivationOtp(Request $request): RedirectResponse
    {
        if (!session()->has('reactivate_user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $sessionOtp = session()->get('email_otp_code');

        if (!$sessionOtp || $request->otp !== (string)$sessionOtp) {
            return back()->withErrors(['otp' => 'The entered verification code is incorrect or has expired.']);
        }

        $user = \App\Models\User::onlyTrashed()->findOrFail(session('reactivate_user_id'));
        $user->restore();

        Auth::login($user);

        ActivityLog::record('ACCOUNT REACTIVATED', 'User reactivated their deactivated profile.', $user->name, null);

        session()->forget(['reactivate_user_id', 'email_otp_code']);

        return redirect()->route('main')->with('success', 'Welcome back! Your account has been successfully reactivated.');
    }
}