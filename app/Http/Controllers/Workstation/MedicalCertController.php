<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, ActivityLog};
use App\Traits\HandlesResultFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MedicalCertController extends Controller
{
    use HandlesResultFiles;

    /**
     * WORKSTATION: Medical Certificate Index
     */
    public function index(Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        // Ensure result record exists
        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);

        // Progress Tracking: Mark as 'encoding' if it was 'pending' or 'returned'
        // This notifies the Hub that a technician is actively working on the certificate.
        if (in_array($res->med_status, ['pending', 'returned'])) {
            $res->update(['med_status' => 'encoding']);
        }

        // SECURED: Pre-authorize the embedded preview iframe to bypass the Reason-Gate safely
        session()->put("access_granted_{$appointment->id}_med_cert", true);

        return view('appointments.workstations.medical', compact('appointment'));
    }

    /**
     * WORKSTATION: Save Medical Certificate Results
     */
    public function save(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result;
        $medData = $request->input('med_cert_data');

        /**
         * 1. SCAN PRIORITY LOGIC
         * If a scan is attached, all other manual entries disappear.
         * We physically empty the med_cert_data arrays before saving if a file is present.
         */
        if ($request->hasFile('med_cert_scan')) {
            $this->uploadResultFile($request, $appointment, 'med_cert_scan');

            $medData = [
                'metadata' => [],
                'findings' => null,
                'remarks' => null,
                'sig' => []
            ]; 
        }

        /**
         * 2. HUB AUDIT VS CLINICAL SIGNATORY
         * Here we capture the ACTUAL SYSTEM ACCOUNT for the "Encoded By" column in the Hub.
         * The manually typed Physician details stay inside $medData for the PDF generator.
         */
        $systemAccountName = auth()->user()->name;

        // 3. Update the Result Record
        $res->update([
            'med_cert_data' => $medData,
            'med_status' => 'encoded', // Signals the Hub that it's ready for verification
            
            // SYSTEM AUDIT COLUMNS (Used by the Hub tracker)
            'med_v1_by_name' => $systemAccountName, 
            'med_v1_at' => now(),
            'med_v1_by' => auth()->id(),
            
            // Clear any previous return instructions
            'med_return_reason' => null 
        ]);

        // 4. System Logging
        ActivityLog::record(
            'ENCODED', 
            'Medical Certificate saved' . ($request->hasFile('med_cert_scan') ? ' (Scan Mode)' : ' (Manual Entry)'), 
            $appointment->patient_name, 
            $appointment->id
        );

        // 5. Redirect back to Hub (Results Management Hub)
        return redirect()->route('appointments.encode', $appointment->id)
            ->with('success', 'Medical Certificate saved and sent for verification.');
    }
}