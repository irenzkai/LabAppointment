@extends('layouts.app')

@section('title', 'Encode Results')

@section('content')
@php
    $res = $appointment->result;
    $status = $res->med_status ?? 'pending';

    // UI Logic States
    $isVerified = ($status === 'verified');
    $isReadonly = in_array($status, ['encoded', 'verified']);
    $hasScan = ($res && $res->med_cert_scan);
    $today = date('Y-m-d');
    $testedDate = $appointment->tested_at ? $appointment->tested_at->format('Y-m-d') : $appointment->appointment_date->format('Y-m-d');

    $scanPath = $res->med_cert_scan ?? null;
    $isImage = false;
    if ($scanPath) {
        $ext = strtolower(pathinfo($scanPath, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']);
    }

    // Dynamically reveal preview on page load if scan exists
    $showPreview = $isReadonly || $hasScan;
@endphp

<div class="container text-start animate-page pt-4" id="medical-workstation-root">

    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase">
                @if($isVerified)
                    REVIEW MODE
                @elseif($isReadonly)
                    VERIFICATION MODE
                @else
                    MEDICAL CERTIFICATE
                @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ $appointment->patient_name }}</span> | Ref: <span class="text-accent">#{{ $appointment->id }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>

            @if(!$isReadonly)
                <button type="submit" form="medForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
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
    @if($res && $res->med_return_reason && $status != 'verified')
        <div class="alert-clinical p-3 mb-4 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div>
                    <div class="fw-bold small uppercase">Verifier Correction Request:</div>
                    <div class="small italic">"{{ $res->med_return_reason }}"</div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. CORE SAVE FORM --}}
    <form id="medForm" action="{{ $isReadonly ? route('workstation.verify', [$appointment->id, 'med_cert']) : route('workstation.medical.save', $appointment->id) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="clear_scan" id="clear_scan_field" value="0">
        <div class="row g-4">

            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES --}}
            <div class="col-md-4" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Clinical Metadata</h6>

                    {{-- Cert No. (Always Required & Always Visible) --}}
                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Cert No. / Reference No.</label>
                        <input type="text" name="cert_no" id="cert_no_field" class="form-control @error('cert_no') is-invalid @enderror" value="{{ old('cert_no', $res->med_cert_data['metadata']['cert_no'] ?? ($res->medCert?->cert_no ?? '')) }}" placeholder="Enter Certificate ID" {{ $isReadonly ? 'readonly' : 'required' }}>
                        <div class="invalid-feedback d-none" id="err_cert_no"></div>
                        @error('cert_no')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- READ-ONLY PATIENT PROFILE SNAPSHOT CARD WITH EDIT LINK --}}
                    <div class="mb-3 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(0,0,0,0.02);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-secondary smaller fw-bold uppercase">Patient Profile</small>
                            @if(!$isReadonly && auth()->user()->isEmployee())
                                <a href="{{ route('appointments.edit-details', $appointment->id) }}?from=med_cert" class="btn btn-sm btn-outline-accent py-0.5 px-2 smaller uppercase fw-bold" style="font-size: 0.68rem;" title="Edit Patient Details">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Details
                                </a>
                            @endif
                        </div>
                        <div class="text-main fw-bold small mb-1">{{ strtoupper($appointment->patient_name) }}</div>
                        <div class="text-secondary smaller mb-1">{{ $appointment->patient_age }} YRS / {{ strtoupper($appointment->patient_sex) }}</div>
                        <div class="text-muted smaller text-break" style="font-size: 0.72rem; line-height: 1.35;">{{ $appointment->patient_address }}</div>

                        {{-- Hidden snapshot payloads for backend backwards-compatibility --}}
                        <input type="hidden" name="med_cert_data[metadata][name]" value="{{ strtoupper($appointment->patient_name) }}">
                        <input type="hidden" name="med_cert_data[metadata][address]" value="{{ $appointment->patient_address }}">
                        <input type="hidden" name="med_cert_data[metadata][age]" value="{{ $appointment->patient_age }}">
                        <input type="hidden" name="med_cert_data[metadata][sex]" value="{{ strtoupper($appointment->patient_sex) }}">
                    </div>

                    {{-- OPTIONAL MANUAL METADATA & SIGNATORIES (Hidden when file/scan is inputted) --}}
                    <div id="sidebar-manual-fields" class="{{ $hasScan ? 'd-none' : '' }}">
                        <div class="mb-4">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Date Tested</label>
                            <input type="date" name="med_cert_data[metadata][tested_date]" id="med_tested_date" class="form-control @error('med_cert_data.metadata.tested_date') is-invalid @enderror" value="{{ old('med_cert_data.metadata.tested_date', isset($res->med_cert_data['metadata']['tested_date']) ? \Carbon\Carbon::parse($res->med_cert_data['metadata']['tested_date'])->format('Y-m-d') : $testedDate) }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_med_tested_date"></div>
                            @error('med_cert_data.metadata.tested_date')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Clinical Signatory --}}
                        <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Clinical Signatory</h6>
                        <div class="mb-0 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">Physician Name</label>
                            <input type="text" name="med_cert_data[sig][name]" id="sig_med_name" class="form-control mb-1 @error('med_cert_data.sig.name') is-invalid @enderror" placeholder="Physician Name" value="{{ old('med_cert_data.sig.name', $res->med_cert_data['sig']['name'] ?? ($res->medCert?->physician_name ?? '')) }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none mb-1" id="err_sig_med_name"></div>
                            @error('med_cert_data.sig.name')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror

                            <label class="text-secondary smaller fw-bold mb-1 d-block" style="font-size: 0.65rem;">License / PRC No.</label>
                            <input type="text" name="med_cert_data[sig][lic]" id="sig_med_lic" class="form-control @error('med_cert_data.sig.lic') is-invalid @enderror" placeholder="License / PRC No." value="{{ old('med_cert_data.sig.lic', $res->med_cert_data['sig']['lic'] ?? ($res->medCert?->physician_license ?? '')) }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <div class="invalid-feedback d-none" id="err_sig_med_lic"></div>
                            @error('med_cert_data.sig.lic')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
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
                            <h6 class="text-warning fw-bold mb-2 uppercase"><i class="bi bi-file-earmark-arrow-up-fill me-2 fs-5"></i>Attach Completed Certificate Scan (Recommended)</h6>
                            <p class="text-secondary small mb-3">Uploading a scanned report takes absolute clinical priority and hides the manual inputs below.</p>
                            <div class="mx-auto" style="max-width: 450px;">
                                <input type="file" name="med_cert_scan" id="med_scan_input" class="form-control @error('med_cert_scan') is-invalid @enderror" onchange="toggleScanPriority(this)">
                                <div class="invalid-feedback d-none" id="err_med_cert_scan"></div>
                                @error('med_cert_scan')
                                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- MANUAL FINDINGS --}}
                @if(!$isReadonly)
                    <div id="manual-panel" class="card p-4 border-secondary bg-card min-vh-75 shadow-lg {{ $hasScan ? 'd-none' : '' }}">
                        <h6 class="text-main border-bottom border-secondary border-opacity-25 pb-2 mb-4 uppercase small fw-bold">Manual Content Entry</h6>

                        <div class="mb-4">
                            <label class="text-secondary smaller fw-bold mb-1 uppercase">Findings</label>
                            <textarea name="med_cert_data[findings]" id="findings_field" class="form-control p-3 @error('med_cert_data.findings') is-invalid @enderror" rows="10" placeholder="Type findings..." {{ $hasScan ? 'disabled' : 'required' }}>{{ old('med_cert_data.findings', $res->med_cert_data['findings'] ?? ($res->medCert?->findings ?? '')) }}</textarea>
                            <div class="invalid-feedback d-none" id="err_findings"></div>
                            @error('med_cert_data.findings')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="text-secondary smaller fw-bold mb-1 uppercase">Remarks</label>
                            <textarea name="med_cert_data[remarks]" id="remarks_field" class="form-control p-3 @error('med_cert_data.remarks') is-invalid @enderror" rows="4" placeholder="Additional notes..." {{ $hasScan ? 'disabled' : 'required' }}>{{ old('med_cert_data.remarks', $res->med_cert_data['remarks'] ?? ($res->medCert?->remarks ?? '')) }}</textarea>
                            <div class="invalid-feedback d-none" id="err_remarks"></div>
                            @error('med_cert_data.remarks')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif

                {{-- VERIFY AREA / SCAN PREVIEW --}}
                <div id="scan-preview-zone" class="{{ $showPreview ? '' : 'd-none' }} h-100 min-vh-75 shadow-lg">
                    <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                        <span>
                            @if($isReadonly)
                                @if($hasScan) PHYSICAL SCAN FILE @else GENERATED CLINICAL PDF PREVIEW @endif
                            @else
                                CLINICAL SCAN OVERRIDE ACTIVE
                            @endif
                        </span>
                        @if(!$isReadonly)
                            <button type="button" id="remove_scan_btn" class="btn btn-sm btn-dark fw-bold px-3" onclick="removeScan()">REMOVE & RESTORE SIDEBAR</button>
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
                                <iframe id="scanViewer" src="{{ route('appointments.result.access', [$appointment->id, 'med_cert', 'preview']) }}" class="w-100 h-100 rounded-bottom border border-warning bg-card" style="min-height: 800px; border: none;"></iframe>
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
        <form action="{{ route('workstation.verify', [$appointment->id, 'med_cert']) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-neon fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Enter your name to sign-off and approve this medical certificate.</p>
            <div class="mb-4">
                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Verifier Full Name</label>
                <input type="text" name="sig_name" class="form-control" value="{{ auth()->user()->name }}" required>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-neon flex-grow-1 fw-bold uppercase">Sign & Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- RETURN MODAL -->
<div class="modal fade" id="returnModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.return', $appointment->id) }}?type=med_cert" method="POST" id="medReturnForm" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase mb-1">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this certificate for corrections.</p>
            <div class="mb-3">
                <label for="return_reason_select" class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" name="reason" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return justification --</option>
                    <option value="Mismatched patient identification or demographic fields">Mismatched patient identification or demographic fields</option>
                    <option value="Incomplete findings or vague diagnostic interpretation">Incomplete findings or vague diagnostic interpretation</option>
                    <option value="Incorrect date of issue or tested date selection">Incorrect date of issue or tested date selection</option>
                    <option value="Discrepancies in clinical signature or credentials">Discrepancies in clinical signature or credentials</option>
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
        window.openFilePreview(fileSrc, 'Medical Certificate Scan Preview');
    } else if (typeof window.zoomFile === 'function') {
        window.zoomFile(fileSrc);
    }
};

// Setup and teardown required status on all manual input elements depending on current layout mode
function setManualFieldsRequired(required) {
    const fields = [
        document.getElementById('cert_no_field'), // Standalone cert_no (Always Required)
        document.getElementById('med_tested_date'),
        document.getElementById('sig_med_name'),
        document.getElementById('sig_med_lic'),
        document.getElementById('findings_field'),
        document.getElementById('remarks_field')
    ];

    fields.forEach(field => {
        if (field) {
            if (field.id === 'cert_no_field') {
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

            const manualPanel = document.getElementById('manual-panel');
            if (manualPanel) manualPanel.classList.add('d-none');

            // Hide only the optional manual fields inside the sidebar, keep Cert No visible
            const sidebarContainer = document.getElementById('sidebar-manual-fields');
            if (sidebarContainer) sidebarContainer.classList.add('d-none');

            setManualFieldsRequired(false);

            // Only hide the optional report scan box
            if (document.getElementById('report-scan-upload-box')) {
                document.getElementById('report-scan-upload-box').classList.add('d-none');
            }
            document.getElementById('scan-preview-zone').classList.remove('d-none');

            // Maintains side-by-side grid structure
            document.getElementById('main-panel-container').className = 'col-md-8';
        };
        reader.readAsDataURL(file);
    }
}
window.toggleScanPriority = toggleScanPriority;

function removeScan() {
    const scanInput = document.getElementById('med_scan_input');
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

    const manualPanel = document.getElementById('manual-panel');
    if (manualPanel) manualPanel.classList.remove('d-none');

    // Restores optional manual entry fields nested within the sidebar
    const sidebarContainer = document.getElementById('sidebar-manual-fields');
    if (sidebarContainer) sidebarContainer.classList.remove('d-none');

    setManualFieldsRequired(true);

    // Restore the optional report scan upload box
    if (document.getElementById('report-scan-upload-box')) {
        document.getElementById('report-scan-upload-box').classList.remove('d-none');
    }
    document.getElementById('scan-preview-zone').classList.add('d-none');

    document.getElementById('main-panel-container').className = 'col-md-8';
}
window.removeScan = removeScan;

function viewMedCertFullscreen() {
    const fileInput = document.getElementById('med_scan_input');
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
            zoomQR("{{ route('appointments.result.access', [$appointment->id, 'med_cert', 'preview']) }}");
        }
    }
}
window.viewMedCertFullscreen = viewMedCertFullscreen;

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup on page load
    if ("{{ $hasScan ? '1' : '0' }}" === "1") {
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
        const form = document.getElementById('medForm');
        form.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(el => el.disabled = true);
    }

    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('medReturnForm');

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
    const mainForm = document.getElementById('medForm');
    if (mainForm) {
        mainForm.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalid = null;

            // Flush previous error states
            document.querySelectorAll('#medForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('#medForm .invalid-feedback').forEach(el => {
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

            // 1. Cert No Validation (Always required)
            const certNo = document.getElementById('cert_no_field');
            if (certNo && !certNo.value.trim()) {
                markInvalid(certNo, 'err_cert_no', 'Certificate Number is required.');
            }

            // 2. Validate Manual Mode Fields
            if (isManualMode) {
                const testedDate = document.getElementById('med_tested_date');
                if (testedDate && !testedDate.value) markInvalid(testedDate, 'err_med_tested_date', 'Date Tested is required.');

                const sigName = document.getElementById('sig_med_name');
                if (sigName && !sigName.value.trim()) markInvalid(sigName, 'err_sig_med_name', 'Physician Name is required.');

                const sigLic = document.getElementById('sig_med_lic');
                if (sigLic && !sigLic.value.trim()) markInvalid(sigLic, 'err_sig_med_lic', 'Physician License / PRC No. is required.');

                const findings = document.getElementById('findings_field');
                if (findings && !findings.value.trim()) markInvalid(findings, 'err_findings', 'Findings are required.');

                const remarks = document.getElementById('remarks_field');
                if (remarks && !remarks.value.trim()) markInvalid(remarks, 'err_remarks', 'Remarks are required.');
            } else {
                // Scan mode: scan file must be present
                const scanInput = document.getElementById('med_scan_input');
                const hasExistingScan = "{{ $hasScan ? '1' : '0' }}" === "1";
                if (!hasExistingScan && scanInput && (!scanInput.files || scanInput.files.length === 0)) {
                    markInvalid(scanInput, 'err_med_cert_scan', 'A completed medical certificate scan file is required.');
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
    document.querySelectorAll('#medForm input, #medForm select, #medForm textarea').forEach(input => {
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
#medical-workstation-root .form-control,
#medical-workstation-root .form-select,
#medical-workstation-root .input-group-text,
#medical-workstation-root .form-control:focus,
#medical-workstation-root .form-select:focus {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}
#medical-workstation-root .input-group-text {
    background-color: var(--border-color) !important;
}
#medical-workstation-root .modal-content .form-control {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border: 1.5px solid var(--border-color) !important;
}
#medical-workstation-root .btn-outline-secondary:hover {
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
#medical-workstation-root .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush