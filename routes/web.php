<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    ServiceController,
    AppointmentController,
    AdminController,
    DependentController,
    AppointmentConfigController,
    BulkAppointmentController,
    DashboardController,
    HistoryController,
    NotificationController,
    ResultController,
    PaymentProviderController
};
use App\Http\Controllers\Workstation\{
    LaboratoryController,
    ImagingController,
    MedicalCertController
};

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () { 
    return view('welcome'); 
});

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

// Public route for clinical QR code verification (Route Model Binding)
Route::get('/verify-result', [ResultController::class, 'verifySearch'])->name('result.verify-search');

// Secured the public verification page with cryptographic signed middleware to block IDOR/URL-tampering [15]
Route::get('/verify-result/{appointment}', [ResultController::class, 'verifyPublic'])->name('result.verify-public')->middleware('signed');

// NEW: Secured public route for historical records verification, protected by cryptographic signatures [15]
Route::get('/verify-history/{user}', [ResultController::class, 'verifyHistoryPublic'])->name('history.verify-public')->middleware('signed');

// Academic Compliance & Legal Routes
Route::prefix('compliance')->group(function () {
    Route::get('/privacy-policy', function () { 
        return view('legal.privacy'); 
    })->name('legal.privacy');

    Route::get('/terms-of-service', function () { 
        return view('legal.terms'); 
    })->name('legal.terms');

    Route::get('/data-privacy-act', function () { 
        return view('legal.dpa'); 
    })->name('legal.dpa');

    Route::get('/cookie-settings', function () { 
        return view('legal.cookies'); 
    })->name('legal.cookies');
});

/*
|--------------------------------------------------------------------------
| 2. SHARED AUTHENTICATED ROUTES (All Roles)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Appointments Index
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');

    // Appointment Wizard (5-Page Booking Flow)
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');

    // Resubmit logic for Patients
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('/appointments/{appointment}/resubmit-batch', [ResultController::class, 'resubmitBatch'])->name('appointments.resubmit-batch');

    // Profile & Security
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password/change', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clearAll');

    // Clinical Archive View (Permission Handshake)
    Route::get('/history/{user?}', [HistoryController::class, 'index'])->name('patient.history');

    // Allowed both Patients and Clinical Staff to accept Handshake permissions securely
    Route::post('/history/accept/{user?}', [HistoryController::class, 'acceptRequest'])->name('history.accept');

    /** 
     * UNIFIED RESULT ACCESS:
     * Patients access directly; Staff go through the Log Gate.
     */
    Route::get('/appointments/{appointment}/result/{type}/{mode}', [ResultController::class, 'access'])->name('appointments.result.access');
    Route::post('/internal/appointment-log-access/{appointment}', [ResultController::class, 'logAccess'])->name('internal.logAccess');
});

