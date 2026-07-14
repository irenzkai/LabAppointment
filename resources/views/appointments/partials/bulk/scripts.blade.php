@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
let rowCount = 0;
let activeRowIdx = null;

// Injected configurations directly as a safe JS object literal to bypass DOM escaping errors
let cachedConfigs = @json($configs);
let cachedOccupancy = [];

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
function proceedFromStep1() {
    const org = masterOrg.value.trim();
    const date = masterDate.value;
    if (!org || !date) {
        return showAlert("Organization name and Start Date are required.");
    }

    // Local date component extraction
    const parts = date.split('-');
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    const dayNum = d.getDay();
    const config = cachedConfigs[dayNum];

    if (!config || !config.is_open || config.is_open === '0' || config.is_open === 0 || config.is_open === false) {
        return showAlert("The selected start date falls on a closed day. Please select another date.");
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
    goToPage(4);
}

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
        const address = tr.querySelector('textarea[name*="[address]"]')?.value || '';
        
        // Get selected tests display label
        const displayTests = document.getElementById(`display_tests_${idx}`)?.innerText || 'No tests selected';

        // Compile HTML-safe popover contents
        let hoverDetails = `
            <strong>Sex:</strong> ${sex}<br>
            <strong>Birthdate:</strong> ${bday || 'N/A'}<br>
            <strong>Phone:</strong> ${phone || 'N/A'}<br>
            <strong>Email:</strong> ${email || 'N/A'}<br>
            <strong>Address:</strong> ${address || 'N/A'}<br>
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
            const checkbox = document.getElementById(`ch_${serviceId}`);
            if (checkbox) {
                const label = checkbox.closest('label');
                const priceSpan = label ? label.querySelector('.text-accent') : null;
                if (priceSpan) {
                    const priceText = priceSpan.innerText.replace(/[^\d.]/g, '');
                    const price = parseFloat(priceText) || 0;
                    totalSum += price;
                }
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

masterDate.addEventListener('change', async function() {
    const selectedDate = this.value;
    if (!selectedDate) return;

    // Prevent browser timezone conversion offset anomalies
    const parts = selectedDate.split('-');
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    const dayNum = d.getDay();
    const config = cachedConfigs[dayNum];

    const proceedBtn = document.getElementById('proceed_to_compilation_btn');
    const errorMsg = document.getElementById('date_validation_msg');

    if (!config || !config.is_open || config.is_open === '0' || config.is_open === 0 || config.is_open === false) {
        proceedBtn.disabled = true;
        errorMsg.classList.remove('d-none');
        return;
    } else {
        proceedBtn.disabled = false;
        errorMsg.classList.add('d-none');
    }

    await fetchOccupancy();

    document.querySelectorAll('.row-date-input').forEach(input => {
        input.value = selectedDate;
        input.min = selectedDate;
        updateRowSlots(input);
    });
    performGlobalSync();
});

function showAlert(msg) {
    document.getElementById('validationMsg').innerText = msg;
    new bootstrap.Modal(document.getElementById('validationModal')).show();
}

function updateRowCompiledName(idx) {
    const first = document.getElementById(`p_first_${idx}`).value.trim();
    const middle = document.getElementById(`p_middle_${idx}`).value.trim();
    const last = document.getElementById(`p_last_${idx}`).value.trim();

    const compiled = first + (middle && middle.toUpperCase() !== 'N/A' ? ' ' + middle : '') + ' ' + last;
    document.getElementById(`p_name_${idx}`).value = compiled.trim().toUpperCase();
    updateBulkSummary();
}

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
    const address = patient ? (patient.address || '') : '';

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
        <td class="py-3" style="width: 300px; min-width: 300px;">
            <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Home Address</label>
            <textarea name="patients[${rowCount}][address]" class="form-control form-control-sm" rows="8" required style="resize:none;" placeholder="Enter complete address...">${address}</textarea>
        </td>
        <td class="py-3 px-3" style="width: 180px; min-width: 180px;">
            <label class="text-main mb-1 uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Tests</label>
            <div id="display_tests_${rowCount}" class="text-main fw-bold mb-2 uppercase border border-secondary border-opacity-10 p-2 rounded small" style="min-height: 50px; font-size: 0.7rem; overflow-y: auto; max-height: 100px;">
                NO TESTS
            </div>
            <div id="hidden_inputs_${rowCount}"></div>
            <div class="d-flex gap-1">
                <button type="button" class="btn-custom btn-outline-accent flex-grow-1 py-1 fw-bold small" onclick="openServiceModal(${rowCount})">SELECT</button>
                <div class="dropdown">
                    <button class="btn-custom btn-outline-accent border-secondary text-secondary py-1 px-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-copy"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark bg-black border-secondary">
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
            <select name="patients[${rowCount}][time_slot]" class="form-select form-select-sm border-secondary text-accent fw-bold t-select py-2 shadow-none" required>
                <option value="">Choose Date First</option>
            </select>
        </td>
        <td class="pe-4 py-3 text-center align-middle" style="width: 120px; min-width: 120px;">
            <button type="button" class="btn btn-danger-custom d-flex flex-column align-items-center justify-content-center py-2 fw-bold shadow-sm" style="border-radius:10px; width: 100%;" onclick="removeSpreadsheetRow(${rowCount})">
                <i class="bi bi-trash3-fill mb-1" style="font-size: 1.2rem;"></i>
                <span style="font-size: 0.6rem; letter-spacing: 1px;">DELETE ROW</span>
            </button>
        </td>
    </tr>`;

    document.getElementById('rowContainer').insertAdjacentHTML('beforeend', html);
    const newTr = document.getElementById(`r_${rowCount}`);
    updateRowSlots(newTr.querySelector('.row-date-input'));
    performGlobalSync();
    rowCount++;
}

