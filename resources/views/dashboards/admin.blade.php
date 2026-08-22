@extends('layouts.app')
@section('title', 'Admin Panel')

@section('content')
<div class="container text-start animate-page py-4">
    {{-- Admin Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">System Administrator Panel</h2>
            <p class="text-secondary small mb-0">System-wide control console: User administration, revenue reports, booking trends, and audit logs.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports') }}" class="btn btn-accent fw-bold btn-sm py-2 px-3 uppercase shadow-sm">
                <i class="bi bi-file-earmark-bar-graph-fill me-1.5"></i> Reports & Exports
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-accent fw-bold btn-sm py-2 px-3 uppercase">
                <i class="bi bi-people-fill me-1.5"></i> Manage Users
            </a>
            <a href="{{ route('admin.logs') }}" class="btn btn-outline-accent fw-bold btn-sm py-2 px-3 uppercase">
                <i class="bi bi-shield-lock-fill me-1.5"></i> Audit Logs
            </a>
        </div>
    </div>

    {{-- Admin Quick Access Navigation Toolbar (Single Row with Side Scroller) --}}
    <div class="d-flex flex-nowrap overflow-auto gap-3 pb-2 mb-4 custom-scroll">
        <div style="min-width: 160px; flex: 1 0 160px;">
            <a href="{{ route('admin.users.index') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-person-gear"></i></div>
                    <div class="fw-bold text-main small uppercase">Manage Users</div>
                </div>
            </a>
        </div>
        <div style="min-width: 160px; flex: 1 0 160px;">
            <a href="{{ route('services.manage') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-list-stars"></i></div>
                    <div class="fw-bold text-main small uppercase">Services List</div>
                </div>
            </a>
        </div>
        <div style="min-width: 160px; flex: 1 0 160px;">
            <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-kanban"></i></div>
                    <div class="fw-bold text-main small uppercase">Appointments</div>
                </div>
            </a>
        </div>
        <div style="min-width: 160px; flex: 1 0 160px;">
            <a href="{{ route('admin.payment-providers.index') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-qr-code-scan"></i></div>
                    <div class="fw-bold text-main small uppercase">Payment QRs</div>
                </div>
            </a>
        </div>
        <div style="min-width: 160px; flex: 1 0 160px;">
            <a href="{{ route('admin.appointment-settings') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-clock-history"></i></div>
                    <div class="fw-bold text-main small uppercase">Schedule Editor</div>
                </div>
            </a>
        </div>
        <div style="min-width: 160px; flex: 1 0 160px;">
            <a href="{{ route('admin.reports') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-accent fs-3 mb-1"><i class="bi bi-file-earmark-bar-graph"></i></div>
                    <div class="fw-bold text-main small uppercase">Reports & Export</div>
                </div>
            </a>
        </div>
        <div style="min-width: 160px; flex: 1 0 160px;">
            <a href="{{ route('admin.logs') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="text-center">
                    <div class="text-danger fs-3 mb-1"><i class="bi bi-shield-check"></i></div>
                    <div class="fw-bold text-main small uppercase">System Logs</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Financial & Revenue Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-4 border-start border-4 border-success bg-card shadow-sm text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1"><i class="bi bi-cash-stack text-success me-1"></i> Total Gross Revenue</small>
                <h2 class="text-accent fw-bold mb-1">₱{{ number_format($totalRevenue, 2) }}</h2>
                <span class="text-muted smaller">*Cumulative paid transactions</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-start border-4 border-accent bg-card shadow-sm text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1"><i class="bi bi-calendar-check text-accent me-1"></i> This Month's Revenue</small>
                <h2 class="text-accent fw-bold mb-1">₱{{ number_format($monthlyRevenue, 2) }}</h2>
                <span class="text-accent small fw-semibold">Current Billing Month</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-start border-4 border-info bg-card shadow-sm text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1"><i class="bi bi-clock-history text-info me-1"></i> Today's Revenue</small>
                <h2 class="text-info fw-bold mb-1">₱{{ number_format($todayRevenue, 2) }}</h2>
                <span class="text-info small fw-semibold">Settled Collections Today</span>
            </div>
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

    {{-- Main Visual Analytics: Booking Trends & Status Distribution --}}
    <div class="row g-4 mb-4">
        {{-- Booking Trends Graph --}}
        <div class="col-lg-6">
            <div class="card p-4 border-secondary bg-card shadow-sm h-100 text-start">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-accent fw-bold uppercase small mb-0">
                        <i class="bi bi-graph-up-arrow me-1.5"></i> <span id="trendChartTitle">Monthly</span> Booking Trends
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-accent" id="btnTrendDaily" onclick="switchTrendTimeframe('daily')">Daily</button>
                        <button type="button" class="btn btn-accent fw-bold" id="btnTrendMonthly" onclick="switchTrendTimeframe('monthly')">Monthly</button>
                        <button type="button" class="btn btn-outline-accent" id="btnTrendYearly" onclick="switchTrendTimeframe('yearly')">Yearly</button>
                    </div>
                </div>
                <div style="height: 280px;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
        {{-- Appointment Status Distribution Chart --}}
        <div class="col-lg-6">
            <div class="card p-4 border-secondary bg-card shadow-sm h-100 text-start">
                <h6 class="text-accent fw-bold uppercase small mb-3"><i class="bi bi-donut-chart-fill me-1.5"></i>Appointment Status Distribution</h6>
                <div style="height: 280px;" class="d-flex justify-content-center">
                    <canvas id="statusDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Workflows & User Role Distribution --}}
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
        {{-- System Account Distribution Chart --}}
        <div class="col-lg-5">
            <div class="card p-4 border-secondary bg-card shadow-sm h-100 text-start">
                <h6 class="text-accent fw-bold uppercase small mb-3"><i class="bi bi-people-fill me-1.5"></i> System Account Distribution</h6>
                <div style="height: 280px;" class="d-flex justify-content-center">
                    <canvas id="userRolesDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- LATEST FINANCIAL TRANSACTIONS --}}
    <div class="card p-0 border-secondary bg-card shadow-lg mb-5 text-start overflow-hidden">
        {{-- Card Header --}}
        <div class="d-flex justify-content-between align-items-center p-3 px-4 border-bottom" style="background-color: var(--brand-dark); border-color: var(--border-color) !important;">
            <h5 class="text-accent fw-bold mb-0 uppercase tracking-tight small d-flex align-items-center">
                <i class="bi bi-receipt me-2 fs-5"></i>Latest Financial Transactions
            </h5>
            <div>
                <a href="{{ route('admin.reports', ['type' => 'transactions']) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase">
                    <i class="bi bi-eye-fill me-1"></i> Full Real-Time Report View &rarr;
                </a>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="transactionsTable" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.7rem;">
                    <tr>
                        <th class="ps-4 py-3">DATE (M/D/Y)</th>
                        <th>REF #</th>
                        <th>PATIENT NAME</th>
                        <th>SERVICES REQUESTED</th>
                        <th>METHOD</th>
                        <th>PAYMENT STATUS</th>
                        <th class="pe-4 text-end">AMOUNT (PHP)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        @php
                            $txDateFormatted = $tx->created_at ? $tx->created_at->format('M d, Y') : ($tx->appointment_date ? $tx->appointment_date->format('M d, Y') : 'N/A');
                            $txAmount = $tx->payment_amount ?: $tx->totalPrice();
                        @endphp
                        <tr class="border-secondary border-opacity-10 tx-row">
                            <td class="ps-4 small fw-semibold">{{ $txDateFormatted }}</td>
                            <td class="font-monospace text-accent small">#{{ $tx->id }}</td>
                            <td class="fw-bold uppercase small">{{ $tx->patient_name }}</td>
                            <td class="small text-secondary">{{ Str::limit($tx->services->pluck('name')->implode(', '), 40) }}</td>
                            <td class="small">{{ $tx->payment_method }}</td>
                            <td>
                                <span class="badge border {{ $tx->payment_status === 'paid' ? 'border-success text-success bg-success bg-opacity-10' : ($tx->payment_status === 'refunded' ? 'border-info text-info bg-info bg-opacity-10' : 'border-warning text-warning bg-warning bg-opacity-10') }} uppercase px-2 py-1 small">
                                    {{ strtoupper($tx->payment_status) }}
                                </span>
                            </td>
                            <td class="pe-4 text-end fw-bold text-accent">₱{{ number_format($txAmount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted small italic">No financial transactions recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Latest System Activity Logs --}}
    <div class="card p-0 border-secondary bg-card shadow-lg mb-5 text-start overflow-hidden">
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
    // 1. Dynamic Appointment Booking Trends Chart (Daily / Monthly / Yearly)
    const trendDataMap = {
        daily: {
            title: 'Daily',
            labels: @json($dayLabels),
            data: @json($dailyAppointmentsData)
        },
        monthly: {
            title: 'Monthly',
            labels: @json($monthLabels),
            data: @json($monthlyAppointmentsData)
        },
        yearly: {
            title: 'Yearly',
            labels: @json($yearLabels),
            data: @json($yearlyAppointmentsData)
        }
    };

    let trendChartInstance = null;

    function renderTrendChart(timeframe = 'monthly') {
        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        const tf = trendDataMap[timeframe] || trendDataMap.monthly;
        
        if (trendChartInstance) {
            trendChartInstance.destroy();
        }

        trendChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: tf.labels,
                datasets: [{
                    label: 'Bookings',
                    data: tf.data,
                    backgroundColor: 'rgba(25, 211, 140, 0.6)',
                    borderColor: '#19d38c',
                    borderWidth: 1.5,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8', precision: 0 } }
                }
            }
        });
    }

    window.switchTrendTimeframe = function(timeframe) {
        document.getElementById('trendChartTitle').innerText = trendDataMap[timeframe].title;
        ['daily', 'monthly', 'yearly'].forEach(tf => {
            const btn = document.getElementById('btnTrend' + tf.charAt(0).toUpperCase() + tf.slice(1));
            if (btn) {
                if (tf === timeframe) {
                    btn.className = 'btn btn-accent fw-bold';
                } else {
                    btn.className = 'btn btn-outline-accent';
                }
            }
        });
        renderTrendChart(timeframe);
    };

    renderTrendChart('monthly');

    // 2. Status Doughnut Chart (With High-Legibility Tooltips on Hover)
    const statusCtx = document.getElementById('statusDoughnutChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Tested', 'Encoded', 'Released', 'Returned', 'Retest', 'Canceled', 'Expired'],
            datasets: [{
                data: [
                    {{ $statusCounts['pending'] }},
                    {{ $statusCounts['approved'] }},
                    {{ $statusCounts['tested'] }},
                    {{ $statusCounts['encoded'] }},
                    {{ $statusCounts['released'] }},
                    {{ $statusCounts['returned'] }},
                    {{ $statusCounts['retest'] }},
                    {{ $statusCounts['canceled'] }},
                    {{ $statusCounts['expired'] }}
                ],
                backgroundColor: [
                    '#ffc107', '#0dcaf0', '#0d6efd', '#6610f2', 
                    '#19d38c', '#dc3545', '#fd7e14', '#6c757d', '#b02a37'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'right', 
                    labels: { color: '#94a3b8', font: { size: 12, weight: 'bold' }, padding: 10 } 
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#1c232d',
                    titleColor: '#19d38c',
                    bodyColor: '#ffffff',
                    borderColor: '#19d38c',
                    borderWidth: 1.5,
                    padding: 14,
                    boxPadding: 6,
                    usePointStyle: true,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 14, weight: 'bold' },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ` ${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // 3. User Roles Breakdown Chart (With High-Legibility Tooltips on Hover)
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
                legend: { 
                    position: 'right', 
                    labels: { color: '#94a3b8', font: { size: 12, weight: 'bold' }, padding: 10 } 
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: '#1c232d',
                    titleColor: '#19d38c',
                    bodyColor: '#ffffff',
                    borderColor: '#19d38c',
                    borderWidth: 1.5,
                    padding: 14,
                    boxPadding: 6,
                    usePointStyle: true,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 14, weight: 'bold' },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ` ${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection