@extends('layouts.app')

@section('title', 'Edit Details - Ref #' . $appointment->id)

@section('content')
@php
    $previousUrl = url()->previous();
    $from = request('from');
    $customId = request('custom_id');

    // Auto-detect custom_id from previous URL if not explicitly provided in request
    if (!$customId && preg_match('/workstation\/custom\/\d+\/(\d+)/', $previousUrl, $matches)) {
        $customId = $matches[1];
    }

    // Auto-detect $from workstation route from previous URL if not explicitly passed
    if (!$from) {
        if (str_contains($previousUrl, 'workstation/radiology')) {
            $from = 'radio';
        } elseif (str_contains($previousUrl, 'workstation/lab')) {
            $from = 'lab';
        } elseif (str_contains($previousUrl, 'workstation/medical')) {
            $from = 'med_cert';
        } elseif (str_contains($previousUrl, 'workstation/drug')) {
            $from = 'drug';
        } elseif (str_contains($previousUrl, 'workstation/custom')) {
            $from = 'custom';
        } elseif (str_contains($previousUrl, 'encode')) {
            $from = 'hub';
        }
    }

    // Context-aware Back URL and Button Label determination
    if ($from === 'radio' || $from === 'radiology') {
        $backUrl = route('workstation.radiology', $appointment->id);
        $backLabel = 'Back to Radiology Workstation';
    } elseif ($from === 'lab') {
        $backUrl = route('workstation.lab', $appointment->id);
        $backLabel = 'Back to Lab Workstation';
    } elseif ($from === 'med_cert' || $from === 'medical') {
        $backUrl = route('workstation.med_cert', $appointment->id);
        $backLabel = 'Back to Medical Cert Workstation';
    } elseif ($from === 'drug') {
        $backUrl = route('workstation.drug', $appointment->id);
        $backLabel = 'Back to Drug Test Workstation';
    } elseif ($from === 'custom' && $customId) {
        $backUrl = route('workstation.custom', [$appointment->id, $customId]);
        $backLabel = 'Back to Custom Workstation';
    } elseif ($from === 'hub' || str_contains($previousUrl, 'encode')) {
        $backUrl = route('appointments.encode', $appointment->id);
        $backLabel = 'Back to Results Hub';
    } else {
        $backUrl = route('appointments.index');
        $backLabel = 'Back to Master Queue';
    }

    $isDependent = $appointment->dependent_id !== null;
    $maxBday = $isDependent ? now()->format('Y-m-d') : now()->subYears(18)->format('Y-m-d');
    $minBday = $isDependent ? now()->subYears(18)->addDay()->format('Y-m-d') : '';
    $currentServiceIds = old('service_ids', $appointment->services->pluck('id')->toArray());
    
    // Evaluate initial price safely to prevent database 0.00 defaults from clobbering calculated test sum
    $initialPrice = ($appointment->payment_amount && floatval($appointment->payment_amount) > 0) 
        ? floatval($appointment->payment_amount) 
        : floatval($appointment->totalPrice());
@endphp

<div class="container text-start animate-page py-4" id="edit-details-page-root">

    {{-- HEADER BAR WITH CONTEXT-AWARE BACK LINK --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                <i class="bi bi-pencil-square me-2"></i>Edit Patient Details & Services
            </h2>
            <p class="text-secondary mb-0 small">Revise demographics, residential address, or diagnostic service requests for Ref #{{ $appointment->id }}.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-reset-custom" onclick="resetReviseForm()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Edits
            </button>
            <a href="{{ $backUrl }}" class="btn-custom btn-outline-secondary px-4 py-2 fw-bold text-uppercase text-decoration-none btn-cancel-custom" style="font-size: 0.75rem;">
                <i class="bi bi-arrow-left me-1"></i> {{ $backLabel }}
            </a>
        </div>
    </div>

    {{-- REVISION FORM --}}
    <form action="{{ route('internal.appointment-details.update', $appointment->id) }}" method="POST" id="revise_details_form" novalidate>
        @csrf
        @method('PUT')

        {{-- Hidden origin tracking --}}
        <input type="hidden" name="from" value="{{ $from }}">
        <input type="hidden" name="custom_id" value="{{ $customId }}">

        <div class="row g-4 align-items-stretch">

            {{-- LEFT COLUMN: IDENTITY & ADDRESS --}}
            <div class="col-lg-6 d-flex flex-column gap-4">

                {{-- 1. Patient Identity Card --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100">
                    <h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-person-bounding-box me-2"></i>1. Personal Identity
                    </h5>

                    <div class="row g-3">
                        {{-- First Name --}}
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                                <label class="smaller fw-bold uppercase mb-0" style="color: var(--text-muted);">First Name</label>
                            </div>
                            <input type="text" name="patient_first_name" id="patient_first_name" class="form-control uppercase fw-bold @error('patient_first_name') is-invalid @enderror" value="{{ old('patient_first_name', $appointment->patient_first_name) }}" required>
                            <div class="invalid-feedback d-none" id="err_patient_first_name"></div>
                            @error('patient_first_name')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Middle Name --}}
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-center mb-1" style="height: 22px;">
                                <label class="smaller fw-bold mb-0 uppercase" style="color: var(--text-muted);">Middle Name</label>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="no_middle_name_toggle" onclick="toggleMiddleName(this)" {{ $appointment->patient_middle_name === 'N/A' ? 'checked' : '' }}>
                                    <label class="smaller text-muted" style="font-size: 0.65rem; line-height: 1;" for="no_middle_name_toggle">None</label>
                                </div>
                            </div>
                            <input type="text" name="patient_middle_name" id="patient_middle_name" class="form-control uppercase fw-bold @error('patient_middle_name') is-invalid @enderror" value="{{ old('patient_middle_name', $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name) }}" {{ $appointment->patient_middle_name === 'N/A' ? 'readonly' : '' }}>
                            <div class="invalid-feedback d-none" id="err_patient_middle_name"></div>
                            @error('patient_middle_name')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Last Name --}}
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                                <label class="smaller fw-bold uppercase mb-0" style="color: var(--text-muted);">Last Name</label>
                            </div>
                            <input type="text" name="patient_last_name" id="patient_last_name" class="form-control uppercase fw-bold @error('patient_last_name') is-invalid @enderror" value="{{ old('patient_last_name', $appointment->patient_last_name) }}" required>
                            <div class="invalid-feedback d-none" id="err_patient_last_name"></div>
                            @error('patient_last_name')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Suffix --}}
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                                <label class="smaller fw-bold uppercase mb-0" style="color: var(--text-muted);">Suffix (Opt.)</label>
                            </div>
                            <input type="text" name="patient_suffix" id="revise_suffix" list="suffix_options" class="form-control uppercase fw-bold @error('patient_suffix') is-invalid @enderror" value="{{ old('patient_suffix', $appointment->patient_suffix) }}" placeholder="e.g. JR">
                            <datalist id="suffix_options">
                                <option value="JR"><option value="SR"><option value="II"><option value="III"><option value="IV"><option value="V">
                            </datalist>
                            <div class="invalid-feedback d-none" id="err_patient_suffix"></div>
                            @error('patient_suffix')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Sex --}}
                        <div class="col-md-6">
                            <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Sex</label>
                            <select name="patient_sex" id="patient_sex" class="form-select @error('patient_sex') is-invalid @enderror" required>
                                <option value="Male" {{ old('patient_sex', $appointment->patient_sex) === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('patient_sex', $appointment->patient_sex) === 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_patient_sex"></div>
                            @error('patient_sex')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Birthdate --}}
                        <div class="col-md-6">
                            <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Birthdate</label>
                            <input type="date" name="patient_birthdate" id="revise_bday" class="form-control @error('patient_birthdate') is-invalid @enderror" 
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
                            <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Contact Phone</label>
                            <div class="input-group">
                                <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                                @php
                                    $rawPhone = old('patient_phone', $appointment->patient_phone);
                                    $displayPhone = str_starts_with($rawPhone, '09') ? substr($rawPhone, 2) : $rawPhone;
                                @endphp
                                <input type="text" id="revise_phone_display" class="form-control py-3 shadow-none @error('patient_phone') is-invalid @enderror" placeholder="171234567" maxlength="9" value="{{ $displayPhone }}" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncRevisePhone();" required>
                            </div>
                            <input type="hidden" name="patient_phone" id="revise_in_phone" value="{{ $rawPhone }}">
                            <div class="invalid-feedback d-none" id="err_patient_phone"></div>
                            @error('patient_phone')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- 2. Residential Address Card --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100">
                    <h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-geo-alt-fill me-2"></i>2. Residential Address
                    </h5>

                    <div class="alert alert-clinical p-2.5 mb-3 border border-secondary border-opacity-10 text-start" style="background-color: rgba(0,0,0,0.015); border-radius: 8px;">
                        <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Saved Address on File:</div>
                        <div class="text-main small">{{ $appointment->patient_address }}</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="smaller text-muted fw-bold mb-1 uppercase">Province</label>
                            <select name="patient_province" id="revise_province" class="form-select @error('patient_province') is-invalid @enderror" required>
                                <option value="">Loading Provinces...</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_patient_province"></div>
                            @error('patient_province')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="smaller text-muted fw-bold mb-1 uppercase">City / Municipality</label>
                            <select name="patient_city" id="revise_city" class="form-select @error('patient_city') is-invalid @enderror" disabled required>
                                <option value="">Select Province First</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_patient_city"></div>
                            @error('patient_city')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="smaller text-muted fw-bold mb-1 uppercase">Barangay</label>
                            <select name="patient_barangay" id="revise_barangay" class="form-select @error('patient_barangay') is-invalid @enderror" disabled required>
                                <option value="">Select City First</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_patient_barangay"></div>
                            @error('patient_barangay')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="smaller text-muted fw-bold mb-1 uppercase">Street / House No.</label>
                            <input type="text" name="patient_street" id="revise_street" class="form-control uppercase @error('patient_street') is-invalid @enderror" value="{{ old('patient_street', $appointment->patient_street) }}" required>
                            <div class="invalid-feedback d-none" id="err_patient_street"></div>
                            @error('patient_street')
                                <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN: SERVICES & JUSTIFICATION --}}
            <div class="col-lg-6 d-flex flex-column gap-4">

                {{-- 3. Services & Billing Card --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100">
                    <h5 class="text-accent fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-flask me-2"></i>3. Requested Medical Services & Billing
                    </h5>

                    <div class="mb-2">
                        <input type="text" id="revise_service_search" class="form-control form-control-sm" placeholder="Search services/tests..." onkeyup="filterServices()">
                    </div>

                    <div class="p-3 border rounded row g-2 custom-scroll mb-3" id="services_container" style="max-height: 220px; overflow-y: auto; background-color: var(--bg-main) !important; border: 1.5px solid var(--border-color) !important;">
                        @foreach($services as $service)
                            <div class="form-check col-md-6 mb-1 revise-service-item">
                                <input class="form-check-input service-checkbox" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="revise_service_{{ $service->id }}" data-price="{{ $service->price }}" {{ in_array($service->id, $currentServiceIds) ? 'checked' : '' }} onchange="updateServicePriceTotal(true)">
                                <label class="form-check-label text-main small cursor-pointer" for="revise_service_{{ $service->id }}">
                                    {{ $service->name }} (₱{{ number_format($service->price, 2) }})
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="invalid-feedback d-none mb-3 text-danger fw-bold" id="err_service_ids"></div>
                    @error('service_ids')
                        <div class="text-danger small mb-3 fw-bold">{{ $message }}</div>
                    @enderror

                    <div class="col-12 mt-2">
                        <label class="smaller fw-bold uppercase mb-1 text-accent" style="font-size: 0.75rem;">
                            <i class="bi bi-cash-coin me-1"></i>Confirmed Collection Price / Total Bill (PHP)
                        </label>
                        <input type="number" step="0.01" name="payment_amount" id="payment_amount" class="form-control py-2 fw-bold text-accent @error('payment_amount') is-invalid @enderror" value="{{ old('payment_amount', number_format($initialPrice, 2, '.', '')) }}" required min="0">
                        <div class="invalid-feedback d-none" id="err_payment_amount"></div>
                        @error('payment_amount')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                        <small class="text-muted smaller mt-1 d-block">Price automatically updates when checking/unchecking tests above, but can be manually overridden if needed.</small>
                    </div>
                </div>

                {{-- 4. Administrative Justification Card --}}
                <div class="card p-4 border-secondary bg-card shadow-sm h-100">
                    <h5 class="text-danger fw-bold uppercase mb-3 small border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-shield-exclamation me-1"></i>4. Administrative Justification
                    </h5>

                    <div class="mb-3">
                        <label class="smaller text-muted d-block mb-2">Select the official justification for modifying this record for the audit log.</label>
                        <select id="revise_reason_select" name="reason" class="form-select @error('reason') is-invalid @enderror" required>
                            <option value="" disabled selected>-- Select a valid justification --</option>
                            <option value="Routine administrative update / profile maintenance">Routine administrative update / profile maintenance</option>
                            <option value="Official request for details correction">Official request for details correction</option>
                            <option value="Correction of typographical / data entry error">Correction of typographical / data entry error</option>
                            <option value="Others">Others (Specify below)</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_reason"></div>
                        @error('reason')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="revise_custom_reason_wrapper" class="mb-0 d-none">
                        <label class="smaller fw-bold uppercase mb-1">Specify Custom Reason</label>
                        <textarea id="revise_custom_reason" class="form-control" rows="3" placeholder="Explain the profile revision justification..."></textarea>
                    </div>
                </div>

            </div>
        </div>

        {{-- Submit Action Toolbar --}}
        <div class="d-flex gap-3 mt-4">
            <a href="{{ $backUrl }}" class="btn-custom btn-outline-secondary w-50 py-3 fw-bold uppercase text-decoration-none text-center btn-cancel-custom" style="font-size: 0.85rem;">
                Cancel
            </a>
            <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm">
                Save Revisions <i class="bi bi-check-circle-fill ms-1"></i>
            </button>
        </div>
    </form>
</div>

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

#edit-details-page-root .is-invalid {
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
const isDependentRecord = @json($appointment->dependent_id !== null);

document.addEventListener('DOMContentLoaded', async () => {
    // Initialize Unified Address Cascade
    await window.initUnifiedAddressCascade({
        provEl: document.getElementById('revise_province'),
        cityEl: document.getElementById('revise_city'),
        brgyEl: document.getElementById('revise_barangay'),
        streetEl: document.getElementById('revise_street'),
        savedProv: savedProvince,
        savedCity: savedCity, 
        savedBrgy: savedBarangay
    });

    // Evaluate and update price on initial load
    updateServicePriceTotal(false);

    const reasonSelect = document.getElementById('revise_reason_select');
    const textareaWrapper = document.getElementById('revise_custom_reason_wrapper');
    const textareaEl = document.getElementById('revise_custom_reason');

    if (reasonSelect && textareaEl && textareaWrapper) {
        reasonSelect.addEventListener('change', function() {
            clearFieldError(this);
            if (this.value === 'Others') {
                textareaWrapper.classList.remove('d-none');
                textareaEl.setAttribute('required', 'required');
                textareaEl.setAttribute('name', 'reason');
                reasonSelect.removeAttribute('name');
                textareaEl.value = '';
            } else {
                textareaWrapper.classList.add('d-none');
                textareaEl.removeAttribute('required');
                textareaEl.removeAttribute('name');
                reasonSelect.setAttribute('name', 'reason');
            }
        });
    }

    // Attach real-time error dismissal listeners
    document.querySelectorAll('#revise_details_form input, #revise_details_form select').forEach(input => {
        input.addEventListener('input', () => clearFieldError(input));
        input.addEventListener('change', () => clearFieldError(input));
    });

    document.getElementById('revise_details_form')?.addEventListener('submit', validateEditDetailsForm);
});

/* -------------------------------------------------------------------------- */
/* DYNAMIC SERVICE PRICE CALCULATION */
/* -------------------------------------------------------------------------- */
function updateServicePriceTotal(forceFromCheckboxes = true) {
    let sum = 0;
    document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
        sum += parseFloat(cb.dataset.price || 0);
    });

    const priceInput = document.getElementById('payment_amount');
    if (priceInput) {
        const currentVal = parseFloat(priceInput.value || 0);
        if (forceFromCheckboxes || currentVal === 0 || isNaN(currentVal)) {
            priceInput.value = sum.toFixed(2);
        }
        clearFieldError(priceInput);
    }
    resortReviseServices();
}

/* -------------------------------------------------------------------------- */
/* IN-PAGE "RESET EDITS" CONTROLLER (NO CONFIRMATION POPUP) */
/* -------------------------------------------------------------------------- */
async function resetReviseForm() {
    document.getElementById('patient_first_name').value = "{{ $appointment->patient_first_name }}";
    document.getElementById('patient_middle_name').value = "{{ $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name }}";
    document.getElementById('no_middle_name_toggle').checked = "{{ $appointment->patient_middle_name }}" === "N/A";
    document.getElementById('patient_middle_name').readOnly = "{{ $appointment->patient_middle_name }}" === "N/A";
    document.getElementById('patient_last_name').value = "{{ $appointment->patient_last_name }}";
    document.getElementById('revise_suffix').value = "{{ $appointment->patient_suffix }}";
    document.getElementById('patient_sex').value = "{{ $appointment->patient_sex }}";
    document.getElementById('revise_bday').value = "{{ $appointment->patient_birthdate ? $appointment->patient_birthdate->format('Y-m-d') : '' }}";

    let origPhone = "{{ $appointment->patient_phone }}".trim();
    if (origPhone.startsWith('+639')) origPhone = '09' + origPhone.substring(4);
    if (origPhone.startsWith('639')) origPhone = '09' + origPhone.substring(3);
    document.getElementById('revise_phone_display').value = origPhone.startsWith('09') ? origPhone.substring(2) : origPhone;
    document.getElementById('revise_in_phone').value = origPhone;

    await window.initUnifiedAddressCascade({
        provEl: document.getElementById('revise_province'),
        cityEl: document.getElementById('revise_city'),
        brgyEl: document.getElementById('revise_barangay'),
        streetEl: document.getElementById('revise_street'),
        savedProv: savedProvince,
        savedCity: savedCity,
        savedBrgy: savedBarangay
    });
    document.getElementById('revise_street').value = "{{ $appointment->patient_street }}";

    const origServices = @json($currentServiceIds);
    document.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.checked = origServices.includes(parseInt(cb.value));
    });

    const origPrice = {{ ($appointment->payment_amount && floatval($appointment->payment_amount) > 0) ? $appointment->payment_amount : $appointment->totalPrice() }};
    document.getElementById('payment_amount').value = parseFloat(origPrice).toFixed(2);

    const reasonSelect = document.getElementById('revise_reason_select');
    reasonSelect.value = '';
    const textareaWrapper = document.getElementById('revise_custom_reason_wrapper');
    const textareaEl = document.getElementById('revise_custom_reason');
    textareaWrapper.classList.add('d-none');
    textareaEl.removeAttribute('required');
    textareaEl.removeAttribute('name');
    reasonSelect.setAttribute('name', 'reason');
    textareaEl.value = '';

    document.querySelectorAll('#revise_details_form .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#revise_details_form .invalid-feedback').forEach(el => {
        el.innerText = '';
        el.classList.add('d-none');
        el.classList.remove('d-block');
    });
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

