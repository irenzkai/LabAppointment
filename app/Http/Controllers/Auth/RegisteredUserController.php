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

                // Check if this is a shadow account promotion or a dependent promotion [43]
                if ($request->query('type') === 'shadow') {
                    $appointment = Appointment::find($decryptedId);
                    if ($appointment) {
                        $promotedDependent = (object) [
                            'id' => null,
                            'first_name' => $appointment->patient_first_name,
                            'middle_name' => $appointment->patient_middle_name,
                            'last_name' => $appointment->patient_last_name,
                            'suffix' => $appointment->patient_suffix ?? null, // Populate optional suffix if it exists
                            'birthdate' => $appointment->patient_birthdate,
                            'sex' => $appointment->patient_sex,
                            'street' => $appointment->patient_street,
                            'province' => $appointment->patient_province,
                            'city' => $appointment->patient_city,
                            'barangay' => $appointment->patient_barangay,
                            'email' => $appointment->patient_email,
                            'phone' => $appointment->patient_phone,
                            'shadow_appointment_id' => $appointment->id // Bind to verify transition [75]
                        ];
                    }
                } else {
                    $promotedDependent = Dependent::find($decryptedId);
                }
            } catch (\Exception $e) {
                // Return generic registration view silently if token was tampered with [43]
            }
        }

        return view('auth.register', compact('promotedDependent'));
    }

    /**
     * Handle an incoming multi-step registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Custom name validation rule block matching the dynamic JS validator exactly
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val)) {
                return; // Managed by standard required rules
            }

            // 1. Allowed characters boundary validation (Letters, Spanish ñ/Ñ, periods, hyphens, spaces, apostrophes)
            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }

            // 2. Strict non-punctuation starting validation
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must start with a letter.");
                return;
            }

            // 3. Must possess at least one character letter to prevent punctuation-only values
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }

            // 4. Consecutive punctuation marks validation
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        $request->validate([
            // Step 1: Identity with strict name rules and 60/10-character boundary limits
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'], // Alphanumeric, spaces, and periods allowed for suffixes
            'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'sex' => ['required', 'string', 'in:Male,Female'],

            // Step 2: Address (PSGC Mapping)
            'province' => ['required', 'string'],
            'city' => ['required', 'string'],
            'barangay' => ['required', 'string'],
            'street' => ['required', 'string', 'max:255'],

            // Step 3: Contact (Single @ rule and strict 11-digit 09 Ph-Mobile format)
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, 'regex:/^[^@]+@[^@]+$/'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],

            // Step 4: Security
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // Optional transition keys
            'promoted_dependent_id' => ['nullable', 'integer', 'exists:dependents,id'],
            'shadow_appointment_id' => ['nullable', 'integer', 'exists:appointments,id']
        ], [
            // Custom fallback messages for general validation errors
            'birthdate.before_or_equal' => 'Administrative Policy: You must be at least 18 years old to create an account.',
            'email.regex' => 'The email address must contain exactly one @ symbol.',
            'phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ]);

        // DATA CLEANING & FORMATTING
        $fName = strtoupper(trim($request->first_name));
        $mName = ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') 
            ? strtoupper(trim($request->middle_name)) 
            : 'N/A';
        $lName = strtoupper(trim($request->last_name));
        $suffix = $request->filled('suffix') ? strtoupper(trim($request->suffix)) : '';

        // Compile combined display name, appending the suffix if it exists
        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) {
            $displayName .= " {$suffix}";
        }

        // USER CREATION (3NF Relational Mapping)
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
            'street' => strtoupper(trim($request->street)),
            'barangay' => strtoupper(trim($request->barangay)),
            'city' => strtoupper(trim($request->city)),
            'province' => strtoupper(trim($request->province)),
            'password' => Hash::make($request->password),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => null, // Require newly registered/promoted users to verify their email
        ]);

        // HISTORICAL RECORD TRANSITION (If promoted from a family dependent) [75, 120]
        if ($request->filled('promoted_dependent_id')) {
            $depId = $request->input('promoted_dependent_id');

            // Set all historically linked appointments to belong to the new user directly [75, 120]
            Appointment::where('dependent_id', $depId)
                ->update([
                    'user_id' => $user->id,
                    'dependent_id' => null, // Moves booking from "Dependent" category to "Personal" category
                ]);

            // Safely delete the old dependent card to complete the transition [120]
            Dependent::destroy($depId);

            ActivityLog::record('ACCOUNT PROMOTED', 'Dependent account successfully promoted to independent user profile', $user->name, $user->id);
        } elseif ($request->filled('shadow_appointment_id')) {
            // Shadow Account Transition [75]
            $appId = $request->input('shadow_appointment_id');
            Appointment::where('id', $appId)->update([
                'user_id' => $user->id,
            ]);
            ActivityLog::record('SHADOW ACCOUNT ACTIVATED', 'Shadow account registered and linked to clinical folder', $user->name, $user->id);
        }

        // EVENTS & LOGIN
        event(new Registered($user));
        Auth::login($user);

        // Record the system audit log
        ActivityLog::record('USER REGISTERED', 'Registration completed and unverified profile activated', $user->name);

        // REDIRECT TO VERIFICATION NOTICE
        return redirect()->route('verification.notice')->with('success', 'Profile successfully registered! Please verify your email.');
    }
}