/*
|--------------------------------------------------------------------------
| 3. PATIENT SPECIFIC ROUTES (Role: user)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::post('/dependents', [DependentController::class, 'store'])->name('dependents.store');
    Route::put('/dependents/{dependent}', [DependentController::class, 'update'])->name('dependents.update');
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy'])->name('dependents.destroy');

    // Wizard Submission
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');

    // Bulk Organization Booking (Standard Manual & Excel Parsing)
    Route::get('/bulk-appointment', [BulkAppointmentController::class, 'index'])->name('appointments.bulk');
    Route::post('/bulk-appointment/manual', [BulkAppointmentController::class, 'storeManual'])->name('appointments.bulk.manual');

    // Registered missing bulk template and excel parsing routes
    Route::get('/bulk-appointment/template/{type?}', [BulkAppointmentController::class, 'downloadTemplate'])->name('appointments.bulk.template');
    Route::post('/bulk-appointment/parse', [BulkAppointmentController::class, 'parseExcel'])->name('appointments.bulk.parse');

    // Historical Request
    Route::post('/history/request', [HistoryController::class, 'requestPermission'])->name('history.request');
});

/*
|--------------------------------------------------------------------------
| 4. CLINICAL PERSONNEL ROUTES (Role: staff, lab_tech, admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:staff,lab_tech,admin'])->group(function () {

    // Clinical Workflow Transitions & Manual Payment Verifications
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::patch('/appointments/{appointment}/mark-tested', [AppointmentController::class, 'markTested'])->name('appointments.tested');

    // Added missing route to confirm cashless transactions manually
    Route::post('/appointments/{appointment}/confirm-payment', [AppointmentController::class, 'confirmPayment'])->name('appointments.confirm-payment');

    // Route for Staff-Triggered Patient Handshake Permission Request
    Route::post('/history/staff-trigger/{user}', [HistoryController::class, 'staffTriggerRequest'])->name('history.staff-trigger');

    // RESULTS HUB (Central View/Modify)
    Route::get('/appointments/{appointment}/encode', [ResultController::class, 'hub'])->name('appointments.encode');

    // Clinical Schedule Configuration
    Route::get('/admin/appointment-settings', [AppointmentConfigController::class, 'index'])->name('admin.appointment-settings');
    Route::post('/admin/appointment-settings', [AppointmentConfigController::class, 'store'])->name('admin.appointment-settings.store');

    // Payment Gateways Management (Staff / Admin / Lab Tech)
    Route::get('/admin/payment-providers', [PaymentProviderController::class, 'index'])->name('admin.payment-providers.index');
    Route::post('/admin/payment-providers', [PaymentProviderController::class, 'store'])->name('admin.payment-providers.store');
    Route::put('/admin/payment-providers/{provider}', [PaymentProviderController::class, 'update'])->name('admin.payment-providers.update');
    Route::patch('/admin/payment-providers/{provider}/toggle', [PaymentProviderController::class, 'toggle'])->name('admin.payment-providers.toggle');
    Route::delete('/admin/payment-providers/{provider}', [PaymentProviderController::class, 'destroy'])->name('admin.payment-providers.destroy');

    // Service Catalog Management
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // WORKSTATIONS
    Route::prefix('workstation/{appointment}')->group(function () {
        Route::get('/lab', [LaboratoryController::class, 'index'])->name('workstation.lab');
        Route::post('/lab/save', [LaboratoryController::class, 'save'])->name('workstation.lab.save');

        Route::get('/medical', [MedicalCertController::class, 'index'])->name('workstation.med_cert');
        Route::post('/medical/save', [MedicalCertController::class, 'save'])->name('workstation.medical.save');

        Route::get('/radiology', [ImagingController::class, 'radioIndex'])->name('workstation.radiology');
        Route::post('/radiology/save', [ImagingController::class, 'radioSave'])->name('workstation.radiology.save');

        // Moved drugtest and drugtest/save inside the workstation prefix group for proper Route Model Binding
        Route::get('/drugtest', [ImagingController::class, 'drugIndex'])->name('workstation.drug');
        Route::post('/drugtest/save', [ImagingController::class, 'drugSave'])->name('workstation.drug.save');

        // Moved verify and return inside the workstation prefix group for proper Route Model Binding
        Route::post('/verify/{type}', [ResultController::class, 'verify'])->name('workstation.verify');
        Route::post('/return', [ResultController::class, 'return'])->name('workstation.return');
    });

    // Internal User Directory
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');

    // Route for Reason-Gated Patient Medical History View
    Route::get('/admin/users/{user}/history', [AdminController::class, 'patientHistory'])->name('admin.users.history');

    // Clinical Audit Trail Gate for Historical/Patient Archive Log Access
    Route::post('/internal/archive-log-access', [HistoryController::class, 'logAccess'])->name('internal.archiveLogAccess');

    // Digitized manual data import route
    Route::post('/history/{user}/import-manual', [HistoryController::class, 'saveManualData'])->name('history.save-manual');

    // Route for notifying the patient that their records have been successfully digitized
    Route::post('/history/{user}/notify-encoded', [HistoryController::class, 'notifyEncoded'])->name('history.notify-encoded');
});

/*
|--------------------------------------------------------------------------
| 5. SYSTEM ADMIN ONLY (Role: admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Audit Logs
    Route::get('/admin/audit-logs', [AdminController::class, 'viewLogs'])->name('admin.logs');

    // User Account Management
    Route::patch('/admin/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle');
    Route::patch('/admin/users/{user}/role', [AdminController::class, 'changeRole'])->name('admin.users.updateRole');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
});

// API Helper (Used by Appointment Wizard for AJAX slot fetching)
Route::get('/api/check-slots', [AppointmentConfigController::class, 'checkOccupancy']);

require __DIR__.'/auth.php';