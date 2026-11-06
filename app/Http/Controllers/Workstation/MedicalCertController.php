<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, User, ActivityLog};
use App\Traits\HandlesResultFiles;
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MedicalCertController extends Controller
{
    use HandlesResultFiles;

    /**
     * WORKSTATION: Medical Certificate Index.
     * Pre-loads registered Attending Physicians for automated 1-click signatory selection.
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

        // Query registered attending physicians
        $physicians = User::physicians()->where('is_active', true)->orderBy('last_name')->get();
        if ($physicians->isEmpty()) {
            $physicians = User::whereIn('role', ['admin', 'lab_tech'])->where('is_active', true)->orderBy('last_name')->get();
        }

        // SECURED: Pre-authorize the embedded preview iframe to bypass the Reason-Gate safely
        session()->put("access_granted_{$appointment->id}_med_cert", true);

        return view('appointments.workstations.medical', compact('appointment', 'physicians'));
    }

    /**
     * WORKSTATION: Save Medical Certificate Results.
     */
    public function save(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result;

        // Extract cert_no and enforce global uniqueness validation across all files
        $request->validate([
            'cert_no' => 'required|string|max:255|unique:appointment_med_certs,cert_no,' . ($res->medCert?->id ?? 'NULL'),
        ]);

        $medData = $request->input('med_cert_data', []);

        /**
         * 1. SCAN PURGE / RESET LOGIC
         * If the scan was cleared on the front-end, remove the old file asset to safely switch back to manual entry mode.
         */
        if ($request->input('clear_scan') == '1') {
            if ($res->med_cert_scan) {
                Storage::disk('public')->delete($res->med_cert_scan);
                $res->update(['med_cert_scan' => null]);
            }
        }

        /**
         * 2. SCAN PRIORITY LOGIC
         * If a new scan is attached, empty the findings/remarks arrays so the PDF generator knows to prioritize rendering only the uploaded document.
         */
        if ($request->hasFile('med_cert_scan')) {
            $this->uploadResultFile($request, $appointment, 'med_cert_scan');

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

            // Automated Signatory Resolution via Physician User ID (Fallback if names/licenses are not typed manually)
            if ($request->filled('physician_user_id')) {
                $doctor = User::find($request->input('physician_user_id'));
                if ($doctor) {
                    $medData['sig']['name'] = $doctor->name;
                    $medData['sig']['lic'] = $doctor->license_number ?? '';
                }
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