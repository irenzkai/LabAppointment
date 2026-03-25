<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Dependent;
use App\Models\AppointmentConfig;
use App\Models\AppointmentResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    /**
     * View Appointments (Categorized for Patients, Unified for Staff)
     */
    public function index()
    {
        $user = Auth::user();
        $query = Appointment::with(['services', 'user', 'dependent', 'result'])->latest();

        if ($user->role === 'user') {
            $all = $query->where('user_id', $user->id)->get();
            return view('appointments.index', [
                'self' => $all->filter(fn($a) => is_null($a->dependent_id) && is_null($a->batch_id)),
                'dependents' => $all->filter(fn($a) => !is_null($a->dependent_id)),
                'bulkGroups' => $all->filter(fn($a) => !is_null($a->batch_id))->groupBy('batch_id'),
                'is_staff' => false
            ]);
        }

        // Unified Queue for Staff
        $staffQueue = $query->orderBy('appointment_date', 'asc')
            ->orderBy('time_slot', 'asc')
            ->get()
            ->groupBy(function($item) {
                return $item->batch_id ?? 'single_' . $item->id;
            });

        return view('appointments.index', ['staffQueue' => $staffQueue, 'is_staff' => true]);
    }

    /**
     * Store Individual Booking (Self or Dependent)
     */
    public function store(Request $request) {
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

        // 2. Data Snapshot
        $user = auth()->user();
        if ($request->filled('dependent_id')) {
            $patient = Dependent::findOrFail($request->dependent_id);
            $patientData = [
                'patient_name' => $patient->name,
                'patient_birthdate' => $patient->birthdate,
                'patient_sex' => $patient->sex,
                'patient_address' => $patient->address,
                'patient_email' => null, 'patient_phone' => $patient->phone,
            ];
        } else {
            $patientData = [
                'patient_name' => $user->name,
                'patient_birthdate' => $user->birthdate,
                'patient_sex' => $user->sex,
                'patient_address' => $user->address,
                'patient_email' => $user->email, 'patient_phone' => $user->phone,
            ];
        }

        // 3. Save
        $appointment = Appointment::create(array_merge($patientData, [
            'user_id' => $user->id,
            'dependent_id' => $request->dependent_id,
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending'
        ]));

        $appointment->services()->attach($request->service_ids);
        session()->forget('cart');
        return redirect()->route('appointments.index')->with('success', 'Appointment submitted!');
    }

    /**
     * Transition 1: Approve or Return
     */
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

        return back()->with('success', 'Status updated to ' . strtoupper($request->status));
    }

    /**
     * Transition 2: Mark as Tested (With Duration Input)
     */
    public function markTested(Request $request, Appointment $appointment)
    {
        $hours = (int) $request->input('est_hours', 0);
        $minutes = (int) $request->input('est_minutes', 0);

        $estimatedTime = ($hours > 0 || $minutes > 0) 
            ? now()->addHours($hours)->addMinutes($minutes) 
            : null;

        $appointment->update([
            'status' => 'tested',
            'tested_at' => now(),
            'result_estimated_at' => $estimatedTime
        ]);

        return back()->with('success', 'Patient marked as tested. Counter started.');
    }

    /**
     * Transition 3: Encode and Release Results (Handles Files)
     */
    public function releaseResults(Request $request, Appointment $appointment) 
    {
        // 1. Validation (Ensure at least one report is included)
        if (!$request->has('included_reports')) {
            return back()->withErrors(['error' => 'Please select at least one report to issue.']);
        }

        $data = [];
        $files = ['lab_scan', 'med_cert_scan', 'drug_test_scan', 'radio_scan', 'xray_image'];
        
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $data[$file] = $request->file($file)->store('results', 'public');
            }
        }

        // 2. Save clinical data
        $appointment->result()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            array_merge($data, [
                'included_reports' => $request->included_reports,
                'lab_data'         => $request->lab_data,
                'med_cert_data'    => $request->med_cert_data,
                'drug_test_data'   => $request->drug_test_data,
                'radio_data'       => $request->radio_data
            ])
        );

        // 3. Update Status to RELEASED
        $appointment->update([
            'status' => 'released',
            'results_released_at' => now()
        ]);

        return redirect()->route('appointments.index')->with('success', 'Patient results have been released.');
    }

    /**
     * Resubmit Returned Appointment
     */
    public function update(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== auth()->id()) abort(403);

        $request->validate(['appointment_date' => 'required|date|after_or_equal:today', 'time_slot' => 'required']);

        // Check availability (exclude current ID)
        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();
        $bookedCount = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['pending', 'approved'])->count();

        if ($bookedCount >= ($config->max_patients_per_slot ?? 1)) {
            return back()->withErrors(['error' => 'That time slot is full.']);
        }

        $updateData = [
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending',
            'return_reason' => null
        ];

        // Handle Identity edits for Bulk appointments
        if ($appointment->batch_id) {
            $updateData = array_merge($updateData, $request->only(['patient_name', 'patient_email', 'patient_phone', 'patient_sex', 'patient_birthdate', 'patient_address']));
        }

        $appointment->update($updateData);

        return back()->with('success', 'Appointment resubmitted.');
    }

    /**
     * Show Encoding Form
     */
    public function encodeResults(Appointment $appointment) {
        if (!in_array(auth()->user()->role, ['staff', 'admin'])) abort(403);
        return view('appointments.encode', compact('appointment'));
    }

    public function downloadResult(Appointment $appointment, $type)
    {
        if ($appointment->status !== 'released') abort(403);
        
        $res = $appointment->result;
        $patientSlug = Str::slug($appointment->patient_name);
        $date = now()->format('Y-m-d');
        
        // 1. Logic: Check for direct file requests (X-Ray or Scans)
        $fileMap = [
            'lab' => 'lab_scan',
            'med_cert' => 'med_cert_scan',
            'drug' => 'drug_test_scan',
            'radio' => 'radio_scan',
            'xray' => 'xray_image'
        ];

        $field = $fileMap[$type] ?? null;
        
        if ($field && $res->$field) {
            $path = storage_path('app/public/' . $res->$field);
            if (file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                return response()->download($path, "{$date}_{$patientSlug}_{$type}.{$ext}");
            }
        }

        // 2. Logic: If no scan, generate PDF (only for lab, med_cert, radio)
        if (in_array($type, ['lab', 'med_cert', 'radio'])) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
                'app' => $appointment, 'res' => $res, 'type' => $type
            ]);
            return $pdf->download("{$date}_{$patientSlug}_{$type}.pdf");
        }

        return back()->with('error', 'Result file not available.');
    }
}