@extends('layouts.app')
@section('title', 'Reports & Export Console')
@section('content')
<div class="container-fluid text-start animate-page py-4" id="reports-console-root">
    {{-- Console Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-1 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Reports & Export Console
            </h2>
            <p class="text-secondary small mb-0">Generate clinical and financial data summaries, export CSV spreadsheets, and generate formal printable records.</p>
        </div>
        <a href="{{ route('admin.panel') }}" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2 align-self-start align-self-md-center">
            <i class="bi bi-arrow-left me-1"></i> Back to Panel
        </a>
    </div>

    {{-- Navigation Tabs --}}
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3 flex-wrap" style="border-color: var(--border-color) !important;">
        <li class="nav-item">
            <a href="{{ route('admin.reports', ['type' => 'transactions']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'transactions' ? 'active' : '' }}">
                <i class="bi bi-receipt me-1.5"></i> Financial Transactions
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.reports', ['type' => 'appointments']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'appointments' ? 'active' : '' }}">
                <i class="bi bi-calendar-check me-1.5"></i> Appointment Records
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.reports', ['type' => 'services']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'services' ? 'active' : '' }}">
                <i class="bi bi-pie-chart-fill me-1.5"></i> Test / Service Records
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.reports', ['type' => 'accounts']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'accounts' ? 'active' : '' }}">
                <i class="bi bi-people-fill me-1.5"></i> Accounts Directory
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.reports', ['type' => 'logs']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'logs' ? 'active' : '' }}">
                <i class="bi bi-shield-check me-1.5"></i> System Audit Logs
            </a>
        </li>
    </ul>

    {{-- =========================================================================
         TAB 1: FINANCIAL TRANSACTIONS REPORT
         ========================================================================= --}}
    @if($type === 'transactions')
    @php
        $paidTotal = $transactions->where('payment_status', 'paid')->sum(fn($t) => $t->payment_amount ?: $t->totalPrice());
    @endphp

    {{-- Refined Filter & Action Bar --}}
    <div class="card p-3 border-secondary bg-card shadow-sm mb-4 no-print report-filter-bar">
        <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
            <input type="hidden" name="type" value="transactions">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search</label>
                    <div class="input-group input-group-sm custom-search-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="txSearchInput" class="form-control form-control-sm" placeholder="Ref #, patient, test..." onkeyup="filterReportTable('tx')">
                    </div>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Period</label>
                    <select name="tx_period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="cumulative" {{ $txPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
                        <option value="daily" {{ $txPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
                        <option value="monthly" {{ $txPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
                        <option value="yearly" {{ $txPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
                    </select>
                </div>

                @if($txPeriod === 'daily')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Date</label>
                    <input type="date" name="tx_date" class="form-control form-control-sm" value="{{ $txDate }}" onchange="this.form.submit()">
                </div>
                @elseif($txPeriod === 'monthly')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Month</label>
                    <input type="month" name="tx_month" class="form-control form-control-sm" value="{{ $txMonth }}" onchange="this.form.submit()">
                </div>
                @elseif($txPeriod === 'yearly')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Year</label>
                    <select name="tx_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $txYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                @endif

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Payment Status</label>
                    <select name="tx_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $txStatus === 'all' ? 'selected' : '' }}>All Payment States</option>
                        <option value="paid" {{ $txStatus === 'paid' ? 'selected' : '' }}>PAID</option>
                        <option value="unpaid" {{ $txStatus === 'unpaid' ? 'selected' : '' }}>UNPAID</option>
                        <option value="refunded" {{ $txStatus === 'refunded' ? 'selected' : '' }}>REFUNDED</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Appointment Status</label>
                    <select name="tx_app_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $txAppStatus === 'all' ? 'selected' : '' }}>All Workflow States</option>
                        <option value="pending" {{ $txAppStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $txAppStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="tested" {{ $txAppStatus === 'tested' ? 'selected' : '' }}>Tested</option>
                        <option value="encoded" {{ $txAppStatus === 'encoded' ? 'selected' : '' }}>Encoded</option>
                        <option value="released" {{ $txAppStatus === 'released' ? 'selected' : '' }}>Released</option>
                        <option value="retest" {{ $txAppStatus === 'retest' ? 'selected' : '' }}>Retest</option>
                        <option value="returned" {{ $txAppStatus === 'returned' ? 'selected' : '' }}>Returned</option>
                        <option value="canceled" {{ $txAppStatus === 'canceled' ? 'selected' : '' }}>Canceled</option>
                        <option value="expired" {{ $txAppStatus === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>

                <div class="col-xl-auto col-lg-auto col-12 ms-xl-auto text-end d-flex gap-2 justify-content-end pt-1">
                    <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'transactions'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm px-3 py-2 d-inline-flex align-items-center gap-2" onclick="triggerPrint()">
                        <i class="bi bi-printer-fill"></i>
                        <span>Print Preview</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Main Table Container --}}
    <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
        {{-- Print Letterhead Block (Visible only when printed) --}}
        <div class="print-header-block d-none p-3 text-center border-bottom border-dark mb-3">
            <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
            <h6 class="text-secondary uppercase mb-1">Official Financial & Transaction Audit Report</h6>
            <small class="text-muted">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary small text-muted">
                <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
                <div><strong>Filter:</strong> {{ strtoupper($txPeriod) }} | <strong>Entries:</strong> <span id="txEntryCountPrint">{{ $transactions->count() }}</span></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table" id="txReportTable" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="text-center cb-col" style="width: 38px;">
                            <input type="checkbox" class="form-check-input select-all-cb" id="selectAllTx" checked onclick="toggleSelectAllRows('tx', this)">
                        </th>
                        <th style="width: 13%;">Date (M/D/Y)</th>
                        <th style="width: 8%;">Ref #</th>
                        <th style="width: 20%;">Patient Name</th>
                        <th style="width: 23%;">Services Requested</th>
                        <th style="width: 10%;">Method</th>
                        <th style="width: 12%;">Workflow</th>
                        <th style="width: 10%;">Payment</th>
                        <th class="text-end pe-4" style="width: 12%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    @php
                        $amt = $tx->payment_amount ?: $tx->totalPrice();
                        $isPaid = ($tx->payment_status === 'paid');
                        $isExpired = $tx->isExpired();
                        $appStatus = $isExpired ? 'expired' : $tx->status;
                    @endphp
                    <tr class="report-row tx-row border-secondary border-opacity-10" data-amount="{{ $amt }}" data-paid="{{ $isPaid ? '1' : '0' }}">
                        <td class="text-center cb-col">
                            <input type="checkbox" class="form-check-input row-cb tx-cb" checked onchange="updateReportTotals('tx')">
                        </td>
                        <td>{{ $tx->appointment_date ? $tx->appointment_date->format('M d, Y') : $tx->created_at->format('M d, Y') }}</td>
                        <td class="font-monospace text-accent small">#{{ $tx->id }}</td>
                        <td>
                            <div class="fw-bold uppercase">{{ $tx->patient_name }}</div>
                            @if($tx->batch_id)
                                <small class="text-muted fs-x-small">BATCH: #{{ $tx->batch_id }}</small>
                            @endif
                        </td>
                        <td class="small text-secondary">{{ $tx->services->pluck('name')->implode(', ') }}</td>
                        <td class="small">{{ $tx->payment_method }}</td>
                        <td>
                            <span class="badge border border-{{ $appStatus === 'released' ? 'success' : ($appStatus === 'expired' ? 'danger' : 'warning') }} text-{{ $appStatus === 'released' ? 'success' : ($appStatus === 'expired' ? 'danger' : 'warning') }} uppercase px-2 py-1 small">
                                {{ strtoupper($appStatus) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $isPaid ? 'bg-success text-white' : ($tx->payment_status === 'refunded' ? 'bg-info text-dark' : 'bg-warning text-dark') }} px-2 py-1 small">
                                {{ strtoupper($tx->payment_status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4 fw-bold text-accent">₱{{ number_format($amt, 2) }}</td>
                    </tr>
                    @empty
                    <tr class="no-records-row">
                        <td colspan="9" class="text-center py-5 text-muted italic">No matching transactions found for selected filters.</td>
                    </tr>
                    @endforelse
                    <tr class="table-light fw-bold summary-total-row">
                        <td class="cb-col text-center"></td>
                        <td colspan="7" class="text-end uppercase">Total Paid Revenue (Selected Rows):</td>
                        <td class="text-end pe-4 text-success fw-bold" id="txTotalPaidDisplay">₱{{ number_format($paidTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Printable Signature Block --}}
        <div class="print-footer-block d-none justify-content-between align-items-end mt-4 pt-3 border-top border-dark">
            <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
            <div class="text-center border-top border-dark pt-1" style="width: 220px;">
                <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 2: APPOINTMENTS MASTER REPORT
         ========================================================================= --}}
    @if($type === 'appointments')

    {{-- Refined Filter & Action Bar --}}
    <div class="card p-3 border-secondary bg-card shadow-sm mb-4 no-print report-filter-bar">
        <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
            <input type="hidden" name="type" value="appointments">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search</label>
                    <div class="input-group input-group-sm custom-search-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="appSearchInput" class="form-control form-control-sm" placeholder="Search patient, ID, batch..." onkeyup="filterReportTable('app')">
                    </div>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Period</label>
                    <select name="app_period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="cumulative" {{ $appPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
                        <option value="daily" {{ $appPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
                        <option value="monthly" {{ $appPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
                        <option value="yearly" {{ $appPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
                    </select>
                </div>

                @if($appPeriod === 'daily')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Date</label>
                    <input type="date" name="app_date" class="form-control form-control-sm" value="{{ $appDate }}" onchange="this.form.submit()">
                </div>
                @elseif($appPeriod === 'monthly')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Month</label>
                    <input type="month" name="app_month" class="form-control form-control-sm" value="{{ $appMonth }}" onchange="this.form.submit()">
                </div>
                @elseif($appPeriod === 'yearly')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Year</label>
                    <select name="app_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $appYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                @endif

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Booking Type</label>
                    <select name="app_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $appType === 'all' ? 'selected' : '' }}>All Booking Types</option>
                        <option value="self" {{ $appType === 'self' ? 'selected' : '' }}>Myself / Individual</option>
                        <option value="dependent" {{ $appType === 'dependent' ? 'selected' : '' }}>Family Dependent</option>
                        <option value="bulk" {{ $appType === 'bulk' ? 'selected' : '' }}>Bulk / Corporate</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Appointment Status</label>
                    <select name="app_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $appStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="pending" {{ $appStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $appStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="tested" {{ $appStatus === 'tested' ? 'selected' : '' }}>Tested</option>
                        <option value="encoded" {{ $appStatus === 'encoded' ? 'selected' : '' }}>Encoded</option>
                        <option value="released" {{ $appStatus === 'released' ? 'selected' : '' }}>Released</option>
                        <option value="retest" {{ $appStatus === 'retest' ? 'selected' : '' }}>Retest Required</option>
                        <option value="returned" {{ $appStatus === 'returned' ? 'selected' : '' }}>Returned</option>
                        <option value="canceled" {{ $appStatus === 'canceled' ? 'selected' : '' }}>Canceled</option>
                        <option value="expired" {{ $appStatus === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>

                <div class="col-xl-auto col-lg-auto col-12 ms-xl-auto text-end d-flex gap-2 justify-content-end pt-1">
                    <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'appointments'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm px-3 py-2 d-inline-flex align-items-center gap-2" onclick="triggerPrint()">
                        <i class="bi bi-printer-fill"></i>
                        <span>Print Preview</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Appointments Table --}}
    <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
        <div class="print-header-block d-none p-3 text-center border-bottom border-dark mb-3">
            <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
            <h6 class="text-secondary uppercase mb-1">Master Clinical Appointments & Schedule Log</h6>
            <small class="text-muted">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary small text-muted">
                <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
                <div><strong>Filter:</strong> {{ strtoupper($appPeriod) }} | <strong>Selected Bookings:</strong> <span id="appEntryCountPrint">{{ $appointments->count() }}</span></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table" id="appReportTable" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="text-center cb-col" style="width: 38px;">
                            <input type="checkbox" class="form-check-input select-all-cb" id="selectAllApp" checked onclick="toggleSelectAllRows('app', this)">
                        </th>
                        <th style="width: 12%;">Schedule</th>
                        <th style="width: 8%;">Ref #</th>
                        <th style="width: 20%;">Patient Name</th>
                        <th style="width: 12%;">Classification</th>
                        <th style="width: 22%;">Services</th>
                        <th style="width: 12%;">Workflow</th>
                        <th class="text-end pe-4" style="width: 14%;">Total Bill</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $app)
                    @php
                        $isExpired = $app->isExpired();
                        $finalStatus = $isExpired ? 'expired' : $app->status;
                        $statusColor = match($finalStatus) {
                            'released' => 'success',
                            'pending' => 'warning',
                            'approved', 'tested', 'encoded' => 'info',
                            'returned', 'retest', 'canceled', 'expired' => 'danger',
                            default => 'secondary'
                        };
                        $typeLabel = $app->batch_id ? 'BULK' : ($app->dependent_id ? 'DEPENDENT' : 'PERSONAL');
                    @endphp
                    <tr class="report-row app-row border-secondary border-opacity-10">
                        <td class="text-center cb-col">
                            <input type="checkbox" class="form-check-input row-cb app-cb" checked onchange="updateReportTotals('app')">
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }}</div>
                            <small class="text-accent">{{ $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : '' }}</small>
                        </td>
                        <td class="font-monospace text-accent small">#{{ $app->id }}</td>
                        <td>
                            <div class="fw-bold uppercase">{{ $app->patient_name }}</div>
                            <small class="text-muted fs-x-small">{{ $app->patient_age }} YRS / {{ strtoupper($app->patient_sex) }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2 py-1 fs-x-small">
                                {{ $typeLabel }}
                            </span>
                        </td>
                        <td class="small text-secondary">{{ $app->services->pluck('name')->implode(', ') }}</td>
                        <td>
                            <span class="badge border border-{{ $statusColor }} text-{{ $statusColor }} uppercase px-2 py-1 small">
                                {{ strtoupper($finalStatus) }}
                            </span>
                        </td>
                        <td class="text-end pe-4 fw-bold text-accent">₱{{ number_format($app->payment_amount ?: $app->totalPrice(), 2) }}</td>
                    </tr>
                    @empty
                    <tr class="no-records-row">
                        <td colspan="8" class="text-center py-5 text-muted italic">No matching appointments found for selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="print-footer-block d-none justify-content-between align-items-end mt-4 pt-3 border-top border-dark">
            <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
            <div class="text-center border-top border-dark pt-1" style="width: 220px;">
                <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 3: CLINICAL TEST & SERVICE UTILIZATION REPORT
         ========================================================================= --}}
    @if($type === 'services')

    {{-- Refined Filter & Action Bar --}}
    <div class="card p-3 border-secondary bg-card shadow-sm mb-4 no-print report-filter-bar">
        <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
            <input type="hidden" name="type" value="services">
            <div class="row g-3 align-items-end">
                <div class="col-xl-4 col-lg-5 col-md-6 col-12">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search</label>
                    <div class="input-group input-group-sm custom-search-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="svcSearchInput" class="form-control form-control-sm" placeholder="Search service name, category..." onkeyup="filterReportTable('svc')">
                    </div>
                </div>

                <div class="col-xl-3 col-lg-3 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Category</label>
                    <select name="svc_category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $svcCategory === 'all' ? 'selected' : '' }}>All Service Categories</option>
                        <option value="individual" {{ $svcCategory === 'individual' ? 'selected' : '' }}>Individual Tests</option>
                        <option value="package" {{ $svcCategory === 'package' ? 'selected' : '' }}>Health Packages</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Status</label>
                    <select name="svc_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $svcStatus === 'all' ? 'selected' : '' }}>All States</option>
                        <option value="active" {{ $svcStatus === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="disabled" {{ $svcStatus === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        <option value="archived" {{ $svcStatus === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                <div class="col-xl-auto col-lg-auto col-12 ms-xl-auto text-end d-flex gap-2 justify-content-end pt-1">
                    <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'services'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm px-3 py-2 d-inline-flex align-items-center gap-2" onclick="triggerPrint()">
                        <i class="bi bi-printer-fill"></i>
                        <span>Print Preview</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Services Table --}}
    <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
        <div class="print-header-block d-none p-3 text-center border-bottom border-dark mb-3">
            <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
            <h6 class="text-secondary uppercase mb-1">Diagnostic Services & Test Utilization Summary</h6>
            <small class="text-muted">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary small text-muted">
                <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
                <div><strong>Total Tests Listed:</strong> {{ $services->count() }}</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table" id="svcReportTable" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="text-center cb-col" style="width: 38px;">
                            <input type="checkbox" class="form-check-input select-all-cb" id="selectAllSvc" checked onclick="toggleSelectAllRows('svc', this)">
                        </th>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 25%;">Service Name</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 12%;">Price (PHP)</th>
                        <th style="width: 14%;">Duration</th>
                        <th style="width: 12%;">Times Booked</th>
                        <th class="text-end pe-4" style="width: 14%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $svc)
                    <tr class="report-row svc-row border-secondary border-opacity-10">
                        <td class="text-center cb-col">
                            <input type="checkbox" class="form-check-input row-cb svc-cb" checked onchange="updateReportTotals('svc')">
                        </td>
                        <td class="font-monospace text-accent small">#{{ $svc->id }}</td>
                        <td class="fw-bold uppercase">{{ $svc->name }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2 py-1 fs-x-small">
                                {{ strtoupper($svc->category) }}
                            </span>
                        </td>
                        <td class="fw-bold text-accent">₱{{ number_format($svc->price, 2) }}</td>
                        <td class="small">{{ $svc->estimated_time }} mins</td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1 fw-bold">
                                {{ $svc->appointments_count }} Bookings
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            @if($svc->trashed())
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">ARCHIVED</span>
                            @elseif($svc->is_available)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">ACTIVE</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25">DISABLED</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr class="no-records-row">
                        <td colspan="8" class="text-center py-5 text-muted italic">No matching services found for selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="print-footer-block d-none justify-content-between align-items-end mt-4 pt-3 border-top border-dark">
            <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
            <div class="text-center border-top border-dark pt-1" style="width: 220px;">
                <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 4: ACCOUNTS DIRECTORY REPORT
         ========================================================================= --}}
    @if($type === 'accounts')

    {{-- Refined Filter & Action Bar --}}
    <div class="card p-3 border-secondary bg-card shadow-sm mb-4 no-print report-filter-bar">
        <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
            <input type="hidden" name="type" value="accounts">
            <div class="row g-3 align-items-end">
                <div class="col-xl-4 col-lg-5 col-md-6 col-12">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search</label>
                    <div class="input-group input-group-sm custom-search-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="accSearchInput" class="form-control form-control-sm" placeholder="Search ID, name, email, phone..." onkeyup="filterReportTable('acc')">
                    </div>
                </div>

                <div class="col-xl-3 col-lg-3 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Role Filter</label>
                    <select name="acc_role" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $accRole === 'all' ? 'selected' : '' }}>All System Accounts</option>
                        <option value="patients" {{ $accRole === 'patients' ? 'selected' : '' }}>Patients Only</option>
                        <option value="employees" {{ $accRole === 'employees' ? 'selected' : '' }}>Employees & Lab Techs</option>
                        <option value="admins" {{ $accRole === 'admins' ? 'selected' : '' }}>Administrators Only</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Status</label>
                    <select name="acc_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $accStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="active" {{ $accStatus === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="deactivated" {{ $accStatus === 'deactivated' ? 'selected' : '' }}>Deactivated Only</option>
                    </select>
                </div>

                <div class="col-xl-auto col-lg-auto col-12 ms-xl-auto text-end d-flex gap-2 justify-content-end pt-1">
                    <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'accounts'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm px-3 py-2 d-inline-flex align-items-center gap-2" onclick="triggerPrint()">
                        <i class="bi bi-printer-fill"></i>
                        <span>Print Preview</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Accounts Table --}}
    <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
        <div class="print-header-block d-none p-3 text-center border-bottom border-dark mb-3">
            <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
            <h6 class="text-secondary uppercase mb-1">User Accounts & Access Registry Report</h6>
            <small class="text-muted">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary small text-muted">
                <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
                <div><strong>Role Filter:</strong> {{ strtoupper($accRole) }} | <strong>Total Accounts:</strong> <span id="accEntryCountPrint">{{ $accounts->count() }}</span></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table" id="accReportTable" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="text-center cb-col" style="width: 38px;">
                            <input type="checkbox" class="form-check-input select-all-cb" id="selectAllAcc" checked onclick="toggleSelectAllRows('acc', this)">
                        </th>
                        <th style="width: 8%;">User ID</th>
                        <th style="width: 22%;">Full Name</th>
                        <th style="width: 25%;">Email Address</th>
                        <th style="width: 15%;">Phone Number</th>
                        <th style="width: 12%;">Access Role</th>
                        <th style="width: 8%;">Status</th>
                        <th class="text-end pe-4" style="width: 10%;">Registered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                    <tr class="report-row acc-row border-secondary border-opacity-10">
                        <td class="text-center cb-col">
                            <input type="checkbox" class="form-check-input row-cb acc-cb" checked onchange="updateReportTotals('acc')">
                        </td>
                        <td class="font-monospace text-accent small">#{{ $acc->id }}</td>
                        <td class="fw-bold uppercase">{{ $acc->name }}</td>
                        <td class="small">{{ $acc->email }}</td>
                        <td class="small">{{ $acc->phone }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2 py-1 fs-x-small">
                                {{ strtoupper($acc->role) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $acc->trashed() ? 'bg-danger text-white' : 'bg-success text-white' }} px-2 py-1 small">
                                {{ $acc->trashed() ? 'DEACTIVATED' : 'ACTIVE' }}
                            </span>
                        </td>
                        <td class="text-end pe-4 small text-muted">{{ $acc->created_at ? $acc->created_at->format('M d, Y') : 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr class="no-records-row">
                        <td colspan="8" class="text-center py-5 text-muted italic">No matching user accounts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="print-footer-block d-none justify-content-between align-items-end mt-4 pt-3 border-top border-dark">
            <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
            <div class="text-center border-top border-dark pt-1" style="width: 220px;">
                <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 5: SYSTEM AUDIT LOGS REPORT
         ========================================================================= --}}
    @if($type === 'logs')

    {{-- Refined Filter & Action Bar --}}
    <div class="card p-3 border-secondary bg-card shadow-sm mb-4 no-print report-filter-bar">
        <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
            <input type="hidden" name="type" value="logs">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search</label>
                    <div class="input-group input-group-sm custom-search-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="logSearchInput" class="form-control form-control-sm" placeholder="Performer, action, patient..." onkeyup="filterReportTable('log')">
                    </div>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Period</label>
                    <select name="log_period" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="cumulative" {{ $logPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
                        <option value="daily" {{ $logPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
                        <option value="monthly" {{ $logPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
                        <option value="yearly" {{ $logPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
                    </select>
                </div>

                @if($logPeriod === 'daily')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Date</label>
                    <input type="date" name="log_date" class="form-control form-control-sm" value="{{ $logDate }}" onchange="this.form.submit()">
                </div>
                @elseif($logPeriod === 'monthly')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Month</label>
                    <input type="month" name="log_month" class="form-control form-control-sm" value="{{ $logMonth }}" onchange="this.form.submit()">
                </div>
                @elseif($logPeriod === 'yearly')
                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Year</label>
                    <select name="log_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $logYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                @endif

                <div class="col-xl-2 col-lg-2 col-md-3 col-6">
                    <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Action Event</label>
                    <select name="log_category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $logCategory === 'all' ? 'selected' : '' }}>All Action Events</option>
                        <option value="VERIFIED" {{ $logCategory === 'VERIFIED' ? 'selected' : '' }}>VERIFIED</option>
                        <option value="ENCODED" {{ $logCategory === 'ENCODED' ? 'selected' : '' }}>ENCODED</option>
                        <option value="TESTED" {{ $logCategory === 'TESTED' ? 'selected' : '' }}>TESTED</option>
                        <option value="BOOKED" {{ $logCategory === 'BOOKED' ? 'selected' : '' }}>BOOKED</option>
                        <option value="ACCESS" {{ $logCategory === 'ACCESS' ? 'selected' : '' }}>SENSITIVE ACCESS</option>
                        <option value="STATUS" {{ $logCategory === 'STATUS' ? 'selected' : '' }}>STATUS CHANGES</option>
                    </select>
                </div>

                <div class="col-xl-auto col-lg-auto col-12 ms-xl-auto text-end d-flex gap-2 justify-content-end pt-1">
                    <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'logs'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm px-3 py-2 d-inline-flex align-items-center gap-2" onclick="triggerPrint()">
                        <i class="bi bi-printer-fill"></i>
                        <span>Print Preview</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- System Logs Table --}}
    <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
        <div class="print-header-block d-none p-3 text-center border-bottom border-dark mb-3">
            <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
            <h6 class="text-secondary uppercase mb-1">System Audit & Clinical Security Logs Report</h6>
            <small class="text-muted">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary small text-muted">
                <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
                <div><strong>Filter:</strong> {{ strtoupper($logPeriod) }} | <strong>Log Records:</strong> <span id="logEntryCountPrint">{{ $logs->count() }}</span></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table" id="logReportTable" style="color: var(--text-main);">
                <thead class="small uppercase bg-black text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="text-center cb-col" style="width: 38px;">
                            <input type="checkbox" class="form-check-input select-all-cb" id="selectAllLogs" checked onclick="toggleSelectAllRows('log', this)">
                        </th>
                        <th style="width: 15%;">Date & Time</th>
                        <th style="width: 18%;">Performer</th>
                        <th style="width: 18%;">Action Event</th>
                        <th style="width: 18%;">Target Patient</th>
                        <th class="pe-4" style="width: 31%;">Justification / Audit Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="report-row log-row border-secondary border-opacity-10">
                        <td class="text-center cb-col">
                            <input type="checkbox" class="form-check-input row-cb log-cb" checked onchange="updateReportTotals('log')">
                        </td>
                        <td class="fw-semibold">{{ $log->created_at ? $log->created_at->format('M d, Y h:i A') : 'N/A' }}</td>
                        <td>
                            <div class="fw-bold uppercase">{{ $log->user->name ?? 'System/Deleted' }}</div>
                            <small class="text-muted fs-x-small">({{ strtoupper($log->user->role ?? 'SYSTEM') }})</small>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25 px-2 py-1 small">{{ $log->action }}</span></td>
                        <td class="small fw-bold uppercase">{{ $log->patient_name }}</td>
                        <td class="italic text-secondary small pe-4">{{ $log->reason }}</td>
                    </tr>
                    @empty
                    <tr class="no-records-row">
                        <td colspan="6" class="text-center py-5 text-muted italic">No audit logs matching selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="print-footer-block d-none justify-content-between align-items-end mt-4 pt-3 border-top border-dark">
            <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
            <div class="text-center border-top border-dark pt-1" style="width: 220px;">
                <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
/* Controls and Filter Bar Enhancements */
.report-filter-bar {
    border-radius: 12px !important;
    background-color: var(--bg-card) !important;
    border: 1.5px solid var(--border-color) !important;
}

/* Fix text vertical alignment and eliminate padding-induced clipping in inputs & selects */
.report-filter-bar .form-control-sm,
.report-filter-bar .form-select-sm {
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    background-color: var(--bg-card) !important;
    border: 1.5px solid var(--border-color) !important;
    color: var(--text-main) !important;
    font-weight: 600;
    border-radius: 8px !important;
    box-shadow: none !important;
    transition: all 0.2s ease-in-out;
    box-sizing: border-box !important;
}

/* Explicit padding and line-height override to stop bottom text clipping */
.report-filter-bar .form-select-sm {
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
    padding-left: 0.75rem !important;
    padding-right: 2.25rem !important;
    font-size: 0.825rem !important;
    line-height: 1.5 !important;
    background-position: right 0.75rem center !important;
}

.report-filter-bar .form-control-sm {
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
    padding-left: 0.75rem !important;
    padding-right: 0.75rem !important;
    font-size: 0.825rem !important;
    line-height: 1.5 !important;
}

.report-filter-bar .form-control-sm:focus,
.report-filter-bar .form-select-sm:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}

.custom-search-group {
    height: 38px !important;
}

.custom-search-group .input-group-text {
    height: 38px !important;
    background-color: var(--bg-card) !important;
    border: 1.5px solid var(--border-color) !important;
    border-right: none !important;
    color: var(--text-muted);
    border-top-left-radius: 8px !important;
    border-bottom-left-radius: 8px !important;
    padding: 0 10px !important;
    display: flex;
    align-items: center;
}

.custom-search-group .form-control {
    height: 38px !important;
    border-left: none !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    padding-left: 4px !important;
}

.custom-search-group:focus-within .input-group-text {
    border-color: var(--brand-accent) !important;
}

.custom-search-group:focus-within .form-control {
    border-color: var(--brand-accent) !important;
}

.report-filter-bar .btn-sm {
    height: 38px !important;
    font-size: 0.75rem !important;
    letter-spacing: 0.5px;
    border-radius: 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* Screen Table & Navigation Styling */
.cb-col {
    vertical-align: middle;
}
.unselected-row {
    opacity: 0.35;
    background-color: rgba(0, 0, 0, 0.05) !important;
}
.nav-pills .nav-link {
    color: var(--text-muted) !important;
    border: 1.5px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    font-weight: 700 !important;
    transition: 0.2s ease;
}
.nav-pills .nav-link:hover {
    color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
}
.nav-pills .nav-link.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 10px rgba(25, 211, 140, 0.2) !important;
}

/* Zero-Margin Precision Print & PDF Engine Styling */
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm 12mm !important;
    }
    html, body, main, .container, .container-fluid, .card, .animate-page {
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 9.5px !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-shadow: none !important;
        border: none !important;
    }
    body * {
        visibility: hidden !important;
    }
    #printableReportCanvas, #printableReportCanvas * {
        visibility: visible !important;
    }
    #printableReportCanvas {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        background: #ffffff !important;
        color: #000000 !important;
    }
    .print-header-block,
    .print-footer-block {
        display: block !important;
    }
    .print-footer-block {
        display: flex !important;
    }
    .no-print,
    .cb-col,
    input[type="checkbox"],
    .btn,
    .input-group,
    nav,
    footer,
    .floating-controls {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    tr.unselected-row,
    tr.d-none,
    tr.search-hidden {
        display: none !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    table.report-table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        margin-bottom: 8px !important;
    }
    table.report-table th,
    table.report-table td {
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        white-space: normal !important;
        padding: 4px 6px !important;
        font-size: 8.5px !important;
        border: 1px solid #000000 !important;
        vertical-align: middle !important;
        color: #000000 !important;
        background-color: transparent !important;
    }
    table.report-table thead {
        display: table-header-group !important;
    }
    table.report-table thead th {
        background-color: #f1f5f9 !important;
        color: #000000 !important;
        font-weight: bold !important;
    }
    .badge {
        border: 1px solid #000000 !important;
        color: #000000 !important;
        background: transparent !important;
        font-size: 7.5px !important;
        font-weight: bold !important;
        padding: 1px 3px !important;
    }
    tr {
        page-break-inside: avoid !important;
    }
    .summary-total-row {
        page-break-inside: avoid !important;
        background-color: #f8fafc !important;
    }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    updateReportTotals('tx');
    updateReportTotals('app');
    updateReportTotals('svc');
    updateReportTotals('acc');
    updateReportTotals('log');
});

// Real-time Search Filter for active report table
function filterReportTable(type) {
    const input = document.getElementById(`${type}SearchInput`);
    if (!input) return;
    const query = input.value.trim().toLowerCase();
    const rows = document.querySelectorAll(`.${type}-row`);

    rows.forEach(tr => {
        const text = tr.innerText.toLowerCase();
        if (query === '' || text.includes(query)) {
            tr.classList.remove('d-none', 'search-hidden');
        } else {
            tr.classList.add('d-none', 'search-hidden');
        }
    });

    updateReportTotals(type);
}

// Master "Select All" Checkbox Toggle
function toggleSelectAllRows(type, masterCb) {
    const isChecked = masterCb.checked;
    const rows = document.querySelectorAll(`.${type}-row:not(.d-none)`);
    rows.forEach(tr => {
        const cb = tr.querySelector('.row-cb');
        if (cb) {
            cb.checked = isChecked;
        }
    });
    updateReportTotals(type);
}

// Recalculate totals and counts based on checked & visible rows
function updateReportTotals(type) {
    const rows = document.querySelectorAll(`.${type}-row:not(.d-none)`);
    let selectedCount = 0;
    let paidTotal = 0;

    rows.forEach(tr => {
        const cb = tr.querySelector('.row-cb');
        if (cb && cb.checked) {
            selectedCount++;
            tr.classList.remove('unselected-row');

            // Calculate paid transactions
            if (type === 'tx') {
                const amt = parseFloat(tr.dataset.amount || 0);
                const isPaid = tr.dataset.paid === '1';
                if (isPaid) {
                    paidTotal += amt;
                }
            }
        } else {
            tr.classList.add('unselected-row');
        }
    });

    // Update print header counts
    const countPrint = document.getElementById(`${type}EntryCountPrint`);
    if (countPrint) {
        countPrint.innerText = selectedCount;
    }

    // Update paid revenue display for Transactions tab
    if (type === 'tx') {
        const totalDisplay = document.getElementById('txTotalPaidDisplay');
        if (totalDisplay) {
            totalDisplay.innerText = '₱' + paidTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    // Sync master checkbox state
    const masterCb = document.getElementById(`selectAll${type.charAt(0).toUpperCase() + type.slice(1)}`);
    if (masterCb) {
        const activeCbs = Array.from(rows).map(tr => tr.querySelector('.row-cb')).filter(Boolean);
        masterCb.checked = activeCbs.length > 0 && activeCbs.every(cb => cb.checked);
    }
}

// Trigger Print
function triggerPrint() {
    window.print();
}
</script>
@endpush
@endsection