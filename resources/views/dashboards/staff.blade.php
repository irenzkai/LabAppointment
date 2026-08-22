@extends('layouts.app')
@section('title', 'Staff Panel')

@section('content')
<div class="container-fluid text-start animate-page py-4">
    
    {{-- Staff Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">Staff Panel</h2>
            <p class="text-secondary small mb-0">Overview of clinical operations, appointment workflows, and patient management.</p>
        </div>
        <div>
            <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="btn btn-accent fw-bold btn-sm py-2 px-3 uppercase">
                <i class="bi bi-list-check me-1.5"></i> Open Master Queue
            </a>
        </div>
    </div>

    {{-- Management Quick Access Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('services.manage') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-accent fs-3"><i class="bi bi-list-stars"></i></div>
                    <div>
                        <div class="fw-bold text-main small uppercase">Services List</div>
                        <small class="text-secondary smaller">Add, edit & archive</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-accent fs-3"><i class="bi bi-kanban"></i></div>
                    <div>
                        <div class="fw-bold text-main small uppercase">Appointment List</div>
                        <small class="text-secondary smaller">Approval workflow</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.payment-providers.index') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-accent fs-3"><i class="bi bi-qr-code-scan"></i></div>
                    <div>
                        <div class="fw-bold text-main small uppercase">Payment QRs</div>
                        <small class="text-secondary smaller">Gateways & channels</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.appointment-settings') }}" class="card p-3 border-secondary bg-card text-decoration-none hover-card shadow-sm h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-accent fs-3"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="fw-bold text-main small uppercase">Schedule Editor</div>
                        <small class="text-secondary smaller">Operating hours & slots</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Top Metrics Summary Grid --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-4 border-accent bg-card shadow-sm h-100 text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1">Total Patient Accounts</small>
                <h2 class="text-main fw-bold mb-1">{{ $totalPatientAccounts }}</h2>
                <span class="text-muted smaller">*Includes staff/admin accounts labeled as patients</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-warning bg-card shadow-sm h-100 text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1">Appointments Needing Action</small>
                <h2 class="text-warning fw-bold mb-1">{{ $needingActionCount }}</h2>
                <span class="text-warning small fw-semibold">Pending approval, sampling, or verification</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 border-info bg-card shadow-sm h-100 text-start">
                <small class="text-secondary fw-bold uppercase fs-x-small mb-1">Completed & Released</small>
                <h2 class="text-info fw-bold mb-1">{{ $statusCounts['released'] }}</h2>
                <span class="text-info small fw-semibold">Results delivered to patients</span>
            </div>
        </div>
    </div>

    {{-- Status Breakdown Metrics (Includes Expired Status) --}}
    <div class="card p-4 border-secondary bg-card shadow-sm mb-4">
        <h6 class="text-accent fw-bold uppercase small mb-3"><i class="bi bi-pie-chart-fill me-1.5"></i> Appointment Status Overview</h6>
        <div class="row g-2 text-center">
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-secondary border-opacity-25 bg-secondary bg-opacity-10">
                    <small class="text-secondary d-block fw-bold fs-x-small uppercase">Pending</small>
                    <span class="fs-5 fw-bold text-main">{{ $statusCounts['pending'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-info border-opacity-25 bg-info bg-opacity-10">
                    <small class="text-info d-block fw-bold fs-x-small uppercase">Approved</small>
                    <span class="fs-5 fw-bold text-info">{{ $statusCounts['approved'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-info border-opacity-25 bg-info bg-opacity-10">
                    <small class="text-info d-block fw-bold fs-x-small uppercase">Tested</small>
                    <span class="fs-5 fw-bold text-info">{{ $statusCounts['tested'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-info border-opacity-25 bg-info bg-opacity-10">
                    <small class="text-info d-block fw-bold fs-x-small uppercase">Encoded</small>
                    <span class="fs-5 fw-bold text-info">{{ $statusCounts['encoded'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-success border-opacity-25 bg-success bg-opacity-10">
                    <small class="text-accent d-block fw-bold fs-x-small uppercase">Released</small>
                    <span class="fs-5 fw-bold text-accent">{{ $statusCounts['released'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-danger border-opacity-25 bg-danger bg-opacity-10">
                    <small class="text-danger d-block fw-bold fs-x-small uppercase">Returned</small>
                    <span class="fs-5 fw-bold text-danger">{{ $statusCounts['returned'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-danger border-opacity-25 bg-danger bg-opacity-10">
                    <small class="text-danger d-block fw-bold fs-x-small uppercase">Retest</small>
                    <span class="fs-5 fw-bold text-danger">{{ $statusCounts['retest'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-secondary border-opacity-25 bg-secondary bg-opacity-10">
                    <small class="text-muted d-block fw-bold fs-x-small uppercase">Canceled</small>
                    <span class="fs-5 fw-bold text-muted">{{ $statusCounts['canceled'] }}</span>
                </div>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <div class="p-2.5 rounded border border-danger border-opacity-25 bg-danger bg-opacity-10">
                    <small class="text-danger d-block fw-bold fs-x-small uppercase">Expired</small>
                    <span class="fs-5 fw-bold text-danger">{{ $statusCounts['expired'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Visual Analytics & Needing Action Table --}}
    <div class="row g-4 mb-4">
        {{-- Appointments Trend Graph with Dynamic Timeframe Selector --}}
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
        {{-- Status Distribution Chart (Redesigned Hover Tooltips) --}}
        <div class="col-lg-6">
            <div class="card p-4 border-secondary bg-card shadow-sm h-100 text-start">
                <h6 class="text-accent fw-bold uppercase small mb-3"><i class="bi bi-donut-chart-fill me-1.5"></i>Status Distribution Breakdown</h6>
                <div style="height: 280px;" class="d-flex justify-content-center">
                    <canvas id="statusDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Appointments Needing Action Table --}}
    <div class="card p-0 border-secondary bg-card shadow-sm mb-5 text-start overflow-hidden">
        <div class="p-3 bg-brand-dark border-bottom border-secondary d-flex justify-content-between align-items-center">
            <h6 class="text-accent fw-bold mb-0 uppercase small"><i class="bi bi-exclamation-circle-fill me-1.5"></i> Appointments Needing Action (Latest)</h6>
            <a href="{{ route('appointments.index', ['view' => 'queue', 'status' => 'needs_action']) }}" class="text-accent small fw-bold text-decoration-none">View All Needing Action &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.7rem;">
                    <tr>
                        <th class="ps-4 py-3">Patient / Entity</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestNeedingAction as $app)
                        <tr class="border-secondary border-opacity-10">
                            <td class="ps-4">
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
                            <td>
                                <span class="small fw-semibold">{{ $app->payment_method }} ({{ strtoupper($app->payment_status) }})</span>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('appointments.index', ['view' => 'queue', 'id' => $app->id]) }}" class="btn btn-sm btn-accent fw-bold uppercase">
                                    Process
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted small italic">No appointments currently require action.</td>
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

    // 2. Status Doughnut Chart (With Redesigned High-Legibility Tooltips on Hover)
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
});
</script>
@endpush
@endsection