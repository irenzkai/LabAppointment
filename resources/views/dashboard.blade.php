@extends('layouts.app')

@section('content')
<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-white fw-bold">DASHBOARD</h2>
        <p class="text-secondary">Hello, <span class="text-white">{{ Auth::user()->name }}</span>. You are logged in as <span class="fw-bold border-neon text-neon small">{{ strtoupper(Auth::user()->role) }}</span></p>
    </div>
</div>

<div class="row g-4">
    
    {{-- --- ADMIN CARDS --- --}}
    @if(Auth::user()->role == 'admin')
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center shadow-sm border-info">
                <i class="bi bi-people text-info fs-1 mb-3"></i>
                <h5 class="text-white fw-bold">USER CONTROL</h5>
                <p class="text-secondary small">Promote, demote, or delete system accounts.</p>
                <a href="{{ url('/admin/users') }}" class="btn-custom btn-outline-neon border-info text-info mt-auto">MANAGE ACCOUNTS</a>
            </div>
        </div>
    @endif

    {{-- --- STAFF & ADMIN CARDS --- --}}
    @if(Auth::user()->isStaff()) {{-- Check logic in User Model --}}
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center shadow-sm">
                <i class="bi bi-clipboard-pulse text-neon fs-1 mb-3"></i>
                <h5 class="text-white fw-bold">APPOINTMENT QUEUE</h5>
                <p class="text-secondary small">Approve or return patient appointment requests.</p>
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-neon mt-auto">VIEW REQUESTS</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center shadow-sm">
                <i class="bi bi-hospital text-neon fs-1 mb-3"></i>
                <h5 class="text-white fw-bold">LAB SERVICES</h5>
                <p class="text-secondary small">Create, edit, or toggle availability of lab tests.</p>
                <a href="{{ route('services.index') }}" class="btn-custom btn-outline-neon mt-auto">MANAGE SERVICES</a>
            </div>
        </div>
    @endif

    {{-- --- REGULAR USER CARDS --- --}}
    @if(Auth::user()->role == 'user')
        <div class="col-md-6">
            <div class="card p-4 h-100 text-center shadow-sm">
                <i class="bi bi-calendar-plus text-neon fs-1 mb-3"></i>
                <h5 class="text-white fw-bold">BOOK A TEST</h5>
                <p class="text-secondary small">Select from our available laboratory services.</p>
                <a href="{{ route('services.index') }}" class="btn-custom btn-neon mt-auto">BROWSE SERVICES</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4 h-100 text-center shadow-sm">
                <i class="bi bi-clock-history text-neon fs-1 mb-3"></i>
                <h5 class="text-white fw-bold">MY HISTORY</h5>
                <p class="text-secondary small">Check the status of your current and past appointments.</p>
                <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-neon mt-auto">VIEW APPOINTMENTS</a>
            </div>
        </div>
    @endif

</div>

<style>
    .dropdown-item:hover { background-color: var(--neon) !important; color: #000 !important; }
</style>
@endsection