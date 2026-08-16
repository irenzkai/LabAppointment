<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    AppointmentController,
    BulkAppointmentController,
    ResultController,
    ServiceController,
    DependentController,
    HistoryController,
    NotificationController,
    PaymentProviderController,
    AdminController,
    AppointmentConfigController,
    ProfileController
};
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Workstation\{
    LaboratoryController,
    ImagingController,
    MedicalCertController,
    CustomWorksheetController
};

/*
|--------------------------------------------------------------------------
| Public & Landing Routes
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

// Legal & Compliance Views
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/dpa', 'legal.dpa')->name('legal.dpa');
Route::view('/legal/cookies', 'legal.cookies')->name('legal.cookies');

// Public Clinical Verification Gateway
Route::get('/verify-result', [ResultController::class, 'verifySearch'])->name('result.verify-search');
Route::get('/verify-result/{appointment}', [ResultController::class, 'verifyPublic'])->name('result.verify-public')->middleware('signed');
Route::get('/verify-history/{user}', [ResultController::class, 'verifyHistoryPublic'])->name('history.verify-public')->middleware('signed');

// Public API endpoint for real-time slot checking in booking wizards
Route::get('/api/check-slots', [AppointmentConfigController::class, 'checkOccupancy'])->name('api.check-slots');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Patients & Staff)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'force.password'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications Management
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clearAll');

    // Clinical Services Catalog
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

    // Patient Appointment Management & Result File Access
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/resubmit', [AppointmentController::class, 'editResubmit'])->name('appointments.resubmit');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/soft-delete', [AppointmentController::class, 'softDelete'])->name('appointments.soft-delete');
    Route::post('/appointments/{appointment}/forward-email', [ResultController::class, 'forwardToEmail'])->name('appointments.forward-email');

    // Accessible by both Patients (for their own records) and Staff
    Route::get('/appointments/{appointment}/result/{type}/{mode}', [ResultController::class, 'access'])->name('appointments.result.access');

    // Bulk Appointment Wizard
    Route::get('/appointments/bulk', [BulkAppointmentController::class, 'index'])->name('appointments.bulk');
    Route::post('/appointments/bulk/manual', [BulkAppointmentController::class, 'storeManual'])->name('appointments.bulk.manual');
    Route::post('/appointments/bulk/excel', [BulkAppointmentController::class, 'storeExcel'])->name('appointments.bulk.excel');
    Route::post('/appointments/bulk/parse-excel', [BulkAppointmentController::class, 'parseExcel'])->name('appointments.bulk.parse-excel');
    Route::get('/appointments/bulk/template/{type?}', [BulkAppointmentController::class, 'downloadTemplate'])->name('appointments.bulk.template');

    // Family Dependents
    Route::post('/dependents', [DependentController::class, 'store'])->name('dependents.store');
    Route::put('/dependents/{dependent}', [DependentController::class, 'update'])->name('dependents.update');
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy'])->name('dependents.destroy');
    Route::post('/dependents/{id}/restore', [DependentController::class, 'restore'])->name('dependents.restore');

    // User Profile & Account Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('profile.password.update');

    // Medical History & Legacy Archive
    Route::get('/patient-history/{user?}', [HistoryController::class, 'index'])->name('patient.history');
    Route::post('/patient-history/request', [HistoryController::class, 'requestPermission'])->name('history.request');
    Route::post('/patient-history/staff-trigger/{user}', [HistoryController::class, 'staffTriggerRequest'])->name('history.staff-trigger');
    Route::post('/patient-history/accept/{user?}', [HistoryController::class, 'acceptRequest'])->name('history.accept');
    Route::post('/patient-history/save-manual/{user}', [HistoryController::class, 'saveManualData'])->name('history.save-manual');
    Route::post('/internal/archive-log-access', [HistoryController::class, 'logAccess'])->name('history.log-access');
    Route::post('/patient-history/notify-encoded/{user}', [HistoryController::class, 'notifyEncoded'])->name('history.notify-encoded');

    /*
    |--------------------------------------------------------------------------
    | Internal Personnel Controls (Staff, Lab Tech, Admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['can:isStaff'])->group(function () {

        // Master Queue Status & Payment Updates
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::post('/appointments/{appointment}/confirm-payment', [AppointmentController::class, 'confirmPayment'])->name('appointments.confirm-payment');
        Route::post('/appointments/{appointment}/invalid-payment', [AppointmentController::class, 'markPaymentInvalid'])->name('appointments.invalid-payment');
        Route::post('/appointments/{appointment}/refund', [AppointmentController::class, 'confirmRefund'])->name('appointments.refund');
        Route::patch('/appointments/{appointment}/mark-tested', [AppointmentController::class, 'markTested'])->name('appointments.tested');

        // Results Hub & Demographics Revision
        Route::get('/appointments/{appointment}/encode', [ResultController::class, 'hub'])->name('appointments.encode');
        Route::get('/appointments/{appointment}/edit-details', [ResultController::class, 'editDemographics'])->name('appointments.edit-details');
        Route::put('/internal/appointment-details/{appointment}', [ResultController::class, 'reviseDemographics'])->name('internal.appointment-details.update');
        Route::post('/internal/appointment-log-access/{appointment}', [ResultController::class, 'logAccess'])->name('appointments.log-access');

        // Service Catalog Admin Controls
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
        Route::post('/services/{id}/restore', [ServiceController::class, 'restore'])->name('services.restore');

        // Dedicated Clinical Workstation Pages
        Route::get('/internal/workstation/lab/{appointment}', [LaboratoryController::class, 'index'])->name('workstation.lab');
        Route::post('/internal/workstation/lab/{appointment}/save', [LaboratoryController::class, 'save'])->name('workstation.lab.save');

        Route::get('/internal/workstation/radiology/{appointment}', [ImagingController::class, 'radioIndex'])->name('workstation.radiology');
        Route::post('/internal/workstation/radiology/{appointment}/save', [ImagingController::class, 'radioSave'])->name('workstation.radiology.save');

        Route::get('/internal/workstation/drug/{appointment}', [ImagingController::class, 'drugIndex'])->name('workstation.drug');
        Route::post('/internal/workstation/drug/{appointment}/save', [ImagingController::class, 'drugSave'])->name('workstation.drug.save');

        Route::get('/internal/workstation/medical/{appointment}', [MedicalCertController::class, 'index'])->name('workstation.med_cert');
        Route::post('/internal/workstation/medical/{appointment}/save', [MedicalCertController::class, 'save'])->name('workstation.medical.save');

        // Dedicated Custom Workstation Page Routes
        Route::get('/internal/workstation/custom/{appointment}/{id}', [CustomWorksheetController::class, 'index'])->name('workstation.custom');
        Route::post('/internal/workstation/custom/{appointment}/{id}/save', [CustomWorksheetController::class, 'save'])->name('workstation.custom.save');
        Route::post('/internal/workstation/custom/{appointment}/{id}/verify', [CustomWorksheetController::class, 'verify'])->name('workstation.custom.verify');
        Route::post('/internal/workstation/custom/{appointment}/{id}/return', [CustomWorksheetController::class, 'return'])->name('workstation.custom.return');
        Route::delete('/internal/workstation/custom/{appointment}/{id}', [CustomWorksheetController::class, 'destroy'])->name('workstation.custom.destroy');

        // Hub Workstation Modals & Actions
        Route::post('/internal/workstation/add/{appointment}', [ResultController::class, 'addWorkstation'])->name('workstation.add');
        Route::post('/internal/workstation/verify/{appointment}/{type}', [ResultController::class, 'verify'])->name('workstation.verify');
        Route::post('/internal/workstation/return/{appointment}', [ResultController::class, 'return'])->name('workstation.return');
        Route::delete('/internal/workstation/original/{appointment}/{type}', [ResultController::class, 'destroyOriginalWorkstation'])->name('workstation.destroy-original');
    });

    /*
    |--------------------------------------------------------------------------
    | System Administrator Routes (Gate: isAdmin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['can:isAdmin'])->group(function () {

        // User Directory Management
        Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');
        Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::get('/admin/users/{user}/history', [AdminController::class, 'patientHistory'])->name('admin.users.history');

        // Audit Logs
        Route::get('/admin/logs', [AdminController::class, 'viewLogs'])->name('admin.logs');

        // Clinical Schedule Configuration
        Route::get('/admin/appointment-settings', [AppointmentConfigController::class, 'index'])->name('admin.appointment-settings');
        Route::post('/admin/appointment-settings', [AppointmentConfigController::class, 'store'])->name('admin.appointment-settings.store');

        // Payment Gateway Configuration
        Route::get('/admin/payment-providers', [PaymentProviderController::class, 'index'])->name('admin.payment-providers.index');
        Route::post('/admin/payment-providers', [PaymentProviderController::class, 'store'])->name('admin.payment-providers.store');
        Route::put('/admin/payment-providers/{provider}', [PaymentProviderController::class, 'update'])->name('admin.payment-providers.update');
        Route::patch('/admin/payment-providers/{provider}/toggle', [PaymentProviderController::class, 'toggle'])->name('admin.payment-providers.toggle');
        Route::delete('/admin/payment-providers/{provider}', [PaymentProviderController::class, 'destroy'])->name('admin.payment-providers.destroy');
        Route::post('/admin/payment-providers/{id}/restore', [PaymentProviderController::class, 'restore'])->name('admin.payment-providers.restore');
    });

});

// Auth scaffolding routes (Register, Login, Password Reset, Email Verification)
require __DIR__.'/auth.php';