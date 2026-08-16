@extends('layouts.app')

@section('title', 'Encode Results')

@section('content')
@php
    $res = $appointment->result;
    $status = $res->lab_status ?? 'pending';

    // UI Logic States
    $isVerified = ($status === 'verified');
    $isReadonly = in_array($status, ['encoded', 'verified']);
    $hasScan = ($res && $res->lab_scan);

    $scanPath = $res->lab_scan ?? null;
    $isImage = false;
    if ($scanPath) {
        $ext = strtolower(pathinfo($scanPath, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    // Dynamically reveal preview on page load if scan exists
    $showPreview = $isReadonly || $hasScan;
@endphp

<div class="container text-start animate-page pt-4" id="lab-workstation-root">

    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase">
                @if($isVerified)
                    REVIEW MODE
                @elseif($isReadonly)
                    VERIFICATION MODE
                @else
                    LAB WORKSTATION
                @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ $appointment->patient_name }}</span> | Status: <span class="text-accent">{{ strtoupper($status) }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>

            @if(!$isReadonly)
                <button type="submit" form="labForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
            @else
                @if($status !== 'verified' || Auth::user()->isLabTech())
                    <button type="button" data-bs-toggle="modal" data-bs-target="#returnModal" class="btn-custom btn-outline-danger px-4">RETURN FOR RE-EDIT</button>
                @endif

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
    <form id="labForm" action="{{ $isReadonly ? route('workstation.verify', [$appointment->id, 'lab']) : route('workstation.lab.save', $appointment->id) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="clear_scan" id="clear_scan_field" value="0">
        <div class="row g-4">

            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES --}}
            <div class="col-md-4" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Clinical Metadata</h6>

                    {{-- Case # / Cert No (Always Required & Always Visible) --}}
                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Case # / Cert No.</label>
                        <input type="text" name="case_no" id="case_no_field" class="form-control @error('case_no') is-invalid @enderror" value="{{ $res->lab_data['metadata']['case_no'] ?? '' }}" placeholder="Enter Case ID" required>
                        <div class="invalid-feedback d-none" id="err_case_no"></div>
                        @error('case_no')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- READ-ONLY PATIENT PROFILE SNAPSHOT CARD WITH EDIT LINK --}}
                    <div class="mb-3 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(0,0,0,0.02);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-secondary smaller fw-bold uppercase">Patient Profile</small>
                            @if(!$isReadonly && auth()->user()->isEmployee())
                                <a href="{{ route('appointments.edit-details', $appointment->id) }}?from=lab" class="btn btn-sm btn-outline-accent py-0.5 px-2 smaller uppercase fw-bold" style="font-size: 0.68rem;" title="Edit Patient Details">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Details
                                </a>
                            @endif
                        </div>
                        <div class="text-main fw-bold small mb-1">{{ strtoupper($appointment->patient_name) }}</div>
                        <div class="text-secondary smaller mb-1">{{ $appointment->patient_age }} YRS / {{ strtoupper($appointment->patient_sex) }}</div>
                        <div class="text-muted smaller text-break" style="font-size: 0.72rem; line-height: 1.35;">{{ $appointment->patient_address }}</div>

                        {{-- Hidden snapshot payloads for backend backwards-compatibility --}}
                        <input type="hidden" name="lab_data[metadata][name]" value="{{ strtoupper($appointment->patient_name) }}">
                        <input type="hidden" name="lab_data[metadata][address]" value="{{ $appointment->patient_address }}">
                        <input type="hidden" name="lab_data[metadata][age_sex]" value="{{ $appointment->patient_age }} / {{ strtoupper($appointment->patient_sex) }}">
                    </div>

                    {{-- OPTIONAL MANUAL METADATA & SIGNATORIES (Hidden when file is inputted/uploaded) --}}
                    <div id="sidebar-manual-fields" class="{{ $hasScan ? 'd-none' : '' }}">
                        {{-- Date (Editable, defaults/fetches to today's date) --}}
                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Date</label>
                            <input type="date" name="lab_data[metadata][date]" id="lab_meta_date" class="form-control @error('lab_data.metadata.date') is-invalid @enderror" value="{{ isset($res->lab_data['metadata']['date']) ? \Carbon\Carbon::parse($res->lab_data['metadata']['date'])->format('Y-m-d') : date('Y-m-d') }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_lab_meta_date"></div>
                            @error('lab_data.metadata.date')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Requested By / Organization Name (Editable) --}}
                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Requested By</label>
                            <input type="text" name="organization_name" id="lab_meta_requested_by" class="form-control @error('organization_name') is-invalid @enderror" value="{{ $appointment->organization_name ?? 'INDIVIDUAL' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_lab_meta_requested_by"></div>
                            @error('organization_name')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Clinical Signatories</h6>

                        {{-- Released By --}}
                        <div class="mb-3 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Released By</label>
                            <input type="text" name="lab_data[sig][rel_name]" id="sig_rel_name" class="form-control mb-1 @error('lab_data.sig.rel_name') is-invalid @enderror" placeholder="Encoder Name" value="{{ $res->lab_data['sig']['rel_name'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none mb-1" id="err_sig_rel_name"></div>
                            <input type="text" name="lab_data[sig][rel_lic]" id="sig_rel_lic" class="form-control @error('lab_data.sig.rel_lic') is-invalid @enderror" placeholder="License / Position" value="{{ $res->lab_data['sig']['rel_lic'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_sig_rel_lic"></div>
                        </div>

                        {{-- Validated By 1 --}}
                        <div class="mb-3 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Validated By 1</label>
                            <input type="text" name="lab_data[sig][val1_name]" id="sig_val1_name" class="form-control mb-1 @error('lab_data.sig.val1_name') is-invalid @enderror" placeholder="Validator Name" value="{{ $res->lab_data['sig']['val1_name'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none mb-1" id="err_sig_val1_name"></div>
                            <input type="text" name="lab_data[sig][val1_lic]" id="sig_val1_lic" class="form-control @error('lab_data.sig.val1_lic') is-invalid @enderror" placeholder="License / Position" value="{{ $res->lab_data['sig']['val1_lic'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_sig_val1_lic"></div>
                        </div>

                        {{-- Validated By 2 --}}
                        <div class="mb-0 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Validated By 2</label>
                            <input type="text" name="lab_data[sig][val2_name]" id="sig_val2_name" class="form-control mb-1 @error('lab_data.sig.val2_name') is-invalid @enderror" placeholder="Pathologist Name" value="{{ $res->lab_data['sig']['val2_name'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none mb-1" id="err_sig_val2_name"></div>
                            <input type="text" name="lab_data[sig][val2_lic]" id="sig_val2_lic" class="form-control @error('lab_data.sig.val2_lic') is-invalid @enderror" placeholder="License / Position" value="{{ $res->lab_data['sig']['val2_lic'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_sig_val2_lic"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MAIN WORKSTATION PANEL --}}
            <div class="col-md-8" id="main-panel-container">
                @if(!$isReadonly)
                    {{-- Prominent Scan Upload Zone --}}
                    <div class="col-md-12 {{ $hasScan ? 'd-none' : '' }}" id="report-scan-upload-box">
                        <div class="card p-4 border-warning border-opacity-25 mb-4 text-center shadow-sm" id="main-upload-zone" style="background-color: rgba(255, 193, 7, 0.02); border-style: dashed !important; border-width: 2px !important;">
                            <h6 class="text-warning fw-bold mb-2 uppercase"><i class="bi bi-file-earmark-arrow-up-fill me-2 fs-5"></i>Attach Completed Laboratory Scan (Recommended)</h6>
                            <p class="text-secondary small mb-3">Uploading a scanned report takes absolute clinical priority and hides the manual inputs below.</p>
                            <div class="mx-auto" style="max-width: 450px;">
                                <input type="file" name="lab_scan" id="lab_scan_input" class="form-control @error('lab_scan') is-invalid @enderror" onchange="toggleScanPriority(this)">
                                <div class="invalid-feedback d-none" id="err_lab_scan"></div>
                                @error('lab_scan')
                                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- MANUAL ENTRY SPREADSHEET LIST --}}
                @if(!$isReadonly)
                    <div id="manual-workstation" class="card p-4 border-secondary bg-card min-vh-75 shadow-lg {{ $hasScan ? 'd-none' : '' }}">
                        <h6 class="text-main border-bottom border-secondary border-opacity-25 pb-2 mb-4 uppercase small fw-bold">Manual Content Entry</h6>

                        <div class="row g-2 mb-4">
                            <div class="col-md-5">
                                <select id="testSearch" class="form-select py-2">
                                    <option value="">Search test line (e.g. WBC, TSH)...</option>
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
                                <button type="button" id="add_field_btn" class="btn-custom btn-neon w-100 py-2">Add Field</button>
                            </div>
                        </div>

                        <div class="invalid-feedback d-none mb-3 text-danger fw-bold fs-6 text-center" id="err_manual_table"></div>

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
                <div id="scan-preview-zone" class="{{ $showPreview ? '' : 'd-none' }} h-100 min-vh-75 shadow-lg">
                    <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                        <span>
                            @if($isReadonly)
                                @if($hasScan)
                                    PHYSICAL SCAN FILE
                                @else
                                    GENERATED CLINICAL PDF PREVIEW
                                @endif
                            @else
                                PHYSICAL SCAN PRIORITY MODE
                            @endif
                        </span>
                        @if(!$isReadonly)
                            <button type="button" id="remove_scan_btn" class="btn btn-sm btn-dark fw-bold px-3">REMOVE & RESTORE FORM</button>
                        @endif
                    </div>
                    <div class="card border-warning border-top-0 rounded-0 rounded-bottom overflow-hidden shadow bg-card p-3 text-center d-flex justify-content-center align-items-center" style="min-height: 500px;">

                        {{-- Image Preview Container --}}
                        <div id="imagePreviewContainer" class="position-relative d-inline-block image-preview-wrapper {{ $hasScan && $isImage ? '' : 'd-none' }}" style="cursor: zoom-in;" onclick="zoomQR('{{ $hasScan && $isImage ? Storage::url($scanPath) : '' }}')">
                            <img id="imagePreviewImg" src="{{ $hasScan && $isImage ? Storage::url($scanPath) : '' }}" class="img-fluid rounded shadow-sm" style="max-height: 800px; object-fit: contain; border: 1px solid var(--border-color);">
                            <div class="position-absolute top-50 start-50 translate-middle zoom-overlay d-flex flex-column align-items-center justify-content-center text-white">
                                <i class="bi bi-zoom-in fs-1"></i>
                                <span class="fw-bold mt-2">CLICK TO ZOOM FULLSCREEN</span>
                            </div>
                        </div>

                        {{-- PDF / Fallback Preview Container --}}
                        @if($isReadonly && !$hasScan)
                            <div id="reportPdfContainer" class="position-relative w-100">
                                <iframe id="scanViewer" src="{{ route('appointments.result.access', [$appointment->id, 'lab', 'preview']) }}" class="w-100 h-100 rounded-bottom border border-warning bg-card" style="min-height: 800px; border: none;"></iframe>
                            </div>
                        @else
                            <div id="reportPdfContainer" class="position-relative w-100 {{ $hasScan && !$isImage ? '' : 'd-none' }}">
                                <iframe id="scanViewer" src="{{ $hasScan && !$isImage ? Storage::url($scanPath) : '' }}" class="w-100 h-100 rounded-bottom border border-warning bg-card" style="min-height: 800px; border: none;"></iframe>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

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
            <div class="mb-3">
                <label class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return reason --</option>
                    <option value="Mismatched patient identification or Case ID">Mismatched patient identification or Case ID</option>
                    <option value="Incomplete test results or missing values">Incomplete test results or missing values</option>
                    <option value="Incorrect reference ranges for this patient profile">Incorrect reference ranges for this patient profile</option>
                    <option value="Discrepancies in clinical signature or licenses">Discrepancies in clinical signature or licenses</option>
                    <option value="Others">Others (Specify details below)</option>
                </select>
            </div>
            <div id="custom_return_reason_wrapper" class="mb-3 d-none">
                <label for="reason_textarea" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                <textarea id="reason_textarea" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="4" placeholder="Identify the specific correction needed..."></textarea>
                <div class="mt-2">
                    <small class="text-muted smaller italic">Minimum 5 characters required for validation.</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2.5" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-danger w-100 py-3 fw-bold uppercase">Confirm Return</button>
            </div>
        </form>
    </div>
