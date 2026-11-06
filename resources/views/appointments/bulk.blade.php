<!-- resources/views/appointments/bulk.blade.php -->
@extends('layouts.app')

@section('title', 'Create Bulk Appointment')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11 col-xl-11 text-start animate-page">
        {{-- Unified 3-Step Wizard Container --}}
        <div class="card p-0 border-secondary bg-card shadow-lg overflow-hidden" style="border-color: var(--border-color) !important; border-radius: 16px;">
            <div class="row g-0 align-items-stretch">
                {{-- LEFT PANEL: WIZARD FLOW (Col 8) --}}
                <div class="col-md-8 border-end border-secondary border-opacity-25 p-4 p-md-5" style="border-color: var(--border-color) !important;">
                    <form id="bulkForm" action="{{ route('appointments.bulk.manual') }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf
                        <input type="hidden" name="organization_name" id="hidden_org">
                        <input type="hidden" name="organization_id" id="hidden_org_id">
                        <input type="hidden" name="use_recognized_org" id="hidden_use_recognized_org" value="0">
                        <input type="hidden" name="appointment_date" id="hidden_date">

                        {{-- STEP 2: ORGANIZATION & START DATE --}}
                        <div class="wiz-section" id="page-2">
                            <div class="mb-4">
                                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Organization & Schedule</h3>
                                <p class="text-secondary small">Provide your requesting entity details and global booking start date.</p>
                            </div>

                            {{-- Direct Navigation Link to Organization Hub --}}
                            <div class="card p-3 p-sm-4 rounded-3 border mb-4 text-start d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
                                <div class="pe-sm-2">
                                    <span class="fw-bold text-main d-block mb-1 fs-6">Booking for a Company, School, or Organization?</span>
                                    <span class="text-muted small d-block" style="line-height: 1.5;">Manage institutional profiles, propose tailored packages, and maintain reusable rosters in the Organization Hub.</span>
                                </div>
                                <a href="{{ route('organization.index') }}" class="btn btn-sm btn-outline-accent py-2 px-3 fw-bold uppercase flex-shrink-0" style="font-size: 0.75rem; border-radius: 8px;">
                                    Organization Hub &rarr;
                                </a>
                            </div>

                            {{-- Option to Use Recognized Organization (Rendered only if user has approved orgs) --}}
                            @if($approvedOrgs->isNotEmpty())
                                <div class="card p-3 p-sm-4 border-accent bg-card shadow-sm rounded-3 mb-4" style="background-color: rgba(25, 211, 140, 0.04); border: 1.5px solid var(--brand-accent) !important;">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div class="flex-grow-1 min-w-0 pe-2">
                                            <label class="form-check-label fw-bold text-main d-flex align-items-center gap-2 cursor-pointer mb-1" for="toggle_use_org" style="font-size: 0.95rem;">
                                                <i class="bi bi-patch-check-fill text-accent fs-5 flex-shrink-0"></i>
                                                <span>Book as Recognized Organization</span>
                                            </label>
                                            <small class="text-muted d-block ps-4 ms-1" style="font-size: 0.8rem; line-height: 1.45;">
                                                Select from your verified organizations to unlock exclusive packages, pre-recorded rosters, and result consolidation.
                                            </small>
                                        </div>
                                        <div class="form-check form-switch m-0 p-0 flex-shrink-0">
                                            <input class="form-check-input m-0 cursor-pointer" type="checkbox" id="toggle_use_org" value="1" onchange="handleUseOrgToggle(this)" style="width: 2.85rem; height: 1.5rem; float: none;">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="row g-3">
                                {{-- Organization Field: Text Input for Normal Bulk; Dropdown for Recognized Org --}}
                                <div class="col-12">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Organization / Company Name <span class="text-danger">*</span></label>
                                    
                                    {{-- A. Normal Bulk: Standard Text Input --}}
                                    <input type="text" id="master_org" class="form-control py-3 fw-bold shadow-none" placeholder="Enter Requesting Entity (e.g. Acme Corp, St. Jude School)..." oninput="handleMasterOrgTextInput(this)">
                                    
                                    {{-- B. Recognized Org: Dropdown Selector --}}
                                    @if($approvedOrgs->isNotEmpty())
                                        <select id="master_org_select" class="form-select py-3 fw-bold shadow-none d-none" onchange="handleOrgDropdownChange(this)">
                                            <option value="" disabled selected>-- Select Approved Organization --</option>
                                            @foreach($approvedOrgs as $org)
                                                <option value="{{ $org->id }}" data-name="{{ $org->name }}">{{ $org->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>

                                <div class="col-12">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Preferred Start Date <span class="text-danger">*</span></label>
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

                        {{-- STEP 3: FORM ENTRY, ROSTER TOOLS OR TEMPLATE UPLOAD --}}
                        <div class="wiz-section d-none" id="page-3">
                            <div class="mb-4">
                                <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 3: Spreadsheet Compilation</h3>
                                <p class="text-secondary small">Add patient rows manually, load saved organization rosters, or upload templates.</p>
                            </div>

                            {{-- Form Entry vs Template Upload sub-navigation --}}
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-3 flex-wrap gap-2">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn-custom btn-accent px-4 py-2 fw-bold btn-sm" id="btn-manual" onclick="switchTab('manual')">
                                        FORM ENTRY
                                    </button>
                                    <button type="button" class="btn-custom btn-outline-accent px-4 py-2 border-0 fw-bold btn-sm" id="btn-excel" onclick="switchTab('excel')">
                                        TEMPLATE UPLOAD
                                    </button>
                                </div>

                                {{-- Roster Management Tools (Rendered ONLY when Recognized Organization mode is active) --}}
                                <div id="roster_tools_container" class="d-none gap-2 align-items-center flex-wrap">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-secondary bg-opacity-10 border text-muted small py-1.5" style="border-color: var(--border-color) !important;">
                                            <i class="bi bi-people-fill text-accent"></i>
                                        </span>
                                        <select id="saved_roster_select" class="form-select form-select-sm py-1.5 small" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" onchange="loadSelectedRoster(this.value)">
                                            <option value="" disabled selected>-- Load Saved Roster --</option>
                                        </select>
                                    </div>
                                </div>
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
                                            <i class="bi bi-info-circle me-1"></i> Required: Upload a PDF or image copy of your transaction receipt to finalize.
                                        </small>
                                    </div>
                                </div>

                                {{-- Clinical Agreements --}}
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
                <div class="col-md-4 bg-secondary bg-opacity-10 p-4 p-md-5 border-start border-secondary border-opacity-10" style="border-color: var(--border-color) !important;">
                    @include('appointments.partials.bulk.summary')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODULAR ROW DUPLICATION MODAL --}}
