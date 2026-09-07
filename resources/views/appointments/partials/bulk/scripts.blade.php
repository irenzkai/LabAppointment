@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
let rowCount = 0;
let activeRowIdx = null;
let isRestoringDraft = false;

// Injected configurations directly as a safe JS object literal to bypass DOM escaping errors
let cachedConfigs = @json($configs);
let cachedOccupancy = {};

// Formed global JS services map to lookup price and gender restrictions dynamically on changes
const servicesMap = @json($services->keyBy('id'));
const masterOrg = document.getElementById('master_org');
const masterDate = document.getElementById('master_date');
const apiBase = "https://psgc.gitlab.io/api";
const DRAFT_STORAGE_KEY = 'medscreen_bulk_appointment_draft';

// Track last selected payment settings for lock protections
let bulkLastMethod = 'Cash';
let bulkLastProviderId = null;
window.bulkReceiptLocalData = null;

// =========================================================================
// 1. NAVIGATION CONTROLLER (3 STEPS) & STEP RESTORATION
// =========================================================================
function goToPage(page) {
    document.querySelectorAll('.wiz-section').forEach(s => s.classList.add('d-none'));
    const target = document.getElementById('page-' + page);
    if (target) {
        target.classList.remove('d-none');
    }
    window.scrollTo(0, 0);
    toggleBulkSubmitButton();
    saveBulkDraft();
}

// Performs final secure validations on proceed click
async function proceedFromStep1() {
    const org = masterOrg.value.trim();
    const date = masterDate.value;
    if (!org || !date) {
        return showAlert("Organization name and Start Date are required.");
    }
    const isValid = await validateMasterDate();
    if (!isValid) {
        const errorMsg = document.getElementById('date_validation_msg').innerText;
        return showAlert(errorMsg);
    }
    performGlobalSync();
    goToPage(3);
}

// Navigates from Step 3 to Step 4 (Payment Checkout)
function validateStep2() {
    const rows = document.querySelectorAll('#rowContainer tr');
    
    // 1. Enforce that at least 2 patients are registered to proceed with bulk processes
    if (rows.length < 2) {
        return showAlert("Bulk booking requires at least 2 patient records. For single appointments, please use the standard booking wizard.");
    }

    // 2. Comprehensive Patient Data Validation per Row
    let invalidRows = [];
    let missingTests = [];
    let missingSlots = [];

    rows.forEach((tr, index) => {
        const idx = tr.id.split('_')[1];
        const rowNum = index + 1;
        const p = getRowPatientData(idx);
        const rowErrors = getPatientRowValidationErrors(p);
        let hasError = false;

        // Check demographic data
        if (rowErrors.length > 0) {
            invalidRows.push(`Row ${rowNum} (${p.name || 'Unnamed'}): ${rowErrors.join(', ')}`);
            hasError = true;
        }

        // Check test selection
        const testInputs = tr.querySelectorAll('input[type="hidden"][name*="[service_ids]"]');
        if (testInputs.length === 0) {
            missingTests.push(`Row ${rowNum} (${p.name || 'Unnamed'})`);
            hasError = true;
        }

        // Check Schedule Date & Time Slot selection
        const rowDate = tr.querySelector('.row-date-input')?.value;
        const tSelect = tr.querySelector('.t-select')?.value;
        if (!rowDate || !tSelect) {
            missingSlots.push(`Row ${rowNum} (${p.name || 'Unnamed'})`);
            hasError = true;
        }

        // Highlight offending rows
        if (hasError) {
            tr.style.borderColor = "#ff4d4d";
        } else {
            tr.style.borderColor = "var(--border-color)";
        }
    });

    // 3. Validation Alerts
    if (invalidRows.length > 0) {
        return showAlert("One or more patient records have incomplete or invalid details. Please click 'Fix Details' on the highlighted rows before proceeding:<br><br>" + invalidRows.join('<br>'));
    }

    if (missingTests.length > 0) {
        return showAlert("Every patient in the list must have at least one test selected. Missing tests on:<br><br>" + missingTests.join('<br>'));
    }

    if (missingSlots.length > 0) {
        return showAlert("Please assign a schedule date and time slot for all patients before proceeding to checkout (or click <strong>SMART AUTO-TIME</strong> to automatically distribute slots). Missing time on:<br><br>" + missingSlots.join('<br>'));
    }

    // Proceed to Step 4 (Payment & Finalize)
    goToPage(4);
}

// Calculates occupied slots live across all rows currently on the screen
window.getOnScreenOccupiedCount = function(date, time, excludeRowIdx = null) {
    let count = 0;
    const rows = document.querySelectorAll('#rowContainer tr');
    rows.forEach(tr => {
        const idx = tr.id.split('_')[1];
        if (excludeRowIdx !== null && idx == excludeRowIdx) return;
        const rDateInput = tr.querySelector('.row-date-input');
        const rTimeSelect = tr.querySelector('.t-select');
        if (rDateInput && rTimeSelect) {
            if (rDateInput.value === date && rTimeSelect.value === time) {
                count++;
            }
        }
    });
    return count;
};

// =========================================================================
// 2. VALIDATION HELPERS & INLINE ERROR HANDLERS
// =========================================================================
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
    if (!suffixRegex.test(v)) return "Suffix may only contain letters, spaces, and periods (e.g. JR, SR, II, III).";
    return null;
}

function calculateAge(bday) {
    if (!bday) return 'N/A';
    const dob = new Date(bday);
    const today = new Date();
    if (isNaN(dob.getTime())) return 'N/A';
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    return age >= 0 ? age : 'N/A';
}

function getPatientRowValidationErrors(p) {
    let errors = [];
    if (!p.first_name || validateNameString(p.first_name, 'First Name')) errors.push("Invalid First Name");
    if (!p.last_name || validateNameString(p.last_name, 'Last Name')) errors.push("Invalid Last Name");
    if (p.middle_name && p.middle_name !== 'N/A' && validateNameString(p.middle_name, 'Middle Name')) errors.push("Invalid Middle Name");
    if (p.suffix && validateSuffixString(p.suffix)) errors.push("Invalid Suffix");
    if (!p.sex || !['Male', 'Female'].includes(p.sex)) errors.push("Select Sex");
    
    if (!p.birthdate) {
        errors.push("Missing Birthdate");
    } else {
        const dob = new Date(p.birthdate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (isNaN(dob.getTime()) || dob > today) {
            errors.push("Invalid Birthdate");
        } else {
            const age = calculateAge(p.birthdate);
            if (age === 'N/A' || age < 18) {
                errors.push("Must be 18+ Yrs Old");
            }
        }
    }

    const phoneClean = (p.phone || '').trim();
    if (!phoneClean || !/^09\d{9}$/.test(phoneClean)) {
        errors.push("Invalid Phone (09XXXXXXXXX)");
    }

    const emailClean = (p.email || '').trim();
    if (!emailClean || !/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(emailClean)) {
        errors.push("Invalid Email");
    }

    if (!p.province) errors.push("Missing Province");
    if (!p.city) errors.push("Missing City");
    if (!p.barangay) errors.push("Missing Barangay");
    if (!p.street || !p.street.trim()) errors.push("Missing Street");

    return errors;
}

function getRowPatientData(idx) {
    const tr = document.getElementById(`r_${idx}`);
    if (!tr) return {};
    return {
        first_name: tr.querySelector(`input[name="patients[${idx}][first_name]"]`)?.value || '',
        middle_name: tr.querySelector(`input[name="patients[${idx}][middle_name]"]`)?.value || 'N/A',
        last_name: tr.querySelector(`input[name="patients[${idx}][last_name]"]`)?.value || '',
        suffix: tr.querySelector(`input[name="patients[${idx}][suffix]"]`)?.value || '',
        name: tr.querySelector(`input[name="patients[${idx}][name]"]`)?.value || '',
        sex: tr.querySelector(`input[name="patients[${idx}][sex]"]`)?.value || 'Male',
        birthdate: tr.querySelector(`input[name="patients[${idx}][birthdate]"]`)?.value || '',
        phone: tr.querySelector(`input[name="patients[${idx}][phone]"]`)?.value || '',
        email: tr.querySelector(`input[name="patients[${idx}][email]"]`)?.value || '',
        province: tr.querySelector(`input[name="patients[${idx}][province]"]`)?.value || '',
        city: tr.querySelector(`input[name="patients[${idx}][city]"]`)?.value || '',
        barangay: tr.querySelector(`input[name="patients[${idx}][barangay]"]`)?.value || '',
        street: tr.querySelector(`input[name="patients[${idx}][street]"]`)?.value || '',
        appointment_date: tr.querySelector('.row-date-input')?.value || '',
        time_slot: tr.querySelector('.t-select')?.value || ''
    };
}

function setModalError(inputId, errDivId, message) {
    const input = document.getElementById(inputId);
    const errDiv = document.getElementById(errDivId);
    if (input) input.classList.add('is-invalid');
    if (errDiv) {
        errDiv.innerText = message;
        errDiv.classList.remove('d-none');
        errDiv.classList.add('d-block');
    }
}

function clearModalErrors() {
    document.querySelectorAll('#modalPatientForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#modalPatientForm .invalid-feedback').forEach(el => {
        el.innerText = '';
        el.classList.add('d-none');
        el.classList.remove('d-block');
    });
}

function toggleModalMN(chk) {
    const input = document.getElementById('modal_middle_name');
    if (input) {
        input.value = chk.checked ? 'N/A' : '';
        input.readOnly = chk.checked;
        input.classList.toggle('opacity-50', chk.checked);
        if (chk.checked) {
            input.classList.remove('is-invalid');
            const errDiv = document.getElementById('err_modal_middle_name');
            if (errDiv) {
                errDiv.classList.add('d-none');
                errDiv.classList.remove('d-block');
                errDiv.innerText = '';
            }
        }
    }
}

// =========================================================================
// 3. PSGC ADDRESS ENGINE FOR MODAL
// =========================================================================
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

async function fetchModalProvinces(targetProv = null, targetCity = null, targetBrgy = null) {
    const provSel = document.getElementById('modal_province');
    if (!provSel) return;
    try {
        const res = await fetch(`${apiBase}/provinces.json`);
        const data = await res.json();
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.dataset.code = p.code;
            opt.textContent = p.name;
            provSel.appendChild(opt);
        });
        if (targetProv) {
            const matched = findOptionFlexibly(provSel, targetProv);
            if (matched) {
                provSel.value = matched.value;
                await fetchModalCities(matched.value, targetCity, targetBrgy);
            }
        }
    } catch (e) {
        console.error("Provinces fetch error:", e);
    }
}

async function fetchModalCities(provName, targetCity = null, targetBrgy = null) {
    const provSel = document.getElementById('modal_province');
    const citySel = document.getElementById('modal_city');
    const brgySel = document.getElementById('modal_barangay');
    if (!citySel || !brgySel) return;
    
    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';
    brgySel.innerHTML = '<option value="">Select City First</option>';

    if (!provName) {
        citySel.innerHTML = '<option value="">Select Province First</option>';
        return;
    }
    const matchedProv = findOptionFlexibly(provSel, provName);
    const provCode = matchedProv ? matchedProv.dataset.code : null;
    if (!provCode) {
        citySel.innerHTML = '<option value="">Select City</option>';
        return;
    }
    try {
        const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities.json`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.dataset.code = c.code;
            opt.textContent = c.name;
            citySel.appendChild(opt);
        });
        citySel.disabled = false;
        if (targetCity) {
            const matchedCity = findOptionFlexibly(citySel, targetCity);
            if (matchedCity) {
                citySel.value = matchedCity.value;
                await fetchModalBarangays(matchedCity.value, targetBrgy);
            }
        }
    } catch (e) {
        console.error("Cities fetch error:", e);
    }
}

async function fetchModalBarangays(cityName, targetBrgy = null) {
    const citySel = document.getElementById('modal_city');
    const brgySel = document.getElementById('modal_barangay');
    if (!brgySel) return;
    
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    if (!cityName) {
        brgySel.innerHTML = '<option value="">Select City First</option>';
        return;
    }
    const matchedCity = findOptionFlexibly(citySel, cityName);
    const cityCode = matchedCity ? matchedCity.dataset.code : null;
    if (!cityCode) {
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        return;
    }
    try {
        const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays.json`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.name;
            opt.dataset.code = b.code;
            opt.textContent = b.name;
            brgySel.appendChild(opt);
        });
        brgySel.disabled = false;
        if (targetBrgy) {
            const matchedBrgy = findOptionFlexibly(brgySel, targetBrgy);
            if (matchedBrgy) {
                brgySel.value = matchedBrgy.value;
            }
        }
    } catch (e) {
        console.error("Barangays fetch error:", e);
    }
}

// =========================================================================
// 4. PATIENT MODAL WORKFLOW (ADD & EDIT WITH 18+ AGE ENFORCEMENT)
// =========================================================================
function openCreatePatientModal() {
    clearModalErrors();
    document.getElementById('modal_row_idx').value = "";
    document.getElementById('modal_patient_mode_title').innerText = "Add Patient Profile";
    document.getElementById('modal_first_name').value = "";
    document.getElementById('modal_middle_name').value = "";
    document.getElementById('modal_middle_name').readOnly = false;
    document.getElementById('modal_middle_name').classList.remove('opacity-50');
    document.getElementById('modal_no_mn').checked = false;
    document.getElementById('modal_last_name').value = "";
    document.getElementById('modal_suffix').value = "";
    document.getElementById('modal_sex').value = "Male";
    document.getElementById('modal_birthdate').value = "";
    document.getElementById('modal_phone_display').value = "";
    document.getElementById('modal_phone').value = "";
    document.getElementById('modal_email').value = "";
    document.getElementById('modal_street').value = "";

    fetchModalProvinces();
    const modalEl = document.getElementById('bulkPatientModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();
}

function openEditPatientModal(idx) {
    clearModalErrors();
    document.getElementById('modal_row_idx').value = idx;
    document.getElementById('modal_patient_mode_title').innerText = `Edit Patient Profile (Row #${parseInt(idx) + 1})`;
    
    const tr = document.getElementById(`r_${idx}`);
    if (!tr) return;
    const p = getRowPatientData(idx);

    document.getElementById('modal_first_name').value = p.first_name;
    document.getElementById('modal_no_mn').checked = (p.middle_name === 'N/A');
    document.getElementById('modal_middle_name').value = (p.middle_name === 'N/A' ? '' : p.middle_name);
    document.getElementById('modal_middle_name').readOnly = (p.middle_name === 'N/A');
    document.getElementById('modal_middle_name').classList.toggle('opacity-50', p.middle_name === 'N/A');
    document.getElementById('modal_last_name').value = p.last_name;
    document.getElementById('modal_suffix').value = p.suffix;
    document.getElementById('modal_sex').value = p.sex;
    document.getElementById('modal_birthdate').value = p.birthdate;

    let displayPhone = p.phone;
    if (displayPhone.startsWith('09')) displayPhone = displayPhone.substring(2);
    else if (displayPhone.startsWith('+639')) displayPhone = displayPhone.substring(4);
    else if (displayPhone.startsWith('639')) displayPhone = displayPhone.substring(3);

    document.getElementById('modal_phone_display').value = displayPhone;
    document.getElementById('modal_phone').value = p.phone;
    document.getElementById('modal_email').value = p.email;
    document.getElementById('modal_street').value = p.street;

    fetchModalProvinces(p.province, p.city, p.barangay);

    const modalEl = document.getElementById('bulkPatientModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();

    setTimeout(() => {
        validateModalFieldsLive();
    }, 200);
}

function validateModalFieldsLive() {
    const fName = document.getElementById('modal_first_name').value.trim();
    if (fName) {
        const err = validateNameString(fName, 'First Name');
        if (err) setModalError('modal_first_name', 'err_modal_first_name', err);
    }
    const mName = document.getElementById('modal_middle_name').value.trim();
    if (mName && mName !== 'N/A') {
        const err = validateNameString(mName, 'Middle Name');
        if (err) setModalError('modal_middle_name', 'err_modal_middle_name', err);
    }
    const lName = document.getElementById('modal_last_name').value.trim();
    if (lName) {
        const err = validateNameString(lName, 'Last Name');
        if (err) setModalError('modal_last_name', 'err_modal_last_name', err);
    }
    const suffix = document.getElementById('modal_suffix').value.trim();
    if (suffix) {
        const err = validateSuffixString(suffix);
        if (err) setModalError('modal_suffix', 'err_modal_suffix', err);
    }
    const bday = document.getElementById('modal_birthdate').value;
    if (bday) {
        const age = calculateAge(bday);
        if (age === 'N/A' || age < 18) {
            setModalError('modal_birthdate', 'err_modal_birthdate', 'Administrative Policy: Patients must be at least 18 years of age.');
        }
    }
    const phoneDisp = document.getElementById('modal_phone_display').value.trim();
    if (phoneDisp && !/^09\d{9}$/.test('09' + phoneDisp)) {
        setModalError('modal_phone_display', 'err_modal_phone', 'Contact number must contain exactly 11 digits (09 + 9 digits).');
    }
    const email = document.getElementById('modal_email').value.trim();
    if (email && !/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
        setModalError('modal_email', 'err_modal_email', 'Please enter a valid email address with a domain.');
    }
}

function savePatientModal() {
    clearModalErrors();
    let isValid = true;
    let firstInvalid = null;

    function markInvalid(inputId, errDivId, msg) {
        setModalError(inputId, errDivId, msg);
        isValid = false;
        if (!firstInvalid) firstInvalid = document.getElementById(inputId);
    }

    const fName = document.getElementById('modal_first_name').value.trim();
    const fErr = validateNameString(fName, 'First Name');
    if (!fName) markInvalid('modal_first_name', 'err_modal_first_name', 'First Name is required.');
    else if (fErr) markInvalid('modal_first_name', 'err_modal_first_name', fErr);

    const mName = document.getElementById('modal_middle_name').value.trim();
    if (mName && mName !== 'N/A') {
        const mErr = validateNameString(mName, 'Middle Name');
        if (mErr) markInvalid('modal_middle_name', 'err_modal_middle_name', mErr);
    }

    const lName = document.getElementById('modal_last_name').value.trim();
    const lErr = validateNameString(lName, 'Last Name');
    if (!lName) markInvalid('modal_last_name', 'err_modal_last_name', 'Last Name is required.');
    else if (lErr) markInvalid('modal_last_name', 'err_modal_last_name', lErr);

    const suffix = document.getElementById('modal_suffix').value.trim();
    if (suffix) {
        const sfxErr = validateSuffixString(suffix);
        if (sfxErr) markInvalid('modal_suffix', 'err_modal_suffix', sfxErr);
    }

    const sex = document.getElementById('modal_sex').value;
    if (!sex) markInvalid('modal_sex', 'err_modal_sex', 'Sex is required.');

    const bday = document.getElementById('modal_birthdate').value;
    if (!bday) {
        markInvalid('modal_birthdate', 'err_modal_birthdate', 'Birthdate is required.');
    } else {
        const dObj = new Date(bday);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (isNaN(dObj.getTime()) || dObj > today) {
            markInvalid('modal_birthdate', 'err_modal_birthdate', 'Birthdate cannot be in the future.');
        } else {
            const calculatedAge = calculateAge(bday);
            if (calculatedAge === 'N/A' || calculatedAge < 18) {
                markInvalid('modal_birthdate', 'err_modal_birthdate', 'Administrative Policy: Patients must be at least 18 years of age.');
            }
        }
    }

    const phoneDisp = document.getElementById('modal_phone_display').value.trim();
    const phoneFull = '09' + phoneDisp;
    const phoneRegex = /^09\d{9}$/;
    if (!phoneDisp) {
        markInvalid('modal_phone_display', 'err_modal_phone', 'Contact Number is required.');
    } else if (!phoneRegex.test(phoneFull)) {
        markInvalid('modal_phone_display', 'err_modal_phone', 'Contact number must contain exactly 11 digits (09 + 9 digits).');
    }

    const email = document.getElementById('modal_email').value.trim();
    const emailRegex = /^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!email) {
        markInvalid('modal_email', 'err_modal_email', 'Email Address is required.');
    } else if (!emailRegex.test(email)) {
        markInvalid('modal_email', 'err_modal_email', 'Please enter a valid email address with a domain.');
    }

    const prov = document.getElementById('modal_province').value;
    const city = document.getElementById('modal_city').value;
    const brgy = document.getElementById('modal_barangay').value;
    const street = document.getElementById('modal_street').value.trim();

    if (!prov) markInvalid('modal_province', 'err_modal_province', 'Province selection is required.');
    if (!city) markInvalid('modal_city', 'err_modal_city', 'City selection is required.');
    if (!brgy) markInvalid('modal_barangay', 'err_modal_barangay', 'Barangay selection is required.');
    if (!street) markInvalid('modal_street', 'err_modal_street', 'Street address is required.');

    if (!isValid) {
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
        return;
    }

    const middleFinal = mName ? (mName.toUpperCase() === 'N/A' ? 'N/A' : mName.toUpperCase()) : 'N/A';
    const compiledFullName = `${fName.toUpperCase()}${middleFinal !== 'N/A' ? ' ' + middleFinal : ''} ${lName.toUpperCase()}${suffix ? ' ' + suffix.toUpperCase() : ''}`.trim();

    const patientData = {
        first_name: fName.toUpperCase(),
        middle_name: middleFinal,
        last_name: lName.toUpperCase(),
        suffix: suffix ? suffix.toUpperCase() : '',
        name: compiledFullName,
        sex: sex,
        birthdate: bday,
        phone: phoneFull,
        email: email,
        province: prov,
        city: city,
        barangay: brgy,
        street: street.toUpperCase(),
    };

    const editIdx = document.getElementById('modal_row_idx').value;
    if (editIdx !== "") {
        updateRowPatientData(editIdx, patientData);
    } else {
        addRow(patientData);
    }

    const modalEl = document.getElementById('bulkPatientModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (modalInstance) modalInstance.hide();

    updateBulkSummary();
    performGlobalSync();
    saveBulkDraft();
}

function updateRowPatientData(idx, p) {
    const tr = document.getElementById(`r_${idx}`);
    if (!tr) return;

    tr.querySelector(`input[name="patients[${idx}][first_name]"]`).value = p.first_name || '';
    tr.querySelector(`input[name="patients[${idx}][middle_name]"]`).value = p.middle_name || 'N/A';
    tr.querySelector(`input[name="patients[${idx}][last_name]"]`).value = p.last_name || '';
    tr.querySelector(`input[name="patients[${idx}][suffix]"]`).value = p.suffix || '';
    tr.querySelector(`input[name="patients[${idx}][name]"]`).value = p.name || '';
    tr.querySelector(`input[name="patients[${idx}][sex]"]`).value = p.sex || 'Male';
    tr.querySelector(`input[name="patients[${idx}][birthdate]"]`).value = p.birthdate || '';
    tr.querySelector(`input[name="patients[${idx}][phone]"]`).value = p.phone || '';
    tr.querySelector(`input[name="patients[${idx}][email]"]`).value = p.email || '';
    tr.querySelector(`input[name="patients[${idx}][province]"]`).value = p.province || '';
    tr.querySelector(`input[name="patients[${idx}][city]"]`).value = p.city || '';
    tr.querySelector(`input[name="patients[${idx}][barangay]"]`).value = p.barangay || '';
    tr.querySelector(`input[name="patients[${idx}][street]"]`).value = p.street || '';

    const errors = getPatientRowValidationErrors(p);
    const hasErrors = errors.length > 0;
    const age = calculateAge(p.birthdate);
    const addressStr = (p.street || p.barangay || p.city || p.province)
        ? `${p.street || ''}, BRGY. ${p.barangay || ''}, ${p.city || ''}, ${p.province || ''}`.toUpperCase()
        : 'N/A';

    const summaryContainer = tr.querySelector('.patient-summary-cell');
    if (summaryContainer) {
        summaryContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                <div class="text-truncate">
                    <span class="fw-bold ${hasErrors ? 'text-danger' : 'text-main'} fs-6 text-truncate d-block" style="max-width: 250px;">${p.name || 'Incomplete Record'}</span>
                    <div class="d-flex align-items-center gap-2 mt-0.5 flex-wrap">
                        <span class="badge ${p.sex === 'Male' ? 'bg-info' : 'bg-danger'} bg-opacity-10 ${p.sex === 'Male' ? 'text-info' : 'text-danger'} border ${p.sex === 'Male' ? 'border-info' : 'border-danger'} border-opacity-25 fw-bold uppercase" style="font-size: 0.65rem;">${p.sex ? p.sex.toUpperCase() : 'N/A'}</span>
                        <span class="text-secondary smaller fw-semibold">${age !== 'N/A' ? age + ' YRS OLD' : 'AGE UNKNOWN'}</span>
                        ${hasErrors ? `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-bold uppercase" style="font-size: 0.6rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>REVISION NEEDED</span>` : ''}
                    </div>
                </div>
                <button type="button" class="btn btn-sm ${hasErrors ? 'btn-danger-custom text-danger btn-outline-danger' : 'btn-outline-accent'} py-1 px-2.5 fw-bold d-inline-flex align-items-center flex-shrink-0" style="font-size: 0.7rem; border-radius: 6px;" onclick="openEditPatientModal(${idx})" title="Edit Details">
                    <i class="bi ${hasErrors ? 'bi-exclamation-octagon-fill' : 'bi-pencil-square'} me-1"></i>${hasErrors ? 'Fix Details' : 'Edit Info'}
                </button>
            </div>
            ${hasErrors ? `<div class="text-danger smaller fw-bold mt-1" style="font-size: 0.8rem;"><i class="bi bi-info-circle me-1" style="font-size: 0.8rem;"></i>${errors.join(' &bull; ')}</div>` : ''}
            <div class="text-muted smaller text-truncate mt-1" style="font-size: 0.72rem;">
                <i class="bi bi-telephone text-accent me-1"></i>${p.phone || 'No Phone'} &bull; <i class="bi bi-envelope text-accent me-1"></i>${p.email || 'No Email'}
            </div>
            <div class="text-secondary smaller text-truncate mt-0.5" style="font-size: 0.7rem;">
                <i class="bi bi-geo-alt text-accent me-1"></i>${addressStr}
            </div>
        `;
    }

    if (hasErrors) {
        tr.style.borderColor = "#ff4d4d";
    } else {
        tr.style.borderColor = "var(--border-color)";
    }

    handleRowSexChange(idx, p.sex);
}

// =========================================================================
// 5. TABLE ROW RENDERER & SPREADSHEET BUILDER
// =========================================================================
function addRow(patient = null) {
    const minDate = document.getElementById('master_date').value || "{{ date('Y-m-d') }}";
    let fName = patient?.first_name || '';
    let mName = patient?.middle_name || 'N/A';
    let lName = patient?.last_name || '';
    let suffix = patient?.suffix || '';
    let fullName = patient?.name || '';
    if (!fullName && (fName || lName)) {
        fullName = `${fName}${mName && mName !== 'N/A' ? ' ' + mName : ''} ${lName}${suffix ? ' ' + suffix : ''}`.trim();
    }
    const email = patient?.email || '';
    const phone = patient?.phone || '';
    const sex = patient?.sex ? (patient.sex.toLowerCase() === 'female' ? 'Female' : 'Male') : 'Male';
    const bday = patient?.birthdate || '';
    const province = patient?.province || '';
    const city = patient?.city || '';
    const barangay = patient?.barangay || '';
    const street = patient?.street || '';

    const pData = {
        first_name: fName,
        middle_name: mName,
        last_name: lName,
        suffix: suffix,
        name: fullName,
        sex: sex,
        birthdate: bday,
        phone: phone,
        email: email,
        province: province,
        city: city,
        barangay: barangay,
        street: street
    };

    const errors = getPatientRowValidationErrors(pData);
    const hasErrors = errors.length > 0;
    const age = calculateAge(bday);
    const addressStr = (street || barangay || city || province) 
        ? `${street}, BRGY. ${barangay}, ${city}, ${province}`.toUpperCase() 
        : 'N/A';
    const selectedServiceIds = patient?.service_ids || [];

    const html = `
        <tr id="r_${rowCount}" class="border-secondary border-opacity-10 align-middle text-main" style="${hasErrors ? 'border: 1.5px solid #ff4d4d !important;' : ''}">
            {{-- 1. Patient Information & Address (Compact Summary & Edit Trigger) --}}
            <td class="ps-4 py-3" style="width: 38%;">
                <input type="hidden" name="patients[${rowCount}][first_name]" value="${fName}">
                <input type="hidden" name="patients[${rowCount}][middle_name]" value="${mName}">
                <input type="hidden" name="patients[${rowCount}][last_name]" value="${lName}">
                <input type="hidden" name="patients[${rowCount}][suffix]" value="${suffix}">
                <input type="hidden" name="patients[${rowCount}][name]" id="p_name_${rowCount}" value="${fullName}">
                <input type="hidden" name="patients[${rowCount}][email]" value="${email}">
                <input type="hidden" name="patients[${rowCount}][phone]" value="${phone}">
                <input type="hidden" name="patients[${rowCount}][sex]" class="p-sex" value="${sex}">
                <input type="hidden" name="patients[${rowCount}][birthdate]" value="${bday}">
                <input type="hidden" name="patients[${rowCount}][province]" value="${province}">
                <input type="hidden" name="patients[${rowCount}][city]" value="${city}">
                <input type="hidden" name="patients[${rowCount}][barangay]" value="${barangay}">
                <input type="hidden" name="patients[${rowCount}][street]" value="${street}">
                
                <div class="patient-summary-cell p-2 rounded bg-card border border-secondary border-opacity-10 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                        <div class="text-truncate">
                            <span class="fw-bold ${hasErrors ? 'text-danger' : 'text-main'} fs-6 text-truncate d-block" style="max-width: 250px;">${fullName || 'Incomplete Record'}</span>
                            <div class="d-flex align-items-center gap-2 mt-0.5 flex-wrap">
                                <span class="badge ${sex === 'Male' ? 'bg-info' : 'bg-danger'} bg-opacity-10 ${sex === 'Male' ? 'text-info' : 'text-danger'} border ${sex === 'Male' ? 'border-info' : 'border-danger'} border-opacity-25 fw-bold uppercase" style="font-size: 0.65rem;">${sex.toUpperCase()}</span>
                                <span class="text-secondary smaller fw-semibold">${age !== 'N/A' ? age + ' YRS OLD' : 'AGE UNKNOWN'}</span>
                                ${hasErrors ? `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fw-bold uppercase" style="font-size: 0.6rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>REVISION NEEDED</span>` : ''}
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm ${hasErrors ? 'btn-danger-custom text-danger btn-outline-danger' : 'btn-outline-accent'} py-1 px-2.5 fw-bold d-inline-flex align-items-center flex-shrink-0" style="font-size: 0.7rem; border-radius: 6px;" onclick="openEditPatientModal(${rowCount})" title="Edit Details">
                            <i class="bi ${hasErrors ? 'bi-exclamation-octagon-fill' : 'bi-pencil-square'} me-1"></i>${hasErrors ? 'Fix Details' : 'Edit Info'}
                        </button>
                    </div>
                    ${hasErrors ? `<div class="text-danger smaller fw-bold mt-1" style="font-size: 0.8rem;"><i class="bi bi-info-circle me-1" style="font-size: 0.8rem;"></i>${errors.join(' &bull; ')}</div>` : ''}
                    <div class="text-muted smaller text-truncate mt-1" style="font-size: 0.72rem;">
                        <i class="bi bi-telephone text-accent me-1"></i>${phone || 'No Phone'} &bull; <i class="bi bi-envelope text-accent me-1"></i>${email || 'No Email'}
                    </div>
                    <div class="text-secondary smaller text-truncate mt-0.5" style="font-size: 0.7rem;">
                        <i class="bi bi-geo-alt text-accent me-1"></i>${addressStr}
                    </div>
                </div>
            </td>

            {{-- 2. Tests Selection --}}
            <td class="py-3 px-3" style="width: 22%;">
                <div id="display_tests_${rowCount}" class="text-main fw-bold mb-2 uppercase border border-secondary border-opacity-10 p-2 rounded small" style="min-height: 48px; font-size: 0.7rem; overflow-y: auto; max-height: 80px; background-color: rgba(0,0,0,0.02);">NO TESTS</div>
                <div id="hidden_inputs_${rowCount}">
                    ${selectedServiceIds.map(id => `<input type="hidden" name="patients[${rowCount}][service_ids][]" value="${id}">`).join('')}
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn-custom btn-outline-accent flex-grow-1 py-1.5 fw-bold small" style="font-size: 0.75rem;" onclick="openServiceModal(${rowCount})">SELECT</button>
                    <div class="dropdown">
                        <button class="btn-custom btn-outline-accent border-secondary text-secondary py-1.5 px-2.5" type="button" data-bs-toggle="dropdown" title="Copy Tests">
                            <i class="bi bi-copy"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark bg-black border-secondary shadow-lg">
                            <li><h6 class="dropdown-header text-accent small">COPY TO:</h6></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'all')">ALL PATIENTS</button></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'Male')">ALL MALES</button></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'Female')">ALL FEMALES</button></li>
                        </ul>
                    </div>
                </div>
            </td>

            {{-- 3. Schedule Slot --}}
            <td class="py-3 px-3" style="width: 22%;">
                <div class="mb-2">
                    <label class="text-secondary mb-1 uppercase fw-bold" style="font-size: 0.62rem; letter-spacing: 0.5px;">Schedule Date</label>
                    <input type="date" name="patients[${rowCount}][appointment_date]" class="form-control form-control-sm row-date-input shadow-none fw-semibold" value="${patient?.appointment_date || minDate}" min="${minDate}" onchange="updateRowSlots(this)">
                </div>
                <div>
                    <label class="text-secondary mb-1 uppercase fw-bold" style="font-size: 0.62rem; letter-spacing: 0.5px;">Time Slot</label>
                    <select name="patients[${rowCount}][time_slot]" class="form-select form-select-sm border-secondary text-accent fw-bold t-select shadow-none" required onchange="refreshAllRowSlots()">
                        <option value="">Choose Date First</option>
                    </select>
                </div>
            </td>

            {{-- 4. Actions --}}
            <td class="pe-4 py-3 text-center align-middle" style="width: 18%;">
                <div class="d-flex flex-column gap-2 align-items-center justify-content-center w-100">
                    <button type="button" class="btn btn-sm btn-outline-accent d-flex align-items-center justify-content-center w-100 py-1.5 px-3" style="border-radius: 8px; font-size: 0.7rem; font-weight: 700; gap: 6px; letter-spacing: 0.5px; white-space: nowrap;" onclick="duplicateRow(${rowCount})">
                        <i class="bi bi-files"></i> DUPLICATE
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center w-100 py-1.5 px-3" style="border-radius: 8px; font-size: 0.7rem; font-weight: 700; gap: 6px; letter-spacing: 0.5px; white-space: nowrap;" onclick="removeSpreadsheetRow(${rowCount})">
                        <i class="bi bi-trash3"></i> DELETE
                    </button>
                </div>
            </td>
        </tr>
    `;

    document.getElementById('rowContainer').insertAdjacentHTML('beforeend', html);
    const newTr = document.getElementById(`r_${rowCount}`);
    updateRowSlots(newTr.querySelector('.row-date-input'), patient?.time_slot || '');
    if (selectedServiceIds.length > 0) {
        const labels = selectedServiceIds.map(id => servicesMap[id]?.name).filter(Boolean);
        document.getElementById(`display_tests_${rowCount}`).innerText = labels.join(', ') || 'NO TESTS';
    }
    performGlobalSync();
    saveBulkDraft();
    rowCount++;
}

function removeSpreadsheetRow(idx) {
    document.getElementById(`r_${idx}`)?.remove();
    performGlobalSync();
    saveBulkDraft();
}

function confirmClearAllBulkRows() {
    document.getElementById('rowContainer').innerHTML = '';
    rowCount = 0;
    const modalEl = document.getElementById('clearAllBulkModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
    performGlobalSync();
    saveBulkDraft();
}

function duplicateRow(idx) {
    const row = document.getElementById(`r_${idx}`);
    if (!row) return;
    const hiddenInputs = document.querySelectorAll(`#hidden_inputs_${idx} input`);
    const serviceIds = Array.from(hiddenInputs).map(input => input.value);
    const clonedPatient = getRowPatientData(idx);
    clonedPatient.service_ids = serviceIds;
    addRow(clonedPatient);
}

// =========================================================================
// 6. LOCAL STORAGE STATE PERSISTENCE & ANTI-STALE REVALIDATION (DRAFT ENGINE)
// =========================================================================
function saveBulkDraft() {
    if (isRestoringDraft) return;
    const activeSection = document.querySelector('.wiz-section:not(.d-none)');
    const currentStep = activeSection ? parseInt(activeSection.id.replace('page-', '')) : 2;
    const patients = [];
    const rows = document.querySelectorAll('#rowContainer tr');
    rows.forEach(tr => {
        const idx = tr.id.split('_')[1];
        if (!idx) return;
        const p = getRowPatientData(idx);
        const hiddenInputs = tr.querySelectorAll(`#hidden_inputs_${idx} input`);
        p.service_ids = Array.from(hiddenInputs).map(i => i.value);
        patients.push(p);
    });

    const activeProvider = document.querySelector('.bulk-prov-radio:checked');
    const payMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'Cash';

    const draft = {
        step: currentStep,
        organization_name: masterOrg ? masterOrg.value.trim() : '',
        appointment_date: masterDate ? masterDate.value : '',
        payment_method: payMethod,
        payment_provider_id: activeProvider ? activeProvider.value : '',
        agree_terms: document.getElementById('agree_terms')?.checked || false,
        patients: patients,
        saved_at: Date.now()
    };

    try {
        localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draft));
    } catch (e) {
        console.warn("Failed to write bulk draft to localStorage:", e);
    }
}

