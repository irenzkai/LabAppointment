<!-- resources/views/appointments/partials/bulk/scripts.blade.php -->
@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
// =========================================================================
// SCRIPT PARTIAL 2: SCHEDULING, SERVICES MODAL, EXCEL & PAYMENT WORKFLOW
// =========================================================================

// Global Configuration Hydration
window.cachedConfigs = @json($configs);
window.cachedOccupancy = {};
window.cachedOccupancyByDate = {};
window.cachedDateConfigs = {};
window.servicesMap = @json($services->keyBy('id'));
window.packagesMap = @json($packages->keyBy('id'));
window.isRecognizedOrg = false;
let isRestoringDraft = false;
const masterDate = document.getElementById('master_date');
const DRAFT_STORAGE_KEY = 'medscreen_bulk_appointment_draft';

// Payment State Tracking
let bulkLastMethod = 'Cash';
let bulkLastProviderId = null;
window.bulkReceiptLocalData = null;

// =========================================================================
// 1. RECOGNIZED ORGANIZATION MODE TOGGLER & INPUT CONTROLLERS
// =========================================================================
function handleUseOrgToggle(checkbox) {
    const isChecked = checkbox ? checkbox.checked : false;
    window.isRecognizedOrg = isChecked;

    const hiddenUseOrg = document.getElementById('hidden_use_recognized_org');
    if (hiddenUseOrg) hiddenUseOrg.value = isChecked ? '1' : '0';

    const orgTextInput = document.getElementById('master_org');
    const orgSelect = document.getElementById('master_org_select');
    const rosterContainer = document.getElementById('roster_tools_container');

    if (isChecked) {
        if (orgTextInput) {
            orgTextInput.classList.add('d-none');
            orgTextInput.value = '';
        }
        if (orgSelect) {
            orgSelect.classList.remove('d-none');
            handleOrgDropdownChange(orgSelect);
        }
        if (rosterContainer) {
            rosterContainer.classList.remove('d-none');
            rosterContainer.classList.add('d-flex');
        }
    } else {
        if (orgSelect) {
            orgSelect.classList.add('d-none');
            orgSelect.value = '';
        }
        if (orgTextInput) {
            orgTextInput.classList.remove('d-none');
        }
        const hiddenOrgId = document.getElementById('hidden_org_id');
        if (hiddenOrgId) hiddenOrgId.value = '';

        if (rosterContainer) {
            rosterContainer.classList.add('d-none');
            rosterContainer.classList.remove('d-flex');
        }
    }

    filterModalPackagesForOrg();
    performGlobalSync();
    saveBulkDraft();
}
window.handleUseOrgToggle = handleUseOrgToggle;

function handleOrgDropdownChange(select) {
    if (!select) return;
    const selectedOpt = select.options[select.selectedIndex];
    const orgId = select.value;
    const orgName = selectedOpt ? (selectedOpt.dataset.name || selectedOpt.text) : '';

    const hiddenOrg = document.getElementById('hidden_org');
    const hiddenOrgId = document.getElementById('hidden_org_id');
    if (hiddenOrg) hiddenOrg.value = orgName;
    if (hiddenOrgId) hiddenOrgId.value = orgId;

    const sumOrg = document.getElementById('sum_org');
    if (sumOrg) sumOrg.innerText = orgName || '---';

    // Fetch roster groups for this specific org
    if (orgId) {
        fetchRosterGroupsForOrg(orgId);
    }

    filterModalPackagesForOrg();
    performGlobalSync();
    saveBulkDraft();
}
window.handleOrgDropdownChange = handleOrgDropdownChange;

function handleMasterOrgTextInput(input) {
    const hiddenOrg = document.getElementById('hidden_org');
    const hiddenOrgId = document.getElementById('hidden_org_id');
    if (hiddenOrg) hiddenOrg.value = input.value.trim();
    if (hiddenOrgId) hiddenOrgId.value = '';

    const sumOrg = document.getElementById('sum_org');
    if (sumOrg) sumOrg.innerText = input.value.trim() || '---';

    performGlobalSync();
    saveBulkDraft();
}
window.handleMasterOrgTextInput = handleMasterOrgTextInput;

async function fetchRosterGroupsForOrg(orgId) {
    const rosterSelect = document.getElementById('saved_roster_select');
    if (!rosterSelect) return;

    rosterSelect.innerHTML = '<option value="" disabled selected>-- Loading Rosters... --</option>';

    try {
        const res = await fetch(`/bulk/roster/members?organization_id=${encodeURIComponent(orgId)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        rosterSelect.innerHTML = '<option value="" disabled selected>-- Load Saved Roster --</option>';
        if (data.success && Array.isArray(data.members)) {
            const groups = [...new Set(data.members.map(m => m.group_name).filter(Boolean))];
            groups.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g;
                opt.textContent = g;
                rosterSelect.appendChild(opt);
            });
        }
    } catch(e) {
        rosterSelect.innerHTML = '<option value="" disabled selected>-- Load Saved Roster --</option>';
    }
}

function filterModalPackagesForOrg() {
    const isOrgMode = document.getElementById('toggle_use_org')?.checked || false;
    const orgId = document.getElementById('hidden_org_id')?.value || '';

    document.querySelectorAll('.pkg-item-card').forEach(card => {
        const pkgOrgId = card.dataset.orgId;
        if (pkgOrgId) {
            // Org-exclusive package: Show ONLY if in Recognized Org mode and matching org is picked
            if (isOrgMode && orgId && String(pkgOrgId) === String(orgId)) {
                card.classList.remove('d-none');
            } else {
                card.classList.add('d-none');
                const chk = card.querySelector('.pkg-chk-item');
                if (chk) chk.checked = false;
            }
        } else {
            // Global standard package: Always accessible
            card.classList.remove('d-none');
        }
    });
}

// =========================================================================
// 2. NAVIGATION CONTROLLER (3 STEPS) & VALIDATION
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
window.goToPage = goToPage;

async function proceedFromStep1() {
    const isOrgMode = document.getElementById('toggle_use_org')?.checked || false;
    const orgName = document.getElementById('hidden_org')?.value.trim() || '';
    const date = masterDate ? masterDate.value : '';

    if (!orgName) {
        if (isOrgMode) {
            return showAlert("Please select a recognized organization from the dropdown.");
        } else {
            return showAlert("Organization / Company Name is required.");
        }
    }

    if (!date) {
        return showAlert("Preferred Start Date is required.");
    }

    const isValid = await validateMasterDate();
    if (!isValid) {
        const errorMsg = document.getElementById('date_validation_msg')?.innerText || "Selected date is closed.";
        return showAlert(errorMsg);
    }

    performGlobalSync();
    goToPage(3);
}
window.proceedFromStep1 = proceedFromStep1;

function validateStep2() {
    const rows = document.querySelectorAll('#rowContainer tr');
    if (rows.length < 2) {
        return showAlert("Bulk booking requires at least 2 patient records. For single appointments, please use the standard booking wizard.");
    }

    let invalidRows = [];
    let missingTests = [];
    let missingSlots = [];
    let conflictRows = [];
    let seenBatchEmails = {};
    let seenBatchPhones = {};
    let duplicateErrors = [];

    rows.forEach((tr, index) => {
        const idx = tr.id.split('_')[1];
        const rowNum = index + 1;
        const p = getRowPatientData(idx);
        const rowErrors = getPatientRowValidationErrors(p);
        let hasError = false;

        if (rowErrors.length > 0) {
            invalidRows.push(`Row ${rowNum} (${p.name || 'Unnamed'}): ${rowErrors.join(', ')}`);
            hasError = true;
        }

        // Check tests & packages
        const svcInputs = Array.from(tr.querySelectorAll(`input[type="hidden"][name="patients[${idx}][service_ids][]"]`)).map(i => i.value);
        const pkgInputs = Array.from(tr.querySelectorAll(`input[type="hidden"][name="patients[${idx}][package_ids][]"]`)).map(i => i.value);

        if (svcInputs.length === 0 && pkgInputs.length === 0) {
            missingTests.push(`Row ${rowNum} (${p.name || 'Unnamed'})`);
            hasError = true;
        } else {
            // Anti-Double Selection & Conflict Check across row's tests
            const coveredServices = new Set();
            let hasOverlappingPackages = false;

            pkgInputs.forEach(pkgId => {
                const pkg = window.packagesMap[pkgId];
                if (pkg && pkg.services) {
                    pkg.services.forEach(svc => {
                        if (coveredServices.has(svc.id.toString())) {
                            hasOverlappingPackages = true;
                        }
                        coveredServices.add(svc.id.toString());
                    });
                }
            });

            if (hasOverlappingPackages) {
                conflictRows.push(`Row ${rowNum} (${p.name || 'Unnamed'}): Contains multiple packages that share identical tests.`);
                hasError = true;
            }

            const hasOverlappingIndiv = svcInputs.some(sId => coveredServices.has(sId.toString()));
            if (hasOverlappingIndiv) {
                conflictRows.push(`Row ${rowNum} (${p.name || 'Unnamed'}): Contains individual tests that are already covered in selected packages.`);
                hasError = true;
            }
        }

        // Schedule check
        const rowDate = tr.querySelector('.row-date-input')?.value;
        const tSelect = tr.querySelector('.t-select')?.value;
        if (!rowDate || !tSelect) {
            missingSlots.push(`Row ${rowNum} (${p.name || 'Unnamed'})`);
            hasError = true;
        }

        // Intra-batch duplicate checks
        if (p.email && p.email.trim()) {
            const cleanEmail = p.email.toLowerCase().trim();
            if (seenBatchEmails[cleanEmail]) {
                duplicateErrors.push(`Duplicate email '${cleanEmail}' on Row ${rowNum} (already assigned to Row ${seenBatchEmails[cleanEmail]})`);
                hasError = true;
            } else {
                seenBatchEmails[cleanEmail] = rowNum;
            }
        }

        if (p.phone && p.phone.trim()) {
            const cleanPhone = p.phone.replace(/[^0-9]/g, '');
            if (seenBatchPhones[cleanPhone]) {
                duplicateErrors.push(`Duplicate phone '${cleanPhone}' on Row ${rowNum} (already assigned to Row ${seenBatchPhones[cleanPhone]})`);
                hasError = true;
            } else {
                seenBatchPhones[cleanPhone] = rowNum;
            }
        }

        tr.style.borderColor = hasError ? "#ff4d4d" : "var(--border-color)";
    });

    if (duplicateErrors.length > 0) {
        return showAlert("Duplicate contact credentials detected within this batch list:<br><br>" + duplicateErrors.join('<br>'));
    }

    if (conflictRows.length > 0) {
        return showAlert("Conflicting tests or packages detected:<br><br>" + conflictRows.join('<br>'));
    }

    if (invalidRows.length > 0) {
        return showAlert("One or more patient records have incomplete details. Click 'Fix Details' on highlighted rows:<br><br>" + invalidRows.join('<br>'));
    }

    if (missingTests.length > 0) {
        return showAlert("Every patient must have at least one test or package selected. Missing on:<br><br>" + missingTests.join('<br>'));
    }

    if (missingSlots.length > 0) {
        return showAlert("Please assign schedules for all patients (or click <strong>SMART AUTO-TIME</strong>). Missing on:<br><br>" + missingSlots.join('<br>'));
    }

    goToPage(4);
}
window.validateStep2 = validateStep2;

// =========================================================================
// 3. REAL-TIME SUMMARY & SYNC
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

        const displayTests = document.getElementById(`display_tests_${idx}`)?.innerText || 'No selections';

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
                    <span class="badge bg-secondary text-white x-small py-0.5" style="font-size:0.6rem;">${p.sex.toUpperCase()}</span>
                </div>
            `;
        }

        // Calculate Packages total
        const selectedPackages = tr.querySelectorAll(`input[name="patients[${idx}][package_ids][]"]`);
        selectedPackages.forEach(input => {
            const pkg = window.packagesMap[input.value];
            if (pkg) totalSum += parseFloat(pkg.price) || 0;
        });

        // Calculate Services total
        const selectedServices = tr.querySelectorAll(`input[name="patients[${idx}][service_ids][]"]`);
        selectedServices.forEach(input => {
            const svc = window.servicesMap[input.value];
            if (svc) totalSum += parseFloat(svc.price) || 0;
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

    const countEl = document.getElementById('sum_pax_count');
    if (countEl) countEl.innerText = `${paxCount} PATIENT${paxCount === 1 ? '' : 'S'} ADDED`;

    const totalEl = document.getElementById('sum_total');
    if (totalEl) totalEl.innerText = totalSum.toLocaleString(undefined, { minimumFractionDigits: 2 });
}
window.updateBulkSummary = updateBulkSummary;

function performGlobalSync() {
    const hiddenOrg = document.getElementById('hidden_org');
    const hiddenDate = document.getElementById('hidden_date');
    const sumOrg = document.getElementById('sum_org');
    const sumSched = document.getElementById('sum_schedule');
    const sumDate = document.getElementById('sum_date');

    const orgVal = hiddenOrg ? hiddenOrg.value : '';
    if (sumOrg) sumOrg.innerText = orgVal || '---';

    if (masterDate && masterDate.value) {
        if (hiddenDate) hiddenDate.value = masterDate.value;
        if (sumSched) sumSched.classList.remove('d-none');
        if (sumDate) sumDate.innerText = masterDate.value;
    } else {
        if (sumSched) sumSched.classList.add('d-none');
    }

    updateBulkSummary();
}
window.performGlobalSync = performGlobalSync;

// =========================================================================
// 4. OCCUPANCY & UNCAPPED CASCADING TIME ALLOCATOR
// =========================================================================
function parseLocalDate(dateStr) {
    if (!dateStr) return null;
    const parts = String(dateStr).split('-').map(Number);
    if (parts.length !== 3 || parts.some(isNaN)) return null;
    return new Date(parts[0], parts[1] - 1, parts[2]);
}

function formatLocalDate(dateObj) {
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function buildLocalDateTime(dateStr, timeStr) {
    const d = parseLocalDate(dateStr);
    if (!d) return new Date(NaN);
    const tParts = String(timeStr || '00:00:00').split(':').map(Number);
    d.setHours(tParts[0] || 0, tParts[1] || 0, tParts[2] || 0, 0);
    return d;
}

function isConfigClosed(config) {
    return !config || !config.is_open || config.is_open === '0' || config.is_open === 0 || config.is_open === false;
}

async function fetchDateSlotInfo(dateStr, forceRefresh = false) {
    if (!dateStr) {
        return { occupied_slots: {}, config: null, is_closed: true };
    }

    if (!forceRefresh && window.cachedOccupancyByDate[dateStr] !== undefined && window.cachedDateConfigs[dateStr] !== undefined) {
        const cachedCfg = window.cachedDateConfigs[dateStr];
        return {
            occupied_slots: window.cachedOccupancyByDate[dateStr],
            config: cachedCfg,
            is_closed: isConfigClosed(cachedCfg)
        };
    }

    try {
        const res = await fetch(`/api/check-slots?date=${encodeURIComponent(dateStr)}`);
        const data = await res.json();
        const occ = data.occupied_slots || {};
        const cfg = data.config || null;
        window.cachedOccupancyByDate[dateStr] = occ;
        window.cachedDateConfigs[dateStr] = cfg;

        return {
            occupied_slots: occ,
            config: cfg,
            is_closed: Boolean(data.is_closed) || isConfigClosed(cfg)
        };
    } catch (e) {
        console.error("Occupancy Fetch Failed", e);
        const d = parseLocalDate(dateStr);
        const fallbackCfg = d && window.cachedConfigs ? window.cachedConfigs[d.getDay()] : null;
        return {
            occupied_slots: {},
            config: fallbackCfg,
            is_closed: isConfigClosed(fallbackCfg)
        };
    }
}
window.fetchDateSlotInfo = fetchDateSlotInfo;

async function fetchOccupancy(dateOverride = null) {
    const mDateInput = document.getElementById('master_date')?.value;
    const todayLocal = formatLocalDate(new Date());
    const mDate = dateOverride || mDateInput || todayLocal;

    window.cachedOccupancyByDate = {};
    window.cachedDateConfigs = {};

    const info = await fetchDateSlotInfo(mDate, true);
    window.cachedOccupancy = info.occupied_slots || {};
    return info;
}
window.fetchOccupancy = fetchOccupancy;

async function validateMasterDate() {
    if (!masterDate) return false;
    const selectedDate = masterDate.value;
    if (!selectedDate) return false;

    const info = await fetchDateSlotInfo(selectedDate);
    const localDate = parseLocalDate(selectedDate);
    const config = info.config || (localDate && window.cachedConfigs ? window.cachedConfigs[localDate.getDay()] : null);

    const proceedBtn = document.getElementById('proceed_to_compilation_btn');
    const errorMsg = document.getElementById('date_validation_msg');

    if (info.is_closed || isConfigClosed(config)) {
        if (errorMsg) {
            errorMsg.innerText = "Clinic is closed on this day. Please select another date.";
            errorMsg.classList.remove('d-none');
        }
        if (proceedBtn) proceedBtn.classList.add('opacity-75');
        return false;
    }

    if (errorMsg) errorMsg.classList.add('d-none');
    if (proceedBtn) proceedBtn.classList.remove('opacity-75');
    return true;
}
window.validateMasterDate = validateMasterDate;

if (masterDate) {
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
}

function renderRowSlotOptions(select, selectedDate, savedSlot = '') {
    if (!select) return;

    if (!selectedDate || !window.cachedConfigs) {
        select.innerHTML = '<option value="">Pick Date First</option>';
        return;
    }

    const localDate = parseLocalDate(selectedDate);
    if (!localDate) {
        select.innerHTML = '<option value="">Pick Date First</option>';
        return;
    }

    const dayNum = localDate.getDay();
    const config = (window.cachedDateConfigs && window.cachedDateConfigs[selectedDate] !== undefined)
        ? window.cachedDateConfigs[selectedDate]
        : window.cachedConfigs[dayNum];

    if (isConfigClosed(config)) {
        select.innerHTML = '<option value="">CLOSED</option>';
        return;
    }

    let html = '<option value="">Choose Time</option>';
    let start = buildLocalDateTime('2000-01-01', config.opening_time || '08:00:00');
    let end = buildLocalDateTime('2000-01-01', config.closing_time || '17:00:00');
    let slotDuration = parseInt(config.slot_duration) || 60;
    let availableCount = 0;

    const now = new Date();
    const todayLocal = formatLocalDate(now);
    const hasLunch = (config.has_lunch_break === true || config.has_lunch_break === 1 || parseInt(config.has_lunch_break) === 1);
    const leadHours = (config.lead_time_hours !== undefined && config.lead_time_hours !== null) ? parseInt(config.lead_time_hours) : 2;

    while (start < end) {
        let hours = start.getHours().toString().padStart(2, '0');
        let minutes = start.getMinutes().toString().padStart(2, '0');
        let tStr = `${hours}:${minutes}:00`;

        let isLunch = (hasLunch && config.lunch_start && config.lunch_end && tStr >= config.lunch_start && tStr < config.lunch_end);
        let isPast = false;

        if (selectedDate === todayLocal) {
            const leadTimeMs = leadHours * 3600 * 1000;
            const cutoffTime = now.getTime() + leadTimeMs;
            const slotDate = buildLocalDateTime(selectedDate, tStr);
            isPast = slotDate.getTime() < cutoffTime;
        }

        if (!isLunch && !isPast) {
            let disp = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            let selectedAttr = (savedSlot === tStr) ? 'selected' : '';
            html += `<option value="${tStr}" ${selectedAttr}>${disp}</option>`;
            availableCount++;
        }

        start.setMinutes(start.getMinutes() + slotDuration);
    }

    select.innerHTML = (availableCount > 0) ? html : '<option value="">NO SLOTS</option>';
}

function updateRowSlots(input, savedSlot = '') {
    const td = input.closest('td');
    if (!td) return;
    const select = td.querySelector('.t-select');
    const selectedDate = input.value;
    if (!select) return;

    renderRowSlotOptions(select, selectedDate, savedSlot);

    if (selectedDate && (!window.cachedDateConfigs || window.cachedDateConfigs[selectedDate] === undefined)) {
        fetchDateSlotInfo(selectedDate).then(() => {
            if (input.value === selectedDate) {
                const currentSelected = savedSlot || select.value || '';
                renderRowSlotOptions(select, selectedDate, currentSelected);
            }
        });
    }
}
window.updateRowSlots = updateRowSlots;

function refreshAllRowSlots() {
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
}
window.refreshAllRowSlots = refreshAllRowSlots;

// Find next available slot starting from (startDateStr, startTimeStr),
// cascading past full slots, Sunday/CLOSED days, and NO SLOTS days.
async function findNextAvailableSlot(startDateStr, startTimeStr = '') {
    const now = new Date();
    const todayLocal = formatLocalDate(now);
    const mDateVal = document.getElementById('master_date')?.value || todayLocal;
    const minAllowedDate = mDateVal > todayLocal ? mDateVal : todayLocal;

    let effectiveStartDate = startDateStr || minAllowedDate;
    let effectiveStartTime = startTimeStr || '';

    if (effectiveStartDate < minAllowedDate) {
        effectiveStartDate = minAllowedDate;
        effectiveStartTime = '';
    }

    // Count slots already allocated across current spreadsheet rows
    const tableCounts = {};
    document.querySelectorAll('#rowContainer tr').forEach(tr => {
        const rDate = tr.querySelector('.row-date-input')?.value;
        const rSlot = tr.querySelector('.t-select')?.value;
        if (rDate && rSlot) {
            const key = `${rDate}_${rSlot}`;
            tableCounts[key] = (tableCounts[key] || 0) + 1;
        }
    });

    let currentPtrDate = parseLocalDate(effectiveStartDate);
    if (!currentPtrDate) return null;

    let minStartTime = effectiveStartTime;
    let daySafety = 0;

    while (daySafety < 90) {
        const dStr = formatLocalDate(currentPtrDate);
        const info = await fetchDateSlotInfo(dStr);
        const config = info.config || (window.cachedConfigs ? window.cachedConfigs[currentPtrDate.getDay()] : null);

        if (info.is_closed || isConfigClosed(config)) {
            currentPtrDate.setDate(currentPtrDate.getDate() + 1);
            minStartTime = '';
            daySafety++;
            continue;
        }

        const openingTime = config.opening_time || '08:00:00';
        const closingTime = config.closing_time || '17:00:00';
        const slotDuration = parseInt(config.slot_duration) || 60;
        const maxPatients = parseInt(config.max_patients_per_slot) || 2;
        const hasLunch = (config.has_lunch_break === true || config.has_lunch_break === 1 || parseInt(config.has_lunch_break) === 1);
        const leadHours = (config.lead_time_hours !== undefined && config.lead_time_hours !== null) ? parseInt(config.lead_time_hours) : 2;

        let startPtr = buildLocalDateTime(dStr, openingTime);
        let endPtr = buildLocalDateTime(dStr, closingTime);

        while (startPtr < endPtr) {
            const hours = startPtr.getHours().toString().padStart(2, '0');
            const minutes = startPtr.getMinutes().toString().padStart(2, '0');
            const tStr = `${hours}:${minutes}:00`;

            if (!minStartTime || tStr >= minStartTime) {
                const isLunch = (hasLunch && config.lunch_start && config.lunch_end && tStr >= config.lunch_start && tStr < config.lunch_end);
                let isPast = false;

                if (dStr === todayLocal) {
                    const leadTimeMs = leadHours * 3600 * 1000;
                    const cutoffTime = now.getTime() + leadTimeMs;
                    const slotDate = buildLocalDateTime(dStr, tStr);
                    isPast = slotDate.getTime() < cutoffTime;
                }

                if (!isLunch && !isPast) {
                    const dbCount = parseInt((info.occupied_slots && info.occupied_slots[tStr]) || 0);
                    const localCount = tableCounts[`${dStr}_${tStr}`] || 0;

                    if (dbCount + localCount < maxPatients) {
                        return {
                            date: dStr,
                            time_slot: tStr
                        };
                    }
                }
            }

            startPtr.setMinutes(startPtr.getMinutes() + slotDuration);
        }

        // Current day is CLOSED or has NO SLOTS left -> cascade to next day
        currentPtrDate.setDate(currentPtrDate.getDate() + 1);
        minStartTime = '';
        daySafety++;
    }

    return null;
}
window.findNextAvailableSlot = findNextAvailableSlot;

// Uncapped Cascading Smart Auto-Time Allocation
async function runSmartScheduler() {
    const mDateInput = document.getElementById('master_date')?.value;
    if (!mDateInput) return showAlert("Please select a Preferred Start Date first.");

    const btn = document.getElementById('smartSchedBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5"></span> SCHEDULING...';
    }

    try {
        await fetchOccupancy();
        let localTracker = {};
        let currentPtrDate = parseLocalDate(mDateInput);
        if (!currentPtrDate) return;

        const rows = document.querySelectorAll('#rowContainer tr');
        const now = new Date();
        const todayLocal = formatLocalDate(now);

        for (let tr of rows) {
            const dInput = tr.querySelector('.row-date-input');
            let assigned = false;
            let daySafety = 0;

            while (!assigned && daySafety < 90) {
                let dStr = formatLocalDate(currentPtrDate);
                let info = await fetchDateSlotInfo(dStr);
                let config = info.config || (window.cachedConfigs ? window.cachedConfigs[currentPtrDate.getDay()] : null);

                // Skip Sunday / CLOSED / specific-date closures
                if (info.is_closed || isConfigClosed(config)) {
                    currentPtrDate.setDate(currentPtrDate.getDate() + 1);
                    daySafety++;
                    continue;
                }

                const openingTime = config.opening_time || '08:00:00';
                const closingTime = config.closing_time || '17:00:00';
                const slotDuration = parseInt(config.slot_duration) || 60;
                const maxPatients = parseInt(config.max_patients_per_slot) || 2;
                const hasLunch = (config.has_lunch_break === true || config.has_lunch_break === 1 || parseInt(config.has_lunch_break) === 1);
                const leadHours = (config.lead_time_hours !== undefined && config.lead_time_hours !== null) ? parseInt(config.lead_time_hours) : 2;

                let startPtr = buildLocalDateTime(dStr, openingTime);
                let endPtr = buildLocalDateTime(dStr, closingTime);

                while (startPtr < endPtr) {
                    let hours = startPtr.getHours().toString().padStart(2, '0');
                    let minutes = startPtr.getMinutes().toString().padStart(2, '0');
                    let tStr = `${hours}:${minutes}:00`;

                    let isLunch = (hasLunch && config.lunch_start && config.lunch_end && tStr >= config.lunch_start && tStr < config.lunch_end);
                    let isPast = false;

                    if (dStr === todayLocal) {
                        const leadTimeMs = leadHours * 3600 * 1000;
                        const cutoffTime = now.getTime() + leadTimeMs;
                        const slotDate = buildLocalDateTime(dStr, tStr);
                        isPast = slotDate.getTime() < cutoffTime;
                    }

                    if (!isLunch && !isPast) {
                        let dbCount = parseInt((info.occupied_slots && info.occupied_slots[tStr]) || 0);
                        let localCount = localTracker[`${dStr}_${tStr}`] || 0;

                        if (dbCount + localCount < maxPatients) {
                            dInput.value = dStr;
                            updateRowSlots(dInput, tStr);
                            localTracker[`${dStr}_${tStr}`] = localCount + 1;
                            assigned = true;
                            break;
                        }
                    }

                    startPtr.setMinutes(startPtr.getMinutes() + slotDuration);
                }

                // If NO SLOTS available on this day, cascade to the next calendar day
                if (!assigned) {
                    currentPtrDate.setDate(currentPtrDate.getDate() + 1);
                }
                daySafety++;
            }
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cpu me-1.5"></i> SMART AUTO-TIME';
        }
        performGlobalSync();
        saveBulkDraft();
    }
}
window.runSmartScheduler = runSmartScheduler;

// =========================================================================
// 5. SERVICES & PACKAGES MODAL (BIDIRECTIONAL CONFLICT ENGINE)
// =========================================================================
window.openServiceModal = function (idx) {
    activeRowIdx = idx;
    const row = document.getElementById(`r_${idx}`);
    const sexInput = row ? row.querySelector(`input[name="patients[${idx}][sex]"]`) : null;
    const activeSex = sexInput ? sexInput.value : 'Male';

    const hiddenContainer = document.getElementById(`hidden_inputs_${idx}`);
    const selectedSvcIds = hiddenContainer ? Array.from(hiddenContainer.querySelectorAll('input[name*="[service_ids]"]')).map(i => i.value) : [];
    const selectedPkgIds = hiddenContainer ? Array.from(hiddenContainer.querySelectorAll('input[name*="[package_ids]"]')).map(i => i.value) : [];

    // Pre-check packages
    document.querySelectorAll('.pkg-chk-item').forEach(chk => {
        chk.checked = selectedPkgIds.includes(chk.value);
    });

    // Pre-check services
    document.querySelectorAll('.svc-chk-item').forEach(chk => {
        chk.checked = selectedSvcIds.includes(chk.value);
        chk.onchange = function() {
            recalculateModalTestDependencies();
        };
    });

    // Filter by Organization Mode
    filterModalPackagesForOrg();

    // Filter by gender
    document.querySelectorAll('.modal-item-row').forEach(item => {
        const restriction = item.dataset.gender;
        if (activeSex === 'Male' && restriction === 'female') {
            item.classList.add('d-none');
            const chk = item.querySelector('input[type="checkbox"]');
            if (chk) chk.checked = false;
        } else if (activeSex === 'Female' && restriction === 'male') {
            item.classList.add('d-none');
            const chk = item.querySelector('input[type="checkbox"]');
            if (chk) chk.checked = false;
        }
    });

    recalculateModalTestDependencies();

    const searchInput = document.getElementById('serviceSearch');
    if (searchInput) searchInput.value = '';

    const modalEl = document.getElementById('serviceModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
};

window.handlePackageCheckboxChange = function() {
    recalculateModalTestDependencies();
};

function recalculateModalTestDependencies() {
    const coveredByCheckedPackages = new Set();
    const activePackages = Array.from(document.querySelectorAll('.pkg-chk-item:checked'));

    activePackages.forEach(pkgChk => {
        const pkgId = pkgChk.value;
        const pkg = window.packagesMap[pkgId];
        if (pkg && pkg.services) {
            pkg.services.forEach(svc => coveredByCheckedPackages.add(svc.id.toString()));
        } else if (pkgChk.dataset.services) {
            try {
                const sArr = JSON.parse(pkgChk.dataset.services);
                sArr.forEach(id => coveredByCheckedPackages.add(id.toString()));
            } catch(e) {}
        }
    });

    const checkedIndividualServices = new Set();
    document.querySelectorAll('.svc-chk-item:checked').forEach(svcChk => {
        checkedIndividualServices.add(svcChk.value.toString());
    });

    // Package-to-Package Conflict Rules
    document.querySelectorAll('.pkg-chk-item').forEach(pkgChk => {
        const pkgId = pkgChk.value;
        const pkg = window.packagesMap[pkgId];
        let pkgServiceIds = [];

        if (pkg && pkg.services) {
            pkgServiceIds = pkg.services.map(s => s.id.toString());
        } else if (pkgChk.dataset.services) {
            try {
                pkgServiceIds = JSON.parse(pkgChk.dataset.services).map(String);
            } catch(e) {}
        }

        const wrapper = pkgChk.closest('.form-check') || pkgChk.parentElement;
        let conflictBadge = wrapper ? wrapper.querySelector('.pkg-conflict-badge') : null;

        if (pkgChk.checked) {
            pkgChk.disabled = false;
            if (wrapper) {
                wrapper.classList.remove('opacity-50');
                wrapper.style.pointerEvents = 'auto';
                wrapper.style.cursor = 'default';
                wrapper.removeAttribute('title');
            }
            if (conflictBadge) conflictBadge.classList.add('d-none');
            return;
        }

        const conflictsWithPackage = pkgServiceIds.some(id => coveredByCheckedPackages.has(id));
        const conflictsWithIndividual = pkgServiceIds.some(id => checkedIndividualServices.has(id));
        const isConflicting = conflictsWithPackage || conflictsWithIndividual;

        if (isConflicting) {
            pkgChk.disabled = true;
            if (wrapper) {
                wrapper.classList.add('opacity-50');
                wrapper.style.pointerEvents = 'none';
                wrapper.style.cursor = 'not-allowed';
                wrapper.setAttribute('title', conflictsWithPackage 
                    ? 'Conflicting package: shares tests with an already selected package.' 
                    : 'Conflicting package: includes an individual test that is already selected.');
            }
            if (!conflictBadge && wrapper) {
                const priceContainer = wrapper.querySelector('.d-flex.justify-content-between');
                if (priceContainer) {
                    conflictBadge = document.createElement('span');
                    conflictBadge.className = 'badge bg-danger text-white border border-danger pkg-conflict-badge smaller ms-2';
                    conflictBadge.style.fontSize = '0.6rem';
                    conflictBadge.innerText = 'Conflicting';
                    priceContainer.appendChild(conflictBadge);
                }
            }
            if (conflictBadge) conflictBadge.classList.remove('d-none');
        } else {
            pkgChk.disabled = false;
            if (wrapper) {
                wrapper.classList.remove('opacity-50');
                wrapper.style.pointerEvents = 'auto';
                wrapper.style.cursor = 'default';
                wrapper.removeAttribute('title');
            }
            if (conflictBadge) conflictBadge.classList.add('d-none');
        }
    });

    // Package-to-Service Conflict Rules
    document.querySelectorAll('.svc-chk-item').forEach(svcChk => {
        const isCovered = coveredByCheckedPackages.has(svcChk.value.toString());
        const wrapper = document.getElementById('service_wrapper_' + svcChk.value) || svcChk.closest('.form-check');
        let coveredBadge = wrapper ? wrapper.querySelector('.pkg-covered-badge') : null;

        if (isCovered) {
            svcChk.checked = false;
            svcChk.disabled = true;
            if (wrapper) {
                wrapper.classList.add('opacity-50');
                wrapper.style.pointerEvents = 'none';
                wrapper.style.cursor = 'not-allowed';
                wrapper.setAttribute('title', 'Already included in your selected package.');
            }
            if (!coveredBadge && wrapper) {
                const flexRow = wrapper.querySelector('.d-flex.justify-content-between');
                if (flexRow) {
                    coveredBadge = document.createElement('span');
                    coveredBadge.className = 'badge bg-warning text-dark border border-warning pkg-covered-badge smaller ms-2';
                    coveredBadge.style.fontSize = '0.6rem';
                    coveredBadge.innerText = 'In Package';
                    flexRow.appendChild(coveredBadge);
                }
            }
            if (coveredBadge) coveredBadge.classList.remove('d-none');
        } else {
            svcChk.disabled = false;
            if (wrapper) {
                wrapper.classList.remove('opacity-50');
                wrapper.style.pointerEvents = 'auto';
                wrapper.style.cursor = 'default';
                wrapper.removeAttribute('title');
            }
            if (coveredBadge) coveredBadge.classList.add('d-none');
        }
    });

    // Make selection pronounced: dynamically toggle .is-selected class on all cards
    document.querySelectorAll('.pkg-chk-item, .svc-chk-item').forEach(chk => {
        const wrapper = chk.closest('.form-check');
        if (wrapper) {
            wrapper.classList.toggle('is-selected', chk.checked);
        }
    });

    const totalSelected = activePackages.length + document.querySelectorAll('.svc-chk-item:checked').length;
    const counterEl = document.getElementById('modal_selection_counter');
    if (counterEl) {
        counterEl.innerText = `${totalSelected} selected (${activePackages.length} package${activePackages.length === 1 ? '' : 's'}, ${document.querySelectorAll('.svc-chk-item:checked').length} test${document.querySelectorAll('.svc-chk-item:checked').length === 1 ? '' : 's'})`;
    }
}

window.filterModalServices = function(query) {
    const q = (query || '').toUpperCase().trim();
    document.querySelectorAll('.modal-item-row').forEach(item => {
        const name = (item.dataset.name || '').toUpperCase();
        item.classList.toggle('d-none', q && !name.includes(q));
    });
};

function applyServices() {
    const checkedPkgs = Array.from(document.querySelectorAll('.pkg-chk-item:checked'));
    const checkedSvcs = Array.from(document.querySelectorAll('.svc-chk-item:checked'));

    const coveredIds = new Set();
    let hasOverlappingPackages = false;

    checkedPkgs.forEach(pkgChk => {
        const pkg = window.packagesMap[pkgChk.value];
        if (pkg && pkg.services) {
            pkg.services.forEach(s => {
                if (coveredIds.has(s.id.toString())) {
                    hasOverlappingPackages = true;
                }
                coveredIds.add(s.id.toString());
            });
        }
    });

    if (hasOverlappingPackages) {
        return alert("Selection error: You have selected multiple packages that share overlapping tests. Please remove one of the conflicting packages.");
    }

    const hasOverlappingSvc = checkedSvcs.some(s => coveredIds.has(s.value.toString()));
    if (hasOverlappingSvc) {
        return alert("Selection error: An individual test is already covered in a selected package. Please remove the duplicate test.");
    }

    let h = '';
    let labels = [];

    checkedPkgs.forEach(p => {
        h += `<input type="hidden" name="patients[${activeRowIdx}][package_ids][]" value="${p.value}">`;
        labels.push(`[PKG] ${p.dataset.label}`);
    });

    checkedSvcs.forEach(s => {
        h += `<input type="hidden" name="patients[${activeRowIdx}][service_ids][]" value="${s.value}">`;
        labels.push(s.dataset.label);
    });

    const displayElem = document.getElementById(`display_tests_${activeRowIdx}`);
    if (displayElem) displayElem.innerText = labels.join(', ') || 'NO SELECTIONS';

    const hiddenElem = document.getElementById(`hidden_inputs_${activeRowIdx}`);
    if (hiddenElem) hiddenElem.innerHTML = h;

    const modalEl = document.getElementById('serviceModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.hide();
    }

    performGlobalSync();
    saveBulkDraft();
}
window.applyServices = applyServices;

// =========================================================================
// 6. EXCEL IMPORT & PARSER
// =========================================================================
function switchTab(tab) {
    const manualPane = document.getElementById('pane-manual');
    const excelPane = document.getElementById('pane-excel');
    const manualBtn = document.getElementById('btn-manual');
    const excelBtn = document.getElementById('btn-excel');

    if (tab === 'manual') {
        if (manualPane) manualPane.style.display = 'block';
        if (excelPane) excelPane.style.display = 'none';
        if (manualBtn) manualBtn.className = 'btn-custom btn-accent px-4 py-2 fw-bold btn-sm';
        if (excelBtn) excelBtn.className = 'btn-custom btn-outline-accent px-4 py-2 border-0 fw-bold btn-sm';
    } else {
        if (manualPane) manualPane.style.display = 'none';
        if (excelPane) excelPane.style.display = 'block';
        if (excelBtn) excelBtn.className = 'btn-custom btn-accent px-4 py-2 fw-bold btn-sm';
        if (manualBtn) manualBtn.className = 'btn-custom btn-outline-accent px-4 py-2 border-0 fw-bold btn-sm';
    }
}
window.switchTab = switchTab;

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
    }
    return val;
}

function normalizeExcelPhone(val) {
    if (val === undefined || val === null) return '';
    let phone = String(val).trim().split('.')[0];
    phone = phone.replace(/[^\d+]/g, '');
    if (phone.startsWith('+639') && phone.length === 13) return '0' + phone.substring(3);
    if (phone.startsWith('639') && phone.length === 12) return '0' + phone.substring(2);
    if (phone.startsWith('9') && phone.length === 10) return '0' + phone;
    return phone;
}

function importExcelData() {
    const fileInput = document.getElementById('excel_file_input');
    const btn = document.getElementById('importBtn');

    if (!fileInput || !fileInput.files[0]) return showAlert("Please select an Excel file first.");

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
            showAlert(`Success! ${loadedCount} patient record(s) loaded into the manual form.`);
        } else {
            showAlert("Excel sheet contains no data records.");
        }

        btn.disabled = false;
        btn.innerHTML = 'LOAD DATA INTO MANUAL FORM <i class="bi bi-arrow-right-short ms-1"></i>';
    };

    reader.readAsArrayBuffer(file);
}
window.importExcelData = importExcelData;

// =========================================================================
// 7. PAYMENT WORKFLOW & RECEIPT LOCK
// =========================================================================
function hasActiveBulkReceipt() {
    const receiptInput = document.getElementById('in_receipt');
    const hasNewFile = receiptInput && receiptInput.files && receiptInput.files.length > 0;
    const hasCachedData = (window.bulkReceiptLocalData !== null) || (localStorage.getItem('receipt_base64_bulk') !== null);
    return hasNewFile || hasCachedData;
}
window.hasActiveBulkReceipt = hasActiveBulkReceipt;

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
window.updateBulkFieldLockState = updateBulkFieldLockState;

function handleBulkPaymentMethodChange(radio) {
    const targetMethod = radio.value;
    if (targetMethod === 'Cash' && bulkLastMethod === 'Cashless' && hasActiveBulkReceipt()) {
        alert("You have an attached proof of payment receipt. Please remove the receipt before switching to Cash on Site.");
        const payCashless = document.getElementById('pay_cashless');
        if (payCashless) payCashless.checked = true;
        return;
    }

    bulkLastMethod = targetMethod;
    toggleBulkPaymentFields();
    updateBulkFieldLockState();
    toggleBulkSubmitButton();
}
window.handleBulkPaymentMethodChange = handleBulkPaymentMethodChange;

function handleBulkProviderChange(radio) {
    const targetProviderId = radio.value;
    if (bulkLastProviderId && bulkLastProviderId !== targetProviderId && hasActiveBulkReceipt()) {
        alert("Please remove the attached receipt before changing E-Wallet providers.");
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
window.handleBulkProviderChange = handleBulkProviderChange;

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
window.toggleBulkPaymentFields = toggleBulkPaymentFields;

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
        if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected: ${file.name}`;

        updateBulkFieldLockState();
        toggleBulkSubmitButton();
    };
    reader.readAsDataURL(file);
}
window.handleBulkReceiptUpload = handleBulkReceiptUpload;

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
window.removeBulkUploadedReceipt = removeBulkUploadedReceipt;

function viewBulkReceiptFile() {
    const b64 = window.bulkReceiptLocalData || localStorage.getItem('receipt_base64_bulk');
    const name = localStorage.getItem('receipt_name_bulk') || 'Proof of Payment';
    if (b64 && typeof window.openFilePreview === 'function') {
        window.openFilePreview(b64, name);
    } else if (b64) {
        window.zoomQR(b64);
    }
}
window.viewBulkReceiptFile = viewBulkReceiptFile;

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
        if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected: ${name}`;
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
window.toggleBulkSubmitButton = toggleBulkSubmitButton;

window.zoomQR = function(qrSrc) {
    if (qrSrc && typeof window.openFilePreview === 'function') {
        const providerName = document.getElementById('selected_provider_name')?.innerText || 'E-Wallet';
        window.openFilePreview(qrSrc, `${providerName} Payment QR Code`);
    }
};

// =========================================================================
// 8. DRAFT PERSISTENCE
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
        const svcInputs = tr.querySelectorAll(`input[name="patients[${idx}][service_ids][]"]`);
        p.service_ids = Array.from(svcInputs).map(i => i.value);

        const pkgInputs = tr.querySelectorAll(`input[name="patients[${idx}][package_ids][]"]`);
        p.package_ids = Array.from(pkgInputs).map(i => i.value);

        patients.push(p);
    });

    const activeProvider = document.querySelector('.bulk-prov-radio:checked');
    const payMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'Cash';
    const toggleUseOrg = document.getElementById('toggle_use_org');

    const draft = {
        step: currentStep,
        use_recognized_org: toggleUseOrg ? toggleUseOrg.checked : false,
        organization_id: document.getElementById('hidden_org_id')?.value || '',
        organization_name: document.getElementById('hidden_org')?.value.trim() || '',
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
        console.warn("Failed to write bulk draft:", e);
    }
}
window.saveBulkDraft = saveBulkDraft;

