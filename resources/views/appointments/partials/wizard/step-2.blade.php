<!-- PAGE 2: PATIENT DETAILS -->
<div class="wiz-section d-none text-start animate-page" id="page-2">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-2">
        <div>
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Patient Information</h3>
            <p class="text-secondary small mb-0">Please verify or enter the details for this medical record.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-accent py-2 px-3 fw-bold" onclick="resetPatientDetails()">
            <i class="bi bi-arrow-counterclockwise me-1"></i> RESET INFO
        </button>
    </div>

    <div class="row g-3">
        {{-- Basic Identity Row with Fixed-Height Label Wrappers --}}
        <div class="col-md-3">
            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">First Name</label>
            </div>
            <input type="text" name="patient_first_name" id="in_first_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="First Name" oninput="validateNameInput(this, 'First Name', 'err_first_name', true); updateSummary();" required>
            <div class="invalid-feedback d-none" id="err_first_name"></div>
        </div>

        <div class="col-md-3">
            <div class="d-flex justify-content-between align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">Middle Name</label>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="profile_no_mn" onclick="toggleProfileMN(this)" style="margin-top: 0.15rem;">
                    <label class="smaller text-muted" style="font-size: 0.65rem; line-height: 1;" for="profile_no_mn">None</label>
                </div>
            </div>
            <input type="text" name="patient_middle_name" id="in_middle_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Middle Name" oninput="validateNameInput(this, 'Middle Name', 'err_middle_name', false); updateSummary();">
            <div class="invalid-feedback d-none" id="err_middle_name"></div>
        </div>

        <div class="col-md-3">
            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">Last Name</label>
            </div>
            <input type="text" name="patient_last_name" id="in_last_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Last Name" oninput="validateNameInput(this, 'Last Name', 'err_last_name', true); updateSummary();" required>
            <div class="invalid-feedback d-none" id="err_last_name"></div>
        </div>

        <div class="col-md-3">
            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">Suffix (Opt.)</label>
            </div>
            <input type="text" name="patient_suffix" id="in_suffix" list="suffix_options" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="e.g. JR" oninput="updateSummary(); clearInlineError(this)">
            <datalist id="suffix_options">
                <option value="JR"><option value="SR"><option value="II"><option value="III"><option value="IV"><option value="V">
            </datalist>
            <div class="invalid-feedback d-none" id="err_suffix"></div>
        </div>

        {{-- Sex Selector --}}
        <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
            <select name="patient_sex" id="in_sex" class="form-select py-3 shadow-none" onchange="clearInlineError(this); updateSummary();" required>
                <option value="">Select Sex</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
            <div class="invalid-feedback d-none" id="err_sex"></div>
        </div>

        {{-- Birthdate Input --}}
        <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
            <input type="date" name="patient_birthdate" id="in_bday" class="form-control py-3 shadow-none" onchange="validateBirthdateInput(); updateSummary();" required>
            <div class="invalid-feedback d-none" id="err_birthdate"></div>
        </div>

        {{-- Contact Number --}}
        <div class="col-12 mt-2">
            <label class="small text-secondary fw-bold mb-1 uppercase">Contact Number</label>
            <div class="input-group">
                <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                <input type="text" id="phone_display" class="form-control py-3 shadow-none" placeholder="171234567" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncWizardPhone(); clearInlineError(document.getElementById('in_phone'))" required>
            </div>
            <input type="hidden" name="patient_phone" id="in_phone" required>
            <div class="invalid-feedback d-none" id="err_phone"></div>
            <div class="mt-1">
                <small class="text-muted smaller">
                    <i class="bi bi-info-circle me-1"></i> For dependents, the guardian's contact number is used for notifications.
                </small>
            </div>
        </div>

        {{-- Doctor's Referral File Upload --}}
        <div class="col-12 mt-2">
            <label class="small text-secondary fw-bold mb-1 uppercase">Doctor's Referral / Note (Optional)</label>
            <div id="referral_input_wrapper">
                <input type="file" name="referral_note" id="in_referral" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReferralUpload(this)">
            </div>
            <div class="mt-1">
                <small class="text-muted smaller">
                    <i class="bi bi-file-earmark-plus me-1"></i> Upload a PDF or image of your doctor's written referral or laboratory request note.
                </small>
            </div>

            {{-- Unified Referral Preview Card --}}
            <div id="referral_preview_container" class="d-none mt-3 p-3 rounded" style="background-color: rgba(25, 211, 140, 0.03); border: 1px solid rgba(25, 211, 140, 0.15);">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-accent fw-semibold" id="referral_file_label">
                        <i class="bi bi-file-earmark-check-fill me-1"></i>Selected File
                    </span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" id="btn_view_referral" onclick="previewReferralFile()">View</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold" onclick="removeUploadedReferral()">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Residential Address --}}
        <div class="col-12 mt-4">
            <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Residential Address</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
                    <select id="addr_province" name="patient_province" class="form-select py-3 shadow-none" onchange="fetchCities(this.value); clearInlineError(this)" required>
                        <option value="">Select Province</option>
                    </select>
                    <div class="invalid-feedback d-none" id="err_province"></div>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                    <select id="addr_city" name="patient_city" class="form-select py-3 shadow-none" onchange="fetchBarangays(this.value); clearInlineError(this)" disabled required>
                        <option value="">Select Province First</option>
                    </select>
                    <div class="invalid-feedback d-none" id="err_city"></div>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
                    <select id="addr_brgy" name="patient_barangay" class="form-select py-3 shadow-none" onchange="updateCompiledAddress(); clearInlineError(this)" disabled required>
                        <option value="">Select City First</option>
                    </select>
                    <div class="invalid-feedback d-none" id="err_barangay"></div>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                    <input type="text" id="addr_street" name="patient_street" class="form-control py-3 shadow-none uppercase" placeholder="House/Lot/Block/Street" oninput="updateCompiledAddress(); clearInlineError(this)" required>
                    <div class="invalid-feedback d-none" id="err_street"></div>
                </div>
            </div>

            {{-- Complete Address Compiled Live Preview --}}
            <div class="col-12 mt-3">
                <div id="compiled_address_container" class="alert alert-clinical p-2.5 d-none text-start" style="background-color: rgba(25, 211, 140, 0.03);">
                    <small class="text-accent fw-bold uppercase d-block mb-1" style="font-size: 0.65rem;">Compiled Residential Address Preview</small>
                    <div id="compiled_address_text" class="text-main small"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="d-flex gap-2 mt-5">
        <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(1)">
            <i class="bi bi-arrow-left me-2"></i> BACK
        </button>
        <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateStep2()">
            NEXT: SELECT TESTS <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</div>

<style>
    .is-invalid {
        border-color: #ff4d4d !important;
        background-image: none !important;
    }
</style>

@push('scripts')
<script>
/**
 * Global Preview Trigger for Step 2 Doctor's Referral File
 */
window.previewReferralFile = function() {
    const fileData = window.referralLocalData || localStorage.getItem('referral_base64');
    const fileName = localStorage.getItem('referral_name') || "Doctor's Referral Note";
    if (fileData) {
        if (typeof openFilePreview === 'function') {
            openFilePreview(fileData, fileName);
        }
    }
};

/**
 * Dynamic Birthdate Restrictions
 */
window.updateBirthdateRestrictions = function() {
    const bdayInput = document.getElementById('in_bday');
    if (!bdayInput) return;

    const targetTypeEl = document.querySelector('input[name="target_type"]:checked');
    const targetType = targetTypeEl ? targetTypeEl.value : 'self';

    const today = new Date();
    
    if (targetType === 'self') {
        const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
        const yyyy = eighteenYearsAgo.getFullYear();
        const mm = String(eighteenYearsAgo.getMonth() + 1).padStart(2, '0');
        const dd = String(eighteenYearsAgo.getDate()).padStart(2, '0');
        const maxDate = `${yyyy}-${mm}-${dd}`;
        
        bdayInput.setAttribute('max', maxDate);
        bdayInput.removeAttribute('min');
    } else if (targetType === 'dependent') {
        const yyyyToday = today.getFullYear();
        const mmToday = String(today.getMonth() + 1).padStart(2, '0');
        const ddToday = String(today.getDate()).padStart(2, '0');
        const maxDate = `${yyyyToday}-${mmToday}-${ddToday}`;

        const eighteenYearsAgoPlusDay = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate() + 1);
        const yyyyMin = eighteenYearsAgoPlusDay.getFullYear();
        const mmMin = String(eighteenYearsAgoPlusDay.getMonth() + 1).padStart(2, '0');
        const ddMin = String(eighteenYearsAgoPlusDay.getDate()).padStart(2, '0');
        const minDate = `${yyyyMin}-${mmMin}-${ddMin}`;

        bdayInput.setAttribute('max', maxDate);
        bdayInput.setAttribute('min', minDate);
    }
};

