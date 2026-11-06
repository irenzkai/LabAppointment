<!-- resources/views/appointments/resubmit.blade.php -->
@extends('layouts.app')
@section('title', 'Resubmit Appointment #' . $appointment->id)
@section('content')
@php
    $isBulk = !is_null($appointment->batch_id);
    $isDependent = $appointment->dependent_id !== null;
    $isBulkCashless = $isBulk && ($appointment->payment_method === 'Cashless');
    $isVoidedRecord = $isExpired || $appointment->isVoided();
    $patientSex = strtolower($appointment->patient_sex ?? 'both');

    // Evaluate staff suggestions if present
    $suggested = $appointment->suggested_changes ?? [];
    $hasSuggestions = !empty($suggested);
    $useSuggestions = request()->has('use_suggestions') 
        ? request()->boolean('use_suggestions') 
        : $hasSuggestions;

    if ($useSuggestions && $hasSuggestions) {
        $selectedDate = old('appointment_date', $suggested['appointment_date'] ?? ($appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : ''));
        $selectedTime = old('time_slot', $suggested['time_slot'] ?? $appointment->time_slot);
        $selectedServiceIds = old('service_ids', $suggested['service_ids'] ?? $appointment->services->pluck('id')->toArray());
        $selectedPackageIds = old('package_ids', $suggested['package_ids'] ?? $appointment->packages->pluck('id')->toArray());
    } else {
        $selectedDate = old('appointment_date', $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : '');
        $selectedTime = old('time_slot', $appointment->time_slot);
        $selectedServiceIds = old('service_ids', $appointment->services->pluck('id')->toArray());
        $selectedPackageIds = old('package_ids', $appointment->packages->pluck('id')->toArray());
    }

    $selectedPaymentMethod = old('payment_method', $appointment->payment_method ?? 'Cash');
@endphp

