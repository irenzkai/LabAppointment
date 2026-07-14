@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">
 
                {{-- LEFT PANEL: CLINICAL INFORMATION (Always Dark for high-contrast presentation) --}}
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 600px;">
                    {{-- Soft backdrop overlay and dark brand styling --}}
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>
 
                    {{-- Brand Content --}}
                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-4 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Join the clinical network.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Follow our secure, multi-step registration flow to set up your personal clinical profile and gain immediate access to our diagnostic suite.</p>
                    </div>
 
                    {{-- Bottom Information --}}
                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Data Protected & Encrypted
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: MULTI-STEP FORM (Dynamic Background for Theme Compatibility) --}}
                <div class="col-lg-7 d-flex flex-column justify-content-center p-4 p-md-5 bg-card">
                    <div class="w-100" style="max-width: 480px; margin: 0 auto;">
 
                        {{-- Header & Progress --}}
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

                        {{-- Validation Errors --}}
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

                        <form id="multiStepForm" method="POST" action="{{ route('register') }}">
                            @csrf

                            {{-- STEP 1: IDENTITY --}}
                            <div class="reg-section" id="section-1">
                                <div class="row g-3 text-start">
                                    <div class="col-12">
                                        <label class="small text-muted fw-bold mb-1">FIRST NAME</label>
                                        <input type="text" name="first_name" class="form-control uppercase" placeholder="Given Name" value="{{ old('first_name') }}" required>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="small text-muted fw-bold mb-0">MIDDLE NAME</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="no_mn" onclick="toggleMN(this)">
                                                <label class="smaller text-muted" for="no_mn">None</label>
                                            </div>
                                        </div>
                                        <input type="text" name="middle_name" id="middle_name" class="form-control uppercase" placeholder="Middle Name" value="{{ old('middle_name') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="small text-muted fw-bold mb-1">LAST NAME</label>
                                        <input type="text" name="last_name" class="form-control uppercase" placeholder="Surname" value="{{ old('last_name') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted fw-bold mb-1">BIRTHDATE</label>
                                        <input type="date" name="birthdate" class="form-control" value="{{ old('birthdate') }}" required max="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted fw-bold mb-1">SEX</label>
                                        <select name="sex" class="form-select" required>
                                            <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="button" class="btn-custom btn-accent w-100 mt-4 py-3" onclick="goToStep(2)">
                                    NEXT: ADDRESS <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>

                            {{-- STEP 2: ADDRESS (API DRIVEN) --}}
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
                                        <input type="text" name="street" class="form-control uppercase" placeholder="House/Lot/Block/Street" value="{{ old('street') }}" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-4">
                                    <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToStep(1)">BACK</button>
                                    <button type="button" class="btn-custom btn-accent w-50 py-3" onclick="goToStep(3)">NEXT</button>
                                </div>
                            </div>

                            {{-- STEP 3: CONTACT --}}
                            <div class="reg-section d-none" id="section-3">
                                <h6 class="text-accent smaller fw-bold mb-3 uppercase text-start">Contact Information</h6>
                                <div class="mb-3 text-start">
                                    <label class="small text-muted fw-bold mb-1">EMAIL ADDRESS</label>
                                    <input type="email" name="email" class="form-control" placeholder="name@example.com" value="{{ old('email') }}" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label class="small text-muted fw-bold mb-1">PHONE NUMBER</label>
                                    <input type="text" name="phone" class="form-control" placeholder="09xxxxxxxxx" value="{{ old('phone') }}" required>
                                </div>
                                <div class="d-flex gap-2 mt-4">
                                    <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToStep(2)">BACK</button>
                                    <button type="button" class="btn-custom btn-accent w-50 py-3" onclick="goToStep(4)">NEXT</button>
                                </div>
                            </div>

                            {{-- STEP 4: SECURITY --}}
                            <div class="reg-section d-none" id="section-4">
                                <h6 class="text-accent smaller fw-bold mb-3 uppercase text-start">Account Security</h6>
                                <div class="mb-3 text-start">
                                    <label class="small text-muted fw-bold mb-1">PASSWORD</label>
                                    <div class="password-container position-relative">
                                        <input type="password" name="password" id="reg_pass" class="form-control" placeholder="Min. 8 characters" required>
                                        <i class="bi bi-eye password-toggle text-accent" id="toggleRegPass" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                                    </div>
                                </div>
                                <div class="mb-3 text-start">
                                    <label class="small text-muted fw-bold mb-1">CONFIRM PASSWORD</label>
                                    <div class="password-container position-relative">
                                        <input type="password" name="password_confirmation" id="reg_pass_conf" class="form-control" placeholder="Repeat password" required>
                                        <i class="bi bi-eye password-toggle text-accent" id="toggleRegPassConf" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 mt-4">
                                    <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToStep(3)">BACK</button>
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

<style>
.is-invalid { border-color: #ff4d4d !important; }
.shadow-neon { box-shadow: 0 0 10px var(--neon); }
.opacity-50 { opacity: 0.5; cursor: not-allowed; }
.uppercase { text-transform: uppercase; }
.password-container input {
    padding-right: 45px;
}
</style>
@endsection

@push('scripts')
<script>
// --- STEP NAVIGATION ---
function goToStep(step) {
    if (step > 1) {
        const current = document.querySelector(`.reg-section:not(.d-none)`);
        const requireds = current.querySelectorAll('[required]');
        let valid = true;
        requireds.forEach(input => {
            if (!input.value.trim()) { 
                valid = false; 
                input.classList.add('is-invalid'); 
            } else { 
                input.classList.remove('is-invalid'); 
            }
        });
        if (!valid && step > parseInt(current.id.split('-')[1])) return;
    }

    document.querySelectorAll('.reg-section').forEach(s => s.classList.add('d-none'));
    document.getElementById(`section-${step}`).classList.remove('d-none');

    const percent = (step * 25);
    document.getElementById('reg-progress').style.width = percent + '%';
    document.getElementById('step-percent').innerText = percent + '%';
    const labels = ["Identity", "Location", "Contact", "Security"];
    document.getElementById('step-label').innerText = `Step ${step}: ${labels[step - 1]}`;
}

// --- MIDDLE NAME LOGIC ---
function toggleMN(checkbox) {
    const input = document.getElementById('middle_name');
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

// --- PSGC ADDRESS API ---
const apiBase = "https://psgc.gitlab.io/api";

async function fetchProvinces() {
    try {
        const res = await fetch(`${apiBase}/provinces/`);
        const data = await res.json();
        const sel = document.getElementById('addr_province');
        sel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            sel.innerHTML += `<option value="${p.code}" data-name="${p.name}">${p.name}</option>`;
        });
    } catch (e) { 
        console.error("Province API Error", e);
    }
}

async function fetchCities(provCode) {
    const citySel = document.getElementById('addr_city');
    const brgySel = document.getElementById('addr_brgy');
    citySel.disabled = true; 
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';

    try {
        const res = await fetch(`${apiBase}/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}" data-name="${c.name}">${c.name}</option>`;
        });
        citySel.disabled = false;
    } catch (e) { 
        console.error("City API Error", e); 
    }
}

async function fetchBarangays(cityCode) {
    const brgySel = document.getElementById('addr_brgy');
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    try {
        const res = await fetch(`${apiBase}/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;
    } catch (e) { 
        console.error("Barangay API Error", e); 
    }
}

// FIXED: Overwrites the numeric keys with actual literal names before submitting
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

// --- INITIALIZATION ---
document.addEventListener('DOMContentLoaded', () => {
    fetchProvinces();
    setupPasswordToggle('#reg_pass', '#toggleRegPass');
    setupPasswordToggle('#reg_pass_conf', '#toggleRegPassConf');

    // FIXED: Form submit handler intercepts and compiles literal addresses
    const regForm = document.getElementById('multiStepForm');
    if (regForm) {
        regForm.addEventListener('submit', function() {
            compileRegisterAddress();
        });
    }
});
</script>
@endpush