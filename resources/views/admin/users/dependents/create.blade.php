@extends('layouts.app')
@section('title', 'Add Dependent for ' . $user->name)
@section('content')
<div class="container text-start animate-page py-4" id="adminCreateDepContainer">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4 p-md-5 border-secondary bg-card shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-25 pb-3">
                    <div>
                        <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                            <i class="bi bi-person-plus-fill me-2"></i>Add Dependent for {{ $user->name }}
                        </h2>
                        <p class="text-secondary mb-0 small">Register a new minor family dependent linked directly to this account.</p>
                    </div>
                    <a href="{{ route('admin.users.edit', ['user' => $user->id, '#tab-dependents']) }}" class="btn-custom btn-cancel-custom px-4 py-2 fw-bold text-uppercase text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Back to User Edit
                    </a>
                </div>

                @if ($errors->any())
                    <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 mb-4 rounded-3">
                        <div class="d-flex align-items-center mb-1 fw-bold uppercase small">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Validation Errors
                        </div>
                        <ul class="mb-0 small ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.users.dependents.store', $user->id) }}" method="POST" id="adminCreateDepForm" onsubmit="return validateAdminCreateDepForm(event)">
                    @csrf
                    {{-- 1. Personal Identity --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase">1. Personal Identity</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control uppercase" value="{{ old('first_name') }}" required>
                            <div class="invalid-feedback d-none" id="err_first_name"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" class="form-control uppercase" value="{{ old('middle_name') }}">
                            <div class="invalid-feedback d-none" id="err_middle_name"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control uppercase" value="{{ old('last_name') }}" required>
                            <div class="invalid-feedback d-none" id="err_last_name"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
                            <input type="text" name="suffix" id="suffix" list="suffix_options" class="form-control uppercase" value="{{ old('suffix') }}" placeholder="e.g. JR">
                            <datalist id="suffix_options">
                                <option value="JR"><option value="SR"><option value="II"><option value="III"><option value="IV"><option value="V">
                            </datalist>
                            <div class="invalid-feedback d-none" id="err_suffix"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                            <input type="date" name="birthdate" id="birthdate" class="form-control" value="{{ old('birthdate') }}" required max="{{ date('Y-m-d') }}" min="{{ now()->subYears(18)->addDay()->format('Y-m-d') }}">
                            <div class="invalid-feedback d-none" id="err_birthdate"></div>
                            <small class="text-muted smaller mt-1 d-block">Dependents must be minors under 18 years of age.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
                            <select name="sex" id="sex" class="form-select" required>
                                <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_sex"></div>
                        </div>
                    </div>

                    {{-- 2. Residential Address with PSGC API Dropdowns --}}
                    <div class="border-top border-secondary border-opacity-10 pt-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-accent small fw-bold uppercase mb-0">2. Home Address (PSGC API)</h6>
                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="copyParentAddress()">
                                <i class="bi bi-geo-alt-fill me-1"></i>Copy Parent's Address
                            </button>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
                                <select id="province" name="province" class="form-select" onchange="fetchAdminDepCities(this.value)" required>
                                    <option value="">Select Province</option>
                                </select>
                                <div class="invalid-feedback d-none" id="err_province"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                                <select id="city" name="city" class="form-select" onchange="fetchAdminDepBarangays(this.value)" disabled required>
                                    <option value="">Select Province First</option>
                                </select>
                                <div class="invalid-feedback d-none" id="err_city"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
                                <select id="barangay" name="barangay" class="form-select" disabled required>
                                    <option value="">Select City First</option>
                                </select>
                                <div class="invalid-feedback d-none" id="err_barangay"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                                <input type="text" name="street" id="street" class="form-control uppercase" value="{{ old('street', $user->street) }}" required>
                                <div class="invalid-feedback d-none" id="err_street"></div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Administrative Justification --}}
                    <div class="border-top border-secondary border-opacity-10 pt-3 mb-4">
                        <h6 class="text-danger mb-2 small fw-bold uppercase"><i class="bi bi-shield-exclamation me-1"></i>3. Administrative Justification</h6>
                        <div class="mb-3">
                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Select Reason</label>
                            <select name="reason" id="reason_select" class="form-select" onchange="toggleDepReason(this.value)" required>
                                <option value="Admin registered dependent under parent account" selected>Admin registered dependent under parent account</option>
                                <option value="Official request by parent for family profile update">Official request by parent for family profile update</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_reason"></div>
                        </div>
                        <div id="reason_custom_wrapper" class="mb-3 d-none">
                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Specify Custom Reason</label>
                            <textarea id="reason_custom" class="form-control" rows="2" placeholder="Provide justification..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4 pt-3 border-top border-secondary border-opacity-25">
                        <a href="{{ route('admin.users.edit', ['user' => $user->id, '#tab-dependents']) }}" class="btn-custom btn-cancel-custom w-50 py-3 fw-bold uppercase text-decoration-none text-center">Cancel</a>
                        <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm">CREATE DEPENDENT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.btn-cancel-custom {
    color: var(--text-muted) !important;
    border: 1.5px solid var(--border-color) !important;
    background-color: transparent !important;
    transition: all 0.2s ease-in-out;
}
.btn-cancel-custom:hover, .btn-cancel-custom:focus {
    color: var(--text-main) !important;
    background-color: rgba(255, 255, 255, 0.08) !important;
    border-color: var(--border-color) !important;
    box-shadow: none !important;
}
#adminCreateDepContainer .form-control:focus,
#adminCreateDepContainer .form-select:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}
#adminCreateDepContainer .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
const parentProv = "{{ $user->province }}";
const parentCity = "{{ $user->city }}";
const parentBrgy = "{{ $user->barangay }}";
const parentStreet = "{{ $user->street }}";

