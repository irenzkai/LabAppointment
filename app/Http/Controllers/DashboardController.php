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

        // Initialize all variables as empty so Blade never sees "null"
        $stats = ['total_users' => 0, 'pending_apps' => 0, 'today_apps' => 0];
        $popularServices = collect();
        $recentAppointments = collect();

        if ($user->role === 'admin') {
            $stats = [
                'total_users' => User::where('role', 'user')->count(),
                'pending_apps' => Appointment::where('status', 'pending')->count(),
                'today_apps' => Appointment::whereDate('appointment_date', now()->toDateString())->count(),
            ];
        } else {
            // Logic for Staff and Users (Main Menu)
            $popularServices = Service::withCount('appointments')
                ->where('is_available', true)
                ->orderBy('appointments_count', 'desc')
                ->take(3)
                ->get();

            $recentAppointments = Appointment::with('services')
                ->where('user_id', $user->id)
                ->latest()
                ->take(3)
                ->get();
        }

        return view('dashboard', compact('stats', 'popularServices', 'recentAppointments'));
    }
}
