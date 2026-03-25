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

// 1. PUBLIC ROUTES (Accessible without logging in)
Route::get('/', function () { return view('welcome'); });
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

// 2. AUTHENTICATED ROUTES (User must be logged in)
Route::middleware('auth')->group(function () {

    // Main Menu / Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Account Settings (Profile)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dependents (Family Members)
    Route::post('/dependents', [DependentController::class, 'store'])->name('dependents.store');
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy'])->name('dependents.destroy');

    // Cart System
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{service}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

    // Appointments (History and Basic Booking)
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

    // API for Real-time Occupancy Checks (used by JS in Checkout)
    Route::get('/api/check-slots', [AppointmentConfigController::class, 'checkOccupancy']);

    // Bulk Appointments (Requesting for an Organization)
    Route::get('/bulk-appointment', [BulkAppointmentController::class, 'index'])->name('appointments.bulk');
    Route::post('/bulk-appointment/manual', [BulkAppointmentController::class, 'storeManual'])->name('appointments.bulk.manual');
    Route::post('/bulk-appointment/excel', [BulkAppointmentController::class, 'storeExcel'])->name('appointments.bulk.excel');
    Route::get('/bulk-appointment/template/{type}', [BulkAppointmentController::class, 'downloadTemplate'])->name('appointments.bulk.template');
    Route::post('/bulk-appointment/parse', [BulkAppointmentController::class, 'parseExcel'])->name('appointments.bulk.parse');

    // Download Test Results (PDF or CSV)
    Route::get('/appointments/{appointment}/download/{type}', [AppointmentController::class, 'downloadResult'])->name('appointments.download');

    // Mark Notifications as Read
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clearAll');

    // 3. STAFF & ADMIN ONLY ROUTES
    Route::middleware('role:staff,admin')->group(function () {
        // Service Management (CRUD)
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        // Appointment Workflow
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');

        // Schedule Configuration (Opening Hours, Slot Duration)
        Route::get('/admin/appointment-settings', [AppointmentConfigController::class, 'index'])->name('admin.appointment-settings');
        Route::put('/admin/appointment-settings/{id}', [AppointmentConfigController::class, 'update'])->name('admin.appointment-settings.update');

        // Test Result Management
        Route::patch('/appointments/{appointment}/tested', [AppointmentController::class, 'markTested'])->name('appointments.tested');
        Route::get('/appointments/{appointment}/encode', [AppointmentController::class, 'encodeResults'])->name('appointments.encode');
        Route::post('/appointments/{appointment}/release', [AppointmentController::class, 'releaseResults'])->name('appointments.release');
    });


    // 4. ADMIN ONLY ROUTES
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [AdminController::class, 'index']);
        Route::patch('/admin/users/{id}/staff', [AdminController::class, 'promote']);
        Route::patch('/admin/users/{id}/user', [AdminController::class, 'demote']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'destroy']);
    });

});

require __DIR__.'/auth.php';