</div>

{{-- THEMED ERROR VALIDATION WARNING MODAL --}}
<div class="modal fade" id="validationErrorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-danger display-4 d-block"></i>
            </div>
            <h5 class="text-main fw-bold mb-2 uppercase tracking-tighter" id="validationErrorTitle">Omissions Found</h5>
            <div id="validationErrorMsg" class="text-secondary small mb-4">At least one laboratory test examination must be entered before saving and releasing to the Hub.</div>
            <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" data-bs-dismiss="modal">UNDERSTOOD</button>
        </div>
    </div>
</div>

{{-- MULTI-FORMAT LIGHTBOX OVERLAY WITH SECURE PREVIEW GATES --}}
@include('layouts.partials.lightbox-overlay')

{{-- ROW SPREADSHEET ROW TEMPLATE ROW --}}
<template id="rowTemplate">
    <tr class="border-bottom border-secondary border-opacity-10 transition-all">
        <td class="text-white fw-bold py-3 test-name-label uppercase" style="color: var(--text-main) !important;"></td>
        <td>
            <input type="hidden" name="lab_data[results][INDEX][name]" class="input-test-name">
            <div class="d-flex justify-content-center">
                <input type="text" name="lab_data[results][INDEX][value]" class="form-control text-center fw-bold py-2 result-value-input" placeholder="--" required>
            </div>
        </td>
        <td>
            <div class="d-flex justify-content-center">
                <input type="text" name="lab_data[results][INDEX][ref_range]" class="form-control text-center small italic py-2 input-ref-range" style="background-color: var(--bg-card); color: var(--text-muted); border: 1.5px solid var(--border-color); font-size: 0.8rem;">
            </div>
        </td>
        <td class="text-end">
            @if(!$isReadonly)
                <button type="button" class="btn btn-link text-danger p-0 remove-row-btn"><i class="bi bi-x-circle fs-5"></i></button>
            @endif
        </td>
    </tr>
