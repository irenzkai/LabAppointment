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

{{-- FIXED: Changed container-fluid to container to restore alignment with the header layout --}}
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
    <form id="medForm" action="{{ $isReadonly ? route('workstation.verify', [$appointment->id, 'med_cert']) : route('workstation.medical.save', $appointment->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="clear_scan" id="clear_scan_field" value="0">
        <div class="row g-4">

            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES --}}
            <div class="col-md-4" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Clinical Metadata</h6>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Cert No.</label>
                        <input type="text" name="cert_no" id="cert_no_field" class="form-control" value="{{ $res->med_cert_data['metadata']['cert_no'] ?? '' }}" placeholder="Enter Certificate ID" required>
                    </div>

                    {{-- FIXED: Hides only the optional metadata snap fields on Scan Mode, keeping Cert No visible --}}
                    <div id="sidebar-manual-fields" class="{{ $hasScan ? 'd-none' : '' }}">
                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Patient Name</label>
                            <input type="text" name="med_cert_data[metadata][name]" class="form-control" value="{{ $res->med_cert_data['metadata']['name'] ?? strtoupper($appointment->patient_name) }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <div class="mb-3">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Address</label>
                            <input type="text" name="med_cert_data[metadata][address]" class="form-control" value="{{ $res->med_cert_data['metadata']['address'] ?? $appointment->patient_address }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Age</label>
                                <input type="number" name="med_cert_data[metadata][age]" class="form-control" value="{{ $res->med_cert_data['metadata']['age'] ?? $appointment->patient_age }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            </div>
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                                <input type="text" name="med_cert_data[metadata][sex]" class="form-control" value="{{ $res->med_cert_data['metadata']['sex'] ?? strtoupper($appointment->patient_sex) }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase">Date Tested</label>
                            <input type="date" name="med_cert_data[metadata][tested_date]" class="form-control" value="{{ isset($res->med_cert_data['metadata']['tested_date']) ? \Carbon\Carbon::parse($res->med_cert_data['metadata']['tested_date'])->format('Y-m-d') : $testedDate }}" {{ $hasScan ? 'disabled' : 'required' }}>
                        </div>

                        <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Clinical Signatory</h6>
                        <div class="mb-0 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <input type="text" name="med_cert_data[sig][name]" class="form-control mb-1" placeholder="Physician Name" value="{{ $res->med_cert_data['sig']['name'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
                            <input type="text" name="med_cert_data[sig][lic]" class="form-control" placeholder="License / PRC No." value="{{ $res->med_cert_data['sig']['lic'] ?? '' }}" {{ $hasScan ? 'disabled' : 'required' }}>
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
                                <input type="file" name="med_cert_scan" id="med_scan_input" class="form-control" onchange="toggleScanPriority(this)">
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
                            <textarea name="med_cert_data[findings]" id="findings_field" class="form-control p-3" rows="10" placeholder="Type findings..." {{ $hasScan ? 'disabled' : 'required' }}>{{ $res->med_cert_data['findings'] ?? '' }}</textarea>
                        </div>

                        <div>
                            <label class="text-secondary smaller fw-bold mb-1 uppercase">Remarks</label>
                            <textarea name="med_cert_data[remarks]" class="form-control p-3" rows="4" placeholder="Additional notes..." {{ $hasScan ? 'disabled' : 'required' }}>{{ $res->med_cert_data['remarks'] ?? '' }}</textarea>
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
                            <button type="button" class="btn btn-sm btn-dark fw-bold px-3" onclick="removeScan()">REMOVE & RESTORE SIDEBAR</button>
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

{{-- FIXED: Modals, overlays, and script blocks moved strictly outside of @section('content') to resolve section ending buffer anomalies --}}

<!-- VERIFY MODAL -->
<div class="modal fade" id="verifyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.verify', [$appointment->id, 'med_cert']) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-neon fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Enter your name to sign-off and approve this medical certificate.</p>
            <div class="mb-4">
                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Verifier Name</label>
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
                <label class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
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

@push('scripts')
<script>
let currentScale = 1;
let translateX = 0;
let translateY = 0;
let isDragging = false;
let startX, startY;

// Setup and teardown required status on all manual input elements depending on current layout mode
function setManualFieldsRequired(required) {
    const fields = [
        document.getElementById('cert_no_field'), // Standalone cert_no (Always Required)
        document.querySelector('input[name="med_cert_data[metadata][date]"]'),
        document.querySelector('input[name="med_cert_data[metadata][name]"]'),
        document.querySelector('input[name="med_cert_data[metadata][address]"]'),
        document.querySelector('input[name="med_cert_data[metadata][age]"]'),
        document.querySelector('input[name="med_cert_data[metadata][sex]"]'),
        document.querySelector('input[name="med_cert_data[metadata][tested_date]"]'),
        document.querySelector('input[name="med_cert_data[sig][name]"]'),
        document.querySelector('input[name="med_cert_data[sig][lic]"]'),
        document.getElementById('findings_field'),
        document.querySelector('textarea[name="med_cert_data[remarks]"]')
    ];

    fields.forEach(field => {
        if (field) {
            // cert_no is always required and enabled!
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

            // FIXED: Hide only the optional manual fields inside the sidebar, keep Cert No visible [358]
            const sidebarContainer = document.getElementById('sidebar-manual-fields');
            if (sidebarContainer) sidebarContainer.classList.add('d-none');

            setManualFieldsRequired(false);

            // Only hide the optional report scan box
            if (document.getElementById('report-scan-upload-box')) {
                document.getElementById('report-scan-upload-box').classList.add('d-none');
            }
            document.getElementById('scan-preview-zone').classList.remove('d-none');
            
            // FIXED: Maintains side-by-side grid structure (prevents wrap-around shifts)
            document.getElementById('main-panel-container').className = 'col-md-8';
        };
        reader.readAsDataURL(file);
    }
}
window.toggleScanPriority = toggleScanPriority;

// FIXED: Defensively updated script references with robust element existence checks [377]
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

    // FIXED: Restores optional manual entry fields nested within the sidebar completely
    const sidebarContainer = document.getElementById('sidebar-manual-fields');
    if (sidebarContainer) sidebarContainer.classList.remove('d-none');

    setManualFieldsRequired(true);

    // Restore the optional report scan upload box
    if (document.getElementById('report-scan-upload-box')) {
        document.getElementById('report-scan-upload-box').classList.remove('d-none');
    }
    document.getElementById('scan-preview-zone').classList.add('d-none');
    
    // FIXED: Maintains side-by-side grid structure (prevents wrap-around shifts)
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
        const manualPanel = document.getElementById('manual-panel');
        if (manualPanel) manualPanel.classList.add('d-none');

        // FIXED: Hide only the optional manual fields inside the sidebar on page load [340]
        const sidebarContainer = document.getElementById('sidebar-manual-fields');
        if (sidebarContainer) sidebarContainer.classList.add('d-none');

        setManualFieldsRequired(false);
        
        // FIXED: Maintains side-by-side grid structure (prevents wrap-around shifts)
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
    const formEl = document.getElementById('medReturnForm');

    if (selectEl && textareaEl && textareaWrapper && formEl) {
        // FIXED: Dynamically toggle form submission names to ensure standard validation always succeeds [352]
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
});
</script>
@endpush

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
    border-radius: inherit;
}
.image-preview-wrapper:hover .zoom-overlay {
    opacity: 1;
}
</style>