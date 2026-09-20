<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medscreen Report - {{ strtoupper($type) }} - {{ date('Y-m-d') }}</title>
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style id="pageOrientationStyle">
        @page {
            size: A4 portrait;
            margin: 0; /* Strips browser-generated headers (URL/timestamp) and footers */
        }
    </style>

    <style>
        /* =========================================================================
           Zero-Margin Print Reset & Strict Flow Containment
           ========================================================================= */
        @media print {
            html, body {
                background-color: #ffffff !important;
                color: #1c232d !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .export-canvas {
                padding: 10mm 12mm !important;
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }

            tr {
                page-break-inside: avoid !important;
            }

            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }
        }

        /* Screen Preview Mode */
        body {
            background-color: #0e131a;
            color: #1c232d;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .no-print-toolbar {
            background-color: #1c232d;
            border-bottom: 2px solid #19d38c;
            padding: 12px 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .export-canvas {
            background: #ffffff;
            width: 210mm;
            max-width: 100%;
            min-height: 297mm;
            margin: 24px auto;
            padding: 12mm 14mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border-radius: 4px;
            box-sizing: border-box;
            overflow: hidden;
            transition: width 0.2s ease;
        }

        .export-canvas.landscape-mode {
            width: 297mm !important;
            min-height: 210mm !important;
        }

        /* Clinical Letterhead Branding */
        .clinic-brand-title {
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #1c232d;
            margin-bottom: 2px;
        }

        .clinic-brand-title span {
            color: #19d38c;
        }

        .clinic-meta-sub {
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.35;
        }

        .report-header-divider {
            border-top: 3px solid #19d38c;
            border-bottom: 1px solid #1c232d;
            height: 5px;
            margin: 10px 0 14px 0;
        }

        /* Document Metadata Block & Multi-Filter Scope Pills */
        .doc-meta-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #19d38c;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        .doc-meta-title {
            font-size: 10.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1c232d;
            margin-bottom: 3px;
        }

        .doc-meta-detail {
            font-size: 7.5pt;
            color: #475569;
        }

        .scope-pills-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 6px;
        }

        .scope-filter-pill {
            display: inline-flex;
            align-items: center;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .scope-filter-pill strong {
            color: #0f172a;
            margin-left: 3px;
        }

        /* Executive Metrics Summary Cards */
        .metric-mini-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            text-align: center;
        }

        .metric-mini-card .val {
            font-size: 1.05rem;
            font-weight: 800;
            color: #1c232d;
            line-height: 1.2;
        }

        .metric-mini-card .lbl {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* =========================================================================
           Strict Overflow-Proof Table Design
           ========================================================================= */
        table.formal-report-table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            font-size: 7.5pt;
            margin-bottom: 14px;
            box-sizing: border-box;
        }

        table.formal-report-table thead th {
            background-color: #1c232d !important;
            color: #ffffff !important;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 7pt;
            letter-spacing: 0.5px;
            padding: 6px 6px;
            border: 1px solid #1c232d;
            vertical-align: middle;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        table.formal-report-table tbody td {
            padding: 6px 6px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: middle;
            word-break: break-all !important; /* Breaks long Windows paths/exceptions at any character */
            overflow-wrap: anywhere !important;
            word-wrap: break-word !important;
            white-space: normal !important;
            box-sizing: border-box;
        }

        table.formal-report-table tbody tr:nth-child(even) {
            background-color: #fcfdfd;
        }

        /* Badges */
        .formal-badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 6.2pt;
            font-weight: 800;
            text-transform: uppercase;
            border-radius: 4px;
            letter-spacing: 0.3px;
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            color: #334155;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            text-align: center;
            line-height: 1.2;
        }

        .formal-badge-paid {
            background-color: #dcfce7;
            border-color: #86efac;
            color: #15803d;
        }

        .formal-badge-released {
            background-color: #d1fae5;
            border-color: #6ee7b7;
            color: #047857;
        }

        .formal-badge-pending {
            background-color: #fef3c7;
            border-color: #fde68a;
            color: #b45309;
        }

        .formal-badge-expired, .formal-badge-canceled {
            background-color: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }

        /* Formatted Audit Log Reason Box */
        .log-reason-content {
            font-size: 7pt;
            line-height: 1.35;
            color: #334155;
            word-break: break-all !important;
            overflow-wrap: anywhere !important;
            white-space: normal !important;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        /* Auto-Signature Box */
        .formal-sign-section {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e1;
            page-break-inside: avoid;
        }

        .formal-signature-box {
            width: 210px;
            text-align: center;
        }

        .formal-sign-line {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 3px;
            margin-bottom: 3px;
        }

        .admin-name-signature {
            font-size: 9pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    {{-- Screen-Only Control Toolbar --}}
    <div class="no-print-toolbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-light text-dark fw-bold uppercase px-3 py-2">
                <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> Print & PDF Preview
            </span>
            <span class="text-white-50 small">Browser header/footer URL suppression enabled.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-light px-3 py-2" id="orientationBtn" onclick="toggleOrientation()">
                <i class="bi bi-arrow-repeat me-1"></i> <span id="orientationBtnLabel">Switch to Landscape</span>
            </button>
            <button type="button" class="btn btn-sm btn-success fw-bold px-4 py-2 text-dark d-inline-flex align-items-center gap-2" style="background-color: #19d38c; border-color: #19d38c;" onclick="window.print()">
                <i class="bi bi-printer-fill"></i>
                <span>SAVE AS PDF / PRINT</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-light px-3 py-2" onclick="window.close()">
                <i class="bi bi-x-lg me-1"></i> Close
            </button>
        </div>
    </div>

    {{-- The Document Canvas (Rendered at exact A4 Dimensions) --}}
    <div class="export-canvas" id="exportCanvas">

        {{-- 1. Official Clinical Letterhead --}}
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid #19d38c; overflow: hidden; background-color: #1c232d; display: flex; align-items: center; justify-content: center;">
                    <img src="{{ asset('images/logo.jpg') }}" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'">
                </div>
                <div>
                    <h1 class="clinic-brand-title">MED<span>SCREEN</span></h1>
                    <div class="clinic-meta-sub fw-bold uppercase tracking-wider text-dark">Diagnostic Laboratory & Clinical Medical Center</div>
                    <div class="clinic-meta-sub">DOH Accredited Clinical Laboratory • DTI / FDA Certified Facility</div>
                </div>
            </div>
            <div class="text-end clinic-meta-sub">
                <div>Atis Street, Brgy. Dadiangas West</div>
                <div>General Santos City, 9500 South Cotabato</div>
                <div>Phone: (083) 823 8754 • medscreen.lab@gmail.com</div>
            </div>
        </div>

        <div class="report-header-divider"></div>

        @php
            $adminName = Auth::check() ? Auth::user()->name : 'SYSTEM ADMINISTRATOR';
            $adminRole = Auth::check() ? strtoupper(Auth::user()->role) : 'ADMINISTRATOR';
            $reportTitles = [
                'transactions' => 'Official Financial & Transactions Audit Report',
                'appointments' => 'Master Clinical Appointments & Schedule Log',
                'services'     => 'Clinical Test Utilization & Catalog Summary',
                'accounts'     => 'System User Accounts & Personnel Directory',
                'logs'         => 'System Security Audit & Operational Activity Logs',
            ];
            $currentTitle = $reportTitles[$type] ?? 'Clinical Records Export';

            // Multi-Filter Scope Builder (Encapsulates All Active Filters)
            $scopeItems = [];

            // 1. Period Filter
            $period = request()->query($type . '_period', request()->query('tx_period', request()->query('app_period', request()->query('log_period', 'cumulative'))));
            if ($period === 'daily') {
                $dateVal = request()->query('tx_date', request()->query('app_date', request()->query('log_date', date('Y-m-d'))));
                $scopeItems[] = ['label' => 'Period', 'val' => 'DAILY (' . date('M d, Y', strtotime($dateVal)) . ')'];
            } elseif ($period === 'monthly') {
                $monthVal = request()->query('tx_month', request()->query('app_month', request()->query('log_month', date('Y-m'))));
                $scopeItems[] = ['label' => 'Period', 'val' => 'MONTHLY (' . date('F Y', strtotime($monthVal . '-01')) . ')'];
            } elseif ($period === 'yearly') {
                $yearVal = request()->query('tx_year', request()->query('app_year', request()->query('log_year', date('Y'))));
                $scopeItems[] = ['label' => 'Period', 'val' => 'YEARLY (' . $yearVal . ')'];
            } else {
                $scopeItems[] = ['label' => 'Period', 'val' => 'CUMULATIVE (ALL TIME)'];
            }

            // 2. Tab-Specific Filters
            if ($type === 'transactions') {
                $txStatus = request()->query('tx_status', 'all');
                $txAppStatus = request()->query('tx_app_status', 'all');
                if ($txStatus !== 'all') $scopeItems[] = ['label' => 'Payment', 'val' => strtoupper($txStatus)];
                if ($txAppStatus !== 'all') $scopeItems[] = ['label' => 'Workflow', 'val' => strtoupper($txAppStatus)];
            } elseif ($type === 'appointments') {
                $appType = request()->query('app_type', 'all');
                $appStatus = request()->query('app_status', 'all');
                if ($appType !== 'all') $scopeItems[] = ['label' => 'Booking Type', 'val' => strtoupper($appType)];
                if ($appStatus !== 'all') $scopeItems[] = ['label' => 'Status', 'val' => strtoupper($appStatus)];
            } elseif ($type === 'services') {
                $svcCat = request()->query('svc_category', 'all');
                $svcStat = request()->query('svc_status', 'all');
                if ($svcCat !== 'all') $scopeItems[] = ['label' => 'Category', 'val' => strtoupper($svcCat)];
                if ($svcStat !== 'all') $scopeItems[] = ['label' => 'Catalog State', 'val' => strtoupper($svcStat)];
            } elseif ($type === 'accounts') {
                $accRole = request()->query('acc_role', 'all');
                $accStat = request()->query('acc_status', 'all');
                if ($accRole !== 'all') $scopeItems[] = ['label' => 'Role', 'val' => strtoupper($accRole)];
                if ($accStat !== 'all') $scopeItems[] = ['label' => 'Status', 'val' => strtoupper($accStat)];
            } elseif ($type === 'logs') {
                $logCat = request()->query('log_category', 'all');
                if ($logCat !== 'all') $scopeItems[] = ['label' => 'Action Event', 'val' => strtoupper($logCat)];
            }

            // 3. Keyword Search Filter
            $search = request()->query('search', request()->query('tx_search', request()->query('app_search', request()->query('log_search'))));
            if ($search) {
                $scopeItems[] = ['label' => 'Search Query', 'val' => '"' . $search . '"'];
            }
        @endphp

        {{-- 2. Report Document Meta Block with Comprehensive Scope Badges --}}
        <div class="doc-meta-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="doc-meta-title">{{ $currentTitle }}</div>
                    <div class="scope-pills-row">
                        @foreach($scopeItems as $item)
                            <span class="scope-filter-pill">
                                {{ $item['label'] }}: <strong>{{ $item['val'] }}</strong>
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="text-end doc-meta-detail">
                    <div>Generated: <strong>{{ date('M d, Y | h:i A') }}</strong></div>
                    <div>Ref Code: <strong class="font-monospace">MS-{{ strtoupper(substr($type, 0, 3)) }}-{{ date('Ymd-His') }}</strong></div>
                </div>
            </div>
        </div>

        {{-- 3. Executive KPI Metric Summaries --}}
        @if($type === 'transactions')
        @php
            $totalAmountSum = $transactions->sum(fn($t) => $t->payment_amount ?: $t->totalPrice());
            $paidTotal = $transactions->where('payment_status', 'paid')->sum(fn($t) => $t->payment_amount ?: $t->totalPrice());
            $paidCount = $transactions->where('payment_status', 'paid')->count();
        @endphp
        <div class="row g-2 mb-3">
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val">{{ $transactions->count() }}</div>
                    <div class="lbl">Total Records</div>
                </div>
            </div>
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val text-success">₱{{ number_format($paidTotal, 2) }}</div>
                    <div class="lbl">Paid Revenue</div>
                </div>
            </div>
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val text-primary">{{ $paidCount }}</div>
                    <div class="lbl">Settled Invoices</div>
                </div>
            </div>
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val text-secondary">₱{{ number_format($totalAmountSum, 2) }}</div>
                    <div class="lbl">Gross Total</div>
                </div>
            </div>
        </div>
        @elseif($type === 'appointments')
        <div class="row g-2 mb-3">
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val">{{ $appointments->count() }}</div>
                    <div class="lbl">Total Bookings</div>
                </div>
            </div>
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val text-success">{{ $appointments->where('status', 'released')->count() }}</div>
                    <div class="lbl">Released</div>
                </div>
            </div>
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val text-warning">{{ $appointments->where('status', 'pending')->count() }}</div>
                    <div class="lbl">Pending Review</div>
                </div>
            </div>
            <div class="col-3">
                <div class="metric-mini-card">
                    <div class="val text-info">{{ $appointments->whereIn('status', ['approved', 'tested', 'encoded'])->count() }}</div>
                    <div class="lbl">In Progress</div>
                </div>
            </div>
        </div>
        @elseif($type === 'logs')
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="metric-mini-card">
                    <div class="val">{{ $logs->count() }}</div>
                    <div class="lbl">Total Log Entries</div>
                </div>
            </div>
            <div class="col-4">
                <div class="metric-mini-card">
                    <div class="val text-danger">{{ $logs->filter(fn($l) => str_contains($l->action, 'ACCESS') || str_contains($l->action, 'EXCEPTION'))->count() }}</div>
                    <div class="lbl">Security & Exceptions</div>
                </div>
            </div>
            <div class="col-4">
                <div class="metric-mini-card">
                    <div class="val text-success">{{ $logs->filter(fn($l) => str_contains($l->action, 'VERIFIED') || str_contains($l->action, 'ENCODED'))->count() }}</div>
                    <div class="lbl">Clinical Workflows</div>
                </div>
            </div>
        </div>
        @endif

        {{-- 4. Dynamic Tables with Guaranteed Overflow Protection --}}

        {{-- TAB 1: TRANSACTIONS --}}
        @if($type === 'transactions')
        <table class="formal-report-table">
            <thead>
                <tr>
                    <th style="width: 13%;">Date</th>
                    <th style="width: 9%;">Ref #</th>
                    <th style="width: 23%;">Patient Name</th>
                    <th style="width: 23%;">Services</th>
                    <th style="width: 10%;">Method</th>
                    <th style="width: 10%;">Payment</th>
                    <th class="text-end" style="width: 12%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                @php
                    $amt = $tx->payment_amount ?: $tx->totalPrice();
                    $isPaid = ($tx->payment_status === 'paid');
                @endphp
                <tr>
                    <td>{{ $tx->appointment_date ? $tx->appointment_date->format('M d, Y') : $tx->created_at->format('M d, Y') }}</td>
                    <td class="font-monospace fw-bold text-dark">#{{ $tx->id }}</td>
                    <td class="fw-bold uppercase">{{ $tx->patient_name }}</td>
                    <td class="small">{{ $tx->services->pluck('name')->implode(', ') }}</td>
                    <td>{{ $tx->payment_method }}</td>
                    <td>
                        <span class="formal-badge {{ $isPaid ? 'formal-badge-paid' : ($tx->payment_status === 'refunded' ? 'formal-badge-pending' : 'formal-badge-expired') }}">
                            {{ strtoupper($tx->payment_status) }}
                        </span>
                    </td>
                    <td class="text-end fw-bold text-dark" style="white-space: nowrap;">₱{{ number_format($amt, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted italic">No transactions found matching criteria.</td>
                </tr>
                @endforelse
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="6" class="text-end uppercase" style="padding: 8px 10px;">Total Confirmed Paid Revenue:</td>
                    <td class="text-end text-success fw-bold" style="white-space: nowrap; padding: 8px 10px; font-size: 9pt;">₱{{ number_format($paidTotal, 2) }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- TAB 2: APPOINTMENTS --}}
        @if($type === 'appointments')
        <table class="formal-report-table">
            <thead>
                <tr>
                    <th style="width: 14%;">Schedule</th>
                    <th style="width: 9%;">Ref #</th>
                    <th style="width: 23%;">Patient Name</th>
                    <th style="width: 11%;">Type</th>
                    <th style="width: 21%;">Services</th>
                    <th style="width: 11%;">Status</th>
                    <th class="text-end" style="width: 11%;">Total Bill</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $app)
                @php
                    $isExpired = $app->isExpired();
                    $finalStatus = $isExpired ? 'expired' : $app->status;
                    $typeLabel = $app->batch_id ? 'BULK' : ($app->dependent_id ? 'DEPENDENT' : 'PERSONAL');
                @endphp
                <tr>
                    <td>
                        <div>{{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }}</div>
                        <small class="text-muted">{{ $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : '' }}</small>
                    </td>
                    <td class="font-monospace fw-bold">#{{ $app->id }}</td>
                    <td>
                        <div class="fw-bold uppercase">{{ $app->patient_name }}</div>
                        <small class="text-muted">{{ $app->patient_age }} YRS / {{ strtoupper($app->patient_sex) }}</small>
                    </td>
                    <td><span class="formal-badge">{{ $typeLabel }}</span></td>
                    <td class="small">{{ $app->services->pluck('name')->implode(', ') }}</td>
                    <td>
                        <span class="formal-badge {{ $finalStatus === 'released' ? 'formal-badge-released' : ($finalStatus === 'pending' ? 'formal-badge-pending' : 'formal-badge-expired') }}">
                            {{ strtoupper($finalStatus) }}
                        </span>
                    </td>
                    <td class="text-end fw-bold" style="white-space: nowrap;">₱{{ number_format($app->payment_amount ?: $app->totalPrice(), 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted italic">No appointments recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        {{-- TAB 3: SERVICES --}}
        @if($type === 'services')
        <table class="formal-report-table">
            <thead>
                <tr>
                    <th style="width: 8%;">ID</th>
                    <th style="width: 28%;">Examination Name</th>
                    <th style="width: 16%;">Category</th>
                    <th style="width: 14%;">Price</th>
                    <th style="width: 12%;">Duration</th>
                    <th style="width: 12%;">Bookings</th>
                    <th class="text-end" style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $svc)
                <tr>
                    <td class="font-monospace fw-bold">#{{ $svc->id }}</td>
                    <td class="fw-bold uppercase">{{ $svc->name }}</td>
                    <td><span class="formal-badge">{{ strtoupper($svc->category) }}</span></td>
                    <td class="fw-bold" style="white-space: nowrap;">₱{{ number_format($svc->price, 2) }}</td>
                    <td>{{ $svc->estimated_time }} mins</td>
                    <td class="fw-bold">{{ $svc->appointments_count }}</td>
                    <td class="text-end">
                        <span class="formal-badge {{ $svc->is_available ? 'formal-badge-released' : 'formal-badge-expired' }}">
                            {{ $svc->is_available ? 'ACTIVE' : 'DISABLED' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted italic">No services listed.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        {{-- TAB 4: ACCOUNTS --}}
        @if($type === 'accounts')
        <table class="formal-report-table">
            <thead>
                <tr>
                    <th style="width: 9%;">User ID</th>
                    <th style="width: 24%;">Full Name</th>
                    <th style="width: 26%;">Email Address</th>
                    <th style="width: 16%;">Phone Number</th>
                    <th style="width: 13%;">Access Role</th>
                    <th class="text-end" style="width: 12%;">Account Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                <tr>
                    <td class="font-monospace fw-bold">#{{ $acc->id }}</td>
                    <td class="fw-bold uppercase">{{ $acc->name }}</td>
                    <td>{{ $acc->email }}</td>
                    <td>{{ $acc->phone }}</td>
                    <td><span class="formal-badge">{{ strtoupper($acc->role) }}</span></td>
                    <td class="text-end">
                        <span class="formal-badge {{ $acc->trashed() ? 'formal-badge-expired' : 'formal-badge-paid' }}">
                            {{ $acc->trashed() ? 'DEACTIVATED' : 'ACTIVE' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted italic">No user accounts found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        {{-- TAB 5: SYSTEM AUDIT LOGS (With Explicit Width Allocation and Word-Break All) --}}
        @if($type === 'logs')
        <table class="formal-report-table">
            <thead>
                <tr>
                    <th style="width: 13%;">Timestamp</th>
                    <th style="width: 15%;">Performer</th>
                    <th style="width: 15%;">Event Action</th>
                    <th style="width: 15%;">Target Patient</th>
                    <th style="width: 42%;">Justification & Audit Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="white-space: nowrap;">{{ $log->created_at ? $log->created_at->format('M d, Y') : 'N/A' }}<br><small class="text-muted">{{ $log->created_at ? $log->created_at->format('h:i A') : '' }}</small></td>
                    <td>
                        <div class="fw-bold uppercase">{{ $log->user->name ?? 'System/Deleted' }}</div>
                        <small class="text-muted">({{ strtoupper($log->user->role ?? 'SYSTEM') }})</small>
                    </td>
                    <td>
                        <span class="formal-badge">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="fw-bold uppercase">{{ $log->patient_name }}</td>
                    <td>
                        {{-- Prevents long stack traces, exceptions, and Windows file paths from overflowing --}}
                        <div class="log-reason-content">
                            {{ $log->reason }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted italic">No audit records logged.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        {{-- 5. Automated Clinical Sign-off & Audit Certification Footer --}}
        <div class="formal-sign-section">
            <div class="row align-items-end">
                <div class="col-7">
                    <p class="mb-1 text-muted" style="font-size: 7.5pt; line-height: 1.4;">
                        <strong>CERTIFICATION & LEGAL DISCLAIMER:</strong> This official document contains privileged clinical and financial data generated by Medscreen Diagnostic Laboratory under Republic Act No. 10173 (Philippine Data Privacy Act of 2012). Any unauthorized reproduction is strictly prohibited.
                    </p>
                    <div class="text-muted font-monospace" style="font-size: 7pt;">
                        Security Audit Verification Hash: {{ hash('sha256', $type . now()->timestamp . $adminName) }}
                    </div>
                </div>
                <div class="col-5 text-end">
                    <div class="formal-signature-box ms-auto">
                        <div class="formal-sign-line">
                            <div class="admin-name-signature">{{ $adminName }}</div>
                        </div>
                        <div class="fw-bold text-dark text-uppercase" style="font-size: 8pt; letter-spacing: 0.5px;">
                            {{ $adminRole === 'ADMIN' ? 'System Administrator' : $adminRole }}
                        </div>
                        <div class="text-muted" style="font-size: 7pt;">
                            Authorized Clinical Signatory
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        let isLandscape = false;

        function toggleOrientation() {
            const styleElem = document.getElementById('pageOrientationStyle');
            const canvasElem = document.getElementById('exportCanvas');
            const labelElem = document.getElementById('orientationBtnLabel');

            isLandscape = !isLandscape;

            if (isLandscape) {
                styleElem.innerHTML = '@page { size: A4 landscape; margin: 0; }';
                canvasElem.classList.add('landscape-mode');
                labelElem.innerText = 'Switch to Portrait';
            } else {
                styleElem.innerHTML = '@page { size: A4 portrait; margin: 0; }';
                canvasElem.classList.remove('landscape-mode');
                labelElem.innerText = 'Switch to Landscape';
            }
        }
    </script>
</body>
</html>