</template>

@push('scripts')
<script>
// 1. Reference ranges lookup map
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

// Universal Global Lightbox Zoom Helper
window.zoomQR = function(fileSrc) {
    if (!fileSrc) return;
    if (typeof window.openFilePreview === 'function') {
        window.openFilePreview(fileSrc, 'Laboratory Scan Preview');
    } else if (typeof window.zoomFile === 'function') {
        window.zoomFile(fileSrc);
    }
};

// 2. Function to dynamically add a manual test examination row
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
        if ("{{ $isReadonly }}" === "1" || "true" === "{{ $isReadonly }}") {
            valInput.readOnly = true;
        }
    }
    
    rowIdx++;
    if (selectEl) selectEl.value = '';
    if (customInput) customInput.value = '';

    const manualWorkstation = document.getElementById('manual-workstation');
    const isManual = manualWorkstation && !manualWorkstation.classList.contains('d-none');
    setManualFieldsRequired(isManual);
}
window.addTestRow = addTestRow;

// Setup and teardown required status on all manual input elements depending on current layout mode
function setManualFieldsRequired(required) {
    const fields = [
        document.getElementById('case_no_field'),
        document.getElementById('lab_meta_date'),
        document.getElementById('lab_meta_requested_by'),
        document.getElementById('sig_rel_name'),
        document.getElementById('sig_rel_lic'),
        document.getElementById('sig_val1_name'),
        document.getElementById('sig_val1_lic'),
        document.getElementById('sig_val2_name'),
        document.getElementById('sig_val2_lic')
    ];

    fields.forEach(field => {
        if (field) {
            if (field.id === 'case_no_field') {
                field.setAttribute('required', 'required');
                field.removeAttribute('disabled');
            } else {
                if (required) {
                    field.setAttribute('required', 'required');
                    field.removeAttribute('disabled');
                } else {
                    field.removeAttribute('required');
                    field.setAttribute('disabled', 'disabled');
                }
            }
        }
    });

    document.querySelectorAll('.result-value-input').forEach(input => {
        if (required) {
            input.setAttribute('required', 'required');
            input.removeAttribute('disabled');
        } else {
            input.removeAttribute('required');
            input.setAttribute('disabled', 'disabled');
        }
    });
}

