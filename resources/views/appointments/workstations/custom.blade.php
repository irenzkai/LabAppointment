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

    // Requested Examinations Breakdown
    $requestedExaminations = $appointment->packages->pluck('name')->map(fn($n) => "[PKG] {$n}")
        ->concat($appointment->services->pluck('name'))
        ->implode(', ');

    // Workstation Audit Trail (Encoder & Verifier)
    $customAudit = $res ? $res->audits->where('workstation_type', "custom_{$customRes->id}")->first() : null;
    $customEncoderName = $customAudit->v1_by_name ?? '---';
    $customVerifierName = $customAudit->v2_by_name ?? '---';
    $customEncodedAt = $customAudit && $customAudit->v1_at ? \Carbon\Carbon::parse($customAudit->v1_at) : null;
    $customVerifiedAt = $customAudit && $customAudit->v2_at ? \Carbon\Carbon::parse($customAudit->v2_at) : null;

    // Pre-load registered clinical personnel for 1-click verifier selection
    $allVerifiers = \App\Models\User::whereIn('role', ['admin', 'lab_tech', 'staff'])
        ->where('is_active', true)
        ->where(function($q) {
            $q->whereIn('role', ['admin', 'lab_tech'])
              ->orWhereNotNull('license_number');
        })
        ->orderBy('last_name')
        ->get();
@endphp

