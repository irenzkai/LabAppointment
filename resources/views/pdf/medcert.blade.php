<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Medical Certificate - {{ $app->patient_name }}</title>
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

        /* Certificate Metadata */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 35px;
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
            margin-bottom: 45px;
        }
        .document-title h1 {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 3px;
            text-decoration: underline;
            margin: 0;
            color: #000;
        }

        /* Body Content */
        .salutation {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
        }
        .cert-body-text {
            font-size: 12px;
            line-height: 2.6;
            text-align: justify;
            margin-bottom: 35px;
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
            margin: 40px 0;
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1.2px solid #000;
            padding-bottom: 12px;
            letter-spacing: 1px;
        }

        /* Remarks Section */
        .remarks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
            margin-bottom: 45px;
            font-size: 12px;
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
            margin-bottom: 60px;
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
            border-collapse: collapse;
            margin-top: 60px;
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
        // FIXED: Safe nested key fallback mappings resolve un-fetched cert and signature values cleanly
        $medCert = $res->medCert;
        
        $certNo = $medCert->cert_no ?? ($res->med_cert_data['metadata']['cert_no'] ?? ($res->med_cert_data['cert_no'] ?? '---'));
        $dateOfIssue = $medCert->date_of_issue ?? ($res->med_cert_data['metadata']['date'] ?? ($res->med_cert_data['date'] ?? now()));
        $physicianName = $medCert->physician_name ?? ($res->med_cert_data['sig']['name'] ?? ($res->med_cert_data['sig_name'] ?? 'Dr. Clarisse Faye Armada'));
        $physicianLicense = $medCert->physician_license ?? ($res->med_cert_data['sig']['lic'] ?? ($res->med_cert_data['sig_info'] ?? 'License No.: 0171334'));
        
        $issuedTo = $medCert->issued_to ?? ($res->med_cert_data['issued_to'] ?? ($res->med_cert_data['metadata']['name'] ?? $app->patient_name));
        $findings = $medCert->findings ?? ($res->med_cert_data['findings'] ?? 'ESSENTIALLY NORMAL FINDINGS');
        $remarks = $medCert->remarks ?? ($res->med_cert_data['remarks'] ?? 'CLASS (A) - PHYSICALLY FIT');
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
        <span class="fill-line" style="min-width: 260px;">&nbsp;{{ strtoupper($res->med_cert_data['metadata']['name'] ?? ($res->med_cert_data['name'] ?? $app->patient_name)) }}&nbsp;</span>, 
        <span class="fill-line" style="min-width: 50px;">&nbsp;{{ $res->med_cert_data['metadata']['age'] ?? ($res->med_cert_data['age'] ?? $app->patient_age) }}&nbsp;</span> years old, 
        <span class="fill-line" style="min-width: 80px;">&nbsp;{{ strtoupper($res->med_cert_data['metadata']['sex'] ?? ($res->med_cert_data['sex'] ?? $app->patient_sex)) }}&nbsp;</span> 
        residing at 
        <span class="fill-line" style="min-width: 320px;">&nbsp;{{ strtoupper($res->med_cert_data['metadata']['address'] ?? ($res->med_cert_data['address'] ?? $app->patient_address)) }}&nbsp;</span> 
        has been examined on 
        <span class="fill-line" style="min-width: 150px;">&nbsp;{{ \Carbon\Carbon::parse($res->med_cert_data['metadata']['tested_date'] ?? ($res->med_cert_data['exam_date'] ?? $app->tested_at))->format('d F Y') }}&nbsp;</span> 
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

    {{-- ACADEMIC PROTOTYPE disclaimer footer --}}
    <div class="digital-footer">
        This is a digital copy. Physical copies can be acquired at the official location of Medscreen Diagnostic Laboratory.
    </div>

</body>
</html>