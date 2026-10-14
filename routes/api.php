<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\User;
use App\Models\Service;
use App\Models\PaymentProvider;
use App\Models\ActivityLog;
use App\Models\LaboratoryHistory;
use App\Notifications\AppointmentNotification;
use App\Events\NotificationSent;
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
        'status' => 'online',
        'app' => 'Medscreen Laboratory API',
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

// Mobile Authentication: Login (Supports Verified Email or Verified Phone)
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|string',
        'password' => 'required|string',
    ]);

    $rawInput = trim((string) $request->input('email', $request->input('phone', '')));
    $cleanDigits = preg_replace('/[\s\-\(\)]/', '', $rawInput);

    // Identify if the input is a Philippine mobile number
    $isPhone = !str_contains($rawInput, '@') && preg_match('/^(\+?639\d{9}|639\d{9}|09\d{9}|9\d{9})$/', $cleanDigits);

    if ($isPhone) {
        $digits = preg_replace('/[^0-9]/', '', $cleanDigits);
        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            $normalizedPhone = '09' . substr($digits, 3);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $normalizedPhone = '09' . $digits;
        } else {
            $normalizedPhone = $digits;
        }

        $user = User::where('phone', $normalizedPhone)->first();
        $credentialType = 'phone';
    } else {
        $normalizedEmail = strtolower($rawInput);
        $user = User::where('email', $normalizedEmail)->first();
        $credentialType = 'email';
    }

    // Check deactivated (soft-deleted) accounts if no active user is found
    if (!$user) {
        if ($isPhone) {
            $trashedUser = User::onlyTrashed()->where('phone', $normalizedPhone)->first();
        } else {
            $trashedUser = User::onlyTrashed()->where('email', $normalizedEmail)->first();
        }

        if ($trashedUser && Hash::check($request->password, $trashedUser->password)) {
            // Conflict check: if the deactivated account's credentials are verified on another active account
            $emailConflict = User::where('id', '!=', $trashedUser->id)
                ->where('email', $trashedUser->email)
                ->whereNotNull('email_verified_at')
                ->exists();

            $phoneConflict = !empty($trashedUser->phone) && User::where('id', '!=', $trashedUser->id)
                ->where('phone', $trashedUser->phone)
                ->whereNotNull('phone_verified_at')
                ->exists();

            if ($emailConflict && $phoneConflict) {
                return response()->json([
                    'message' => 'This account has been permanently retired because its email address and phone number are already verified on another active account.'
                ], 422);
            }

            if ($isPhone && $phoneConflict) {
                return response()->json([
                    'message' => 'This account cannot be reactivated with this phone number because it has already been claimed and verified by another active account.'
                ], 422);
            }

            if (!$isPhone && $emailConflict) {
                return response()->json([
                    'message' => 'This account cannot be reactivated with this email address because it has already been claimed and verified by another active account.'
                ], 422);
            }

            // No conflict: proceed to dispatch reactivation OTP
            Cache::put("reactivate_ip_{$request->ip()}", $trashedUser->id, now()->addMinutes(30));
            app(EmailVerificationNotificationController::class)->sendReactivationOtp($request);

            return response()->json([
                'deactivated' => true,
                'email' => $trashedUser->email,
                'message' => 'Your account is currently deactivated. A verification code has been dispatched to your email.',
            ], 422);
        }

        return response()->json(['message' => 'Invalid email/phone or password.'], 401);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid email/phone or password.'], 401);
    }

    if (!$user->is_active) {
        return response()->json(['message' => 'This account has been disabled by the administrator.'], 403);
    }

    // Enforce verified credentials rule:
    // Only the verified credential allows login
    if ($credentialType === 'phone') {
        if (is_null($user->phone_verified_at)) {
            if (!is_null($user->email_verified_at)) {
                return response()->json([
                    'message' => 'This phone number is unverified and cannot be used to log in. Please sign in using your verified email address.'
                ], 422);
            } else {
                return response()->json([
                    'message' => 'This phone number is unverified and cannot be used to log in. Please sign in using your registered email address.'
                ], 422);
            }
        }
    } else {
        if (is_null($user->email_verified_at)) {
            if (!is_null($user->phone_verified_at)) {
                return response()->json([
                    'message' => 'This email address is unverified. Please sign in using your verified mobile phone number.'
                ], 422);
            }
        }
    }

    $token = $user->createToken('mobile-patient-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'unverified' => ! $user->hasVerifiedEmail(),
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
        'first_name' => $fName,
        'middle_name' => $mName,
        'last_name' => $lName,
        'suffix' => $suffix ?: null,
        'name' => $displayName,
        'email' => strtolower(trim($request->email)),
        'phone' => $request->phone,
        'birthdate' => $request->birthdate,
        'sex' => $request->sex,
        'street' => mb_strtoupper(trim($request->street), 'UTF-8'),
        'barangay' => mb_strtoupper(trim($request->barangay), 'UTF-8'),
        'city' => mb_strtoupper(trim($request->city), 'UTF-8'),
        'province' => mb_strtoupper(trim($request->province), 'UTF-8'),
        'password' => Hash::make($request->password),
        'role' => 'user',
        'is_active' => true,
        'email_verified_at' => null,
        'phone_verified_at' => null,
    ]);

    $token = $user->createToken('mobile-patient-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'unverified' => true,
        'message' => 'Registration completed. Please verify your email.',
    ]);
});