async function loadBulkDraft() {
    try {
        const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
        if (!raw) return false;
        const draft = JSON.parse(raw);
        if (!draft) return false;

        isRestoringDraft = true;
        const now = new Date();
        const todayLocal = now.toLocaleDateString('en-CA');

        // REVALIDATE MASTER APPOINTMENT DATE: Reset if date is in the past
        let isDateOutdated = false;
        if (draft.appointment_date) {
            if (draft.appointment_date < todayLocal) {
                isDateOutdated = true;
                draft.appointment_date = todayLocal;
            }
            if (masterDate) masterDate.value = draft.appointment_date;
        }

        if (draft.organization_name && masterOrg) {
            masterOrg.value = draft.organization_name;
        }

        await fetchOccupancy();

        if (Array.isArray(draft.patients) && draft.patients.length > 0) {
            document.getElementById('rowContainer').innerHTML = '';
            rowCount = 0;
            draft.patients.forEach(p => {
                // Clear stale time slot if date changed or was in the past
                if (isDateOutdated || (p.appointment_date && p.appointment_date < todayLocal)) {
                    p.appointment_date = todayLocal;
                    p.time_slot = '';
                }
                addRow(p);
            });
        }

        // Restore Payment Method & Provider
        if (draft.payment_method) {
            const payRadio = document.getElementById(draft.payment_method === 'Cashless' ? 'pay_cashless' : 'pay_cash');
            if (payRadio) {
                payRadio.checked = true;
                bulkLastMethod = draft.payment_method;
            }
        }

        if (draft.payment_provider_id) {
            const provRadio = document.getElementById(`provider_${draft.payment_provider_id}`);
            if (provRadio) {
                provRadio.checked = true;
                bulkLastProviderId = draft.payment_provider_id;
                const qrImg = document.getElementById('selected_provider_qr');
                const qrName = document.getElementById('selected_provider_name');
                if (qrImg && provRadio.dataset.qr) qrImg.src = provRadio.dataset.qr;
                if (qrName && provRadio.dataset.name) qrName.innerText = provRadio.dataset.name;
            }
        }

        if (draft.agree_terms) {
            const agreeTerms = document.getElementById('agree_terms');
            if (agreeTerms) agreeTerms.checked = true;
        }

        restoreBulkReceiptPreview();
        toggleBulkPaymentFields();
        performGlobalSync();
        toggleBulkSubmitButton();

        // If date was in the past, keep user on Step 2 to pick a fresh valid schedule
        if (!isDateOutdated && draft.step && draft.step > 2 && draft.organization_name && draft.appointment_date) {
            goToPage(draft.step);
        } else {
            goToPage(2);
        }

        isRestoringDraft = false;
        return true;
    } catch (e) {
        console.error("Failed to load bulk draft:", e);
        isRestoringDraft = false;
        return false;
    }
}

