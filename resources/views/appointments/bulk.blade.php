@extends('layouts.app')

@section('title', 'Create Bulk Appointment')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11 col-xl-11 text-start animate-page">

        {{-- Unified 3-Step Wizard Container --}}
        <div class="card p-0 border-secondary bg-card shadow-lg overflow-hidden">
            <div class="row g-0 align-items-stretch">

                {{-- LEFT PANEL: WIZARD FLOW (Col 8) --}}
                <div class="col-md-8 border-end border-secondary border-opacity-25 p-4 p-md-5">
                    <form id="bulkForm" action="{{ route('appointments.bulk.manual') }}" method="POST" enctype="multipart/form-data" onsubmit="return validateBulkForm(event)">
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
                                    <input type="date" id="master_date" class="form-control py-3 fw-bold shadow-none" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">

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

                                        {{-- Option 2: Cashless --}}
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

                                {{-- Dynamic E-Wallet Selector Grid --}}
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
                                            <div id="qr_zoom_wrapper" class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" onclick="zoomQR(document.getElementById('selected_provider_qr').src)" title="Click to view full screen">
                                                <img src="" id="selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
                                            </div>
                                        </div>
                                        <p class="text-muted smaller mt-3 mb-0 italic" style="font-size: 0.7rem;">
                                            <i class="bi bi-zoom-in text-accent me-1"></i> Click the QR code image to view it full screen.<br>
                                            Please take a screenshot of your transaction to present upon arrival.
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

        {{-- FULLSCREEN QR LIGHTBOX OVERLAY --}}
        <div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="window.closeQRLightbox()">
            <div class="text-center p-3 animate-fade-in">
                <img src="" id="lightbox_qr_img" alt="Zoomed QR" class="img-fluid rounded border border-secondary p-3 bg-white" style="max-height: 75vh; max-width: 90vw; object-fit: contain;">
                <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the screen to close preview</p>
            </div>
        </div>

    </div>
</div>

{{-- CUSTOM THEME-COMPATIBLE VALIDATION ALERT MODAL --}}
<div class="modal fade" id="wizardValidationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-accent display-4 d-block"></i>
            </div>
            <h5 class="text-main fw-bold mb-2 uppercase tracking-tighter" id="wizardValidationTitle">Omissions Found</h5>
            <div id="wizardValidationMsg" class="text-secondary small mb-4">Please fill in all required fields and complete your selections before proceeding.</div>
            <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" data-bs-dismiss="modal">UNDERSTOOD</button>
        </div>
    </div>
</div>

