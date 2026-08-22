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
    User,
    AppointmentResult,
    CustomWorkstationResult,
    WorkstationAudit,
    Service,
    AppointmentDrugTest
};
use App\Http\Controllers\Workstation\CustomWorksheetController;
use App\Events\QueueUpdated;
use App\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Gate, Storage, URL, Crypt, Log, Mail};
use Barryvdh\DomPDF\Facade\Pdf;

class ResultController extends Controller
{
    /**
     * THE HUB: Displays the progress tracker for all required forms.
     */
    public function hub(Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);
        if ($appointment->status === 'released') {
            if (!session()->has("access_granted_{$appointment->id}_hub")) {
                if (request()->ajax() || request()->wantsJson() || request()->headers->has('X-Requested-With')) {
                    return response()->json(['error' => 'Clinical authorization required.'], 403);
                }
                return redirect()->route('appointments.index')
                    ->with('error', 'Clinical authorization required to access this patient folder.');
            }
        }

        $serviceNames = $appointment->services->pluck('name')->map(fn($n) => strtoupper($n))->toArray();
        $autoReportTypes = [];

        foreach ($serviceNames as $name) {
            if (str_contains($name, 'DRUG TEST')) $autoReportTypes[] = 'drug';
            elseif (str_contains($name, 'XRAY') || str_contains($name, 'X-RAY')) $autoReportTypes[] = 'radio';
            elseif (str_contains($name, 'MEDICAL CERTIFICATE')) $autoReportTypes[] = 'med_cert';
            else $autoReportTypes[] = 'lab';
        }

