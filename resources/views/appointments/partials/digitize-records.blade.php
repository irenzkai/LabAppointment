<div id="add-record-panel" class="card p-4 border-secondary shadow-lg {{ ($isOpen ?? false) ? '' : 'd-none' }}" style="background-color: var(--bg-card); color: var(--text-main); border-radius: 16px;">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h5 class="text-accent fw-bold mb-0 uppercase tracking-wider">
                <i class="bi bi-file-earmark-medical me-2"></i>DIGITIZE HISTORICAL LABORATORY RECORD
            </h5>
            <div class="text-secondary small">Clinical Compilation Console</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1.5 fw-bold uppercase" onclick="cancelDigitizerForm()" title="Close Form">
            <i class="bi bi-x-lg me-1"></i> Close
        </button>
    </div>

    {{-- Save Record Form --}}
    <form action="{{ route('history.save-manual', $targetUser->id) }}" method="POST" enctype="multipart/form-data" id="historicalImportForm" onsubmit="return validateDigitizerForm(event)">
        @csrf

        {{-- Pre-populated hidden fields to prevent validation dropouts --}}
        <input type="hidden" name="patient_province" id="dig_province_hidden" value="{{ old('patient_province', $targetUser->province) }}">
        <input type="hidden" name="patient_city" id="dig_city_hidden" value="{{ old('patient_city', $targetUser->city) }}">
        <input type="hidden" name="patient_barangay" id="dig_barangay_hidden" value="{{ old('patient_barangay', $targetUser->barangay) }}">

        <div class="row g-3 text-start">
            {{-- 1. RECORD PARAMETERS --}}
            <div class="col-md-6">
                <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Date of Original Record <span class="text-danger">*</span></label>
                <input type="date" name="date_of_record" id="dig_date_of_record" class="form-control @error('date_of_record') is-invalid @enderror" value="{{ old('date_of_record', date('Y-m-d')) }}" required max="{{ date('Y-m-d') }}">
                <div class="invalid-feedback d-none" id="err_date_of_record"></div>
                @error('date_of_record')
                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Requested By (Entity / Organization) <span class="text-danger">*</span></label>
                <input type="text" name="requested_by" id="dig_requested_by" class="form-control @error('requested_by') is-invalid @enderror" value="{{ old('requested_by', 'INDIVIDUAL') }}" required>
                <div class="invalid-feedback d-none" id="err_requested_by"></div>
                @error('requested_by')
                    <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                @enderror
            </div>

            {{-- 2. AUTO-FILLED EDITABLE PROFILE SNAPSHOT --}}
            <div class="col-12 mt-4">
                <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Verify Patient Demographic Snapshot</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="patient_first_name" id="dig_first_name" class="form-control uppercase @error('patient_first_name') is-invalid @enderror" value="{{ old('patient_first_name', $targetUser->first_name) }}" required>
                        <div class="invalid-feedback d-none" id="err_patient_first_name"></div>
                        @error('patient_first_name')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Middle Name</label>
                        <input type="text" name="patient_middle_name" id="dig_middle_name" class="form-control uppercase @error('patient_middle_name') is-invalid @enderror" value="{{ old('patient_middle_name', $targetUser->middle_name) }}">
                        <div class="invalid-feedback d-none" id="err_patient_middle_name"></div>
                        @error('patient_middle_name')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="patient_last_name" id="dig_last_name" class="form-control uppercase @error('patient_last_name') is-invalid @enderror" value="{{ old('patient_last_name', $targetUser->last_name) }}" required>
                        <div class="invalid-feedback d-none" id="err_patient_last_name"></div>
                        @error('patient_last_name')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Suffix (Opt.)</label>
                        <input type="text" name="patient_suffix" id="dig_suffix" list="suffix_options" class="form-control uppercase @error('patient_suffix') is-invalid @enderror" value="{{ old('patient_suffix', $targetUser->suffix ?? '') }}" placeholder="e.g. JR" maxlength="10">
                        <datalist id="suffix_options">
                            <option value="JR">
                            <option value="SR">
                            <option value="II">
                            <option value="III">
                            <option value="IV">
                            <option value="V">
                        </datalist>
                        <div class="invalid-feedback d-none" id="err_patient_suffix"></div>
                        @error('patient_suffix')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Age <span class="text-danger">*</span></label>
                        <input type="number" name="age" id="dig_age" class="form-control @error('age') is-invalid @enderror" value="{{ old('age', $targetUser->birthdate ? \Carbon\Carbon::parse($targetUser->birthdate)->age : '') }}" required min="0">
                        <div class="invalid-feedback d-none" id="err_age"></div>
                        @error('age')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Sex <span class="text-danger">*</span></label>
                        <select name="sex" id="dig_sex" class="form-select @error('sex') is-invalid @enderror" required>
                            <option value="Male" {{ old('sex', $targetUser->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex', $targetUser->sex) == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_sex"></div>
                        @error('sex')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Cascading Address Selection Panel --}}
            <div class="col-12 mt-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Province <span class="text-danger">*</span></label>
                        <select id="dig_province" class="form-select @error('patient_province') is-invalid @enderror" onchange="fetchDigCities(this.value)" required>
                            <option value="">Select Province</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_dig_province"></div>
                        @error('patient_province')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">City / Municipality <span class="text-danger">*</span></label>
                        <select id="dig_city" class="form-select @error('patient_city') is-invalid @enderror" onchange="fetchDigBarangays(this.value)" disabled required>
                            <option value="">Select Province First</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_dig_city"></div>
                        @error('patient_city')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Barangay <span class="text-danger">*</span></label>
                        <select id="dig_brgy" class="form-select @error('patient_barangay') is-invalid @enderror" onchange="updateDigCompiledAddress()" disabled required>
                            <option value="">Select City First</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_dig_brgy"></div>
                        @error('patient_barangay')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Street / House No. <span class="text-danger">*</span></label>
                        <input type="text" id="dig_street" name="patient_street" class="form-control uppercase @error('patient_street') is-invalid @enderror" value="{{ old('patient_street', $targetUser->street) }}" placeholder="House/Lot/Block/Street" oninput="updateDigCompiledAddress()" required>
                        <div class="invalid-feedback d-none" id="err_dig_street"></div>
                        @error('patient_street')
                            <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Live Compiled Address Preview Container --}}
                    <div class="col-12 mt-2">
                        <div id="dig_compiled_address_container" class="alert alert-clinical p-2.5 d-none text-start" style="background-color: rgba(25, 211, 140, 0.03);">
                            <small class="text-accent fw-bold uppercase d-block mb-1" style="font-size: 0.65rem;">Compiled Address Preview</small>
                            <div id="dig_compiled_address_text" class="text-main small font-monospace"></div>
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
                        <button type="button" class="btn btn-outline-accent w-100 py-2 fw-bold uppercase" onclick="addProcedureBadge()">
                            <i class="bi bi-plus-lg me-1"></i> ADD TEST
                        </button>
                    </div>
                </div>

                {{-- Rendered test badges container --}}
                <div class="d-flex flex-wrap gap-2 p-3 border border-secondary border-opacity-15 rounded-3 mb-1" id="badges_container" style="background-color: rgba(0,0,0,0.01);">
                    <span class="text-muted small italic" id="badges-placeholder">No procedures selected yet. Select or type above.</span>
                </div>
                <div class="invalid-feedback d-none mb-3" id="err_tests_requested"></div>
                @error('tests_requested')
                    <div class="text-danger small mb-3 fw-bold">{{ $message }}</div>
                @enderror
            </div>

            {{-- 4. ATTACH SCANS & MANUALLY LABEL --}}
            <div class="col-12 mt-4">
                <h6 class="text-accent mb-2 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Results Document Uploads (PDF / Images Only)</h6>
                <p class="text-secondary small mb-3">Upload physical documents and assign descriptive titles (e.g. "Urinalysis PDF", "Hematology Image") and required Certificate Numbers.</p>

                <div class="table-responsive border border-secondary border-opacity-25 rounded-3 mb-1">
                    <table class="table align-middle mb-0" style="color: var(--text-main);">
                        <thead class="text-secondary small uppercase" style="background-color: rgba(0, 0, 0, 0.02); border-bottom: 1.5px solid var(--border-color);">
                            <tr>
                                <th>Upload File <span class="text-danger">*</span></th>
                                <th style="width: 30%;">Assign Label <span class="text-danger">*</span></th>
                                <th style="width: 28%;">Certificate Number <span class="text-danger">*</span></th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="scansTableBody">
                            {{-- Rows added dynamically --}}
                        </tbody>
                    </table>
                </div>
                <div class="invalid-feedback d-none mb-3" id="err_scans"></div>
                @error('scans')
                    <div class="text-danger small mb-2 fw-bold">{{ $message }}</div>
                @enderror
                @error('scan_cert_nos')
                    <div class="text-danger small mb-2 fw-bold">{{ $message }}</div>
                @enderror
                @error('scan_cert_nos.*')
                    <div class="text-danger small mb-2 fw-bold">{{ $message }}</div>
                @enderror

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <button type="button" class="btn btn-sm btn-outline-accent py-1.5 px-3 fw-bold uppercase" onclick="addScanUploadRow()">
                        <i class="bi bi-file-earmark-plus-fill me-1"></i> ADD FILE LINE
                    </button>
                </div>
            </div>

            {{-- 5. SUBMIT ACTIONS --}}
            <div class="col-12 d-flex flex-wrap gap-2 mt-4 border-top border-secondary border-opacity-10 pt-3">
                <button type="submit" class="btn-custom btn-accent flex-grow-1 py-2.5 fw-bold uppercase shadow-sm">
                    <i class="bi bi-cloud-arrow-up-fill me-1.5"></i> SAVE ALL ARCHIVED DATA
                </button>
                <button type="button" class="btn-custom btn-outline-secondary px-4 py-2.5 fw-bold uppercase" onclick="resetDigitizerForm()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> RESET
                </button>
                <button type="button" class="btn-custom btn-outline-danger px-4 py-2.5 fw-bold uppercase" onclick="cancelDigitizerForm()">
                    <i class="bi bi-x-lg me-1"></i> CANCEL
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Row Template for dynamic manual parameters (Cert Number is required) --}}
<template id="scanRowTemplate">
    <tr class="border-secondary border-opacity-10">
        <td>
            <input type="file" name="scans[]" class="form-control" accept="image/*, application/pdf" required>
        </td>
        <td>
            <input type="text" name="scan_labels[]" class="form-control" placeholder="e.g. Hematology Report" required>
        </td>
        <td>
            <input type="text" name="scan_cert_nos[]" class="form-control uppercase" placeholder="e.g. CERT-2026-0042" required>
        </td>
        <td class="text-end pe-3">
            <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="bi bi-x-circle fs-5"></i></button>
        </td>
    </tr>