<div class="container text-start animate-page py-4" id="resubmit-page-root">
    {{-- BREADCRUMB & HEADER ACTION BAR --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                Resubmit Appointment #{{ $appointment->id }}
            </h2>
            <p class="text-secondary mb-0 small">
                @if($isBulk)
                    Update preferred schedule or tests for corporate batch <strong>{{ $appointment->organization_name }} (Batch #{{ $appointment->batch_id }})</strong>.
                @else
                    Review patient details, adjust tests or health packages, and pick a visit schedule to resubmit for clinical review.
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-reset-custom" onclick="resetResubmitForm()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Edits
            </button>
            <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-secondary px-4 py-2 fw-bold text-uppercase text-decoration-none btn-cancel-custom" style="font-size: 0.75rem;">
                <i class="bi bi-arrow-left me-1"></i> Back to Appointments
            </a>
        </div>
    </div>

    {{-- SUGGESTIONS CONTEXT BANNER --}}
    @if($hasSuggestions)
        @if($useSuggestions)
            <div class="alert alert-clinical border-accent bg-accent bg-opacity-10 p-3 rounded-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-magic text-accent fs-4"></i>
                    <div>
                        <strong class="text-main d-block small uppercase" style="letter-spacing: 0.5px;">Staff Suggestions Applied</strong>
                        <span class="text-muted smaller">The schedule and test recommendations proposed by clinical staff have been loaded into this form. You may adjust them before resubmitting.</span>
                    </div>
                </div>
                <a href="{{ route('appointments.resubmit', [$appointment->id, 'use_suggestions' => 0]) }}" class="btn btn-sm btn-outline-secondary py-1 px-3 fw-bold text-nowrap" style="font-size: 0.72rem;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Use Original Details Instead
                </a>
            </div>
        @else
            <div class="alert alert-clinical border-secondary bg-secondary bg-opacity-10 p-3 rounded-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-secondary fs-4"></i>
                    <div>
                        <strong class="text-main d-block small uppercase" style="letter-spacing: 0.5px;">Using Original Booking Details</strong>
                        <span class="text-muted smaller">You are currently editing your original booking parameters. Staff suggestions are not applied.</span>
                    </div>
                </div>
                <a href="{{ route('appointments.resubmit', [$appointment->id, 'use_suggestions' => 1]) }}" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold text-nowrap" style="font-size: 0.72rem;">
                    <i class="bi bi-magic me-1"></i>Apply Staff Suggestions
                </a>
            </div>
        @endif
    @endif

    {{-- STATUS CONTEXT ALERTS --}}
    @if($appointment->status === 'returned' && $appointment->return_reason)
        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-4 rounded-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-exclamation-octagon-fill fs-3 me-3 text-danger"></i>
                <div>
                    <h5 class="fw-bold uppercase mb-0">Action Required: Correction Requested by Staff</h5>
                    <small class="text-danger-50">Please review the reason below and update the necessary details before resubmitting.</small>
                </div>
            </div>
            <div class="p-3 bg-card border border-danger border-opacity-25 rounded-3 mt-2">
                <strong class="d-block text-danger small uppercase mb-1">Reason for Return:</strong>
                <p class="mb-0 text-main italic">"{{ $appointment->return_reason }}"</p>
            </div>
        </div>
    @elseif($appointment->status === 'canceled')
        <div class="alert alert-clinical border-warning p-4 rounded-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
                <div>
                    <h5 class="fw-bold uppercase mb-0 text-warning">Reactivating Canceled Appointment</h5>
                    <small class="text-secondary">This appointment was previously canceled.</small>
                </div>
            </div>
            @if($appointment->cancellation_reason || $appointment->return_reason)
                <div class="p-3 bg-card border border-warning border-opacity-25 rounded-3 mt-2 mb-2">
                    <strong class="d-block text-warning small uppercase mb-1">Reason for Cancellation:</strong>
                    <p class="mb-0 text-main italic">"{{ $appointment->cancellation_reason ?: $appointment->return_reason }}"</p>
                </div>
            @endif
            <p class="mb-0 small text-main mt-1">
                Submitting this form will update your visit schedule and place your record back into the active processing queue.
                @if(!$isBulk && $appointment->payment_status === 'paid')
                    <br><strong class="text-warning">Payment Rollover:</strong> Your previously confirmed payment will be automatically rolled over to cover this resubmission. No new payment receipt is required.
                @endif
            </p>
        </div>
    @elseif($isVoidedRecord)
        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-4 rounded-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-clock-history fs-3 me-3 text-danger"></i>
                <div>
                    <h5 class="fw-bold uppercase mb-0 text-danger">Rescheduling Voided Appointment</h5>
                    <small class="text-danger-50">Appointment updated to VOIDED due to patient not arriving on scheduled time without prior notice.</small>
                </div>
            </div>
            <p class="mb-0 small text-main mt-1">
                Select a new preferred visit date and available time slot below to reactivate this appointment.
            </p>
        </div>
    @endif

    {{-- RESUBMISSION FORM --}}
    <form action="{{ route('appointments.update', $appointment->id) }}" method="POST" enctype="multipart/form-data" id="resubmitForm" novalidate>
        @csrf
        @method('PUT')

        {{-- Tracking flags for server-side file removals and batch payment reset --}}
        <input type="hidden" name="remove_referral" id="remove_referral" value="0">
        <input type="hidden" name="remove_receipt" id="remove_receipt" value="0">
        <input type="hidden" name="reset_batch_payment" id="reset_batch_payment" value="0">

        <div class="row g-4 align-items-stretch">
            {{-- LEFT COLUMN: PATIENT IDENTITY (READONLY VERIFIED RECORD) & REFERRAL --}}
            <div class="col-lg-6 d-flex flex-column gap-4">
                {{-- 1. Patient Identity Card (Readonly) --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100" style="border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-10">
                        <h5 class="text-accent fw-bold uppercase mb-0 small">
                            <i class="bi bi-person-bounding-box me-2"></i>1. Verified Patient Record
                        </h5>
                        @if($isDependent)
                            <a href="{{ route('profile.edit') }}#tab-dependents" target="_blank" class="small text-accent fw-bold text-decoration-none" style="font-size: 0.72rem;">
                                <i class="bi bi-pencil-square me-1"></i>Edit Dependent Details
                            </a>
                        @elseif(!$isBulk)
                            <a href="{{ route('profile.edit') }}" target="_blank" class="small text-accent fw-bold text-decoration-none" style="font-size: 0.72rem;">
                                <i class="bi bi-pencil-square me-1"></i>Edit Profile Settings
                            </a>
                        @endif
                    </div>

                    <div class="row g-3">
                        {{-- Patient Name --}}
                        <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                            <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Patient Name</label>
                            <div class="fw-bold text-main fs-6 text-truncate">{{ strtoupper($appointment->patient_name) }}</div>
                        </div>

                        {{-- Age & Biological Classification --}}
                        <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                            <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Age & Classification</label>
                            <div class="fw-bold text-main fs-6">
                                {{ $appointment->patient_age !== 'N/A' ? $appointment->patient_age . ' YRS OLD' : 'AGE UNKNOWN' }}
                                <span class="text-muted mx-1">&bull;</span>
                                {{ strtoupper($appointment->patient_sex) }}
                            </div>
                        </div>

                        {{-- Contact Phone --}}
                        <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                            <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Contact Number</label>
                            <div class="fw-bold text-main fs-6">
                                <i class="bi bi-telephone text-accent me-1"></i>{{ $appointment->patient_phone }}
                            </div>
                        </div>

                        {{-- Digital Results Email --}}
                        <div class="col-md-6 border-bottom border-secondary border-opacity-10 pb-2">
                            <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Account Email</label>
                            <div class="fw-bold text-main fs-6 text-truncate">
                                <i class="bi bi-envelope text-accent me-1"></i>{{ $appointment->patient_email ?: ($appointment->user?->email ?? 'N/A') }}
                            </div>
                        </div>

                        {{-- Registered Residential Address --}}
                        <div class="col-12 pt-1">
                            <label class="small text-secondary fw-bold mb-1 uppercase fs-x-small">Registered Clinical Address</label>
                            <div class="fw-bold text-main small">
                                <i class="bi bi-geo-alt text-accent me-1"></i>{{ strtoupper($appointment->patient_address) }}
                            </div>
                        </div>

                        @if($isBulk)
                            <div class="col-12 pt-2 border-top border-secondary border-opacity-10">
                                <span class="badge bg-secondary bg-opacity-15 text-main border border-secondary border-opacity-25 px-2.5 py-1 small rounded uppercase fw-bold">
                                    <i class="bi bi-buildings me-1 text-accent"></i>BATCH #{{ $appointment->batch_id }} &bull; {{ $appointment->organization_name }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 2. Doctor's Referral Note Card (Omitted for Bulk) --}}
                @if(!$isBulk)
                    <div class="card p-4 border-secondary bg-card shadow-sm" style="border-color: var(--border-color) !important;">
                        <h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                            <i class="bi bi-file-earmark-medical me-2"></i>2. Doctor's Referral / Note (Optional)
                        </h5>

                        @if($appointment->referral_note)
                            <div id="existing_referral_box" class="p-3 border rounded border-secondary border-opacity-25 bg-secondary bg-opacity-10 mb-3 d-flex justify-content-between align-items-center">
                                <span class="small text-main">
                                    <i class="bi bi-paperclip text-accent me-2"></i>Existing Referral Note on File
                                </span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-accent" onclick="openFilePreview('{{ Storage::url($appointment->referral_note) }}', 'Doctor\'s Referral Note')">View File</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExistingReferral()">Remove</button>
                                </div>
                            </div>
                        @endif

                        <div id="referral_preview_container" class="d-none mb-3 p-3 border rounded border-secondary border-opacity-25 bg-secondary bg-opacity-10 d-flex justify-content-between align-items-center">
                            <span class="small text-accent fw-bold" id="referral_file_label">
                                <i class="bi bi-file-earmark-check-fill me-2"></i>Selected File
                            </span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-accent" id="btn_view_referral">View File</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeUploadedReferral()">Remove</button>
                            </div>
                        </div>

                        <div id="referral_upload_wrapper" class="{{ $appointment->referral_note ? 'd-none' : '' }}">
                            <input type="file" name="referral_note" id="referral_note" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReferralUpload(this)">
                            <small class="text-muted d-block mt-1">Accepted formats: PDF or image files (Max: 10MB).</small>
                            <div class="invalid-feedback d-none" id="err_referral_note"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT COLUMN: SERVICES, SCHEDULE & PAYMENT --}}
            <div class="col-lg-6 d-flex flex-column gap-4">
                {{-- 3. Diagnostic Tests & Packages Selection Card --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100" style="border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3" style="border-color: var(--border-color) !important;">
                        <h5 class="text-accent fw-bold uppercase mb-0 small">
                            <i class="bi bi-flask me-2"></i>3. Select Tests & Packages
                        </h5>
                        @if($isBulkCashless)
                            <span class="badge bg-secondary bg-opacity-25 text-main small" id="tests_lock_status_badge">
                                <i class="bi bi-lock-fill text-warning me-1"></i>Locked
                            </span>
                        @endif
                    </div>

                    {{-- Lock warning and unlock button for cashless bulk bookings --}}
                    @if($isBulkCashless)
                        <div class="alert alert-clinical border-warning p-2.5 rounded-3 mb-3 d-flex align-items-center justify-content-between gap-2" id="bulk_tests_lock_banner">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-lock-fill text-warning fs-5"></i>
                                <div class="smaller text-main">
                                    <strong>Tests Locked:</strong> Part of Cashless Batch #{{ $appointment->batch_id }}. Modifying tests will alter batch billing.
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-warning fw-bold text-nowrap" id="btn_unlock_tests" data-bs-toggle="modal" data-bs-target="#confirmUnlockTestsModal">
                                <i class="bi bi-unlock-fill me-1"></i>Change Tests
                            </button>
                        </div>
                        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-2.5 rounded-3 mb-3 d-none" id="bulk_tests_unlocked_banner">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <span class="smaller fw-bold">Notice: Tests Unlocked. Modifying services will reset the entire Batch #{{ $appointment->batch_id }} payment status to UNPAID.</span>
                        </div>
                    @endif

                    {{-- Search Filter --}}
                    <div class="mb-3">
                        <input type="text" id="resubmit_catalog_search" class="form-control form-control-sm" placeholder="Search tests or packages..." onkeyup="filterResubmitCatalog()">
                    </div>

                    {{-- Sub-Tabs: Packages vs Individual Tests --}}
                    <ul class="nav nav-pills mb-2 gap-2 border-bottom border-secondary border-opacity-10 pb-2 flex-wrap" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold px-3 py-1 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#resubmit-pane-packages" type="button" role="tab" style="font-size: 0.72rem;">
                                <i class="bi bi-box-seam me-1"></i> Packages ({{ $packages->count() }})
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold px-3 py-1 uppercase btn-sm" data-bs-toggle="pill" data-bs-target="#resubmit-pane-services" type="button" role="tab" style="font-size: 0.72rem;">
                                <i class="bi bi-flask me-1"></i> Individual Tests ({{ $services->count() }})
                            </button>
                        </li>
                    </ul>

                    <div class="position-relative">
                        <div class="tab-content mb-3" id="resubmitCatalogTabsContent" style="{{ $isBulkCashless ? 'pointer-events: none;' : '' }}">
                            {{-- Pane 1: Health Packages --}}
                            <div class="tab-pane fade show active" id="resubmit-pane-packages" role="tabpanel">
                                <div class="p-2 border rounded custom-scroll" id="packages_container" style="max-height: 220px; overflow-y: auto; background-color: var(--bg-main) !important; border: 1.5px solid var(--border-color) !important;">
                                    @php $resubmitPkgCount = 0; @endphp
                                    @forelse($packages as $pkg)
                                        @php $pkgGender = strtolower($pkg->gender_restriction ?? 'both'); @endphp
                                        @if($pkgGender !== 'both' && $pkgGender !== $patientSex)
                                            @continue
                                        @endif
                                        @php $resubmitPkgCount++; @endphp
                                        <div class="form-check p-2 mb-1.5 border rounded bg-card d-flex align-items-center justify-content-between gap-2 resubmit-catalog-item package-item-wrapper" id="resubmit_pkg_wrap_{{ $pkg->id }}" data-name="{{ strtoupper($pkg->name) }}" data-gender="{{ $pkgGender }}" style="transition: all 0.2s ease;">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                <input class="form-check-input ms-0 me-1 pkg-checkbox" type="checkbox" name="package_ids[]" value="{{ $pkg->id }}" id="resubmit_pkg_{{ $pkg->id }}" data-price="{{ $pkg->price }}" data-services="{{ json_encode($pkg->services->pluck('id')->toArray()) }}" {{ in_array($pkg->id, $selectedPackageIds) ? 'checked' : '' }} onchange="onResubmitCatalogChange()">
                                                <label class="form-check-label text-main small cursor-pointer text-truncate fw-bold mb-0" for="resubmit_pkg_{{ $pkg->id }}">
                                                    {{ strtoupper($pkg->name) }}
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-danger bg-opacity-15 text-white border border-danger border-opacity-30 pkg-conflict-badge d-none smaller" style="font-size: 0.6rem;">Conflicting</span>
                                                <span class="text-accent smaller fw-bold text-nowrap">₱{{ number_format($pkg->price, 2) }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted smaller text-center py-3">No diagnostic packages available.</div>
                                    @endforelse
                                    @if($resubmitPkgCount === 0)
                                        <div class="text-muted smaller text-center py-3">No diagnostic packages available for this demographic profile.</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Pane 2: Individual Tests --}}
                            <div class="tab-pane fade" id="resubmit-pane-services" role="tabpanel">
                                <div class="p-2 border rounded custom-scroll" id="services_container" style="max-height: 220px; overflow-y: auto; background-color: var(--bg-main) !important; border: 1.5px solid var(--border-color) !important;">
                                    @php $resubmitSvcCount = 0; @endphp
                                    @forelse($services as $s)
                                        @php $sGender = strtolower($s->gender_restriction ?? 'both'); @endphp
                                        @if($sGender !== 'both' && $sGender !== $patientSex)
                                            @continue
                                        @endif
                                        @php $resubmitSvcCount++; @endphp
                                        <div class="form-check p-2 mb-1.5 border rounded bg-card d-flex align-items-center justify-content-between gap-2 resubmit-catalog-item service-item-wrapper" id="resubmit_svc_wrap_{{ $s->id }}" data-name="{{ strtoupper($s->name) }}" data-gender="{{ $sGender }}" style="transition: all 0.2s ease;">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                <input class="form-check-input ms-0 me-1 service-checkbox" type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="resubmit_svc_{{ $s->id }}" data-price="{{ $s->price }}" {{ in_array($s->id, $selectedServiceIds) ? 'checked' : '' }} onchange="onResubmitCatalogChange()">
                                                <label class="form-check-label text-main small cursor-pointer text-truncate mb-0" for="resubmit_svc_{{ $s->id }}">
                                                    {{ strtoupper($s->name) }}
                                                </label>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-warning bg-opacity-15 text-secondary border border-warning border-opacity-30 pkg-covered-badge d-none smaller" style="font-size: 0.6rem;">In Package</span>
                                                <span class="text-muted smaller fw-bold text-nowrap">₱{{ number_format($s->price, 2) }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted smaller text-center py-3">No individual services available.</div>
                                    @endforelse
                                    @if($resubmitSvcCount === 0)
                                        <div class="text-muted smaller text-center py-3">No individual tests available for this demographic profile.</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Visual overlay for locked state --}}
                        @if($isBulkCashless)
                            <div id="services_lock_overlay" class="position-absolute top-0 start-0 w-100 h-100 rounded d-flex align-items-center justify-content-center" style="background-color: rgba(28, 35, 45, 0.35); backdrop-filter: blur(1px); z-index: 5;">
                                <span class="badge bg-dark border border-warning text-white px-3 py-2 shadow"><i class="bi bi-lock-fill text-warning me-1"></i> Locked to Batch Settlement</span>
                            </div>
                        @endif
                    </div>

                    <div class="invalid-feedback d-none mb-3 text-danger fw-bold" id="err_service_ids"></div>

                    <div class="d-flex justify-content-between align-items-center p-3 rounded bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
                        <span class="fw-bold uppercase small text-secondary">Total Estimated Bill:</span>
                        <span class="text-accent fs-5 fw-bold" id="total_bill_display">₱0.00</span>
                    </div>
                </div>

                {{-- 4. Preferred Visit Schedule Card --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100" style="border-color: var(--border-color) !important;">
                    <h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-calendar3 me-2"></i>4. Preferred Visit Schedule
                    </h5>
                    <div class="row g-3 mb-1">
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Visit Date</label>
                            <input type="date" name="appointment_date" id="appointment_date" class="form-control py-3 shadow-none fw-bold" value="{{ $selectedDate }}" min="{{ date('Y-m-d') }}" onchange="fetchTimeSlots(this.value)" required>
                            <div class="invalid-feedback d-none" id="err_appointment_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Time Slot</label>
                            <select name="time_slot" id="time_slot" class="form-select py-3 shadow-none fw-bold" required>
                                <option value=""></option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_time_slot"></div>
                        </div>
                    </div>
                </div>

                {{-- 5. Payment Settlement (Omitted for Bulk) --}}
                @if(!$isBulk)
                    <div class="card p-4 border-secondary bg-card shadow-sm h-100" style="border-color: var(--border-color) !important;">
                        <h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                            <i class="bi bi-credit-card me-2"></i>5. Payment Settlement
                        </h5>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="Cash" {{ $selectedPaymentMethod === 'Cash' ? 'checked' : '' }} onchange="handlePaymentMethodChange(this)">
                                <label class="btn btn-outline-secondary w-100 py-3 text-center" for="pay_cash">
                                    <i class="bi bi-cash-stack fs-3 d-block mb-1"></i>
                                    <span class="fw-bold small uppercase">Cash on Site</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_cashless" value="Cashless" {{ $selectedPaymentMethod === 'Cashless' ? 'checked' : '' }} onchange="handlePaymentMethodChange(this)">
                                <label class="btn btn-outline-secondary w-100 py-3 text-center" for="pay_cashless">
                                    <i class="bi bi-qr-code-scan fs-3 d-block mb-1"></i>
                                    <span class="fw-bold small uppercase">Online / E-Wallet</span>
                                </label>
                            </div>
                        </div>

                        {{-- Cashless Upload Section --}}
                        <div id="provider_selection_container" class="{{ $selectedPaymentMethod === 'Cashless' ? '' : 'd-none' }}">
                            @if($paymentProviders->count() > 0)
                                <div class="mb-4">
                                    <label class="small text-secondary fw-bold mb-2 uppercase d-block">Choose E-Wallet Provider</label>
                                    <div class="row g-2">
                                        @foreach($paymentProviders as $provider)
                                            <div class="col-6">
                                                <input type="radio" class="btn-check provider-radio" name="payment_provider_id" id="provider_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" onchange="handleProviderChange(this)">
                                                <label class="btn btn-outline-secondary w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="provider_{{ $provider->id }}">
                                                    @if($provider->logo)
                                                        <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" class="mb-2" style="height: 28px; object-fit: contain;">
                                                    @else
                                                        <i class="bi bi-wallet2 fs-3 mb-2 text-secondary"></i>
                                                    @endif
                                                    <span class="small fw-bold uppercase text-main">{{ $provider->name }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- QR Code Display Box --}}
                                <div id="qr_section" class="mb-4 d-none">
                                    <div class="p-3 border border-secondary border-opacity-25 rounded-3 text-center" style="background-color: rgba(108, 117, 125, 0.05);">
                                        <small class="text-main fw-bold mb-2 d-block uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            Scan to Pay (<span id="selected_provider_name" class="text-accent"></span>)
                                        </small>
                                        <div class="d-flex justify-content-center">
                                            <div class="bg-white p-2 rounded shadow-sm border" style="cursor: zoom-in;" onclick="openFilePreview(document.getElementById('selected_provider_qr').src, document.getElementById('selected_provider_name').innerText + ' QR Code')">
                                                <img src="" id="selected_provider_qr" alt="Scan QR" style="width: 140px; height: 140px; object-fit: contain;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Existing Receipt Container --}}
                            @if($appointment->payment_receipt && $appointment->status !== 'canceled')
                                <div id="existing_receipt_box" class="p-3 border rounded border-secondary border-opacity-25 bg-secondary bg-opacity-10 mb-3 d-flex justify-content-between align-items-center">
                                    <span class="small text-main">
                                        <i class="bi bi-receipt text-accent me-2"></i>Proof of Payment on File
                                    </span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-accent" onclick="openFilePreview('{{ Storage::url($appointment->payment_receipt) }}', 'Proof of Payment Receipt')">View Receipt</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExistingReceipt()">Remove</button>
                                    </div>
                                </div>
                            @endif

                            <div id="receipt_preview_container" class="d-none mb-3 p-3 border rounded border-secondary border-opacity-25 bg-secondary bg-opacity-10 d-flex justify-content-between align-items-center">
                                <span class="small text-accent fw-bold" id="receipt_file_label">
                                    <i class="bi bi-file-earmark-check-fill me-2"></i>Selected Receipt File
                                </span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-accent" id="btn_view_receipt">View Receipt</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeUploadedReceipt()">Remove</button>
                                </div>
                            </div>

                            {{-- Receipt File Upload Input --}}
                            <div id="receipt_upload_wrapper" class="{{ ($appointment->payment_receipt && $appointment->status !== 'canceled' && empty(old('remove_receipt'))) ? 'd-none' : '' }}">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment</label>
                                <input type="file" name="payment_receipt" id="payment_receipt" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReceiptUpload(this)">
                                <div class="invalid-feedback d-none" id="err_payment_receipt"></div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Informational Notice for Bulk Appointment Batch Billing --}}
                    <div class="card p-3 border-accent bg-card shadow-sm" style="border-left: 4px solid var(--brand-accent) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-accent fs-4" style="color: var(--brand-accent) !important;"></i>
                            <div>
                                <div class="fw-bold text-main small uppercase">Batch Billing Policy</div>
                                <small class="text-secondary">Payment and settlement are managed collectively at the organization batch level (Batch #{{ $appointment->batch_id }}). Individual receipt uploads are handled directly via the Batch Workspace.</small>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Action Submission Buttons --}}
        <div class="d-flex gap-3 mt-4">
            <a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-secondary w-50 py-3 fw-bold uppercase text-decoration-none text-center btn-cancel-custom" style="font-size: 0.85rem;">
                Cancel
            </a>
            <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="btn_submit_resubmit">
                Submit Resubmission <i class="bi bi-check-circle-fill ms-1"></i>
            </button>
        </div>
    </form>
</div>

{{-- Confirmation Modal for Unlocking Tests on Cashless Bulk Appointment --}}
@if($isBulkCashless)
    <div class="modal fade" id="confirmUnlockTestsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content shadow-lg border-warning" style="background-color: var(--bg-card); border: 1.5px solid #ffc107; color: var(--text-main);">
                <div class="modal-header py-3 border-bottom border-warning border-opacity-25">
                    <h5 class="modal-title text-warning fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <span>Modify Tests & Reset Batch Payment?</span>
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="small mb-2" style="color: var(--text-main) !important;">
                        Modifying the clinical tests for <strong>{{ $appointment->patient_name }}</strong> will alter the total cost of <strong>Batch #{{ $appointment->batch_id }}</strong>.
                    </p>
                    <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-2.5 rounded-3 mb-0 smaller">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i>
                        <strong>Warning:</strong> Proceeding will <strong>reset the entire batch payment status to UNPAID</strong>. The batch submitter will be required to upload a new payment receipt for the revised batch total.
                    </div>
                </div>
                <div class="modal-footer p-3 border-top d-flex gap-2" style="border-color: var(--border-color) !important;">
                    <button type="button" class="btn-custom btn-outline-secondary flex-grow-1 py-2 fw-bold uppercase" data-bs-dismiss="modal">Keep Locked</button>
                    <button type="button" class="btn-custom btn-danger-custom flex-grow-1 py-2 fw-bold uppercase" onclick="unlockBulkTests()">Yes, Unlock Tests</button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Includes the Unified Lightbox Partial --}}
@include('layouts.partials.lightbox-overlay')
@endsection

@push('styles')
<style>
.btn-reset-custom {
    background-color: transparent !important;
    border: 1.5px solid var(--text-muted) !important;
    color: var(--text-muted) !important;
    font-size: 0.75rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 6px 14px !important;
    border-radius: 6px !important;
    transition: all 0.2s ease-in-out;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}
.btn-reset-custom:hover, .btn-reset-custom:focus {
    border-color: #ffc107 !important;
    color: #ffc107 !important;
    background-color: rgba(255, 193, 7, 0.08) !important;
    box-shadow: none !important;
}
.btn-cancel-custom {
    color: var(--text-muted) !important;
    border: 1.5px solid var(--border-color) !important;
    background-color: transparent !important;
    transition: all 0.2s ease-in-out;
}
.btn-cancel-custom:hover, .btn-cancel-custom:focus {
    color: var(--text-main) !important;
    background-color: rgba(255, 255, 255, 0.08) !important;
    border-color: var(--border-color) !important;
    box-shadow: none !important;
}
#resubmitForm .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
const savedTimeSlot = @json($selectedTime);
const appId = @json($appointment->id);
const isBulk = @json($isBulk);
const isBulkCashless = @json($isBulkCashless);
const isPaidRollover = @json($appointment->payment_status === 'paid');
const isCanceledRecord = @json($appointment->status === 'canceled');
const packagesMap = @json($packages->keyBy('id'));
const servicesMap = @json($services->keyBy('id'));
const origPackageIds = @json($selectedPackageIds);
const origServiceIds = @json($selectedServiceIds);

let lastMethod = @json($selectedPaymentMethod);
let lastProviderId = null;

// File Base64 memory caches for referral and receipt uploads
window.referralLocalData = null;
window.receiptLocalData = null;

document.addEventListener('DOMContentLoaded', async () => {
    try { loadDraftData(); } catch(e) {}
    try { onResubmitCatalogChange(); } catch(e) {}

    if (!isBulk) {
        try { initPaymentState(); } catch(e) {}
    }

    const initialDate = document.getElementById('appointment_date')?.value;
    if (initialDate) {
        fetchTimeSlots(initialDate).catch(err => console.error("Slots fetch error:", err));
    }

    document.querySelectorAll('#resubmitForm input, #resubmitForm select').forEach(input => {
        input.addEventListener('input', () => { clearFieldError(input); saveDraftData(); });
        input.addEventListener('change', () => { clearFieldError(input); saveDraftData(); });
    });

    const resubmitForm = document.getElementById('resubmitForm');
    if (resubmitForm) {
        resubmitForm.addEventListener('submit', validateResubmitForm);
    }
});

/* -------------------------------------------------------------------------- */
/* BIDIRECTIONAL ANTI-DOUBLE SELECTION & ANTI-CONFLICTING PACKAGES ENGINE     */
/* -------------------------------------------------------------------------- */
function recalculateResubmitDependencies() {
    // 1. Gather all service IDs covered by checked packages
    const coveredServiceIds = new Set();
    const activePackages = Array.from(document.querySelectorAll('.pkg-checkbox:checked'));

    activePackages.forEach(pkgCb => {
        const pkgId = pkgCb.value;
        const pkg = packagesMap[pkgId];
        if (pkg && pkg.services) {
            pkg.services.forEach(svc => coveredServiceIds.add(svc.id.toString()));
        } else if (pkgCb.dataset.services) {
            try {
                const sArr = JSON.parse(pkgCb.dataset.services);
                sArr.forEach(id => coveredServiceIds.add(id.toString()));
            } catch(e) {}
        }
    });

    // 2. Gather all individually checked services
    const checkedIndividualServices = new Set();
    document.querySelectorAll('.service-checkbox:checked').forEach(svcCb => {
        checkedIndividualServices.add(svcCb.value.toString());
    });

    // 3. Package Conflict Check (Package-to-Package and Service-to-Package)
    document.querySelectorAll('.pkg-checkbox').forEach(pkgCb => {
        const pkgId = pkgCb.value;
        const pkg = packagesMap[pkgId];
        let pkgServiceIds = [];
        if (pkg && pkg.services) {
            pkgServiceIds = pkg.services.map(s => s.id.toString());
        } else if (pkgCb.dataset.services) {
            try {
                pkgServiceIds = JSON.parse(pkgCb.dataset.services).map(String);
            } catch(e) {}
        }

        const itemWrapper = document.getElementById('resubmit_pkg_wrap_' + pkgId) || pkgCb.closest('.resubmit-catalog-item');
        const conflictBadge = itemWrapper ? itemWrapper.querySelector('.pkg-conflict-badge') : null;

        if (pkgCb.checked) {
            pkgCb.disabled = false;
            if (itemWrapper) {
                itemWrapper.classList.remove('opacity-50');
                itemWrapper.style.pointerEvents = 'auto';
                itemWrapper.style.cursor = 'default';
                itemWrapper.removeAttribute('title');
            }
            if (conflictBadge) conflictBadge.classList.add('d-none');
            return;
        }

        const conflictsWithPackage = pkgServiceIds.some(id => coveredServiceIds.has(id));
        const conflictsWithIndividual = pkgServiceIds.some(id => checkedIndividualServices.has(id));
        const isConflicting = conflictsWithPackage || conflictsWithIndividual;

        if (isConflicting) {
            pkgCb.disabled = true;
            if (itemWrapper) {
                itemWrapper.classList.add('opacity-50');
                itemWrapper.style.pointerEvents = 'none'; // BARS USER AT UI
                itemWrapper.style.cursor = 'not-allowed';
                if (conflictsWithPackage) {
                    itemWrapper.setAttribute('title', 'Conflicting package: shares tests with an already selected package.');
                } else {
                    itemWrapper.setAttribute('title', 'Conflicting package: includes an individual test that is already selected.');
                }
            }
            if (conflictBadge) conflictBadge.classList.remove('d-none');
        } else {
            pkgCb.disabled = false;
            if (itemWrapper) {
                itemWrapper.classList.remove('opacity-50');
                itemWrapper.style.pointerEvents = 'auto';
                itemWrapper.style.cursor = 'default';
                itemWrapper.removeAttribute('title');
            }
            if (conflictBadge) conflictBadge.classList.add('d-none');
        }
    });

    // 4. Individual Tests Conflict Check (Package-to-Service)
    document.querySelectorAll('.service-checkbox').forEach(svcCb => {
        const isCovered = coveredServiceIds.has(svcCb.value.toString());
        const itemWrapper = document.getElementById('resubmit_svc_wrap_' + svcCb.value) || svcCb.closest('.service-item-wrapper');
        const badge = itemWrapper ? itemWrapper.querySelector('.pkg-covered-badge') : null;

        if (isCovered) {
            svcCb.checked = false;
            svcCb.disabled = true;
            if (itemWrapper) {
                itemWrapper.classList.add('opacity-50');
                itemWrapper.style.pointerEvents = 'none'; // BARS USER AT UI
                itemWrapper.style.cursor = 'not-allowed';
                itemWrapper.setAttribute('title', 'Already included in a selected package.');
            }
            if (badge) badge.classList.remove('d-none');
        } else {
            svcCb.disabled = false;
            if (itemWrapper) {
                itemWrapper.classList.remove('opacity-50');
                itemWrapper.style.pointerEvents = 'auto';
                itemWrapper.style.cursor = 'default';
                itemWrapper.removeAttribute('title');
            }
            if (badge) badge.classList.add('d-none');
        }
    });
}

function onResubmitCatalogChange() {
    recalculateResubmitDependencies();
    updateTotal();
    clearFieldError(document.getElementById('services_container'));
    clearFieldError(document.getElementById('packages_container'));
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.pkg-checkbox:checked').forEach(cb => {
        total += parseFloat(cb.dataset.price || 0);
    });
    document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
        total += parseFloat(cb.dataset.price || 0);
    });

    const totalDisplay = document.getElementById('total_bill_display');
    if (totalDisplay) {
        totalDisplay.innerText = '₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function filterResubmitCatalog() {
    const query = document.getElementById('resubmit_catalog_search').value.toUpperCase().trim();
    document.querySelectorAll('.resubmit-catalog-item').forEach(item => {
        const name = (item.dataset.name || '').toUpperCase();
        item.style.display = (query === '' || name.includes(query)) ? '' : 'none';
    });
}

