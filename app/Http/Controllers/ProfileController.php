<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile settings form (Left-Right Split Pane).
     */
    public function edit(Request $request): View 
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Update the user's profile information (Handles name and suffix collation).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse 
    {
        $user = $request->user();

        // 1. Clean and normalize name values
        $fName = strtoupper(trim($request->first_name));
        $mName = ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') 
            ? strtoupper(trim($request->middle_name)) 
            : 'N/A';
        $lName = strtoupper(trim($request->last_name));
        $suffix = $request->filled('suffix') ? strtoupper(trim($request->suffix)) : '';

        // 2. Compile combined display name, appending the suffix if it exists
        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) {
            $displayName .= " {$suffix}";
        }

        // 3. Fill and save the mass-assignable attributes
        $user->fill(array_merge($request->validated(), [
            'first_name' => $fName,
            'middle_name' => $mName,
            'last_name' => $lName,
            'suffix' => $suffix ?: null, // Save suffix snapshot
            'name' => $displayName, // Computed dynamic display name
            'street' => strtoupper(trim($request->street)),
            'barangay' => strtoupper(trim($request->barangay)),
            'city' => strtoupper(trim($request->city)),
            'province' => strtoupper(trim($request->province)),
        ]));

        $emailChanged = $user->isDirty('email');
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        // 4. Record security audit log per RA 10173 compliance guidelines
        ActivityLog::record('PROFILE UPDATED', "User updated their clinical profile details.", $user->name);

        if ($emailChanged) {
            // Clear current OTP session to trigger a fresh OTP email upon prompt redirection
            session()->forget('email_otp_code');
            return redirect()->route('verification.notice')->with('status', 'verification-code-sent');
        }

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account (Supports standard redirect and async AJAX workflows).
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse 
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Log the account deactivation event before session invalidation
        ActivityLog::record('ACCOUNT DELETED', 'User voluntarily deactivated their account', $user->name);

        Auth::logout();

        $user->delete(); // Soft-deletes user record

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Deliver clean, non-redirect JSON to the async frontend deactivation interceptor
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => url('/')
            ]);
        }

        return Redirect::to('/');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::record('PASSWORD UPDATED', "User updated their account password.", $request->user()->name);

        return back()->with('status', 'password-updated');
    }

    /**
     * Update unverified email corrections on the fly directly from the verification hub.
     */
    public function changeUnverifiedEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id, 'regex:/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
        ], [
            'email.regex' => 'Please enter a valid email address with a domain (e.g. name@domain.com or user@online.htcgsc.edu.ph).',
            'email.unique' => 'This email address is already registered.'
        ]);

        $user->email = $request->email;
        $user->email_verified_at = null;
        $user->save();

        ActivityLog::record('EMAIL CORRECTED', "User changed their unverified email address to {$user->email}.", $user->name);

        // Clear current OTP session to trigger a fresh OTP email upon prompt redirection
        session()->forget('email_otp_code');

        return back()->with('status', 'verification-code-sent');
    }
}