function syncRevisePhone() {
    const displayInput = document.getElementById('revise_phone_display');
    const hiddenInput = document.getElementById('revise_in_phone');
    if (displayInput && hiddenInput) {
        hiddenInput.value = displayInput.value ? '09' + displayInput.value : '';
    }
    clearFieldError(displayInput);
}

function resortReviseServices() {
    const container = document.getElementById('services_container');
    if (!container) return;
    const items = Array.from(container.querySelectorAll('.revise-service-item'));
    items.sort((a, b) => {
        const aChecked = a.querySelector('input[type="checkbox"]').checked ? 1 : 0;
        const bChecked = b.querySelector('input[type="checkbox"]').checked ? 1 : 0;
        if (aChecked !== bChecked) return bChecked - aChecked;
        return a.querySelector('label').innerText.trim().localeCompare(b.querySelector('label').innerText.trim());
    });
    items.forEach(item => container.appendChild(item));
}

function filterServices() {
    const query = document.getElementById('revise_service_search').value.toLowerCase();
    document.querySelectorAll('.revise-service-item').forEach(item => {
        const labelText = item.querySelector('label').innerText.toLowerCase();
        item.classList.toggle('d-none', !labelText.includes(query));
    });
}

function validateNameString(val, fieldName) {
    if (!val || val === 'N/A') return null;
    const charRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc \s.\'-]+$/;
    const startRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc ]/;
    const consecutiveRegex = /[.\'-]{2,}/;
    const letterRegex = /[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc]/;

    if (!charRegex.test(val)) return `${fieldName} may only contain letters, spaces, periods, hyphens, and apostrophes.`;
    if (!startRegex.test(val)) return `${fieldName} must start with a letter.`;
    if (!letterRegex.test(val)) return `${fieldName} must contain at least one letter.`;
    if (consecutiveRegex.test(val)) return `${fieldName} cannot contain consecutive punctuation marks.`;
    if (val.length > 60) return `${fieldName} cannot exceed 60 characters.`;
    return null;
}

