<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the account verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Generate temporary 6-digit verification OTP if not already present
        if (!session()->has('email_otp_code')) {
            $otp = rand(100000, 999999);
            session()->put('email_otp_code', $otp);

            $email = $user->email;
            $firstName = ucwords(strtolower($user->first_name));

            // Initial Registration flow: Always dispatches the Account Activation template
            $htmlContent = "
<div style='background-color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; width: 100%; color: #1c232d;'>
    <div style='background-color: #1C232D; padding: 30px; text-align: center; border-bottom: 4px solid #19D38C;'>
        <span style='color: #ffffff; font-weight: 800; font-size: 26px; letter-spacing: 1px;'>MED<span style='color: #19D38C;'>SCREEN</span></span>
    </div>
    <div style='padding: 40px 20px; max-width: 800px; margin: 0 auto;'>
        <h3 style='margin-top: 0; color: #1c232d; font-size: 20px;'>Dear {$firstName},</h3>
        <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>Thank you for creating an account with Medscreen Diagnostic Laboratory.</p>
        <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>To activate your clinical portal using One-Time Password verification, please enter the following 6-digit verification code:</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <span style='background-color: #f8fafc; border: 2px solid #19D38C; color: #1c232d; font-family: monospace; font-size: 32px; font-weight: 800; padding: 15px 30px; border-radius: 8px; letter-spacing: 5px; display: inline-block;'>{$otp}</span>
        </div>
        
        <p style='line-height: 1.6; color: #718096; font-size: 12px; margin-top: 30px;'>This code is highly sensitive and is valid for a single verification attempt only. If you did not request this, no further action is required.</p>
        <p style='line-height: 1.6; color: #4a5568; font-size: 15px; margin-top: 30px;'>Best regards,<br><strong>Medscreen Support Team</strong></p>
    </div>
</div>
            ";

            try {
                Mail::html($htmlContent, function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Your Account Activation Code - Medscreen');
                });
            } catch (\Exception $e) {
                Log::error("Failed to send verification OTP email: " . $e->getMessage());
            }

            // SANDBOX TESTING LOG: Prints the generated OTP safely to storage/logs/laravel.log
            Log::info("Sandbox Verification OTP for User ID " . $user->id . ": " . $otp);
        }

        // Point to verify-account view
        return view('auth.verify-account');
    }
}