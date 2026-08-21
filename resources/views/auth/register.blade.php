@extends('layouts.app')
@section('title', 'Register')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
 <div class="col-12 col-lg-11 col-xl-10">
 <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
 <div class="row g-0 align-items-stretch">
 {{-- LEFT PANEL: CLINICAL INFORMATION --}}
 <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 600px;">
 <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
 <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>
 <div class="position-relative" style="z-index: 3;">
 <div class="d-flex align-items-center gap-3 mb-5">
 <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
 <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
 </div>
 <h1 class="display-4 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Join the clinical network.</h1>
 <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Follow our secure, multi-step registration flow to set up your personal clinical profile and gain immediate access to our diagnostic suite.</p>
 </div>
 <div class="position-relative mt-auto pt-4" style="z-index: 3;">
 <div class="d-flex align-items-center gap-2">
 <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
 <i class="bi bi-shield-lock-fill me-1"></i>Data Protected & Encrypted
 </span>
 </div>
 </div>
 </div>

 {{-- RIGHT PANEL: MULTI-STEP FORM --}}
 <div class="col-lg-7 d-flex flex-column justify-content-center p-4 p-md-5 bg-card">
 <div class="w-100" style="max-width: 480px; margin: 0 auto;">
 <div class="mb-4 text-start">
 <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter" style="font-size: 1.75rem;">Create Account</h3>
 {{-- Step Tracker --}}
 <div class="mt-3">
 <div class="d-flex justify-content-between mb-1 text-muted smaller fw-bold uppercase">
 <span id="step-label" class="text-accent">Step 1: Identity</span>
 <span id="step-percent">25%</span>
 </div>
 <div class="progress bg-secondary bg-opacity-10" style="height: 6px;">
 <div id="reg-progress" class="progress-bar bg-neon shadow-neon" style="width: 25%; transition: 0.4s;"></div>
 </div>
 </div>
 </div>

 {{-- Validation Errors Banner --}}
 @if ($errors->any())
 <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
 <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
 <div>
 <div class="fw-800 uppercase fs-x-small text-danger">Validation Error</div>
 <ul class="mb-0 text-main small ps-3">
 @foreach ($errors->all() as $error)
 <li>{{ $error }}</li>
 @endforeach
 </ul>
 </div>
 </div>
 @endif

 <form id="multiStepForm" method="POST" action="{{ route('register') }}" novalidate>
 @csrf
 @if(isset($promotedDependent))
 <input type="hidden" name="promoted_dependent_id" value="{{ $promotedDependent->id }}">
 @endif

 {{-- STEP 1: IDENTITY --}}
 <div class="reg-section" id="section-1">
 <div class="row g-3 text-start">
 <div class="col-12">
 <label class="small text-muted fw-bold mb-1">FIRST NAME</label>
 <input type="text" name="first_name" class="form-control uppercase" placeholder="Given Name" value="{{ old('first_name', $promotedDependent->first_name ?? '') }}" maxlength="60" required>
 </div>
 <div class="col-12">
 <div class="d-flex justify-content-between align-items-center mb-1">
 <label class="small text-muted fw-bold mb-0">MIDDLE NAME (OPTIONAL)</label>
 <div class="form-check form-switch">
 <input class="form-check-input" type="checkbox" id="no_mn" onclick="toggleMN(this)" {{ old('middle_name', isset($promotedDependent) && $promotedDependent->middle_name == 'N/A' ? 'checked' : '') }}>
 <label class="smaller text-muted" for="no_mn">None</label>
 </div>
 </div>
 <input type="text" name="middle_name" id="middle_name" class="form-control uppercase" placeholder="Middle Name" value="{{ old('middle_name', $promotedDependent->middle_name ?? '') }}" maxlength="60">
 <small class="text-muted mt-1 d-block" style="font-size: 0.65rem;">If you do not have a middle name, check the <strong>None</strong> toggle or leave this field blank.</small>
 </div>
 <div class="col-md-9 col-12">
 <label class="small text-muted fw-bold mb-1">LAST NAME</label>
 <input type="text" name="last_name" class="form-control uppercase" placeholder="Surname" value="{{ old('last_name', $promotedDependent->last_name ?? '') }}" maxlength="60" required>
 </div>
 <div class="col-md-3 col-12">
 <label class="small text-muted fw-bold mb-1 text-nowrap">SUFFIX (OPT.)</label>
 <input type="text" name="suffix" id="suffix" list="suffix_options" class="form-control uppercase" placeholder="e.g. JR" value="{{ old('suffix', $promotedDependent->suffix ?? '') }}" maxlength="10">
 <datalist id="suffix_options">
 <option value="JR">
 <option value="SR">
 <option value="II">
 <option value="III">
 <option value="IV">
 <option value="V">
 </datalist>
 </div>
 <div class="col-md-6 col-12">
 <label class="small text-muted fw-bold mb-1">BIRTHDATE</label>
 <input type="date" name="birthdate" class="form-control" value="{{ old('birthdate', isset($promotedDependent) && $promotedDependent->birthdate ? $promotedDependent->birthdate->format('Y-m-d') : '') }}" required max="{{ now()->subYears(18)->format('Y-m-d') }}">
 <small class="text-muted mt-1 d-block" style="font-size: 0.65rem;">You must be at least 18 years old to register.</small>
 </div>
 <div class="col-md-6 col-12">
 <label class="small text-muted fw-bold mb-1">SEX</label>
 <select name="sex" class="form-select" required>
 <option value="Male" {{ old('sex', $promotedDependent->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
 <option value="Female" {{ old('sex', $promotedDependent->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
 </select>
 </div>
 </div>
 <button type="button" class="btn-custom btn-accent w-100 mt-4 py-3" onclick="goToStep(2)">
 NEXT: ADDRESS <i class="bi bi-arrow-right ms-1"></i>
 </button>
 </div>

 {{-- STEP 2: ADDRESS --}}
 <div class="reg-section d-none" id="section-2">
 <h6 class="text-accent smaller fw-bold mb-3 uppercase text-start">Home Address</h6>
 <div class="row g-3 text-start">
 <div class="col-12">
 <label class="small text-muted fw-bold mb-1">PROVINCE</label>
 <select id="addr_province" name="province" class="form-select" onchange="fetchCities(this.value)" required>
 <option value="">Loading Provinces...</option>
 </select>
 </div>
 <div class="col-12">
 <label class="small text-muted fw-bold mb-1">CITY / MUNICIPALITY</label>
 <select id="addr_city" name="city" class="form-select" onchange="fetchBarangays(this.value)" disabled required>
 <option value="">Select Province First</option>
 </select>
 </div>
 <div class="col-12">
 <label class="small text-muted fw-bold mb-1">BARANGAY</label>
 <select id="addr_brgy" name="barangay" class="form-select" disabled required>
 <option value="">Select City First</option>
 </select>
 </div>
 <div class="col-12">
 <label class="small text-muted fw-bold mb-1">STREET / HOUSE NO.</label>
 <input type="text" name="street" class="form-control uppercase" placeholder="House/Lot/Block/Street" value="{{ old('street', $promotedDependent?->street ?? '') }}" required>
 </div>
 </div>
 <div class="d-flex gap-2 mt-4">
 <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToStep(1, false)">BACK</button>
 <button type="button" class="btn-custom btn-accent w-50 py-3" onclick="goToStep(3)">NEXT</button>
 </div>
 </div>

 {{-- STEP 3: CONTACT --}}
 <div class="reg-section d-none" id="section-3">
 <h6 class="text-accent smaller fw-bold mb-3 uppercase text-start">Contact Information</h6>
 <div class="row g-3 text-start">
 <div class="col-12">
 <label class="small text-muted fw-bold mb-1">EMAIL ADDRESS</label>
 <input type="email" name="email" class="form-control" placeholder="name@domain.com" value="{{ old('email', $promotedDependent?->email ?? '') }}" required>
 <small class="text-muted mt-1 d-block" style="font-size: 0.65rem;">Email must contain a valid domain (e.g., name@domain.com or user@online.htcgsc.edu.ph).</small>
 </div>
 <div class="col-12">
 <label class="small text-muted fw-bold mb-1">PHONE NUMBER</label>
 <div class="input-group">
 <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
 <input type="text" id="phone_display" class="form-control" placeholder="171234567" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncPhoneNumber()" required>
 </div>
 <input type="hidden" name="phone" id="phone_hidden" value="{{ old('phone', $promotedDependent?->phone ?? '') }}">
 <small class="text-muted mt-1 d-block" style="font-size: 0.65rem;">Enter the remaining 9 digits of your mobile number.</small>
 </div>
 </div>
 <div class="d-flex gap-2 mt-4">
 <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToStep(2, false)">BACK</button>
 <button type="button" class="btn-custom btn-accent w-50 py-3" onclick="goToStep(4)">NEXT</button>
 </div>
 </div>

 {{-- STEP 4: SECURITY --}}
 <div class="reg-section d-none" id="section-4">
 <h6 class="text-accent smaller fw-bold mb-3 uppercase text-start">Account Security</h6>
 <div class="mb-3 text-start">
 <label class="small text-muted fw-bold mb-1">PASSWORD</label>
 <div class="input-group">
 <input type="password" name="password" id="reg_pass" class="form-control" placeholder="Min. 8 characters" required>
 <span class="input-group-text border-secondary bg-secondary bg-opacity-25" style="cursor: pointer;">
 <i class="bi bi-eye text-main" id="toggleRegPass"></i>
 </span>
 </div>
 <div class="mt-3 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.02);">
 <small class="text-accent fw-bold uppercase d-block mb-1.5" style="font-size: 0.7rem;"><i class="bi bi-shield-lock-fill"></i> Password Guidelines:</small>
 <ul class="mb-0 ps-3 text-muted d-flex flex-column gap-1" style="font-size: 0.65rem; list-style-type: disc;">
 <li>Minimum length of <strong>8 characters</strong>.</li>
 <li>Include both <strong>uppercase</strong> and <strong>lowercase</strong> characters.</li>
 <li>Include at least <strong>one number</strong>.</li>
 <li>Include at least <strong>one special character</strong> (e.g !@#$%^&*).</li>
 </ul>
 </div>
 </div>
 <div class="mb-3 text-start">
 <label class="small text-muted mb-1">CONFIRM PASSWORD</label>
 <div class="input-group">
 <input type="password" name="password_confirmation" id="reg_pass_conf" class="form-control" placeholder="Repeat password" required>
 <span class="input-group-text border-secondary bg-secondary bg-opacity-25" style="cursor: pointer;">
 <i class="bi bi-eye text-main" id="toggleRegPassConf"></i>
 </span>
 </div>
 </div>
 <div class="d-flex gap-2 mt-4">
 <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToStep(3, false)">BACK</button>
 <button type="submit" class="btn-custom btn-accent w-50 py-3">FINALIZE</button>
 </div>
 </div>
 </form>
 </div>
 </div>
 </div>
 </div>
 </div>
</div>
@endsection

@push('scripts')
<script>
// --- SHIELDED LOCAL STORAGE WRAPPER ---
const safeStorage = {
 getItem(key) {
 try { return localStorage.getItem(key); } catch (e) { return null; }
 },
 setItem(key, value) {
 try { localStorage.setItem(key, value); } catch (e) {}
 },
 removeItem(key) {
 try { localStorage.removeItem(key); } catch (e) {}
 }
};

// --- DYNAMIC MULTI-POINT NAME VALIDATOR ---
function validateName(value) {
 const val = value.trim();
 if (!val) return { valid: false, message: "This field is required." };
 const charRegex = /^[a-zA-ZñÑ \s.\'-]+$/;
 if (!charRegex.test(val)) {
 return { valid: false, message: "Must contain letters, spaces, periods, hyphens, and apostrophes only." };
 }
 const startRegex = /^[a-zA-ZñÑ ]/;
 if (!startRegex.test(val)) {
 return { valid: false, message: "Names must start with a letter." };
 }
 const consecutiveRegex = /[.\'-]{2,}/;
 if (consecutiveRegex.test(val)) {
 return { valid: false, message: "Consecutive punctuation marks are prohibited." };
 }
 const letterRegex = /[a-zA-ZñÑ ]/;
 if (!letterRegex.test(val)) {
 return { valid: false, message: "Must contain at least one letter." };
 }
 return { valid: true };
}

// --- FIELD ERROR HANDLER ---
function showRegisterFieldError(inputElement, errorMessage) {
 if (!inputElement) return;
 inputElement.classList.add('is-invalid');
 let parent = inputElement.parentElement;
 let targetParent = parent.classList.contains('input-group') ? parent.parentElement : parent;
 let existingError = targetParent.querySelector('.invalid-feedback');
 if (existingError) {
 existingError.innerText = errorMessage;
 existingError.classList.remove('d-none');
 } else {
 let errorDiv = document.createElement('div');
 errorDiv.className = 'invalid-feedback d-block text-danger small mt-1 fw-bold';
 errorDiv.innerText = errorMessage;
 targetParent.appendChild(errorDiv);
 }
 const dismissHandler = () => {
 inputElement.classList.remove('is-invalid');
 let errorDiv = targetParent.querySelector('.invalid-feedback');
 if (errorDiv) {
 errorDiv.classList.add('d-none');
 errorDiv.innerText = '';
 }
 inputElement.removeEventListener('input', dismissHandler);
 inputElement.removeEventListener('change', dismissHandler);
 };
 inputElement.addEventListener('input', dismissHandler);
 inputElement.addEventListener('change', dismissHandler);
}

// --- COGNITIVE FLOW CONTROL & STEP TRACKER ---
function goToStep(step, validate = true) {
 const current = document.querySelector(`.reg-section:not(.d-none)`);
 const currentStep = current ? parseInt(current.id.split('-')[1]) : 1;
 if (validate && step > currentStep) {
 const requireds = current.querySelectorAll('[required]');
 let valid = true;
 requireds.forEach(input => {
 if (!input.value.trim()) {
 valid = false;
 showRegisterFieldError(input, "This field is required.");
 }
 });
 if (currentStep === 1) {
 const fName = document.querySelector('[name="first_name"]');
 const mName = document.getElementById('middle_name');
 const lName = document.querySelector('[name="last_name"]');
 const suffix = document.getElementById('suffix');
 const bday = document.querySelector('[name="birthdate"]');
 if (fName) {
 const check = validateName(fName.value);
 if (!check.valid) {
 valid = false;
 showRegisterFieldError(fName, check.message);
 } else if (fName.value.trim().length > 60) {
 valid = false;
 showRegisterFieldError(fName, "First Name cannot exceed 60 characters.");
 }
 }
 if (mName && mName.value !== 'N/A' && mName.value.trim() !== '') {
 const check = validateName(mName.value);
 if (!check.valid) {
 valid = false;
 showRegisterFieldError(mName, check.message);
 } else if (mName.value.trim().length > 60) {
 valid = false;
 showRegisterFieldError(mName, "Middle Name cannot exceed 60 characters.");
 }
 }
 if (lName) {
 const check = validateName(lName.value);
 if (!check.valid) {
 valid = false;
 showRegisterFieldError(lName, check.message);
 } else if (lName.value.trim().length > 60) {
 valid = false;
 showRegisterFieldError(lName, "Last Name cannot exceed 60 characters.");
 }
 }
 if (suffix && suffix.value.trim() !== '') {
 const sVal = suffix.value.trim();
 const suffixRegex = /^[a-zA-Z\s.]+$/;
 if (!suffixRegex.test(sVal)) {
 valid = false;
 showRegisterFieldError(suffix, "The suffix may only contain letters, spaces, and periods.");
 } else if (sVal.length > 10) {
 valid = false;
 showRegisterFieldError(suffix, "Suffix cannot exceed 10 characters.");
 }
 }
 if (bday && bday.value) {
 const dob = new Date(bday.value);
 const today = new Date();
 let age = today.getFullYear() - dob.getFullYear();
 const m = today.getMonth() - dob.getMonth();
 if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
 age--;
 }
 if (age < 18) {
 valid = false;
 showRegisterFieldError(bday, "Administrative Policy: You must be at least 18 years old to register.");
 }
 }
 } else if (currentStep === 2) {
 const prov = document.getElementById('addr_province');
 const city = document.getElementById('addr_city');
 const brgy = document.getElementById('addr_brgy');
 const street = document.querySelector('[name="street"]');
 if (prov && !prov.value) {
 valid = false;
 showRegisterFieldError(prov, "Province selection is required.");
 }
 if (city && !city.value) {
 valid = false;
 showRegisterFieldError(city, "City/Municipality selection is required.");
 }
 if (brgy && !brgy.value) {
 valid = false;
 showRegisterFieldError(brgy, "Barangay selection is required.");
 }
 if (street && !street.value.trim()) {
 valid = false;
 showRegisterFieldError(street, "Street Address is required.");
 }
 } else if (currentStep === 3) {
 const displayPhone = document.getElementById('phone_display');
 if (displayPhone && displayPhone.value.trim() && displayPhone.value.length !== 9) {
 valid = false;
 showRegisterFieldError(displayPhone, "The phone number must contain exactly 11 digits (09 + 9 digits).");
 }
 
 // Enhanced Email Validation with Multi-Level Subdomain Regex Support
 const emailInput = document.querySelector('[name="email"]');
 if (emailInput && emailInput.value.trim()) {
 const val = emailInput.value.trim();
 const emailRegex = /^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
 if (!emailRegex.test(val)) {
 valid = false;
 showRegisterFieldError(emailInput, "Please enter a valid email address with a domain (e.g. name@domain.com or user@online.htcgsc.edu.ph).");
 }
 }
 }
 if (!valid) {
 return;
 }
 }
 document.querySelectorAll('.reg-section').forEach(s => s.classList.add('d-none'));
 const targetSection = document.getElementById(`section-${step}`);
 if (targetSection) {
 targetSection.classList.remove('d-none');
 }
 const percent = (step * 25);
 const progressEl = document.getElementById('reg-progress');
 if (progressEl) progressEl.style.width = percent + '%';
 
 const percentEl = document.getElementById('step-percent');
 if (percentEl) percentEl.innerText = percent + '%';
 
 const labels = ["Identity", "Location", "Contact", "Security"];
 const labelEl = document.getElementById('step-label');
 if (labelEl) labelEl.innerText = `Step ${step}: ${labels[step - 1]}`;
 safeStorage.setItem('register_step', step);
}

// --- REAL-TIME DRAFT STATE SAVE ---
function saveDraft() {
 const form = document.getElementById('multiStepForm');
 if (!form) return;
 const draftData = {};
 const inputs = form.querySelectorAll('input:not([type="password"]), select, textarea');
 inputs.forEach(input => {
 if (input.name) {
 if (input.type === 'checkbox') {
 draftData[input.name] = input.checked;
 } else {
 draftData[input.name] = input.value;
 }
 }
 });
 safeStorage.setItem('register_draft', JSON.stringify(draftData));
}

function toggleMN(checkbox) {
 const input = document.getElementById('middle_name');
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
 saveDraft();
}

function syncPhoneNumber() {
 const displayInput = document.getElementById('phone_display');
 const hiddenInput = document.getElementById('phone_hidden');
 if (displayInput && hiddenInput) {
 hiddenInput.value = displayInput.value ? '09' + displayInput.value : '';
 }
 saveDraft();
}

const apiBase = "https://psgc.gitlab.io/api";
async function fetchProvinces() {
 try {
 const res = await fetch(`${apiBase}/provinces.json`);
 const data = await res.json();
 const sel = document.getElementById('addr_province');
 if (sel) {
 sel.innerHTML = '<option value="">Select Province</option>';
 data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
 sel.innerHTML += `<option value="${p.code}" data-name="${p.name}">${p.name}</option>`;
 });
 }
 } catch (e) {
 console.error("Province API Error", e);
 }
}

async function fetchCities(provCode) {
 const citySel = document.getElementById('addr_city');
 const brgySel = document.getElementById('addr_brgy');
 if (citySel) citySel.disabled = true;
 if (brgySel) brgySel.disabled = true;
 if (citySel) citySel.innerHTML = '<option value="">Loading Cities...</option>';
 try {
 const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities.json`);
 const data = await res.json();
 if (citySel) {
 citySel.innerHTML = '<option value="">Select City</option>';
 data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
 citySel.innerHTML += `<option value="${c.code}" data-name="${c.name}">${c.name}</option>`;
 });
 citySel.disabled = false;
 }
 } catch (e) {
 console.error("City API Error", e);
 }
 saveDraft();
}

async function fetchBarangays(cityCode) {
 const brgySel = document.getElementById('addr_brgy');
 if (brgySel) {
 brgySel.disabled = true;
 brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
 }
 try {
 const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays.json`);
 const data = await res.json();
 if (brgySel) {
 brgySel.innerHTML = '<option value="">Select Barangay</option>';
 data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
 brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
 });
 brgySel.disabled = false;
 }
 } catch (e) {
 console.error("Barangay API Error", e);
 }
 saveDraft();
}

function compileRegisterAddress() {
 const brgy = document.getElementById('addr_brgy');
 const city = document.getElementById('addr_city');
 const prov = document.getElementById('addr_province');
 if (brgy && city && prov) {
 const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
 const cityName = city.options[city.selectedIndex]?.text || '';
 const provName = prov.options[prov.selectedIndex]?.text || '';
 if (brgyName && cityName && provName) {
 prov.options[prov.selectedIndex].value = provName;
 city.options[city.selectedIndex].value = cityName;
 brgy.options[brgy.selectedIndex].value = brgyName;
 }
 }
}

async function initializeAddress() {
 const draft = JSON.parse(safeStorage.getItem('register_draft') || '{}');
 const provinceVal = draft['province'] || @json(old('province', $promotedDependent?->province ?? ''));
 const cityVal = draft['city'] || @json(old('city', $promotedDependent?->city ?? ''));
 const barangayVal = draft['barangay'] || @json(old('barangay', $promotedDependent?->barangay ?? ''));

 await fetchProvinces();
 if (provinceVal) {
 const provSel = document.getElementById('addr_province');
 if (provSel) {
 let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === provinceVal.toUpperCase() || opt.value === provinceVal);
 if (provOpt) {
 provSel.value = provOpt.value;
 await fetchCities(provOpt.value);
 if (cityVal) {
 const citySel = document.getElementById('addr_city');
 if (citySel) {
 let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === cityVal.toUpperCase() || opt.value === cityVal);
 if (cityOpt) {
 citySel.value = cityOpt.value;
 await fetchBarangays(cityOpt.value);
 if (barangayVal) {
 const brgySel = document.getElementById('addr_brgy');
 if (brgySel) {
 let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === barangayVal.toUpperCase() || opt.value === barangayVal);
 if (brgyOpt) {
 brgySel.value = brgyOpt.value;
 }
 }
 }
 }
 }
 }
 }
 }
 }
}