</template>

@push('styles')
<style>
/* Inline Error Highlights */
#historicalImportForm .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
#historicalImportForm .form-control:focus,
#historicalImportForm .form-select:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
const digProvinceVal = "{{ $targetUser->province }}";
const digCityVal = "{{ $targetUser->city }}";
const digBrgyVal = "{{ $targetUser->barangay }}";
const draftStorageKey = "medscreen_digitize_draft_{{ $targetUser->id }}";

let scanRowIdx = 0;
const selectedProcedures = new Set();
let digAddressInitialized = false;

// --- DRAFT AUTOSAVE & RESTORE ---
function saveDigitizeDraft() {
    const draft = {
        date_of_record: document.getElementById('dig_date_of_record')?.value || '',
        requested_by: document.getElementById('dig_requested_by')?.value || '',
        first_name: document.getElementById('dig_first_name')?.value || '',
        middle_name: document.getElementById('dig_middle_name')?.value || '',
        last_name: document.getElementById('dig_last_name')?.value || '',
        suffix: document.getElementById('dig_suffix')?.value || '',
        age: document.getElementById('dig_age')?.value || '',
        sex: document.getElementById('dig_sex')?.value || '',
        street: document.getElementById('dig_street')?.value || '',
        procedures: Array.from(selectedProcedures)
    };
    try {
        sessionStorage.setItem(draftStorageKey, JSON.stringify(draft));
    } catch (e) {}
}

