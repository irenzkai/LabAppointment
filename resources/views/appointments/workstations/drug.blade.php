@extends('layouts.app')

@section('content')
@php
    $res = $appointment->result;
    $status = $res->drug_status ?? 'pending';
    
    // UI Logic States
    $isVerified = ($status === 'verified');
    $isReadonly = in_array($status, ['encoded', 'verified']);
    $hasScan = ($res && $res->drug_test_scan);
@endphp

<div class="container text-start" id="drug-workstation-root">
    
    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase">
                @if($isVerified) REVIEW MODE @elseif($isReadonly) VERIFICATION MODE @else DRUG TEST WORKSTATION @endif
            </h2>
            <p class="text-secondary small mb-0 uppercase">Patient: <span class="fw-bold" style="color: var(--text-main);">{{ strtoupper($appointment->patient_name) }}</span> | Ref: <span class="text-accent">#{{ $appointment->id }}</span></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            {{-- Styled outline button to avoid blue fallback links --}}
            <a href="{{ route('appointments.encode', $appointment->id) }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">BACK TO HUB</a>
            
            @if(!$isReadonly)
                <button type="submit" form="drugForm" class="btn-custom btn-accent px-5 shadow-lg">SAVE & SEND TO HUB</button>
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
    @if($res && $res->drug_return_reason && $status != 'verified')
        <div class="alert-clinical p-3 mb-4 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                <div>
                    <div class="fw-bold small uppercase">Verifier Correction Request:</div>
                    <div class="small italic">"{{ $res->drug_return_reason }}"</div>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. CORE SAVE FORM --}}
    <form id="drugForm" action="{{ $isReadonly ? route('workstation.verify', [$appointment->id, 'drug']) : route('workstation.drug.save', $appointment->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row justify-content-center">
            <div class="{{ $isReadonly ? 'col-md-12' : 'col-md-10' }}" id="main-panel-container">
                
                {{-- Prominent Drug Test Scan Dropzone (Only visible in active encoding mode) --}}
                @if(!$isReadonly)
                    <div class="card p-5 text-center shadow-lg mb-4 {{ $hasScan ? 'd-none' : '' }}" id="upload-zone" style="background-color: var(--bg-card); border: 2px dashed rgba(220, 53, 69, 0.25) !important; border-radius: 12px; color: var(--text-main);">
                        <i class="bi bi-file-earmark-arrow-up-fill text-danger display-1 mb-3"></i>
                        <h4 class="fw-bold uppercase" style="color: var(--text-main);">Mandatory Drug Test Scan</h4>
                        <p class="text-secondary mb-4 small">Drug test results are strictly handled via scanned physical reports.<br>Please select the official document to proceed.</p>
                        
                        <div class="mx-auto" style="max-width: 450px;">
                            <input type="file" name="drug_test_scan" id="drug_scan_input" class="form-control form-control-lg" onchange="previewDrugScan(this)" required>
                        </div>
                    </div>
                @endif

                {{-- 4. SCAN PREVIEW PANEL --}}
                <div id="scan-preview-zone" class="{{ $isReadonly || $hasScan ? '' : 'd-none' }} shadow-lg">
                    <div class="bg-warning text-dark p-2 px-3 fw-bold d-flex justify-content-between align-items-center rounded-top">
                        <span><i class="bi bi-eye-fill me-2"></i>OFFICIAL DRUG TEST SCAN PREVIEW</span>
                        @if(!$isReadonly)
                            <button type="button" class="btn btn-sm btn-dark fw-bold px-3" onclick="resetUpload()">CHANGE FILE</button>
                        @endif
                    </div>
                    <div class="card border-warning border-top-0 rounded-0 rounded-bottom overflow-hidden shadow">
                        {{-- FIXED: Dynamically streams the generated clinical PDF report if manual entry was used --}}
                        <iframe id="scanViewer" src="{{ $hasScan ? Storage::url($res->drug_test_scan) : route('appointments.result.access', [$appointment->id, 'drug', 'preview']) }}" class="w-100 bg-dark" style="min-height: 800px; border: none;"></iframe>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- 5. MODALS (OUTSIDE MAIN FORM) --}}

<!-- VERIFY MODAL -->
<div class="modal fade" id="verifyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.verify', [$appointment->id, 'drug']) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-neon fw-bold mb-1 uppercase">Clinical Verification</h5>
            <p class="text-secondary small mb-4">Enter your name to verify that the uploaded drug test scan matches the patient record.</p>
            
            <div class="mb-4">
                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Verifier Full Name</label>
                <input type="text" name="sig_name" class="form-control" value="{{ auth()->user()->name }}" required>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-neon flex-grow-1 fw-bold uppercase">Confirm & Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- RETURN MODAL (Dropdown style with others custom text area) -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.return', $appointment->id) }}?type=drug" method="POST" id="drugReturnForm" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            <h5 class="text-danger fw-bold uppercase">Return to Encoder</h5>
            <p class="text-secondary small mb-3">Provide a reason for returning this scan (e.g., blurry image, wrong patient).</p>
            
            {{-- Predefined Return Reasons Dropdown --}}
            <div class="mb-3">
                <label for="return_reason_select" class="smaller fw-bold mb-2 d-block uppercase" style="color: var(--text-muted);">Reason for Return</label>
                <select id="return_reason_select" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                    <option value="" disabled selected>-- Select a return justification --</option>
                    <option value="Mismatched patient identification or demographic fields">Mismatched patient identification or demographic fields</option>
                    <option value="Unclear, low-quality, or blurry document scan">Unclear, low-quality, or blurry document scan</option>
                    <option value="Discrepancies in medical signatory or licenses">Discrepancies in medical signatory or licenses</option>
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
function previewDrugScan(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('scanViewer').src = e.target.result;
            document.getElementById('upload-zone')?.classList.add('d-none');
            document.getElementById('scan-preview-zone').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function resetUpload() {
    const input = document.getElementById('drug_scan_input');
    if(input) input.value = "";
    
    document.getElementById('upload-zone').classList.remove('d-none');
    document.getElementById('scan-preview-zone').classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', () => {
    // Enforce readonly state for verification/review modes
    if("{{ $isReadonly }}") {
        const input = document.getElementById('drug_scan_input');
        if(input) input.disabled = true;
    }

    // Dynamic dropdown return reason toggle
    const selectEl = document.getElementById('return_reason_select');
    const textareaWrapper = document.getElementById('custom_return_reason_wrapper');
    const textareaEl = document.getElementById('reason_textarea');
    const formEl = document.getElementById('drugReturnForm');

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
#drug-workstation-root .form-control,
#drug-workstation-root .form-select,
#drug-workstation-root .input-group-text,
#drug-workstation-root .form-control:focus,
#drug-workstation-root .form-select:focus {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}

#drug-workstation-root .input-group-text {
    background-color: var(--border-color) !important;
}

#drug-workstation-root .modal-content .form-control {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border: 1.5px solid var(--border-color) !important;
}

#drug-workstation-root .btn-outline-secondary:hover {
    background-color: var(--border-color) !important;
    color: var(--text-main) !important;
}
</style>
@endpush
@endsection