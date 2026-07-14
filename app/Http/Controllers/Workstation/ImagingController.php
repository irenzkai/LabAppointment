<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, ActivityLog};
use App\Traits\HandlesResultFiles;
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
        if (in_array($res->radio_status, ['pending', 'returned'])) {
            $res->update(['radio_status' => 'encoding']);
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

        // 2. Handle File Uploads via Trait (Cleans up old files automatically)
        $this->uploadResultFile($request, $appointment, 'xray_image');

        if ($request->input('clear_scan') == '1') {
            if ($res->radio_scan) {
                Storage::disk('public')->delete($res->radio_scan);
                $res->update(['radio_scan' => null]);
            }
        } else {
            $this->uploadResultFile($request, $appointment, 'radio_scan');
        }

        // 3. Process Data
        $radioData = $request->input('radio_data');

        /**
         * SCAN PRIORITY LOGIC
         * If a report scan is attached, we nullify manual text and metadata.
         * This ensures the PDF generator only sees the scan.
         */
        $res->refresh(); // Get updated scan path if just uploaded
        if ($res->radio_scan) {
            $radioData = [
                'metadata' => [],
                'findings' => null,
                'impression' => null,
                'sig' => []
            ];
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

        ActivityLog::record('ENCODED', 'Radiology workstation updated' . ($res->radio_scan ? ' (Scan Override)' : ''), $appointment->patient_name, $appointment->id);

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

        if (in_array($res->drug_status, ['pending', 'returned'])) {
            $res->update(['drug_status' => 'encoding']);
        }

        // SECURED: Pre-authorize the embedded preview iframe to bypass the Reason-Gate safely
        session()->put("access_granted_{$appointment->id}_drug", true);

        return view('appointments.workstations.drug', compact('appointment'));
    }

    /**
     * WORKSTATION: Drug Test Save (Strictly Scan-Based)
     */
    public function drugSave(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        // Drug Test strictly requires a scan upload
        if (!$appointment->result?->drug_test_scan && !$request->hasFile('drug_test_scan')) {
            return back()->with('error', 'Official Drug Test scan is required.');
        }

        $this->uploadResultFile($request, $appointment, 'drug_test_scan');

        // Update progress for the Hub tracker
        $appointment->result->update([
            'drug_status' => 'encoded',
            'drug_v1_by_name' => auth()->user()->name, // System User
            'drug_v1_at' => now(),
            'drug_v1_by' => auth()->id(),
            'drug_return_reason' => null
        ]);

        ActivityLog::record('ENCODED', 'Drug test scan uploaded', $appointment->patient_name, $appointment->id);

        return redirect()->route('appointments.encode', $appointment->id)
            ->with('success', 'Drug test result uploaded.');
    }
}