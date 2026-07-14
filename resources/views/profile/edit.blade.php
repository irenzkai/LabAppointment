@extends('layouts.app')

@section('content')
<div class="container text-start animate-page" id="settings-page-wrapper">
    
    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-5 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo" style="height: 50px; width: 50px; border-radius: 50%;">
        <h2 class="text-accent fw-bold mb-0 uppercase" style="letter-spacing: 2px;">ACCOUNT SETTINGS</h2>
    </div>

    {{-- Split Pane Settings Layout --}}
    <div class="row g-4">
        
        {{-- LEFT PANEL: Selection Sidebar --}}
        <div class="col-lg-4 col-xl-3">
            <div class="card p-3 border-secondary bg-card shadow-sm sticky-top settings-sidebar-container" style="top: 100px;">
                <h6 class="text-muted fw-800 uppercase tracking-widest mb-3 fs-x-small">Settings Panel</h6>
                <div class="list-group list-group-flush settings-sidebar gap-1" id="settingsTabs" role="tablist">
                    <button class="list-group-item active d-flex align-items-center gap-2 text-start" id="btn-personal" data-bs-toggle="pill" data-bs-target="#tab-personal">
                        <i class="bi bi-person-circle fs-5"></i> Personal Info
                    </button>
                    <button class="list-group-item d-flex align-items-center gap-2 text-start" id="btn-password" data-bs-toggle="pill" data-bs-target="#tab-password">
                        <i class="bi bi-shield-lock fs-5"></i> Update Password
                    </button>
                    <button class="list-group-item d-flex align-items-center gap-2 text-start" id="btn-dependents" data-bs-toggle="pill" data-bs-target="#tab-dependents">
                        <i class="bi bi-people fs-5"></i> Family Dependents
                    </button>
                    <button class="list-group-item d-flex align-items-center gap-2 text-start text-danger" id="btn-danger" data-bs-toggle="pill" data-bs-target="#tab-danger">
                        <i class="bi bi-exclamation-triangle fs-5"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: Active Setting Form Content (Includes Partials) --}}
        <div class="col-lg-8 col-xl-9">
            <div class="tab-content" id="settingsContent">
                
                {{-- Tab 1: Personal Info --}}
                <div class="tab-pane fade show active" id="tab-personal">
                    @include('profile.partials.update-profile-information-form')
                </div>

                {{-- Tab 2: Security --}}
                <div class="tab-pane fade" id="tab-password">
                    @include('profile.partials.update-password-form')
                </div>

                {{-- Tab 3: Dependents --}}
                <div class="tab-pane fade" id="tab-dependents">
                    @include('profile.partials.manage-dependents-form')
                </div>

                {{-- Tab 4: Danger Zone --}}
                <div class="tab-pane fade" id="tab-danger">
                    @include('profile.partials.delete-user-form')
                </div>

            </div>
        </div>
 
    </div>
</div>

{{-- MODALS SECTION --}}

