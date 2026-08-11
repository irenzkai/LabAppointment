<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification (Link-based).
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Send a dedicated One-Time Password email (OTP-based).
     */
    public function sendOtp(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $email = $request->input('email', $user->email);

        // Only block if the target email is the user's current email AND it is already verified.
        if ($email === $user->email && $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified.'
                ], 422);
            }
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 1. Generate or refresh the 6-digit OTP code in session
        $otp = rand(100000, 999999);
        session()->put('email_otp_code', $otp);

        $firstName = ucwords(strtolower($user->first_name));

        // Determine if this is an email change (Reactivation) or initial sign-up (Activation)
        $isReactivation = $request->has('email') && $request->input('email') !== $user->getOriginal('email');

        if ($isReactivation) {
            $subject = 'Your Email Reactivation Code - Medscreen';
            $headline = 'Email Reactivation';
            $messageBody = "
            <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>You are receiving this email because you have requested to update the registered email address on your Medscreen profile.</p>
            <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>To reactivate your portal and confirm this change, please enter the following 6-digit verification code:</p>
            ";
        } else {
            $subject = 'Your Account Activation Code - Medscreen';
            $headline = 'Account Activation';
            $messageBody = "
            <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>Thank you for creating an account with Medscreen Diagnostic Laboratory.</p>
            <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>To activate your clinical portal using One-Time Password verification, please enter the following 6-digit verification code:</p>
            ";
        }

        // 2. Dispatch the clinical OTP email template
        $htmlContent = "
        <div style='background-color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; width: 100%; color: #1c232d;'>
            <div style='background-color: #1C232D; padding: 30px; text-align: center; border-bottom: 4px solid #19D38C;'>
                <span style='color: #ffffff; font-weight: 800; font-size: 26px; letter-spacing: 1px;'>MED<span style='color: #19D38C;'>SCREEN</span></span>
            </div>
            <div style='padding: 40px 20px; max-width: 800px; margin: 0 auto;'>
                <h3 style='margin-top: 0; color: #1c232d; font-size: 20px;'>Dear {$firstName},</h3>
                {$messageBody}
                
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='background-color: #f8fafc; border: 2px solid #19D38C; color: #1c232d; font-family: monospace; font-size: 32px; font-weight: 800; padding: 15px 30px; border-radius: 8px; letter-spacing: 5px; display: inline-block;'>{$otp}</span>
                </div>
                
                <p style='line-height: 1.6; color: #718096; font-size: 12px; margin-top: 30px;'>This code is highly sensitive and is valid for a single verification attempt only. If you did not request this, no further action is required.</p>
                <p style='line-height: 1.6; color: #4a5568; font-size: 15px; margin-top: 30px;'>Best regards,<br><strong>Medscreen Support Team</strong></p>
            </div>
        </div>";

        try {
            Mail::html($htmlContent, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error("Failed to send verification OTP email: " . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send verification email.'
                ], 500);
            }
        }

        // Log the event safely for sandbox testing
        Log::info("Sandbox Verification OTP for User ID " . $user->id . ": " . $otp);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Verification code sent successfully.'
            ]);
        }

        return back()->with('status', 'verification-code-sent');
    }

    /**
     * Resend the rephrased reactivation OTP to unauthenticated soft-deleted user [122]
     */
    public function sendReactivationOtp(Request $request): RedirectResponse
    {
        if (!session()->has('reactivate_user_id')) {
            return redirect()->route('login');
        }

        $user = User::onlyTrashed()->findOrFail(session('reactivate_user_id'));

        // 1. Generate or refresh the 6-digit OTP code in session
        $otp = rand(100000, 999999);
        session()->put('email_otp_code', $otp);

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
            Mail::html($htmlContent, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error("Failed to send reactivation OTP email: " . $e->getMessage());
        }

        // Log the event safely for sandbox testing [108]
        Log::info("Sandbox Reactivation OTP for User ID " . $user->id . ": " . $otp);

        return back()->with('status', 'verification-code-sent');
    }
}