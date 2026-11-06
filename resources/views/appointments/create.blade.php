<!-- resources/views/appointments/create.blade.php -->
@extends('layouts.app')
@section('title', 'Create Appointment')
@section('content')
<div class="row justify-content-center animate-page">
    <div class="col-lg-11 col-xl-10 text-start">
        <div class="card p-0 border-secondary bg-card shadow-lg overflow-hidden" style="border-color: var(--border-color) !important; border-radius: 16px;">
            <div class="row g-0 align-items-stretch">
                {{-- LEFT: WIZARD FORM PANEL --}}
                <div class="col-md-8 border-end border-secondary border-opacity-25 p-4 p-md-5" style="border-color: var(--border-color) !important;">
                    <form id="appointmentWizard" method="POST" action="{{ route('appointments.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('appointments.partials.wizard.step-1') {{-- Step 1: Target Selection --}}
                        @include('appointments.partials.wizard.self-wizard') {{-- Dedicated Self Flow (Steps 2-5) --}}
                        @include('appointments.partials.wizard.dependent-wizard') {{-- Dedicated Dependent Flow (Steps 2-5) --}}
                    </form>
                </div>

                {{-- RIGHT: STICKY SUMMARY SIDEBAR --}}
                <div class="col-md-4 bg-secondary bg-opacity-10 p-4 p-md-5 border-start border-secondary border-opacity-10" style="border-color: var(--border-color) !important;">
                    @include('appointments.partials.wizard.summary')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CUSTOM THEME-COMPATIBLE VALIDATION ALERT MODAL --}}
<div class="modal fade" id="wizardValidationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-secondary bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            <div class="mb-3">
                <i class="bi bi-exclamation-circle text-accent display-4 d-block"></i>
            </div>
            <h5 class="modal-title text-main fw-bold mb-2 uppercase tracking-tighter" id="wizardValidationTitle">Selection Required</h5>
            <div id="wizardValidationMsg" class="text-secondary small mb-4">Please fill in all required selections before proceeding.</div>
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
.test-checkbox:checked + label .check-indicator,
.pkg-checkbox:checked + label .check-indicator,
.service-checkbox:checked + label .check-indicator {
    background-color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
}
.test-checkbox:checked + label .check-indicator i,
.pkg-checkbox:checked + label .check-indicator i,
.service-checkbox:checked + label .check-indicator i {
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
const servicesMap = @json($services->keyBy('id'));
const packagesMap = @json($packages->keyBy('id'));
const apiBase = "https://psgc.gitlab.io/api";

let activeTargetType = 'self';
let currentStepNumber = 1;
let isRestoringDraft = false;

function getDraftKey(type) {
    return 'appointment_draft_' + (type || activeTargetType || 'self');
}

function getOptData(opt, attr) {
    if (!opt) return '';
    return opt.getAttribute('data-' + attr) || opt.dataset[attr] || '';
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
            showWizardAlert("Please select a registered family dependent before proceeding.");
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

        const selfCashless = document.getElementById('self_pay_cashless');
        const selfCash = document.getElementById('self_pay_cash');
        if (selfCash && (!selfCashless || !selfCashless.checked)) {
            selfCash.checked = true;
        }

        if (!isInitialLoad) {
            const selfDraft = getDraftData('self');
            if (selfDraft && selfDraft.patient_first_name) {
                restoreDraftIntoForm(selfDraft, 'self');
            } else {
                resetSelfDetails();
            }
        }
        togglePaymentFields('self');
    } else if (type === 'dependent') {
        if (selfContainer) selfContainer.classList.add('d-none');
        if (depContainer) depContainer.classList.remove('d-none');
        disableContainerInputs(selfContainer, true);
        enableContainerInputs(depContainer, true);

        const typeSpan = document.getElementById('sum_patient_type');
        if (typeSpan) typeSpan.innerText = "Family Dependent";

        const depCashless = document.getElementById('dep_pay_cashless');
        const depCash = document.getElementById('dep_pay_cash');
        if (depCash && (!depCashless || !depCashless.checked)) {
            depCash.checked = true;
        }

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
        togglePaymentFields('dep');
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
    const cashRadio = document.getElementById('self_pay_cash');
    if (cashRadio) cashRadio.checked = true;
    togglePaymentFields('self');
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
    const cashRadio = document.getElementById('dep_pay_cash');
    if (cashRadio) cashRadio.checked = true;
    togglePaymentFields('dep');
    updateSummary();
    saveAppointmentDraft();
}

// --- READONLY FORM POPULATION HELPERS ---
function fillDetails(prefix, f, m, l, suffix, sex, bday, phone, street, barangay, city, province) {
    const fnEl = document.getElementById(`${prefix}_first_name`);
    if (fnEl) fnEl.value = f || '';

    const middleInput = document.getElementById(`${prefix}_middle_name`);
    if (middleInput) middleInput.value = (!m || m === 'N/A' || m.toUpperCase() === 'N/A') ? 'N/A' : m;

    const lnEl = document.getElementById(`${prefix}_last_name`);
    if (lnEl) lnEl.value = l || '';

    const sfxEl = document.getElementById(`${prefix}_suffix`);
    if (sfxEl) sfxEl.value = suffix || '';

    const sexEl = document.getElementById(`${prefix}_sex`);
    if (sexEl) sexEl.value = sex || '';

    const bdayEl = document.getElementById(`${prefix}_bday`);
    if (bdayEl) bdayEl.value = (bday || '').split('T')[0];

    const phoneDisp = document.getElementById(`${prefix}_phone_display`);
    const phoneHidden = document.getElementById(`${prefix}_phone_hidden`);
    const rawPhone = phone || '';

    if (phoneDisp && phoneHidden) {
        phoneDisp.value = rawPhone.startsWith('09') ? rawPhone.substring(2) : rawPhone;
        phoneHidden.value = rawPhone;
    }

    const streetEl = document.getElementById(`${prefix}_street`);
    if (streetEl) streetEl.value = street || '';

    const provEl = document.getElementById(`${prefix}_province`);
    if (provEl) provEl.value = province || '';

    const cityEl = document.getElementById(`${prefix}_city`);
    if (cityEl) cityEl.value = city || '';

    const brgyEl = document.getElementById(`${prefix}_brgy`);
    if (brgyEl) brgyEl.value = barangay || '';

    updateCompiledAddress(prefix);
}

function clearDetails(prefix) {
    [`${prefix}_first_name`, `${prefix}_middle_name`, `${prefix}_last_name`, `${prefix}_suffix`, `${prefix}_phone_hidden`, `${prefix}_bday`, `${prefix}_street`, `${prefix}_province`, `${prefix}_city`, `${prefix}_brgy`].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });

    const phoneDisp = document.getElementById(`${prefix}_phone_display`);
    if (phoneDisp) phoneDisp.value = "";

    const sexEl = document.getElementById(`${prefix}_sex`);
    if (sexEl) sexEl.value = "";

    updateCompiledAddress(prefix);
}