function validateBirthdateInput() {
    const bdayInput = document.getElementById('revise_bday');
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
function validateEditDetailsForm(e) {
    let isValid = true;
    let firstInvalidInput = null;

    document.querySelectorAll('#revise_details_form .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#revise_details_form .invalid-feedback').forEach(el => {
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

    const suffixInput = document.getElementById('revise_suffix');
    if (suffixInput.value.trim()) {
        const suffixRegex = /^[a-zA-Z0-9\s.]+$/;
        if (!suffixRegex.test(suffixInput.value.trim())) markInvalid(suffixInput, 'err_patient_suffix', 'Suffix may only contain letters, numbers, spaces, and periods.');
        else if (suffixInput.value.trim().length > 10) markInvalid(suffixInput, 'err_patient_suffix', 'Suffix cannot exceed 10 characters.');
    }

    // 2. Sex Validation
    const sexSel = document.getElementById('patient_sex');
    if (!sexSel.value) markInvalid(sexSel, 'err_patient_sex', 'Please select a gender.');

    // 3. Birthdate Validation
    const bdayInput = document.getElementById('revise_bday');
    if (!validateBirthdateInput()) {
        if (!firstInvalidInput) firstInvalidInput = bdayInput;
        isValid = false;
    }

    // 4. Contact Phone Validation
    const phoneInput = document.getElementById('revise_in_phone');
    const displayPhoneInput = document.getElementById('revise_phone_display');
    const phoneRegex = /^09\d{9}$/;
    if (!displayPhoneInput.value.trim()) {
        markInvalid(displayPhoneInput, 'err_patient_phone', 'Contact phone number is required.');
    } else if (!phoneRegex.test(phoneInput.value.trim())) {
        markInvalid(displayPhoneInput, 'err_patient_phone', 'Phone number must start with 09 and contain exactly 11 digits.');
    }

    // 5. Address Validation
    const provSel = document.getElementById('revise_province');
    const citySel = document.getElementById('revise_city');
    const brgySel = document.getElementById('revise_barangay');
    const streetInput = document.getElementById('revise_street');

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

    // 7. Confirmed Price Validation
    const payAmountInput = document.getElementById('payment_amount');
    if (!payAmountInput.value.trim() || parseFloat(payAmountInput.value) < 0) {
        markInvalid(payAmountInput, 'err_payment_amount', 'Confirmed price must be a valid positive number.');
    }

    // 8. Administrative Justification Validation
    const reasonSelect = document.getElementById('revise_reason_select');
    const customReasonInput = document.getElementById('revise_custom_reason');
    if (!reasonSelect.value) {
        markInvalid(reasonSelect, 'err_reason', 'Administrative justification is required for the audit log.');
    } else if (reasonSelect.value === 'Others' && customReasonInput.value.trim().length < 5) {
        markInvalid(customReasonInput, 'err_reason', 'Custom justification must be at least 5 characters long.');
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

    return true;
}
</script>
@endpush