<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified (Link-based).
     * Validates the cryptographic URL signature so this works across ANY device.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse 
    {
        // 1. Verify cryptographic signature and expiration
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->withErrors([
                'email' => 'The email verification link is invalid or has expired. Please log in to request a fresh verification link.'
            ]);
        }

        // 2. Find the target user by the route parameter ID
        $user = User::findOrFail($id);

        // 3. Validate that the email hash matches the user's current email address
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->withErrors([
                'email' => 'The verification link does not match this account email address.'
            ]);
        }

        // 4. Check if already verified
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Your email is already verified. Please log in to continue.');
        }

        // 5. Mark as verified and dispatch system event
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            ActivityLog::record('EMAIL VERIFIED', 'User verified email via secure link.', $user->name);
        }

        // 6. Clear cached OTP states
        session()->forget('email_otp_code');
        Cache::forget("email_otp_{$user->id}");
        Cache::forget("email_otp_addr_{$user->id}");

        // 7. If the user happens to be logged in on this same device, log them out cleanly so they re-authenticate with full privileges
        if (Auth::check() && Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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

        // Check both Cache (for mobile API) and Session (for web)
        $cachedOtp = Cache::get("email_otp_{$user->id}");
        $sessionOtp = session()->get('email_otp_code');
        $validOtp = $cachedOtp ?: $sessionOtp;
        $submittedOtp = trim($request->input('otp'));

        if (!$validOtp || $submittedOtp !== (string) $validOtp) {
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
        Cache::forget("email_otp_{$user->id}");
        Cache::forget("email_otp_addr_{$user->id}");

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

        $userId = session('reactivate_user_id');
        $cachedOtp = Cache::get("email_otp_{$userId}");
        $sessionOtp = session()->get('email_otp_code');
        $validOtp = $cachedOtp ?: $sessionOtp;
        $submittedOtp = trim($request->input('otp'));

        if (!$validOtp || $submittedOtp !== (string)$validOtp) {
            return back()->withErrors(['otp' => 'The entered verification code is incorrect or has expired.']);
        }

        $user = \App\Models\User::onlyTrashed()->findOrFail($userId);
        $user->restore();
        Auth::login($user);

        ActivityLog::record('ACCOUNT REACTIVATED', 'User reactivated their deactivated profile.', $user->name, null);
        session()->forget(['reactivate_user_id', 'email_otp_code']);
        Cache::forget("email_otp_{$userId}");

        return redirect()->route('main')->with('success', 'Welcome back! Your account has been successfully reactivated.');
    }
}