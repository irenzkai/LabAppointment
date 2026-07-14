<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the multi-step registration view.
     */
    public function create(): View
    {
        return view('auth.register');
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
            'birthdate' => ['required', 'date', 'before_or_equal:today'], // Enforces past/current dates
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
        ]);

        // 2. DATA CLEANING & FORMATTING
        $fName = strtoupper(trim($request->first_name));
        $mName = ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') 
            ? strtoupper(trim($request->middle_name)) 
            : 'N/A';
        $lName = strtoupper(trim($request->last_name));

        // 3. USER CREATION (3NF Relational Mapping)
        $user = User::create([
            'first_name'  => $fName,
            'middle_name' => $mName,
            'last_name'   => $lName,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'birthdate'   => $request->birthdate,
            'sex'         => $request->sex,
            
            // Storing individual location fields directly
            'street'      => strtoupper(trim($request->street)),
            'barangay'    => strtoupper(trim($request->barangay)),
            'city'        => strtoupper(trim($request->city)),
            'province'    => strtoupper(trim($request->province)),
            
            'password'    => Hash::make($request->password),
            'role'        => 'user',
            'is_active'   => true,
            
            // email_verified_at is set to now() to bypass the verification screen in dev
            'email_verified_at' => now(), 
        ]);

        // 4. EVENTS & LOGIN
        event(new Registered($user));
        Auth::login($user);

        // Record the system audit log (Uses dynamic $user->name accessor)
        ActivityLog::record('USER REGISTERED', 'Registration completed and auto-verified', $user->name);

        // 5. REDIRECT TO DASHBOARD
        return redirect()->route('dashboard')->with('success', 'Welcome to Medscreen! Your account is ready.');
    }
}