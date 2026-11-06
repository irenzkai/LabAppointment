<?php

namespace App\Http\Controllers;

use App\Models\{Appointment, Service, Package, Dependent, AppointmentConfig, ActivityLog, User, PaymentProvider};
use App\Notifications\AppointmentNotification;
use App\Http\Controllers\ResultController;
use App\Events\QueueUpdated;
use App\Events\NotificationSent;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{Auth, Gate, DB, Str, Storage};
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * View Appointments: Categorized for Patients, Master Queue for Staff with Expired/Voided & Action filters.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. SELF-CLEANING SWEEP
        Appointment::where('appointment_date', '<', Carbon::now()->subDays(90))
            ->where('status', '!=', 'released')
            ->delete();

        Appointment::where('appointment_date', '<', Carbon::now()->subYears(10))
            ->where('status', 'released')
            ->delete();

        $services = Service::where('is_available', true)->orderBy('name')->get();
        $packages = Package::with('services')->where('is_available', true)->orderBy('name')->get();
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        $viewMode = $request->query('view');
        $isStaffUser = $user->isEmployee();
        $isStaffQueue = $isStaffUser && ($viewMode !== 'patient');

        // =========================================================================
        // PATIENT APPOINTMENT DASHBOARD VIEW
        // =========================================================================
        if (! $isStaffQueue) {
            // Myself: Individual appointments booked by user for self
            $self = Appointment::with(['services', 'packages.services', 'user', 'dependent', 'result'])
                ->where('user_id', $user->id)
                ->whereNull('dependent_id')
                ->whereNull('batch_id')
                ->where('deleted_by_patient', false)
                ->latest()
                ->paginate(10, ['*'], 'self_page')
                ->withQueryString();

            // Family Dependents tab
            $dependents = Appointment::with(['services', 'packages.services', 'user', 'dependent', 'result'])
                ->where('user_id', $user->id)
                ->whereNotNull('dependent_id')
                ->where('deleted_by_patient', false)
                ->latest()
                ->paginate(10, ['*'], 'dependents_page')
                ->withQueryString();

            // Bulk tab: ONLY for the creator/maker of the bulk appointment batch
            $bulkPaginator = Appointment::with(['services', 'packages.services', 'user', 'dependent', 'result'])
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

            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'self' => $self,
                    'dependents' => $dependents,
                    'bulkGroups' => $bulkGroups,
                    'bulkPaginator' => $bulkPaginator,
                ]);
            }

            return view('appointments.index', [
                'self' => $self,
                'dependents' => $dependents,
                'bulkGroups' => $bulkGroups,
                'bulkPaginator' => $bulkPaginator,
                'allApps' => $allApps,
                'is_staff' => false,
                'services' => $services,
                'packages' => $packages,
                'paymentProviders' => $paymentProviders
            ]);
        }

        // =========================================================================
        // STAFF MASTER CLINICAL QUEUE
        // =========================================================================
        $query = Appointment::with(['services', 'packages.services', 'user', 'dependent', 'result']);
        $nowSub24 = Carbon::now()->subHours(24)->toDateTimeString();

        // A. Server-side text search filter
        $search = $request->query('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('organization_name', 'like', "%{$search}%")
                    ->orWhere('batch_id', 'like', "%{$search}%");
            });
        }

        // B. Booking Type Filtering (Bulk, Self, Dependent)
        $appType = $request->query('app_type', 'all');
        if ($appType === 'self') {
            $query->whereNull('dependent_id')->whereNull('batch_id');
        } elseif ($appType === 'dependent') {
            $query->whereNotNull('dependent_id');
        } elseif ($appType === 'bulk') {
            $query->whereNotNull('batch_id');
        }

        // C. Specific Schedule Date Filtering
        $dateFilter = $request->query('date');
        if ($dateFilter) {
            $query->whereDate('appointment_date', $dateFilter);
        }

        // D. Context-aware Status filtering
        $statusFilter = $request->query('status');
        if ($statusFilter) {
            if ($statusFilter === 'needs_action') {
                $query->where(function($q) {
                    $q->whereIn('status', ['pending', 'approved', 'retest', 'tested', 'encoded', 'needs_review'])
                        ->orWhere(function($sub) {
                            $sub->where('status', 'canceled')
                                ->where('payment_method', 'Cashless')
                                ->where('payment_status', 'paid');
                        });
                })->where(function($q) use ($nowSub24) {
                    $q->whereIn('status', ['retest', 'tested', 'encoded'])
                        ->orWhereRaw("TIMESTAMP(appointment_date, time_slot) >= ?", [$nowSub24]);
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
            } elseif ($statusFilter === 'expired' || $statusFilter === 'voided') {
                $query->whereNotIn('status', ['retest', 'tested', 'encoded', 'released'])
                    ->whereRaw("TIMESTAMP(appointment_date, time_slot) < ?", [$nowSub24]);
            } else {
                $query->where('status', $statusFilter);
                if (in_array($statusFilter, ['pending', 'approved', 'returned', 'needs_review'])) {
                    $query->whereRaw("TIMESTAMP(appointment_date, time_slot) >= ?", [$nowSub24]);
                }
            }
        }

        // E. Sort variables & Action Priority Ordering
        $sortBy = $request->query('sort_by', 'date');
        $order = in_array(strtolower($request->query('order', 'desc')), ['asc', 'desc']) ? strtolower($request->query('order', 'desc')) : 'desc';

        $query->select('*')
            ->selectRaw("
                CASE 
                    WHEN status IN ('retest', 'tested', 'encoded') THEN 2
                    WHEN status IN ('pending', 'approved', 'needs_review') AND TIMESTAMP(appointment_date, time_slot) >= ? THEN 2
                    WHEN status = 'canceled' AND payment_method = 'Cashless' AND payment_status = 'paid' THEN 2
                    ELSE 1
                END as action_priority
            ", [$nowSub24]);

        $query->orderBy('action_priority', 'desc');

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

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'staffQueue' => $staffQueue,
                'staffPaginator' => $staffPaginator,
                'allApps' => $allApps,
            ]);
        }

        // Return the dedicated Staff Master Queue view
        return view('appointments.queue', [
            'staffQueue' => $staffQueue,
            'staffPaginator' => $staffPaginator,
            'allApps' => $allApps,
            'is_staff' => true,
            'services' => $services,
            'packages' => $packages,
            'paymentProviders' => $paymentProviders
        ]);
    }

    /**
     * Display the 5-Step Appointment Wizard.
     * Pre-loads user profile and verified dependents so fields are presented in readonly mode.
     */
    public function create()
    {
        $user = Auth::user();
        $services = Service::where('is_available', true)->orderBy('name')->get();
        $packages = Package::with('services')->where('is_available', true)->orderBy('name')->get();
        $paymentProviders = PaymentProvider::where('is_active', true)->get();
        $dependents = $user->dependents()->get();

        return view('appointments.create', compact('services', 'packages', 'paymentProviders', 'dependents', 'user'));
    }

    /**
     * Store Appointment from Wizard.
     * Enforces read-only integrity: Demographic details are pulled directly from the profile or dependent record.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'target_type' => 'required|in:self,dependent,bulk',
            'dependent_id' => 'required_if:target_type,dependent|nullable|exists:dependents,id',
            'organization_name' => 'required_if:target_type,bulk|nullable|string|max:255',
            'referral_note' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
            'payment_method' => 'required|string',
            'payment_receipt' => 'required_if:payment_method,Cashless|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
        ]);

        // Unified Anti-Double-Selection & Conflict Check
        $conflictError = $this->validateServiceAndPackageConflicts($request->service_ids, $request->package_ids);
        if ($conflictError) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json(['message' => $conflictError, 'errors' => ['service_ids' => [$conflictError]]], 422);
            }
            return back()->withErrors(['service_ids' => $conflictError])->withInput();
        }

        // Operational Schedule & Capacity Check
        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();

        // Dynamic Same-Day Cutoff Validation (Respects lead time buffer)
        if ($request->appointment_date === Carbon::today()->toDateString()) {
            $leadHours = $config->lead_time_hours ?? 2;
            $earliestAllowedTime = Carbon::now()->addHours($leadHours)->format('H:i:00');

            if ($request->time_slot < $earliestAllowedTime) {
                $err = "Today's appointments require at least {$leadHours} hour(s) advance booking. Please choose a slot after " . date('h:i A', strtotime($earliestAllowedTime)) . ".";
                if ($request->expectsJson() && ! $request->acceptsHtml()) {
                    return response()->json(['message' => $err, 'errors' => ['time_slot' => [$err]]], 422);
                }
                return back()->withErrors(['time_slot' => $err])->withInput();
            }
        }

        $bookedCount = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released', 'needs_review'])->count();

        if ($bookedCount >= ($config->max_patients_per_slot ?? 1)) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'message' => 'This slot is no longer available. Please select another time.',
                    'errors' => ['time_slot' => ['This slot is no longer available. Please select another time.']]
                ], 422);
            }
            return back()->withErrors(['time_slot' => 'This slot is no longer available. Please select another time.'])->withInput();
        }

        // BIND DEMOGRAPHICS STRICTLY FROM SOURCE OF TRUTH (Profile Settings or Dependent Profile)
        if ($request->target_type === 'dependent') {
            $dep = Dependent::where('user_id', $user->id)->findOrFail($request->dependent_id);
            $fName = $dep->first_name;
            $mName = $dep->middle_name ?: 'N/A';
            $lName = $dep->last_name;
            $suffix = $dep->suffix;
            $fullName = $dep->name;
            $birthdate = $dep->birthdate ? Carbon::parse($dep->birthdate)->format('Y-m-d') : null;
            $sex = $dep->sex;
            $phone = $dep->phone ?: $user->phone;
            $email = $user->email;
            $street = $dep->street ?: $user->street;
            $barangay = $dep->barangay ?: $user->barangay;
            $city = $dep->city ?: $user->city;
            $province = $dep->province ?: $user->province;
        } else {
            // For 'self'
            $fName = $user->first_name;
            $mName = $user->middle_name ?: 'N/A';
            $lName = $user->last_name;
            $suffix = $user->suffix;
            $fullName = $user->name;
            $birthdate = $user->birthdate ? Carbon::parse($user->birthdate)->format('Y-m-d') : null;
            $sex = $user->sex;
            $phone = $user->phone;
            $email = $user->email;
            $street = $user->street;
            $barangay = $user->barangay;
            $city = $user->city;
            $province = $user->province;
        }

        $data = [
            'user_id' => $user->id,
            'dependent_id' => ($request->target_type === 'dependent') ? $request->dependent_id : null,
            'organization_name' => ($request->target_type === 'bulk') ? strtoupper($request->organization_name) : null,
            'batch_id' => ($request->target_type === 'bulk') ? Str::random(10) : null,
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'patient_first_name' => strtoupper($fName),
            'patient_middle_name' => ($mName && strtoupper($mName) !== 'N/A') ? strtoupper($mName) : 'N/A',
            'patient_last_name' => strtoupper($lName),
            'patient_suffix' => $suffix ?: null,
            'patient_name' => strtoupper($fullName),
            'patient_email' => strtolower(trim($email)),
            'patient_sex' => $sex,
            'patient_birthdate' => $birthdate,
            'patient_phone' => $phone,
            'patient_street' => strtoupper($street),
            'patient_barangay' => strtoupper($barangay),
            'patient_city' => strtoupper($city),
            'patient_province' => strtoupper($province),
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

            if (!empty($request->service_ids)) {
                $appointment->services()->attach($request->service_ids);
            }
            if (!empty($request->package_ids)) {
                $appointment->packages()->attach($request->package_ids);
            }

            ActivityLog::record('BOOKED', "New appointment for {$appointment->patient_name}", $appointment->patient_name, $appointment->id);

            $notifiables = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
            foreach ($notifiables as $staff) {
                $staff->notify(new AppointmentNotification([
                    'title' => 'New Booking Request',
                    'message' => "Patient: {$appointment->patient_name} for " . date('M d', strtotime($appointment->appointment_date)),
                    'url' => route('appointments.index', ['view' => 'queue']),
                    'type' => 'info'
                ]));
                event(new NotificationSent($staff->id, 'New Booking Request', "Patient: {$appointment->patient_name} for " . date('M d', strtotime($appointment->appointment_date))));
            }

            DB::commit();
            event(new QueueUpdated());

            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment successfully requested!',
                    'appointment' => $appointment->load(['services', 'packages.services', 'user', 'dependent'])
                ], 200);
            }

            $redirectParams = $user->isEmployee() ? ['view' => 'patient'] : [];
            return redirect()->route('appointments.index', $redirectParams)->with('success', 'Appointment successfully requested!');
        } catch (\Exception $e) {
            DB::rollback();
            if (isset($data['referral_note'])) Storage::disk('public')->delete($data['referral_note']);
            if (isset($data['payment_receipt'])) Storage::disk('public')->delete($data['payment_receipt']);

            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking failed: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Booking failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the edit/resubmit form for an appointment.
     */
    public function editResubmit(Appointment $appointment)
    {
        $user = Auth::user();
        $isOwner = ($appointment->user_id === $user->id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        if (!$isOwner) abort(403, 'Unauthorized action.');

        if ($appointment->status === 'released') {
            $redirectParams = $user->isEmployee() ? ['view' => 'patient'] : [];
            return redirect()->route('appointments.index', $redirectParams)->with('error', 'Released appointments are locked and cannot be modified.');
        }

        $isExpired = $appointment->isExpired();
        if (!($appointment->status === 'returned' || $appointment->status === 'canceled' || $appointment->status === 'needs_review' || $isExpired)) {
            $redirectParams = $user->isEmployee() ? ['view' => 'patient'] : [];
            return redirect()->route('appointments.index', $redirectParams)->with('error', 'This appointment is currently not eligible for resubmission.');
        }

        $services = Service::where('is_available', true)->orderBy('name')->get();
        $packages = Package::with('services')->where('is_available', true)->orderBy('name')->get();
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        if (request()->expectsJson() && ! request()->acceptsHtml()) {
            return response()->json([
                'appointment' => $appointment->load(['services', 'packages.services', 'user', 'dependent']),
                'services' => $services,
                'packages' => $packages,
                'paymentProviders' => $paymentProviders,
                'isExpired' => $isExpired
            ]);
        }

        return view('appointments.resubmit', compact('appointment', 'services', 'packages', 'paymentProviders', 'isExpired'));
    }

    /**
     * Update/Resubmit an appointment.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        $isOwner = ($appointment->user_id === $user->id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        if (!$isOwner) abort(403, 'Unauthorized action.');

        if ($appointment->status === 'released') {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json(['message' => 'Released appointments cannot be updated.'], 403);
            }
            $redirectParams = $user->isEmployee() ? ['view' => 'patient'] : [];
            return redirect()->route('appointments.index', $redirectParams)->with('error', 'Released appointments cannot be updated.');
        }

        $isBulk = !is_null($appointment->batch_id);

        $rules = [
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
        ];

        if (!$isBulk) {
            $rules['payment_method'] = 'required|string';
            $rules['referral_note'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240';

            $isReceiptRequired = false;
            if ($request->payment_method === 'Cashless' && $appointment->payment_status !== 'paid') {
                if (in_array($appointment->status, ['canceled', 'returned', 'needs_review']) || in_array($appointment->payment_status, ['invalid', 'refunded'])) {
                    if (!$appointment->payment_receipt || $request->input('remove_receipt') === '1') {
                        $isReceiptRequired = true;
                    }
                }
            }
            $rules['payment_receipt'] = $isReceiptRequired ? 'required|file|mimes:pdf,jpg,jpeg,png|max:10240' : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240';
        }

        $request->validate($rules);

        // Unified Anti-Double-Selection & Conflict Check
        $conflictError = $this->validateServiceAndPackageConflicts($request->service_ids, $request->package_ids);
        if ($conflictError) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json(['message' => $conflictError, 'errors' => ['service_ids' => [$conflictError]]], 422);
            }
            return back()->withErrors(['service_ids' => $conflictError])->withInput();
        }

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();

        // Same-day lead time check
        if ($request->appointment_date === Carbon::today()->toDateString()) {
            $leadHours = $config->lead_time_hours ?? 2;
            $earliestAllowedTime = Carbon::now()->addHours($leadHours)->format('H:i:00');

            if ($request->time_slot < $earliestAllowedTime) {
                $err = "Today's appointments require at least {$leadHours} hour(s) advance booking. Please choose a slot after " . date('h:i A', strtotime($earliestAllowedTime)) . ".";
                if ($request->expectsJson() && ! $request->acceptsHtml()) {
                    return response()->json(['message' => $err, 'errors' => ['time_slot' => [$err]]], 422);
                }
                return back()->withErrors(['time_slot' => $err])->withInput();
            }
        }

        $booked = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released', 'needs_review'])
            ->where('id', '!=', $appointment->id)->count();

        if ($booked >= ($config->max_patients_per_slot ?? 1)) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'message' => 'Slot is full.',
                    'errors' => ['time_slot' => ['Slot is full.']]
                ], 422);
            }
            return back()->withErrors(['time_slot' => 'Slot is full.'])->withInput();
        }

        $paymentStatus = $isBulk 
            ? $appointment->payment_status 
            : (($appointment->status === 'canceled' && $appointment->payment_status === 'paid') ? 'paid' : 'unpaid');

        $updateData = [
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending',
            'suggested_changes' => null,
            'payment_status' => $paymentStatus,
            'return_reason' => null,
            'cancellation_reason' => null
        ];

        if (!$isBulk) {
            $updateData['payment_method'] = $request->payment_method;

            if ($request->payment_method === 'Cash') {
                if ($appointment->payment_receipt) Storage::disk('public')->delete($appointment->payment_receipt);
                $updateData['payment_receipt'] = null;
            }

            if ($request->input('remove_referral') === '1') {
                if ($appointment->referral_note) Storage::disk('public')->delete($appointment->referral_note);
                $updateData['referral_note'] = null;
            }

            if ($request->hasFile('referral_note') && $request->file('referral_note')->isValid()) {
                if ($appointment->referral_note) Storage::disk('public')->delete($appointment->referral_note);
                $updateData['referral_note'] = $request->file('referral_note')->store('referrals', 'public');
            }

            if ($request->input('remove_receipt') === '1') {
                if ($appointment->payment_receipt) Storage::disk('public')->delete($appointment->payment_receipt);
                $updateData['payment_receipt'] = null;
            }

            if ($request->hasFile('payment_receipt') && $request->file('payment_receipt')->isValid()) {
                if ($appointment->payment_receipt) Storage::disk('public')->delete($appointment->payment_receipt);
                $updateData['payment_receipt'] = $request->file('payment_receipt')->store('receipts', 'public');
            }
        }

        $appointment->update($updateData);
        $appointment->services()->sync($request->service_ids ?? []);
        $appointment->packages()->sync($request->package_ids ?? []);

        ActivityLog::record('RESUBMITTED', 'Patient corrected schedule/tests and resubmitted', $appointment->patient_name, $appointment->id);

        $notifiables = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($notifiables as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Resubmitted Booking',
                'message' => "Patient: {$appointment->patient_name} has resubmitted their appointment.",
                'url' => route('appointments.index', ['view' => 'queue']),
                'type' => 'info'
            ]));
            event(new NotificationSent($staff->id, 'Resubmitted Booking', "Patient: {$appointment->patient_name} has resubmitted their appointment."));
        }

        event(new QueueUpdated());

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Appointment resubmitted for approval.',
                'appointment' => $appointment->fresh(['services', 'packages.services', 'user', 'dependent']),
            ], 200);
        }

        $redirectParams = $user->isEmployee() ? ['view' => 'patient'] : [];
        return redirect()->route('appointments.index', $redirectParams)->with('success', 'Appointment resubmitted for approval.');
    }

    /**
     * Update Appointment Status (Strictly individual - no batch bulk action).
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'status' => 'required|in:approved,returned,released',
            'return_reason' => 'required_if:status,returned'
        ]);

        $updatePayload = [
            'status' => $request->status,
            'return_reason' => ($request->status == 'returned') ? $request->return_reason : null,
            'cancellation_reason' => null
        ];

        if ($request->has('payment_status')) {
            $updatePayload['payment_status'] = $request->payment_status;
        }

        $appointment->update($updatePayload);

        $patient = $appointment->user;
        if ($patient) {
            $dateFormatted = date('M d, Y', strtotime($appointment->appointment_date));
            $timeFormatted = date('h:i A', strtotime($appointment->time_slot));

            if ($request->status === 'approved') {
                $appointment->user->notify(new AppointmentNotification([
                    'title' => 'Appointment Approved',
                    'message' => "Your laboratory appointment scheduled for {$dateFormatted} at {$timeFormatted} has been approved.",
                    'url' => route('appointments.index', ['view' => 'patient']),
                    'type' => 'success'
                ]));
                event(new NotificationSent($patient->id, 'Appointment Approved', "Your laboratory appointment scheduled for {$dateFormatted} at {$timeFormatted} has been approved."));
            } elseif ($request->status === 'returned') {
                $appointment->user->notify(new AppointmentNotification([
                    'title' => 'Appointment Returned',
                    'message' => "Your appointment scheduled for {$dateFormatted} at {$timeFormatted} requires corrections: \"{$request->return_reason}\"",
                    'url' => route('appointments.index', ['view' => 'patient']),
                    'type' => 'danger'
                ]));
                event(new NotificationSent($patient->id, 'Appointment Returned', "Your appointment scheduled for {$dateFormatted} at {$timeFormatted} requires corrections."));
            }
        }

        if ($request->status === 'released') {
            ResultController::deliverResult($appointment);
        }

        event(new QueueUpdated());

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Appointment updated to ' . strtoupper($request->status),
                'appointment' => $appointment->fresh(),
            ]);
        }

        if ($request->status === 'released') {
            return redirect()->route('appointments.index', ['view' => 'queue'])->with('success', 'Appointment folder has been successfully released.');
        }

        return back()->with('success', 'Appointment updated to ' . strtoupper($request->status));
    }

    /**
     * SUGGEST CHANGES (Return Option 2): Staff proposes adjustments to schedule or tests.
     * Enforces anti-double selection and package conflict validation.
     * Prevents defaulting to previously booked services when only a package is selected.
     */
    public function suggestChanges(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'reason' => 'required|string|min:5',
            'suggested_date' => 'nullable|date|after_or_equal:today',
            'suggested_time' => 'nullable|string',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
        ]);

        // STRICT CAPTURE: Do not fall back to old appointment services if unchecked!
        $serviceIds = $request->has('service_ids') ? array_map('intval', (array) $request->service_ids) : [];
        $packageIds = $request->has('package_ids') ? array_map('intval', (array) $request->package_ids) : [];

        // Unified Anti-Double-Selection & Conflict Check
        $conflictError = $this->validateServiceAndPackageConflicts($serviceIds, $packageIds);
        if ($conflictError) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json(['message' => $conflictError, 'errors' => ['service_ids' => [$conflictError]]], 422);
            }
            return back()->withErrors(['service_ids' => $conflictError])->withInput();
        }

        $suggestedPayload = [
            'appointment_date' => $request->suggested_date ?: ($appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : date('Y-m-d')),
            'time_slot' => $request->suggested_time ?: $appointment->time_slot,
            'service_ids' => $serviceIds,
            'package_ids' => $packageIds,
            'reason' => $request->reason,
            'suggested_by' => Auth::user()->name,
            'suggested_at' => now()->toDateTimeString(),
        ];

        // Suggest changes works like a return (patient must resubmit to go back to pending)
        $appointment->update([
            'status' => 'needs_review',
            'suggested_changes' => $suggestedPayload,
            'return_reason' => "Staff Proposed Change: {$request->reason}"
        ]);

        ActivityLog::record(
            'CHANGES SUGGESTED',
            "Staff proposed schedule/test adjustments: {$request->reason}",
            $appointment->patient_name,
            $appointment->id
        );

        $patient = $appointment->user;
        if ($patient) {
            $patient->notify(new AppointmentNotification([
                'title' => 'Appointment Modification Proposed',
                'message' => "The clinic staff proposed changes to your appointment: \"{$request->reason}\". Please review and confirm in your resubmission form.",
                'url' => route('appointments.index', ['view' => 'patient']),
                'type' => 'info'
            ]));
            event(new NotificationSent($patient->id, 'Appointment Modification Proposed', "The clinic staff proposed changes to your appointment."));
        }

        event(new QueueUpdated());

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Proposed changes submitted to patient for review.',
                'appointment' => $appointment->fresh(),
            ]);
        }

        return back()->with('success', 'Proposed adjustments dispatched to patient for review.');
    }

    /**
     * PATIENT AGREEMENT: Redirects to the resubmission form with staff suggestions applied.
     * Does NOT mark the appointment as approved immediately.
     */
    public function acceptSuggestedChanges(Appointment $appointment)
    {
        $user = Auth::user();
        $isOwner = ($appointment->user_id === $user->id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        if (!$isOwner) abort(403, 'Unauthorized action.');

        if (!$appointment->hasSuggestedChanges()) {
            return back()->with('error', 'No pending staff adjustments found for this appointment.');
        }

        // Entering resubmission with staff suggestions loaded
        return redirect()->route('appointments.resubmit', [
            'appointment' => $appointment->id,
            'use_suggestions' => 1
        ]);
    }

    /**
     * PATIENT DECLINE: Redirects to the resubmission form with original booking parameters.
     */
    public function rejectSuggestedChanges(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        $isOwner = ($appointment->user_id === $user->id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        if (!$isOwner) abort(403, 'Unauthorized action.');

        // Entering resubmission with original booking parameters loaded
        return redirect()->route('appointments.resubmit', [
            'appointment' => $appointment->id,
            'use_suggestions' => 0
        ]);
    }

    /**
     * Mark Appointment as Tested or flag for retest.
     */
    public function markTested(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isLabTech')) abort(403, 'Clinical personnel only.');

        $action = $request->input('action', 'tested');

        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') return;
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
                'patient_suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z\s.]+$/u'],
                'patient_birthdate' => 'required|date|before_or_equal:today',
                'patient_sex' => 'required|in:Male,Female',
                'patient_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
                'patient_street' => 'required|string|max:150',
                'patient_barangay' => 'required|string|max:100',
                'patient_city' => 'required|string|max:100',
                'patient_province' => 'required|string|max:100',
                'service_ids' => 'nullable|array',
                'service_ids.*' => 'exists:services,id',
                'package_ids' => 'nullable|array',
                'package_ids.*' => 'exists:packages,id',
                'payment_amount' => 'required|numeric|min:0',
                'retest_reason' => 'required|string',
                'retest_custom_reason' => 'required_if:retest_reason,Others|nullable|string|min:5',
            ], [
                'patient_phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
                'patient_suffix.regex' => 'The suffix may only contain letters, spaces, and periods.',
            ]);

            // Conflict Check
            $conflictError = $this->validateServiceAndPackageConflicts($request->service_ids, $request->package_ids);
            if ($conflictError) {
                if ($request->expectsJson() && ! $request->acceptsHtml()) {
                    return response()->json(['message' => $conflictError, 'errors' => ['service_ids' => [$conflictError]]], 422);
                }
                return back()->withErrors(['service_ids' => $conflictError])->withInput();
            }

            $retestReason = $request->input('retest_reason') === 'Others'
                ? $request->input('retest_custom_reason')
                : $request->input('retest_reason');

            $fName = strtoupper(trim($request->patient_first_name));
            $mName = ($request->patient_middle_name && strtoupper($request->patient_middle_name) !== 'N/A') ? strtoupper(trim($request->patient_middle_name)) : 'N/A';
            $lName = strtoupper(trim($request->patient_last_name));
            $suffix = $request->filled('patient_suffix') ? strtoupper($request->patient_suffix) : '';
            $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
            if (!empty($suffix)) $displayName .= " {$suffix}";

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
                'payment_status' => 'paid',
                'status' => 'retest',
                'return_reason' => $retestReason,
                'cancellation_reason' => null,
                'tested_at' => null,
                'result_estimated_at' => null
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'payment_amount')) {
                $updatePayload['payment_amount'] = $request->input('payment_amount');
            }

            $appointment->update($updatePayload);
            $appointment->services()->sync($request->service_ids ?? []);
            $appointment->packages()->sync($request->package_ids ?? []);

            ActivityLog::record('RETEST', 'Marked for retesting: ' . $retestReason, $appointment->patient_name, $appointment->id);

            $patient = $appointment->user;
            if ($patient) {
                $patient->notify(new AppointmentNotification([
                    'title' => 'Retesting Required',
                    'message' => "Your clinical sample requires a recollect due to: \"{$retestReason}\". Please return to the Medscreen Diagnostic Laboratory for retesting.",
                    'url' => route('appointments.index', ['view' => 'patient']),
                    'type' => 'danger'
                ]));
                event(new NotificationSent($patient->id, 'Retesting Required', "Your sample requires a recollect. Please return to the lab."));
            }

            event(new QueueUpdated());

            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment successfully flagged for retesting.',
                    'appointment' => $appointment->fresh(),
                ]);
            }

            return redirect()->back()->with('success', 'Appointment successfully flagged for retesting.');
        }

        $request->validate([
            'patient_first_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patient_last_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z\s.]+$/u'],
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_sex' => 'required|in:Male,Female',
            'patient_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'patient_street' => 'required|string|max:150',
            'patient_barangay' => 'required|string|max:100',
            'patient_city' => 'required|string|max:100',
            'patient_province' => 'required|string|max:100',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
            'payment_amount' => 'required|numeric|min:0',
            'est_hours' => 'nullable|integer|min:0',
            'est_minutes' => 'nullable|integer|min:0',
        ], [
            'patient_phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'patient_suffix.regex' => 'The suffix may only contain letters, spaces, and periods.',
        ]);

        // Conflict Check
        $conflictError = $this->validateServiceAndPackageConflicts($request->service_ids, $request->package_ids);
        if ($conflictError) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json(['message' => $conflictError, 'errors' => ['service_ids' => [$conflictError]]], 422);
            }
            return back()->withErrors(['service_ids' => $conflictError])->withInput();
        }

        $h = (int)$request->input('est_hours', 0);
        $m = (int)$request->input('est_minutes', 0);
        $est = ($h > 0 || $m > 0) ? now()->addHours($h)->addMinutes($m) : null;

        $fName = strtoupper(trim($request->patient_first_name));
        $mName = ($request->patient_middle_name && strtoupper($request->patient_middle_name) !== 'N/A') ? strtoupper(trim($request->patient_middle_name)) : 'N/A';
        $lName = strtoupper(trim($request->patient_last_name));
        $suffix = $request->filled('patient_suffix') ? strtoupper($request->patient_suffix) : '';
        $displayName = ($mName !== 'N/A') ? "{$fName} {$mName} {$lName}" : "{$fName} {$lName}";
        if (!empty($suffix)) $displayName .= " {$suffix}";

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
            'payment_status' => 'paid',
            'status' => 'tested',
            'tested_at' => now(),
            'result_estimated_at' => $est,
            'return_reason' => null,
            'cancellation_reason' => null
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'payment_amount')) {
            $updatePayload['payment_amount'] = $request->input('payment_amount');
        }

        $appointment->update($updatePayload);
        $appointment->services()->sync($request->service_ids ?? []);
        $appointment->packages()->sync($request->package_ids ?? []);

        ActivityLog::record('TESTED', 'Sampling completed and details verified', $appointment->patient_name, $appointment->id);

        $patient = $appointment->user;
        if ($patient) {
            $estTimeText = $est ? " (Estimated processing duration: " . ($h > 0 ? "{$h}h " : "") . ($m > 0 ? "{$m}m " : "") . ")" : "";
            $patient->notify(new AppointmentNotification([
                'title' => 'Sampling Completed',
                'message' => "Your clinical laboratory sampling is complete. Your results are currently being processed in our lab." . $estTimeText,
                'url' => route('appointments.index', ['view' => 'patient']),
                'type' => 'info'
            ]));
            event(new NotificationSent($patient->id, 'Sampling Completed', "Your clinical laboratory sampling is complete. Your results are currently being processed in our lab."));
        }

        event(new QueueUpdated());

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Sampling logged. Results are being processed.',
                'appointment' => $appointment->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Sampling logged. Results are being processed.');
    }

    /**
     * Dedicated Demographic and Diagnostic Examination Editor for Staff.
     */
    public function editDetails(Appointment $appointment)
    {
        $user = Auth::user();
        if (!$user->isEmployee()) {
            abort(403, 'Unauthorized access to edit patient details.');
        }

        if ($appointment->status === 'released') {
            return redirect()->back()->with('error', 'Released appointments are locked and cannot be modified.');
        }

        $services = Service::where('is_available', true)->orderBy('name')->get();
        $packages = Package::with('services')->where('is_available', true)->orderBy('name')->get();

        if (request()->expectsJson() && ! request()->acceptsHtml()) {
            return response()->json([
                'appointment' => $appointment->load(['services', 'packages.services', 'user', 'dependent']),
                'services' => $services,
                'packages' => $packages,
            ]);
        }

        return view('appointments.edit-details', compact('appointment', 'services', 'packages'));
    }

    /**
     * Save revised patient demographics, services, and billing from the Edit Details screen.
     */
    public function updateDetails(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        if (!$user->isEmployee()) {
            abort(403, 'Unauthorized access.');
        }

        if ($appointment->status === 'released') {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json(['message' => 'Released appointments cannot be updated.'], 403);
            }
            return redirect()->back()->with('error', 'Released appointments are locked and cannot be modified.');
        }

        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') return;
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
            'patient_first_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patient_last_name' => ['required', 'string', 'max:60', $nameRule],
            'patient_suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z\s.]+$/u'],
            'patient_sex' => 'required|in:Male,Female',
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'patient_street' => 'required|string|max:150',
            'patient_barangay' => 'required|string|max:100',
            'patient_city' => 'required|string|max:100',
            'patient_province' => 'required|string|max:100',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
            'payment_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|min:5',
        ], [
            'patient_phone.regex' => 'The phone number must start with 09 and contain exactly 11 digits.',
            'patient_suffix.regex' => 'The suffix may only contain letters, spaces, and periods.',
        ]);

        // Unified Anti-Double-Selection & Conflict Check
        $conflictError = $this->validateServiceAndPackageConflicts($request->service_ids, $request->package_ids);
        if ($conflictError) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json(['message' => $conflictError, 'errors' => ['service_ids' => [$conflictError]]], 422);
            }
            return back()->withErrors(['service_ids' => $conflictError])->withInput();
        }

        $mName = $request->patient_middle_name;
        $lName = $request->patient_last_name;
        $suffix = $request->filled('patient_suffix') ? strtoupper($request->patient_suffix) : '';

        $fullName = $request->patient_first_name . ($mName && strtoupper($mName) !== 'N/A' ? ' ' . $mName : '') . ' ' . $lName;
        if (!empty($suffix)) $fullName .= ' ' . $suffix;

        $updateData = [
            'patient_first_name' => strtoupper($request->patient_first_name),
            'patient_middle_name' => ($mName && strtoupper($mName) !== 'N/A') ? strtoupper($mName) : 'N/A',
            'patient_last_name' => strtoupper($lName),
            'patient_suffix' => $suffix ?: null,
            'patient_name' => strtoupper($fullName),
            'patient_sex' => $request->patient_sex,
            'patient_birthdate' => $request->patient_birthdate,
            'patient_phone' => $request->patient_phone,
            'patient_street' => strtoupper(trim($request->patient_street)),
            'patient_barangay' => strtoupper(trim($request->patient_barangay)),
            'patient_city' => strtoupper(trim($request->patient_city)),
            'patient_province' => strtoupper(trim($request->patient_province)),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'payment_amount')) {
            $updateData['payment_amount'] = $request->input('payment_amount');
        }

        $appointment->update($updateData);
        $appointment->services()->sync($request->service_ids ?? []);
        $appointment->packages()->sync($request->package_ids ?? []);

        ActivityLog::record('DETAILS REVISED', 'Patient demographics & services updated: ' . $request->input('reason'), $appointment->patient_name, $appointment->id);
        event(new QueueUpdated());

        $from = $request->input('from');
        $customId = $request->input('custom_id');

        if ($from === 'radio' || $from === 'radiology') {
            $redirectUrl = route('workstation.radiology', $appointment->id);
        } elseif ($from === 'lab') {
            $redirectUrl = route('workstation.lab', $appointment->id);
        } elseif ($from === 'med_cert' || $from === 'medical') {
            $redirectUrl = route('workstation.med_cert', $appointment->id);
        } elseif ($from === 'drug') {
            $redirectUrl = route('workstation.drug', $appointment->id);
        } elseif ($from === 'custom' && $customId) {
            $redirectUrl = route('workstation.custom', [$appointment->id, $customId]);
        } elseif ($from === 'hub') {
            $redirectUrl = route('appointments.encode', $appointment->id);
        } else {
            $redirectUrl = route('appointments.index', ['view' => 'queue', 'id' => $appointment->id]);
        }

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Patient demographics and tests updated successfully.',
                'appointment' => $appointment->fresh(['services', 'packages.services', 'user', 'dependent']),
                'redirect' => $redirectUrl,
            ], 200);
        }

        return redirect($redirectUrl)->with('success', 'Patient demographics and tests updated successfully.');
    }

    /**
     * Cancel an appointment (Strictly individual - no batch bulk cancellation).
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        $isOwner = ($appointment->user_id === $user->id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        if ($user->isEmployee() && ! $isOwner) {
            abort(403, 'Employees/Staff are not authorized to cancel other patients\' appointments.');
        }

        if (! $isOwner) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|min:5|max:1000',
            'reason' => 'nullable|string|min:5|max:1000',
            'custom_cancellation_reason' => 'nullable|string|min:5|max:1000',
        ]);

        $reason = $request->input('cancellation_reason')
            ?: $request->input('reason')
            ?: $request->input('custom_cancellation_reason')
            ?: 'Canceled by patient';

        $paymentStatus = $appointment->payment_status === 'paid' ? 'paid' : 'unpaid';

        if ($appointment->status === 'approved') {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Approved appointments cannot be canceled. Please contact clinic staff for assistance.'
                ], 422);
            }
            return back()->with('error', 'Approved appointments cannot be canceled. Please contact clinic staff for assistance.');
        }

        if (!in_array($appointment->status, ['pending', 'returned', 'needs_review'])) {
            if ($request->expectsJson() && ! $request->acceptsHtml()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending appointments can be canceled.'
                ], 422);
            }
            return back()->with('error', 'Only pending appointments can be canceled.');
        }

        $appointment->update([
            'status' => 'canceled',
            'payment_status' => $paymentStatus,
            'cancellation_reason' => $reason,
            'return_reason' => $reason
        ]);

        ActivityLog::record('CANCELED', "Appointment canceled. Reason: {$reason}", $appointment->patient_name, $appointment->id);
        event(new QueueUpdated());

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Appointment successfully canceled.'
            ]);
        }

        return back()->with('success', 'Appointment successfully canceled.');
    }

    /**
     * Flag cashless proof of payment as invalid.
     */
    public function markPaymentInvalid(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

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

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment flagged as invalid.'
            ]);
        }

        return back()->with('success', 'Payment flagged as invalid.');
    }

    /**
     * Confirm a manual refund transaction.
     */
    public function confirmRefund(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

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

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Refund confirmed successfully.'
            ]);
        }

        return back()->with('success', 'Refund confirmed successfully.');
    }

    /**
     * Patient soft-deletion of expired/voided unprogressed appointments.
     */
    public function softDelete(Appointment $appointment)
    {
        $user = Auth::user();
        $isOwner = ($appointment->user_id === $user->id)
            || ($appointment->patient_email && strtolower($user->email) === strtolower($appointment->patient_email))
            || ($appointment->dependent_id && $user->dependents()->where('id', $appointment->dependent_id)->exists());

        if (!$isOwner) abort(403);

        if (!$appointment->canBeDeletedByPatient()) {
            if (request()->expectsJson() && ! request()->acceptsHtml()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paid or active appointments cannot be deleted.'
                ], 422);
            }
            return back()->with('error', 'Paid or active appointments cannot be deleted.');
        }

        $appointment->update(['deleted_by_patient' => true]);
        ActivityLog::record('SOFT DELETED', 'Patient soft-deleted voided appointment', $appointment->patient_name, $appointment->id);
        event(new QueueUpdated());

        if (request()->expectsJson() && ! request()->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'Appointment removed from your dashboard.'
            ]);
        }

        return redirect()->back()->with('success', 'Appointment removed from your dashboard.');
    }

    /**
     * Confirm or revoke payment status. Retains batch payment for corporate bulk appointments.
     * Enforces that revoking payment strictly requires an audit justification reason.
     */
    public function confirmPayment(Request $request, Appointment $appointment)
    {
        if (Gate::denies('isStaff')) abort(403);

        $request->validate([
            'payment_status' => 'required|in:unpaid,paid',
            'reason' => 'required_if:payment_status,unpaid|nullable|string|min:5',
            'custom_reason' => 'required_if:reason,Others|nullable|string|min:5',
        ]);

        $statusLabel = strtoupper($request->payment_status);
        $reasonText = null;

        if ($request->payment_status === 'unpaid') {
            $reasonText = $request->input('reason') === 'Others' 
                ? $request->input('custom_reason') 
                : $request->input('reason');
        }

        $updatePayload = [
            'payment_status' => $request->payment_status,
        ];

        if ($request->payment_status === 'unpaid') {
            $updatePayload['return_reason'] = 'Payment Revoked: ' . ($reasonText ?: 'Revoked by clinical staff');
        } else {
            $updatePayload['return_reason'] = null;
        }

        if ($appointment->batch_id) {
            Appointment::where('batch_id', $appointment->batch_id)->update($updatePayload);
            $actionTitle = ($request->payment_status === 'paid') ? 'BATCH PAYMENT CONFIRMED' : 'BATCH PAYMENT REVOKED';
            $logMsg = ($request->payment_status === 'paid')
                ? "Staff confirmed batch cashless payment as PAID for {$appointment->organization_name} (Batch #{$appointment->batch_id})"
                : "Staff revoked batch cashless payment for {$appointment->organization_name} (Batch #{$appointment->batch_id}). Reason: {$reasonText}";

            ActivityLog::record($actionTitle, $logMsg, $appointment->patient_name, $appointment->id);
        } else {
            $appointment->update($updatePayload);
            $actionTitle = ($request->payment_status === 'paid') ? 'PAYMENT CONFIRMED' : 'PAYMENT REVOKED';
            $logMsg = ($request->payment_status === 'paid')
                ? "Staff confirmed appointment payment as PAID"
                : "Staff revoked appointment payment. Reason: {$reasonText}";

            ActivityLog::record($actionTitle, $logMsg, $appointment->patient_name, $appointment->id);
        }

        event(new QueueUpdated());

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => "Payment status updated to {$statusLabel}."
            ]);
        }

        return redirect()->back()->with('success', "Payment status updated to {$statusLabel}.");
    }

    /**
     * Resubmit a new cashless proof of payment receipt for an entire bulk batch.
     * Accessible by the creator/submitter of the bulk appointment.
     */
    public function resubmitBatchReceipt(Request $request, $batchId)
    {
        $user = Auth::user();
        $firstApp = Appointment::where('batch_id', $batchId)
            ->where('user_id', $user->id)
            ->first();

        if (!$firstApp) {
            abort(403, 'Unauthorized: You do not have permission to manage this bulk appointment batch.');
        }

        $request->validate([
            'payment_receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($firstApp->payment_receipt && Storage::disk('public')->exists($firstApp->payment_receipt)) {
            Storage::disk('public')->delete($firstApp->payment_receipt);
        }

        $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');

        Appointment::where('batch_id', $batchId)->update([
            'payment_receipt' => $receiptPath,
            'payment_status' => 'unpaid',
            'return_reason' => null,
        ]);

        ActivityLog::record(
            'BATCH RECEIPT RESUBMITTED',
            "New cashless payment receipt uploaded for Batch #{$batchId} ({$firstApp->organization_name})",
            $firstApp->patient_name,
            $firstApp->id
        );

        $notifiables = User::whereIn('role', ['staff', 'lab_tech', 'admin'])->get();
        foreach ($notifiables as $staff) {
            $staff->notify(new AppointmentNotification([
                'title' => 'Batch Receipt Resubmitted',
                'message' => "New payment receipt uploaded for Batch #{$batchId} ({$firstApp->organization_name}).",
                'url' => route('appointments.index', ['view' => 'queue']),
                'type' => 'info'
            ]));
            event(new NotificationSent($staff->id, 'Batch Receipt Resubmitted', "New payment receipt uploaded for Batch #{$batchId}."));
        }

        event(new QueueUpdated());

        if ($request->expectsJson() && ! $request->acceptsHtml()) {
            return response()->json([
                'success' => true,
                'message' => 'New payment receipt submitted successfully for Batch #' . $batchId,
            ]);
        }

        return redirect()->back()->with('success', "New payment receipt submitted successfully for Batch #{$batchId}.");
    }

    /**
     * Unified Validation Helper: Validates that there are no overlapping services or conflicting packages.
     *
     * @param array|null $serviceIds
     * @param array|null $packageIds
     * @return string|null Error message if conflict found, null otherwise.
     */
    private function validateServiceAndPackageConflicts(?array $serviceIds, ?array $packageIds): ?string
    {
        $serviceIds = $serviceIds ? array_filter($serviceIds) : [];
        $packageIds = $packageIds ? array_filter($packageIds) : [];

        if (empty($serviceIds) && empty($packageIds)) {
            return 'Please select at least one individual test or health package.';
        }

        $selectedPackages = !empty($packageIds)
            ? Package::with('services')->whereIn('id', $packageIds)->get()
            : collect();

        // 1. Conflict: Standalone service covered by a selected package
        if (!empty($serviceIds) && $selectedPackages->isNotEmpty()) {
            $overlapping = [];
            foreach ($selectedPackages as $pkg) {
                foreach ($pkg->services as $svc) {
                    if (in_array($svc->id, $serviceIds)) {
                        $overlapping[] = "{$svc->name} (included in {$pkg->name})";
                    }
                }
            }
            if (!empty($overlapping)) {
                return "Double selection conflict detected: " . implode(', ', $overlapping) . ". Individual tests already covered by selected packages cannot be selected concurrently.";
            }
        }

        // 2. Conflict: Two or more selected packages sharing identical constituent services
        if ($selectedPackages->count() > 1) {
            $seen = [];
            $conflicts = [];
            foreach ($selectedPackages as $pkg) {
                foreach ($pkg->services as $svc) {
                    if (isset($seen[$svc->id])) {
                        $conflicts[] = "Package '{$pkg->name}' conflicts with '{$seen[$svc->id]}' (both share '{$svc->name}')";
                    } else {
                        $seen[$svc->id] = $pkg->name;
                    }
                }
            }
            if (!empty($conflicts)) {
                return "Conflicting packages detected: " . implode('; ', $conflicts) . ". Packages sharing identical individual services cannot be booked together.";
            }
        }

        return null;
    }
}