function toggleScanPriority(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const isImgFile = file.type.startsWith('image/');
        const reader = new FileReader();

        reader.onload = e => {
            const imgContainer = document.getElementById('imagePreviewContainer');
            const imgElement = document.getElementById('imagePreviewImg');
            const pdfContainer = document.getElementById('reportPdfContainer');
            const viewer = document.getElementById('scanViewer');

            if (isImgFile && imgContainer && imgElement) {
                imgElement.src = e.target.result;
                imgContainer.setAttribute('onclick', `zoomQR('${e.target.result}')`);
                imgContainer.classList.remove('d-none');
                if (pdfContainer) pdfContainer.classList.add('d-none');
            } else if (pdfContainer && viewer) {
                viewer.src = e.target.result;
                pdfContainer.classList.remove('d-none');
                if (imgContainer) imgContainer.classList.add('d-none');
            }

            const manualPanel = document.getElementById('manual-workstation');
            if (manualPanel) manualPanel.classList.add('d-none');

            const sidebarContainer = document.getElementById('sidebar-manual-fields');
            if (sidebarContainer) sidebarContainer.classList.add('d-none');

            setManualFieldsRequired(false);

            if (document.getElementById('report-scan-upload-box')) {
                document.getElementById('report-scan-upload-box').classList.add('d-none');
            }
            document.getElementById('scan-preview-zone').classList.remove('d-none');
            document.getElementById('main-panel-container').className = 'col-md-8';
        };
        reader.readAsDataURL(file);
    }
}
window.toggleScanPriority = toggleScanPriority;

