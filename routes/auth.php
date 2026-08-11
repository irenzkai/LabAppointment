<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ProfileController; // Imported to handle unverified email changes
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // --- CUSTOM UNAUTHENTICATED ACCOUNT REACTIVATION BOUNDARIES ---
    Route::get('reactivate', [VerifyEmailController::class, 'reactivateNotice'])
        ->name('reactivate.notice');

    Route::post('reactivate/verify-otp', [VerifyEmailController::class, 'verifyReactivationOtp'])
        ->name('reactivate.verify-otp');

    Route::post('reactivate/resend-otp', [EmailVerificationNotificationController::class, 'sendReactivationOtp'])
        ->name('reactivate.send-otp');
});

Route::middleware('auth')->group(function () {
    // 1. Core Verification Prompt (Renders your verify-account view)
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    // 2. Link-based Email Verification (Standard Laravel)
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->name('verification.verify');

    // 3. Link-based Email Resend (Standard Laravel)
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->name('verification.send');

    // 4. FIXED: OTP-based Email Verification submit endpoint
    Route::post('verify-otp', [VerifyEmailController::class, 'verifyOtp'])
        ->name('verification.verify-otp');

    // 5. FIXED: OTP-based Email Resend endpoint
    Route::post('email/verification-otp', [EmailVerificationNotificationController::class, 'sendOtp'])
        ->name('verification.send-otp');

    // 6. FIXED: Email change adjustment for unverified users
    Route::post('email/change', [ProfileController::class, 'changeUnverifiedEmail'])
        ->name('verification.change-email');

    // 7. Session Polling Status Endpoint for browser redirect sync
    Route::get('/api/verification-status', function () {
        return response()->json([
            'verified' => auth()->user() ? auth()->user()->hasVerifiedEmail() : false
        ]);
    })->name('verification.status');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});