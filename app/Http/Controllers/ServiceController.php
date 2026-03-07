<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // View all services (Common for all)
    public function index() {
        $services = Service::all();
        return view('services.index', compact('services'));
    }

    // Store new service (Staff/Admin only)
    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'description' => 'required',
            'preparation' => 'required',
        ]);

        Service::create($validated);
        return back()->with('success', 'Service created successfully!');
    }

    // Update service
    public function update(Request $request, Service $service) {
        $service->update($request->all());
        return back()->with('success', 'Service updated!');
    }

    // Toggle Availability
    public function toggle(Service $service) {
        $service->update(['is_available' => !$service->is_available]);
        return back()->with('success', 'Service status updated.');
    }

    // Delete service
    public function destroy(Service $service) {
        $service->delete();
        return back()->with('success', 'Service deleted!');
    }
}