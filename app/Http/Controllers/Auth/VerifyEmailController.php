<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

        \App\Models\ActivityLog::record('ACCOUNT REACTIVATED', 'User reactivated their deactivated profile.', $user->name, null);

        session()->forget(['reactivate_user_id', 'email_otp_code']);

        return redirect()->route('main')->with('success', 'Welcome back! Your account has been successfully reactivated.');
    }
}