<?php

namespace App\Http\Controllers;

use App\Models\{Appointment, Service, Dependent, AppointmentConfig, ActivityLog, User, PaymentProvider};
use App\Notifications\AppointmentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Gate, DB, Str, Storage};
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * View Appointments: Categorized for Patients, Master Queue for Staff.
     */
    public function index()
    {
        $user = Auth::user();
        
        // 1. SELF-CLEANING SWEEP: Automatically hard purge any unpaid, soft-deleted, or expired appointments older than 30 days
        $purgeCutoff = Carbon::now()->subDays(30);
        Appointment::where('payment_status', 'unpaid')
            ->where(function($q) use ($purgeCutoff) {
                $q->where('deleted_by_patient', true)
                    ->orWhere(function($sub) use ($purgeCutoff) {
                        $sub->where('appointment_date', '<', $purgeCutoff)
                            ->whereNotIn('status', ['tested', 'encoded', 'released']);
                    });
            })->delete();

        // 2. Fetch Active Queue (Filtering out soft-deleted files for patients)
        $query = Appointment::with(['services', 'user', 'dependent', 'result'])->latest();
        if ($user->isPatient()) {
            $query->where('deleted_by_patient', false);
        }

        $services = Service::where('is_available', true)->orderBy('name')->get();
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        if ($user->isPatient()) {
            $all = $query->where('user_id', $user->id)->get();
            return view('appointments.index', [
                'self' => $all->filter(fn($a) => is_null($a->dependent_id) && is_null($a->batch_id)),
                'dependents' => $all->filter(fn($a) => !is_null($a->dependent_id)),
                'bulkGroups' => $all->filter(fn($a) => !is_null($a->batch_id))->groupBy('batch_id'),
                'is_staff' => false,
                'services' => $services,
                'paymentProviders' => $paymentProviders
            ]);
        }

        $staffQueue = $query->orderBy('appointment_date', 'asc')
            ->orderBy('time_slot', 'asc')
            ->get()
            ->groupBy(fn($item) => $item->batch_id ?? 'single_' . $item->id);

        return view('appointments.index', [
            'staffQueue' => $staffQueue, 
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
        $request->validate([
            'target_type' => 'required|in:self,dependent,bulk',
            'dependent_id' => 'required_if:target_type,dependent|nullable|exists:dependents,id',
            'organization_name' => 'required_if:target_type,bulk|nullable|string|max:255',
            'patient_first_name' => 'required|string|max:255',
            'patient_middle_name' => 'nullable|string|max:255',
            'patient_last_name' => 'required|string|max:255',
            'patient_sex' => 'required|in:Male,Female',
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_phone' => 'required|string',
            'patient_street' => 'required|string|max:255',
            'patient_barangay' => 'required|string|max:255',
            'patient_city' => 'required|string|max:255',
            'patient_province' => 'required|string|max:255',
            'referral_note' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'service_ids' => 'required|array|min:1',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
            'payment_method' => 'required|string',
            'payment_receipt' => 'required_if:payment_method,Cashless|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
        ]);

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();

        $bookedCount = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])->count();

        if ($bookedCount >= ($config->max_patients_per_slot ?? 1)) {
            return back()->withErrors(['time_slot' => 'This slot is no longer available. Please select another time.'])->withInput();
        }

        $mName = $request->patient_middle_name;
        $fullName = $request->patient_first_name . ($mName && strtoupper($mName) !== 'N/A' ? ' ' . $mName : '') . ' ' . $request->patient_last_name;

        $data = [
            'user_id' => Auth::id(),
            'dependent_id' => ($request->target_type === 'dependent') ? $request->dependent_id : null,
            'organization_name' => ($request->target_type === 'bulk') ? strtoupper($request->organization_name) : null,
            'batch_id' => ($request->target_type === 'bulk') ? Str::random(10) : null,
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'patient_first_name' => strtoupper($request->patient_first_name),
            'patient_middle_name' => $request->patient_middle_name ? strtoupper($request->patient_middle_name) : 'N/A',
            'patient_last_name' => strtoupper($request->patient_last_name),
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
            }

            DB::commit();
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
     * Resubmit: Patients correcting a "Returned" record.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $isBulk = !is_null($appointment->batch_id);

        $rules = [
            'patient_first_name' => 'required|string|max:255',
            'patient_middle_name' => 'nullable|string|max:255',
            'patient_last_name' => 'required|string|max:255',
            'patient_sex' => 'required|in:Male,Female',
            'patient_birthdate' => 'required|date|before_or_equal:today',
            'patient_phone' => 'required|string',
            'patient_street' => 'required|string|max:255',
            'service_ids' => 'required|array|min:1',
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
        ];

        if (!$isBulk) {
            $rules['patient_barangay'] = 'required|string|max:255';
            $rules['patient_city'] = 'required|string|max:255';
            $rules['patient_province'] = 'required|string|max:255';
            $rules['payment_method'] = 'required|string';
            $rules['referral_note'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240';
            $rules['payment_receipt'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240';
        }

        $request->validate($rules);

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();

        $booked = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])
            ->where('id', '!=', $appointment->id)->count();

        if ($booked >= ($config->max_patients_per_slot ?? 1)) {
            return back()->withErrors(['time_slot' => 'Slot is full.']);
        }

        $mName = $request->patient_middle_name;
        $fullName = $request->patient_first_name . ($mName && strtoupper($mName) !== 'N/A' ? ' ' . $mName : '') . ' ' . $request->patient_last_name;

        $street = strtoupper(trim($request->patient_street));
        $barangay = $isBulk ? 'N/A' : strtoupper(trim($request->patient_barangay));
        $city = $isBulk ? 'N/A' : strtoupper(trim($request->patient_city));
        $province = $isBulk ? 'N/A' : strtoupper(trim($request->patient_province));

        $updateData = [
            'patient_first_name' => strtoupper($request->patient_first_name),
            'patient_middle_name' => $mName ? strtoupper($mName) : 'N/A',
            'patient_last_name' => strtoupper($request->patient_last_name),
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
            'return_reason' => null
        ];

        if (!$isBulk) {
            $updateData['payment_method'] = $request->payment_method;
        }

        if (!$isBulk && $request->hasFile('referral_note') && $request->file('referral_note')->isValid()) {
            if ($appointment->referral_note) {
                Storage::disk('public')->delete($appointment->referral_note);
            }
            $updateData['referral_note'] = $request->file('referral_note')->store('referrals', 'public');
        }

        if (!$isBulk && $request->hasFile('payment_receipt') && $request->file('payment_receipt')->isValid()) {
            if ($appointment->payment_receipt) {
                Storage::disk('public')->delete($appointment->payment_receipt);
            }
            $updateData['payment_receipt'] = $request->file('payment_receipt')->store('receipts', 'public');
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
        }

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

        // FIXED: Handles cascade updates for all records matching this bulk batch
        if ($appointment->batch_id) {
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
                    } elseif ($request->status === 'returned') {
                        $patient->notify(new AppointmentNotification([
                            'title' => 'Appointment Returned',
                            'message' => "Your appointment scheduled for {$dateFormatted} requires corrections: \"{$request->return_reason}\"",
                            'url' => route('appointments.index'),
                            'type' => 'danger'
                        ]));
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
                } elseif ($request->status === 'returned') {
                    $appointment->user->notify(new AppointmentNotification([
                        'title' => 'Appointment Returned',
                        'message' => "Your appointment scheduled for {$dateFormatted} requires corrections: \"{$request->return_reason}\"",
                        'url' => route('appointments.index'),
                        'type' => 'danger'
                    ]));
                }
            }
        }

        // FIXED: Redirects directly to the Master Queue on official folder releases to prevent Reason-Gate lockouts
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

        $h = (int) $request->input('est_hours', 0);
        $m = (int) $request->input('est_minutes', 0);
        $est = ($h > 0 || $m > 0) ? now()->addHours($h)->addMinutes($m) : null;

        // Progressing to Tested automatically flags Cash appointments as PAID
        $paymentStatus = ($appointment->payment_method === 'Cash') ? 'paid' : $appointment->payment_status;

        $appointment->update([
            'status' => 'tested', 
            'tested_at' => now(), 
            'result_estimated_at' => $est,
            'payment_status' => $paymentStatus
        ]);

        ActivityLog::record('TESTED', 'Sampling completed', $appointment->patient_name, $appointment->id);

        $patient = $appointment->user;
        if ($patient) {
            $estTimeText = $est ? " (Estimated processing duration: " . ($h > 0 ? "{$h}h " : "") . ($m > 0 ? "{$m}m " : "") . ")" : "";
            $patient->notify(new AppointmentNotification([
                'title' => 'Sampling Completed',
                'message' => "Your clinical laboratory sampling is complete. Your results are currently being processed in our lab." . $estTimeText,
                'url' => route('appointments.index'),
                'type' => 'info'
            ]));
        }

        return back()->with('success', 'Sampling logged. Results are being processed.');
    }

    /**
     * Patient Soft Delete: Hide expired unpaid appointments from patient dashboard
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

        return back()->with('success', 'Appointment removed from your dashboard.');
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

        // FIXED: Bulk batch rule updating payment status cascades across the entire batch
        if ($appointment->batch_id) {
            Appointment::where('batch_id', $appointment->batch_id)->update(['payment_status' => $request->payment_status]);
        } else {
            $appointment->update(['payment_status' => $request->payment_status]);
        }

        $statusLabel = strtoupper($request->payment_status);
        ActivityLog::record('PAYMENT UPDATE', "Staff flagged appointment payment as {$statusLabel}", $appointment->patient_name, $appointment->id);

        return back()->with('success', "Payment status updated to {$statusLabel}.");
    }
}