<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, ActivityLog};
use App\Traits\HandlesResultFiles;
use App\Events\QueueUpdated; // Import our real-time broadcasting event
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ImagingController extends Controller
{
    use HandlesResultFiles;

    /**
     * WORKSTATION: Radiology Index
     */
    public function radioIndex(Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);

        // Progress Tracking: Mark as 'encoding' if it was 'pending' or 'returned'
        // This notifies the Hub that a technician is actively working on it.
        if (in_array($res->radio_status, ['pending', 'returned'])) {
            $res->update(['radio_status' => 'encoding']);
            
            // Broadcast state update (updates "In Progress" badge on the Hub)
            event(new QueueUpdated());
        }

        // SECURED: Pre-authorize the embedded preview iframe to bypass the Reason-Gate safely
        session()->put("access_granted_{$appointment->id}_radio", true);

        return view('appointments.workstations.radiology', compact('appointment'));
    }

    /**
     * WORKSTATION: Radiology Save
     */
    public function radioSave(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result;

        // 1. Mandatory X-Ray Image Check (Always needed regardless of scan mode)
        if (!$res->xray_image && !$request->hasFile('xray_image')) {
            return back()->with('error', 'Patient X-Ray image is mandatory.');
        }

        // FIXED: Extract case_no and enforce global uniqueness validation across all files [153]
        $request->validate([
            'case_no' => 'required|string|max:255|unique:appointment_radiology_reports,case_no,' . ($res->radiologyReport?->id ?? 'NULL'),
        ]);

        // 2. Handle File Uploads via Trait (Cleans up old files automatically)
        $this->uploadResultFile($request, $appointment, 'xray_image');

        $radioData = $request->input('radio_data');

        // Check if any manual entries (findings/impression) are submitted in the request
        $hasManualInput = !empty($request->input('radio_data.findings')) || !empty($request->input('radio_data.impression'));

        /**
         * FIXED: Robust clearing mechanism. If the clear flag is set OR the technician 
         * has submitted manual findings, automatically delete and clear the existing report scan.
         */
        if ($request->input('clear_scan') == '1' || $hasManualInput) {
            if ($res->radio_scan) {
                Storage::disk('public')->delete($res->radio_scan);
                $res->update(['radio_scan' => null]);
            }
        } else {
            $this->uploadResultFile($request, $appointment, 'radio_scan');
        }

        $res->refresh(); // Get updated scan path if just uploaded
        if ($res->radio_scan) {
            // FIXED: Do not discard case_no when uploading a scanned copy [153]
            $radioData = [
                'metadata' => [
                    'case_no' => $request->input('case_no'),
                    'date' => $request->input('radio_data.metadata.date') ?? now()->format('Y-m-d'),
                    'age_sex' => $request->input('radio_data.metadata.age_sex') ?? ($appointment->patient_age . ' / ' . $appointment->patient_sex),
                ],
                'findings' => null,
                'impression' => null,
                'sig' => []
            ];
        } else {
            // Ensure case_no is bound to metadata block for manual findings
            if (is_array($radioData)) {
                $radioData['metadata']['case_no'] = $request->input('case_no');
            }
        }

        // 4. Update the Database with Hub Audit Info
        $res->update([
            'radio_data' => $radioData,
            'radio_status' => 'encoded', // Ready for Hub validation
            
            // HUB PROGRESS TRACKER: Record the account name that performed the encoding
            'radio_v1_by_name' => auth()->user()->name,
            'radio_v1_at' => now(),
            'radio_v1_by' => auth()->id(),
            
            // CLINICAL SIGNATORY: Manually typed name/lic (if not in scan mode)
            'radio_sig_name' => $radioData['sig']['name'] ?? null,
            'radio_sig_lic' => $radioData['sig']['lic'] ?? null,
            
            'radio_return_reason' => null // Clear correction instructions
        ]);

        ActivityLog::record(
            'ENCODED', 
            'Radiology workstation updated' . ($res->radio_scan ? ' (Scan Override)' : ''),
            $appointment->patient_name,
            $appointment->id
        );

        // Dispatch real-time refresh to the Results Hub and Master Queue
        event(new QueueUpdated());

        return redirect()->route('appointments.encode', $appointment->id)
            ->with('success', 'Radiology report saved and sent for verification.');
    }

    /**
     * WORKSTATION: Drug Test Index
     */
    public function drugIndex(Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);

        // Progress Tracking: Mark as 'encoding' if it was 'pending' or 'returned'
        if (in_array($res->drug_status, ['pending', 'returned'])) {
            $res->update(['drug_status' => 'encoding']);
            event(new QueueUpdated());
        }

        // SECURED: Pre-authorize the embedded preview iframe to bypass the Reason-Gate safely
        session()->put("access_granted_{$appointment->id}_drug", true);

        return view('appointments.workstations.drug', compact('appointment'));
    }

    /**
     * WORKSTATION: Drug Test Save
     */
    public function drugSave(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result;

        // FIXED: If clear_scan is '1', we enforce that a file is strictly required, blocking fileless resubmits [190]
        $isScanCleared = ($request->input('clear_scan') == '1');
        $isScanRequired = !$res->drug_test_scan || $isScanCleared;

        $request->validate([
            'cert_no' => 'required|string|max:255',
            'drug_test_scan' => ($isScanRequired ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Handle file uploads with static cleanup support
        if ($isScanCleared) {
            if ($res->drug_test_scan) {
                Storage::disk('public')->delete($res->drug_test_scan);
                $res->update(['drug_test_scan' => null]);
            }
        } else {
            $this->uploadResultFile($request, $appointment, 'drug_test_scan');
        }

        $res->refresh();

        // Save Certificate Number inside the JSON schema
        $drugData = [
            'metadata' => [
                'cert_no' => $request->input('cert_no'),
                'date' => now()->format('Y-m-d'),
            ]
        ];

        $res->update([
            'drug_test_data' => $drugData,
            'drug_status' => 'encoded',
            
            // Workstation Audit Columns for Drug Test
            'drug_v1_by_name' => auth()->user()->name,
            'drug_v1_at' => now(),
            'drug_v1_by' => auth()->id(),
            
            'drug_return_reason' => null
        ]);

        ActivityLog::record(
            'ENCODED', 
            'Drug test result saved' . ($res->drug_test_scan ? ' (Scan Override)' : ''),
            $appointment->patient_name,
            $appointment->id
        );

        event(new QueueUpdated());

        return redirect()->route('appointments.encode', $appointment->id)
            ->with('success', 'Drug test result saved and sent for verification.');
    }
}