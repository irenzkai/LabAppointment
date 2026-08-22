@extends('layouts.app')
@section('title', 'Add Family Dependent')
@section('content')
<div class="container text-start animate-page py-4" id="create-dependent-root">
 <div class="row justify-content-center">
 <div class="col-lg-8">
 <div class="card p-4 p-md-5 border-secondary bg-card shadow-lg">
 {{-- Header Bar --}}
 <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
 <div>
 <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
 <i class="bi bi-person-plus-fill me-2"></i>Add Child / Dependent
 </h2>
 <p class="text-secondary mb-0 small">Register a minor dependent (under 18 years of age) under your account.</p>
 </div>
 <a href="{{ route('profile.edit') }}#tab-dependents" class="btn-custom btn-cancel-custom px-4 py-2 fw-bold text-uppercase text-decoration-none">
 <i class="bi bi-arrow-left me-1"></i> Back to Dependents
 </a>
 </div>
 {{-- Add Dependent Form --}}
 <form action="{{ route('dependents.store') }}" method="POST" id="addDependentForm" onsubmit="return validateAddDependentForm(event)" novalidate>
 @csrf
 {{-- 1. Personal Identity --}}
 <h6 class="text-accent mb-3 small fw-bold uppercase">1. Personal Identity</h6>
 <div class="row g-3 mb-4">
 {{-- First Name --}}
 <div class="col-md-3">
 <label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
 <input type="text" name="first_name" id="in_first_name" class="form-control uppercase @error('first_name') is-invalid @enderror" placeholder="Given Name" value="{{ old('first_name') }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑ\s.\'-]/g, '')" required>
 <div class="invalid-feedback d-none" id="err_first_name"></div>
 @error('first_name')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 {{-- Middle Name --}}
 <div class="col-md-3">
 <div class="d-flex justify-content-between align-items-center mb-1">
 <label class="small text-secondary fw-bold mb-0 uppercase">Middle Name</label>
 <div class="form-check form-switch mb-0">
 <input class="form-check-input" type="checkbox" id="dep_no_mn" onclick="toggleDepMN(this)">
 <label class="smaller text-secondary" style="font-size: 0.65rem;" for="dep_no_mn">None</label>
 </div>
 </div>
 <input type="text" name="middle_name" id="dep_middle_name" class="form-control uppercase @error('middle_name') is-invalid @enderror" placeholder="Middle Name" value="{{ old('middle_name') }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑ\s.\'-]/g, '')">
 <div class="invalid-feedback d-none" id="err_middle_name"></div>
 @error('middle_name')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 {{-- Last Name --}}
 <div class="col-md-3">
 <label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
 <input type="text" name="last_name" id="in_last_name" class="form-control uppercase @error('last_name') is-invalid @enderror" placeholder="Surname" value="{{ old('last_name') }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑ\s.\'-]/g, '')" required>
 <div class="invalid-feedback d-none" id="err_last_name"></div>
 @error('last_name')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 {{-- Suffix --}}
 <div class="col-md-3">
 <label class="small text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
 <input type="text" name="suffix" id="in_suffix" list="suffix_options" class="form-control uppercase @error('suffix') is-invalid @enderror" placeholder="e.g. JR" value="{{ old('suffix') }}" maxlength="10">
 <div class="invalid-feedback d-none" id="err_suffix"></div>
 @error('suffix')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 </div>
 {{-- 2. Demographics --}}
 <h6 class="text-accent mb-3 small fw-bold uppercase">2. Demographics</h6>
 <div class="row g-3 mb-4">
 {{-- Birthdate --}}
 <div class="col-md-6">
 <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
 <input type="date" name="birthdate" id="in_birthdate" class="form-control @error('birthdate') is-invalid @enderror" value="{{ old('birthdate') }}" required min="{{ now()->subYears(18)->addDay()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" onchange="validateBirthdateInput()">
 <div class="invalid-feedback d-none" id="err_birthdate"></div>
 @error('birthdate')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 <small class="text-muted smaller mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Dependents must be minors under 18 years of age. If the family member is 18 years old or older, please register a new independent account for them instead.</small>
 </div>
 {{-- Sex --}}
 <div class="col-md-6">
 <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
 <select name="sex" id="in_sex" class="form-select @error('sex') is-invalid @enderror" required>
 <option value="" disabled {{ old('sex') ? '' : 'selected' }}>-- Select Sex --</option>
 <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
 <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
 </select>
 <div class="invalid-feedback d-none" id="err_sex"></div>
 @error('sex')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 </div>
 {{-- 3. Home Address --}}
 <div class="border-top border-secondary border-opacity-25 pt-3 mb-4">
 <div class="d-flex justify-content-between align-items-center mb-3">
 <h6 class="text-accent small fw-bold uppercase mb-0">3. Home Address</h6>
 <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="fetchParentAddress('dep_')">
 <i class="bi bi-geo-alt-fill me-1.5"></i>Use Parent's Address
 </button>
 </div>
 <div id="manual_dep_address_wrapper" class="row g-3">
 <div class="col-md-6">
 <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
 <select id="dep_province" name="province" class="form-select @error('province') is-invalid @enderror" onchange="fetchDepCities(this.value)" required>
 <option value="">Select Province</option>
 </select>
 <div class="invalid-feedback d-none" id="err_province"></div>
 @error('province')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
 <select id="dep_city" name="city" class="form-select @error('city') is-invalid @enderror" onchange="fetchDepBarangays(this.value)" disabled required>
 <option value="">Select Province First</option>
 </select>
 <div class="invalid-feedback d-none" id="err_city"></div>
 @error('city')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
 <select id="dep_barangay" name="barangay" class="form-select @error('barangay') is-invalid @enderror" disabled required>
 <option value="">Select City First</option>
 </select>
 <div class="invalid-feedback d-none" id="err_barangay"></div>
 @error('barangay')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 <div class="col-md-6">
 <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
 <input type="text" id="dep_street" name="street" class="form-control uppercase @error('street') is-invalid @enderror" placeholder="House/Lot/Block/Street" value="{{ old('street') }}" required>
 <div class="invalid-feedback d-none" id="err_street"></div>
 @error('street')
 <div class="text-danger small mt-1 fw-bold">{{ $message }}</div>
 @enderror
 </div>
 </div>
 </div>
 {{-- Submit & Cancel Buttons --}}
 <div class="d-flex gap-3 mt-4 pt-3 border-top border-secondary border-opacity-25">
 <a href="{{ route('profile.edit') }}#tab-dependents" class="btn-custom btn-cancel-custom w-50 py-3 fw-bold uppercase text-decoration-none text-center">
 Cancel
 </a>
 <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm">
 Save to Family List
 </button>
 </div>
 </form>
 </div>
 </div>
 </div>
