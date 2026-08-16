@extends('layouts.app')

@section('title', 'Encode Results')

@section('content')
@php
    $res = $appointment->result;
    $status = $customRes->status ?? 'pending';

    // UI Logic States
    $isVerified = ($status === 'verified');
    $isReadonly = in_array($status, ['encoded', 'verified']);
    $hasScan = !empty($customRes->scan_path);

    $scanPath = $customRes->scan_path ?? null;
    $isImage = false;
    if ($scanPath) {
        $ext = strtolower(pathinfo($scanPath, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']);
    }

    // Dynamically reveal preview on page load if scan exists
    $showPreview = $isReadonly || $hasScan;
@endphp

<div class="container text-start animate-page pt-4" id="custom-workstation-root">

    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase">
                @if($isVerified) REVIEW MODE @elseif($isReadonly) VERIFICATION MODE @else {{ strtoupper($customRes->name) }} WORKSTATION @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ strtoupper($appointment->patient_name) }}</span> | Ref: <span class="text-accent">#{{ $appointment->id }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>

            @if(!$isReadonly)
                <button type="submit" form="customForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
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
    @if($customRes->return_reason && $status != 'verified')
        <div class="alert-clinical p-3 mb-4 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div>
                    <div class="fw-bold small uppercase">Verifier Correction Request:</div>
                    <div class="small italic">"{{ $customRes->return_reason }}"</div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. CORE SAVE FORM --}}
    <form id="customForm" action="{{ $isReadonly ? route('workstation.custom.verify', [$appointment->id, $customRes->id]) : route('workstation.custom.save', [$appointment->id, $customRes->id]) }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="clear_scan" id="clear_scan_field" value="0">
        <div class="row g-4">

            {{-- SIDEBAR: METADATA & PATIENT PROFILE --}}
            <div class="col-md-4" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" id="sidebar-card" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">{{ $customRes->name }} Metadata</h6>

                    {{-- Cert No. / Reference No. (Always Required & Always Visible) --}}
                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Certificate / Tracking No.</label>
                        <input type="text" name="cert_no" id="cert_no_field" class="form-control @error('cert_no') is-invalid @enderror" value="{{ old('cert_no', $customRes->cert_no ?? '') }}" placeholder="Enter Certificate or Tracking No." {{ $isReadonly ? 'readonly' : 'required' }}>
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
                                <a href="{{ route('appointments.edit-details', $appointment->id) }}?from=custom" class="btn btn-sm btn-outline-accent py-0.5 px-2 smaller uppercase fw-bold" style="font-size: 0.68rem;" title="Edit Patient Details">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Details
                                </a>
                            @endif
                        </div>
                        <div class="text-main fw-bold small mb-1">{{ strtoupper($appointment->patient_name) }}</div>
                        <div class="text-secondary smaller mb-1">{{ $appointment->patient_age }} YRS / {{ strtoupper($appointment->patient_sex) }}</div>
                        <div class="text-muted smaller text-break" style="font-size: 0.72rem; line-height: 1.35;">{{ $appointment->patient_address }}</div>
                    </div>
                </div>
            </div>

            {{-- MAIN WORKSTATION PANEL --}}
            <div class="col-md-8" id="main-panel-container">

                {{-- Prominent Scan Dropzone --}}
                @if(!$isReadonly)
                    <div class="card p-5 text-center shadow-lg mb-4 {{ $hasScan ? 'd-none' : '' }}" id="upload-zone" style="background-color: var(--bg-card); border: 2px dashed rgba(25, 211, 140, 0.25) !important; border-radius: 12px; color: var(--text-main);">
                        <i class="bi bi-file-earmark-arrow-up-fill text-accent display-1 mb-3"></i>
                        <h4 class="fw-bold uppercase" style="color: var(--text-main);">Upload {{ $customRes->name }} Scan</h4>
                        <p class="text-secondary mb-4 small">Attach the official PDF document or image scan for this worksheet.</p>

                        <div class="mx-auto" style="max-width: 450px;">
                            <input type="file" name="scan_file" id="custom_scan_input" class="form-control form-control-lg @error('scan_file') is-invalid @enderror" onchange="previewScan(this)" {{ !$hasScan ? 'required' : '' }}>
                            <div class="invalid-feedback d-none" id="err_scan_file"></div>
                            @error('scan_file')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif

                {{-- SCAN PREVIEW PANEL --}}
                <div id="scan-preview-zone" class="{{ $showPreview ? '' : 'd-none' }} shadow-lg mb-4">
                    <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                        <span><i class="bi bi-eye-fill me-2"></i>OFFICIAL {{ strtoupper($customRes->name) }} SCAN PREVIEW</span>
                        @if(!$isReadonly)
                            <button type="button" id="remove_scan_btn" class="btn btn-sm btn-dark fw-bold px-3" onclick="removeScan()">REMOVE & RESTORE FORM</button>
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

                        {{-- PDF / Document Preview Container --}}
                        <div id="pdfPreviewContainer" class="position-relative w-100 h-100 {{ $hasScan && !$isImage ? '' : 'd-none' }}">
                            <iframe id="scanViewer" src="{{ $hasScan && !$isImage ? Storage::url($scanPath) : '' }}" class="w-100 h-100 rounded-bottom border border-warning bg-card" style="min-height: 800px; border: none;"></iframe>
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
        <form action="{{ route('workstation.custom.verify', [$appointment->id, $customRes->id]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-neon fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Enter your name to sign-off and approve this {{ $customRes->name }} worksheet.</p>
            <div class="mb-4">
                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Verifier Full Name</label>
                <input type="text" name="sig_name" class="form-control" value="{{ auth()->user()->name }}" required>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-neon flex-grow-1 fw-bold uppercase">Approve & Sign</button>
            </div>
        </form>
    </div>
