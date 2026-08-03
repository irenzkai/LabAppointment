<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * View all services (Common for all roles, with admin archive pane) [58]
     */
    public function index() 
    {
        $services = Service::all(); // Soft-deleted services are automatically excluded [102]

        // Fetch archived (soft-deleted) services for on-page reactivation [15, 102]
        $archivedServices = [];
        if (auth()->check() && auth()->user()->isEmployee()) {
            $archivedServices = Service::onlyTrashed()->get();
        }

        return view('services.index', compact('services', 'archivedServices'));
    }

    /**
     * Store new service (Staff/Admin only)
     * Handles many-to-many relationship synchronization via pivot tables [58]
     */
    public function store(Request $request) 
    {
        if (Gate::denies('isStaff')) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'preparation' => 'required|string',
            'samples' => 'nullable|array', // The array from checkboxes/custom inputs [58]
            'estimated_time' => 'required|integer|min:1', // Minutes as a number [58]
            'category' => 'required|in:individual,package',
            'gender_restriction' => 'required|in:male,female,both',
        ]);

        // Create the record excluding sample_required (since it's normalized out) [38]
        $service = Service::create(array_merge($validated, [
            'is_available' => true
        ]));

        // Synchronize Many-to-Many Samples Pivot table [58]
        if ($request->has('samples') && is_array($request->samples)) {
            $uniqueSamples = array_filter(array_unique($request->samples));
            
            // Map names to unique IDs from 'samples' table [58]
            $sampleIds = [];
            foreach ($uniqueSamples as $name) {
                $sampleId = DB::table('samples')->where('name', $name)->value('id');
                if ($sampleId) {
                    $sampleIds[] = $sampleId;
                }
            }

            // Sync pivot table directly [59]
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
     * Update existing service [59]
     */
    public function update(Request $request, Service $service) 
    {
        if (Gate::denies('isStaff')) abort(403);

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

        // Update the record excluding sample_required (since it's normalized out) [59]
        $service->update($validated);

        // Synchronize Many-to-Many Samples Pivot table [59]
        if ($request->has('samples') && is_array($request->samples)) {
            $uniqueSamples = array_filter(array_unique($request->samples));
            
            // Map names to unique IDs from 'samples' table [59]
            $sampleIds = [];
            foreach ($uniqueSamples as $name) {
                $sampleId = DB::table('samples')->where('name', $name)->value('id');
                if ($sampleId) {
                    $sampleIds[] = $sampleId;
                }
            }

            // Sync pivot table directly [59]
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
     * Toggle Availability [60]
     */
    public function toggle(Service $service) 
    {
        if (Gate::denies('isStaff')) abort(403);

        $service->update(['is_available' => !$service->is_available]);

        $status = $service->is_available ? 'enabled' : 'disabled';
        return back()->with('success', "Service has been {$status}.");
    }

    /**
     * Archive Service (Soft-Deletes the record to preserve compliance history) [60, 102]
     */
    public function destroy(Service $service) 
    {
        if (Gate::denies('isStaff')) abort(403);

        // FIXED: Do NOT delete pivot references here. This ensures that if the service is 
        // reactivated later, its linked sample relationships remain intact on disk [59, 60].
        $service->delete(); // Soft-deletes the record (sets 'deleted_at') [102]

        return back()->with('success', 'Service successfully moved to archives.');
    }

    /**
     * Reactivate an archived service (Reverses Soft-Delete) [102]
     */
    public function restore($id)
    {
        if (Gate::denies('isStaff')) abort(403);

        // Find the soft-deleted record and restore it [15, 102]
        $service = Service::onlyTrashed()->findOrFail($id);
        $service->restore();

        return back()->with('success', "Service {$service->name} has been successfully reactivated.");
    }
}