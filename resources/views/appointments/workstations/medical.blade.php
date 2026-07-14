@extends('layouts.app')

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
@endphp

<div class="container-fluid text-start" id="medical-workstation-root">
    
    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase">
                @if($isVerified) REVIEW MODE @elseif($status == 'encoded') VERIFICATION MODE @else MEDICAL CERTIFICATE @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ strtoupper($appointment->patient_name) }}</span> | Ref: <span class="text-accent">#{{ $appointment->id }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            {{-- Styled outline button to avoid blue fallback links --}}
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>
            
            @if(!$isReadonly)
                <button type="submit" form="medForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
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
        <div class="row g-4">
            
            {{-- SIDEBAR: METADATA & CLINICAL SIGNATORIES --}}
            <div class="col-md-4 {{ $isReadonly && !$hasScan ? 'd-none' : ($hasScan ? 'd-none' : '') }}" id="sidebar-container">
                <div class="card p-3 border-secondary bg-card mb-3 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Certificate Metadata</h6>
                    
                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Cert No.</label>
                        <input type="text" name="med_cert_data[metadata][cert_no]" id="cert_no_field" class="form-control" value="{{ $res->med_cert_data['metadata']['cert_no'] ?? '' }}" placeholder="Enter Certificate ID" required>
                    </div>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Date of Issue</label>
                        <input type="date" name="med_cert_data[metadata][date]" class="form-control" value="{{ $res->med_cert_data['metadata']['date'] ?? $today }}">
                    </div>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Patient Name</label>
                        <input type="text" name="med_cert_data[metadata][name]" class="form-control" value="{{ $res->med_cert_data['metadata']['name'] ?? strtoupper($appointment->patient_name) }}">
                    </div>

                    <div class="mb-3">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Address</label>
                        <input type="text" name="med_cert_data[metadata][address]" class="form-control" value="{{ $res->med_cert_data['metadata']['address'] ?? $appointment->patient_address }}">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Age</label>
                            <input type="number" name="med_cert_data[metadata][age]" class="form-control" value="{{ $res->med_cert_data['metadata']['age'] ?? $appointment->patient_age }}">
                        </div>
                        <div class="col-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Sex</label>
                            <input type="text" name="med_cert_data[metadata][sex]" class="form-control" value="{{ $res->med_cert_data['metadata']['sex'] ?? strtoupper($appointment->patient_sex) }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Date Tested</label>
                        <input type="date" name="med_cert_data[metadata][tested_date]" class="form-control" value="{{ $res->med_cert_data['metadata']['tested_date'] ?? $testedDate }}">
                    </div>

                    <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Clinical Signatory</h6>
                    <div class="mb-0 border border-secondary border-opacity-10 p-2.5 rounded" style="background-color: rgba(0,0,0,0.015);">
                        <input type="text" name="med_cert_data[sig][name]" class="form-control mb-1" placeholder="Physician Name" value="{{ $res->med_cert_data['sig']['name'] ?? '' }}">
                        <input type="text" name="med_cert_data[sig][lic]" class="form-control" placeholder="License / PRC No." value="{{ $res->med_cert_data['sig']['lic'] ?? '' }}">
                    </div>
                </div>
            </div>

            {{-- MAIN WORKSTATION PANEL --}}
            <div class="{{ $isReadonly ? 'col-md-12' : ($hasScan ? 'col-md-12' : 'col-md-8') }}" id="main-panel-container">
                
                {{-- Prominent Scan Upload Zone (Only visible in active encoding mode) --}}
                @if(!$isReadonly)
                    <div class="card p-4 border-warning border-opacity-25 mb-4 text-center shadow-sm" id="main-upload-zone" style="background-color: rgba(255, 193, 7, 0.02); border-style: dashed !important; border-width: 2px !important;">
                        <h6 class="text-warning fw-bold mb-2 uppercase"><i class="bi bi-file-earmark-arrow-up-fill me-2 fs-5"></i>Attach Completed Certificate Scan (Recommended)</h6>
                        <p class="text-secondary small mb-3">Uploading a physical scanned report takes absolute clinical priority and hides the manual inputs below.</p>
                        <div class="mx-auto" style="max-width: 450px;">
                            <input type="file" name="med_cert_scan" id="med_scan_input" class="form-control" onchange="toggleScanPriority(this)">
                        </div>
                    </div>
                @endif

                {{-- MANUAL FINDINGS (Hidden if scan/verify is active and form is writable) --}}
                @if(!$isReadonly)
                    <div id="manual-panel" class="card p-4 border-secondary bg-card min-vh-75 shadow-lg {{ $hasScan ? 'd-none' : '' }}">
                        <h6 class="text-main border-bottom border-secondary border-opacity-25 pb-2 mb-4 uppercase small fw-bold" style="color: var(--text-main);">Manual Content Entry</h6>
                        
                        <div class="mb-4">
                            <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Findings</label>
                            <textarea name="med_cert_data[findings]" id="findings_field" class="form-control p-3" rows="12" placeholder="Describe the medical findings here..." required>{{ $res->med_cert_data['findings'] ?? '' }}</textarea>
                        </div>

                        <div>
                            <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Remarks</label>
                            <textarea name="med_cert_data[remarks]" class="form-control p-3" rows="4" placeholder="Additional notes...">{{ $res->med_cert_data['remarks'] ?? '' }}</textarea>
                        </div>
                    </div>
                @endif

                {{-- 4. VERIFY AREA / SCAN PREVIEW (Always displayed when in Verification or Review Mode) --}}
                @if($isReadonly)
                    <div id="scan-preview-zone" class="h-100 min-vh-75 shadow-lg">
                        <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                            <span>
                                @if($hasScan) <i class="bi bi-file-earmark-pdf-fill me-2"></i>PHYSICAL SCAN FILE@else <i class="bi bi-file-pdf me-2"></i>GENERATED CLINICAL PDF PREVIEW @endif
                            </span>
                            @if(!$isReadonly && $hasScan)
                                <button type="button" class="btn btn-sm btn-dark fw-bold" onclick="removeScan()">REMOVE & RESTORE FORM</button>
                            @endif
                        </div>
                        {{-- FIXED: Dynamically streams the generated clinical PDF report if manual entry was used --}}
                        <iframe id="scanViewer" src="{{ $hasScan ? Storage::url($res->med_cert_scan) : route('appointments.result.access', [$appointment->id, 'med_cert', 'preview']) }}" class="w-100 h-100 rounded-bottom border border-warning bg-dark" style="min-height: 800px; border: none;"></iframe>
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

