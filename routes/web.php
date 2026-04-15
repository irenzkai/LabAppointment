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
use App\Http\Controllers\NotificationController;

// 1. PUBLIC
Route::get('/', function () { return view('welcome'); });
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

// 2. AUTHENTICATED
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Password
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password/change', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Family / Dependents
    Route::post('/dependents', [DependentController::class, 'store'])->name('dependents.store');
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy'])->name('dependents.destroy');

    // Booking & Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{service}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    
    // Bulk Booking
    Route::get('/bulk-appointment', [BulkAppointmentController::class, 'index'])->name('appointments.bulk');
    Route::post('/bulk-appointment/manual', [BulkAppointmentController::class, 'storeManual'])->name('appointments.bulk.manual');
    Route::get('/bulk-appointment/template/{type}', [BulkAppointmentController::class, 'downloadTemplate'])->name('appointments.bulk.template');
    Route::post('/bulk-appointment/parse', [BulkAppointmentController::class, 'parseExcel'])->name('appointments.bulk.parse');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clearAll');

    // Medical Record Access (Reason-Locked)
    Route::get('/appointments/{appointment}/result/{type}/{mode}', [AppointmentController::class, 'accessResult'])->name('appointments.result.access');
    Route::post('/appointments/{appointment}/log-access', [AppointmentController::class, 'logAccess'])->name('appointments.logAccess');
    Route::get('/api/check-slots', [AppointmentConfigController::class, 'checkOccupancy']);

    // --- ADMINISTRATIVE & STAFF AREA ---
    
    // User List is shared by Admin & Staff
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');

    // STAFF ONLY: Patient History
    Route::middleware('role:staff')->group(function () {
        Route::get('/admin/users/{user}/history', [AdminController::class, 'patientHistory'])->name('admin.patient-history');
    });

    // ADMIN ONLY: Account Control
    Route::middleware('role:admin')->group(function () {
        Route::patch('/admin/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle');
        Route::patch('/admin/users/{id}/{role}', [AdminController::class, 'changeRole'])->name('admin.users.changeRole');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
        // Audit Logs View
        Route::get('/admin/audit-logs', [AdminController::class, 'viewLogs'])->name('admin.logs');
    });

    // STAFF & ADMIN: Clinic Workflow
    Route::middleware('role:staff,admin')->group(function () {
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::patch('/appointments/{appointment}/tested', [AppointmentController::class, 'markTested'])->name('appointments.tested');
        Route::get('/appointments/{appointment}/encode', [AppointmentController::class, 'encodeResults'])->name('appointments.encode');
        Route::post('/appointments/{appointment}/release', [AppointmentController::class, 'releaseResults'])->name('appointments.release');

        Route::get('/admin/appointment-settings', [AppointmentConfigController::class, 'index'])->name('admin.appointment-settings');
        Route::put('/admin/appointment-settings/{id}', [AppointmentConfigController::class, 'update'])->name('admin.appointment-settings.update');
    });

});

require __DIR__.'/auth.php';