{{-- A. ADD DEPENDENT RECORD MODAL --}}
<div class="modal fade" id="addDepModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('dependents.store') }}" method="POST" id="addDependentForm" class="modal-content border-secondary bg-card shadow-lg" onsubmit="compileDependentAddress()">
            @csrf
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-main fw-bold small">ADD FAMILY MEMBER RECORD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 text-start" style="color: var(--text-main);">
                
                {{-- 1. Split Name Fields (1NF/3NF Atomic) --}}
                <h6 class="text-accent mb-3 small fw-bold uppercase">Personal Identity</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">First Name</label>
                        <input type="text" name="first_name" class="form-control uppercase" placeholder="Given Name" required>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="text-secondary smaller fw-bold mb-0 uppercase" style="color: var(--text-muted);">Middle Name</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="dep_no_mn" onclick="toggleDepMN(this)">
                                <label class="smaller text-secondary" style="font-size: 0.6rem;" for="dep_no_mn">None</label>
                            </div>
                        </div>
                        <input type="text" name="middle_name" id="dep_middle_name" class="form-control uppercase" placeholder="Middle Name">
                    </div>
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Last Name</label>
                        <input type="text" name="last_name" class="form-control uppercase" placeholder="Surname" required>
                    </div>
                </div>

                {{-- 2. Demographics --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Birthdate</label>
                        <input type="date" name="birthdate" class="form-control" required max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Relationship</label>
                        <input type="text" name="relationship" class="form-control" placeholder="e.g. Son, Daughter, Spouse" required>
                    </div>
                </div>

                {{-- 3. Address Section (PSGC API Integrated) --}}
                <div class="mb-3 border-top border-secondary border-opacity-25 pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-accent small fw-bold uppercase mb-0">Home Address</h6>
                        <div class="form-check form-switch">
                            {{-- FIXED: Changed ID to addUseMyAddress to guarantee global uniqueness across the entire page DOM --}}
                            <input class="form-check-input" type="checkbox" name="inherit_address" id="addUseMyAddress" value="1" onchange="toggleDependentAddress(this)" checked>
                            <label class="form-check-label text-neon fw-bold" style="font-size: 0.6rem;" for="addUseMyAddress">INHERIT MY ADDRESS</label>
                        </div>
                    </div>

                    {{-- Inherited Parent Address Preview Box --}}
                    <div id="add_inherited_address_preview" class="alert alert-clinical p-2.5 mb-3 border border-neon border-opacity-25 text-start" style="background-color: rgba(25, 211, 140, 0.03);">
                        <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Inherited Parent Address:</div>
                        <div class="text-main small">{{ $user->address }}</div>
                    </div>

                    {{-- Dynamic cascading selects, revealed if "Inherit my address" is untoggled --}}
                    <div id="manual_dep_address_wrapper" class="row g-3 d-none">
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Province</label>
                            <select id="dep_province" name="province" class="form-select" onchange="fetchDepCities(this.value)">
                                <option value="">Select Province</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">City / Municipality</label>
                            <select id="dep_city" name="city" class="form-select" onchange="fetchDepBarangays(this.value)" disabled>
                                <option value="">Select Province First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Barangay</label>
                            <select id="dep_brgy" name="barangay" class="form-select" disabled>
                                <option value="">Select City First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Street / House No.</label>
                            <input type="text" id="dep_street" name="street" class="form-control uppercase" placeholder="House/Lot/Block/Street">
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3">
                <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase">SAVE TO FAMILY LIST</button>
            </div>
        </form>
    </div>
</div>

{{-- B. SELF ACCOUNT PERMANENT DELETION MODAL --}}
<div class="modal fade" id="confirmSelfDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger bg-card shadow-lg">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                <div class="modal-header border-danger border-bottom border-secondary border-opacity-10 p-3">
                    <h5 class="modal-title text-danger fw-bold uppercase small" style="letter-spacing: 0.5px;">Confirm Permanent Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="text-main small mb-4 text-center">Please enter your password to confirm account deletion.</p>
                    <label class="smaller text-secondary fw-bold mb-2 uppercase">Password</label>
                    <div class="password-container position-relative">
                        <input type="password" name="password" id="del_pass" class="form-control border-danger" placeholder="Enter password" required>
                        <i class="bi bi-eye password-toggle text-danger" id="toggleDelPass" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                    </div>
                </div>
                <div class="modal-footer border-danger border-top border-secondary border-opacity-10 p-3">
                    <button type="button" class="btn-custom btn-outline-secondary py-2 px-3" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">DELETE NOW</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Settings Sidebar customized overrides */
.settings-sidebar .list-group-item {
    color: var(--text-muted);
    border: 1px solid transparent;
    background-color: transparent;
    border-radius: 8px !important;
    padding: 12px 18px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
}
.settings-sidebar .list-group-item:hover {
    color: var(--brand-accent);
    background-color: rgba(25, 211, 140, 0.05);
}
.settings-sidebar .list-group-item.active {
    color: #1c232d !important;
    background-color: var(--brand-accent) !important;
    font-weight: 700;
}
.settings-sidebar .list-group-item.text-danger:hover {
    color: #ff0000 !important;
    background-color: rgba(255, 77, 77, 0.05) !important;
}
.settings-sidebar .list-group-item.text-danger.active {
    color: #ffffff !important;
    background-color: #8a0808 !important;
}
.password-container input {
    padding-right: 45px;
}
</style>
@endsection

@push('scripts')
<script>
// Dynamic Middle-Name Toggle logic for Dependents form
function toggleDepMN(checkbox) {
    const input = document.getElementById('dep_middle_name');
    if (input) {
        if (checkbox.checked) {
            input.value = "N/A";
            input.readOnly = true;
            input.classList.add('opacity-50');
        } else {
            input.value = "";
            input.readOnly = false;
            input.classList.remove('opacity-50');
        }
    }
}

// FIXED: Defensively coded globally-hoisted toggle function (Safe from element null checks)
function toggleDependentAddress(checkbox) {
    console.log("toggleDependentAddress triggered. Checked State:", checkbox.checked);
    
    const wrapper = document.getElementById('manual_dep_address_wrapper');
    const preview = document.getElementById('add_inherited_address_preview');
    const province = document.getElementById('dep_province');
    const city = document.getElementById('dep_city');
    const brgy = document.getElementById('dep_brgy');
    const street = document.getElementById('dep_street');

    // Soft-conditional check to ensure NO Javascript exceptions can ever halt the page
    if (checkbox.checked) {
        if (wrapper) wrapper.classList.add('d-none');
        if (preview) preview.classList.remove('d-none');
        if (province) province.removeAttribute('required');
        if (city) city.removeAttribute('required');
        if (brgy) brgy.removeAttribute('required');
        if (street) street.removeAttribute('required');
    } else {
        if (wrapper) wrapper.classList.remove('d-none');
        if (preview) preview.classList.add('d-none');
        if (province) province.setAttribute('required', 'required');
        if (city) city.setAttribute('required', 'required');
        if (brgy) brgy.setAttribute('required', 'required');
        if (street) street.setAttribute('required', 'required');
        
        // Lazy-load lookup list of provinces from API only when needed
        if (province && province.options.length <= 1) {
            fetchDepProvinces();
        }
    }
}

// Compiles selected Dependent PSGC inputs to textual names before submitting
function compileDependentAddress() {
    const inheritToggle = document.getElementById('addUseMyAddress');
    if (inheritToggle && inheritToggle.checked) return;

    const street = document.getElementById('dep_street');
    const brgy = document.getElementById('dep_brgy');
    const city = document.getElementById('dep_city');
    const prov = document.getElementById('dep_province');

    if (street && brgy && city && prov) {
        const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
        const cityName = city.options[city.selectedIndex]?.text || '';
        const provName = prov.options[prov.selectedIndex]?.text || '';

        // Overwrite numerical value with literal string representation before form submission
        if (street.value && brgyName && cityName && provName) {
            prov.options[prov.selectedIndex].value = provName;
            city.options[city.selectedIndex].value = cityName;
        }
    }
}

// --- DEPUTY PSGC ADDRESS API (No Global Const collision) ---
async function fetchDepProvinces() {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces/');
        const data = await res.json();
        const sel = document.getElementById('dep_province');
        if (sel) {
            sel.innerHTML = '<option value="">Select Province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
            });
        }
    } catch (e) { console.error("Dependent Province API Error", e); }
}

