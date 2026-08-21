@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid text-start animate-page">
    
    {{-- Admin Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">System Administrator Dashboard</h2>
            <p class="text-secondary small mb-0">System-wide control console: User administration, audit logs, and clinical configurations.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-accent fw-bold btn-sm py-2 px-3 uppercase">
                <i class="bi bi-people-fill me-1.5"></i> Manage Users
            </a>
            <a href="{{ route('admin.logs') }}" class="btn btn-outline-accent fw-bold btn-sm py-2 px-3 uppercase">
                <i class="bi bi-shield-lock-fill me-1.5"></i> Audit Logs
            </a>
        </div>
    </div>

    {{-- Admin Quick Access Navigation Toolbar --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.users.index') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-person-gear"></i></div>
                    <div class="fw-bold text-main small uppercase">Manage Users</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('services.manage') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-list-stars"></i></div>
                    <div class="fw-bold text-main small uppercase">Services List</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-kanban"></i></div>
                    <div class="fw-bold text-main small uppercase">Appointments</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.payment-providers.index') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-qr-code-scan"></i></div>
                    <div class="fw-bold text-main small uppercase">Payment QRs</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.appointment-settings') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-clock-history"></i></div>
                    <div class="fw-bold text-main small uppercase">Schedule Editor</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.logs') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-danger fs-3 mb-1"><i class="bi bi-shield-check"></i></div>
                    <div class="fw-bold text-main small uppercase">System Logs</div>
                </div>
            </a>
        </div>
    </div>

    {{-- System User Account Summary Grid --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3 border-start border-4 border-accent bg-card shadow-sm text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1">Total System Accounts</small>
                <h3 class="text-main fw-bold mb-0">{{ $userRoleBreakdown['total'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-start border-4 border-info bg-card shadow-sm text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1">Patient Accounts</small>
                <h3 class="text-info fw-bold mb-0">{{ $userRoleBreakdown['patient'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-start border-4 border-warning bg-card shadow-sm text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1">Staff / LabTech Personnel</small>
                <h3 class="text-warning fw-bold mb-0">{{ $userRoleBreakdown['staff'] }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 border-start border-4 border-danger bg-card shadow-sm text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1">System Administrators</small>
                <h3 class="text-danger fw-bold mb-0">{{ $userRoleBreakdown['admin'] }}</h3>
            </div>
        </div>
    </div>

    {{-- Appointment Workflow & Needing Actions --}}
    <div class="row g-4 mb-4">
        {{-- Appointments Needing Action Table --}}
        <div class="col-lg-7">
            <div class="card p-0 border-secondary bg-card shadow-sm text-start overflow-hidden h-100">
                <div class="p-3 bg-brand-dark border-bottom border-secondary d-flex justify-content-between align-items-center">
                    <h6 class="text-accent fw-bold mb-0 uppercase small"><i class="bi bi-exclamation-circle-fill me-1.5"></i> Appointments Needing Action</h6>
                    <a href="{{ route('appointments.index', ['view' => 'queue', 'status' => 'needs_action']) }}" class="text-accent small fw-bold text-decoration-none">Master Queue &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="color: var(--text-main);">
                        <thead class="small uppercase bg-black text-secondary" style="font-size: 0.7rem;">
                            <tr>
                                <th class="ps-3 py-2">Patient</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestNeedingAction->take(6) as $app)
                            <tr class="border-secondary border-opacity-10">
                                <td class="ps-3">
                                    <div class="fw-bold text-main small">{{ $app->patient_name }}</div>
                                    <small class="text-muted">Ref: #{{ $app->id }}</small>
                                </td>
                                <td>
                                    <div class="small fw-semibold">{{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }}</div>
                                    <small class="text-accent">{{ date('h:i A', strtotime($app->time_slot)) }}</small>
                                </td>
                                <td>
                                    <span class="badge border border-warning text-warning uppercase px-2 py-1 small">
                                        {{ strtoupper($app->status) }}
                                    </span>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('appointments.index', ['view' => 'queue', 'id' => $app->id]) }}" class="btn btn-sm btn-accent fw-bold uppercase">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted small italic">No appointments currently require action.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Role Distribution Chart --}}
        <div class="col-lg-5">
            <div class="card p-4 border-secondary bg-card shadow-sm h-100 text-start">
                <h6 class="text-accent fw-bold uppercase small mb-3"><i class="bi bi-people-fill me-1.5"></i> System Account Distribution</h6>
                <div style="height: 250px;" class="d-flex justify-content-center">
                    <canvas id="userRolesDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest System Activity Logs --}}
    <div class="card p-0 border-secondary bg-card shadow-sm mb-5 text-start overflow-hidden">
        <div class="p-3 bg-brand-dark border-bottom border-secondary d-flex justify-content-between align-items-center">
            <h6 class="text-danger fw-bold mb-0 uppercase small"><i class="bi bi-shield-lock-fill me-1.5"></i> Latest System Audit Logs</h6>
            <a href="{{ route('admin.logs') }}" class="text-accent small fw-bold text-decoration-none">Full Audit History &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.7rem;">
                    <tr>
                        <th class="ps-4 py-3">Performer</th>
                        <th>Action</th>
                        <th>Target Patient</th>
                        <th>Justification / Reason</th>
                        <th class="pe-4 text-end">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestLogs as $log)
                    <tr class="border-secondary border-opacity-10">
                        <td class="ps-4 py-2.5">
                            <div class="fw-bold uppercase small">{{ $log->user->name ?? 'System/Deleted' }}</div>
                            <small class="text-muted fs-x-small">{{ strtoupper($log->user->role ?? 'N/A') }}</small>
                        </td>
                        <td>
                            <span class="badge border border-info text-info fw-bold small uppercase px-2 py-1">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="small fw-bold uppercase">{{ $log->patient_name }}</td>
                        <td class="small text-muted italic">{{ Str::limit($log->reason, 50) }}</td>
                        <td class="pe-4 text-end small text-secondary">
                            {{ $log->created_at->format('M d, Y | h:i A') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted small italic">No system audit logs recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // User Roles Breakdown Chart
    const rolesCtx = document.getElementById('userRolesDoughnutChart').getContext('2d');
    new Chart(rolesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Patients', 'Staff & Techs', 'Administrators'],
            datasets: [{
                data: [
                    {{ $userRoleBreakdown['patient'] }},
                    {{ $userRoleBreakdown['staff'] }},
                    {{ $userRoleBreakdown['admin'] }}
                ],
                backgroundColor: ['#0dcaf0', '#ffc107', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { color: '#94a3b8', font: { size: 11 } } }
            }
        }
    });
});
</script>
@endpush
@endsection