</div>
<datalist id="suffix_options">
 <option value="JR">
 <option value="SR">
 <option value="II">
 <option value="III">
 <option value="IV">
 <option value="V">
</datalist>
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
#create-dependent-root .form-control:focus,
#create-dependent-root .form-select:focus {
 border-color: var(--brand-accent) !important;
 box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}
#create-dependent-root .is-invalid {
 border-color: #ff4d4d !important;
 box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
function toggleDepMN(checkbox) {
 const input = document.getElementById('dep_middle_name');
 if (input) {
 if (checkbox.checked) {
 input.value = "N/A";
 input.readOnly = true;
 input.classList.add('opacity-50');
 clearFieldError(input);
 } else {
 input.value = "";
 input.readOnly = false;
 input.classList.remove('opacity-50');
 }
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
function validateNameString(val, fieldName) {
 if (!val || val === 'N/A') return null;
 const charRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc \s.\'-]+$/;
 const startRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc ]/;
 const consecutiveRegex = /[.\'-]{2,}/;
 const letterRegex = /[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc]/;
 if (!charRegex.test(val)) return `${fieldName} may only contain letters, spaces, periods, hyphens, and apostrophes.`;
 if (!startRegex.test(val)) return `${fieldName} must start with a letter.`;
 if (!letterRegex.test(val)) return `${fieldName} must contain at least one letter.`;
 if (consecutiveRegex.test(val)) return `${fieldName} cannot contain consecutive punctuation marks.`;
 if (val.length > 60) return `${fieldName} cannot exceed 60 characters.`;
 return null;
}
function validateBirthdateInput() {
 const bdayInput = document.getElementById('in_birthdate');
 if (!bdayInput) return true;
 clearFieldError(bdayInput);
 if (!bdayInput.value) {
 setFieldError(bdayInput, 'err_birthdate', 'Birthdate is required.');
 return false;
 }
 const dob = new Date(bdayInput.value);
 const today = new Date();
 let age = today.getFullYear() - dob.getFullYear();
 const m = today.getMonth() - dob.getMonth();
 if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
 if (age < 0) {
 setFieldError(bdayInput, 'err_birthdate', 'Birthdate cannot be in the future.');
 return false;
 }
 if (age >= 18) {
 setFieldError(bdayInput, 'err_birthdate', 'Administrative Policy: Dependents must be minors (under 18 years of age).');
 return false;
 }
 return true;
}
function validateAddDependentForm(e) {
 let isValid = true;
 let firstInvalidInput = null;
 document.querySelectorAll('#addDependentForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
 document.querySelectorAll('#addDependentForm .invalid-feedback').forEach(el => {
 el.innerText = '';
 el.classList.add('d-none');
 el.classList.remove('d-block');
 });
 function markInvalid(input, errId, msg) {
 setFieldError(input, errId, msg);
 isValid = false;
 if (!firstInvalidInput) firstInvalidInput = input;
 }
 const fNameInput = document.getElementById('in_first_name');
 const fNameErr = validateNameString(fNameInput ? fNameInput.value.trim() : '', 'First Name');
 if (!fNameInput || !fNameInput.value.trim()) markInvalid(fNameInput, 'err_first_name', 'First Name is required.');
 else if (fNameErr) markInvalid(fNameInput, 'err_first_name', fNameErr);
 const mNameInput = document.getElementById('dep_middle_name');
 const mNameErr = validateNameString(mNameInput ? mNameInput.value.trim() : '', 'Middle Name');
 if (mNameErr) markInvalid(mNameInput, 'err_middle_name', mNameErr);
 const lNameInput = document.getElementById('in_last_name');
 const lNameErr = validateNameString(lNameInput ? lNameInput.value.trim() : '', 'Last Name');
 if (!lNameInput || !lNameInput.value.trim()) markInvalid(lNameInput, 'err_last_name', 'Last Name is required.');
 else if (lNameErr) markInvalid(lNameInput, 'err_last_name', lNameErr);
 const suffixInput = document.getElementById('in_suffix');
 if (suffixInput && suffixInput.value.trim()) {
 const suffixRegex = /^[a-zA-Z\s.]+$/;
 if (!suffixRegex.test(suffixInput.value.trim())) markInvalid(suffixInput, 'err_suffix', 'Suffix may only contain letters, spaces, and periods.');
 else if (suffixInput.value.trim().length > 10) markInvalid(suffixInput, 'err_suffix', 'Suffix cannot exceed 10 characters.');
 }
 const bdayInput = document.getElementById('in_birthdate');
 if (!validateBirthdateInput()) {
 if (!firstInvalidInput) firstInvalidInput = bdayInput;
 isValid = false;
 }
 const sexSel = document.getElementById('in_sex');
 if (!sexSel || !sexSel.value) markInvalid(sexSel, 'err_sex', 'Please select a gender.');

 const provSel = document.getElementById('dep_province');
 const citySel = document.getElementById('dep_city');
 const brgySel = document.getElementById('dep_barangay');
 const streetInput = document.getElementById('dep_street');
 if (!provSel || !provSel.value) markInvalid(provSel, 'err_province', 'Province selection is required.');
 if (!citySel || !citySel.value) markInvalid(citySel, 'err_city', 'City/Municipality selection is required.');
 if (!brgySel || !brgySel.value) markInvalid(brgySel, 'err_barangay', 'Barangay selection is required.');
 if (!streetInput || !streetInput.value.trim()) markInvalid(streetInput, 'err_street', 'Street address is required.');
 if (!isValid) {
 e.preventDefault();
 e.stopPropagation();
 if (firstInvalidInput) {
 firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
 firstInvalidInput.focus();
 }
 return false;
 }
 compileDependentAddress();
 return true;
}
async function fetchDepProvinces() {
 const provSel = document.getElementById('dep_province');
 if (!provSel) return;
 try {
 let res = await fetch('https://psgc.gitlab.io/api/provinces/');
 if (!res.ok) res = await fetch('https://psgc.gitlab.io/api/provinces');
 const data = await res.json();
 provSel.innerHTML = '<option value="">Select Province</option>';
 data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
 provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
 });
 } catch (e) {
 console.error("Dependent Province API Error", e);
 }
}
async function fetchDepCities(provCode) {
 const citySel = document.getElementById('dep_city');
 const brgySel = document.getElementById('dep_barangay');
 if (!citySel || !brgySel) return;
 citySel.disabled = true;
 brgySel.disabled = true;
 citySel.innerHTML = '<option value="">Loading Cities...</option>';
 try {
 let res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
 if (!res.ok) res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities`);
 const data = await res.json();
 citySel.innerHTML = '<option value="">Select City</option>';
 data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
 citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
 });
 citySel.disabled = false;
 brgySel.innerHTML = '<option value="">Select City First</option>';
 } catch (e) {
 console.error("Dependent City API Error", e);
 }
}
async function fetchDepBarangays(cityCode) {
 const brgySel = document.getElementById('dep_barangay');
 if (!brgySel) return;
 brgySel.disabled = true;
 brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
 try {
 let res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
 if (!res.ok) res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays`);
 const data = await res.json();
 brgySel.innerHTML = '<option value="">Select Barangay</option>';
 data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
 brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
 });
 brgySel.disabled = false;
 } catch (e) {
 console.error("Dependent Barangay API Error", e);
 }
}
async function fetchParentAddress(prefix) {
 const provSel = document.getElementById(`${prefix}province`);
 const citySel = document.getElementById(`${prefix}city`);
 const brgySel = document.getElementById(`${prefix}barangay`);
 const streetInput = document.getElementById(`${prefix}street`);
 if (!provSel || !citySel || !brgySel || !streetInput) return;
 const parentProvName = "{{ trim(Auth::user()->province) }}";
 const parentCityName = "{{ trim(Auth::user()->city) }}";
 const parentBrgyName = "{{ trim(Auth::user()->barangay) }}";
 const parentStreet = "{{ trim(Auth::user()->street) }}";
 provSel.innerHTML = '<option value="">Loading Provinces...</option>';
 citySel.innerHTML = '<option value="">Loading Cities...</option>';
 brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
 provSel.disabled = true;
 citySel.disabled = true;
 brgySel.disabled = true;
 try {
 let res = await fetch('https://psgc.gitlab.io/api/provinces/');
 if (!res.ok) res = await fetch('https://psgc.gitlab.io/api/provinces');
 const provinces = await res.json();
 provSel.innerHTML = '<option value="">Select Province</option>';
 provinces.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
 provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
 });
 provSel.disabled = false;
 let provOpt = Array.from(provSel.options).find(opt => 
 opt.text.toUpperCase().trim() === parentProvName.toUpperCase().trim() || opt.value === parentProvName.trim()
 );
 if (provOpt) {
 provSel.value = provOpt.value;
 let cityRes = await fetch(`https://psgc.gitlab.io/api/provinces/${provOpt.value}/cities-municipalities/`);
 if (!cityRes.ok) cityRes = await fetch(`https://psgc.gitlab.io/api/provinces/${provOpt.value}/cities-municipalities`);
 const cities = await cityRes.json();
 citySel.innerHTML = '<option value="">Select City</option>';
 cities.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
 citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
 });
 citySel.disabled = false;
 let cityOpt = Array.from(citySel.options).find(opt => 
 opt.text.toUpperCase().trim() === parentCityName.toUpperCase().trim() || opt.value === parentCityName.trim()
 );
 if (cityOpt) {
 citySel.value = cityOpt.value;
 let brgyRes = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityOpt.value}/barangays/`);
 if (!brgyRes.ok) brgyRes = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityOpt.value}/barangays`);
 const barangays = await brgyRes.json();
 brgySel.innerHTML = '<option value="">Select Barangay</option>';
 barangays.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
 brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
 });
 brgySel.disabled = false;
 let brgyOpt = Array.from(brgySel.options).find(opt => 
 opt.text.toUpperCase().trim() === parentBrgyName.toUpperCase().trim() || opt.value === parentBrgyName.trim()
 );
 if (brgyOpt) {
 brgySel.value = brgyOpt.value;
 }
 }
 }
 streetInput.value = parentStreet;
 streetInput.disabled = false;
 clearFieldError(provSel);
 clearFieldError(citySel);
 clearFieldError(brgySel);
 clearFieldError(streetInput);
 } catch (e) {
 console.error("Failed to fetch parent address:", e);
 }
}
function compileDependentAddress() {
 const street = document.getElementById('dep_street');
 const brgy = document.getElementById('dep_barangay');
 const city = document.getElementById('dep_city');
 const prov = document.getElementById('dep_province');
 if (street && brgy && city && prov) {
 const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
 const cityName = city.options[city.selectedIndex]?.text || '';
 const provName = prov.options[prov.selectedIndex]?.text || '';
 if (provName && cityName && brgyName && !provName.includes('Select')) {
 prov.options[prov.selectedIndex].value = provName;
 city.options[city.selectedIndex].value = cityName;
 brgy.options[brgy.selectedIndex].value = brgyName;
 }
 }
}
document.addEventListener('DOMContentLoaded', async () => {
 await fetchDepProvinces();
 document.querySelectorAll('#addDependentForm input, #addDependentForm select').forEach(input => {
 input.addEventListener('input', () => clearFieldError(input));
 input.addEventListener('change', () => clearFieldError(input));
 });
 const addForm = document.getElementById('addDependentForm');
 if (addForm) {
 addForm.addEventListener('submit', validateAddDependentForm);
 }
});
</script>
@endpush