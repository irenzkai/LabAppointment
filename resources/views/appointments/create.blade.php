@extends('layouts.app')
@section('title', 'Create Appointment')

@section('content')
<div class="row justify-content-center animate-page">
    <div class="col-lg-11 col-xl-10 text-start">
        <div class="card p-0 border-secondary bg-card shadow-lg overflow-hidden">
            <div class="row g-0 align-items-stretch">
                {{-- LEFT: WIZARD FORM PANEL --}}
                <div class="col-md-8 border-end border-secondary border-opacity-25 p-4 p-md-5">
                    <form id="appointmentWizard" method="POST" action="{{ route('appointments.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('appointments.partials.wizard.step-1') {{-- Target Selection --}}
                        @include('appointments.partials.wizard.self-wizard') {{-- Dedicated Self Flow --}}
                        @include('appointments.partials.wizard.dependent-wizard') {{-- Dedicated Dependent Flow --}}
                    </form>
                </div>
                {{-- RIGHT: STICKY SUMMARY SIDEBAR --}}
                <div class="col-md-4 bg-secondary bg-opacity-10 p-4 p-md-5">
                    @include('appointments.partials.wizard.summary')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CUSTOM THEME-COMPATIBLE VALIDATION ALERT MODAL --}}
<div class="modal fade" id="wizardValidationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-accent display-4 d-block"></i>
            </div>
            <h5 class="modal-title text-main fw-bold mb-2 uppercase tracking-tighter" id="wizardValidationTitle">Selection Required</h5>
            <div id="wizardValidationMsg" class="text-secondary small mb-4">Please select a family member before proceeding.</div>
            <button type="button" class="btn-custom btn-accent w-100 py-3 uppercase fw-bold" data-bs-dismiss="modal">UNDERSTOOD</button>
        </div>
    </div>
</div>

{{-- SINGLE REUSABLE UNIFIED MULTI-FORMAT FILE PREVIEW LIGHTBOX OVERLAY --}}
@include('layouts.partials.lightbox-overlay')
@endsection

@push('styles')
<style>
/* Step 3 Selection States & Check Indicator Overrides */
.test-item {
    transition: all 0.2s ease-in-out;
}
.test-item:hover {
    background-color: rgba(25, 211, 140, 0.02);
}
.selected-test-item {
    background-color: rgba(25, 211, 140, 0.04) !important;
    border-left: 4px solid var(--brand-accent) !important;
}
.test-checkbox:checked + label .check-indicator {
    background-color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
}
.test-checkbox:checked + label .check-indicator i {
    display: block !important;
}

/* Step 4 High-Contrast Selected Time Slot Button Overrides */
#self_slots_container .btn-check:checked + label,
#dep_slots_container .btn-check:checked + label {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
    font-weight: 800 !important;
    box-shadow: 0 4px 14px rgba(25, 211, 140, 0.35) !important;
    transform: translateY(-2px);
}
#self_slots_container .btn-check:not(:checked) + label,
#dep_slots_container .btn-check:not(:checked) + label {
    border: 1.5px solid var(--border-color) !important;
    color: var(--text-main) !important;
    background-color: var(--bg-card) !important;
}
#self_slots_container .btn-check:not(:checked) + label:hover,
#dep_slots_container .btn-check:not(:checked) + label:hover {
    border-color: var(--brand-accent) !important;
    color: var(--brand-accent) !important;
}
#self_slots_container .btn-check:disabled + label,
#dep_slots_container .btn-check:disabled + label {
    opacity: 0.4 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}