async function fetchDepCities(provCode) {
    const citySel = document.getElementById('dep_city');
    const brgySel = document.getElementById('dep_brgy');
    if (!citySel || !brgySel) return;

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
    } catch (e) { console.error("Dependent City API Error", e); }
}

async function fetchDepBarangays(cityCode) {
    const brgySel = document.getElementById('dep_brgy');
    if (!brgySel) return;

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
    } catch (e) { console.error("Dependent Barangay API Error", e); }
}

// --- EDIT DEPENDENTS PSGC LOGIC ---
function toggleEditDepMN(depId, checkbox) {
    const input = document.getElementById(`edit_dep_middle_name_${depId}`);
    if (input) {
        if (checkbox.checked) {
            input.value = "N/A";
            input.readOnly = true;
            input.classList.add('opacity-50');
        } else {
            input.value = "";
            input.readOnly = false;
            input.classList.remove('opacity-50');
        }
    }
}

// FIXED: Scoped selector update to toggle both the selects and the inherited address preview card safely
function toggleEditDepAddress(depId, checkbox) {
    console.log(`toggleEditDepAddress triggered for Dependent #${depId}. Checked:`, checkbox.checked);

    const wrapper = document.getElementById(`manual_edit_dep_address_wrapper_${depId}`);
    const preview = document.getElementById(`edit_inherited_address_preview_${depId}`);
    const province = document.getElementById(`edit_dep_province_${depId}`);
    const city = document.getElementById(`edit_dep_city_${depId}`);
    const brgy = document.getElementById(`edit_dep_brgy_${depId}`);
    const street = document.getElementById(`edit_dep_street_${depId}`);

    if (checkbox.checked) {
        if (wrapper) wrapper.classList.add('d-none');
        if (preview) preview.classList.remove('d-none');
        if (province) province.removeAttribute('required');
        if (city) city.removeAttribute('required');
        if (brgy) brgy.removeAttribute('required');
        if (street) street.removeAttribute('required');
    } else {
        if (wrapper) wrapper.classList.remove('d-none');
        if (preview) preview.classList.add('d-none');
        if (province) province.setAttribute('required', 'required');
        if (city) city.setAttribute('required', 'required');
        if (brgy) brgy.setAttribute('required', 'required');
        if (street) street.setAttribute('required', 'required');
        
        if (province && province.options.length <= 1) {
            fetchEditDepProvinces(depId);
        }
    }
}

