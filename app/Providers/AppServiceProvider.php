<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\Paginator;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\HtmlString;

// Symfony Mailer & Brevo Transport factories
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * REGISTER CUSTOM BREVO MAIL TRANSPORT
         * Tells Laravel's MailManager how to build the Brevo HTTP API transport over port 443
         */
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn(
                    'brevo+api',
                    'default',
                    config('services.brevo.key')
                )
            );
        });

        /**
         * REGISTER SECURE PASSWORD VALIDATION RULES (GLOBAL)
         * Enforces strict password requirements (min 8 chars, mixed case, numbers, symbols)
         */
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        /**
         * 1. ADMIN GATE
         * Strictly for the System Administrator.
         * Used for: User account management, promoting/demoting staff, and deleting records.
         */
        Gate::define('isAdmin', function (User $user) {
            return $user->role === 'admin';
        });

        /**
         * 2. LAB TECHNICIAN GATE (CLINICAL PRECISION)
         * Used for: Marking as Tested (Sampling), Encoding Clinical Data, and Verifying Results.
         * Note: Admins are included for oversight.
         * 'staff' role is explicitly excluded here.
         */
        Gate::define('isLabTech', function (User $user) {
            return in_array($user->role, ['lab_tech', 'admin']);
        });

        /**
         * 3. STAFF GATE (BASE INTERNAL ACCESS)
         * Used for: Accessing Dashboards, Approving/Returning Appointments, and viewing general lists.
         * Note: Both Lab Techs and Admins inherit these base functions.
         */
        Gate::define('isStaff', function (User $user) {
            return in_array($user->role, ['staff', 'lab_tech', 'admin']);
        });

        /**
         * 4. PATIENT GATE
         * Strictly for the registered patient user role.
         */
        Gate::define('isPatient', function (User $user) {
            return $user->role === 'user';
        });

        /**
         * 5. HELPER GATE for UI Logic
         * Specifically used for administrative oversight sections (Logs, User lists).
         */
        Gate::define('manage-accounts', function (User $user) {
            return $user->role === 'admin';
        });

        /**
         * 6. BRANDED EMAIL VERIFICATION DESIGN (MEDSCREEN HTML)
         * Dynamically translates generic system alerts into branded laboratory notifications.
         */
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $firstName = ucwords(strtolower($notifiable->first_name ?? 'User'));

            $htmlContent = "
            <div style='background-color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; width: 100%; color: #1c232d;'>
                <div style='background-color: #1C232D; padding: 30px; text-align: center; border-bottom: 4px solid #19D38C;'>
                    <span style='color: #ffffff; font-weight: 800; font-size: 26px; letter-spacing: 1px;'>MED<span style='color: #19D38C;'>SCREEN</span></span>
                </div>
                <div style='padding: 40px 20px; max-width: 800px; margin: 0 auto;'>
                    <h3 style='margin-top: 0; color: #1c232d; font-size: 20px;'>Hello, {$firstName}!</h3>
                    <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>Thank you for creating an account with Medscreen Diagnostic Laboratory.</p>
                    <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>Please click the button below to verify your email address and activate your clinical portal access:</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$url}' style='background-color: #19D38C; color: #1C232D; font-weight: 800; font-size: 15px; text-decoration: none; padding: 14px 32px; border-radius: 6px; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;'>Verify Email Address</a>
                    </div>
                    
                    <p style='line-height: 1.6; color: #718096; font-size: 12px; margin-top: 30px;'>If you did not register for this account, no further action is required.</p>
                    <p style='line-height: 1.6; color: #4a5568; font-size: 15px; margin-top: 30px;'>Regards,<br><strong>Medscreen Support Team</strong></p>
                    
                    <div style='border-top: 1px solid #e2e8f0; margin-top: 30px; padding-top: 20px;'>
                        <p style='line-height: 1.5; color: #a0aec0; font-size: 11px; word-break: break-all;'>If you're having trouble clicking the \"Verify Email Address\" button, copy and paste the URL below into your web browser:<br><a href='{$url}' style='color: #19D38C;'>{$url}</a></p>
                    </div>
                </div>
            </div>";

            return (new MailMessage)
                ->subject('Verify Email Address - Medscreen')
                ->view(['html' => new HtmlString($htmlContent)]);
        });

        /**
         * 7. BRANDED CUSTOM PASSWORD RESET EMAIL DESIGN (MEDSCREEN HTML)
         * Translates default password reset notification into a branded Medscreen clinical template.
         */
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $firstName = ucwords(strtolower($notifiable->first_name ?? 'User'));

            $htmlContent = "
            <div style='background-color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; width: 100%; color: #1c232d;'>
                <div style='background-color: #1C232D; padding: 30px; text-align: center; border-bottom: 4px solid #19D38C;'>
                    <span style='color: #ffffff; font-weight: 800; font-size: 26px; letter-spacing: 1px;'>MED<span style='color: #19D38C;'>SCREEN</span></span>
                </div>
                <div style='padding: 40px 20px; max-width: 800px; margin: 0 auto;'>
                    <h3 style='margin-top: 0; color: #1c232d; font-size: 20px;'>Hello, {$firstName}!</h3>
                    <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>You are receiving this email because we received a password reset request for your Medscreen account.</p>
                    <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>Please click the button below to reset your password. This link will expire in 30 minutes:</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$url}' style='background-color: #19D38C; color: #1C232D; font-weight: 800; font-size: 15px; text-decoration: none; padding: 14px 32px; border-radius: 6px; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;'>Reset Password</a>
                    </div>
                    
                    <p style='line-height: 1.6; color: #718096; font-size: 12px; margin-top: 30px;'>If you did not request a password reset, no further action is required.</p>
                    <p style='line-height: 1.6; color: #4a5568; font-size: 15px; margin-top: 30px;'>Regards,<br><strong>Medscreen Support Team</strong></p>
                    
                    <div style='border-top: 1px solid #e2e8f0; margin-top: 30px; padding-top: 20px;'>
                        <p style='line-height: 1.5; color: #a0aec0; font-size: 11px; word-break: break-all;'>If you're having trouble clicking the \"Reset Password\" button, copy and paste the URL below into your web browser:<br><a href='{$url}' style='color: #19D38C;'>{$url}</a></p>
                    </div>
                </div>
            </div>";

            return (new MailMessage)
                ->subject('Reset Password - Medscreen')
                ->view(['html' => new HtmlString($htmlContent)]);
        });

        /*
        |--------------------------------------------------------------------------
        | PRODUCTION SECURITY
        |--------------------------------------------------------------------------
        */
        // Enforce HTTPS in production environments (Render / Hostinger)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Enable Bootstrap 5 styling for pagination links
        Paginator::useBootstrapFive();
    }
}