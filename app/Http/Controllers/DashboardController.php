<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user roles.
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // Initialize variables to avoid "undefined variable" errors in Blade templates
        $stats = [
            'total_users' => 0, 
            'pending_apps' => 0, 
            'today_apps' => 0,
            'released_today' => 0,
            'role_queue_count' => 0, // This represents the updated "In Queue" metric
        ];
        $popularServices = collect();
        $recentAppointments = collect();

        // 1. Logic for EMPLOYEES (Admin, Staff, Lab Tech)
        if ($user->isEmployee()) {
            // Today's Load: Records every single appointment scheduled for today, regardless of status
            $todayAppsCount = Appointment::whereDate('appointment_date', $today)->count();

            // In Queue: Active appointments that are not finished (status is not 'released') and are not expired
            $roleQueueCount = Appointment::where('status', '!=', 'released')
                ->get()
                ->filter(fn($app) => !$app->isExpired())
                ->count();

            $stats = [
                'total_users' => User::where('role', 'user')->count(),
                'pending_apps' => Appointment::where('status', 'pending')->count(),
                'today_apps' => $todayAppsCount,
                'released_today' => Appointment::where('status', 'released')
                    ->whereDate('updated_at', $today)->count(),
                'role_queue_count' => $roleQueueCount,
            ];

            // Staff see the most recent clinic-wide activity
            $recentAppointments = Appointment::with(['services', 'user'])
                ->latest()
                ->take(5)
                ->get();
        } 

        // 2. Logic for PATIENTS (Regular Users)
        if ($user->isPatient()) {
            // Patients see the most booked services to help them choose
            $popularServices = Service::withCount('appointments')
                ->where('is_available', true)
                ->orderBy('appointments_count', 'desc')
                ->take(3)
                ->get();

            // Patients see only their own (or their dependents') appointments
            $recentAppointments = Appointment::with('services')
                ->where('user_id', $user->id)
                ->latest()
                ->take(3)
                ->get();
        }

        return view('dashboard', compact('stats', 'popularServices', 'recentAppointments'));
    }
}