async function fetchEditDepProvinces(depId) {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces/');
        const data = await res.json();
        const sel = document.getElementById(`edit_dep_province_${depId}`);
        if (sel) {
            sel.innerHTML = '<option value="">Select Province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
            });
        }
    } catch (e) { console.error("Edit Dependent Province API Error", e); }
}

async function fetchEditDepCities(depId, provCode) {
    const citySel = document.getElementById(`edit_dep_city_${depId}`);
    const brgySel = document.getElementById('edit_dep_brgy_' + depId);
    if (!citySel || !brgySel) return;

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
    } catch (e) { console.error("Edit Dependent City API Error", e); }
}

async function fetchEditDepBarangays(depId, cityCode) {
    const brgySel = document.getElementById(`edit_dep_brgy_${depId}`);
    if (!brgySel) return;

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
    } catch (e) { console.error("Edit Dependent Barangay API Error", e); }
}

function compileEditDepAddress(depId) {
    const inheritToggle = document.getElementById(`editUseMyAddress${depId}`);
    if (inheritToggle && inheritToggle.checked) return;

    const street = document.getElementById(`edit_dep_street_${depId}`);
    const brgy = document.getElementById(`edit_dep_brgy_${depId}`);
    const city = document.getElementById(`edit_dep_city_${depId}`);
    const prov = document.getElementById(`edit_dep_province_${depId}`);

    if (street && brgy && city && prov) {
        const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
        const cityName = city.options[city.selectedIndex]?.text || '';
        const provName = prov.options[prov.selectedIndex]?.text || '';

        if (street.value && brgyName && cityName && provName) {
            prov.options[prov.selectedIndex].value = provName;
            city.options[city.selectedIndex].value = cityName;
        }
    }
}

// --- INITIALIZE ALL LISTENERS SECURELY ON DOM LOAD ---
document.addEventListener('DOMContentLoaded', () => {
    // Eye toggles for password and deletion elements
    setupPasswordToggle('#curr_pass', '#toggleCurrPass');
    setupPasswordToggle('#new_pass', '#toggleNewPass');
    setupPasswordToggle('#conf_pass', '#toggleConfPass');
    setupPasswordToggle('#del_pass', '#toggleDelPass');

    // --- ACTIVE TAB ROUTER (Focuses correct settings panel based on URL hash) ---
    const hash = window.location.hash;
    if (hash) {
        const tabBtn = document.querySelector(`[data-bs-target="${hash}"]`);
        if (tabBtn) {
            const tab = new bootstrap.Tab(tabBtn);
            tab.show();
        }
    }
});
</script>
@endpush