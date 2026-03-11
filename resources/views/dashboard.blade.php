@extends('layouts.app')

@section('content')

{{-- --- ADMIN & STAFF VIEW --- --}}
@if(Auth::user()->role !== 'user')
    <div class="row mb-5 text-start">
        <div class="col-12">
            <h2 class="text-neon fw-bold mb-0 uppercase" style="letter-spacing: 2px;">System Dashboard</h2>
            <p class="text-secondary small">Clinic oversight and management tools</p>
        </div>
    </div>

    <!-- Stats Row (Only for Admin) -->
    @if(Auth::user()->role == 'admin')
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card p-3 border-secondary bg-black text-start">
                <small class="text-secondary fw-bold uppercase" style="font-size: 0.65rem;">Total Registered Patients</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['total_users'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-neon bg-black shadow-sm text-start">
                <small class="text-neon fw-bold uppercase" style="font-size: 0.65rem;">Total Pending Requests</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['pending_apps'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-secondary bg-black text-start">
                <small class="text-secondary fw-bold uppercase" style="font-size: 0.65rem;">Scheduled for Today</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['today_apps'] }}</h2>
            </div>
        </div>
    </div>
    @endif

    <!-- Staff/Admin Action Grid -->
    <div class="row g-4 text-start">
        <div class="col-md-4">
            <div class="card p-4 h-100 border-neon position-relative overflow-hidden">
                <h5 class="text-white fw-bold mb-1 uppercase">Appointment Queue</h5>
                <p class="text-secondary small mb-4">Review and process patient test requests.</p>
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-neon mt-auto w-100">VIEW QUEUE</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 border-secondary">
                <h5 class="text-white fw-bold mb-1 uppercase">Lab Services</h5>
                <p class="text-secondary small mb-4">Manage test prices, availability, and details.</p>
                <a href="{{ route('services.index') }}" class="btn-custom btn-outline-neon mt-auto w-100">MANAGE TESTS</a>
            </div>
        </div>
        @if(Auth::user()->role == 'admin')
        <div class="col-md-4">
            <div class="card p-4 h-100 border-info">
                <h5 class="text-info fw-bold mb-1 uppercase">User Accounts</h5>
                <p class="text-secondary small mb-4">Manage system access, roles, and security.</p>
                <a href="{{ url('/admin/users') }}" class="btn-custom btn-outline-neon border-info text-info mt-auto w-100">MANAGE ACCOUNTS</a>
            </div>
        </div>
        @endif
    </div>

{{-- --- REGULAR PATIENT VIEW (MAIN MENU) --- --}}
@else
    <div class="row mb-5 text-start align-items-end">
        <div class="col-md-8">
            <h1 class="text-white fw-bold mb-0">Hello, <span class="text-neon">{{ strtoupper(Auth::user()->name) }}</span></h1>
            <p class="text-secondary mb-0">Select an action to proceed with your laboratory needs.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3">
            <span class="badge border border-secondary text-secondary p-2 px-3 fw-bold">
                <i class="bi bi-bell-fill me-2"></i>0 NOTIFICATIONS
            </span>
        </div>
    </div>

    <div class="row g-4 text-start">
        <!-- Booking Card -->
        <div class="col-md-7">
            <div class="card p-5 h-100 border-neon shadow-lg position-relative overflow-hidden" style="background: linear-gradient(45deg, #0a0a0a, #000);">
                <div class="position-relative" style="z-index: 2;">
                    <h2 class="text-white fw-bold mb-2">BOOK A TEST</h2>
                    <p class="text-secondary mb-4" style="max-width: 400px;">Schedule your medical examination from our list of quality and affordable diagnostic services.</p>
                    <a href="{{ route('services.index') }}" class="btn-custom btn-neon px-5 py-3 fs-6 shadow">BROWSE ALL SERVICES</a>
                </div>
                <i class="bi bi-clipboard2-pulse position-absolute text-neon opacity-10" style="font-size: 12rem; right: -20px; bottom: -40px;"></i>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="col-md-5">
            <div class="card p-4 h-100 border-secondary bg-black shadow">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-2">
                    <h6 class="text-white fw-bold mb-0 uppercase small">Recent Appointments</h6>
                    <a href="{{ route('appointments.index') }}" class="text-neon small fw-bold uppercase" style="font-size: 0.65rem;">View All</a>
                </div>

                @forelse($recentAppointments as $app)
                    <div class="bg-dark p-3 rounded mb-2 border border-secondary d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white small fw-bold">{{ $app->appointment_date->format('M d, Y') }}</div>
                            <div class="text-secondary" style="font-size: 0.65rem;">{{ $app->services->count() }} Test(s) at {{ date('h:i A', strtotime($app->time_slot)) }}</div>
                        </div>
                        <span class="badge border {{ $app->status == 'pending' ? 'text-warning border-warning' : ($app->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}" style="font-size: 0.6rem;">
                            {{ strtoupper($app->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x text-secondary fs-2"></i>
                        <p class="text-secondary small italic mt-2">No recent activity found.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Popular Tests Row -->
        @if($popularServices->count() > 0)
        <div class="col-12 mt-4">
            <h6 class="text-secondary fw-bold uppercase mb-3 small" style="letter-spacing: 1px;">Recommended For You</h6>
            <div class="row g-3">
                @foreach($popularServices as $popular)
                    <div class="col-md-4">
                        <div class="card bg-black border-secondary p-3 shadow-sm h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="text-white fw-bold mb-0 small">{{ strtoupper($popular->name) }}</h6>
                                <span class="text-neon fw-bold small">₱{{ number_format($popular->price) }}</span>
                            </div>
                            <p class="text-secondary smaller mb-3">{{ \Illuminate\Support\Str::limit($popular->description, 50) }}</p>
                            <form action="{{ route('cart.add', $popular->id) }}" method="POST" class="mt-auto">
                                @csrf
                                <button type="submit" class="btn-custom btn-outline-neon py-2 w-100 fw-bold" style="font-size: 0.65rem;">QUICK ADD</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
@endif

@endsection
