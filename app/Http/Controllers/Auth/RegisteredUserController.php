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
use Illuminate\Support\Facades\Crypt; // Imported to secure direct-object references against scraping [15, 43]
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
        // 1. VALIDATION
        $request->validate([
            // Step 1: Identity
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'date', 'before_or_equal:today'],
            'sex' => ['required', 'string', 'in:Male,Female'],

            // Step 2: Address (PSGC Mapping)
            'province' => ['required', 'string'],
            'city' => ['required', 'string'],
            'barangay' => ['required', 'string'],
            'street' => ['required', 'string', 'max:255'],

            // Step 3: Contact
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20'],

            // Step 4: Security
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            
            // Optional transition keys
            'promoted_dependent_id' => ['nullable', 'integer', 'exists:dependents,id'],
            'shadow_appointment_id' => ['nullable', 'integer', 'exists:appointments,id']
        ]);

        // 2. DATA CLEANING & FORMATTING
        $fName = strtoupper(trim($request->first_name));
        $mName = ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') 
            ? strtoupper(trim($request->middle_name)) 
            : 'N/A';
        $lName = strtoupper(trim($request->last_name));

        // 3. USER CREATION (3NF Relational Mapping)
        $user = User::create([
            'first_name' => $fName,
            'middle_name' => $mName,
            'last_name' => $lName,
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

        // 4. HISTORICAL RECORD TRANSITION (If promoted from a family dependent) [75, 120]
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

        // 5. EVENTS & LOGIN
        event(new Registered($user));
        Auth::login($user);

        // Record the system audit log
        ActivityLog::record('USER REGISTERED', 'Registration completed and unverified profile activated', $user->name);

        // 6. REDIRECT TO VERIFICATION NOTICE
        return redirect()->route('verification.notice')->with('success', 'Profile successfully registered! Please verify your email.');
    }
}