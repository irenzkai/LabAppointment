<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, ActivityLog};
use App\Traits\HandlesResultFiles;
use App\Events\QueueUpdated; // Import our real-time broadcasting event
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
            
            // Broadcast state update (updates "In Progress" badge on the Hub)
            event(new QueueUpdated());
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

        // FIXED: Extract cert_no and enforce global uniqueness validation across all files [153]
        $request->validate([
            'cert_no' => 'required|string|max:255|unique:appointment_med_certs,cert_no,' . ($res->medCert?->id ?? 'NULL'),
        ]);

        $medData = $request->input('med_cert_data');

        /**
         * 1. SCAN PURGE / RESET LOGIC
         * If the scan was cleared on the front-end, remove the old file asset to 
         * safely switch back to manual entry mode.
         */
        if ($request->input('clear_scan') == '1') {
            if ($res->med_cert_scan) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($res->med_cert_scan);
                $res->update(['med_cert_scan' => null]);
            }
        }

        /**
         * 2. SCAN PRIORITY LOGIC
         * If a new scan is attached, empty the findings/remarks arrays so the PDF generator 
         * knows to prioritize rendering only the uploaded document.
         */
        if ($request->hasFile('med_cert_scan')) {
            $this->uploadResultFile($request, $appointment, 'med_cert_scan');

            // FIXED: Do not discard cert_no when uploading a scanned copy [153]
            $medData = [
                'metadata' => [
                    'cert_no' => $request->input('cert_no'),
                    'date' => $request->input('med_cert_data.metadata.date') ?? now()->format('Y-m-d'),
                ],
                'findings' => null,
                'remarks' => null,
                'sig' => []
            ]; 
        } else {
            // Ensure cert_no is bound to metadata block for manual findings
            if (is_array($medData)) {
                $medData['metadata']['cert_no'] = $request->input('cert_no');
            }
        }

        // 3. Update the Result Record
        $res->update([
            'med_cert_data' => $medData,
            'med_status' => 'encoded', // Signals the Hub that it's ready for verification
            
            // SYSTEM AUDIT COLUMNS (Used by the Hub tracker)
            'med_v1_by_name' => auth()->user()->name, 
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

        // Dispatch real-time refresh to the Results Hub and Master Queue
        event(new QueueUpdated());

        // 5. Redirect back to Hub (Results Management Hub)
        return redirect()->route('appointments.encode', $appointment->id)
            ->with('success', 'Medical Certificate saved and sent for verification.');
    }
}