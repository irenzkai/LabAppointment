@extends('layouts.app')
@section('title', 'Create User Account')
@section('content')
<div class="container text-start animate-page py-4" id="createUserContainer">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card p-4 p-md-5 border-secondary bg-card shadow-lg">
                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-25 pb-3">
                    <div>
                        <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                            <i class="bi bi-person-plus-fill me-2"></i>Create User Account
                        </h2>
                        <p class="text-secondary mb-0 small">Register a new patient, staff, or laboratory technician profile.</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="btn-custom btn-cancel-custom px-4 py-2 fw-bold text-uppercase text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Back to Directory
                    </a>
                </div>

                {{-- Promoted Dependent Pre-Fill Banner --}}
                @if(isset($promotedDependent))
                <div class="alert alert-clinical border-accent bg-accent bg-opacity-10 text-main p-3 mb-4 rounded-3 d-flex align-items-center">
                    <i class="bi bi-arrow-up-circle-fill text-accent me-2 fs-5"></i>
                    <div>
                        <div class="fw-bold uppercase small text-accent">Promoting Dependent Profile</div>
                        <div class="small">Pre-filling details for <strong>{{ strtoupper($promotedDependent->first_name . ' ' . $promotedDependent->last_name) }}</strong>. Historical medical records will be automatically linked upon account creation.</div>
                    </div>
                </div>
                @endif

                {{-- Server-Side Validation Banner --}}
                @if ($errors->any())
                <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 mb-4 rounded-3">
                    <div class="d-flex align-items-center mb-1 fw-bold uppercase small">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Validation Failure
                    </div>
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('admin.users.store') }}" method="POST" id="createUserForm" onsubmit="return validateCreateUserForm(event)">
                    @csrf
                    @if(isset($promotedDependent))
                    <input type="hidden" name="promoted_dependent_id" value="{{ $promotedDependent->id }}">
                    @endif

                    {{-- 1. Identity --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase">1. Personal Identity</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control uppercase @error('first_name') is-invalid @enderror" value="{{ old('first_name', $promotedDependent->first_name ?? '') }}" required>
                            <div class="invalid-feedback d-none" id="err_first_name"></div>
                            @error('first_name') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" class="form-control uppercase @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $promotedDependent->middle_name ?? '') }}">
                            <div class="invalid-feedback d-none" id="err_middle_name"></div>
                            @error('middle_name') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control uppercase @error('last_name') is-invalid @enderror" value="{{ old('last_name', $promotedDependent->last_name ?? '') }}" required>
                            <div class="invalid-feedback d-none" id="err_last_name"></div>
                            @error('last_name') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
                            <input type="text" name="suffix" id="suffix" list="suffix_options" class="form-control uppercase @error('suffix') is-invalid @enderror" value="{{ old('suffix', $promotedDependent->suffix ?? '') }}" placeholder="e.g. JR" maxlength="10">
                            <datalist id="suffix_options">
                                <option value="JR"><option value="SR"><option value="II"><option value="III"><option value="IV"><option value="V">
                            </datalist>
                            <div class="invalid-feedback d-none" id="err_suffix"></div>
                            @error('suffix') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                            <input type="date" name="birthdate" id="birthdate" class="form-control @error('birthdate') is-invalid @enderror" value="{{ old('birthdate', isset($promotedDependent) && $promotedDependent->birthdate ? $promotedDependent->birthdate->format('Y-m-d') : '') }}" required max="{{ isset($promotedDependent) ? date('Y-m-d') : now()->subYears(18)->format('Y-m-d') }}">
                            <div class="invalid-feedback d-none" id="err_birthdate"></div>
                            @error('birthdate') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                            <small class="text-muted smaller mt-1 d-block">{{ isset($promotedDependent) ? 'Mindful of birthdate pre-filled from dependent record.' : 'Users must be at least 18 years old.' }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
                            <select name="sex" id="sex" class="form-select @error('sex') is-invalid @enderror" required>
                                <option value="" disabled {{ old('sex', $promotedDependent->sex ?? '') ? '' : 'selected' }}>-- Select Sex --</option>
                                <option value="Male" {{ old('sex', $promotedDependent->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('sex', $promotedDependent->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_sex"></div>
                            @error('sex') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Contact Phone</label>
                            <div class="input-group">
                                <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                                @php
                                $rawPhone = old('phone', $promotedDependent->phone ?? '');
                                $displayPhone = str_starts_with($rawPhone, '09') ? substr($rawPhone, 2) : $rawPhone;
                                @endphp
                                <input type="text" id="phone_display" class="form-control @error('phone') is-invalid @enderror" placeholder="171234567" maxlength="9" value="{{ $displayPhone }}" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncCreatePhone();" required>
                            </div>
                            <input type="hidden" name="phone" id="in_phone" value="{{ $rawPhone }}">
                            <div class="invalid-feedback d-none" id="err_phone"></div>
                            @error('phone') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- 2. Residential Address with PSGC API Dropdowns --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">2. Residential Address (PSGC API)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
                            <select id="create_province" name="province" class="form-select @error('province') is-invalid @enderror" onchange="fetchCreateCities(this.value)" required>
                                <option value="">Select Province</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_province"></div>
                            @error('province') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                            <select id="create_city" name="city" class="form-select @error('city') is-invalid @enderror" onchange="fetchCreateBarangays(this.value)" disabled required>
                                <option value="">Select Province First</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_city"></div>
                            @error('city') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
                            <select id="create_brgy" name="barangay" class="form-select @error('barangay') is-invalid @enderror" disabled required>
                                <option value="">Select City First</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_barangay"></div>
                            @error('barangay') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                            <input type="text" id="create_street" name="street" class="form-control uppercase @error('street') is-invalid @enderror" value="{{ old('street', $promotedDependent->street ?? '') }}" placeholder="House/Lot/Block/Street" required>
                            <div class="invalid-feedback d-none" id="err_street"></div>
                            @error('street') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- 3. Account Role & Security --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">3. Account Role & Security</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="name@domain.com" required>
                            <div class="invalid-feedback d-none" id="err_email"></div>
                            @error('email') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Access Role</label>
                            <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>Patient / User</option>
                                <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Clinic Staff</option>
                                <option value="lab_tech" {{ old('role') == 'lab_tech' ? 'selected' : '' }}>Laboratory Technician</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_role"></div>
                            @error('role') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Initial Password</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Min. 8 characters" required>
                            <div class="invalid-feedback d-none" id="err_password"></div>
                            @error('password') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Confirm Initial Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repeat password" required>
                            <div class="invalid-feedback d-none" id="err_password_confirmation"></div>
                        </div>

                        {{-- Email Pre-Verification Option --}}
                        <div class="col-12 mt-3">
                            <div class="form-check form-switch p-3 rounded text-start" style="background-color: rgba(25, 211, 140, 0.04); border: 1.5px solid var(--border-color);">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="verify_email_now" id="verify_email_now" value="1" {{ old('verify_email_now', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label text-main fw-bold small uppercase" for="verify_email_now">
                                    Mark Email as Verified Immediately
                                </label>
                                <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                    Pre-verifies this account so the user can log in right away. If unchecked, the user will be prompted to verify their email upon login.
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Administrative Justification --}}
                    <div class="border-top border-secondary border-opacity-10 pt-3 mb-4">
                        <h6 class="text-danger mb-2 small fw-bold uppercase"><i class="bi bi-shield-exclamation me-1"></i>4. Administrative Justification</h6>
                        <div class="mb-3 text-start">
                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Select Administrative Justification</label>
                            <select name="reason" id="reason_select" class="form-select @error('reason') is-invalid @enderror" onchange="toggleCreateReason(this.value)" required>
                                <option value="" disabled {{ old('reason') ? '' : (isset($promotedDependent) ? '' : 'selected') }}>-- Select a valid justification --</option>
                                <option value="Minor reached adulthood / account promotion" {{ isset($promotedDependent) ? 'selected' : '' }}>Minor reached adulthood / account promotion</option>
                                <option value="New patient account registration by admin" {{ old('reason') == 'New patient account registration by admin' ? 'selected' : '' }}>New patient account registration by admin</option>
                                <option value="Onboarding new clinical staff / laboratory technician" {{ old('reason') == 'Onboarding new clinical staff / laboratory technician' ? 'selected' : '' }}>Onboarding new clinical staff / laboratory technician</option>
                                <option value="Walk-in patient registration at reception desk" {{ old('reason') == 'Walk-in patient registration at reception desk' ? 'selected' : '' }}>Walk-in patient registration at reception desk</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_reason"></div>
                            @error('reason') <div class="text-danger small mt-1 fw-bold">{{ $message }}</div> @enderror
                        </div>
                        <div id="reason_custom_wrapper" class="mb-3 text-start d-none">
                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Specify Custom Reason</label>
                            <textarea id="reason_custom" class="form-control" rows="2" placeholder="Provide details regarding the account creation justification..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4 pt-3 border-top border-secondary border-opacity-25">
                        <a href="{{ route('admin.users.index') }}" class="btn-custom btn-cancel-custom w-50 py-3 fw-bold uppercase text-decoration-none text-center">Cancel</a>
                        <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm">CREATE ACCOUNT NOW</button>
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
#createUserContainer .form-control:focus,
#createUserContainer .form-select:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}
#createUserContainer .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
const savedProv = @json(old('province', $promotedDependent?->province ?? ''));
const savedCity = @json(old('city', $promotedDependent?->city ?? ''));
const savedBrgy = @json(old('barangay', $promotedDependent?->barangay ?? ''));
const isPromotedAccount = {{ isset($promotedDependent) ? 'true' : 'false' }};

function syncCreatePhone() {
    const display = document.getElementById('phone_display');
    const hidden = document.getElementById('in_phone');
    if (display && hidden) {
        hidden.value = display.value ? '09' + display.value.trim() : '';
        clearFieldError(display);
    }
}

function toggleCreateReason(val) {
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
    clearFieldError(selectEl);
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

function validateCreateUserForm(e) {
    let isValid = true;
    let firstInvalidInput = null;

    document.querySelectorAll('#createUserForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#createUserForm .invalid-feedback').forEach(el => {
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
    const fErr = validateNameString(fName ? fName.value.trim() : '', 'First Name');
    if (!fName || !fName.value.trim()) markInvalid(fName, 'err_first_name', 'First Name is required.');
    else if (fErr) markInvalid(fName, 'err_first_name', fErr);

    const mName = document.getElementById('middle_name');
    const mErr = validateNameString(mName ? mName.value.trim() : '', 'Middle Name');
    if (mErr) markInvalid(mName, 'err_middle_name', mErr);

    const lName = document.getElementById('last_name');
    const lErr = validateNameString(lName ? lName.value.trim() : '', 'Last Name');
    if (!lName || !lName.value.trim()) markInvalid(lName, 'err_last_name', 'Last Name is required.');
    else if (lErr) markInvalid(lName, 'err_last_name', lErr);

    const suffix = document.getElementById('suffix');
    if (suffix && suffix.value.trim()) {
        const suffixRegex = /^[a-zA-Z0-9\s.]+$/;
        if (!suffixRegex.test(suffix.value.trim())) markInvalid(suffix, 'err_suffix', 'Suffix may only contain letters, numbers, spaces, and periods.');
        else if (suffix.value.trim().length > 10) markInvalid(suffix, 'err_suffix', 'Suffix cannot exceed 10 characters.');
    }

    const bday = document.getElementById('birthdate');
    if (!bday || !bday.value) {
        markInvalid(bday, 'err_birthdate', 'Birthdate is required.');
    } else {
        const dob = new Date(bday.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        if (!isPromotedAccount && age < 18) {
            markInvalid(bday, 'err_birthdate', 'Administrative Policy: Users must be at least 18 years old.');
        }
    }

    const sex = document.getElementById('sex');
    if (!sex || !sex.value) markInvalid(sex, 'err_sex', 'Please select a gender.');

    const displayPhone = document.getElementById('phone_display');
    const hiddenPhone = document.getElementById('in_phone');
    const phoneRegex = /^09\d{9}$/;
    if (!displayPhone || !displayPhone.value.trim()) {
        markInvalid(displayPhone, 'err_phone', 'Contact phone number is required.');
    } else if (!phoneRegex.test(hiddenPhone.value.trim())) {
        markInvalid(displayPhone, 'err_phone', 'Phone number must contain exactly 11 digits (09 + 9 digits).');
    }

    const prov = document.getElementById('create_province');
    const city = document.getElementById('create_city');
    const brgy = document.getElementById('create_brgy');
    const street = document.getElementById('create_street');

    if (!prov || !prov.value) markInvalid(prov, 'err_province', 'Province selection is required.');
    if (!city || !city.value) markInvalid(city, 'err_city', 'City selection is required.');
    if (!brgy || !brgy.value) markInvalid(brgy, 'err_barangay', 'Barangay selection is required.');
    if (!street || !street.value.trim()) markInvalid(street, 'err_street', 'Street address is required.');

    const email = document.getElementById('email');
    const emailRegex = /^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!email || !email.value.trim()) {
        markInvalid(email, 'err_email', 'Email Address is required.');
    } else if (!emailRegex.test(email.value.trim())) {
        markInvalid(email, 'err_email', 'Please enter a valid email address with a domain.');
    }

    const pass = document.getElementById('password');
    const passConf = document.getElementById('password_confirmation');
    if (!pass || !pass.value) {
        markInvalid(pass, 'err_password', 'Initial password is required.');
    } else if (pass.value.length < 8) {
        markInvalid(pass, 'err_password', 'Password must be at least 8 characters long.');
    } else if (!/[A-Z]/.test(pass.value) || !/[a-z]/.test(pass.value) || !/[0-9]/.test(pass.value) || !/[!@#$%^&*(),.?\":{}|<>]/.test(pass.value)) {
        markInvalid(pass, 'err_password', 'Password must include uppercase, lowercase, number, and special character.');
    }

    if (!passConf || !passConf.value) {
        markInvalid(passConf, 'err_password_confirmation', 'Password confirmation is required.');
    } else if (pass && pass.value !== passConf.value) {
        markInvalid(passConf, 'err_password_confirmation', 'Passwords do not match.');
    }

    const reasonSel = document.getElementById('reason_select');
    const customReason = document.getElementById('reason_custom');
    const activeReasonVal = reasonSel.value === 'Others' ? customReason.value.trim() : reasonSel.value;
    if (!activeReasonVal || activeReasonVal.length < 5) {
        markInvalid(reasonSel.value === 'Others' ? customReason : reasonSel, 'err_reason', 'Administrative justification is required.');
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

    compileCreateAddress();
    return true;
}

async function fetchCreateProvinces() {
    const provSel = document.getElementById('create_province');
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces/');
        const data = await res.json();
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(p => {
            provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });
        if (savedProv) {
            let opt = Array.from(provSel.options).find(o => o.text.toUpperCase() === savedProv.toUpperCase());
            if (opt) { provSel.value = opt.value; await fetchCreateCities(opt.value); }
        }
    } catch(e){}
}

async function fetchCreateCities(provCode) {
    const citySel = document.getElementById('create_city');
    const brgySel = document.getElementById('create_brgy');
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
        if (savedCity) {
            let opt = Array.from(citySel.options).find(o => o.text.toUpperCase() === savedCity.toUpperCase());
            if (opt) { citySel.value = opt.value; await fetchCreateBarangays(opt.value); }
        }
    } catch(e){}
}

async function fetchCreateBarangays(cityCode) {
    const brgySel = document.getElementById('create_brgy');
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
        if (savedBrgy) {
            let opt = Array.from(brgySel.options).find(o => o.text.toUpperCase() === savedBrgy.toUpperCase());
            if (opt) brgySel.value = opt.value;
        }
    } catch(e){}
}

function compileCreateAddress() {
    const prov = document.getElementById('create_province');
    const city = document.getElementById('create_city');
    const brgy = document.getElementById('create_brgy');
    if (prov && city && brgy) {
        if(prov.selectedIndex >= 0) prov.options[prov.selectedIndex].value = prov.options[prov.selectedIndex].text;
        if(city.selectedIndex >= 0) city.options[city.selectedIndex].value = city.options[city.selectedIndex].text;
        if(brgy.selectedIndex >= 0) brgy.options[brgy.selectedIndex].value = brgy.options[brgy.selectedIndex].text;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchCreateProvinces();
    syncCreatePhone();
    document.querySelectorAll('#createUserForm input, #createUserForm select').forEach(input => {
        input.addEventListener('input', () => clearFieldError(input));
        input.addEventListener('change', () => clearFieldError(input));
    });
});
</script>
@endpush