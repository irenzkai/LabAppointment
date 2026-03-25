<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 35px; }
        body { font-family: 'Helvetica', sans-serif; color: #000; font-size: 10px; line-height: 1.4; margin: 0; }
        
        /* Clinic Branding Header */
        .clinic-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 20px; }
        .clinic-name { font-size: 18px; font-weight: bold; margin-bottom: 2px; }
        .clinic-sub { font-size: 9px; text-transform: uppercase; color: #333; }

        /* Metadata Grid */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .meta-table td { padding: 4px 5px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .label { font-weight: bold; color: #555; width: 90px; font-size: 9px; }
        .value { font-weight: bold; font-size: 10px; }

        /* Layout Elements */
        .title-bar { text-align: center; padding: 5px; font-weight: bold; font-size: 13px; letter-spacing: 2px; border: 1.5px solid #000; background: #f4f4f4; margin-bottom: 20px; }
        .section-header { background: #eee; padding: 5px 10px; font-weight: bold; text-transform: uppercase; margin-top: 15px; border-left: 5px solid #5af781; font-size: 11px; }
        
        /* Data Presentation */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .data-table th { text-align: left; padding: 6px; border-bottom: 1.5px solid #000; font-size: 9px; background: #fafafa; }
        .data-table td { padding: 5px; border-bottom: 1px solid #eee; }
        .ref-range { font-size: 8.5px; color: #666; font-style: italic; }

        /* Signatory Logic */
        .footer-wrap { margin-top: 50px; }
        .sig-container { width: 100%; border-collapse: collapse; }
        .sig-box { width: 32%; text-align: center; vertical-align: top; padding: 0 10px; }
        .sig-line { border-top: 1px solid #000; font-weight: bold; padding-top: 3px; font-size: 10px; }
        .sig-sub { font-size: 8px; color: #555; text-transform: uppercase; }
        
        .notice-box { margin-top: 30px; font-size: 8.5px; color: #444; border: 1px solid #eee; padding: 10px; }
        .digital-footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; border-top: 1px solid #ddd; padding-top: 5px; font-weight: bold; color: #888; }
    </style>
</head>
<body>

    {{-- SHARED CLINIC HEADER --}}
    <div class="clinic-header">
        <div class="clinic-name">MEDSCREEN DIAGNOSTIC LABORATORY</div>
        <div class="clinic-sub">BANISIL STREET, BRGY. DADIANGAS WEST, GENERAL SANTOS CITY</div>
        <div class="clinic-sub">DOH ACCREDITED | TEL: (083) 823 8754 | medscreen.lab@gmail.com</div>
    </div>

    {{-- --- TYPE: LABORATORY RESULTS --- --}}
    @if($type == 'lab')
        <table class="meta-table">
            <tr>
                <td class="label">NAME:</td><td class="value" colspan="3">{{ strtoupper($app->patient_name) }}</td>
                <td class="label">DATE:</td><td class="value">{{ $app->appointment_date->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">ADDRESS:</td><td class="value" colspan="3">{{ $app->patient_address }}</td>
                <td class="label">CASE #:</td><td class="value" style="color:#000;">{{ $res->lab_data['metadata']['case_no'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">AGE / SEX:</td><td class="value">{{ $app->patient_age }} / {{ strtoupper($app->patient_sex) }}</td>
                <td class="label">REQUESTED BY:</td><td class="value" colspan="3">{{ strtoupper($app->organization_name ?? 'INDIVIDUAL') }}</td>
            </tr>
        </table>

        <div class="title-bar">LABORATORY RESULT(S)</div>

        {{-- 1. Hematology --}}
        @php
            $hemMap = [
                'wbc' => ['label' => 'WBC COUNT', 'ref' => '5-10 x 10⁹/L'],
                'hb'  => ['label' => 'HEMOGLOBIN', 'ref' => '(M) 140-170 / (F) 120-150 G/L'],
                'mch' => ['label' => 'MCH', 'ref' => '25.0-35.0 pg'],
                'mchc'=> ['label' => 'MCHC', 'ref' => '310-380 g/dl'],
                'mcv' => ['label' => 'MCV', 'ref' => '75.0-100.0 fl'],
                'rbc' => ['label' => 'RBC', 'ref' => '(M) 4.5-6.5 / (F) 4.3-5.5'],
                'hct' => ['label' => 'HEMATOCRIT', 'ref' => '(M) 0.40-0.50 / (F) 0.36-0.48'],
                'plt' => ['label' => 'PLATELET COUNT', 'ref' => '150-400 x 10⁹/L'],
                'bt_time' => ['label' => 'BLEEDING TIME', 'ref' => '2-6 MINUTES'],
                'ct_time' => ['label' => 'CLOTTING TIME', 'ref' => '2-8 MINUTES'],
                'esr' => ['label' => 'ESR', 'ref' => '(M) 0-10 / (F) 0-20 mm/hr'],
                'rdw' => ['label' => 'RDW', 'ref' => '11.0-16.0%'],
                'retic' => ['label' => 'RETICULOCYTE CT', 'ref' => '0.5-1.5%']
            ];
            $diffMap = [
                'neu' => ['label' => 'NEUTROPHILS', 'ref' => '0.40-0.65'],
                'lym' => ['label' => 'LYMPHOCYTES', 'ref' => '0.20-0.40'],
                'mon' => ['label' => 'MONOCYTES', 'ref' => '0.02-0.06'],
                'eos' => ['label' => 'EOSINOPHILS', 'ref' => '0.01-0.03'],
                'bas' => ['label' => 'BASOPHILS', 'ref' => '0.00-0.01'],
                'sta' => ['label' => 'STABS', 'ref' => '0.01-0.04']
            ];
        @endphp

        {{-- Hematology Main --}}
        @php $hasH = false; foreach($hemMap as $k=>$v){ if(!empty($res->lab_data['hem'][$k])) $hasH = true; } @endphp
        @if($hasH)
            <div class="section-header">Hematology</div>
            <table class="data-table">
                <thead><tr><th style="width:40%">EXAMINATION</th><th style="width:20%">RESULT</th><th style="width:40%">REFERENCE RANGE</th></tr></thead>
                <tbody>
                    @foreach($hemMap as $key => $val)
                        @if(!empty($res->lab_data['hem'][$key]))
                            <tr><td>{{ $val['label'] }}</td><td><strong>{{ $res->lab_data['hem'][$key] }}</strong></td><td class="ref-range">{{ $val['ref'] }}</td></tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Differential Count --}}
        @php $hasD = false; foreach($diffMap as $k=>$v){ if(!empty($res->lab_data['hem'][$k])) $hasD = true; } @endphp
        @if($hasD)
            <p style="font-weight:bold; margin: 10px 0 5px 0; text-decoration: underline;">DIFFERENTIAL COUNT (%)</p>
            <table class="data-table">
                <tbody>
                    @foreach($diffMap as $key => $val)
                        @if(!empty($res->lab_data['hem'][$key]))
                            <tr><td>{{ $val['label'] }}</td><td><strong>{{ $res->lab_data['hem'][$key] }}</strong></td><td class="ref-range">{{ $val['ref'] }}</td></tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- 2. Urinalysis --}}
        @if(!empty($res->lab_data['uri']))
            <div class="section-header">Urinalysis</div>
            <table class="data-table">
                @foreach(['color'=>'COLOR', 'trans'=>'TRANSPARENCY', 'pus'=>'PUS CELLS (0-2)', 'rbc'=>'RBC (0-2)', 'ph'=>'pH', 'sg'=>'SP. GRAVITY', 'sugar'=>'SUGAR', 'prot'=>'PROTEIN'] as $k=>$v)
                    @if(!empty($res->lab_data['uri'][$k]))
                        <tr><td style="width:40%">{{ $v }}</td><td><strong>{{ $res->lab_data['uri'][$k] }}</strong></td></tr>
                    @endif
                @endforeach
            </table>
        @endif

        {{-- 3. Fecalysis --}}
        @if(!empty($res->lab_data['fec']))
            <div class="section-header">Fecalysis</div>
            <table class="data-table">
                @foreach(['color'=>'COLOR', 'cons'=>'CONSISTENCY', 'wbc'=>'WBC / HPF', 'rbc'=>'RBC / HPF', 'occ'=>'OCCULT BLOOD'] as $k=>$v)
                    @if(!empty($res->lab_data['fec'][$k]))
                        <tr><td style="width:40%">{{ $v }}</td><td><strong>{{ $res->lab_data['fec'][$k] }}</strong></td></tr>
                    @endif
                @endforeach
            </table>
        @endif

        {{-- 4. Serology --}}
        @if(!empty($res->lab_data['ser']))
            <div class="section-header">Serology</div>
            <table class="data-table">
                @foreach(['hbsag'=>'HBsAg', 'hav'=>'HAV', 'vdrl'=>'VDRL/RPR', 'preg_ser'=>'PREGNANCY TEST', 'tsh'=>'TSH (0.4-5.5)'] as $k=>$v)
                    @if(!empty($res->lab_data['ser'][$k]))
                        <tr><td style="width:40%">{{ $v }}</td><td><strong>{{ $res->lab_data['ser'][$k] }}</strong></td></tr>
                    @endif
                @endforeach
            </table>
        @endif

        {{-- Signatories: Multiple support for Lab --}}
        <div class="footer-wrap">
            <table class="sig-container">
                <tr>
                    <td class="sig-box">
                        <div class="sig-line">{{ strtoupper($res->lab_data['sig']['rel_name'] ?? 'Authorized Personnel') }}</div>
                        <div class="sig-sub">RELEASED BY</div>
                        <div class="sig-sub">{{ $res->lab_data['sig']['rel_lic'] ?? '-----' }}</div>
                    </td>
                    @if(isset($res->lab_data['sig']['val']))
                        @foreach($res->lab_data['sig']['val'] as $v)
                            @if(!empty($v['name']))
                                <td class="sig-box">
                                    <div class="sig-line">{{ strtoupper($v['name']) }}</div>
                                    <div class="sig-sub">VALIDATED BY</div>
                                    <div class="sig-sub">{{ $v['lic'] ?? '-----' }}</div>
                                </td>
                            @endif
                        @endforeach
                    @endif
                </tr>
            </table>
        </div>
        <div style="margin-top: 30px;">
            <div class="notice-box">
                <strong>Reminder:</strong> Tests left blank or without recorded results are considered not performed or not requested by patient.<br>
                <strong>Importance Notice:</strong> This laboratory report is designed for interpretation by a qualified medical doctor with clinical assessment and other diagnostic procedures.
            </div>
        </div>
    @endif

    {{-- --- TYPE: MEDICAL CERTIFICATE --- --}}
    @if($type == 'med_cert')
        <div style="padding: 10px 40px;">
            <div style="text-align: right; margin-bottom: 30px; font-size: 11px;">
                <strong>CERT. NO:</strong> {{ $res->med_cert_data['cert_no'] ?? '---' }}<br>
                <strong>DATE:</strong> {{ \Carbon\Carbon::parse($res->med_cert_data['date'] ?? now())->format('d M Y') }}
            </div>
            <h2 style="text-align: center; letter-spacing: 5px; text-decoration: underline;">MEDICAL CERTIFICATE</h2>
            
            <p style="margin-top: 50px; line-height: 2.5; text-align: justify; font-size: 13px;">
                TO WHOM IT MAY CONCERN:<br><br>
                This is to certify that <strong>{{ strtoupper($res->med_cert_data['name'] ?? $app->patient_name) }}</strong>, 
                {{ $res->med_cert_data['age'] ?? $app->patient_age }} years old, 
                {{ strtoupper($res->med_cert_data['sex'] ?? $app->patient_sex) }} residing at {{ $res->med_cert_data['address'] ?? $app->patient_address }} 
                has been examined on {{ \Carbon\Carbon::parse($res->med_cert_data['exam_date'] ?? $app->tested_at)->format('d M Y') }} 
                with the following findings and/or diagnosis:
            </p>

            <div style="margin: 25px 0; border: 1.5px solid #000; padding: 20px; min-height: 120px; font-size: 13px; font-weight: bold;">
                {!! nl2br(e($res->med_cert_data['findings'] ?? 'NO SIGNIFICANT FINDINGS')) !!}
            </div>

            <p style="font-size: 12px;"><strong>REMARKS:</strong> {{ strtoupper($res->med_cert_data['remarks'] ?? 'N/A') }}</p>
            
            <p style="margin-top: 40px; line-height: 2; font-size: 12px;">
                This certification is being issued to {{ strtoupper($res->med_cert_data['issued_to'] ?? $app->patient_name) }} 
                for whatever legal purposes it may serve him/her best. Not for medico-legal or court purposes.
            </p>

            <div style="margin-top: 100px; text-align: right; width: 100%;">
                <div style="display: inline-block; text-align: center; border-top: 1.5px solid #000; min-width: 280px; padding-top: 5px;">
                    <strong style="font-size: 13px;">{{ strtoupper($res->med_cert_data['sig_name'] ?? 'Physician Name') }}</strong><br>
                    <span style="font-size: 10px;">{{ $res->med_cert_data['sig_info'] ?? 'POSITION / LICENSE NO.' }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- --- TYPE: RADIOLOGIC REPORT --- --}}
    @if($type == 'radio')
        <div style="padding: 10px 20px;">
            {{-- Patient Meta in Header format for Radio --}}
            <div style="border: 1px solid #000; padding: 10px; margin-bottom: 20px;">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td><strong>NAME:</strong> {{ strtoupper($res->radio_data['patient_name'] ?? $app->patient_name) }}</td>
                        <td style="text-align: right;"><strong>DATE:</strong> {{ \Carbon\Carbon::parse($res->radio_data['date'] ?? now())->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>ADDRESS:</strong> {{ $res->radio_data['address'] ?? $app->patient_address }}</td>
                        <td style="text-align: right;"><strong>CASE #:</strong> {{ $res->radio_data['case_no'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>AGE/SEX:</strong> {{ $res->radio_data['age_sex'] ?? $app->patient_age.'/'.$app->patient_sex }}</td>
                        <td></td>
                    </tr>
                </table>
            </div>

            <h3 style="text-align: center; text-decoration: underline; margin-bottom: 30px;">RADIOLOGIC REPORT</h3>
            
            <p><strong>EXAMINATION: {{ strtoupper($res->radio_data['technique'] ?? 'CHEST PA') }}</strong></p>
            
            <div style="margin: 30px 0; min-height: 250px; text-align: justify; font-size: 12px; line-height: 1.8;">
                <strong>FINDINGS:</strong><br><br>
                {!! nl2br(e($res->radio_data['findings'] ?? '')) !!}
            </div>

            <div style="border-top: 2px solid #000; padding-top: 15px;">
                <strong style="font-size: 12px;">IMPRESSION:</strong><br>
                <p style="font-size: 14px; font-weight: bold; margin-top: 5px;">{{ strtoupper($res->radio_data['impression'] ?? 'ESSENTIALLY NORMAL CHEST') }}</p>
            </div>

            <div style="margin-top: 80px; text-align: right;">
                <div style="display: inline-block; text-align: center; border-top: 1.5px solid #000; min-width: 280px; padding-top: 5px;">
                    <strong style="font-size: 13px;">{{ strtoupper($res->radio_data['sig_name'] ?? 'Radiologist Name') }}</strong><br>
                    <span style="font-size: 10px;">{{ $res->radio_data['sig_info'] ?? 'RADIOLOGIST / LICENSE NO.' }}</span>
                </div>
            </div>
        </div>
    @endif

    <div class="digital-footer">
        This is a digital copy. Physical copies can be acquired at the official location of Medscreen Diagnostic Laboratory.
    </div>

</body>
</html>