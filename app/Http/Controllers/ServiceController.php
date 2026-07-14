<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * View all services (Common for all roles)
     */
    public function index() 
    {
        $services = Service::all();
        return view('services.index', compact('services'));
    }

    /**
     * Store new service (Staff/Admin only)
     * Handles many-to-many relationship synchronization via pivot tables
     */
    public function store(Request $request) 
    {
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

        // Create the record excluding sample_required (since it's normalized out)
        $service = Service::create(array_merge($validated, [
            'is_available' => true
        ]));

        // Synchronize Many-to-Many Samples Pivot table
        if ($request->has('samples') && is_array($request->samples)) {
            $uniqueSamples = array_filter(array_unique($request->samples));
            
            // Map names to unique IDs from 'samples' table
            $sampleIds = [];
            foreach ($uniqueSamples as $name) {
                $sampleId = DB::table('samples')->where('name', $name)->value('id');
                if ($sampleId) {
                    $sampleIds[] = $sampleId;
                }
            }

            // Sync pivot table directly
            DB::table('service_sample')->where('service_id', $service->id)->delete();
            foreach ($sampleIds as $id) {
                DB::table('service_sample')->insert([
                    'service_id' => $service->id,
                    'sample_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return back()->with('success', 'New service added successfully.');
    }

    /**
     * Update existing service
     */
    public function update(Request $request, Service $service) 
    {
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

        // Update the record excluding sample_required (since it's normalized out)
        $service->update($validated);

        // Synchronize Many-to-Many Samples Pivot table
        if ($request->has('samples') && is_array($request->samples)) {
            $uniqueSamples = array_filter(array_unique($request->samples));
            
            // Map names to unique IDs from 'samples' table
            $sampleIds = [];
            foreach ($uniqueSamples as $name) {
                $sampleId = DB::table('samples')->where('name', $name)->value('id');
                if ($sampleId) {
                    $sampleIds[] = $sampleId;
                }
            }

            // Sync pivot table directly
            DB::table('service_sample')->where('service_id', $service->id)->delete();
            foreach ($sampleIds as $id) {
                DB::table('service_sample')->insert([
                    'service_id' => $service->id,
                    'sample_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return back()->with('success', 'Service updated successfully.');
    }

    /**
     * Toggle Availability
     */
    public function toggle(Service $service) 
    {
        // Ensure only staff/admin can toggle
        if (Gate::denies('isStaff')) abort(403);

        $service->update(['is_available' => !$service->is_available]);

        $status = $service->is_available ? 'enabled' : 'disabled';
        return back()->with('success', "Service has been {$status}.");
    }

    /**
     * Delete service
     */
    public function destroy(Service $service) 
    {
        // Ensure only staff/admin can delete
        if (Gate::denies('isStaff')) abort(403);

        // Delete pivot references first
        DB::table('service_sample')->where('service_id', $service->id)->delete();

        $service->delete();
        return back()->with('success', 'Service deleted from catalog!');
    }
}