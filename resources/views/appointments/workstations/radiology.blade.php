@extends('layouts.app')

@section('title', 'Encode Results')

@section('content')
@php
    $res = $appointment->result;
    $status = $res->radio_status ?? 'pending';

    // UI Logic States
    $isVerified = ($status === 'verified');
    $isReadonly = in_array($status, ['encoded', 'verified']);
    $hasXray = ($res && $res->xray_image);
    $hasReportScan = ($res && $res->radio_scan);
    $testedDate = $appointment->tested_at ? $appointment->tested_at->format('Y-m-d') : date('Y-m-d');

    // Parse Report Scan Type
    $scanPath = $res->radio_scan ?? null;
    $isImage = false;
    if ($scanPath) {
        $ext = strtolower(pathinfo($scanPath, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']);
    }

    // Parse X-Ray Scan Type
    $xrayPath = $res->xray_image ?? null;
    $isXrayImage = false;
    if ($xrayPath) {
        $xrayExt = strtolower(pathinfo($xrayPath, PATHINFO_EXTENSION));
        $isXrayImage = in_array($xrayExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']);
    }

    // Dynamically reveal preview on page load if scan exists
    $showPreview = $isReadonly || $hasReportScan;
@endphp

<div class="container text-start animate-page pt-4" id="radio-workstation-root">

    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase">
                @if($isVerified) REVIEW MODE @elseif($isReadonly) VERIFICATION MODE @else RADIOLOGY WORKSTATION @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ $appointment->patient_name }}</span> | Status: <span class="text-accent">{{ strtoupper($status) }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>

            @if(!$isReadonly)
                <button type="submit" form="radioForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
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
    @if($res && $res->radio_return_reason && $status != 'verified')
        <div class="alert-clinical p-3 mb-4 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div>
                    <div class="fw-bold small uppercase">Verifier Correction Request:</div>
                    <div class="small italic">"{{ $res->radio_return_reason }}"</div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. CORE SAVE FORM --}}
    <form id="radioForm" action="{{ $isReadonly ? route('workstation.verify', [$appointment->id, 'radio']) : route('workstation.radiology.save', $appointment->id) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="clear_scan" id="clear_scan_field" value="0">
        <div class="row g-4">

            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES --}}
            <div class="col-md-4" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" id="sidebar-card" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Radiology Metadata</h6>

                    {{-- Case # Field (Always Required & Always Visible) --}}
                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Case # / Cert No.</label>
                        <input type="text" name="case_no" id="case_no_field" class="form-control @error('case_no') is-invalid @enderror" value="{{ old('case_no', $res->radio_data['metadata']['case_no'] ?? ($res->radiologyReport?->case_no ?? '')) }}" placeholder="Enter Case ID" {{ $isReadonly ? 'readonly' : 'required' }}>
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
                                <a href="{{ route('appointments.edit-details', $appointment->id) }}?from=radio" class="btn btn-sm btn-outline-accent py-0.5 px-2 smaller uppercase fw-bold" style="font-size: 0.68rem;" title="Edit Patient Details">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Details
                                </a>
                            @endif
                        </div>
                        <div class="text-main fw-bold small mb-1">{{ strtoupper($appointment->patient_name) }}</div>
                        <div class="text-secondary smaller mb-1">{{ $appointment->patient_age }} YRS / {{ strtoupper($appointment->patient_sex) }}</div>
                        <div class="text-muted smaller text-break" style="font-size: 0.72rem; line-height: 1.35;">{{ $appointment->patient_address }}</div>

                        {{-- Hidden snapshot payloads for backend backwards-compatibility --}}
                        <input type="hidden" name="radio_data[metadata][name]" value="{{ strtoupper($appointment->patient_name) }}">
                        <input type="hidden" name="radio_data[metadata][address]" value="{{ $appointment->patient_address }}">
                        <input type="hidden" name="radio_data[metadata][age_sex]" value="{{ $appointment->patient_age }} / {{ strtoupper($appointment->patient_sex) }}">
                    </div>

                    {{-- OPTIONAL MANUAL METADATA & SIGNATORIES (Hidden when file/scan is inputted) --}}
                    <div id="sidebar-manual-fields" class="{{ $isReadonly || $hasReportScan ? 'd-none' : '' }}">
                        {{-- Date of Exam --}}
                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Date of Exam</label>
                            <input type="date" name="radio_data[metadata][date]" id="radio_meta_date" class="form-control @error('radio_data.metadata.date') is-invalid @enderror" value="{{ old('radio_data.metadata.date', isset($res->radio_data['metadata']['date']) ? \Carbon\Carbon::parse($res->radio_data['metadata']['date'])->format('Y-m-d') : $testedDate) }}" {{ $hasReportScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_radio_meta_date"></div>
                            @error('radio_data.metadata.date')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Technique / Examination Editable Dropdown --}}
                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Technique / Examination</label>
                            <input type="text" name="radio_data[technique]" id="technique_field" list="technique_options" class="form-control uppercase @error('radio_data.technique') is-invalid @enderror" placeholder="Select or type technique..." value="{{ old('radio_data.technique', $res->radio_data['technique'] ?? ($res->radiologyReport?->technique ?? '')) }}" {{ $hasReportScan ? 'disabled' : 'required' }}>
                            <datalist id="technique_options">
                                <option value="CHEST PA">
                                <option value="CHEST AP / LATERAL">
                                <option value="CHEST PA / LATERAL">
                                <option value="ABDOMEN PLAIN (KUB)">
                                <option value="SKULL AP / LATERAL">
                                <option value="CERVICAL SPINE AP / LATERAL">
                                <option value="LUMBOSACRAL SPINE AP / LATERAL">
                                <option value="UPPER EXTREMITY">
                                <option value="LOWER EXTREMITY">
                                <option value="PELVIS AP">
                            </datalist>
                            <div class="invalid-feedback d-none" id="err_technique"></div>
                            @error('radio_data.technique')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Radiologist Signatory (Blank on Default) --}}
                        <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Manual Signatory (PDF)</h6>
                        <div class="mb-3 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Radiologist Name</label>
                            <input type="text" name="radio_data[sig][name]" id="sig_radio_name" class="form-control mb-1 @error('radio_data.sig.name') is-invalid @enderror" placeholder="Radiologist Name" value="{{ old('radio_data.sig.name', $res->radio_data['sig']['name'] ?? ($res->radiologyReport?->radiologist_name ?? '')) }}" {{ $hasReportScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none mb-1" id="err_sig_radio_name"></div>
                            @error('radio_data.sig.name')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror

                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">License / PRC No.</label>
                            <input type="text" name="radio_data[sig][lic]" id="sig_radio_lic" class="form-control @error('radio_data.sig.lic') is-invalid @enderror" placeholder="License / Position" value="{{ old('radio_data.sig.lic', $res->radio_data['sig']['lic'] ?? ($res->radiologyReport?->radiologist_license ?? '')) }}" {{ $hasReportScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_sig_radio_lic"></div>
                            @error('radio_data.sig.lic')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- MAIN WORKSTATION PANEL --}}
            <div class="col-md-8" id="main-panel-container">

                {{-- Prominent Dual-Column Upload Control Center --}}
                @if(!$isReadonly)
                    <div class="row g-3 mb-4" id="upload-control-center">
                        {{-- 1. Patient X-Ray Upload Box --}}
                        <div class="col-md-6 {{ $hasXray ? 'd-none' : '' }}" id="xray-image-upload-box">
                            <div class="card p-3 h-100 text-center" style="background-color: rgba(220, 53, 69, 0.02); border: 2px dashed rgba(220, 53, 69, 0.25) !important; border-radius: 12px;">
                                <h6 class="text-danger fw-bold mb-1 uppercase"><i class="bi bi-camera-fill me-2 fs-5"></i>Patient X-Ray Scan (Required)</h6>
                                <p class="text-secondary small mb-3">Select the raw patient chest radiologic snapshot file.</p>
                                <input type="file" name="xray_image" id="xray_input" class="form-control form-control-sm @error('xray_image') is-invalid @enderror" onchange="previewXray(this)" {{ !$hasXray ? 'required' : '' }}>
                                <div class="invalid-feedback d-none" id="err_xray_image"></div>
                                @error('xray_image')
                                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                                @enderror
                                @if($hasXray)
                                    <p class="text-success smaller mt-2 mb-0 fw-bold"><i class="bi bi-check-circle-fill me-1"></i> X-Ray File Cached on Server</p>
                                @endif
                            </div>
                        </div>

                        {{-- 2. Optional Report PDF Scan Override --}}
                        <div class="col-md-6 {{ $hasReportScan ? 'd-none' : '' }}" id="report-scan-upload-box">
                            <div class="card p-3 h-100 text-center" style="background-color: rgba(255, 193, 7, 0.02); border: 2px dashed rgba(255, 193, 7, 0.25) !important; border-radius: 12px;">
                                <h6 class="text-warning fw-bold mb-1 uppercase"><i class="bi bi-file-earmark-arrow-up-fill me-2 fs-5"></i>Report PDF Scan (Optional)</h6>
                                <p class="text-secondary small mb-3">Uploading a scanned report hides the manual text fields below.</p>
                                <input type="file" name="radio_scan" id="report_scan_input" class="form-control form-control-sm @error('radio_scan') is-invalid @enderror" onchange="toggleScanPriority(this)">
                                <div class="invalid-feedback d-none" id="err_radio_scan"></div>
                                @error('radio_scan')
                                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                                @enderror
                                @if($hasReportScan)
                                    <p class="text-warning smaller mt-2 mb-0 fw-bold"><i class="bi bi-eye-fill me-1"></i> PDF Scan Override Active</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- TOP: X-RAY IMAGE INTERACTIVE PREVIEW --}}
                <div id="xray-viewer-card" class="card border-secondary bg-card mb-4 overflow-hidden shadow-lg {{ !$hasXray ? 'd-none' : '' }}">
                    <div class="bg-danger text-white p-2 px-3 fw-bold small uppercase d-flex justify-content-between">
                        <span><i class="bi bi-camera-fill me-2"></i>Patient X-Ray Preview</span>
                        @if(!$isReadonly)
                            <button type="button" class="btn btn-sm btn-dark fw-bold px-3" onclick="removeXray()">REMOVE</button>
                        @endif
                    </div>
                    <div class="p-2 text-center" style="background-color: rgba(0,0,0,0.03) !important;">
                        {{-- Image container with click-to-zoom --}}
                        <div id="xrayImageContainer" class="position-relative d-inline-block image-preview-wrapper {{ $hasXray && $isXrayImage ? '' : 'd-none' }}" style="cursor: zoom-in;" onclick="zoomQR('{{ $hasXray && $isXrayImage ? Storage::url($res->xray_image) : '' }}')">
                            <img id="xrayPreview" src="{{ $hasXray && $isXrayImage ? Storage::url($res->xray_image) : '#' }}" class="img-fluid rounded shadow-sm" style="max-height: 450px; object-fit: contain; border: 1px solid var(--border-color);">
                            <div class="position-absolute top-50 start-50 translate-middle zoom-overlay d-flex flex-column align-items-center justify-content-center text-white">
                                <i class="bi bi-zoom-in fs-1"></i>
                                <span class="fw-bold mt-2">CLICK TO ZOOM FULLSCREEN</span>
                            </div>
                        </div>
                        {{-- PDF fallback iframe --}}
                        <div id="xrayPdfContainer" class="position-relative {{ $hasXray && !$isXrayImage ? '' : 'd-none' }}" style="cursor: zoom-in;" onclick="viewXrayFullscreen()">
                            <iframe id="xrayViewer" src="{{ $hasXray && !$isXrayImage ? Storage::url($res->xray_image) : '' }}" class="w-100 rounded-bottom border border-secondary bg-card" style="min-height: 500px; border: none; pointer-events: none;"></iframe>
                            <div class="position-absolute top-0 start-0 w-100 h-100"></div>
                        </div>
                    </div>
                </div>

                {{-- BOTTOM: REPORT CONTENT --}}
                <div id="report-workstation">
                    {{-- MANUAL FINDINGS --}}
                    @if(!$isReadonly)
                        <div id="manual-panel" class="card p-4 border-secondary bg-card shadow-lg {{ $hasReportScan ? 'd-none' : '' }}">
                            <h6 class="border-bottom border-secondary border-opacity-25 pb-2 mb-3 uppercase small fw-bold" style="color: var(--text-main);">Manual Findings Entry</h6>

                            <div class="mb-4">
                                <label class="text-secondary smaller fw-bold mb-1 uppercase">Findings</label>
                                <textarea name="radio_data[findings]" id="findings_field" class="form-control p-3 @error('radio_data.findings') is-invalid @enderror" rows="10" placeholder="Type findings..." {{ $hasReportScan ? 'disabled' : 'required' }}>{{ old('radio_data.findings', $res->radio_data['findings'] ?? ($res->radiologyReport?->findings ?? '')) }}</textarea>
                                <div class="invalid-feedback d-none" id="err_findings"></div>
                                @error('radio_data.findings')
                                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="text-secondary smaller fw-bold mb-1 uppercase">Impression</label>
                                <input type="text" name="radio_data[impression]" id="impression_field" class="form-control py-3 fw-bold @error('radio_data.impression') is-invalid @enderror" value="{{ old('radio_data.impression', $res->radio_data['impression'] ?? ($res->radiologyReport?->impression ?? '')) }}" placeholder="Final clinical impression" {{ $hasReportScan ? 'disabled' : 'required' }}>
                                <div class="invalid-feedback d-none" id="err_impression"></div>
                                @error('radio_data.impression')
                                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    {{-- REPORT SCAN PREVIEW --}}
                    <div id="scan-preview-zone" class="{{ $showPreview ? '' : 'd-none' }} shadow-lg">
                        <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                            <span>
                                @if($isReadonly)
                                    @if($hasReportScan) <i class="bi bi-file-earmark-pdf-fill me-2"></i>PHYSICAL SCAN FILE @else <i class="bi bi-file-pdf me-2"></i>GENERATED CLINICAL PDF PREVIEW @endif
                                @else
                                    REPORT SCAN OVERRIDE ACTIVE
                                @endif
                            </span>
                            @if(!$isReadonly)
                                <button type="button" class="btn btn-sm btn-dark fw-bold px-3" onclick="removeScan()">REMOVE & RESTORE FORM</button>
                            @endif
                        </div>
                        <div class="card border-warning border-top-0 rounded-0 rounded-bottom overflow-hidden shadow bg-card p-3 text-center d-flex justify-content-center align-items-center" style="min-height: 500px;">
                            @if($hasReportScan)
                                @if($isImage)
                                    <div id="imagePreviewContainer" class="position-relative d-inline-block image-preview-wrapper" style="cursor: zoom-in;" onclick="zoomQR('{{ Storage::url($scanPath) }}')">
                                        <img id="imagePreviewImg" src="{{ Storage::url($scanPath) }}" class="img-fluid rounded shadow-sm" style="max-height: 800px; object-fit: contain; border: 1px solid var(--border-color);">
                                        <div class="position-absolute top-50 start-50 translate-middle zoom-overlay d-flex flex-column align-items-center justify-content-center text-white">
                                            <i class="bi bi-zoom-in fs-1"></i>
                                            <span class="fw-bold mt-2">CLICK TO ZOOM FULLSCREEN</span>
                                        </div>
                                    </div>
                                    <div id="reportPdfContainer" class="position-relative w-100 d-none">
                                        <iframe id="reportViewer" src="" class="w-100 h-100 rounded-bottom border border-warning bg-card" style="min-height: 700px; border: none;"></iframe>
                                    </div>
                                @else
                                    <div id="reportPdfContainer" class="position-relative w-100">
                                        <iframe id="reportViewer" src="{{ Storage::url($res->radio_scan) }}" class="w-100 rounded-bottom border border-warning bg-card" style="min-height: 700px; border: none;"></iframe>
                                    </div>
                                @endif
                            @else
                                {{-- Fallback newly selected preview --}}
                                <div id="imagePreviewContainer" class="position-relative d-inline-block image-preview-wrapper d-none" style="cursor: zoom-in;">
                                    <img id="imagePreviewImg" src="" class="img-fluid rounded shadow-sm" style="max-height: 800px; object-fit: contain; border: 1px solid var(--border-color);">
                                </div>
                                <div id="reportPdfContainer" class="position-relative w-100 {{ $isReadonly ? '' : 'd-none' }}">
                                    <iframe id="reportViewer" src="{{ route('appointments.result.access', [$appointment->id, 'radio', 'preview']) }}" class="w-100 rounded-bottom border border-warning bg-card" style="min-height: 800px; border: none;"></iframe>
                                </div>
                            @endif
                        </div>
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
        <form action="{{ route('workstation.verify', [$appointment->id, 'radio']) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-neon fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Enter your name to verify that the uploaded medical scans match this clinical record.</p>
            <div class="mb-4">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Verifier Name</label>
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
        <form action="{{ route('workstation.return', $appointment->id) }}?type=radio" method="POST" id="radioReturnForm" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase mb-1">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this report for corrections.</p>
            <div class="mb-3">
                <label class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" name="reason" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return justification --</option>
                    <option value="Mismatched patient identification or Case ID">Mismatched patient identification or Case ID</option>
                    <option value="Unclear, low-quality, or incorrect X-Ray image snapshot">Unclear, low-quality, or incorrect X-Ray image snapshot</option>
                    <option value="Incomplete findings or vague diagnostic interpretation">Incomplete findings or vague diagnostic interpretation</option>
                    <option value="Discrepancies in radiologist signature or credentials">Discrepancies in radiologist signature or credentials</option>
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

{{-- MULTI-FORMAT LIGHTBOX OVERLAY --}}
@include('layouts.partials.lightbox-overlay')

@push('scripts')
<script>
// Universal Global Lightbox Zoom Helper
window.zoomQR = function(fileSrc) {
    if (!fileSrc) return;
    if (typeof window.openFilePreview === 'function') {
        window.openFilePreview(fileSrc, 'Radiology Asset Preview');
    } else if (typeof window.zoomFile === 'function') {
        window.zoomFile(fileSrc);
    }
};

// Setup and teardown required status on all manual input elements depending on current layout mode
function setManualFieldsRequired(required) {
    const fields = [
        document.getElementById('case_no_field'), // Standalone case_no (Always Required)
        document.getElementById('radio_meta_date'),
        document.getElementById('technique_field'),
        document.getElementById('sig_radio_name'),
        document.getElementById('sig_radio_lic'),
        document.getElementById('findings_field'),
        document.getElementById('impression_field')
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
}

function previewXray(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const isImg = file.type.startsWith('image/');
        const reader = new FileReader();
        reader.onload = e => {
            const imgContainer = document.getElementById('xrayImageContainer');
            const imgElement = document.getElementById('xrayPreview');
            const pdfContainer = document.getElementById('xrayPdfContainer');
            const iframe = document.getElementById('xrayViewer');

            if (isImg && imgContainer && imgElement) {
                imgElement.src = e.target.result;
                imgContainer.setAttribute('onclick', `zoomQR('${e.target.result}')`);
                imgContainer.classList.remove('d-none');
                if (pdfContainer) pdfContainer.classList.add('d-none');
            } else if (pdfContainer && iframe) {
                iframe.src = e.target.result;
                pdfContainer.classList.remove('d-none');
                if (imgContainer) imgContainer.classList.add('d-none');
            }

            document.getElementById('xray_input').removeAttribute('required');
            document.getElementById('xray-viewer-card').classList.remove('d-none');

            if (document.getElementById('xray-image-upload-box')) {
                document.getElementById('xray-image-upload-box').classList.add('d-none');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
window.previewXray = previewXray;

function removeXray() {
    const xrayInput = document.getElementById('xray_input');
    if (xrayInput) {
        xrayInput.value = "";
        xrayInput.setAttribute('required', 'required');
    }

    const imgContainer = document.getElementById('xrayImageContainer');
    if (imgContainer) {
        imgContainer.classList.add('d-none');
        const imgElement = document.getElementById('xrayPreview');
        if (imgElement) imgElement.src = "";
        imgContainer.removeAttribute('onclick');
    }

    const pdfContainer = document.getElementById('xrayPdfContainer');
    if (pdfContainer) {
        pdfContainer.classList.add('d-none');
        const iframe = document.getElementById('xrayViewer');
        if (iframe) iframe.src = "";
    }

    const viewerCard = document.getElementById('xray-viewer-card');
    if (viewerCard) {
        viewerCard.classList.add('d-none');
    }

    const uploadBox = document.getElementById('xray-image-upload-box');
    if (uploadBox) {
        uploadBox.classList.remove('d-none');
    }
}
window.removeXray = removeXray;

function viewXrayFullscreen() {
    const fileInput = document.getElementById('xray_input');
    if (fileInput && fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        const reader = new FileReader();
        reader.onload = e => {
            zoomQR(e.target.result);
        };
        reader.readAsDataURL(file);
    } else {
        const savedPath = "{{ $hasXray ? Storage::url($xrayPath) : '' }}";
        if (savedPath) {
            zoomQR(savedPath);
        }
    }
}
window.viewXrayFullscreen = viewXrayFullscreen;

function toggleScanPriority(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const isImgFile = file.type.startsWith('image/');
        const reader = new FileReader();

        reader.onload = e => {
            const imgContainer = document.getElementById('imagePreviewContainer');
            const imgElement = document.getElementById('imagePreviewImg');
            const pdfContainer = document.getElementById('reportPdfContainer');
            const viewer = document.getElementById('reportViewer');

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

            const manualPanel = document.getElementById('manual-panel');
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
    const scanInput = document.getElementById('report_scan_input');
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
        const viewer = document.getElementById('reportViewer');
        if (viewer) viewer.src = "";
    }

    const manualPanel = document.getElementById('manual-panel');
    if (manualPanel) manualPanel.classList.remove('d-none');

    const sidebarContainer = document.getElementById('sidebar-manual-fields');
    if (sidebarContainer) sidebarContainer.classList.remove('d-none');

    setManualFieldsRequired(true);

    if (document.getElementById('report-scan-upload-box')) {
        document.getElementById('report-scan-upload-box').classList.remove('d-none');
    }
    document.getElementById('scan-preview-zone').classList.add('d-none');

    document.getElementById('main-panel-container').className = 'col-md-8';
}
window.removeScan = removeScan;

function viewReportFullscreen() {
    const fileInput = document.getElementById('report_scan_input');
    if (fileInput && fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        const reader = new FileReader();
        reader.onload = e => {
            zoomQR(e.target.result);
        };
        reader.readAsDataURL(file);
    } else {
        const savedPath = "{{ $hasReportScan ? Storage::url($scanPath) : '' }}";
        if (savedPath) {
            zoomQR(savedPath);
        } else if ("{{ $isReadonly }}") {
            zoomQR("{{ route('appointments.result.access', [$appointment->id, 'radio', 'preview']) }}");
        }
    }
}
window.viewReportFullscreen = viewReportFullscreen;

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup on page load
    if ("{{ $hasReportScan ? '1' : '0' }}" === "1") {
        const manualPanel = document.getElementById('manual-panel');
        if (manualPanel) manualPanel.classList.add('d-none');

        const sidebarContainer = document.getElementById('sidebar-manual-fields');
        if (sidebarContainer) sidebarContainer.classList.add('d-none');

        setManualFieldsRequired(false);
        document.getElementById('main-panel-container').className = 'col-md-8';
    } else {
        setManualFieldsRequired(true);
    }

    if("{{ $isReadonly }}") {
        const form = document.getElementById('radioForm');
        form.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(el => el.disabled = true);
    }

    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('radioReturnForm');

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
    const mainForm = document.getElementById('radioForm');
    if (mainForm) {
        mainForm.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalid = null;

            // Flush previous error states
            document.querySelectorAll('#radioForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('#radioForm .invalid-feedback').forEach(el => {
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

            const manualPanel = document.getElementById('manual-panel');
            const isManualMode = manualPanel && !manualPanel.classList.contains('d-none');

            // 1. Case # Validation (Always required)
            const caseNo = document.getElementById('case_no_field');
            if (caseNo && !caseNo.value.trim()) {
                markInvalid(caseNo, 'err_case_no', 'Case Number is required.');
            }

            // 2. Patient X-Ray Image File Check (Always required if not already uploaded)
            const xrayInput = document.getElementById('xray_input');
            const hasExistingXray = "{{ $hasXray ? '1' : '0' }}" === "1";
            if (!hasExistingXray && xrayInput && (!xrayInput.files || xrayInput.files.length === 0)) {
                markInvalid(xrayInput, 'err_xray_image', 'Patient X-Ray image file is required.');
            }

            // 3. Validate Manual Mode Fields
            if (isManualMode) {
                const date = document.getElementById('radio_meta_date');
                if (date && !date.value) markInvalid(date, 'err_radio_meta_date', 'Date of exam is required.');

                const technique = document.getElementById('technique_field');
                if (technique && !technique.value.trim()) markInvalid(technique, 'err_technique', 'Radiologic technique is required.');

                const relName = document.getElementById('sig_radio_name');
                if (relName && !relName.value.trim()) markInvalid(relName, 'err_sig_radio_name', 'Radiologist Name is required.');

                const relLic = document.getElementById('sig_radio_lic');
                if (relLic && !relLic.value.trim()) markInvalid(relLic, 'err_sig_radio_lic', 'Radiologist License/PRC No. is required.');

                const findings = document.getElementById('findings_field');
                if (findings && !findings.value.trim()) markInvalid(findings, 'err_findings', 'Findings are required.');

                const impression = document.getElementById('impression_field');
                if (impression && !impression.value.trim()) markInvalid(impression, 'err_impression', 'Impression is required.');
            } else {
                // Scan mode: scan file must be present
                const scanInput = document.getElementById('report_scan_input');
                const hasExistingScan = "{{ $hasReportScan ? '1' : '0' }}" === "1";
                if (!hasExistingScan && scanInput && (!scanInput.files || scanInput.files.length === 0)) {
                    markInvalid(scanInput, 'err_radio_scan', 'A completed radiology report scan file is required.');
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
    document.querySelectorAll('#radioForm input, #radioForm select, #radioForm textarea').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            let errDiv = document.getElementById('err_' + input.id) || document.getElementById('err_' + input.name.replace(/\[|\]/g, '_'));
            if (errDiv) {
                errDiv.classList.add('d-none');
                errDiv.classList.remove('d-block');
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
#radio-workstation-root .form-control,
#radio-workstation-root .form-select,
#radio-workstation-root .input-group-text,
#radio-workstation-root .form-control:focus,
#radio-workstation-root .form-select:focus {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}
#radio-workstation-root .input-group-text {
    background-color: var(--border-color) !important;
}
#radio-workstation-root .modal-content .form-control {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border: 1.5px solid var(--border-color) !important;
}
#radio-workstation-root .btn-outline-secondary:hover {
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
}
#radio-workstation-root .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush