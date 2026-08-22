<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
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
     * Staff Panel View: Analytics, status breakdowns, needing action list, and quick management links.
     */
    public function staffPanel()
    {
        $user = Auth::user();
        if (!$user->isEmployee()) {
            abort(403, 'Access denied to Staff Panel.');
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
        // Daily (Last 7 Days)
        $dailyAppointmentsData = [];
        $dayLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayLabels[] = $day->format('M d');
            $dailyAppointmentsData[] = Appointment::whereDate('created_at', $day->toDateString())->count();
        }

        // Monthly (Last 6 Months)
        $monthlyAppointmentsData = [];
        $monthLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabels[] = $month->format('M Y');
            $monthlyAppointmentsData[] = Appointment::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        // Yearly (Last 5 Years)
        $yearlyAppointmentsData = [];
        $yearLabels = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i);
            $yearLabels[] = $year->format('Y');
            $yearlyAppointmentsData[] = Appointment::whereYear('created_at', $year->year)->count();
        }

        return view('dashboards.staff', compact(
            'totalPatientAccounts',
            'statusCounts',
            'needingActionCount',
            'latestNeedingAction',
            'dailyAppointmentsData',
            'dayLabels',
            'monthlyAppointmentsData',
            'monthLabels',
            'yearlyAppointmentsData',
            'yearLabels'
        ));
    }

    /**
     * Admin Panel View: System-wide analytics, user breakdown, status stats, needing action, revenue, transactions, and system logs.
     */
    public function adminPanel(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Access denied to System Administrator Panel.');
        }

        // Carry over all Staff Panel statistics
        $staffData = $this->staffPanel()->getData();

        // Admin-Specific User Account Breakdown
        $userRoleBreakdown = [
            'patient' => User::where('role', 'user')->count(),
            'staff' => User::whereIn('role', ['staff', 'lab_tech'])->count(),
            'admin' => User::where('role', 'admin')->count(),
            'total' => User::count(),
        ];

        // Financial & Revenue Metrics
        $totalRevenue = Appointment::where('payment_status', 'paid')->sum('payment_amount');
        $monthlyRevenue = Appointment::where('payment_status', 'paid')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('payment_amount');
        $todayRevenue = Appointment::where('payment_status', 'paid')
            ->whereDate('created_at', Carbon::today())
            ->sum('payment_amount');

        // Transactions Filter & Pagination (10 per page)
        $txPeriod = $request->query('tx_period', 'cumulative');
        $txDate = $request->query('tx_date', Carbon::today()->toDateString());
        $txMonth = $request->query('tx_month', Carbon::now()->format('Y-m'));
        $txYear = $request->query('tx_year', Carbon::now()->format('Y'));
        $txStatus = $request->query('tx_status', 'all');
        $txSearch = $request->query('tx_search');

        $txQuery = Appointment::with('services');

        if ($txSearch) {
            $txQuery->where(function($q) use ($txSearch) {
                $q->where('patient_name', 'like', "%{$txSearch}%")
                    ->orWhere('id', 'like', "%{$txSearch}%")
                    ->orWhere('organization_name', 'like', "%{$txSearch}%");
            });
        }

        if ($txStatus && $txStatus !== 'all') {
            $txQuery->where('payment_status', $txStatus);
        }

        if ($txPeriod === 'daily' && $txDate) {
            $txQuery->whereDate('appointment_date', $txDate);
        } elseif ($txPeriod === 'monthly' && $txMonth) {
            $mParts = explode('-', $txMonth);
            if (count($mParts) === 2) {
                $txQuery->whereYear('appointment_date', $mParts[0])->whereMonth('appointment_date', $mParts[1]);
            }
        } elseif ($txPeriod === 'yearly' && $txYear) {
            $txQuery->whereYear('appointment_date', $txYear);
        }

        $transactions = $txQuery->latest()->paginate(10, ['*'], 'tx_page')->withQueryString();

        // Latest System Audit Logs Pagination (10 per page)
        $latestLogs = ActivityLog::with('user')->latest()->paginate(10, ['*'], 'logs_page')->withQueryString();

        return view('dashboards.admin', array_merge($staffData, compact(
            'userRoleBreakdown',
            'latestLogs',
            'totalRevenue',
            'monthlyRevenue',
            'todayRevenue',
            'transactions',
            'txPeriod',
            'txDate',
            'txMonth',
            'txYear',
            'txStatus',
            'txSearch'
        )));
    }
}