async function loadBulkDraft() {
    try {
        const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
        if (!raw) return false;
        const draft = JSON.parse(raw);
        if (!draft) return false;

        isRestoringDraft = true;
        const todayLocal = formatLocalDate(new Date());
        let isDateOutdated = false;

        if (draft.appointment_date) {
            if (draft.appointment_date < todayLocal) {
                isDateOutdated = true;
                draft.appointment_date = todayLocal;
            }
            if (masterDate) masterDate.value = draft.appointment_date;
        }

        if (draft.use_recognized_org !== undefined) {
            const toggleUseOrg = document.getElementById('toggle_use_org');
            if (toggleUseOrg) {
                toggleUseOrg.checked = Boolean(draft.use_recognized_org);
                handleUseOrgToggle(toggleUseOrg);
            }
        }

        if (draft.organization_id) {
            const orgSelect = document.getElementById('master_org_select');
            if (orgSelect) {
                orgSelect.value = draft.organization_id;
                handleOrgDropdownChange(orgSelect);
            }
        } else if (draft.organization_name) {
            const orgInput = document.getElementById('master_org');
            if (orgInput) {
                orgInput.value = draft.organization_name;
                handleMasterOrgTextInput(orgInput);
            }
        }

        await fetchOccupancy();

        if (Array.isArray(draft.patients) && draft.patients.length > 0) {
            document.getElementById('rowContainer').innerHTML = '';
            rowCount = 0;
            draft.patients.forEach(p => {
                if (isDateOutdated || (p.appointment_date && p.appointment_date < todayLocal)) {
                    p.appointment_date = todayLocal;
                    p.time_slot = '';
                }
                addRow(p);
            });
        }

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

        if (!isDateOutdated && draft.step && draft.step > 2 && draft.organization_name && draft.appointment_date) {
            goToPage(draft.step);
        } else {
            goToPage(2);
        }

        isRestoringDraft = false;
        return true;
    } catch (e) {
        console.error("Failed to load draft:", e);
        isRestoringDraft = false;
        return false;
    }
}
window.loadBulkDraft = loadBulkDraft;