/**
 * Real-time dynamic name validation handler supporting Spanish characters
 */
window.validateNameInput = function(inputElement, fieldName, errorDivId, required = true) {
    if (!inputElement) return false;
    const val = inputElement.value.trim();
    
    inputElement.classList.remove('is-invalid');
    const errorDiv = document.getElementById(errorDivId);
    if (errorDiv) {
        errorDiv.classList.add('d-none');
        errorDiv.innerText = '';
    }

    if (!val) {
        if (required) {
            inputElement.classList.add('is-invalid');
            if (errorDiv) {
                errorDiv.innerText = `${fieldName} is required.`;
                errorDiv.classList.remove('d-none');
            }
        }
        return false;
    }

    if (val === 'N/A') return true;

    const nameRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc \s.\'-]+$/;
    const startRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc ]/;
    const consecutiveRegex = /[.\'-]{2,}/;
    const letterRegex = /[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc ]/;

    let message = '';
    if (!nameRegex.test(val)) {
        message = `${fieldName} may only contain letters, spaces, periods, hyphens, and apostrophes.`;
    } else if (!startRegex.test(val)) {
        message = `${fieldName} must start with a letter.`;
    } else if (!letterRegex.test(val)) {
        message = `${fieldName} must contain at least one letter.`;
    } else if (consecutiveRegex.test(val)) {
        message = `${fieldName} cannot contain consecutive punctuation marks.`;
    } else if (val.length > 60) {
        message = `${fieldName} cannot exceed 60 characters.`;
    }

    if (message) {
        inputElement.classList.add('is-invalid');
        if (errorDiv) {
            errorDiv.innerText = message;
            errorDiv.classList.remove('d-none');
        }
        return false;
    }
    return true;
};

/**
 * Real-time birthdate validation checker
 */
window.validateBirthdateInput = function() {
    const bdayInput = document.getElementById('in_bday');
    if (!bdayInput) return false;
    const bday = bdayInput.value;
    const errDiv = document.getElementById('err_birthdate');

    bdayInput.classList.remove('is-invalid');
    if (errDiv) {
        errDiv.classList.add('d-none');
        errDiv.innerText = '';
    }

    if (!bday) {
        bdayInput.classList.add('is-invalid');
        if (errDiv) {
            errDiv.innerText = "Birthdate is required.";
            errDiv.classList.remove('d-none');
        }
        return false;
    }

    const birthDateObj = new Date(bday);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (birthDateObj > today) {
        bdayInput.classList.add('is-invalid');
        if (errDiv) {
            errDiv.innerText = "Birthdate cannot be in the future.";
            errDiv.classList.remove('d-none');
        }
        return false;
    }

    let age = today.getFullYear() - birthDateObj.getFullYear();
    const m = today.getMonth() - birthDateObj.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDateObj.getDate())) {
        age--;
    }

    const targetTypeEl = document.querySelector('input[name="target_type"]:checked');
    const targetType = targetTypeEl ? targetTypeEl.value : 'self';

    if (targetType === 'self') {
        if (age < 18) {
            bdayInput.classList.add('is-invalid');
            if (errDiv) {
                errDiv.innerText = "Administrative Policy: You must be at least 18 years old to book a personal appointment.";
                errDiv.classList.remove('d-none');
            }
            return false;
        }
    } else if (targetType === 'dependent') {
        if (age >= 18) {
            bdayInput.classList.add('is-invalid');
            if (errDiv) {
                errDiv.innerText = "Administrative Policy: Dependents must be minors (under 18 years of age).";
                errDiv.classList.remove('d-none');
            }
            return false;
        }
    }
    return true;
};

