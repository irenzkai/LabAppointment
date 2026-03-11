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

        // 1. ADMIN LOGIC
        if ($user->role === 'admin') {
            $stats = [
                'total_users' => User::where('role', 'user')->count(),
                'pending_apps' => Appointment::where('status', 'pending')->count(),
                'today_apps' => Appointment::whereDate('appointment_date', now()->toDateString())->count(),
            ];
            return view('dashboard', compact('stats'));
        }

        // 2. USER LOGIC (Staff/Regular User)
        // Safer way to get popular services
        $popularServices = Service::has('appointments') 
            ? Service::withCount('appointments')
                ->where('is_available', true)
                ->orderBy('appointments_count', 'desc')
                ->take(3)
                ->get()
            : collect(); // Return empty list if no appointments exist yet

        $recentAppointments = Appointment::with('services')
            ->where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard', compact('popularServices', 'recentAppointments'));
    }
}