{{-- 4. MODALS --}}

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

<!-- RETURN MODAL (Dropdown style with others custom text area) -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.return', $appointment->id) }}?type=med_cert" method="POST" id="medReturnForm" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this certificate for corrections.</p>
            
            {{-- Predefined Return Reasons Dropdown --}}
            <div class="mb-3">
                <label for="return_reason_select" class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return justification --</option>
                    <option value="Mismatched patient identification or demographic fields">Mismatched patient identification or demographic fields</option>
                    <option value="Incomplete findings or vague diagnostic interpretation">Incomplete findings or vague diagnostic interpretation</option>
                    <option value="Incorrect date of issue or tested date selection">Incorrect date of issue or tested date selection</option>
                    <option value="Discrepancies in clinical signature or credentials">Discrepancies in clinical signature or credentials</option>
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
function toggleScanPriority(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('scanViewer').src = e.target.result;
            
            // Hide parts
            if (document.getElementById('manual-panel')) {
                document.getElementById('manual-panel').classList.add('d-none');
            }
            document.getElementById('sidebar-container').classList.add('d-none');
            if (document.getElementById('main-upload-zone')) {
                document.getElementById('main-upload-zone').classList.add('d-none');
            }
            document.getElementById('scan-preview-zone').classList.remove('d-none');
            document.getElementById('main-panel-container').className = 'col-md-12';

            // Remove required attributes so browser lets us save
            document.getElementById('cert_no_field').required = false;
            if (document.getElementById('findings_field')) document.getElementById('findings_field').required = false;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeScan() {
    document.getElementById('med_scan_input').value = "";
    if (document.getElementById('manual-panel')) {
        document.getElementById('manual-panel').classList.remove('d-none');
    }
    document.getElementById('sidebar-container').classList.remove('d-none');
    if (document.getElementById('main-upload-zone')) {
        document.getElementById('main-upload-zone').classList.remove('d-none');
    }
    document.getElementById('scan-preview-zone').classList.add('d-none');
    document.getElementById('main-panel-container').className = 'col-md-8';

    // RESTORE required attributes
    document.getElementById('cert_no_field').required = true;
    if (document.getElementById('findings_field')) document.getElementById('findings_field').required = true;
}

document.addEventListener('DOMContentLoaded', () => {
    // Initial setup if scan exists
    if ("{{ $hasScan }}" === "1" || "{{ $hasScan }}" === "true") {
        document.getElementById('cert_no_field').required = false;
        if (document.getElementById('findings_field')) document.getElementById('findings_field').required = false;
    }

    if("{{ $isReadonly }}") {
        const form = document.getElementById('medForm');
        form.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(el => el.disabled = true);
    }

    // Dynamic dropdown return reason toggle
    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('medReturnForm');

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
</style>
@endpush
@endsection