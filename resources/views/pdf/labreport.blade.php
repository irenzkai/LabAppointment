<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laboratory Result(s) - {{ $app->patient_name }}</title>
    <style>
        @page { 
            margin: 30px 35px; 
        }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            color: #000; 
            font-size: 9.5px; 
            line-height: 1.4; 
            margin: 0; 
            padding: 0;
        }

        /* Clinic Branding Header */
        .clinic-header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1c232d;
            padding-bottom: 8px;
            margin-bottom: 12px;
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
            font-size: 20px;
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
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
            margin-bottom: 4px;
        }
        .clinic-details {
            font-size: 7.5px;
            color: #334155;
            line-height: 1.2;
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
            border: 1.5px solid #1c232d;
            margin-bottom: 12px;
        }
        .patient-meta-table td {
            border: 1px solid #1c232d;
            padding: 4px 6px;
            font-size: 9px;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            color: #475569;
            width: 12%;
            background-color: #f8fafc;
        }
        .meta-value {
            font-weight: bold;
            color: #000;
            width: 21.3%;
        }

        /* Section Title Bar */
        .section-title-bar {
            background-color: #1c232d;
            color: #fff;
            text-align: center;
            padding: 5px 0;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 12px;
            border-radius: 2px;
        }

        /* Result Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            font-size: 8.5px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 1.5px solid #1c232d;
            padding-bottom: 4px;
            background-color: #f8fafc;
            padding: 5px;
        }
        .data-table td {
            padding: 5px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: middle;
        }
        .exam-name {
            font-weight: bold;
            color: #1c232d;
        }
        .exam-value {
            font-weight: bold;
            color: #000;
        }
        .exam-range {
            color: #64748b;
            font-style: italic;
        }

        /* Clinical Remarks/Note Footer */
        .notes-section {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8px;
            color: #475569;
            line-height: 1.5;
        }

        /* Signatories Row */
        .signatory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }
        .sig-box {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 15px;
        }
        .sig-handwritten {
            font-family: 'Georgia', serif;
            font-style: italic;
            font-size: 13px;
            margin-bottom: -3px;
            color: #334155;
        }
        .sig-line {
            border-top: 1px dashed #475569;
            font-weight: bold;
            padding-top: 5px;
            text-transform: uppercase;
            font-size: 8.5px;
            color: #000;
        }
        .sig-sub {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 1.5px;
        }

        /* Fixed Disclaimers */
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
        // Resolve relational signatories fallback cleanly to support both database formats
        $results = $res->labResults;
        $details = $res->labDetails;
        
        $releasedByName = $details->released_by_name ?? ($res->lab_data['sig']['rel_name'] ?? 'PERSON A');
        $releasedByLicense = $details->released_by_license ?? ($res->lab_data['sig']['rel_lic'] ?? 'MEDTECH / 09354237');
        $validatedByName = $details->validated_by_name ?? ($res->lab_data['sig']['val1_name'] ?? 'PERSON B');
        $validatedByLicense = $details->validated_by_license ?? ($res->lab_data['sig']['val1_lic'] ?? 'MEDTECH / 043524237');
        $pathologistName = $details->validated_by_name_2 ?? ($res->lab_data['sig']['val2_name'] ?? 'INGAYON, NENA SALCEDO, MD, FPSP, MHC');
        $pathologistLicense = $details->validated_by_license_2 ?? ($res->lab_data['sig']['val2_lic'] ?? 'PATHOLOGIST / License No.: 0092052');
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
                {{-- FIXED: Integrated live scannable validation QR code generator --}}
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
            <td class="meta-value">{{ strtoupper($app->patient_name) }}</td>
            <td class="meta-label">Date:</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($app->tested_at ?? $app->appointment_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Address:</td>
            <td class="meta-value">{{ strtoupper($app->patient_address) }}</td>
            <td class="meta-label">Age / Sex:</td>
            <td class="meta-value">{{ $app->patient_age }} / {{ strtoupper($app->patient_sex) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Case #:</td>
            <td class="meta-value">{{ $details->case_no ?? ($res->lab_data['metadata']['case_no'] ?? 'N/A') }}</td>
            <td class="meta-label">Requested By:</td>
            <td class="meta-value">{{ strtoupper($app->organization_name ?? 'INDIVIDUAL') }}</td>
        </tr>
    </table>

    {{-- DYNAMIC TITLE BAR --}}
    <div class="section-title-bar">LABORATORY RESULT(S)</div>

    {{-- SEQUENTIAL LINE-BY-LINE RESULT LISTING --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 45%;">Examination Parameter</th>
                <th style="width: 25%; text-align: center;">Observed Result</th>
                <th style="width: 30%; text-align: right;">Reference Range</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $r)
                <tr>
                    <td class="exam-name">{{ strtoupper($r->parameter_name) }}</td>
                    <td class="exam-value" style="text-align: center;">{{ $r->observed_value }}</td>
                    <td class="exam-range" style="text-align: right;">{{ $r->reference_range ?? 'NONE' }}</td>
                </tr>
            @empty
                {{-- Retroactive Fallback: Fall back to legacy lab_data JSON structure if relational tables are empty --}}
                @if(!empty($res->lab_data['results']))
                    @foreach($res->lab_data['results'] as $r)
                        <tr>
                            <td class="exam-name">{{ strtoupper($r['name']) }}</td>
                            <td class="exam-value" style="text-align: center;">{{ $r['value'] }}</td>
                            <td class="exam-range" style="text-align: right;">{{ $r['ref_range'] ?? ($r['ref'] ?? 'NONE') }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3" style="text-align: center; color: #64748b; font-style: italic; padding: 15px;">No clinical parameters recorded.</td>
                    </tr>
                @endif
            @endforelse
        </tbody>
    </table>

    {{-- CLINICAL REMARKS/NOTES --}}
    <div class="notes-section">
        <strong>NOTE: FOR SCREENING PURPOSES ONLY.</strong><br>
        <strong>Reminder:</strong> Tests left <span style="text-decoration: underline;">blank</span> or without recorded result(s) are considered <span style="text-decoration: underline;">not performed or not requested by the patient</span>.<br>
        <strong>Important Notice:</strong> This laboratory report is designed for interpretation by a qualified medical doctor in conjunction with clinical assessment and other diagnostic procedures.
    </div>

    {{-- CLINICAL SIGN-OFF BLOCKS (3 Signatories) --}}
    <table class="signatory-table">
        <tr>
            {{-- Released By --}}
            <td class="sig-box">
                <div class="sig-handwritten">{{ $releasedByName }}</div>
                <div class="sig-line">{{ strtoupper($releasedByName) }}</div>
                <div class="sig-sub">{{ strtoupper($releasedByLicense) }}</div>
            </td>
            {{-- Validated By --}}
            <td class="sig-box">
                <div class="sig-handwritten">{{ $validatedByName }}</div>
                <div class="sig-line">{{ strtoupper($validatedByName) }}</div>
                <div class="sig-sub">{{ strtoupper($validatedByLicense) }}</div>
            </td>
            {{-- Pathologist --}}
            <td class="sig-box">
                <div class="sig-handwritten">{{ $pathologistName }}</div>
                <div class="sig-line">{{ strtoupper($pathologistName) }}</div>
                <div class="sig-sub">{{ strtoupper($pathologistLicense) }}</div>
            </td>
        </tr>
    </table>

    {{-- ACADEMIC PROTOTYPE disclaimer footer --}}
    <div class="digital-footer">
        This is a digital copy. Physical copies can be acquired at the official location of Medscreen Diagnostic Laboratory.
    </div>

</body>
</html>