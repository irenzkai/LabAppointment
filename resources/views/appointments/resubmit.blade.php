@extends('layouts.app')
@section('title', 'Resubmit Appointment #' . $appointment->id)
@section('content')
<div class="container text-start animate-page py-4" id="resubmit-page-root">
{{-- BREADCRUMB & HEADER ACTION BAR --}}
<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
<div>
<h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
Resubmit Appointment #{{ $appointment->id }}
</h2>
<p class="text-secondary mb-0 small">Update your patient details, selected services, or visit schedule to resubmit for clinical review.</p>
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

{{-- STATUS CONTEXT ALERTS --}}
@if($appointment->status === 'returned' && $appointment->return_reason)
{{-- Returned Case Alert --}}
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
{{-- Canceled Case Alert --}}
<div class="alert alert-clinical border-warning p-4 rounded-3 mb-4 shadow-sm">
<div class="d-flex align-items-center mb-2">
<i class="bi bi-exclamation-triangle-fill fs-3 me-3 warning-icon"></i>
<div>
<h5 class="fw-bold uppercase mb-0 warning-title">Reactivating Canceled Appointment</h5>
<small class="warning-sub">This appointment was previously canceled.</small>
</div>
</div>
<p class="mb-0 small text-main mt-1">
Submitting this form will update your information and place your record back into the active processing queue.
@if($appointment->payment_status === 'paid')
<br><strong class="warning-title">Payment Rollover:</strong> Your previously confirmed payment will be automatically rolled over to cover this resubmission. No new payment receipt is required.
@endif
</p>
</div>
@elseif($isExpired)
{{-- Expired Case Alert --}}
<div class="alert alert-clinical border-warning p-4 rounded-3 mb-4 shadow-sm">
<div class="d-flex align-items-center mb-2">
<i class="bi bi-clock-history fs-3 me-3 warning-icon"></i>
<div>
<h5 class="fw-bold uppercase mb-0 warning-title">Rescheduling Expired Appointment</h5>
<small class="warning-sub">This appointment passed its 24-hour unprogressed schedule window.</small>
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
{{-- Tracking flags for server-side file removals --}}
<input type="hidden" name="remove_referral" id="remove_referral" value="0">
<input type="hidden" name="remove_receipt" id="remove_receipt" value="0">

<div class="row g-4 align-items-stretch">
{{-- LEFT COLUMN: PATIENT IDENTITY, ADDRESS & REFERRAL --}}
<div class="col-lg-6 d-flex flex-column gap-4">
{{-- 1. Patient Identity Card --}}
<div class="card p-4 border-secondary bg-card shadow-sm h-100">
<h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
<i class="bi bi-person-bounding-box me-2"></i>1. Patient Identity & Demographics
</h5>
<div class="row g-3">
{{-- First Name --}}
<div class="col-md-4">
<label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
<input type="text" name="patient_first_name" id="patient_first_name" class="form-control uppercase" value="{{ old('patient_first_name', $appointment->patient_first_name) }}" required>
<div class="invalid-feedback d-none" id="err_patient_first_name"></div>
@error('patient_first_name')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
{{-- Middle Name --}}
<div class="col-md-4">
<div class="d-flex justify-content-between align-items-center mb-1">
<label class="small text-secondary fw-bold mb-0 uppercase">Middle Name</label>
<div class="form-check form-switch mb-0">
<input class="form-check-input" type="checkbox" id="no_middle_name_toggle" onclick="toggleMiddleName(this)" {{ old('patient_middle_name', $appointment->patient_middle_name) === 'N/A' ? 'checked' : '' }}>
<label class="smaller text-muted" for="no_middle_name_toggle">None</label>
</div>
</div>
<input type="text" name="patient_middle_name" id="patient_middle_name" class="form-control uppercase" value="{{ old('patient_middle_name', $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name) }}" {{ old('patient_middle_name', $appointment->patient_middle_name) === 'N/A' ? 'readonly' : '' }}>
<div class="invalid-feedback d-none" id="err_patient_middle_name"></div>
@error('patient_middle_name')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
{{-- Last Name --}}
<div class="col-md-4">
<label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
<input type="text" name="patient_last_name" id="patient_last_name" class="form-control uppercase" value="{{ old('patient_last_name', $appointment->patient_last_name) }}" required>
<div class="invalid-feedback d-none" id="err_patient_last_name"></div>
@error('patient_last_name')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
{{-- Suffix --}}
<div class="col-md-3">
<label class="small text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
<input type="text" name="patient_suffix" id="patient_suffix" list="suffix_options" class="form-control uppercase" value="{{ old('patient_suffix', $appointment->patient_suffix) }}" placeholder="e.g. JR" maxlength="10">
<datalist id="suffix_options">
<option value="JR"><option value="SR"><option value="II"><option value="III"><option value="IV"><option value="V">
</datalist>
<div class="invalid-feedback d-none" id="err_patient_suffix"></div>
@error('patient_suffix')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
{{-- Sex --}}
<div class="col-md-3">
<label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
<select name="patient_sex" id="patient_sex" class="form-select" required>
<option value="Male" {{ old('patient_sex', $appointment->patient_sex) === 'Male' ? 'selected' : '' }}>Male</option>
<option value="Female" {{ old('patient_sex', $appointment->patient_sex) === 'Female' ? 'selected' : '' }}>Female</option>
</select>
<div class="invalid-feedback d-none" id="err_patient_sex"></div>
@error('patient_sex')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
{{-- Birthdate (Strict Policy Boundaries) --}}
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
@php
 $isDependent = $appointment->dependent_id !== null;
 $maxBday = $isDependent ? now()->format('Y-m-d') : now()->subYears(18)->format('Y-m-d');
 $minBday = $isDependent ? now()->subYears(18)->addDay()->format('Y-m-d') : '';
@endphp
<input type="date" name="patient_birthdate" id="patient_birthdate" class="form-control" 
 value="{{ old('patient_birthdate', $appointment->patient_birthdate ? $appointment->patient_birthdate->format('Y-m-d') : '') }}" 
 @if($minBday) min="{{ $minBday }}" @endif 
 max="{{ $maxBday }}" 
 onchange="validateBirthdateInput()" required>
<div class="invalid-feedback d-none" id="err_patient_birthdate"></div>
@error('patient_birthdate')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
<small class="text-muted smaller mt-1 d-block">
 {{ $isDependent ? 'Dependents must be minors under 18 years of age.' : 'Patients must be at least 18 years of age for personal bookings.' }}
</small>
</div>
{{-- Phone Number --}}
<div class="col-12">
<label class="small text-secondary fw-bold mb-1 uppercase">Contact Phone Number</label>
<div class="input-group">
<span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
@php
$rawPhone = old('patient_phone', $appointment->patient_phone);
$displayPhone = str_starts_with($rawPhone, '09') ? substr($rawPhone, 2) : $rawPhone;
@endphp
<input type="text" id="phone_display" class="form-control" placeholder="171234567" maxlength="9" value="{{ $displayPhone }}" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncPhone();" required>
</div>
<input type="hidden" name="patient_phone" id="patient_phone" value="{{ $rawPhone }}">
<div class="invalid-feedback d-none" id="err_patient_phone"></div>
@error('patient_phone')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
</div>
</div>

{{-- 2. Address Card (Identical to edit-details.blade.php) --}}
<div class="card p-4 border-secondary bg-card shadow-sm h-100">
<h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
<i class="bi bi-geo-alt-fill me-2"></i>2. Residential Address
</h5>
<div class="row g-3">
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
<select id="addr_province" name="patient_province" class="form-select @error('patient_province') is-invalid @enderror" required>
<option value="">Loading Provinces...</option>
</select>
<div class="invalid-feedback d-none" id="err_patient_province"></div>
@error('patient_province')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
<select id="addr_city" name="patient_city" class="form-select @error('patient_city') is-invalid @enderror" disabled required>
<option value="">Select Province First</option>
</select>
<div class="invalid-feedback d-none" id="err_patient_city"></div>
@error('patient_city')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
<select id="addr_brgy" name="patient_barangay" class="form-select @error('patient_barangay') is-invalid @enderror" disabled required>
<option value="">Select City First</option>
</select>
<div class="invalid-feedback d-none" id="err_patient_barangay"></div>
@error('patient_barangay')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
<input type="text" name="patient_street" id="patient_street" class="form-control uppercase @error('patient_street') is-invalid @enderror" value="{{ old('patient_street', $appointment->patient_street) }}" required>
<div class="invalid-feedback d-none" id="err_patient_street"></div>
@error('patient_street')
    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
