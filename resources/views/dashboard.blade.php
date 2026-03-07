@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-light">Welcome back, <span class="fw-bold text-white">{{ Auth::user()->name }}</span></h2>
        <p class="text-secondary">Role: {{ ucfirst(Auth::user()->role) }}</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6"> <!-- Changed to 6 for better layout -->
        <div class="card p-4 text-center h-100 shadow">
            <h3>🧪</h3>
            <h4 class="mt-2 text-white">Services</h4>
            <p class="text-secondary small">Browse available laboratory tests and pricing.</p>
            <a href="{{ route('services.index') }}" class="btn btn-outline-primary mt-auto">View Services</a>
        </div>
    </div>
    <div class="col-md-6"> <!-- Changed to 6 for better layout -->
        <div class="card p-4 text-center h-100 shadow">
            <h3>📅</h3>
            <h4 class="mt-2 text-white">Appointments</h4>
            <p class="text-secondary small">Check the status of your bookings or create new ones.</p>
            <a href="{{ route('appointments.index') }}" class="btn btn-outline-primary mt-auto">View History</a>
        </div>
    </div>
</div>
@endsection