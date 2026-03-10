<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Eager load 'services' (plural) and 'user'
        if ($user->role === 'user') {
            $appointments = Appointment::with('services')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        } else {
            $appointments = Appointment::with(['services', 'user'])
                ->latest()
                ->get();
        }

        return view('appointments.index', compact('appointments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
            'service_ids' => 'required|array',
        ]);

        try {
            $appointment = Appointment::create([
                'user_id' => Auth::id(),
                'appointment_date' => $request->appointment_date,
                'time_slot' => $request->time_slot,
                'status' => 'pending'
            ]);

            // Attach multiple services to the pivot table
            $appointment->services()->attach($request->service_ids);

            // Clear Cart
            session()->forget('cart');

            return redirect()->route('appointments.index')->with('success', 'Appointment submitted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['time_slot' => 'This slot is already occupied.']);
        }
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:approved,returned',
            'return_reason' => 'required_if:status,returned'
        ]);

        $appointment->update([
            'status' => $request->status,
            'return_reason' => ($request->status == 'returned') ? $request->return_reason : null,
        ]);

        return back()->with('success', 'Appointment status updated.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
        ]);

        $appointment->update([
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending',
            'return_reason' => null
        ]);

        return back()->with('success', 'Appointment resubmitted successfully.');
    }
}