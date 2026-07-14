<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Appointment;
use App\Models\ActivityLog;
use App\Models\LaboratoryHistory;
use App\Models\LaboratoryHistoryRecord;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * View all users (Shared by Admin & Staff)
     */
    public function index() 
    {
        // Fetch all users except the currently logged-in person
        $query = User::where('id', '!=', Auth::id());

        // If the logged-in user is 'staff', hide 'admin' accounts from the list
        if (Auth::user()->role === 'staff') {
            $query->where('role', '!=', 'admin');
        }

        $users = $query->latest()->get();

        return view('admin.users', compact('users'));
    }

    /**
     * ADMIN ONLY: Toggle Account Status (Disable/Enable)
     */
    public function toggleStatus(User $user) 
    {
        if (Auth::user()->role !== 'admin') abort(403);

        // Toggle boolean
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'ENABLED' : 'DISABLED';

        // Audit Log
        ActivityLog::record(
            'ACCOUNT STATUS CHANGE', 
            "Admin toggled account to $status", 
            $user->name
        );

        return back()->with('success', "Account for {$user->name} has been $status.");
    }

    /**
     * ADMIN ONLY: Promote/Demote Roles
     */
    public function changeRole(Request $request, User $user) 
    {
        // 1. Authorization Check
        if (Auth::user()->role !== 'admin') abort(403);

        // 2. Validate the input (Explicitly exclude 'admin' to prevent rogue admins)
        $request->validate([
            'role' => 'required|in:user,staff,lab_tech'
        ]);

        // 3. Update and Log
        $user->update(['role' => $request->role]);

        ActivityLog::record(
            'ROLE CHANGE', 
            "Account promoted/demoted to " . strtoupper($request->role), 
            $user->name
        );

        return back()->with('success', "User {$user->name} role updated to " . strtoupper($request->role));
    }

    /**
     * ADMIN ONLY: Permanent Delete with Audit Reason
     * FIXED: Changed dissociation to deletion for activity logs to resolve NOT NULL constraints.
     */
    public function destroy(Request $request, $id) 
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $user = User::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        DB::beginTransaction();

        try {
            // 1. Create a snapshot log of the deletion event performed by the current Admin
            ActivityLog::create([
                'user_id' => Auth::id(),
                'patient_name' => $user->name,
                'action' => 'PERMANENT ACCOUNT DELETION',
                'reason' => $request->reason,
            ]);

            // 2. FIXED: Delete activity logs performed by this user
            // We delete these to respect the NOT NULL database constraint while purging the account.
            ActivityLog::where('user_id', $id)->delete();

            // 3. Cleanup relational data
            $user->dependents()->delete();
            
            foreach($user->appointments as $app) {
                $app->services()->detach();
                if($app->result) {
                    // Clean up normalized workstation sub-tables first
                    $app->result->labResults()->delete();
                    $app->result->labDetails()->delete();
                    $app->result->medCert()->delete();
                    $app->result->radiologyReport()->delete();
                    $app->result->delete();
                }
                $app->delete();
            }

            // 4. Delete the user profile
            $user->delete();

            DB::commit();
            return redirect()->route('admin.users.index')->with('success', 'User account successfully purged from system.');

        } catch (\Exception $e) {
            DB::rollback();
            // Capture the specific SQL error for the session feedback
            return back()->with('error', 'Purge failed: ' . $e->getMessage());
        }
    }

    /**
     * View Patient Medical History (Accessed via the Reason-Gate)
     */
    public function patientHistory(User $user) 
    {
        if (!Auth::user()->isEmployee()) {
            abort(403);
        }

        if (!session()->has("access_granted_{$user->id}_history")) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Clinical authorization required to view patient records.');
        }

        session()->forget("access_granted_{$user->id}_history");

        $targetUser = $user; 
        $labHistory = LaboratoryHistory::firstOrCreate(['user_id' => $targetUser->id]);

        $appointments = Appointment::with(['services', 'result'])
            ->where('user_id', $targetUser->id)
            ->latest()
            ->get();

        ActivityLog::record('VIEWED HISTORY', 'Accessed clinical archive via User Management', $targetUser->name);

        $availableServices = Service::where('is_available', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $recordsModels = LaboratoryHistoryRecord::whereHas('laboratoryHistory', function($q) use ($targetUser) {
            $q->where('user_id', $targetUser->id);
        })
        ->with(['scans', 'procedures'])
        ->latest('date_of_record')
        ->get();

        $existingRecords = $recordsModels->map(function($r) {
            return [
                'id' => $r->id,
                'date_of_record' => $r->date_of_record ? $r->date_of_record->format('Y-m-d') : '',
                'requested_by' => $r->requested_by,
                'patient_name' => $r->patient_name,
                'age' => $r->age,
                'sex' => $r->sex,
                'address' => $r->patient_address,
                'tests_requested' => $r->procedures->pluck('procedure_name')->toArray(),
                'scans' => $r->scans->map(function($s) {
                    return [
                        'label' => $s->label,
                        'file_path' => $s->file_path
                    ];
                })->toArray()
            ];
        })->toArray();

        return view('patient-history', compact('targetUser', 'appointments', 'labHistory', 'availableServices', 'existingRecords'));
    }

    /**
     * ADMIN ONLY: View System Audit Logs
     */
    public function viewLogs(Request $request) 
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $roleFilter = $request->query('role');
        $query = ActivityLog::with('user')->latest();

        if ($roleFilter) {
            $query->whereHas('user', function($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.logs', compact('logs'));
    }
}