@extends('layouts.app')

@section('title', 'Reports & Export Console')

@section('content')
@php
    $currentUser = Auth::user();
    $allowedTypes = $allowedTypes ?? ['appointments', 'services'];
    $sortBy = request('sort_by', $sortBy ?? 'date');
    $order = strtolower(request('order', $order ?? 'desc'));
    $svcKind = request('svc_kind', $svcKind ?? 'all');

    // Helper to generate bi-directional sort URLs preserving active filters
    function getReportSortUrl($column, $currSort, $currOrder) {
        $params = request()->all();
        $params['sort_by'] = $column;
        $params['order'] = ($currSort === $column && $currOrder === 'asc') ? 'desc' : 'asc';
        return route('admin.reports', $params);
    }
@endphp

<div class="container-fluid text-start py-3 py-sm-4 px-3 px-sm-4" id="reports-console-root">
    {{-- Console Top Header --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div class="me-sm-3 flex-grow-1">
            <h2 class="text-accent fw-bold mb-1 uppercase tracking-tight fs-3 fs-md-2" style="letter-spacing: 1px;">
                <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Reports & Export Console
            </h2>
            <p class="text-secondary small mb-0">Generate clinical and financial summaries, export CSV spreadsheets, and print official records.</p>
        </div>
        <div class="flex-shrink-0 back-panel-box">
            <a href="{{ $currentUser && $currentUser->isAdmin() ? route('admin.panel') : route('staff.panel') }}" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2 text-decoration-none d-inline-flex align-items-center gap-1" style="border-radius: 8px; white-space: nowrap;">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Panel</span>
            </a>
        </div>
    </div>

    {{-- Top Navigation Tabs (Role-Based Filtered) --}}
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3 flex-wrap no-print" style="border-color: var(--border-color) !important;">
        @if(in_array('transactions', $allowedTypes))
            <li class="nav-item">
                <a href="{{ route('admin.reports', ['type' => 'transactions']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'transactions' ? 'active' : '' }}">
                    <i class="bi bi-receipt me-1.5"></i> Financial Transactions
                </a>
            </li>
        @endif

        @if(in_array('appointments', $allowedTypes))
            <li class="nav-item">
                <a href="{{ route('admin.reports', ['type' => 'appointments']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'appointments' ? 'active' : '' }}">
                    <i class="bi bi-calendar-check me-1.5"></i> Appointment Records
                </a>
            </li>
        @endif

        @if(in_array('services', $allowedTypes))
            <li class="nav-item">
                <a href="{{ route('admin.reports', ['type' => 'services']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'services' ? 'active' : '' }}">
                    <i class="bi bi-pie-chart-fill me-1.5"></i> Tests & Packages
                </a>
            </li>
        @endif

        @if(in_array('accounts', $allowedTypes))
            <li class="nav-item">
                <a href="{{ route('admin.reports', ['type' => 'accounts']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'accounts' ? 'active' : '' }}">
                    <i class="bi bi-people-fill me-1.5"></i> Accounts Directory
                </a>
            </li>
        @endif

        @if(in_array('logs', $allowedTypes))
            <li class="nav-item">
                <a href="{{ route('admin.reports', ['type' => 'logs']) }}" class="nav-link fw-bold px-3 py-2 uppercase {{ $type === 'logs' ? 'active' : '' }}">
                    <i class="bi bi-shield-check me-1.5"></i> System Audit Logs
                </a>
            </li>
        @endif
    </ul>

    {{-- =========================================================================
         TAB 1: FINANCIAL TRANSACTIONS REPORT
         ========================================================================= --}}
    @if($type === 'transactions' && in_array('transactions', $allowedTypes))
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
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="order" value="{{ $order }}">

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
                                <option value="expired" {{ in_array($txAppStatus, ['expired', 'voided']) ? 'selected' : '' }}>Voided (No-Show)</option>
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

                        <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2">
                            <button type="button" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="triggerExport('tx')">
                                <i class="bi bi-download"></i>
                                <span>Export CSV</span>
                            </button>
                            <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="openPrintSignatoryModal()">
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
                                    <th style="width: 12%; min-width: 105px;">
                                        <a href="{{ getReportSortUrl('date', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Date (M/D/Y)</span>
                                            <i class="bi {{ $sortBy === 'date' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 8%; min-width: 70px;">
                                        <a href="{{ getReportSortUrl('id', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Ref #</span>
                                            <i class="bi {{ $sortBy === 'id' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 18%; min-width: 140px;">
                                        <a href="{{ getReportSortUrl('patient', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Patient Name</span>
                                            <i class="bi {{ $sortBy === 'patient' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 20%; min-width: 150px;">Services & Packages</th>
                                    <th style="width: 10%; min-width: 90px;">Method</th>
                                    <th style="width: 11%; min-width: 100px;">
                                        <a href="{{ getReportSortUrl('status', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Workflow</span>
                                            <i class="bi {{ $sortBy === 'status' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 10%; min-width: 90px;">
                                        <a href="{{ getReportSortUrl('payment_status', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Payment</span>
                                            <i class="bi {{ $sortBy === 'payment_status' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th class="text-end pe-4" style="width: 11%; min-width: 100px;">
                                        <a href="{{ getReportSortUrl('amount', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center justify-content-end gap-1 w-100">
                                            <span>Amount</span>
                                            <i class="bi {{ $sortBy === 'amount' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $tx)
                                    @php
                                        $amt = $tx->payment_amount ?: $tx->totalPrice();
                                        $isPaid = ($tx->payment_status === 'paid');
                                        $isVoided = $tx->isVoided();
                                        $appStatus = $isVoided ? 'voided' : $tx->status;
                                        $items = $tx->services->pluck('name')
                                            ->concat($tx->packages->pluck('name')->map(fn($n) => "[PKG] {$n}"))
                                            ->implode(', ');
                                    @endphp
                                    <tr class="report-row tx-row border-secondary border-opacity-10" data-amount="{{ $amt }}" data-paid="{{ $isPaid ? '1' : '0' }}">
                                        <td class="text-center cb-col">
                                            <input type="checkbox" class="form-check-input row-cb tx-cb" value="{{ $tx->id }}" checked onchange="updateReportTotals('tx')">
                                        </td>
                                        <td class="fw-semibold">{{ $tx->appointment_date ? $tx->appointment_date->format('M d, Y') : $tx->created_at->format('M d, Y') }}</td>
                                        <td class="font-monospace text-accent small fw-bold">#{{ $tx->id }}</td>
                                        <td>
                                            <div class="fw-bold uppercase tracking-tight text-main">{{ $tx->patient_name }}</div>
                                            @if($tx->batch_id)
                                                <small class="text-muted fs-x-small">BATCH: #{{ $tx->batch_id }}</small>
                                            @endif
                                        </td>
                                        <td class="small text-secondary">{{ $items }}</td>
                                        <td class="small fw-semibold">{{ $tx->payment_method }}</td>
                                        <td>
                                            <span class="badge border border-{{ $appStatus === 'released' ? 'success' : ($appStatus === 'voided' ? 'danger' : 'warning') }} text-{{ $appStatus === 'released' ? 'success' : ($appStatus === 'voided' ? 'danger' : 'warning') }} uppercase px-2.5 py-1.5 small fw-bold">
                                                {{ $appStatus === 'voided' ? 'VOIDED' : strtoupper($appStatus) }}
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
                    <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-3 px-sm-4 no-print">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                            <div class="small text-muted text-center text-sm-start" id="txPaginationInfo">
                                Showing <span id="txStart" class="fw-bold text-main">1</span> to <span id="txEnd" class="fw-bold text-main">10</span> of <span id="txTotal" class="fw-bold text-main">{{ $transactions->count() }}</span> entries
                            </div>
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-sm-end flex-wrap" id="txPaginationNav"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         TAB 2: APPOINTMENTS MASTER REPORT
         ========================================================================= --}}
    @if($type === 'appointments' && in_array('appointments', $allowedTypes))
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
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="order" value="{{ $order }}">

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
                                <option value="needs_review" {{ $appStatus === 'needs_review' ? 'selected' : '' }}>Pending Patient Review</option>
                                <option value="approved" {{ $appStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="tested" {{ $appStatus === 'tested' ? 'selected' : '' }}>Tested</option>
                                <option value="encoded" {{ $appStatus === 'encoded' ? 'selected' : '' }}>Encoded</option>
                                <option value="released" {{ $appStatus === 'released' ? 'selected' : '' }}>Released</option>
                                <option value="retest" {{ $appStatus === 'retest' ? 'selected' : '' }}>Retest Required</option>
                                <option value="returned" {{ $appStatus === 'returned' ? 'selected' : '' }}>Returned</option>
                                <option value="canceled" {{ $appStatus === 'canceled' ? 'selected' : '' }}>Canceled</option>
                                <option value="expired" {{ in_array($appStatus, ['expired', 'voided']) ? 'selected' : '' }}>Voided (No-Show)</option>
                            </select>
                        </div>

                        <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-x-small uppercase fw-bold text-secondary">Selected Bookings:</span>
                                <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="appEntryCountSidebar">{{ $appointments->count() }}</span>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2">
                            <button type="button" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="triggerExport('app')">
                                <i class="bi bi-download"></i>
                                <span>Export CSV</span>
                            </button>
                            <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="openPrintSignatoryModal()">
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
                                    <th style="width: 13%; min-width: 110px;">
                                        <a href="{{ getReportSortUrl('date', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Schedule</span>
                                            <i class="bi {{ $sortBy === 'date' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 8%; min-width: 65px;">
                                        <a href="{{ getReportSortUrl('id', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Ref #</span>
                                            <i class="bi {{ $sortBy === 'id' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 20%; min-width: 150px;">
                                        <a href="{{ getReportSortUrl('patient', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Patient Name</span>
                                            <i class="bi {{ $sortBy === 'patient' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 10%; min-width: 90px;">Type</th>
                                    <th style="width: 21%; min-width: 160px;">Services & Packages</th>
                                    <th style="width: 13%; min-width: 100px;">
                                        <a href="{{ getReportSortUrl('status', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Workflow</span>
                                            <i class="bi {{ $sortBy === 'status' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th class="text-end pe-4" style="width: 15%; min-width: 110px;">Total Bill</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($appointments as $app)
                                    @php
                                        $isVoided = $app->isVoided();
                                        $finalStatus = $isVoided ? 'voided' : $app->status;
                                        $statusColor = match($finalStatus) {
                                            'released' => 'success',
                                            'pending' => 'warning',
                                            'needs_review' => 'warning',
                                            'approved', 'tested', 'encoded' => 'info',
                                            'returned', 'retest', 'canceled', 'expired', 'voided' => 'danger',
                                            default => 'secondary'
                                        };
                                        $typeLabel = $app->batch_id ? 'BULK' : ($app->dependent_id ? 'DEPENDENT' : 'PERSONAL');
                                        $items = $app->packages->pluck('name')->map(fn($n) => "[PKG] {$n}")
                                            ->concat($app->services->pluck('name'))
                                            ->implode(', ');
                                    @endphp
                                    <tr class="report-row app-row border-secondary border-opacity-10">
                                        <td class="text-center cb-col">
                                            <input type="checkbox" class="form-check-input row-cb app-cb" value="{{ $app->id }}" checked onchange="updateReportTotals('app')">
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
                                        <td class="small text-secondary">{{ $items }}</td>
                                        <td>
                                            <span class="badge border border-{{ $statusColor }} text-{{ $statusColor }} uppercase px-2.5 py-1.5 small fw-bold">
                                                {{ $finalStatus === 'voided' ? 'VOIDED' : strtoupper($finalStatus) }}
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
                    <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-3 px-sm-4 no-print">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                            <div class="small text-muted text-center text-sm-start" id="appPaginationInfo">
                                Showing <span id="appStart" class="fw-bold text-main">1</span> to <span id="appEnd" class="fw-bold text-main">10</span> of <span id="appTotal" class="fw-bold text-main">{{ $appointments->count() }}</span> entries
                            </div>
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-sm-end flex-wrap" id="appPaginationNav"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         TAB 3: CLINICAL TEST & PACKAGE UTILIZATION REPORT
         ========================================================================= --}}
    @if($type === 'services' && in_array('services', $allowedTypes))
        @php
            $displayPackages = ($svcKind !== 'individual');
            $displayServices = ($svcKind !== 'package');
            $pkgCount = $displayPackages ? $packages->count() : 0;
            $svcCount = $displayServices ? $services->count() : 0;
            $combinedTotalRecords = $pkgCount + $svcCount;
        @endphp

        <div class="row g-4 position-relative align-items-stretch">
            <div class="col-xl-3 col-lg-4 col-12 no-print sticky-sidebar-col">
                <div class="sticky-controls-sidebar">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <h6 class="text-accent fw-bold uppercase mb-0 tracking-wider" style="font-size: 0.8rem;">
                            <i class="bi bi-sliders2 me-1.5"></i> Filter Console
                        </h6>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-x-small">
                            SERVICES & PACKAGES
                        </span>
                    </div>

                    <form action="{{ route('admin.reports') }}" method="GET" class="m-0">
                        <input type="hidden" name="type" value="services">
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="order" value="{{ $order }}">

                        <div class="mb-3">
                            <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Search Table</label>
                            <div class="input-group input-group-sm custom-search-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="svcSearchInput" class="form-control form-control-sm" placeholder="Search service name..." onkeyup="filterReportTable('svc')">
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

                        {{-- CLASSIFICATION / TYPE FILTER --}}
                        <div class="mb-3">
                            <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Classification / Type</label>
                            <select name="svc_kind" id="svcKindSelect" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ $svcKind === 'all' ? 'selected' : '' }}>All (Individual & Packages)</option>
                                <option value="individual" {{ $svcKind === 'individual' ? 'selected' : '' }}>Individual Tests Only</option>
                                <option value="package" {{ $svcKind === 'package' ? 'selected' : '' }}>Packages Only</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label smaller fw-bold uppercase mb-1 text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Catalog Status</label>
                            <select name="svc_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ ($svcStatus ?? 'all') === 'all' ? 'selected' : '' }}>All States</option>
                                <option value="active" {{ ($svcStatus ?? '') === 'active' ? 'selected' : '' }}>Active Only</option>
                                <option value="disabled" {{ ($svcStatus ?? '') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                <option value="archived" {{ ($svcStatus ?? '') === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>

                        <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fs-x-small uppercase fw-bold text-secondary">Selected Items:</span>
                                <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="svcEntryCountSidebar">{{ $combinedTotalRecords }}</span>
                            </div>
                            @if($displayServices)
                                <div class="d-flex justify-content-between align-items-center {{ $displayPackages ? 'mb-2' : '' }}">
                                    <span class="fs-x-small uppercase fw-bold text-secondary">Services:</span>
                                    <span class="badge bg-secondary bg-opacity-25 text-main fw-bold">{{ $services->count() }}</span>
                                </div>
                            @endif
                            @if($displayPackages)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-x-small uppercase fw-bold text-secondary">Packages:</span>
                                    <span class="badge bg-secondary bg-opacity-25 text-main fw-bold">{{ $packages->count() }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2">
                            <button type="button" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="triggerExport('svc')">
                                <i class="bi bi-download"></i>
                                <span>Export CSV</span>
                            </button>
                            <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="openPrintSignatoryModal()">
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
                                    <th style="width: 8%; min-width: 70px;">ID</th>
                                    <th style="width: 26%; min-width: 180px;">
                                        <a href="{{ getReportSortUrl('name', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Test / Package Name</span>
                                            <i class="bi {{ $sortBy === 'name' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 14%; min-width: 110px;">Classification</th>
                                    <th style="width: 14%; min-width: 100px;">
                                        <a href="{{ getReportSortUrl('price', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Price (PHP)</span>
                                            <i class="bi {{ $sortBy === 'price' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 12%; min-width: 95px;">Duration</th>
                                    <th style="width: 13%; min-width: 100px;">
                                        <a href="{{ getReportSortUrl('bookings', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Bookings</span>
                                            <i class="bi {{ $sortBy === 'bookings' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th class="text-end pe-4" style="width: 13%; min-width: 100px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Diagnostic Packages --}}
                                @if($displayPackages && isset($packages) && $packages->isNotEmpty())
                                    @foreach($packages as $pkg)
                                        <tr class="report-row svc-row border-secondary border-opacity-10" data-item-type="package">
                                            <td class="text-center cb-col">
                                                <input type="checkbox" class="form-check-input row-cb svc-cb" value="pkg_{{ $pkg->id }}" checked onchange="updateReportTotals('svc')">
                                            </td>
                                            <td class="font-monospace text-accent small fw-bold">P-{{ $pkg->id }}</td>
                                            <td class="fw-bold uppercase tracking-tight text-main">
                                                {{ $pkg->name }}
                                                <small class="text-muted d-block fs-x-small">Includes: {{ $pkg->services->pluck('name')->implode(', ') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-success bg-opacity-15 text-accent border border-success border-opacity-30 uppercase px-2.5 py-1.5 fs-x-small fw-bold">
                                                    PACKAGE
                                                </span>
                                            </td>
                                            <td class="fw-bold text-accent fs-6 text-nowrap">₱{{ number_format($pkg->price, 2) }}</td>
                                            <td class="small">{{ $pkg->estimated_time ?: 15 }} mins</td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1.5 fw-bold">
                                                    {{ $pkg->appointments_count ?? 0 }} Bookings
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                @if($pkg->trashed())
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1.5 fw-bold">ARCHIVED</span>
                                                @elseif($pkg->is_available)
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 fw-bold">ACTIVE</span>
                                                @else
                                                    <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25 px-2.5 py-1.5 fw-bold">DISABLED</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                {{-- Individual Services --}}
                                @if($displayServices)
                                    @forelse($services as $svc)
                                        <tr class="report-row svc-row border-secondary border-opacity-10" data-item-type="individual">
                                            <td class="text-center cb-col">
                                                <input type="checkbox" class="form-check-input row-cb svc-cb" value="{{ $svc->id }}" checked onchange="updateReportTotals('svc')">
                                            </td>
                                            <td class="font-monospace text-accent small fw-bold">#{{ $svc->id }}</td>
                                            <td class="fw-bold uppercase tracking-tight text-main">{{ $svc->name }}</td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2.5 py-1.5 fs-x-small fw-bold">
                                                    INDIVIDUAL
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
                                        @if(!$displayPackages || $packages->isEmpty())
                                            <tr class="no-records-row">
                                                <td colspan="8" class="text-center py-5 text-muted italic">No matching services found for selected filters.</td>
                                            </tr>
                                        @endif
                                    @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-3 px-sm-4 no-print">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                            <div class="small text-muted text-center text-sm-start" id="svcPaginationInfo">
                                Showing <span id="svcStart" class="fw-bold text-main">1</span> to <span id="svcEnd" class="fw-bold text-main">10</span> of <span id="svcTotal" class="fw-bold text-main">{{ $combinedTotalRecords }}</span> entries
                            </div>
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-sm-end flex-wrap" id="svcPaginationNav"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         TAB 4: ACCOUNTS DIRECTORY REPORT (Admin Only)
         ========================================================================= --}}
    @if($type === 'accounts' && in_array('accounts', $allowedTypes))
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
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="order" value="{{ $order }}">

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

                        <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2">
                            <button type="button" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="triggerExport('acc')">
                                <i class="bi bi-download"></i>
                                <span>Export CSV</span>
                            </button>
                            <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="openPrintSignatoryModal()">
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
                                    <th style="width: 8%; min-width: 70px;">
                                        <a href="{{ getReportSortUrl('id', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>User ID</span>
                                            <i class="bi {{ $sortBy === 'id' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 20%; min-width: 160px;">
                                        <a href="{{ getReportSortUrl('name', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Full Name</span>
                                            <i class="bi {{ $sortBy === 'name' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 21%; min-width: 175px;">Email Address</th>
                                    <th style="width: 14%; min-width: 130px; white-space: nowrap !important;">Phone Number</th>
                                    <th class="text-center" style="width: 16%; min-width: 165px;">
                                        <a href="{{ getReportSortUrl('role', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center justify-content-center gap-1 w-100">
                                            <span>Role / Occupation</span>
                                            <i class="bi {{ $sortBy === 'role' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th class="text-center" style="width: 10%; min-width: 95px;">Status</th>
                                    <th class="text-end pe-4" style="width: 11%; min-width: 105px;">Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounts as $acc)
                                    <tr class="report-row acc-row border-secondary border-opacity-10">
                                        <td class="text-center cb-col">
                                            <input type="checkbox" class="form-check-input row-cb acc-cb" value="{{ $acc->id }}" checked onchange="updateReportTotals('acc')">
                                        </td>
                                        <td class="font-monospace text-accent small fw-bold">#{{ $acc->id }}</td>
                                        <td class="fw-bold uppercase tracking-tight text-main">{{ $acc->name }}</td>
                                        <td class="small" style="word-break: break-all;">{{ $acc->email }}</td>
                                        <td class="small fw-semibold text-nowrap">{{ $acc->phone }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2.5 py-1.5 fs-x-small fw-bold d-inline-block text-wrap" style="max-width: 140px; line-height: 1.25;">
                                                {{ strtoupper($acc->occupation ?: $acc->role) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $acc->trashed() ? 'bg-danger text-white' : 'bg-success text-white' }} px-2.5 py-1.5 small fw-bold">
                                                {{ $acc->trashed() ? 'DEACTIVATED' : 'ACTIVE' }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4 small text-muted text-nowrap">{{ $acc->created_at ? $acc->created_at->format('M d, Y') : 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr class="no-records-row">
                                        <td colspan="8" class="text-center py-5 text-muted italic">No matching user accounts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-3 px-sm-4 no-print">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                            <div class="small text-muted text-center text-sm-start" id="accPaginationInfo">
                                Showing <span id="accStart" class="fw-bold text-main">1</span> to <span id="accEnd" class="fw-bold text-main">10</span> of <span id="accTotal" class="fw-bold text-main">{{ $accounts->count() }}</span> entries
                            </div>
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-sm-end flex-wrap" id="accPaginationNav"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         TAB 5: SYSTEM AUDIT LOGS REPORT (Admin Only)
         ========================================================================= --}}
    @if($type === 'logs' && in_array('logs', $allowedTypes))
        @php
            $allActionEvents = \App\Models\ActivityLog::select('action')
                ->distinct()
                ->whereNotNull('action')
                ->where('action', '!=', '')
                ->orderBy('action')
                ->pluck('action');

            if (!empty($logCategory) && $logCategory !== 'all' && !$allActionEvents->contains($logCategory)) {
                $allActionEvents = $allActionEvents->push($logCategory)->sort()->values();
            }
        @endphp

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
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="order" value="{{ $order }}">

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
                                <option value="all" {{ ($logCategory ?? 'all') === 'all' ? 'selected' : '' }}>All Action Events</option>
                                @foreach($allActionEvents as $event)
                                    <option value="{{ $event }}" {{ ($logCategory ?? '') === $event ? 'selected' : '' }}>{{ strtoupper($event) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="card p-3 bg-main border-secondary border-opacity-25 rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-x-small uppercase fw-bold text-secondary">Selected Logs:</span>
                                <span class="badge bg-secondary bg-opacity-25 text-main fw-bold" id="logEntryCountSidebar">{{ $logs->count() }}</span>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2">
                            <button type="button" class="btn btn-outline-accent btn-sm fw-bold uppercase py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="triggerExport('log')">
                                <i class="bi bi-download"></i>
                                <span>Export CSV</span>
                            </button>
                            <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm py-2.5 d-inline-flex align-items-center justify-content-center gap-2 flex-grow-1" onclick="openPrintSignatoryModal()">
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
                                    <th style="width: 14%; min-width: 120px;">
                                        <a href="{{ getReportSortUrl('date', $sortBy, $order) }}" class="text-decoration-none text-white d-inline-flex align-items-center gap-1">
                                            <span>Date & Time</span>
                                            <i class="bi {{ $sortBy === 'date' ? ($order === 'asc' ? 'bi-caret-up-fill text-accent' : 'bi-caret-down-fill text-accent') : 'bi-arrow-down-up opacity-50' }}"></i>
                                        </a>
                                    </th>
                                    <th style="width: 16%; min-width: 140px;">Performer</th>
                                    <th style="width: 16%; min-width: 140px;">Event Action</th>
                                    <th style="width: 16%; min-width: 140px;">Target Patient</th>
                                    <th class="pe-4" style="width: 38%; min-width: 250px;">Justification / Audit Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr class="report-row log-row border-secondary border-opacity-10">
                                        <td class="text-center cb-col">
                                            <input type="checkbox" class="form-check-input row-cb log-cb" value="{{ $log->id }}" checked onchange="updateReportTotals('log')">
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <div class="fw-semibold text-main">{{ $log->created_at ? $log->created_at->format('M d, Y') : 'N/A' }}</div>
                                            <small class="text-accent fw-bold">{{ $log->created_at ? $log->created_at->format('h:i A') : '' }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold uppercase tracking-tight text-main">{{ $log->user?->name ?? 'System/Deleted' }}</div>
                                            <small class="text-muted fs-x-small fw-semibold">({{ strtoupper($log->user?->occupation ?: ($log->user?->role ?? 'SYSTEM')) }})</small>
                                        </td>
                                        <td class="action-event-cell">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small fw-bold uppercase action-event-badge text-wrap">
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
                    <div class="card-footer bg-card border-top border-secondary border-opacity-10 py-3 px-3 px-sm-4 no-print">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                            <div class="small text-muted text-center text-sm-start" id="logPaginationInfo">
                                Showing <span id="logStart" class="fw-bold text-main">1</span> to <span id="logEnd" class="fw-bold text-main">10</span> of <span id="logTotal" class="fw-bold text-main">{{ $logs->count() }}</span> entries
                            </div>
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-sm-end flex-wrap" id="logPaginationNav"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- EDITABLE SIGNATORY & OCCUPATION CONFIRMATION MODAL --}}
<div class="modal fade" id="printSignatoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pen-fill fs-5"></i>
                    <span>Confirm Signatory & Letterhead</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Confirm or customize your signatory details for the printed clinical letterhead.
                </p>
                <div class="mb-3">
                    <label class="smaller fw-bold text-muted uppercase mb-1 d-block">Signatory Full Name</label>
                    <input type="text" id="custom_signatory_name" class="form-control" value="{{ $currentUser?->name ?? 'SYSTEM ADMINISTRATOR' }}" required>
                </div>
                <div class="mb-2">
                    <label class="smaller fw-bold text-muted uppercase mb-1 d-block">Occupation / Official Designation</label>
                    <input type="text" id="custom_signatory_role" class="form-control" value="{{ $currentUser?->occupation ?: ($currentUser && $currentUser->isAdmin() ? 'Laboratory Administrator' : strtoupper($currentUser?->role ?? 'ADMINISTRATOR')) }}" placeholder="e.g. Clinic Manager, Lab Supervisor" required>
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-custom btn-accent py-2 px-4 fw-bold" onclick="executePrintWithSignatory()">
                    <i class="bi bi-printer-fill me-1"></i> Generate Printable Report
                </button>
            </div>
        </div>
    </div>
</div>

{{-- CUSTOM THEME-COMPATIBLE SELECTION REQUIRED ALERT MODAL --}}
<div class="modal fade" id="reportNoticeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 390px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 12px;">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3" style="background-color: rgba(25, 211, 140, 0.1); width: 68px; height: 68px;">
                    <i class="bi bi-exclamation-octagon-fill text-accent fs-1" id="reportNoticeIcon"></i>
                </div>
            </div>
            <h5 class="modal-title text-main fw-bold mb-2 uppercase tracking-tight" id="reportNoticeTitle">Selection Required</h5>
            <div id="reportNoticeMessage" class="text-secondary small mb-4">Please select at least one record before proceeding.</div>
            <button type="button" class="btn btn-accent w-100 py-2.5 uppercase fw-bold" style="border-radius: 8px; letter-spacing: 0.5px;" data-bs-dismiss="modal">
                UNDERSTOOD
            </button>
        </div>
    </div>
</div>

<style>
#reports-console-root {
    overflow: visible !important;
    animation: none !important;
    transform: none !important;
}
.back-panel-box a {
    width: auto !important;
}
@media (max-width: 575.98px) {
    .back-panel-box {
        width: 100% !important;
    }
    .back-panel-box a {
        width: 100% !important;
        justify-content: center !important;
    }
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
@media (max-width: 991.98px) {
    .sticky-controls-sidebar {
        position: static !important;
        max-height: none !important;
        overflow-y: visible !important;
        margin-bottom: 1.5rem;
    }
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
    table-layout: auto !important;
    width: 100% !important;
    min-width: 1050px !important;
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
    padding: 0.85rem 1rem !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    word-wrap: break-word !important;
    white-space: normal !important;
    vertical-align: middle !important;
}
.action-event-badge,
table.report-table .action-event-badge,
table.report-table td .badge.action-event-badge {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    word-wrap: break-word !important;
    display: inline-block !important;
    line-height: 1.35 !important;
    max-width: 100% !important;
    text-align: left !important;
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
    tx: { page: 1, pageSize: 10 },
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

function showReportNotice(message, title = 'Selection Required', iconClass = 'bi-exclamation-octagon-fill text-accent') {
    const titleElem = document.getElementById('reportNoticeTitle');
    const msgElem = document.getElementById('reportNoticeMessage');
    const iconElem = document.getElementById('reportNoticeIcon');
    const modalElem = document.getElementById('reportNoticeModal');

    if (titleElem) titleElem.innerText = title;
    if (msgElem) msgElem.innerText = message;
    if (iconElem) {
        iconElem.className = 'bi fs-1 ' + iconClass;
    }

    if (modalElem && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalElem) || new bootstrap.Modal(modalElem);
        modal.show();
    } else {
        alert(message);
    }
}

function triggerExport(type) {
    const prefix = getPrefix(type);
    const checkedRows = Array.from(document.querySelectorAll(`.${prefix}-row`)).filter(tr => {
        const cb = tr.querySelector('.row-cb');
        return cb && cb.checked && !tr.classList.contains('search-hidden');
    });

    if (checkedRows.length === 0) {
        showReportNotice(
            'Please select at least one record from the table before exporting the CSV spreadsheet.',
            'Export Selection Required'
        );
        return;
    }

    const selectedIds = checkedRows.map(tr => {
        const cb = tr.querySelector('.row-cb');
        return cb ? cb.value : null;
    }).filter(Boolean);

    const typeMap = {
        'tx': 'transactions',
        'app': 'appointments',
        'svc': 'services',
        'acc': 'accounts',
        'log': 'logs'
    };

    const reportType = typeMap[prefix] || prefix;
    const url = new URL('{{ route("admin.reports.export") }}', window.location.origin);
    const searchParams = new URLSearchParams(window.location.search);

    searchParams.forEach((val, key) => url.searchParams.set(key, val));
    url.searchParams.set('report_type', reportType);
    url.searchParams.set('selected_ids', selectedIds.join(','));

    // If services tab, preserve classification filter
    if (prefix === 'svc') {
        const svcKind = document.getElementById('svcKindSelect')?.value;
        if (svcKind) url.searchParams.set('svc_kind', svcKind);
    }

    window.location.href = url.toString();
}

function openPrintSignatoryModal() {
    const rawType = '{{ $type }}';
    const prefix = getPrefix(rawType);
    const checkedRows = Array.from(document.querySelectorAll(`.${prefix}-row`)).filter(tr => {
        const cb = tr.querySelector('.row-cb');
        return cb && cb.checked && !tr.classList.contains('search-hidden');
    });

    if (checkedRows.length === 0) {
        showReportNotice(
            'Please select at least one record from the table before generating the official print preview.',
            'Print Selection Required'
        );
        return;
    }

    const modalEl = document.getElementById('printSignatoryModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
}

function executePrintWithSignatory() {
    const rawType = '{{ $type }}';
    const prefix = getPrefix(rawType);
    const checkedRows = Array.from(document.querySelectorAll(`.${prefix}-row`)).filter(tr => {
        const cb = tr.querySelector('.row-cb');
        return cb && cb.checked && !tr.classList.contains('search-hidden');
    });

    const selectedIds = checkedRows.map(tr => {
        const cb = tr.querySelector('.row-cb');
        return cb ? cb.value : null;
    }).filter(Boolean);

    const name = document.getElementById('custom_signatory_name')?.value || '{{ $currentUser?->name ?? "ADMINISTRATOR" }}';
    const role = document.getElementById('custom_signatory_role')?.value || 'Clinic Manager';

    const modalEl = document.getElementById('printSignatoryModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }

    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('type', rawType);
    currentParams.set('selected_ids', selectedIds.join(','));
    currentParams.set('signatory_name', name);
    currentParams.set('signatory_role', role);

    if (prefix === 'svc') {
        const svcKind = document.getElementById('svcKindSelect')?.value;
        if (svcKind) currentParams.set('svc_kind', svcKind);
    }

    const printUrl = '{{ route("admin.reports.print") }}?' + currentParams.toString();
    window.open(printUrl, '_blank');
}
</script>
@endpush
@endsection