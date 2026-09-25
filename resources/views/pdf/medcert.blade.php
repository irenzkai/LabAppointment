<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Medical Certificate - {{ $app->patient_name }}</title>
    <style>
        @page { 
            margin: 40px 45px; 
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            color: #000; 
            font-size: 11px; 
            line-height: 1.5; 
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
            border-bottom: 2.5px solid #1c232d;
            padding-bottom: 10px;
            margin-bottom: 16px;
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

        /* Certificate Metadata Top Bar */
        .meta-table {
            width: 100%;
            margin-top: 8px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .meta-table td {
            vertical-align: middle;
        }
        .underline-value {
            border-bottom: 1.2px solid #000;
            padding: 0 10px;
            display: inline-block;
            min-width: 110px;
            text-align: center;
            font-weight: bold;
        }

        /* Title Area */
        .document-title {
            text-align: center;
            margin-bottom: 25px;
        }
        .document-title h1 {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 2px;
            text-decoration: underline;
            margin: 0;
            color: #000;
        }

        /* Body Content */
        .salutation {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }
        .cert-body-text {
            font-size: 11.5px;
            line-height: 2.5;
            text-align: justify;
            margin-bottom: 25px;
        }
        .fill-line {
            display: inline-block;
            text-align: center;
            border-bottom: 1.2px solid #000;
            font-weight: bold;
            padding: 0 4px;
        }

        /* Findings Box */
        .findings-box {
            text-align: center;
            margin: 25px 0;
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1.2px solid #000;
            padding-bottom: 10px;
            letter-spacing: 0.5px;
        }

        /* Remarks Section */
        .remarks-table {
            width: 100%;
            margin-top: 25px;
            margin-bottom: 35px;
            font-size: 11.5px;
        }
        .remarks-table td {
            vertical-align: middle;
        }
        .remarks-label {
            width: 12%;
            font-weight: bold;
        }
        .remarks-value {
            width: 88%;
            border-bottom: 1.2px solid #000;
            font-weight: bold;
            padding-bottom: 4px;
        }

        /* Release Clause */
        .release-clause {
            font-size: 11px;
            line-height: 2.2;
            text-align: justify;
            margin-bottom: 45px;
        }
        .release-fill-line {
            display: inline-block;
            text-align: center;
            border-bottom: 1.2px solid #000;
            font-weight: bold;
            padding: 0 15px;
            min-width: 220px;
        }

        /* Signatory Section */
        .signatory-table {
            width: 100%;
            margin-top: 40px;
            font-size: 11px;
        }
        .signature-placeholder {
            font-family: 'Georgia', serif;
            font-style: italic;
            font-size: 17px;
            margin-bottom: -4px;
            color: #2c3e50;
            text-align: center;
        }
        .signature-line {
            border-top: 1.5px solid #000;
            font-weight: bold;
            padding-top: 6px;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .signature-sub {
            color: #444;
            font-size: 9px;
            margin-top: 3px;
            text-align: center;
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
    $medCert = $res->medCert;
    $certNo = $medCert?->cert_no ?? ($res->med_cert_data['metadata']['cert_no'] ?? ($res->med_cert_data['cert_no'] ?? '---'));
    $dateOfIssue = $medCert?->date_of_issue ?? ($res->med_cert_data['metadata']['date'] ?? ($res->med_cert_data['date'] ?? now()));
    $physicianName = $medCert?->physician_name ?? ($res->med_cert_data['sig']['name'] ?? ($res->med_cert_data['sig_name'] ?? 'CLARISSE FAYE D. ARMADA, RMT, MD'));
    $physicianLicense = $medCert?->physician_license ?? ($res->med_cert_data['sig']['lic'] ?? ($res->med_cert_data['sig_info'] ?? 'PHYSICIAN / License No.: 0171334'));
    
    $issuedTo = $medCert?->issued_to ?? ($res->med_cert_data['issued_to'] ?? ($res->med_cert_data['metadata']['name'] ?? $app->patient_name));
    $findings = $medCert?->findings ?? ($res->med_cert_data['findings'] ?? 'ESSENTIALLY NORMAL FINDINGS');
    $remarks = $medCert?->remarks ?? ($res->med_cert_data['remarks'] ?? 'CLASS (A) - PHYSICALLY FIT');

    // Cryptographically signed URL prevents 403 Invalid Signature errors
    $signedVerificationUrl = \Illuminate\Support\Facades\URL::signedRoute('result.verify-public', ['appointment' => $app->id]);
@endphp

{{-- CLINICAL HEADER --}}
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

{{-- CERTIFICATE METADATA --}}
<table class="meta-table">
    <tr>
        <td style="text-align: left; width: 50%;">
            <strong>Cert. No.:</strong> 
            <span class="underline-value">{{ $certNo }}</span>
        </td>
        <td style="text-align: right; width: 50%;">
            <strong>Date:</strong> 
            <span class="underline-value">{{ \Carbon\Carbon::parse($dateOfIssue)->format('d F Y') }}</span>
        </td>
    </tr>
</table>

{{-- DOCUMENT TITLE --}}
<div class="document-title">
    <h1>MEDICAL CERTIFICATE</h1>
</div>

{{-- SALUTATION --}}
<div class="salutation">TO WHOM IT MAY CONCERN:</div>

{{-- BODY TEXT --}}
<div class="cert-body-text">
    This is to certify that 
    <span class="fill-line" style="min-width: 250px;">&nbsp;{{ strtoupper($res->med_cert_data['metadata']['name'] ?? ($res->med_cert_data['name'] ?? $app->patient_name)) }}&nbsp;</span>,
    <span class="fill-line" style="min-width: 45px;">&nbsp;{{ $res->med_cert_data['metadata']['age'] ?? ($res->med_cert_data['age'] ?? $app->patient_age) }}&nbsp;</span> years old, 
    <span class="fill-line" style="min-width: 75px;">&nbsp;{{ strtoupper($res->med_cert_data['metadata']['sex'] ?? ($res->med_cert_data['sex'] ?? $app->patient_sex)) }}&nbsp;</span> 
    residing at 
    <span class="fill-line" style="min-width: 300px;">&nbsp;{{ strtoupper($res->med_cert_data['metadata']['address'] ?? ($res->med_cert_data['address'] ?? $app->patient_address)) }}&nbsp;</span> 
    has been examined on 
    <span class="fill-line" style="min-width: 140px;">&nbsp;{{ \Carbon\Carbon::parse($res->med_cert_data['metadata']['tested_date'] ?? ($res->med_cert_data['exam_date'] ?? $app->tested_at))->format('d F Y') }}&nbsp;</span> 
    with the following findings and/or diagnosis:
</div>

{{-- FINDINGS BOX --}}
<div class="findings-box">
    {!! nl2br(e($findings)) !!}
</div>

{{-- REMARKS --}}
<table class="remarks-table">
    <tr>
        <td class="remarks-label">REMARKS:</td>
        <td class="remarks-value">
            {{ strtoupper($remarks) }}
        </td>
    </tr>
</table>

{{-- RELEASE CLAUSE --}}
<div class="release-clause">
    This certification is being issued to 
    <span class="release-fill-line">&nbsp;{{ strtoupper($issuedTo) }}&nbsp;</span> 
    for whatever legal purposes it may serve him/her best. Not for medico-legal or court purposes.
</div>

{{-- SIGNATORY --}}
<table class="signatory-table">
    <tr>
        <td style="width: 55%;"></td>
        <td style="width: 45%;">
            <div class="signature-placeholder">
                {{ $physicianName }}
            </div>
            <div class="signature-line">
                {{ strtoupper($physicianName) }}
            </div>
            <div class="signature-sub">
                {{ strtoupper($physicianLicense) }}
            </div>
        </td>
    </tr>
</table>

{{-- DIGITAL FOOTER --}}
<div class="digital-footer">
    This is a digital copy. Physical copies can be acquired at the official location of Medscreen Diagnostic Laboratory.
</div>
</body>
</html>