/**
 * Middle name override toggle
 */
window.toggleProfileMN = function(checkbox) {
    const input = document.getElementById('in_middle_name');
    if (input) {
        if (checkbox.checked) {
            input.value = "N/A";
            input.readOnly = true;
            input.classList.add('opacity-50');
            input.classList.remove('is-invalid');
            const err = document.getElementById('err_middle_name');
            if (err) err.classList.add('d-none');
        } else {
            input.value = "";
            input.readOnly = false;
            input.classList.remove('opacity-50');
        }
    }
    if (typeof saveAppointmentDraft === 'function') saveAppointmentDraft();
    if (typeof updateSummary === 'function') updateSummary();
};

/**
 * Clear Inline Errors Helper
 */
window.clearInlineError = function(inputElement) {
    if (!inputElement) return;
    inputElement.classList.remove('is-invalid');
    let parent = inputElement.parentElement;
    let errorDiv = parent ? (parent.classList.contains('input-group') 
        ? parent.parentElement.querySelector('.invalid-feedback') 
        : parent.querySelector('.invalid-feedback')) : null;
    if (!errorDiv) {
        errorDiv = document.getElementById('err_' + inputElement.id) || document.getElementById('err_' + inputElement.name);
    }
    if (errorDiv) {
        errorDiv.classList.add('d-none');
        errorDiv.classList.remove('d-block');
        errorDiv.innerText = '';
    }
};

/**
 * Robust Step 2 Form Validator
 */