/**
 * Mobile Forgot Password Route (Requires Verified Email)
 */
Route::post('/forgot-password', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
    ]);

    $targetEmail = strtolower(trim($request->email));
    $user = User::where('email', $targetEmail)->first();

    if ($user && is_null($user->email_verified_at)) {
        return response()->json([
            'success' => false,
            'message' => 'Password reset links can only be sent to verified email addresses.',
        ], 422);
    }

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status == Password::RESET_LINK_SENT) {
        return response()->json([
            'success' => true,
            'status' => __($status),
            'message' => 'We have emailed your password reset link!',
        ], 200);
    }

    return response()->json([
        'success' => false,
        'message' => __($status),
    ], 422);
});

// Reactivation OTP verification (Supports both Web and Mobile API)
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

        // Conflict check for email
        if ($request->filled('email')) {
            $newEmail = strtolower(trim($request->email));
            $emailConflict = User::where('id', '!=', $user->id)
                ->where('email', $newEmail)
                ->whereNotNull('email_verified_at')
                ->exists();

            if ($emailConflict) {
                return response()->json([
                    'message' => 'This email address has already been claimed and verified by another active account.'
                ], 422);
            }
        }

        // Conflict check for phone
        if ($request->filled('phone')) {
            $digits = preg_replace('/[^0-9]/', '', $request->phone);
            if (str_starts_with($digits, '639') && strlen($digits) === 12) {
                $digits = '09' . substr($digits, 3);
            } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
                $digits = '09' . $digits;
            }
            $newPhone = $digits;

            $phoneConflict = User::where('id', '!=', $user->id)
                ->where('phone', $newPhone)
                ->whereNotNull('phone_verified_at')
                ->exists();

            if ($phoneConflict) {
                return response()->json([
                    'message' => 'This mobile phone number has already been claimed and verified by another active account.'
                ], 422);
            }
        }

        $emailChanged = $request->filled('email') && strtolower(trim($request->email)) !== strtolower($user->email);
        $phoneChanged = $request->filled('phone') && isset($newPhone) && $newPhone !== $user->phone;

        $updateData = $request->only([
            'first_name', 'middle_name', 'last_name', 'suffix',
            'email', 'phone', 'birthdate', 'sex', 'street',
            'barangay', 'city', 'province'
        ]);

        if (isset($newPhone)) {
            $updateData['phone'] = $newPhone;
        }

        $user->update($updateData);

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
            Cache::forget("email_otp_{$user->id}");
            Cache::forget("email_otp_addr_{$user->id}");
        }

        if ($phoneChanged) {
            $user->phone_verified_at = null;
            $user->save();
            Cache::forget("sms_otp_{$user->id}");
            Cache::forget("sms_otp_phone_{$user->id}");
        }

        return response()->json(['success' => true, 'user' => $user->fresh()]);
    });

    // Update password
    Route::put('/profile/password', function (Request $request) {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', PasswordRule::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::record('PASSWORD UPDATED', 'User updated their account password.', $request->user()->name);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    });

    // Voluntary Account Deactivation: unsets verification to release credentials
    Route::delete('/profile', function (Request $request) {
        $request->validate(['password' => 'required']);
        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        // Unverify credentials so they are not locked
        $user->email_verified_at = null;
        $user->phone_verified_at = null;
        $user->save();

        Cache::forget("email_otp_{$user->id}");
        Cache::forget("email_otp_addr_{$user->id}");
        Cache::forget("sms_otp_{$user->id}");
        Cache::forget("sms_otp_phone_{$user->id}");

        ActivityLog::record('ACCOUNT DELETED', 'User voluntarily deactivated their account via Mobile API', $user->name);

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['success' => true]);
    });

    // Verification OTP endpoints
    Route::post('/email/verification-otp', [EmailVerificationNotificationController::class, 'sendOtp']);
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store']);
    Route::post('/sms/verification-otp', [VerifyEmailController::class, 'sendSmsOtp']);
    Route::post('/verify-otp', [VerifyEmailController::class, 'verifyOtp']);

    // Change unverified email address
    Route::post('/email/change', function (Request $request) {
        $user = $request->user();
        $request->validate([
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
                'regex:/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
        ], [
            'email.regex' => 'Please enter a valid email address with a domain.',
            'email.unique' => 'This email address is already registered.'
        ]);

        $newEmail = strtolower(trim($request->email));

        $emailConflict = User::where('id', '!=', $user->id)
            ->where('email', $newEmail)
            ->whereNotNull('email_verified_at')
            ->exists();

        if ($emailConflict) {
            return response()->json([
                'message' => 'This email address has already been claimed and verified by another active account.'
            ], 422);
        }

        $user->email = $newEmail;
        $user->email_verified_at = null;
        $user->save();

        ActivityLog::record('EMAIL CORRECTED', "User changed their unverified email address to {$user->email}.", $user->name);

        app(EmailVerificationNotificationController::class)->sendOtp($request);

        return response()->json([
            'success' => true,
            'message' => 'Email updated. A new verification code has been dispatched.',
            'user' => $user,
        ]);
    });

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
            'self' => $self,
            'dependents' => $dependents,
        ]);
    });

    Route::post('/appointments', [AppointmentController::class, 'store']);
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
            'archived' => $user->dependents()->onlyTrashed()->get(),
        ]);
    });

    Route::post('/dependents', [DependentController::class, 'store']);
    Route::put('/dependents/{dependent}', [DependentController::class, 'update']);
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy']);
    Route::post('/dependents/{id}/restore', [DependentController::class, 'restore']);

    // History Endpoints
    Route::get('/patient-history', function (Request $request) {
        $user = $request->user();
        $labHistory = LaboratoryHistory::firstOrCreate(['user_id' => $user->id]);
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
                'id' => $r->id,
                'date_of_record' => $r->date_of_record ? $r->date_of_record->format('Y-m-d') : '',
                'requested_by' => $r->requested_by,
                'patient_name' => $r->patient_name,
                'age' => $r->age,
                'sex' => $r->sex,
                'address' => $r->patient_address,
                'tests_requested' => $r->procedures->pluck('procedure_name')->toArray(),
                'scans' => $r->scans->map(fn($s) => [
                    'label' => $s->label,
                    'file_path' => $s->file_path,
                    'certificate_no' => $s->certificate_no ?? null,
                ])->toArray(),
            ];
        });

        return response()->json([
            'labHistory' => $labHistory,
            'appointments' => $appointments,
            'existingRecords' => $existingRecords,
        ]);
    });

    // Dedicated JSON endpoints for mobile digitization request & accept
    Route::post('/patient-history/request', function (Request $request) {
        $user = $request->user();
        $history = LaboratoryHistory::firstOrCreate(['user_id' => $user->id]);
        $history->update(['permission_status' => 'pending_staff']);

        ActivityLog::record('HISTORY REQUEST', 'Patient requested data import', $user->name);

        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($internalStaff as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Lab History Request',
                'message' => "Patient {$user->name} is requesting a historical data import.",
                'url' => route('admin.users.history', $user->id),
                'type' => 'info'
            ]));
            event(new NotificationSent($staff->id, 'Lab History Request', "Patient {$user->name} is requesting a historical data import."));
        }

        return response()->json([
            'success' => true,
            'message' => 'Your request has been dispatched to laboratory staff for verification.',
        ]);
    });

    Route::post('/patient-history/accept', function (Request $request) {
        $user = $request->user();
        $history = LaboratoryHistory::firstOrCreate(['user_id' => $user->id]);
        $history->update(['permission_status' => 'granted']);

        ActivityLog::record('HISTORY GRANTED', "Handshake accepted by {$user->role}", $user->name);

        return response()->json([
            'success' => true,
            'message' => 'Permission granted. Access is now open.',
        ]);
    });

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