.cursor-pointer {
    cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
const user = @json(Auth::user());
user.birthdate = "{{ Auth::user()->birthdate ? Auth::user()->birthdate->format('Y-m-d') : '' }}";
const apiBase = "https://psgc.gitlab.io/api";

let activeTargetType = 'self';
let currentStepNumber = 1;
let isRestoringDraft = false;

function getDraftKey(type) {
    return 'appointment_draft_' + (type || activeTargetType || 'self');
}

// Safely fetch dataset attributes from options
function getOptData(opt, attr) {
    if (!opt) return '';
    return opt.getAttribute('data-' + attr) || opt.dataset[attr] || '';
}

// --- NAME, SUFFIX & BIRTHDATE VALIDATION ENGINE ---
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

function validateSuffixString(val) {
    if (!val) return null;
    const v = val.trim();
    if (!v) return null;
    if (v.length > 10) return "Suffix cannot exceed 10 characters.";
    const suffixRegex = /^[a-zA-Z\s.]+$/;
    if (!suffixRegex.test(v)) return "Suffix may only contain letters, spaces, and periods.";
    return null;
}

function validateNameInput(input, fieldName, errDivId, required = true) {
    if (!input) return;
    const val = input.value.trim();
    if (!val) {
        if (required) {
            setFieldError(input, errDivId, `${fieldName} is required.`);
        } else {
            clearInlineError(input);
        }
        return;
    }
    const err = validateNameString(val, fieldName);
    if (err) {
        setFieldError(input, errDivId, err);
    } else {
        clearInlineError(input);
    }
}

function validateBirthdateInput() {
    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const bdayInput = document.getElementById(`${prefix}_bday`);
    if (!bdayInput || !bdayInput.value) return true;

    const dob = new Date(bdayInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (isNaN(dob.getTime()) || dob > today) return false;

    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;

    if (activeTargetType === 'self' && age < 18) return false;
    if (activeTargetType === 'dependent' && age >= 18) return false;

    return true;
}

function setFieldError(input, errDivId, message) {
    if (!input) return;
    input.classList.add('is-invalid');
    const errDiv = document.getElementById(errDivId);
    if (errDiv) {
        errDiv.innerText = message;
        errDiv.classList.remove('d-none');
        errDiv.classList.add('d-block');
    }
}

function clearInlineError(input) {
    if (!input) return;
    input.classList.remove('is-invalid');
    let errDiv = document.getElementById('err_' + input.id) || document.getElementById('err_' + input.name);
    if (errDiv) {
        errDiv.innerText = '';
        errDiv.classList.add('d-none');
        errDiv.classList.remove('d-block');
    }
}

// --- PROCEED FROM STEP 1 HANDLER ---
function proceedFromStep1() {
    const typeElement = document.querySelector('input[name="target_type"]:checked');
    if (!typeElement) return;

    const type = typeElement.value;
    if (type === 'bulk') {
        window.location.href = "{{ route('appointments.bulk') }}";
        return;
    }

    if (type === 'dependent') {
        const depSel = document.getElementById('dependent_id');
        if (!depSel || !depSel.value) {
            showWizardAlert("Please select a family member before proceeding.");
            depSel?.classList.add('is-invalid');
            return;
        }
        depSel.classList.remove('is-invalid');
    }

    goToPage(2);
}

// Handle explicit dropdown selection change for dependents
function handleDependentSelectChange() {
    const sel = document.getElementById('dependent_id');
    const opt = sel ? sel.options[sel.selectedIndex] : null;

    if (opt && opt.value) {
        localStorage.removeItem(getDraftKey('dependent'));
        fillDetails(
            'dep',
            getOptData(opt, 'first-name'),
            getOptData(opt, 'middle-name'),
            getOptData(opt, 'last-name'),
            getOptData(opt, 'suffix'),
            getOptData(opt, 'sex'),
            getOptData(opt, 'bday'),
            user.phone,
            getOptData(opt, 'street'),
            getOptData(opt, 'barangay'),
            getOptData(opt, 'city'),
            getOptData(opt, 'province')
        );
        updateSummary();
        saveAppointmentDraft();
    } else {
        clearDetails('dep');
        updateSummary();
    }
}

// --- TARGET TYPE SWITCHER & FLOW ISOLATION ---
function handleTargetChange(isInitialLoad = false) {
    const typeElement = document.querySelector('input[name="target_type"]:checked');
    if (!typeElement) return;

    const type = typeElement.value;
    activeTargetType = type;
    localStorage.setItem('appointment_active_target', type);

    const depDiv = document.getElementById('dep_selector_div');
    if (depDiv) {
        depDiv.classList.toggle('d-none', type !== 'dependent');
    }

    const selfContainer = document.getElementById('self-wizard-container');
    const depContainer = document.getElementById('dependent-wizard-container');

    if (type === 'self') {
        if (selfContainer) selfContainer.classList.remove('d-none');
        if (depContainer) depContainer.classList.add('d-none');
        disableContainerInputs(depContainer, true);
        enableContainerInputs(selfContainer, true);

        const typeSpan = document.getElementById('sum_patient_type');
        if (typeSpan) typeSpan.innerText = "Personal Account";

        if (!isInitialLoad) {
            const selfDraft = getDraftData('self');
            if (selfDraft && selfDraft.patient_first_name) {
                restoreDraftIntoForm(selfDraft, 'self');
            } else {
                resetSelfDetails();
            }
        }
    } else if (type === 'dependent') {
        if (selfContainer) selfContainer.classList.add('d-none');
        if (depContainer) depContainer.classList.remove('d-none');
        disableContainerInputs(selfContainer, true);
        enableContainerInputs(depContainer, true);

        const typeSpan = document.getElementById('sum_patient_type');
        if (typeSpan) typeSpan.innerText = "Family Dependent";

        if (!isInitialLoad) {
            const depDraft = getDraftData('dependent');
            const sel = document.getElementById('dependent_id');
            const opt = sel ? sel.options[sel.selectedIndex] : null;

            if (depDraft && depDraft.patient_first_name) {
                restoreDraftIntoForm(depDraft, 'dep');
            } else if (opt && opt.value) {
                fillDetails(
                    'dep',
                    getOptData(opt, 'first-name'),
                    getOptData(opt, 'middle-name'),
                    getOptData(opt, 'last-name'),
                    getOptData(opt, 'suffix'),
                    getOptData(opt, 'sex'),
                    getOptData(opt, 'bday'),
                    user.phone,
                    getOptData(opt, 'street'),
                    getOptData(opt, 'barangay'),
                    getOptData(opt, 'city'),
                    getOptData(opt, 'province')
                );
            } else {
                clearDetails('dep');
            }
        }
    }

    updateSummary();
    if (!isInitialLoad) saveAppointmentDraft();
}

function disableContainerInputs(container, disable) {
    if (!container) return;
    container.querySelectorAll('input, select, textarea').forEach(el => el.disabled = disable);
}

function enableContainerInputs(container, enable) {
    if (!container) return;
    container.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !enable);
}

// --- RESET DETAILS IMPLEMENTATION ---
function resetPatientDetails() {
    if (activeTargetType === 'dependent') {
        resetDependentDetails();
    } else {
        resetSelfDetails();
    }
}

function resetSelfDetails() {
    localStorage.removeItem(getDraftKey('self'));
    fillDetails(
        'self', 
        user.first_name, 
        user.middle_name, 
        user.last_name, 
        user.suffix, 
        user.sex, 
        user.birthdate, 
        user.phone, 
        user.street, 
        user.barangay, 
        user.city, 
        user.province
    );
    updateSummary();
    saveAppointmentDraft();
}

function resetDependentDetails() {
    localStorage.removeItem(getDraftKey('dependent'));
    const sel = document.getElementById('dependent_id');
    const opt = sel ? sel.options[sel.selectedIndex] : null;

    if (opt && opt.value) {
        fillDetails(
            'dep',
            getOptData(opt, 'first-name'),
            getOptData(opt, 'middle-name'),
            getOptData(opt, 'last-name'),
            getOptData(opt, 'suffix'),
            getOptData(opt, 'sex'),
            getOptData(opt, 'bday'),
            user.phone,
            getOptData(opt, 'street'),
            getOptData(opt, 'barangay'),
            getOptData(opt, 'city'),
            getOptData(opt, 'province')
        );
    } else {
        clearDetails('dep');
    }
    updateSummary();
    saveAppointmentDraft();
}

function copyParentAddressToDep() {
    setAddressDropdowns('dep', user.province, user.city, user.barangay);
    const streetInput = document.getElementById('dep_street');
    if (streetInput) streetInput.value = user.street || '';
    updateCompiledAddress('dep');
    saveAppointmentDraft();
}

// --- FORM AUTO-FILL & CLEAR HELPERS ---
function fillDetails(prefix, f, m, l, suffix, sex, bday, phone, street, barangay, city, province) {
    const fnEl = document.getElementById(`${prefix}_first_name`);
    if (fnEl) fnEl.value = f || '';

    const middleInput = document.getElementById(`${prefix}_middle_name`);
    const noneMnSwitch = document.getElementById(`${prefix}_no_mn`);

    if (!m || m === 'N/A' || m.toUpperCase() === 'N/A') {
        if (middleInput) {
            middleInput.value = 'N/A';
            middleInput.readOnly = true;
            middleInput.classList.add('opacity-50');
        }
        if (noneMnSwitch) noneMnSwitch.checked = true;
    } else {
        if (middleInput) {
            middleInput.value = m;
            middleInput.readOnly = false;
            middleInput.classList.remove('opacity-50');
        }
        if (noneMnSwitch) noneMnSwitch.checked = false;
    }

    const lnEl = document.getElementById(`${prefix}_last_name`);
    if (lnEl) lnEl.value = l || '';

    const sfxEl = document.getElementById(`${prefix}_suffix`);
    if (sfxEl) sfxEl.value = suffix || '';

    const sexEl = document.getElementById(`${prefix}_sex`);
    if (sexEl) sexEl.value = sex || '';

    const bdayEl = document.getElementById(`${prefix}_bday`);
    if (bdayEl) {
        const cleanBday = (bday || '').split('T')[0];
        bdayEl.value = cleanBday;
    }

    const hiddenPhoneVal = phone || '';
    const displayPhoneInput = document.getElementById(`${prefix}_phone_display`);
    const hiddenPhoneInput = document.getElementById(`${prefix}_phone_hidden`);

    if (displayPhoneInput && hiddenPhoneInput) {
        if (hiddenPhoneVal.startsWith('09')) {
            displayPhoneInput.value = hiddenPhoneVal.substring(2);
            hiddenPhoneInput.value = hiddenPhoneVal;
        } else {
            displayPhoneInput.value = hiddenPhoneVal;
            hiddenPhoneInput.value = hiddenPhoneVal ? (hiddenPhoneVal.startsWith('09') ? hiddenPhoneVal : '09' + hiddenPhoneVal) : '';
        }
    }

    const streetEl = document.getElementById(`${prefix}_street`);
    if (streetEl) streetEl.value = street || '';

    setAddressDropdowns(prefix, province, city, barangay);
}

function clearDetails(prefix) {
    [`${prefix}_first_name`, `${prefix}_middle_name`, `${prefix}_last_name`, `${prefix}_suffix`, `${prefix}_phone_hidden`, `${prefix}_bday`, `${prefix}_street`].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });

    const phoneDisp = document.getElementById(`${prefix}_phone_display`);
    if (phoneDisp) phoneDisp.value = "";

    const sexEl = document.getElementById(`${prefix}_sex`);
    if (sexEl) sexEl.value = "";

    const provEl = document.getElementById(`${prefix}_province`);
    if (provEl) provEl.value = "";

    const cityEl = document.getElementById(`${prefix}_city`);
    if (cityEl) {
        cityEl.innerHTML = '<option value="">Select Province First</option>';
        cityEl.disabled = true;
    }

    const brgyEl = document.getElementById(`${prefix}_brgy`);
    if (brgyEl) {
        brgyEl.innerHTML = '<option value="">Select City First</option>';
        brgyEl.disabled = true;
    }

    updateCompiledAddress(prefix);
}