/* -------------------------------------------------------------------------- */
/* BULK CASHLESS TESTS UNLOCK ENGINE                                          */
/* -------------------------------------------------------------------------- */
window.unlockBulkTests = function() {
    const overlay = document.getElementById('services_lock_overlay');
    const container = document.getElementById('resubmitCatalogTabsContent');
    const lockBanner = document.getElementById('bulk_tests_lock_banner');
    const unlockedBanner = document.getElementById('bulk_tests_unlocked_banner');
    const badge = document.getElementById('tests_lock_status_badge');
    const resetInput = document.getElementById('reset_batch_payment');

    if (overlay) overlay.classList.add('d-none');
    if (container) container.style.pointerEvents = 'auto';
    if (lockBanner) lockBanner.classList.add('d-none');
    if (unlockedBanner) unlockedBanner.classList.remove('d-none');
    if (resetInput) resetInput.value = '1';

    if (badge) {
        badge.innerHTML = '<i class="bi bi-unlock-fill text-danger me-1"></i>Unlocked (Will Reset Batch)';
        badge.classList.remove('text-main');
        badge.classList.add('text-danger');
    }

    const modalEl = document.getElementById('confirmUnlockTestsModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }
};

/* -------------------------------------------------------------------------- */
/* REAL-TIME DRAFT AUTOSAVE & RECOVERY ENGINE                                 */
/* -------------------------------------------------------------------------- */
function saveDraftData() {
    const form = document.getElementById('resubmitForm');
    if (!form) return;

    const draft = {};
    const inputs = form.querySelectorAll('input:not([type="password"]):not([type="file"]), select, textarea');

    inputs.forEach(input => {
        if (input.name) {
            if (input.type === 'checkbox') {
                if (input.name.endsWith('[]')) {
                    if (!draft[input.name]) draft[input.name] = [];
                    if (input.checked) draft[input.name].push(input.value);
                } else {
                    draft[input.name] = input.checked;
                }
            } else if (input.type === 'radio') {
                if (input.checked) draft[input.name] = input.value;
            } else {
                draft[input.name] = input.value;
            }
        }
    });

    localStorage.setItem(`resubmit_draft_${appId}`, JSON.stringify(draft));
}

function loadDraftData() {
    try {
        const raw = localStorage.getItem(`resubmit_draft_${appId}`);
        if (!raw) return;
        const draft = JSON.parse(raw);

        for (const [key, value] of Object.entries(draft)) {
            const escKey = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(key) : key;
            if (Array.isArray(value)) {
                value.forEach(val => {
                    const escVal = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(val) : val;
                    try {
                        const cb = document.querySelector(`input[name="${escKey}"][value="${escVal}"]`);
                        if (cb) cb.checked = true;
                    } catch(e) {}
                });
            } else {
                try {
                    const el = document.querySelector(`[name="${escKey}"]`);
                    if (el) {
                        if (el.type === 'checkbox') el.checked = value;
                        else if (el.type === 'radio') {
                            const escVal = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(value) : value;
                            const targetRadio = document.querySelector(`input[name="${escKey}"][value="${escVal}"]`);
                            if (targetRadio) targetRadio.checked = true;
                        } else if (el.tagName === 'SELECT') {
                            if (value) {
                                let opt = Array.from(el.options).find(o => o.value === value || o.text === value);
                                if (!opt) {
                                    opt = document.createElement('option');
                                    opt.value = value;
                                    opt.textContent = value;
                                    el.appendChild(opt);
                                }
                                el.value = value;
                            }
                        } else {
                            el.value = value;
                        }
                    }
                } catch(e) {}
            }
        }
        recalculateResubmitDependencies();
        updateTotal();
    } catch (e) {
        console.warn("Draft restore failed:", e);
    }
}

function clearDraftData() {
    localStorage.removeItem(`resubmit_draft_${appId}`);
}

/* -------------------------------------------------------------------------- */
/* INSTANT IN-PAGE "RESET EDITS" CONTROLLER                                   */
/* -------------------------------------------------------------------------- */
async function resetResubmitForm() {
    clearDraftData();

    if (isBulkCashless) {
        const overlay = document.getElementById('services_lock_overlay');
        const container = document.getElementById('resubmitCatalogTabsContent');
        const lockBanner = document.getElementById('bulk_tests_lock_banner');
        const unlockedBanner = document.getElementById('bulk_tests_unlocked_banner');
        const badge = document.getElementById('tests_lock_status_badge');
        const resetInput = document.getElementById('reset_batch_payment');

        if (overlay) overlay.classList.remove('d-none');
        if (container) container.style.pointerEvents = 'none';
        if (lockBanner) lockBanner.classList.remove('d-none');
        if (unlockedBanner) unlockedBanner.classList.add('d-none');
        if (resetInput) resetInput.value = '0';

        if (badge) {
            badge.innerHTML = '<i class="bi bi-lock-fill text-warning me-1"></i>Locked';
            badge.classList.remove('text-danger');
            badge.classList.add('text-main');
        }
    }

    if (!isBulk) {
        document.getElementById('remove_referral').value = "0";
        const existingRefBox = document.getElementById('existing_referral_box');
        if (existingRefBox) existingRefBox.classList.remove('d-none');
        removeUploadedReferral();

        document.getElementById('remove_receipt').value = "0";
        const payCash = document.getElementById('pay_cash');
        const payCashless = document.getElementById('pay_cashless');
        if ("{{ $appointment->payment_method }}" === 'Cashless') payCashless.checked = true;
        else payCash.checked = true;
        lastMethod = "{{ $appointment->payment_method }}";

        const existingRecBox = document.getElementById('existing_receipt_box');
        if (existingRecBox && !isCanceledRecord) {
            existingRecBox.classList.remove('d-none');
            delete existingRecBox.dataset.removed;
        }
        removeUploadedReceipt();
        syncPaymentUI();
    }

    // Restore original or suggested packages & services
    document.querySelectorAll('.pkg-checkbox').forEach(cb => {
        cb.checked = origPackageIds.includes(parseInt(cb.value));
    });
    document.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.checked = origServiceIds.includes(parseInt(cb.value));
    });

    onResubmitCatalogChange();

    const origDate = "{{ $selectedDate }}";
    document.getElementById('appointment_date').value = origDate;
    if (origDate) await fetchTimeSlots(origDate);

    document.querySelectorAll('#resubmitForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#resubmitForm .invalid-feedback').forEach(el => {
        el.innerText = '';
        el.classList.add('d-none');
        el.classList.remove('d-block');
    });
}

/* -------------------------------------------------------------------------- */
/* REFERRAL FILE CONTROLLERS                                                  */
/* -------------------------------------------------------------------------- */
function handleReferralUpload(input) {
    const file = input.files[0];
    const previewContainer = document.getElementById('referral_preview_container');
    const inputWrapper = document.getElementById('referral_upload_wrapper');
    const fileLabel = document.getElementById('referral_file_label');
    const viewBtn = document.getElementById('btn_view_referral');

    if (!file) {
        if (previewContainer) previewContainer.classList.add('d-none');
        if (inputWrapper) inputWrapper.classList.remove('d-none');
        window.referralLocalData = null;
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        window.referralLocalData = e.target.result;
        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (fileLabel) fileLabel.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${file.name}`;
        if (viewBtn) {
            viewBtn.onclick = function() {
                openFilePreview(window.referralLocalData, "Doctor's Referral Note");
            };
        }
    };
    reader.readAsDataURL(file);
}

function removeUploadedReferral() {
    const input = document.getElementById('referral_note');
    if (input) input.value = '';
    const previewContainer = document.getElementById('referral_preview_container');
    const inputWrapper = document.getElementById('referral_upload_wrapper');
    if (previewContainer) previewContainer.classList.add('d-none');
    if (inputWrapper) inputWrapper.classList.remove('d-none');
    window.referralLocalData = null;
}

function removeExistingReferral() {
    document.getElementById('remove_referral').value = '1';
    const box = document.getElementById('existing_referral_box');
    if (box) box.classList.add('d-none');
    document.getElementById('referral_upload_wrapper').classList.remove('d-none');
}

/* -------------------------------------------------------------------------- */
/* RECEIPT FILE CONTROLLERS                                                   */
/* -------------------------------------------------------------------------- */
function handleReceiptUpload(input) {
    const file = input.files[0];
    const previewContainer = document.getElementById('receipt_preview_container');
    const inputWrapper = document.getElementById('receipt_upload_wrapper');
    const fileLabel = document.getElementById('receipt_file_label');
    const viewBtn = document.getElementById('btn_view_receipt');

    if (!file) {
        if (previewContainer) previewContainer.classList.add('d-none');
        if (inputWrapper) inputWrapper.classList.remove('d-none');
        window.receiptLocalData = null;
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        window.receiptLocalData = e.target.result;
        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (fileLabel) fileLabel.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected Receipt: ${file.name}`;
        if (viewBtn) {
            viewBtn.onclick = function() {
                openFilePreview(window.receiptLocalData, "Proof of Payment Receipt");
            };
        }
    };
    reader.readAsDataURL(file);
    clearFieldError(input);
}

function removeUploadedReceipt() {
    const input = document.getElementById('payment_receipt');
    if (input) input.value = '';
    const previewContainer = document.getElementById('receipt_preview_container');
    const inputWrapper = document.getElementById('receipt_upload_wrapper');
    if (previewContainer) previewContainer.classList.add('d-none');
    if (inputWrapper) inputWrapper.classList.remove('d-none');
    window.receiptLocalData = null;
    syncPaymentUI();
}

function removeExistingReceipt() {
    document.getElementById('remove_receipt').value = '1';
    const fileInput = document.getElementById('payment_receipt');
    if (fileInput) fileInput.value = '';
    const box = document.getElementById('existing_receipt_box');
    if (box) {
        box.classList.add('d-none');
        box.dataset.removed = 'true';
    }
    syncPaymentUI();
}

function hasActiveReceipt() {
    if (isBulk) return true;
    if (isCanceledRecord) {
        const receiptInput = document.getElementById('payment_receipt');
        return receiptInput && receiptInput.files && receiptInput.files.length > 0;
    }

    const isRemoved = document.getElementById('remove_receipt')?.value === '1';
    const receiptInput = document.getElementById('payment_receipt');
    const hasNewFile = receiptInput && receiptInput.files && receiptInput.files.length > 0;
    const existingBox = document.getElementById('existing_receipt_box');
    const hasExistingOnServer = existingBox && !existingBox.dataset.removed;

    if (isRemoved) return hasNewFile;
    return hasExistingOnServer || hasNewFile;
}