        $autoReportTypes = array_unique($autoReportTypes);
        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);

        // Only initialize included_reports if it's empty to allow persistent deletion/addition
        if (is_null($res->included_reports) || empty($res->included_reports)) {
            $res->update(['included_reports' => $autoReportTypes]);
        } else {
            $autoReportTypes = $res->included_reports;
        }

        // Fetch all active diagnostic services for the Demographics/Services revision
        $services = Service::where('is_available', true)->orderBy('name')->get();

        return view('appointments.encode', [
            'appointment' => $appointment,
            'autoReportTypes' => $autoReportTypes,
            'services' => $services
        ]);
    }

    /**
     * EDIT DETAILS PAGE: Dedicated full-page view to revise patient identity, PSGC address, and medical services.
     */
    public function editDemographics(Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);
        $services = Service::where('is_available', true)->orderBy('name')->get();
        return view('appointments.edit-details', compact('appointment', 'services'));
    }

    /**
     * VERIFY: System sign-off for a specific form.
     */
    public function verify(Request $request, Appointment $appointment, $type)
    {
        if (Gate::denies('isStaff')) abort(403);
        $request->validate(['sig_name' => 'required|string|max:255']);

        $res = $appointment->result;
        $prefix = ($type == 'med_cert' ? 'med' : $type);

        $updateData = [
            "{$prefix}_status" => 'verified',
            "{$prefix}_v2_by_name" => $request->sig_name,
        ];

        // Whitelisted _v2_at and _v2_by timestamp updates
        if ($type === 'lab') {
            $updateData['lab_v2_at'] = now();
            $updateData['lab_v2_by'] = auth()->id();
        } else {
            $updateData["{$prefix}_v2_at"] = now();
            $updateData["{$prefix}_v2_by"] = auth()->id();
        }

        $res->update($updateData);

        ActivityLog::record('VERIFIED', "Clinical sign-off for $type", $appointment->patient_name, $appointment->id);

        event(new QueueUpdated());

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
        $prefix = ($type == 'med_cert' ? 'med' : $type);

        $updateData = [
            "{$prefix}_status" => 'returned',
            "{$prefix}_v2_by_name" => null,
            "{$prefix}_return_reason" => $request->reason,
        ];

        // Replaced non-whitelisted columns with whitelisted _v2_at and _v2_by
        if ($type === 'lab') {
            $updateData['lab_v2_at'] = null;
            $updateData['lab_v2_by'] = null;
        } else {
            $updateData["{$prefix}_v2_at"] = null;
            $updateData["{$prefix}_v2_by"] = null;
        }

        $appointment->result->update($updateData);

        // Dynamically unlock the overall patient folder if returned after final release has completed
        if ($appointment->status === 'released') {
            $appointment->update(['status' => 'encoded']);
        }

        ActivityLog::record('RETURNED', "Form ($type) sent back: " . $request->reason, $appointment->patient_name, $appointment->id);

        event(new QueueUpdated());

        return redirect()->route('appointments.encode', $appointment->id)
            ->with('info', 'Form sent back for correction.');
    }

    /**
     * DELETE ORIGINAL WORKSTATION: Deletes/removes an original worksheet from the active report set.
     */
    public function destroyOriginalWorkstation(Request $request, Appointment $appointment, $type)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        $res = $appointment->result;
        if (!$res) {
            return back()->with('error', 'Appointment result folder not found.');
        }

        $reports = $res->included_reports ?? [];
        if (($key = array_search($type, $reports)) !== false) {
            unset($reports[$key]);
            $res->included_reports = array_values($reports);

            // Clean up corresponding data columns, files, and state details
            $prefix = ($type == 'med_cert' ? 'med' : $type);
            $res->{"{$prefix}_status"} = 'pending';
            $res->{"{$prefix}_v1_by_name"} = null;
            $res->{"{$prefix}_v1_by"} = null;
            $res->{"{$prefix}_v1_at"} = null;
            $res->{"{$prefix}_v2_by_name"} = null;
            $res->{"{$prefix}_v2_by"} = null;
            $res->{"{$prefix}_v2_at"} = null;
            $res->{"{$prefix}_return_reason"} = null;

            if ($type === 'lab') {
                $res->lab_scan = null;
                $res->lab_data = null;
                $res->labResults()->delete();
                $res->labDetails()->delete();
            } elseif ($type === 'radio') {
                $res->radio_scan = null;
                $res->xray_image = null;
                $res->radio_data = null;
                $res->radiologyReport()->delete();
            } elseif ($type === 'drug') {
                $res->drug_test_scan = null;
                $res->drug_test_data = null;
                $res->drugTest()->delete();
            } elseif ($type === 'med_cert') {
                $res->med_cert_scan = null;
                $res->med_cert_data = null;
                $res->medCert()->delete();
            }

            $res->save();

            // Clear any related audit trails
            $res->audits()->where('workstation_type', $type)->delete();

            ActivityLog::record(
                'DELETED WORKSTATION',
                "Deleted original workstation: " . strtoupper($type) . ". Reason: " . $request->reason,
                $appointment->patient_name,
                $appointment->id
            );

            event(new QueueUpdated());

            return back()->with('success', 'Workstation removed from folder successfully.');
        }

        return back()->with('error', 'Workstation not found.');
    }

    /**
     * ADD WORKSTATION: Adds an original workstation or a custom dynamic worksheet.
     */
    public function addWorkstation(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'workstation_type' => 'required|string|in:lab,radio,drug,med_cert,custom',
            'custom_name' => 'required_if:workstation_type,custom|nullable|string|max:255',
            'cert_no' => 'required_if:workstation_type,custom|nullable|string|max:255',
            'scan_file' => 'required_if:workstation_type,custom|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);
        $type = $request->workstation_type;

        if ($type === 'custom') {
            $customRes = CustomWorkstationResult::create([
                'appointment_result_id' => $res->id,
                'name' => $request->custom_name,
                'cert_no' => $request->cert_no,
                'status' => 'encoded',
            ]);

            // Utilize upload function from standard workspace helper
            $controller = new CustomWorksheetController();
            $controller->uploadCustomWorksheetFile($request, $customRes, 'scan_file');

            // Set system audit trail for newly added custom worksheet
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

            return back()->with('success', "Worksheet '{$customRes->name}' added successfully.");
        } else {
            $reports = $res->included_reports ?? [];
            if (in_array($type, $reports)) {
                return back()->with('error', 'This workstation is already active.');
            }

            $reports[] = $type;
            $res->included_reports = $reports;
            $prefix = ($type == 'med_cert' ? 'med' : $type);
            $res->{"{$prefix}_status"} = 'pending';
            $res->save();

            ActivityLog::record(
                'ADDED WORKSTATION',
                "Added workstation: " . strtoupper($type),
                $appointment->patient_name,
                $appointment->id
            );

            event(new QueueUpdated());

            return back()->with('success', 'Workstation added successfully.');
        }
    }

    /**
     * REVISE DEMOGRAPHICS: Edits schedule and demographics details from the hub or dedicated edit page, with robust logging.
     */
    public function reviseDemographics(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'patient_first_name' => 'required|string|max:255',
            'patient_middle_name' => 'nullable|string|max:255',
            'patient_last_name' => 'required|string|max:255',
            'patient_suffix' => 'nullable|string|max:10|regex:/^[a-zA-Z0-9\s.]+$/u',
            'patient_phone' => 'required|string|regex:/^09\d{9}$/',
            'patient_sex' => 'required|string|in:Male,Female',
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_street' => 'required|string|max:255',
            'patient_barangay' => 'required|string|max:255',
            'patient_city' => 'required|string|max:255',
            'patient_province' => 'required|string|max:255',
            'service_ids' => 'required|array|min:1',
            'payment_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|min:5',
        ]);

        $fName = strtoupper(trim($request->patient_first_name));
        $mName = ($request->patient_middle_name && strtoupper($request->patient_middle_name) !== 'N/A') 
            ? strtoupper(trim($request->patient_middle_name)) : 'N/A';
        $lName = strtoupper(trim($request->patient_last_name));
        $suffix = $request->filled('patient_suffix') ? strtoupper(trim($request->patient_suffix)) : '';

        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) {
            $displayName .= " {$suffix}";
        }

        $appointment->update([
            'patient_first_name' => $fName,
            'patient_middle_name' => $mName,
            'patient_last_name' => $lName,
            'patient_suffix' => $suffix ?: null,
            'patient_name' => $displayName,
            'patient_phone' => $request->patient_phone,
            'patient_sex' => $request->patient_sex,
            'patient_birthdate' => $request->patient_birthdate,
            'patient_street' => strtoupper(trim($request->patient_street)),
            'patient_barangay' => strtoupper(trim($request->patient_barangay)),
            'patient_city' => strtoupper(trim($request->patient_city)),
            'patient_province' => strtoupper(trim($request->patient_province)),
            'payment_amount' => $request->payment_amount,
        ]);

        // Sync the edited services requested for the appointment
        $appointment->services()->sync($request->service_ids);

        // Record audit log for compliance tracing
        ActivityLog::record(
            'DEMOGRAPHICS REVISED',
            "Revised patient details and selected services. Reason: " . $request->reason,
            $appointment->patient_name,
            $appointment->id
        );

        event(new QueueUpdated());

        $from = $request->input('from');
        $customId = $request->input('custom_id');

        if ($from === 'radio' || $from === 'radiology') {
            return redirect()->route('workstation.radiology', $appointment->id)
                ->with('success', 'Patient details and services revised successfully.');
        } elseif ($from === 'lab') {
            return redirect()->route('workstation.lab', $appointment->id)
                ->with('success', 'Patient details and services revised successfully.');
        } elseif ($from === 'med_cert' || $from === 'medical') {
            return redirect()->route('workstation.med_cert', $appointment->id)
                ->with('success', 'Patient details and services revised successfully.');
        } elseif ($from === 'drug') {
            return redirect()->route('workstation.drug', $appointment->id)
                ->with('success', 'Patient details and services revised successfully.');
        } elseif ($from === 'custom' && $customId) {
            return redirect()->route('workstation.custom', [$appointment->id, $customId])
                ->with('success', 'Patient details and services revised successfully.');
        } elseif ($from === 'hub') {
            return redirect()->route('appointments.encode', $appointment->id)
                ->with('success', 'Patient details and services revised successfully.');
        }

        return redirect()->route('appointments.index', ['view' => 'queue', 'id' => $appointment->id])
            ->with('success', 'Patient details and services revised successfully.');
    }

    /**
     * LOG ACCESS: The "Reason-Gate".
     */
    public function logAccess(Request $request, Appointment $appointment)
    {
        $request->validate([
            'access_reason' => 'required|string|min:5',
            'type' => 'required',
            'mode' => 'required'
        ]);

        ActivityLog::record(
            'SENSITIVE DATA ACCESS',
            "Reason: {$request->access_reason} | Action: " . strtoupper($request->mode) . " | Target: " . strtoupper($request->type),
            $appointment->patient_name,
            $appointment->id
        );

        session()->put("access_granted_{$appointment->id}_{$request->type}", true);

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
     * ACCESS: Enforces privacy shield and converts raw images to password-secured PDFs.
     */
    public function access(Appointment $appointment, $type, $mode)
    {
        $user = Auth::user();

        // 1. Expanded ownership check: User is owner if user ID matches appointment user_id,
        // OR patient_email matches user's email, OR patient is a family dependent of the user.
        $isOwner = ($user->id === $appointment->user_id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        $isStaff = $user->isEmployee();

        // 2. Privacy Shield for Batch Coordinators: ONLY restricts viewing unreleased individual raw worksheets of other people.
        // Once released, or if the user is the actual patient, access is permitted.
        if ($appointment->status !== 'released' && $appointment->batch_id && $user->id === $appointment->user_id && strtolower($appointment->patient_email) !== strtolower($user->email)) {
            abort(403, 'Privacy Shield: Batch coordinators are restricted from viewing individual patient worksheets prior to clinical release.');
        }

        if (!$isOwner && !$isStaff) {
            abort(403, 'Unauthorized access: You do not have permission to view or download this clinical result.');
        }

        // 3. Staff members who are NOT the patient owner must pass the Reason-Gate
        if ($isStaff && !$isOwner) {
            if (!session()->has("access_granted_{$appointment->id}_{$type}")) {
                return redirect()->route('appointments.index')->with('error', 'Clinical authorization required.');
            }
        }

        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);

        if (str_starts_with($type, 'custom_')) {
            $customId = str_replace('custom_', '', $type);
            $customRes = CustomWorkstationResult::findOrFail($customId);
            $filePath = $customRes->scan_path;

            if ($filePath && Storage::disk('public')->exists($filePath)) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $filename = "Result_{$customRes->name}_{$appointment->id}.{$ext}";

                if ($mode === 'preview') {
                    $contentType = $ext === 'pdf' ? 'application/pdf' : 'image/' . $ext;
                    return response()->stream(function() use ($filePath) {
                        $stream = Storage::disk('public')->readStream($filePath);
                        fpassthru($stream);
                        if (is_resource($stream)) fclose($stream);
                    }, 200, [
                        'Content-Type' => $contentType,
                        'Content-Disposition' => 'inline; filename="' . $filename . '"'
                    ]);
                } else {
                    return Storage::disk('public')->download($filePath, $filename);
                }
            }

            abort(404, 'Scanned worksheet file not found on storage server.');
        }

        if ($type === 'radio') {
            return $this->generateMergedRadioPdf($appointment, $res, $mode);
        }

        $fileMap = [
            'lab' => 'lab_scan',
            'med_cert' => 'med_cert_scan',
            'drug' => 'drug_test_scan',
            'radio' => 'radio_scan',
            'xray' => 'xray_image'
        ];

        $column = $fileMap[$type] ?? null;
        $filePath = $res->$column;

        if ($column && $res && $filePath && Storage::disk('public')->exists($filePath)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'jfif'])) {
                $fileContents = Storage::disk('public')->get($filePath);
                $imageData = base64_encode($fileContents);
                $mimeType = 'image/' . $ext;
                if ($ext === 'jpg' || $ext === 'jfif') $mimeType = 'image/jpeg';
                $base64Image = 'data:' . $mimeType . ';base64,' . $imageData;
                $dimensions = @getimagesizefromstring($fileContents);
                $imgWidth = $dimensions[0] ?? null;
                $imgHeight = $dimensions[1] ?? null;

                $pdf = Pdf::loadView('pdf.image_wrapper', [
                    'base64Image' => $base64Image,
                    'imgWidth' => $imgWidth,
                    'imgHeight' => $imgHeight
                ]);

                $filename = "Result_{$type}_{$appointment->id}.pdf";
                return $mode === 'preview' ? $pdf->stream($filename) : $pdf->download($filename);
            }

            $filename = "Result_{$type}_{$appointment->id}.{$ext}";

            if ($mode === 'preview') {
                $contentType = $ext === 'pdf' ? 'application/pdf' : 'image/' . $ext;
                return response()->stream(function() use ($filePath) {
                    $stream = Storage::disk('public')->readStream($filePath);
                    fpassthru($stream);
                    if (is_resource($stream)) fclose($stream);
                }, 200, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'inline; filename="' . $filename . '"'
                ]);
            } else {
                return Storage::disk('public')->download($filePath, $filename);
            }
        }

        $viewMap = [
            'lab' => 'pdf.labreport',
            'drug' => 'pdf.labreport',
            'med_cert' => 'pdf.medcert',
            'radio' => 'pdf.radio',
            'xray' => 'pdf.radio'
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
     * FORWARD TO EMAIL: Triggers compiler to bundle and mail password-protected PDFs to patient.
     */
    public function forwardToEmail(Appointment $appointment)
    {
        $user = auth()->user();
        $isOwner = ($user->id === $appointment->user_id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        if (!$isOwner && !$user->isEmployee()) {
            abort(403, 'Unauthorized action.');
        }

        if ($appointment->status !== 'released') {
            return back()->with('error', 'Results must be clinically released before they can be forwarded.');
        }

        // Deliver results securely using background PDF compiler
        self::deliverResult($appointment, true);

        return back()->with('success', "Pristine clinical results forwarded to your registered email: {$appointment->patient_email}.");
    }

    /**
     * STATIC HELPER: Compiles password-secured PDFs (including image-converted files) and delivers them.
     */
    public static function deliverResult(Appointment $appointment, $isForward = false)
    {
        // Dynamic snapshot fallback. If patient_email is empty/null, fall back to registered parent email
        $email = $appointment->patient_email ?: ($appointment->user?->email);
        if (!$email) return;

        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $appointment->user_id !== $existingUser->id) {
            $appointment->update(['user_id' => $existingUser->id]);
        }

        // 1. Resolve Birthdate cleanly (fallback chain: appointment -> dependent -> user)
        $rawBirthdate = $appointment->patient_birthdate 
            ?? $appointment->dependent?->birthdate 
            ?? $appointment->user?->birthdate;

        if ($rawBirthdate instanceof \DateTimeInterface) {
            $dateStr = $rawBirthdate->format('Y-m-d');
        } elseif (!empty($rawBirthdate)) {
            $dateStr = date('Y-m-d', strtotime((string)$rawBirthdate));
        } else {
            $dateStr = '2000-01-01';
        }

        // Extract YYYY, MM, DD directly from ISO string YYYY-MM-DD to avoid timezone shifting or locale format inversion
        $dateParts = explode('-', $dateStr);
        $y = $dateParts[0] ?? '2000';
        $m = $dateParts[1] ?? '01';
        $d = $dateParts[2] ?? '01';

        // 2. Resolve Full Name parts & extract first letter of every word for initials
        $nameParts = [];
        if (!empty($appointment->patient_first_name)) {
            $nameParts[] = trim($appointment->patient_first_name);
        }
        if (!empty($appointment->patient_middle_name) && strtoupper(trim($appointment->patient_middle_name)) !== 'N/A') {
            $nameParts[] = trim($appointment->patient_middle_name);
        }
        if (!empty($appointment->patient_last_name)) {
            $nameParts[] = trim($appointment->patient_last_name);
        }
        if (!empty($appointment->patient_suffix)) {
            $nameParts[] = trim($appointment->patient_suffix);
        }

        $rawFullName = !empty($nameParts) 
            ? implode(' ', $nameParts) 
            : ($appointment->patient_name ?? '');

        // Extract first letter of every word (letters only)
        preg_match_all('/\b\p{L}/u', $rawFullName, $matches);
        $initials = !empty($matches[0]) ? implode('', $matches[0]) : '';
        $initials = mb_strtoupper($initials, 'UTF-8');

        if (empty($initials)) {
            $initials = 'PATIENT';
        }

        $password = "{$m}{$d}{$y}{$initials}";

        // Log generated password for local testing & debugging
        Log::info("PDF Decryption Password generated for Appointment #{$appointment->id} ({$rawFullName}): {$password}");

        $promoUrl = route('register', [
            'promote' => Crypt::encryptString($appointment->id),
            'type' => 'shadow'
        ]);

        $res = $appointment->result()->firstOrCreate(['appointment_id' => $appointment->id]);
        $included = $res->included_reports ?? ['lab'];
        $attachments = [];

        $fileMap = [
            'lab' => 'lab_scan',
            'med_cert' => 'med_cert_scan',
            'drug' => 'drug_test_scan',
            'radio' => 'radio_scan',
            'xray' => 'xray_image'
        ];

        $viewMap = [
            'lab' => 'pdf.labreport',
            'drug' => 'pdf.labreport',
            'med_cert' => 'pdf.medcert',
            'radio' => 'pdf.radio',
            'xray' => 'pdf.radio'
        ];

        foreach ($included as $type) {
            if ($type === 'radio') {
                $controller = new self();
                $reportPages = $controller->fileToBase64Pages($res->radio_scan);
                $xrayPages = $controller->fileToBase64Pages($res->xray_image);
                $hasManualFindings = !empty($res->radio_data['findings']) || !empty($res->radio_data['impression']);
                $renderManualReport = empty($reportPages) && ($hasManualFindings || !$res->radio_scan);

                $pdf = Pdf::loadView('pdf.radio', [
                    'app' => $appointment,
                    'res' => $res,
                    'renderManualReport' => $renderManualReport,
                    'reportPages' => $reportPages,
                    'xrayPages' => $xrayPages
                ]);

                $pdf->setEncryption($password);

                $attachments[] = [
                    'data' => $pdf->output(),
                    'name' => "Medscreen_Result_Radiology_{$appointment->id}.pdf",
                    'mime' => 'application/pdf'
                ];
                continue;
            }

            $column = $fileMap[$type] ?? null;
            $filePath = $res->$column;
            $isImage = false;
            if ($column && $filePath) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'jfif']);
            }

            if ($isImage && Storage::disk('public')->exists($filePath)) {
                $fileContents = Storage::disk('public')->get($filePath);
                $imageData = base64_encode($fileContents);
                $mimeType = 'image/' . $ext;
                if ($ext === 'jpg' || $ext === 'jfif') $mimeType = 'image/jpeg';
                $base64Image = 'data:' . $mimeType . ';base64,' . $imageData;
                $dimensions = @getimagesizefromstring($fileContents);
                $imgWidth = $dimensions[0] ?? null;
                $imgHeight = $dimensions[1] ?? null;

                $pdf = Pdf::loadView('pdf.image_wrapper', [
                    'base64Image' => $base64Image,
                    'imgWidth' => $imgWidth,
                    'imgHeight' => $imgHeight
                ]);
            } else {
                $viewName = $viewMap[$type] ?? 'pdf.labreport';
                $pdf = Pdf::loadView($viewName, [
                    'app' => $appointment,
                    'res' => $res,
                    'type' => $type
                ]);
            }

            $pdf->setEncryption($password);

            $attachments[] = [
                'data' => $pdf->output(),
                'name' => "Medscreen_Result_{$type}_{$appointment->id}.pdf",
                'mime' => 'application/pdf'
            ];
        }

        // Dynamically capture, convert, and attach verified custom worksheets securely
        $customs = $res->customWorkstationResults()->where('status', 'verified')->get();
        foreach ($customs as $custom) {
            $filePath = $custom->scan_path;
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'jfif'])) {
                    $fileContents = Storage::disk('public')->get($filePath);
                    $imageData = base64_encode($fileContents);
                    $mimeType = 'image/' . $ext;
                    if ($ext === 'jpg' || $ext === 'jfif') $mimeType = 'image/jpeg';
                    $base64Image = 'data:' . $mimeType . ';base64,' . $imageData;
                    $dimensions = @getimagesizefromstring($fileContents);
                    $imgWidth = $dimensions[0] ?? null;
                    $imgHeight = $dimensions[1] ?? null;

                    $pdf = Pdf::loadView('pdf.image_wrapper', [
                        'base64Image' => $base64Image,
                        'imgWidth' => $imgWidth,
                        'imgHeight' => $imgHeight
                    ]);

                    $pdf->setEncryption($password);

                    $attachments[] = [
                        'data' => $pdf->output(),
                        'name' => "Medscreen_Result_{$custom->name}_{$appointment->id}.pdf",
                        'mime' => 'application/pdf'
                    ];
                } else {
                    $attachments[] = [
                        'data' => Storage::disk('public')->get($filePath),
                        'name' => "Medscreen_Result_{$custom->name}_{$appointment->id}.{$ext}",
                        'mime' => $ext === 'pdf' ? 'application/pdf' : 'image/' . $ext
                    ];
                }
            }
        }

        $hasAccount = User::where('email', $email)->exists();
        $patientFirstName = ucwords(strtolower($appointment->patient_first_name));

        Mail::send([], [], function ($message) use ($email, $appointment, $attachments, $promoUrl, $hasAccount, $isForward, $patientFirstName) {
            $message->to($email)
                ->subject('Your Medical Results are Ready - Medscreen')
                ->html("
                    <div style='background-color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; width: 100%; color: #1c232d;'>
                        <div style='background-color: #1C232D; padding: 30px; text-align: center; border-bottom: 4px solid #19D38C;'>
                            <span style='color: #ffffff; font-weight: 800; font-size: 26px; letter-spacing: 1px;'>MED<span style='color: #19D38C;'>SCREEN</span></span>
                        </div>
                        <div style='padding: 40px 20px; max-width: 800px; margin: 0 auto;'>
                            <h3 style='margin-top: 0; color: #1c232d; font-size: 20px;'>Dear {$patientFirstName},</h3>
                            <p style='line-height: 1.6; color: #4a5568; font-size: 15px;'>Your secure, password-protected clinical results have been successfully released. Please find the encrypted PDF documents attached to this email.</p>
                            <div style='background-color: #f8fafc; border-left: 4px solid #19D38C; padding: 20px; margin: 30px 0; border-radius: 6px;'>
                                <strong style='color: #1c232d; display: block; margin-bottom: 8px; font-size: 16px;'>PDF Decryption Password:</strong>
                                <span style='font-size: 14px; color: #4a5568; line-height: 1.5;'>
                                    Your files are secured using your personal password pattern:<br>
                                    <strong style='color: #15b376; font-size: 15px;'>MMDDYYYY + Capitalized Initials</strong><br>
                                    <span style='color: #718096; font-size: 12px; display: block; margin-top: 6px;'>Example: For birthdate October 24, 2005 & initials JDC, the password is <strong>10242005JDC</strong></span>
                                </span>
                            </div>
                            " . (($hasAccount || $isForward) ? "" : "
                            <div style='border: 1.5px dashed #19D38C; background-color: rgba(25, 211, 140, 0.03); padding: 25px; text-align: center; border-radius: 8px; margin: 30px 0;'>
                                <h4 style='margin-top: 0; color: #1c232d; font-size: 18px;'>Activate Your Permanent Portal</h4>
                                <p style='font-size: 14px; color: #4a5568; margin-bottom: 20px; line-height: 1.5;'>A temporary profile has been registered for you. Click below to secure your credentials and access your lifetime clinical history logs.</p>
                                <a href='{$promoUrl}' style='display: inline-block; background-color: #19D38C; color: #1C232D; font-weight: bold; text-decoration: none; padding: 12px 30px; border-radius: 6px;'>ACTIVATE PROFILE</a>
                            </div>
                            ") . "
                            <p style='margin-top: 30px; line-height: 1.6; color: #4a5568; font-size: 15px;'>Best regards,<br><strong>Medscreen Diagnostic Laboratory</strong></p>
                        </div>
                    </div>
                ");

            foreach ($attachments as $file) {
                $message->attachData($file['data'], $file['name'], ['mime' => $file['mime']]);
            }
        });

        if ($appointment->results_released_at) {
            $patientUser = $appointment->user;
            if ($patientUser) {
                $patientUser->notify(new \App\Notifications\AppointmentNotification([
                    'title' => 'Clinical Record Corrected',
                    'message' => "Your clinical results for Appointment #{$appointment->id} have been updated/corrected by the laboratory staff. Please review your updated files.",
                    'url' => route('patient.history'),
                    'type' => 'success'
                ]));
                event(new \App\Events\NotificationSent($patientUser->id, 'Clinical Record Corrected', "Your clinical results for Appointment #{$appointment->id} have been updated."));
            }
        }

        $appointment->update(['results_released_at' => now()]);
    }

    /**
     * Generate centered radiology PDF documents.
     */
    protected function generateMergedRadioPdf(Appointment $appointment, AppointmentResult $res, $mode)
    {
        $reportPages = $this->fileToBase64Pages($res->radio_scan);
        $xrayPages = $this->fileToBase64Pages($res->xray_image);
        $hasManualFindings = !empty($res->radio_data['findings']) || !empty($res->radio_data['impression']);
        $renderManualReport = empty($reportPages) && ($hasManualFindings || !$res->radio_scan);

        $pdf = Pdf::loadView('pdf.radio', [
            'app' => $appointment,
            'res' => $res,
            'renderManualReport' => $renderManualReport,
            'reportPages' => $reportPages,
            'xrayPages' => $xrayPages
        ]);

        $filename = "Result_radio_{$appointment->id}.pdf";
        return $mode === 'preview' ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * File Base64 Parser.
     */
    public function fileToBase64Pages($filePath)
    {
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            return [];
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'])) {
            $data = base64_encode(Storage::disk('public')->get($filePath));
            $mime = 'image/' . $ext;
            if ($ext === 'jpg' || $ext === 'jfif') $mime = 'image/jpeg';
            return ["data:{$mime};base64,{$data}"];
        } elseif ($ext === 'pdf') {
            $pages = [];
            if (class_exists('Imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->setResolution(150, 150);
                    $imagick->readImage($fullPath);

                    foreach ($imagick as $image) {
                        $image->setImageFormat('jpeg');
                        $data = base64_encode($image->getImageBlob());
                        $pages[] = "data:image/jpeg;base64,{$data}";
                    }
                    $imagick->clear();
                    $imagick->destroy();
                } catch (\Exception $e) {
                    Log::error("Imagick PDF parsing conversion failed for file {$filePath}: " . $e->getMessage());
                }
            } else {
                Log::warning("Imagick extension is not installed. PDF merging was bypassed for {$filePath}.");
            }
            return $pages;
        }
        return [];
    }

    /**
     * GET /verify-result
     * Public search gateway to evaluate and verify code identifiers.
     */
    public function verifySearch(Request $request)
    {
        $query = $request->query('query');
        if (empty($query)) {
            return view('verify-search');
        }

        $query = trim($query);

        // 1. Check if it matches a Laboratory Case Number
        $labDetail = AppointmentLabDetail::where('case_no', $query)->first();
        if ($labDetail && $labDetail->result && $labDetail->result->appointment) {
            return redirect(URL::signedRoute('result.verify-public', ['appointment' => $labDetail->result->appointment->id]));
        }

        // 2. Check if it matches a Medical Certificate ID
        $medCert = AppointmentMedCert::where('cert_no', $query)->first();
        if ($medCert && $medCert->result && $medCert->result->appointment) {
            return redirect(URL::signedRoute('result.verify-public', ['appointment' => $medCert->result->appointment->id]));
        }

        // 3. Check if it matches a Radiology Case Number
        $radioReport = AppointmentRadiologyReport::where('case_no', $query)->first();
        if ($radioReport && $radioReport->result && $radioReport->result->appointment) {
            return redirect(URL::signedRoute('result.verify-public', ['appointment' => $radioReport->result->appointment->id]));
        }

        // 4. Check if it matches a Drug Test Certificate Number
        $drugTest = AppointmentDrugTest::where('cert_no', $query)->first();
        if ($drugTest && $drugTest->parentResult && $drugTest->parentResult->appointment) {
            return redirect(URL::signedRoute('result.verify-public', ['appointment' => $drugTest->parentResult->appointment->id]));
        }

        // 5. Check if it matches an Archived Digitized Certificate Number
        $historyScan = LaboratoryHistoryScan::where('certificate_no', $query)->first();
        if ($historyScan && $historyScan->record && $historyScan->record->laboratoryHistory && $historyScan->record->laboratoryHistory->user) {
            return redirect(URL::signedRoute('history.verify-public', ['user' => $historyScan->record->laboratoryHistory->user->id]));
        }

        // Fallback: If no document matches
        return back()->withErrors(['query' => 'No active clinical records or digitized certificates match the provided ID. Please verify the code and try again.'])->withInput();
    }

    /**
     * GET /verify-result/{appointment}
     * Render the signed, public clinical result verification layout.
     */
    public function verifyPublic(Appointment $appointment)
    {
        $appointment->load(['result', 'services']);
        return view('verify-result', compact('appointment'));
    }

    /**
     * GET /verify-history/{user}
     * Render the signed, public clinical archive history layout.
     */
    public function verifyHistoryPublic(User $user)
    {
        $labHistory = LaboratoryHistory::where('user_id', $user->id)->first();
        $existingRecords = $labHistory ? (is_array($labHistory->dynamic_data) ? array_reverse($labHistory->dynamic_data) : []) : [];
        return view('verify-history', compact('user', 'existingRecords', 'labHistory'));
    }
}