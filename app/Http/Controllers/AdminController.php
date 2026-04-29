<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Appointment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * View all users (Shared by Admin & Staff)
     */
    public function index() {
        // Fetch all users except the currently logged-in admin/staff
        $query = User::where('id', '!=', Auth::id());
        
        // If staff, hide admin users
        if (Auth::user()->role === 'staff') {
            $query->where('role', '!=', 'admin');
        }
        
        $users = $query->latest()->get();
        return view('admin.users', compact('users'));
    }

    /**
     * ADMIN ONLY: Toggle Account Status (Disable/Enable)
     */
    public function toggleStatus(User $user) {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'ENABLED' : 'DISABLED';

        ActivityLog::record('ACCOUNT STATUS CHANGE', 'Admin toggled account', $user->name);

        return back()->with('success', "Account for {$user->name} has been $status.");
    }

    /**
     * ADMIN ONLY: Promote/Demote Roles
     */
    public function changeRole($id, $role) {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $user = User::findOrFail($id);
        $user->update(['role' => $role]);
        
        ActivityLog::record('ROLE CHANGE', "Changed to $role", $user->name);

        return back()->with('success', "User {$user->name} is now a " . strtoupper($role));
    }

    /**
     * ADMIN ONLY: Permanent Delete with Audit Reason
     */
    public function destroy(Request $request, $id) {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $user = User::findOrFail($id);
        
        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        // Create log entry before deleting user
        ActivityLog::create([
            'user_id' => Auth::id(),
            'patient_name' => $user->name,
            'action' => 'PERMANENT ACCOUNT DELETION',
            'reason' => $request->reason,
        ]);

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User account successfully purged from system.');
    }

    /**
     * View Patient Medical History
     */
    public function patientHistory(User $user) {
        // 1. Personnel Check
        if (!Auth::user()->isEmployee()) {
            abort(403);
        }

        // 2. REASON-GATE Check
        if (!session()->has("access_granted_{$user->id}_history")) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Clinical authorization required to view patient records.');
        }

        // Forget after one use
        session()->forget("access_granted_{$user->id}_history");

        // 3. Define variables to match what the 'patient-history' view expects
        $targetUser = $user; 
        
        // We also need the lab history record for the dynamic table to work
        $labHistory = \App\Models\LaboratoryHistory::firstOrCreate(['user_id' => $targetUser->id]);

        $appointments = Appointment::with(['services', 'result'])
            ->where('user_id', $targetUser->id)
            ->latest()
            ->get();

        ActivityLog::record('VIEWED HISTORY', 'Accessed clinical archive', $targetUser->name);

        // FIX: Change 'user' to 'targetUser' and add 'labHistory'
        return view('patient-history', compact('targetUser', 'appointments', 'labHistory'));
    }

    public function viewLogs(Request $request) 
    {
        // 1. Security Check
        if (Auth::user()->role !== 'admin') abort(403);

        $roleFilter = $request->query('role'); // admin, staff, or user
        
        // 2. Build Query
        $query = ActivityLog::with('user')->latest();

        // 3. APPLY FILTER
        if ($roleFilter) {
            // This tells Laravel to only find logs where the linked user has this role
            $query->whereHas('user', function($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            });
        }

        $logs = $query->paginate(20)->withQueryString();
        
        return view('admin.logs', compact('logs'));
    }
}