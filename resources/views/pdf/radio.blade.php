<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Radiologic Report - {{ $app->patient_name }}</title>
    <style>
        @page { 
            size: A4 portrait;
            margin: 35px 45px; 
        }
        * {
            box-sizing: border-box;
        }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            color: #000; 
            font-size: 10.5px; 
            line-height: 1.45; 
            margin: 0; 
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Clinic Branding Header */
        .clinic-header-table {
            width: 100%;
            border-bottom: 2px solid #1c232d;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .clinic-logo-left {
            width: 16%;
            vertical-align: middle;
            text-align: left;
        }
        .doh-badge-box {
            display: inline-block;
            text-align: center;
            border: 1.2px solid #19d38c;
            padding: 3px 6px;
            border-radius: 4px;
            background-color: #f8fafc;
        }
        .doh-text {
            font-size: 12pt;
            font-weight: 900;
            color: #19d38c;
            line-height: 1;
            margin: 0;
        }
        .doh-text span {
            color: #1c232d;
        }
        .doh-sub {
            font-size: 5.8pt;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1;
            margin-top: 1px;
        }
        .clinic-info-center {
            width: 68%;
            text-align: center;
            vertical-align: middle;
        }
        .clinic-name {
            font-size: 16pt;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #1c232d;
            margin: 0;
            line-height: 1.1;
        }
        .clinic-name span {
            color: #19d38c;
        }
        .clinic-subname {
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #1c232d;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .clinic-details {
            font-size: 7.2pt;
            color: #334155;
            line-height: 1.3;
            margin: 0;
        }
        .clinic-header-right {
            width: 16%;
            vertical-align: middle;
            text-align: right;
        }
        .qr-wrapper {
            display: inline-block;
            text-align: center;
        }
        .qr-wrapper img {
            width: 42px;
            height: 42px;
            display: block;
            margin: 0 auto;
        }
        .qr-sub {
            font-size: 4.8pt;
            text-transform: uppercase;
            color: #475569;
            margin-top: 2px;
            font-weight: bold;
            text-align: center;
        }

        /* Shaded Title Banner */
        .main-title-bar {
            background-color: #cbd5e1;
            border-top: 1.5px solid #94a3b8;
            border-bottom: 1.5px solid #94a3b8;
            text-align: center;
            font-size: 13pt;
            font-weight: 900;
            letter-spacing: 2px;
            color: #1c232d;
            padding: 4px 0;
            margin-top: 4px;
            margin-bottom: 16px;
        }

        /* Patient Metadata Box */
        .patient-meta-table {
            width: 100%;
            margin-bottom: 22px;
        }
        .patient-meta-table td {
            padding: 3.5px 6px;
            font-size: 9.5pt;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            color: #000;
            width: 12%;
        }
        .meta-value {
            color: #000;
            font-weight: 500;
        }

        /* Report Body Content */
        .report-body {
            font-size: 11px;
            line-height: 1.7;
            margin-bottom: 30px;
        }
        .exam-header {
            font-size: 12.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 12px;
            color: #000;
        }
        .findings-section {
            text-align: left;
            margin-bottom: 25px;
            color: #1c232d;
            line-height: 1.7;
        }

        /* Impression Section */
        .impression-section {
            margin-top: 20px;
        }
        .impression-label {
            font-size: 11px;
            font-style: italic;
            text-decoration: underline;
            color: #000;
            margin-bottom: 4px;
        }
        .impression-text {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            color: #000;
            margin: 0;
        }

        /* Signatory Section */
        .signatory-table {
            width: 100%;
            margin-top: 40px;
            font-size: 10.5px;
        }
        .signature-placeholder {
            font-family: 'Georgia', serif;
            font-style: italic;
            font-size: 16px;
            margin-bottom: -4px;
            color: #2c3e50;
            text-align: center;
        }
        .signature-line {
            border-top: 1.5px solid #000;
            font-weight: bold;
            padding-top: 5px;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .signature-sub {
            color: #444;
            font-size: 9px;
            margin-top: 2px;
            text-align: center;
            text-transform: uppercase;
        }

        /* Footer */
        .digital-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-weight: bold;
            color: #64748b;
        }
    </style>
