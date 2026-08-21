<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the standard patient menu (Shared base interface for ALL users).
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // Initialize default stats to prevent Blade undefined variable errors
        $stats = [
            'total_users' => 0, 
            'pending_apps' => 0, 
            'today_apps' => 0,
            'released_today' => 0,
            'role_queue_count' => 0,
        ];

        // If an employee accesses the patient main menu, calculate queue stats for header badges
        if ($user->isEmployee()) {
            $todayAppsCount = Appointment::whereDate('appointment_date', $today)->count();
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
        }

        // Patients and employees acting as patients see popular services and recent bookings
        $popularServices = Service::withCount('appointments')
            ->where('is_available', true)
            ->orderBy('appointments_count', 'desc')
            ->take(3)
            ->get();

        $recentAppointments = Appointment::with('services')
            ->where('user_id', $user->id)
            ->where('deleted_by_patient', false)
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard', compact('stats', 'popularServices', 'recentAppointments'));
    }

    /**
     * Staff Dashboard View: Analytics, status breakdowns, needing action list, and quick management links.
     */
    public function staffDashboard()
    {
        $user = Auth::user();
        if (!$user->isEmployee()) {
            abort(403, 'Access denied to Staff Dashboard.');
        }

        $nowSub24 = Carbon::now()->subHours(24)->toDateTimeString();

        // 1. All Patient Accounts Count
        $totalPatientAccounts = User::count();

        // 2. Expired Count Calculation
        $expiredCount = Appointment::whereNotIn('status', ['tested', 'encoded', 'released'])
            ->whereRaw("TIMESTAMP(appointment_date, time_slot) < ?", [$nowSub24])
            ->count();

        // 3. Comprehensive Appointment Status Breakdown (Filtering out expired from active statuses)
        $statusCounts = [
            'pending' => Appointment::where('status', 'pending')->whereRaw("TIMESTAMP(appointment_date, time_slot) >= ?", [$nowSub24])->count(),
            'approved' => Appointment::where('status', 'approved')->whereRaw("TIMESTAMP(appointment_date, time_slot) >= ?", [$nowSub24])->count(),
            'tested' => Appointment::where('status', 'tested')->count(),
            'encoded' => Appointment::where('status', 'encoded')->count(),
            'released' => Appointment::where('status', 'released')->count(),
            'returned' => Appointment::where('status', 'returned')->whereRaw("TIMESTAMP(appointment_date, time_slot) >= ?", [$nowSub24])->count(),
            'retest' => Appointment::where('status', 'retest')->whereRaw("TIMESTAMP(appointment_date, time_slot) >= ?", [$nowSub24])->count(),
            'canceled' => Appointment::where('status', 'canceled')->count(),
            'expired' => $expiredCount,
        ];

        // 4. Appointments Needing Action Query (EXCLUDES EXPIRED APPOINTMENTS ALWAYS)
        $needingActionQuery = Appointment::with(['services', 'user'])
            ->where(function($q) {
                $q->whereIn('status', ['pending', 'approved', 'retest', 'tested', 'encoded'])
                  ->orWhere(function($sub) {
                      $sub->where('status', 'canceled')
                          ->where('payment_method', 'Cashless')
                          ->where('payment_status', 'paid');
                  });
            })
            ->where(function($q) use ($nowSub24) {
                $q->whereIn('status', ['tested', 'encoded'])
                  ->orWhereRaw("TIMESTAMP(appointment_date, time_slot) >= ?", [$nowSub24]);
            });

        $needingActionCount = (clone $needingActionQuery)->count();
        $latestNeedingAction = (clone $needingActionQuery)->latest()->take(8)->get();

        // 5. Graphs & Analytics Payload
        $monthlyAppointmentsData = [];
        $monthLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabels[] = $month->format('M Y');
            $monthlyAppointmentsData[] = Appointment::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return view('dashboards.staff', compact(
            'totalPatientAccounts',
            'statusCounts',
            'needingActionCount',
            'latestNeedingAction',
            'monthLabels',
            'monthlyAppointmentsData'
        ));
    }

    /**
     * Admin Dashboard View: System-wide analytics, user breakdown, status stats, needing action, and system logs.
     */
    public function adminDashboard()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Access denied to System Administrator Dashboard.');
        }

        // Carry over all Staff Dashboard statistics
        $staffData = $this->staffDashboard()->getData();

        // Admin-Specific User Account Breakdown
        $userRoleBreakdown = [
            'patient' => User::where('role', 'user')->count(),
            'staff' => User::whereIn('role', ['staff', 'lab_tech'])->count(),
            'admin' => User::where('role', 'admin')->count(),
            'total' => User::count(),
        ];

        // Latest System Audit Logs
        $latestLogs = ActivityLog::with('user')->latest()->take(10)->get();

        return view('dashboards.admin', array_merge($staffData, compact(
            'userRoleBreakdown',
            'latestLogs'
        )));
    }
}