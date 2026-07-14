<?php

namespace App\Http\Controllers;

use App\Models\{
    Appointment, 
    ActivityLog, 
    AppointmentMedCert, 
    AppointmentRadiologyReport, 
    AppointmentLabDetail,
    LaboratoryHistory,
    LaboratoryHistoryRecord,
    LaboratoryHistoryScan,
    User
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Gate, Storage, URL}; // Imported URL facade to generate cryptographically signed verification links

class ResultController extends Controller
{
    /**
     * THE HUB: Displays the progress tracker for all required forms.
     * This is the central command center for viewing and modifying results.
     */
    public function hub(Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        // SECURITY: Check if staff passed the logAccess reason gate for the 'hub'
        // If the appointment is already released, we require an audit reason to enter.
        if ($appointment->status === 'released') {
            if (!session()->has("access_granted_{$appointment->id}_hub")) {
                return redirect()->route('appointments.index')
                    ->with('error', 'Clinical authorization required to access this patient folder.');
            }
            // One-time use session key
            session()->forget("access_granted_{$appointment->id}_hub");
        }

        // Map services to required workstation types
        $serviceNames = $appointment->services->pluck('name')->map(fn($n) => strtoupper($n))->toArray();
        $autoReportTypes = [];

        foreach ($serviceNames as $name) {
            if (str_contains($name, 'DRUG TEST')) $autoReportTypes[] = 'drug';
            elseif (str_contains($name, 'XRAY') || str_contains($name, 'X-RAY')) $autoReportTypes[] = 'radio';
            elseif (str_contains($name, 'MEDICAL CERTIFICATE')) $autoReportTypes[] = 'med_cert';
            else $autoReportTypes[] = 'lab';
        }

        $autoReportTypes = array_unique($autoReportTypes);

        // Sync the results record with the list of expected reports for this folder
        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);
        $res->update(['included_reports' => $autoReportTypes]);

