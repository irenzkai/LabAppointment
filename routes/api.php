<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Models\Service;
use App\Models\PaymentProvider;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DependentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\AppointmentConfigController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;

/*
|--------------------------------------------------------------------------
| Public Mobile API Routes
|--------------------------------------------------------------------------
*/

// Server Health Check / Connectivity Test
Route::get('/ping', function () {
    return response()->json([
        'status'    => 'online',
        'app'       => 'Medscreen Laboratory API',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Slot occupancy check (for booking wizard)
Route::get('/check-slots', [AppointmentConfigController::class, 'checkOccupancy']);

// Public catalog of services
Route::get('/services', function () {
    $services = Service::where('is_available', true)->orderBy('name')->get();
    return response()->json($services);
});

// Public payment providers (QRs)
Route::get('/payment-providers', function () {
    $providers = PaymentProvider::where('is_active', true)->get();
    return response()->json($providers);
});

/**
 * MEDIA & STORAGE STREAMER
 * Serves public disk files (QRs, receipts, referrals, scans) directly through Laravel.
 */
Route::get('/media/{path}', function ($path) {
    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'File not found on laboratory storage.'], 404);
    }
    return Storage::disk('public')->response($path);
})->where('path', '.*');

// Mobile Authentication: Login
Route::post('/login', function (Request $request) {
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = User::withTrashed()->where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid email or password.'], 401);
    }

    if ($user->trashed()) {
        session()->put('reactivate_user_id', $user->id);
        return response()->json([
            'deactivated' => true,
            'message'     => 'Your account is currently deactivated. You must reactivate it to log in.',
        ], 422);
    }

    if (!$user->is_active) {
        return response()->json(['message' => 'This account has been disabled by the administrator.'], 403);
    }

    $token = $user->createToken('mobile-patient-token')->plainTextToken;

    return response()->json([
        'token'      => $token,
        'user'       => $user,
        'unverified' => is_null($user->email_verified_at),
    ]);
});

// Mobile Authentication: Register
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'first_name' => 'required|string|max:60',
        'middle_name' => 'nullable|string|max:60',
        'last_name' => 'required|string|max:60',
        'suffix' => 'nullable|string|max:10',
        'birthdate' => 'required|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
        'sex' => 'required|string|in:Male,Female',
        'province' => 'required|string',
        'city' => 'required|string',
        'barangay' => 'required|string',
        'street' => 'required|string|max:255',
        'email' => 'required|email|max:191|unique:users,email',
        'phone' => 'required|string|regex:/^09\d{9}$/',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $fName = mb_strtoupper(trim($request->first_name), 'UTF-8');
    $mName = ($request->middle_name && mb_strtoupper(trim($request->middle_name), 'UTF-8') !== 'N/A')
        ? mb_strtoupper(trim($request->middle_name), 'UTF-8')
        : 'N/A';
    $lName = mb_strtoupper(trim($request->last_name), 'UTF-8');
    $suffix = $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : '';

    $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
    if (!empty($suffix)) {
        $displayName .= " {$suffix}";
    }

    $user = User::create([
        'first_name'        => $fName,
        'middle_name'       => $mName,
        'last_name'         => $lName,
        'suffix'            => $suffix ?: null,
        'name'              => $displayName,
        'email'             => $request->email,
        'phone'             => $request->phone,
        'birthdate'         => $request->birthdate,
        'sex'               => $request->sex,
        'street'            => mb_strtoupper(trim($request->street), 'UTF-8'),
        'barangay'          => mb_strtoupper(trim($request->barangay), 'UTF-8'),
        'city'              => mb_strtoupper(trim($request->city), 'UTF-8'),
        'province'          => mb_strtoupper(trim($request->province), 'UTF-8'),
        'password'          => Hash::make($request->password),
        'role'              => 'user',
        'is_active'         => true,
        'email_verified_at' => null,
    ]);

    $token = $user->createToken('mobile-patient-token')->plainTextToken;

    return response()->json([
        'token'      => $token,
        'user'       => $user,
        'unverified' => true,
        'message'    => 'Registration completed. Please verify your email.',
    ]);
});

/**
 * Mobile Forgot Password Route
 */
Route::post('/forgot-password', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
    ]);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status == Password::RESET_LINK_SENT) {
        return response()->json([
            'success' => true,
            'status'  => __($status),
            'message' => 'We have emailed your password reset link!',
        ], 200);
    }

    return response()->json([
        'success' => false,
        'message' => __($status),
    ], 422);
});

// Reactivation OTP verification
Route::post('/reactivate/resend-otp', [EmailVerificationNotificationController::class, 'sendReactivationOtp']);
Route::post('/reactivate/verify-otp', [VerifyEmailController::class, 'verifyReactivationOtp']);

