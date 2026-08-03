<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\Paginator;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword; // Imported for Forgot Password custom notification [12.2]
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Validation\Rules\Password;

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
         * Used for: Accessing Dashboards, Approving/Returning Appointments, 
         * and viewing general lists.
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
         * 6. CUSTOM EMAIL VERIFICATION DESIGN
         * Dynamically translates generic system alerts into branded laboratory notifications.
         */
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verify Email Address - Medscreen')
                ->greeting('Hello, ' . $notifiable->first_name . '!')
                ->line('Thank you for creating an account with Medscreen Diagnostic Laboratory.')
                ->line('Please click the button below to verify your email address and activate your clinical portal access.')
                ->action('Verify Email Address', $url)
                ->line('If you did not register for this account, no further action is required.')
                ->salutation("Regards,\nMedscreen Support Team");
        });

        /**
         * 7. CUSTOM PASSWORD RESET EMAIL DESIGN
         * Translates the default Laravel password reset notification into a branded Medscreen clinical template.
         */
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Password - Medscreen')
                ->greeting('Hello, ' . $notifiable->first_name . '!')
                ->line('You are receiving this email because we received a password reset request for your Medscreen account.')
                ->action('Reset Password', $url)
                ->line('This password reset link will expire in 30 minutes.') 
                ->line('If you did not request a password reset, no further action is required.')
                ->salutation("Regards,\nMedscreen Support Team");
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