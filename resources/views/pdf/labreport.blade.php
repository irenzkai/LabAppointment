<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laboratory Result(s) - {{ $app->patient_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 12mm 6mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #000000;
            font-size: 7.2pt;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        /* ----------------------------------------------------
           CLINIC HEADER
        ---------------------------------------------------- */
        .clinic-header-table {
            width: 100%;
            border-bottom: 2px solid #1c232d;
            padding-bottom: 6px;
            margin-bottom: 6px;
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
            font-size: 11pt;
            font-weight: 900;
            color: #19d38c;
            line-height: 1;
            margin: 0;
        }
        .doh-text span {
            color: #1c232d;
        }
        .doh-sub {
            font-size: 5.5pt;
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
            font-size: 14pt;
            font-weight: 900;
            letter-spacing: 1px;
            color: #1c232d;
            margin: 0;
            line-height: 1.1;
        }
        .clinic-name span {
            color: #19d38c;
        }
        .clinic-details {
            font-size: 6.5pt;
            color: #334155;
            line-height: 1.25;
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

        /* ----------------------------------------------------
           PATIENT METADATA BOX
        ---------------------------------------------------- */
        .patient-meta-table {
            border: 1.2px solid #1c232d;
            margin-bottom: 4px;
        }
        .patient-meta-table td {
            border: 1px solid #cbd5e1;
            padding: 2.5px 5px;
            font-size: 7pt;
            vertical-align: middle;
        }
        .meta-lbl {
            font-weight: bold;
            color: #1e293b;
            width: 12%;
            background-color: #f8fafc;
        }
        .meta-val {
            font-weight: bold;
            color: #000000;
            width: 38%;
        }

        /* ----------------------------------------------------
           SECTION TITLE BARS
        ---------------------------------------------------- */
        .main-title-bar {
            border-top: 2px solid #1c232d;
            border-bottom: 2px solid #1c232d;
            text-align: center;
            font-size: 8.5pt;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #1c232d;
            padding: 2px 0;
            margin-bottom: 3px;
        }
        .sub-section-title {
            text-align: center;
            font-size: 7.8pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #8b0000;
            border-bottom: 1.2px solid #19d38c;
            padding: 1.5px 0;
            margin-top: 2px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        /* ----------------------------------------------------
           TEST TABLES & FILL-IN UNDERLINES
        ---------------------------------------------------- */
        .form-grid-table {
            width: 100%;
            margin-bottom: 2px;
        }
        .form-grid-table td {
            vertical-align: top;
            padding: 0 3px;
        }
        .test-row-table {
            width: 100%;
        }
        .test-row-table td {
            padding: 1px 1px;
            font-size: 6.8pt;
            vertical-align: middle;
        }
        .lbl-cell {
            font-weight: bold;
            color: #1e293b;
            white-space: nowrap;
            text-align: left;
        }
        .val-cell {
            text-align: center;
            padding: 0 2px;
        }
        .val-underline {
            border-bottom: 1px solid #000000;
            display: inline-block;
            min-width: 48px;
            height: 11px;
            line-height: 11px;
            text-align: center;
            font-weight: bold;
            color: #000000;
            font-size: 7pt;
        }
        .ref-cell {
            color: #475569;
            font-size: 6.2pt;
            white-space: nowrap;
            text-align: left;
        }
        .sub-header-lbl {
            font-size: 6.5pt;
            font-weight: bold;
            font-style: italic;
            color: #1c232d;
            padding-top: 2px;
            padding-bottom: 1px;
        }

        /* ----------------------------------------------------
           SEROLOGY TABLE
        ---------------------------------------------------- */
        .serology-table {
            border: 1px solid #94a3b8;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .serology-table th {
            background-color: #f1f5f9;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #94a3b8;
            padding: 2px 4px;
            text-align: left;
        }
        .serology-table td {
            padding: 1.5px 4px;
            font-size: 6.8pt;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        /* ----------------------------------------------------
           OTHERS / CLINICAL CHEMISTRY DYNAMIC TABLE
        ---------------------------------------------------- */
        .others-table {
            border: 1px solid #94a3b8;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .others-table th {
            background-color: #f8fafc;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #94a3b8;
            padding: 2px 4px;
        }
        .others-table td {
            padding: 1.5px 4px;
            font-size: 6.8pt;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        /* ----------------------------------------------------
           CLINICAL NOTES & LEGAL DISCLAIMERS
        ---------------------------------------------------- */
        .notes-container {
            border-top: 1px solid #cbd5e1;
            padding-top: 2px;
            margin-top: 2px;
            font-size: 5.8pt;
            color: #334155;
            line-height: 1.25;
        }
        .notes-container strong {
            color: #0f172a;
        }

        /* ----------------------------------------------------
           SIGNATORIES BLOCK
        ---------------------------------------------------- */
        .signatory-table {
            margin-top: 8px;
            border-collapse: collapse;
        }
        .sig-col {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }
        .sig-handwritten {
            font-family: 'Georgia', serif;
            font-style: italic;
            font-size: 9.5pt;
            color: #1e3a8a;
            height: 16px;
            line-height: 16px;
        }
        .sig-line {
            border-top: 1px solid #000000;
            font-weight: bold;
            font-size: 6.8pt;
            text-transform: uppercase;
            padding-top: 2px;
            margin-top: 1px;
            color: #000000;
        }
        .sig-sub {
            font-size: 5.8pt;
            color: #475569;
            text-transform: uppercase;
            line-height: 1.2;
        }

        /* ----------------------------------------------------
           DIGITAL FOOTER
        ---------------------------------------------------- */
        .digital-footer {
            margin-top: 4px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 2px;
            text-align: center;
            font-size: 5.6pt;
            color: #64748b;
            font-weight: bold;
        }
    </style>
</head>
<body>
@php
    $rawResults = [];
    if ($res && $res->labResults && $res->labResults->isNotEmpty()) {
        foreach ($res->labResults as $r) {
            $rawResults[] = [
                'name' => trim($r->parameter_name),
                'value' => trim($r->observed_value),
                'ref' => trim($r->reference_range ?? '')
            ];
        }
    } elseif ($res && !empty($res->lab_data['results'])) {
        foreach ($res->lab_data['results'] as $r) {
            $rawResults[] = [
                'name' => trim($r['name'] ?? ''),
                'value' => trim($r['value'] ?? ''),
                'ref' => trim($r['ref_range'] ?? ($r['ref'] ?? ''))
            ];
        }
    }

    $normalize = function($str) {
        return strtolower(preg_replace('/[^a-z0-9]/', '', $str ?? ''));
    };

    $matchedIndices = [];
    $getVal = function($aliases) use (&$rawResults, &$matchedIndices, $normalize) {
        if (!is_array($aliases)) {
            $aliases = [$aliases];
        }
        $normAliases = array_map($normalize, $aliases);
        foreach ($rawResults as $idx => $item) {
            if (isset($matchedIndices[$idx])) {
                continue;
            }
            $normName = $normalize($item['name']);
            if (in_array($normName, $normAliases, true)) {
                $matchedIndices[$idx] = true;
                return $item['value'];
            }
        }
        return '';
    };

    // 4. Extract parameters for Hematology
    $wbc = $getVal(['WBC Count', 'White Blood Cells', 'WBC']);
    $hgb = $getVal(['Hemoglobin', 'Hb', 'Hgb']);
    $mch = $getVal(['MCH']);
    $mchc = $getVal(['MCHC']);
    $mcv = $getVal(['MCV']);
    $rbc = $getVal(['RBC Count', 'Red Blood Cells', 'RBC']);
    $hct = $getVal(['Hematocrit', 'Hct']);
    $plt = $getVal(['Platelet Count', 'Platelets', 'Platelet', 'Plt']);
    $bleedingTime = $getVal(['Bleeding Time', 'BT']);
    $clottingTime = $getVal(['Clotting Time', 'CT']);
    $esr = $getVal(['ESR', 'Erythrocyte Sedimentation Rate']);
    $rdw = $getVal(['RDW']);
    $retic = $getVal(['Reticulocyte CT', 'Reticulocyte Count', 'Reticulocytes']);
    $neutrophils = $getVal(['Neutrophils', 'Neutrophil', 'Segments']);
    $lymphocytes = $getVal(['Lymphocytes', 'Lymphocyte']);
    $monocytes = $getVal(['Monocytes', 'Monocyte']);
    $eosinophils = $getVal(['Eosinophils', 'Eosinophil']);
    $basophils = $getVal(['Basophils', 'Basophil']);
    $stabs = $getVal(['Stabs', 'Bands']);
    $bloodType = $getVal(['Blood Type', 'ABO Typing', 'ABO']);
    $rhTyping = $getVal(['Rh Typing', 'Rh Factor', 'Rh']);

    // 5. Extract parameters for Urinalysis
    $uriColor = $getVal(['Urine Color', 'Color (Urine)', 'Color']);
    $uriTrans = $getVal(['Transparency', 'Clarity', 'Urine Transparency']);
    $uriPus = $getVal(['Urine Pus Cells', 'Pus Cells (Urine)', 'Pus Cells']);
    $uriRbc = $getVal(['Urine RBC', 'RBC (Urine)']);
    $uriEpi = $getVal(['Epithelial Cells', 'Urine Epithelial Cells']);
    $uriMucus = $getVal(['Mucus Threads', 'Urine Mucus Threads']);
    $uriBacteria = $getVal(['Bacteria', 'Urine Bacteria']);
    $uriPh = $getVal(['Urine pH', 'pH']);
    $uriSg = $getVal(['Specific Gravity', 'Sp. Gravity', 'SG']);
    $uriSugar = $getVal(['Urine Sugar', 'Sugar (Urine)', 'Sugar', 'Glucose (Urine)']);
    $uriProtein = $getVal(['Urine Protein', 'Protein (Urine)', 'Protein', 'Albumin (Urine)']);
    $uriKetone = $getVal(['Ketone', 'Ketones', 'Urine Ketone']);
    $uriBlood = $getVal(['Urine Blood', 'Blood (Urine)', 'Occult Blood (Urine)']);
    $uriNitrite = $getVal(['Nitrite', 'Nitrites', 'Urine Nitrite']);
    $uriLeuk = $getVal(['Leukocytes', 'Urine Leukocytes']);
    $uriUro = $getVal(['Urobilinogen', 'Urine Urobilinogen']);
    $castFine = $getVal(['Fine Granular Cast', 'Fine Granular']);
    $castCoarse = $getVal(['Coarse Granular Cast', 'Coarse Granular']);
    $castHyaline = $getVal(['Hyaline Cast', 'Hyaline']);
    $castPus = $getVal(['Pus Cell Casts', 'Pus Cell Cast']);
    $castWaxy = $getVal(['Waxy Cast', 'Waxy Casts']);
    $crystOxalate = $getVal(['Crystals: Calcium Oxalate', 'Calcium Oxalate']);
    $crystUrates = $getVal(['Crystals: Amorphous Urates', 'Amorphous Urates', 'Amorphous Urate']);
    $crystPhos = $getVal(['Amorphous Phosphate', 'Amorphous Phosphates', 'Crystals: Amorphous Phosphate']);
    $crystOthers = $getVal(['Crystals Others', 'Crystals: Others', 'Crystal Others']);
    $uriPregTest = $getVal(['Pregnancy Test (HCG)', 'Urine Pregnancy Test', 'Pregnancy Test (Urine)']);

    // 6. Extract parameters for Fecalysis
    $fecColor = $getVal(['Fecal Color', 'Stool Color']);
    $fecCons = $getVal(['Consistency', 'Fecal Consistency', 'Stool Consistency']);
    $fecWbc = $getVal(['Fecal WBC', 'Stool WBC', 'WBC (Stool)']);
    $fecRbc = $getVal(['Fecal RBC', 'Stool RBC', 'RBC (Stool)']);
    $fecFat = $getVal(['Fat Globule', 'Fat Globules']);
    $fecOva = $getVal(['Ova / Parasites', 'Ova and Parasites', 'Ova', 'Parasites']);
    $fecOccBlood = $getVal(['Occult Blood', 'Fecal Occult Blood', 'Occult Blood (Stool)', 'FOBT']);

    // 7. Extract parameters for Serology
    $serHbsag = $getVal(['HBsAg', 'HBsAg (Hepatitis B)', 'HBsAg (Qualitative)', 'HBsAg Screening', 'Hepatitis B Screening', 'HBsAg (Hepatitis B Screening)']);
    $serHav = $getVal(['HAV', 'HAV (Hepatitis A)', 'Anti-HAV', 'Anti-HAV IGM/IGG', 'Hepatitis A Screening', 'HAV (Hepatitis A Screening)']);
    $serVdrl = $getVal(['VDRL / RPR', 'VDRL / RPR (Syphilis)', 'VDRL', 'RPR', 'Syphilis']);
    $serPreg = $getVal(['Pregnancy Test (Serum)', 'Serum Pregnancy Test']);
    $serTsh = $getVal(['TSH', 'Thyroid Stimulating Hormone']);

    $genericPreg = $getVal(['Pregnancy Test']);
    if ($genericPreg !== '') {
        if ($uriPregTest === '') $uriPregTest = $genericPreg;
        elseif ($serPreg === '') $serPreg = $genericPreg;
    }

    $otherResults = [];
    foreach ($rawResults as $idx => $item) {
        if (!isset($matchedIndices[$idx]) && !empty($item['name']) && $item['value'] !== '') {
            $otherResults[] = $item;
        }
    }

    $details = $res?->labDetails;
    $relName = $details->released_by_name ?? ($res->lab_data['sig']['rel_name'] ?? 'JOHN MAIAH G. MAO, RMT');
    $relLic = $details->released_by_license ?? ($res->lab_data['sig']['rel_lic'] ?? 'MEDICAL TECHNOLOGIST / License No. : 0108745');
    $val1Name = $details->validated_by_name ?? ($res->lab_data['sig']['val1_name'] ?? 'JOHN ANDREW C. AGUILAR, RMT');
    $val1Lic = $details->validated_by_license ?? ($res->lab_data['sig']['val1_lic'] ?? 'MEDICAL TECHNOLOGIST / License No. : 0108313');
    $val2Name = $details->validated_by_name_2 ?? ($res->lab_data['sig']['val2_name'] ?? 'INGAYON, NENA SALCEDO, MD, FPSP, MHC');
    $val2Lic = $details->validated_by_license_2 ?? ($res->lab_data['sig']['val2_lic'] ?? 'PATHOLOGIST / License No. : 0092052');
    $caseNo = $details->case_no ?? ($res->lab_data['metadata']['case_no'] ?? 'N/A');
    
    // Cryptographically signed URL prevents 403 Invalid Signature errors
    $signedVerificationUrl = \Illuminate\Support\Facades\URL::signedRoute('result.verify-public', ['appointment' => $app->id]);
@endphp

{{-- 1. CLINIC BRANDING HEADER --}}
<table class="clinic-header-table">
    <tr>
        <td class="clinic-logo-left">
            <div class="doh-badge-box">
                <div class="doh-text">D<span>O</span>H</div>
                <div class="doh-sub">Accredited</div>
            </div>
        </td>
        <td class="clinic-info-center">
            <div class="clinic-name"><span>MED</span>SCREEN DIAGNOSTIC LABORATORY</div>
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

{{-- 2. PATIENT DEMOGRAPHIC METADATA BOX --}}
<table class="patient-meta-table">
    <tr>
        <td class="meta-lbl">Name:</td>
        <td class="meta-val">{{ strtoupper($app->patient_name) }}</td>
        <td class="meta-lbl">Date:</td>
        <td class="meta-val">{{ \Carbon\Carbon::parse($app->tested_at ?? $app->appointment_date)->format('M d, Y') }}</td>
    </tr>
    <tr>
        <td class="meta-lbl">Address:</td>
        <td class="meta-val">{{ strtoupper($app->patient_address) }}</td>
        <td class="meta-lbl">Age/Sex:</td>
        <td class="meta-val">{{ $app->patient_age }} / {{ strtoupper($app->patient_sex) }}</td>
    </tr>
    <tr>
        <td class="meta-lbl">Case #:</td>
        <td class="meta-val">{{ $caseNo }}</td>
        <td class="meta-lbl">Requested By:</td>
        <td class="meta-val">{{ strtoupper($app->organization_name ?? 'INDIVIDUAL') }}</td>
    </tr>
</table>

{{-- 3. REPORT TITLE BANNER --}}
<div class="main-title-bar">LABORATORY RESULT(S)</div>

{{-- 4. HEMATOLOGY SECTION --}}
<div class="sub-section-title">HEMATOLOGY</div>
<table class="form-grid-table">
    <tr>
        <td style="width: 36%;">
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 36%;">WBC COUNT</td>
                    <td class="val-cell" style="width: 26%;"><span class="val-underline">{!! $wbc !== '' ? e($wbc) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell" style="width: 38%;">5-10 x 10<sup>9</sup>/L</td>
                </tr>
                <tr>
                    <td class="lbl-cell">HEMOGLOBIN</td>
                    <td class="val-cell"><span class="val-underline">{!! $hgb !== '' ? e($hgb) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(M) 140-170 G/L<br>(F) 120-150 G/L</td>
                </tr>
                <tr>
                    <td class="lbl-cell">MCH</td>
                    <td class="val-cell"><span class="val-underline">{!! $mch !== '' ? e($mch) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">25.0 - 35.0 pg</td>
                </tr>
                <tr>
                    <td class="lbl-cell">MCHC</td>
                    <td class="val-cell"><span class="val-underline">{!! $mchc !== '' ? e($mchc) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">310 - 380 g/dl</td>
                </tr>
                <tr>
                    <td class="lbl-cell">MCV</td>
                    <td class="val-cell"><span class="val-underline">{!! $mcv !== '' ? e($mcv) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">75.0 - 100.0 fl</td>
                </tr>
                <tr>
                    <td class="lbl-cell">RBC</td>
                    <td class="val-cell"><span class="val-underline">{!! $rbc !== '' ? e($rbc) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(M) 4.5-6.5 x 10<sup>12</sup>/L<br>(F) 4.3-5.5 x 10<sup>12</sup>/L</td>
                </tr>
                <tr>
                    <td class="lbl-cell">HEMATOCRIT</td>
                    <td class="val-cell"><span class="val-underline">{!! $hct !== '' ? e($hct) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(M) 0.40 - 0.50<br>(F) 0.36 - 0.48</td>
                </tr>
            </table>
        </td>
        <td style="width: 32%;">
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 44%;">PLATELET COUNT</td>
                    <td class="val-cell" style="width: 26%;"><span class="val-underline">{!! $plt !== '' ? e($plt) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell" style="width: 30%;">150 - 400 x 10<sup>9</sup>/L</td>
                </tr>
                <tr>
                    <td class="lbl-cell">BLEEDING TIME</td>
                    <td class="val-cell"><span class="val-underline">{!! $bleedingTime !== '' ? e($bleedingTime) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">2 - 6 MINUTES</td>
                </tr>
                <tr>
                    <td class="lbl-cell">CLOTTING TIME</td>
                    <td class="val-cell"><span class="val-underline">{!! $clottingTime !== '' ? e($clottingTime) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">2 - 8 MINUTES</td>
                </tr>
                <tr>
                    <td class="lbl-cell">ESR</td>
                    <td class="val-cell"><span class="val-underline">{!! $esr !== '' ? e($esr) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(M) 0 - 10 mm/hr<br>(F) 0 - 20 mm/hr</td>
                </tr>
                <tr>
                    <td class="lbl-cell">RDW</td>
                    <td class="val-cell"><span class="val-underline">{!! $rdw !== '' ? e($rdw) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">11.0 - 16.0%</td>
                </tr>
                <tr>
                    <td class="lbl-cell">RETICULOCYTE CT</td>
                    <td class="val-cell"><span class="val-underline">{!! $retic !== '' ? e($retic) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">0.5 - 1.5%</td>
                </tr>
            </table>
        </td>
        <td style="width: 32%;">
            <div class="sub-header-lbl">Differential Count:</div>
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 42%;">NEUTROPHILS</td>
                    <td class="val-cell" style="width: 28%;"><span class="val-underline">{!! $neutrophils !== '' ? e($neutrophils) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell" style="width: 30%;">(0.40 - 0.65)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">LYMPHOCYTES</td>
                    <td class="val-cell"><span class="val-underline">{!! $lymphocytes !== '' ? e($lymphocytes) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(0.20 - 0.40)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">MONOCYTES</td>
                    <td class="val-cell"><span class="val-underline">{!! $monocytes !== '' ? e($monocytes) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(0.02 - 0.06)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">EOSINOPHILS</td>
                    <td class="val-cell"><span class="val-underline">{!! $eosinophils !== '' ? e($eosinophils) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(0.01 - 0.03)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">BASOPHILS</td>
                    <td class="val-cell"><span class="val-underline">{!! $basophils !== '' ? e($basophils) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(0.00 - 0.01)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">STABS</td>
                    <td class="val-cell"><span class="val-underline">{!! $stabs !== '' ? e($stabs) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(0.01 - 0.04)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">BLOOD TYPE:</td>
                    <td class="val-cell" colspan="2"><span class="val-underline" style="min-width: 65px;">{!! $bloodType !== '' ? e($bloodType) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">RH TYPING:</td>
                    <td class="val-cell" colspan="2"><span class="val-underline" style="min-width: 65px;">{!! $rhTyping !== '' ? e($rhTyping) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- 5. URINALYSIS SECTION --}}
<div class="sub-section-title">URINALYSIS</div>
<table class="form-grid-table">
    <tr>
        <td style="width: 27%;">
            <div class="sub-header-lbl">Microscopic Examination:</div>
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 44%;">COLOR</td>
                    <td class="val-cell" colspan="2"><span class="val-underline" style="min-width: 60px;">{!! $uriColor !== '' ? e($uriColor) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">TRANSPARENCY</td>
                    <td class="val-cell" colspan="2"><span class="val-underline" style="min-width: 60px;">{!! $uriTrans !== '' ? e($uriTrans) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="2" style="font-size: 5.5pt; font-weight: bold; color: #475569; text-align: right; padding-right: 5px;">Normal Values</td>
                </tr>
                <tr>
                    <td class="lbl-cell">PUS CELLS</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriPus !== '' ? e($uriPus) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(0-5)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">RBC</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriRbc !== '' ? e($uriRbc) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="ref-cell">(0-2)</td>
                </tr>
                <tr>
                    <td class="lbl-cell">EPITHELIAL CELLS</td>
                    <td class="val-cell" colspan="2"><span class="val-underline" style="min-width: 60px;">{!! $uriEpi !== '' ? e($uriEpi) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">MUCUS THREADS</td>
                    <td class="val-cell" colspan="2"><span class="val-underline" style="min-width: 60px;">{!! $uriMucus !== '' ? e($uriMucus) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">BACTERIA</td>
                    <td class="val-cell" colspan="2"><span class="val-underline" style="min-width: 60px;">{!! $uriBacteria !== '' ? e($uriBacteria) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
            </table>
        </td>
        <td style="width: 25%;">
            <div class="sub-header-lbl">Chemical Examination:</div>
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 48%;">URINE pH</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriPh !== '' ? e($uriPh) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">SPECIFIC GRAVITY</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriSg !== '' ? e($uriSg) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">SUGAR</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriSugar !== '' ? e($uriSugar) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">PROTEIN</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriProtein !== '' ? e($uriProtein) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">KETONE</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriKetone !== '' ? e($uriKetone) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">BLOOD</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriBlood !== '' ? e($uriBlood) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">NITRITE</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriNitrite !== '' ? e($uriNitrite) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">LEUKOCYTES</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriLeuk !== '' ? e($uriLeuk) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">UROBILINOGEN</td>
                    <td class="val-cell"><span class="val-underline">{!! $uriUro !== '' ? e($uriUro) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
            </table>
        </td>
        <td style="width: 24%;">
            <div class="sub-header-lbl">Casts:</div>
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 50%;">FINE GRANULAR</td>
                    <td class="val-cell"><span class="val-underline">{!! $castFine !== '' ? e($castFine) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">COARSE GRANULAR</td>
                    <td class="val-cell"><span class="val-underline">{!! $castCoarse !== '' ? e($castCoarse) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">HYALINE</td>
                    <td class="val-cell"><span class="val-underline">{!! $castHyaline !== '' ? e($castHyaline) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">PUS CELL CASTS</td>
                    <td class="val-cell"><span class="val-underline">{!! $castPus !== '' ? e($castPus) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">WAXY CAST</td>
                    <td class="val-cell"><span class="val-underline">{!! $castWaxy !== '' ? e($castWaxy) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
            </table>
        </td>
        <td style="width: 24%;">
            <div class="sub-header-lbl">Crystals:</div>
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 52%;">CALCIUM OXALATE</td>
                    <td class="val-cell"><span class="val-underline">{!! $crystOxalate !== '' ? e($crystOxalate) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">AMORPHOUS URATE</td>
                    <td class="val-cell"><span class="val-underline">{!! $crystUrates !== '' ? e($crystUrates) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">AMORPHOUS PHOSPHATE</td>
                    <td class="val-cell"><span class="val-underline">{!! $crystPhos !== '' ? e($crystPhos) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">Others</td>
                    <td class="val-cell"><span class="val-underline">{!! $crystOthers !== '' ? e($crystOthers) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Urinalysis Bottom Note & Pregnancy Test --}}
<table style="width: 100%; border-top: 1px solid #cbd5e1; margin-top: 2px; margin-bottom: 3px;">
    <tr>
        <td style="font-weight: bold; font-size: 6.8pt; color: #1e293b; width: 50%;">
            URINALYSIS MANUALLY DONE
        </td>
        <td style="text-align: right; font-size: 6.8pt; width: 50%;">
            <span style="font-weight: bold; color: #1e293b;">PREGNANCY TEST:</span>
            <span class="val-underline" style="min-width: 80px;">{!! $uriPregTest !== '' ? e($uriPregTest) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span>
        </td>
    </tr>
</table>

{{-- 6. FECALYSIS --}}
<table style="width: 100%; border-top: 1px solid #cbd5e1; padding-top: 2px; margin-bottom: 3px;">
    <tr>
        <td style="width: 75%; vertical-align: top;">
            <div class="sub-header-lbl" style="margin-top: 0; padding-top: 0;">Microscopic Examination (Fecalysis):</div>
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 12%;">COLOR:</td>
                    <td class="val-cell" style="width: 20%;"><span class="val-underline" style="min-width: 50px;">{!! $fecColor !== '' ? e($fecColor) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="lbl-cell" style="width: 10%;">WBC:</td>
                    <td class="val-cell" style="width: 24%;"><span class="val-underline" style="min-width: 35px;">{!! $fecWbc !== '' ? e($fecWbc) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span> <span class="ref-cell">/HPF</span></td>
                    <td class="lbl-cell" style="width: 18%;">FAT GLOBULES:</td>
                    <td class="val-cell" style="width: 16%;"><span class="val-underline" style="min-width: 45px;">{!! $fecFat !== '' ? e($fecFat) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
                <tr>
                    <td class="lbl-cell">CONSISTENCY:</td>
                    <td class="val-cell"><span class="val-underline" style="min-width: 50px;">{!! $fecCons !== '' ? e($fecCons) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                    <td class="lbl-cell">RBC:</td>
                    <td class="val-cell"><span class="val-underline" style="min-width: 35px;">{!! $fecRbc !== '' ? e($fecRbc) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span> <span class="ref-cell">/HPF</span></td>
                    <td class="lbl-cell">OVA/PARASITES:</td>
                    <td class="val-cell"><span class="val-underline" style="min-width: 45px;">{!! $fecOva !== '' ? e($fecOva) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
            </table>
        </td>
        <td style="width: 25%; vertical-align: top; border-left: 1px solid #cbd5e1; padding-left: 5px;">
            <div class="sub-header-lbl" style="margin-top: 0; padding-top: 0;">Others:</div>
            <table class="test-row-table">
                <tr>
                    <td class="lbl-cell" style="width: 45%;">OCCULT BLOOD:</td>
                    <td class="val-cell"><span class="val-underline" style="min-width: 55px;">{!! $fecOccBlood !== '' ? e($fecOccBlood) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- 7. SEROLOGY SECTION --}}
<div class="sub-section-title" style="color: #1e3a8a;">SEROLOGY</div>
<table class="serology-table">
    <thead>
        <tr>
            <th style="width: 32%;">Examination</th>
            <th style="width: 20%; text-align: center;">Result</th>
            <th style="width: 20%;">Examination</th>
            <th style="width: 14%; text-align: center;">Result</th>
            <th style="width: 14%; text-align: right;">Normal Values</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: bold;">HBsAg (Hepatitis B Screening)</td>
            <td style="text-align: center;"><span class="val-underline" style="min-width: 75px;">{!! $serHbsag !== '' ? e($serHbsag) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
            <td style="font-weight: bold;">TSH</td>
            <td style="text-align: center;"><span class="val-underline" style="min-width: 50px;">{!! $serTsh !== '' ? e($serTsh) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
            <td style="text-align: right; color: #475569; font-size: 6.2pt;">0.4-5.5 uIU/mL</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">HAV (Hepatitis A Screening)</td>
            <td style="text-align: center;"><span class="val-underline" style="min-width: 75px;">{!! $serHav !== '' ? e($serHav) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">VDRL / RPR (Syphilis)</td>
            <td style="text-align: center;"><span class="val-underline" style="min-width: 75px;">{!! $serVdrl !== '' ? e($serVdrl) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Pregnancy Test (Serum)</td>
            <td style="text-align: center;"><span class="val-underline" style="min-width: 75px;">{!! $serPreg !== '' ? e($serPreg) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' !!}</span></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>

{{-- 8. DYNAMIC OTHERS SECTION --}}
@if(count($otherResults) > 0)
<div class="sub-section-title" style="color: #0d9488;">OTHER CLINICAL EXAMINATIONS / CHEMISTRY</div>
<table class="others-table">
    <thead>
        <tr>
            <th style="width: 45%; text-align: left;">Examination Parameter</th>
            <th style="width: 25%; text-align: center;">Observed Result</th>
            <th style="width: 30%; text-align: right;">Reference Range</th>
        </tr>
    </thead>
    <tbody>
        @foreach($otherResults as $or)
        <tr>
            <td style="font-weight: bold; color: #1c232d;">{{ strtoupper($or['name']) }}</td>
            <td style="text-align: center; font-weight: bold;">{{ $or['value'] }}</td>
            <td style="text-align: right; color: #475569; font-style: italic;">{{ $or['ref'] ?: 'NONE' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- 9. CLINICAL REMARKS & NOTICES --}}
<div class="notes-container">
    <div><strong>NOTE: FOR SCREENING PURPOSES ONLY.</strong></div>
    <div><strong>Reminder:</strong> Tests left <span style="text-decoration: underline;">blank</span> or without recorded result(s) are considered <span style="text-decoration: underline;">not performed or not requested by the patient</span>.</div>
    <div><strong>Important Notice:</strong> This laboratory report is designed for interpretation by a qualified medical doctor in conjunction with clinical assessment and other diagnostic procedures.</div>
</div>

{{-- 10. CLINICAL SIGN-OFF BLOCKS --}}
<table class="signatory-table">
    <tr>
        <td class="sig-col">
            <div style="font-size: 6pt; color: #475569; text-align: left; margin-bottom: 2px;">Released by:</div>
            <div class="sig-handwritten">{{ $relName }}</div>
            <div class="sig-line">{{ strtoupper($relName) }}</div>
            <div class="sig-sub">{{ strtoupper($relLic) }}</div>
        </td>
        <td class="sig-col">
            <div style="font-size: 6pt; color: #475569; text-align: left; margin-bottom: 2px;">Validated by:</div>
            <div class="sig-handwritten">{{ $val1Name }}</div>
            <div class="sig-line">{{ strtoupper($val1Name) }}</div>
            <div class="sig-sub">{{ strtoupper($val1Lic) }}</div>
        </td>
        <td class="sig-col">
            <div style="font-size: 6pt; color: #475569; text-align: left; margin-bottom: 2px;">&nbsp;</div>
            <div class="sig-handwritten">{{ $val2Name }}</div>
            <div class="sig-line">{{ strtoupper($val2Name) }}</div>
            <div class="sig-sub">{{ strtoupper($val2Lic) }}</div>
        </td>
    </tr>
</table>

{{-- 11. DIGITAL FOOTER --}}
<div class="digital-footer">
    This is a digital copy. Physical copies can be acquired at the official location of Medscreen Diagnostic Laboratory.
</div>
</body>
</html>