/*
|--------------------------------------------------------------------------
| Protected Mobile API Routes (Requires Bearer Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Current user profile
    Route::get('/profile', function (Request $request) {
        return response()->json(['user' => $request->user()]);
    });

    Route::patch('/profile', function (Request $request) {
        $user = $request->user();
        $user->update($request->only([
            'first_name', 'middle_name', 'last_name', 'suffix',
            'email', 'phone', 'birthdate', 'sex', 'street',
            'barangay', 'city', 'province'
        ]));
        return response()->json(['success' => true, 'user' => $user]);
    });

    Route::delete('/profile', function (Request $request) {
        $request->validate(['password' => 'required']);
        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['success' => true]);
    });

    // Verification OTP endpoints
    Route::post('/email/verification-otp', [EmailVerificationNotificationController::class, 'sendOtp']);
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store']);
    Route::post('/verify-otp', [VerifyEmailController::class, 'verifyOtp']);

    // Logout
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    });

    // Appointments Endpoints
    Route::get('/appointments', function (Request $request) {
        $user = $request->user();

        $self = \App\Models\Appointment::with(['services', 'dependent', 'result', 'user'])
            ->where('user_id', $user->id)
            ->whereNull('dependent_id')
            ->whereNull('batch_id')
            ->where('deleted_by_patient', false)
            ->latest()
            ->get();

        $dependents = \App\Models\Appointment::with(['services', 'dependent', 'result', 'user'])
            ->where('user_id', $user->id)
            ->whereNotNull('dependent_id')
            ->where('deleted_by_patient', false)
            ->latest()
            ->get();

        return response()->json([
            'self'       => $self,
            'dependents' => $dependents,
        ]);
    });

    Route::post('/appointments', [AppointmentController::class, 'store']);
    
    // FIXED: Accept both POST and PUT methods to allow method spoofing (_method=PUT) on resubmission
    Route::match(['post', 'put'], '/appointments/{appointment}', [AppointmentController::class, 'update']);
    
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{appointment}/soft-delete', [AppointmentController::class, 'softDelete']);
    Route::post('/appointments/{appointment}/forward-email', [ResultController::class, 'forwardToEmail']);

    // Mobile In-App Result Access
    Route::get('/appointments/{appointment}/result/{type}/{mode}', [ResultController::class, 'access']);

    // Dependents Endpoints
    Route::get('/dependents', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'dependents' => $user->dependents()->get(),
            'archived'   => $user->dependents()->onlyTrashed()->get(),
        ]);
    });

    Route::post('/dependents', [DependentController::class, 'store']);
    Route::put('/dependents/{dependent}', [DependentController::class, 'update']);
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy']);
    Route::post('/dependents/{id}/restore', [DependentController::class, 'restore']);

    // History Endpoints
    Route::get('/patient-history', function (Request $request) {
        $user = $request->user();
        $labHistory = \App\Models\LaboratoryHistory::firstOrCreate(['user_id' => $user->id]);
        $appointments = \App\Models\Appointment::with(['services', 'result', 'dependent', 'user'])
            ->where('user_id', $user->id)
            ->where('deleted_by_patient', false)
            ->latest()
            ->get();

        $recordsModels = \App\Models\LaboratoryHistoryRecord::whereHas('laboratoryHistory', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->with(['scans', 'procedures'])
            ->latest('date_of_record')
            ->get();

        $existingRecords = $recordsModels->map(function ($r) {
            return [
                'id'              => $r->id,
                'date_of_record'  => $r->date_of_record ? $r->date_of_record->format('Y-m-d') : '',
                'requested_by'    => $r->requested_by,
                'patient_name'    => $r->patient_name,
                'age'             => $r->age,
                'sex'             => $r->sex,
                'address'         => $r->patient_address,
                'tests_requested' => $r->procedures->pluck('procedure_name')->toArray(),
                'scans'           => $r->scans->map(fn($s) => [
                    'label'          => $s->label,
                    'file_path'      => $s->file_path,
                    'certificate_no' => $s->certificate_no ?? null,
                ])->toArray(),
            ];
        });

        return response()->json([
            'labHistory'      => $labHistory,
            'appointments'    => $appointments,
            'existingRecords' => $existingRecords,
        ]);
    });

    Route::post('/patient-history/request', [HistoryController::class, 'requestPermission']);
    Route::post('/patient-history/accept', [HistoryController::class, 'acceptRequest']);

    // Notifications
    Route::get('/notifications', function (Request $request) {
        return response()->json($request->user()->notifications()->take(20)->get());
    });

    Route::get('/notifications/{id}/read', function (Request $request, $id) {
        $notif = $request->user()->notifications()->findOrFail($id);
        $notif->markAsRead();
        return response()->json(['success' => true]);
    });

    Route::get('/notifications/clear-all', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });
});