</head>
<body>
@php
    $report = $res->radiologyReport;
    $caseNo = $report?->case_no ?? ($res->radio_data['metadata']['case_no'] ?? ($res->radio_data['case_no'] ?? 'N/A'));
    $dateOfExam = $report?->date_of_exam ?? ($res->radio_data['metadata']['date'] ?? ($res->radio_data['date'] ?? now()));
    $technique = $report?->technique ?? ($res->radio_data['technique'] ?? 'CHEST PA');
    $findings = $report?->findings ?? ($res->radio_data['findings'] ?? 'Both lungs are clear.\nHeart is not enlarged.\nDiaphragm and sinuses are intact.');
    $impression = $report?->impression ?? ($res->radio_data['impression'] ?? 'ESSENTIALLY NORMAL CHEST');
    $radiologistName = $report?->radiologist_name ?? ($res->radio_data['sig']['name'] ?? ($res->radio_data['sig_name'] ?? 'DR. MAE SHELLE D. JOPSON, DPBR'));
    $radiologistLicense = $report?->radiologist_license ?? ($res->radio_data['sig']['lic'] ?? ($res->radio_data['sig_info'] ?? 'RADIOLOGIST'));

    // Cryptographically signed URL prevents 403 Invalid Signature errors
    $signedVerificationUrl = \Illuminate\Support\Facades\URL::signedRoute('result.verify-public', ['appointment' => $app->id]);
@endphp

@if($renderManualReport)
    {{-- A. RENDER MANUALLY ENTERED REPORT SHEET --}}
    <table class="clinic-header-table">
        <tr>
            <td class="clinic-logo-left">
                <div class="doh-badge-box">
                    <div class="doh-text">D<span>O</span>H</div>
                    <div class="doh-sub">Accredited</div>
                </div>
            </td>
            <td class="clinic-info-center">
                <div class="clinic-name"><span>MED</span>SCREEN</div>
                <div class="clinic-subname">Diagnostic Laboratory</div>
                <div class="clinic-details">Banisil Street (Formerly Atis Street), Brgy. Dadiangas West, General Santos City</div>
                <div class="clinic-details">Tel. No.: (083) 823 8754 ; Email: medscreen.lab@gmail.com</div>
            </td>
            <td class="clinic-header-right">
                <div class="qr-wrapper">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($signedVerificationUrl) }}" alt="QR">
                    <div class="qr-sub">Scan to Verify</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="main-title-bar">RADIOLOGIC REPORT</div>

    <table class="patient-meta-table">
        <tr>
            <td class="meta-label">Name:</td>
            <td class="meta-value">{{ strtoupper($app->patient_name) }}</td>
            <td class="meta-label">Date:</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($dateOfExam)->format('M d, Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Address:</td>
            <td class="meta-value">{{ strtoupper($app->patient_address) }}</td>
            <td class="meta-label">Age/Sex:</td>
            <td class="meta-value">{{ $app->patient_age }} / {{ strtoupper($app->patient_sex) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Case #:</td>
            <td class="meta-value" colspan="3">{{ $caseNo }}</td>
        </tr>
    </table>

    <div class="report-body">
        <div class="exam-header">
            {{ strtoupper($technique) }}
        </div>
        <div class="findings-section">
            {!! nl2br(e($findings)) !!}
        </div>
        <div class="impression-section">
            <div class="impression-label">Impression:</div>
            <p class="impression-text">
                {{ strtoupper($impression) }}
            </p>
        </div>
    </div>

    <table class="signatory-table">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%;">
                <div class="signature-placeholder">
                    {{ $radiologistName }}
                </div>
                <div class="signature-line">
                    {{ strtoupper($radiologistName) }}
                </div>
                <div class="signature-sub">
                    {{ strtoupper($radiologistLicense) }}
                </div>
            </td>
        </tr>
    </table>

    <div class="digital-footer">
        This is a digital copy. Physical copies can be acquired at the official location of Medscreen Diagnostic Laboratory.
    </div>
@else
    {{-- B. RENDER UPLOADED SCANNED REPORT FILE --}}
    @if(!empty($reportPages))
        @foreach($reportPages as $index => $pageData)
            <div style="page-break-before: {{ $index === 0 ? 'avoid' : 'always' }}; text-align: center; margin: 0; padding: 0;">
                <img src="{{ $pageData }}" style="width: 100%; height: auto; max-height: 100%;" alt="Scanned Report Page">
            </div>
        @endforeach
    @endif
@endif

{{-- C. APPEND UPLOADED X-RAY SCAN PAGES --}}
@if(!empty($xrayPages))
    @foreach($xrayPages as $pageData)
        <div style="page-break-before: always; text-align: center; margin: 0; padding: 0;">
            <img src="{{ $pageData }}" style="width: 100%; height: auto; max-height: 100%;" alt="X-Ray Scan Page">
        </div>
    @endforeach
@endif
</body>
</html>