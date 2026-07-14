<?php

namespace App\Http\Controllers;

use App\Models\{Appointment, AppointmentConfig, Dependent};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};

class AppointmentConfigController extends Controller
{
    /**
     * Admin/Staff View: Settings and Occupancy Visualizer
     */
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', date('Y-m-d'));
        
        // 1. Get Effective Configuration (Override takes priority)
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
                    // Fetch real appointments for this specific slot to show names/status
                    $appointments = Appointment::where('appointment_date', $selectedDate)
                        ->where('time_slot', $time)
                        ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released'])
                        ->with('user') // Eager load for popover
                        ->get();

                    $slots[] = [
                        'time' => $time,
                        'booked_count' => $appointments->count(),
                        'capacity' => $config->max_patients_per_slot,
                        'patients' => $appointments, // Used for the interactable popovers
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
        
        $data['is_open'] = $request->has('is_open');
        $data['has_lunch_break'] = $request->has('has_lunch_break');

        if ($request->mode === 'all') {
            // Update all 7 recurring day rules
            AppointmentConfig::whereNotNull('day_of_week')->update($data);
            $msg = "Global rules updated for all standard operating days.";
        } elseif ($request->mode === 'date') {
            // Create or Update a one-off override (e.g. Holiday)
            AppointmentConfig::updateOrCreate(['specific_date' => $request->specific_date], $data);
            $msg = "Schedule override set for " . date('M d, Y', strtotime($request->specific_date));
        } else {
            // Update a standard recurring day (e.g. Every Monday)
            AppointmentConfig::updateOrCreate(['day_of_week' => $request->day_of_week], $data);
            $msg = "Recurring rules updated.";
        }

        return back()->with('success', $msg);
    }

    /**
     * API: Check Occupancy for the Booking Wizard
     * Handles: Lead Time check, Capacity, and Lunch breaks.
     */
    public function checkOccupancy(Request $request)
    {
        $date = $request->query('date');
        $depId = $request->query('dependent_id');
        
        if (!$date) return response()->json(['error' => 'Date required'], 400);

        try {
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
                ?? AppointmentConfig::where('day_of_week', date('w', strtotime($date)))->first();

            // 3. Identify Full Slots via DB
            $fullSlots = Appointment::where('appointment_date', $date)
                ->whereIn('status', ['pending', 'approved'])
                ->select('time_slot', DB::raw('count(*) as patient_count'))
                ->groupBy('time_slot')
                ->having('patient_count', '>=', $config->max_patients_per_slot ?? 1)
                ->pluck('time_slot')
                ->toArray();

            // 4. Lead Time Check: Generate list of "too late" slots if booking for today
            $tooLateSlots = [];
            if ($date === date('Y-m-d') && $config) {
                $leadTimeMs = ($config->lead_time_hours ?? 0) * 3600;
                $cutoffTime = time() + $leadTimeMs;
                
                // We'll calculate specific slots locally or pass the lead time back
            }

            return response()->json([
                'patient_gender' => strtolower($gender),
                'is_closed' => !($config->is_open ?? false),
                'config' => $config,
                'full_slots' => $fullSlots,
                'server_time' => date('H:i:s'),
                'server_date' => date('Y-m-d')
            ]);

        } catch (\Exception $e) {
            Log::error("Occupancy Check Error: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}