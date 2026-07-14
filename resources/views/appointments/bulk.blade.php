@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11 col-xl-11 text-start animate-page">
 
        {{-- Unified 3-Step Wizard Container --}}
        <div class="card p-0 border-secondary bg-card shadow-lg overflow-hidden">
            <div class="row g-0 align-items-stretch">
 
                {{-- LEFT PANEL: WIZARD FLOW (Col 8) --}}
                <div class="col-md-8 border-end border-secondary border-opacity-25 p-4 p-md-5">
                    <form id="bulkForm" action="{{ route('appointments.bulk.manual') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="organization_name" id="hidden_org">
                        <input type="hidden" name="appointment_date" id="hidden_date">

                        {{-- STEP 2: ORGANIZATION & START DATE --}}
                        <div class="wiz-section" id="page-2">
                            <div class="mb-4">
                                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Organization & Schedule</h3>
                                <p class="text-secondary small">Provide your requesting entity details and global booking start date.</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Organization / Company Name</label>
                                    <input type="text" id="master_org" class="form-control py-3 fw-bold shadow-none" placeholder="Enter Requesting Entity...">
                                </div>
                                <div class="col-12">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Preferred Start Date</label>
                                    <input type="date" id="master_date" class="form-control py-3 fw-bold shadow-none" min="{{ date('Y-m-d') }}">
 
                                    {{-- Dynamic closed-day alert element --}}
                                    <div id="date_validation_msg" class="text-danger small mt-2 d-none">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Clinic is closed on this day. Please select another date.
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5">
                                <button type="button" id="proceed_to_compilation_btn" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase shadow-sm" onclick="proceedFromStep1()">
                                    PROCEED TO COMPILATION <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        {{-- STEP 3: FORM ENTRY OR TEMPLATE UPLOAD --}}
                        <div class="wiz-section d-none" id="page-3">
                            <div class="mb-4">
                                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 3: Spreadsheet Compilation</h3>
                                <p class="text-secondary small">Add patient rows manually, use smart scheduling, or upload legacy templates.</p>
                            </div>

                            {{-- Form Entry vs Template Upload sub-navigation --}}
                            <div class="d-flex gap-2 mb-4 border-bottom border-secondary border-opacity-10 pb-3">
                                <button type="button" class="btn-custom btn-accent px-4 py-2 fw-bold btn-sm" id="btn-manual" onclick="switchTab('manual')">
                                    FORM ENTRY
                                </button>
                                <button type="button" class="btn-custom btn-outline-accent px-4 py-2 border-0 fw-bold btn-sm" id="btn-excel" onclick="switchTab('excel')">
                                    TEMPLATE UPLOAD
                                </button>
                            </div>

                            <div id="tab-content" class="mb-5">
                                {{-- A. Manual data-entry table spreadsheet --}}
                                @include('appointments.partials.bulk.manual-pane')

                                {{-- B. Excel parser template uploader --}}
                                @include('appointments.partials.bulk.excel-pane')
                            </div>

                            <div class="d-flex gap-2 mt-5">
                                <button type="button" class="btn-custom btn-outline-secondary w-50 py-3 uppercase fw-bold" onclick="goToPage(2)">
                                    <i class="bi bi-arrow-left me-2"></i> BACK
                                </button>
                                <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateStep2()">
                                    PROCEED TO CHECKOUT <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        {{-- STEP 4: PAYMENT & CLINICAL AGREEMENTS --}}
                        <div class="wiz-section d-none" id="page-4">
                            <div class="mb-4">
                                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 4: Settle Payment & Finalize</h3>
                                <p class="text-secondary small">Select your preferred payment channel and agree to terms to commit the batch reservation.</p>
                            </div>

                            <div class="row g-4 text-start">
                                {{-- Payment Method Selection --}}
                                <div class="col-12">
                                    <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Select Payment Method</label>
                                    <div class="row g-3">
                                        {{-- Option 1: Cash --}}
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="Cash" checked>
                                            <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="pay_cash">
                                                <i class="bi bi-cash-stack fs-1 mb-2"></i>
                                                <div class="fw-bold uppercase">Cash on Site</div>
                                                <div class="smaller opacity-75 mt-1">Pay at the reception desk upon arrival.</div>
                                            </label>
                                        </div>

                                        {{-- Option 2: Cashless (Enabled) --}}
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="payment_method" id="pay_cashless" value="Cashless">
                                            <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="pay_cashless">
                                                <i class="bi bi-qr-code-scan fs-1 mb-2"></i>
                                                <div class="fw-bold uppercase">Online / E-Wallet</div>
                                                <div class="smaller opacity-75 mt-1">Scan and pay using digital wallets.</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Dynamic E-Wallet Selector Grid (Hidden until Cashless is selected) --}}
                                <div id="provider_selection_container" class="col-12 d-none mt-4 animate-fade-in">
                                    <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Choose E-Wallet Provider</label>
                                    <div class="row g-3">
                                        @if(isset($paymentProviders) && $paymentProviders->count() > 0)
                                            @foreach($paymentProviders as $provider)
                                                <div class="col-md-4 col-6">
                                                    <input type="radio" class="btn-check provider-radio" name="payment_provider_id" id="provider_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}">
                                                    <label class="btn btn-outline-secondary w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="provider_{{ $provider->id }}">
                                                        @if($provider->logo)
                                                            <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" class="mb-2" style="height: 32px; object-fit: contain;">
                                                        @else
                                                            <i class="bi bi-wallet2 fs-3 mb-2 text-secondary"></i>
                                                        @endif
                                                        {{-- FIXED: Switched static class 'text-white' to active theme variable mapping to prevent light mode blending --}}
                                                        <div class="small fw-bold uppercase" style="color: var(--text-main) !important;">{{ $provider->name }}</div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="col-12">
                                                <div class="alert alert-clinical text-center p-3 mb-0">
                                                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                                                    <span>No active payment gateways are configured. Please pay Cash on Site.</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- QR Code Display Container with Click-to-Zoom --}}
                                <div id="qr_section" class="col-12 d-none animate-fade-in mt-4">
                                    <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                                        <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Scan to Pay (<span id="selected_provider_name" class="text-accent"></span>)</h6>
                                        <div class="d-flex justify-content-center">
                                            {{-- FIXED: Added explicit ID for modern decoupled click listening --}}
                                            <div id="qr_zoom_wrapper" class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" title="Click to view full screen">
                                                <img src="" id="selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
                                            </div>
                                        </div>
                                        <p class="text-muted smaller mt-3 mb-0 italic" style="font-size: 0.7rem;">
                                            <i class="bi bi-zoom-in me-1 text-accent"></i> Click the QR code image to view it full screen.<br>
                                            Please take a screenshot of your successful transaction to present upon arrival.
                                        </p>
                                    </div>
                                </div>

                                {{-- Proof of payment receipt container for bulk cashless checkout --}}
                                <div id="receipt_upload_container" class="col-12 d-none mt-4 animate-fade-in">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>
                                    <input type="file" name="payment_receipt" id="in_receipt" class="form-control py-3 shadow-none" accept="image/*, application/pdf">
                                    <div class="mt-1">
                                        <small class="text-muted smaller">
                                            <i class="bi bi-info-circle me-1"></i> Required: Upload a PDF or image copy of your GCash/Maya transaction receipt to finalize.
                                        </small>
                                    </div>
                                </div>

                                {{-- Clinical Agreements --}}
                                <div class="col-12">
                                    <div class="card border-secondary border-opacity-25 bg-card p-4">
                                        <div class="form-check text-start">
                                            <input class="form-check-input" type="checkbox" id="agree_terms" required>
                                            <label class="form-check-label text-main small" for="agree_terms" style="font-size: 0.85rem;">
                                                I confirm that all information provided is accurate and I agree to the <a href="{{ route('legal.privacy') }}" target="_blank" class="text-accent fw-bold text-decoration-none">Clinical Privacy Policy</a>.
                                            </label>
                                        </div>
                                        <div class="mt-3 p-3 rounded border border-secondary border-opacity-10 text-start" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                                            <h6 class="text-warning fw-bold mb-1 smaller uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Important Reminder:</h6>
                                            <p class="text-muted smaller mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                                                For Blood Chemistry (FBS, Lipid Profile, etc.), please ensure you have undergone 10-12 hours of fasting for accurate results.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-5">
                                <button type="button" class="btn-custom btn-outline-secondary w-50 py-3 uppercase fw-bold" onclick="goToPage(3)">
                                    <i class="bi bi-arrow-left me-2"></i> BACK
                                </button>
                                <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="final_submit_btn">
                                    CONFIRM & REGISTER BATCH <i class="bi bi-check2-circle ms-2"></i>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

                {{-- RIGHT PANEL: STICKY BATCH SUMMARY (Col 4) --}}
                <div class="col-md-4 bg-secondary bg-opacity-10 p-4 p-md-5 border-start border-secondary border-opacity-10">
                    @include('appointments.partials.bulk.summary')
                </div>

            </div>
        </div>

        {{-- MODAL 1: TEST SELECTOR MODAL --}}
        <div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-secondary bg-card shadow-lg">
                    <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                        <h5 class="modal-title text-main fw-bold uppercase small">Select Laboratory Tests</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="p-3 border-bottom border-secondary border-opacity-10">
                            <input type="text" id="serviceSearch" class="form-control" placeholder="Search test name...">
                        </div>
                        <div id="serviceList" class="overflow-auto" style="max-height: 400px;">
                            @foreach($services as $s)
                                <label class="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary border-opacity-10 service-item cursor-pointer hover-bg" for="ch_{{ $s->id }}" data-name="{{ strtoupper($s->name) }}" data-gender="{{ $s->gender_restriction }}">
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input me-3 mt-0 border-secondary" type="checkbox" value="{{ $s->id }}" data-label="{{ $s->name }}" id="ch_{{ $s->id }}">
                                        <span class="text-main fw-bold small">{{ strtoupper($s->name) }}</span>
                                    </div>
                                    <span class="text-accent fw-bold small"> {{ number_format($s->price) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-0">
                        <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" onclick="applyServices()">APPLY SELECTION</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL 2: CUSTOM VALIDATION ALERT --}}
        <div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-secondary bg-card shadow-lg text-center p-4">
                    <i class="bi bi-exclamation-circle text-accent mb-3 d-block" style="font-size: 3rem;"></i>
                    <h5 class="text-main fw-bold mb-2 uppercase">Information</h5>
                    <p id="validationMsg" class="text-secondary small mb-4"></p>
                    <button type="button" class="btn-custom btn-accent w-100 py-2 uppercase fw-bold" data-bs-dismiss="modal">UNDERSTOOD</button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- FULLSCREEN QR LIGHTBOX OVERLAY --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="window.closeQRLightbox()">
    <div class="text-center p-3 animate-fade-in">
        <img src="" id="lightbox_qr_img" alt="Zoomed QR" class="img-fluid rounded border border-secondary p-3 bg-white" style="max-height: 75vh; max-width: 90vw; object-fit: contain;">
        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the screen to close preview</p>
    </div>
</div>

<style>
.hover-bg:hover { background-color: rgba(25, 211, 140, 0.05); }
.cursor-pointer { cursor: pointer; }

#rowContainer input, #rowContainer select, #rowContainer textarea { 
    background-color: var(--bg-card) !important; 
    border: 1px solid var(--border-color) !important; 
    color: var(--text-main) !important; 
}
.cursor-not-allowed { cursor: not-allowed !important; }

/* FIXED: Highlights selected payment method cleanly with themed border-accent glow */
.btn-check:checked + label.btn-outline-accent {
    background-color: rgba(25, 211, 140, 0.06) !important;
    border-color: var(--brand-accent) !important;
    border-width: 2.2px !important;
    box-shadow: 0 0 12px rgba(25, 211, 140, 0.12) !important;
}
.btn-check:checked + label.btn-outline-accent i {
    color: var(--brand-accent) !important;
}

/* FIXED: Light Mode Checked text / icon high contrast emerald green */
.btn-check:checked + label.btn-outline-accent,
.btn-check:checked + label.btn-outline-accent i,
.btn-check:checked + label.btn-outline-accent div,
.btn-check:checked + label.btn-outline-accent span {
    color: #15b376 !important; 
}

/* FIXED: Dark Mode Checked text / icon high contrast brand accent */
[data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent,
[data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent i,
[data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent div,
[data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent span {
    color: var(--brand-accent) !important;
}

/* FIXED: Ensure unselected payment method cards use high-contrast, non-blending colors in both modes */
label.btn-outline-accent {
    border-color: var(--border-color) !important;
    color: var(--text-main) !important;
    background-color: var(--bg-card) !important;
}
label.btn-outline-accent i {
    color: var(--brand-accent) !important;
}
label.btn-outline-accent div, 
label.btn-outline-accent span {
    color: var(--text-main) !important;
}
label.btn-outline-accent .opacity-75, 
label.btn-outline-accent div.smaller {
    color: var(--text-muted) !important;
}
</style>

@include('appointments.partials.bulk.scripts')

@push('scripts')
<script>
// FIXED: Attached layout handlers globally onto window object to avoid DOM namespace collisions
window.zoomQR = function(qrSrc) {
    if (qrSrc) {
        document.getElementById('lightbox_qr_img').src = qrSrc;
        document.getElementById('qr_lightbox').classList.remove('d-none');
        document.getElementById('qr_lightbox').classList.add('d-flex');
    }
}

window.closeQRLightbox = function() {
    document.getElementById('qr_lightbox').classList.add('d-none');
    document.getElementById('qr_lightbox').classList.remove('d-flex');
}

document.addEventListener('DOMContentLoaded', () => {
    const payCash = document.getElementById('pay_cash');
    const payCashless = document.getElementById('pay_cashless');
    const providerContainer = document.getElementById('provider_selection_container');
    const qrSection = document.getElementById('qr_section');
    const receiptContainer = document.getElementById('receipt_upload_container');
    const providerRadios = document.querySelectorAll('.provider-radio');
    const qrImage = document.getElementById('selected_provider_qr');
    const qrLabel = document.getElementById('selected_provider_name');

    // proof of payment uploader now only appears once a cashless provider is checked
    function togglePaymentFields() {
        if (payCashless.checked) {
            providerContainer.classList.remove('d-none');
            const activeRadio = document.querySelector('.provider-radio:checked');
            if (activeRadio) {
                qrSection.classList.remove('d-none');
                receiptContainer.classList.remove('d-none');
            } else {
                qrSection.classList.add('d-none');
                receiptContainer.classList.add('d-none');
            }
        } else {
            providerContainer.classList.add('d-none');
            receiptContainer.classList.add('d-none');
            qrSection.classList.add('d-none');
            providerRadios.forEach(radio => radio.checked = false);
        }
    }

    [payCash, payCashless].forEach(input => {
        if (input) input.addEventListener('change', togglePaymentFields);
    });

    providerRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                qrImage.src = this.dataset.qr;
                qrLabel.innerText = this.dataset.name;
                togglePaymentFields();
            }
        });
    });
});
</script>
@endpush
@endsection