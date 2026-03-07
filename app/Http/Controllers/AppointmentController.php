<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    // View list of appointments
    public function index() {
        $user = Auth::user();

        if ($user->role === 'user') {
            // Users only see their own
            $appointments = Appointment::with('service')->where('user_id', $user->id)->latest()->get();
        } else {
            // Staff and Admin see all
            $appointments = Appointment::with(['service', 'user'])->latest()->get();
        }

        return view('appointments.index', compact('appointments'));
    }

    // User creates an appointment
    public function store(Request $request) {
        $x_hours = 0; 
        
        $request->validate([
            'service_id' => 'required',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required'
        ]);

        // Time-block logic: If date is today, check if time is at least X hours ahead
        if($request->appointment_date == date('Y-m-d')) {
            $currentTime = now()->addHours($x_hours)->format('H:i');
            if($request->time_slot <= $currentTime) {
                return back()->withErrors(['time_slot' => "You cannot book a time slot in the past or within the next $x_hours hour(s)."]);
            }
        }

        try {
            Appointment::create([
                'user_id' => auth()->id(),
                'service_id' => $request->service_id,
                'appointment_date' => $request->appointment_date,
                'time_slot' => $request->time_slot,
                'status' => 'pending'
            ]);
            return redirect()->route('appointments.index')->with('success', 'Booking successful!');
        } catch (\Exception $e) {
            return back()->withErrors(['time_slot' => 'This slot is already occupied.']);
        }
    }

    // Staff/Admin Approve or Return
    public function updateStatus(Request $request, Appointment $appointment)
        {
            $request->validate([
                'status' => 'required|in:approved,returned',
                'return_reason' => 'required_if:status,returned|string|nullable'
            ]);

            $appointment->update([
                'status' => $request->status,
                // Only save the reason if the status is 'returned'
                'return_reason' => ($request->status == 'returned') ? $request->return_reason : null,
            ]);

            return back()->with('success', 'Appointment status updated.');
        }

    public function update(Request $request, Appointment $appointment)
        {
            // Ensure only the owner can resubmit
            if (auth()->id() !== $appointment->user_id) {
                abort(403);
            }

            $request->validate([
                'appointment_date' => 'required|date|after:today',
            ]);

            $appointment->update([
                'appointment_date' => $request->appointment_date,
                'status' => 'pending', // Reset to pending
                'return_reason' => null // Clear the reason
            ]);

            return back()->with('success', 'Appointment resubmitted successfully!');
        }
}