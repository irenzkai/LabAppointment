<?php

namespace App\Http\Controllers;

use App\Models\Dependent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DependentController extends Controller
{
    /**
     * Store a newly created family dependent in the database.
     */
    public function store(Request $request) 
    {
        // Calculate the threshold date exactly 18 years ago from today [38]
        $eighteenYearsAgo = Carbon::now()->subYears(18)->toDateString();

        // Custom name validation rule block matching parent registries
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') {
                return; // Handled by nullable/required constraints
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
            // 1. Identity with strict name rules and 60/10-character limits
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'], // Alphanumeric, spaces, and periods allowed
            
            // Enforce minor status (under 18 years of age) to meet legal compliance [38]
            'birthdate' => 'required|date|before_or_equal:today|after:' . $eighteenYearsAgo, 
            'sex' => 'required|in:Male,Female',
            'relationship' => 'required|string|in:Son,Daughter,SON,DAUGHTER',

            // 2. Address fields (PSGC size standard matching)
            'province' => 'required_unless:inherit_address,1|nullable|string|max:100',
            'city' => 'required_unless:inherit_address,1|nullable|string|max:100',
            'barangay' => 'required_unless:inherit_address,1|nullable|string|max:100',
            'street' => 'required_unless:inherit_address,1|nullable|string|max:150',
        ], [
            'birthdate.after' => 'Administrative Policy: Dependents must be minors (under 18 years of age).',
            'suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ]);

        $user = Auth::user();

        // 3. Inherit parent addresses if toggled
        if ($request->has('inherit_address')) {
            $street = $user->street;
            $barangay = $user->barangay;
            $city = $user->city;
            $province = $user->province;
        } else {
            $street = strtoupper(trim($request->street));
            $barangay = strtoupper(trim($request->barangay));
            $city = strtoupper(trim($request->city));
            $province = strtoupper(trim($request->province));
        }

        // 4. Create record with normalized fields
        $user->dependents()->create([
            'first_name' => strtoupper(trim($request->first_name)),
            'middle_name' => ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') 
                ? strtoupper(trim($request->middle_name)) 
                : 'N/A',
            'last_name' => strtoupper(trim($request->last_name)),
            'suffix' => $request->filled('suffix') ? strtoupper(trim($request->suffix)) : null,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'relationship' => strtoupper(trim($request->relationship)), // Normalized to SON/DAUGHTER
            'street' => $street,
            'barangay' => $barangay,
            'city' => $city, 
            'province' => $province 
        ]);

        return back()->with('success', 'Dependent record created.');
    }

    /**
     * UPDATE: Revise and update an existing family dependent record.
     */
    public function update(Request $request, Dependent $dependent)
    {
        if ($dependent->user_id !== Auth::id()) {
            abort(403);
        }

        // Calculate the threshold date exactly 18 years ago from today [39]
        $eighteenYearsAgo = Carbon::now()->subYears(18)->toDateString();

        // Custom name validation rule block matching parent registries
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') {
                return; // Handled by nullable/required constraints
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
            // 1. Identity with strict name rules and 60/10-character limits
            'first_name' => ['required', 'string', 'max:60', $nameRule],
            'middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'last_name' => ['required', 'string', 'max:60', $nameRule],
            'suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'], // Alphanumeric, spaces, and periods allowed
            
            // Enforce minor status (under 18 years of age) to meet legal compliance [39]
            'birthdate' => 'required|date|before_or_equal:today|after:' . $eighteenYearsAgo, 
            'sex' => 'required|in:Male,Female',
            'relationship' => 'required|string|in:Son,Daughter,SON,DAUGHTER',

            // 2. Address fields (PSGC size standard matching)
            'province' => 'required_unless:inherit_address,1|nullable|string|max:100',
            'city' => 'required_unless:inherit_address,1|nullable|string|max:100',
            'barangay' => 'required_unless:inherit_address,1|nullable|string|max:100',
            'street' => 'required_unless:inherit_address,1|nullable|string|max:150',
        ], [
            'birthdate.after' => 'Administrative Policy: Dependents must be minors (under 18 years of age).',
            'suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ]);

        $user = Auth::user();

        // 3. Inherit parent addresses if toggled
        if ($request->has('inherit_address')) {
            $street = $user->street;
            $barangay = $user->barangay;
            $city = $user->city;
            $province = $user->province;
        } else {
            $street = strtoupper(trim($request->street));
            $barangay = strtoupper(trim($request->barangay));
            $city = strtoupper(trim($request->city));
            $province = strtoupper(trim($request->province));
        }

        // 4. Update record with normalized fields
        $dependent->update([
            'first_name' => strtoupper(trim($request->first_name)),
            'middle_name' => ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') 
                ? strtoupper(trim($request->middle_name)) 
                : 'N/A',
            'last_name' => strtoupper(trim($request->last_name)),
            'suffix' => $request->filled('suffix') ? strtoupper(trim($request->suffix)) : null,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'relationship' => strtoupper(trim($request->relationship)), // Normalized to SON/DAUGHTER
            'street' => $street, 
            'barangay' => $barangay, 
            'city' => $city,
            'province' => $province
        ]);

        return back()->with('success', 'Dependent record successfully updated.');
    }

    /**
     * Remove the specified family dependent from the database.
     */
    public function destroy(Dependent $dependent) 
    {
        if ($dependent->user_id !== Auth::id()) {
            abort(403);
        }

        $dependent->delete(); // Soft-deletes record (preserves audit trail)

        return back()->with('success', 'Dependent removed.');
    }

    /**
     * Reactivate an archived family dependent record.
     */
    public function restore($id)
    {
        $dependent = Dependent::onlyTrashed()->findOrFail($id);

        if ($dependent->user_id !== Auth::id()) {
            abort(403);
        }

        $dependent->restore();

        return back()->with('success', 'Dependent record successfully reactivated.');
    }
}