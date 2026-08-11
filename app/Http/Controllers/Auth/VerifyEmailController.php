<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

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
     * Mark the authenticated user's email address as verified (OTP-based).
     */
    public function verifyOtp(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
            'email' => 'nullable|email'
        ]);

        $sessionOtp = session()->get('email_otp_code');

        if (!$sessionOtp || $request->otp !== (string)$sessionOtp) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The entered verification code is incorrect.'
                ], 422);
            }
            return back()->withErrors(['otp' => 'The entered verification code is incorrect or has expired. Please check your logs and try again.']);
        }

        $user = $request->user();
        $email = $request->input('email', $user->email);

        // VITAL FIX: If this is an AJAX request from the profile page email change flow,
        // commit and verify the new email immediately so it does not trigger the global 'verified' middleware.
        if ($request->expectsJson() && $email !== $user->email) {
            $user->email = $email;
            $user->email_verified_at = now();
            $user->save();
        } else {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        session()->forget('email_otp_code');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'email' => $user->email,
                'message' => 'Email verified successfully!'
            ]);
        }

        // Only log out and redirect to login for standard registration verify-account flow
        Auth::logout();

        return redirect()->route('login')->with('status', 'Your email has been successfully verified via OTP! Please log in to continue.');
    }

    /**
     * Render the reactivation notice and automatically dispatch the OTP code [122]
     */
    public function reactivateNotice(Request $request)
    {
        if (!session()->has('reactivate_user_id')) {
            return redirect()->route('login');
        }

        $user = User::onlyTrashed()->findOrFail(session('reactivate_user_id'));

        // Auto-send OTP if not already present in the current session
        if (!session()->has('email_otp_code')) {
            $otp = rand(100000, 999999);
            session()->put('email_otp_code', $otp);

            // Dispatch rephrased reactivation email
            $this->sendReactivationEmail($user, $otp);
        }

        return view('auth.reactivate-account');
    }

    /**
     * Verify the reactivation OTP, restore the account, and log them in safely [102, 122]
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

        // Find, restore, and authenticate the user cleanly
        $user = User::onlyTrashed()->findOrFail(session('reactivate_user_id'));
        $user->restore();
        
        Auth::login($user);

        // Log administrative audit trail for security [100]
        ActivityLog::record('ACCOUNT REACTIVATED', 'User reactivated their deactivated profile.', $user->name, null);

        // Clean up session parameters
        session()->forget(['reactivate_user_id', 'email_otp_code']);

        return redirect()->route('dashboard')->with('success', 'Welcome back! Your account has been successfully reactivated.');
    }

    /**
     * Internal helper to dispatch the rephrased email content safely
     */
    private function sendReactivationEmail(User $user, $otp)
    {
        $email = $user->email;
        $firstName = ucwords(strtolower($user->first_name));
        $subject = 'Reactivate Your Medscreen Account';
        
        $htmlContent = "
        <div style='background-color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; width: 100%; color: #1c232d;'>
            <div style='background-color: #1C232D; padding: 30px; text-align: center; border-bottom: 4px solid #19D38C;'>
                <span style='color: #ffffff; font-weight: 800; font-size: 26px; letter-spacing: 1px;'>MED<span style='color: #19D38C;'>SCREEN</span></span>
            </div>
            <div style='padding: 40px 20px; max-width: 800px; margin: 0 auto;'>
                <h3 style='margin-top: 0; color: #1c232d; font-size: 20px;'>Dear {$firstName},</h3>
                <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>Welcome back! We received a request to reactivate your deactivated Medscreen account.</p>
                <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>To confirm your identity and complete the reactivation process, please enter the following 6-digit verification code in your browser:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='background-color: #f8fafc; border: 2px solid #19D38C; color: #1c232d; font-family: monospace; font-size: 32px; font-weight: 800; padding: 15px 30px; border-radius: 8px; letter-spacing: 5px; display: inline-block;'>{$otp}</span>
                </div>
                
                <p style='line-height: 1.6; color: #718096; font-size: 12px; margin-top: 30px;'>This code is highly sensitive and is valid for a single reactivation attempt only. If you did not request this, please ignore this email.</p>
                <p style='line-height: 1.6; color: #4a5568; font-size: 15px; margin-top: 30px;'>Best regards,<br><strong>Medscreen Support Team</strong></p>
            </div>
        </div>";

        try {
            \Illuminate\Support\Facades\Mail::html($htmlContent, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error("Failed to send reactivation OTP email: " . $e->getMessage());
        }
    }
}