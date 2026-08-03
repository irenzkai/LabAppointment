@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
let rowCount = 0;
let activeRowIdx = null;

// Injected configurations directly as a safe JS object literal to bypass DOM escaping errors
let cachedConfigs = @json($configs);
let cachedOccupancy = {};

// Formed global JS services map to lookup price and gender restrictions dynamically on changes
const servicesMap = @json($services->keyBy('id'));

const masterOrg = document.getElementById('master_org');
const masterDate = document.getElementById('master_date');

// 1. Navigation Controller (3 Steps)
function goToPage(page) {
    document.querySelectorAll('.wiz-section').forEach(s => s.classList.add('d-none'));
    document.getElementById('page-' + page).classList.remove('d-none');
    window.scrollTo(0, 0);
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

    // Enforce that at least 2 patients are registered to proceed with bulk processes
    if (rows.length < 2) {
        return showAlert("Bulk booking requires at least 2 patient records. For single appointments, please use the standard booking wizard.");
    }

    let missingTests = false;
    rows.forEach(tr => {
        const testInputs = tr.querySelectorAll('input[type="hidden"][name*="[service_ids]"]');
        if (testInputs.length === 0) {
            missingTests = true;
            tr.style.borderColor = "#ff4d4d";
        } else {
            tr.style.borderColor = "var(--border-color)";
        }
    });

    if (missingTests) {
        return showAlert("Every patient in the list must have at least one test selected.");
    }

    // Ensure all dynamic address dropdowns have been evaluated cleanly
    let missingAddress = false;
    rows.forEach(tr => {
        const idx = tr.id.split('_')[1];
        const prov = document.getElementById(`p_province_${idx}`);
        const city = document.getElementById(`p_city_${idx}`);
        const brgy = document.getElementById(`p_brgy_${idx}`);
        const street = document.getElementById(`p_street_${idx}`);

        if (!prov?.value || !city?.value || !brgy?.value || !street?.value.trim()) {
            missingAddress = true;
            tr.style.borderColor = "#ff4d4d";
        }
    });

    if (missingAddress) {
        return showAlert("Please complete all patient address selection fields before proceeding.");
    }

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

// Generate summary statistics and render scrollable patient directory
function updateBulkSummary() {
    const rows = document.querySelectorAll('#rowContainer tr');
    let totalSum = 0;
    let paxCount = rows.length;
    let paxListHtml = '';

    rows.forEach(tr => {
        const idx = tr.id.split('_')[1];
        if (!idx) return;

        const firstName = document.getElementById(`p_first_${idx}`)?.value || '';
        const lastName = document.getElementById(`p_last_${idx}`)?.value || '';
        const compiledName = document.getElementById(`p_name_${idx}`)?.value || '';
        const sex = tr.querySelector('.p-sex')?.value || 'Male';
        const bday = tr.querySelector('input[name*="[birthdate]"]')?.value || '';
        const email = tr.querySelector('input[name*="[email]"]')?.value || '';
        const phone = tr.querySelector('input[name*="[phone]"]')?.value || '';

        // Compile live address blocks from row selects
        const streetVal = document.getElementById(`p_street_${idx}`)?.value || '';
        const brgySel = document.getElementById(`p_brgy_${idx}`);
        const citySel = document.getElementById(`p_city_${idx}`);
        const provSel = document.getElementById(`p_province_${idx}`);
        const brgyVal = brgySel?.options[brgySel.selectedIndex]?.text || '';
        const cityVal = citySel?.options[citySel.selectedIndex]?.text || '';
        const provVal = provSel?.options[provSel.selectedIndex]?.text || '';
        const address = streetVal && brgyVal && cityVal && provVal
            ? `${streetVal}, BRGY. ${brgyVal}, ${cityVal}, ${provVal}`.toUpperCase()
            : 'N/A';

        // Get selected tests display label
        const displayTests = document.getElementById(`display_tests_${idx}`)?.innerText || 'No tests selected';

        // Compile HTML-safe popover contents
        let hoverDetails = `
            <strong>Sex:</strong> ${sex}<br>
            <strong>Birthdate:</strong> ${bday || 'N/A'}<br>
            <strong>Phone:</strong> ${phone || 'N/A'}<br>
            <strong>Email:</strong> ${email || 'N/A'}<br>
            <strong>Address:</strong> ${address}<br>
            <strong>Tests:</strong> ${displayTests}
        `.replace(/"/g, '&quot;').replace(/\n/g, ' ');

        if (compiledName.trim()) {
            paxListHtml += `
                <div class="p-1.5 mb-1 rounded hover-bg border-bottom border-secondary border-opacity-5 d-flex justify-content-between align-items-center"
                style="cursor: help;"
                data-bs-toggle="popover" 
                data-bs-trigger="hover focus" 
                data-bs-html="true"
                data-bs-content="${hoverDetails}"
                title="${compiledName}">
                <span class="text-truncate small text-main fw-semibold" style="max-width: 180px;">${compiledName}</span>
                <span class="badge bg-secondary bg-opacity-10 text-secondary x-small py-0.5" style="font-size:0.6rem;">${sex.toUpperCase()}</span>
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

    // Populate the scrollable directory preview panel
    const listContainer = document.getElementById('sum_pax_list_container');
    const listDiv = document.getElementById('sum_pax_list');
    if (paxListHtml && listContainer && listDiv) {
        listDiv.innerHTML = paxListHtml;
        listContainer.classList.remove('d-none');

        // Initialize Popovers
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

masterOrg.addEventListener('input', performGlobalSync);

// Shared master date & lead-time validator
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

    // Validate if there are actually any available slots
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
    }
});

function addRow(patient = null) {
    const minDate = document.getElementById('master_date').value || "{{ date('Y-m-d') }}";

    let firstName = '';
    let middleName = '';
    let lastName = '';
    let compiledName = '';

    if (patient) {
        if (patient.first_name) {
            firstName = patient.first_name;
            middleName = patient.middle_name || '';
            lastName = patient.last_name || '';
            compiledName = firstName + (middleName && middleName.toUpperCase() !== 'N/A' ? ' ' + middleName : '') + ' ' + lastName;
        } else if (patient.name) {
            compiledName = patient.name;
            const nameParts = patient.name.trim().split(' ');
            if (nameParts.length === 1) {
                firstName = nameParts[0];
            } else if (nameParts.length === 2) {
                firstName = nameParts[0];
                lastName = nameParts[1];
            } else {
                firstName = nameParts[0];
                middleName = nameParts.slice(1, nameParts.length - 1).join(' ');
                lastName = nameParts[nameParts.length - 1];
            }
        }
    }

    const email = patient ? (patient.email || '') : '';
    const phone = patient ? (patient.phone || '') : '';
    const sex = patient && patient.sex ? (patient.sex.toLowerCase() === 'female' ? 'Female' : 'Male') : 'Male';
    const bday = patient ? (patient.birthdate || '') : '';

    const street = patient ? (patient.street || '') : '';
    const province = patient ? (patient.province || '') : '';
    const city = patient ? (patient.city || '') : '';
    const barangay = patient ? (patient.barangay || '') : '';

    const html = `
        <tr id="r_${rowCount}" class="border-secondary border-opacity-10 align-top text-main">
            <td class="ps-4 py-3" style="width: 320px; min-width: 320px;">
                <div class="row g-1 mb-2">
                    <div class="col-4">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.55rem; letter-spacing: 0.5px;">First Name</label>
                        <input type="text" name="patients[${rowCount}][first_name]" id="p_first_${rowCount}" value="${firstName}" class="form-control form-control-sm uppercase" placeholder="First" oninput="updateRowCompiledName(${rowCount})" required>
                    </div>
                    <div class="col-4">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.55rem; letter-spacing: 0.5px;">Middle</label>
                        <input type="text" name="patients[${rowCount}][middle_name]" id="p_middle_${rowCount}" value="${middleName}" class="form-control form-control-sm uppercase" placeholder="Middle" oninput="updateRowCompiledName(${rowCount})">
                    </div>
                    <div class="col-4">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.55rem; letter-spacing: 0.5px;">Last Name</label>
                        <input type="text" name="patients[${rowCount}][last_name]" id="p_last_${rowCount}" value="${lastName}" class="form-control form-control-sm uppercase" placeholder="Last" oninput="updateRowCompiledName(${rowCount})" required>
                    </div>
                    <input type="hidden" name="patients[${rowCount}][name]" id="p_name_${rowCount}" value="${compiledName}">
                </div>
                <div class="mb-2">
                    <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Email</label>
                    <input type="email" name="patients[${rowCount}][email]" value="${email}" class="form-control form-control-sm" placeholder="name@email.com" required>
                </div>
                <div class="mb-2">
                    <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Contact Number</label>
                    <input type="text" name="patients[${rowCount}][phone]" value="${phone}" class="form-control form-control-sm" placeholder="09xxxxxxxxx" required>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Sex</label>
                        <select name="patients[${rowCount}][sex]" class="form-select form-select-sm p-sex" onchange="handleRowSexChange(${rowCount}, this.value)">
                            <option value="Male" ${sex === 'Male' ? 'selected' : ''}>MALE</option>
                            <option value="Female" ${sex === 'Female' ? 'selected' : ''}>FEMALE</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Birthdate</label>
                        <input type="date" name="patients[${rowCount}][birthdate]" value="${bday}" class="form-control form-control-sm" required max="{{ date('Y-m-d') }}">
                    </div>
                </div>
            </td>
            <td class="py-3 px-3" style="width: 300px; min-width: 300px;">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.55rem; letter-spacing: 0.5px;">Province</label>
                        <select name="patients[${rowCount}][province]" id="p_province_${rowCount}" class="form-select form-select-sm" onchange="fetchRowCities(${rowCount}, this.value)" required>
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.55rem; letter-spacing: 0.5px;">City</label>
                        <select name="patients[${rowCount}][city]" id="p_city_${rowCount}" class="form-select form-select-sm" onchange="fetchRowBarangays(${rowCount}, this.value)" disabled required>
                            <option value="">Select Province First</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.55rem; letter-spacing: 0.5px;">Barangay</label>
                        <select name="patients[${rowCount}][barangay]" id="p_brgy_${rowCount}" class="form-select form-select-sm" disabled required>
                            <option value="">Select City First</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.55rem; letter-spacing: 0.5px;">Street</label>
                        <input type="text" name="patients[${rowCount}][street]" id="p_street_${rowCount}" class="form-control form-control-sm uppercase" value="${street}" placeholder="Street / House No." required oninput="updateBulkSummary()">
                    </div>
                </div>
            </td>
            <td class="py-3 px-3" style="width: 180px; min-width: 180px;">
                <div id="display_tests_${rowCount}" class="text-main fw-bold mb-2 uppercase border border-secondary border-opacity-10 p-2 rounded small" style="min-height: 50px; font-size: 0.7rem; overflow-y: auto; max-height: 100px;">NO TESTS</div>
                <div id="hidden_inputs_${rowCount}"></div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn-custom btn-outline-accent flex-grow-1 py-1 fw-bold small" onclick="openServiceModal(${rowCount})">SELECT</button>
                    <div class="dropdown">
                        <button class="btn-custom btn-outline-accent border-secondary text-secondary py-1 px-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-copy"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark bg-black border-secondary">
                            <li><h6 class="dropdown-header text-accent small">COPY TO:</h6></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'all')">ALL PATIENTS</button></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'Male')">ALL MALES</button></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'Female')">ALL FEMALES</button></li>
                        </ul>
                    </div>
                </div>
            </td>
            <td class="py-3 px-3" style="width: 220px; min-width: 220px;">
                <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Schedule Date</label>
                <input type="date" name="patients[${rowCount}][appointment_date]" class="form-control form-control-sm row-date-input mb-3 shadow-none" value="${minDate}" min="${minDate}" onchange="updateRowSlots(this)">
                
                <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Time Slot</label>
                <select name="patients[${rowCount}][time_slot]" class="form-select form-select-sm border-secondary text-accent fw-bold t-select py-2 shadow-none" required onchange="refreshAllRowSlots()">
                    <option value="">Choose Date First</option>
                </select>
            </td>
            <td class="pe-4 py-3 text-center align-middle" style="width: 160px; min-width: 160px;">
                <div class="d-flex flex-column gap-2 align-items-center justify-content-center w-100">
                    <!-- Redesigned Duplicate Button -->
                    <button type="button" class="btn btn-sm btn-outline-accent d-flex align-items-center justify-content-center w-100 py-1.5 px-3" style="border-radius: 8px; font-size: 0.7rem; font-weight: 700; gap: 6px; letter-spacing: 0.5px; white-space: nowrap;" onclick="duplicateRow(${rowCount})">
                        <i class="bi bi-files"></i> DUPLICATE
                    </button>
                    <!-- Redesigned Delete Button -->
                    <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center w-100 py-1.5 px-3" style="border-radius: 8px; font-size: 0.7rem; font-weight: 700; gap: 6px; letter-spacing: 0.5px; white-space: nowrap;" onclick="removeSpreadsheetRow(${rowCount})">
                        <i class="bi bi-trash3"></i> DELETE
                    </button>
                </div>
            </td>
        </tr>
    `;

    document.getElementById('rowContainer').insertAdjacentHTML('beforeend', html);
    const newTr = document.getElementById(`r_${rowCount}`);
    updateRowSlots(newTr.querySelector('.row-date-input'));

    fetchRowProvinces(rowCount, province, city, barangay);
    performGlobalSync();
    rowCount++;
}

function removeSpreadsheetRow(idx) {
    document.getElementById(`r_${idx}`).remove();
    performGlobalSync();
}

function duplicateRow(idx) {
    const row = document.getElementById(`r_${idx}`);
    if (!row) return;

    const hiddenInputs = document.querySelectorAll(`#hidden_inputs_${idx} input`);
    const serviceIds = Array.from(hiddenInputs).map(input => input.value);

    const provSel = document.getElementById(`p_province_${idx}`);
    const citySel = document.getElementById(`p_city_${idx}`);
    const brgySel = document.getElementById(`p_brgy_${idx}`);

    const clonedPatient = {
        first_name: document.getElementById(`p_first_${idx}`).value,
        middle_name: document.getElementById(`p_middle_${idx}`).value,
        last_name: document.getElementById(`p_last_${idx}`).value,
        sex: row.querySelector('.p-sex').value,
        birthdate: row.querySelector('input[type="date"]:not(.row-date-input)').value,
        email: row.querySelector('input[type="email"]').value,
        phone: row.querySelector('input[name*="[phone]"]').value,
        street: document.getElementById(`p_street_${idx}`).value,
        province: provSel.value,
        city: citySel.value,
        barangay: brgySel.value,
        service_ids: serviceIds,
        appointment_date: row.querySelector('.row-date-input').value,
        time_slot: row.querySelector('.t-select').value,
        
        prov_text: provSel.options[provSel.selectedIndex]?.text,
        city_text: citySel.options[citySel.selectedIndex]?.text,
        brgy_text: brgySel.options[brgySel.selectedIndex]?.text
    };

    addRow(clonedPatient);
}

// Intercepts the final form submission securely on step 4 to list lacking requirements
function validateBulkForm(e) {
    let errors = [];

    // 1. Basic organization name & date validation
    const org = masterOrg.value.trim();
    const date = masterDate.value;
    if (!org) {
        errors.push("Step 2: Organization Name is required.");
    }
    if (!date) {
        errors.push("Step 2: Preferred Start Date is required.");
    }

    // 2. Validate patient rows
    const rows = document.querySelectorAll('#rowContainer tr');
    if (rows.length < 2) {
        errors.push("Spreadsheet: Bulk booking requires at least 2 patient records.");
    }

    rows.forEach((tr, index) => {
        const idx = tr.id.split('_')[1];
        const rowNum = index + 1;

        const fName = document.getElementById(`p_first_${idx}`)?.value.trim();
        const lName = document.getElementById(`p_last_${idx}`)?.value.trim();
        const email = tr.querySelector('input[type="email"]')?.value.trim();
        const phone = tr.querySelector('input[name*="[phone]"]')?.value.trim();
        const bday = tr.querySelector('input[name*="[birthdate]"]')?.value;

        if (!fName || !lName) {
            errors.push(`Row ${rowNum}: First Name and Last Name are required.`);
        }
        if (!email) {
            errors.push(`Row ${rowNum}: Email Address is required.`);
        }
        if (!phone) {
            errors.push(`Row ${rowNum}: Contact Number is required.`);
        }
        if (!bday) {
            errors.push(`Row ${rowNum}: Birthdate is required.`);
        }

        // Test selections check
        const testInputs = tr.querySelectorAll('input[type="hidden"][name*="[service_ids]"]');
        if (testInputs.length === 0) {
            errors.push(`Row ${rowNum}: At least one laboratory test must be selected.`);
        }

        // Address fields check
        const prov = document.getElementById(`p_province_${idx}`)?.value;
        const city = document.getElementById(`p_city_${idx}`)?.value;
        const brgy = document.getElementById(`p_brgy_${idx}`)?.value;
        const street = document.getElementById(`p_street_${idx}`)?.value.trim();

        if (!prov || !city || !brgy || !street) {
            errors.push(`Row ${rowNum}: Complete address selection is required.`);
        }

        // Schedule check
        const rowDate = tr.querySelector('.row-date-input')?.value;
        const tSelect = tr.querySelector('.t-select')?.value;

        if (!rowDate) {
            errors.push(`Row ${rowNum}: Schedule Date is required.`);
        }
        if (!tSelect) {
            errors.push(`Row ${rowNum}: Preferred Time Slot is required.`);
        }
    });

    // 3. Validate payment settlement
    const payCashless = document.getElementById('pay_cashless');
    if (payCashless && payCashless.checked) {
        const selectedProvider = document.querySelector('input[name="payment_provider_id"]:checked');
        if (!selectedProvider) {
            errors.push("Payment: Please select an E-Wallet provider (e.g., GCash, Maya).");
        }
        const receiptInput = document.getElementById('in_receipt');
        if (receiptInput && (!receiptInput.files || receiptInput.files.length === 0)) {
            errors.push("Payment: Please upload a copy of your transaction receipt to finalize.");
        }
    }

    // 4. Validate clinical agreements
    const agreeTerms = document.getElementById('agree_terms');
    if (agreeTerms && !agreeTerms.checked) {
        errors.push("Agreements: You must agree to the Clinical Privacy Policy to confirm.");
    }

    if (errors.length > 0) {
        e.preventDefault();

        // Build error list HTML
        let errorHtml = '<div class="text-start mb-3 small text-white-50">Please correct the following omissions to proceed:</div>';
        errorHtml += '<ul class="text-start small text-danger mb-0 ps-3" style="max-height: 250px; overflow-y: auto;">';
        errors.forEach(err => {
            errorHtml += `<li class="mb-1">${err}</li>`;
        });
        errorHtml += '</ul>';

        // Show the standard themed validation modal
        document.getElementById('wizardValidationTitle').innerText = "Requirements Lacking";
        document.getElementById('wizardValidationMsg').innerHTML = errorHtml;

        const modalEl = document.getElementById('wizardValidationModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
        return false;
    }

    // FIXED: Disable final submit button instantly to prevent duplicate batch generation locks
    const finalSubmitBtn = document.getElementById('final_submit_btn');
    if (finalSubmitBtn) {
        setTimeout(() => {
            finalSubmitBtn.disabled = true;
            finalSubmitBtn.innerHTML = 'SUBMITTING... <span class="spinner-border spinner-border-sm ms-2"></span>';
        }, 0);
    }

    // If validation succeeds, perform native select value compilation
    rows.forEach(tr => {
        const idx = tr.id.split('_')[1];
        const prov = document.getElementById(`p_province_${idx}`);
        const city = document.getElementById(`p_city_${idx}`);
        const brgy = document.getElementById(`p_brgy_${idx}`);

        if (prov && city && brgy) {
            const provName = prov.options[prov.selectedIndex]?.text || '';
            const cityName = city.options[city.selectedIndex]?.text || '';
            const brgyName = brgy.options[brgy.selectedIndex]?.text || '';

            if (provName && cityName && brgyName) {
                prov.options[prov.selectedIndex].value = provName;
                city.options[city.selectedIndex].value = cityName;
                brgy.options[brgy.selectedIndex].value = brgyName;
            }
        }
    });
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

    if (!fileInput.files[0]) return showAlert("Please select an Excel or CSV file first.");

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

            jsonData.slice(1).forEach(row => {
                if (row[0]) {
                    const birthdateFormatted = formatExcelDate(row[1]);
                    const phoneNormalized = normalizeExcelPhone(row[3]);

                    const rawName = row[0] ? String(row[0]).trim() : '';
                    const nameParts = rawName.split(' ');
                    let firstName = '', middleName = '', lastName = '';

                    if (nameParts.length === 1) {
                        firstName = nameParts[0];
                    } else if (nameParts.length === 2) {
                        firstName = nameParts[0];
                        lastName = nameParts[1];
                    } else if (nameParts.length >= 3) {
                        firstName = nameParts[0];
                        middleName = nameParts.slice(1, nameParts.length - 1).join(' ');
                        lastName = nameParts[nameParts.length - 1];
                    }

                    addRow({
                        first_name: firstName,
                        middle_name: middleName,
                        last_name: lastName,
                        birthdate: birthdateFormatted,
                        sex: row[2] ? String(row[2]).trim() : 'Male',
                        phone: phoneNormalized,
                        email: row[4] ? String(row[4]).trim() : '',
                        street: row[5] ? String(row[5]).trim() : '',
                        barangay: row[6] ? String(row[6]).trim() : '',
                        city: row[7] ? String(row[7]).trim() : '',
                        province: row[8] ? String(row[8]).trim() : ''
                    });
                }
            });
            switchTab('manual');
            showAlert(`Success! ${jsonData.length - 1} patients loaded. Please select tests and time slots.`);
        } else {
            showAlert("Excel sheet contains no records.");
        }
        btn.disabled = false;
        btn.innerHTML = 'LOAD DATA INTO MANUAL FORM <i class="bi bi-arrow-right-short ms-1"></i>';
    };
    reader.readAsArrayBuffer(file);
}

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

        // Correct lunch check supporting true boolean types smoothly
        let isLunch = ((config.has_lunch_break === true || config.has_lunch_break === 1 || parseInt(config.has_lunch_break) === 1) && tStr >= config.lunch_start && tStr < config.lunch_end);
        let dbCount = parseInt(cachedOccupancy[tStr] || 0);

        let onScreenCount = getOnScreenOccupiedCount(selectedDate, tStr, idx);
        const maxLimit = config ? parseInt(config.max_patients_per_slot) : 1;
        let isFull = (dbCount + onScreenCount) >= maxLimit;

        // Enforce dynamic local lead-time / past validation boundaries
        let isPast = false;
        if (selectedDate === todayLocal) {
            const leadTimeMs = (parseInt(config.lead_time_hours) || 0) * 3600 * 1000;
            const cutoffTime = now.getTime() + leadTimeMs;
            const slotDate = new Date(`${selectedDate} ${tStr}`);
            isPast = slotDate.getTime() < cutoffTime;
        }

        if (!isLunch && !isFull && !isPast) {
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

                    // Correct lunch check supporting true boolean types smoothly
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
    }
}

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

window.openServiceModal = function (idx) {
    activeRowIdx = idx;
    const row = document.getElementById(`r_${idx}`);

    const sexSelect = row ? row.querySelector('.p-sex') : null;
    const activeSex = sexSelect ? sexSelect.value : 'Male';

    const hiddenContainer = document.getElementById(`hidden_inputs_${idx}`);
    const selectedIds = hiddenContainer ? Array.from(hiddenContainer.querySelectorAll('input')).map(i => i.value) : [];

    document.querySelectorAll('.service-item').forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        const restriction = item.dataset.gender;

        if (checkbox) {
            if (selectedIds.includes(checkbox.value)) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }

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

        const targetSex = tr.querySelector('.p-sex').value;
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
}

window.fetchRowProvinces = async function (idx, savedProv, savedCity, savedBrgy) {
    const provSel = document.getElementById(`p_province_${idx}`);
    if (!provSel) return;

    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces/');
        const data = await res.json();
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });

        if (savedProv) {
            let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === savedProv.toUpperCase());
            if (provOpt) {
                provSel.value = provOpt.value;
                await fetchRowCities(idx, provOpt.value, savedCity, savedBrgy);
            }
        }
    } catch (e) {
        console.error("Row province fetch failed:", e);
    }
};

