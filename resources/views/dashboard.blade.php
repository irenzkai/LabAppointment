@extends('layouts.app')
@section('title', 'Main Menu')

@section('content')
<div class="container-fluid text-start animate-page">
    
    {{-- 1. UNIFORM HEADER SECTION FOR ALL USERS --}}
    <div class="row mb-5 align-items-center">
        <div class="col-md-8">
            <h6 class="text-accent fw-bold mb-1 uppercase tracking-widest" style="font-size: 0.75rem; letter-spacing: 1px;">
                Main Menu
            </h6>
            <h1 class="text-main fw-bold mb-0">
                Welcome back, <span class="text-accent">{{ Auth::user()->first_name }}</span>
            </h1>
            <p class="text-muted mb-0 small">
                Patient Care Portal active. Access your clinical gateway below.
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="card py-2 px-3 border-secondary d-inline-block bg-card shadow-sm">
                <div class="text-muted small fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ now()->format('l, j F Y') }}</div>
                <div class="text-main fs-4 fw-bold font-monospace" id="live-clock">00:00:00</div>
            </div>
        </div>
    </div>

    {{-- 2. PATIENT ACTION CARDS FOR EVERYONE --}}
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card p-4 border-accent bg-card shadow-sm h-100 text-center">
                <div class="bg-secondary bg-opacity-10 rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center text-main" style="width: 60px; height: 60px;">
                    <i class="bi bi-calendar-check fs-3 text-accent"></i>
                </div>
                <h5 class="fw-bold text-main">New Booking</h5>
                <p class="text-muted small mb-4">Schedule your laboratory examinations in just a few minutes.</p>
                <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent w-100 mt-auto">Book Appointment</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-secondary bg-card shadow-sm h-100 text-center">
                <div class="bg-secondary bg-opacity-10 rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center text-main" style="width: 60px; height: 60px;">
                    <i class="bi bi-file-earmark-text fs-3 text-accent"></i>
                </div>
                <h5 class="fw-bold text-main">Result Archive</h5>
                <p class="text-muted small mb-4">Securely view, preview, or download your historical clinical files.</p>
                <a href="{{ route('patient.history') }}" class="btn-custom btn-outline-accent w-100 mt-auto">Access Records</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-secondary bg-card shadow-sm h-100 text-center">
                <div class="bg-secondary bg-opacity-10 rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center text-main" style="width: 60px; height: 60px;">
                    <i class="bi bi-people fs-3 text-accent"></i>
                </div>
                <h5 class="fw-bold text-main">Dependent Records</h5>
                <p class="text-muted small mb-4">Add, register, and manage account details for your child dependents.</p>
                <a href="{{ route('profile.edit') }}" class="btn-custom btn-outline-accent w-100 mt-auto">Manage Dependents</a>
            </div>
        </div>
    </div>

    {{-- 3. MAIN ACTION GRID --}}
    <div class="row g-4">
        {{-- LEFT PANEL: Recent Inquiries --}}
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm border-secondary bg-card overflow-hidden">
                <div class="card-header bg-brand-dark text-white d-flex justify-content-between align-items-center py-3 border-bottom border-secondary border-opacity-25">
                    <h5 class="mb-0 small uppercase fw-bold tracking-wider" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                        <i class="bi bi-activity text-accent me-2"></i>My Recent Inquiries
                    </h5>
                    <a href="{{ route('appointments.index') }}" class="text-accent small text-decoration-none fw-bold">VIEW ALL</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-black text-muted x-small uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <tr>
                                    <th class="ps-4">Patient / Entity</th>
                                    <th>Examinations</th>
                                    <th>Schedule</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAppointments as $app)
                                <tr class="border-secondary border-opacity-10 align-middle" style="cursor: pointer;" onclick="window.location.href='{{ route('appointments.index') }}?id={{ $app->id }}'">
                                    <td class="ps-4">
                                        <div class="text-main fw-bold small">{{ strtoupper($app->patient_name) }}</div>
                                        <div class="text-muted x-small" style="font-size: 0.65rem;">ID: #{{ $app->id }}</div>
                                    </td>
                                    <td class="small text-main">
                                        {{ Str::limit($app->services->pluck('name')->implode(', '), 35) }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="badge border border-info text-info uppercase x-small" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                            {{ strtoupper($app->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted small italic">
                                        No recent appointment inquiries found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: Recommended Tests --}}
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm border-secondary bg-card overflow-hidden">
                <div class="card-header bg-brand-dark text-white py-3 border-bottom border-secondary border-opacity-25">
                    <h6 class="mb-0 small uppercase fw-bold tracking-wider" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                        <i class="bi bi-star-fill text-accent me-2"></i>Recommended Tests
                    </h6>
                </div>
                <div class="card-body p-4">
                    @foreach($popularServices as $service)
                    <div class="mb-3 pb-3 border-bottom border-secondary border-opacity-10 text-start">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="text-main fw-bold small uppercase">{{ $service->name }}</div>
                            <div class="text-accent small fw-bold">₱{{ number_format($service->price, 2) }}</div>
                        </div>
                        <div class="x-small text-muted mb-2" style="font-size: 0.7rem;">{{ Str::limit($service->description, 65) }}</div>
                        <a href="{{ route('appointments.create') }}" class="text-accent x-small fw-bold text-decoration-none" style="font-size: 0.7rem;">BOOK TEST <i class="bi bi-chevron-right"></i></a>
                    </div>
                    @endforeach
                    <div class="text-center mt-3">
                        <a href="{{ route('services.index') }}" class="text-muted small text-decoration-none">Browse all services...</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const clockEl = document.getElementById('live-clock');
    if (clockEl) clockEl.innerText = timeStr;
}
setInterval(updateClock, 1000);
updateClock();
</script>
@endpush
@endsection