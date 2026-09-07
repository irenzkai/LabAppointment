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
                    <form id="bulkForm" action="{{ route('appointments.bulk.manual') }}" method="POST" enctype="multipart/form-data" novalidate>
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
                                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 4: Payment & Finalize</h3>
                                <p class="text-secondary small">Choose how you would like to settle your bulk laboratory fees.</p>
                            </div>

                            <div class="row g-4 text-start">
                                {{-- Payment Method Selection --}}
                                <div class="col-12">
                                    <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Select Payment Method</label>
                                    <div class="row g-3">
                                        {{-- Option 1: Cash --}}
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="Cash" checked onchange="handleBulkPaymentMethodChange(this)">
                                            <label class="btn payment-method-card w-100 p-4 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="pay_cash">
                                                <i class="bi bi-cash-stack fs-1 mb-2"></i>
                                                <div class="fw-bold uppercase option-title">Cash on Site</div>
                                                <div class="smaller opacity-75 mt-1">Pay at reception desk upon arrival.</div>
                                            </label>
                                        </div>

                                        {{-- Option 2: Cashless --}}
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="payment_method" id="pay_cashless" value="Cashless" onchange="handleBulkPaymentMethodChange(this)">
                                            <label class="btn payment-method-card w-100 p-4 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="pay_cashless">
                                                <i class="bi bi-qr-code-scan fs-1 mb-2"></i>
                                                <div class="fw-bold uppercase option-title">Online / E-Wallet</div>
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
                                                <input type="radio" class="btn-check provider-radio bulk-prov-radio" name="payment_provider_id" id="provider_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" onchange="handleBulkProviderChange(this)">
                                                <label class="btn btn-outline-secondary w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="provider_{{ $provider->id }}">
                                                    @if($provider->logo)
                                                    <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" class="mb-2" style="height: 32px; object-fit: contain;">
                                                    @else
                                                    <i class="bi bi-wallet2 fs-3 mb-2 text-secondary"></i>
                                                    @endif
                                                    <div class="small fw-bold uppercase text-main">{{ $provider->name }}</div>
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
                                    <div class="invalid-feedback d-none mt-2" id="err_bulk_provider"></div>
                                </div>

                                {{-- QR Code Display Box --}}
                                <div id="qr_section" class="col-12 d-none animate-fade-in mt-4">
                                    <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                                        <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            Scan to Pay (<span id="selected_provider_name" class="text-accent">E-Wallet</span>)
                                        </h6>
                                        <div class="d-flex justify-content-center">
                                            <div id="qr_zoom_wrapper" class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10 cursor-pointer" style="cursor: zoom-in;" onclick="window.zoomQR(document.getElementById('selected_provider_qr').src)" title="Click to view full screen">
                                                <img src="" id="selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2" style="font-size: 0.7rem;">
                                            <i class="bi bi-zoom-in text-accent me-1"></i>Click the QR code image to zoom full-screen.
                                        </small>
                                    </div>
                                </div>

                                {{-- Proof of Payment Receipt Container --}}
                                <div id="receipt_upload_container" class="col-12 d-none mt-4 animate-fade-in">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>
                                    <div id="receipt_input_wrapper">
                                        <input type="file" name="payment_receipt" id="in_receipt" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleBulkReceiptUpload(this)">
                                    </div>
                                    <div id="receipt_preview_container" class="d-none mt-3 p-3 rounded" style="background-color: rgba(25, 211, 140, 0.03); border: 1px solid rgba(25, 211, 140, 0.15);">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="small text-accent fw-semibold" id="receipt_file_label">
                                                <i class="bi bi-file-earmark-check-fill me-1"></i>Selected File
                                            </span>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="viewBulkReceiptFile()">View</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold" onclick="removeBulkUploadedReceipt()">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted smaller">
                                            <i class="bi bi-info-circle me-1"></i> Required: Upload a PDF or image copy of your GCash/Maya transaction receipt to finalize.
                                        </small>
                                    </div>
                                </div>

                                {{-- Clinical Agreements & Cancellation Policy --}}
                                <div class="col-12">
                                    <div class="card border-secondary border-opacity-25 bg-card p-4">
                                        <div class="form-check text-start">
                                            <input class="form-check-input" type="checkbox" id="agree_terms" onchange="toggleBulkSubmitButton()" required>
                                            <label class="form-check-label text-main small" for="agree_terms" style="font-size: 0.85rem;">
                                                I confirm that all information provided is accurate and I agree to the <a href="{{ route('legal.privacy') }}" target="_blank" class="text-accent fw-bold text-decoration-none">Clinical Privacy Policy</a>.
                                            </label>
                                        </div>
                                        <div class="mt-3 p-3 rounded border border-secondary border-opacity-10 text-start" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                                            <h6 class="text-warning fw-bold mb-1 smaller uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;"><i class="bi bi-info-circle-fill me-1.5"></i> Cancellation & Refund Policy:</h6>
                                            <p class="text-muted smaller mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                                                Cancellations made <strong>more than 24 hours</strong> prior to your scheduled visit qualify for a <strong>100% full refund</strong>. Cancellations requested <strong>within 24 hours</strong> of your scheduled time are subject to a <strong>50% administrative cancellation fee</strong> (50% refund).
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-5">
                                <button type="button" class="btn-custom btn-outline-secondary w-50 py-3 uppercase fw-bold" onclick="goToPage(3)">
                                    <i class="bi bi-arrow-left me-2"></i> BACK
                                </button>
                                <button type="button" form="bulkForm" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm opacity-50 cursor-not-allowed" id="final_submit_btn" disabled style="pointer-events: none;" onclick="submitBulkManualForm(event)">
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

{{-- UNIFIED LIGHTBOX OVERLAY --}}
@include('layouts.partials.lightbox-overlay')

<style>
    .hover-bg:hover { background-color: rgba(25, 211, 140, 0.05); }
    .cursor-pointer { cursor: pointer; }
    #rowContainer input, #rowContainer select { 
        background-color: var(--bg-card) !important; 
        border: 1px solid var(--border-color) !important; 
        color: var(--text-main) !important; 
    }
    .cursor-not-allowed { cursor: not-allowed !important; }

    /* =========================================================================
       CUSTOM HIGH-CONTRAST PAYMENT METHOD SELECTION CARDS
       ========================================================================= */
    .payment-method-card {
        border: 1.5px solid var(--border-color) !important;
        color: var(--text-main) !important;
        background-color: var(--bg-card) !important;
        transition: all 0.2s ease-in-out !important;
        cursor: pointer;
    }
    .payment-method-card:hover {
        border-color: var(--brand-accent) !important;
        background-color: rgba(25, 211, 140, 0.04) !important;
        color: var(--text-main) !important;
    }
    .payment-method-card i {
        color: var(--brand-accent) !important;
        transition: color 0.2s ease;
    }
    .btn-check:checked + label.payment-method-card {
        background-color: rgba(25, 211, 140, 0.08) !important;
        border-color: var(--brand-accent) !important;
        border-width: 2px !important;
        box-shadow: 0 0 14px rgba(25, 211, 140, 0.15) !important;
    }
    .btn-check:checked + label.payment-method-card .option-title {
        color: var(--brand-accent) !important;
    }
    .btn-check:checked + label.payment-method-card i {
        color: var(--brand-accent) !important;
    }
    .btn-check:disabled + label.payment-method-card {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }

    /* Redesigned action buttons */
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

    /* Service modal theming */
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
@endsection