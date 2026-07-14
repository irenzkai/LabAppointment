<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog; // FIXED: Imported ActivityLog model to prevent account deletion crashes
use Illuminate\Http\RedirectResponse;
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
     * Update the user's profile information (Handles name separation).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Clean name fields
        $fName = strtoupper(trim($request->first_name));
        $mName = ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') 
            ? strtoupper(trim($request->middle_name)) 
            : 'N/A';
        $lName = strtoupper(trim($request->last_name));

        // Compile combined display name
        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";

        // Fill user attributes
        $user->fill(array_merge($request->validated(), [
            'first_name' => $fName,
            'middle_name' => $mName,
            'last_name' => $lName,
            'name' => $displayName,
            'street' => strtoupper(trim($request->street)),
            'barangay' => strtoupper(trim($request->barangay)),
            'city' => strtoupper(trim($request->city)),
            'province' => strtoupper(trim($request->province)),
        ]));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse 
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        /**
         * FIXED: Moved ActivityLog::record BEFORE Auth::logout() and $user->delete().
         * This allows the system to correctly associate the 'user_id' with the log 
         * before the record is purged from the database.
         */
        ActivityLog::record('ACCOUNT DELETED', 'User voluntarily deleted their account', $user->name);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

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

        return back()->with('status', 'password-updated');
    }
}