<?php

namespace App\Http\Controllers;

use App\Models\Dependent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DependentController extends Controller
{
    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'birthdate' => 'required|date',
            'sex' => 'required|in:Male,Female',
            'relationship' => 'required|string',
            'address' => 'nullable|string|required_unless:inherit_address,1',
        ]);

        // Handle address inheritance
        $address = $request->has('inherit_address') 
                ? auth()->user()->address 
                : $request->address;

        auth()->user()->dependents()->create([
            'name' => $request->name,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'relationship' => $request->relationship,
            'address' => $address,
        ]);

        return back()->with('success', 'Dependent record created.');
    }

    public function destroy(Dependent $dependent) {
        if ($dependent->user_id !== Auth::id()) abort(403);
        $dependent->delete();
        return back()->with('success', 'Dependent removed.');
    }
}