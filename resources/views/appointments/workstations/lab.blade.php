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

{{-- FIXED: Changed container-fluid to container to restore alignment with the header layout --}}
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
    <form id="labForm" action="{{ $isReadonly ? route('workstation.verify', [$appointment->id, 'lab']) : route('workstation.lab.save', $appointment->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="clear_scan" id="clear_scan_field" value="0">
        <div class="row g-4">

            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES --}}
            <div class="col-md-4" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Clinical Metadata</h6>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Case #</label>
                        <input type="text" name="case_no" id="case_no_field" class="form-control" value="{{ $res->lab_data['metadata']['case_no'] ?? '' }}" placeholder="Enter Case ID" required>
                    </div>

                    {{-- FIXED: Hides only the optional metadata snap fields on Scan Mode, keeping Case # visible --}}
                    <div id="sidebar-manual-fields" class="{{ $hasScan ? 'd-none' : '' }}">
                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Patient Name</label>
                            <input type="text" name="lab_data[metadata][name]" class="form-control" value="{{ $res->lab_data['metadata']['name'] ?? strtoupper($appointment->patient_name) }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Address</label>
                            <input type="text" name="lab_data[metadata][address]" class="form-control" value="{{ $res->lab_data['metadata']['address'] ?? $appointment->patient_address }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Date</label>
                                <input type="date" name="lab_data[metadata][date]" class="form-control" value="{{ isset($res->lab_data['metadata']['date']) ? \Carbon\Carbon::parse($res->lab_data['metadata']['date'])->format('Y-m-d') : date('Y-m-d') }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            </div>
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Age/Sex</label>
                                <input type="text" name="lab_data[metadata][age_sex]" class="form-control" value="{{ $res->lab_data['metadata']['age_sex'] ?? ($appointment->patient_age.' / '.$appointment->patient_sex) }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Requested By</label>
                            <input type="text" name="organization_name" class="form-control" value="{{ $appointment->organization_name ?? 'INDIVIDUAL' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Clinical Signatories</h6>
                        <div class="mb-3 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Released By</label>
                            <input type="text" name="lab_data[sig][rel_name]" class="form-control mb-1" placeholder="Encoder Name" value="{{ $res->lab_data['sig']['rel_name'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <input type="text" name="lab_data[sig][rel_lic]" class="form-control" placeholder="License / Position" value="{{ $res->lab_data['sig']['rel_lic'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <div class="mb-3 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Validated By 1</label>
                            <input type="text" name="lab_data[sig][val1_name]" class="form-control mb-1" placeholder="Validator Name" value="{{ $res->lab_data['sig']['val1_name'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <input type="text" name="lab_data[sig][val1_lic]" class="form-control" placeholder="License / Position" value="{{ $res->lab_data['sig']['val1_lic'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <div class="mb-0 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Validated By 2</label>
                            <input type="text" name="lab_data[sig][val2_name]" class="form-control mb-1" placeholder="Pathologist Name" value="{{ $res->lab_data['sig']['val2_name'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <input type="text" name="lab_data[sig][val2_lic]" class="form-control" placeholder="License / Position" value="{{ $res->lab_data['sig']['val2_lic'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
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
                                <input type="file" name="lab_scan" id="lab_scan_input" class="form-control" onchange="toggleScanPriority(this)">
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

{{-- FIXED: Modals, overlays, and script blocks moved strictly outside of @section('content') to resolve section ending buffer anomalies --}}

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
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox(event)">
    <div class="text-center p-3 animate-fade-in w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="max-width: 95vw; max-height: 95vh;">
        
        {{-- Floating File Canvas --}}
        <div id="lightbox_viewer_container" class="position-relative d-flex align-items-center justify-content-center bg-white rounded p-2 border border-secondary shadow-lg" style="max-width: 85vw; max-height: 80vh; overflow: auto; min-width: 300px; min-height: 300px;">
            <!-- Render Image Scan -->
            <img src="" id="lightbox_qr_img" alt="Zoomed Asset" class="img-fluid rounded transition-all" style="max-height: 75vh; max-width: 80vw; object-fit: contain; transform: scale(1); transform-origin: center; cursor: grab;">
            
            <!-- Render PDF Document Scan -->
            <iframe id="lightbox_pdf_viewer" class="d-none rounded" style="width: 80vw; height: 75vh; border: none;"></iframe>
        </div>

        {{-- Interactive Document Control Toolbar --}}
        <div id="lightbox_zoom_controls" class="mt-3 d-flex gap-3 align-items-center bg-dark bg-opacity-75 px-4 py-2 rounded-pill border border-secondary">
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="zoomImage(-0.15, event)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
            <span id="zoom_percent" class="text-white small fw-bold">100%</span>
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="zoomImage(0.15, event)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="toggleFullscreen(event)" title="Toggle Fullscreen"><i class="bi bi-fullscreen" id="fullscreen_icon"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2.5 py-1" onclick="resetZoom(event)" title="Reset Zoom"><i class="bi bi-arrow-counterclockwise"></i></button>
        </div>

        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the dark overlay boundary to close preview</p>
    </div>
</div>

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
                {{-- FIXED: Removed inline onclick and added helper class remove-row-btn for delegation [354] --}}
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
let currentScale = 1;
let translateX = 0;
let translateY = 0;
let isDragging = false;
let startX, startY;

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

    // Dynamically re-align the required/disabled states on newly appended rows
    const manualWorkstation = document.getElementById('manual-workstation');
    const isManual = manualWorkstation && !manualWorkstation.classList.contains('d-none');
    setManualFieldsRequired(isManual);
}
window.addTestRow = addTestRow;

// Setup and teardown required status on all manual input elements depending on current layout mode
function setManualFieldsRequired(required) {
    const fields = [
        document.getElementById('case_no_field'), // Standalone case_no (Always Required)
        document.querySelector('input[name="lab_data[metadata][name]"]'),
        document.querySelector('input[name="lab_data[metadata][address]"]'),
        document.querySelector('input[name="lab_data[metadata][date]"]'),
        document.querySelector('input[name="lab_data[metadata][age_sex]"]'), // FIXED: Matches lab structure [376]
        document.querySelector('input[name="organization_name"]'),
        document.querySelector('input[name="lab_data[sig][rel_name]"]'),
        document.querySelector('input[name="lab_data[sig][rel_lic]"]'),
        document.querySelector('input[name="lab_data[sig][val1_name]"]'),
        document.querySelector('input[name="lab_data[sig][val1_lic]"]'),
        document.querySelector('input[name="lab_data[sig][val2_name]"]'),
        document.querySelector('input[name="lab_data[sig][val2_lic]"]')
    ];

    fields.forEach(field => {
        if (field) {
            // case_no_field is always required and enabled!
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

    // Mirror logic changes dynamically to newly added row inputs within manual grid
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

            // FIXED: Hide only the optional manual fields inside the sidebar, keep Case # visible [340]
            const manualSidebarFields = document.getElementById('sidebar-manual-fields');
            if (manualSidebarFields) manualSidebarFields.classList.add('d-none');

            setManualFieldsRequired(false);

            // Only hide the optional report scan box
            if (document.getElementById('report-scan-upload-box')) {
                document.getElementById('report-scan-upload-box').classList.add('d-none');
            }
            document.getElementById('scan-preview-zone').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}
window.toggleScanPriority = toggleScanPriority;

function removeScan() {
    const scanInput = document.getElementById('lab_scan_input');
    if (scanInput) scanInput.value = "";

    // FIXED: Set clear_scan to 1 so the backend knows to delete the old scan
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

    // FIXED: Restore the manual fields inside the sidebar [340]
    const manualSidebarFields = document.getElementById('sidebar-manual-fields');
    if (manualSidebarFields) manualSidebarFields.classList.remove('d-none');

    setManualFieldsRequired(true);

    const uploadBox = document.getElementById('report-scan-upload-box');
    if (uploadBox) uploadBox.classList.remove('d-none');

    const previewZone = document.getElementById('scan-preview-zone');
    if (previewZone) previewZone.classList.add('d-none');
}
window.removeScan = removeScan;

function viewReportFullscreen() {
    const fileInput = document.getElementById('lab_scan_input');
    if (fileInput && fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        const reader = new FileReader();
        reader.onload = e => {
            zoomQR(e.target.result);
        };
        reader.readAsDataURL(file);
    } else {
        const savedPath = "{{ $hasScan ? Storage::url($scanPath) : '' }}";
        if (savedPath) {
            zoomQR(savedPath);
        } else if ("{{ $isReadonly }}") {
            zoomQR("{{ route('appointments.result.access', [$appointment->id, 'lab', 'preview']) }}");
        }
    }
}
window.viewReportFullscreen = viewReportFullscreen;

function zoomImage(amount, event) {
    if (event) event.stopPropagation();

    currentScale += amount;
    currentScale = Math.max(0.5, Math.min(3, currentScale));

    const img = document.getElementById('lightbox_qr_img');
    if (img) {
        img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
        img.style.cursor = currentScale > 1 ? 'grab' : 'default';
    }

    const percentEl = document.getElementById('zoom_percent');
    if (percentEl) {
        percentEl.innerText = `${Math.round(currentScale * 100)}%`;
    }
}
window.zoomImage = zoomImage;

function resetZoom(event) {
    if (event) event.stopPropagation();

    currentScale = 1;
    translateX = 0;
    translateY = 0;
    isDragging = false;

    const img = document.getElementById('lightbox_qr_img');
    if (img) {
        img.style.transform = 'translate(0px, 0px) scale(1)';
        img.style.cursor = 'default';
    }

    const percentEl = document.getElementById('zoom_percent');
    if (percentEl) {
        percentEl.innerText = '100%';
    }
}
window.resetZoom = resetZoom;

function zoomFile(fileSrc) {
    if (!fileSrc) return;

    const isPdf = fileSrc.toLowerCase().endsWith('.pdf') || fileSrc.startsWith('data:application/pdf');
    const img = document.getElementById('lightbox_qr_img');
    const iframe = document.getElementById('lightbox_pdf_viewer');
    const controls = document.getElementById('lightbox_zoom_controls');

    resetZoom();

    if (isPdf) {
        img.classList.add('d-none');
        controls.classList.add('d-none');
        iframe.src = fileSrc;
        iframe.classList.remove('d-none');
    } else {
        iframe.classList.add('d-none');
        iframe.src = '';
        img.src = fileSrc;
        img.classList.remove('d-none');
        controls.classList.remove('d-none');
    }

    document.getElementById('qr_lightbox').classList.remove('d-none');
    document.getElementById('qr_lightbox').classList.add('d-flex');
}
window.zoomQR = zoomFile;

function closeQRLightbox(event) {
    if (event) {
        const container = document.getElementById('lightbox_viewer_container');
        const controls = document.getElementById('lightbox_zoom_controls');
        if (container.contains(event.target) || (controls && controls.contains(event.target))) {
            return;
        }
    }
    document.getElementById('qr_lightbox').classList.add('d-none');
    document.getElementById('qr_lightbox').classList.remove('d-flex');

    if (document.fullscreenElement) {
        document.exitFullscreen().catch(err => console.error("Error exiting fullscreen:", err));
    }
    resetZoom();
}
window.closeQRLightbox = closeQRLightbox;

function toggleFullscreen(event) {
    if (event) event.stopPropagation();

    const container = document.getElementById('lightbox_viewer_container');
    const icon = document.getElementById('fullscreen_icon');

    if (!document.fullscreenElement) {
        container.requestFullscreen().then(() => {
            if (icon) {
                icon.classList.remove('bi-fullscreen');
                icon.classList.add('bi-fullscreen-exit');
            }
        }).catch(err => {
            console.error("Error attempting to enable fullscreen mode:", err);
        });
    } else {
        document.exitFullscreen().then(() => {
            if (icon) {
                icon.classList.remove('bi-fullscreen-exit');
                icon.classList.add('bi-fullscreen');
            }
        }).catch(err => {
            console.error("Error attempting to exit fullscreen mode:", err);
        });
    }
}
window.toggleFullscreen = toggleFullscreen;

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup on page load
    if ("{{ $hasScan ? '1' : '0' }}" === "1") {
        const manualPanel = document.getElementById('manual-workstation');
        if (manualPanel) manualPanel.classList.add('d-none');

        // FIXED: Hide only the optional manual fields inside the sidebar on page load [340]
        const manualSidebarFields = document.getElementById('sidebar-manual-fields');
        if (manualSidebarFields) manualSidebarFields.classList.add('d-none');

        setManualFieldsRequired(false);
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

    // MAIN FORM SUBMIT GATEWAY: Enforces at least one manual row before submission when not in scan bypass mode
    const mainForm = document.getElementById('labForm');
    if (mainForm) {
        mainForm.addEventListener('submit', function(e) {
            const manualWorkstation = document.getElementById('manual-workstation');
            if (manualWorkstation && !manualWorkstation.classList.contains('d-none')) {
                const totalRows = document.querySelectorAll('#labBody tr').length;
                if (totalRows === 0) {
                    e.preventDefault();
                    
                    // Trigger the high-contrast themed modal instead of a generic browser alert
                    const modalEl = document.getElementById('validationErrorModal');
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modalInstance.show();
                    }
                }
            }
        });
    }

    // Draggable canvas functionality for zoomed-in images
    const img = document.getElementById('lightbox_qr_img');
    if (img) {
        img.addEventListener('mousedown', (e) => {
            if (currentScale > 1) {
                isDragging = true;
                img.style.cursor = 'grabbing';
                startX = e.clientX;
                startY = e.clientY;
                e.preventDefault();
            }
        });

        window.addEventListener('mousemove', (e) => {
            if (isDragging && currentScale > 1) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                translateX += dx;
                translateY += dy;
                startX = e.clientX;
                startY = e.clientY;
                img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
            }
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
            if (img) img.style.cursor = currentScale > 1 ? 'grab' : 'default';
        });

        // Mobile touch swipe gestures
        img.addEventListener('touchstart', (e) => {
            if (currentScale > 1 && e.touches.length === 1) {
                isDragging = true;
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            }
        }, { passive: true });

        img.addEventListener('touchmove', (e) => {
            if (isDragging && currentScale > 1 && e.touches.length === 1) {
                const dx = e.touches[0].clientX - startX;
                const dy = e.touches[0].clientY - startY;
                translateX += dx;
                translateY += dy;
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
            }
        }, { passive: true });

        img.addEventListener('touchend', () => {
            isDragging = false;
        });
    }

    // Fullscreen wheel-to-zoom mapping
    const container = document.getElementById('lightbox_viewer_container');
    if (container) {
        container.addEventListener('wheel', (e) => {
            if (document.fullscreenElement) {
                e.preventDefault();
                const amount = e.deltaY < 0 ? 0.15 : -0.15;
                zoomImage(amount);
            }
        }, { passive: false });
    }

    // FIXED: Seeded existing results safely inside DOMContentLoaded to ensure elements are fully parsed [351, 387]
    @if(!empty($res->lab_data['results']))
        @foreach($res->lab_data['results'] as $r)
            addTestRow('{!! addslashes($r['name']) !!}', '{!! addslashes($r['value']) !!}', '{!! addslashes($r['ref_range'] ?? '') !!}');
        @endforeach
    @endif

    // FIXED: Dynamically bind the click event to "ADD FIELD" button to avoid inline scope blocks [342, 351]
    const addFieldBtn = document.getElementById('add_field_btn');
    if (addFieldBtn) {
        addFieldBtn.addEventListener('click', () => {
            addTestRow();
        });
    }

    // FIXED: Dynamically bind the click event to "REMOVE & RESTORE FORM" button [343, 351]
    const removeScanBtn = document.getElementById('remove_scan_btn');
    if (removeScanBtn) {
        removeScanBtn.addEventListener('click', removeScan);
    }

    // FIXED: Dynamic event delegation on `#labBody` to remove spreadsheet rows [351, 354]
    const labBody = document.getElementById('labBody');
    if (labBody) {
        labBody.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-row-btn');
            if (removeBtn) {
                removeBtn.closest('tr').remove();
                
                // Re-evaluate required properties on row removal
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
</style>