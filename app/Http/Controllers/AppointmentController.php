<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Notifications\AppointmentNotification;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Dependent;
use App\Models\AppointmentConfig;
use App\Models\AppointmentResult;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    /**
     * View Appointments (Categorized for Patients, Unified for Lab Personnel)
     */
    public function index()
    {
        $user = Auth::user();
        $query = Appointment::with(['services', 'user', 'dependent', 'result'])->latest();

        if ($user->isPatient()) {
            $all = $query->where('user_id', $user->id)->get();
            return view('appointments.index', [
                'self' => $all->filter(fn($a) => is_null($a->dependent_id) && is_null($a->batch_id)),
                'dependents' => $all->filter(fn($a) => !is_null($a->dependent_id)),
                'bulkGroups' => $all->filter(fn($a) => !is_null($a->batch_id))->groupBy('batch_id'),
                'is_staff' => false
            ]);
        }

        $staffQueue = $query->orderBy('appointment_date', 'asc')
            ->orderBy('time_slot', 'asc')
            ->get()
            ->groupBy(function($item) {
                return $item->batch_id ?? 'single_' . $item->id;
            });

        return view('appointments.index', ['staffQueue' => $staffQueue, 'is_staff' => true]);
    }

    /**
     * Store Appointment (Booking logic)
     */
    public function store(Request $request) 
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
            'service_ids' => 'required|array',
            'dependent_id' => 'nullable|exists:dependents,id',
        ]);

        // 1. Capacity Check
        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();
        $bookedCount = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])->count();

        if ($bookedCount >= ($config->max_patients_per_slot ?? 1)) {
            return back()->withErrors(['time_slot' => 'This slot is now full.']);
        }

        // 2. Data Snapshot (Copying info to the record)
        $user = auth()->user();
        if ($request->filled('dependent_id')) {
            $patient = Dependent::findOrFail($request->dependent_id);
            $patientData = [
                'patient_name' => $patient->name,
                'patient_birthdate' => $patient->birthdate,
                'patient_sex' => $patient->sex,
                'patient_address' => $patient->address,
                'patient_phone' => $patient->phone,
            ];
        } else {
            $patientData = [
                'patient_name' => $user->name,
                'patient_birthdate' => $user->birthdate,
                'patient_sex' => $user->sex,
                'patient_address' => $user->address,
                'patient_email' => $user->email, 
                'patient_phone' => $user->phone,
            ];
        }

        $appointment = Appointment::create(array_merge($patientData, [
            'user_id' => $user->id,
            'dependent_id' => $request->dependent_id,
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending'
        ]));

        $appointment->services()->attach($request->service_ids);
        session()->forget('cart');

        // 3. Notify Internal Staff
        $notifiables = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach($notifiables as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'New Appointment Request',
                'message' => "New request by " . $user->name,
                'type' => 'info'
            ]));
        }

        ActivityLog::record('BOOKED', 'New appointment submitted', $patientData['patient_name'], $appointment->id);

        return redirect()->route('appointments.index')->with('success', 'Appointment submitted!');
    }

    /**
     * Transition 1: Approve or Return (Front Desk)
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);
        $request->validate(['status' => 'required|in:approved,returned', 'return_reason' => 'required_if:status,returned']);

        $appointment->update([
            'status' => $request->status,
            'return_reason' => ($request->status == 'returned') ? $request->return_reason : null,
        ]);

        return back()->with('success', 'Appointment ' . $request->status);
    }

    /**
     * Transition 2: Mark as Tested (Clinical Sampling - Tech Only)
     */
    public function markTested(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isLabTech')) abort(403, 'Only Technicians can perform sampling.');

        $hours = (int) $request->input('est_hours', 0);
        $minutes = (int) $request->input('est_minutes', 0);
        $estimatedTime = ($hours > 0 || $minutes > 0) ? now()->addHours($hours)->addMinutes($minutes) : null;

        $appointment->update([
            'status' => 'tested',
            'tested_at' => now(),
            'result_estimated_at' => $estimatedTime
        ]);

        ActivityLog::record('PATIENT TESTED', 'Sampling completed', $appointment->patient_name, $appointment->id);
        return back()->with('success', 'Patient marked as tested.');
    }

    /**
     * Transition 3: Encode Results (Initial Save)
     */
    public function releaseResults(Request $request, Appointment $appointment) 
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'included_reports' => 'required|array',
            'lab_scan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'med_cert_scan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'drug_test_scan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'radio_scan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'xray_image' => 'nullable|image|max:10240',
        ]);

        $fileData = [];
        $files = ['lab_scan', 'med_cert_scan', 'drug_test_scan', 'radio_scan', 'xray_image'];
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                if ($appointment->result && $appointment->result->$file) {
                    Storage::disk('public')->delete($appointment->result->$file);
                }
                $fileData[$file] = $request->file($file)->store('results', 'public');
            }
        }

        // Combine data AND explicitly reset all verification columns to NULL
        $appointment->result()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            array_merge($fileData, [
                'included_reports' => $request->included_reports,
                'lab_data' => $request->lab_data,
                'med_cert_data' => $request->med_cert_data,
                'drug_test_data' => $request->drug_test_data,
                'radio_data' => $request->radio_data,
                // Reset verifications as requested earlier
                'lab_v1_by' => null, 'lab_v1_at' => null,
                'lab_v2_by' => null, 'lab_v2_at' => null,
                'med_verified_by' => null, 'med_verified_at' => null,
                'drug_verified_by' => null, 'drug_verified_at' => null,
                'radio_verified_by' => null, 'radio_verified_at' => null,
            ])
        );

        $appointment->update(['status' => 'encoded']);

        ActivityLog::record('SAVED DATA', 'Technician saved clinical data', $appointment->patient_name, $appointment->id);

         return redirect()->route('appointments.index')->with('success', 'Data saved successfully.');
    }

    /**
     * Transition 4: Clinical Verification
     */
    public function verifyResult(Request $request, Appointment $appointment, $type) 
    {
        if (Gate::denies('isLabTech')) abort(403);
        $res = $appointment->result;
        $user = auth()->user();

        if ($type == 'lab') {
            if (!$res->lab_v1_at) {
                $res->update(['lab_v1_by' => $user->id, 'lab_v1_at' => now()]);
                $msg = "First Lab verification successful.";
            } elseif (!$res->lab_v2_at && $res->lab_v1_by != $user->id) {
                $res->update(['lab_v2_by' => $user->id, 'lab_v2_at' => now()]);
                $msg = "Final Lab verification successful.";
            } else {
                return back()->with('error', 'Requires a different technician for second verification.');
            }
        } else {
            $field = ($type == 'med_cert' ? 'med' : $type) . '_verified_';
            $res->update([$field.'by' => $user->id, $field.'at' => now()]);
            $msg = strtoupper($type) . " verified.";
        }

        // Auto-Release check
        if ($appointment->isFullyVerified()) {
            $appointment->update(['status' => 'released', 'results_released_at' => now()]);
            $msg = "All verifications complete. Results released to patient.";
        }

        // REDIRECT TO APPOINTMENTS PAGE
        return redirect()->route('appointments.index')->with('success', $msg);
    }

    private function isFullyVerified($app) {
        $res = $app->result;
        if (!$res) return false;
        
        $reports = $res->included_reports ?? [];
        if (empty($reports)) return false;

        // Laboratory: Must have TWO timestamps (v1 AND v2)
        if (in_array('lab', $reports)) {
            if (!$res->lab_v1_at || !$res->lab_v2_at) return false;
        }

        // Others: Must have ONE timestamp
        if (in_array('med_cert', $reports) && !$res->med_verified_at) return false;
        if (in_array('drug', $reports) && !$res->drug_verified_at) return false;
        if (in_array('radio', $reports) && !$res->radio_verified_at) return false;

        return true;
    }

    /**
     * Access Result (Preview/Download)
     */
    public function accessResult(Appointment $appointment, $type, $mode)
    {
        // Check if the user is internal personnel
        if (Auth::user()->isEmployee()) {
            // If they are not the owner of the record, check for the session reason
            if (Auth::id() !== $appointment->user_id) {
                if (!session()->has("access_granted_{$appointment->id}_{$type}")) {
                    return redirect()->route('appointments.index')
                        ->with('error', 'Authorization required to access clinical data.');
                }
                session()->forget("access_granted_{$appointment->id}_{$type}");
            }
        }

        $res = $appointment->result;
        
        $fileMap = ['lab'=>'lab_scan', 'med_cert'=>'med_cert_scan', 'drug'=>'drug_test_scan', 'radio'=>'radio_scan', 'xray'=>'xray_image'];
        $column = $fileMap[$type] ?? null;

        // Image Overwrite logic
        if ($column && $res && $res->$column) {
            $path = Storage::disk('public')->path($res->$column);
            if (file_exists($path)) {
                return $mode === 'preview' ? response()->file($path) : response()->download($path);
            }
        }

        $pdf = Pdf::loadView('pdf.report', ['app' => $appointment, 'res' => $res, 'type' => $type]);
        return $mode === 'preview' ? $pdf->stream() : $pdf->download("Result_{$type}.pdf");
    }

    /**
     * Show Encoding Form (Locked behind Reason for Staff/LabTech)
     */
    public function encodeResults(Appointment $appointment) 
    {
        if (Gate::denies('isStaff')) abort(403);

        // Only check for the session 'unlock' if it's an edit (status is not tested)
        if ($appointment->status !== 'tested') {
            if (!session()->has("access_granted_{$appointment->id}_edit")) {
                return redirect()->route('appointments.index')->with('error', 'Authorization required to modify results.');
            }
            // Forget it so they have to provide a reason next time they enter
            session()->forget("access_granted_{$appointment->id}_edit");
        }

        return view('appointments.encode', compact('appointment'));
    }

    /**
     * Unified Log Access Handler
     */
    public function logAccess(Request $request, Appointment $appointment = null) {
        $request->validate([
            'access_reason' => 'required|string|min:5', 
            'type' => 'required', 
            'mode' => 'required'
        ]);

        // CASE A: User Archive/History Access
        if ($request->mode === 'history') {
            $userId = $request->input('target_user_id');
            $patient = User::findOrFail($userId);

            ActivityLog::record('HISTORY ACCESS', $request->access_reason, $patient->name);
            
            // Grant session permission for the History page
            session()->put("access_granted_{$userId}_history", true);
            
            // Redirect to the route named in your web.php (Line 125)
            return redirect()->route('admin.patient-history', $userId);
        }

        // CASE B: Specific Appointment Result Access
        if (!$appointment) {
            return redirect()->route('appointments.index')->with('error', 'Appointment ID missing.');
        }

        ActivityLog::record(strtoupper($request->mode) . ' ACCESS', $request->access_reason, $appointment->patient_name, $appointment->id);
        
        // Grant session permission for the specific file
        session()->put("access_granted_{$appointment->id}_{$request->type}", true);

        if ($request->mode === 'edit') {
            return redirect()->route('appointments.encode', $appointment->id);
        }

        // Redirect to the result access route (Line 61)
        return redirect()->route('appointments.result.access', [
            'appointment' => $appointment->id,
            'type' => $request->type,
            'mode' => 'preview'
        ]);
    }
}