<?php

namespace App\Http\Controllers;

use App\Models\Dependent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DependentController extends Controller
{
    /**
     * Store a newly created family dependent in the database.
     */
    public function store(Request $request) 
    {
        $request->validate([
            'first_name'   => 'required|string|max:255',
            'middle_name'  => 'nullable|string|max:255',
            'last_name'    => 'required|string|max:255',
            'birthdate'    => 'required|date|before_or_equal:today', 
            'sex'          => 'required|in:Male,Female',
            'relationship' => 'required|string',
            'province'     => 'required_unless:inherit_address,1|nullable|string',
            'city'         => 'required_unless:inherit_address,1|nullable|string',
            'barangay'     => 'required_unless:inherit_address,1|nullable|string',
            'street'       => 'required_unless:inherit_address,1|nullable|string|max:255',
        ]);

        $user = Auth::user();

        if ($request->has('inherit_address')) {
            $street   = $user->street;
            $barangay = $user->barangay;
            $city     = $user->city;
            $province = $user->province;
        } else {
            $street   = strtoupper(trim($request->street));
            $barangay = strtoupper(trim($request->barangay));
            $city     = strtoupper(trim($request->city));
            $province = strtoupper(trim($request->province));
        }

        $user->dependents()->create([
            'first_name'   => strtoupper(trim($request->first_name)),
            'middle_name'  => ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') ? strtoupper(trim($request->middle_name)) : 'N/A',
            'last_name'    => strtoupper(trim($request->last_name)),
            'birthdate'    => $request->birthdate,
            'sex'          => $request->sex,
            'relationship' => strtoupper(trim($request->relationship)),
            'street'       => $street,
            'barangay'     => $barangay,
            'city'         => $city,
            'province'     => $province
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

        $request->validate([
            'first_name'   => 'required|string|max:255',
            'middle_name'  => 'nullable|string|max:255',
            'last_name'    => 'required|string|max:255',
            'birthdate'    => 'required|date|before_or_equal:today',
            'sex'          => 'required|in:Male,Female',
            'relationship' => 'required|string',
            'province'     => 'required_unless:inherit_address,1|nullable|string',
            'city'         => 'required_unless:inherit_address,1|nullable|string',
            'barangay'     => 'required_unless:inherit_address,1|nullable|string',
            'street'       => 'required_unless:inherit_address,1|nullable|string|max:255',
        ]);

        $user = Auth::user();

        if ($request->has('inherit_address')) {
            $street   = $user->street;
            $barangay = $user->barangay;
            $city     = $user->city;
            $province = $user->province;
        } else {
            $street   = strtoupper(trim($request->street));
            $barangay = strtoupper(trim($request->barangay));
            $city     = strtoupper(trim($request->city));
            $province = strtoupper(trim($request->province));
        }

        $dependent->update([
            'first_name'   => strtoupper(trim($request->first_name)),
            'middle_name'  => ($request->middle_name && strtoupper($request->middle_name) !== 'N/A') ? strtoupper(trim($request->middle_name)) : 'N/A',
            'last_name'    => strtoupper(trim($request->last_name)),
            'birthdate'    => $request->birthdate,
            'sex'          => $request->sex,
            'relationship' => strtoupper(trim($request->relationship)),
            'street'       => $street,
            'barangay'     => $barangay,
            'city'         => $city,
            'province'     => $province
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

        $dependent->delete();

        return back()->with('success', 'Dependent removed.');
    }
}