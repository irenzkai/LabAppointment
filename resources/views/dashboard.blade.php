@extends('layouts.app')

@section('content')

{{-- --- ADMIN VIEW --- --}}
@if(Auth::user()->role == 'admin')
    <div class="row mb-5 text-start">
        <div class="col-12">
            <h2 class="text-neon fw-bold mb-0 uppercase">System Dashboard</h2>
            <p class="text-secondary small">Administrative overview</p>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card p-3 border-secondary bg-black">
                <small class="text-secondary fw-bold">TOTAL PATIENTS</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['total_users'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-neon bg-black">
                <small class="text-neon fw-bold">PENDING REQUESTS</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['pending_apps'] }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-secondary bg-black">
                <small class="text-secondary fw-bold">APPOINTMENTS TODAY</small>
                <h2 class="text-white fw-bold mb-0">{{ $stats['today_apps'] }}</h2>
            </div>
        </div>
    </div>

    {{-- Admin Action Cards --}}
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center border-info">
                <i class="bi bi-people text-info fs-1"></i>
                <h5 class="text-white fw-bold mt-2">USER CONTROL</h5>
                <a href="{{ url('/admin/users') }}" class="btn-custom btn-outline-neon border-info text-info mt-auto">MANAGE</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center">
                <i class="bi bi-calendar-range text-neon fs-1"></i>
                <h5 class="text-white fw-bold mt-2">SCHEDULE RULES</h5>
                <a href="{{ route('admin.appointment-settings') }}" class="btn-custom btn-neon mt-auto">SETTINGS</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center">
                <i class="bi bi-clipboard-pulse text-neon fs-1"></i>
                <h5 class="text-white fw-bold mt-2">QUEUE</h5>
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-neon mt-auto">VIEW ALL</a>
            </div>
        </div>
    </div>

{{-- --- USER / STAFF VIEW (MAIN MENU) --- --}}
@else
    <div class="row mb-5 text-start">
        <div class="col-12">
            <h2 class="text-white fw-bold mb-0">Hello, <span class="text-neon">{{ strtoupper(Auth::user()->name) }}</span></h2>
            <p class="text-secondary">Welcome to Medscreen Main Menu</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-4 h-100 shadow border-neon">
                <h4 class="text-white fw-bold">BOOK NEW TEST</h4>
                <p class="text-secondary small mb-4">Select individual tests or packages.</p>
                <a href="{{ route('services.index') }}" class="btn-custom btn-neon px-4 py-2">BROWSE SERVICES</a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4 h-100 shadow border-secondary">
                <h5 class="text-white fw-bold mb-3 small">RECENT ACTIVITY</h5>
                @forelse($recentAppointments as $app)
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom border-secondary mb-1">
                        <div>
                            <small class="text-white fw-bold d-block">{{ $app->appointment_date->format('M d, Y') }}</small>
                            <small class="text-neon" style="font-size: 0.65rem;">{{ $app->services->count() }} Test(s)</small>
                        </div>
                        <span class="badge border {{ $app->status == 'pending' ? 'text-warning border-warning' : 'text-success border-success' }}">
                            {{ strtoupper($app->status) }}
                        </span>
                    </div>
                @empty
                    <p class="text-secondary small italic">No recent appointments.</p>
                @endforelse
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-neon btn-sm mt-3 w-100">VIEW HISTORY</a>
            </div>
        </div>

        @if($popularServices->count() > 0)
        <div class="col-12 mt-4 text-start">
            <h6 class="text-secondary fw-bold uppercase mb-3 px-1 small">Popular Tests</h6>
            <div class="row g-3">
                @foreach($popularServices as $popular)
                    <div class="col-md-4">
                        <div class="card bg-black border-secondary p-3">
                            <div class="d-flex justify-content-between">
                                <h6 class="text-white fw-bold mb-1 small">{{ strtoupper($popular->name) }}</h6>
                                <span class="text-neon fw-bold small">₱{{ number_format($popular->price) }}</span>
                            </div>
                            <form action="{{ route('cart.add', $popular->id) }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="btn-custom btn-outline-neon py-1 w-100" style="font-size: 0.6rem;">ADD TO BOOKING</button>
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
