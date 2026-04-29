<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\LaboratoryHistory;
use App\Models\Appointment;
use App\Notifications\AppointmentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class HistoryController extends Controller
{
    public function index(User $user = null)
    {
        // Fix for the "id on null" error
        $targetUser = $user ?: Auth::user();
        
        // Security: Patients can only see themselves
        if (Auth::user()->isPatient() && Auth::id() !== $targetUser->id) {
            abort(403);
        }

        $labHistory = LaboratoryHistory::firstOrCreate(['user_id' => $targetUser->id]);
        
        $appointments = Appointment::where('user_id', $targetUser->id)
            ->with('services')
            ->latest()
            ->get();

        return view('patient-history', compact('targetUser', 'labHistory', 'appointments'));
    }

    public function requestPermission()
    {
        $user = Auth::user();
        $history = LaboratoryHistory::firstOrCreate(['user_id' => $user->id]);

        // Update status to pending
        $history->update(['permission_status' => 'pending_staff']);

        // Create a system log
        ActivityLog::record('HISTORY REQUEST', 'Patient requested data import', $user->name);

        // Notify all staff and admins
        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach($internalStaff as $staff) {
            $staff->notify(new \App\Notifications\AppointmentNotification([
                'title' => 'Lab History Request',
                'message' => "Patient {$user->name} is requesting a historical data import.",
                'url' => route('patient.history', $user->id), // Direct link to that patient's history page
                'type' => 'info'
            ]));
        }

        return back()->with('success', 'Your request has been sent to the laboratory staff.');
    }

    public function staffTriggerRequest(User $user)
    {
        $history = LaboratoryHistory::where('user_id', $user->id)->first();
        $history->update(['permission_status' => 'pending_patient']);

        $user->notify(new \App\Notifications\AppointmentNotification([
            'title' => 'Data Import Permission',
            'message' => 'The laboratory is asking for permission to digitize your physical records. Please visit your History page to approve.',
            'url' => route('patient.history'),
            'type' => 'info'
        ]));

        return back()->with('success', 'Permission request sent to patient.');
    }

    public function acceptRequest(User $user = null)
    {
        // 1. Determine whose history we are updating
        // If $user is provided, a staff member is accepting a patient's request.
        // If $user is null, a patient is accepting a staff member's request for themselves.
        $targetUser = $user ?: Auth::user();

        $history = LaboratoryHistory::where('user_id', $targetUser->id)->first();

        if (!$history) {
            return back()->with('error', 'History record not found.');
        }

        $history->update(['permission_status' => 'granted']);

        // 2. Log the action
        $roleName = Auth::user()->role;
        ActivityLog::record('HISTORY GRANTED', "Handshake accepted by {$roleName}", $targetUser->name);

        return back()->with('success', 'Permission granted. Access is now open.');
    }

    public function saveManualData(Request $request, User $user)
    {
        // Validate that we actually have data
        if (!$request->has('headers')) {
            return back()->with('error', 'Table cannot be empty.');
        }

        $history = LaboratoryHistory::where('user_id', $user->id)->first();
        
        $history->update([
            'dynamic_data' => [
                'headers' => $request->headers,
                'rows' => $request->rows
            ],
            'permission_status' => 'granted'
        ]);

        return back()->with('success', 'Archived lab data saved successfully.');
    }
}