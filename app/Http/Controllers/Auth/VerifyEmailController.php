<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     * 
     * This is triggered when the user clicks the link in the verification email.
     * Even without an active Email API, this logic handles the database update
     * for the 'email_verified_at' column.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // 1. If already verified, just send to dashboard
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // 2. Mark user as verified in the database
        if ($request->user()->markEmailAsVerified()) {
            // Trigger the Verified event (useful for notifications/logs)
            event(new Verified($request->user()));
        }

        // 3. Redirect to dashboard with a success flag
        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}