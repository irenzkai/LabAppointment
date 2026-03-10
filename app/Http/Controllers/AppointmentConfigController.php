<?php

namespace App\Http\Controllers;

use App\Models\AppointmentConfig;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentConfigController extends Controller
{
    // Admin/Staff View: Settings and Occupancy Visualizer
    public function index(Request $request) {
        $config = AppointmentConfig::first();
        $selectedDate = $request->get('date', date('Y-m-d'));
        $slots = [];
        $current = strtotime($config->opening_time);
        $end = strtotime($config->closing_time);

        while ($current < $end) {
            $time = date('H:i:00', $current);
            
            // CHECK LUNCH BREAK LOGIC
            $isLunch = false;
            if ($config->has_lunch_break) {
                if ($time >= $config->lunch_start && $time < $config->lunch_end) {
                    $isLunch = true;
                }
            }

            if (!$isLunch) {
                $bookedCount = Appointment::where('appointment_date', $selectedDate)
                    ->where('time_slot', $time)
                    ->whereIn('status', ['pending', 'approved'])->count();

                $slots[] = [
                    'time' => $time,
                    'booked' => $bookedCount,
                    'capacity' => $config->max_patients_per_slot,
                    'is_full' => $bookedCount >= $config->max_patients_per_slot
                ];
            }
            $current = strtotime("+$config->slot_duration minutes", $current);
        }
        return view('admin.appointment-settings', compact('config', 'slots', 'selectedDate'));
    }

    // Update Global Settings
    public function update(Request $request) {
        $config = AppointmentConfig::first();
        
        // We use except('_token', '_method') to avoid MassAssignment errors
        $config->update($request->except(['_token', '_method']));
        
        return back()->with('success', 'Configuration updated!');
    }

    // API Logic for the User Booking Modal (AJAX)
    public function checkOccupancy(Request $request) {
        $date = $request->query('date');
        $config = AppointmentConfig::first();
        
        // Find slots that are at or above capacity
        $fullSlots = Appointment::where('appointment_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->select('time_slot')
            ->groupBy('time_slot')
            ->havingRaw('count(*) >= ?', [$config->max_patients_per_slot])
            ->pluck('time_slot')
            ->toArray();

        return response()->json([
            'full_slots' => $fullSlots,
            'config' => $config
        ]);
    }
}