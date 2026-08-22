@extends('layouts.app')
@section('title', 'Reports & Export Console')

@section('content')
<div class="container text-start animate-page py-4">
 
 {{-- Header --}}
 <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
 <div>
 <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">
 <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Reports & Export Console
 </h2>
 <p class="text-secondary small mb-0">Generate real-time printable reports and CSV data exports for transactions, user accounts, and system logs.</p>
 </div>
 <a href="{{ route('admin.panel') }}" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2">
 <i class="bi bi-arrow-left me-1"></i> Back to Panel
 </a>
 </div>

 {{-- Main Report Type Navigation Tabs --}}
 <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" style="border-color: var(--border-color) !important;">
 <li class="nav-item">
 <a href="{{ route('admin.reports', ['type' => 'transactions']) }}" class="nav-link fw-bold px-4 py-2 uppercase {{ $type === 'transactions' ? 'active' : '' }}">
 <i class="bi bi-receipt me-1.5"></i> Transactions Report
 </a>
 </li>
 <li class="nav-item">
 <a href="{{ route('admin.reports', ['type' => 'accounts']) }}" class="nav-link fw-bold px-4 py-2 uppercase {{ $type === 'accounts' ? 'active' : '' }}">
 <i class="bi bi-people-fill me-1.5"></i> Accounts Directory
 </a>
 </li>
 <li class="nav-item">
 <a href="{{ route('admin.reports', ['type' => 'logs']) }}" class="nav-link fw-bold px-4 py-2 uppercase {{ $type === 'logs' ? 'active' : '' }}">
 <i class="bi bi-shield-check me-1.5"></i> Audit Logs
 </a>
 </li>
 </ul>

 {{-- TAB 1: TRANSACTIONS REPORT --}}
 @if($type === 'transactions')
 <div class="card p-4 border-secondary bg-card shadow-lg mb-5 text-start">
 {{-- Filter & Search Bar --}}
 <form action="{{ route('admin.reports') }}" method="GET" class="mb-4">
 <input type="hidden" name="type" value="transactions">
 <div class="row g-3 align-items-center">
 {{-- Live Search Input --}}
 <div class="col-md-3">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Search Filter</label>
 <div class="input-group input-group-sm">
 <span class="input-group-text bg-secondary bg-opacity-10 border-secondary text-secondary"><i class="bi bi-search"></i></span>
 <input type="text" id="txSearchInput" class="form-control form-control-sm" placeholder="Search ref, patient, test..." onkeyup="filterReportTable('tx')">
 </div>
 </div>
 {{-- Period Filter --}}
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Period Filter</label>
 <select name="tx_period" class="form-select form-select-sm" onchange="this.form.submit()">
 <option value="cumulative" {{ $txPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
 <option value="daily" {{ $txPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
 <option value="monthly" {{ $txPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
 <option value="yearly" {{ $txPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
 </select>
 </div>
 @if($txPeriod === 'daily')
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Specific Date</label>
 <input type="date" name="tx_date" class="form-control form-control-sm" value="{{ $txDate }}" onchange="this.form.submit()">
 </div>
 @elseif($txPeriod === 'monthly')
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Specific Month</label>
 <input type="month" name="tx_month" class="form-control form-control-sm" value="{{ $txMonth }}" onchange="this.form.submit()">
 </div>
 @elseif($txPeriod === 'yearly')
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Specific Year</label>
 <select name="tx_year" class="form-select form-select-sm" onchange="this.form.submit()">
 @for($y = date('Y'); $y >= date('Y') - 5; $y--)
 <option value="{{ $y }}" {{ $txYear == $y ? 'selected' : '' }}>{{ $y }}</option>
 @endfor
 </select>
 </div>
 @endif
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Payment Status</label>
 <select name="tx_status" class="form-select form-select-sm" onchange="this.form.submit()">
 <option value="all" {{ $txStatus === 'all' ? 'selected' : '' }}>All Payment States</option>
 <option value="paid" {{ $txStatus === 'paid' ? 'selected' : '' }}>PAID</option>
 <option value="unpaid" {{ $txStatus === 'unpaid' ? 'selected' : '' }}>UNPAID</option>
 <option value="refunded" {{ $txStatus === 'refunded' ? 'selected' : '' }}>REFUNDED</option>
 </select>
 </div>
 <div class="col-md text-end align-self-end">
 <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'transactions'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase me-2">
 <i class="bi bi-download me-1"></i> Export CSV
 </a>
 <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm" onclick="triggerPrint()">
 <i class="bi bi-printer-fill me-1"></i> Print Preview
 </button>
 </div>
 </div>
 </form>
 {{-- REAL-TIME PRINTABLE REPORT CANVAS --}}
 <div id="printableReportCanvas" class="p-4 p-md-5 border rounded bg-white text-dark shadow-sm">
 <div class="text-center border-bottom border-dark pb-2 mb-3 report-header">
 <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark report-title">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
 <h6 class="text-secondary uppercase mb-1 report-subtitle">Official Financial & Transaction Audit Report</h6>
 <small class="text-muted report-address">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
 </div>
 <div class="d-flex justify-content-between align-items-center mb-3 small text-muted border-bottom pb-2 report-meta">
 <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
 <div><strong>Filtered Period:</strong> {{ strtoupper($txPeriod) }} | <strong>Selected Entries:</strong> <span id="txEntryCount">{{ $transactions->count() }}</span> / {{ $transactions->count() }}</div>
 </div>
 <div class="table-responsive">
 <table class="table table-bordered table-sm align-middle mb-0 text-dark report-table" id="txReportTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
 <thead class="table-light text-uppercase fw-bold">
 <tr>
 <th class="text-center cb-col" style="width: 35px;">
 <input type="checkbox" class="form-check-input select-all-cb" id="selectAllTx" checked onclick="toggleSelectAllRows('tx', this)">
 </th>
 <th style="width: 13%;">Date (M/D/Y)</th>
 <th style="width: 7%;">Ref #</th>
 <th style="width: 20%;">Patient Name</th>
 <th style="width: 28%;">Services Requested</th>
 <th style="width: 10%;">Method</th>
 <th style="width: 10%;">Status</th>
 <th class="text-end" style="width: 12%;">Amount (PHP)</th>
 </tr>
 </thead>
 <tbody>
 @forelse($transactions as $tx)
 @php
 $amt = $tx->payment_amount ?: $tx->totalPrice();
 $isPaid = ($tx->payment_status === 'paid');
 @endphp
 <tr class="report-row tx-row" data-amount="{{ $amt }}" data-paid="{{ $isPaid ? '1' : '0' }}">
 <td class="text-center cb-col">
 <input type="checkbox" class="form-check-input row-cb tx-cb" checked onchange="updateReportTotals('tx')">
 </td>
 <td>{{ $tx->appointment_date ? $tx->appointment_date->format('M d, Y') : $tx->created_at->format('M d, Y') }}</td>
 <td class="font-monospace">#{{ $tx->id }}</td>
 <td><strong>{{ $tx->patient_name }}</strong></td>
 <td>{{ $tx->services->pluck('name')->implode(', ') }}</td>
 <td>{{ $tx->payment_method }}</td>
 <td><span class="badge print-badge {{ $isPaid ? 'badge-paid' : ($tx->payment_status === 'refunded' ? 'badge-refunded' : 'badge-unpaid') }}">{{ strtoupper($tx->payment_status) }}</span></td>
 <td class="text-end fw-bold">₱{{ number_format($amt, 2) }}</td>
 </tr>
 @empty
 <tr class="no-records-row">
 <td colspan="8" class="text-center py-4 text-muted italic">No matching transactions found for selected filters.</td>
 </tr>
 @endforelse
 <tr class="table-light fw-bold summary-total-row">
 <td class="cb-col text-center"></td>
 <td colspan="6" class="text-end uppercase">Total Paid Revenue (Selected Rows):</td>
 <td class="text-end text-success fw-bold" id="txTotalPaidDisplay">₱0.00</td>
 </tr>
 </tbody>
 </table>
 </div>
 <div class="d-flex justify-content-between align-items-end mt-4 pt-3 report-signature-block">
 <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
 <div class="text-center border-top border-dark pt-1" style="width: 220px;">
 <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
 </div>
 </div>
 </div>
 </div>
 @endif

 {{-- TAB 2: ACCOUNTS DIRECTORY REPORT --}}
 @if($type === 'accounts')
 <div class="card p-4 border-secondary bg-card shadow-lg mb-5 text-start">
 {{-- Filter & Search Bar --}}
 <form action="{{ route('admin.reports') }}" method="GET" class="mb-4">
 <input type="hidden" name="type" value="accounts">
 <div class="row g-3 align-items-center">
 {{-- Live Search Input --}}
 <div class="col-md-4">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Search Filter</label>
 <div class="input-group input-group-sm">
 <span class="input-group-text bg-secondary bg-opacity-10 border-secondary text-secondary"><i class="bi bi-search"></i></span>
 <input type="text" id="accSearchInput" class="form-control form-control-sm" placeholder="Search ID, name, email, phone..." onkeyup="filterReportTable('acc')">
 </div>
 </div>
 {{-- Account Role Filter --}}
 <div class="col-md-3">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Account Role Filter</label>
 <select name="acc_role" class="form-select form-select-sm" onchange="this.form.submit()">
 <option value="all" {{ $accRole === 'all' ? 'selected' : '' }}>All System Accounts</option>
 <option value="patients" {{ $accRole === 'patients' ? 'selected' : '' }}>Patients Only</option>
 <option value="employees" {{ $accRole === 'employees' ? 'selected' : '' }}>Employees & Lab Techs</option>
 <option value="admins" {{ $accRole === 'admins' ? 'selected' : '' }}>Administrators Only</option>
 </select>
 </div>
 <div class="col-md text-end align-self-end">
 <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'accounts'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase me-2">
 <i class="bi bi-download me-1"></i> Export CSV
 </a>
 <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm" onclick="triggerPrint()">
 <i class="bi bi-printer-fill me-1"></i> Print Preview
 </button>
 </div>
 </div>
 </form>
 {{-- REAL-TIME PRINTABLE REPORT CANVAS --}}
 <div id="printableReportCanvas" class="p-4 p-md-5 border rounded bg-white text-dark shadow-sm">
 <div class="text-center border-bottom border-dark pb-2 mb-3 report-header">
 <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark report-title">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
 <h6 class="text-secondary uppercase mb-1 report-subtitle">User Accounts & Access Registry Report</h6>
 <small class="text-muted report-address">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
 </div>
 <div class="d-flex justify-content-between align-items-center mb-3 small text-muted border-bottom pb-2 report-meta">
 <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
 <div><strong>Role Filter:</strong> {{ strtoupper($accRole) }} | <strong>Selected Accounts:</strong> <span id="accEntryCount">{{ $accounts->count() }}</span> / {{ $accounts->count() }}</div>
 </div>
 <div class="table-responsive">
 <table class="table table-bordered table-sm align-middle mb-0 text-dark report-table" id="accReportTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
 <thead class="table-light text-uppercase fw-bold">
 <tr>
 <th class="text-center cb-col" style="width: 35px;">
 <input type="checkbox" class="form-check-input select-all-cb" id="selectAllAcc" checked onclick="toggleSelectAllRows('acc', this)">
 </th>
 <th style="width: 8%;">User ID</th>
 <th style="width: 20%;">Full Name</th>
 <th style="width: 27%;">Email Address</th>
 <th style="width: 15%;">Phone Number</th>
 <th style="width: 12%;">Access Role</th>
 <th style="width: 8%;">Status</th>
 <th style="width: 10%;">Registered Date</th>
 </tr>
 </thead>
 <tbody>
 @forelse($accounts as $acc)
 <tr class="report-row acc-row">
 <td class="text-center cb-col">
 <input type="checkbox" class="form-check-input row-cb acc-cb" checked onchange="updateReportTotals('acc')">
 </td>
 <td class="font-monospace">#{{ $acc->id }}</td>
 <td><strong>{{ $acc->name }}</strong></td>
 <td>{{ $acc->email }}</td>
 <td>{{ $acc->phone }}</td>
 <td><span class="badge bg-dark">{{ strtoupper($acc->role) }}</span></td>
 <td><span class="badge {{ $acc->trashed() ? 'bg-danger' : 'bg-success' }}">{{ $acc->trashed() ? 'DEACTIVATED' : 'ACTIVE' }}</span></td>
 <td>{{ $acc->created_at ? $acc->created_at->format('M d, Y') : 'N/A' }}</td>
 </tr>
 @empty
 <tr class="no-records-row">
 <td colspan="8" class="text-center py-4 text-muted italic">No matching user accounts found.</td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 <div class="d-flex justify-content-between align-items-end mt-4 pt-3 report-signature-block">
 <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
 <div class="text-center border-top border-dark pt-1" style="width: 220px;">
 <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
 </div>
 </div>
 </div>
 </div>
 @endif

 {{-- TAB 3: AUDIT LOGS REPORT --}}
 @if($type === 'logs')
 <div class="card p-4 border-secondary bg-card shadow-lg mb-5 text-start">
 {{-- Filter & Search Bar --}}
 <form action="{{ route('admin.reports') }}" method="GET" class="mb-4">
 <input type="hidden" name="type" value="logs">
 <div class="row g-3 align-items-center">
 {{-- Live Search Input --}}
 <div class="col-md-3">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Search Filter</label>
 <div class="input-group input-group-sm">
 <span class="input-group-text bg-secondary bg-opacity-10 border-secondary text-secondary"><i class="bi bi-search"></i></span>
 <input type="text" id="logSearchInput" class="form-control form-control-sm" placeholder="Search performer, action, patient..." onkeyup="filterReportTable('log')">
 </div>
 </div>
 {{-- Period Filter --}}
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Period Filter</label>
 <select name="log_period" class="form-select form-select-sm" onchange="this.form.submit()">
 <option value="cumulative" {{ $logPeriod === 'cumulative' ? 'selected' : '' }}>Cumulative (All Time)</option>
 <option value="daily" {{ $logPeriod === 'daily' ? 'selected' : '' }}>Daily (Specific Date)</option>
 <option value="monthly" {{ $logPeriod === 'monthly' ? 'selected' : '' }}>Monthly (Specific Month)</option>
 <option value="yearly" {{ $logPeriod === 'yearly' ? 'selected' : '' }}>Yearly (Specific Year)</option>
 </select>
 </div>
 @if($logPeriod === 'daily')
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Specific Date</label>
 <input type="date" name="log_date" class="form-control form-control-sm" value="{{ $logDate }}" onchange="this.form.submit()">
 </div>
 @elseif($logPeriod === 'monthly')
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Specific Month</label>
 <input type="month" name="log_month" class="form-control form-control-sm" value="{{ $logMonth }}" onchange="this.form.submit()">
 </div>
 @elseif($logPeriod === 'yearly')
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Specific Year</label>
 <select name="log_year" class="form-select form-select-sm" onchange="this.form.submit()">
 @for($y = date('Y'); $y >= date('Y') - 5; $y--)
 <option value="{{ $y }}" {{ $logYear == $y ? 'selected' : '' }}>{{ $y }}</option>
 @endfor
 </select>
 </div>
 @endif
 <div class="col-md-2">
 <label class="smaller text-secondary fw-bold uppercase mb-1 d-block">Action Event Filter</label>
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
 <div class="col-md text-end align-self-end">
 <a href="{{ route('admin.reports.export', array_merge(request()->query(), ['report_type' => 'logs'])) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase me-2">
 <i class="bi bi-download me-1"></i> Export CSV
 </a>
 <button type="button" class="btn btn-accent btn-sm fw-bold uppercase shadow-sm" onclick="triggerPrint()">
 <i class="bi bi-printer-fill me-1"></i> Print Preview
 </button>
 </div>
 </div>
 </form>
 {{-- REAL-TIME PRINTABLE REPORT CANVAS --}}
 <div id="printableReportCanvas" class="p-4 p-md-5 border rounded bg-white text-dark shadow-sm">
 <div class="text-center border-bottom border-dark pb-2 mb-3 report-header">
 <h3 class="fw-bold mb-1 uppercase tracking-tight text-dark report-title">MEDSCREEN DIAGNOSTIC LABORATORY</h3>
 <h6 class="text-secondary uppercase mb-1 report-subtitle">System Audit & Clinical Security Logs Report</h6>
 <small class="text-muted report-address">Banisil Street, Brgy. Dadiangas West, General Santos City | DOH Accredited</small>
 </div>
 <div class="d-flex justify-content-between align-items-center mb-3 small text-muted border-bottom pb-2 report-meta">
 <div><strong>Generated Date:</strong> {{ date('M d, Y | h:i A') }}</div>
 <div><strong>Filtered Period:</strong> {{ strtoupper($logPeriod) }} | <strong>Selected Log Entries:</strong> <span id="logEntryCount">{{ $logs->count() }}</span> / {{ $logs->count() }}</div>
 </div>
 <div class="table-responsive">
 <table class="table table-bordered table-sm align-middle mb-0 text-dark report-table" id="logReportTable" style="font-size: 0.8rem; table-layout: fixed; width: 100%;">
 <thead class="table-light text-uppercase fw-bold">
 <tr>
 <th class="text-center cb-col" style="width: 35px;">
 <input type="checkbox" class="form-check-input select-all-cb" id="selectAllLogs" checked onclick="toggleSelectAllRows('log', this)">
 </th>
 <th style="width: 15%;">Date & Time</th>
 <th style="width: 18%;">Performer</th>
 <th style="width: 20%;">Action Event</th>
 <th style="width: 17%;">Target Patient</th>
 <th style="width: 30%;">Justification / Audit Reason</th>
 </tr>
 </thead>
 <tbody>
 @forelse($logs as $log)
 <tr class="report-row log-row">
 <td class="text-center cb-col">
 <input type="checkbox" class="form-check-input row-cb log-cb" checked onchange="updateReportTotals('log')">
 </td>
 <td class="fw-semibold">{{ $log->created_at ? $log->created_at->format('M d, Y h:i A') : 'N/A' }}</td>
 <td>
 <strong>{{ $log->user->name ?? 'System/Deleted' }}</strong><br>
 <small class="text-muted">({{ strtoupper($log->user->role ?? 'SYSTEM') }})</small>
 </td>
 <td><span class="badge bg-dark print-badge">{{ $log->action }}</span></td>
 <td>{{ $log->patient_name }}</td>
 <td class="italic">{{ $log->reason }}</td>
 </tr>
 @empty
 <tr class="no-records-row">
 <td colspan="6" class="text-center py-4 text-muted italic">No audit logs matching selected filters.</td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 <div class="d-flex justify-content-between align-items-end mt-4 pt-3 report-signature-block">
 <small class="text-muted">System-generated official report. Confirmed by Medscreen Administrative Console.</small>
 <div class="text-center border-top border-dark pt-1" style="width: 220px;">
 <strong>PREPARED BY</strong><br><small>SYSTEM ADMINISTRATOR</small>
 </div>
 </div>
 </div>
 </div>
 @endif
</div>

<style>
/* Screen Display Custom Styles */
.cb-col {
 vertical-align: middle;
}
.unselected-row {
 opacity: 0.35;
 background-color: #f8f9fa !important;
}
/* On-Screen Badge Styling for Paid/Unpaid/Refunded */
.badge-paid { 
 background-color: #19d38c !important;
 color: #1c232d !important;
}
.badge-unpaid {
 background-color: #ffc107 !important;
 color: #1c232d !important;
}
.badge-refunded {
 background-color: #0dcaf0 !important;
 color: #1c232d !important;
}

/* Zero-Margin Precision Print & PDF Engine Styling */
@media print {
 @page {
 size: A4 portrait;
 margin: 10mm 12mm !important; /* Standard printable margins */
 }
 html, body, main, .container, .card, .animate-page {
 background-color: #ffffff !important;
 color: #000000 !important;
 font-size: 9.5px !important;
 margin: 0 !important;
 padding: 0 !important;
 width: 100% !important;
 max-width: 100% !important;
 position: static !important;
 transform: none !important;
 box-shadow: none !important;
 border: none !important;
 background: transparent !important;
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
 box-sizing: border-box !important;
 display: block !important;
 }

 .report-header {
 padding-bottom: 4px !important;
 margin-bottom: 6px !important;
 }
 .report-title {
 font-size: 15px !important;
 margin-bottom: 2px !important;
 }
 .report-subtitle {
 font-size: 10.5px !important;
 margin-bottom: 2px !important;
 }
 .report-address {
 font-size: 8px !important;
 }
 .report-meta {
 font-size: 8.5px !important;
 margin-bottom: 6px !important;
 padding-bottom: 3px !important;
 }

 /* Hide Checkboxes, Search Bars, Buttons in Print */
 .cb-col, 
 input[type="checkbox"], 
 .btn, 
 .input-group,
 .no-print {
 display: none !important;
 width: 0 !important;
 height: 0 !important;
 padding: 0 !important;
 margin: 0 !important;
 }

 /* Completely Remove Unselected or Search-Filtered Rows from Printed Output */
 tr.unselected-row, 
 tr.d-none,
 tr.search-hidden {
 display: none !important;
 }

 /* Strict Table Dimensions and Cell Wrapping to Prevent Column Overlapping */
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
 padding: 3.5px 5px !important;
 font-size: 8px !important;
 border: 1px solid #000000 !important;
 vertical-align: middle !important;
 position: relative !important;
 }
 table.report-table thead {
 display: table-header-group !important;
 }
 table.report-table thead th {
 background-color: #f1f5f9 !important;
 color: #000000 !important;
 font-weight: bold !important;
 }

 /* Print Badge High-Contrast Overrides - Prevents Badges from Overlapping Columns */
 .badge, .print-badge {
 display: inline-block !important;
 max-width: 100% !important;
 white-space: normal !important;
 word-wrap: break-word !important;
 border: 1px solid #000000 !important;
 color: #000000 !important;
 background: transparent !important;
 font-weight: bold !important;
 padding: 1px 3px !important;
 font-size: 7.5px !important;
 line-height: 1.1 !important;
 text-align: center !important;
 box-sizing: border-box !important;
 }

 tr {
 page-break-inside: avoid !important;
 }
 .summary-total-row {
 page-break-inside: avoid !important;
 background-color: #f8fafc !important;
 }
 .report-signature-block {
 page-break-inside: avoid !important;
 margin-top: 20px !important;
 padding-top: 8px !important;
 }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
 updateReportTotals('tx');
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

 // Update entry count displays
 const countEl = document.getElementById(`${type}EntryCount`);
 if (countEl) {
 countEl.innerText = selectedCount;
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