function initPaymentState() {
    if (isBulk) return;
    const activeProvider = document.querySelector('.provider-radio:checked');
    if (activeProvider) {
        lastProviderId = activeProvider.value;
        updateProviderQr(activeProvider);
    }
    syncPaymentUI();
}

function handlePaymentMethodChange(radio) {
    if (isBulk) return;
    const targetMethod = radio.value;
    if (targetMethod === 'Cash' && lastMethod === 'Cashless' && hasActiveReceipt()) {
        alert("You have an attached proof of payment receipt. Please click 'Remove' on the receipt first before switching to Cash on Site.");
        const payCashless = document.getElementById('pay_cashless');
        if (payCashless) payCashless.checked = true;
        return;
    }
    lastMethod = targetMethod;
    syncPaymentUI();
}

function handleProviderChange(radio) {
    if (isBulk) return;
    const targetProviderId = radio.value;
    if (lastProviderId && lastProviderId !== targetProviderId && hasActiveReceipt()) {
        alert("You have an attached proof of payment receipt. Please click 'Remove' on the receipt first before changing E-Wallet providers.");
        const prev = document.getElementById('provider_' + lastProviderId);
        if (prev) prev.checked = true;
        return;
    }
    lastProviderId = targetProviderId;
    updateProviderQr(radio);
    syncPaymentUI();
}

