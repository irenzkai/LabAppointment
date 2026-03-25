<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentConfig;
use App\Models\Dependent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentConfigController extends Controller
{
    // Admin/Staff View: Settings and Occupancy Visualizer
    public function index(Request $request) {
        $selectedDate = $request->get('date', date('Y-m-d'));
        $dayOfWeek = date('w', strtotime($selectedDate)); // 0-6
        
        // Get all 7 configs for the settings tabs
        $allConfigs = AppointmentConfig::orderBy('day_of_week')->get();
        // Get specific config for the visualizer
        $config = $allConfigs->where('day_of_week', $dayOfWeek)->first();

        $slots = [];
        if($config->is_open) {
            $current = strtotime($config->opening_time);
            $end = strtotime($config->closing_time);
            while ($current < $end) {
                $time = date('H:i:00', $current);
                $isLunch = ($config->has_lunch_break && $time >= $config->lunch_start && $time < $config->lunch_end);
                
                if (!$isLunch) {
                    $bookedCount = Appointment::where('appointment_date', $selectedDate)
                        ->where('time_slot', $time)
                        ->whereIn('status', ['pending', 'approved'])->count();

                    $slots[] = [
                        'time' => $time, 'booked' => $bookedCount,
                        'capacity' => $config->max_patients_per_slot,
                        'is_full' => $bookedCount >= $config->max_patients_per_slot
                    ];
                }
                $current = strtotime("+$config->slot_duration minutes", $current);
            }
        }

        return view('admin.appointment-settings', compact('allConfigs', 'config', 'slots', 'selectedDate'));
    }

    // Update Global Settings
    public function update(Request $request, $id) {
        $config = AppointmentConfig::findOrFail($id);
        $data = $request->except(['_token', '_method']);
        $data['is_open'] = $request->has('is_open');
        $data['has_lunch_break'] = $request->has('has_lunch_break');
        
        $config->update($data);
        return back()->with('success', 'Rules updated for ' . date('l', strtotime("Sunday +{$config->day_of_week} days")));
    }

    // API Logic for the User Booking Modal (AJAX)
    public function checkOccupancy(Request $request)
    {
        $date = $request->query('date');
        $depId = $request->query('dependent_id');
        $excludeId = $request->query('exclude_id'); // Used during Resubmit

        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        try {
            // 1. DETERMINE PATIENT GENDER
            // Fetches gender from Dependent model or currently logged-in User
            $gender = 'both';
            if ($depId) {
                $patient = Dependent::find($depId);
                $gender = $patient ? $patient->sex : 'both';
            } elseif (Auth::check()) {
                $gender = Auth::user()->sex;
            }

            // 2. FETCH CLINIC CONFIGURATIONS
            // We get all 7 days so the Smart Scheduler can "bleed" into next available days locally
            $configs = AppointmentConfig::all()->keyBy('day_of_week');
            
            // Get specific config for the selected date
            $dayOfWeek = date('w', strtotime($date));
            $specificConfig = $configs[$dayOfWeek] ?? null;

            // 3. FETCH OCCUPANCY MAP (14-Day Window)
            // We count appointments grouped by date and time to check against capacity limits
            $endDate = date('Y-m-d', strtotime($date . ' +14 days'));
            
            $occupancy = Appointment::whereIn('status', ['pending', 'approved'])
                ->whereBetween('appointment_date', [$date, $endDate])
                // CRITICAL: If Resubmitting, exclude the current appointment from the count
                // so the user is not "blocking" themselves from their own slot.
                ->when($excludeId, function($query) use ($excludeId) {
                    return $query->where('id', '!=', $excludeId);
                })
                ->select('appointment_date', 'time_slot', DB::raw('count(*) as patient_count'))
                ->groupBy('appointment_date', 'time_slot')
                ->get();

            // 4. IDENTIFY FULL SLOTS FOR THE SELECTED DATE
            // This helper array is used by the simple single-day dropdown logic
            $fullSlots = [];
            if ($specificConfig) {
                $isClosed = (int)$specificConfig->is_open === 0;
                
                if (!$isClosed) {
                    // Query the DB directly for a list of times that have reached the max limit
                    // We ensure we only exclude the current ID for the count
                    $fullSlots = Appointment::where('appointment_date', $date)
                        ->whereIn('status', ['pending', 'approved'])
                        ->when($excludeId, function($q) use ($excludeId) {
                            return $q->where('id', '!=', $excludeId);
                        })
                        ->select('time_slot', DB::raw('count(*) as patient_count'))
                        ->groupBy('time_slot')
                        ->having('patient_count', '>=', (int)$specificConfig->max_patients_per_slot)
                        ->pluck('time_slot')
                        ->toArray();
                }
            }

            // 5. RETURN UNIFIED JSON RESPONSE
            return response()->json([
                'patient_gender' => strtolower($gender),
                'is_closed'      => $isClosed,
                'full_slots'     => $fullSlots,      // Specific to the $date provided
                'config'         => $specificConfig, // Rules for the $date provided
                'configs'        => $configs,        // Rules for all 7 days (for bulk JS)
                'occupancy'      => $occupancy       // 14-day booking map (for bulk JS)
            ]);

        } catch (\Exception $e) {
            // Log error and return 500 for debugging
            \Log::error("Occupancy Check Error: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }
}