// --- REFERRAL ATTACHMENT HANDLERS ---
function handleReferralUpload(input) {
    const file = input.files[0];
    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const previewContainer = document.getElementById(`${prefix}_referral_preview_container`);
    const inputWrapper = document.getElementById(`${prefix}_referral_wrapper`);
    const label = document.getElementById(`${prefix}_referral_file_label`);

    if (!file) {
        removeUploadedReferral(prefix);
        return;
    }

    if (inputWrapper) inputWrapper.classList.add('d-none');
    if (previewContainer) previewContainer.classList.remove('d-none');
    if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${file.name}`;

    const reader = new FileReader();
    reader.onload = function(e) {
        localStorage.setItem(`referral_base64_${prefix}`, e.target.result);
        localStorage.setItem(`referral_name_${prefix}`, file.name);
    };
    reader.readAsDataURL(file);
}

function removeUploadedReferral(prefixOverride = null) {
    const prefix = prefixOverride || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const input = document.getElementById(`${prefix}_referral`);
    const previewContainer = document.getElementById(`${prefix}_referral_preview_container`);
    const inputWrapper = document.getElementById(`${prefix}_referral_wrapper`);

    if (input) input.value = '';
    if (previewContainer) previewContainer.classList.add('d-none');
    if (inputWrapper) inputWrapper.classList.remove('d-none');

    localStorage.removeItem(`referral_base64_${prefix}`);
    localStorage.removeItem(`referral_name_${prefix}`);
}

function viewReferralFile(prefixOverride = null) {
    const prefix = prefixOverride || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const b64 = localStorage.getItem(`referral_base64_${prefix}`);
    const name = localStorage.getItem(`referral_name_${prefix}`) || "Doctor's Referral Note";

    if (b64 && typeof window.openFilePreview === 'function') {
        window.openFilePreview(b64, name);
    }
}

function restoreReferralPreview(prefix) {
    const b64 = localStorage.getItem(`referral_base64_${prefix}`);
    const name = localStorage.getItem(`referral_name_${prefix}`);

    if (b64 && name) {
        const previewContainer = document.getElementById(`${prefix}_referral_preview_container`);
        const inputWrapper = document.getElementById(`${prefix}_referral_wrapper`);
        const label = document.getElementById(`${prefix}_referral_file_label`);

        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${name}`;
    }
}

// --- PAYMENT RECEIPT FILE HANDLERS & QR CODE FETCHING ---
function handleReceiptUpload(input) {
    const file = input.files[0];
    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const previewContainer = document.getElementById(`${prefix}_receipt_preview_container`);
    const inputWrapper = document.getElementById(`${prefix}_receipt_input_wrapper`);
    const label = document.getElementById(`${prefix}_receipt_file_label`);

    if (!file) {
        removeUploadedReceipt(prefix);
        return;
    }

    if (inputWrapper) inputWrapper.classList.add('d-none');
    if (previewContainer) previewContainer.classList.remove('d-none');
    if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${file.name}`;

    const reader = new FileReader();
    reader.onload = function(e) {
        localStorage.setItem(`receipt_base64_${prefix}`, e.target.result);
        localStorage.setItem(`receipt_name_${prefix}`, file.name);
        updateFieldLockState(prefix);
        toggleSubmitButton(prefix);
    };
    reader.readAsDataURL(file);
}

function removeUploadedReceipt(prefixOverride = null) {
    const prefix = prefixOverride || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const input = document.getElementById(`${prefix}_in_receipt`);
    const previewContainer = document.getElementById(`${prefix}_receipt_preview_container`);
    const inputWrapper = document.getElementById(`${prefix}_receipt_input_wrapper`);

    if (input) input.value = '';
    if (previewContainer) previewContainer.classList.add('d-none');
    if (inputWrapper) inputWrapper.classList.remove('d-none');

    localStorage.removeItem(`receipt_base64_${prefix}`);
    localStorage.removeItem(`receipt_name_${prefix}`);
    updateFieldLockState(prefix);
    toggleSubmitButton(prefix);
}

function viewReceiptFile(prefixOverride = null) {
    const prefix = prefixOverride || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const b64 = localStorage.getItem(`receipt_base64_${prefix}`);
    const name = localStorage.getItem(`receipt_name_${prefix}`) || "Proof of Payment Receipt";

    if (b64 && typeof window.openFilePreview === 'function') {
        window.openFilePreview(b64, name);
    }
}

function restoreReceiptPreview(prefix) {
    const b64 = localStorage.getItem(`receipt_base64_${prefix}`);
    const name = localStorage.getItem(`receipt_name_${prefix}`);

    if (b64 && name) {
        const previewContainer = document.getElementById(`${prefix}_receipt_preview_container`);
        const inputWrapper = document.getElementById(`${prefix}_receipt_input_wrapper`);
        const label = document.getElementById(`${prefix}_receipt_file_label`);

        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${name}`;
    }
}

function updateFieldLockState(prefix) {
    const receiptInput = document.getElementById(`${prefix}_in_receipt`);
    const hasReceipt = (receiptInput && receiptInput.files && receiptInput.files.length > 0) || localStorage.getItem(`receipt_base64_${prefix}`) !== null;

    const payCash = document.getElementById(`${prefix}_pay_cash`);
    const payCashless = document.getElementById(`${prefix}_pay_cashless`);
    const providerRadios = document.querySelectorAll(`.${prefix}-prov-radio`);

    if (hasReceipt) {
        if (payCash) payCash.disabled = !payCash.checked;
        if (payCashless) payCashless.disabled = !payCashless.checked;
        providerRadios.forEach(radio => radio.disabled = !radio.checked);
    } else {
        if (payCash) payCash.disabled = false;
        if (payCashless) payCashless.disabled = false;
        providerRadios.forEach(radio => radio.disabled = false);
    }
}

function toggleSubmitButton(prefixOverride = null) {
    const prefix = prefixOverride || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const agreeCheckbox = document.getElementById(`${prefix}_agree_terms`);
    const submitBtn = document.getElementById(`${prefix}_submit_btn`);
    const payCashless = document.getElementById(`${prefix}_pay_cashless`);
    const receiptInput = document.getElementById(`${prefix}_in_receipt`);

    if (agreeCheckbox && submitBtn) {
        const isCashless = payCashless && payCashless.checked;
        const hasReceipt = (receiptInput && receiptInput.files && receiptInput.files.length > 0) || localStorage.getItem(`receipt_base64_${prefix}`) !== null;
        const isTermsAgreed = agreeCheckbox.checked;

        const isFormValid = isCashless ? (isTermsAgreed && hasReceipt) : isTermsAgreed;

        if (isFormValid) {
            submitBtn.removeAttribute('disabled');
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.style.pointerEvents = 'auto';
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.style.pointerEvents = 'none';
        }
    }
}

function toggleTestDetails(drawerId, btn) {
    const drawer = document.getElementById(drawerId);
    if (!drawer) return;

    drawer.classList.toggle('d-none');
    const icon = btn.querySelector('i');
    if (icon) {
        icon.classList.toggle('bi-chevron-down');
        icon.classList.toggle('bi-chevron-up');
    }
}

// --- RESORT SELECTED TESTS TO TOP IN STEP 3 ---
function resortTests(prefixOverride = null) {
    const prefix = prefixOverride || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const container = document.querySelector(`.${prefix}-test-list`);
    if (!container) return;

    const items = Array.from(container.querySelectorAll('.test-item'));
    items.sort((a, b) => {
        const aChecked = a.querySelector('.test-checkbox')?.checked ? 1 : 0;
        const bChecked = b.querySelector('.test-checkbox')?.checked ? 1 : 0;

        if (aChecked !== bChecked) return bChecked - aChecked;

        const aName = a.dataset.name || '';
        const bName = b.dataset.name || '';
        return aName.localeCompare(bName);
    });

    items.forEach(item => {
        const cb = item.querySelector('.test-checkbox');
        if (cb && cb.checked) {
            item.classList.add('selected-test-item');
        } else {
            item.classList.remove('selected-test-item');
        }
        container.appendChild(item);
    });
}

// --- DRAFT STORAGE & RESTORATION ENGINE ---
function getDraftData(type) {
    try {
        const raw = localStorage.getItem(getDraftKey(type));
        return raw ? JSON.parse(raw) : {};
    } catch(e) {
        return {};
    }
}

function saveAppointmentDraft() {
    if (isRestoringDraft) return;

    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const container = document.getElementById(`${activeTargetType}-wizard-container`);
    if (!container) return;

    const depSel = document.getElementById('dependent_id');

    const draftData = {
        target_type: activeTargetType,
        dependent_id: (activeTargetType === 'dependent' && depSel) ? depSel.value : '',
        patient_first_name: document.getElementById(`${prefix}_first_name`)?.value || '',
        patient_middle_name: document.getElementById(`${prefix}_middle_name`)?.value || '',
        patient_last_name: document.getElementById(`${prefix}_last_name`)?.value || '',
        patient_suffix: document.getElementById(`${prefix}_suffix`)?.value || '',
        patient_sex: document.getElementById(`${prefix}_sex`)?.value || '',
        patient_birthdate: document.getElementById(`${prefix}_bday`)?.value || '',
        patient_phone: document.getElementById(`${prefix}_phone_hidden`)?.value || '',
        patient_province: document.getElementById(`${prefix}_province`)?.value || '',
        patient_city: document.getElementById(`${prefix}_city`)?.value || '',
        patient_barangay: document.getElementById(`${prefix}_brgy`)?.value || '',
        patient_street: document.getElementById(`${prefix}_street`)?.value || '',
        appointment_date: document.getElementById(`${prefix}_wiz_date`)?.value || '',
        time_slot: document.querySelector(`#${activeTargetType}-wizard-container input[name="time_slot"]:checked`)?.value || '',
        payment_method: document.querySelector(`#${activeTargetType}-wizard-container input[name="payment_method"]:checked`)?.value || 'Cash',
        payment_provider_id: document.querySelector(`#${activeTargetType}-wizard-container input[name="payment_provider_id"]:checked`)?.value || ''
    };

    const selectedTests = Array.from(container.querySelectorAll('.test-checkbox:checked')).map(cb => cb.value);
    draftData['service_ids[]'] = selectedTests;

    localStorage.setItem(getDraftKey(activeTargetType), JSON.stringify(draftData));
    localStorage.setItem('appointment_active_target', activeTargetType);
}

function restoreDraftIntoForm(draftData, prefix) {
    if (!draftData || Object.keys(draftData).length === 0) return;

    fillDetails(
        prefix,
        draftData.patient_first_name,
        draftData.patient_middle_name,
        draftData.patient_last_name,
        draftData.patient_suffix,
        draftData.patient_sex,
        draftData.patient_birthdate,
        draftData.patient_phone,
        draftData.patient_street,
        draftData.patient_province,
        draftData.patient_city,
        draftData.patient_barangay
    );

    const testIds = draftData['service_ids[]'] || [];
    const container = document.getElementById(`${prefix === 'dep' ? 'dependent' : 'self'}-wizard-container`);

    if (container) {
        container.querySelectorAll('.test-checkbox').forEach(cb => {
            cb.checked = testIds.includes(cb.value);
        });
        resortTests(prefix);
    }

    if (draftData.appointment_date) {
        const dateEl = document.getElementById(`${prefix}_wiz_date`);
        if (dateEl) dateEl.value = draftData.appointment_date;
    }

    if (draftData.payment_method) {
        const payRadio = document.getElementById(`${prefix}_pay_${draftData.payment_method.toLowerCase()}`);
        if (payRadio) payRadio.checked = true;
    }

    if (draftData.payment_provider_id) {
        const provRadio = document.getElementById(`${prefix}_prov_${draftData.payment_provider_id}`);
        if (provRadio) {
            provRadio.checked = true;
            handleProviderChange(prefix, provRadio);
        }
    }

    restoreReferralPreview(prefix);
    restoreReceiptPreview(prefix);
}

// --- STEP NAVIGATION CONTROLLER ---
function goToPage(stepNum) {
    currentStepNumber = stepNum;
    localStorage.setItem('appointment_step', stepNum);

    document.querySelectorAll('.wiz-section').forEach(s => s.classList.add('d-none'));

    if (stepNum === 1) {
        document.getElementById('page-1')?.classList.remove('d-none');
    } else {
        const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
        const targetSection = document.getElementById(`${prefix}-step-${stepNum}`);
        if (targetSection) {
            targetSection.classList.remove('d-none');
        }
    }

    window.scrollTo(0, 0);
}

// --- PHONE & ADDRESS SYNCHRONIZATION ---
function syncSelfPhone() {
    const displayInput = document.getElementById('self_phone_display');
    const hiddenInput = document.getElementById('self_phone_hidden');

    if (displayInput && hiddenInput) {
        const val = displayInput.value.trim();
        hiddenInput.value = val ? (val.startsWith('09') ? val : '09' + val) : '';
    }

    updateSummary();
    saveAppointmentDraft();
}

function syncDepPhone() {
    const displayInput = document.getElementById('dep_phone_display');
    const hiddenInput = document.getElementById('dep_phone_hidden');

    if (displayInput && hiddenInput) {
        const val = displayInput.value.trim();
        hiddenInput.value = val ? (val.startsWith('09') ? val : '09' + val) : '';
    }

    updateSummary();
    saveAppointmentDraft();
}

function toggleSelfMN(chk) {
    const input = document.getElementById('self_middle_name');
    if (input) {
        input.value = chk.checked ? 'N/A' : '';
        input.readOnly = chk.checked;
        input.classList.toggle('opacity-50', chk.checked);
    }
    updateSummary();
    saveAppointmentDraft();
}

function toggleDepMN(chk) {
    const input = document.getElementById('dep_middle_name');
    if (input) {
        input.value = chk.checked ? 'N/A' : '';
        input.readOnly = chk.checked;
        input.classList.toggle('opacity-50', chk.checked);
    }
    updateSummary();
    saveAppointmentDraft();
}

// --- PSGC ADDRESS ENGINE ---
function findOptionFlexibly(selectEl, searchVal) {
    if (!selectEl || !searchVal) return null;
    const target = searchVal.toString().trim().toUpperCase();

    return Array.from(selectEl.options).find(opt => {
        if (!opt.value && !opt.text) return false;
        const optVal = opt.value.toString().trim().toUpperCase();
        const optText = opt.text.toString().trim().toUpperCase();

        if (optVal === target || optText === target) return true;

        const normOpt = optText.replace(/\b(CITY|PROVINCE|MUNICIPALITY) OF\b/g, '').replace(/[^A-Z0-9]/g, '');
        const normTarget = target.replace(/\b(CITY|PROVINCE|MUNICIPALITY) OF\b/g, '').replace(/[^A-Z0-9]/g, '');

        return normOpt && normOpt === normTarget;
    });
}

async function fetchProvinces() {
    try {
        const res = await fetch(`${apiBase}/provinces.json`);
        const data = await res.json();

        ['self', 'dep'].forEach(prefix => {
            const sel = document.getElementById(`${prefix}_province`);
            if (sel) {
                sel.innerHTML = '<option value="">Select Province</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                    sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
                });
            }
        });
    } catch (e) {
        console.error("Province API Error", e);
    }
}

async function fetchCities(provCode, prefix = 'self') {
    if (!provCode) return;
    const citySel = document.getElementById(`${prefix}_city`);
    const brgySel = document.getElementById(`${prefix}_brgy`);

    if (citySel) citySel.disabled = true;
    if (brgySel) brgySel.disabled = true;

    if (citySel) citySel.innerHTML = '<option value="">Loading Cities...</option>';

    try {
        const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities.json`);
        const data = await res.json();

        if (citySel) {
            citySel.innerHTML = '<option value="">Select City</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
            });
            citySel.disabled = false;
        }
    } catch (e) {
        console.error("City API Error", e);
    }

    updateCompiledAddress(prefix);
    saveAppointmentDraft();
}

async function fetchBarangays(cityCode, prefix = 'self') {
    if (!cityCode) return;
    const brgySel = document.getElementById(`${prefix}_brgy`);

    if (brgySel) {
        brgySel.disabled = true;
        brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
    }

    try {
        const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays.json`);
        const data = await res.json();

        if (brgySel) {
            brgySel.innerHTML = '<option value="">Select Barangay</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
            });
            brgySel.disabled = false;
        }
    } catch (e) {
        console.error("Barangay API Error", e);
    }

    updateCompiledAddress(prefix);
    saveAppointmentDraft();
}

async function setAddressDropdowns(prefix, provinceName, cityName, barangayName) {
    if (!provinceName) return;
    const provSel = document.getElementById(`${prefix}_province`);
    const citySel = document.getElementById(`${prefix}_city`);
    const brgySel = document.getElementById(`${prefix}_brgy`);

    if (!provSel) return;

    if (provSel.options.length <= 1) {
        await fetchProvinces();
    }

    let provOpt = findOptionFlexibly(provSel, provinceName);
    if (provOpt) {
        provSel.value = provOpt.value;
        await fetchCities(provSel.value, prefix);

        if (citySel && cityName) {
            let cityOpt = findOptionFlexibly(citySel, cityName);
            if (cityOpt) {
                citySel.value = cityOpt.value;
                await fetchBarangays(citySel.value, prefix);

                if (brgySel && barangayName) {
                    let brgyOpt = findOptionFlexibly(brgySel, barangayName);
                    if (brgyOpt) {
                        brgySel.value = brgyOpt.value;
                    }
                }
            }
        }
    }

    updateCompiledAddress(prefix);
}

function updateCompiledAddress(prefix = 'self') {
    const streetInput = document.getElementById(`${prefix}_street`);
    const brgy = document.getElementById(`${prefix}_brgy`);
    const city = document.getElementById(`${prefix}_city`);
    const prov = document.getElementById(`${prefix}_province`);

    const street = streetInput ? streetInput.value.trim() : '';
    const brgyName = brgy && brgy.selectedIndex >= 0 ? brgy.options[brgy.selectedIndex]?.text || '' : '';
    const cityName = city && city.selectedIndex >= 0 ? city.options[city.selectedIndex]?.text || '' : '';
    const provName = prov && prov.selectedIndex >= 0 ? prov.options[prov.selectedIndex]?.text || '' : '';

    const textEl = document.getElementById('compiled_address_text');
    const containerEl = document.getElementById('compiled_address_container');

    if (street || brgyName || cityName || provName) {
        let parts = [];
        if (street) parts.push(street);
        if (brgyName && !brgyName.includes('Select')) parts.push('BRGY. ' + brgyName);
        if (cityName && !cityName.includes('Select')) parts.push(cityName);
        if (provName && !provName.includes('Select')) parts.push(provName);

        const compiled = parts.join(', ').toUpperCase();
        if (textEl) textEl.innerText = compiled;
        if (containerEl) containerEl.classList.remove('d-none');
    } else {
        if (containerEl) containerEl.classList.add('d-none');
    }
}

function compileAppointmentAddress() {
    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const brgy = document.getElementById(`${prefix}_brgy`);
    const city = document.getElementById(`${prefix}_city`);
    const prov = document.getElementById(`${prefix}_province`);

    if (brgy && city && prov) {
        const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
        const cityName = city.options[city.selectedIndex]?.text || '';
        const provName = prov.options[prov.selectedIndex]?.text || '';

        if (brgyName && cityName && provName) {
            prov.options[prov.selectedIndex].value = provName;
            city.options[city.selectedIndex].value = cityName;
            brgy.options[brgy.selectedIndex].value = brgyName;
        }
    }
}