function clearBulkDraft() {
    try {
        localStorage.removeItem(DRAFT_STORAGE_KEY);
        localStorage.removeItem('receipt_base64_bulk');
        localStorage.removeItem('receipt_name_bulk');
    } catch (e) {}
}

// =========================================================================
// 7. SUMMARY UPDATER & REAL-TIME SYNCS
// =========================================================================
function updateBulkSummary() {
    const rows = document.querySelectorAll('#rowContainer tr');
    let totalSum = 0;
    let paxCount = rows.length;
    let paxListHtml = '';

    rows.forEach(tr => {
        const idx = tr.id.split('_')[1];
        if (!idx) return;
        const p = getRowPatientData(idx);
        const age = calculateAge(p.birthdate);
        const address = (p.street || p.barangay || p.city || p.province) 
            ? `${p.street}, BRGY. ${p.barangay}, ${p.city}, ${p.province}`.toUpperCase() 
            : 'N/A';
        const displayTests = document.getElementById(`display_tests_${idx}`)?.innerText || 'No tests selected';

        let hoverDetails = `
            <strong>Sex:</strong> ${p.sex}<br>
            <strong>Birthdate:</strong> ${p.birthdate || 'N/A'}<br>
            <strong>Phone:</strong> ${p.phone || 'N/A'}<br>
            <strong>Email:</strong> ${p.email || 'N/A'}<br>
            <strong>Address:</strong> ${address}<br>
            <strong>Tests:</strong> ${displayTests}
        `.replace(/"/g, '&quot;').replace(/\n/g, ' ');

        if (p.name && p.name.trim()) {
            paxListHtml += `
                <div class="p-1.5 mb-1 rounded hover-bg border-bottom border-secondary border-opacity-5 d-flex justify-content-between align-items-center"
                     style="cursor: help;"
                     data-bs-toggle="popover" 
                     data-bs-trigger="hover focus" 
                     data-bs-html="true"
                     data-bs-content="${hoverDetails}"
                     title="${p.name}">
                    <span class="text-truncate small text-main fw-semibold" style="max-width: 180px;">${p.name}</span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary x-small py-0.5" style="font-size:0.6rem;">${p.sex.toUpperCase()}</span>
                </div>
            `;
        }

        const selectedServices = tr.querySelectorAll('input[type="hidden"][name*="[service_ids]"]');
        selectedServices.forEach(input => {
            const serviceId = input.value;
            const service = servicesMap[serviceId];
            if (service) {
                totalSum += parseFloat(service.price) || 0;
            }
        });
    });

    const listContainer = document.getElementById('sum_pax_list_container');
    const listDiv = document.getElementById('sum_pax_list');
    if (paxListHtml && listContainer && listDiv) {
        listDiv.innerHTML = paxListHtml;
        listContainer.classList.remove('d-none');
        const popovers = listDiv.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(el => new bootstrap.Popover(el));
    } else if (listContainer) {
        listContainer.classList.add('d-none');
    }

    document.getElementById('sum_pax_count').innerText = `${paxCount} PATIENT${paxCount === 1 ? '' : 'S'} ADDED`;
    document.getElementById('sum_total').innerText = totalSum.toLocaleString(undefined, { minimumFractionDigits: 2 });
}

function performGlobalSync() {
    document.getElementById('hidden_org').value = masterOrg.value;
    document.getElementById('hidden_date').value = masterDate.value;
    document.getElementById('sum_org').innerText = masterOrg.value || '---';
    if (masterDate.value) {
        document.getElementById('sum_schedule').classList.remove('d-none');
        document.getElementById('sum_date').innerText = masterDate.value;
    } else {
        document.getElementById('sum_schedule').classList.add('d-none');
    }
    updateBulkSummary();
}

masterOrg.addEventListener('input', () => {
    performGlobalSync();
    saveBulkDraft();
});

// =========================================================================
// 8. TIME SLOTS & OCCUPANCY ENGINE
// =========================================================================
async function fetchOccupancy() {
    const mDateInput = document.getElementById('master_date').value;
    const now = new Date();
    const todayLocal = now.toLocaleDateString('en-CA');
    const mDate = mDateInput || todayLocal;
    try {
        const res = await fetch(`/api/check-slots?date=${mDate}`);
        const data = await res.json();
        cachedOccupancy = data.occupied_slots || {};
    } catch (e) {
        console.error("Occupancy Fetch Failed", e);
        cachedOccupancy = {};
    }
}

async function validateMasterDate() {
    const selectedDate = masterDate.value;
    if (!selectedDate) return false;
    const parts = selectedDate.split('-');
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    const dayNum = d.getDay();
    const config = cachedConfigs[dayNum];
    const proceedBtn = document.getElementById('proceed_to_compilation_btn');
    const errorMsg = document.getElementById('date_validation_msg');

    if (!config || !config.is_open || config.is_open === '0' || config.is_open === 0 || config.is_open === false) {
        errorMsg.innerText = "Clinic is closed on this day. Please select another date.";
        errorMsg.classList.remove('d-none');
        proceedBtn.classList.add('opacity-75');
        return false;
    }

    let hasAvailableSlots = false;
    let start = new Date(`2000-01-01 ${config.opening_time}`);
    let end = new Date(`2000-01-01 ${config.closing_time}`);
    const now = new Date();
    const todayLocal = now.toLocaleDateString('en-CA');

    while (start < end) {
        let tStr = start.toTimeString().split(' ')[0];
        let isLunch = ((config.has_lunch_break === true || config.has_lunch_break === 1 || parseInt(config.has_lunch_break) === 1) && tStr >= config.lunch_start && tStr < config.lunch_end);
        let dbCount = parseInt(cachedOccupancy[tStr] || 0);
        let isPast = false;
        if (selectedDate === todayLocal) {
            const leadTimeMs = (parseInt(config.lead_time_hours) || 0) * 3600 * 1000;
            const cutoffTime = now.getTime() + leadTimeMs;
            const slotDate = new Date(`${selectedDate} ${tStr}`);
            isPast = slotDate.getTime() < cutoffTime;
        }

        if (!isLunch && !isPast && dbCount < parseInt(config.max_patients_per_slot || 1)) {
            hasAvailableSlots = true;
            break;
        }
        start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
    }

    if (!hasAvailableSlots) {
        errorMsg.innerText = "All time slots for this date are fully booked or unavailable due to lead-time limits. Please select another date.";
        errorMsg.classList.remove('d-none');
        proceedBtn.classList.add('opacity-75');
        return false;
    } else {
        errorMsg.classList.add('d-none');
        proceedBtn.classList.remove('opacity-75');
        return true;
    }
}

masterDate.addEventListener('change', async function () {
    const selectedDate = this.value;
    if (!selectedDate) return;
    await fetchOccupancy();
    const isValid = await validateMasterDate();
    if (isValid) {
        document.querySelectorAll('.row-date-input').forEach(input => {
            input.value = selectedDate;
            input.min = selectedDate;
            updateRowSlots(input);
        });
        performGlobalSync();
        saveBulkDraft();
    }
});

function updateRowSlots(input, savedSlot = '') {
    const td = input.closest('td');
    const select = td.querySelector('.t-select');
    const selectedDate = input.value;
    const tr = input.closest('tr');
    const idx = tr.id.split('_')[1];

    if (!selectedDate || !cachedConfigs) {
        select.innerHTML = '<option value="">Pick Date First</option>';
        return;
    }

    const dayNum = new Date(selectedDate).getDay();
    const config = cachedConfigs[dayNum];

    if (!config || !config.is_open || config.is_open === '0' || config.is_open === 0 || config.is_open === false) {
        select.innerHTML = '<option value="">CLOSED</option>';
        return;
    }

    let html = '<option value="">Choose Time</option>';
    let start = new Date(`2000-01-01 ${config.opening_time}`);
    let end = new Date(`2000-01-01 ${config.closing_time}`);
    let availableCount = 0;
    const now = new Date();
    const todayLocal = now.toLocaleDateString('en-CA');

    while (start < end) {
        let hours = start.getHours().toString().padStart(2, '0');
        let minutes = start.getMinutes().toString().padStart(2, '0');
        let tStr = `${hours}:${minutes}:00`;

        let isLunch = ((config.has_lunch_break === true || config.has_lunch_break === 1 || parseInt(config.has_lunch_break) === 1) && tStr >= config.lunch_start && tStr < config.lunch_end);
        let dbCount = parseInt(cachedOccupancy[tStr] || 0);
        let onScreenCount = getOnScreenOccupiedCount(selectedDate, tStr, idx);
        const maxLimit = config ? parseInt(config.max_patients_per_slot) : 1;
        let isFull = (dbCount + onScreenCount) >= maxLimit;
        let isPast = false;

        if (selectedDate === todayLocal) {
            const leadTimeMs = (parseInt(config.lead_time_hours) || 0) * 3600 * 1000;
            const cutoffTime = now.getTime() + leadTimeMs;
            const slotDate = new Date(`${selectedDate} ${tStr}`);
            isPast = slotDate.getTime() < cutoffTime;
        }

        if (!isLunch && !isPast && (!isFull || savedSlot === tStr)) {
            let disp = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            let selectedAttr = (savedSlot === tStr) ? 'selected' : '';
            html += `<option value="${tStr}" ${selectedAttr}>${disp}</option>`;
            availableCount++;
        }
        start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
    }

    select.innerHTML = (availableCount > 0) ? html : '<option value="">FULLY BOOKED</option>';
    select.disabled = (availableCount === 0);
}

window.refreshAllRowSlots = function () {
    const rows = document.querySelectorAll('#rowContainer tr');
    rows.forEach(tr => {
        const rDateInput = tr.querySelector('.row-date-input');
        if (rDateInput && rDateInput.value) {
            const tSelect = tr.querySelector('.t-select');
            const savedVal = tSelect ? tSelect.value : '';
            updateRowSlots(rDateInput, savedVal);
        }
    });
    performGlobalSync();
    saveBulkDraft();
};

async function runSmartScheduler() {
    const mDateInput = document.getElementById('master_date').value;
    if (!mDateInput) return showAlert("Please select a Preferred Start Date first.");
    const btn = document.getElementById('smartSchedBtn');
    btn.disabled = true;
    btn.innerHTML = 'SCHEDULING...';

    try {
        await fetchOccupancy();
        let localTracker = {};
        const initialRows = document.querySelectorAll('#rowContainer tr');
        initialRows.forEach(tr => {
            const rDateInput = tr.querySelector('.row-date-input');
            const rTimeSelect = tr.querySelector('.t-select');
            if (rDateInput?.value && rTimeSelect?.value) {
                const key = `${rDateInput.value}_${rTimeSelect.value}`;
                localTracker[key] = (localTracker[key] || 0) + 1;
            }
        });

        let currentPtrDate = new Date(mDateInput);
        const rows = document.querySelectorAll('#rowContainer tr');
        const now = new Date();
        const todayLocal = now.toLocaleDateString('en-CA');

        for (let tr of rows) {
            const dInput = tr.querySelector('.row-date-input');
            const tSelect = tr.querySelector('.t-select');
            if (dInput?.value && tSelect?.value && tSelect.value !== '') continue;

            let assigned = false;
            let daySafety = 0;
            while (!assigned && daySafety < 30) {
                let dStr = currentPtrDate.toLocaleDateString('en-CA');
                let config = cachedConfigs[currentPtrDate.getDay()];
                if (!config || parseInt(config.is_open) === 0) {
                    currentPtrDate.setDate(currentPtrDate.getDate() + 1);
                    daySafety++;
                    continue;
                }

                const openingTime = config ? config.opening_time : '08:00:00';
                const closingTime = config ? config.closing_time : '17:00:00';
                let startPtr = new Date(`${dStr} ${openingTime}`);
                let endPtr = new Date(`${dStr} ${closingTime}`);

                while (startPtr < endPtr) {
                    let hours = startPtr.getHours().toString().padStart(2, '0');
                    let minutes = startPtr.getMinutes().toString().padStart(2, '0');
                    let tStr = `${hours}:${minutes}:00`;

                    let isLunch = ((config.has_lunch_break === true || config.has_lunch_break === 1 || parseInt(config.has_lunch_break) === 1) && tStr >= config.lunch_start && tStr < config.lunch_end);
                    let isPast = false;
                    if (dStr === todayLocal) {
                        const leadTimeMs = (parseInt(config.lead_time_hours) || 0) * 3600 * 1000;
                        const cutoffTime = now.getTime() + leadTimeMs;
                        const slotDate = new Date(`${dStr} ${tStr}`);
                        isPast = slotDate.getTime() < cutoffTime;
                    }

                    if (!isLunch && !isPast) {
                        let dbCount = parseInt(cachedOccupancy[tStr] || 0);
                        let localCount = localTracker[`${dStr}_${tStr}`] || 0;
                        const maxPatients = config ? parseInt(config.max_patients_per_slot) : 1;

                        if (dbCount + localCount < maxPatients) {
                            dInput.value = dStr;
                            updateRowSlots(dInput, tStr);
                            localTracker[`${dStr}_${tStr}`] = localCount + 1;
                            assigned = true;
                            break;
                        }
                    }
                    startPtr.setMinutes(startPtr.getMinutes() + parseInt(config.slot_duration));
                }
                if (!assigned) currentPtrDate.setDate(currentPtrDate.getDate() + 1);
                daySafety++;
            }
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cpu me-1"></i> SMART AUTO-TIME';
        performGlobalSync();
        saveBulkDraft();
    }
}

// =========================================================================
// 9. SERVICES SELECTION & GENDER RESTRICTIONS
// =========================================================================
window.openServiceModal = function (idx) {
    activeRowIdx = idx;
    const row = document.getElementById(`r_${idx}`);
    const sexInput = row ? row.querySelector(`input[name="patients[${idx}][sex]"]`) : null;
    const activeSex = sexInput ? sexInput.value : 'Male';
    const hiddenContainer = document.getElementById(`hidden_inputs_${idx}`);
    const selectedIds = hiddenContainer ? Array.from(hiddenContainer.querySelectorAll('input')).map(i => i.value) : [];

    document.querySelectorAll('.service-item').forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        const restriction = item.dataset.gender;
        if (checkbox) {
            checkbox.checked = selectedIds.includes(checkbox.value);
            if (activeSex === 'Male' && restriction === 'female') {
                item.classList.add('d-none');
                checkbox.checked = false;
            } else if (activeSex === 'Female' && restriction === 'male') {
                item.classList.add('d-none');
                checkbox.checked = false;
            } else {
                item.classList.remove('d-none');
            }
        }
    });

    const searchInput = document.getElementById('serviceSearch');
    if (searchInput) searchInput.value = '';

    const modalEl = document.getElementById('serviceModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
};

function applyServices() {
    const sel = Array.from(document.querySelectorAll('.service-item input:checked'));
    document.getElementById(`display_tests_${activeRowIdx}`).innerText = sel.map(s => s.dataset.label).join(', ') || 'NO TESTS';
    let h = '';
    sel.forEach(s => h += `<input type="hidden" name="patients[${activeRowIdx}][service_ids][]" value="${s.value}">`);
    document.getElementById(`hidden_inputs_${activeRowIdx}`).innerHTML = h;

    const modalEl = document.getElementById('serviceModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.hide();
    performGlobalSync();
    saveBulkDraft();
}

function handleRowSexChange(idx, newSex) {
    const hiddenInputs = document.querySelectorAll(`#hidden_inputs_${idx} input`);
    let updatedLabels = [];
    hiddenInputs.forEach(input => {
        const serviceId = input.value;
        const service = servicesMap[serviceId];
        if (service) {
            const restriction = service.gender_restriction;
            if (newSex === 'Male' && restriction === 'female') {
                input.remove();
            } else if (newSex === 'Female' && restriction === 'male') {
                input.remove();
            } else {
                updatedLabels.push(service.name);
            }
        }
    });
    document.getElementById(`display_tests_${idx}`).innerText = updatedLabels.join(', ') || 'NO TESTS';
    performGlobalSync();
    saveBulkDraft();
}

function bulkCopy(sourceIdx, genderTarget) {
    const sourceInputs = document.querySelectorAll(`#hidden_inputs_${sourceIdx} input`);
    const sourceIds = Array.from(sourceInputs).map(i => i.value);
    if (sourceIds.length === 0) return showAlert("Please select tests for this row first before copying.");

    const rows = document.querySelectorAll('#rowContainer tr');
    let count = 0;
    rows.forEach(tr => {
        const targetIdx = tr.id.split('_')[1];
        if (targetIdx == sourceIdx) return;
        const targetSex = tr.querySelector(`input[name="patients[${targetIdx}][sex]"]`)?.value || 'Male';
        if (genderTarget === 'all' || targetSex === genderTarget) {
            let filteredIds = [];
            let filteredLabels = [];
            sourceIds.forEach(id => {
                const service = servicesMap[id];
                if (service) {
                    const restriction = service.gender_restriction;
                    if (targetSex === 'Male' && restriction === 'female') return;
                    if (targetSex === 'Female' && restriction === 'male') return;
                    filteredIds.push(id);
                    filteredLabels.push(service.name);
                }
            });
            document.getElementById(`display_tests_${targetIdx}`).innerText = filteredLabels.join(', ') || 'NO TESTS';
            let h = '';
            filteredIds.forEach(id => h += `<input type="hidden" name="patients[${targetIdx}][service_ids][]" value="${id}">`);
            document.getElementById(`hidden_inputs_${targetIdx}`).innerHTML = h;
            count++;
        }
    });
    performGlobalSync();
    saveBulkDraft();
}

// =========================================================================
// 10. 12-COLUMN EXCEL IMPORT & PARSER
// =========================================================================
function switchTab(tab) {
    const manualPane = document.getElementById('pane-manual');
    const excelPane = document.getElementById('pane-excel');
    const manualBtn = document.getElementById('btn-manual');
    const excelBtn = document.getElementById('btn-excel');

    if (tab === 'manual') {
        manualPane.style.display = 'block';
        excelPane.style.display = 'none';
        manualBtn.className = 'btn-custom btn-accent px-4 py-2 fw-bold';
        excelBtn.className = 'btn-custom btn-outline-accent text-white px-4 py-2 border-0 fw-bold';
    } else {
        manualPane.style.display = 'none';
        excelPane.style.display = 'block';
        excelBtn.className = 'btn-custom btn-accent px-4 py-2 fw-bold';
        manualBtn.className = 'btn-custom btn-outline-accent text-white px-4 py-2 border-0 fw-bold';
    }
}

function excelDateToJSDate(serial) {
    const utc_days = Math.floor(serial - 25569);
    const utc_value = utc_days * 86400;
    const date_info = new Date(utc_value * 1000);
    const y = date_info.getFullYear();
    const m = String(date_info.getMonth() + 1).padStart(2, '0');
    const d = String(date_info.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function formatExcelDate(val) {
    if (!val) return '';
    if (val instanceof Date) {
        const y = val.getFullYear();
        const m = String(val.getMonth() + 1).padStart(2, '0');
        const d = String(val.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    if (typeof val === 'number' && val > 25569) {
        return excelDateToJSDate(val);
    }
    if (typeof val === 'string') {
        const cleanStr = val.trim();
        const parsed = Date.parse(cleanStr);
        if (!isNaN(parsed)) {
            const dObj = new Date(parsed);
            const y = dObj.getFullYear();
            const m = String(dObj.getMonth() + 1).padStart(2, '0');
            const d = String(dObj.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }
        const match = cleanStr.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/);
        if (match) {
            const y = match[3];
            const m = match[1].padStart(2, '0');
            const d = match[2].padStart(2, '0');
            return `${y}-${m}-${d}`;
        }
    }
    return val;
}

function normalizeExcelPhone(val) {
    if (val === undefined || val === null) return '';
    let phone = String(val).trim().split('.')[0];
    phone = phone.replace(/[^\d+]/g, '');
    if (phone.startsWith('+639') && phone.length === 13) {
        return '0' + phone.substring(3);
    }
    if (phone.startsWith('639') && phone.length === 12) {
        return '0' + phone.substring(2);
    }
    if (phone.startsWith('9') && phone.length === 10) {
        return '0' + phone;
    }
    return phone;
}

function importExcelData() {
    const fileInput = document.getElementById('excel_file_input');
    const btn = document.getElementById('importBtn');
    if (!fileInput.files[0]) return showAlert("Please select an Excel file first.");

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>IMPORTING...';

    const file = fileInput.files[0];
    const reader = new FileReader();

    reader.onload = function (e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array', cellDates: true });
        const firstSheet = workbook.SheetNames[0];
        const jsonData = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], { header: 1 });

        if (jsonData.length > 1) {
            document.getElementById('rowContainer').innerHTML = '';
            rowCount = 0;
            let loadedCount = 0;

            jsonData.slice(1).forEach(row => {
                if (row[0] || row[2]) {
                    const firstName = String(row[0] || '').trim();
                    const middleName = String(row[1] || '').trim() || 'N/A';
                    const lastName = String(row[2] || '').trim();
                    const suffix = String(row[3] || '').trim();
                    const birthdateFormatted = formatExcelDate(row[4]);
                    const sex = row[5] ? (String(row[5]).trim().toLowerCase() === 'female' ? 'Female' : 'Male') : 'Male';
                    const phoneNormalized = normalizeExcelPhone(row[6]);
                    const email = String(row[7] || '').trim();
                    const street = String(row[8] || '').trim();
                    const barangay = String(row[9] || '').trim();
                    const city = String(row[10] || '').trim();
                    const province = String(row[11] || '').trim();
                    const fullName = `${firstName}${middleName && middleName !== 'N/A' ? ' ' + middleName : ''} ${lastName}${suffix ? ' ' + suffix : ''}`.trim();

                    addRow({
                        first_name: firstName,
                        middle_name: middleName,
                        last_name: lastName,
                        suffix: suffix,
                        name: fullName,
                        birthdate: birthdateFormatted,
                        sex: sex,
                        phone: phoneNormalized,
                        email: email,
                        street: street,
                        barangay: barangay,
                        city: city,
                        province: province
                    });
                    loadedCount++;
                }
            });

            switchTab('manual');
            showAlert(`Success! ${loadedCount} patient records loaded into the manual form. Please review any flagged records and select tests.`);
        } else {
            showAlert("Excel sheet contains no data records.");
        }
        btn.disabled = false;
        btn.innerHTML = 'LOAD DATA INTO MANUAL FORM <i class="bi bi-arrow-right-short ms-1"></i>';
    };

    reader.readAsArrayBuffer(file);
}

// =========================================================================
// 11. PAYMENT CONTROLS, RECEIPT WORKFLOW & LOCK PROTOCOL
// =========================================================================
function hasActiveBulkReceipt() {
    const receiptInput = document.getElementById('in_receipt');
    const hasNewFile = receiptInput && receiptInput.files && receiptInput.files.length > 0;
    const hasCachedData = (window.bulkReceiptLocalData !== null) || (localStorage.getItem('receipt_base64_bulk') !== null);
    return hasNewFile || hasCachedData;
}

function updateBulkFieldLockState() {
    const hasReceipt = hasActiveBulkReceipt();
    const payCash = document.getElementById('pay_cash');
    const payCashless = document.getElementById('pay_cashless');
    const providerRadios = document.querySelectorAll('.bulk-prov-radio');

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

function handleBulkPaymentMethodChange(radio) {
    const targetMethod = radio.value;
    if (targetMethod === 'Cash' && bulkLastMethod === 'Cashless' && hasActiveBulkReceipt()) {
        alert("You have an attached proof of payment receipt. Please click 'Remove' on the receipt first before switching to Cash on Site.");
        const payCashless = document.getElementById('pay_cashless');
        if (payCashless) payCashless.checked = true;
        return;
    }
    bulkLastMethod = targetMethod;
    toggleBulkPaymentFields();
    updateBulkFieldLockState();
    toggleBulkSubmitButton();
}

function handleBulkProviderChange(radio) {
    const targetProviderId = radio.value;
    if (bulkLastProviderId && bulkLastProviderId !== targetProviderId && hasActiveBulkReceipt()) {
        alert("You have an attached proof of payment receipt. Please click 'Remove' on the receipt first before changing E-Wallet providers.");
        const prev = document.getElementById('provider_' + bulkLastProviderId);
        if (prev) prev.checked = true;
        return;
    }
    bulkLastProviderId = targetProviderId;
    const qrImg = document.getElementById('selected_provider_qr');
    const qrName = document.getElementById('selected_provider_name');
    if (radio && radio.checked) {
        if (qrImg) qrImg.src = radio.dataset.qr;
        if (qrName) qrName.innerText = radio.dataset.name;
        toggleBulkPaymentFields();
    }
    updateBulkFieldLockState();
    toggleBulkSubmitButton();
}

function toggleBulkPaymentFields() {
    const payCashless = document.getElementById('pay_cashless');
    const providerContainer = document.getElementById('provider_selection_container');
    const qrSection = document.getElementById('qr_section');
    const receiptContainer = document.getElementById('receipt_upload_container');
    const providerRadios = document.querySelectorAll('.bulk-prov-radio');
    const activeRadio = document.querySelector('.bulk-prov-radio:checked');

    if (payCashless && payCashless.checked) {
        if (providerContainer) providerContainer.classList.remove('d-none');
        if (activeRadio) {
            if (qrSection) qrSection.classList.remove('d-none');
            if (receiptContainer) receiptContainer.classList.remove('d-none');
            const qrImg = document.getElementById('selected_provider_qr');
            const qrName = document.getElementById('selected_provider_name');
            if (qrImg && activeRadio.dataset.qr) qrImg.src = activeRadio.dataset.qr;
            if (qrName && activeRadio.dataset.name) qrName.innerText = activeRadio.dataset.name;
        } else {
            if (qrSection) qrSection.classList.add('d-none');
            if (receiptContainer) receiptContainer.classList.add('d-none');
        }
    } else {
        if (providerContainer) providerContainer.classList.add('d-none');
        if (qrSection) qrSection.classList.add('d-none');
        if (receiptContainer) receiptContainer.classList.add('d-none');
        providerRadios.forEach(radio => radio.checked = false);
        bulkLastProviderId = null;
    }
    updateBulkFieldLockState();
    toggleBulkSubmitButton();
    saveBulkDraft();
}

function handleBulkReceiptUpload(input) {
    const file = input.files[0];
    const previewContainer = document.getElementById('receipt_preview_container');
    const inputWrapper = document.getElementById('receipt_input_wrapper');
    const label = document.getElementById('receipt_file_label');

    if (!file) {
        removeBulkUploadedReceipt();
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        window.bulkReceiptLocalData = e.target.result;
        localStorage.setItem('receipt_base64_bulk', e.target.result);
        localStorage.setItem('receipt_name_bulk', file.name);
        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${file.name}`;
        updateBulkFieldLockState();
        toggleBulkSubmitButton();
    };
    reader.readAsDataURL(file);
}

function removeBulkUploadedReceipt() {
    const input = document.getElementById('in_receipt');
    const previewContainer = document.getElementById('receipt_preview_container');
    const inputWrapper = document.getElementById('receipt_input_wrapper');
    if (input) input.value = '';
    if (previewContainer) previewContainer.classList.add('d-none');
    if (inputWrapper) inputWrapper.classList.remove('d-none');
    window.bulkReceiptLocalData = null;
    localStorage.removeItem('receipt_base64_bulk');
    localStorage.removeItem('receipt_name_bulk');
    updateBulkFieldLockState();
    toggleBulkSubmitButton();
}

function viewBulkReceiptFile() {
    const b64 = window.bulkReceiptLocalData || localStorage.getItem('receipt_base64_bulk');
    const name = localStorage.getItem('receipt_name_bulk') || 'Proof of Payment Receipt';
    if (b64 && typeof window.openFilePreview === 'function') {
        window.openFilePreview(b64, name);
    } else if (b64) {
        window.zoomQR(b64);
    }
}

function restoreBulkReceiptPreview() {
    const b64 = localStorage.getItem('receipt_base64_bulk');
    const name = localStorage.getItem('receipt_name_bulk');
    if (b64 && name) {
        window.bulkReceiptLocalData = b64;
        const previewContainer = document.getElementById('receipt_preview_container');
        const inputWrapper = document.getElementById('receipt_input_wrapper');
        const label = document.getElementById('receipt_file_label');
        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${name}`;
    }
}

function toggleBulkSubmitButton() {
    const agreeCheckbox = document.getElementById('agree_terms');
    const submitBtn = document.getElementById('final_submit_btn');
    const payCashless = document.getElementById('pay_cashless');
    const activeProvider = document.querySelector('.bulk-prov-radio:checked');

    if (!agreeCheckbox || !submitBtn) return;

    const isTermsAgreed = agreeCheckbox.checked;
    const isCashless = payCashless && payCashless.checked;
    const hasReceipt = hasActiveBulkReceipt();
    const hasProvider = isCashless ? (activeProvider !== null) : true;

    const isFormValid = isCashless ? (isTermsAgreed && hasReceipt && hasProvider) : isTermsAgreed;

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

// Global zoomQR helper redirecting directly to openFilePreview so controls, image, and scale toolbar work
window.zoomQR = function(qrSrc) {
    if (qrSrc && typeof window.openFilePreview === 'function') {
        const providerName = document.getElementById('selected_provider_name')?.innerText || 'E-Wallet';
        window.openFilePreview(qrSrc, `${providerName} Payment QR Code`);
    }
};

// =========================================================================
// 12. FINAL SUBMISSION VALIDATOR & STEP 4 INTERCEPTOR
// =========================================================================
function submitBulkManualForm(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const form = document.getElementById('bulkForm');
    if (!form) return;

    // Run full validation check
    if (!validateBulkForm(e)) {
        return false;
    }

    // Activate the loading spinner immediately
    const submitBtn = document.getElementById('final_submit_btn');
    if (submitBtn) {
        submitBtn.style.pointerEvents = 'none';
        submitBtn.classList.add('opacity-75');
        submitBtn.innerHTML = 'SUBMITTING... <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>';
    }

    // Clear active draft from local storage
    clearBulkDraft();

    // Dispatch native POST submission
    form.submit();
}
window.submitBulkManualForm = submitBulkManualForm;

function validateBulkForm(e) {
    // 1. Sync hidden organization and date fields
    performGlobalSync();
    let errors = [];

    // 2. Validate Step 2 Fields
    const org = masterOrg ? masterOrg.value.trim() : '';
    const date = masterDate ? masterDate.value : '';
    if (!org) errors.push("Organization / Company Name is required.");
    if (!date) errors.push("Preferred Start Date is required.");

    // 3. Validate Patient Rows (Minimum 2)
    const rows = document.querySelectorAll('#rowContainer tr');
    if (rows.length < 2) {
        errors.push("Bulk booking requires at least 2 patient records.");
    }

    rows.forEach((tr, index) => {
        const idx = tr.id.split('_')[1];
        const rowNum = index + 1;
        const p = getRowPatientData(idx);
        const rowErrors = getPatientRowValidationErrors(p);
        
        rowErrors.forEach(err => errors.push(`Row ${rowNum} (${p.name || 'Unnamed'}): ${err}`));

        const testInputs = tr.querySelectorAll('input[type="hidden"][name*="[service_ids]"]');
        if (testInputs.length === 0) {
            errors.push(`Row ${rowNum} (${p.name || 'Unnamed'}): At least one laboratory test must be selected.`);
        }

        const rowDate = tr.querySelector('.row-date-input')?.value;
        const tSelect = tr.querySelector('.t-select')?.value;
        if (!rowDate) errors.push(`Row ${rowNum}: Schedule Date is required.`);
        if (!tSelect) errors.push(`Row ${rowNum}: Preferred Time Slot is required.`);
    });

    // 4. Validate Payment Method & E-Wallet Receipt
    const payCashless = document.getElementById('pay_cashless');
    if (payCashless && payCashless.checked) {
        const selectedProvider = document.querySelector('.bulk-prov-radio:checked');
        if (!selectedProvider) {
            errors.push("Payment: Please select an E-Wallet provider (e.g., GCash, Maya).");
        }
        if (!hasActiveBulkReceipt()) {
            errors.push("Payment: Please upload a copy of your transaction receipt to finalize.");
        }
    }

    // 5. Validate Terms Checkbox
    const agreeTerms = document.getElementById('agree_terms');
    if (agreeTerms && !agreeTerms.checked) {
        errors.push("Agreements: You must agree to the Clinical Privacy Policy to confirm.");
    }

    // 6. Display Validation Modal If Any Errors Exist
    if (errors.length > 0) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        let errorHtml = '<div class="text-start mb-3 small text-white-50">Please correct the following omissions to proceed:</div>';
        errorHtml += '<ul class="text-start small text-danger mb-0 ps-3" style="max-height: 250px; overflow-y: auto;">';
        errors.forEach(err => {
            errorHtml += `<li class="mb-1">${err}</li>`;
        });
        errorHtml += '</ul>';

        document.getElementById('wizardValidationTitle').innerText = "Requirements Lacking";
        document.getElementById('wizardValidationMsg').innerHTML = errorHtml;
        const modalEl = document.getElementById('wizardValidationModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
        return false;
    }

    return true;
}

function showAlert(msg) {
    document.getElementById('wizardValidationTitle').innerText = "Attention Required";
    document.getElementById('wizardValidationMsg').innerHTML = msg;
    const modalEl = document.getElementById('wizardValidationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

// =========================================================================
// 13. DOM INITIALIZER & LIFECYCLE HOOKS
// =========================================================================
document.addEventListener('DOMContentLoaded', async () => {
    // Real-time error clearance on modal inputs
    document.querySelectorAll('#modalPatientForm input, #modalPatientForm select').forEach(input => {
        const clearAction = () => {
            input.classList.remove('is-invalid');
            const errDiv = document.getElementById('err_' + input.id);
            if (errDiv) {
                errDiv.classList.add('d-none');
                errDiv.classList.remove('d-block');
                errDiv.innerText = '';
            }
        };
        input.addEventListener('input', clearAction);
        input.addEventListener('change', clearAction);
    });

    const searchInput = document.getElementById('serviceSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toUpperCase();
            document.querySelectorAll('.service-item').forEach(item => {
                const name = item.dataset.name || '';
                item.classList.toggle('d-none', !name.includes(query));
            });
        });
    }

    const modalPhoneDisp = document.getElementById('modal_phone_display');
    if (modalPhoneDisp) {
        modalPhoneDisp.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            document.getElementById('modal_phone').value = this.value ? '09' + this.value : '';
        });
    }

    // Attempt to restore and revalidate state from localStorage
    const hasDraft = await loadBulkDraft();
    if (!hasDraft) {
        await fetchOccupancy();
        await validateMasterDate();
        toggleBulkPaymentFields();
        toggleBulkSubmitButton();
    }
});
</script>
@endpush