function restoreDigitizeDraft() {
    try {
        const raw = sessionStorage.getItem(draftStorageKey);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (draft.date_of_record) document.getElementById('dig_date_of_record').value = draft.date_of_record;
        if (draft.requested_by) document.getElementById('dig_requested_by').value = draft.requested_by;
        if (draft.first_name) document.getElementById('dig_first_name').value = draft.first_name;
        if (draft.middle_name) document.getElementById('dig_middle_name').value = draft.middle_name;
        if (draft.last_name) document.getElementById('dig_last_name').value = draft.last_name;
        if (draft.suffix) document.getElementById('dig_suffix').value = draft.suffix;
        if (draft.age) document.getElementById('dig_age').value = draft.age;
        if (draft.sex) document.getElementById('dig_sex').value = draft.sex;
        if (draft.street) document.getElementById('dig_street').value = draft.street;

        if (Array.isArray(draft.procedures)) {
            draft.procedures.forEach(p => {
                if (!selectedProcedures.has(p)) {
                    addProcedureBadgeByName(p);
                }
            });
        }
    } catch (e) {}
}

function clearDigitizeDraft() {
    try {
        sessionStorage.removeItem(draftStorageKey);
    } catch (e) {}
}

// PSGC Address API for Digitizer Panel (Lazy loaded on form demand)
async function fetchDigProvinces() {
    const sel = document.getElementById('dig_province');
    if (!sel || sel.options.length > 1) return;
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        sel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });
    } catch (e) {
        if (digProvinceVal && sel) {
            sel.innerHTML = `<option value="${digProvinceVal}" selected>${digProvinceVal}</option>`;
        }
    }
}

async function fetchDigCities(provCode) {
    if (!provCode) return;
    const citySel = document.getElementById('dig_city');
    const brgySel = document.getElementById('dig_brgy');
    if (!citySel || !brgySel) return;
    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;
    } catch (e) {
        if (digCityVal) {
            citySel.innerHTML = `<option value="${digCityVal}" selected>${digCityVal}</option>`;
            citySel.disabled = false;
        }
    }
    updateDigCompiledAddress();
    saveDigitizeDraft();
}

async function fetchDigBarangays(cityCode) {
    if (!cityCode) return;
    const brgySel = document.getElementById('dig_brgy');
    if (!brgySel) return;
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;
    } catch (e) {
        if (digBrgyVal) {
            brgySel.innerHTML = `<option value="${digBrgyVal}" selected>${digBrgyVal}</option>`;
            brgySel.disabled = false;
        }
    }
    updateDigCompiledAddress();
    saveDigitizeDraft();
}

function updateDigCompiledAddress() {
    const streetInput = document.getElementById('dig_street');
    const brgy = document.getElementById('dig_brgy');
    const city = document.getElementById('dig_city');
    const prov = document.getElementById('dig_province');

    if (!streetInput || !brgy || !city || !prov) return;

    const street = streetInput.value.trim();
    const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
    const cityName = city.options[city.selectedIndex]?.text || '';
    const provName = prov.options[prov.selectedIndex]?.text || '';

    if (street || brgyName || cityName || provName) {
        let parts = [];
        if (street) parts.push(street);
        if (brgyName && !brgyName.includes('Select') && !brgyName.includes('Loading')) parts.push('BRGY. ' + brgyName);
        if (cityName && !cityName.includes('Select') && !cityName.includes('Loading')) parts.push(cityName);
        if (provName && !provName.includes('Select') && !provName.includes('Loading')) parts.push(provName);

        const compiled = parts.join(', ').toUpperCase();
        const textEl = document.getElementById('dig_compiled_address_text');
        const contEl = document.getElementById('dig_compiled_address_container');
        if (textEl) textEl.innerText = compiled;
        if (contEl) contEl.classList.remove('d-none');
    }
}

function compileDigAddress() {
    const brgy = document.getElementById('dig_brgy');
    const city = document.getElementById('dig_city');
    const prov = document.getElementById('dig_province');

    const brgyName = brgy ? (brgy.options[brgy.selectedIndex]?.text || '') : '';
    const cityName = city ? (city.options[city.selectedIndex]?.text || '') : '';
    const provName = prov ? (prov.options[prov.selectedIndex]?.text || '') : '';

    if (brgyName && !brgyName.includes('Select') && cityName && !cityName.includes('Select') && provName && !provName.includes('Select')) {
        document.getElementById('dig_province_hidden').value = provName;
        document.getElementById('dig_city_hidden').value = cityName;
        document.getElementById('dig_barangay_hidden').value = brgyName;
    }
}