function removeScan() {
    const scanInput = document.getElementById('lab_scan_input');
    if (scanInput) scanInput.value = "";

    const clearField = document.getElementById('clear_scan_field');
    if (clearField) clearField.value = "1";

    const imgContainer = document.getElementById('imagePreviewContainer');
    if (imgContainer) {
        imgContainer.classList.add('d-none');
        const imgElement = document.getElementById('imagePreviewImg');
        if (imgElement) imgElement.src = "";
        imgContainer.removeAttribute('onclick');
    }

    const pdfContainer = document.getElementById('reportPdfContainer');
    if (pdfContainer) {
        pdfContainer.classList.add('d-none');
        const viewer = document.getElementById('scanViewer');
        if (viewer) viewer.src = "";
    }

    const manualPanel = document.getElementById('manual-workstation');
    if (manualPanel) manualPanel.classList.remove('d-none');

    const sidebarContainer = document.getElementById('sidebar-manual-fields');
    if (sidebarContainer) sidebarContainer.classList.remove('d-none');

    setManualFieldsRequired(true);

    const uploadBox = document.getElementById('report-scan-upload-box');
    if (uploadBox) uploadBox.classList.remove('d-none');

    const previewZone = document.getElementById('scan-preview-zone');
    if (previewZone) previewZone.classList.add('d-none');

    document.getElementById('main-panel-container').className = 'col-md-8';
}
window.removeScan = removeScan;

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup on page load
    if ("{{ $hasScan ? '1' : '0' }}" === "1") {
        const manualPanel = document.getElementById('manual-workstation');
        if (manualPanel) manualPanel.classList.add('d-none');

        const sidebarContainer = document.getElementById('sidebar-manual-fields');
        if (sidebarContainer) sidebarContainer.classList.add('d-none');

        setManualFieldsRequired(false);
        document.getElementById('main-panel-container').className = 'col-md-8';
    } else {
        setManualFieldsRequired(true);
    }

    if("{{ $isReadonly }}") {
        const form = document.getElementById('labForm');
        form.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(el => el.disabled = true);
    }

    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('labReturnForm');

    if (selectEl && textareaEl && textareaWrapper && formEl) {
        selectEl.addEventListener('change', function() {
            if (this.value === 'Others') {
                textareaWrapper.classList.remove('d-none');
                textareaEl.setAttribute('required', 'required');
                textareaEl.setAttribute('name', 'reason');
                selectEl.removeAttribute('name');
                textareaEl.value = '';
            } else {
                textareaWrapper.classList.add('d-none');
                textareaEl.removeAttribute('required');
                textareaEl.removeAttribute('name');
                selectEl.setAttribute('name', 'reason');
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

    // MAIN FORM SUBMIT GATEWAY: Enforces validation with inline error highlights
    const mainForm = document.getElementById('labForm');
    if (mainForm) {
        mainForm.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalid = null;

            // Flush previous error states
            document.querySelectorAll('#labForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('#labForm .invalid-feedback').forEach(el => {
                el.classList.add('d-none');
                el.innerText = '';
            });

            function markInvalid(input, errId, msg) {
                if (!input) return;
                input.classList.add('is-invalid');
                const errDiv = document.getElementById(errId);
                if (errDiv) {
                    errDiv.innerText = msg;
                    errDiv.classList.remove('d-none');
                    errDiv.classList.add('d-block');
                }
                isValid = false;
                if (!firstInvalid) firstInvalid = input;
            }

            const manualWorkstation = document.getElementById('manual-workstation');
            const isManualMode = manualWorkstation && !manualWorkstation.classList.contains('d-none');

            // 1. Case # Validation (Always required)
            const caseNo = document.getElementById('case_no_field');
            if (caseNo && !caseNo.value.trim()) {
                markInvalid(caseNo, 'err_case_no', 'Case Number is required.');
            }

            // 2. Validate Manual Mode Fields
            if (isManualMode) {
                const date = document.getElementById('lab_meta_date');
                if (date && !date.value) markInvalid(date, 'err_lab_meta_date', 'Date is required.');

                const reqBy = document.getElementById('lab_meta_requested_by');
                if (reqBy && !reqBy.value.trim()) markInvalid(reqBy, 'err_lab_meta_requested_by', 'Requested By is required.');

                const relName = document.getElementById('sig_rel_name');
                if (relName && !relName.value.trim()) markInvalid(relName, 'err_sig_rel_name', 'Released By name is required.');

                const relLic = document.getElementById('sig_rel_lic');
                if (relLic && !relLic.value.trim()) markInvalid(relLic, 'err_sig_rel_lic', 'Released By license is required.');

                const val1Name = document.getElementById('sig_val1_name');
                if (val1Name && !val1Name.value.trim()) markInvalid(val1Name, 'err_sig_val1_name', 'Validator 1 name is required.');

                const val1Lic = document.getElementById('sig_val1_lic');
                if (val1Lic && !val1Lic.value.trim()) markInvalid(val1Lic, 'err_sig_val1_lic', 'Validator 1 license is required.');

                const val2Name = document.getElementById('sig_val2_name');
                if (val2Name && !val2Name.value.trim()) markInvalid(val2Name, 'err_sig_val2_name', 'Validator 2 name is required.');

                const val2Lic = document.getElementById('sig_val2_lic');
                if (val2Lic && !val2Lic.value.trim()) markInvalid(val2Lic, 'err_sig_val2_lic', 'Validator 2 license is required.');

                const totalRows = document.querySelectorAll('#labBody tr').length;
                if (totalRows === 0) {
                    const tableErr = document.getElementById('err_manual_table');
                    if (tableErr) {
                        tableErr.innerText = 'At least one laboratory examination result must be entered.';
                        tableErr.classList.remove('d-none');
                        tableErr.classList.add('d-block');
                    }
                    isValid = false;
                    if (!firstInvalid) firstInvalid = document.getElementById('testSearch') || manualWorkstation;
                } else {
                    document.querySelectorAll('#labBody .result-value-input').forEach(valInput => {
                        if (!valInput.value.trim()) {
                            valInput.classList.add('is-invalid');
                            isValid = false;
                            if (!firstInvalid) firstInvalid = valInput;
                        }
                    });
                }
            } else {
                // Scan mode: scan file must be present
                const scanInput = document.getElementById('lab_scan_input');
                const hasExistingScan = "{{ $hasScan ? '1' : '0' }}" === "1";
                if (!hasExistingScan && scanInput && (!scanInput.files || scanInput.files.length === 0)) {
                    markInvalid(scanInput, 'err_lab_scan', 'A completed laboratory scan file is required.');
                }
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
                return false;
            }
        });
    }

    // Dismiss errors on input change
    document.querySelectorAll('#labForm input, #labForm select, #labForm textarea').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            let errDiv = document.getElementById('err_' + input.id) || document.getElementById('err_' + input.name.replace(/\[|\]/g, '_'));
            if (errDiv) {
                errDiv.classList.add('d-none');
                errDiv.classList.remove('d-block');
            }
        });
    });

    // Seeded existing results safely inside DOMContentLoaded
    @if(!empty($res->lab_data['results']))
        @foreach($res->lab_data['results'] as $r)
            addTestRow('{!! addslashes($r['name']) !!}', '{!! addslashes($r['value']) !!}', '{!! addslashes($r['ref_range'] ?? '') !!}');
        @endforeach
    @endif

    // Dynamically bind the click event to "ADD FIELD" button
    const addFieldBtn = document.getElementById('add_field_btn');
    if (addFieldBtn) {
        addFieldBtn.addEventListener('click', () => {
            addTestRow();
        });
    }

    // Dynamically bind the click event to "REMOVE & RESTORE FORM" button
    const removeScanBtn = document.getElementById('remove_scan_btn');
    if (removeScanBtn) {
        removeScanBtn.addEventListener('click', removeScan);
    }

    // Dynamic event delegation on `#labBody` to remove spreadsheet rows
    const labBody = document.getElementById('labBody');
    if (labBody) {
        labBody.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-row-btn');
            if (removeBtn) {
                removeBtn.closest('tr').remove();
                const manualWorkstation = document.getElementById('manual-workstation');
                const isManual = manualWorkstation && !manualWorkstation.classList.contains('d-none');
                setManualFieldsRequired(isManual);
            }
        });
    }
});
</script>
@endpush

<style>
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
.result-value-input,
.input-ref-range {
    max-width: 250px;
}
.image-preview-wrapper {
    position: relative;
}
.image-preview-wrapper .zoom-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    background: rgba(0, 0, 0, 0.6);
    transition: opacity 0.22s ease-in-out;
    border-radius: inherit;
}
.image-preview-wrapper:hover .zoom-overlay {
    opacity: 1;
}
#lab-workstation-root .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>