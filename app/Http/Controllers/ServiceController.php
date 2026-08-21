<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * View all services (Normal Patient Catalog View)
     */
    public function index() 
    {
        $services = Service::where('is_available', true)->get();
        return view('services.index', compact('services'));
    }

    /**
     * Dedicated Staff/Admin Services Management Console View
     */
    public function manage()
    {
        if (Gate::denies('isStaff')) abort(403);

        $services = Service::all();
        $archivedServices = Service::onlyTrashed()->get();

        return view('services.manage', compact('services', 'archivedServices'));
    }

    /**
     * Store new service (Staff/Admin only)
     */
    public function store(Request $request) 
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

        $service = Service::create(array_merge($validated, [
            'is_available' => true
        ]));

        if ($request->has('samples') && is_array($request->samples)) {
            $uniqueSamples = array_filter(array_unique($request->samples));
            $sampleIds = [];
            foreach ($uniqueSamples as $name) {
                $sampleId = DB::table('samples')->where('name', $name)->value('id');
                if ($sampleId) {
                    $sampleIds[] = $sampleId;
                }
            }
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

        $service->update($validated);

        if ($request->has('samples') && is_array($request->samples)) {
            $uniqueSamples = array_filter(array_unique($request->samples));
            $sampleIds = [];
            foreach ($uniqueSamples as $name) {
                $sampleId = DB::table('samples')->where('name', $name)->value('id');
                if ($sampleId) {
                    $sampleIds[] = $sampleId;
                }
            }
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
        if (Gate::denies('isStaff')) abort(403);

        $service->update(['is_available' => !$service->is_available]);
        $status = $service->is_available ? 'enabled' : 'disabled';

        return back()->with('success', "Service has been {$status}.");
    }

    /**
     * Archive Service (Soft-Delete)
     */
    public function destroy(Service $service) 
    {
        if (Gate::denies('isStaff')) abort(403);

        $service->delete();

        return back()->with('success', 'Service successfully moved to archives.');
    }

    /**
     * Reactivate an archived service
     */
    public function restore($id)
    {
        if (Gate::denies('isStaff')) abort(403);

        $service = Service::onlyTrashed()->findOrFail($id);
        $service->restore();

        return back()->with('success', "Service {$service->name} has been successfully reactivated.");
    }
}