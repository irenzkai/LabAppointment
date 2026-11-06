<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Package;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * View all available individual services and packages (Patient Catalog View).
     */
    public function index() 
    {
        $services = Service::where('is_available', true)->orderBy('name')->get();
        $packages = Package::with('services')->where('is_available', true)->orderBy('name')->get();

        return view('services.index', compact('services', 'packages'));
    }

    /**
     * Dedicated Staff/Admin Services & Packages Management Console View.
     */
    public function manage()
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $services = Service::with('packages')->orderBy('name')->get();
        $archivedServices = Service::onlyTrashed()->orderBy('name')->get();

        $packages = Package::with('services')->orderBy('name')->get();
        $archivedPackages = Package::onlyTrashed()->with('services')->orderBy('name')->get();

        return view('services.manage', compact(
            'services', 
            'archivedServices', 
            'packages', 
            'archivedPackages'
        ));
    }

    // =========================================================================
    // ATOMIC INDIVIDUAL SERVICES MANAGEMENT
    // =========================================================================

    /**
     * Store new atomic individual diagnostic service (Staff/Admin only).
     */
    public function store(Request $request) 
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'preparation' => 'required|string',
            'samples' => 'nullable|array',
            'estimated_time' => 'required|integer|min:1',
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

        ActivityLog::record('SERVICE CREATED', "Added diagnostic test: {$service->name}");

        return back()->with('success', "Individual service '{$service->name}' added successfully.");
    }

    /**
     * Update existing individual service.
     */
    public function update(Request $request, Service $service) 
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name,' . $service->id,
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'preparation' => 'required|string',
            'samples' => 'nullable|array',
            'estimated_time' => 'required|integer|min:1',
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

        ActivityLog::record('SERVICE UPDATED', "Updated diagnostic test: {$service->name}");

        return back()->with('success', "Service '{$service->name}' updated successfully.");
    }

    /**
     * Toggle Service Availability.
     */
    public function toggle(Service $service) 
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $service->update(['is_available' => !$service->is_available]);
        $status = $service->is_available ? 'enabled' : 'disabled';

        ActivityLog::record('SERVICE TOGGLE', "Toggled service {$service->name} to {$status}");

        return back()->with('success', "Service has been {$status}.");
    }

    /**
     * Archive Service (Soft-Delete).
     */
    public function destroy(Service $service) 
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $name = $service->name;
        $service->delete();

        ActivityLog::record('SERVICE ARCHIVED', "Archived service {$name}");

        return back()->with('success', "Service '{$name}' successfully moved to archives.");
    }

    /**
     * Reactivate an archived service.
     */
    public function restore($id)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $service = Service::onlyTrashed()->findOrFail($id);
        $service->restore();

        ActivityLog::record('SERVICE RESTORED', "Restored service {$service->name}");

        return back()->with('success', "Service '{$service->name}' has been successfully reactivated.");
    }

    // =========================================================================
    // DIAGNOSTIC PACKAGES MANAGEMENT
    // =========================================================================

    /**
     * Store new diagnostic package containing multiple atomic services.
     */
    public function storePackage(Request $request)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'preparation' => 'nullable|string',
            'estimated_time' => 'nullable|integer|min:1',
            'gender_restriction' => 'required|in:male,female,both',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
        ], [
            'service_ids.required' => 'At least one individual service must be assigned to this package.',
            'service_ids.min' => 'A package must include at least one individual test.',
        ]);

        $package = Package::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'preparation' => $validated['preparation'] ?? null,
            'estimated_time' => $validated['estimated_time'] ?? null,
            'gender_restriction' => $validated['gender_restriction'],
            'is_available' => true,
        ]);

        $package->services()->sync($validated['service_ids']);

        ActivityLog::record('PACKAGE CREATED', "Created diagnostic package: {$package->name}");

        return back()->with('success', "Package '{$package->name}' created successfully.");
    }

    /**
     * Update an existing diagnostic package and its constituent services.
     */
    public function updatePackage(Request $request, Package $package)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name,' . $package->id,
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'preparation' => 'nullable|string',
            'estimated_time' => 'nullable|integer|min:1',
            'gender_restriction' => 'required|in:male,female,both',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
        ], [
            'service_ids.required' => 'At least one individual service must be assigned to this package.',
        ]);

        $package->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'preparation' => $validated['preparation'] ?? null,
            'estimated_time' => $validated['estimated_time'] ?? null,
            'gender_restriction' => $validated['gender_restriction'],
        ]);

        $package->services()->sync($validated['service_ids']);

        ActivityLog::record('PACKAGE UPDATED', "Updated diagnostic package: {$package->name}");

        return back()->with('success', "Package '{$package->name}' updated successfully.");
    }

    /**
     * Toggle Package Availability.
     */
    public function togglePackage(Package $package)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $package->update(['is_available' => !$package->is_available]);
        $status = $package->is_available ? 'enabled' : 'disabled';

        ActivityLog::record('PACKAGE TOGGLE', "Toggled package {$package->name} to {$status}");

        return back()->with('success', "Package '{$package->name}' has been {$status}.");
    }

    /**
     * Archive Package (Soft-Delete).
     */
    public function destroyPackage(Package $package)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $name = $package->name;
        $package->delete();

        ActivityLog::record('PACKAGE ARCHIVED', "Archived package {$name}");

        return back()->with('success', "Package '{$name}' successfully moved to archives.");
    }

    /**
     * Reactivate an archived package.
     */
    public function restorePackage($id)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $package = Package::onlyTrashed()->findOrFail($id);
        $package->restore();

        ActivityLog::record('PACKAGE RESTORED', "Restored package {$package->name}");

        return back()->with('success', "Package '{$package->name}' has been successfully reactivated.");
    }
}