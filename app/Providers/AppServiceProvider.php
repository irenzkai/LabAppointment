<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use App\Models\User;

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