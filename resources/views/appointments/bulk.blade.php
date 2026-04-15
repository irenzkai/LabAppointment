@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 text-start">
        <h2 class="text-neon fw-bold mb-4 uppercase" style="letter-spacing: 2px;">BULK APPOINTMENT</h2>

        {{-- Hidden bridge to pass clinic rules to JS instantly --}}
        <div id="clinic-rules" data-configs='@json($configs)' class="d-none"></div>

        {{-- STEP 1: ORGANIZATION & GLOBAL START DATE --}}
        <div class="card p-4 border-secondary mb-4 shadow-lg">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="text-white small fw-bold mb-1 uppercase">Organization / Company Name</label>
                    <input type="text" id="master_org" class="form-control border-neon py-3 fw-bold shadow-none" placeholder="Enter Requesting Entity...">
                </div>
                <div class="col-md-5">
                    <label class="text-white small fw-bold mb-1 uppercase">Preferred Start Date</label>
                    <input type="date" id="master_date" class="form-control border-neon py-3 fw-bold shadow-none" min="{{ date('Y-m-d') }}">
                </div>
            </div>
        </div>

        {{-- STEP 2: TABS --}}
        <div class="d-flex gap-2 mb-4 border-bottom border-secondary pb-3">
            <button class="btn-custom btn-neon px-4 py-2 fw-bold" id="btn-manual" onclick="switchTab('manual')">
                FORM ENTRY
            </button>
            <button class="btn-custom btn-outline-neon text-white px-4 py-2 border-0 fw-bold" id="btn-excel" onclick="switchTab('excel')">
                TEMPLATE UPLOAD
            </button>
        </div>

        <div id="tab-content">
            <div id="pane-manual">
                <form action="{{ route('appointments.bulk.manual') }}" method="POST" id="bulkForm">
                    @csrf
                    <input type="hidden" name="organization_name" class="sync-org">
                    <input type="hidden" name="appointment_date" class="sync-date">

                    <div class="card border-secondary p-0 shadow-lg overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-dark align-top mb-0">
                                <thead class="bg-black text-secondary uppercase small">
                                    <tr style="letter-spacing: 1px;">
                                        <th class="ps-4" style="width: 350px;">Patient Info</th>
                                        <th>Address</th>
                                        <th style="width: 200px;">Tests</th>
                                        <th style="width: 220px;">Schedule Slot</th>
                                        <th class="pe-4 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="rowContainer"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                        <button type="button" class="btn-custom btn-outline-neon px-4" onclick="addRow()">+ ADD PATIENT</button>
                        <div class="d-flex gap-2">
                            <button type="button" id="smartSchedBtn" class="btn-custom btn-outline-neon border-neon text-neon px-4" onclick="runSmartScheduler()">
                                <i class="bi bi-cpu me-1"></i> SMART AUTO-TIME
                            </button>
                            <button type="submit" class="btn-custom btn-neon px-5 py-3">SUBMIT BULK BOOKING</button>
                        </div>
                    </div>
                </form>
            </div>
            
            {{-- Excel Pane --}}
            <div id="pane-excel" style="display: none;">
                <div class="row g-4 text-start">
                    {{-- Step A: Download --}}
                    <div class="col-md-5">
                        <div class="card p-4 h-100 border-secondary bg-black shadow-lg">
                            <h5 class="text-white fw-bold mb-3 small uppercase" style="letter-spacing: 1px;">DOWNLOAD TEMPLATE</h5>
                            <p class="text-white smaller mb-4">Download the base file. Enter patient names, birthdates, and contact info, then save.</p>
                            
                            <div class="d-grid gap-2">
                                <a href="{{ route('appointments.bulk.template', 'csv') }}" class="btn-custom btn-outline-neon border-neon text-neon py-3 shadow-none">
                                    <i class="bi bi-filetype-csv me-2"></i> DOWNLOAD .CSV
                                </a>
                                <a href="{{ route('appointments.bulk.template', 'xlsx') }}" class="btn-custom btn-outline-neon border-neon text-neon py-3 shadow-none">
                                    <i class="bi bi-filetype-xlsx me-2"></i> DOWNLOAD .XLSX
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Step B: Upload & Parse --}}
                    <div class="col-md-7">
                        <div class="card p-4 h-100 border-neon bg-black shadow-lg">
                            <h5 class="text-neon fw-bold mb-3 small uppercase" style="letter-spacing: 1px;">IMPORT DATA TO FORM</h5>
                            
                            {{-- REMOVED <form> tag to prevent accidental page refresh --}}
                            <div class="p-5 text-center border border-secondary border-dashed rounded mb-4 bg-dark bg-opacity-25">
                                <i class="bi bi-file-earmark-arrow-up text-neon display-4 mb-3 d-block"></i>
                                
                                {{-- Input for the Excel file --}}
                                <input type="file" id="excel_file_input" class="form-control bg-black border-secondary text-white mx-auto shadow-none" style="max-width: 320px;" accept=".xlsx, .xls, .csv">
                                
                                <p class="text-secondary smaller mt-3 mb-0 italic">Supported: Excel (.xlsx) or CSV files.</p>
                            </div>

                            {{-- Trigger Button: Calls the JS function --}}
                            <button type="button" id="importBtn" onclick="importExcelData()" class="btn-custom btn-neon w-100 py-3 fw-bold fs-6">
                                LOAD DATA INTO MANUAL FORM <i class="bi bi-arrow-right-short ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: TEST SELECTOR --}}