window.validateStep2 = function() {
    let isValid = true;

    // Clean previous states
    document.querySelectorAll('#page-2 .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#page-2 .invalid-feedback').forEach(el => {
        el.classList.add('d-none');
        el.classList.remove('d-block');
        el.innerText = '';
    });

    function setFieldInvalid(elementId, errorDivId, message) {
        const input = document.getElementById(elementId);
        const errorDiv = document.getElementById(errorDivId);
        if (input) input.classList.add('is-invalid');
        if (errorDiv) {
            errorDiv.innerText = message;
            errorDiv.classList.remove('d-none');
            errorDiv.classList.add('d-block');
        }
        isValid = false;
    }

    if (typeof syncWizardPhone === 'function') {
        syncWizardPhone();
    }

    const firstNameEl = document.getElementById('in_first_name');
    const lastNameEl = document.getElementById('in_last_name');
    const middleNameEl = document.getElementById('in_middle_name');
    const suffixEl = document.getElementById('in_suffix');

    const firstName = firstNameEl ? firstNameEl.value.trim() : '';
    const lastName = lastNameEl ? lastNameEl.value.trim() : '';
    const middleName = middleNameEl ? middleNameEl.value.trim() : '';
    const suffix = suffixEl ? suffixEl.value.trim() : '';

    function checkNameString(val, fieldName, elementId, errorDivId, required = true) {
        if (!val) {
            if (required) setFieldInvalid(elementId, errorDivId, `${fieldName} is required.`);
            return;
        }
        if (val === 'N/A') return;

        const nameRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc \s.\'-]+$/;
        const startRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc ]/;
        const consecutiveRegex = /[.\'-]{2,}/;
        const letterRegex = /[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc ]/;

        if (!nameRegex.test(val)) {
            setFieldInvalid(elementId, errorDivId, `${fieldName} may only contain letters, spaces, periods, hyphens, and apostrophes.`);
        } else if (!startRegex.test(val)) {
            setFieldInvalid(elementId, errorDivId, `${fieldName} must start with a letter.`);
        } else if (!letterRegex.test(val)) {
            setFieldInvalid(elementId, errorDivId, `${fieldName} must contain at least one letter.`);
        } else if (consecutiveRegex.test(val)) {
            setFieldInvalid(elementId, errorDivId, `${fieldName} cannot contain consecutive punctuation marks.`);
        } else if (val.length > 60) {
            setFieldInvalid(elementId, errorDivId, `${fieldName} cannot exceed 60 characters.`);
        }
    }

    checkNameString(firstName, "First Name", "in_first_name", "err_first_name", true);
    checkNameString(lastName, "Last Name", "in_last_name", "err_last_name", true);
    checkNameString(middleName, "Middle Name", "in_middle_name", "err_middle_name", false);

    if (suffix) {
        const suffixRegex = /^[a-zA-Z0-9\s.]+$/;
        if (!suffixRegex.test(suffix)) {
            setFieldInvalid("in_suffix", "err_suffix", "Suffix may only contain letters, numbers, spaces, and periods.");
        } else if (suffix.length > 10) {
            setFieldInvalid("in_suffix", "err_suffix", "Suffix cannot exceed 10 characters.");
        }
    }

    // Sex Validation
    const sexEl = document.getElementById('in_sex');
    const sex = sexEl ? sexEl.value : '';
    if (!sex) {
        setFieldInvalid("in_sex", "err_sex", "Please select a gender.");
    }

    // Birthdate Validation
    if (typeof validateBirthdateInput === 'function') {
        if (!validateBirthdateInput()) {
            isValid = false;
        }
    }

    // Contact Phone Validation (Strictly 11 digits)
    const phoneInput = document.getElementById('in_phone');
    const displayPhoneInput = document.getElementById('phone_display');
    const phoneVal = phoneInput ? phoneInput.value.trim() : '';
    const displayVal = displayPhoneInput ? displayPhoneInput.value.trim() : '';
    const phoneRegex = /^09\d{9}$/;

    if (!displayVal) {
        setFieldInvalid("phone_display", "err_phone", "Contact number is required.");
    } else if (!phoneRegex.test(phoneVal)) {
        setFieldInvalid("phone_display", "err_phone", "Phone number must start with 09 and contain exactly 11 digits.");
    }

    // Address verification checks
    const provEl = document.getElementById('addr_province');
    const cityEl = document.getElementById('addr_city');
    const brgyEl = document.getElementById('addr_brgy');
    const streetEl = document.getElementById('addr_street');

    const prov = provEl ? provEl.value : '';
    const city = cityEl ? cityEl.value : '';
    const brgy = brgyEl ? brgyEl.value : '';
    const street = streetEl ? streetEl.value.trim() : '';

    if (!prov) setFieldInvalid("addr_province", "err_province", "Province selection is required.");
    if (!city) setFieldInvalid("addr_city", "err_city", "City selection is required.");
    if (!brgy) setFieldInvalid("addr_brgy", "err_barangay", "Barangay selection is required.");
    if (!street) setFieldInvalid("addr_street", "err_street", "Street address is required.");

    if (isValid) {
        if (typeof goToPage === 'function') {
            goToPage(3);
        }
    } else {
        if (typeof showWizardAlert === 'function') {
            showWizardAlert("Please review the fields in Step 2. Correct any highlighted validation errors before proceeding.");
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.updateBirthdateRestrictions();

    document.querySelectorAll('input[name="target_type"]').forEach(radio => {
        radio.addEventListener('change', window.updateBirthdateRestrictions);
    });

    const referralInput = document.getElementById('in_referral');
    const previewContainer = document.getElementById('referral_preview_container');
    const inputWrapper = document.getElementById('referral_input_wrapper');
    const fileLabel = document.getElementById('referral_file_label');

    // Restore file state on load from localStorage
    const savedReferralBase64 = localStorage.getItem('referral_base64');
    const savedReferralName = localStorage.getItem('referral_name');

    if (savedReferralBase64 && savedReferralName) {
        window.referralLocalData = savedReferralBase64;
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (fileLabel) {
            fileLabel.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${savedReferralName}`;
        }
    }

    if (referralInput) {
        referralInput.addEventListener('change', function() {
            if (typeof handleReferralUpload === 'function') {
                handleReferralUpload(this);
            }
        });
    }
});

window.removeUploadedReferral = function() {
    const input = document.getElementById('in_referral');
    const inputWrapper = document.getElementById('referral_input_wrapper');
    const previewContainer = document.getElementById('referral_preview_container');
    
    if (input) input.value = '';
    if (inputWrapper) inputWrapper.classList.remove('d-none');
    if (previewContainer) previewContainer.classList.add('d-none');

    window.referralLocalData = null;
    localStorage.removeItem('referral_base64');
    localStorage.removeItem('referral_name');
    if (typeof saveAppointmentDraft === 'function') saveAppointmentDraft();
};
</script>
@endpush