        return view('appointments.encode', [
            'appointment' => $appointment,
            'autoReportTypes' => $autoReportTypes
        ]);
    }

    /**
     * VERIFY: System sign-off for a specific form.
     */
    public function verify(Request $request, Appointment $appointment, $type)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate(['sig_name' => 'required|string|max:255']);

        $res = $appointment->result;
        $prefix = ($type == 'med_cert') ? 'med' : $type;

        $updateData = [
            "{$prefix}_status" => 'verified',
            "{$prefix}_v2_by_name" => $request->sig_name,
        ];

        if ($type === 'lab') {
            $updateData['lab_v2_at'] = now();
            $updateData['lab_v2_by'] = auth()->id();
        } else {
            $updateData["{$prefix}_verified_at"] = now();
            $updateData["{$prefix}_verified_by"] = auth()->id();
        }

        $res->update($updateData);

        ActivityLog::record('VERIFIED', "Clinical sign-off for $type", $appointment->patient_name, $appointment->id);

        return redirect()->route('appointments.encode', $appointment->id)
            ->with('success', strtoupper($type) . ' verified.');
    }

    /**
     * RETURN: Send a form back to 'encoding' status for correction.
     */
    public function return(Request $request, Appointment $appointment)
    {
        $request->validate(['reason' => 'required|min:5']);

        $type = $request->query('type', 'lab');
        $prefix = ($type == 'med_cert') ? 'med' : $type;

        $updateData = [
            "{$prefix}_status" => 'returned',
            "{$prefix}_return_reason" => $request->reason,
            "{$prefix}_v2_by_name" => null
        ];

        if ($type === 'lab') {
            $updateData['lab_v2_at'] = null;
            $updateData['lab_v2_by'] = null;
        } else {
            $updateData["{$prefix}_verified_at"] = null;
            $updateData["{$prefix}_verified_by"] = null;
        }

        $appointment->result->update($updateData);

        ActivityLog::record('RETURNED', "Form ($type) sent back: " . $request->reason, $appointment->patient_name, $appointment->id);

        return redirect()->route('appointments.encode', $appointment->id)
            ->with('info', 'Form sent back for correction.');
    }

    /**
     * LOG ACCESS: The "Reason-Gate".
     * Validates why staff is accessing records and grants temporary session access.
     */
    public function logAccess(Request $request, Appointment $appointment)
    {
        $request->validate([
            'access_reason' => 'required|string|min:5',
            'type' => 'required', // can be 'hub', 'lab', 'radio', etc.
            'mode' => 'required' // 'edit' or 'preview'
        ]);

        // 1. Record the audit trail
        ActivityLog::record(
            'SENSITIVE DATA ACCESS',
            "Reason: {$request->access_reason} | Action: " . strtoupper($request->mode) . " | Target: " . strtoupper($request->type),
            $appointment->patient_name,
            $appointment->id
        );

        // 2. Grant session-based access
        session()->put("access_granted_{$appointment->id}_{$type}", true);

        // 3. UNIFIED REDIRECT: If type is 'hub', go to the Hub. Otherwise, go to file.
        if ($request->type === 'hub') {
            return redirect()->route('appointments.encode', $appointment->id);
        }

        return redirect()->route('appointments.result.access', [
            'appointment' => $appointment->id,
            'type' => $request->type,
            'mode' => $request->mode
        ]);
    }

    /**
     * ACCESS: serve physical scans first, then fall back to generated PDF.
     */
    public function access(Appointment $appointment, $type, $mode)
    {
        $user = Auth::user();

        $isOwner = $user->id === $appointment->user_id;
        $isStaff = $user->isEmployee();

        // 1. Basic Auth
        if (!$isOwner && !$isStaff) abort(403);

        // 2. Reason-Gate Check for Staff
        if ($isStaff && !$isOwner) {
            if (!session()->has("access_granted_{$appointment->id}_{$type}")) {
                return redirect()->route('appointments.index')
                    ->with('error', 'Clinical authorization required.');
            }
            session()->forget("access_granted_{$appointment->id}_{$type}");
        }

        $res = $appointment->result;
        $fileMap = [
            'lab' => 'lab_scan', 'med_cert' => 'med_cert_scan', 
            'drug' => 'drug_test_scan', 'radio' => 'radio_scan', 'xray' => 'xray_image'
        ];

        $column = $fileMap[$type] ?? null;

        // 1. Priority: Physical Scan
        if ($column && $res && $res->$column) {
            $path = Storage::disk('public')->path($res->$column);
            if (file_exists($path)) {
                return $mode === 'preview' ? response()->file($path) : response()->download($path);
            }
        }

        // 2. Fallback: Generate PDF dynamically using the new specialized view files
        $viewMap = [
            'lab' => 'pdf.labreport',
            'drug' => 'pdf.labreport',
            'med_cert' => 'pdf.medcert',
            'radio' => 'pdf.radio',
            'xray' => 'pdf.radio',
        ];
        
        $viewName = $viewMap[$type] ?? 'pdf.labreport';

        $pdf = Pdf::loadView($viewName, [
            'app' => $appointment, 
            'res' => $res, 
            'type' => $type
        ]);

        $filename = "Result_{$type}_{$appointment->id}.pdf";

        return $mode === 'preview' ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * PUBLIC QR VERIFICATION: Validate and display verified clinical records.
     */
    public function verifyPublic(Appointment $appointment)
    {
        $res = $appointment->result;
        return view('verify-result', compact('appointment', 'res'));
    }

    /**
     * NEW: PUBLIC HISTORICAL QR VERIFICATION: Validate and display verified digitized historical records.
     */
    public function verifyHistoryPublic(User $user)
    {
        $labHistory = LaboratoryHistory::where('user_id', $user->id)->first();
        
        // Retrieve the verified digitized records in chronological order
        $existingRecords = LaboratoryHistoryRecord::whereHas('laboratoryHistory', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['scans', 'procedures'])->latest('date_of_record')->get();
        
        return view('verify-history', compact('user', 'existingRecords'));
    }

    /**
     * PUBLIC SEARCH VERIFICATION: Search and resolve secure signed redirects.
     */
    public function verifySearch(Request $request)
    {
        $query = $request->query('query');
        if ($query) {
            // Strip whitespace
            $query = trim($query);

            // 1. Resolve by Laboratory Case Number (e.g., 2345345)
            $labDetail = AppointmentLabDetail::where('case_no', $query)->first();
            if ($labDetail && $labDetail->result && $labDetail->result->appointment) {
                return redirect()->to(URL::signedRoute('result.verify-public', ['appointment' => $labDetail->result->appointment->id]));
            }

            // 2. Resolve by Medical Certificate Number (e.g., 01261065)
            $medCert = AppointmentMedCert::where('cert_no', $query)->first();
            if ($medCert && $medCert->result && $medCert->result->appointment) {
                return redirect()->to(URL::signedRoute('result.verify-public', ['appointment' => $medCert->result->appointment->id]));
            }

            // 3. Resolve by Radiology Case Number (e.g., MDL-0225806)
            $radReport = AppointmentRadiologyReport::where('case_no', $query)->first();
            if ($radReport && $radReport->result && $radReport->result->appointment) {
                return redirect()->to(URL::signedRoute('result.verify-public', ['appointment' => $radReport->result->appointment->id]));
            }

            // FIXED: Added public validation support for optional Certificate Numbers on digitized historical scans
            $scan = LaboratoryHistoryScan::where('certificate_no', $query)->first();
            if ($scan && $scan->record && $scan->record->laboratoryHistory && $scan->record->laboratoryHistory->user) {
                $user = $scan->record->laboratoryHistory->user;
                return redirect()->to(URL::signedRoute('history.verify-public', ['user' => $user->id]));
            }

            return back()->with('error', 'No clinical record found matching: ' . $query);
        }

        return view('verify-search');
    }

    /**
     * Check if all required components are 'verified'.
     */
    private function checkAllVerified($appointment)
    {
        $res = $appointment->result;
        $required = $res->included_reports ?? [];

        if (empty($required)) return false;

        foreach ($required as $type) {
            $prefix = ($type == 'med_cert') ? 'med' : $type;
            $statusField = $prefix . '_status';
            if (($res->$statusField ?? 'pending') !== 'verified') {
                return false;
            }
        }
        return true;
    }
}