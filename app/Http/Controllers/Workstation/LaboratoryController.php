<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, Service, ActivityLog};
use App\Traits\HandlesResultFiles;
use App\Events\QueueUpdated; // Import our real-time broadcasting event
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LaboratoryController extends Controller
{
    use HandlesResultFiles;

    /**
     * Display the Laboratory Workstation.
     */
    public function index(Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        // Ensure result record exists
        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);

        // Progress Tracking: Mark as 'encoding' if it was 'pending' or 'returned'
        // This notifies the Hub that a technician is actively working on it.
        if (in_array($res->lab_status, ['pending', 'returned'])) {
            $res->update(['lab_status' => 'encoding']);
            
            // Broadcast state update (updates "In Progress" badge on the Hub)
            event(new QueueUpdated());
        }

        // Fetch individual services for the dynamic workstation search dropdown
        $allServices = Service::where('category', 'individual')
            ->where('is_available', true)
            ->get();

        // SECURED: Pre-authorize the embedded preview iframe to bypass the Reason-Gate safely
        session()->put("access_granted_{$appointment->id}_lab", true);

        return view('appointments.workstations.lab', compact('appointment', 'allServices'));
    }

    /**
     * Save Laboratory Data (Handles Metadata, Manual Entry results, and Scans).
     */
    public function save(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result;

        // FIXED: Extract case_no and enforce global uniqueness validation across all files [153]
        $request->validate([
            'case_no' => 'required|string|max:255|unique:appointment_lab_details,case_no,' . ($res->labDetails?->id ?? 'NULL'),
        ]);

        $labData = $request->input('lab_data');

        /**
         * 1. SCAN PURGE / RESET LOGIC
         * If the scan was cleared on the front-end, remove the old file asset to 
         * safely switch back to manual entry mode.
         */
        if ($request->input('clear_scan') == '1') {
            if ($res->lab_scan) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($res->lab_scan);
                $res->update(['lab_scan' => null]);
            }
        }

        /**
         * 2. SCAN PRIORITY LOGIC
         * If a new scan is attached, empty the lab_data arrays so the PDF generator 
         * knows to prioritize rendering only the uploaded document.
         */
        if ($request->hasFile('lab_scan')) {
            $this->uploadResultFile($request, $appointment, 'lab_scan');

            // FIXED: Do not discard case_no when uploading a scanned copy [153]
            $labData = [
                'metadata' => [
                    'case_no' => $request->input('case_no'),
                    'date' => $request->input('lab_data.metadata.date') ?? now()->format('Y-m-d'),
                ],
                'results' => [],
                'sig' => []
            ]; 
        } else {
            // Ensure case_no is bound to metadata block for manual findings
            if (is_array($labData)) {
                $labData['metadata']['case_no'] = $request->input('case_no');
            }
        }

        /**
         * 3. SYSTEM AUDIT (HUB) VS CLINICAL SIGNATORY (PDF)
         * - $labData['sig'] contains the names manually typed for the PDF.
         * - Here we capture the ACTUAL SYSTEM ACCOUNT for the Hub progress tracker.
         */
        $systemUser = auth()->user()->name;

        // 4. Update the Database Record
        $res->update([
            'lab_data' => $labData,
            'lab_status' => 'encoded', // Signals the Hub that it's ready for verification
            
            // SYSTEM AUDIT COLUMNS (Used by encode.blade.php)
            'lab_v1_by_name' => $systemUser, 
            'lab_v1_at' => now(),
            'lab_v1_by' => auth()->id(),
            
            // Clear correction instructions as the form has been re-saved
            'lab_return_reason' => null 
        ]);

        // 5. System Logging
        ActivityLog::record(
            'ENCODED', 
            'Laboratory results submitted' . ($request->hasFile('lab_scan') ? ' (Scan Mode)' : ' (Manual Mode)'), 
            $appointment->patient_name, 
            $appointment->id
        );

        // Dispatch real-time refresh to the Results Hub and Master Queue
        event(new QueueUpdated());

        // 6. Redirect back to the Results Management Hub (The Command Center)
        return redirect()->route('appointments.encode', $appointment->id)
            ->with('success', 'Laboratory workstation saved. Awaiting verification.');
    }
}