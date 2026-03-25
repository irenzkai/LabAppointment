<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Dependent;
use App\Models\AppointmentConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Appointment::with(['services', 'user', 'dependent'])->latest();

        if ($user->role === 'user') {
            $all = $query->where('user_id', $user->id)->get();
            return view('appointments.index', [
                'self' => $all->filter(fn($a) => is_null($a->dependent_id) && is_null($a->batch_id)),
                'dependents' => $all->filter(fn($a) => !is_null($a->dependent_id)),
                'bulkGroups' => $all->filter(fn($a) => !is_null($a->batch_id))->groupBy('batch_id'),
                'is_staff' => false
            ]);
        }

        // Staff Queue: Grouped by Batch for organization
        $staffQueue = Appointment::with(['services', 'user', 'dependent'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('time_slot', 'asc')
            ->get()
            ->groupBy(function($item) {
                return $item->batch_id ?? 'single_' . $item->id;
            });

        return view('appointments.index', ['staffQueue' => $staffQueue, 'is_staff' => true]);
    }

    public function store(Request $request) {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
            'service_ids' => 'required|array',
            'dependent_id' => 'nullable|exists:dependents,id',
        ]);

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();
        
        $bookedCount = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($bookedCount >= ($config->max_patients_per_slot ?? 1)) {
            return back()->withErrors(['time_slot' => 'This slot is now full.']);
        }

        $user = auth()->user();
        if ($request->filled('dependent_id')) {
            $patient = Dependent::findOrFail($request->dependent_id);
            $patientData = [
                'patient_name' => $patient->name,
                'patient_birthdate' => $patient->birthdate,
                'patient_sex' => $patient->sex,
                'patient_address' => $patient->address,
                'patient_email' => null,
                'patient_phone' => $patient->phone,
            ];
        } else {
            $patientData = [
                'patient_name' => $user->name,
                'patient_birthdate' => $user->birthdate,
                'patient_sex' => $user->sex,
                'patient_address' => $user->address,
                'patient_email' => $user->email,
                'patient_phone' => $user->phone,
            ];
        }

        try {
            $appointment = Appointment::create(array_merge($patientData, [
                'user_id' => $user->id,
                'dependent_id' => $request->dependent_id,
                'appointment_date' => $request->appointment_date,
                'time_slot' => $request->time_slot,
                'status' => 'pending'
            ]));

            $appointment->services()->attach($request->service_ids);
            session()->forget('cart');
            return redirect()->route('appointments.index')->with('success', 'Appointment submitted!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:approved,returned',
            'return_reason' => 'required_if:status,returned|string|nullable'
        ]);

        $appointment->update([
            'status' => $request->status,
            'return_reason' => ($request->status == 'returned') ? $request->return_reason : null,
        ]);

        return back()->with('success', 'Appointment successfully ' . $request->status);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'required',
            // Identity validation (Only required if it's a bulk appointment)
            'patient_name' => 'nullable|string|max:255',
            'patient_email' => 'nullable|email',
            'patient_phone' => 'nullable|string',
            'patient_address' => 'nullable|string',
        ]);

        // 1. RULE CHECK: Clinic hours and occupancy
        $dayNum = date('w', strtotime($request->appointment_date));
        $config = \App\Models\AppointmentConfig::where('day_of_week', $dayNum)->first();

        if (!$config || !$config->is_open) {
            return back()->withErrors(['error' => 'The clinic is closed on the selected date.']);
        }

        // 2. OCCUPANCY CHECK: Is the slot full? (Excluding this specific appointment)
        $bookedCount = Appointment::where('appointment_date', $request->appointment_date)
            ->where('time_slot', $request->time_slot)
            ->where('id', '!=', $appointment->id) // Don't block the user from their own slot
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($bookedCount >= $config->max_patients_per_slot) {
            return back()->withErrors(['error' => 'This slot is no longer available. Please select another time.']);
        }

        // 3. UPDATE LOGIC
        $data = [
            'appointment_date' => $request->appointment_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending',
            'return_reason' => null
        ];

        // If it's a bulk appointment (has batch_id), update identity too
        if ($appointment->batch_id) {
            $data['patient_name'] = $request->patient_name;
            $data['patient_email'] = $request->patient_email;
            $data['patient_phone'] = $request->patient_phone;
            $data['patient_sex'] = $request->patient_sex;
            $data['patient_birthdate'] = $request->patient_birthdate;
            $data['patient_address'] = $request->patient_address;
        }

        $appointment->update($data);

        return back()->with('success', 'Appointment resubmitted successfully.');
    }
}