function updateCompiledAddress(prefix = 'self') {
    const street = document.getElementById(`${prefix}_street`)?.value?.trim() || '';
    const brgy = document.getElementById(`${prefix}_brgy`)?.value?.trim() || '';
    const city = document.getElementById(`${prefix}_city`)?.value?.trim() || '';
    const prov = document.getElementById(`${prefix}_province`)?.value?.trim() || '';

    const textEl = document.getElementById('compiled_address_text');
    const containerEl = document.getElementById('compiled_address_container');

    if (street || brgy || city || prov) {
        let parts = [];
        if (street) parts.push(street);
        if (brgy && !brgy.includes('Select')) parts.push('BRGY. ' + brgy);
        if (city && !city.includes('Select')) parts.push(city);
        if (prov && !prov.includes('Select')) parts.push(prov);

        const compiled = parts.join(', ').toUpperCase();
        if (textEl) textEl.innerText = compiled;
        if (containerEl) containerEl.classList.remove('d-none');
    } else {
        if (containerEl) containerEl.classList.add('d-none');
    }
}

// --- REFERRAL & RECEIPT ATTACHMENT HANDLERS ---
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

// --- BIDIRECTIONAL ANTI-DOUBLE SELECTION & ANTI-CONFLICTING PACKAGES ENGINE ---
function recalculateTestDependencies(prefixOverride = null) {
    const prefix = prefixOverride || (activeTargetType === 'dependent' ? 'dep' : 'self');
    const container = document.getElementById(`${prefix === 'dep' ? 'dependent' : 'self'}-wizard-container`);
    if (!container) return;

    // 1. Collect all service IDs covered by checked packages
    const coveredByCheckedPackages = new Set();
    const activePackages = Array.from(container.querySelectorAll('.pkg-checkbox:checked'));
    
    activePackages.forEach(pkgCb => {
        const pkgId = pkgCb.value;
        const pkg = packagesMap[pkgId];
        if (pkg && pkg.services) {
            pkg.services.forEach(svc => coveredByCheckedPackages.add(svc.id.toString()));
        } else if (pkgCb.dataset.services) {
            try {
                const sArr = JSON.parse(pkgCb.dataset.services);
                sArr.forEach(id => coveredByCheckedPackages.add(id.toString()));
            } catch(e) {}
        }
    });

    // 2. Collect all individually checked services
    const checkedIndividualServices = new Set();
    container.querySelectorAll('.service-checkbox:checked, .test-checkbox:checked').forEach(svcCb => {
        checkedIndividualServices.add(svcCb.value.toString());
    });

    // 3. Package Conflict Check (Package-to-Package and Service-to-Package)
    container.querySelectorAll('.pkg-checkbox').forEach(pkgCb => {
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

        const itemWrapper = pkgCb.closest('.test-item');
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

        const conflictsWithPackage = pkgServiceIds.some(id => coveredByCheckedPackages.has(id));
        const conflictsWithIndividual = pkgServiceIds.some(id => checkedIndividualServices.has(id));
        const isConflicting = conflictsWithPackage || conflictsWithIndividual;

        if (isConflicting) {
            pkgCb.disabled = true;
            if (itemWrapper) {
                itemWrapper.classList.add('opacity-50');
                itemWrapper.style.pointerEvents = 'none'; // BARS CLICK AT UI
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
    container.querySelectorAll('.service-checkbox, .test-checkbox').forEach(svcCb => {
        const isCovered = coveredByCheckedPackages.has(svcCb.value.toString());
        const itemWrapper = svcCb.closest('.test-item');
        const badge = itemWrapper ? itemWrapper.querySelector('.pkg-covered-badge') : null;

        if (isCovered) {
            svcCb.checked = false;
            svcCb.disabled = true;
            if (itemWrapper) {
                itemWrapper.classList.add('opacity-50');
                itemWrapper.classList.remove('selected-test-item');
                itemWrapper.style.pointerEvents = 'none'; // BARS CLICK AT UI
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

function handleTestSelectionChange(prefix = null) {
    const currentPrefix = prefix || (activeTargetType === 'dependent' ? 'dep' : 'self');
    recalculateTestDependencies(currentPrefix);
    updateSummary();
    saveAppointmentDraft();
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
        const aChecked = a.querySelector('.test-checkbox, .pkg-checkbox, .service-checkbox')?.checked ? 1 : 0;
        const bChecked = b.querySelector('.test-checkbox, .pkg-checkbox, .service-checkbox')?.checked ? 1 : 0;
        if (aChecked !== bChecked) return bChecked - aChecked;

        const aName = a.dataset.name || '';
        const bName = b.dataset.name || '';
        return aName.localeCompare(bName);
    });

    items.forEach(item => {
        const cb = item.querySelector('.test-checkbox, .pkg-checkbox, .service-checkbox');
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
        appointment_date: document.getElementById(`${prefix}_wiz_date`)?.value || '',
        time_slot: document.querySelector(`#${activeTargetType}-wizard-container input[name="time_slot"]:checked`)?.value || '',
        payment_method: document.querySelector(`#${activeTargetType}-wizard-container input[name="payment_method"]:checked`)?.value || 'Cash',
        payment_provider_id: document.querySelector(`#${activeTargetType}-wizard-container input[name="payment_provider_id"]:checked`)?.value || ''
    };

    const selectedServices = Array.from(container.querySelectorAll('.service-checkbox:checked, .test-checkbox:checked')).map(cb => cb.value);
    const selectedPackages = Array.from(container.querySelectorAll('.pkg-checkbox:checked')).map(cb => cb.value);
    
    draftData['service_ids[]'] = selectedServices;
    draftData['package_ids[]'] = selectedPackages;

    localStorage.setItem(getDraftKey(activeTargetType), JSON.stringify(draftData));
    localStorage.setItem('appointment_active_target', activeTargetType);
}

function restoreDraftIntoForm(draftData, prefix) {
    if (!draftData || Object.keys(draftData).length === 0) return;

    const serviceIds = draftData['service_ids[]'] || [];
    const packageIds = draftData['package_ids[]'] || [];
    const container = document.getElementById(`${prefix === 'dep' ? 'dependent' : 'self'}-wizard-container`);
    
    if (container) {
        container.querySelectorAll('.pkg-checkbox').forEach(cb => {
            cb.checked = packageIds.includes(cb.value);
        });
        container.querySelectorAll('.service-checkbox, .test-checkbox').forEach(cb => {
            cb.checked = serviceIds.includes(cb.value);
        });
        recalculateTestDependencies(prefix);
        resortTests(prefix);
    }

    if (draftData.appointment_date) {
        const dateEl = document.getElementById(`${prefix}_wiz_date`);
        if (dateEl) dateEl.value = draftData.appointment_date;
    }

    const targetMethod = (draftData.payment_method || 'Cash').toLowerCase();
    const payRadio = document.getElementById(`${prefix}_pay_${targetMethod}`);
    if (payRadio) {
        payRadio.checked = true;
    } else {
        const cashRadio = document.getElementById(`${prefix}_pay_cash`);
        if (cashRadio) cashRadio.checked = true;
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
    togglePaymentFields(prefix);
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
        if (stepNum === 5) {
            const cashRadio = document.getElementById(`${prefix}_pay_cash`);
            const cashlessRadio = document.getElementById(`${prefix}_pay_cashless`);
            if (cashRadio && (!cashlessRadio || !cashlessRadio.checked)) {
                cashRadio.checked = true;
            }
            togglePaymentFields(prefix);
        }
    }
    window.scrollTo(0, 0);
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
    const selectedPkgs = container ? container.querySelectorAll('.pkg-checkbox:checked') : [];
    const selectedSvcs = container ? container.querySelectorAll('.service-checkbox:checked, .test-checkbox:checked') : [];
    
    const sidebarBadge = document.getElementById('test_count_badge');
    if (sidebarBadge) sidebarBadge.innerText = selectedPkgs.length + selectedSvcs.length;

    let total = 0;
    let sidebarHtml = '';

    // Tally Bundled Health Packages
    selectedPkgs.forEach(cb => {
        const price = parseFloat(cb.dataset.price || 0);
        total += price;
        sidebarHtml += `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-truncate me-2 small uppercase fw-bold">
                    <span class="badge bg-success bg-opacity-15 text-accent border border-success border-opacity-25 py-0.5 px-1.5 me-1" style="font-size: 0.6rem;">PKG</span>
                    ${cb.dataset.name}
                </span>
                <span class="text-accent fw-bold small text-nowrap">₱${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </div>
        `;
    });

    // Tally Individual Diagnostic Services
    selectedSvcs.forEach(cb => {
        const price = parseFloat(cb.dataset.price || 0);
        total += price;
        sidebarHtml += `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-truncate me-2 small uppercase">${cb.dataset.name}</span>
                <span class="text-accent fw-bold small text-nowrap">₱${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </div>
        `;
    });

    const sumTests = document.getElementById('sum_tests');
    if (sumTests) {
        sumTests.innerHTML = sidebarHtml || '<div class="italic text-muted">No tests or packages selected</div>';
    }

    const sumTotal = document.getElementById('sum_total');
    if (sumTotal) {
        sumTotal.innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

    container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-accent"></div></div>';

    try {
        const res = await fetch(`/api/check-slots?date=${date}`);
        const data = await res.json();

        if (data.is_closed) {
            container.innerHTML = '<div class="col-12 py-5 text-center text-danger border border-danger border-dashed rounded-3"><i class="bi bi-door-closed-fill me-2"></i>Clinic Closed on this Date</div>';
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
            container.innerHTML = '<div class="col-12 py-5 text-center text-warning border border-warning border-dashed rounded-3"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>No available slots remaining for today. Please pick another date.</div>';
        }
    } catch (e) {
        console.error(e);
        container.innerHTML = '<div class="col-12 text-center py-4 text-danger">Error loading slots. Please try again.</div>';
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

// --- STEP VALIDATORS ---
function validateSelfStep2() {
    goToPage(3);
}

function validateDepStep2() {
    const depSel = document.getElementById('dependent_id');
    if (!depSel || !depSel.value) {
        showWizardAlert("Please select a registered family dependent before proceeding.");
        depSel?.classList.add('is-invalid');
        return;
    }
    depSel.classList.remove('is-invalid');
    goToPage(3);
}

function validateStep3() {
    const container = document.getElementById(`${activeTargetType}-wizard-container`);
    const selectedPkgs = container ? Array.from(container.querySelectorAll('.pkg-checkbox:checked')) : [];
    const selectedSvcs = container ? Array.from(container.querySelectorAll('.service-checkbox:checked, .test-checkbox:checked')) : [];

    if (selectedPkgs.length === 0 && selectedSvcs.length === 0) {
        showWizardAlert("Please select at least one individual test or health package before proceeding.");
        return;
    }

    // Enforce no package-to-package conflict
    const coveredServices = new Set();
    for (const pkgCb of selectedPkgs) {
        const pkg = packagesMap[pkgCb.value];
        let serviceIds = [];
        if (pkg && pkg.services) {
            serviceIds = pkg.services.map(s => s.id.toString());
        } else if (pkgCb.dataset.services) {
            try { serviceIds = JSON.parse(pkgCb.dataset.services).map(String); } catch(e) {}
        }

        for (const sId of serviceIds) {
            if (coveredServices.has(sId)) {
                showWizardAlert("Conflicting packages detected: multiple selected packages share identical tests. Please remove one of the conflicting packages.");
                return;
            }
            coveredServices.add(sId);
        }
    }

    // Enforce no package-to-service conflict
    for (const svcCb of selectedSvcs) {
        if (coveredServices.has(svcCb.value.toString())) {
            showWizardAlert("Double selection detected: An individual test is already covered in a selected package.");
            return;
        }
    }

    goToPage(4);
}

function validateStep4() {
    const prefix = activeTargetType === 'dependent' ? 'dep' : 'self';
    const dateInput = document.getElementById(`${prefix}_wiz_date`);
    const container = document.getElementById(`${activeTargetType}-wizard-container`);
    const selectedSlot = container ? container.querySelector('input[name="time_slot"]:checked') : null;

    if (!dateInput || !dateInput.value) {
        showWizardAlert("Please select a preferred date for your clinical visit.");
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
    const container = document.getElementById(`${prefix}-step-3`);
    if (!container) return;

    container.querySelectorAll('.test-item').forEach(item => {
        if (item.classList.contains('gender-hidden')) {
            item.classList.add('d-none');
            return;
        }
        const testName = item.dataset.name ? item.dataset.name.toLowerCase() : '';
        item.classList.toggle('d-none', !!query && !testName.includes(query));
    });
}

function togglePaymentFields(prefix) {
    const payCash = document.getElementById(`${prefix}_pay_cash`);
    const payCashless = document.getElementById(`${prefix}_pay_cashless`);
    const providerContainer = document.getElementById(`${prefix}_provider_container`);
    const qrSection = document.getElementById(`${prefix}_qr_section`);
    const receiptContainer = document.getElementById(`${prefix}_receipt_container`);
    const activeRadio = document.querySelector(`#${prefix}_provider_container .provider-radio:checked`) || document.querySelector(`.${prefix}-prov-radio:checked`);

    if (payCash && payCashless && !payCash.checked && !payCashless.checked) {
        payCash.checked = true;
    }

    if (payCashless && payCashless.checked) {
        if (providerContainer) providerContainer.classList.remove('d-none');
        if (activeRadio) {
            if (qrSection) qrSection.classList.remove('d-none');
            if (receiptContainer) receiptContainer.classList.remove('d-none');
            const qrImg = document.getElementById(`${prefix}_selected_provider_qr`);
            const qrName = document.getElementById(`${prefix}_selected_provider_name`);
            if (qrImg && activeRadio.dataset.qr) qrImg.src = activeRadio.dataset.qr;
            if (qrName && activeRadio.dataset.name) qrName.innerText = activeRadio.dataset.name;
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

window.zoomQR = function(qrSrc) {
    if (qrSrc && typeof window.openFilePreview === 'function') {
        window.openFilePreview(qrSrc, 'E-Wallet Payment QR Code');
    }
};

// --- DOM DOCUMENT INITIALIZER & REFRESH RECOVERY ENGINE ---
document.addEventListener('DOMContentLoaded', async () => {
    isRestoringDraft = true;
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

    const savedDate = draftData['appointment_date'] || document.getElementById(`${prefix}_wiz_date`)?.value;
    if (savedDate) {
        const dateEl = document.getElementById(`${prefix}_wiz_date`);
        if (dateEl) dateEl.value = savedDate;
        await fetchTimeSlots(prefix);
    }

    const savedStep = parseInt(localStorage.getItem('appointment_step') || '1');
    goToPage(savedStep > 0 ? savedStep : 1);

    const initialCash = document.getElementById(`${prefix}_pay_cash`);
    const initialCashless = document.getElementById(`${prefix}_pay_cashless`);
    if (initialCash && (!initialCashless || !initialCashless.checked)) {
        initialCash.checked = true;
    }
    togglePaymentFields(prefix);

    isRestoringDraft = false;

    const depSelect = document.getElementById('dependent_id');
    if (depSelect) {
        depSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
            }
        });
    }

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
                submitBtn.innerHTML = 'SUBMITTING... <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>';
            }

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