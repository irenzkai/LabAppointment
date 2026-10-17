<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
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
     * Normalize the input into a standard 11-digit Philippine mobile format (09XXXXXXXXX).
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '09' . substr($digits, 3);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '09' . $digits;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Update the user's profile information (Handles name, suffix collation, and credential conflicts).
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

        $newEmail = strtolower(trim($request->email));
        $newPhone = $this->normalizePhoneNumber((string) $request->phone);

        // 3. Credential Conflict Checks: Ensure email and phone aren't already verified on another account
        $emailVerifiedElsewhere = User::where('id', '!=', $user->id)
            ->where('email', $newEmail)
            ->whereNotNull('email_verified_at')
            ->exists();

        if ($emailVerifiedElsewhere) {
            return back()->withInput()->withErrors([
                'email' => 'This email address has already been claimed and verified by another active account.'
            ]);
        }

        $phoneVerifiedElsewhere = User::where('id', '!=', $user->id)
            ->where('phone', $newPhone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($phoneVerifiedElsewhere) {
            return back()->withInput()->withErrors([
                'phone' => 'This mobile phone number has already been claimed and verified by another active account.'
            ]);
        }

        // 4. Fill and save the mass-assignable attributes
        $user->fill(array_merge($request->validated(), [
            'first_name' => $fName,
            'middle_name' => $mName,
            'last_name' => $lName,
            'suffix' => $suffix ?: null,
            'name' => $displayName,
            'email' => $newEmail,
            'phone' => $newPhone,
            'street' => strtoupper(trim($request->street)),
            'barangay' => strtoupper(trim($request->barangay)),
            'city' => strtoupper(trim($request->city)),
            'province' => strtoupper(trim($request->province)),
        ]));

        $emailChanged = $user->isDirty('email');
        if ($emailChanged) {
            $user->email_verified_at = null;
            session()->forget('email_otp_code');
            Cache::forget("email_otp_{$user->id}");
            Cache::forget("email_otp_addr_{$user->id}");
        }

        $phoneChanged = $user->isDirty('phone');
        if ($phoneChanged) {
            $user->phone_verified_at = null;
            session()->forget(['sms_otp_code', 'sms_otp_phone']);
            Cache::forget("sms_otp_{$user->id}");
            Cache::forget("sms_otp_phone_{$user->id}");
        }

        $user->save();

        // 5. Record security audit log per RA 10173 compliance guidelines
        ActivityLog::record('PROFILE UPDATED', "User updated their clinical profile details.", $user->name);

        if ($emailChanged) {
            return redirect()->route('verification.notice')->with('status', 'verification-code-sent');
        }

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account (Supports standard redirect and async AJAX workflows).
     * Deactivation unsets verified timestamps so credentials can be reclaimed if needed.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Log the account deactivation event before session invalidation
        ActivityLog::record('ACCOUNT DELETED', 'User voluntarily deactivated their account', $user->name);

        // Unverify email and phone so credentials can be used again without deadlock
        $user->email_verified_at = null;
        $user->phone_verified_at = null;
        $user->save();

        // Clear any lingering OTP cache entries
        Cache::forget("email_otp_{$user->id}");
        Cache::forget("email_otp_addr_{$user->id}");
        Cache::forget("sms_otp_{$user->id}");
        Cache::forget("sms_otp_phone_{$user->id}");

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
            return redirect()->route(Route::has('main') ? 'main' : 'dashboard');
        }

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
            'email.regex' => 'Please enter a valid email address with a domain (e.g. name@domain.com or user@online.htcgsc.edu.ph).',
            'email.unique' => 'This email address is already registered.'
        ]);

        $newEmail = strtolower(trim($request->email));

        // Ensure this email is not verified on another active account
        $emailVerifiedElsewhere = User::where('id', '!=', $user->id)
            ->where('email', $newEmail)
            ->whereNotNull('email_verified_at')
            ->exists();

        if ($emailVerifiedElsewhere) {
            return back()->withInput()->withErrors([
                'email' => 'This email address has already been claimed and verified by another active account.'
            ]);
        }

        $user->email = $newEmail;
        $user->email_verified_at = null;
        $user->save();

        ActivityLog::record('EMAIL CORRECTED', "User changed their unverified email address to {$user->email}.", $user->name);

        // Clear current OTP session to trigger a fresh OTP email upon prompt redirection
        session()->forget('email_otp_code');
        Cache::forget("email_otp_{$user->id}");
        Cache::forget("email_otp_addr_{$user->id}");

        return back()->with('status', 'verification-code-sent');
    }

    /**
     * Update unverified mobile phone number corrections on the fly directly from the verification hub.
     */
    public function changeUnverifiedPhone(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your mobile phone number is already verified.'
                ], 422);
            }
            return redirect()->route(Route::has('main') ? 'main' : 'dashboard');
        }

        $request->validate([
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ], [
            'phone.required' => 'Please enter your mobile phone number.',
            'phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits (e.g. 09123456789).',
        ]);

        $newPhone = $this->normalizePhoneNumber((string) $request->phone);

        // Ensure this phone number is not verified on another active account
        $phoneVerifiedElsewhere = User::where('id', '!=', $user->id)
            ->where('phone', $newPhone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($phoneVerifiedElsewhere) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mobile phone number has already been claimed and verified by another active account.'
                ], 422);
            }

            return back()->withInput()->withErrors([
                'phone' => 'This mobile phone number has already been claimed and verified by another active account.'
            ]);
        }

        $user->phone = $newPhone;
        $user->phone_verified_at = null;
        $user->save();

        ActivityLog::record('PHONE CORRECTED', "User changed their unverified mobile phone number to {$user->phone}.", $user->name);

        // Clear previous SMS OTP sessions and caches to reset dispatch limits
        session()->forget(['sms_otp_code', 'sms_otp_phone']);
        Cache::forget("sms_otp_{$user->id}");
        Cache::forget("sms_otp_phone_{$user->id}");

        // Automatically dispatch fresh SMS OTP to the newly updated mobile number
        try {
            return app(VerifyEmailController::class)->sendSmsOtp($request);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mobile number updated successfully. You can now request your SMS verification code.',
                    'phone' => $user->phone,
                ]);
            }

            return back()->with('status', 'verification-sms-sent');
        }
    }
}