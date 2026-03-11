<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            // ADMIN DATA
            $stats = [
                'total_users' => User::where('role', 'user')->count(),
                'pending_apps' => Appointment::where('status', 'pending')->count(),
                'today_apps' => Appointment::whereDate('appointment_date', now()->toDateString())->count(),
            ];
            return view('dashboard', compact('stats'));
        }

        // USER DATA (Main Menu)
        // 1. Get 3 most booked services
        $popularServices = Service::withCount('appointments')
            ->where('is_available', true)
            ->orderBy('appointments_count', 'desc')
            ->take(3)
            ->get();

        // 2. Get user's 3 most recent appointments
        $recentAppointments = Appointment::with('services')
            ->where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard', compact('popularServices', 'recentAppointments'));
    }
}
