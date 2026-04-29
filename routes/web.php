<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DependentController;
use App\Http\Controllers\AppointmentConfigController;
use App\Http\Controllers\BulkAppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', function () { return view('welcome'); });
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

/*
|--------------------------------------------------------------------------
| 2. AUTHENTICATED USER ROUTES (Common to all roles)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Security
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password/change', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clearAll');

    // Shared Archive Access (Used by Patients for self, and Employees for any patient)
    Route::get('/history/{user?}', [HistoryController::class, 'index'])->name('patient.history');
    Route::post('/history/{user?}/accept', [HistoryController::class, 'acceptRequest'])->name('history.accept');

    /*
    |--------------------------------------------------------------------------
    | 2a. REASON-GATE ACCESS ROUTES (Unique Internal URLs)
    | These are defined FIRST to prevent collisions with appointment IDs
    |--------------------------------------------------------------------------
    */
    // Route for general archive access (no appointment ID)
    Route::post('/internal/archive-log-access', [AppointmentController::class, 'logAccess'])
        ->name('appointments.logAccessHistory');

    // Route for specific appointment access (requires appointment ID)
    Route::post('/internal/appointment-log-access/{appointment}', [AppointmentController::class, 'logAccess'])
        ->name('appointments.logAccess');

    // Actual Result File Access (PDF/Image)
    Route::get('/appointments/{appointment}/result/{type}/{mode}', [AppointmentController::class, 'accessResult'])
        ->name('appointments.result.access');


    /*
    |--------------------------------------------------------------------------
    | 2b. PATIENT SPECIFIC ROUTES (Role: user)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:user')->group(function () {
        Route::post('/dependents', [DependentController::class, 'store'])->name('dependents.store');
        Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy'])->name('dependents.destroy');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/add/{service}', [CartController::class, 'add'])->name('cart.add');
        Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
        
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        
        // Bulk Booking
        Route::get('/bulk-appointment', [BulkAppointmentController::class, 'index'])->name('appointments.bulk');
        Route::post('/bulk-appointment/manual', [BulkAppointmentController::class, 'storeManual'])->name('appointments.bulk.manual');
        Route::get('/bulk-appointment/template/{type}', [BulkAppointmentController::class, 'downloadTemplate'])->name('appointments.bulk.template');
        Route::post('/bulk-appointment/parse', [BulkAppointmentController::class, 'parseExcel'])->name('appointments.bulk.parse');

        // History Request
        Route::post('/history/request', [HistoryController::class, 'requestPermission'])->name('history.request');
    });

    /*
    |--------------------------------------------------------------------------
    | 2c. SHARED LIST VIEW
    |--------------------------------------------------------------------------
    */
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update'); 

    /*
    |--------------------------------------------------------------------------
    | 3. INTERNAL PERSONNEL AREA (Staff, Lab Tech, Admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:staff,lab_tech,admin')->group(function () {
        
        // Front-Office Check-in
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        
        // Results Management (Staff types it, Tech verifies it)
        Route::get('/appointments/{appointment}/encode', [AppointmentController::class, 'encodeResults'])->name('appointments.encode');
        Route::post('/appointments/{appointment}/release', [AppointmentController::class, 'releaseResults'])->name('appointments.release');

        // Catalog & Settings
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('/admin/appointment-settings', [AppointmentConfigController::class, 'index'])->name('admin.appointment-settings');
        Route::put('/admin/appointment-settings/{id}', [AppointmentConfigController::class, 'update'])->name('admin.appointment-settings.update');

        // Archive and Data Triage Management
        Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/patient-records/{user}', [AdminController::class, 'patientHistory'])->name('admin.patient-history');
        
        Route::post('/history/{user}/trigger-request', [HistoryController::class, 'staffTriggerRequest'])->name('history.staff-trigger');
        Route::post('/history/{user}/import-manual', [HistoryController::class, 'saveManualData'])->name('history.save-manual');
    });

    /*
    |--------------------------------------------------------------------------
    | 4. CLINICAL WORKFLOW ROUTES (Lab Tech and Admin ONLY)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:lab_tech,admin')->group(function () {
        
        // Sampling
        Route::patch('/appointments/{appointment}/mark-tested', [AppointmentController::class, 'markTested'])->name('appointments.tested');
        
        // Clinical Sign-off (Verify)
        Route::post('/appointments/{appointment}/verify/{type}', [AppointmentController::class, 'verifyResult'])->name('appointments.verify');
    });

    /*
    |--------------------------------------------------------------------------
    | 5. SYSTEM ADMINISTRATOR ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        Route::patch('/admin/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle');
        Route::patch('/admin/users/{id}/{role}', [AdminController::class, 'changeRole'])->name('admin.users.changeRole');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
        Route::get('/admin/audit-logs', [AdminController::class, 'viewLogs'])->name('admin.logs');
    });

});

Route::get('/api/check-slots', [AppointmentConfigController::class, 'checkOccupancy']);

require __DIR__.'/auth.php';