async function copyParentAddress() {
    document.getElementById('street').value = parentStreet;
    const provSel = document.getElementById('province');
    let provOpt = Array.from(provSel.options).find(o => o.text.toUpperCase() === parentProv.toUpperCase());
    if (provOpt) {
        provSel.value = provOpt.value;
        await fetchAdminDepCities(provOpt.value);
        const citySel = document.getElementById('city');
        let cityOpt = Array.from(citySel.options).find(o => o.text.toUpperCase() === parentCity.toUpperCase());
        if (cityOpt) {
            citySel.value = cityOpt.value;
            await fetchAdminDepBarangays(cityOpt.value);
            const brgySel = document.getElementById('barangay');
            let brgyOpt = Array.from(brgySel.options).find(o => o.text.toUpperCase() === parentBrgy.toUpperCase());
            if (brgyOpt) brgySel.value = brgyOpt.value;
        }
    }
}

function toggleDepReason(val) {
    const wrapper = document.getElementById('reason_custom_wrapper');
    const customInput = document.getElementById('reason_custom');
    const selectEl = document.getElementById('reason_select');
    if (val === 'Others') {
        wrapper.classList.remove('d-none');
        customInput.setAttribute('required', 'required');
        customInput.setAttribute('name', 'reason');
        selectEl.removeAttribute('name');
    } else {
        wrapper.classList.add('d-none');
        customInput.removeAttribute('required');
        customInput.removeAttribute('name');
        selectEl.setAttribute('name', 'reason');
    }
}

function setFieldError(input, errDivId, message) {
    if (!input) return;
    input.classList.add('is-invalid');
    const errDiv = document.getElementById(errDivId);
    if (errDiv) {
        errDiv.innerText = message;
        errDiv.classList.add('d-block');
        errDiv.classList.remove('d-none');
    }
}

function clearFieldError(input) {
    if (!input) return;
    input.classList.remove('is-invalid');
    let errDiv = document.getElementById('err_' + input.id) || document.getElementById('err_' + input.name);
    if (errDiv) {
        errDiv.innerText = '';
        errDiv.classList.add('d-none');
        errDiv.classList.remove('d-block');
    }
}

function validateAdminCreateDepForm(e) {
    let isValid = true;
    let firstInvalidInput = null;
    document.querySelectorAll('#adminCreateDepForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#adminCreateDepForm .invalid-feedback').forEach(el => {
        el.innerText = '';
        el.classList.add('d-none');
        el.classList.remove('d-block');
    });
    function markInvalid(input, errId, msg) {
        setFieldError(input, errId, msg);
        isValid = false;
        if (!firstInvalidInput) firstInvalidInput = input;
    }
    const fName = document.getElementById('first_name');
    if (!fName || !fName.value.trim()) markInvalid(fName, 'err_first_name', 'First Name is required.');
    const lName = document.getElementById('last_name');
    if (!lName || !lName.value.trim()) markInvalid(lName, 'err_last_name', 'Last Name is required.');
    const bday = document.getElementById('birthdate');
    if (!bday || !bday.value) {
        markInvalid(bday, 'err_birthdate', 'Birthdate is required.');
    } else {
        const dob = new Date(bday.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        if (age >= 18) markInvalid(bday, 'err_birthdate', 'Administrative Policy: Dependents must be minors under 18 years of age.');
    }
    const prov = document.getElementById('province');
    const city = document.getElementById('city');
    const brgy = document.getElementById('barangay');
    const street = document.getElementById('street');
    if (!prov || !prov.value.trim()) markInvalid(prov, 'err_province', 'Province is required.');
    if (!city || !city.value.trim()) markInvalid(city, 'err_city', 'City is required.');
    if (!brgy || !brgy.value.trim()) markInvalid(brgy, 'err_barangay', 'Barangay is required.');
    if (!street || !street.value.trim()) markInvalid(street, 'err_street', 'Street address is required.');
    if (!isValid) {
        e.preventDefault();
        e.stopPropagation();
        if (firstInvalidInput) {
            firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidInput.focus();
        }
        return false;
    }
    compileAdminDepAddress();
    return true;
}

async function fetchAdminDepProvinces() {
    const provSel = document.getElementById('province');
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces/');
        const data = await res.json();
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(p => {
            provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });
    } catch(e){}
}

async function fetchAdminDepCities(provCode) {
    const citySel = document.getElementById('city');
    const brgySel = document.getElementById('barangay');
    citySel.disabled = true; brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading...</option>';
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;
    } catch(e){}
}

async function fetchAdminDepBarangays(cityCode) {
    const brgySel = document.getElementById('barangay');
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading...</option>';
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;
    } catch(e){}
}

function compileAdminDepAddress() {
    const prov = document.getElementById('province');
    const city = document.getElementById('city');
    const brgy = document.getElementById('barangay');
    if (prov && city && brgy) {
        if(prov.selectedIndex >= 0) prov.options[prov.selectedIndex].value = prov.options[prov.selectedIndex].text;
        if(city.selectedIndex >= 0) city.options[city.selectedIndex].value = city.options[city.selectedIndex].text;
        if(brgy.selectedIndex >= 0) brgy.options[brgy.selectedIndex].value = brgy.options[brgy.selectedIndex].text;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchAdminDepProvinces();
    document.querySelectorAll('#adminCreateDepForm input, #adminCreateDepForm select').forEach(input => {
        input.addEventListener('input', () => clearFieldError(input));
        input.addEventListener('change', () => clearFieldError(input));
    });
});
</script>
@endpush