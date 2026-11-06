<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, Service, User, ActivityLog};
use App\Traits\HandlesResultFiles;
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LaboratoryController extends Controller
{
    use HandlesResultFiles;

    /**
     * Display the Laboratory Workstation.
     * Pre-loads registered Medical Technologists and Pathologists for 1-click signatory selection.
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
        $allServices = Service::where('is_available', true)->orderBy('name')->get();

        // 1. Predefined Medical Technologists (released_by and validated_by signatories)
        $medTechs = User::medicalTechnologists()->where('is_active', true)->orderBy('last_name')->get();
        if ($medTechs->isEmpty()) {
            $medTechs = User::whereIn('role', ['lab_tech', 'admin'])->where('is_active', true)->orderBy('last_name')->get();
        }

        // 2. Predefined Pathologists (final clinical validation signatory)
        $pathologists = User::pathologists()->where('is_active', true)->orderBy('last_name')->get();
        if ($pathologists->isEmpty()) {
            $pathologists = User::whereIn('role', ['admin', 'lab_tech'])->where('is_active', true)->orderBy('last_name')->get();
        }

        // SECURED: Pre-authorize the embedded preview iframe to bypass the Reason-Gate safely
        session()->put("access_granted_{$appointment->id}_lab", true);

        return view('appointments.workstations.lab', compact(
            'appointment', 
            'allServices', 
            'medTechs', 
            'pathologists'
        ));
    }

    /**
     * Save Laboratory Data (Handles Metadata, Manual Entry results, Signatories, and Scans).
     */
    public function save(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result;

        // Extract case_no and enforce global uniqueness validation across all files
        $request->validate([
            'case_no' => 'required|string|max:255|unique:appointment_lab_details,case_no,' . ($res->labDetails?->id ?? 'NULL'),
        ]);

        $labData = $request->input('lab_data', []);

        /**
         * 1. SCAN PURGE / RESET LOGIC
         * If the scan was cleared on the front-end, remove the old file asset to safely switch back to manual entry mode.
         */
        if ($request->input('clear_scan') == '1') {
            if ($res->lab_scan) {
                Storage::disk('public')->delete($res->lab_scan);
                $res->update(['lab_scan' => null]);
            }
        }

        /**
         * 2. SCAN PRIORITY LOGIC
         * If a new scan is attached, empty the lab_data arrays so the PDF generator knows to prioritize rendering only the uploaded document.
         */
        if ($request->hasFile('lab_scan')) {
            $this->uploadResultFile($request, $appointment, 'lab_scan');

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

            // Automated Signatory Resolution via User ID (Fallback if names/licenses are not submitted directly)
            if ($request->filled('released_by_user_id')) {
                $mt1 = User::find($request->input('released_by_user_id'));
                if ($mt1) {
                    $labData['sig']['rel_name'] = $mt1->name;
                    $labData['sig']['rel_lic'] = $mt1->license_number ?? '';
                }
            }

            if ($request->filled('validated_by_user_id')) {
                $mt2 = User::find($request->input('validated_by_user_id'));
                if ($mt2) {
                    $labData['sig']['val1_name'] = $mt2->name;
                    $labData['sig']['val1_lic'] = $mt2->license_number ?? '';
                }
            }

            if ($request->filled('pathologist_user_id')) {
                $patho = User::find($request->input('pathologist_user_id'));
                if ($patho) {
                    $labData['sig']['val2_name'] = $patho->name;
                    $labData['sig']['val2_lic'] = $patho->license_number ?? '';
                }
            }
        }

        /**
         * 3. SYSTEM AUDIT (HUB) VS CLINICAL SIGNATORY (PDF)
         * - $labData['sig'] contains the names manually selected or typed for the PDF.
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