document.addEventListener('DOMContentLoaded', async () => {
 const form = document.getElementById('multiStepForm');
 const draftData = JSON.parse(safeStorage.getItem('register_draft') || '{}');
 for (const [key, value] of Object.entries(draftData)) {
 const input = document.querySelector(`[name="${key}"]`);
 if (input) {
 if (input.type === 'checkbox') {
 input.checked = value;
 } else {
 input.value = value;
 }
 }
 }
 await initializeAddress();
 setupPasswordToggle('#reg_pass', '#toggleRegPass');
 setupPasswordToggle('#reg_pass_conf', '#toggleRegPassConf');

 const mnCheck = document.getElementById('no_mn');
 if (mnCheck && mnCheck.checked) {
 const middleNameEl = document.getElementById('middle_name');
 if (middleNameEl) {
 middleNameEl.classList.add('opacity-50');
 middleNameEl.readOnly = true;
 }
 }

 const phoneHiddenEl = document.getElementById('phone_hidden');
 if (phoneHiddenEl) {
 const hiddenPhone = phoneHiddenEl.value;
 if (hiddenPhone && hiddenPhone.startsWith('09')) {
 const displayInput = document.getElementById('phone_display');
 if (displayInput) {
 displayInput.value = hiddenPhone.substring(2);
 }
 }
 }

 const savedStep = safeStorage.getItem('register_step');
 if (savedStep) {
 goToStep(parseInt(savedStep), false);
 } else {
 goToStep(1, false);
 }

 if (form) {
 form.addEventListener('keydown', function(e) {
 if (e.key === 'Enter') {
 if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'BUTTON') {
 return;
 }
 const current = document.querySelector(`.reg-section:not(.d-none)`);
 const currentStep = current ? parseInt(current.id.split('-')[1]) : 1;
 if (currentStep < 4) {
 e.preventDefault();
 goToStep(currentStep + 1);
 }
 }
 });

 form.addEventListener('submit', function(e) {
 const current = document.querySelector(`.reg-section:not(.d-none)`);
 const currentStep = current ? parseInt(current.id.split('-')[1]) : 4;
 if (currentStep === 4) {
 let valid = true;
 const password = document.getElementById('reg_pass');
 const confirmPassword = document.getElementById('reg_pass_conf');
 if (password) {
 const val = password.value;
 if (!val) {
 valid = false;
 showRegisterFieldError(password, "Password is required.");
 } else {
 if (val.length < 8) {
 valid = false;
 showRegisterFieldError(password, "Password must be at least 8 characters long.");
 }
 if (!/[A-Z]/.test(val) || !/[a-z]/.test(val)) {
 valid = false;
 showRegisterFieldError(password, "Password must contain both uppercase and lowercase characters.");
 }
 if (!/[0-9]/.test(val)) {
 valid = false;
 showRegisterFieldError(password, "Password must include at least one number.");
 }
 if (!/[!@#$%^&*(),.?\":{}|<>]/.test(val)) {
 valid = false;
 showRegisterFieldError(password, "Password must include at least one special character / symbol.");
 }
 }
 }
 if (confirmPassword) {
 if (!confirmPassword.value) {
 valid = false;
 showRegisterFieldError(confirmPassword, "Confirm Password is required.");
 } else if (password && password.value !== confirmPassword.value) {
 valid = false;
 showRegisterFieldError(confirmPassword, "Password confirmation does not match.");
 }
 }
 if (!valid) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 }
 compileRegisterAddress();
 safeStorage.removeItem('register_draft');
 safeStorage.removeItem('register_step');
 });
 }

 const draftFields = document.querySelectorAll('#multiStepForm input:not([type="password"]), #multiStepForm select, #multiStepForm textarea');
 draftFields.forEach(element => {
 element.addEventListener('input', saveDraft);
 element.addEventListener('change', saveDraft);
 });
});
</script>
@endpush