function syncPaymentUI() {
    if (isBulk) return;
    const cashlessRadio = document.getElementById('pay_cashless');
    const providerContainer = document.getElementById('provider_selection_container');
    const qrSection = document.getElementById('qr_section');
    const receiptWrapper = document.getElementById('receipt_upload_wrapper');
    const existingBox = document.getElementById('existing_receipt_box');
    const previewBox = document.getElementById('receipt_preview_container');

    const isCashless = cashlessRadio && cashlessRadio.checked;

    if (isCashless) {
        if (providerContainer) providerContainer.classList.remove('d-none');
        const activeProvider = document.querySelector('.provider-radio:checked');
        if (activeProvider) {
            if (qrSection) qrSection.classList.remove('d-none');
            const isRemoved = document.getElementById('remove_receipt')?.value === '1';
            const hasOnServer = !isCanceledRecord && existingBox && !existingBox.dataset.removed;

            if (hasOnServer && !isRemoved) {
                if (existingBox) existingBox.classList.remove('d-none');
                if (receiptWrapper) receiptWrapper.classList.add('d-none');
                if (previewBox) previewBox.classList.add('d-none');
            } else if (window.receiptLocalData) {
                if (existingBox) existingBox.classList.add('d-none');
                if (receiptWrapper) receiptWrapper.classList.add('d-none');
                if (previewBox) previewBox.classList.remove('d-none');
            } else {
                if (existingBox) existingBox.classList.add('d-none');
                if (previewBox) previewBox.classList.add('d-none');
                if (receiptWrapper) receiptWrapper.classList.remove('d-none');
            }
        } else {
            if (qrSection) qrSection.classList.add('d-none');
            if (receiptWrapper) receiptWrapper.classList.add('d-none');
            if (existingBox) existingBox.classList.add('d-none');
            if (previewBox) previewBox.classList.add('d-none');
        }
    } else {
        if (providerContainer) providerContainer.classList.add('d-none');
        if (qrSection) qrSection.classList.add('d-none');
        if (receiptWrapper) receiptWrapper.classList.add('d-none');
        if (existingBox) existingBox.classList.add('d-none');
        if (previewBox) previewBox.classList.add('d-none');
        document.querySelectorAll('.provider-radio').forEach(r => r.checked = false);
        lastProviderId = null;
    }
}

function updateProviderQr(radio) {
    const qrImg = document.getElementById('selected_provider_qr');
    const qrName = document.getElementById('selected_provider_name');
    if (radio && radio.checked) {
        if (qrImg) qrImg.src = radio.dataset.qr;
        if (qrName) qrName.innerText = radio.dataset.name;
    }
}

