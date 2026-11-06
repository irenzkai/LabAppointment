<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentConfig;
use App\Models\Service;
use App\Models\Package;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\PaymentProvider;
use App\Notifications\AppointmentNotification;
use App\Imports\BulkAppointmentImport;
use App\Exports\BulkTemplateExport;
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class BulkAppointmentController extends Controller
{
    /**
     * View Bulk Appointments Wizard Page.
     * Passes available services, packages, configs, payment providers, and approved organizations.
     */
    public function index()
    {
        $user = Auth::user();
        $services = Service::where('is_available', true)->orderBy('name')->get();
        
        // Fetch user's approved recognized organizations
        $approvedOrgs = $user 
            ? $user->organizations()->where('status', 'approved')->with('packages.services')->orderBy('name')->get() 
            : collect();

        // Fetch all packages available for user (global packages + their approved org packages)
        $packages = Package::availableFor($user)->orderBy('name')->get();
        $configs = AppointmentConfig::all()->keyBy('day_of_week');
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        return view('appointments.bulk', compact(
            'services', 
            'packages', 
            'configs', 
            'paymentProviders', 
            'approvedOrgs'
        ));
    }

    /**
     * Store manual spreadsheet compiled entries with uncapped cascading schedule overflow.
     * Enforces mandatory contact credentials for ALL patients, intra-batch duplicate checks,
     * and proper organization binding.
     */
    public function storeManual(Request $request)
    {
        $user = Auth::user();
        $useRecognizedOrg = $request->boolean('use_recognized_org');

        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') return;
            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        $rules = [
            'use_recognized_org' => 'nullable|boolean',
            'appointment_date' => 'required|date|after_or_equal:today',
            'payment_method' => 'required|string|in:Cash,Cashless',
            'payment_receipt' => 'required_if:payment_method,Cashless|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'patients' => 'required|array|min:2',
            'patients.*.first_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patients.*.middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patients.*.last_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patients.*.suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z\s.]+$/u'],
            'patients.*.name' => 'required|string|max:255',
            'patients.*.sex' => 'required|string|in:Male,Female',
            'patients.*.birthdate' => 'required|date|before_or_equal:today',
            // Contact info is strictly required for BOTH recognized and normal bulk
            'patients.*.phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'patients.*.email' => ['required', 'email', 'max:255'],
            'patients.*.service_ids' => 'nullable|array',
            'patients.*.service_ids.*' => 'exists:services,id',
            'patients.*.package_ids' => 'nullable|array',
            'patients.*.package_ids.*' => 'exists:packages,id',
            'patients.*.street' => 'required|string|max:255',
            'patients.*.barangay' => 'required|string|max:255',
            'patients.*.city' => 'required|string|max:255',
            'patients.*.province' => 'required|string|max:255',
        ];

        if ($useRecognizedOrg) {
            $rules['organization_id'] = 'required|exists:organizations,id';
        } else {
            $rules['organization_name'] = 'required|string|max:255';
        }

        $request->validate($rules, [
            'patients.min' => 'Bulk booking requires at least 2 patient records. For single appointments, please use the standard booking wizard.',
            'patients.*.suffix.regex' => 'The suffix may only contain letters, spaces, and periods (e.g. JR, SR, II, III).',
            'patients.*.phone.required' => 'Patient phone numbers are strictly required.',
            'patients.*.phone.regex' => 'Phone numbers must contain exactly 11 digits starting with 09.',
            'patients.*.email.required' => 'Patient email addresses are strictly required to receive test notification updates.',
        ]);

        // Resolve Organization details
        $resolvedOrgId = null;
        $resolvedOrgName = '';

        if ($useRecognizedOrg) {
            $org = $user->organizations()->where('status', 'approved')->findOrFail($request->organization_id);
            $resolvedOrgId = $org->id;
            $resolvedOrgName = $org->name;
        } else {
            $resolvedOrgName = strtoupper(trim($request->organization_name));
        }

        // =========================================================================
        // 1. INTRA-BATCH DUPLICATE & ANTI-DOUBLE SELECTION CHECKS
        // =========================================================================
        $seenBatchEmails = [];
        $seenBatchPhones = [];

        foreach ($request->patients as $idx => $p) {
            $rowNum = $idx + 1;
            $hasServices = !empty($p['service_ids']);
            $hasPackages = !empty($p['package_ids']);

            if (!$hasServices && !$hasPackages) {
                return back()->withInput()->withErrors([
                    "patients.{$idx}.service_ids" => "Patient #{$rowNum} must have at least one test or package selected."
                ]);
            }

            $selectedPackages = $hasPackages
                ? Package::with('services')->whereIn('id', $p['package_ids'])->get()
                : collect();

            // Prevent picking an individual test that is already covered in a selected package
            if ($hasServices && $selectedPackages->isNotEmpty()) {
                $overlapping = [];
                foreach ($selectedPackages as $pkg) {
                    foreach ($pkg->services as $svc) {
                        if (in_array($svc->id, $p['service_ids'])) {
                            $overlapping[] = "{$svc->name} (in {$pkg->name})";
                        }
                    }
                }
                if (!empty($overlapping)) {
                    return back()->withInput()->withErrors([
                        "patients.{$idx}.service_ids" => "Patient #{$rowNum} has duplicate test(s) already covered by package: " . implode(', ', $overlapping) . ". Please deselect individual duplicates."
                    ]);
                }
            }

            // Prevent selecting multiple packages that share the same constituent services
            if ($selectedPackages->count() > 1) {
                $seenServiceIds = [];
                $conflicting = [];
                foreach ($selectedPackages as $pkg) {
                    foreach ($pkg->services as $svc) {
                        if (isset($seenServiceIds[$svc->id])) {
                            $prevPkgName = $seenServiceIds[$svc->id];
                            $conflicting[] = "Package '{$pkg->name}' conflicts with '{$prevPkgName}' (both share '{$svc->name}')";
                        } else {
                            $seenServiceIds[$svc->id] = $pkg->name;
                        }
                    }
                }
                if (!empty($conflicting)) {
                    return back()->withInput()->withErrors([
                        "patients.{$idx}.package_ids" => "Patient #{$rowNum} has conflicting packages: " . implode('; ', $conflicting) . ". Packages sharing identical individual services cannot be booked together."
                    ]);
                }
            }

            // Intra-batch Duplicate Credential Checks
            $cleanEmail = strtolower(trim($p['email']));
            if (isset($seenBatchEmails[$cleanEmail])) {
                return back()->withInput()->withErrors([
                    "patients.{$idx}.email" => "Duplicate email '{$cleanEmail}' detected on Patient #{$rowNum} (already assigned to Patient #{$seenBatchEmails[$cleanEmail]})."
                ]);
            }
            $seenBatchEmails[$cleanEmail] = $rowNum;

            $cleanPhone = preg_replace('/[^0-9]/', '', $p['phone']);
            if (isset($seenBatchPhones[$cleanPhone])) {
                return back()->withInput()->withErrors([
                    "patients.{$idx}.phone" => "Duplicate phone number '{$cleanPhone}' detected on Patient #{$rowNum} (already assigned to Patient #{$seenBatchPhones[$cleanPhone]})."
                ]);
            }
            $seenBatchPhones[$cleanPhone] = $rowNum;

            // Cross-Account Conflict Guard: Ensure email and phone do not belong to two separate verified accounts
            $userByEmail = User::where('email', $cleanEmail)->whereNotNull('email_verified_at')->first();
            $userByPhone = User::where('phone', $cleanPhone)->whereNotNull('phone_verified_at')->first();
            if ($userByEmail && $userByPhone && $userByEmail->id !== $userByPhone->id) {
                return back()->withInput()->withErrors([
                    "patients.{$idx}.email" => "Patient #{$rowNum} has an identity conflict: Email belongs to account '{$userByEmail->name}', but Phone belongs to account '{$userByPhone->name}'."
                ]);
            }
        }

        // =========================================================================
        // 2. UNCAPPED CASCADING SCHEDULE OVERFLOW ALLOCATION
        // =========================================================================
        $requestedDate = $request->appointment_date;
        $initialTimeSlot = $request->patients[0]['time_slot'] ?? '08:00:00';

        $occupancyCache = [];
        $allocatedSchedules = [];

        foreach ($request->patients as $idx => $p) {
            $patientRequestedSlot = !empty($p['time_slot']) ? $p['time_slot'] : $initialTimeSlot;
            $patientRequestedDate = !empty($p['appointment_date']) ? $p['appointment_date'] : $requestedDate;

            $assigned = $this->allocateCascadingSlot($patientRequestedDate, $patientRequestedSlot, $occupancyCache);
            $allocatedSchedules[$idx] = $assigned;
        }

        $batchId = Str::random(10);

        $receiptPath = null;
        if ($request->hasFile('payment_receipt') && $request->file('payment_receipt')->isValid()) {
            $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');
        }

        // =========================================================================
        // 3. ATOMIC DATABASE TRANSACTION
        // =========================================================================
        DB::beginTransaction();
        try {
            foreach ($request->patients as $idx => $p) {
                $fName = !empty($p['first_name']) ? strtoupper(trim($p['first_name'])) : '';
                $mName = (!empty($p['middle_name']) && strtoupper(trim($p['middle_name'])) !== 'N/A') ? strtoupper(trim($p['middle_name'])) : 'N/A';
                $lName = !empty($p['last_name']) ? strtoupper(trim($p['last_name'])) : '';
                $suffix = !empty($p['suffix']) ? strtoupper(trim($p['suffix'])) : null;

                if (empty($fName) || empty($lName)) {
                    $nameParts = explode(' ', trim($p['name']));
                    $fName = strtoupper($nameParts[0]);
                    $lName = strtoupper(end($nameParts));
                    $mName = count($nameParts) > 2 ? strtoupper(implode(' ', array_slice($nameParts, 1, -1))) : 'N/A';
                }

                $displayName = !empty($p['name']) ? strtoupper(trim($p['name'])) : trim("{$fName} " . ($mName !== 'N/A' ? "{$mName} " : "") . "{$lName}" . ($suffix ? " {$suffix}" : ""));
                $patientEmail = strtolower(trim($p['email']));
                $patientPhone = preg_replace('/[^0-9]/', '', $p['phone']);

                $schedule = $allocatedSchedules[$idx];

                $appointment = Appointment::create([
                    'user_id' => auth()->id(),
                    'organization_id' => $resolvedOrgId,
                    'organization_name' => $resolvedOrgName,
                    'batch_id' => $batchId,
                    'appointment_date' => $schedule['date'],
                    'time_slot' => $schedule['time_slot'],
                    'patient_first_name' => $fName,
                    'patient_middle_name' => $mName,
                    'patient_last_name' => $lName,
                    'patient_suffix' => $suffix,
                    'patient_name' => $displayName,
                    'patient_email' => $patientEmail,
                    'patient_phone' => $patientPhone,
                    'patient_sex' => $p['sex'] ?? 'Male',
                    'patient_birthdate' => $p['birthdate'] ?? null,
                    'patient_street' => strtoupper($p['street']),
                    'patient_barangay' => strtoupper($p['barangay']),
                    'patient_city' => strtoupper($p['city']),
                    'patient_province' => strtoupper($p['province']),
                    'payment_method' => $request->payment_method,
                    'payment_receipt' => $receiptPath,
                    'payment_status' => 'unpaid',
                    'status' => 'pending'
                ]);

                if (!empty($p['service_ids'])) {
                    $appointment->services()->attach($p['service_ids']);
                }

                if (!empty($p['package_ids'])) {
                    $appointment->packages()->attach($p['package_ids']);
                }
            }

            // 4. Notify Staff
            $staffMembers = User::whereIn('role', ['staff', 'admin'])->get();
            foreach ($staffMembers as $staff) {
                $staff->notify(new AppointmentNotification([
                    'title' => 'New Bulk Request',
                    'message' => "{$resolvedOrgName} submitted " . count($request->patients) . " patient(s) in Batch #{$batchId}.",
                    'url' => route('appointments.index', ['view' => 'queue']),
                    'type' => 'info'
                ]));
            }

            DB::commit();

            event(new QueueUpdated());

            $firstAlloc = $allocatedSchedules[0]['date'];
            $lastAlloc = end($allocatedSchedules)['date'];
            $rangeMsg = ($firstAlloc === $lastAlloc) ? "on {$firstAlloc}" : "scheduled from {$firstAlloc} to {$lastAlloc}";

            return redirect()->route('appointments.index')->with(
                'success', 
                "Bulk batch recorded successfully! " . count($request->patients) . " patient(s) successfully scheduled {$rangeMsg} without capacity bottlenecks."
            );
        } catch (\Exception $e) {
            DB::rollback();
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            return back()->withInput()->withErrors(['error' => 'Database error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Algorithmic Slot Allocator: Cascades over-capacity patients into the next available consecutive slot or day.
     */
    protected function allocateCascadingSlot(string $startDate, string $startTime, array &$occupancyCache): array
    {
        $currentDate = Carbon::parse($startDate);
        $currentTime = $startTime;

        while (true) {
            $dateStr = $currentDate->toDateString();
            $config = AppointmentConfig::getEffectiveConfig($dateStr);

            // If day is closed (e.g. Sunday or holiday override), advance to next calendar day
            if (!$config || !$config->is_open) {
                $currentDate->addDay();
                $nextConfig = AppointmentConfig::getEffectiveConfig($currentDate->toDateString());
                $currentTime = $nextConfig ? $nextConfig->opening_time : '08:00:00';
                continue;
            }

            $slotDuration = $config->slot_duration ?: 60;
            $maxQuota = $config->max_patients_per_slot ?: 2;
            $openingTime = $config->opening_time ?: '08:00:00';
            $closingTime = $config->closing_time ?: '17:00:00';

            if ($currentTime < $openingTime) {
                $currentTime = $openingTime;
            }

            // Same-day booking lead-time cutoff enforcement
            if ($dateStr === Carbon::today()->toDateString()) {
                $leadBuffer = $config->lead_time_hours ?: 2;
                $earliestAllowedTime = Carbon::now()->addHours($leadBuffer)->format('H:i:00');
                if ($currentTime < $earliestAllowedTime) {
                    $currentTime = $earliestAllowedTime;
                }
            }

            if ($config->isLunchTime($currentTime)) {
                $currentTime = $config->lunch_end ?: '13:00:00';
            }

            if ($currentTime >= $closingTime) {
                $currentDate->addDay();
                $nextConfig = AppointmentConfig::getEffectiveConfig($currentDate->toDateString());
                $currentTime = $nextConfig ? $nextConfig->opening_time : '08:00:00';
                continue;
            }

            if (!isset($occupancyCache[$dateStr][$currentTime])) {
                $existingCount = Appointment::where('appointment_date', $dateStr)
                    ->where('time_slot', $currentTime)
                    ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released', 'needs_review'])
                    ->count();
                $occupancyCache[$dateStr][$currentTime] = $existingCount;
            }

            if ($occupancyCache[$dateStr][$currentTime] < $maxQuota) {
                $occupancyCache[$dateStr][$currentTime]++;
                return [
                    'date' => $dateStr,
                    'time_slot' => $currentTime,
                ];
            }

            $nextTimeUnix = strtotime("+{$slotDuration} minutes", strtotime("{$dateStr} {$currentTime}"));
            $currentTime = date('H:i:00', $nextTimeUnix);
        }
    }

    /**
     * API: Fetch saved roster members for an organization group.
     */
    public function getRosterMembers(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $groupName = $request->query('group');
        $orgId = $request->query('organization_id');

        $query = OrganizationMember::where('user_id', $user->id);

        if ($orgId) {
            $query->where('organization_id', $orgId);
        }
        if ($groupName) {
            $query->where('group_name', $groupName);
        }

        $members = $query->with('defaultPackage.services')->get();

        return response()->json([
            'success' => true,
            'group' => $groupName,
            'members' => $members
        ]);
    }

    /**
     * Store new pre-recorded roster member.
     */
    public function storeRosterMember(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) abort(401);

        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'group_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:10',
            'birthdate' => 'required|date',
            'sex' => 'required|in:Male,Female',
            'phone' => 'required|string|regex:/^09\d{9}$/',
            'email' => 'required|email|max:191',
            'street' => 'nullable|string|max:150',
            'barangay' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'default_package_id' => 'nullable|exists:packages,id',
            'default_service_ids' => 'nullable|array',
        ]);

        $member = OrganizationMember::create(array_merge($validated, [
            'user_id' => $user->id,
            'first_name' => mb_strtoupper(trim($request->first_name), 'UTF-8'),
            'middle_name' => $request->filled('middle_name') ? mb_strtoupper(trim($request->middle_name), 'UTF-8') : 'N/A',
            'last_name' => mb_strtoupper(trim($request->last_name), 'UTF-8'),
            'suffix' => $request->filled('suffix') ? mb_strtoupper(trim($request->suffix), 'UTF-8') : null,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Roster member added successfully.',
            'member' => $member
        ], 201);
    }

    /**
     * Delete a pre-recorded roster member.
     */
    public function deleteRosterMember($id): JsonResponse
    {
        $user = Auth::user();
        $member = OrganizationMember::where('user_id', $user->id)->findOrFail($id);
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Roster member removed.'
        ]);
    }

    // =========================================================================
    // BATCH CARD STATUS HIERARCHY HELPER (Lowest Milestone First)
    // =========================================================================

    /**
     * Determines the overall display status of a bulk batch based on the lowest milestone.
     * Hierarchy: invalid_payment/refunded > returned > canceled > needs_review > pending > approved > retest > tested > encoded > released
     */
    public static function getBatchOverallStatus(string $batchId): array
    {
        $appointments = Appointment::where('batch_id', $batchId)->get();
        if ($appointments->isEmpty()) {
            return ['status' => 'empty', 'label' => 'Empty Batch', 'badge' => 'secondary'];
        }

        $statuses = $appointments->pluck('status')->unique()->toArray();
        $paymentStatuses = $appointments->pluck('payment_status')->unique()->toArray();

        // 1. Payment Issues / Revocations
        if (in_array('invalid', $paymentStatuses) || in_array('refunded', $paymentStatuses)) {
            return ['status' => 'payment_issue', 'label' => 'PAYMENT INVALID / ACTION NEEDED', 'badge' => 'danger'];
        }

        // 2. Returns or Rejections
        if (in_array('returned', $statuses)) {
            return ['status' => 'returned', 'label' => 'CORRECTIONS NEEDED (RETURNED)', 'badge' => 'danger'];
        }

        // 3. All Appointments Canceled
        if (in_array('canceled', $statuses) && count(array_diff($statuses, ['canceled'])) === 0) {
            return ['status' => 'canceled', 'label' => 'BATCH CANCELED', 'badge' => 'danger'];
        }

        // 4. Needs Patient Review (Staff suggested modifications)
        if (in_array('needs_review', $statuses)) {
            return ['status' => 'needs_review', 'label' => 'ADJUSTMENT PENDING PATIENT REVIEW', 'badge' => 'warning'];
        }

        // 5. Pending Clinic Approval
        if (in_array('pending', $statuses)) {
            return ['status' => 'pending', 'label' => 'AWAITING APPROVAL', 'badge' => 'warning'];
        }

        // 6. Approved for Sampling
        if (in_array('approved', $statuses)) {
            return ['status' => 'approved', 'label' => 'APPROVED (SAMPLING PENDING)', 'badge' => 'info'];
        }

        // 7. Retesting Required
        if (in_array('retest', $statuses)) {
            return ['status' => 'retest', 'label' => 'RETESTING REQUIRED', 'badge' => 'danger'];
        }

        // 8. Tested (Sample collected, in laboratory processing)
        if (in_array('tested', $statuses)) {
            return ['status' => 'tested', 'label' => 'IN PROCESSING (TESTED)', 'badge' => 'primary'];
        }

        // 9. Encoded (Awaiting clinical sign-off)
        if (in_array('encoded', $statuses)) {
            return ['status' => 'encoded', 'label' => 'ENCODED (AWAITING SIGN-OFF)', 'badge' => 'primary'];
        }

        // 10. All patients released
        if (count($statuses) === 1 && $statuses[0] === 'released') {
            return ['status' => 'released', 'label' => 'RESULTS RELEASED', 'badge' => 'success'];
        }

        return ['status' => 'mixed', 'label' => 'IN PROGRESS', 'badge' => 'info'];
    }

    /**
     * Store Excel import records.
     */
    public function storeExcel(Request $request)
    {
        $request->validate([
            'organization_name' => 'required',
            'appointment_date' => 'required|date',
            'excel_file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new BulkAppointmentImport($request->all()), $request->file('excel_file'));
        event(new QueueUpdated());

        return redirect()->route('appointments.index')->with('success', 'Excel data imported successfully!');
    }

    /**
     * Parse Excel to Array for live table rendering.
     */
    public function parseExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            $data = Excel::toArray(new BulkAppointmentImport, $request->file('excel_file'));
            $patients = collect($data[0] ?? [])->filter(function($row) {
                return !empty($row['name']) || !empty($row['first_name']) || !empty($row[0]);
            })->values();

            return response()->json($patients);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not read file. Please ensure it is a valid Excel spreadsheet.'], 500);
        }
    }

    /**
     * Download refined XLSX Bio-data template with dropdown data validations and cell instructions.
     */
    public function downloadTemplate()
    {
        $filename = "medscreen_bulk_template.xlsx";
        return Excel::download(new BulkTemplateExport(), $filename);
    }
}