async function initializeDigAddress() {
    if (digAddressInitialized) return;
    digAddressInitialized = true;
    await fetchDigProvinces();
    if (digProvinceVal) {
        const provSel = document.getElementById('dig_province');
        if (provSel) {
            let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === digProvinceVal.toUpperCase());
            if (provOpt) {
                provSel.value = provOpt.value;
                await fetchDigCities(provSel.value);

                const citySel = document.getElementById('dig_city');
                if (citySel) {
                    let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === digCityVal.toUpperCase());
                    if (cityOpt) {
                        citySel.value = cityOpt.value;
                        await fetchDigBarangays(citySel.value);

                        const brgySel = document.getElementById('dig_brgy');
                        if (brgySel) {
                            let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === digBrgyVal.toUpperCase());
                            if (brgyOpt) {
                                brgySel.value = brgyOpt.value;
                            }
                        }
                    }
                }
            }
        }
    }
    updateDigCompiledAddress();
}
window.initializeDigAddress = initializeDigAddress;

function addProcedureBadgeByName(testName) {
    if (!testName || selectedProcedures.has(testName)) return;
    const container = document.getElementById('badges_container');
    const placeholder = document.getElementById('badges-placeholder');
    if (!container) return;

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
    clearDigFieldError(container);
    saveDigitizeDraft();
}

function addProcedureBadge() {
    const dropdown = document.getElementById('test_dropdown');
    const customInput = document.getElementById('custom_test_input');
    let testName = customInput?.value.trim() || '';
    if (!testName && dropdown && dropdown.value !== '') {
        testName = dropdown.value;
    }
    if (!testName) return;

    if (selectedProcedures.has(testName)) {
        alert('This procedure has already been added to the list.');
        return;
    }

    addProcedureBadgeByName(testName);
    if (dropdown) dropdown.value = '';
    if (customInput) customInput.value = '';
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
    saveDigitizeDraft();
}

function addScanUploadRow() {
    const template = document.getElementById('scanRowTemplate');
    const body = document.getElementById('scansTableBody');
    if (!template || !body) return;
    body.insertAdjacentHTML('beforeend', template.innerHTML);
    scanRowIdx++;
    clearDigFieldError(body);
}
window.addScanUploadRow = addScanUploadRow;

function resetDigitizerForm() {
    if (confirm('Reset this digitizer form to default state?')) {
        document.getElementById('historicalImportForm').reset();
        document.getElementById('scansTableBody').innerHTML = '';
        document.getElementById('badges_container').innerHTML = '<span class="text-muted small italic" id="badges-placeholder">No procedures selected yet. Select or type above.</span>';
        selectedProcedures.clear();
        scanRowIdx = 0;
        clearDigitizeDraft();
        addScanUploadRow();
        digAddressInitialized = false;
        initializeDigAddress();
        
        document.querySelectorAll('#historicalImportForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('#historicalImportForm .invalid-feedback').forEach(el => {
            el.innerText = '';
            el.classList.add('d-none');
            el.classList.remove('d-block');
        });
    }
}

function cancelDigitizerForm() {
    const addRecordPanel = document.getElementById('add-record-panel');
    if (addRecordPanel) {
        addRecordPanel.classList.add('d-none');
    }
    const recordPlaceholder = document.getElementById('record-placeholder');
    if (recordPlaceholder) {
        recordPlaceholder.classList.remove('d-none');
    }
    clearDigitizeDraft();
    try {
        sessionStorage.removeItem("archive_action_{{ $targetUser->id }}");
        const url = new URL(window.location.href);
        url.searchParams.delete('action');
        history.replaceState(null, null, url.toString());
    } catch (e) {}
}
window.cancelDigitizerForm = cancelDigitizerForm;

// Inline Field Error Helpers
function setDigFieldError(input, errDivId, message) {
    if (!input) return;
    input.classList.add('is-invalid');
    const errDiv = document.getElementById(errDivId);
    if (errDiv) {
        errDiv.innerText = message;
        errDiv.classList.add('d-block');
        errDiv.classList.remove('d-none');
    }
}

function clearDigFieldError(input) {
    if (!input) return;
    input.classList.remove('is-invalid');
    let errDiv = document.getElementById('err_' + input.id) || document.getElementById('err_' + input.name);
    if (errDiv) {
        errDiv.innerText = '';
        errDiv.classList.add('d-none');
        errDiv.classList.remove('d-block');
    }
}