/* -------------------------------------------------------------------------- */
/* DYNAMIC TIME SLOTS FETCHER WITH INLINE ERRORS                              */
/* -------------------------------------------------------------------------- */
async function fetchTimeSlots(dateStr) {
    const tsSel = document.getElementById('time_slot');
    const dateInput = document.getElementById('appointment_date');
    if (!dateStr || !tsSel) return;

    const now = new Date();
    const todayLocal = now.toLocaleDateString('en-CA');

    if (dateStr < todayLocal) {
        tsSel.innerHTML = '<option value=""></option>';
        tsSel.disabled = true;
        setFieldError(dateInput, 'err_appointment_date', 'Preferred visit date cannot be in the past. Please select today or a future date.');
        setFieldError(tsSel, 'err_time_slot', 'Time slot cannot be in the past. Please select a valid future visit date.');
        return;
    } else {
        clearFieldError(dateInput);
    }

    tsSel.innerHTML = '<option value=""></option>';
    tsSel.disabled = true;

    try {
        const res = await fetch(`/api/check-slots?date=${dateStr}&exclude_id=${appId}`);
        if (!res.ok) throw new Error("HTTP " + res.status);
        const data = await res.json();

        if (data.is_closed) {
            tsSel.innerHTML = '<option value=""></option>';
            tsSel.disabled = true;
            setFieldError(tsSel, 'err_time_slot', 'The clinic is closed on this date. Please select another date.');
            return;
        }

        const config = data.config;
        if (!config) {
            tsSel.innerHTML = '<option value=""></option>';
            tsSel.disabled = true;
            setFieldError(tsSel, 'err_time_slot', 'No schedule configuration found for this date.');
            return;
        }

        let html = '<option value=""></option>';
        let start = new Date(`2000-01-01T${config.opening_time}`);
        let end = new Date(`2000-01-01T${config.closing_time}`);
        let count = 0;
        let matchedSaved = false;
        const targetSaved = (savedTimeSlot || '').trim().toLowerCase();

        while (start < end) {
            let hours = start.getHours().toString().padStart(2, '0');
            let minutes = start.getMinutes().toString().padStart(2, '0');
            let tStr = `${hours}:${minutes}:00`;
            let disp = start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            let isFull = (data.full_slots || []).includes(tStr);
            let isLunch = (config.has_lunch_break && tStr >= config.lunch_start && tStr < config.lunch_end);
            let isPast = false;

            if (dateStr === todayLocal) {
                const leadTimeMs = (parseInt(config.lead_time_hours) || 0) * 3600 * 1000;
                const cutoffTime = now.getTime() + leadTimeMs;
                const slotDate = new Date(`${dateStr}T${tStr}`);
                isPast = slotDate.getTime() < cutoffTime;
            }

            if (!isLunch && !isFull && !isPast) {
                let isSelected = (
                    tStr.toLowerCase() === targetSaved || 
                    tStr.substring(0, 5) === targetSaved.substring(0, 5) || 
                    disp.toLowerCase() === targetSaved
                );
                if (isSelected) matchedSaved = true;
                html += `<option value="${tStr}" ${isSelected ? 'selected' : ''}>${disp}</option>`;
                count++;
            }
            start.setMinutes(start.getMinutes() + parseInt(config.slot_duration || 30));
        }

        tsSel.innerHTML = html;
        tsSel.disabled = (count === 0);

        if (count === 0) {
            setFieldError(tsSel, 'err_time_slot', 'All time slots for this date are fully booked or have passed.');
        } else if (!tsSel.value) {
            if (targetSaved && !matchedSaved) {
                setFieldError(tsSel, 'err_time_slot', 'Previously selected time slot is in the past or unavailable. Please choose a new time slot.');
            } else {
                clearFieldError(tsSel);
            }
        } else {
            clearFieldError(tsSel);
        }
    } catch (e) {
        console.error("Slots fetch error", e);
        tsSel.innerHTML = '<option value=""></option>';
        tsSel.disabled = false;
    }
}

function setFieldError(input, errDivId, message) {
    if (!input) return;
    input.classList.add('is-invalid');
    const errDiv = document.getElementById(errDivId);
    if (errDiv) {
        errDiv.innerText = message;
        errDiv.classList.add('d-block');
        errDiv.classList.remove('d-none');
    }
}

function clearFieldError(input) {
    if (!input) return;
    input.classList.remove('is-invalid');
    let errDiv = document.getElementById('err_' + input.id) || document.getElementById('err_' + input.name);
    if (errDiv) {
        errDiv.innerText = '';
        errDiv.classList.add('d-none');
        errDiv.classList.remove('d-block');
    }
}

function validateResubmitForm(e) {
    let isValid = true;
    let firstInvalidInput = null;

    document.querySelectorAll('#resubmitForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#resubmitForm .invalid-feedback').forEach(el => {
        el.innerText = '';
        el.classList.add('d-none');
        el.classList.remove('d-block');
    });

    function markInvalid(input, errId, msg) {
        setFieldError(input, errId, msg);
        isValid = false;
        if (!firstInvalidInput) firstInvalidInput = input;
    }

    // 1. Diagnostic Tests & Packages Selection Validation
    const selectedPkgs = Array.from(document.querySelectorAll('.pkg-checkbox:checked'));
    const selectedSvcs = Array.from(document.querySelectorAll('.service-checkbox:checked'));
    const container = document.getElementById('resubmitCatalogTabsContent');

    if (selectedPkgs.length === 0 && selectedSvcs.length === 0) {
        markInvalid(container, 'err_service_ids', 'Please select at least one individual test or health package.');
    } else {
        // Enforce no package-to-package conflicts
        const coveredServices = new Set();
        for (const pkgCb of selectedPkgs) {
            const pkg = packagesMap[pkgCb.value];
            let serviceIds = [];
            if (pkg && pkg.services) {
                serviceIds = pkg.services.map(s => s.id.toString());
            } else if (pkgCb.dataset.services) {
                try { serviceIds = JSON.parse(pkgCb.dataset.services).map(String); } catch(err) {}
            }

            for (const sId of serviceIds) {
                if (coveredServices.has(sId)) {
                    markInvalid(container, 'err_service_ids', 'Conflicting packages detected: multiple selected packages share identical tests. Please remove one of the conflicting packages.');
                    break;
                }
                coveredServices.add(sId);
            }
        }

        // Enforce no package-to-service conflicts
        for (const svcCb of selectedSvcs) {
            if (coveredServices.has(svcCb.value.toString())) {
                markInvalid(container, 'err_service_ids', 'Double selection detected: An individual test is already covered in a selected package.');
                break;
            }
        }
    }

    // 2. Schedule Validation
    const dateInput = document.getElementById('appointment_date');
    const slotSel = document.getElementById('time_slot');
    const now = new Date();
    const todayLocal = now.toLocaleDateString('en-CA');

    if (!dateInput.value) {
        markInvalid(dateInput, 'err_appointment_date', 'Preferred visit date is required.');
    } else if (dateInput.value < todayLocal) {
        markInvalid(dateInput, 'err_appointment_date', 'Preferred visit date cannot be in the past. Please select today or a future date.');
    }

    if (!slotSel.value) {
        markInvalid(slotSel, 'err_time_slot', 'Preferred time slot is required. Please select a valid available time slot.');
    }

    // 3. Payment Receipt Validation (Bypassed if bulk OR confirmed paid rollover)
    if (!isBulk) {
        const payCashless = document.getElementById('pay_cashless');
        if (payCashless && payCashless.checked && !isPaidRollover) {
            const activeProvider = document.querySelector('.provider-radio:checked');
            if (!activeProvider) {
                const providerContainer = document.getElementById('provider_selection_container');
                markInvalid(providerContainer, 'err_payment_receipt', 'Please choose an E-Wallet provider.');
            }
            const receiptInput = document.getElementById('payment_receipt');
            const hasExistingReceipt = !isCanceledRecord && document.getElementById('existing_receipt_box') && !document.getElementById('existing_receipt_box').classList.contains('d-none');
            const isRemoved = document.getElementById('remove_receipt').value === '1';

            if (!receiptInput.files[0] && (!hasExistingReceipt || isRemoved)) {
                markInvalid(receiptInput, 'err_payment_receipt', 'Please upload a valid proof of payment / receipt image.');
            }
        }
    }

    if (!isValid) {
        e.preventDefault();
        e.stopPropagation();
        if (firstInvalidInput) {
            firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidInput.focus();
        }
        return false;
    }

    clearDraftData();
    return true;
}
</script>
@endpush