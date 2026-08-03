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

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            // Enforce minor status (under 18 years of age) to meet legal compliance [38]
            'birthdate' => 'required|date|before_or_equal:today|after:' . $eighteenYearsAgo, 
            'sex' => 'required|in:Male,Female',
            'relationship' => 'required|string',
            'province' => 'required_unless:inherit_address,1|nullable|string',
            'city' => 'required_unless:inherit_address,1|nullable|string',
            'barangay' => 'required_unless:inherit_address,1|nullable|string',
            'street' => 'required_unless:inherit_address,1|nullable|string|max:255',
        ], [
            'birthdate.after' => 'Administrative Policy: Dependents must be minors (under 18 years of age).',
        ]);

        $user = Auth::user();

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

        $user->dependents()->create([
            'first_name' => strtoupper(trim($request->first_name)),
            'middle_name' => ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') ? strtoupper(trim($request->middle_name)) : 'N/A',
            'last_name' => strtoupper(trim($request->last_name)),
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'relationship' => strtoupper(trim($request->relationship)),
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

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            // Enforce minor status (under 18 years of age) to meet legal compliance [39]
            'birthdate' => 'required|date|before_or_equal:today|after:' . $eighteenYearsAgo, 
            'sex' => 'required|in:Male,Female',
            'relationship' => 'required|string',
            'province' => 'required_unless:inherit_address,1|nullable|string',
            'city' => 'required_unless:inherit_address,1|nullable|string',
            'barangay' => 'required_unless:inherit_address,1|nullable|string',
            'street' => 'required_unless:inherit_address,1|nullable|string|max:255',
        ], [
            'birthdate.after' => 'Administrative Policy: Dependents must be minors (under 18 years of age).',
        ]);

        $user = Auth::user();

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

        $dependent->update([
            'first_name' => strtoupper(trim($request->first_name)),
            'middle_name' => ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') ? strtoupper(trim($request->middle_name)) : 'N/A',
            'last_name' => strtoupper(trim($request->last_name)),
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'relationship' => strtoupper(trim($request->relationship)),
            'street' => $street,
            'barangay' => $barangay,
            'city' => $city,
            'province' => $province
        ]);

        return back()->with('success', 'Dependent record successfully updated.');
    }

    /**
     * Remove the specified family dependent from the database.
     * Triggers an audit-compliant soft delete (retained for 25 years for legal compliance) [40].
     */
    public function destroy(Dependent $dependent) 
    {
        if ($dependent->user_id !== Auth::id()) {
            abort(403);
        }

        // Soft deletes the dependent (sets 'deleted_at' timestamp) [40]
        $dependent->delete();

        return back()->with('success', 'Dependent removed.');
    }

    /**
     * Reactivate an archived family dependent record (Reverses Soft-Delete).
     */
    public function restore($id)
    {
        // Fetch the soft-deleted record explicitly
        $dependent = Dependent::onlyTrashed()->findOrFail($id);

        if ($dependent->user_id !== Auth::id()) {
            abort(403);
        }

        // Reverses the soft-delete state
        $dependent->restore();

        return back()->with('success', 'Dependent record successfully reactivated.');
    }
}