function validateDigitizerForm(e) {
    let isValid = true;
    let firstInvalidInput = null;

    document.querySelectorAll('#historicalImportForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#historicalImportForm .invalid-feedback').forEach(el => {
        el.innerText = '';
        el.classList.add('d-none');
        el.classList.remove('d-block');
    });

    function markInvalid(input, errId, msg) {
        setDigFieldError(input, errId, msg);
        isValid = false;
        if (!firstInvalidInput) firstInvalidInput = input;
    }

    const dateRecord = document.getElementById('dig_date_of_record');
    if (!dateRecord || !dateRecord.value) markInvalid(dateRecord, 'err_date_of_record', 'Date of Original Record is required.');

    const requestedBy = document.getElementById('dig_requested_by');
    if (!requestedBy || !requestedBy.value.trim()) markInvalid(requestedBy, 'err_requested_by', 'Requested By field is required.');

    const fName = document.getElementById('dig_first_name');
    if (!fName || !fName.value.trim()) markInvalid(fName, 'err_patient_first_name', 'First Name is required.');

    const lName = document.getElementById('dig_last_name');
    if (!lName || !lName.value.trim()) markInvalid(lName, 'err_patient_last_name', 'Last Name is required.');

    const suffix = document.getElementById('dig_suffix');
    if (suffix && suffix.value.trim()) {
        const suffixRegex = /^[a-zA-Z0-9\s.]+$/;
        if (!suffixRegex.test(suffix.value.trim())) markInvalid(suffix, 'err_patient_suffix', 'Suffix may only contain letters, numbers, spaces, and periods.');
        else if (suffix.value.trim().length > 10) markInvalid(suffix, 'err_patient_suffix', 'Suffix cannot exceed 10 characters.');
    }

    const age = document.getElementById('dig_age');
    if (!age || age.value === '' || parseInt(age.value) < 0) markInvalid(age, 'err_age', 'Valid age is required.');

    const sex = document.getElementById('dig_sex');
    if (!sex || !sex.value) markInvalid(sex, 'err_sex', 'Sex selection is required.');

    const prov = document.getElementById('dig_province');
    const city = document.getElementById('dig_city');
    const brgy = document.getElementById('dig_brgy');
    const street = document.getElementById('dig_street');

    if (!prov || !prov.value) markInvalid(prov, 'err_dig_province', 'Province selection is required.');
    if (!city || !city.value) markInvalid(city, 'err_dig_city', 'City selection is required.');
    if (!brgy || !brgy.value) markInvalid(brgy, 'err_dig_brgy', 'Barangay selection is required.');
    if (!street || !street.value.trim()) markInvalid(street, 'err_dig_street', 'Street address is required.');

    if (selectedProcedures.size === 0) {
        const badgeContainer = document.getElementById('badges_container');
        markInvalid(badgeContainer, 'err_tests_requested', 'At least one procedure/test must be selected.');
    }

    const rows = document.querySelectorAll('#scansTableBody tr');
    if (rows.length === 0) {
        const scansTable = document.getElementById('scansTableBody');
        markInvalid(scansTable, 'err_scans', 'At least one file upload line is required.');
    } else {
        rows.forEach((tr, index) => {
            const fileInput = tr.querySelector('input[type="file"]');
            const labelInput = tr.querySelector('input[name="scan_labels[]"]');
            const certInput = tr.querySelector('input[name="scan_cert_nos[]"]');

            if (fileInput && !fileInput.files.length) {
                markInvalid(fileInput, 'err_scans', `File line ${index + 1} requires a file.`);
            }
            if (labelInput && !labelInput.value.trim()) {
                markInvalid(labelInput, 'err_scans', `File line ${index + 1} requires a label.`);
            }
            if (certInput && !certInput.value.trim()) {
                markInvalid(certInput, 'err_scans', `File line ${index + 1} requires a Certificate Number.`);
            }
        });
    }

    if (!isValid) {
        e.preventDefault();
        e.stopPropagation();
        if (firstInvalidInput) {
            firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidInput.focus();
        }
        return false;
    }

    clearDigitizeDraft();
    compileDigAddress();
    return true;
}

// Ensure execution waits until scripts stack runs
window.addEventListener('load', () => {
    if (document.querySelectorAll('#scansTableBody tr').length === 0) {
        addScanUploadRow();
    }

    // Restore draft if saved
    restoreDigitizeDraft();

    // Attach real-time error dismissal and auto-save listeners
    document.querySelectorAll('#historicalImportForm input, #historicalImportForm select').forEach(input => {
        input.addEventListener('input', () => {
            clearDigFieldError(input);
            saveDigitizeDraft();
        });
        input.addEventListener('change', () => {
            clearDigFieldError(input);
            saveDigitizeDraft();
        });
    });
});
</script>
@endpush