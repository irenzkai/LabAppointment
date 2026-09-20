@extends('layouts.app')
@section('title', 'Reports & Export Console')

@section('content')
<div class="container-fluid text-start py-4" id="reports-console-root">
    {{-- Console Top Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-1 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Reports & Export Console
            </h2>
            <p class="text-secondary small mb-0">Generate clinical and financial summaries, export CSV spreadsheets, and print official records.</p>
        </div>
        <a href="{{ route('admin.panel') }}" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2 align-self-start align-self-md-center">
            <i class="bi bi-arrow-left me-1"></i> Back to Panel
        </a>
    </div>

    {{-- Top Navigation Tabs --}}
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3 flex-wrap no-print" style="border-color: var(--border-color) !important;">
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

    <div class="row g-4 position-relative align-items-stretch">
        <div class="col-xl-3 col-lg-4 col-12 no-print sticky-sidebar-col">
            <div class="sticky-controls-sidebar">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    <h6 class="text-accent fw-bold uppercase mb-0 tracking-wider" style="font-size: 0.8rem;">
                        <i class="bi bi-sliders2 me-1.5"></i> Filter Console
                    </h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-x-small">
                        TRANSACTIONS
                    </span>
                </div>

                <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
                    <input type="hidden" name="type" value="transactions">

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search Table</label>
                        <div class="input-group input-group-sm custom-search-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="txSearchInput" class="form-control form-control-sm" placeholder="Ref #, patient, test..." onkeyup="filterReportTable('tx')">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Entries Per Page</label>
                        <select class="form-select form-select-sm" id="txPageSize" onchange="changePageSize('tx', this.value)">
                            <option value="10" selected>10 Entries</option>
                            <option value="25">25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                            <option value="-1">Show All Entries</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Date Range Period</label>
                        <select name="tx_period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="cumulative" {{ $txPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
                            <option value="daily" {{ $txPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
                            <option value="monthly" {{ $txPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
                            <option value="yearly" {{ $txPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
                        </select>
                    </div>

                    @if($txPeriod === 'daily')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Date</label>
                        <input type="date" name="tx_date" class="form-control form-control-sm" value="{{ $txDate }}" onchange="this.form.submit()">
                    </div>
                    @elseif($txPeriod === 'monthly')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Month</label>
                        <input type="month" name="tx_month" class="form-control form-control-sm" value="{{ $txMonth }}" onchange="this.form.submit()">
                    </div>
                    @elseif($txPeriod === 'yearly')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Year</label>
                        <select name="tx_year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $txYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Payment Settlement</label>
                        <select name="tx_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $txStatus === 'all' ? 'selected' : '' }}>All Payment States</option>
                            <option value="paid" {{ $txStatus === 'paid' ? 'selected' : '' }}>PAID</option>
                            <option value="unpaid" {{ $txStatus === 'unpaid' ? 'selected' : '' }}>UNPAID</option>
                            <option value="refunded" {{ $txStatus === 'refunded' ? 'selected' : '' }}>REFUNDED</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Workflow Status</label>
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

                    <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fs-x-small uppercase fw-bold text-secondary">Selected Records:</span>
                            <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="txEntryCountSidebar">{{ $transactions->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-x-small uppercase fw-bold text-secondary">Paid Revenue:</span>
                            <span class="text-accent fw-bold fs-6 text-nowrap" id="txSidebarTotalPaid">₱{{ number_format($paidTotal, 2) }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'transactions'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Export CSV</span>
                        </a>
                        <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2" onclick="triggerPrint()">
                            <i class="bi bi-printer-fill"></i>
                            <span>Print Preview</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8 col-12">
            <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-table" id="txReportTable">
                        <thead class="small uppercase bg-black text-secondary">
                            <tr>
                                <th class="text-center cb-col" style="width: 44px;">
                                    <input type="checkbox" class="form-check-input select-all-cb" id="selectAllTx" checked onclick="toggleSelectAllRows('tx', this)">
                                </th>
                                <th style="width: 12%;">Date (M/D/Y)</th>
                                <th style="width: 9%;">Ref #</th>
                                <th style="width: 19%;">Patient Name</th>
                                <th style="width: 18%;">Services Requested</th>
                                <th style="width: 12%;">Method</th>
                                <th style="width: 14%;">Workflow</th>
                                <th style="width: 12%;">Payment</th>
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
                                <td class="fw-semibold">{{ $tx->appointment_date ? $tx->appointment_date->format('M d, Y') : $tx->created_at->format('M d, Y') }}</td>
                                <td class="font-monospace text-accent small fw-bold">#{{ $tx->id }}</td>
                                <td>
                                    <div class="fw-bold uppercase tracking-tight text-main">{{ $tx->patient_name }}</div>
                                    @if($tx->batch_id)
                                        <small class="text-muted fs-x-small">BATCH: #{{ $tx->batch_id }}</small>
                                    @endif
                                </td>
                                <td class="small text-secondary">{{ $tx->services->pluck('name')->implode(', ') }}</td>
                                <td class="small fw-semibold">{{ $tx->payment_method }}</td>
                                <td>
                                    <span class="badge border border-{{ $appStatus === 'released' ? 'success' : ($appStatus === 'expired' ? 'danger' : 'warning') }} text-{{ $appStatus === 'released' ? 'success' : ($appStatus === 'expired' ? 'danger' : 'warning') }} uppercase px-2.5 py-1.5 small fw-bold">
                                        {{ strtoupper($appStatus) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $isPaid ? 'bg-success text-white' : ($tx->payment_status === 'refunded' ? 'bg-info text-dark' : 'bg-warning text-dark') }} px-2.5 py-1.5 small fw-bold">
                                        {{ strtoupper($tx->payment_status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-accent fs-6 text-nowrap">₱{{ number_format($amt, 2) }}</td>
                            </tr>
                            @empty
                            <tr class="no-records-row">
                                <td colspan="9" class="text-center py-5 text-muted italic">No matching transactions found for selected filters.</td>
                            </tr>
                            @endforelse

                            <tr class="table-light fw-bold summary-total-row">
                                <td class="cb-col text-center"></td>
                                <td colspan="6" class="text-end uppercase py-3 text-secondary" style="letter-spacing: 0.5px;">
                                    Total Paid Revenue (Selected Rows):
                                </td>
                                <td colspan="2" class="text-end pe-4 text-success fw-bold fs-5 py-3 text-nowrap" id="txTotalPaidDisplay">
                                    ₱{{ number_format($paidTotal, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-4 no-print">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="small text-muted" id="txPaginationInfo">
                            Showing <span id="txStart" class="fw-bold text-main">1</span> to <span id="txEnd" class="fw-bold text-main">10</span> of <span id="txTotal" class="fw-bold text-main">{{ $transactions->count() }}</span> entries
                        </div>
                        <ul class="pagination pagination-sm mb-0" id="txPaginationNav"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 2: APPOINTMENTS MASTER REPORT
         ========================================================================= --}}
    @if($type === 'appointments')
    <div class="row g-4 position-relative align-items-stretch">
        <div class="col-xl-3 col-lg-4 col-12 no-print sticky-sidebar-col">
            <div class="sticky-controls-sidebar">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    <h6 class="text-accent fw-bold uppercase mb-0 tracking-wider" style="font-size: 0.8rem;">
                        <i class="bi bi-sliders2 me-1.5"></i> Filter Console
                    </h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-x-small">
                        APPOINTMENTS
                    </span>
                </div>

                <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
                    <input type="hidden" name="type" value="appointments">

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search Table</label>
                        <div class="input-group input-group-sm custom-search-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="appSearchInput" class="form-control form-control-sm" placeholder="Search patient, ID, batch..." onkeyup="filterReportTable('app')">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Entries Per Page</label>
                        <select class="form-select form-select-sm" id="appPageSize" onchange="changePageSize('app', this.value)">
                            <option value="10" selected>10 Entries</option>
                            <option value="25">25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                            <option value="-1">Show All Entries</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Date Range Period</label>
                        <select name="app_period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="cumulative" {{ $appPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
                            <option value="daily" {{ $appPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
                            <option value="monthly" {{ $appPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
                            <option value="yearly" {{ $appPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
                        </select>
                    </div>

                    @if($appPeriod === 'daily')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Date</label>
                        <input type="date" name="app_date" class="form-control form-control-sm" value="{{ $appDate }}" onchange="this.form.submit()">
                    </div>
                    @elseif($appPeriod === 'monthly')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Month</label>
                        <input type="month" name="app_month" class="form-control form-control-sm" value="{{ $appMonth }}" onchange="this.form.submit()">
                    </div>
                    @elseif($appPeriod === 'yearly')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Year</label>
                        <select name="app_year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $appYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Booking Type</label>
                        <select name="app_type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $appType === 'all' ? 'selected' : '' }}>All Booking Types</option>
                            <option value="self" {{ $appType === 'self' ? 'selected' : '' }}>Myself / Individual</option>
                            <option value="dependent" {{ $appType === 'dependent' ? 'selected' : '' }}>Family Dependent</option>
                            <option value="bulk" {{ $appType === 'bulk' ? 'selected' : '' }}>Bulk / Corporate</option>
                        </select>
                    </div>

                    <div class="mb-4">
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

                    <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-x-small uppercase fw-bold text-secondary">Selected Bookings:</span>
                            <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="appEntryCountSidebar">{{ $appointments->count() }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'appointments'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Export CSV</span>
                        </a>
                        <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2" onclick="triggerPrint()">
                            <i class="bi bi-printer-fill"></i>
                            <span>Print Preview</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8 col-12">
            <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-table" id="appReportTable">
                        <thead class="small uppercase bg-black text-secondary">
                            <tr>
                                <th class="text-center cb-col" style="width: 44px;">
                                    <input type="checkbox" class="form-check-input select-all-cb" id="selectAllApp" checked onclick="toggleSelectAllRows('app', this)">
                                </th>
                                <th style="width: 14%;">Schedule</th>
                                <th style="width: 9%;">Ref #</th>
                                <th style="width: 21%;">Patient Name</th>
                                <th style="width: 12%;">Type</th>
                                <th style="width: 19%;">Services</th>
                                <th style="width: 13%;">Workflow</th>
                                <th class="text-end pe-4" style="width: 12%;">Total Bill</th>
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
                                    <div class="fw-semibold text-main">{{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }}</div>
                                    <small class="text-accent fw-bold">{{ $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : '' }}</small>
                                </td>
                                <td class="font-monospace text-accent small fw-bold">#{{ $app->id }}</td>
                                <td>
                                    <div class="fw-bold uppercase tracking-tight text-main">{{ $app->patient_name }}</div>
                                    <small class="text-muted fs-x-small">{{ $app->patient_age }} YRS / {{ strtoupper($app->patient_sex) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2.5 py-1.5 fs-x-small fw-bold">
                                        {{ $typeLabel }}
                                    </span>
                                </td>
                                <td class="small text-secondary">{{ $app->services->pluck('name')->implode(', ') }}</td>
                                <td>
                                    <span class="badge border border-{{ $statusColor }} text-{{ $statusColor }} uppercase px-2.5 py-1.5 small fw-bold">
                                        {{ strtoupper($finalStatus) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-accent fs-6 text-nowrap">₱{{ number_format($app->payment_amount ?: $app->totalPrice(), 2) }}</td>
                            </tr>
                            @empty
                            <tr class="no-records-row">
                                <td colspan="8" class="text-center py-5 text-muted italic">No matching appointments found for selected filters.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-4 no-print">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="small text-muted" id="appPaginationInfo">
                            Showing <span id="appStart" class="fw-bold text-main">1</span> to <span id="appEnd" class="fw-bold text-main">10</span> of <span id="appTotal" class="fw-bold text-main">{{ $appointments->count() }}</span> entries
                        </div>
                        <ul class="pagination pagination-sm mb-0" id="appPaginationNav"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 3: CLINICAL TEST & SERVICE UTILIZATION REPORT
         ========================================================================= --}}
    @if($type === 'services')
    <div class="row g-4 position-relative align-items-stretch">
        <div class="col-xl-3 col-lg-4 col-12 no-print sticky-sidebar-col">
            <div class="sticky-controls-sidebar">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    <h6 class="text-accent fw-bold uppercase mb-0 tracking-wider" style="font-size: 0.8rem;">
                        <i class="bi bi-sliders2 me-1.5"></i> Filter Console
                    </h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-x-small">
                        SERVICES
                    </span>
                </div>

                <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
                    <input type="hidden" name="type" value="services">

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search Table</label>
                        <div class="input-group input-group-sm custom-search-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="svcSearchInput" class="form-control form-control-sm" placeholder="Search service name, category..." onkeyup="filterReportTable('svc')">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Entries Per Page</label>
                        <select class="form-select form-select-sm" id="svcPageSize" onchange="changePageSize('svc', this.value)">
                            <option value="10" selected>10 Entries</option>
                            <option value="25">25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                            <option value="-1">Show All Entries</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Category</label>
                        <select name="svc_category" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $svcCategory === 'all' ? 'selected' : '' }}>All Service Categories</option>
                            <option value="individual" {{ $svcCategory === 'individual' ? 'selected' : '' }}>Individual Tests</option>
                            <option value="package" {{ $svcCategory === 'package' ? 'selected' : '' }}>Health Packages</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Catalog Status</label>
                        <select name="svc_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $svcStatus === 'all' ? 'selected' : '' }}>All States</option>
                            <option value="active" {{ $svcStatus === 'active' ? 'selected' : '' }}>Active Only</option>
                            <option value="disabled" {{ $svcStatus === 'disabled' ? 'selected' : '' }}>Disabled</option>
                            <option value="archived" {{ $svcStatus === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>

                    <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-x-small uppercase fw-bold text-secondary">Selected Tests:</span>
                            <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="svcEntryCountSidebar">{{ $services->count() }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'services'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Export CSV</span>
                        </a>
                        <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2" onclick="triggerPrint()">
                            <i class="bi bi-printer-fill"></i>
                            <span>Print Preview</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8 col-12">
            <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-table" id="svcReportTable">
                        <thead class="small uppercase bg-black text-secondary">
                            <tr>
                                <th class="text-center cb-col" style="width: 44px;">
                                    <input type="checkbox" class="form-check-input select-all-cb" id="selectAllSvc" checked onclick="toggleSelectAllRows('svc', this)">
                                </th>
                                <th style="width: 8%;">ID</th>
                                <th style="width: 26%;">Service Name</th>
                                <th style="width: 15%;">Category</th>
                                <th style="width: 12%;">Price (PHP)</th>
                                <th style="width: 13%;">Duration</th>
                                <th style="width: 14%;">Times Booked</th>
                                <th class="text-end pe-4" style="width: 12%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $svc)
                            <tr class="report-row svc-row border-secondary border-opacity-10">
                                <td class="text-center cb-col">
                                    <input type="checkbox" class="form-check-input row-cb svc-cb" checked onchange="updateReportTotals('svc')">
                                </td>
                                <td class="font-monospace text-accent small fw-bold">#{{ $svc->id }}</td>
                                <td class="fw-bold uppercase tracking-tight text-main">{{ $svc->name }}</td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2.5 py-1.5 fs-x-small fw-bold">
                                        {{ strtoupper($svc->category) }}
                                    </span>
                                </td>
                                <td class="fw-bold text-accent fs-6 text-nowrap">₱{{ number_format($svc->price, 2) }}</td>
                                <td class="small">{{ $svc->estimated_time }} mins</td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1.5 fw-bold">
                                        {{ $svc->appointments_count }} Bookings
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    @if($svc->trashed())
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1.5 fw-bold">ARCHIVED</span>
                                    @elseif($svc->is_available)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 fw-bold">ACTIVE</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25 px-2.5 py-1.5 fw-bold">DISABLED</span>
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

                <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-4 no-print">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="small text-muted" id="svcPaginationInfo">
                            Showing <span id="svcStart" class="fw-bold text-main">1</span> to <span id="svcEnd" class="fw-bold text-main">10</span> of <span id="svcTotal" class="fw-bold text-main">{{ $services->count() }}</span> entries
                        </div>
                        <ul class="pagination pagination-sm mb-0" id="svcPaginationNav"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 4: ACCOUNTS DIRECTORY REPORT
         ========================================================================= --}}
    @if($type === 'accounts')
    <div class="row g-4 position-relative align-items-stretch">
        <div class="col-xl-3 col-lg-4 col-12 no-print sticky-sidebar-col">
            <div class="sticky-controls-sidebar">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    <h6 class="text-accent fw-bold uppercase mb-0 tracking-wider" style="font-size: 0.8rem;">
                        <i class="bi bi-sliders2 me-1.5"></i> Filter Console
                    </h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-x-small">
                        ACCOUNTS
                    </span>
                </div>

                <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
                    <input type="hidden" name="type" value="accounts">

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search Table</label>
                        <div class="input-group input-group-sm custom-search-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="accSearchInput" class="form-control form-control-sm" placeholder="Search ID, name, email..." onkeyup="filterReportTable('acc')">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Entries Per Page</label>
                        <select class="form-select form-select-sm" id="accPageSize" onchange="changePageSize('acc', this.value)">
                            <option value="10" selected>10 Entries</option>
                            <option value="25">25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                            <option value="-1">Show All Entries</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Access Role</label>
                        <select name="acc_role" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $accRole === 'all' ? 'selected' : '' }}>All System Accounts</option>
                            <option value="patients" {{ $accRole === 'patients' ? 'selected' : '' }}>Patients Only</option>
                            <option value="employees" {{ $accRole === 'employees' ? 'selected' : '' }}>Employees & Lab Techs</option>
                            <option value="admins" {{ $accRole === 'admins' ? 'selected' : '' }}>Administrators Only</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Account State</label>
                        <select name="acc_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ $accStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="active" {{ $accStatus === 'active' ? 'selected' : '' }}>Active Only</option>
                            <option value="deactivated" {{ $accStatus === 'deactivated' ? 'selected' : '' }}>Deactivated Only</option>
                        </select>
                    </div>

                    <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-x-small uppercase fw-bold text-secondary">Selected Accounts:</span>
                            <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="accEntryCountSidebar">{{ $accounts->count() }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'accounts'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Export CSV</span>
                        </a>
                        <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2" onclick="triggerPrint()">
                            <i class="bi bi-printer-fill"></i>
                            <span>Print Preview</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8 col-12">
            <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-table" id="accReportTable">
                        <thead class="small uppercase bg-black text-secondary">
                            <tr>
                                <th class="text-center cb-col" style="width: 44px;">
                                    <input type="checkbox" class="form-check-input select-all-cb" id="selectAllAcc" checked onclick="toggleSelectAllRows('acc', this)">
                                </th>
                                <th style="width: 8%;">User ID</th>
                                <th style="width: 22%;">Full Name</th>
                                <th style="width: 22%;">Email Address</th>
                                <th style="width: 15%;">Phone Number</th>
                                <th style="width: 11%;">Role</th>
                                <th style="width: 11%;">Status</th>
                                <th class="text-end pe-4" style="width: 13%;">Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $acc)
                            <tr class="report-row acc-row border-secondary border-opacity-10">
                                <td class="text-center cb-col">
                                    <input type="checkbox" class="form-check-input row-cb acc-cb" checked onchange="updateReportTotals('acc')">
                                </td>
                                <td class="font-monospace text-accent small fw-bold">#{{ $acc->id }}</td>
                                <td class="fw-bold uppercase tracking-tight text-main">{{ $acc->name }}</td>
                                <td class="small">{{ $acc->email }}</td>
                                <td class="small fw-semibold">{{ $acc->phone }}</td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2.5 py-1.5 fs-x-small fw-bold">
                                        {{ strtoupper($acc->role) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $acc->trashed() ? 'bg-danger text-white' : 'bg-success text-white' }} px-2.5 py-1.5 small fw-bold">
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

                <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-4 no-print">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="small text-muted" id="accPaginationInfo">
                            Showing <span id="accStart" class="fw-bold text-main">1</span> to <span id="accEnd" class="fw-bold text-main">10</span> of <span id="accTotal" class="fw-bold text-main">{{ $accounts->count() }}</span> entries
                        </div>
                        <ul class="pagination pagination-sm mb-0" id="accPaginationNav"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         TAB 5: SYSTEM AUDIT LOGS REPORT
         ========================================================================= --}}
    @if($type === 'logs')
    <div class="row g-4 position-relative align-items-stretch">
        <div class="col-xl-3 col-lg-4 col-12 no-print sticky-sidebar-col">
            <div class="sticky-controls-sidebar">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    <h6 class="text-accent fw-bold uppercase mb-0 tracking-wider" style="font-size: 0.8rem;">
                        <i class="bi bi-sliders2 me-1.5"></i> Filter Console
                    </h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-x-small">
                        AUDIT LOGS
                    </span>
                </div>

                <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
                    <input type="hidden" name="type" value="logs">

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search Table</label>
                        <div class="input-group input-group-sm custom-search-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="logSearchInput" class="form-control form-control-sm" placeholder="Performer, action, patient..." onkeyup="filterReportTable('log')">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Entries Per Page</label>
                        <select class="form-select form-select-sm" id="logPageSize" onchange="changePageSize('log', this.value)">
                            <option value="10" selected>10 Entries</option>
                            <option value="25">25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                            <option value="-1">Show All Entries</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Date Range Period</label>
                        <select name="log_period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="cumulative" {{ $logPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
                            <option value="daily" {{ $logPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
                            <option value="monthly" {{ $logPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
                            <option value="yearly" {{ $logPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
                        </select>
                    </div>

                    @if($logPeriod === 'daily')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Date</label>
                        <input type="date" name="log_date" class="form-control form-control-sm" value="{{ $logDate }}" onchange="this.form.submit()">
                    </div>
                    @elseif($logPeriod === 'monthly')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Month</label>
                        <input type="month" name="log_month" class="form-control form-control-sm" value="{{ $logMonth }}" onchange="this.form.submit()">
                    </div>
                    @elseif($logPeriod === 'yearly')
                    <div class="mb-3">
                        <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Specific Year</label>
                        <select name="log_year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $logYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    @endif

                    <div class="mb-4">
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

                    <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-x-small uppercase fw-bold text-secondary">Selected Logs:</span>
                            <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="logEntryCountSidebar">{{ $logs->count() }}</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'logs'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Export CSV</span>
                        </a>
                        <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2" onclick="triggerPrint()">
                            <i class="bi bi-printer-fill"></i>
                            <span>Print Preview</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8 col-12">
            <div id="printableReportCanvas" class="card p-0 border-secondary bg-card shadow-lg mb-5 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-table" id="logReportTable">
                        <thead class="small uppercase bg-black text-secondary">
                            <tr>
                                <th class="text-center cb-col" style="width: 44px;">
                                    <input type="checkbox" class="form-check-input select-all-cb" id="selectAllLogs" checked onclick="toggleSelectAllRows('log', this)">
                                </th>
                                <th style="width: 14%;">Date & Time</th>
                                <th style="width: 15%;">Performer</th>
                                <th style="width: 18%;">Action Event</th>
                                <th style="width: 15%;">Target Patient</th>
                                <th class="pe-4" style="width: 38%;">Justification / Audit Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr class="report-row log-row border-secondary border-opacity-10">
                                <td class="text-center cb-col">
                                    <input type="checkbox" class="form-check-input row-cb log-cb" checked onchange="updateReportTotals('log')">
                                </td>
                                <td>
                                    <div class="fw-semibold text-main">{{ $log->created_at ? $log->created_at->format('M d, Y') : 'N/A' }}</div>
                                    <small class="text-accent fw-bold">{{ $log->created_at ? $log->created_at->format('h:i A') : '' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold uppercase tracking-tight text-main">{{ $log->user->name ?? 'System/Deleted' }}</div>
                                    <small class="text-muted fs-x-small fw-semibold">({{ strtoupper($log->user->role ?? 'SYSTEM') }})</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small fw-bold uppercase">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold uppercase tracking-tight text-main">{{ $log->patient_name }}</div>
                                </td>
                                <td class="pe-4">
                                    <div class="log-reason-card">
                                        {{ $log->reason }}
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="no-records-row">
                                <td colspan="6" class="text-center py-5 text-muted italic">No audit logs matching selected filters.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-4 no-print">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="small text-muted" id="logPaginationInfo">
                            Showing <span id="logStart" class="fw-bold text-main">1</span> to <span id="logEnd" class="fw-bold text-main">10</span> of <span id="logTotal" class="fw-bold text-main">{{ $logs->count() }}</span> entries
                        </div>
                        <ul class="pagination pagination-sm mb-0" id="logPaginationNav"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
#reports-console-root {
    overflow: visible !important;
    animation: none !important;
    transform: none !important;
}

.sticky-sidebar-col {
    position: relative;
}

.sticky-controls-sidebar {
    position: -webkit-sticky !important;
    position: sticky !important;
    top: 90px !important;
    z-index: 100 !important;
    max-height: calc(100vh - 110px);
    overflow-y: auto;
    border-radius: 16px !important;
    background-color: var(--bg-card) !important;
    border: 1.5px solid var(--border-color) !important;
    padding: 1.25rem !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

.sticky-controls-sidebar::-webkit-scrollbar {
    width: 4px;
}
.sticky-controls-sidebar::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 4px;
}
.sticky-controls-sidebar::-webkit-scrollbar-thumb:hover {
    background: var(--brand-accent);
}

.sticky-controls-sidebar .form-control-sm,
.sticky-controls-sidebar .form-select-sm {
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    background-color: var(--bg-main) !important;
    border: 1.5px solid var(--border-color) !important;
    color: var(--text-main) !important;
    font-weight: 600;
    border-radius: 8px !important;
    box-shadow: none !important;
    transition: all 0.2s ease-in-out;
    box-sizing: border-box !important;
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
    padding-left: 0.75rem !important;
    font-size: 0.825rem !important;
    line-height: 1.5 !important;
}

.sticky-controls-sidebar .form-select-sm {
    padding-right: 2.25rem !important;
    background-position: right 0.75rem center !important;
}

.sticky-controls-sidebar .form-control-sm:focus,
.sticky-controls-sidebar .form-select-sm:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}

.custom-search-group {
    height: 38px !important;
}

.custom-search-group .input-group-text {
    height: 38px !important;
    background-color: var(--bg-main) !important;
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

.custom-search-group:focus-within .input-group-text,
.custom-search-group:focus-within .form-control {
    border-color: var(--brand-accent) !important;
}

table.report-table {
    table-layout: fixed !important;
    width: 100% !important;
    border-collapse: collapse !important;
    color: var(--text-main);
}

table.report-table thead th {
    padding: 0.95rem 1rem !important;
    font-size: 0.75rem !important;
    letter-spacing: 0.75px;
    font-weight: 800;
    border: none !important;
    background-color: var(--brand-dark) !important;
    color: #ffffff !important;
}

table.report-table tbody td {
    padding: 1rem 1rem !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    word-wrap: break-word !important;
    white-space: normal !important;
    vertical-align: middle !important;
}

.text-nowrap,
.summary-total-row td,
#txTotalPaidDisplay,
#txReportTable td.text-end,
#appReportTable td.text-end,
#svcReportTable td:nth-child(5) {
    white-space: nowrap !important;
    word-break: normal !important;
    overflow-wrap: normal !important;
}

.log-reason-card {
    background-color: var(--bg-main);
    border: 1px solid var(--border-color);
    border-left: 3px solid var(--brand-accent) !important;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.775rem;
    line-height: 1.45;
    color: var(--text-main);
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    word-wrap: break-word !important;
    white-space: normal !important;
    max-height: 130px;
    overflow-y: auto;
}

.pagination {
    margin-bottom: 0;
    gap: 4px;
}
.pagination .page-item .page-link {
    background-color: var(--bg-main) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-main) !important;
    border-radius: 6px !important;
    padding: 6px 12px !important;
    font-size: 0.75rem !important;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.pagination .page-item:not(.active):not(.disabled) .page-link:hover {
    background-color: rgba(25, 211, 140, 0.1) !important;
    border-color: var(--brand-accent) !important;
    color: var(--brand-accent) !important;
}
.pagination .page-item.active .page-link {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
    font-weight: 800 !important;
}
.pagination .page-item.disabled .page-link {
    opacity: 0.45;
    pointer-events: none;
    background-color: var(--bg-card) !important;
}

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
</style>

@push('scripts')
<script>
function getPrefix(type) {
    const map = {
        'transactions': 'tx',
        'appointments': 'app',
        'services': 'svc',
        'accounts': 'acc',
        'logs': 'log',
        'tx': 'tx',
        'app': 'app',
        'svc': 'svc',
        'acc': 'acc',
        'log': 'log'
    };
    return map[type] || type;
}

const paginationState = {
    tx:  { page: 1, pageSize: 10 },
    app: { page: 1, pageSize: 10 },
    svc: { page: 1, pageSize: 10 },
    acc: { page: 1, pageSize: 10 },
    log: { page: 1, pageSize: 10 },
};

document.addEventListener('DOMContentLoaded', () => {
    const rawType = '{{ $type }}';
    const prefix = getPrefix(rawType);
    if (prefix) {
        initPagination(prefix);
        updateReportTotals(prefix);
    }
});

function initPagination(type) {
    renderPagination(type);
}

function changePageSize(type, newSize) {
    const prefix = getPrefix(type);
    paginationState[prefix].pageSize = parseInt(newSize, 10);
    paginationState[prefix].page = 1;
    renderPagination(prefix);
}

function goToPage(type, pageNum) {
    const prefix = getPrefix(type);
    paginationState[prefix].page = pageNum;
    renderPagination(prefix);
}

function renderPagination(type) {
    const prefix = getPrefix(type);
    const state = paginationState[prefix];
    if (!state) return;

    const rows = Array.from(document.querySelectorAll(`.${prefix}-row`)).filter(tr => !tr.classList.contains('search-hidden'));
    const totalRows = rows.length;
    const pageSize = state.pageSize;
    const totalPages = pageSize === -1 ? 1 : Math.ceil(totalRows / pageSize) || 1;

    if (state.page > totalPages) state.page = totalPages;
    if (state.page < 1) state.page = 1;

    const startIdx = pageSize === -1 ? 0 : (state.page - 1) * pageSize;
    const endIdx = pageSize === -1 ? totalRows : Math.min(startIdx + pageSize, totalRows);

    rows.forEach((tr, idx) => {
        if (idx >= startIdx && idx < endIdx) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });

    const startElem = document.getElementById(`${prefix}Start`);
    const endElem = document.getElementById(`${prefix}End`);
    const totalElem = document.getElementById(`${prefix}Total`);
    if (startElem) startElem.innerText = totalRows === 0 ? 0 : startIdx + 1;
    if (endElem) endElem.innerText = endIdx;
    if (totalElem) totalElem.innerText = totalRows;

    const navElem = document.getElementById(`${prefix}PaginationNav`);
    if (!navElem) return;

    let html = '';

    html += `
        <li class="page-item ${state.page === 1 ? 'disabled' : ''}">
            <a class="page-link" onclick="goToPage('${prefix}', ${state.page - 1})" aria-label="Previous">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
    `;

    const windowSize = 2;
    const startPage = Math.max(1, state.page - windowSize);
    const endPage = Math.min(totalPages, state.page + windowSize);

    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" onclick="goToPage('${prefix}', 1)">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === state.page ? 'active' : ''}">
                <a class="page-link" onclick="goToPage('${prefix}', ${p})">${p}</a>
            </li>
        `;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" onclick="goToPage('${prefix}', ${totalPages})">${totalPages}</a></li>`;
    }

    html += `
        <li class="page-item ${state.page === totalPages || totalRows === 0 ? 'disabled' : ''}">
            <a class="page-link" onclick="goToPage('${prefix}', ${state.page + 1})" aria-label="Next">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    `;

    navElem.innerHTML = html;
}

function filterReportTable(type) {
    const prefix = getPrefix(type);
    const input = document.getElementById(`${prefix}SearchInput`);
    if (!input) return;
    const query = input.value.trim().toLowerCase();
    const rows = document.querySelectorAll(`.${prefix}-row`);

    rows.forEach(tr => {
        const text = tr.innerText.toLowerCase();
        if (query === '' || text.includes(query)) {
            tr.classList.remove('search-hidden');
        } else {
            tr.classList.add('search-hidden');
            tr.style.display = 'none';
        }
    });

    if (paginationState[prefix]) {
        paginationState[prefix].page = 1;
    }
    renderPagination(type);
    updateReportTotals(type);
}

function toggleSelectAllRows(type, masterCb) {
    const prefix = getPrefix(type);
    const isChecked = masterCb.checked;
    const rows = document.querySelectorAll(`.${prefix}-row:not(.search-hidden)`);
    rows.forEach(tr => {
        const cb = tr.querySelector('.row-cb');
        if (cb) {
            cb.checked = isChecked;
        }
    });
    updateReportTotals(type);
}

function updateReportTotals(type) {
    const prefix = getPrefix(type);
    const rows = document.querySelectorAll(`.${prefix}-row:not(.search-hidden)`);
    let selectedCount = 0;
    let paidTotal = 0;

    rows.forEach(tr => {
        const cb = tr.querySelector('.row-cb');
        if (cb && cb.checked) {
            selectedCount++;
            tr.classList.remove('unselected-row');

            if (prefix === 'tx') {
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

    const countSidebar = document.getElementById(`${prefix}EntryCountSidebar`);
    if (countSidebar) {
        countSidebar.innerText = selectedCount;
    }

    if (prefix === 'tx') {
        const totalDisplay = document.getElementById('txTotalPaidDisplay');
        if (totalDisplay) {
            totalDisplay.innerText = '₱' + paidTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        const sidebarTotal = document.getElementById('txSidebarTotalPaid');
        if (sidebarTotal) {
            sidebarTotal.innerText = '₱' + paidTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    const masterCb = document.getElementById(`selectAll${prefix.charAt(0).toUpperCase() + prefix.slice(1)}`);
    if (masterCb) {
        const activeCbs = Array.from(rows).map(tr => tr.querySelector('.row-cb')).filter(Boolean);
        masterCb.checked = activeCbs.length > 0 && activeCbs.every(cb => cb.checked);
    }
}

// Opens the designer printable report in a dedicated preview tab
function triggerPrint() {
    const currentParams = new URLSearchParams(window.location.search);
    const printUrl = '{{ route("admin.reports.print") }}?' + currentParams.toString();
    window.open(printUrl, '_blank');
}
</script>
@endpush
@endsection