function removeSpreadsheetRow(idx) {
    document.getElementById(`r_${idx}`).remove();
    performGlobalSync();
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

    if(!fileInput.files[0]) return showAlert("Please select an Excel or CSV file first.");

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>IMPORTING...';

    const file = fileInput.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array', cellDates: true});
        const firstSheet = workbook.SheetNames[0];
        const jsonData = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], {header: 1});

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
                        address: row[5] ? String(row[5]).trim() : ''
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
 
        // FIXED: Map fully booked slots directly to ensure they are marked correctly in manual-pane slots generator
        cachedOccupancy = (data.full_slots || []).map(o => ({
            appointment_date: mDate,
            time_slot: o,
            patient_count: parseInt(data.config.max_patients_per_slot)
        }));
    } catch (e) {
        console.error("Occupancy Fetch Failed", e);
        cachedOccupancy = [];
    }
}

function updateRowSlots(input) {
    const td = input.closest('td');
    const select = td.querySelector('.t-select');
    const selectedDate = input.value;

    if (!selectedDate || !cachedConfigs) {
        select.innerHTML = '<option value="">Pick Date First</option>';
        return;
    }

    const dayNum = new Date(selectedDate).getDay();
    const config = cachedConfigs[dayNum];

    if (!config || parseInt(config.is_open) === 0) {
        select.innerHTML = '<option value="">CLOSED</option>';
        return;
    }

    let html = '<option value="">Choose Time</option>';
    let start = new Date(`2000-01-01 ${config.opening_time}`);
    let end = new Date(`2000-01-01 ${config.closing_time}`);
    let availableCount = 0;

    while (start < end) {
        let hours = start.getHours().toString().padStart(2, '0');
        let minutes = start.getMinutes().toString().padStart(2, '0');
        let tStr = `${hours}:${minutes}:00`;

        let isLunch = (parseInt(config.has_lunch_break) === 1 && tStr >= config.lunch_start && tStr < config.lunch_end);
        let dbMatch = (cachedOccupancy || []).find(o => o.appointment_date === selectedDate && o.time_slot === tStr);
        let isFull = dbMatch ? parseInt(dbMatch.patient_count) >= parseInt(config.max_patients_per_slot) : false;

        if (!isLunch && !isFull) {
            let disp = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            html += `<option value="${tStr}">${disp}</option>`;
            availableCount++;
        }
        start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
    }

    select.innerHTML = (availableCount > 0) ? html : '<option value="">FULLY BOOKED</option>';
    select.disabled = (availableCount === 0);
}