// --- SIDEBAR SUMMARY UPDATER ---
function updateSummary() {
    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const fn = document.getElementById(`${prefix}_first_name`)?.value || '';
    const mn = document.getElementById(`${prefix}_middle_name`)?.value || '';
    const ln = document.getElementById(`${prefix}_last_name`)?.value || '';

    const fullName = fn + (mn && mn !== 'N/A' ? ' ' + mn : '') + ' ' + ln;
    const nameEl = document.getElementById('sum_name');
    if (nameEl) nameEl.innerText = fullName.trim() || 'Not specified';

    const container = document.getElementById(`${activeTargetType}-wizard-container`);
    const selected = container ? container.querySelectorAll('.test-checkbox:checked') : [];

    const sidebarBadge = document.getElementById('test_count_badge');
    if (sidebarBadge) sidebarBadge.innerText = selected.length;

    let total = 0;
    let sidebarHtml = '';

    if (container) {
        container.querySelectorAll('.test-item').forEach(item => {
            const cb = item.querySelector('.test-checkbox');
            if (cb && cb.checked) {
                item.classList.add('selected-test-item');
                const price = parseFloat(cb.dataset.price);
                total += price;
                sidebarHtml += `
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-truncate me-2 small uppercase">${cb.dataset.name}</span>
                        <span class="text-neon fw-bold small">₱${price.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                    </div>
                `;
            } else {
                item.classList.remove('selected-test-item');
            }
        });
    }

    const sumTests = document.getElementById('sum_tests');
    if (sumTests) {
        sumTests.innerHTML = sidebarHtml || '<div class="italic text-muted">No tests selected</div>';
    }

    const sumTotal = document.getElementById('sum_total');
    if (sumTotal) {
        sumTotal.innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    resortTests(prefix);
}

// --- STEP 4: SCHEDULE & SLOTS FETCHING ---
async function fetchTimeSlots(prefix = null) {
    const currentPrefix = prefix || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const dateEl = document.getElementById(`${currentPrefix}_wiz_date`);
    if (!dateEl) return;

    const date = dateEl.value;
    const container = document.getElementById(`${currentPrefix}_slots_container`);
    if (!date || !container) return;

    container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-neon"></div></div>';

    try {
        const res = await fetch(`/api/check-slots?date=${date}`);
        const data = await res.json();

        if (data.is_closed) {
            container.innerHTML = '<div class="col-12 py-5 text-center text-danger border border-danger border-dashed rounded">Clinic Closed</div>';
            return;
        }

        let html = '';
        let start = new Date(`2000-01-01 ${data.config.opening_time}`);
        let end = new Date(`2000-01-01 ${data.config.closing_time}`);
        let availableCount = 0;

        const now = new Date();
        const todayLocal = now.toLocaleDateString('en-CA');

        const draft = getDraftData(activeTargetType);
        const savedSlot = draft['time_slot'] || '';

        while (start < end) {
            let tStr = start.toTimeString().split(' ')[0];
            let disp = start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            let isFull = (data.full_slots || []).includes(tStr);
            let isLunch = (data.config.has_lunch_break && tStr >= data.config.lunch_start && tStr < data.config.lunch_end);

            let isPast = false;
            if (date === todayLocal) {
                const leadTimeMs = (parseInt(data.config.lead_time_hours) || 0) * 3600 * 1000;
                const cutoffTime = now.getTime() + leadTimeMs;
                const slotDate = new Date(`${date} ${tStr}`);
                isPast = slotDate.getTime() < cutoffTime;
            }

            if (!isLunch && !isPast) {
                const isChecked = (tStr === savedSlot) ? 'checked' : '';
                html += `<div class="col-4">
                    <input type="radio" class="btn-check" name="time_slot" id="${currentPrefix}_slot_${tStr}" value="${tStr}" ${isFull ? 'disabled' : ''} ${isChecked} onchange="handleSlotSelection('${currentPrefix}')">
                    <label class="btn ${isFull ? 'btn-outline-danger opacity-50 cursor-not-allowed' : 'btn-outline-accent'} btn-sm w-100 py-2 fw-bold" for="${currentPrefix}_slot_${tStr}">${disp}</label>
                </div>`;
                availableCount++;
            }
            start.setMinutes(start.getMinutes() + data.config.slot_duration);
        }

        if (availableCount > 0) {
            container.innerHTML = html;
            const selectedRadio = container.querySelector('input[name="time_slot"]:checked');
            if (selectedRadio) {
                handleSlotSelection(currentPrefix);
            }
        } else {
            container.innerHTML = '<div class="col-12 py-5 text-center text-warning border border-warning border-dashed rounded"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>No available slots remaining for today. Please pick another date.</div>';
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div class="col-12 text-center py-4 text-danger">Error loading slots.</div>';
    }
}

function handleSlotSelection(prefix = null) {
    const currentPrefix = prefix || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const container = document.getElementById(`${currentPrefix}-wizard-container`);
    const selectedRadio = container ? container.querySelector('input[name="time_slot"]:checked') : null;

    if (selectedRadio) {
        const dateEl = document.getElementById(`${currentPrefix}_wiz_date`);
        const date = dateEl ? dateEl.value : '';
        const timeLabel = selectedRadio.nextElementSibling.innerText;

        setSchedule(date, timeLabel);
        saveAppointmentDraft();
    }
}

function setSchedule(date, time) {
    const sumSched = document.getElementById('sum_schedule');
    if (sumSched) sumSched.classList.remove('d-none');

    const sumDate = document.getElementById('sum_date');
    if (sumDate) sumDate.innerText = date;

    const sumTime = document.getElementById('sum_time');
    if (sumTime) sumTime.innerText = time;
}

// --- EVALUATE HIGHEST SATISFIED STEP ON REFRESH ---
async function determineHighestStep(draftData) {
    const targetType = activeTargetType || 'self';
    const prefix = targetType === 'dependent' ? 'dep' : 'self';

    if (targetType === 'dependent') {
        const depId = draftData.dependent_id || document.getElementById('dependent_id')?.value;
        if (!depId) return 1;
    }

    const fn = document.getElementById(`${prefix}_first_name`)?.value?.trim();
    const ln = document.getElementById(`${prefix}_last_name`)?.value?.trim();
    const sfx = document.getElementById(`${prefix}_suffix`)?.value?.trim();
    const sex = document.getElementById(`${prefix}_sex`)?.value;
    const bday = document.getElementById(`${prefix}_bday`)?.value;
    const phone = document.getElementById(`${prefix}_phone_hidden`)?.value?.trim();
    const prov = document.getElementById(`${prefix}_province`)?.value;
    const city = document.getElementById(`${prefix}_city`)?.value;
    const brgy = document.getElementById(`${prefix}_brgy`)?.value;
    const street = document.getElementById(`${prefix}_street`)?.value?.trim();

    const phoneRegex = /^09\d{9}$/;

    if (!fn || !ln || validateSuffixString(sfx) || !sex || !bday || !phone || !phoneRegex.test(phone) || !prov || !city || !brgy || !street) {
        return 2;
    }

    const dob = new Date(bday);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (isNaN(dob.getTime()) || dob > today) return 2;

    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;

    if (targetType === 'self' && age < 18) return 2;
    if (targetType === 'dependent' && age >= 18) return 2;

    const container = document.getElementById(`${targetType}-wizard-container`);
    const selectedTests = container ? container.querySelectorAll('.test-checkbox:checked') : [];

    if (selectedTests.length === 0) return 3;

    const date = document.getElementById(`${prefix}_wiz_date`)?.value;
    const slotRadio = container ? container.querySelector('input[name="time_slot"]:checked') : null;
    const slot = slotRadio ? slotRadio.value : '';

    const todayStr = today.toISOString().split('T')[0];

    if (!date || !slot || date < todayStr) return 4;

    try {
        const res = await fetch(`/api/check-slots?date=${date}`);
        const data = await res.json();

        if (data.is_closed) {
            clearSavedSlotInDraft();
            return 4;
        }

        const isFull = (data.full_slots || []).includes(slot);
        let isPast = false;
        const now = new Date();
        const todayLocal = now.toLocaleDateString('en-CA');

        if (date === todayLocal && data.config) {
            const leadTimeMs = (parseInt(data.config.lead_time_hours) || 0) * 3600 * 1000;
            const cutoffTime = now.getTime() + leadTimeMs;
            const slotDate = new Date(`${date}T${slot}`);
            isPast = slotDate.getTime() < cutoffTime;
        }

        const isLunch = data.config && data.config.has_lunch_break && slot >= data.config.lunch_start && slot < data.config.lunch_end;

        if (isFull || isPast || isLunch) {
            clearSavedSlotInDraft();
            return 4;
        }
    } catch (e) {
        console.warn("Slot verification check failed during page load:", e);
        return 4;
    }

    return 5;
}

function clearSavedSlotInDraft() {
    const draftRaw = localStorage.getItem(getDraftKey(activeTargetType));
    if (draftRaw) {
        try {
            const draft = JSON.parse(draftRaw);
            draft['time_slot'] = '';
            localStorage.setItem(getDraftKey(activeTargetType), JSON.stringify(draft));
        } catch(e) {}
    }
    const container = document.getElementById(`${activeTargetType}-wizard-container`);
    const radio = container ? container.querySelector('input[name="time_slot"]:checked') : null;
    if (radio) radio.checked = false;

    const sumSched = document.getElementById('sum_schedule');
    if (sumSched) sumSched.classList.add('d-none');
}

// --- STEP VALIDATORS ---
function validateSelfStep2() {
    return validateGenericStep2('self');
}

function validateDepStep2() {
    return validateGenericStep2('dep');
}

function validateGenericStep2(prefix) {
    let isValid = true;
    let firstInvalidInput = null;
    let firstErrorMessage = '';

    const container = document.getElementById(`${prefix === 'dep' ? 'dependent' : 'self'}-wizard-container`);
    container.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    container.querySelectorAll('.invalid-feedback').forEach(el => {
        el.classList.add('d-none');
        el.classList.remove('d-block');
        el.innerText = '';
    });

    function markInvalid(input, errDivId, message) {
        if (!input) return;
        input.classList.add('is-invalid');
        const errDiv = document.getElementById(errDivId);
        if (errDiv) {
            errDiv.innerText = message;
            errDiv.classList.remove('d-none');
            errDiv.classList.add('d-block');
        }
        if (isValid) {
            firstInvalidInput = input;
            firstErrorMessage = message;
        }
        isValid = false;
    }

    if (prefix === 'self') syncSelfPhone(); else syncDepPhone();

    const fnInput = document.getElementById(`${prefix}_first_name`);
    if (!fnInput || !fnInput.value.trim()) {
        markInvalid(fnInput, `err_${prefix}_first_name`, 'First Name is required.');
    } else {
        const check = validateNameString(fnInput.value.trim(), 'First Name');
        if (check) markInvalid(fnInput, `err_${prefix}_first_name`, check);
    }

    const mnInput = document.getElementById(`${prefix}_middle_name`);
    if (mnInput && mnInput.value.trim() && mnInput.value.trim() !== 'N/A') {
        const check = validateNameString(mnInput.value.trim(), 'Middle Name');
        if (check) markInvalid(mnInput, `err_${prefix}_middle_name`, check);
    }

    const lnInput = document.getElementById(`${prefix}_last_name`);
    if (!lnInput || !lnInput.value.trim()) {
        markInvalid(lnInput, `err_${prefix}_last_name`, 'Last Name is required.');
    } else {
        const check = validateNameString(lnInput.value.trim(), 'Last Name');
        if (check) markInvalid(lnInput, `err_${prefix}_last_name`, check);
    }

    const suffixInput = document.getElementById(`${prefix}_suffix`);
    if (suffixInput && suffixInput.value.trim()) {
        const sfxErr = validateSuffixString(suffixInput.value);
        if (sfxErr) markInvalid(suffixInput, `err_${prefix}_suffix`, sfxErr);
    }

    const sexInput = document.getElementById(`${prefix}_sex`);
    if (!sexInput || !sexInput.value) {
        markInvalid(sexInput, `err_${prefix}_sex`, 'Sex selection is required.');
    }

    const bdayInput = document.getElementById(`${prefix}_bday`);
    if (!bdayInput || !bdayInput.value) {
        markInvalid(bdayInput, `err_${prefix}_birthdate`, 'Birthdate is required.');
    } else {
        if (!validateBirthdateInput()) {
            markInvalid(bdayInput, `err_${prefix}_birthdate`, prefix === 'dep' ? 'Dependents must be minors under 18 years of age.' : 'Patients must be at least 18 years of age.');
        }
    }

    const phoneDisplay = document.getElementById(`${prefix}_phone_display`);
    const phoneHidden = document.getElementById(`${prefix}_phone_hidden`);
    const phoneVal = phoneHidden ? phoneHidden.value.trim() : '';
    const phoneRegex = /^09\d{9}$/;

    if (!phoneDisplay || !phoneDisplay.value.trim()) {
        markInvalid(phoneDisplay, `err_${prefix}_phone`, 'Contact phone number is required.');
    } else if (!phoneRegex.test(phoneVal)) {
        markInvalid(phoneDisplay, `err_${prefix}_phone`, 'Phone number must start with 09 and contain exactly 11 digits.');
    }

    const provSel = document.getElementById(`${prefix}_province`);
    const citySel = document.getElementById(`${prefix}_city`);
    const brgySel = document.getElementById(`${prefix}_brgy`);
    const streetInput = document.getElementById(`${prefix}_street`);

    if (!provSel || !provSel.value) markInvalid(provSel, `err_${prefix}_province`, 'Province selection is required.');
    if (!citySel || !citySel.value) markInvalid(citySel, `err_${prefix}_city`, 'City/Municipality selection is required.');
    if (!brgySel || !brgySel.value) markInvalid(brgySel, `err_${prefix}_barangay`, 'Barangay selection is required.');
    if (!streetInput || !streetInput.value.trim()) markInvalid(streetInput, `err_${prefix}_street`, 'Street address is required.');

    if (!isValid) {
        if (firstInvalidInput) {
            firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidInput.focus();
        }
        showWizardAlert(firstErrorMessage || "Please review the highlighted fields in Step 2 before proceeding.");
        return false;
    }

    goToPage(3);
}

function validateStep3() {
    const container = document.getElementById(`${activeTargetType}-wizard-container`);
    const selected = container ? container.querySelectorAll('.test-checkbox:checked') : [];

    if (selected.length === 0) {
        showWizardAlert("Please select at least one laboratory test before proceeding.");
        return;
    }

    goToPage(4);
}

function validateStep4() {
    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const dateInput = document.getElementById(`${prefix}_wiz_date`);
    const container = document.getElementById(`${activeTargetType}-wizard-container`);
    const selectedSlot = container ? container.querySelector('input[name="time_slot"]:checked') : null;

    if (!dateInput || !dateInput.value) {
        showWizardAlert("Please select a preferred date for your laboratory visit.");
        return;
    }

    if (!selectedSlot) {
        showWizardAlert("Please select an available preferred time slot before proceeding.");
        return;
    }

    goToPage(5);
}

function filterTestList(prefix) {
    const query = document.getElementById(`${prefix}TestSearch`)?.value.trim().toLowerCase() || '';
    const container = document.querySelector(`.${prefix}-test-list`);
    if (!container) return;

    container.querySelectorAll('.test-item').forEach(item => {
        const testName = item.dataset.name ? item.dataset.name.toLowerCase() : '';
        item.classList.toggle('d-none', !!query && !testName.includes(query));
    });
}

function togglePaymentFields(prefix) {
    const payCashless = document.getElementById(`${prefix}_pay_cashless`);
    const providerContainer = document.getElementById(`${prefix}_provider_container`);
    const qrSection = document.getElementById(`${prefix}_qr_section`);
    const receiptContainer = document.getElementById(`${prefix}_receipt_container`);

    const activeRadio = document.querySelector(`#${prefix}_provider_container .provider-radio:checked`) || document.querySelector(`.${prefix}-prov-radio:checked`);

    if (payCashless && payCashless.checked) {
        if (providerContainer) providerContainer.classList.remove('d-none');
        if (activeRadio) {
            if (qrSection) qrSection.classList.remove('d-none');
            if (receiptContainer) receiptContainer.classList.remove('d-none');

            const qrImg = document.getElementById(`${prefix}_selected_provider_qr`);
            const qrName = document.getElementById(`${prefix}_selected_provider_name`);

            if (qrImg && activeRadio.dataset.qr) {
                qrImg.src = activeRadio.dataset.qr;
            }
            if (qrName && activeRadio.dataset.name) {
                qrName.innerText = activeRadio.dataset.name;
            }
        } else {
            if (qrSection) qrSection.classList.add('d-none');
            if (receiptContainer) receiptContainer.classList.add('d-none');
        }
    } else {
        if (providerContainer) providerContainer.classList.add('d-none');
        if (receiptContainer) receiptContainer.classList.add('d-none');
        if (qrSection) qrSection.classList.add('d-none');
    }

    updateFieldLockState(prefix);
    toggleSubmitButton(prefix);
    saveAppointmentDraft();
}

function handleProviderChange(prefix, radio) {
    const qrImg = document.getElementById(`${prefix}_selected_provider_qr`);
    const qrName = document.getElementById(`${prefix}_selected_provider_name`);

    if (radio && radio.checked) {
        if (qrImg) qrImg.src = radio.dataset.qr;
        if (qrName) qrName.innerText = radio.dataset.name;
        togglePaymentFields(prefix);
    }
}

// Global zoomQR helper redirecting directly to openFilePreview so controls, image, and scale toolbar work
window.zoomQR = function(qrSrc) {
    if (qrSrc && typeof window.openFilePreview === 'function') {
        window.openFilePreview(qrSrc, 'E-Wallet Payment QR Code');
    }
};

// --- DOM DOCUMENT INITIALIZER & REFRESH RECOVERY ENGINE ---
document.addEventListener('DOMContentLoaded', async () => {
    isRestoringDraft = true;

    try {
        await fetchProvinces();
    } catch (e) {}

    const savedTarget = localStorage.getItem('appointment_active_target') || 'self';
    const targetRadio = document.querySelector(`input[name="target_type"][value="${savedTarget}"]`);

    if (targetRadio) {
        targetRadio.checked = true;
        activeTargetType = savedTarget;
    }

    const draftData = getDraftData(activeTargetType);

    if (draftData.dependent_id) {
        const depSelect = document.getElementById('dependent_id');
        if (depSelect) depSelect.value = draftData.dependent_id;
    }

    handleTargetChange(true);

    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';

    if (draftData && Object.keys(draftData).length > 0) {
        restoreDraftIntoForm(draftData, prefix);
    } else {
        resetPatientDetails();
    }

    if (draftData['patient_province'] || document.getElementById(`${prefix}_province`)?.value) {
        const provToLoad = draftData['patient_province'] || document.getElementById(`${prefix}_province`)?.value;
        const cityToLoad = draftData['patient_city'] || '';
        const brgyToLoad = draftData['patient_barangay'] || '';

        if (provToLoad) {
            await setAddressDropdowns(prefix, provToLoad, cityToLoad, brgyToLoad);
        }
    }

    const savedDate = draftData['appointment_date'] || document.getElementById(`${prefix}_wiz_date`)?.value;
    if (savedDate) {
        const dateEl = document.getElementById(`${prefix}_wiz_date`);
        if (dateEl) dateEl.value = savedDate;
        await fetchTimeSlots(prefix);
    }

    const highestStep = await determineHighestStep(draftData);
    const savedStep = parseInt(localStorage.getItem('appointment_step') || '1');
    const targetStep = Math.min(savedStep, highestStep);

    goToPage(targetStep > 0 ? targetStep : 1);

    isRestoringDraft = false;

    const depSelect = document.getElementById('dependent_id');
    if (depSelect) {
        depSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
            }
        });
    }

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, { sanitize: false });
    });

    const draftFields = document.querySelectorAll('#appointmentWizard input:not([type="password"]):not([type="file"]), #appointmentWizard select, #appointmentWizard textarea');
    draftFields.forEach(element => {
        element.addEventListener('input', () => {
            if (!isRestoringDraft) saveAppointmentDraft();
        });
        element.addEventListener('change', () => {
            if (!isRestoringDraft) saveAppointmentDraft();
        });
    });

    ['self', 'dep'].forEach(p => {
        const agree = document.getElementById(`${p}_agree_terms`);
        if (agree) {
            agree.addEventListener('change', () => toggleSubmitButton(p));
        }
        toggleSubmitButton(p);
    });

    const form = document.getElementById('appointmentWizard');
    if (form) {
        form.addEventListener('submit', function(e) {
            const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
            const submitBtn = document.getElementById(`${prefix}_submit_btn`);

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'SUBMITTING... <span class="spinner-border spinner-border-sm ms-2"></span>';
            }

            compileAppointmentAddress();

            localStorage.removeItem(getDraftKey('self'));
            localStorage.removeItem(getDraftKey('dependent'));
            localStorage.removeItem('appointment_active_target');
            localStorage.removeItem('appointment_step');
            localStorage.removeItem('referral_base64_self');
            localStorage.removeItem('referral_name_self');
            localStorage.removeItem('referral_base64_dep');
            localStorage.removeItem('referral_name_dep');
            localStorage.removeItem('receipt_base64_self');
            localStorage.removeItem('receipt_name_self');
            localStorage.removeItem('receipt_base64_dep');
            localStorage.removeItem('receipt_name_dep');
        });
    }
});

function showWizardAlert(msg) {
    document.getElementById('wizardValidationTitle').innerText = "Selection Required";
    document.getElementById('wizardValidationMsg').innerText = msg;
    const modalEl = document.getElementById('wizardValidationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
@endpush