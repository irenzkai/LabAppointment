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
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date|after:today',
        ]);

        Appointment::create([
            'user_id' => Auth::id(),
            'service_id' => $request->service_id,
            'appointment_date' => $request->appointment_date,
            'status' => 'pending'
        ]);

        return back()->with('success', 'Appointment booked! Please wait for staff approval.');
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