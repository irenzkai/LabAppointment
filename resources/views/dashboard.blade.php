@extends('layouts.app')

@section('content')

{{-- --- ADMIN VIEW --- --}}
@if(Auth::user()->role == 'admin')
    <div class="row mb-5 text-start">
        <div class="col-12">
            <h2 class="text-neon fw-bold mb-0 uppercase" style="letter-spacing: 2px;">System Dashboard</h2>
            <p class="text-secondary small">Administrative overview and system controls</p>
        </div>
    </div>

    <!-- Admin Stats Row -->
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card p-3 border-secondary bg-black">
                <small class="text-secondary fw-bold uppercase">Total Patients</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['total_users'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-neon bg-black shadow-sm">
                <small class="text-neon fw-bold uppercase">Pending Requests</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['pending_apps'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-secondary bg-black">
                <small class="text-secondary fw-bold uppercase">Appointments Today</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['today_apps'] }}</h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center border-info">
                <i class="bi bi-people text-info fs-1 mb-3"></i>
                <h5 class="text-white fw-bold uppercase">User Control</h5>
                <p class="text-secondary small">Manage roles and account access.</p>
                <a href="{{ url('/admin/users') }}" class="btn-custom btn-outline-neon border-info text-info mt-auto">MANAGE ACCOUNTS</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center">
                <i class="bi bi-calendar-range text-neon fs-1 mb-3"></i>
                <h5 class="text-white fw-bold uppercase">Schedule Rules</h5>
                <p class="text-secondary small">Set hours, time blocks, and limits.</p>
                <a href="{{ route('admin.appointment-settings') }}" class="btn-custom btn-neon mt-auto">APPOINTMENT SETTINGS</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center">
                <i class="bi bi-clipboard-pulse text-neon fs-1 mb-3"></i>
                <h5 class="text-white fw-bold uppercase">Queue</h5>
                <p class="text-secondary small">Process incoming appointment requests.</p>
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-neon mt-auto">VIEW ALL REQUESTS</a>
            </div>
        </div>
    </div>

{{-- --- USER / PATIENT VIEW --- --}}
@else
    <div class="row mb-5 text-start">
        <div class="col-md-8">
            <h2 class="text-white fw-bold mb-0">Hello, <span class="text-neon">{{ strtoupper(Auth::user()->name) }}</span></h2>
            <p class="text-secondary">Welcome to Medscreen Main Menu</p>
        </div>
        {{-- Notifications Placeholder --}}
        <div class="col-md-4 text-md-end">
            <div class="p-2 px-3 rounded border border-secondary d-inline-block opacity-50 bg-black">
                <i class="bi bi-bell text-secondary me-2"></i>
                <small class="text-secondary fw-bold">0 NEW NOTIFICATIONS</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Quick Actions -->
        <div class="col-md-6">
            <div class="card p-4 h-100 shadow border-neon position-relative overflow-hidden">
                <div class="position-relative" style="z-index: 2;">
                    <h4 class="text-white fw-bold">BOOK NEW TEST</h4>
                    <p class="text-secondary small mb-4">Choose from our individual tests or specialized medical packages.</p>
                    <a href="{{ route('services.index') }}" class="btn-custom btn-neon px-4 py-2">BROWSE SERVICES</a>
                </div>
                <i class="bi bi-plus-circle position-absolute text-neon opacity-10" style="font-size: 8rem; right: -20px; bottom: -30px; z-index: 1;"></i>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4 h-100 shadow border-secondary">
                <h5 class="text-white fw-bold mb-3 uppercase small">Recent Appointments</h5>
                @forelse($recentAppointments as $app)
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom border-secondary mb-1">
                        <div>
                            <small class="text-white fw-bold d-block">{{ $app->appointment_date->format('M d, Y') }}</small>
                            <small class="text-neon" style="font-size: 0.65rem;">{{ $app->services->count() }} Test(s)</small>
                        </div>
                        <span class="badge border {{ $app->status == 'pending' ? 'text-warning border-warning' : ($app->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }} small">
                            {{ strtoupper($app->status) }}
                        </span>
                    </div>
                @empty
                    <p class="text-secondary small italic mt-4">No recent activity found.</p>
                @endforelse
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-neon btn-sm mt-3 w-100">VIEW HISTORY</a>
            </div>
        </div>

        <!-- Popular Services Section -->
        <div class="col-12 mt-4">
            <h6 class="text-secondary fw-bold uppercase mb-3 px-1 small" style="letter-spacing: 1px;">Most Popular Tests</h6>
            <div class="row g-3">
                @foreach($popularServices as $popular)
                    <div class="col-md-4">
                        <div class="card bg-black border-secondary p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="text-white fw-bold mb-1 small">{{ strtoupper($popular->name) }}</h6>
                                <span class="text-neon fw-bold small">₱{{ number_format($popular->price) }}</span>
                            </div>
                            <p class="text-secondary smaller mb-2">{{ \Illuminate\Support\Str::limit($popular->description, 60) }}</p>
                            <form action="{{ route('cart.add', $popular->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-custom btn-outline-neon py-1 w-100" style="font-size: 0.65rem;">ADD TO BOOKING</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@endsection
