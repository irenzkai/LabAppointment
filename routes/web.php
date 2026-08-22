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
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/dpa', 'legal.dpa')->name('legal.dpa');
Route::view('/legal/cookies', 'legal.cookies')->name('legal.cookies');
Route::get('/verify-result', [ResultController::class, 'verifySearch'])->name('result.verify-search');
Route::get('/verify-result/{appointment}', [ResultController::class, 'verifyPublic'])->name('result.verify-public')->middleware('signed');
Route::get('/verify-history/{user}', [ResultController::class, 'verifyHistoryPublic'])->name('history.verify-public')->middleware('signed');
Route::get('/api/check-slots', [AppointmentConfigController::class, 'checkOccupancy'])->name('api.check-slots');

/*
|--------------------------------------------------------------------------
| Authenticated Base Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'force.password'])->group(function () {
    Route::get('/main', [DashboardController::class, 'index'])->name('main');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clearAll');
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}/resubmit', [AppointmentController::class, 'editResubmit'])->name('appointments.resubmit');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/soft-delete', [AppointmentController::class, 'softDelete'])->name('appointments.soft-delete');
    Route::post('/appointments/{appointment}/forward-email', [ResultController::class, 'forwardToEmail'])->name('appointments.forward-email');
    Route::get('/appointments/{appointment}/result/{type}/{mode}', [ResultController::class, 'access'])->name('appointments.result.access');
    Route::get('/appointments/bulk', [BulkAppointmentController::class, 'index'])->name('appointments.bulk');
    Route::post('/appointments/bulk/manual', [BulkAppointmentController::class, 'storeManual'])->name('appointments.bulk.manual');
    Route::post('/appointments/bulk/excel', [BulkAppointmentController::class, 'storeExcel'])->name('appointments.bulk.excel');
    Route::post('/appointments/bulk/parse-excel', [BulkAppointmentController::class, 'parseExcel'])->name('appointments.bulk.parse-excel');
    Route::get('/appointments/bulk/template/{type?}', [BulkAppointmentController::class, 'downloadTemplate'])->name('appointments.bulk.template');
    Route::get('/dependents/create', [DependentController::class, 'create'])->name('dependents.create');
    Route::post('/dependents', [DependentController::class, 'store'])->name('dependents.store');
    Route::get('/dependents/{dependent}/edit', [DependentController::class, 'edit'])->name('dependents.edit');
    Route::put('/dependents/{dependent}', [DependentController::class, 'update'])->name('dependents.update');
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy'])->name('dependents.destroy');
    Route::post('/dependents/{id}/restore', [DependentController::class, 'restore'])->name('dependents.restore');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('profile.password.update');
    Route::get('/patient-history/{user?}', [HistoryController::class, 'index'])->name('patient.history');
    Route::post('/patient-history/request', [HistoryController::class, 'requestPermission'])->name('history.request');
    Route::post('/patient-history/staff-trigger/{user}', [HistoryController::class, 'staffTriggerRequest'])->name('history.staff-trigger');
    Route::post('/patient-history/accept/{user?}', [HistoryController::class, 'acceptRequest'])->name('history.accept');
    Route::post('/patient-history/save-manual/{user}', [HistoryController::class, 'saveManualData'])->name('history.save-manual');
    Route::post('/internal/archive-log-access', [HistoryController::class, 'logAccess'])->name('history.log-access');
    Route::post('/patient-history/notify-encoded/{user}', [HistoryController::class, 'notifyEncoded'])->name('history.notify-encoded');

    /*
    |--------------------------------------------------------------------------
    | Staff Internal Management Controls
    |--------------------------------------------------------------------------
    */
    Route::middleware(['can:isStaff'])->prefix('staff')->group(function () {
        Route::get('/panel', [DashboardController::class, 'staffPanel'])->name('staff.panel');
        Route::get('/services/manage', [ServiceController::class, 'manage'])->name('services.manage');
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
        Route::post('/appointments/{appointment}/confirm-payment', [AppointmentController::class, 'confirmPayment'])->name('appointments.confirm-payment');
        Route::post('/appointments/{appointment}/invalid-payment', [AppointmentController::class, 'markPaymentInvalid'])->name('appointments.invalid-payment');
        Route::post('/appointments/{appointment}/refund', [AppointmentController::class, 'confirmRefund'])->name('appointments.refund');
        Route::patch('/appointments/{appointment}/mark-tested', [AppointmentController::class, 'markTested'])->name('appointments.tested');
        Route::get('/appointments/{appointment}/encode', [ResultController::class, 'hub'])->name('appointments.encode');
        Route::get('/appointments/{appointment}/edit-details', [ResultController::class, 'editDemographics'])->name('appointments.edit-details');
        Route::put('/internal/appointment-details/{appointment}', [ResultController::class, 'reviseDemographics'])->name('internal.appointment-details.update');
        Route::post('/internal/appointment-log-access/{appointment}', [ResultController::class, 'logAccess'])->name('appointments.log-access');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
        Route::post('/services/{id}/restore', [ServiceController::class, 'restore'])->name('services.restore');
        Route::get('/workstation/lab/{appointment}', [LaboratoryController::class, 'index'])->name('workstation.lab');
        Route::post('/workstation/lab/{appointment}/save', [LaboratoryController::class, 'save'])->name('workstation.lab.save');
        Route::get('/workstation/radiology/{appointment}', [ImagingController::class, 'radioIndex'])->name('workstation.radiology');
        Route::post('/workstation/radiology/{appointment}/save', [ImagingController::class, 'radioSave'])->name('workstation.radiology.save');
        Route::get('/workstation/drug/{appointment}', [ImagingController::class, 'drugIndex'])->name('workstation.drug');
        Route::post('/workstation/drug/{appointment}/save', [ImagingController::class, 'drugSave'])->name('workstation.drug.save');
        Route::get('/workstation/medical/{appointment}', [MedicalCertController::class, 'index'])->name('workstation.med_cert');
        Route::post('/workstation/medical/{appointment}/save', [MedicalCertController::class, 'save'])->name('workstation.medical.save');
        Route::get('/workstation/custom/{appointment}/{id}', [CustomWorksheetController::class, 'index'])->name('workstation.custom');
        Route::post('/workstation/custom/{appointment}/{id}/save', [CustomWorksheetController::class, 'save'])->name('workstation.custom.save');
        Route::post('/workstation/custom/{appointment}/{id}/verify', [CustomWorksheetController::class, 'verify'])->name('workstation.custom.verify');
        Route::post('/workstation/custom/{appointment}/{id}/return', [CustomWorksheetController::class, 'return'])->name('workstation.custom.return');
        Route::delete('/workstation/custom/{appointment}/{id}', [CustomWorksheetController::class, 'destroy'])->name('workstation.custom.destroy');
        Route::post('/workstation/add/{appointment}', [ResultController::class, 'addWorkstation'])->name('workstation.add');
        Route::post('/workstation/verify/{appointment}/{type}', [ResultController::class, 'verify'])->name('workstation.verify');
        Route::post('/workstation/return/{appointment}', [ResultController::class, 'return'])->name('workstation.return');
        Route::delete('/workstation/original/{appointment}/{type}', [ResultController::class, 'destroyOriginalWorkstation'])->name('workstation.destroy-original');
    });

    /*
    |--------------------------------------------------------------------------
    | Staff & Admin Shared Settings / Gateways (Gate: isStaff)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['can:isStaff'])->prefix('admin')->group(function () {
        // Schedule Editor
        Route::get('/appointment-settings', [AppointmentConfigController::class, 'index'])->name('admin.appointment-settings');
        Route::post('/appointment-settings', [AppointmentConfigController::class, 'store'])->name('admin.appointment-settings.store');

        // Payment Gateway Configuration
        Route::get('/payment-providers', [PaymentProviderController::class, 'index'])->name('admin.payment-providers.index');
        Route::post('/payment-providers', [PaymentProviderController::class, 'store'])->name('admin.payment-providers.store');
        Route::put('/payment-providers/{provider}', [PaymentProviderController::class, 'update'])->name('admin.payment-providers.update');
        Route::patch('/payment-providers/{provider}/toggle', [PaymentProviderController::class, 'toggle'])->name('admin.payment-providers.toggle');
        Route::delete('/payment-providers/{provider}', [PaymentProviderController::class, 'destroy'])->name('admin.payment-providers.destroy');
        Route::post('/payment-providers/{id}/restore', [PaymentProviderController::class, 'restore'])->name('admin.payment-providers.restore');
    });

    /*
    |--------------------------------------------------------------------------
    | System Administrator Routes (Gate: isAdmin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['can:isAdmin'])->prefix('admin')->group(function () {
        Route::get('/panel', [DashboardController::class, 'adminPanel'])->name('admin.panel');

        // User Management Routes
        Route::get('/users', [AdminController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('admin.users.toggle-status');
        Route::post('/users/{id}/send-verification', [AdminController::class, 'sendVerificationEmail'])->name('admin.users.send-verification');
        Route::get('/users/{id}/history', [AdminController::class, 'patientHistory'])->name('admin.users.history');

        // Admin Dedicated Full-Page Dependent Management Routes
        Route::get('/users/{user}/dependents/create', [AdminController::class, 'createDependentForUser'])->name('admin.users.dependents.create');
        Route::post('/users/{user}/dependents', [AdminController::class, 'storeDependentForUser'])->name('admin.users.dependents.store');
        Route::get('/users/{user}/dependents/{dependent}/edit', [AdminController::class, 'editDependentForUser'])->name('admin.users.dependents.edit');
        Route::put('/users/{user}/dependents/{dependent}', [AdminController::class, 'updateDependentForUser'])->name('admin.users.dependents.update');
        Route::delete('/users/{user}/dependents/{dependent}', [AdminController::class, 'destroyDependentForUser'])->name('admin.users.dependents.destroy');
        Route::post('/users/{user}/dependents/{id}/restore', [AdminController::class, 'restoreDependentForUser'])->name('admin.users.dependents.restore');
        Route::post('/users/{user}/dependents/{dependent}/promote', [AdminController::class, 'promoteDependentForUser'])->name('admin.users.dependents.promote');

        // Audit Logs
        Route::get('/logs', [AdminController::class, 'viewLogs'])->name('admin.logs');

        // Reports
        Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
        Route::get('/reports/export', [AdminController::class, 'exportReport'])->name('admin.reports.export');
    });
});

require __DIR__.'/auth.php';