</div>

<!-- RETURN MODAL -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.custom.return', [$appointment->id, $customRes->id]) }}" method="POST" id="customReturnForm" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this {{ $customRes->name }} worksheet for corrections.</p>
            <div class="mb-3">
                <label for="return_reason_select" class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return justification --</option>
                    <option value="Mismatched patient identification or demographic fields">Mismatched patient identification or demographic fields</option>
                    <option value="Unclear, low-quality, or blurry document scan">Unclear, low-quality, or blurry document scan</option>
                    <option value="Incorrect reference value / Certificate No.">Incorrect reference value / Certificate No.</option>
                    <option value="Discrepancies in clinical signature or credentials">Discrepancies in clinical signature or credentials</option>
                    <option value="Others">Others (Specify details below)</option>
                </select>
            </div>
            <div id="custom_return_reason_wrapper" class="mb-3 d-none">
                <label for="reason_textarea" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                <textarea id="reason_textarea" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="4" placeholder="Identify the specific correction needed..."></textarea>
                <div class="mt-2"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
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
        window.openFilePreview(fileSrc, '{{ $customRes->name }} Scan Preview');
    } else if (typeof window.zoomFile === 'function') {
        window.zoomFile(fileSrc);
    }
};

function previewScan(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const isImgFile = file.type.startsWith('image/');
        const reader = new FileReader();

        reader.onload = e => {
            const imgContainer = document.getElementById('imagePreviewContainer');
            const imgElement = document.getElementById('imagePreviewImg');
            const pdfContainer = document.getElementById('pdfPreviewContainer');
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

            document.getElementById('upload-zone')?.classList.add('d-none');
            document.getElementById('scan-preview-zone').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}

function removeScan() {
    const input = document.getElementById('custom_scan_input');
    if (input) {
        input.value = "";
        input.setAttribute('required', 'required');
    }

    const clearField = document.getElementById('clear_scan_field');
    if (clearField) clearField.value = "1";

    const imgContainer = document.getElementById('imagePreviewContainer');
    if (imgContainer) {
        imgContainer.classList.add('d-none');
        const imgElement = document.getElementById('imagePreviewImg');
        if (imgElement) imgElement.src = "";
        imgContainer.removeAttribute('onclick');
    }

    const pdfContainer = document.getElementById('pdfPreviewContainer');
    if (pdfContainer) {
        pdfContainer.classList.add('d-none');
        const viewer = document.getElementById('scanViewer');
        if (viewer) viewer.src = "";
    }

    document.getElementById('upload-zone')?.classList.remove('d-none');
    document.getElementById('scan-preview-zone').classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', () => {
    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('customReturnForm');

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
    const mainForm = document.getElementById('customForm');
    if (mainForm) {
        mainForm.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalid = null;

            // Flush previous error states
            document.querySelectorAll('#customForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('#customForm .invalid-feedback').forEach(el => {
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

            // 1. Certificate / Tracking No. Validation
            const certNo = document.getElementById('cert_no_field');
            if (certNo && !certNo.value.trim()) {
                markInvalid(certNo, 'err_cert_no', 'Certificate / Tracking Number is required.');
            }

            // 2. Scan File Validation (Required if no scan is currently attached)
            const scanInput = document.getElementById('custom_scan_input');
            const hasExistingScan = "{{ $hasScan ? '1' : '0' }}" === "1";
            if (!hasExistingScan && scanInput && (!scanInput.files || scanInput.files.length === 0)) {
                markInvalid(scanInput, 'err_scan_file', 'A completed custom worksheet scan file is required.');
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
    document.querySelectorAll('#customForm input, #customForm select, #customForm textarea').forEach(input => {
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
#custom-workstation-root .form-control,
#custom-workstation-root .form-select,
#custom-workstation-root .input-group-text {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}
#custom-workstation-root .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush