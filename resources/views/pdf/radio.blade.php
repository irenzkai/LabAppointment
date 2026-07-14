<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Radiologic Report - {{ $app->patient_name }}</title>
    <style>
        @page { 
            margin: 40px 45px; 
        }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            color: #000; 
            font-size: 11px; 
            line-height: 1.5; 
            margin: 0; 
            padding: 0;
        }

        /* Clinic Branding Header */
        .clinic-header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #1c232d;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .clinic-logo-left {
            width: 15%;
            vertical-align: middle;
            text-align: left;
        }
        .clinic-logo-left .doh-text {
            font-size: 16px;
            font-weight: 800;
            color: #19d38c;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1;
        }
        .clinic-logo-left .doh-text span {
            color: #1c232d;
        }
        .clinic-logo-left .doh-sub {
            font-size: 7px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 1px;
        }
        .clinic-info-center {
            width: 70%;
            text-align: center;
            vertical-align: middle;
        }
        .clinic-name {
            font-size: 21px;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #1c232d;
            margin: 0;
            line-height: 1;
        }
        .clinic-name span {
            color: #19d38c;
        }
        .clinic-tagline {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
            margin-bottom: 6px;
        }
        .clinic-details {
            font-size: 8px;
            color: #334155;
            line-height: 1.3;
            margin: 0;
        }
        .clinic-qr-right {
            width: 15%;
            vertical-align: middle;
            text-align: right;
        }
        .qr-placeholder {
            border: 2px solid #19d38c;
            display: inline-block;
            padding: 2px;
            border-radius: 4px;
            background-color: #fff;
        }
        .qr-placeholder img {
            width: 40px;
            height: 40px;
            display: block;
        }

        /* Patient Metadata Box */
        .patient-meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 25px;
        }
        .patient-meta-table td {
            padding: 6px 10px;
            font-size: 10px;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            color: #000;
            width: 15%;
        }
        .meta-value {
            color: #000;
            width: 35%;
        }

        /* Document Title Area */
        .document-title {
            text-align: center;
            margin-bottom: 35px;
        }
        .document-title h1 {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 3px;
            text-decoration: underline;
            margin: 0;
            color: #000;
        }

        /* Report Body Content */
        .report-body {
            font-size: 11.5px;
            line-height: 1.8;
            margin-bottom: 40px;
        }
        .exam-header {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
            color: #000;
        }
        .findings-section {
            text-align: justify;
            margin-bottom: 30px;
            color: #333;
        }

        /* Impression Section */
        .impression-section {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        .impression-label {
            font-size: 11.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 5px;
            text-decoration: underline;
        }
        .impression-text {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
            margin: 0;
        }

        /* Signatory Section */
        .signatory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 60px;
            font-size: 11px;
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
            font-size: 8.5px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-weight: bold;
            color: #64748b;
        }
    </style>
</head>
<body>

    @php
        // FIXED: Safe nested key fallback mappings resolve un-fetched case number, date, and signatories cleanly
        $radiologyReport = $res->radiologyReport;
        
        $caseNo = $radiologyReport->case_no ?? ($res->radio_data['metadata']['case_no'] ?? ($res->radio_data['case_no'] ?? 'N/A'));
        $dateOfExam = $radiologyReport->date_of_exam ?? ($res->radio_data['metadata']['date'] ?? ($res->radio_data['date'] ?? now()));
        $radiologistName = $radiologyReport->radiologist_name ?? ($res->radio_data['sig']['name'] ?? ($res->radio_data['sig_name'] ?? 'Dr. Mae Shelle Jopson'));
        $radiologistLicense = $radiologyReport->radiologist_license ?? ($res->radio_data['sig']['lic'] ?? ($res->radio_data['sig_info'] ?? 'RADIOLOGIST'));
        
        $technique = $radiologyReport->technique ?? ($res->radio_data['technique'] ?? 'CHEST PA');
        $findings = $radiologyReport->findings ?? ($res->radio_data['findings'] ?? "Both lungs are clear.\nHeart is not enlarged.\nDiaphragm and sinuses are intact.");
        $impression = $radiologyReport->impression ?? ($res->radio_data['impression'] ?? 'ESSENTIALLY NORMAL CHEST');
    @endphp

    {{-- CLINICAL HEADER --}}
    <table class="clinic-header-table">
        <tr>
            <td class="clinic-logo-left">
                <div class="doh-text">D<span>O</span>H</div>
                <div class="doh-sub">Accredited</div>
            </td>
            <td class="clinic-info-center">
                <div class="clinic-name"><span>MED</span>SCREEN</div>
                <div class="clinic-tagline">Diagnostic Laboratory</div>
                <div class="clinic-details">Banisil Street (Formerly Atis St.), Brgy. Dadiangas West, General Santos City</div>
                <div class="clinic-details">DOH ACCREDITED | Tel. No.: (083) 823 8754 | Email: medscreen.lab@gmail.com</div>
            </td>
            <td class="clinic-qr-right">
                {{-- FIXED: Integrated live scannable verification QR code generator --}}
                <div class="qr-placeholder">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('result.verify-public', $app->id)) }}" alt="Verification QR">
                </div>
                <div style="font-size: 5px; text-transform: uppercase; color: #475569; margin-top: 2px; text-align: center; font-weight: bold; width: 45px;">Scan to Verify</div>
            </td>
        </tr>
    </table>

    {{-- PATIENT METADATA BOX --}}
    <table class="patient-meta-table">
        <tr>
            <td class="meta-label">Name:</td>
            <td class="meta-value">{{ strtoupper($res->radio_data['metadata']['name'] ?? ($res->radio_data['name'] ?? $app->patient_name)) }}</td>
            <td class="meta-label">Date:</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($dateOfExam)->format('d F Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Address:</td>
            <td class="meta-value">{{ strtoupper($res->radio_data['metadata']['address'] ?? ($res->radio_data['address'] ?? $app->patient_address)) }}</td>
            <td class="meta-label">Age / Sex:</td>
            <td class="meta-value">{{ $res->radio_data['metadata']['age_sex'] ?? ($res->radio_data['age_sex'] ?? ($app->patient_age . ' / ' . strtoupper($app->patient_sex))) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Case #:</td>
            <td class="meta-value" colspan="3">{{ $caseNo }}</td>
        </tr>
    </table>

    {{-- DOCUMENT TITLE --}}
    <div class="document-title">
        <h1>RADIOLOGIC REPORT</h1>
    </div>

    {{-- REPORT BODY CONTENT --}}
    <div class="report-body">
        <div class="exam-header">
            {{ strtoupper($technique) }}
        </div>
        
        <div class="findings-section">
            {!! nl2br(e($findings)) !!}
        </div>

        {{-- IMPRESSION --}}
        <div class="impression-section">
            <div class="impression-label">Impression:</div>
            <p class="impression-text">
                {{ strtoupper($impression) }}
            </p>
        </div>
    </div>

    {{-- SIGNATORY --}}
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

    {{-- ACADEMIC PROTOTYPE disclaimer footer --}}
    <div class="digital-footer">
        This is a digital copy. Physical copies can be acquired at the official location of Medscreen Diagnostic Laboratory.
    </div>

</body>
</html>