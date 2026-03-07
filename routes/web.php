<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard - Redirect based on login
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // PROFILE ROUTES (User Edit/Delete Account)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SHARED ROUTES (Everyone can see)
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');

    // USER ONLY ROUTES
    Route::middleware('role:user')->group(function () {
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    });

    // STAFF & ADMIN ROUTES (Manage Services & Appointments)
        Route::middleware(['auth', 'role:staff,admin'])->group(function () {
            Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
            Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
            Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
            Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
            Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status'); 
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