@enderror
</div>
</div>
</div>

{{-- 3. Doctor's Referral Note Card --}}
<div class="card p-4 border-secondary bg-card shadow-sm">
<h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
<i class="bi bi-file-earmark-medical me-2"></i>3. Doctor's Referral / Note (Optional)
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
<input type="file" name="referral_note" id="referral_note" class="form-control" accept="image/*, application/pdf" onchange="handleReferralUpload(this)">
<small class="text-muted d-block mt-1">Accepted formats: PDF or image files (Max: 10MB).</small>
<div class="invalid-feedback d-none" id="err_referral_note"></div>
</div>
</div>
</div>

{{-- RIGHT COLUMN: SERVICES, SCHEDULE & PAYMENT --}}
<div class="col-lg-6 d-flex flex-column gap-4">
{{-- 4. Test Selection Card --}}
<div class="card p-4 border-secondary bg-card shadow-sm h-100">
<h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
<i class="bi bi-flask me-2"></i>4. Select Medical Services
</h5>
<div class="mb-3">
<input type="text" id="service_search" class="form-control form-control-sm" placeholder="Search tests..." onkeyup="filterServices()">
</div>
@php
$selectedServiceIds = old('service_ids', $appointment->services->pluck('id')->toArray());
@endphp
<div class="p-3 border rounded border-secondary border-opacity-25 overflow-auto custom-scroll mb-2" id="services_container" style="max-height: 220px;">
@foreach($services as $s)
<div class="form-check p-2.5 rounded mb-1.5 service-item" data-name="{{ strtoupper($s->name) }}">
<input class="form-check-input service-checkbox me-2" type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="svc_{{ $s->id }}" data-price="{{ $s->price }}" {{ in_array($s->id, $selectedServiceIds) ? 'checked' : '' }} onchange="onServiceCheckChange()">
<label class="form-check-label text-main small cursor-pointer w-100 align-items-center" for="svc_{{ $s->id }}">
<span>{{ strtoupper($s->name) }}</span>
<span class="text-accent fw-bold">₱{{ number_format($s->price, 2) }}</span>
</label>
</div>
@endforeach
</div>
<div class="invalid-feedback d-none mb-3 text-danger fw-bold" id="err_service_ids"></div>
<div class="d-flex justify-content-between align-items-center p-3 rounded bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
<span class="fw-bold uppercase small text-secondary">Total Estimated Bill:</span>
<span class="text-accent fs-5 fw-bold" id="total_bill_display">₱0.00</span>
</div>
</div>

{{-- 5. Schedule Visit Card --}}
<div class="card p-4 border-secondary bg-card shadow-sm h-100">
<h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
<i class="bi bi-calendar3 me-2"></i>5. Preferred Schedule
</h5>
<div class="row g-3 mb-1">
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">Visit Date</label>
<input type="date" name="appointment_date" id="appointment_date" class="form-control" value="{{ old('appointment_date', $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : '') }}" min="{{ date('Y-m-d') }}" onchange="fetchTimeSlots(this.value)" required>
<div class="invalid-feedback d-none" id="err_appointment_date"></div>
</div>
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">Time Slot</label>
<select name="time_slot" id="time_slot" class="form-select" required>
<option value=""></option>
</select>
<div class="invalid-feedback d-none" id="err_time_slot"></div>
</div>
</div>
</div>

{{-- 6. Payment Method & Receipt Card --}}
<div class="card p-4 border-secondary bg-card shadow-sm h-100">
<h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
<i class="bi bi-credit-card me-2"></i>6. Payment Settlement
</h5>
@php
$selectedPaymentMethod = old('payment_method', $appointment->payment_method ?? 'Cash');
@endphp
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

{{-- Existing Receipt Container (Disabled for canceled appointments) --}}
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
<div id="receipt_upload_wrapper" class="{{ ($appointment->payment_receipt && $appointment->status !== 'canceled' && empty(old('remove_receipt'))) ? 'd-none' : 'd-none' }}">
<label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment</label>
<input type="file" name="payment_receipt" id="payment_receipt" class="form-control" accept="image/*, application/pdf" onchange="handleReceiptUpload(this)">
<div class="invalid-feedback d-none" id="err_payment_receipt"></div>
</div>
</div>
</div>

{{-- Action Submission Buttons --}}
<div class="d-flex gap-3 mt-2">
<a href="{{ route('appointments.index') }}" class="btn-custom btn-outline-secondary w-50 py-3 fw-bold uppercase text-decoration-none text-center btn-cancel-custom" style="font-size: 0.85rem;">
Cancel
</a>
<button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="btn_submit_resubmit">
Submit Resubmission <i class="bi bi-check-circle-fill ms-1"></i>
</button>
</div>
</div>
</div>
</form>
</div>

{{-- Includes the Unified Lightbox Partial --}}
@include('layouts.partials.lightbox-overlay')
@endsection

@push('styles')
<style>
/* Custom Reset & Cancel Buttons with No Default Bootstrap Blue Overlay */
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
const savedProvince = "{{ $appointment->patient_province }}";
const savedCity = "{{ $appointment->patient_city }}";
const savedBarangay = "{{ $appointment->patient_barangay }}";
const savedTimeSlot = @json(old('time_slot', $appointment->time_slot));
const appId = @json($appointment->id);
const isDependentRecord = @json($appointment->dependent_id !== null);
const isPaidRollover = @json($appointment->payment_status === 'paid');
const isCanceledRecord = @json($appointment->status === 'canceled');
let lastMethod = @json($selectedPaymentMethod);
let lastProviderId = null;

// File Base64 memory caches for referral and receipt uploads
window.referralLocalData = null;
window.receiptLocalData = null;

document.addEventListener('DOMContentLoaded', async () => {
 // Safe step-by-step setup calls
 try { loadDraftData(); } catch(e) { console.warn("Draft load notice:", e); }
 try { onServiceCheckChange(); } catch(e) { console.warn("Service check notice:", e); }
 try { initPaymentState(); } catch(e) { console.warn("Payment state notice:", e); }

 // Initialize Unified Address Cascade exactly like edit-details.blade.php
 await window.initUnifiedAddressCascade({
  provEl: document.getElementById('addr_province'),
  cityEl: document.getElementById('addr_city'),
  brgyEl: document.getElementById('addr_brgy'),
  streetEl: document.getElementById('patient_street'),
  savedProv: savedProvince,
  savedCity: savedCity, 
  savedBrgy: savedBarangay,
  onCompiled: saveDraftData
 });

 // Non-blocking time slot fetch
 const initialDate = document.getElementById('appointment_date')?.value;
 if (initialDate) {
  fetchTimeSlots(initialDate).catch(err => console.error("Slots fetch error:", err));
 }

 // Attach input observers for draft autosave & inline error dismissal
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
/* REAL-TIME DRAFT AUTOSAVE & RECOVERY ENGINE */
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
   // Skip address fields so loadDraftData does not interfere with initUnifiedAddressCascade
   if (['patient_province', 'patient_city', 'patient_barangay'].includes(key)) {
    continue;
   }
   const escKey = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(key) : key;
   if (Array.isArray(value)) {
    value.forEach(val => {
     const escVal = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(val) : val;
     try {
      const cb = document.querySelector(`input[name="${escKey}"][value="${escVal}"]`) || document.getElementById(`svc_${val}`);
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
 } catch(e) {
  console.warn("Draft restore failed:", e);
 }
}

function clearDraftData() {
 localStorage.removeItem(`resubmit_draft_${appId}`);
}

/* -------------------------------------------------------------------------- */
/* INSTANT IN-PAGE "RESET EDITS" CONTROLLER */
/* -------------------------------------------------------------------------- */
async function resetResubmitForm() {
 clearDraftData();

 // 1. Reset identity
 document.getElementById('patient_first_name').value = "{{ $appointment->patient_first_name }}";
 document.getElementById('patient_middle_name').value = "{{ $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name }}";
 document.getElementById('no_middle_name_toggle').checked = "{{ $appointment->patient_middle_name }}" === "N/A";
 document.getElementById('patient_middle_name').readOnly = "{{ $appointment->patient_middle_name }}" === "N/A";
 document.getElementById('patient_last_name').value = "{{ $appointment->patient_last_name }}";
 document.getElementById('patient_suffix').value = "{{ $appointment->patient_suffix }}";
 document.getElementById('patient_sex').value = "{{ $appointment->patient_sex }}";
 document.getElementById('patient_birthdate').value = "{{ $appointment->patient_birthdate ? $appointment->patient_birthdate->format('Y-m-d') : '' }}";

 // 2. Reset phone
 let origPhone = "{{ $appointment->patient_phone }}".trim();
 if (origPhone.startsWith('+639')) origPhone = '09' + origPhone.substring(4);
 if (origPhone.startsWith('639')) origPhone = '09' + origPhone.substring(3);
 document.getElementById('phone_display').value = origPhone.startsWith('09') ? origPhone.substring(2) : origPhone;
 document.getElementById('patient_phone').value = origPhone;

 // 3. Reset address - EXACTLY like edit-details.blade.php
 await window.initUnifiedAddressCascade({
  provEl: document.getElementById('addr_province'),
  cityEl: document.getElementById('addr_city'),
  brgyEl: document.getElementById('addr_brgy'),
  streetEl: document.getElementById('patient_street'),
  savedProv: savedProvince,
  savedCity: savedCity,
  savedBrgy: savedBarangay
 });
 document.getElementById('patient_street').value = "{{ $appointment->patient_street }}";

 // 4. Reset referral flags
 document.getElementById('remove_referral').value = "0";
 const existingRefBox = document.getElementById('existing_referral_box');
 if (existingRefBox) existingRefBox.classList.remove('d-none');
 removeUploadedReferral();

 // 5. Reset services checklist
 const origServices = @json($appointment->services->pluck('id')->toArray());
 document.querySelectorAll('.service-checkbox').forEach(cb => {
  cb.checked = origServices.includes(parseInt(cb.value));
 });
 onServiceCheckChange();

 // 6. Reset schedule
 const origDate = "{{ $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : '' }}";
 document.getElementById('appointment_date').value = origDate;
 if (origDate) await fetchTimeSlots(origDate);

 // 7. Reset payment & receipt
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

 // Clear all inline errors
 document.querySelectorAll('#resubmitForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
 document.querySelectorAll('#resubmitForm .invalid-feedback').forEach(el => {
  el.innerText = '';
  el.classList.add('d-none');
  el.classList.remove('d-block');
 });
}

/* -------------------------------------------------------------------------- */
/* REFERRAL FILE CONTROLLERS */
/* -------------------------------------------------------------------------- */
function handleReferralUpload(input) {
 const file = input.files[0];
 const previewContainer = document.getElementById('referral_preview_container');
 const inputWrapper = document.getElementById('referral_input_wrapper');
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
 const inputWrapper = document.getElementById('referral_input_wrapper');
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
/* RECEIPT FILE CONTROLLERS */
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

function onReceiptFileChange(input) {
 handleReceiptUpload(input);
}

/* -------------------------------------------------------------------------- */
/* GENERAL FORM HELPERS & VALIDATION ENGINE */
/* -------------------------------------------------------------------------- */
function toggleMiddleName(chk) {
 const input = document.getElementById('patient_middle_name');
 if (chk.checked) {
  input.value = 'N/A';
  input.readOnly = true;
 } else {
  input.value = '';
  input.readOnly = false;
 }
 clearFieldError(input);
}

function syncPhone() {
 const display = document.getElementById('phone_display').value;
 const hidden = document.getElementById('patient_phone');
 hidden.value = display ? '09' + display : '';
 clearFieldError(document.getElementById('phone_display'));
}

function onServiceCheckChange() {
 updateTotal();
 resortServices();
 clearFieldError(document.getElementById('services_container'));
}

function updateTotal() {
 let total = 0;
 document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
  total += parseFloat(cb.dataset.price || 0);
  cb.closest('.service-item')?.classList.add('selected-test');
 });
 document.querySelectorAll('.service-checkbox:not(:checked)').forEach(cb => {
  cb.closest('.service-item')?.classList.remove('selected-test');
 });
 const totalDisplay = document.getElementById('total_bill_display');
 if (totalDisplay) {
  totalDisplay.innerText = '₱' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
 }
}

function resortServices() {
 const container = document.getElementById('services_container');
 if (!container) return;
 const items = Array.from(container.querySelectorAll('.service-item'));
 items.sort((a, b) => {
  const aChecked = a.querySelector('.service-checkbox')?.checked ? 1 : 0;
  const bChecked = b.querySelector('.service-checkbox')?.checked ? 1 : 0;
  if (aChecked !== bChecked) return bChecked - aChecked;
  return (a.dataset.name || '').localeCompare(b.dataset.name || '');
 });
 items.forEach(item => container.appendChild(item));
}

function filterServices() {
 const query = document.getElementById('service_search').value.toUpperCase();
 document.querySelectorAll('.service-item').forEach(item => {
  const name = item.dataset.name || '';
  item.style.display = name.includes(query) ? '' : 'none';
 });
}

function hasActiveReceipt() {
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
 const activeProvider = document.querySelector('.provider-radio:checked');
 if (activeProvider) {
  lastProviderId = activeProvider.value;
  updateProviderQr(activeProvider);
 }
 syncPaymentUI();
}

function handlePaymentMethodChange(radio) {
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
/* DYNAMIC TIME SLOTS FETCHER WITH INLINE ERRORS */
/* -------------------------------------------------------------------------- */
async function fetchTimeSlots(dateStr) {
 const tsSel = document.getElementById('time_slot');
 const dateInput = document.getElementById('appointment_date');
 if (!dateStr || !tsSel) return;
 const now = new Date();
 const todayLocal = now.toLocaleDateString('en-CA');

 // Prevent fetching or selecting past dates
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

function validateNameString(val, fieldName) {
 if (!val || val === 'N/A') return null;
 const charRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc \s.\'-]+$/;
 const startRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc]/;
 const consecutiveRegex = /[.\'-]{2,}/;
 const letterRegex = /[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc]/;
 if (!charRegex.test(val)) return `${fieldName} may only contain letters, spaces, periods, hyphens, and apostrophes.`;
 if (!startRegex.test(val)) return `${fieldName} must start with a letter.`;
 if (!letterRegex.test(val)) return `${fieldName} must contain at least one letter.`;
 if (consecutiveRegex.test(val)) return `${fieldName} cannot contain consecutive punctuation marks.`;
 if (val.length > 60) return `${fieldName} cannot exceed 60 characters.`;
 return null;
}

/* -------------------------------------------------------------------------- */
/* REAL-TIME BIRTHDATE VALIDATION CHECKER */
/* -------------------------------------------------------------------------- */
function validateBirthdateInput() {
 const bdayInput = document.getElementById('patient_birthdate');
 if (!bdayInput) return true;
 clearFieldError(bdayInput);
 if (!bdayInput.value) {
  setFieldError(bdayInput, 'err_patient_birthdate', 'Birthdate is required.');
  return false;
 }
 const dob = new Date(bdayInput.value);
 const today = new Date();
 let age = today.getFullYear() - dob.getFullYear();
 const m = today.getMonth() - dob.getMonth();
 if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
 if (age < 0) {
  setFieldError(bdayInput, 'err_patient_birthdate', 'Birthdate cannot be in the future.');
  return false;
 } else if (isDependentRecord) {
  if (age >= 18) {
   setFieldError(bdayInput, 'err_patient_birthdate', 'Administrative Policy: Dependents must be minors (under 18 years of age).');
   return false;
  }
 } else {
  if (age < 18) {
   setFieldError(bdayInput, 'err_patient_birthdate', 'Administrative Policy: You must be at least 18 years old to book a personal appointment.');
   return false;
  }
 }
 return true;
}

/* -------------------------------------------------------------------------- */
/* STRICT CLIENT-SIDE SUBMIT VALIDATOR WITH INLINE ERRORS */
/* -------------------------------------------------------------------------- */
function validateResubmitForm(e) {
 let isValid = true;
 let firstInvalidInput = null;

 // Flush previous error states
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

 // 1. Patient Name Validation
 const fNameInput = document.getElementById('patient_first_name');
 const fNameErr = validateNameString(fNameInput.value.trim(), 'First Name');
 if (!fNameInput.value.trim()) markInvalid(fNameInput, 'err_patient_first_name', 'First Name is required.');
 else if (fNameErr) markInvalid(fNameInput, 'err_patient_first_name', fNameErr);

 const mNameInput = document.getElementById('patient_middle_name');
 const mNameErr = validateNameString(mNameInput.value.trim(), 'Middle Name');
 if (mNameErr) markInvalid(mNameInput, 'err_patient_middle_name', mNameErr);

 const lNameInput = document.getElementById('patient_last_name');
 const lNameErr = validateNameString(lNameInput.value.trim(), 'Last Name');
 if (!lNameInput.value.trim()) markInvalid(lNameInput, 'err_patient_last_name', 'Last Name is required.');
 else if (lNameErr) markInvalid(lNameInput, 'err_patient_last_name', lNameErr);

 const suffixInput = document.getElementById('patient_suffix');
 if (suffixInput.value.trim()) {
  const suffixRegex = /^[a-zA-Z\s.]+$/;
  if (!suffixRegex.test(suffixInput.value.trim())) markInvalid(suffixInput, 'err_patient_suffix', 'Suffix may only contain letters, spaces, and periods.');
  else if (suffixInput.value.trim().length > 10) markInvalid(suffixInput, 'err_patient_suffix', 'Suffix cannot exceed 10 characters.');
 }

 // 2. Sex Validation
 const sexSel = document.getElementById('patient_sex');
 if (!sexSel.value) markInvalid(sexSel, 'err_patient_sex', 'Please select a gender.');

 // 3. Birthdate & Age Validation
 const bdayInput = document.getElementById('patient_birthdate');
 if (!validateBirthdateInput()) {
  if (!firstInvalidInput) firstInvalidInput = bdayInput;
  isValid = false;
 }

 // 4. Contact Phone Validation
 const phoneInput = document.getElementById('patient_phone');
 const displayPhoneInput = document.getElementById('phone_display');
 const phoneRegex = /^09\d{9}$/;
 if (!displayPhoneInput.value.trim()) {
  markInvalid(displayPhoneInput, 'err_patient_phone', 'Contact phone number is required.');
 } else if (!phoneRegex.test(phoneInput.value.trim())) {
  markInvalid(displayPhoneInput, 'err_patient_phone', 'Phone number must start with 09 and contain exactly 11 digits.');
 }

 // 5. Address Validation
 const provSel = document.getElementById('addr_province');
 const citySel = document.getElementById('addr_city');
 const brgySel = document.getElementById('addr_brgy');
 const streetInput = document.getElementById('patient_street');
 if (!provSel.value) markInvalid(provSel, 'err_patient_province', 'Province selection is required.');
 if (!citySel.value) markInvalid(citySel, 'err_patient_city', 'City/Municipality selection is required.');
 if (!brgySel.value) markInvalid(brgySel, 'err_patient_barangay', 'Barangay selection is required.');
 if (!streetInput.value.trim()) markInvalid(streetInput, 'err_patient_street', 'Street address is required.');

 // 6. Test Services Selection
 const selectedTests = document.querySelectorAll('.service-checkbox:checked');
 if (selectedTests.length === 0) {
  const container = document.getElementById('services_container');
  markInvalid(container, 'err_service_ids', 'Please select at least one laboratory test.');
 }

 // 7. Schedule Validation
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

 // 8. Payment Receipt Validation (Bypassed if confirmed paid rollover)
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