window.fetchRowCities = async function (idx, provCode, savedCity, savedBrgy) {
    const citySel = document.getElementById(`p_city_${idx}`);
    const brgySel = document.getElementById(`p_brgy_${idx}`);
    if (!citySel || !brgySel) return;

    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading...</option>';

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;

        if (savedCity) {
            let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === savedCity.toUpperCase());
            if (cityOpt) {
                citySel.value = cityOpt.value;
                await fetchRowBarangays(idx, cityOpt.value, savedBrgy);
            }
        }
    } catch (e) {
        console.error("Row city fetch failed:", e);
    }
};

window.fetchRowBarangays = async function (idx, cityCode, savedBrgy) {
    const brgySel = document.getElementById(`p_brgy_${idx}`);
    if (!brgySel) return;

    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading...</option>';

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;

        if (savedBrgy) {
            let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === savedBrgy.toUpperCase());
            if (brgyOpt) {
                brgySel.value = brgyOpt.value;
            }
        }
    } catch (e) {
        console.error("Row barangay fetch failed:", e);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('serviceSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toUpperCase();
            document.querySelectorAll('.service-item').forEach(item => {
                const name = item.dataset.name || '';
                if (name.includes(query)) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        });
    }
});

// Shared master date & lead-time validator
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

    // Validate if there are actually any available slots
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
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Auto-select appointment if 'id' parameter is passed in the URL (e.g. from Dashboard)
    const urlParams = new URLSearchParams(window.location.search);
    const selectId = urlParams.get('id');
    if (selectId) {
        const card = document.getElementById(`card-${selectId}`);
        if (card) {
            // Find parent tab and trigger change layout cleanly
            const tabPane = card.closest('.tab-pane');
            if (tabPane) {
                const tabId = tabPane.id;
                const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                if (tabButton) {
                    const tab = bootstrap.Tab.getInstance(tabButton) || new bootstrap.Tab(tabButton);
                    tab.show();
                }
            }
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            showAppointmentDetails(selectId);
        }
    }
});

window.onload = async () => {
    await fetchOccupancy();
    await validateMasterDate();
    addRow();
};
</script>
@endpush