<div class="modal fade" id="duplicateRowModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-copy fs-5"></i>
                    <span>Duplicate Patient Row</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Select which non-personal details to duplicate. Personal identity fields (Name, Birthdate, Phone, Email) will remain blank for the new patient.
                </p>
                <div class="d-flex flex-column gap-2.5 mb-4">
                    <div class="form-check p-3 rounded border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="dup_tests" checked>
                        <label class="form-check-label text-main small fw-bold cursor-pointer" for="dup_tests">
                            Diagnostic Tests & Packages
                        </label>
                    </div>
                    <div class="form-check p-3 rounded border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="dup_address" checked>
                        <label class="form-check-label text-main small fw-bold cursor-pointer" for="dup_address">
                            Address (Street, Barangay, City, Province)
                        </label>
                    </div>
                    <div class="form-check p-3 rounded border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="dup_schedule" checked>
                        <label class="form-check-label text-main small fw-bold cursor-pointer" for="dup_schedule">
                            Schedule & Slot (Cascades automatically if full)
                        </label>
                    </div>
                    <div class="form-check p-3 rounded border" style="border-color: var(--border-color) !important;">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="dup_personal">
                        <label class="form-check-label text-muted small cursor-pointer" for="dup_personal">
                            Personal Details (Leave unchecked for next individual)
                        </label>
                    </div>
                </div>
                <input type="hidden" id="source_row_index_to_duplicate" value="">
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-custom btn-accent py-2 px-4 fw-bold" onclick="executeModularDuplication()">
                    Duplicate Row
                </button>
            </div>
        </div>
    </div>
