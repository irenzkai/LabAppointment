<?php

namespace App\Http\Controllers\Workstation;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, CustomWorkstationResult, ActivityLog};
use App\Traits\HandlesResultFiles;
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CustomWorksheetController extends Controller
{
    use HandlesResultFiles;

    /**
     * Store a dynamic, appointment-scoped custom workstation result (Worksheet)
     */
    public function store(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);

        $request->validate([
            'name' => 'required|string|max:255',
            'cert_no' => 'required|string|max:255',
            'scan_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $customRes = CustomWorkstationResult::create([
            'appointment_result_id' => $res->id,
            'name' => $request->name,
            'cert_no' => $request->cert_no,
            'status' => 'encoded', // Automatically set to encoded once file is uploaded
        ]);

        // Upload the scanned file directly to S3/Local Storage using the standard helper
        $this->uploadCustomWorksheetFile($request, $customRes, 'scan_file');

        // Set system audit trail for dynamic custom worksheet encoding
        $res->updateAudit("custom_{$customRes->id}", [
            'v1_by' => auth()->id(),
            'v1_by_name' => auth()->user()->name,
            'v1_at' => now(),
        ]);

        ActivityLog::record(
            'ENCODED', 
            "Created custom worksheet: {$customRes->name} (Cert: {$customRes->cert_no})",
            $appointment->patient_name,
            $appointment->id
        );

        event(new QueueUpdated());

        return back()->with('success', "Custom worksheet '{$customRes->name}' successfully added!");
    }

    /**
     * Update an appointment-scoped custom workstation result (Worksheet)
     */
    public function update(Request $request, Appointment $appointment, $id)
    {
        if (Gate::denies('isStaff')) abort(403);

        $customRes = CustomWorkstationResult::findOrFail($id);

        $request->validate([
            'cert_no' => 'required|string|max:255',
            'scan_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $customRes->update([
            'cert_no' => $request->cert_no,
            'status' => 'encoded', // Sets status back to encoded for verification
            'return_reason' => null // Clears any previous correction logs
        ]);

        // Manage file override cleanups safely
        if ($request->hasFile('scan_file')) {
            if ($customRes->scan_path) {
                Storage::disk('public')->delete($customRes->scan_path);
            }
            $this->uploadCustomWorksheetFile($request, $customRes, 'scan_file');
        }

        // Update system audit trail with new encoder, clearing previous verifier details
        $appointment->result->updateAudit("custom_{$customRes->id}", [
            'v1_by' => auth()->id(),
            'v1_by_name' => auth()->user()->name,
            'v1_at' => now(),
            'v2_by' => null,
            'v2_by_name' => null,
            'v2_at' => null,
        ]);

        ActivityLog::record(
            'ENCODED', 
            "Updated custom worksheet: {$customRes->name}",
            $appointment->patient_name,
            $appointment->id
        );

        event(new QueueUpdated());

        return back()->with('success', "Custom worksheet '{$customRes->name}' updated successfully.");
    }

    /**
     * Delete an appointment-scoped custom workstation result
     */
    public function destroy(Request $request, Appointment $appointment, $id)
    {
        if (Gate::denies('isStaff')) abort(403);

        // Validate the audit justification reason
        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        $customRes = CustomWorkstationResult::findOrFail($id);

        // Delete active file asset from storage
        if ($customRes->scan_path) {
            Storage::disk('public')->delete($customRes->scan_path);
        }

        $name = $customRes->name;
        $customRes->delete();

        // Clear corresponding audit trail logs to protect referential hygiene
        $appointment->result->audits()->where('workstation_type', "custom_{$id}")->delete();

        ActivityLog::record(
            'DELETED', 
            "Removed custom worksheet: {$name}. Reason: " . $request->reason,
            $appointment->patient_name,
            $appointment->id
        );

        event(new QueueUpdated());

        return back()->with('success', "Custom worksheet '{$name}' removed from folder.");
    }

    /**
     * Sign-off / Verify a dynamic custom worksheet
     */
    public function verify(Request $request, Appointment $appointment, $id)
    {
        if (Gate::denies('isStaff')) abort(403);

        $customRes = CustomWorkstationResult::findOrFail($id);

        $request->validate([
            'sig_name' => 'required|string|max:255'
        ]);

        $customRes->update([
            'status' => 'verified'
        ]);

        // Record verification details in security audits log
        $appointment->result->updateAudit("custom_{$customRes->id}", [
            'v2_by' => auth()->id(),
            'v2_by_name' => $request->sig_name,
            'v2_at' => now(),
        ]);

        ActivityLog::record(
            'VERIFIED', 
            "Verified custom worksheet: {$customRes->name}",
            $appointment->patient_name,
            $appointment->id
        );

        event(new QueueUpdated());

        return back()->with('success', "Worksheet '{$customRes->name}' verified and approved.");
    }

    /**
     * Return a dynamic custom worksheet back for corrections
     */
    public function return(Request $request, Appointment $appointment, $id)
    {
        if (Gate::denies('isStaff')) abort(403);

        $customRes = CustomWorkstationResult::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        $customRes->update([
            'status' => 'returned',
            'return_reason' => $request->reason
        ]);

        // Nullify verifier logs since state is returned for correction
        $appointment->result->updateAudit("custom_{$customRes->id}", [
            'v2_by' => null,
            'v2_by_name' => null,
            'v2_at' => null,
        ]);

        // Dynamically unlock the overall patient folder if returned after final release has completed
        if ($appointment->status === 'released') {
            $appointment->update(['status' => 'encoded']);
        }

        ActivityLog::record(
            'RETURNED', 
            "Returned custom worksheet: {$customRes->name} for correction",
            $appointment->patient_name,
            $appointment->id
        );

        event(new QueueUpdated());

        return back()->with('success', "Worksheet '{$customRes->name}' sent back to encoder.");
    }

    /**
     * Trait-mirror helper specifically tailored for dynamic record uploads
     */
    public function uploadCustomWorksheetFile(Request $request, CustomWorkstationResult $record, $fieldName)
    {
        if ($request->hasFile($fieldName)) {
            $path = $request->file($fieldName)->store('results', 'public');
            $record->update(['scan_path' => $path]);
        }
    }
}