<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-neon bg-black shadow-lg">
            <div class="modal-header border-neon bg-dark py-3">
                <h6 class="modal-title text-neon fw-bold uppercase">Select Laboratory Tests</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom border-secondary"><input type="text" id="serviceSearch" class="form-control bg-black border-secondary text-white" placeholder="Search test name..."></div>
                <div id="serviceList" class="overflow-auto" style="max-height: 400px;">
                    @foreach($services as $s)
                    <label class="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary service-item cursor-pointer hover-bg" for="ch_{{ $s->id }}" data-name="{{ strtoupper($s->name) }}">
                        <div class="d-flex align-items-center">
                            <input class="form-check-input me-3 mt-0 border-secondary" type="checkbox" value="{{ $s->id }}" data-label="{{ $s->name }}" id="ch_{{ $s->id }}">
                            <span class="text-white fw-bold small">{{ strtoupper($s->name) }}</span>
                        </div>
                        <span class="text-neon fw-bold small">₱{{ number_format($s->price) }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-neon bg-dark p-0"><button type="button" class="btn-custom btn-neon w-100 py-3" onclick="applyServices()">APPLY SELECTION</button></div>
        </div>
    </div>
</div>

{{-- MODAL: VALIDATION ALERT --}}
<div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-neon bg-black shadow-lg text-center p-4">
            <i class="bi bi-exclamation-circle text-neon mb-3 d-block" style="font-size: 3rem;"></i>
            <h5 class="text-white fw-bold mb-2 uppercase">Information</h5>
            <p id="validationMsg" class="text-secondary small mb-4"></p>
            <button type="button" class="btn-custom btn-neon w-100 py-2" data-bs-dismiss="modal">UNDERSTOOD</button>
        </div>
    </div>
</div>

<style>
    .hover-bg:hover { background-color: rgba(90, 247, 129, 0.05); }
    .cursor-pointer { cursor: pointer; }
    #rowContainer input, #rowContainer select, #rowContainer textarea { background-color: #050505 !important; border: 1px solid #222 !important; color: white !important; }
</style>

<script>
let rowCount = 0;
let activeRowIdx = null;
let cachedConfigs = JSON.parse(document.getElementById('clinic-rules').dataset.configs);
let cachedOccupancy = [];
const masterOrg = document.getElementById('master_org');
const masterDate = document.getElementById('master_date');

function performGlobalSync() {
    document.querySelectorAll('.sync-org').forEach(el => el.value = masterOrg.value);
    document.querySelectorAll('.sync-date').forEach(el => el.value = masterDate.value);
}

masterOrg.addEventListener('input', performGlobalSync);
masterDate.addEventListener('change', performGlobalSync);

// Helper: Show custom modal
function showAlert(msg) {
    document.getElementById('validationMsg').innerText = msg;
    new bootstrap.Modal(document.getElementById('validationModal')).show();
}

// 2. DYNAMIC ROW GENERATION (Fixed with default parameter)
function addRow(patient = null) {
    const minDate = document.getElementById('master_date').value || "{{ date('Y-m-d') }}";

    // Safety checks: handle if patient data is provided (from Excel) or not (manual add)
    const name = patient ? (patient.name || '') : '';
    const email = patient ? (patient.email || '') : '';
    const phone = patient ? (patient.phone || '') : '';
    const sex = patient && patient.sex ? (patient.sex.toLowerCase() === 'female' ? 'Female' : 'Male') : 'Male';
    const bday = patient ? (patient.birthdate || '') : '';
    const address = patient ? (patient.address || '') : '';
    
    const html = `
        <tr id="r_${rowCount}" class="border-secondary align-top">
            <td class="ps-4 py-3" style="min-width: 320px;">
                <div class="mb-2">
                    <label class="text-white mb-1 uppercase" style="font-size: 0.6rem; letter-spacing:1px;">FULL NAME</label>
                    <input type="text" name="patients[${rowCount}][name]" value="${name}" class="form-control form-control-sm uppercase" placeholder="Enter Full Name" required>
                </div>
                <div class="mb-2">
                    <label class="text-white mb-1 uppercase" style="font-size: 0.6rem; letter-spacing:1px;">EMAIL</label>
                    <input type="email" name="patients[${rowCount}][email]" value="${email}" class="form-control form-control-sm" placeholder="name@email.com" required>
                </div>
                <div class="mb-2">
                    <label class="text-white mb-1 uppercase" style="font-size: 0.6rem; letter-spacing:1px;">CONTACT NUMBER</label>
                    <input type="text" name="patients[${rowCount}][phone]" value="${phone}" class="form-control form-control-sm" placeholder="09xxxxxxxxx" required>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="text-white mb-1 uppercase" style="font-size: 0.6rem; letter-spacing:1px;">SEX</label>
                        <select name="patients[${rowCount}][sex]" class="form-select form-select-sm p-sex">
                            <option value="Male" ${sex === 'Male' ? 'selected' : ''}>MALE</option>
                            <option value="Female" ${sex === 'Female' ? 'selected' : ''}>FEMALE</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="text-white mb-1 uppercase" style="font-size: 0.6rem; letter-spacing:1px;">BIRTHDATE</label>
                        <input type="date" name="patients[${rowCount}][birthdate]" value="${bday}" class="form-control form-control-sm" required>
                    </div>
                </div>
            </td>

            <td class="py-3">
                <label class="text-white mb-1 uppercase small" style="font-size: 0.6rem; letter-spacing:1px;">HOME ADDRESS</label>
                <textarea name="patients[${rowCount}][address]" class="form-control form-control-sm" rows="8" required style="resize:none;" placeholder="Enter complete address...">${address}</textarea>
            </td>

            <td class="py-3 px-3">
                <label class="text-white mb-1 uppercase small" style="font-size: 0.6rem; letter-spacing:1px;">TESTS</label>
                <div id="display_tests_${rowCount}" class="text-white fw-bold mb-2 uppercase border border-secondary p-2 rounded bg-black small" style="min-height: 50px; font-size: 0.7rem; overflow-y: auto; max-height: 100px;">
                    NO TESTS
                </div>
                <div id="hidden_inputs_${rowCount}"></div>
                
                <div class="d-flex gap-1">
                    <button type="button" class="btn-custom btn-outline-neon flex-grow-1 py-1 fw-bold small" onclick="openServiceModal(${rowCount})">
                        SELECT
                    </button>

                    <div class="dropdown">
                        <button class="btn-custom btn-outline-neon border-neon text-neon py-1 px-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-copy"></i>
                        </button>
                        <ul class="dropdown-menu bg-black border-neon shadow-lg">
                            <li><h6 class="dropdown-header text-neon small">COPY TO:</h6></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'all')">ALL PATIENTS</button></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'Male')">ALL MALES</button></li>
                            <li><button type="button" class="dropdown-item text-white small" onclick="bulkCopy(${rowCount}, 'Female')">ALL FEMALES</button></li>
                        </ul>
                    </div>
                </div>
            </td>

            <td class="py-3 px-3" style="width: 220px;">
                <label class="text-white mb-1 uppercase small" style="font-size: 0.6rem; letter-spacing:1px;">SCHEDULE DATE</label>
                <input type="date" name="patients[${rowCount}][appointment_date]" class="form-control form-control-sm row-date-input mb-3 shadow-none" value="${minDate}" min="${minDate}" onchange="updateRowSlots(this)">
                
                <label class="text-white mb-1 uppercase small" style="font-size: 0.6rem; letter-spacing:1px;">TIME SLOT</label>
                <select name="patients[${rowCount}][time_slot]" class="form-select form-select-sm border-neon text-neon fw-bold t-select py-2 shadow-none" required>
                    <option value="">Choose Date</option>
                </select>
            </td>

            <td class="pe-4 py-3 text-center align-middle">
                <button type="button" class="btn btn-danger-custom d-flex flex-column align-items-center justify-content-center py-2 fw-bold shadow-sm" 
                        style="border-radius:10px; width: 100%; min-width: 80px;" 
                        onclick="document.getElementById('r_${rowCount}').remove()">
                    <i class="bi bi-trash3-fill mb-1" style="font-size: 1.2rem;"></i>
                    <span style="font-size: 0.6rem; letter-spacing: 1px;">DELETE ROW</span>  
                </button>
            </td>
        </tr>`;

    document.getElementById('rowContainer').insertAdjacentHTML('beforeend', html);
    
    // Automatically fetch slots for the new row
    const newTr = document.getElementById(`r_${rowCount}`);
    updateRowSlots(newTr.querySelector('.row-date-input'));

    performGlobalSync(); 
    
    rowCount++;
}

async function importExcelData() {
    const fileInput = document.getElementById('excel_file_input');
    const btn = document.getElementById('importBtn');
    
    if(!fileInput.files[0]) return showAlert("Please select an Excel or CSV file first.");

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>IMPORTING...';

    const formData = new FormData();
    formData.append('excel_file', fileInput.files[0]);
    formData.append('_token', '{{ csrf_token() }}'); // CSRF protection

    try {
        // This is the route we just defined!
        const res = await fetch('{{ route("appointments.bulk.parse") }}', {
            method: 'POST',
            body: formData
        });
        
        if (!res.ok) throw new Error('File parse failed');
        
        const patients = await res.json();

        // Switch to the form tab and add rows
        switchTab('manual');
        // Clear existing rows if any
        document.getElementById('rowContainer').innerHTML = '';
        rowCount = 0;

        // Loop through patients from Excel and use your addRow function
        patients.forEach(p => addRow(p));
        
        showAlert(`Success! ${patients.length} patients loaded. Please select tests and time slots.`);

    } catch (e) {
        showAlert("Error: Please ensure your file matches the template headers.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'LOAD DATA INTO MANUAL FORM <i class="bi bi-arrow-right-short ms-1"></i>';
    }
}

async function fetchOccupancy() {
    // Use local date instead of toISOString to avoid "yesterday" errors
    const now = new Date();
    const todayLocal = now.toLocaleDateString('en-CA'); 
    const mDateInput = document.getElementById('master_date').value;
    const mDate = mDateInput || todayLocal;
    
    try {
        const res = await fetch(`/api/check-slots?date=${mDate}`);
        const data = await res.json();
        
        // Ensure occupancy is an array and dates are cleaned
        cachedOccupancy = (data.occupancy || []).map(o => ({
            ...o,
            // Clean "2026-03-26 00:00:00" to just "2026-03-26"
            appointment_date: o.appointment_date.split('T')[0].split(' ')[0]
        }));

        console.log("--- OCCUPANCY MAP LOADED ---");
        console.table(cachedOccupancy); // This lets you see the full slots in F12 console
    } catch (e) {
        console.error("Occupancy Fetch Failed", e);
        cachedOccupancy = [];
    }
}

function updateRowSlots(input) {
    const td = input.closest('td');
    const select = td.querySelector('.t-select');
    const selectedDate = input.value; // Format: YYYY-MM-DD

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
        const tStr = start.toTimeString().split(' ')[0]; // "08:00:00"
        
        // CHECK LUNCH
        const isLunch = (parseInt(config.has_lunch_break) === 1 && tStr >= config.lunch_start && tStr < config.lunch_end);
        
        // CHECK DB OCCUPANCY
        const dbMatch = (cachedOccupancy || []).find(o => o.appointment_date === selectedDate && o.time_slot === tStr);
        const isFull = dbMatch ? parseInt(dbMatch.patient_count) >= parseInt(config.max_patients_per_slot) : false;

        // ONLY ADD TO DROPDOWN IF NOT FULL AND NOT LUNCH
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
        await fetchOccupancy(); // Get latest data from database
        let localTracker = {}; 
        let currentPtrDate = new Date(mDateInput);
        const rows = document.querySelectorAll('#rowContainer tr');

        for (let tr of rows) {
            let assigned = false;
            let daySafety = 0;

            while (!assigned && daySafety < 30) {
                const dStr = currentPtrDate.toLocaleDateString('en-CA');
                const config = cachedConfigs[currentPtrDate.getDay()];

                if (!config || parseInt(config.is_open) === 0) {
                    currentPtrDate.setDate(currentPtrDate.getDate() + 1);
                    daySafety++;
                    continue;
                }

                let startPtr = new Date(`${dStr} ${config.opening_time}`);
                let endPtr = new Date(`${dStr} ${config.closing_time}`);

                while (startPtr < endPtr) {
                    const tStr = startPtr.toTimeString().split(' ')[0];
                    const isLunch = (parseInt(config.has_lunch_break) === 1 && tStr >= config.lunch_start && tStr < config.lunch_end);

                    if (!isLunch) {
                        const dbMatch = cachedOccupancy.find(o => o.appointment_date === dStr && o.time_slot === tStr);
                        const dbCount = dbMatch ? parseInt(dbMatch.patient_count) : 0;
                        const localCount = localTracker[`${dStr}_${tStr}`] || 0;

                        if (dbCount + localCount < parseInt(config.max_patients_per_slot)) {
                            const dInput = tr.querySelector('.row-date-input');
                            const tSelect = tr.querySelector('.t-select');

                            dInput.value = dStr;
                            updateRowSlots(dInput); // Refill dropdown
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
        // Show/Hide Panes
        manualPane.style.display = 'block';
        excelPane.style.display = 'none';

        // Highlight Manual Button (Solid Neon)
        manualBtn.className = 'btn-custom btn-neon px-4 py-2 fw-bold';
        // Dim Excel Button (Outline/Transparent)
        excelBtn.className = 'btn-custom btn-outline-neon text-white px-4 py-2 border-0 fw-bold';
    } else {
        // Show/Hide Panes
        manualPane.style.display = 'none';
        excelPane.style.display = 'block';

        // Highlight Excel Button (Solid Neon)
        excelBtn.className = 'btn-custom btn-neon px-4 py-2 fw-bold';
        // Dim Manual Button (Outline/Transparent)
        manualBtn.className = 'btn-custom btn-outline-neon text-white px-4 py-2 border-0 fw-bold';
    }
}
function openServiceModal(idx) { activeRowIdx = idx; new bootstrap.Modal(document.getElementById('serviceModal')).show(); }
function applyServices() {
    const sel = Array.from(document.querySelectorAll('.service-item input:checked'));
    document.getElementById(`display_tests_${activeRowIdx}`).innerText = sel.map(s => s.dataset.label).join(', ') || 'NO TESTS';
    let h = ''; sel.forEach(s => h += `<input type="hidden" name="patients[${activeRowIdx}][service_ids][]" value="${s.value}">`);
    document.getElementById(`hidden_inputs_${activeRowIdx}`).innerHTML = h;
    bootstrap.Modal.getInstance(document.getElementById('serviceModal')).hide();
}
function bulkCopy(sourceIdx, genderTarget) {
    // 1. Get the data from the source row
    const sourceInputs = document.querySelectorAll(`#hidden_inputs_${sourceIdx} input`);
    const sourceIds = Array.from(sourceInputs).map(i => i.value);
    const sourceText = document.getElementById(`display_tests_${sourceIdx}`).innerText;

    if (sourceIds.length === 0) {
        return showAlert("Please select tests for this row first before copying.");
    }

    // 2. Loop through all existing rows
    const rows = document.querySelectorAll('#rowContainer tr');
    let count = 0;

    rows.forEach(tr => {
        const targetIdx = tr.id.split('_')[1];
        
        // Skip the source row itself
        if (targetIdx == sourceIdx) return;

        // Check gender filter
        const targetSex = tr.querySelector('.p-sex').value;
        if (genderTarget === 'all' || targetSex === genderTarget) {
            
            // Update the display text
            document.getElementById(`display_tests_${targetIdx}`).innerText = sourceText;
            
            // Sync the hidden inputs
            let h = ''; 
            sourceIds.forEach(id => {
                h += `<input type="hidden" name="patients[${targetIdx}][service_ids][]" value="${id}">`;
            });
            document.getElementById(`hidden_inputs_${targetIdx}`).innerHTML = h;
            
            count++;
        }
    });

    // Optional: Visual feedback
    console.log(`Copied tests from row ${sourceIdx} to ${count} rows.`);
}



document.getElementById('master_date').addEventListener('change', async function() {
    const selectedDate = this.value;
    if(!selectedDate) return;
    await fetchOccupancy();

    document.querySelectorAll('.row-date-input').forEach(input => {
        input.value = selectedDate;

        input.min = selectedDate;
        
        updateRowSlots(input);
    });

    document.querySelectorAll('.sync-date').forEach(el => el.value = selectedDate);
    
    console.log("All rows synced to: " + selectedDate);
});

document.getElementById('bulkForm').addEventListener('submit', function(e) {
    const org = document.getElementById('master_org').value;
    const date = document.getElementById('master_date').value;
    const rows = document.querySelectorAll('#rowContainer tr');
    let missingTests = false;

    // 1. Check Master Fields
    if(!org || !date) {
        e.preventDefault();
        showAlert("Organization name and Start Date are required.");
        return;
    }

    // 2. Check Every Patient Row for Tests
    rows.forEach((tr, index) => {
        const testInputs = tr.querySelectorAll('input[name*="[service_ids]"]');
        const patientName = tr.querySelector('input[name*="[name]"]').value || `Patient in Row ${index + 1}`;
        
        if (testInputs.length === 0) {
            missingTests = true;
            tr.style.borderColor = "#ff4d4d"; // Highlight the problematic row
        } else {
            tr.style.borderColor = "var(--border-color)";
        }
    });

    if (missingTests) {
        e.preventDefault();
        showAlert("Every patient in the list must have at least one test selected.");
    }
});

window.onload = async () => {
    await fetchOccupancy();
    addRow(); 
};
</script>
@endsection