<div class="container text-start animate-page pt-4 px-3 px-sm-4" id="custom-workstation-root">

    {{-- 1. HEADER SECTION --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase fs-3 fs-md-2">
                @if($isVerified) REVIEW MODE @elseif($isReadonly) VERIFICATION MODE @else {{ strtoupper($customRes->name) }} WORKSTATION @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase text-break">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ strtoupper($appointment->patient_name) }}</span> | Ref: <span class="text-accent">#{{ $appointment->id }}</span></p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center w-100 w-md-auto justify-content-start justify-content-md-end">
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-3 px-sm-4 py-2 fw-bold text-uppercase flex-grow-1 flex-md-grow-0 text-center" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>

            @if(!$isReadonly)
                <button type="submit" form="customForm" class="btn-custom btn-accent px-4 px-sm-5 py-2 shadow-lg flex-grow-1 flex-md-grow-0 text-center">SAVE & SEND TO HUB</button>
            @else
                @if($status !== 'verified' || Auth::user()->isLabTech())
                    <button type="button" data-bs-toggle="modal" data-bs-target="#returnModal" class="btn-custom btn-outline-danger px-3 px-sm-4 py-2 flex-grow-1 flex-md-grow-0 text-center">RETURN FOR RE-EDIT</button>
                @endif

                @if($status == 'encoded')
                    @can('isLabTech')
                        <button type="button" data-bs-toggle="modal" data-bs-target="#verifyModal" class="btn-custom btn-accent px-4 px-sm-5 py-2 shadow-lg flex-grow-1 flex-md-grow-0 text-center">VERIFY & APPROVE</button>
                    @endcan
                @endif
            @endif
        </div>
    </div>

    {{-- 2. CORRECTION ALERT --}}
    @if($customRes->return_reason && $status != 'verified')
        <div class="alert-clinical p-3 mb-4 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-start mb-1 gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-2 text-danger flex-shrink-0"></i>
                <div class="text-break">
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

            {{-- SIDEBAR: METADATA, AUDIT & PATIENT PROFILE --}}
            <div class="col-12 col-lg-4" id="sidebar-container">
                <div class="card p-3 p-sm-4 border-secondary bg-card mb-3 shadow-sm" id="sidebar-card" style="background-color: var(--bg-card); color: var(--text-main);">
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
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                            <small class="text-secondary smaller fw-bold uppercase">Patient Profile</small>
                            @if(!$isReadonly && auth()->user()->isEmployee())
                                <a href="{{ route('appointments.edit-details', $appointment->id) }}?from=custom&custom_id={{ $customRes->id }}" class="btn btn-sm btn-outline-accent py-0.5 px-2 smaller uppercase fw-bold" style="font-size: 0.68rem;" title="Edit Patient Details">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Details
                                </a>
                            @endif
                        </div>
                        <div class="text-main fw-bold small mb-1 text-break">{{ strtoupper($appointment->patient_name) }}</div>
                        <div class="text-secondary smaller mb-1">{{ $appointment->patient_age }} YRS / {{ strtoupper($appointment->patient_sex) }}</div>
                        <div class="text-muted smaller text-break mb-2" style="font-size: 0.72rem; line-height: 1.35;">{{ $appointment->patient_address }}</div>

                        {{-- Requested Examinations Summary --}}
                        <div class="pt-2 border-top border-secondary border-opacity-10">
                            <small class="text-secondary d-block smaller fw-bold uppercase">Requested Examinations</small>
                            <span class="small fw-semibold text-accent text-break" style="font-size: 0.75rem;">
                                {{ $requestedExaminations ?: 'None registered' }}
                            </span>
                        </div>
                    </div>

                    {{-- System Audit Trail (Theme-Aware Container) --}}
                    <div class="p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(0, 0, 0, 0.02);">
                        <div class="row g-2">
                            <div class="col-6 pe-2">
                                <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Encoder</label>
                                <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;" title="{{ $customEncoderName }}">{{ $customEncoderName }}</span>
                                @if($customEncodedAt)
                                    <span class="text-secondary smaller italic d-block text-truncate" style="font-size: 0.65rem;">
                                        {{ $customEncodedAt->format('M d, Y | h:i A') }}
                                    </span>
                                @endif
                            </div>
                            <div class="col-6 ps-2 border-start border-secondary border-opacity-25">
                                <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Verifier</label>
                                <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;" title="{{ $customVerifierName }}">{{ $customVerifierName }}</span>
                                @if($customVerifiedAt)
                                    <span class="text-secondary smaller italic d-block text-truncate" style="font-size: 0.65rem;">
                                        {{ $customVerifiedAt->format('M d, Y | h:i A') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- MAIN WORKSTATION PANEL --}}
            <div class="col-12 col-lg-8" id="main-panel-container">

                {{-- Prominent Scan Dropzone --}}
                @if(!$isReadonly)
                    <div class="card p-4 p-sm-5 text-center shadow-lg mb-4 {{ $hasScan ? 'd-none' : '' }}" id="upload-zone" style="background-color: var(--bg-card); border: 2px dashed rgba(25, 211, 140, 0.25) !important; border-radius: 12px; color: var(--text-main);">
                        <i class="bi bi-file-earmark-arrow-up-fill text-accent display-2 display-sm-1 mb-3"></i>
                        <h4 class="fw-bold uppercase fs-5 fs-sm-4" style="color: var(--text-main);">Upload {{ $customRes->name }} Scan</h4>
                        <p class="text-secondary mb-4 small">Attach the official PDF document or image scan for this worksheet.</p>

                        <div class="mx-auto w-100" style="max-width: 450px;">
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
                    <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 rounded-top">
                        <span class="small"><i class="bi bi-eye-fill me-2"></i>OFFICIAL {{ strtoupper($customRes->name) }} SCAN PREVIEW</span>
                        @if(!$isReadonly)
                            <button type="button" id="remove_scan_btn" class="btn btn-sm btn-dark fw-bold px-3 w-100 w-sm-auto" onclick="removeScan()">REMOVE & RESTORE FORM</button>
                        @endif
                    </div>
                    <div class="card border-warning border-top-0 rounded-0 rounded-bottom overflow-hidden shadow bg-card p-2 p-sm-3 text-center d-flex justify-content-center align-items-center" style="min-height: 500px;">

                        {{-- Image Preview Container --}}
                        <div id="imagePreviewContainer" class="position-relative d-inline-block image-preview-wrapper w-100 {{ $hasScan && $isImage ? '' : 'd-none' }}" style="cursor: zoom-in;" onclick="zoomQR('{{ $hasScan && $isImage ? Storage::url($scanPath) : '' }}')">
                            <img id="imagePreviewImg" src="{{ $hasScan && $isImage ? Storage::url($scanPath) : '' }}" class="img-fluid rounded shadow-sm w-100" style="max-height: 800px; object-fit: contain; border: 1px solid var(--border-color);">
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

<!-- VERIFY MODAL WITH PREDEFINED CLINICAL PERSONNEL SELECTION -->
<div class="modal fade" id="verifyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.custom.verify', [$appointment->id, $customRes->id]) }}" method="POST" class="modal-content shadow-lg p-3 p-sm-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-accent fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Select or confirm your name to sign-off and approve this {{ $customRes->name }} worksheet.</p>

            <div class="mb-4">
                <label class="smaller fw-bold uppercase mb-1.5 d-block" style="color: var(--text-muted);">Choose Verifier from Clinical Registry</label>
                
                {{-- Search & Select Dropdown --}}
                <div class="position-relative mb-2 signatory-search-wrapper" id="sig_verify_wrapper">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-secondary bg-opacity-10 text-muted border-secondary border-opacity-25">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" 
                               id="sig_verify_search" 
                               class="form-control form-control-sm signatory-search-input" 
                               placeholder="Search or choose verifier..." 
                               autocomplete="off"
                               onfocus="openSignatoryMenu('verify')" 
                               oninput="filterSignatoryMenu('verify', this.value)">
                        <button type="button" 
                                class="btn btn-outline-secondary btn-sm signatory-toggle-btn" 
                                onclick="toggleSignatoryMenu('verify')" 
                                tabindex="-1">
                            <i class="bi bi-chevron-down" style="font-size: 0.7rem;"></i>
                        </button>
                    </div>

                    <div class="signatory-dropdown-list shadow-lg bg-card border rounded-3 p-1 d-none position-absolute w-100 mt-1" 
                         id="sig_verify_menu" 
                         style="z-index: 1060; max-height: 200px; overflow-y: auto; background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        @foreach($allVerifiers as $ver)
                            <div class="signatory-item p-2 rounded cursor-pointer transition-all d-flex justify-content-between align-items-center" 
                                 data-name="{{ $ver->name }}" 
                                 data-lic="{{ $ver->license_number ?: ($ver->occupation ?: '') }}" 
                                 onclick="selectVerifier('{{ addslashes($ver->name) }}')">
                                <div>
                                    <span class="fw-bold small d-block text-main">{{ $ver->name }}</span>
                                    <small class="text-muted" style="font-size: 0.68rem;">{{ $ver->occupation ?: ($ver->isAdmin() ? 'Administrator' : 'Lab Technologist') }}</small>
                                </div>
                                @if($ver->license_number)
                                    <span class="badge bg-secondary bg-opacity-10 text-accent font-monospace" style="font-size: 0.65rem;">
                                        PRC #{{ $ver->license_number }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                        <div class="signatory-empty-msg text-muted small p-2 text-center d-none" id="sig_verify_empty">
                            No matching personnel found
                        </div>
                    </div>
                </div>

                <label class="smaller fw-bold uppercase mb-1 d-block" style="color: var(--text-muted);">Verifier Full Name</label>
                <input type="text" name="sig_name" id="sig_name_field" class="form-control" value="{{ auth()->user()->name }}" required>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2.5" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-accent flex-grow-1 py-2.5 fw-bold uppercase">Approve & Sign</button>
            </div>
        </form>
    </div>
</div>

<!-- RETURN MODAL -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.custom.return', [$appointment->id, $customRes->id]) }}" method="POST" id="customReturnForm" class="modal-content shadow-lg p-3 p-sm-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this {{ $customRes->name }} worksheet for corrections.</p>
            <div class="mb-3">
                <label for="return_reason_select" class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" name="reason" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
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
            <div class="d-flex flex-column flex-sm-row gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2.5" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-danger flex-grow-1 py-2.5 fw-bold uppercase">Confirm Return</button>
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

// Searchable Signatory Dropdown Handlers
window.openSignatoryMenu = function(key) {
    document.querySelectorAll('.signatory-dropdown-list').forEach(el => {
        if (el.id !== `sig_${key}_menu`) el.classList.add('d-none');
    });
    const menu = document.getElementById(`sig_${key}_menu`);
    if (menu) menu.classList.remove('d-none');
};

window.toggleSignatoryMenu = function(key) {
    const menu = document.getElementById(`sig_${key}_menu`);
    if (menu) {
        menu.classList.toggle('d-none');
        if (!menu.classList.contains('d-none')) {
            document.getElementById(`sig_${key}_search`)?.focus();
        }
    }
};

window.filterSignatoryMenu = function(key, query) {
    const q = (query || '').toLowerCase().trim();
    const menu = document.getElementById(`sig_${key}_menu`);
    if (!menu) return;
    menu.classList.remove('d-none');

    let matchCount = 0;
    menu.querySelectorAll('.signatory-item').forEach(item => {
        const name = (item.dataset.name || '').toLowerCase();
        const lic = (item.dataset.lic || '').toLowerCase();
        const match = name.includes(q) || lic.includes(q);
        item.classList.toggle('d-none', !match);
        if (match) matchCount++;
    });

    const emptyMsg = document.getElementById(`sig_${key}_empty`);
    if (emptyMsg) {
        emptyMsg.classList.toggle('d-none', matchCount > 0);
    }
};

window.selectVerifier = function(name) {
    const searchInput = document.getElementById('sig_verify_search');
    const nameInput = document.getElementById('sig_name_field');

    if (searchInput) searchInput.value = name;
    if (nameInput) {
        nameInput.value = name;
        nameInput.classList.remove('is-invalid');
    }

    const menu = document.getElementById('sig_verify_menu');
    if (menu) menu.classList.add('d-none');
};

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.signatory-search-wrapper')) {
        document.querySelectorAll('.signatory-dropdown-list').forEach(el => el.classList.add('d-none'));
    }
});

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
    document.getElementById('scan-preview-zone')?.classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', () => {
    // Pre-populate verifier search placeholder with auth user name
    const currentUserName = "{{ auth()->user()->name }}";
    const verifierSearch = document.getElementById('sig_verify_search');
    if (verifierSearch && !verifierSearch.value) {
        verifierSearch.value = currentUserName;
    }

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
                textareaEl.focus();
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
                activeInput.focus();
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
.signatory-item {
    cursor: pointer;
}
.signatory-item:hover {
    background-color: rgba(25, 211, 140, 0.08) !important;
}
@media (max-width: 767.98px) {
    #scanViewer {
        min-height: 480px !important;
    }
}
</style>
@endpush