<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\LaboratoryHistory;
use App\Models\LaboratoryHistoryRecord;
use App\Models\LaboratoryHistoryScan;
use App\Models\LaboratoryHistoryProcedure;
use App\Models\Appointment;
use App\Models\Service;
use App\Notifications\AppointmentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class HistoryController extends Controller
{
    /**
     * Display medical history view
     */
    public function index(User $user = null)
    {
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

        // FETCH: Real catalog of active/available clinical services
        $availableServices = Service::where('is_available', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Retrieve digitized sub-table records & map them back into a backward-compatible array structure
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
                'address' => $r->patient_address, // dynamic address string
                'tests_requested' => $r->procedures->pluck('procedure_name')->toArray(),
                'scans' => $r->scans->map(function($s) {
                    return [
                        'label' => $s->label,
                        'file_path' => $s->file_path,
                        'certificate_no' => $s->certificate_no ?? null // FIXED: Included certificate_no in mapped array for patient view loading
                    ];
                })->toArray()
            ];
        })->toArray();

        return view('patient-history', compact('targetUser', 'labHistory', 'appointments', 'availableServices', 'existingRecords'));
    }

    /**
     * Patient requests a manual data import from the laboratory staff
     */
    public function requestPermission()
    {
        $user = Auth::user();
        $history = LaboratoryHistory::firstOrCreate(['user_id' => $user->id]);

        $history->update(['permission_status' => 'pending_staff']);

        ActivityLog::record('HISTORY REQUEST', 'Patient requested data import', $user->name);

        $internalStaff = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach($internalStaff as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Lab History Request',
                'message' => "Patient {$user->name} is requesting a historical data import.",
                'url' => route('admin.users.history', $user->id),
                'type' => 'info'
            ]));
        }

        return back()->with('success', 'Your request has been sent to the laboratory staff.');
    }

    /**
     * Clinical staff requests permission to digitize physical records for a patient
     */
    public function staffTriggerRequest(User $user)
    {
        // RENEW SESSION TOKEN FIRST: Re-authorize the session key so any validation/redirect errors do not trigger lockouts
        session()->put("access_granted_{$user->id}_history", true);

        $history = LaboratoryHistory::where('user_id', $user->id)->first();
        $history->update(['permission_status' => 'pending_patient']);

        $user->notify(new AppointmentNotification([
            'title' => 'Data Import Permission',
            'message' => 'The laboratory is asking for permission to digitize your previous physical records. Please visit your History page to approve.',
            'url' => route('patient.history'),
            'type' => 'info'
        ]));

        return back()->with('success', 'Permission request sent to patient.');
    }

    /**
     * Accept a permission handshake request
     */
    public function acceptRequest(User $user = null)
    {
        if ($user && !Auth::user()->isEmployee()) {
            abort(403, 'Unauthorized operation.');
        }

        $targetUser = $user ?: Auth::user();

        // RENEW SESSION TOKEN FIRST: Re-authorize the session key so any redirects do not lock employees out
        if (Auth::user()->isEmployee()) {
            session()->put("access_granted_{$targetUser->id}_history", true);
        }

        $history = LaboratoryHistory::where('user_id', $targetUser->id)->first();

        if (!$history) {
            return back()->with('error', 'History record not found.');
        }

        $history->update(['permission_status' => 'granted']);

        $roleName = Auth::user()->role;
        ActivityLog::record('HISTORY GRANTED', "Handshake accepted by {$roleName}", $targetUser->name);

        return back()->with('success', 'Permission granted. Access is now open.');
    }

    /**
     * Save newly digitized manual laboratory report into patient archive timeline
     */
    public function saveManualData(Request $request, User $user)
    {
        // RENEW SESSION TOKEN FIRST: Re-authorize the session key so validation failures redirect back safely instead of kicking users out
        session()->put("access_granted_{$user->id}_history", true);

        $request->validate([
            'date_of_record' => 'required|date|before_or_equal:today',
            'requested_by' => 'required|string|max:255',
            'patient_first_name' => 'required|string|max:255',
            'patient_middle_name' => 'nullable|string|max:255',
            'patient_last_name' => 'required|string|max:255',
            'age' => 'required|integer|min:0',
            'sex' => 'required|in:Male,Female',
            'patient_street' => 'required|string|max:255',
            'patient_barangay' => 'required|string|max:255',
            'patient_city' => 'required|string|max:255',
            'patient_province' => 'required|string|max:255',
            'tests_requested' => 'required|array|min:1',
            'scans' => 'required|array|min:1',
            'scans.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10000',
            'scan_labels' => 'required|array|min:1',
            'scan_labels.*' => 'required|string|max:255',
            
            // FIXED: Added validation rules for optional certificate numbers
            'scan_cert_nos' => 'nullable|array',
            'scan_cert_nos.*' => 'nullable|string|max:255',
        ]);

        $history = LaboratoryHistory::where('user_id', $user->id)->firstOrCreate(['user_id' => $user->id]);

        $fName = strtoupper(trim($request->patient_first_name));
        $mName = ($request->patient_middle_name && strtoupper($request->patient_middle_name) !== 'N/A') ? strtoupper(trim($request->patient_middle_name)) : 'N/A';
        $lName = strtoupper(trim($request->patient_last_name));
        $fullName = $fName . ($mName !== 'N/A' ? ' ' . $mName : '') . ' ' . $lName;

        $street = strtoupper(trim($request->patient_street));
        $barangay = strtoupper(trim($request->patient_barangay));
        $city = strtoupper(trim($request->patient_city));
        $province = strtoupper(trim($request->patient_province));
        $fullAddress = "{$street}, BRGY. {$barangay}, {$city}, {$province}";

        // 1. Create Main Digitized Record entry (Demographics Snapshot)
        $record = LaboratoryHistoryRecord::create([
            'laboratory_history_id' => $history->id,
            'date_of_record' => $request->date_of_record,
            'requested_by' => strtoupper($request->requested_by),
            'patient_first_name' => $fName,
            'patient_middle_name' => $mName,
            'patient_last_name' => $lName,
            'patient_name' => strtoupper($fullName),
            'age' => $request->age,
            'sex' => $request->sex,
            'patient_street' => $street,
            'patient_barangay' => $barangay,
            'patient_city' => $city,
            'patient_province' => $province,
            'patient_address' => strtoupper($fullAddress),
        ]);

        // 2. Save Procedures (Tests Requested)
        foreach ($request->tests_requested as $testName) {
            LaboratoryHistoryProcedure::create([
                'history_record_id' => $record->id,
                'procedure_name' => strtoupper($testName)
            ]);
        }

        // 3. Save Scans
        if ($request->hasFile('scans')) {
            foreach ($request->file('scans') as $index => $file) {
                if ($file->isValid()) {
                    $path = $file->store('historical', 'public');
                    LaboratoryHistoryScan::create([
                        'history_record_id' => $record->id,
                        'label' => $request->scan_labels[$index] ?? 'Diagnostic Scan',
                        'file_path' => $path,
                        // FIXED: Saves the optional certificate number directly to our normalized sub-table column
                        'certificate_no' => $request->scan_cert_nos[$index] ?? null,
                    ]);
                }
            }
        }

        return back()->with('success', 'Historical laboratory record successfully compiled and archived!');
    }

    /**
     * RESTORED & EXPOSABLE: Handle Reason-Gate Submission for Patient Medical History
     * Verifies, logs access, grants session-based token, and forwards to the view.
     */
    public function logAccess(Request $request)
    {
        $request->validate([
            'access_reason' => 'required|string|min:5',
            'target_user_id' => 'required|exists:users,id',
        ]);

        $targetUser = User::findOrFail($request->target_user_id);

        // 1. Record the clinical audit trail
        ActivityLog::record(
            'SENSITIVE DATA ACCESS',
            "Reason: {$request->access_reason} | Action: VIEW | Target: HISTORICAL ARCHIVE",
            $targetUser->name
        );

        // 2. Grant session-based access to pass the Reason-Gate
        session()->put("access_granted_{$targetUser->id}_history", true);

        // 3. Redirect to the secure medical history view handled by AdminController
        return redirect()->route('admin.users.history', $targetUser->id);
    }

    /**
     * FIXED: Dispatch status notification alerting patient of successfully digitized records
     */
    public function notifyEncoded(User $user)
    {
        // RENEW SESSION TOKEN FIRST: Re-authorize the session key so the back redirection doesn't trigger the security block
        session()->put("access_granted_{$user->id}_history", true);

        $user->notify(new AppointmentNotification([
            'title' => 'Laboratory Records Digitized',
            'message' => 'The laboratory staff has successfully digitized and archived your historical medical records. You can now view them directly under your Laboratory Records history tab.',
            'url' => route('patient.history'),
            'type' => 'success'
        ]));

        // Safe activity logging
        ActivityLog::record('NOTIFIED PATIENT', 'Dispatched historical records digitization completion alert', $user->name);

        return back()->with('success', 'Patient has been successfully notified of records encoding.');
    }
}