</div>

{{-- VALIDATION ALERT MODAL --}}
<div class="modal fade" id="wizardValidationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-accent display-4 d-block"></i>
            </div>
            <h5 class="text-main fw-bold mb-2 uppercase tracking-tighter" id="wizardValidationTitle">Omissions Found</h5>
            <div id="wizardValidationMsg" class="text-secondary small mb-4">Please fill in all required fields and complete your selections before proceeding.</div>
            <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" data-bs-dismiss="modal">UNDERSTOOD</button>
        </div>
    </div>
</div>

{{-- SERVICES & PACKAGES SELECTION MODAL --}}
<div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-secondary bg-card">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalLabel">
                    <i class="bi bi-flask-fill me-2"></i>Select Diagnostic Packages & Tests
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                {{-- Search Input --}}
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="serviceSearch" class="form-control shadow-none" placeholder="Search tests or packages by name..." oninput="filterModalServices(this.value)">
                    </div>
                </div>

                {{-- Sub-Tabs: Packages vs Individual Services --}}
                <ul class="nav nav-pills mb-4 gap-2 border-bottom border-secondary border-opacity-10 pb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold px-4 py-2 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#modal-pane-packages">
                            Diagnostic Packages ({{ $packages->count() }})
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold px-4 py-2 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#modal-pane-services">
                            Individual Tests ({{ $services->count() }})
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- 1. PACKAGES LIST (With Anti-Conflict Protection and Org-Package Filtering) --}}
                    <div class="tab-pane fade show active" id="modal-pane-packages">
                        <div class="row g-3" id="packageListContainer" style="max-height: 400px; overflow-y: auto;">
                            @foreach($packages as $pkg)
                                <div class="col-md-6 modal-item-row pkg-item-card {{ $pkg->organization_id ? 'org-exclusive-pkg d-none' : 'global-pkg' }}" 
                                     data-name="{{ strtoupper($pkg->name) }}" 
                                     data-gender="{{ $pkg->gender_restriction }}"
                                     data-org-id="{{ $pkg->organization_id ?? '' }}">
                                    <div class="form-check p-3 rounded border h-100 modal-selectable-card" id="pkg_wrapper_{{ $pkg->id }}" style="background-color: var(--bg-card); border-color: var(--border-color) !important; transition: all 0.2s ease;">
                                        <input class="form-check-input ms-0 me-2 pkg-chk-item modal-item-checkbox" type="checkbox" value="{{ $pkg->id }}" data-price="{{ $pkg->price }}" data-label="{{ $pkg->name }}" data-services="{{ json_encode($pkg->services->pluck('id')->toArray()) }}" id="pkg_chk_{{ $pkg->id }}" onchange="handlePackageCheckboxChange(this)">
                                        <label class="form-check-label text-main small cursor-pointer w-100" for="pkg_chk_{{ $pkg->id }}">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    <span class="fw-bold d-block text-main">{{ strtoupper($pkg->name) }}</span>
                                                    @if($pkg->organization_id)
                                                        <span class="badge bg-success text-white border border-success uppercase" style="font-size: 0.58rem;">ORG EXCLUSIVE</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-danger text-white border border-danger pkg-conflict-badge d-none smaller" style="font-size: 0.6rem;">Conflicting</span>
                                                    <span class="text-accent">&#x20B1;<span class="fw-bold">{{ number_format($pkg->price, 2) }}</span></span>
                                                </div>
                                            </div>
                                            <div class="text-muted smaller" style="font-size: 0.72rem;">
                                                Includes: {{ $pkg->services->pluck('name')->implode(', ') }}
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 2. INDIVIDUAL SERVICES LIST (With Anti-Double Selection) --}}
                    <div class="tab-pane fade" id="modal-pane-services">
                        <div class="row g-3" id="serviceListContainer" style="max-height: 400px; overflow-y: auto;">
                            @foreach($services as $service)
                                <div class="col-md-6 modal-item-row" data-name="{{ strtoupper($service->name) }}" data-gender="{{ $service->gender_restriction }}">
                                    <div class="form-check p-3 rounded border h-100 modal-selectable-card" id="service_wrapper_{{ $service->id }}" style="background-color: var(--bg-card); border-color: var(--border-color) !important; transition: all 0.2s ease;">
                                        <input class="form-check-input ms-0 me-2 svc-chk-item modal-item-checkbox" type="checkbox" value="{{ $service->id }}" data-price="{{ $service->price }}" data-label="{{ $service->name }}" id="service_chk_{{ $service->id }}">
                                        <label class="form-check-label text-main small cursor-pointer w-100" for="service_chk_{{ $service->id }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold d-block text-main">{{ strtoupper($service->name) }}</span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-warning text-dark border border-warning pkg-covered-badge d-none smaller" style="font-size: 0.6rem;">In Package</span>
                                                    <span class="text-accent">&#x20B1;<span class="fw-bold">{{ number_format($service->price, 2) }}</span></span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                <div class="small text-muted" id="modal_selection_counter">0 selected</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                    <button type="button" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase" onclick="applyServices()">APPLY SELECTIONS</button>
                </div>
            </div>
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

    /* =========================================================================
       PRONOUNCED ROW SELECTION & CHECKBOX STYLING (SPREADSHEET TABLE)
       ========================================================================= */
    #rowContainer tr {
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }
    #rowContainer tr.row-selected {
        background-color: rgba(25, 211, 140, 0.09) !important;
        box-shadow: inset 4px 0 0 var(--brand-accent) !important;
    }
    #rowContainer tr.row-selected td {
        background-color: transparent !important;
    }

    /* High-Visibility Checkboxes for Table & Modal */
    .row-select-chk, #selectAllRows {
        width: 1.45rem !important;
        height: 1.45rem !important;
        cursor: pointer;
        border: 2px solid var(--border-color) !important;
        border-radius: 6px !important;
        background-color: var(--bg-card) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .row-select-chk:hover, #selectAllRows:hover {
        border-color: var(--brand-accent) !important;
        transform: scale(1.05);
    }
    .row-select-chk:checked, #selectAllRows:checked {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%231c232d' stroke-linecap='round' stroke-linejoin='round' stroke-width='3.2' d='m5 10.2 3.5 3.5 7-7'/%3e%3c/svg%3e") !important;
        box-shadow: 0 0 10px rgba(25, 211, 140, 0.45) !important;
    }

    /* =========================================================================
       PRONOUNCED SERVICE & PACKAGE SELECTION CARDS (MODAL)
       ========================================================================= */
    .modal-selectable-card {
        border: 1.5px solid var(--border-color) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .modal-selectable-card:has(input:checked),
    .modal-selectable-card.is-selected {
        border: 2px solid var(--brand-accent) !important;
        background-color: rgba(25, 211, 140, 0.09) !important;
        box-shadow: 0 0 14px rgba(25, 211, 140, 0.22) !important;
        transform: translateY(-1px);
    }
    .modal-item-checkbox {
        width: 1.35rem !important;
        height: 1.35rem !important;
        cursor: pointer;
        border: 2px solid var(--border-color) !important;
        border-radius: 6px !important;
        background-color: var(--bg-card) !important;
        transition: all 0.2s ease-in-out;
    }
    .modal-item-checkbox:hover {
        border-color: var(--brand-accent) !important;
    }
    .modal-item-checkbox:checked {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%231c232d' stroke-linecap='round' stroke-linejoin='round' stroke-width='3.2' d='m5 10.2 3.5 3.5 7-7'/%3e%3c/svg%3e") !important;
        box-shadow: 0 0 8px rgba(25, 211, 140, 0.45) !important;
    }

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
</style>

@include('layouts.partials.lightbox-overlay')
@include('appointments.partials.bulk.scripts-patient')
@include('appointments.partials.bulk.scripts')
@endsection