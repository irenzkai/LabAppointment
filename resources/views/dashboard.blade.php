@extends('layouts.app')

@section('content')
<div class="container-fluid text-start">

    {{-- 1. DYNAMIC HEADER SECTION --}}
    <div class="row mb-5 align-items-center">
        <div class="col-md-8">
            <h6 class="text-accent fw-bold mb-1 uppercase tracking-widest" style="font-size: 0.75rem; letter-spacing: 1px;">
                {{ Auth::user()->isPatient() ? 'Main Menu' : 'Clinical Management System' }}
            </h6>
            <h1 class="text-main fw-bold mb-0">
                Welcome back, <span class="text-accent">{{ Auth::user()->first_name }}</span>
            </h1>
            <p class="text-muted mb-0 small">
                @if(Auth::user()->isAdmin()) 
                    System Administrator Portal active. All nodes operational.
                @elseif(Auth::user()->isLabTech()) 
                    {{-- FIXED: Referenced role_queue_count to represent pending workstation actions accurately --}}
                    Clinical Lab Workstation active. <span class="badge bg-accent text-dark ms-2">{{ $stats['role_queue_count'] }} Pending Tasks</span>
                @elseif(Auth::user()->isStaffOnly())
                    Front Desk Reception active. Ready for check-ins.
                @else
                    Patient Care Portal active. Access your clinical gateway below.
                @endif
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="card py-2 px-3 border-secondary d-inline-block bg-card shadow-sm">
                <div class="text-muted small fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ now()->format('l, j F Y') }}</div>
                <div class="text-main fs-4 fw-bold font-monospace" id="live-clock">00:00:00</div>
            </div>
        </div>
    </div>

    {{-- 2. STATS ROW --}}
    <div class="row g-3 mb-5">
        @if(Auth::user()->isEmployee())
            {{-- EMPLOYEE STATS CARDS --}}
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-4 border-accent h-100 shadow-sm bg-card">
                    <small class="text-muted fw-bold uppercase x-small" style="font-size: 0.65rem;">Today's Load</small>
                    <h2 class="text-main fw-bold mb-0">{{ $stats['today_apps'] }}</h2>
                    <div class="text-accent small fw-bold" style="font-size: 0.75rem;">Daily Appointments</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-4 border-warning h-100 shadow-sm bg-card">
                    <small class="text-muted fw-bold uppercase x-small" style="font-size: 0.65rem;">In Queue</small>
                    {{-- FIXED: Dynamically renders only the count of files awaiting action from the respective role --}}
                    <h2 class="text-main fw-bold mb-0">{{ $stats['role_queue_count'] }}</h2>
                    <div class="text-warning small fw-bold" style="font-size: 0.75rem;">Awaiting Action</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-4 border-info h-100 shadow-sm bg-card">
                    <small class="text-muted fw-bold uppercase x-small" style="font-size: 0.65rem;">Released</small>
                    <h2 class="text-main fw-bold mb-0">{{ $stats['released_today'] }}</h2>
                    <div class="text-info small fw-bold" style="font-size: 0.75rem;">Completed Today</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card p-3 border-start border-4 border-secondary h-100 shadow-sm bg-card">
                    <small class="text-muted fw-bold uppercase x-small" style="font-size: 0.65rem;">Total Patients</small>
                    <h2 class="text-main fw-bold mb-0">{{ $stats['total_users'] }}</h2>
                    <div class="text-muted small fw-bold" style="font-size: 0.75rem;">Registered Database</div>
                </div>
            </div>
        @else
            {{-- PATIENT STATS CARDS --}}
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
                    <h5 class="fw-bold text-main">Family Records</h5>
                    <p class="text-muted small mb-4">Add, register, and manage account details for your dependents.</p>
                    <a href="{{ route('profile.edit') }}" class="btn-custom btn-outline-accent w-100 mt-auto">Manage Family</a>
                </div>
            </div>
        @endif
    </div>

    {{-- 3. MAIN ACTION GRID --}}
    <div class="row g-4">
        {{-- LEFT PANEL: ACTIVITY --}}
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm border-secondary bg-card overflow-hidden">
                <div class="card-header bg-brand-dark text-white d-flex justify-content-between align-items-center py-3 border-bottom border-secondary border-opacity-25">
                    <h5 class="mb-0 small uppercase fw-bold tracking-wider" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                        <i class="bi bi-activity text-accent me-2"></i>
                        {{ Auth::user()->isEmployee() ? 'Recent Clinic-Wide Activity' : 'My Recent Inquiries' }}
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
                                @php
                                // SECURITY GUARD: Automatically group collection inside Blade if controller did not
                                $isGrouped = $recentAppointments->first() instanceof \Illuminate\Support\Collection;
                                
                                $displayInquiries = $isGrouped 
                                    ? $recentAppointments 
                                    : $recentAppointments->groupBy(fn($item) => $item->batch_id ?? 'single_' . $item->id);
                                @endphp

                                @forelse($displayInquiries as $batchId => $group)
                                    @php
                                    $first = $group->first();
                                    $groupCount = $group->count();
                                    $isGroup = $first->batch_id !== null;

                                    // Concat unique requested tests inside this appointment group
                                    $tests = $group->flatMap(fn($item) => $item->services->pluck('name'))->unique()->implode(', ');
                                    @endphp
                                    <tr class="border-secondary border-opacity-10">
                                        <td class="ps-4">
                                            @if($isGroup)
                                                {{-- Consolidated bulk line --}}
                                                <div class="text-main fw-bold small">{{ strtoupper($first->organization_name) }}</div>
                                                <div class="text-muted x-small" style="font-size: 0.65rem;">BULK ({{ $groupCount }} PAX)</div>
                                            @else
                                                <div class="text-main fw-bold small">{{ strtoupper($first->patient_name) }}</div>
                                                <div class="text-muted x-small" style="font-size: 0.65rem;">ID: #{{ $first->id }}</div>
                                            @endif
                                        </td>
                                        <td class="small text-main">
                                            {{ Str::limit($tests, 35) }}
                                        </td>
                                        <td class="small text-muted">
                                            {{ $first->appointment_date->format('M d, Y') }}
                                        </td>
                                        <td class="text-end pe-4">
                                            @php
                                            $statusColor = match($first->status) {
                                                'pending' => 'warning',
                                                'approved' => 'info',
                                                'released' => 'accent',
                                                'returned' => 'danger',
                                                default => 'secondary'
                                            };
                                            @endphp
                                            <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} uppercase x-small" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                                {{ $first->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted small italic">
                                            No activity recorded for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: TOOLS / POPULAR --}}
        <div class="col-lg-4">
            @if(Auth::user()->isEmployee())
                {{-- CLINICAL TOOLS PANEL (For Admin / Staff) --}}
                <div class="card bg-brand-dark text-white p-4 shadow-sm mb-4 border-secondary">
                    <h6 class="text-accent fw-bold mb-3 uppercase small tracking-widest" style="font-size: 0.75rem; letter-spacing: 1px;">Rapid Links</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('appointments.index') }}" class="btn btn-light btn-sm text-start py-2.5 px-3 fw-bold uppercase smaller" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <i class="bi bi-list-check me-2 text-accent"></i> Master Queue
                        </a>
                        <a href="{{ route('services.index') }}" class="btn btn-outline-light btn-sm text-start py-2.5 px-3 fw-bold uppercase smaller" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <i class="bi bi-list me-2 text-accent"></i> Service Catalog
                        </a>
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.appointment-settings') }}" class="btn btn-outline-info btn-sm text-start py-2.5 px-3 fw-bold uppercase smaller border-info" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-gear me-2"></i> Schedule Config
                            </a>
                        @endif
                    </div>
                </div>

                @if(Auth::user()->isAdmin())
                    <div class="card p-4 border-secondary shadow-sm bg-card">
                        <h6 class="text-muted fw-bold mb-3 uppercase small" style="font-size: 0.75rem; letter-spacing: 0.5px;">System Security</h6>
                        <p class="text-muted x-small mb-3" style="font-size: 0.7rem;">Review clinical logs and administrative audit trails.</p>
                        <a href="{{ route('admin.logs') }}" class="btn-custom btn-outline-secondary w-100 py-2">Audit Logs</a>
                    </div>
                @endif
            @else
                {{-- RECOMMENDATIONS PANEL (For Patients) --}}
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
                                    <div class="text-accent small fw-bold"> {{ number_format($service->price, 2) }}</div>
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
            @endif
        </div>
    </div>

</div>

<script>
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { 
        hour12: true, 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
    const clockEl = document.getElementById('live-clock');
    if (clockEl) {
        clockEl.innerText = timeStr;
    }
}
setInterval(updateClock, 1000);
updateClock();
</script>

<style>
.bg-brand-dark { background-color: var(--brand-dark) !important; }
.text-accent { color: var(--brand-accent) !important; }
.border-accent { border-color: var(--brand-accent) !important; }
.x-small { font-size: 0.7rem; }
.uppercase { text-transform: uppercase; }
</style>
@endsection