<style>
    .hover-bg:hover { background-color: rgba(25, 211, 140, 0.05); }
    .cursor-pointer { cursor: pointer; }

    #rowContainer input, #rowContainer select { 
        background-color: var(--bg-card) !important; 
        border: 1px solid var(--border-color) !important; 
        color: var(--text-main) !important; 
    }
    .cursor-not-allowed { cursor: not-allowed !important; }

    /* Highlights selected payment method cleanly with themed border-accent glow */
    .btn-check:checked + label.btn-outline-accent {
        background-color: rgba(25, 211, 140, 0.06) !important;
        border-color: var(--brand-accent) !important;
        border-width: 2.2px !important;
        box-shadow: 0 0 12px rgba(25, 211, 140, 0.12) !important;
    }
    .btn-check:checked + label.btn-outline-accent i {
        color: var(--brand-accent) !important;
    }

    /* Light Mode Checked text / icon high contrast emerald green */
    .btn-check:checked + label.btn-outline-accent,
    .btn-check:checked + label.btn-outline-accent i,
    .btn-check:checked + label.btn-outline-accent div,
    .btn-check:checked + label.btn-outline-accent span {
        color: #15b376 !important; 
    }

    /* Dark Mode Checked text / icon high contrast brand accent */
    [data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent,
    [data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent i,
    [data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent div,
    [data-bs-theme="dark"] .btn-check:checked + label.btn-outline-accent span {
        color: var(--brand-accent) !important;
    }

    /* Ensure unselected payment method cards use high-contrast, non-blending colors in both modes */
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

    /* --- REDESIGNED ACTION BUTTON CORES --- */
    .btn-outline-accent {
        border-color: var(--brand-accent) !important;
        color: var(--brand-accent) !important;
        background-color: transparent !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-outline-accent:hover {
        background-color: var(--brand-accent) !important;
        color: var(--brand-dark) !important;
        box-shadow: 0 0 10px rgba(25, 211, 140, 0.2);
    }
    .btn-outline-danger {
        border-color: #ff4d4d !important;
        color: #ff4d4d !important;
        background-color: transparent !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-outline-danger:hover {
        background-color: #ff4d4d !important;
        color: #ffffff !important;
        box-shadow: 0 0 10px rgba(255, 77, 77, 0.2);
    }

    /* --- SERVICE SELECTOR MODAL HIGH CONTRAST THEMING --- */
    #serviceModal .modal-content {
        background-color: var(--bg-card) !important;
        border: 1.5px solid var(--border-color) !important;
        border-radius: 16px !important;
    }
    #serviceModal .modal-header {
        border-bottom: 1px solid var(--border-color) !important;
        background-color: rgba(25, 211, 140, 0.05) !important;
    }
    #serviceModal .modal-title {
        color: var(--brand-accent) !important;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    #serviceModal .service-item .form-check {
        background-color: var(--bg-card);
        border: 1.5px solid var(--border-color) !important;
        border-radius: 10px;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        padding: 14px 15px 14px 38px !important;
        margin-bottom: 0;
    }
    #serviceModal .service-item .form-check:hover {
        border-color: var(--brand-accent) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(25, 211, 140, 0.1);
    }
    #serviceModal .service-item .form-check-input {
        margin-left: -24px !important;
        margin-top: 5px !important;
        border: 1.5px solid var(--border-color);
        cursor: pointer;
        width: 1.15em;
        height: 1.15em;
    }
    #serviceModal .service-item .form-check-input:checked {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
    }
    #serviceModal .service-item .form-check:has(.form-check-input:checked) {
        border-color: var(--brand-accent) !important;
        background-color: rgba(25, 211, 140, 0.06) !important;
    }
    #serviceModal .service-item .form-check-input:checked + .form-check-label {
        color: var(--brand-accent) !important;
    }
    #serviceModal .modal-footer {
        border-top: 1px solid var(--border-color) !important;
        background-color: rgba(0, 0, 0, 0.02) !important;
    }
    #serviceModal #serviceSearch {
        background-color: var(--bg-card) !important;
        border: 1.5px solid var(--border-color) !important;
        color: var(--text-main) !important;
        border-radius: 10px;
    }
    #serviceModal #serviceSearch:focus {
        border-color: var(--brand-accent) !important;
        box-shadow: 0 0 0 4px rgba(25, 211, 140, 0.1) !important;
    }
</style>

<!-- Service Selection Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-secondary bg-card">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalLabel">
                    <i class="bi bi-flask-fill me-2"></i>Select Laboratory Tests
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <!-- Search Input -->
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="serviceSearch" class="form-control shadow-none" placeholder="Search tests by name...">
                    </div>
                </div>

                <!-- Services List -->
                <div class="row g-3" id="serviceListContainer" style="max-height: 400px; overflow-y: auto;">
                    @foreach($services as $service)
                        <div class="col-md-6 service-item" data-name="{{ strtoupper($service->name) }}" data-gender="{{ $service->gender_restriction }}">
                            <div class="form-check p-2">
                                <input class="form-check-input ms-0 me-2" type="checkbox" value="{{ $service->id }}" data-label="{{ $service->name }}" id="service_chk_{{ $service->id }}">
                                <label class="form-check-label text-main small cursor-pointer" for="service_chk_{{ $service->id }}">
                                    <span class="fw-bold d-block text-main">{{ strtoupper($service->name) }}</span>
                                    <span class="text-accent">&#x20B1;<span class="fw-bold">{{ number_format($service->price, 2) }}</span></span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase" onclick="applyServices()">APPLY SELECTIONS</button>
            </div>
        </div>
    </div>
</div>

@include('appointments.partials.bulk.scripts')

@push('scripts')
<script>
// Attached layout handlers globally onto window object to avoid DOM namespace collisions
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