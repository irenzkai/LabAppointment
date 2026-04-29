<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    /**
     * View all services (Common for all roles)
     */
    public function index() {
        $services = Service::all();
        return view('services.index', compact('services'));
    }

    /**
     * Store new service (Staff/Admin only)
     * Handles the conversion of sample arrays to strings
     */
    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'preparation' => 'required|string',
            'samples' => 'nullable|array', // The array from checkboxes/custom inputs
            'estimated_time' => 'required|integer|min:1', // Minutes as a number
            'category' => 'required|in:individual,package',
            'gender_restriction' => 'required|in:male,female,both',
        ]);

        // Logic to handle flexible sample inputs
        // If no samples are selected, default to 'N/A'
        $sampleString = 'N/A';
        if ($request->has('samples') && !empty($request->samples)) {
            // Remove any duplicates and empty values, then combine
            $uniqueSamples = array_filter(array_unique($request->samples));
            $sampleString = implode(',', $uniqueSamples);
        }

        // Create the record with merged sample data
        Service::create(array_merge($validated, [
            'sample_required' => $sampleString,
            'is_available' => true
        ]));

        return back()->with('success', 'New service added successfully.');
    }

    /**
     * Update existing service
     */
    public function update(Request $request, Service $service) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'preparation' => 'required|string',
            'samples' => 'nullable|array',
            'estimated_time' => 'required|integer|min:1',
            'category' => 'required|in:individual,package',
            'gender_restriction' => 'required|in:male,female,both',
        ]);

        // Logic to handle flexible sample inputs
        $sampleString = 'N/A';
        if ($request->has('samples') && !empty($request->samples)) {
            $uniqueSamples = array_filter(array_unique($request->samples));
            $sampleString = implode(',', $uniqueSamples);
        }

        $service->update(array_merge($validated, [
            'sample_required' => $sampleString
        ]));

        return back()->with('success', 'Service updated successfully.');
    }

    /**
     * Toggle Availability
     */
    public function toggle(Service $service) {
        // Ensure only staff/admin can toggle
        if (Gate::denies('isStaff')) abort(403);

        $service->update(['is_available' => !$service->is_available]);
        
        $status = $service->is_available ? 'enabled' : 'disabled';
        return back()->with('success', "Service has been {$status}.");
    }

    /**
     * Delete service
     */
    public function destroy(Service $service) {
        // Ensure only staff/admin can delete
        if (Gate::denies('isStaff')) abort(403);

        $service->delete();
        return back()->with('success', 'Service deleted from catalog!');
    }
}