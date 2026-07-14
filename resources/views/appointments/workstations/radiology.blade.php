@extends('layouts.app')

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
@endphp

<div class="container-fluid text-start" id="radio-workstation-root">
    
    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">
                @if($isVerified) REVIEW MODE @elseif($isReadonly) VERIFICATION MODE @else RADIOLOGY WORKSTATION @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ strtoupper($appointment->patient_name) }}</span> | Status: <span class="text-accent">{{ strtoupper($status) }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            {{-- Styled outline button to avoid blue fallback links --}}
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>
            
            @if(!$isReadonly)
                <button type="submit" form="radioForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
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
    @if($res && $res->radio_return_reason && $status != 'verified')
        <div class="alert-clinical p-3 mb-4 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div>
                    <div class="fw-bold small uppercase">Correction Required:</div>
                    <div class="small italic">"{{ $res->radio_return_reason }}"</div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. CORE SAVE FORM --}}
    <form id="radioForm" action="{{ $isReadonly ? route('workstation.verify', [$appointment->id, 'radio']) : route('workstation.radiology.save', $appointment->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            
            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES --}}
            <div class="col-md-4 {{ $isReadonly && !$hasReportScan ? 'd-none' : ($hasReportScan ? 'd-none' : '') }}" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" id="sidebar-card" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Radiology Metadata</h6>
                    
                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Case #</label>
                        <input type="text" name="radio_data[metadata][case_no]" id="case_no_field" class="form-control" value="{{ $res->radio_data['metadata']['case_no'] ?? '' }}" placeholder="Enter Case ID" required>
                    </div>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Name</label>
                        <input type="text" name="radio_data[metadata][name]" class="form-control" value="{{ $res->radio_data['metadata']['name'] ?? strtoupper($appointment->patient_name) }}">
                    </div>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Address</label>
                        <input type="text" name="radio_data[metadata][address]" class="form-control" value="{{ $res->radio_data['metadata']['address'] ?? $appointment->patient_address }}">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Date</label>
                            <input type="date" name="radio_data[metadata][date]" class="form-control" value="{{ $res->radio_data['metadata']['date'] ?? date('Y-m-d') }}">
                        </div>
                        <div class="col-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Age/Sex</label>
                            <input type="text" name="radio_data[metadata][age_sex]" class="form-control" value="{{ $res->radio_data['metadata']['age_sex'] ?? ($appointment->patient_age.' / '.$appointment->patient_sex) }}">
                        </div>
                    </div>

                    <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Manual Signatory (PDF)</h6>
                    <div class="mb-4 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                        <input type="text" name="radio_data[sig][name]" class="form-control mb-1" placeholder="Radiologist Name" value="{{ $res->radio_data['sig']['name'] ?? '' }}">
                        <input type="text" name="radio_data[sig][lic]" class="form-control" placeholder="License / Position" value="{{ $res->radio_data['sig']['lic'] ?? '' }}">
                    </div>
                </div>
            </div>

            {{-- MAIN WORKSTATION PANEL --}}
            <div class="{{ $isReadonly ? 'col-md-12' : ($hasReportScan ? 'col-md-12' : 'col-md-8') }}" id="main-panel-container">
                
                {{-- Prominent Dual-Column Upload Control Center --}}
                @if(!$isReadonly)
                    <div class="row g-3 mb-4" id="upload-control-center">
                        {{-- 1. X-Ray Image Upload Zone (Required) --}}
                        <div class="col-md-6">
                            <div class="card p-3 h-100 text-center" style="background-color: rgba(220, 53, 69, 0.02); border: 2px dashed rgba(220, 53, 69, 0.25) !important; color: var(--text-main); border-radius: 12px;">
                                <h6 class="text-danger fw-bold mb-1 uppercase"><i class="bi bi-camera-fill me-2 fs-5"></i>Patient X-Ray Image (Required)</h6>
                                <p class="text-secondary small mb-3">Select the raw patient chest radiologic snapshot file.</p>
                                <input type="file" name="xray_image" id="xray_input" class="form-control form-control-sm" onchange="previewXray(this)" {{ !$hasXray ? 'required' : '' }}>
                                @if($hasXray) 
                                    <p class="text-success smaller mt-2 mb-0 fw-bold"><i class="bi bi-check-circle-fill me-1"></i> X-Ray File Cached on Server</p> 
                                @endif
                            </div>
                        </div>

                        {{-- 2. Optional Report PDF Scan Override --}}
                        <div class="col-md-6">
                            <div class="card p-3 h-100 text-center" style="background-color: rgba(255, 193, 7, 0.02); border: 2px dashed rgba(255, 193, 7, 0.25) !important; color: var(--text-main); border-radius: 12px;">
                                <h6 class="text-warning fw-bold mb-1 uppercase"><i class="bi bi-file-earmark-arrow-up-fill me-2 fs-5"></i>Report PDF Scan (Optional)</h6>
                                <p class="text-secondary small mb-3">Uploading a scanned report hides the manual text fields below.</p>
                                <input type="file" name="radio_scan" id="report_scan_input" class="form-control form-control-sm" onchange="toggleScanPriority(this)">
                                <input type="hidden" name="clear_scan" id="clear_scan_field" value="0">
                                @if($hasReportScan)
                                    <p class="text-warning smaller mt-2 mb-0 fw-bold"><i class="bi bi-eye-fill me-1"></i> PDF Scan Override Active</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- TOP: X-RAY IMAGE VIEWER --}}
                <div id="xray-viewer-card" class="card border-secondary bg-card mb-4 overflow-hidden shadow-lg {{ !$hasXray ? 'd-none' : '' }}">
                    <div class="bg-danger text-white p-2 px-3 fw-bold small uppercase d-flex justify-content-between">
                        <span><i class="bi bi-camera-fill me-2"></i>Patient X-Ray Image</span>
                    </div>
                    <div class="p-2 text-center" style="background-color: rgba(0,0,0,0.03) !important;">
                        <img id="xrayPreview" src="{{ $hasXray ? Storage::url($res->xray_image) : '#' }}" class="img-fluid rounded border border-secondary shadow" style="max-height: 450px; object-fit: contain;">
                    </div>
                </div>

                {{-- BOTTOM: REPORT CONTENT --}}
                <div id="report-workstation">
                    {{-- MANUAL FINDINGS (Hidden if scan/verify is active) --}}
                    @if(!$isReadonly)
                        <div id="manual-panel" class="card p-4 border-secondary bg-card shadow-lg {{ $hasReportScan ? 'd-none' : '' }}">
                            <h6 class="border-bottom border-secondary border-opacity-25 pb-2 mb-3 uppercase small fw-bold" style="color: var(--text-main);">Manual Findings Entry</h6>
                            
                            <div class="mb-4">
                                <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Findings</label>
                                <textarea name="radio_data[findings]" id="findings_field" class="form-control p-3" rows="10" placeholder="Type findings..." required>{{ $res->radio_data['findings'] ?? '' }}</textarea>
                            </div>
                            
                            <div>
                                <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Impression</label>
                                <input type="text" name="radio_data[impression]" id="impression_field" class="form-control py-3 fw-bold" value="{{ $res->radio_data['impression'] ?? '' }}" placeholder="Final clinical impression" required>
                            </div>
                        </div>
                    @endif

                    {{-- REPORT SCAN PREVIEW (Shown if scan attached or in verification mode) --}}
                    @if($isReadonly)
                        <div id="scan-preview-zone" class="shadow-lg">
                            <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                                <span>
                                    @if($hasReportScan) <i class="bi bi-file-earmark-pdf-fill me-2"></i>PHYSICAL SCAN FILE@else <i class="bi bi-file-pdf me-2"></i>GENERATED CLINICAL PDF PREVIEW @endif
                                </span>
                            </div>
                            {{-- FIXED: Dynamically streams the generated clinical PDF report if manual entry was used --}}
                            <iframe id="reportViewer" src="{{ $hasReportScan ? Storage::url($res->radio_scan) : route('appointments.result.access', [$appointment->id, 'radio', 'preview']) }}" class="w-100 rounded-bottom border border-warning bg-dark" style="min-height: 700px;"></iframe>
                        </div>
                    @else
                        {{-- Active scan preview toggle for editing mode --}}
                        <div id="scan-preview-zone" class="d-none shadow-lg">
                            <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                                <span><i class="bi bi-file-earmark-pdf-fill me-2"></i>REPORT SCAN OVERRIDE ACTIVE</span>
                                <button type="button" class="btn btn-sm btn-dark fw-bold px-3" onclick="removeScan()">REMOVE & RESTORE SIDEBAR</button>
                            </div>
                            <iframe id="reportViewer" src="" class="w-100 rounded-bottom border border-warning bg-dark" style="min-height: 700px;"></iframe>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </form>
</div>

{{-- 4. MODALS --}}

<!-- VERIFY MODAL -->
<div class="modal fade" id="verifyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.verify', [$appointment->id, 'radio']) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-neon fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Enter your name to sign-off on this radiologic report.</p>
            
            <div class="mb-4">
                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Verifier Name</label>
                <input type="text" name="sig_name" class="form-control" value="{{ auth()->user()->name }}" required>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-neon flex-grow-1 fw-bold uppercase">Approve & Hub Release</button>
            </div>
        </form>
    </div>
</div>

<!-- RETURN MODAL (Dropdown style with others custom text area) -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.return', $appointment->id) }}?type=radio" method="POST" id="radioReturnForm" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase mb-1">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this report for corrections.</p>
            
            {{-- Predefined Return Reasons Dropdown --}}
            <div class="mb-3">
                <label for="return_reason_select" class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return justification --</option>
                    <option value="Mismatched patient identification or Case ID">Mismatched patient identification or Case ID</option>
                    <option value="Unclear, low-quality, or incorrect X-Ray image snapshot">Unclear, low-quality, or incorrect X-Ray image snapshot</option>
                    <option value="Incomplete findings or vague diagnostic interpretation">Incomplete findings or vague diagnostic interpretation</option>
                    <option value="Discrepancies in radiologist signature or credentials">Discrepancies in radiologist signature or credentials</option>
                    <option value="Others">Others (Specify details below)</option>
                </select>
            </div>

            {{-- Hidden custom reason textbox, shown if "Others" is selected --}}
            <div id="custom_return_reason_wrapper" class="mb-3 d-none">
                <label for="reason_textarea" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                <textarea name="reason" id="reason_textarea" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="4" placeholder="Identify the specific correction needed..."></textarea>
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

