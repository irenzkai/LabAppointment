<!-- resources/views/appointments/partials/bulk/scripts-patient.blade.php -->
@push('scripts')
<script>
// =========================================================================
// SCRIPT PARTIAL 1: PATIENT DEMOGRAPHICS, ADDRESS & SPREADSHEET BUILDER
// =========================================================================
let rowCount = 0;
let activeRowIdx = null;
let isDuplicatingRow = false;
const apiBase = "https://psgc.gitlab.io/api";

// =========================================================================
// 1. VALIDATION RULES (STRICT 18+ BIRTHDATE & MANDATORY CONTACT VERIFICATION)
// =========================================================================
function validateNameString(val, fieldName) {
    if (!val || val === 'N/A') return null;
    const charRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc\s.\'-]+$/;
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

    // Strict 18+ Mandatory Birthdate Verification
    if (!p.birthdate) {
        errors.push("Missing Birthdate (18+ Required)");
    } else {
        const age = calculateAge(p.birthdate);
        const dob = new Date(p.birthdate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (isNaN(dob.getTime()) || dob > today) {
            errors.push("Invalid Birthdate");
        } else if (age === 'N/A' || age < 18) {
            errors.push(`Must be at least 18 years old (Current: ${age} yrs)`);
        }
    }

    const phoneClean = (p.phone || '').trim();
    const emailClean = (p.email || '').trim();

    // Patient contact details are strictly mandatory for BOTH normal bulk and recognized org bulk
    if (!phoneClean) {
        errors.push("Missing Phone Number");
    } else if (!/^09\d{9}$/.test(phoneClean)) {
        errors.push("Invalid Phone (09XXXXXXXXX)");
    }

    if (!emailClean) {
        errors.push("Missing Email Address");
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(emailClean)) {
        errors.push("Invalid Email Address");
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
// 2. CHECKBOX MULTI-ROW SELECTION & BATCH DELETE (BOOTSTRAP MODAL ENFORCED)
// =========================================================================
window.toggleSelectAllRows = function(masterCheckbox) {
    const isChecked = masterCheckbox.checked;
    document.querySelectorAll('.row-select-chk').forEach(chk => {
        chk.checked = isChecked;
    });
    updateSelectedRowsState();
};

window.updateSelectedRowsState = function() {
    const checkedBoxes = document.querySelectorAll('.row-select-chk:checked');
    const totalBoxes = document.querySelectorAll('.row-select-chk');
    const deleteBtn = document.getElementById('btnDeleteSelected');
    const countSpan = document.getElementById('selectedRowsCount');
    const masterCheckbox = document.getElementById('selectAllRows');

    // Make row selection pronounced by dynamically toggling .row-selected class
    document.querySelectorAll('.row-select-chk').forEach(chk => {
        const tr = chk.closest('tr');
        if (tr) {
            tr.classList.toggle('row-selected', chk.checked);
        }
    });

    if (countSpan) countSpan.innerText = checkedBoxes.length;
    if (deleteBtn) {
        deleteBtn.classList.toggle('d-none', checkedBoxes.length === 0);
    }
    if (masterCheckbox && totalBoxes.length > 0) {
        masterCheckbox.checked = (checkedBoxes.length === totalBoxes.length);
    }
};

window.deleteSelectedBulkRows = function() {
    const checkedBoxes = document.querySelectorAll('.row-select-chk:checked');
    if (checkedBoxes.length === 0) return;

    const modalCountEl = document.getElementById('modalDeleteSelectedCount');
    if (modalCountEl) {
        modalCountEl.innerText = checkedBoxes.length;
    }

    const modalEl = document.getElementById('deleteSelectedBulkModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.confirmDeleteSelectedBulkRows = function() {
    const checkedBoxes = document.querySelectorAll('.row-select-chk:checked');
    checkedBoxes.forEach(chk => {
        const row = chk.closest('tr');
        if (row) row.remove();
    });

    const masterCheckbox = document.getElementById('selectAllRows');
    if (masterCheckbox) masterCheckbox.checked = false;

    const modalEl = document.getElementById('deleteSelectedBulkModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    updateSelectedRowsState();
    if (typeof performGlobalSync === 'function') performGlobalSync();
    if (typeof saveBulkDraft === 'function') saveBulkDraft();
};

// =========================================================================
// 3. MODULAR ROW DUPLICATION CONTROLLER
// =========================================================================
window.duplicateRow = function(idx) {
    const modalEl = document.getElementById('duplicateRowModal');
    if (!modalEl) return;

    document.getElementById('source_row_index_to_duplicate').value = idx;

    // Reset default duplicate toggles
    document.getElementById('dup_tests').checked = true;
    document.getElementById('dup_address').checked = true;
    document.getElementById('dup_schedule').checked = true;
    document.getElementById('dup_personal').checked = false;

    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();
};

window.executeModularDuplication = async function() {
    if (isDuplicatingRow) return;

    const sourceIdx = document.getElementById('source_row_index_to_duplicate').value;
    const tr = document.getElementById(`r_${sourceIdx}`);
    if (!tr) return;

    isDuplicatingRow = true;

    try {
        const dupTests = document.getElementById('dup_tests').checked;
        const dupAddress = document.getElementById('dup_address').checked;
        const dupSchedule = document.getElementById('dup_schedule').checked;
        const dupPersonal = document.getElementById('dup_personal').checked;

        const src = getRowPatientData(sourceIdx);
        const newPatient = {};

        // 1. Personal Identity
        if (dupPersonal) {
            newPatient.first_name = src.first_name;
            newPatient.middle_name = src.middle_name;
            newPatient.last_name = src.last_name;
            newPatient.suffix = src.suffix;
            newPatient.name = src.name;
            newPatient.sex = src.sex;
            newPatient.birthdate = src.birthdate;
            newPatient.phone = src.phone;
            newPatient.email = src.email;
        } else {
            newPatient.first_name = '';
            newPatient.middle_name = 'N/A';
            newPatient.last_name = '';
            newPatient.suffix = '';
            newPatient.name = '';
            newPatient.sex = src.sex || 'Male';
            newPatient.birthdate = '';
            newPatient.phone = '';
            newPatient.email = '';
        }

        // 2. Address Details
        if (dupAddress) {
            newPatient.province = src.province;
            newPatient.city = src.city;
            newPatient.barangay = src.barangay;
            newPatient.street = src.street;
        }

        // 3. Tests & Packages
        if (dupTests) {
            const svcInputs = tr.querySelectorAll(`input[name="patients[${sourceIdx}][service_ids][]"]`);
            newPatient.service_ids = Array.from(svcInputs).map(i => i.value);

            const pkgInputs = tr.querySelectorAll(`input[name="patients[${sourceIdx}][package_ids][]"]`);
            newPatient.package_ids = Array.from(pkgInputs).map(i => i.value);
        }

        // 4. Schedule Slot (Cascades automatically through full slots, Sunday/CLOSED, and NO SLOTS)
        if (dupSchedule) {
            const fallbackDate = document.getElementById('master_date')?.value || '';
            const baseDate = src.appointment_date || fallbackDate;
            const baseSlot = src.time_slot || '';

            if (baseDate && typeof window.findNextAvailableSlot === 'function') {
                const nextSched = await window.findNextAvailableSlot(baseDate, baseSlot);
                if (nextSched) {
                    newPatient.appointment_date = nextSched.date;
                    newPatient.time_slot = nextSched.time_slot;
                } else {
                    newPatient.appointment_date = baseDate;
                    newPatient.time_slot = baseSlot;
                }
            } else if (baseDate) {
                newPatient.appointment_date = baseDate;
                newPatient.time_slot = baseSlot;
            }
        }

        const modalEl = document.getElementById('duplicateRowModal');
        if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
        }

        const targetRowIdx = rowCount;
        addRow(newPatient);

        // If personal info was left blank, immediately prompt the user to input details
        if (!dupPersonal) {
            setTimeout(() => {
                openEditPatientModal(targetRowIdx);
            }, 300);
        }
    } finally {
        isDuplicatingRow = false;
    }
};

// =========================================================================
// 4. PRE-RECORDED ROSTER LOADER (ORGANIZATION-SPECIFIC)
// =========================================================================
window.loadSelectedRoster = async function(groupName) {
    if (!groupName) return;
    const orgId = document.getElementById('hidden_org_id')?.value || '';
    
    try {
        const url = `/bulk/roster/members?group=${encodeURIComponent(groupName)}${orgId ? '&organization_id=' + encodeURIComponent(orgId) : ''}`;
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        if (!data.success || !Array.isArray(data.members) || data.members.length === 0) {
            return showAlert(`No saved members found in roster '${groupName}'.`);
        }

        let loadedCount = 0;
        data.members.forEach(m => {
            const patientData = {
                first_name: m.first_name,
                middle_name: m.middle_name || 'N/A',
                last_name: m.last_name,
                suffix: m.suffix || '',
                name: m.name,
                sex: m.sex || 'Male',
                birthdate: m.birthdate || '',
                phone: m.phone || '',
                email: m.email || '',
                province: m.province || '',
                city: m.city || '',
                barangay: m.barangay || '',
                street: m.street || '',
                package_ids: m.default_package_id ? [m.default_package_id.toString()] : [],
                service_ids: Array.isArray(m.default_service_ids) ? m.default_service_ids.map(String) : [],
            };
            addRow(patientData);
            loadedCount++;
        });

        const rosterSel = document.getElementById('saved_roster_select');
        if (rosterSel) rosterSel.value = '';

        showAlert(`Successfully loaded ${loadedCount} patient record(s) from roster '<strong>${groupName}</strong>'.`);
    } catch (err) {
        console.error("Failed to load roster:", err);
        showAlert("Error loading roster members. Please check your network connection.");
    }
};

// =========================================================================
// 5. PSGC ADDRESS ENGINE
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
// 6. PATIENT MODAL WORKFLOW
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

    const bdayInput = document.getElementById('modal_birthdate');
    if (bdayInput) {
        const eighteenYearsAgo = new Date();
        eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
        bdayInput.max = eighteenYearsAgo.toISOString().split('T')[0];
    }

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

    const bdayInput = document.getElementById('modal_birthdate');
    if (bdayInput) {
        const eighteenYearsAgo = new Date();
        eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
        bdayInput.max = eighteenYearsAgo.toISOString().split('T')[0];
    }

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

    // Birthdate 18+ Mandatory Verification
    const bday = document.getElementById('modal_birthdate').value;
    if (!bday) {
        markInvalid('modal_birthdate', 'err_modal_birthdate', 'Birthdate is required.');
    } else {
        const age = calculateAge(bday);
        const dob = new Date(bday);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (isNaN(dob.getTime()) || dob > today) {
            markInvalid('modal_birthdate', 'err_modal_birthdate', 'Invalid birthdate.');
        } else if (age === 'N/A' || age < 18) {
            markInvalid('modal_birthdate', 'err_modal_birthdate', `Patient must be at least 18 years old (Current: ${age} yrs).`);
        }
    }

    // Contact Verification (Mandatory for both Normal and Recognized Org Bulk)
    const phoneDisp = document.getElementById('modal_phone_display').value.trim();
    const phoneFull = phoneDisp ? ('09' + phoneDisp) : '';
    const phoneRegex = /^09\d{9}$/;

    if (!phoneDisp) {
        markInvalid('modal_phone_display', 'err_modal_phone', 'Contact Number is strictly required.');
    } else if (!phoneRegex.test(phoneFull)) {
        markInvalid('modal_phone_display', 'err_modal_phone', 'Must contain exactly 11 digits (09XXXXXXXXX).');
    }

    const email = document.getElementById('modal_email').value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!email) {
        markInvalid('modal_email', 'err_modal_email', 'Email Address is strictly required.');
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

    if (typeof updateBulkSummary === 'function') updateBulkSummary();
    if (typeof performGlobalSync === 'function') performGlobalSync();
    if (typeof saveBulkDraft === 'function') saveBulkDraft();
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
                        <span class="badge ${p.sex === 'Male' ? 'bg-info' : 'bg-danger'} text-white fw-bold uppercase" style="font-size: 0.65rem;">
                            ${p.sex ? p.sex.toUpperCase() : 'N/A'}
                        </span>
                        <span class="text-secondary smaller fw-semibold">${age !== 'N/A' ? age + ' YRS OLD' : 'AGE UNKNOWN'}</span>
                        ${hasErrors ? `<span class="badge bg-danger text-white uppercase" style="font-size: 0.6rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>REVISION NEEDED</span>` : ''}
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

    tr.style.borderColor = hasErrors ? "#ff4d4d" : "var(--border-color)";
    handleRowSexChange(idx, p.sex);
}

// =========================================================================
// 7. TABLE ROW RENDERER & SPREADSHEET BUILDER
// =========================================================================
function addRow(patient = null) {
    const minDate = document.getElementById('master_date')?.value || "{{ date('Y-m-d') }}";

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
    const selectedPackageIds = patient?.package_ids || [];

    const html = `
        <tr id="r_${rowCount}" class="border-secondary border-opacity-10 align-middle text-main" style="${hasErrors ? 'border: 1.5px solid #ff4d4d !important;' : ''}">
            {{-- Checkbox Multi-Selection --}}
            <td class="text-center ps-3 py-3" style="width: 4%;">
                <input type="checkbox" class="form-check-input row-select-chk ms-0" data-idx="${rowCount}" onchange="updateSelectedRowsState()">
            </td>

            {{-- 1. Patient Information & Address --}}
            <td class="ps-3 py-3" style="width: 36%;">
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
                                <span class="badge ${sex === 'Male' ? 'bg-info' : 'bg-danger'} text-white fw-bold uppercase" style="font-size: 0.65rem;">
                                    ${sex.toUpperCase()}
                                </span>
                                <span class="text-secondary smaller fw-semibold">${age !== 'N/A' ? age + ' YRS OLD' : 'AGE UNKNOWN'}</span>
                                ${hasErrors ? `<span class="badge bg-danger text-white uppercase" style="font-size: 0.6rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>REVISION NEEDED</span>` : ''}
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

            {{-- 2. Tests & Packages Selection --}}
            <td class="py-3 px-3" style="width: 22%;">
                <div id="display_tests_${rowCount}" class="text-main fw-bold mb-2 uppercase border border-secondary border-opacity-10 p-2 rounded small" style="min-height: 48px; font-size: 0.7rem; overflow-y: auto; max-height: 80px; background-color: rgba(0,0,0,0.02);">NO SELECTIONS</div>
                <div id="hidden_inputs_${rowCount}">
                    ${selectedPackageIds.map(id => `<input type="hidden" name="patients[${rowCount}][package_ids][]" value="${id}">`).join('')}
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
            <td class="pe-4 py-3 text-center align-middle" style="width: 16%;">
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

    if (typeof updateRowSlots === 'function') {
        updateRowSlots(newTr.querySelector('.row-date-input'), patient?.time_slot || '');
    }

    renderRowTestLabels(rowCount, selectedPackageIds, selectedServiceIds);

    if (typeof performGlobalSync === 'function') performGlobalSync();
    updateSelectedRowsState();
    if (typeof saveBulkDraft === 'function') saveBulkDraft();

    rowCount++;
}

function renderRowTestLabels(idx, packageIds, serviceIds) {
    let labels = [];
    packageIds.forEach(id => {
        const pkg = window.packagesMap ? window.packagesMap[id] : null;
        if (pkg) labels.push(`[PKG] ${pkg.name}`);
    });
    serviceIds.forEach(id => {
        const svc = window.servicesMap ? window.servicesMap[id] : null;
        if (svc) labels.push(svc.name);
    });

    const display = document.getElementById(`display_tests_${idx}`);
    if (display) {
        display.innerText = labels.join(', ') || 'NO SELECTIONS';
    }
}

function removeSpreadsheetRow(idx) {
    document.getElementById(`r_${idx}`)?.remove();
    updateSelectedRowsState();
    if (typeof performGlobalSync === 'function') performGlobalSync();
    if (typeof saveBulkDraft === 'function') saveBulkDraft();
}

function confirmClearAllBulkRows() {
    document.getElementById('rowContainer').innerHTML = '';
    rowCount = 0;
    const modalEl = document.getElementById('clearAllBulkModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
    updateSelectedRowsState();
    if (typeof performGlobalSync === 'function') performGlobalSync();
    if (typeof saveBulkDraft === 'function') saveBulkDraft();
}

function handleRowSexChange(idx, newSex) {
    const hiddenContainer = document.getElementById(`hidden_inputs_${idx}`);
    if (!hiddenContainer) return;

    let updatedLabels = [];
    hiddenContainer.querySelectorAll(`input[name="patients[${idx}][package_ids][]"]`).forEach(input => {
        const pkg = window.packagesMap ? window.packagesMap[input.value] : null;
        if (pkg) {
            if ((newSex === 'Male' && pkg.gender_restriction === 'female') || (newSex === 'Female' && pkg.gender_restriction === 'male')) {
                input.remove();
            } else {
                updatedLabels.push(`[PKG] ${pkg.name}`);
            }
        }
    });

    hiddenContainer.querySelectorAll(`input[name="patients[${idx}][service_ids][]"]`).forEach(input => {
        const svc = window.servicesMap ? window.servicesMap[input.value] : null;
        if (svc) {
            if ((newSex === 'Male' && svc.gender_restriction === 'female') || (newSex === 'Female' && svc.gender_restriction === 'male')) {
                input.remove();
            } else {
                updatedLabels.push(svc.name);
            }
        }
    });

    document.getElementById(`display_tests_${idx}`).innerText = updatedLabels.join(', ') || 'NO SELECTIONS';

    if (typeof performGlobalSync === 'function') performGlobalSync();
    if (typeof saveBulkDraft === 'function') saveBulkDraft();
}

function bulkCopy(sourceIdx, genderTarget) {
    const sourceSvcInputs = document.querySelectorAll(`input[name="patients[${sourceIdx}][service_ids][]"]`);
    const sourcePkgInputs = document.querySelectorAll(`input[name="patients[${sourceIdx}][package_ids][]"]`);

    const sourceSvcIds = Array.from(sourceSvcInputs).map(i => i.value);
    const sourcePkgIds = Array.from(sourcePkgInputs).map(i => i.value);

    if (sourceSvcIds.length === 0 && sourcePkgIds.length === 0) {
        return showAlert("Please select tests or packages for this row first before copying.");
    }

    const rows = document.querySelectorAll('#rowContainer tr');
    let count = 0;

    rows.forEach(tr => {
        const targetIdx = tr.id.split('_')[1];
        if (targetIdx == sourceIdx) return;

        const targetSex = tr.querySelector(`input[name="patients[${targetIdx}][sex]"]`)?.value || 'Male';

        if (genderTarget === 'all' || targetSex === genderTarget) {
            let h = '';
            let labels = [];

            sourcePkgIds.forEach(id => {
                const pkg = window.packagesMap ? window.packagesMap[id] : null;
                if (pkg) {
                    if (targetSex === 'Male' && pkg.gender_restriction === 'female') return;
                    if (targetSex === 'Female' && pkg.gender_restriction === 'male') return;
                    h += `<input type="hidden" name="patients[${targetIdx}][package_ids][]" value="${id}">`;
                    labels.push(`[PKG] ${pkg.name}`);
                }
            });

            sourceSvcIds.forEach(id => {
                const svc = window.servicesMap ? window.servicesMap[id] : null;
                if (svc) {
                    if (targetSex === 'Male' && svc.gender_restriction === 'female') return;
                    if (targetSex === 'Female' && svc.gender_restriction === 'male') return;
                    h += `<input type="hidden" name="patients[${targetIdx}][service_ids][]" value="${id}">`;
                    labels.push(svc.name);
                }
            });

            document.getElementById(`display_tests_${targetIdx}`).innerText = labels.join(', ') || 'NO SELECTIONS';
            document.getElementById(`hidden_inputs_${targetIdx}`).innerHTML = h;
            count++;
        }
    });

    if (typeof performGlobalSync === 'function') performGlobalSync();
    if (typeof saveBulkDraft === 'function') saveBulkDraft();
}
</script>
@endpush