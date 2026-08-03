<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth; 

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // 1. If already verified, log out and send to login
        if ($request->user()->hasVerifiedEmail()) {
            Auth::logout();
            return redirect()->route('login')->with('status', 'Your email is already verified. Please log in.');
        }

        // 2. Mark user as verified in the database
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // 3. Log the user out and redirect to login with a success prompt
        Auth::logout();
        return redirect()->route('login')->with('status', 'Your email has been successfully verified! Please log in to continue.');
    }
}