<div class="card p-4 border-secondary shadow-lg animate-page" style="background-color: var(--bg-card); color: var(--text-main);">
    
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <h5 class="text-accent fw-bold mb-0 uppercase tracking-wider">
            <i class="bi bi-file-earmark-medical me-2"></i>DIGITIZE HISTORICAL LABORATORY RECORD
        </h5>
        <div class="text-secondary small">Compilation Console</div>
    </div>

    {{-- Save Record Form --}}
    <form action="{{ route('history.save-manual', $targetUser->id) }}" method="POST" enctype="multipart/form-data" id="historicalImportForm" onsubmit="compileDigAddress()">
        @csrf
        
        <input type="hidden" name="patient_province" id="dig_province_hidden">
        <input type="hidden" name="patient_city" id="dig_city_hidden">
        <input type="hidden" name="patient_barangay" id="dig_barangay_hidden">

        <div class="row g-3 text-start">
            {{-- 1. RECORD PARAMETERS --}}
            <div class="col-md-6">
                <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Date of Original Record</label>
                <input type="date" name="date_of_record" class="form-control" required max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-6">
                <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Requested By (Entity / Organization)</label>
                <input type="text" name="requested_by" class="form-control" value="INDIVIDUAL" required>
            </div>

            {{-- 2. AUTO-FILLED EDITABLE PROFILE SNAPSHOT --}}
            <div class="col-12 mt-4">
                <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Verify Patient Demographic Snapshot</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">First Name</label>
                        <input type="text" name="patient_first_name" class="form-control uppercase" value="{{ $targetUser->first_name }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Middle Name</label>
                        <input type="text" name="patient_middle_name" class="form-control uppercase" value="{{ $targetUser->middle_name }}">
                    </div>
                    <div class="col-md-4">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Last Name</label>
                        <input type="text" name="patient_last_name" class="form-control uppercase" value="{{ $targetUser->last_name }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Age</label>
                        <input type="number" name="age" class="form-control" value="{{ $targetUser->birthdate ? \Carbon\Carbon::parse($targetUser->birthdate)->age : '' }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="Male" {{ $targetUser->sex == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ $targetUser->sex == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Cascading Address Selection Panel --}}
            <div class="col-12 mt-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Province</label>
                        <select id="dig_province" class="form-select" onchange="fetchDigCities(this.value)" required>
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">City / Municipality</label>
                        <select id="dig_city" class="form-select" onchange="fetchDigBarangays(this.value)" disabled required>
                            <option value="">Select Province First</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Barangay</label>
                        <select id="dig_brgy" class="form-select" onchange="updateDigCompiledAddress()" disabled required>
                            <option value="">Select City First</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Street / House No.</label>
                        <input type="text" id="dig_street" name="patient_street" class="form-control uppercase" value="{{ $targetUser->street }}" placeholder="House/Lot/Block/Street" oninput="updateDigCompiledAddress()" required>
                    </div>

                    {{-- Live Compiled Address Preview Container --}}
                    <div class="col-12 mt-2">
                        <div id="dig_compiled_address_container" class="alert alert-clinical p-2.5 d-none text-start animate-page" style="background-color: rgba(25, 211, 140, 0.03);">
                            <small class="text-accent fw-bold uppercase d-block mb-1" style="font-size: 0.65rem;">Compiled Address Preview</small>
                            <div id="dig_compiled_address_text" class="text-main small"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. INTERACTIVE PROCEDURES SELECTION --}}
            <div class="col-12 mt-4">
                <h6 class="text-accent mb-2 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Select Digitized Procedures / Tests</h6>
                <p class="text-secondary small mb-3">Choose standard clinic procedures or enter a custom test option below to attach as badges.</p>
                
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-5">
                        <select id="test_dropdown" class="form-select">
                            <option value="" disabled selected>-- Select a procedure --</option>
                            @foreach($availableServices->groupBy('category') as $category => $services)
                                <optgroup label="{{ strtoupper($category === 'individual' ? 'Individual Tests' : 'Health Packages') }}">
                                    @foreach($services as $service)
                                        <option value="{{ $service->name }}">{{ $service->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="custom_test_input" class="form-control" placeholder="Or type custom procedure name...">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-accent w-100" onclick="addProcedureBadge()">
                            <i class="bi bi-plus-lg me-1"></i> ADD TEST
                        </button>
                    </div>
                </div>

                {{-- Rendered test badges container --}}
                <div class="d-flex flex-wrap gap-2 p-3 border border-secondary border-opacity-15 rounded-3 mb-3" id="badges_container" style="background-color: rgba(0,0,0,0.01);">
                    <span class="text-muted small italic" id="badges-placeholder">No procedures selected yet. Select or type above.</span>
                </div>
            </div>

            {{-- 4. ATTACH SCANS & MANUALLY LABEL --}}
            <div class="col-12 mt-4">
                <h6 class="text-accent mb-2 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Results Document Uploads (PDF / Images Only)</h6>
                <p class="text-secondary small mb-3">Upload physical documents and assign descriptive titles (e.g. "Urinalysis PDF", "Hematology Image") and optional Certificate Numbers.</p>
                
                <div class="table-responsive border border-secondary border-opacity-25 rounded-3 mb-3">
                    <table class="table align-middle mb-0" style="color: var(--text-main);">
                        <thead class="text-secondary small uppercase" style="background-color: rgba(0, 0, 0, 0.02); border-bottom: 1.5px solid var(--border-color);">
                            <tr>
                                <th>Upload File</th>
                                <th style="width: 30%;">Assign Label</th>
                                {{-- FIXED: Added optional certificate number header to the staff digitizer table --}}
                                <th style="width: 25%;">Certificate Number (Optional)</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="scansTableBody">
                            {{-- Rows added dynamically --}}
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-accent py-1.5 px-3 fw-bold" onclick="addScanUploadRow()">
                        <i class="bi bi-file-earmark-plus-fill me-1"></i> ADD FILE LINE
                    </button>
                </div>
            </div>

            {{-- 5. SUBMIT ACTIONS --}}
            <div class="col-12 d-flex gap-2 mt-4 border-top border-secondary border-opacity-10 pt-3">
                <button type="submit" class="btn-custom btn-accent flex-grow-1 py-3 fw-bold uppercase">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> SAVE ALL ARCHIVED DATA
                </button>
                <button type="button" class="btn-custom btn-outline-secondary px-4 fw-bold uppercase" onclick="resetDigitizerForm()">
                    RESET
                </button>
            </div>

        </div>
    </form>
</div>

{{-- Row Template for dynamic manual parameters --}}
<template id="scanRowTemplate">
    <tr class="border-secondary border-opacity-10">
        <td>
            <input type="file" name="scans[]" class="form-control" accept="image/*, application/pdf" required>
        </td>
        <td>
            <input type="text" name="scan_labels[]" class="form-control" placeholder="e.g. Hematology Report" required>
        </td>
        <td>
            {{-- FIXED: Added optional certificate number input field to the dynamic template row --}}
            <input type="text" name="scan_cert_nos[]" class="form-control" placeholder="e.g. 01261065">
        </td>
        <td class="text-end pe-3">
            <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="bi bi-x-circle fs-5"></i></button>
        </td>
    </tr>
</template>

<script>
const digProvinceVal = "{{ $targetUser->province }}";
const digCityVal = "{{ $targetUser->city }}";
const digBrgyVal = "{{ $targetUser->barangay }}";

let scanRowIdx = 0;
const selectedProcedures = new Set();

// PSGC Address API for Digitizer Panel
async function fetchDigProvinces() {
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/`);
        const data = await res.json();
        const sel = document.getElementById('dig_province');
        sel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });
    } catch (e) { 
        console.error("Provinces API Error"); 
    }
}

async function fetchDigCities(provCode) {
    if (!provCode) return;
    const citySel = document.getElementById('dig_city');
    const brgySel = document.getElementById('dig_brgy');
    citySel.disabled = true; 
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;
    } catch (e) { 
        console.error("Cities API Error"); 
    }
    updateDigCompiledAddress();
}

async function fetchDigBarangays(cityCode) {
    if (!cityCode) return;
    const brgySel = document.getElementById('dig_brgy');
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;
    } catch (e) { 
        console.error("Barangays API Error"); 
    }
    updateDigCompiledAddress();
}

function updateDigCompiledAddress() {
    const street = document.getElementById('dig_street').value.trim();
    const brgy = document.getElementById('dig_brgy');
    const city = document.getElementById('dig_city');
    const prov = document.getElementById('dig_province');

    const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
    const cityName = city.options[city.selectedIndex]?.text || '';
    const provName = prov.options[prov.selectedIndex]?.text || '';

    if (street || brgyName || cityName || provName) {
        let parts = [];
        if (street) parts.push(street);
        if (brgyName && !brgyName.includes('Select')) parts.push('BRGY. ' + brgyName);
        if (cityName && !cityName.includes('Select')) parts.push(cityName);
        if (provName && !provName.includes('Select')) parts.push(provName);

        const compiled = parts.join(', ').toUpperCase();
        document.getElementById('dig_compiled_address_text').innerText = compiled;
        document.getElementById('dig_compiled_address_container').classList.remove('d-none');
    } else {
        document.getElementById('dig_compiled_address_container').classList.add('d-none');
    }
}

function compileDigAddress() {
    const brgy = document.getElementById('dig_brgy');
    const city = document.getElementById('dig_city');
    const prov = document.getElementById('dig_province');

    const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
    const cityName = city.options[city.selectedIndex]?.text || '';
    const provName = prov.options[prov.selectedIndex]?.text || '';

    if (brgyName && cityName && provName) {
        document.getElementById('dig_province_hidden').value = provName;
        document.getElementById('dig_city_hidden').value = cityName;
        document.getElementById('dig_barangay_hidden').value = brgyName;
    }
}

async function initializeDigAddress() {
    await fetchDigProvinces();
    if (digProvinceVal) {
        const provSel = document.getElementById('dig_province');
        let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === digProvinceVal.toUpperCase());
        if (provOpt) {
            provSel.value = provOpt.value;
            await fetchDigCities(provSel.value);
            
            const citySel = document.getElementById('dig_city');
            let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === digCityVal.toUpperCase());
            if (cityOpt) {
                citySel.value = cityOpt.value;
                await fetchDigBarangays(citySel.value);
                
                const brgySel = document.getElementById('dig_brgy');
                let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === digBrgyVal.toUpperCase());
                if (brgyOpt) {
                    brgySel.value = brgyOpt.value;
                }
            }
        }
    }
    updateDigCompiledAddress();
}

function addProcedureBadge() {
    const dropdown = document.getElementById('test_dropdown');
    const customInput = document.getElementById('custom_test_input');
    const container = document.getElementById('badges_container');
    const placeholder = document.getElementById('badges-placeholder');

    let testName = customInput.value.trim();
    if (!testName && dropdown.value !== '') {
        testName = dropdown.value;
    }

    if (!testName) return;

    if (selectedProcedures.has(testName)) {
        alert('This procedure has already been added to the list.');
        return;
    }

    if (placeholder) placeholder.classList.add('d-none');
    selectedProcedures.add(testName);

    const badgeId = 'badge_' + Math.random().toString(36).substr(2, 9);
    const badgeHtml = `
        <span class="badge border uppercase px-2.5 py-1.5 rounded d-inline-flex align-items-center gap-1.5" id="${badgeId}" style="background-color: rgba(25, 211, 140, 0.08); font-size: 0.75rem; border-color: var(--brand-accent) !important; color: var(--brand-accent) !important;">
            <span style="color: var(--brand-accent) !important; font-weight: 700;">${testName.toUpperCase()}</span>
            <input type="hidden" name="tests_requested[]" value="${testName}">
            <i class="bi bi-x cursor-pointer ms-1 text-danger" style="font-weight: 800; font-size: 1rem;" onclick="removeProcedureBadge('${badgeId}', '${testName}')"></i>
        </span>
    `;

    container.insertAdjacentHTML('beforeend', badgeHtml);
    dropdown.value = '';
    customInput.value = '';
}

function removeProcedureBadge(badgeId, testName) {
    const badge = document.getElementById(badgeId);
    if (badge) {
        badge.remove();
        selectedProcedures.delete(testName);
    }
    
    if (selectedProcedures.size === 0) {
        const placeholder = document.getElementById('badges-placeholder');
        if (placeholder) placeholder.classList.remove('d-none');
    }
}

function addScanUploadRow() {
    const template = document.getElementById('scanRowTemplate').innerHTML;
    document.getElementById('scansTableBody').insertAdjacentHTML('beforeend', template);
    scanRowIdx++;
}

function resetDigitizerForm() {
    if (confirm('Reset this digitizer form to default state?')) {
        document.getElementById('historicalImportForm').reset();
        document.getElementById('scansTableBody').innerHTML = '';
        document.getElementById('badges_container').innerHTML = '<span class="text-muted small italic" id="badges-placeholder">No procedures selected yet. Select or type above.</span>';
        selectedProcedures.clear();
        scanRowIdx = 0;
        addScanUploadRow();
        initializeDigAddress();
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    addScanUploadRow();
    await initializeDigAddress();
});
</script>