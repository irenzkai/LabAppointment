<?php

namespace App\Http\Controllers;

use App\Models\{Appointment, Service, Dependent, AppointmentConfig, ActivityLog, User, PaymentProvider};
use App\Notifications\AppointmentNotification;
use App\Http\Controllers\ResultController; // Imported to execute secure PDF deliveries on release [52, 115]
use App\Events\QueueUpdated; // Broadcasts status/badge refreshes to the Hub and master queue [53]
use App\Events\NotificationSent; // Broadcasts instant real-time bell notifications globally [423]
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{Auth, Gate, DB, Str, Storage};
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * View Appointments: Categorized for Patients, Master Queue for Staff.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. SELF-CLEANING SWEEP: Enforces exact clinical data retention policies [10]

        // A. Automatically purge incomplete, unpaid, soft-deleted, or expired appointments older than 90 days [10]
        Appointment::where('appointment_date', '<', Carbon::now()->subDays(90))
            ->where('status', '!=', 'released')
            ->delete();

        // B. Automatically purge completed/released clinical folders older than 10 years (10-year retention rule) [10]
        Appointment::where('appointment_date', '<', Carbon::now()->subYears(10))
            ->where('status', 'released')
            ->delete();

        $services = Service::where('is_available', true)->orderBy('name')->get();
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        if ($user->isPatient()) {
            $self = Appointment::with(['services', 'user', 'dependent', 'result'])
                ->where('user_id', $user->id)
                ->whereNull('dependent_id')
                ->whereNull('batch_id')
                ->where('deleted_by_patient', false)
                ->latest()
                ->paginate(10, ['*'], 'self_page')
                ->withQueryString();

            $dependents = Appointment::with(['services', 'user', 'dependent', 'result'])
                ->where('user_id', $user->id)
                ->whereNotNull('dependent_id')
                ->where('deleted_by_patient', false)
                ->latest()
                ->paginate(10, ['*'], 'dependents_page')
                ->withQueryString();

            $bulkPaginator = Appointment::with(['services', 'user', 'dependent', 'result'])
                ->where('user_id', $user->id)
                ->whereNotNull('batch_id')
                ->where('deleted_by_patient', false)
                ->latest()
                ->paginate(10, ['*'], 'bulk_page')
                ->withQueryString();

            $bulkGroups = $bulkPaginator->getCollection()->groupBy('batch_id');

            $allApps = $self->getCollection()
                ->concat($dependents->getCollection())
                ->concat($bulkPaginator->getCollection());

            return view('appointments.index', [
                'self' => $self,
                'dependents' => $dependents,
                'bulkGroups' => $bulkGroups,
                'bulkPaginator' => $bulkPaginator,
                'allApps' => $allApps,
                'is_staff' => false,
                'services' => $services,
                'paymentProviders' => $paymentProviders
            ]);
        }

        // --- STAFF MAIN QUEUE CONTROLS ---
        $query = Appointment::with(['services', 'user', 'dependent', 'result']);

        // A. Server-side search filter
        $search = $request->query('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('organization_name', 'like', "%{$search}%")
                    ->orWhere('batch_id', 'like', "%{$search}%");
            });
        }

        // B. Context-aware Status filtering
        $statusFilter = $request->query('status');
        if ($statusFilter) {
            if ($statusFilter === 'needs_action') {
                $query->where(function($q) {
                    $q->whereIn('status', ['pending', 'approved', 'retest', 'tested', 'encoded'])
                        ->orWhere(function($sub) {
                            $sub->where('status', 'canceled')
                                ->where('payment_method', 'Cashless')
                                ->where('payment_status', 'paid');
                        });
                });
            } elseif ($statusFilter === 'no_action') {
                $query->where(function($q) {
                    $q->where('status', 'released')
                        ->orWhere(function($sub) {
                            $sub->where('status', 'canceled')
                                ->where(function($sub2) {
                                    $sub2->where('payment_method', '!=', 'Cashless')
                                        ->orWhere('payment_status', '!=', 'paid');
                                });
                        });
                });
            } else {
                $query->where('status', $statusFilter);
            }
        }

        // C. Sort variables
        $sortBy = $request->query('sort_by', 'date');
        $order = $request->query('order', 'desc');
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // Case evaluation separating action-required items from resolved (released/canceled) files
        $query->select('*')
            ->selectRaw("
                CASE 
                    WHEN status IN ('pending', 'approved', 'retest', 'tested', 'encoded') THEN 2
                    WHEN status = 'canceled' AND payment_method = 'Cashless' AND payment_status = 'paid' THEN 2
                    ELSE 1
                END as action_priority
            ");

        // Action requirements strictly on top
        $query->orderBy('action_priority', 'desc');

        // Apply sub-ordering parameters
        if ($sortBy === 'name') {
            $query->orderBy('patient_name', $order);
        } elseif ($sortBy === 'submitted') {
            $query->orderBy('created_at', $order);
        } else {
            $query->orderBy('appointment_date', $order)
                ->orderBy('time_slot', $order);
        }

        $staffPaginator = $query->paginate(10, ['*'], 'staff_page')->withQueryString();

        $staffQueue = $staffPaginator->getCollection()
            ->groupBy(fn($item) => $item->batch_id ?? 'single_' . $item->id);

        $allApps = $staffPaginator->getCollection();

        return view('appointments.index', [
            'staffQueue' => $staffQueue,
            'staffPaginator' => $staffPaginator,
            'allApps' => $allApps,
            'is_staff' => true,
            'services' => $services,
            'paymentProviders' => $paymentProviders
        ]);
    }

    /**
     * Display the 5-Step Appointment Wizard.
     */
    public function create()
    {
        $services = Service::where('is_available', true)->orderBy('name')->get();
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        return view('appointments.create', compact('services', 'paymentProviders'));
    }

    /**
     * Store Appointment from Wizard.
     */
    public function store(Request $request)
    {
        // Custom name validation closure rules matching register schema (supports Unicode Ñ/ñ)
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') {
                return;
            }
            if (!preg_match('/^[\p{L} \s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[\p{L} ]/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[\p{L}]/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        $request->validate([
            'target_type' => 'required|in:self,dependent,bulk',
            'dependent_id' => 'required_if:target_type,dependent|nullable|exists:dependents,id',
            'organization_name' => 'required_if:target_type,bulk|nullable|string|max:255',
            'patient_first_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patient_last_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'], // Optional suffix
            'patient_sex' => 'required|in:Male,Female',
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_phone' => ['required', 'string', 'regex:/^09\d{9}$/'], // PH standard format

            // PSGC size standard matching
            'patient_street' => 'required|string|max:150',
            'patient_barangay' => 'required|string|max:100',
            'patient_city' => 'required|string|max:100',
            'patient_province' => 'required|string|max:100',

            'referral_note' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'service_ids' => 'required|array|min:1',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
            'payment_method' => 'required|string',
            'payment_receipt' => 'required_if:payment_method,Cashless|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
        ], [
            'patient_phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'patient_suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.'
        ]);

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();

        $bookedCount = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released'])->count();

        if ($bookedCount >= ($config->max_patients_per_slot ?? 1)) {
            return back()->withErrors(['time_slot' => 'This slot is no longer available. Please select another time.'])->withInput();
        }

        $mName = $request->patient_middle_name;
        $lName = $request->patient_last_name;
        $suffix = $request->filled('patient_suffix') ? strtoupper($request->patient_suffix) : '';

        // Compile display name dynamically
        $fullName = $request->patient_first_name . ($mName && strtoupper($mName) !== 'N/A' ? ' ' . $mName : '') . ' ' . $lName;
        if (!empty($suffix)) {
            $fullName .= ' ' . $suffix;
        }

        $data = [
            'user_id' => Auth::id(),
            'dependent_id' => ($request->target_type === 'dependent') ? $request->dependent_id : null,
            'organization_name' => ($request->target_type === 'bulk') ? strtoupper($request->organization_name) : null,
            'batch_id' => ($request->target_type === 'bulk') ? Str::random(10) : null,
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'patient_first_name' => strtoupper($request->patient_first_name),
            'patient_middle_name' => $mName ? strtoupper($mName) : 'N/A',
            'patient_last_name' => strtoupper($lName),
            'patient_suffix' => $suffix ?: null,
            'patient_name' => strtoupper($fullName),
            'patient_sex' => $request->patient_sex,
            'patient_birthdate' => $request->patient_birthdate,
            'patient_phone' => $request->patient_phone,
            'patient_street' => strtoupper($request->patient_street),
            'patient_barangay' => strtoupper($request->patient_barangay),
            'patient_city' => strtoupper($request->patient_city),
            'patient_province' => strtoupper($request->patient_province),
            'payment_method' => $request->payment_method,
            'payment_status' => 'unpaid',
            'status' => 'pending'
        ];

        if ($request->hasFile('referral_note') && $request->file('referral_note')->isValid()) {
            $data['referral_note'] = $request->file('referral_note')->store('referrals', 'public');
        }

        if ($request->hasFile('payment_receipt') && $request->file('payment_receipt')->isValid()) {
            $data['payment_receipt'] = $request->file('payment_receipt')->store('receipts', 'public');
        }

        DB::beginTransaction();
        try {
            $appointment = Appointment::create($data);
            $appointment->services()->attach($request->service_ids);

            ActivityLog::record('BOOKED', "New appointment for {$appointment->patient_name}", $appointment->patient_name, $appointment->id);

            $notifiables = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
            foreach ($notifiables as $staff) {
                $staff->notify(new AppointmentNotification([
                    'title' => 'New Booking Request',
                    'message' => "Patient: {$appointment->patient_name} for " . date('M d', strtotime($appointment->appointment_date)),
                    'url' => route('appointments.index'),
                    'type' => 'info'
                ]));

                event(new NotificationSent($staff->id, 'New Booking Request', "Patient: {$appointment->patient_name} for " . date('M d', strtotime($appointment->appointment_date))));
            }

            DB::commit();
            event(new QueueUpdated());

            return redirect()->route('appointments.index')->with('success', 'Appointment successfully requested!');
        } catch (\Exception $e) {
            DB::rollback();
            if (isset($data['referral_note'])) {
                Storage::disk('public')->delete($data['referral_note']);
            }
            if (isset($data['payment_receipt'])) {
                Storage::disk('public')->delete($data['payment_receipt']);
            }
            return back()->with('error', 'Booking failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the dedicated full-page resubmission form for returned or canceled appointments.
     */
    public function editResubmit(Appointment $appointment)
    {
        // Enforce authorization: Ensure the patient owns this specific record
        if ($appointment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Validate current status: Must be eligible for modification
        if ($appointment->status === 'released') {
            return redirect()->route('appointments.index')->with('error', 'Released appointments are locked and cannot be modified.');
        }

        $isExpired = $appointment->isExpired();
        
        if (!($appointment->status === 'returned' || $appointment->status === 'canceled' || $isExpired)) {
            return redirect()->route('appointments.index')->with('error', 'This appointment is currently not eligible for resubmission.');
        }

        $services = Service::where('is_available', true)->orderBy('name')->get();
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        return view('appointments.resubmit', compact('appointment', 'services', 'paymentProviders', 'isExpired'));
    }

    /**
     * Resubmit: Patients correcting a "Returned" or "Canceled" record.
     */
    public function update(Request $request, Appointment $appointment)
    {
        // Enforce authorization: Prevent cross-user parameter tampering
        if ($appointment->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Do not allow post-release tampering
        if ($appointment->status === 'released') {
            return redirect()->route('appointments.index')->with('error', 'Released appointments cannot be updated.');
        }

        $isBulk = !is_null($appointment->batch_id);

        // Unicode-aware name closure supporting Spanish & Filipino Ñ/ñ characters
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') {
                return;
            }
            if (!preg_match('/^[\p{L} \s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[\p{L} ]/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[\p{L}]/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        $rules = [
            'patient_first_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patient_last_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'],
            'patient_sex' => 'required|in:Male,Female',
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'patient_street' => 'required|string|max:150',
            'service_ids' => 'required|array|min:1',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
        ];

        if (!$isBulk) {
            $rules['patient_barangay'] = 'required|string|max:100';
            $rules['patient_city'] = 'required|string|max:100';
            $rules['patient_province'] = 'required|string|max:100';
            $rules['payment_method'] = 'required|string';
            $rules['referral_note'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240';

            // Only require payment receipt if payment is NOT already confirmed (rollover)
            $isReceiptRequired = false;
            if ($request->payment_method === 'Cashless' && $appointment->payment_status !== 'paid') {
                if (in_array($appointment->status, ['canceled', 'returned']) || in_array($appointment->payment_status, ['invalid', 'refunded'])) {
                    if (!$appointment->payment_receipt || $request->input('remove_receipt') === '1') {
                        $isReceiptRequired = true;
                    }
                }
            }

            if ($isReceiptRequired) {
                $rules['payment_receipt'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:10240';
            } else {
                $rules['payment_receipt'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240';
            }
        }

        $request->validate($rules, [
            'patient_phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'patient_suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ]);

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();

        $booked = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released'])
            ->where('id', '!=', $appointment->id)->count();

        if ($booked >= ($config->max_patients_per_slot ?? 1)) {
            return back()->withErrors(['time_slot' => 'Slot is full.']);
        }

        $mName = $request->patient_middle_name;
        $lName = $request->patient_last_name;
        $suffix = $request->filled('patient_suffix') ? strtoupper($request->patient_suffix) : '';

        $fullName = $request->patient_first_name . ($mName && strtoupper($mName) !== 'N/A' ? ' ' . $mName : '') . ' ' . $lName;
        if (!empty($suffix)) {
            $fullName .= ' ' . $suffix;
        }

        $street = strtoupper(trim($request->patient_street));
        $barangay = $isBulk ? 'N/A' : strtoupper(trim($request->patient_barangay));
        $city = $isBulk ? 'N/A' : strtoupper(trim($request->patient_city));
        $province = $isBulk ? 'N/A' : strtoupper(trim($request->patient_province));

        // Preserve 'paid' payment_status for canceled appointments being rolled over
        $paymentStatus = ($appointment->status === 'canceled' && $appointment->payment_status === 'paid') ? 'paid' : 'unpaid';

        $updateData = [
            'patient_first_name' => strtoupper($request->patient_first_name),
            'patient_middle_name' => $mName ? strtoupper($mName) : 'N/A',
            'patient_last_name' => strtoupper($lName),
            'patient_suffix' => $suffix ?: null,
            'patient_name' => strtoupper($fullName),
            'patient_sex' => $request->patient_sex,
            'patient_birthdate' => $request->patient_birthdate,
            'patient_phone' => $request->patient_phone,
            'patient_street' => $street,
            'patient_barangay' => $barangay,
            'patient_city' => $city,
            'patient_province' => $province,
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending',
            'payment_status' => $paymentStatus,
            'return_reason' => null
        ];

        if (!$isBulk) {
            $updateData['payment_method'] = $request->payment_method;

            if ($request->payment_method === 'Cash') {
                if ($appointment->payment_receipt) {
                    Storage::disk('public')->delete($appointment->payment_receipt);
                }
                $updateData['payment_receipt'] = null;
            }
        }

        // Safely process server-side file deletion if requested
        if (!$isBulk) {
            if ($request->input('remove_referral') === '1') {
                if ($appointment->referral_note) {
                    Storage::disk('public')->delete($appointment->referral_note);
                }
                $updateData['referral_note'] = null;
            }

            if ($request->hasFile('referral_note') && $request->file('referral_note')->isValid()) {
                if ($appointment->referral_note) {
                    Storage::disk('public')->delete($appointment->referral_note);
                }
                $updateData['referral_note'] = $request->file('referral_note')->store('referrals', 'public');
            }
        }

        if (!$isBulk) {
            if ($request->input('remove_receipt') === '1') {
                if ($appointment->payment_receipt) {
                    Storage::disk('public')->delete($appointment->payment_receipt);
                }
                $updateData['payment_receipt'] = null;
            }

            if ($request->hasFile('payment_receipt') && $request->file('payment_receipt')->isValid()) {
                if ($appointment->payment_receipt) {
                    Storage::disk('public')->delete($appointment->payment_receipt);
                }
                $updateData['payment_receipt'] = $request->file('payment_receipt')->store('receipts', 'public');
            }
        }

        $appointment->update($updateData);
        $appointment->services()->sync($request->service_ids);

        ActivityLog::record('RESUBMITTED', 'Patient corrected schedule', $appointment->patient_name, $appointment->id);

        $notifiables = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($notifiables as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Resubmitted Booking',
                'message' => "Patient: {$appointment->patient_name} has corrected and resubmitted their appointment.",
                'url' => route('appointments.index'),
                'type' => 'info'
            ]));

            event(new NotificationSent($staff->id, 'Resubmitted Booking', "Patient: {$appointment->patient_name} has corrected and resubmitted their appointment."));
        }

        event(new QueueUpdated());

        return redirect()->route('appointments.index')->with('success', 'Appointment resubmitted for approval.');
    }

    /**
     * Transition 1: Approve, Return, or Release
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:approved,returned,released',
            'return_reason' => 'required_if:status,returned'
        ]);

        $updatePayload = ['status' => $request->status];
        if ($request->status == 'returned') {
            $updatePayload['return_reason'] = $request->return_reason;
        } else {
            $updatePayload['return_reason'] = null;
        }

        if ($request->has('payment_status')) {
            $updatePayload['payment_status'] = $request->payment_status;
        }

        if ($appointment->batch_id && $request->input('batch') === 'true') {
            Appointment::where('batch_id', $appointment->batch_id)->update($updatePayload);

            $batchApps = Appointment::where('batch_id', $appointment->batch_id)->get();
            foreach ($batchApps as $app) {
                $patient = $app->user;
                if ($patient) {
                    $dateFormatted = date('M d, Y', strtotime($app->appointment_date));
                    $timeFormatted = date('h:i A', strtotime($app->time_slot));

                    if ($request->status === 'approved') {
                        $patient->notify(new AppointmentNotification([
                            'title' => 'Appointment Approved',
                            'message' => "Your laboratory appointment scheduled for {$dateFormatted} at {$timeFormatted} has been approved.",
                            'url' => route('appointments.index'),
                            'type' => 'success'
                        ]));

                        event(new NotificationSent($patient->id, 'Appointment Approved', "Your laboratory appointment scheduled for {$dateFormatted} at {$timeFormatted} has been approved."));
                    } elseif ($request->status === 'returned') {
                        $patient->notify(new AppointmentNotification([
                            'title' => 'Appointment Returned',
                            'message' => "Your appointment scheduled for {$dateFormatted} at {$timeFormatted} requires corrections: \"{$request->return_reason}\"",
                            'url' => route('appointments.index'),
                            'type' => 'danger'
                        ]));

                        event(new NotificationSent($patient->id, 'Appointment Returned', "Your appointment scheduled for {$dateFormatted} at {$timeFormatted} requires corrections."));
                    }
                }
            }
        } else {
            $appointment->update($updatePayload);

            $patient = $appointment->user;
            if ($patient) {
                $dateFormatted = date('M d, Y', strtotime($appointment->appointment_date));
                $timeFormatted = date('h:i A', strtotime($appointment->time_slot));

                if ($request->status === 'approved') {
                    $appointment->user->notify(new AppointmentNotification([
                        'title' => 'Appointment Approved',
                        'message' => "Your laboratory appointment scheduled for {$dateFormatted} at {$timeFormatted} has been approved.",
                        'url' => route('appointments.index'),
                        'type' => 'success'
                    ]));

                    event(new NotificationSent($patient->id, 'Appointment Approved', "Your laboratory appointment scheduled for {$dateFormatted} at {$timeFormatted} has been approved."));
                } elseif ($request->status === 'returned') {
                    $appointment->user->notify(new AppointmentNotification([
                        'title' => 'Appointment Returned',
                        'message' => "Your appointment scheduled for {$dateFormatted} at {$timeFormatted} requires corrections: \"{$request->return_reason}\"",
                        'url' => route('appointments.index'),
                        'type' => 'danger'
                    ]));

                    event(new NotificationSent($patient->id, 'Appointment Returned', "Your appointment scheduled for {$dateFormatted} at {$timeFormatted} requires corrections."));
                }
            }
        }

        if ($request->status === 'released') {
            if ($appointment->batch_id && $request->input('batch') === 'true') {
                $batchApps = Appointment::where('batch_id', $appointment->batch_id)->get();
                foreach ($batchApps as $app) {
                    ResultController::deliverResult($app);
                }
            } elseif ($appointment->batch_id) {
                ResultController::deliverResult($appointment);
            }
        }

        event(new QueueUpdated());

        if ($request->status === 'released') {
            return redirect()->route('appointments.index')->with('success', 'Appointment folder has been successfully released.');
        }

        return back()->with('success', 'Appointment updated to ' . strtoupper($request->status));
    }

    /**
     * Transition 2: Mark as Tested (Clinical Lab Tech)
     */
    public function markTested(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isLabTech')) {
            abort(403, 'Clinical personnel only.');
        }

        $action = $request->input('action', 'tested'); // 'tested' or 'retest'

        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') {
                return;
            }
            if (!preg_match('/^[\p{L} \s.\'-]+$/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[\p{L} ]/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[\p{L}]/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $fail("The " . str_replace('patient_', ' ', $attribute) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        if ($action === 'retest') {
            $request->validate([
                'patient_first_name' => ['required', 'string', 'max:60', $nameRule],
                'patient_middle_name' => ['nullable', 'string', 'max:60', $nameRule],
                'patient_last_name' => ['required', 'string', 'max:60', $nameRule],
                'patient_suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'],
                'patient_birthdate' => 'required|date|before_or_equal:today',
                'patient_sex' => 'required|in:Male,Female',
                'patient_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
                'patient_street' => 'required|string|max:150',
                'patient_barangay' => 'required|string|max:100',
                'patient_city' => 'required|string|max:100',
                'patient_province' => 'required|string|max:100',
                'service_ids' => 'required|array|min:1',
                'payment_amount' => 'required|numeric|min:0',
                'retest_reason' => 'required|string',
                'retest_custom_reason' => 'required_if:retest_reason,Others|nullable|string|min:5',
            ], [
                'patient_phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
                'patient_suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
            ]);

            $retestReason = $request->input('retest_reason') === 'Others'
                ? $request->input('retest_custom_reason')
                : $request->input('retest_reason');

            $fName = strtoupper(trim($request->patient_first_name));
            $mName = ($request->patient_middle_name && strtoupper($request->patient_middle_name) !== 'N/A') ? strtoupper(trim($request->patient_middle_name)) : 'N/A';
            $lName = strtoupper(trim($request->patient_last_name));
            $suffix = $request->filled('patient_suffix') ? strtoupper($request->patient_suffix) : '';

            $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
            if (!empty($suffix)) {
                $displayName .= " {$suffix}";
            }

            $updatePayload = [
                'patient_first_name' => $fName,
                'patient_middle_name' => $mName,
                'patient_last_name' => $lName,
                'patient_suffix' => $suffix ?: null,
                'patient_name' => $displayName,
                'patient_birthdate' => $request->patient_birthdate,
                'patient_sex' => $request->patient_sex,
                'patient_phone' => $request->patient_phone,
                'patient_street' => strtoupper(trim($request->patient_street)),
                'patient_barangay' => strtoupper(trim($request->patient_barangay)),
                'patient_city' => strtoupper(trim($request->patient_city)),
                'patient_province' => strtoupper(trim($request->patient_province)),
                'payment_status' => 'paid', // Automatically flagged as PAID
                'status' => 'retest',
                'return_reason' => $retestReason, // Stores the exception reason in return_reason
                'tested_at' => null, // Resets tested timestamp since a recollect is needed
                'result_estimated_at' => null
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'payment_amount')) {
                $updatePayload['payment_amount'] = $request->input('payment_amount');
            }

            $appointment->update($updatePayload);
            $appointment->services()->sync($request->service_ids);

            ActivityLog::record('RETEST', 'Marked for retesting: ' . $retestReason, $appointment->patient_name, $appointment->id);

            $patient = $appointment->user;
            if ($patient) {
                $patient->notify(new AppointmentNotification([
                    'title' => 'Retesting Required',
                    'message' => "Your clinical sample requires a recollect due to: \"{$retestReason}\". Please return to the Medscreen Diagnostic Laboratory for retesting.",
                    'url' => route('appointments.index'),
                    'type' => 'danger'
                ]));

                event(new NotificationSent($patient->id, 'Retesting Required', "Your sample requires a recollect. Please return to the lab."));
            }

            event(new QueueUpdated());

            return redirect()->back()->with('success', 'Appointment successfully flagged for retesting.');
        }

        // --- PROGRESSION TO TESTED: CONFIRM, UPDATE DETAILS & COMPLETE CLINICAL SAMPLING ---
        $request->validate([
            'patient_first_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patient_last_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z0-9\s.]+$/u'],
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_sex' => 'required|in:Male,Female',
            'patient_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'patient_street' => 'required|string|max:150',
            'patient_barangay' => 'required|string|max:100',
            'patient_city' => 'required|string|max:100',
            'patient_province' => 'required|string|max:100',
            'service_ids' => 'required|array|min:1',
            'payment_amount' => 'required|numeric|min:0',
            'est_hours' => 'nullable|integer|min:0',
            'est_minutes' => 'nullable|integer|min:0',
        ], [
            'patient_phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'patient_suffix.regex' => 'The suffix may only contain letters, numbers, spaces, and periods.',
        ]);

        $h = (int)$request->input('est_hours', 0);
        $m = (int)$request->input('est_minutes', 0);
        $est = ($h > 0 || $m > 0) ? now()->addHours($h)->addMinutes($m) : null;

        $fName = strtoupper(trim($request->patient_first_name));
        $mName = ($request->patient_middle_name && strtoupper($request->patient_middle_name) !== 'N/A') ? strtoupper(trim($request->patient_middle_name)) : 'N/A';
        $lName = strtoupper(trim($request->patient_last_name));
        $suffix = $request->filled('patient_suffix') ? strtoupper($request->patient_suffix) : '';

        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) {
            $displayName .= " {$suffix}";
        }

        $updatePayload = [
            'patient_first_name' => $fName,
            'patient_middle_name' => $mName,
            'patient_last_name' => $lName,
            'patient_suffix' => $suffix ?: null,
            'patient_name' => $displayName,
            'patient_birthdate' => $request->patient_birthdate,
            'patient_sex' => $request->patient_sex,
            'patient_phone' => $request->patient_phone,
            'patient_street' => strtoupper(trim($request->patient_street)),
            'patient_barangay' => strtoupper(trim($request->patient_barangay)),
            'patient_city' => strtoupper(trim($request->patient_city)),
            'patient_province' => strtoupper(trim($request->patient_province)),
            'payment_status' => 'paid', // Automatically flagged as PAID on sampling stage
            'status' => 'tested',
            'tested_at' => now(),
            'result_estimated_at' => $est,
            'return_reason' => null
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'payment_amount')) {
            $updatePayload['payment_amount'] = $request->input('payment_amount');
        }

        $appointment->update($updatePayload);
        $appointment->services()->sync($request->service_ids);

        ActivityLog::record('TESTED', 'Sampling completed and details verified', $appointment->patient_name, $appointment->id);

        $patient = $appointment->user;
        if ($patient) {
            $estTimeText = $est ? " (Estimated processing duration: " . ($h > 0 ? "{$h}h " : "") . ($m > 0 ? "{$m}m " : "") . ")" : "";
            $patient->notify(new AppointmentNotification([
                'title' => 'Sampling Completed',
                'message' => "Your clinical laboratory sampling is complete. Your results are currently being processed in our lab." . $estTimeText,
                'url' => route('appointments.index'),
                'type' => 'info'
            ]));

            event(new NotificationSent($patient->id, 'Sampling Completed', "Your clinical laboratory sampling is complete. Your results are currently being processed in our lab."));
        }

        event(new QueueUpdated());

        return redirect()->back()->with('success', 'Sampling logged. Results are being processed.');
    }

    /**
     * POST /appointments/{appointment}/cancel
     * Allows only patients to cancel appointments prior to tested stage.
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        if ($user->isEmployee()) {
            abort(403, 'Employees/Staff are not authorized to cancel appointments.');
        }

        if ($appointment->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($appointment->status, ['tested', 'encoded', 'released'])) {
            return back()->with('error', 'Appointments that have progressed to sampling cannot be canceled.');
        }

        $reason = 'Canceled by patient';

        // MODIFIED: Preserves the 'paid' status so that refund tracking workflows function accurately.
        $paymentStatus = $appointment->payment_status === 'paid' ? 'paid' : 'unpaid';

        $appointment->update([
            'status' => 'canceled',
            'payment_status' => $paymentStatus,
            'return_reason' => $reason
        ]);

        ActivityLog::record('CANCELED', "Appointment canceled. Reason: {$reason}", $appointment->patient_name, $appointment->id);

        event(new QueueUpdated());

        return back()->with('success', 'Appointment successfully canceled.');
    }

    /**
     * POST /appointments/{appointment}/invalid-payment
     */
    public function markPaymentInvalid(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required',
            'custom_reason' => 'required_if:reason,Others'
        ]);

        $invalidReason = $request->input('reason') === 'Others' ? $request->input('custom_reason') : $request->input('reason');

        $appointment->update([
            'payment_status' => 'invalid',
            'return_reason' => 'Invalid Payment: ' . $invalidReason
        ]);

        ActivityLog::record('INVALID PAYMENT', 'Payment flagged as invalid: ' . $invalidReason, $appointment->patient_name, $appointment->id);

        event(new QueueUpdated());

        return back()->with('success', 'Payment flagged as invalid.');
    }

    /**
     * POST /appointments/{appointment}/refund
     */
    public function confirmRefund(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $staffName = Auth::user()->name;
        $staffRole = strtoupper(Auth::user()->role);
        $timestamp = now()->format('M d, Y | h:i A');

        $logMessage = "Refund processed by {$staffName} ({$staffRole}) on {$timestamp}";

        $appointment->update([
            'payment_status' => 'refunded',
            'return_reason' => $logMessage
        ]);

        ActivityLog::record('REFUNDED', "Refund manually confirmed for {$appointment->patient_name}. Processed by: {$staffName} ({$staffRole})", $appointment->patient_name, $appointment->id);

        event(new QueueUpdated());

        return back()->with('success', 'Refund confirmed successfully.');
    }

    /**
     * Patient Soft Delete
     */
    public function softDelete(Appointment $appointment)
    {
        if ($appointment->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$appointment->canBeDeletedByPatient()) {
            return back()->with('error', 'Paid or active appointments cannot be deleted.');
        }

        $appointment->update(['deleted_by_patient' => true]);

        ActivityLog::record('SOFT DELETED', 'Patient soft-deleted expired appointment', $appointment->patient_name, $appointment->id);

        event(new QueueUpdated());

        return redirect()->back()->with('success', 'Appointment removed from your dashboard.');
    }

    /**
     * Staff Manual Payment status toggle
     */
    public function confirmPayment(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) {
            abort(403);
        }

        $request->validate([
            'payment_status' => 'required|in:unpaid,paid'
        ]);

        if ($appointment->batch_id) {
            Appointment::where('batch_id', $appointment->batch_id)->update(['payment_status' => $request->payment_status]);
        } else {
            $appointment->update(['payment_status' => $request->payment_status]);
        }

        $statusLabel = strtoupper($request->payment_status);
        ActivityLog::record('PAYMENT UPDATE', "Staff flagged appointment payment as {$statusLabel}", $appointment->patient_name, $appointment->id);

        event(new QueueUpdated());

        return redirect()->back()->with('success', "Payment status updated to {$statusLabel}.");
    }
}