@push('scripts')
<script>
function previewXray(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('xrayPreview').src = e.target.result;
            document.getElementById('xray-viewer-card').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleScanPriority(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('reportViewer').src = e.target.result;
            
            // Hide details
            if (document.getElementById('manual-panel')) {
                document.getElementById('manual-panel').classList.add('d-none');
            }
            document.getElementById('sidebar-container').classList.add('d-none');
            if (document.getElementById('main-upload-zone')) {
                document.getElementById('main-upload-zone').classList.add('d-none');
            }
            document.getElementById('scan-preview-zone').classList.remove('d-none');
            document.getElementById('main-panel-container').className = 'col-md-12';

            // BUG FIX: Disable text inputs but KEEP file inputs enabled for submission
            document.getElementById('sidebar-card').querySelectorAll('input:not([type="file"]), textarea, select').forEach(el => el.disabled = true);
            if (document.getElementById('findings_field')) document.getElementById('findings_field').disabled = true;
            if (document.getElementById('impression_field')) document.getElementById('impression_field').disabled = true;
            document.getElementById('clear_scan_field').value = "0";
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeScan() {
    document.getElementById('report_scan_input').value = "";
    document.getElementById('clear_scan_field').value = "1";
    if (document.getElementById('manual-panel')) {
        document.getElementById('manual-panel').classList.remove('d-none');
    }
    document.getElementById('sidebar-container').classList.remove('d-none');
    if (document.getElementById('main-upload-zone')) {
        document.getElementById('main-upload-zone').classList.remove('d-none');
    }
    document.getElementById('scan-preview-zone').classList.add('d-none');
    document.getElementById('main-panel-container').className = 'col-md-8';
    
    // RE-ENABLE inputs so they can be typed and validated again
    document.getElementById('sidebar-card').querySelectorAll('input, textarea, select').forEach(el => el.disabled = false);
    if (document.getElementById('findings_field')) document.getElementById('findings_field').disabled = false;
    if (document.getElementById('impression_field')) document.getElementById('impression_field').disabled = false;
}

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup for existing scans
    if ("{{ $hasReportScan }}" === "1") {
        document.getElementById('sidebar-card').querySelectorAll('input:not([type="file"]), textarea, select').forEach(el => el.disabled = true);
        if (document.getElementById('findings_field')) document.getElementById('findings_field').disabled = true;
        if (document.getElementById('impression_field')) document.getElementById('impression_field').disabled = true;
    }
    
    if ("{{ $isReadonly }}") {
        const form = document.getElementById('radioForm');
        form.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(el => el.disabled = true);
    }

    // Dynamic dropdown return reason toggle
    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('radioReturnForm');

    if (selectEl && textareaEl && textareaWrapper && formEl) {
        selectEl.addEventListener('change', function() {
            if (this.value === 'Others') {
                textareaWrapper.classList.remove('d-none');
                textareaEl.setAttribute('required', 'required');
                textareaEl.value = ''; // Reset Custom field
            } else {
                textareaWrapper.classList.add('d-none');
                textareaEl.removeAttribute('required');
                textareaEl.value = this.value; // Store standard justification directly
            }
        });

        formEl.addEventListener('submit', function(e) {
            if (selectEl.value !== 'Others') {
                textareaEl.value = selectEl.value;
            }
            if (textareaEl.value.trim().length < 5) {
                e.preventDefault();
                alert('A valid reason of at least 5 characters is required.');
            }
        });
    }
});
</script>

<style>
/* Scoped layout modifications */
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
</style>
@endpush
@endsection