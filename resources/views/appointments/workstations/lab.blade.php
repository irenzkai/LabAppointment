@extends('layouts.app')

@section('content')
@php
$res = $appointment->result;
$status = $res->lab_status ?? 'pending';

// UI Logic States
$isVerified = ($status === 'verified');
$isReadonly = in_array($status, ['encoded', 'verified']);
$hasScan = ($res && $res->lab_scan);
$testedDate = $appointment->tested_at ? $appointment->tested_at->format('Y-m-d') : date('Y-m-d');
@endphp

{{-- FIXED: Added 'pt-4' to establish clean, consistent spacing below the top navigation bar --}}
<div class="container text-start animate-page pt-4" id="lab-workstation-root">
 
    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">
                @if($isVerified) REVIEW MODE @elseif($status == 'encoded') VERIFICATION MODE @else LAB WORKSTATION @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ strtoupper($appointment->patient_name) }}</span> | Status: <span class="text-accent">{{ strtoupper($status) }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>
            
            @if(!$isReadonly)
                <button type="submit" form="labForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
            @else
                {{-- RESTRICTED: Hide Return button for standard reception staff if the document is in Review Mode --}}
                @if($status !== 'verified' || Auth::user()->isLabTech())
                    <button type="button" data-bs-toggle="modal" data-bs-target="#returnModal" class="btn-custom btn-outline-danger px-4">RETURN FOR RE-EDIT</button>
                @endif
                
                {{-- RESTRICTED: Hide Verify button entirely for standard receptionist staff --}}
                @if($status == 'encoded')
                    @can('isLabTech')
                        <button type="button" data-bs-toggle="modal" data-bs-target="#verifyModal" class="btn-custom btn-accent px-5 shadow-lg">VERIFY & APPROVE</button>
                    @endcan
                @endif
            @endif
        </div>
    </div>

    {{-- 2. CORRECTION ALERT --}}
    @if($res && $res->lab_return_reason && $status != 'verified')
        <div class="alert-clinical p-3 mb-4 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div>
                    <div class="fw-bold small uppercase">Correction Required:</div>
                    <div class="small italic">"{{ $res->lab_return_reason }}"</div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. CORE SAVE FORM --}}
    <form id="labForm" action="{{ route('workstation.lab.save', $appointment->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            
            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES (Hidden if a scan is already attached) --}}
            <div class="col-md-4 {{ $isReadonly && !$hasScan ? 'd-none' : ($hasScan ? 'd-none' : '') }}" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Clinical Metadata</h6>
                    
                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Case #</label>
                        <input type="text" name="lab_data[metadata][case_no]" id="case_no_field" class="form-control" value="{{ $res->lab_data['metadata']['case_no'] ?? '' }}" placeholder="Enter Case ID">
                    </div>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Patient Name</label>
                        <input type="text" name="lab_data[metadata][name]" class="form-control" value="{{ $res->lab_data['metadata']['name'] ?? strtoupper($appointment->patient_name) }}">
                    </div>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Address</label>
                        <input type="text" name="lab_data[metadata][address]" class="form-control" value="{{ $res->lab_data['metadata']['address'] ?? $appointment->patient_address }}">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Date</label>
                            <input type="date" name="lab_data[metadata][date]" class="form-control" value="{{ $res->lab_data['metadata']['date'] ?? $testedDate }}">
                        </div>
                        <div class="col-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Age</label>
                            <input type="number" name="lab_data[metadata][age]" class="form-control" value="{{ $res->lab_data['metadata']['age'] ?? $appointment->patient_age }}">
                        </div>
                        <div class="col-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Sex</label>
                            <select name="lab_data[metadata][sex]" class="form-select">
                                <option value="Male" {{ ($res->lab_data['metadata']['sex'] ?? $appointment->patient_sex) == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ ($res->lab_data['metadata']['sex'] ?? $appointment->patient_sex) == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Requested By</label>
                        <input type="text" name="organization_name" class="form-control" value="{{ $appointment->organization_name ?? 'INDIVIDUAL' }}">
                    </div>

                    <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Clinical Signatory</h6>
                    
                    <div class="mb-3 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                        <label class="text-secondary smaller fw-bold uppercase mb-1 d-block" style="font-size: 0.65rem;">Released By</label>
                        <input type="text" name="lab_data[sig][rel_name]" class="form-control mb-1" placeholder="Encoder Name" value="{{ $res->lab_data['sig']['rel_name'] ?? '' }}">
                        <input type="text" name="lab_data[sig][rel_lic]" class="form-control" placeholder="License / Position" value="{{ $res->lab_data['sig']['rel_lic'] ?? '' }}">
                    </div>
                    
                    <div class="mb-3 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                        <label class="text-secondary smaller fw-bold uppercase mb-1 d-block" style="font-size: 0.65rem;">Validated By 1</label>
                        <input type="text" name="lab_data[sig][val1_name]" class="form-control mb-1" placeholder="Validator Name" value="{{ $res->lab_data['sig']['val1_name'] ?? '' }}">
                        <input type="text" name="lab_data[sig][val1_lic]" class="form-control" placeholder="License / Position" value="{{ $res->lab_data['sig']['val1_lic'] ?? '' }}">
                    </div>
                    
                    <div class="mb-0 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                        <label class="text-secondary smaller fw-bold uppercase mb-1 d-block" style="font-size: 0.65rem;">Validated By 2</label>
                        <input type="text" name="lab_data[sig][val2_name]" class="form-control mb-1" placeholder="Pathologist Name" value="{{ $res->lab_data['sig']['val2_name'] ?? '' }}">
                        <input type="text" name="lab_data[sig][val2_lic]" class="form-control" placeholder="License / Position" value="{{ $res->lab_data['sig']['val2_lic'] ?? '' }}">
                    </div>
                </div>
            </div>

            {{-- MAIN WORKSTATION PANEL --}}
            <div class="{{ $isReadonly ? 'col-md-12' : ($hasScan ? 'col-md-12' : 'col-md-8') }}" id="main-panel-container">
                
                {{-- Prominent Scan Upload Zone (Only visible in active encoding mode) --}}
                @if(!$isReadonly)
                    <div class="card p-4 border-warning border-opacity-25 mb-4 text-center shadow-sm" id="main-upload-zone" style="background-color: rgba(255, 193, 7, 0.02); border-style: dashed !important; border-width: 2px !important;">
                        <h6 class="text-warning fw-bold mb-2 uppercase"><i class="bi bi-file-earmark-arrow-up-fill me-2 fs-5"></i>Attach Completed Laboratory Scan (Recommended)</h6>
                        <p class="text-secondary small mb-3">Uploading a physical scanned report takes absolute clinical priority and hides the manual inputs below.</p>
                        <div class="mx-auto" style="max-width: 450px;">
                            <input type="file" name="lab_scan" id="lab_scan_input" class="form-control" onchange="toggleScanPriority(this)">
                        </div>
                    </div>
                @endif

                {{-- MANUAL ENTRY --}}
                @if(!$isReadonly)
                    <div id="manual-workstation" class="card p-4 border-secondary bg-card min-vh-75 shadow-lg {{ $hasScan ? 'd-none' : '' }}">
                        <h6 class="text-main border-bottom border-secondary border-opacity-25 pb-2 mb-4 uppercase small fw-bold">Manual Content Entry</h6>
                        
                        <div class="row g-2 mb-4">
                            <div class="col-md-5">
                                <select id="testSearch" class="form-select py-2">
                                    <option value="">Search test line (e.g. WBC, Sugar, TSH)...</option>
                                    <optgroup label="HEMATOLOGY">
                                        <option value="WBC Count">WBC Count</option>
                                        <option value="Hemoglobin">Hemoglobin</option>
                                        <option value="Platelet Count">Platelet Count</option>
                                        <option value="MCH">MCH</option>
                                        <option value="MCHC">MCHC</option>
                                        <option value="MCV">MCV</option>
                                        <option value="RBC Count">RBC Count</option>
                                        <option value="Hematocrit">Hematocrit</option>
                                        <option value="Bleeding Time">Bleeding Time</option>
                                        <option value="Clotting Time">Clotting Time</option>
                                        <option value="ESR">ESR</option>
                                        <option value="RDW">RDW</option>
                                        <option value="Reticulocyte CT">Reticulocyte CT</option>
                                    </optgroup>
                                    <optgroup label="URINALYSIS">
                                        <option value="Urine Color">Urine Color</option>
                                        <option value="Transparency">Transparency</option>
                                        <option value="Urine Pus Cells">Urine Pus Cells</option>
                                        <option value="Urine RBC">Urine RBC</option>
                                        <option value="Specific Gravity">Specific Gravity</option>
                                        <option value="Urine pH">Urine pH</option>
                                        <option value="Urine Sugar">Urine Sugar</option>
                                        <option value="Urine Protein">Urine Protein</option>
                                    </optgroup>
                                    <optgroup label="SEROLOGY">
                                        <option value="HBsAg">HBsAg (Hepatitis B)</option>
                                        <option value="HAV">HAV (Hepatitis A)</option>
                                        <option value="VDRL / RPR">VDRL / RPR (Syphilis)</option>
                                        <option value="Pregnancy Test">Pregnancy Test</option>
                                        <option value="TSH">TSH</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="customTestInput" class="form-control py-2 shadow-none" placeholder="Or type a custom test name...">
                            </div>
                            <div class="col-md-3">
                                <button type="button" onclick="addTestRow()" class="btn-custom btn-neon w-100 py-2">Add Field</button>
                            </div>
                        </div>

                        <table class="table align-middle workstation-table" style="color: var(--text-main);">
                            <thead class="text-secondary smaller uppercase border-bottom border-secondary border-opacity-25">
                                <tr>
                                    <th>Examination</th>
                                    <th style="width: 35%; text-align: center;">Result Value</th>
                                    <th style="width: 35%; text-align: center;">Ref Range</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="labBody"></tbody>
                        </table>
                    </div>
                @endif

                {{-- VERIFY AREA / SCAN PREVIEW --}}
                @if($isReadonly)
                    <div id="scan-preview-zone" class="h-100 min-vh-75 shadow-lg">
                        <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                            <span>
                                @if($hasScan) <i class="bi bi-file-earmark-pdf-fill me-2"></i>PHYSICAL SCAN FILE @else <i class="bi bi-file-pdf me-2"></i>GENERATED CLINICAL PDF PREVIEW @endif
                            </span>
                            @if(!$isReadonly && $hasScan)
                                <button type="button" class="btn btn-sm btn-dark fw-bold" onclick="removeScan()">REMOVE & RESTORE FORM</button>
                            @endif
                        </div>
                        <iframe id="scanViewer" src="{{ $hasScan ? Storage::url($res->lab_scan) : route('appointments.result.access', [$appointment->id, 'lab', 'preview']) }}" class="w-100 h-100 rounded-bottom border border-warning bg-dark" style="min-height: 800px; border: none;"></iframe>
                    </div>
                @else
                    {{-- Active scan preview toggle for editing mode --}}
                    <div id="scan-preview-zone" class="d-none h-100 min-vh-75 shadow-lg">
                        <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                            <span><i class="bi bi-eye-fill me-2"></i>PHYSICAL SCAN PRIORITY MODE</span>
                            <button type="button" class="btn btn-sm btn-dark fw-bold" onclick="removeScan()">REMOVE & RESTORE FORM</button>
                        </div>
                        <iframe id="scanViewer" src="" class="w-100 h-100 rounded-bottom border border-warning bg-dark" style="min-height: 800px; border: none;"></iframe>
                    </div>
                @endif

            </div>
        </div>
    </form>
</div>

{{-- 5. MODALS --}}

<!-- VERIFY MODAL -->
<div class="modal fade" id="verifyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.verify', [$appointment->id, 'lab']) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-neon fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Enter your name to verify these results for the Hub audit trail.</p>
            
            <div class="mb-4">
                <label class="text-white small fw-bold uppercase mb-1">Verifier Name</label>
                <input type="text" name="sig_name" class="form-control" value="{{ auth()->user()->name }}" required>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-accent flex-grow-1 fw-bold uppercase">Approve & Hub Release</button>
            </div>
        </form>
    </div>
</div>

<!-- RETURN MODAL -->
<div class="modal fade" id="returnModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.return', $appointment->id) }}?type=lab" method="POST" id="labReturnForm" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase mb-1">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this test sheet for corrections.</p>
            
            {{-- Predefined Return Reasons Dropdown --}}
            <div class="mb-3">
                <label for="return_reason_select" class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                {{-- FIXED: Dynamically managed 'name="reason"' attribute handles direct post values reliably --}}
                <select id="return_reason_select" name="reason" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return justification --</option>
                    <option value="Mismatched patient identification or Case ID">Mismatched patient identification or Case ID</option>
                    <option value="Incomplete test results or missing values">Incomplete test results or missing values</option>
                    <option value="Incorrect reference ranges for this patient profile">Incorrect reference ranges for this patient profile</option>
                    <option value="Discrepancies in clinical signature or licenses">Discrepancies in clinical signature or licenses</option>
                    <option value="Others">Others (Specify details below)</option>
                </select>
            </div>

            {{-- Hidden custom reason textbox, shown if "Others" is selected --}}
            <div id="custom_return_reason_wrapper" class="mb-3 d-none">
                <label for="reason_textarea" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                <textarea id="reason_textarea" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="4" placeholder="Identify the specific correction needed..."></textarea>
                <div class="mt-2">
                    <small class="text-muted smaller italic">Minimum 5 characters required for validation.</small>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2.5" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger w-100 py-3 fw-bold uppercase">Confirm Return</button>
            </div>
        </form>
    </div>
</div>

{{-- Row template for Spreadsheet --}}
<template id="rowTemplate">
    <tr class="border-bottom border-secondary border-opacity-10 transition-all">
        <td class="text-white fw-bold py-3 test-name-label uppercase" style="color: var(--text-main) !important;"></td>
        <td>
            <input type="hidden" name="lab_data[results][INDEX][name]" class="input-test-name">
            <div class="d-flex justify-content-center">
                <input type="text" name="lab_data[results][INDEX][value]" class="form-control text-center fw-bold py-2 result-value-input" placeholder="--">
            </div>
        </td>
        <td>
            <div class="d-flex justify-content-center">
                <input type="text" name="lab_data[results][INDEX][ref_range]" class="form-control text-center small italic py-2 input-ref-range" style="background-color: var(--bg-card); color: var(--text-muted); border: 1.5px solid var(--border-color); font-size: 0.8rem; font-style: italic;" placeholder="e.g. 5-10 x 10 /L">
            </div>
        </td>
        <td class="text-end">
            @if(!$isReadonly)
                <button type="button" onclick="this.closest('tr').remove()" class="btn btn-link text-danger p-0"><i class="bi bi-x-circle fs-5"></i></button>
            @endif
        </td>
    </tr>
</template>

@push('scripts')
<script>
// Extended Reference Map containing clinical values for every single select option
const refMap = {
    'WBC Count': '5-10 x 10^9/L',
    'Hemoglobin': '(M) 140-170 / (F) 120-150 G/L',
    'Platelet Count': '150-400 x 10^9/L',
    'MCH': '25.0-35.0 pg',
    'MCHC': '310-380 g/dl',
    'MCV': '75.0-100.0 fl',
    'RBC Count': '(M) 4.5-6.5 / (F) 4.3-5.5',
    'Hematocrit': '(M) 0.40-0.50 / (F) 0.36-0.48',
    'Bleeding Time': '2-6 MINUTES',
    'Clotting Time': '2-8 MINUTES',
    'ESR': '(M) 0-10 / (F) 0-20 mm/hr',
    'RDW': '11.0-16.0%',
    'Reticulocyte CT': '0.5-1.5%',
    'Blood Type': 'NONE',
    'Rh Typing': 'NONE',
    'Urine Color': 'NONE',
    'Transparency': 'NONE',
    'Urine Pus Cells': '0-2 / (0-5)',
    'Urine RBC': '0-2 / (0-2)',
    'Specific Gravity': 'NONE',
    'Urine pH': 'NONE',
    'Urine Sugar': 'NONE',
    'Urine Protein': 'NONE',
    'HBsAg': 'NONE',
    'HAV': 'NONE',
    'VDRL / RPR': 'NONE',
    'Pregnancy Test': 'NONE',
    'TSH': '0.4-5.5 uIU/mL'
};

let rowIdx = 0;

function toggleScanPriority(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('scanViewer').src = e.target.result;
            
            // Hide spreadsheet elements and the main upload zone
            document.getElementById('manual-workstation').classList.add('d-none');
            document.getElementById('sidebar-container').classList.add('d-none');
            if (document.getElementById('main-upload-zone')) {
                document.getElementById('main-upload-zone').classList.add('d-none');
            }
            document.getElementById('scan-preview-zone').classList.remove('d-none');
            document.getElementById('main-panel-container').className = 'col-md-12';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeScan() {
    document.getElementById('lab_scan_input').value = "";
    document.getElementById('manual-workstation').classList.remove('d-none');
    document.getElementById('sidebar-container').classList.remove('d-none');
    if (document.getElementById('main-upload-zone')) {
        document.getElementById('main-upload-zone').classList.remove('d-none');
    }
    document.getElementById('scan-preview-zone').classList.add('d-none');
    document.getElementById('main-panel-container').className = 'col-md-8';
}

function addTestRow(name = null, val = '', ref = null) {
    const selectEl = document.getElementById('testSearch');
    const customInput = document.getElementById('customTestInput');
    
    let testName = name;
    let isCustom = false;

    if (!testName) {
        if (customInput && customInput.value.trim() !== '') {
            testName = customInput.value.trim();
            isCustom = true;
        } else if (selectEl) {
            testName = selectEl.value;
        }
    }

    if (!testName) return;

    const template = document.getElementById('rowTemplate').innerHTML;
    const html = template.replace(/INDEX/g, rowIdx);
    
    document.getElementById('labBody').insertAdjacentHTML('beforeend', html);
    const lastRow = document.getElementById('labBody').lastElementChild;
    lastRow.querySelector('.test-name-label').innerText = testName;
    lastRow.querySelector('.input-test-name').value = testName;
    
    // Determine default reference range or load custom/cached payload range
    let refRange = '';
    if (ref !== null) {
        refRange = ref;
    } else if (!isCustom) {
        refRange = refMap[testName] || 'NONE';
    } else {
        refRange = 'NONE';
    }

    const refInput = lastRow.querySelector('.input-ref-range');
    if (refInput) {
        refInput.value = refRange;
    }

    const valInput = lastRow.querySelector('input[type="text"].result-value-input');
    if (valInput) {
        valInput.value = val;
        if ({{ $isReadonly ? 'true' : 'false' }}) {
            valInput.readOnly = true;
        }
    }
    
    rowIdx++;
    if (selectEl) selectEl.value = '';
    if (customInput) customInput.value = '';
}

document.addEventListener('DOMContentLoaded', () => {
    // Populate manual results list on load if they exist (supports legacy ref fallbacks)
    @if($res && isset($res->lab_data['results']))
        @foreach($res->lab_data['results'] as $r) 
            addTestRow("{{ $r['name'] }}", "{{ $r['value'] }}", "{{ $r['ref_range'] ?? ($r['ref'] ?? '') }}"); 
        @endforeach
    @endif
    
    // Disable inputs dynamically if workflow mode is read-only
    if ("{{ $isReadonly }}") {
        const form = document.getElementById('labForm');
        form.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(el => el.disabled = true);
    }

    // FIXED: Dynamic name-swapping to guarantee that 'reason' is reliably processed
    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('labReturnForm');

    if (selectEl && textareaEl && textareaWrapper && formEl) {
        selectEl.addEventListener('change', function() {
            if (this.value === 'Others') {
                textareaWrapper.classList.remove('d-none');
                textareaEl.setAttribute('required', 'required');
                textareaEl.setAttribute('name', 'reason'); // Textarea receives name="reason"
                selectEl.removeAttribute('name');         // Select loses name="reason"
                textareaEl.value = ''; 
            } else {
                textareaWrapper.classList.add('d-none');
                textareaEl.removeAttribute('required');
                textareaEl.removeAttribute('name');       // Textarea loses name="reason"
                selectEl.setAttribute('name', 'reason');  // Select receives name="reason"
                textareaEl.value = this.value;
            }
        });

        formEl.addEventListener('submit', function(e) {
            const activeInput = selectEl.value === 'Others' ? textareaEl : selectEl;
            if (activeInput.value.trim().length < 5) {
                e.preventDefault();
                alert('A valid reason of at least 5 characters is required.');
            }
        });
    }
});
</script>

<style>
/* Scoped alignment styles */
#lab-workstation-root .form-control,
#lab-workstation-root .form-select,
#lab-workstation-root .input-group-text,
#lab-workstation-root .form-control:focus,
#lab-workstation-root .form-select:focus {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}

#lab-workstation-root .input-group-text {
    background-color: var(--border-color) !important;
}

#lab-workstation-root .modal-content .form-control {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border: 1.5px solid var(--border-color) !important;
}

#lab-workstation-root .btn-outline-secondary:hover {
    background-color: var(--border-color) !important;
    color: var(--text-main) !important;
}

/* FIXED: Imposed safe cap limits on manual entry input fields to avoid horizontal bloating */
.result-value-input, 
.input-ref-range {
    max-width: 250px;
}
</style>
@endpush