async function runSmartScheduler() {
    const mDateInput = document.getElementById('master_date').value;
    if (!mDateInput) return showAlert("Please select a Preferred Start Date first.");

    const btn = document.getElementById('smartSchedBtn');
    btn.disabled = true;
    btn.innerHTML = 'SCHEDULING...';

    try {
        await fetchOccupancy();
        let localTracker = {};
        let currentPtrDate = new Date(mDateInput);
        const rows = document.querySelectorAll('#rowContainer tr');

        for (let tr of rows) {
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

                let startPtr = new Date(`${dStr} ${config.opening_time}`);
                let endPtr = new Date(`${dStr} ${config.closing_time}`);

                while (startPtr < endPtr) {
                    let hours = startPtr.getHours().toString().padStart(2, '0');
                    let minutes = startPtr.getMinutes().toString().padStart(2, '0');
                    let tStr = `${hours}:${minutes}:00`;

                    let isLunch = (parseInt(config.has_lunch_break) === 1 && tStr >= config.lunch_start && tStr < config.lunch_end);

                    if (!isLunch) {
                        let dbMatch = cachedOccupancy.find(o => o.appointment_date === dStr && o.time_slot === tStr);
                        let dbCount = dbMatch ? parseInt(dbMatch.patient_count) : 0;
                        let localCount = localTracker[`${dStr}_${tStr}`] || 0;

                        if (dbCount + localCount < parseInt(config.max_patients_per_slot)) {
                            const dInput = tr.querySelector('.row-date-input');
                            const tSelect = tr.querySelector('.t-select');

                            dInput.value = dStr;
                            updateRowSlots(dInput);
                            tSelect.value = tStr;

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

function openServiceModal(idx) { 
    activeRowIdx = idx; 
    const row = document.getElementById(`r_${idx}`);
    const activeSex = row.querySelector('.p-sex').value;
    const selectedIds = Array.from(document.querySelectorAll(`#hidden_inputs_${idx} input`)).map(i => i.value);

    document.querySelectorAll('.service-item').forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        const restriction = item.dataset.gender;

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
    });

    const searchInput = document.getElementById('serviceSearch');
    if (searchInput) searchInput.value = '';

    new bootstrap.Modal(document.getElementById('serviceModal')).show(); 
}

function applyServices() {
    const sel = Array.from(document.querySelectorAll('.service-item input:checked'));
    document.getElementById(`display_tests_${activeRowIdx}`).innerText = sel.map(s => s.dataset.label).join(', ') || 'NO TESTS'; 

    let h = ''; 
    sel.forEach(s => h += `<input type="hidden" name="patients[${activeRowIdx}][service_ids][]" value="${s.value}">`);
    document.getElementById(`hidden_inputs_${activeRowIdx}`).innerHTML = h; 

    bootstrap.Modal.getInstance(document.getElementById('serviceModal')).hide();
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
    console.log(`Copied tests from row ${sourceIdx} to ${count} rows.`);
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('serviceSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
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

document.getElementById('master_date').addEventListener('change', async function() {
    const selectedDate = this.value;
    if(!selectedDate) return;
    await fetchOccupancy();

    document.querySelectorAll('.row-date-input').forEach(input => {
        input.value = selectedDate;
        input.min = selectedDate;
        updateRowSlots(input);
    });
    performGlobalSync();
});

// FIXED: Handles bulk form level gate checks for Cashless configurations on submit
document.getElementById('bulkForm').addEventListener('submit', function(e) {
    const org = document.getElementById('master_org').value;
    const date = document.getElementById('master_date').value;
    const rows = document.querySelectorAll('#rowContainer tr');
    let missingTests = false;

    if(!org || !date) {
        e.preventDefault();
        showAlert("Organization name and Start Date are required.");
        return;
    }

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
        e.preventDefault();
        showAlert("Every patient in the list must have at least one test selected.");
        return;
    }

    const payCashless = document.getElementById('pay_cashless');
    if (payCashless && payCashless.checked) {
        const selectedProvider = document.querySelector('input[name="payment_provider_id"]:checked');
        if (!selectedProvider) {
            e.preventDefault();
            showAlert("Please select an E-Wallet provider (e.g. GCash, Maya) to scan the payment QR code before submitting.");
            return;
        }
    }
});

window.onload = async () => {
    await fetchOccupancy();
    addRow(); 
};
</script>
@endpush