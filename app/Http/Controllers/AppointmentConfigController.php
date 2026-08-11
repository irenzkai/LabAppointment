<?php

namespace App\Http\Controllers;

use App\Models\{Appointment, AppointmentConfig, Dependent};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{Auth, DB, Log};

class AppointmentConfigController extends Controller
{
    /**
     * Admin/Staff View: Settings and Occupancy Visualizer
     */
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', date('Y-m-d'));

        // 1. Get Effective Configuration (Specific Date Override takes priority, fall back to recurring weekly rule)
        $config = AppointmentConfig::where('specific_date', $selectedDate)->first()
            ?? AppointmentConfig::where('day_of_week', date('w', strtotime($selectedDate)))->first();

        // 2. Get all 7 weekly configs for the settings tabs
        $weeklyConfigs = AppointmentConfig::whereNotNull('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        // 3. Generate Slot Occupancy Grid
        $slots = [];
        if ($config && $config->is_open) {
            $current = strtotime($config->opening_time);
            $end = strtotime($config->closing_time);

            while ($current < $end) {
                $time = date('H:i:00', $current);
                $isLunch = ($config->has_lunch_break && $time >= $config->lunch_start && $time < $config->lunch_end);

                if (!$isLunch) {
                    // Fetch real appointments for this specific slot to show names/status in popovers
                    $appointments = Appointment::where('appointment_date', $selectedDate)
                        ->where('time_slot', $time)
                        ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released'])
                        ->with('user') // Eager load for popover performance
                        ->get();

                    $slots[] = [
                        'time' => $time,
                        'booked_count' => $appointments->count(),
                        'capacity' => $config->max_patients_per_slot,
                        'patients' => $appointments, // Used for the interactive popovers
                    ];
                }
                $current = strtotime("+{$config->slot_duration} minutes", $current);
            }
        }

        return view('admin.appointment-settings', compact('config', 'weeklyConfigs', 'slots', 'selectedDate'));
    }

    /**
     * Store or Update Configurations
     * Handles: Specific Date Overrides, Weekly Recurring, or Apply to All.
     */
    public function store(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:day,date,all',
            'opening_time' => 'required',
            'closing_time' => 'required',
            'slot_duration' => 'required|integer|min:5',
            'max_patients_per_slot' => 'required|integer|min:1',
            'lead_time_hours' => 'required|integer|min:0',
            'day_of_week' => 'nullable|integer|between:0,6',
            'specific_date' => 'nullable|date'
        ]);

        $data = $request->only([
            'opening_time', 'closing_time', 'slot_duration',
            'lunch_start', 'lunch_end', 'max_patients_per_slot', 'lead_time_hours'
        ]);

        // Explicit cast to map directly to our boolean schema columns [0008]
        $data['is_open'] = $request->has('is_open');
        $data['has_lunch_break'] = $request->has('has_lunch_break');

        if ($request->mode === 'all') {
            // Update all 7 recurring day rules
            AppointmentConfig::whereNotNull('day_of_week')->update($data);
            $msg = "Global rules updated for all standard operating days.";
        } elseif ($request->mode === 'date') {
            // Create or Update a one-off override (e.g., Holiday or special schedule)
            AppointmentConfig::updateOrCreate(['specific_date' => $request->specific_date], $data);
            $msg = "Schedule override set for " . date('M d, Y', strtotime($request->specific_date));
        } else {
            // Update a standard recurring day (e.g., Every Monday)
            AppointmentConfig::updateOrCreate(['day_of_week' => $request->day_of_week], $data);
            $msg = "Recurring rules updated.";
        }

        return back()->with('success', $msg);
    }

    /**
     * API: Check Occupancy for the Booking Wizard with Exclude ID overrides
     * Handles: Lead Time check, Capacity, and Lunch breaks.
     */
    public function checkOccupancy(Request $request): JsonResponse
    {
        $date = $request->query('date');
        $depId = $request->query('dependent_id');
        $excludeId = $request->query('exclude_id'); // Optional ID of currently active resubmission record

        if (!$date) {
            return response()->json(['error' => 'Date required'], 400);
        }

        try {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }

            // 1. Determine Patient Gender for validation
            $gender = 'both';
            if ($depId) {
                $patient = Dependent::find($depId);
                $gender = $patient ? $patient->sex : 'both';
            } elseif (Auth::check()) {
                $gender = Auth::user()->sex;
            }

            // 2. Fetch Effective Config for the requested date
            $config = AppointmentConfig::where('specific_date', $date)->first()
                ?? AppointmentConfig::where('day_of_week', (int) date('w', $timestamp))->first();

            $maxPatients = $config?->max_patients_per_slot ?? 1;

            // 3. Identify Full Slots via DB (Excluding active resubmitting appointment)
            $fullQuery = Appointment::where('appointment_date', $date)
                ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released']);

            if ($excludeId) {
                $fullQuery->where('id', '!=', $excludeId);
            }

            // FIXED: Fetched records with get() first to keep the selected 'patient_count' column intact before plucking
            $fullSlots = $fullQuery->select('time_slot', DB::raw('count(*) as patient_count'))
                ->groupBy('time_slot')
                ->having('patient_count', '>=', $maxPatients)
                ->get()
                ->pluck('time_slot')
                ->toArray();

            // 4. Fetch all occupied slots and their exact counts
            $occupiedQuery = Appointment::where('appointment_date', $date)
                ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released']);

            if ($excludeId) {
                $occupiedQuery->where('id', '!=', $excludeId);
            }

            $occupiedSlots = $occupiedQuery->select('time_slot', DB::raw('count(*) as patient_count'))
                ->groupBy('time_slot')
                ->get()
                ->pluck('patient_count', 'time_slot')
                ->toArray();

            return response()->json([
                'patient_gender' => strtolower($gender),
                'is_closed' => !($config?->is_open ?? false),
                'config' => $config,
                'full_slots' => $fullSlots,
                'occupied_slots' => $occupiedSlots,
                'server_time' => date('H:i:s'),
                'server_date' => date('Y-m-d')
            ]);

        } catch (\Exception $e) {
            Log::error("Occupancy Check Error: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}