function clearBulkDraft() {
    try {
        localStorage.removeItem(DRAFT_STORAGE_KEY);
        localStorage.removeItem('receipt_base64_bulk');
        localStorage.removeItem('receipt_name_bulk');
    } catch (e) {}
}
window.clearBulkDraft = clearBulkDraft;

// =========================================================================
// 9. FINAL SUBMISSION HANDLER & VALIDATION
// =========================================================================
function submitBulkManualForm(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    const form = document.getElementById('bulkForm');
    if (!form) return;

    if (!validateBulkForm(e)) {
        return false;
    }

    const submitBtn = document.getElementById('final_submit_btn');
    if (submitBtn) {
        submitBtn.style.pointerEvents = 'none';
        submitBtn.classList.add('opacity-75');
        submitBtn.innerHTML = 'SUBMITTING... <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>';
    }

    clearBulkDraft();
    form.submit();
}
window.submitBulkManualForm = submitBulkManualForm;

function validateBulkForm(e) {
    performGlobalSync();
    let errors = [];

    const isOrgMode = document.getElementById('toggle_use_org')?.checked || false;
    const orgName = document.getElementById('hidden_org')?.value.trim() || '';
    const date = masterDate ? masterDate.value : '';

    if (!orgName) {
        errors.push(isOrgMode ? "Please select a recognized organization from the dropdown." : "Organization / Company Name is required.");
    }

    if (!date) errors.push("Preferred Start Date is required.");

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

        const svcInputs = Array.from(tr.querySelectorAll(`input[name="patients[${idx}][service_ids][]"]`)).map(i => i.value);
        const pkgInputs = Array.from(tr.querySelectorAll(`input[name="patients[${idx}][package_ids][]"]`)).map(i => i.value);

        if (svcInputs.length === 0 && pkgInputs.length === 0) {
            errors.push(`Row ${rowNum} (${p.name || 'Unnamed'}): At least one test or package must be selected.`);
        } else {
            const covered = new Set();
            let rowHasConflictingPackages = false;

            pkgInputs.forEach(pkgId => {
                const pkg = window.packagesMap[pkgId];
                if (pkg && pkg.services) {
                    pkg.services.forEach(svc => {
                        if (covered.has(svc.id.toString())) {
                            rowHasConflictingPackages = true;
                        }
                        covered.add(svc.id.toString());
                    });
                }
            });

            if (rowHasConflictingPackages) {
                errors.push(`Row ${rowNum} (${p.name || 'Unnamed'}): Contains multiple packages that share overlapping tests.`);
            }

            const hasDouble = svcInputs.some(sId => covered.has(sId.toString()));
            if (hasDouble) {
                errors.push(`Row ${rowNum} (${p.name || 'Unnamed'}): Contains individual tests that are already covered in selected packages.`);
            }
        }

        const rowDate = tr.querySelector('.row-date-input')?.value;
        const tSelect = tr.querySelector('.t-select')?.value;
        if (!rowDate) errors.push(`Row ${rowNum}: Schedule Date is required.`);
        if (!tSelect) errors.push(`Row ${rowNum}: Preferred Time Slot is required.`);
    });

    const payCashless = document.getElementById('pay_cashless');
    if (payCashless && payCashless.checked) {
        const selectedProvider = document.querySelector('.bulk-prov-radio:checked');
        if (!selectedProvider) errors.push("Payment: Please select an E-Wallet provider (GCash, Maya).");
        if (!hasActiveBulkReceipt()) errors.push("Payment: Please upload a transaction receipt.");
    }

    const agreeTerms = document.getElementById('agree_terms');
    if (agreeTerms && !agreeTerms.checked) {
        errors.push("Agreements: You must agree to the Clinical Privacy Policy.");
    }

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

        const titleEl = document.getElementById('wizardValidationTitle');
        const msgEl = document.getElementById('wizardValidationMsg');
        if (titleEl) titleEl.innerText = "Requirements Lacking";
        if (msgEl) msgEl.innerHTML = errorHtml;

        const modalEl = document.getElementById('wizardValidationModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
        return false;
    }
    return true;
}

function showAlert(msg) {
    const titleEl = document.getElementById('wizardValidationTitle');
    const msgEl = document.getElementById('wizardValidationMsg');
    if (titleEl) titleEl.innerText = "Attention Required";
    if (msgEl) msgEl.innerHTML = msg;

    const modalEl = document.getElementById('wizardValidationModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}
window.showAlert = showAlert;

// =========================================================================
// 10. DOM INITIALIZER
// =========================================================================
document.addEventListener('DOMContentLoaded', async () => {
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
            document.querySelectorAll('.modal-item-row').forEach(item => {
                const name = item.dataset.name || '';
                item.classList.toggle('d-none', !name.includes(query));
            });
        });
    }

    const modalPhoneDisp = document.getElementById('modal_phone_display');
    if (modalPhoneDisp) {
        modalPhoneDisp.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            const phoneHidden = document.getElementById('modal_phone');
            if (phoneHidden) phoneHidden.value = this.value ? '09' + this.value : '';
        });
    }

    const hasDraft = await loadBulkDraft();
    if (!hasDraft) {
        await fetchOccupancy();
        await validateMasterDate();
        toggleBulkPaymentFields();
        toggleBulkSubmitButton();
        filterModalPackagesForOrg();
    }
});
</script>
@endpush