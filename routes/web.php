<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AppointmentConfigController;
use Illuminate\Support\Facades\Route;

// --- PUBLIC ROUTES (No Login Required) ---
Route::get('/', function () {
    return view('welcome');
});

// Everyone can see services, but booking logic is handled in the view via @auth
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');


// --- AUTHENTICATED ROUTES (Login Required) ---
Route::middleware('auth')->group(function () {
    Route::get('/api/check-slots', [AppointmentConfigController::class, 'checkOccupancy']);
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // PROFILE ROUTES (Edit/Delete Account)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/password/change', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // CART ROUTES 
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{service}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    
    // APPOINTMENTS INDEX (Shared among all roles, but must be logged in)
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');

    // USER ONLY ROUTES
    Route::middleware('role:user')->group(function () {
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    });

    // STAFF & ADMIN ROUTES (Manage Services & Appointment Status)
    Route::middleware('role:staff,admin')->group(function () {
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status'); 

        Route::get('/admin/appointment-settings', [AppointmentConfigController::class, 'index'])->name('admin.appointment-settings');
        Route::put('/admin/appointment-settings', [AppointmentConfigController::class, 'update'])->name('admin.appointment-settings.update');
    });

    // ADMIN ONLY ROUTES (User Management)
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [AdminController::class, 'index']);
        Route::patch('/admin/users/{id}/staff', [AdminController::class, 'promote']);
        Route::patch('/admin/users/{id}/user', [AdminController::class, 'demote']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'destroy']);
    });
});

require __DIR__.'/auth.php';