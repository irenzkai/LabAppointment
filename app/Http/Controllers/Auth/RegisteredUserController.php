<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Dependent;
use App\Models\Appointment;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the multi-step registration view.
     */
    public function create(Request $request): View
    {
        $promotedDependent = null;

        if ($request->has('promote')) {
            try {
                $decryptedId = Crypt::decryptString($request->query('promote'));

                if ($request->query('type') === 'shadow') {
                    $appointment = Appointment::find($decryptedId);
                    if ($appointment) {
                        $promotedDependent = (object) [
                            'id' => null,
                            'first_name' => $appointment->patient_first_name,
                            'middle_name' => $appointment->patient_middle_name,
                            'last_name' => $appointment->patient_last_name,
                            'suffix' => $appointment->patient_suffix ?? null,
                            'birthdate' => $appointment->patient_birthdate,
                            'sex' => $appointment->patient_sex,
                            'street' => $appointment->patient_street,
                            'province' => $appointment->patient_province,
                            'city' => $appointment->patient_city,
                            'barangay' => $appointment->patient_barangay,
                            'email' => $appointment->patient_email,
                            'phone' => $appointment->patient_phone,
                            'shadow_appointment_id' => $appointment->id
                        ];
                    }
                } else {
                    $promotedDependent = Dependent::find($decryptedId);
                }
            } catch (\Exception $e) {
                // Return generic registration view silently if token was tampered with
            }
        }

        return view('auth.register', compact('promotedDependent'));
    }

    /**
     * Handle an incoming multi-step registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val)) return;

            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        $request->validate([
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'],
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'sex' => ['required', 'string', 'in:Male,Female'],
            'province' => ['required', 'string'],
            'city' => ['required', 'string'],
            'barangay' => ['required', 'string'],
            'street' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, 'regex:/^[^@]+@[^@]+$/'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'promoted_dependent_id' => ['nullable', 'integer', 'exists:dependents,id'],
            'shadow_appointment_id' => ['nullable', 'integer', 'exists:appointments,id']
        ], [
            'birthdate.before_or_equal' => 'Administrative Policy: You must be at least 18 years old to create an account.',
            'email.regex' => 'The email address must contain exactly one @ symbol.',
            'phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ]);

        // DATA CLEANING & FORMATTING (Multibyte UTF-8 Ñ/ñ Safe)
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
            'email' => $request->email,
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
        ]);

        // HISTORICAL RECORD TRANSITION (If promoted from a family dependent)
        if ($request->filled('promoted_dependent_id')) {
            $depId = $request->input('promoted_dependent_id');
            Appointment::where('dependent_id', $depId)
                ->update([
                    'user_id' => $user->id,
                    'dependent_id' => null,
                ]);

            Dependent::destroy($depId);
            ActivityLog::record('ACCOUNT PROMOTED', "Dependent account successfully promoted to independent user profile for {$user->name}", $user->name);
        } elseif ($request->filled('shadow_appointment_id')) {
            $appId = $request->input('shadow_appointment_id');
            Appointment::where('id', $appId)->update([
                'user_id' => $user->id,
            ]);
            ActivityLog::record('SHADOW ACCOUNT ACTIVATED', 'Shadow account registered and linked to clinical folder', $user->name);
        }

        event(new Registered($user));
        Auth::login($user);

        ActivityLog::record('USER REGISTERED', 'Registration completed and unverified profile activated', $user->name);

        return redirect()->route('verification.notice')->with('success', 'Profile successfully registered! Please verify your email.');
    }
}