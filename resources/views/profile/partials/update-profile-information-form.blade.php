<div class="card p-4 p-md-5 border-secondary shadow-lg animate-page">
    <h5 class="text-main fw-bold mb-4 border-bottom border-secondary border-opacity-25 pb-2 uppercase" style="letter-spacing: 1px;">Personal Information</h5>

    {{-- Active Address on File --}}
    <div class="alert alert-clinical p-3 mb-4 text-start">
        <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Registered Address on File:</div>
        <div class="text-main small">{{ $user->address }}</div>
    </div>

    <form method="post" action="{{ route('profile.update') }}" onsubmit="return validateProfileForm(event)" novalidate id="profileUpdateForm">
        @csrf
        @method('patch')

        <input type="hidden" name="address" id="profile_address_hidden" value="{{ $user->address }}">

        <div class="row g-3 text-start">
            {{-- Name Separation --}}
            <div class="col-md-3">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">First Name</label>
                <input type="text" name="first_name" class="form-control uppercase" value="{{ old('first_name', $user->first_name) }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑ\s.\'-]/g, '')" required>
            </div>

            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="smaller text-secondary fw-bold mb-0 uppercase">Middle Name</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="profile_no_mn" onclick="toggleProfileMN(this)" {{ $user->middle_name == 'N/A' ? 'checked' : '' }}>
                        <label class="smaller text-muted" style="font-size: 0.65rem;" for="profile_no_mn">None</label>
                    </div>
                </div>
                <input type="text" name="middle_name" id="profile_middle_name" class="form-control uppercase" value="{{ old('middle_name', $user->middle_name) }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑ\s.\'-]/g, '')" {{ $user->middle_name == 'N/A' ? 'readonly' : '' }}>
            </div>

            <div class="col-md-3">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Last Name</label>
                <input type="text" name="last_name" class="form-control uppercase" value="{{ old('last_name', $user->last_name) }}" oninput="this.value = this.value.replace(/[^a-zA-ZñÑ\s.\'-]/g, '')" required>
            </div>

            <div class="col-md-3">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
                <input type="text" name="suffix" id="suffix" list="suffix_options" class="form-control uppercase" value="{{ old('suffix', $user->suffix) }}" placeholder="e.g. JR" maxlength="10">
                <datalist id="suffix_options">
                    <option value="JR">
                    <option value="SR">
                    <option value="II">
                    <option value="III">
                    <option value="IV">
                    <option value="V">
                </datalist>
            </div>

            {{-- Clinical re-verification helper alert --}}
            <div class="col-12 mt-2">
                <div class="alert alert-clinical p-2 border-warning border-opacity-10" style="background-color: rgba(25, 211, 140, 0.03);">
                    <i class="bi bi-shield-exclamation text-warning me-1.5"></i>
                    <small class="text-muted"><strong>Safety Notice:</strong> Modifying your registered email or phone number will automatically reset its verification status and require re-verification.</small>
                </div>
            </div>

            {{-- Contact Information with Verification Badges and triggers --}}
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="smaller text-secondary fw-bold mb-0 uppercase">Email Address</label>
                    <div id="email_badge_container">
                        @if($user->email_verified_at)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>
                        @else
                            <div class="d-flex align-items-center">
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-exclamation-triangle-fill"></i> Unverified</span>
                                <button type="button" class="btn btn-link text-accent text-decoration-none p-0 small ms-2 align-baseline" onclick="openProfileVerifyModal('email')" style="font-size:0.7rem;">Verify Now</button>
                            </div>
                        @endif
                    </div>
                </div>
                <input type="email" name="email" id="prof_email" class="form-control" value="{{ old('email', $user->email) }}" oninput="handleProfileEmailInput()" required>
            </div>

            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="smaller text-secondary fw-bold mb-0 uppercase">Phone Number</label>
                    <div id="phone_badge_container">
                        @if($user->phone_verified_at)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>
                        @else
                            <div class="d-flex align-items-center">
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-exclamation-triangle-fill"></i> Unverified</span>
                                <button type="button" class="btn btn-link text-accent text-decoration-none p-0 small ms-2 align-baseline" onclick="openProfileVerifyModal('phone')" style="font-size:0.7rem;">Verify Now</button>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="input-group">
                    <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                    <input type="text" id="phone_display" class="form-control" placeholder="171234567" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncProfilePhoneNumber()" required>
                </div>
                <input type="hidden" name="phone" id="phone_hidden" value="{{ old('phone', $user->phone) }}">
            </div>

            {{-- Demographics --}}
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                <input type="date" name="birthdate" class="form-control" value="{{ $user->birthdate ? $user->birthdate->format('Y-m-d') : '' }}" required max="{{ now()->subYears(18)->format('Y-m-d') }}">
            </div>

            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                <select name="sex" class="form-select" required>
                    <option value="Male" {{ old('sex', $user->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('sex', $user->sex) == 'Female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>

            {{-- Interactive Address Selection --}}
            <div class="col-12 border-top border-secondary border-opacity-10 pt-3 mt-4">
                <h6 class="text-accent smaller fw-bold mb-3 uppercase">Update Home Address</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Province</label>
                        <select id="addr_province" name="province" class="form-select" onchange="fetchCities(this.value)" required>
                            <option value="">Loading Provinces...</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                        <select id="addr_city" name="city" class="form-select" onchange="fetchBarangays(this.value)" disabled required>
                            <option value="">Select Province First</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Barangay</label>
                        <select id="addr_brgy" name="barangay" class="form-select" disabled required>
                            <option value="">Select City First</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                        <input type="text" id="addr_street" name="street" class="form-control uppercase" value="{{ old('street', $user->street) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center mt-4">
            <button type="submit" class="btn-custom btn-accent px-4 uppercase fw-bold">SAVE DETAILS</button>
            <button type="reset" class="btn-custom btn-outline-secondary px-4 uppercase fw-bold ms-2" onclick="resetProfileForm(event)">RESET</button>
        </div>
    </form>
</div>

<!-- EMAIL OTP MODAL -->
<div class="modal fade" id="profileEmailVerifyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1050;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-secondary bg-card text-start" style="background-color: var(--bg-card); color: var(--text-main);">
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0">
                    <i class="bi bi-shield-check-fill me-2 fs-5"></i>Email Verification Code
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form onsubmit="submitProfileOtpCode(event)">
                    <div class="mb-3 text-center">
                        <label class="small text-muted fw-bold mb-1 uppercase d-block text-start">Enter 6-Digit Email Code</label>
                        <div class="d-flex justify-content-between gap-2 my-3 mx-auto" style="max-width: 350px;">
                            @for($i = 0; $i < 6; $i++)
                                <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="{{ $i }}" oninput="handleOtpInput(this, event)" onkeydown="handleOtpKeydown(this, event)">
                            @endfor
                        </div>
                        <input type="hidden" name="otp" id="otp_hidden">
                        <div id="otp_error_msg" class="text-danger small mt-2 d-none fw-bold text-center"></div>
                    </div>
                    <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm uppercase">SUBMIT CODE</button>
                </form>
                <div class="col-12 mt-3 text-center">
                    <form onsubmit="sendProfileOtpCode(event)">
                        <span class="small text-muted">Didn't receive the email code?</span>
                        <button id="otp-resend-btn" type="submit" class="btn btn-link text-accent fw-bold text-decoration-none p-0 small ms-1 align-baseline" style="font-size:0.85rem;">SEND CODE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PHONE SMS OTP MODAL -->
<div class="modal fade" id="profilePhoneVerifyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1050;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-secondary bg-card text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0">
                    <i class="bi bi-chat-dots-fill me-2 fs-5"></i>Phone SMS Verification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form onsubmit="submitProfilePhoneOtpCode(event)">
                    <div class="mb-3 text-center">
                        <label class="small text-muted fw-bold mb-1 uppercase d-block text-start">Enter 6-Digit SMS Verification Code</label>
                        <div class="d-flex justify-content-between gap-2 my-3 mx-auto" style="max-width: 350px;">
                            @for($i = 0; $i < 6; $i++)
                                <input type="text" class="form-control otp-box phone-otp-box text-center fw-bold fs-3" maxlength="1" data-index="{{ $i }}" oninput="handlePhoneOtpInput(this, event)" onkeydown="handlePhoneOtpKeydown(this, event)">
                            @endfor
                        </div>
                        <input type="hidden" name="phone_otp" id="phone_otp_hidden">
                        <div id="phone_otp_error_msg" class="text-danger small mt-2 d-none fw-bold text-center"></div>
                    </div>
                    <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm uppercase">VERIFY PHONE CODE</button>
                </form>
                <div class="col-12 mt-3 text-center">
                    <form onsubmit="sendProfilePhoneOtpCode(event)">
                        <span class="small text-muted">Didn't receive the SMS code?</span>
                        <button id="phone-otp-resend-btn" type="submit" class="btn btn-link text-accent fw-bold text-decoration-none p-0 small ms-1 align-baseline" style="font-size:0.85rem;">SEND SMS CODE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SETTINGS OMISSIONS ERROR MODAL -->
<div class="modal fade" id="profileValidationErrorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1060;">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-danger bg-card text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid #dc3545; color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-exclamation-triangle-fill text-danger display-4 d-block animate-pulse"></i>
            </div>
            <h5 class="text-danger fw-bold mb-2 uppercase tracking-tighter">Omissions Found</h5>
            <div id="profile_validation_error_msg" class="text-secondary small mb-4 text-start">Please complete all required fields correctly before saving changes.</div>
            <button type="button" class="btn btn-danger w-100 py-2.5 uppercase fw-bold" onclick="bootstrap.Modal.getInstance(document.getElementById('profileValidationErrorModal')).hide()">Understood</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// --- SHIELDED STORAGE WRAPPER ---
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

const originalEmail = "{{ $user->email }}";
const originalPhone = "{{ $user->phone }}";

// Evaluate email_verified_at directly to prevent phone-only verification from faking email status
let emailVerifiedLocally = {{ $user->email_verified_at ? 'true' : 'false' }};
let verifiedEmailValue = originalEmail;

let phoneVerifiedLocally = {{ $user->phone_verified_at ? 'true' : 'false' }};
let verifiedPhoneValue = originalPhone;

let isSubmittingForm = false;

// --- DYNAMIC BADGE SYNCHRONIZERS ON INPUT ---
function handleProfileEmailInput() {
    const emailInput = document.getElementById('prof_email');
    const badgeContainer = document.getElementById('email_badge_container');
    if (!emailInput || !badgeContainer) return;

    const currentVal = emailInput.value.trim().toLowerCase();
    const isVerified = emailVerifiedLocally && currentVal === verifiedEmailValue.toLowerCase();

    if (isVerified) {
        badgeContainer.innerHTML = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>';
    } else {
        badgeContainer.innerHTML = '<div class="d-flex align-items-center"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-exclamation-triangle-fill"></i> Unverified</span><button type="button" class="btn btn-link text-accent text-decoration-none p-0 small ms-2 align-baseline" onclick="openProfileVerifyModal(\'email\')" style="font-size:0.7rem;">Verify Now</button></div>';
    }
}

function handleProfilePhoneInput() {
    const phoneInput = document.getElementById('phone_hidden');
    const phoneBadgeContainer = document.getElementById('phone_badge_container');
    if (!phoneInput || !phoneBadgeContainer) return;

    const currentVal = phoneInput.value.trim();
    const isVerified = phoneVerifiedLocally && currentVal === verifiedPhoneValue;

    if (isVerified) {
        phoneBadgeContainer.innerHTML = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>';
    } else {
        phoneBadgeContainer.innerHTML = '<div class="d-flex align-items-center"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-exclamation-triangle-fill"></i> Unverified</span><button type="button" class="btn btn-link text-accent text-decoration-none p-0 small ms-2 align-baseline" onclick="openProfileVerifyModal(\'phone\')" style="font-size:0.7rem;">Verify Now</button></div>';
    }
}

// --- INLINE FIELD ERROR HANDLER ---
function showFieldError(inputElement, errorMessage) {
    if (!inputElement) return;
    inputElement.classList.add('is-invalid');
    let parent = inputElement.parentElement;
    let targetParent = parent.classList.contains('input-group') ? parent.parentElement : parent;
    let existingError = targetParent.querySelector('.invalid-feedback-inline');
    if (existingError) {
        existingError.innerText = errorMessage;
        existingError.classList.remove('d-none');
    } else {
        let errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback-inline text-danger small mt-1 fw-bold';
        errorDiv.innerText = errorMessage;
        targetParent.appendChild(errorDiv);
    }

    const dismissHandler = () => {
        inputElement.classList.remove('is-invalid');
        let errorDiv = targetParent.querySelector('.invalid-feedback-inline');
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

// --- DYNAMIC NAME VALIDATOR ---
function validateName(value) {
    const val = value.trim();
    if (!val) return { valid: false, message: "is required." };

    const charRegex = /^[a-zA-ZñÑ \s.\'-]+$/;
    if (!charRegex.test(val)) {
        return { valid: false, message: "may only contain letters, spaces, periods, hyphens, and apostrophes." };
    }

    const startRegex = /^[a-zA-ZñÑ ]/;
    if (!startRegex.test(val)) {
        return { valid: false, message: "must start with a letter." };
    }

    const letterRegex = /[a-zA-ZñÑ ]/;
    if (!letterRegex.test(val)) {
        return { valid: false, message: "must contain at least one letter." };
    }

    const consecutiveRegex = /[.\'-]{2,}/;
    if (consecutiveRegex.test(val)) {
        return { valid: false, message: "cannot contain consecutive punctuation marks." };
    }

    return { valid: true };
}

// --- FORM VALIDATOR & INTERCEPTOR ---
function validateProfileForm(event) {
    const form = event.target;
    let errorsCount = 0;

    form.querySelectorAll('.invalid-feedback-inline').forEach(el => {
        el.classList.add('d-none');
        el.innerText = '';
    });
    form.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });

    const fName = form.querySelector('[name="first_name"]');
    const mName = document.getElementById('profile_middle_name');
    const lName = form.querySelector('[name="last_name"]');
    const suffix = document.getElementById('suffix');
    const bday = form.querySelector('[name="birthdate"]');
    const email = form.querySelector('[name="email"]');
    const displayPhone = document.getElementById('phone_display');
    const hiddenPhone = document.getElementById('phone_hidden');

    if (fName) {
        const check = validateName(fName.value);
        if (!check.valid) {
            showFieldError(fName, "First Name " + check.message);
            errorsCount++;
        } else if (fName.value.trim().length > 60) {
            showFieldError(fName, "First Name cannot exceed 60 characters.");
            errorsCount++;
        }
    }

    if (mName && mName.value !== 'N/A' && mName.value.trim() !== '') {
        const check = validateName(mName.value);
        if (!check.valid) {
            showFieldError(mName, "Middle Name " + check.message);
            errorsCount++;
        } else if (mName.value.trim().length > 60) {
            showFieldError(mName, "Middle Name cannot exceed 60 characters.");
            errorsCount++;
        }
    }

    if (lName) {
        const check = validateName(lName.value);
        if (!check.valid) {
            showFieldError(lName, "Last Name " + check.message);
            errorsCount++;
        } else if (lName.value.trim().length > 60) {
            showFieldError(lName, "Last Name cannot exceed 60 characters.");
            errorsCount++;
        }
    }

    if (suffix && suffix.value.trim() !== '') {
        const sVal = suffix.value.trim();
        const suffixRegex = /^[a-zA-Z\s.]+$/;
        if (!suffixRegex.test(sVal)) {
            showFieldError(suffix, "Suffix may only contain letters, spaces, and periods.");
            errorsCount++;
        } else if (sVal.length > 10) {
            showFieldError(suffix, "Suffix cannot exceed 10 characters.");
            errorsCount++;
        }
    }

    if (bday) {
        if (!bday.value) {
            showFieldError(bday, "Birthdate is required.");
            errorsCount++;
        } else {
            const dob = new Date(bday.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
            if (age < 18) {
                showFieldError(bday, "You must be at least 18 years old.");
                errorsCount++;
            }
        }
    }

    if (displayPhone) {
        const val = displayPhone.value.trim();
        if (!val) {
            showFieldError(displayPhone, "Phone Number is required.");
            errorsCount++;
        } else if (val.length !== 9) {
            showFieldError(displayPhone, "The phone number must contain exactly 11 digits.");
            errorsCount++;
        }
    }

    let currentEmail = '';
    if (email) {
        currentEmail = email.value.trim();
        if (!currentEmail) {
            showFieldError(email, "Email Address is required.");
            errorsCount++;
        } else {
            const emailRegex = /^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(currentEmail)) {
                showFieldError(email, "Please enter a valid email address.");
                errorsCount++;
            }
        }
    }

    if (errorsCount > 0) {
        event.preventDefault();
        event.stopPropagation();
        return false;
    }

    // Intercept unverified email changes
    if (currentEmail && currentEmail !== originalEmail) {
        if (!emailVerifiedLocally || currentEmail !== verifiedEmailValue) {
            event.preventDefault();
            event.stopPropagation();
            isSubmittingForm = true;
            openProfileVerifyModal('email');
            return false;
        }
    }

    // Intercept unverified phone changes
    const currentPhone = hiddenPhone ? hiddenPhone.value.trim() : '';
    if (currentPhone && currentPhone !== originalPhone) {
        if (!phoneVerifiedLocally || currentPhone !== verifiedPhoneValue) {
            event.preventDefault();
            event.stopPropagation();
            isSubmittingForm = true;
            openProfileVerifyModal('phone');
            return false;
        }
    }

    compileProfileAddress();
    return true;
}

// --- FORM RESET ---
function resetProfileForm(event) {
    setTimeout(async () => {
        const initialPhone = "{{ $user->phone }}";
        const displayInput = document.getElementById('phone_display');
        const hiddenInput = document.getElementById('phone_hidden');
        if (displayInput && hiddenInput) {
            let cleanPhone = initialPhone.trim();
            if (cleanPhone.startsWith('+639')) cleanPhone = '09' + cleanPhone.substring(4);
            else if (cleanPhone.startsWith('639')) cleanPhone = '09' + cleanPhone.substring(3);

            if (cleanPhone && (cleanPhone.startsWith('09') || cleanPhone.startsWith('9')) && (cleanPhone.length === 11 || cleanPhone.length === 10)) {
                let suffixVal = cleanPhone.startsWith('09') ? cleanPhone.substring(2) : cleanPhone.substring(1);
                displayInput.value = suffixVal;
                hiddenInput.value = '09' + suffixVal;
            } else {
                displayInput.value = cleanPhone;
                hiddenInput.value = cleanPhone;
            }
        }

        const mnCheck = document.getElementById('profile_no_mn');
        const input = document.getElementById('profile_middle_name');
        if (mnCheck && input) {
            input.readOnly = mnCheck.checked;
            input.classList.toggle('opacity-50', mnCheck.checked);
        }

        emailVerifiedLocally = {{ $user->email_verified_at ? 'true' : 'false' }};
        verifiedEmailValue = originalEmail;
        phoneVerifiedLocally = {{ $user->phone_verified_at ? 'true' : 'false' }};
        verifiedPhoneValue = originalPhone;
        isSubmittingForm = false;

        const badgeContainer = document.getElementById('email_badge_container');
        if (badgeContainer) {
            badgeContainer.innerHTML = emailVerifiedLocally
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>'
                : '<div class="d-flex align-items-center"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-exclamation-triangle-fill"></i> Unverified</span><button type="button" class="btn btn-link text-accent text-decoration-none p-0 small ms-2 align-baseline" onclick="openProfileVerifyModal(\'email\')" style="font-size:0.7rem;">Verify Now</button></div>';
        }

        const phoneBadgeContainer = document.getElementById('phone_badge_container');
        if (phoneBadgeContainer) {
            phoneBadgeContainer.innerHTML = phoneVerifiedLocally
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>'
                : '<div class="d-flex align-items-center"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-exclamation-triangle-fill"></i> Unverified</span><button type="button" class="btn btn-link text-accent text-decoration-none p-0 small ms-2 align-baseline" onclick="openProfileVerifyModal(\'phone\')" style="font-size:0.7rem;">Verify Now</button></div>';
        }

        document.querySelectorAll('.invalid-feedback-inline').forEach(el => {
            el.classList.add('d-none');
            el.innerText = '';
        });
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        await initializeAddress();
    }, 50);
}

// --- MODAL TRIGGERS ---
function openProfileVerifyModal(type) {
    if (type === 'email') {
        const emailModal = new bootstrap.Modal(document.getElementById('profileEmailVerifyModal'));
        emailModal.show();
        const firstBox = document.querySelector('.otp-box:not(.phone-otp-box)[data-index="0"]');
        if (firstBox) setTimeout(() => firstBox.focus(), 150);
        triggerAutoSendProfileOtp();
    } else {
        const phoneModal = new bootstrap.Modal(document.getElementById('profilePhoneVerifyModal'));
        phoneModal.show();
        const firstPhoneBox = document.querySelector('.phone-otp-box[data-index="0"]');
        if (firstPhoneBox) setTimeout(() => firstPhoneBox.focus(), 150);
        triggerAutoSendProfilePhoneOtp();
    }
}

// --- DIGIT BOX CONTROLLERS ---
function handleOtpInput(input, event) {
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value.length === 1) {
        const nextIndex = parseInt(input.dataset.index) + 1;
        const nextInput = document.querySelector(`.otp-box:not(.phone-otp-box)[data-index="${nextIndex}"]`);
        if (nextInput) nextInput.focus();
    }
    compileOtpValue();
}

function handleOtpKeydown(input, event) {
    if (event.key === 'Backspace') {
        if (input.value === '') {
            const prevIndex = parseInt(input.dataset.index) - 1;
            const prevInput = document.querySelector(`.otp-box:not(.phone-otp-box)[data-index="${prevIndex}"]`);
            if (prevInput) {
                prevInput.focus();
                prevInput.value = '';
            }
        } else {
            input.value = '';
        }
        compileOtpValue();
    }
}

function compileOtpValue() {
    let compiled = '';
    document.querySelectorAll('.otp-box:not(.phone-otp-box)').forEach(box => {
        compiled += box.value;
    });
    document.getElementById('otp_hidden').value = compiled;
    return compiled;
}

function handlePhoneOtpInput(input, event) {
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value.length === 1) {
        const nextIndex = parseInt(input.dataset.index) + 1;
        const nextInput = document.querySelector(`.phone-otp-box[data-index="${nextIndex}"]`);
        if (nextInput) nextInput.focus();
    }
    compilePhoneOtpValue();
}

function handlePhoneOtpKeydown(input, event) {
    if (event.key === 'Backspace') {
        if (input.value === '') {
            const prevIndex = parseInt(input.dataset.index) - 1;
            const prevInput = document.querySelector(`.phone-otp-box[data-index="${prevIndex}"]`);
            if (prevInput) {
                prevInput.focus();
                prevInput.value = '';
            }
        } else {
            input.value = '';
        }
        compilePhoneOtpValue();
    }
}

function compilePhoneOtpValue() {
    let compiled = '';
    document.querySelectorAll('.phone-otp-box').forEach(box => {
        compiled += box.value;
    });
    document.getElementById('phone_otp_hidden').value = compiled;
    return compiled;
}

// --- EMAIL OTP DISPATCH & VERIFY (DELEGATED TO SERVER) ---
async function sendProfileOtpCode(event) {
    if (event) event.preventDefault();
    await triggerAutoSendProfileOtp();
}

async function triggerAutoSendProfileOtp() {
    const btn = document.getElementById('otp-resend-btn');
    const emailInput = document.getElementById('prof_email');
    const errorMsgDiv = document.getElementById('otp_error_msg');
    if (errorMsgDiv) {
        errorMsgDiv.classList.add('d-none');
        errorMsgDiv.innerText = '';
    }
    if (!emailInput || !emailInput.value.trim()) return;

    const now = Date.now();
    const cooldownExpiry = safeStorage.getItem('resend_cooldown_expiry');
    if (cooldownExpiry && now < parseInt(cooldownExpiry)) return;

    if (btn) {
        btn.disabled = true;
        btn.innerText = "SENDING...";
    }

    try {
        await fetch("{{ route('verification.send-otp') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ email: emailInput.value.trim(), channel: 'email' })
        });
        const expiryTime = now + (30 * 1000);
        safeStorage.setItem('resend_cooldown_expiry', expiryTime.toString());
        updateEmailResendState();
    } catch (e) {
        if (btn) {
            btn.disabled = false;
            btn.innerText = "SEND CODE";
        }
    }
}

function updateEmailResendState() {
    const btn = document.getElementById('otp-resend-btn');
    if (!btn) return;
    const now = Date.now();
    const cooldownExpiry = safeStorage.getItem('resend_cooldown_expiry');

    if (cooldownExpiry && now < parseInt(cooldownExpiry)) {
        const remaining = Math.ceil((parseInt(cooldownExpiry) - now) / 1000);
        btn.disabled = true;
        btn.innerText = `RESEND IN ${remaining}S`;
        setTimeout(updateEmailResendState, 1000);
    } else {
        btn.disabled = false;
        btn.innerText = "SEND CODE";
    }
}

async function submitProfileOtpCode(event) {
    event.preventDefault();
    const otpCode = compileOtpValue();
    const errorMsgDiv = document.getElementById('otp_error_msg');
    const emailInput = document.getElementById('prof_email');
    const hiddenPhone = document.getElementById('phone_hidden');

    if (errorMsgDiv) {
        errorMsgDiv.classList.add('d-none');
        errorMsgDiv.innerText = '';
    }

    if (otpCode.length !== 6) {
        if (errorMsgDiv) {
            errorMsgDiv.innerText = "Please enter all 6 digits of the code.";
            errorMsgDiv.classList.remove('d-none');
        }
        return;
    }

    try {
        const response = await fetch("{{ route('verification.verify-otp') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                otp: otpCode,
                email: emailInput ? emailInput.value.trim() : originalEmail,
                channel: 'email'
            })
        });

        const data = await response.json();
        if (data.success) {
            emailVerifiedLocally = true;
            verifiedEmailValue = data.email || (emailInput ? emailInput.value.trim() : originalEmail);

            const modal = bootstrap.Modal.getInstance(document.getElementById('profileEmailVerifyModal'));
            if (modal) modal.hide();

            const badgeContainer = document.getElementById('email_badge_container');
            if (badgeContainer) {
                badgeContainer.innerHTML = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>';
            }

            if (isSubmittingForm) {
                const currentPhone = hiddenPhone ? hiddenPhone.value.trim() : '';
                if (currentPhone && currentPhone !== originalPhone && (!phoneVerifiedLocally || currentPhone !== verifiedPhoneValue)) {
                    openProfileVerifyModal('phone');
                    return;
                }
                const mainForm = document.getElementById('profileUpdateForm');
                compileProfileAddress();
                mainForm.submit();
            }
        } else {
            if (errorMsgDiv) {
                errorMsgDiv.innerText = data.message || "Incorrect verification code.";
                errorMsgDiv.classList.remove('d-none');
            }
        }
    } catch (e) {
        if (errorMsgDiv) {
            errorMsgDiv.innerText = "Verification failed. Incorrect code or connection error.";
            errorMsgDiv.classList.remove('d-none');
        }
    }
}

// --- PHONE SMS OTP DISPATCH & VERIFY (DELEGATED TO SERVER) ---
async function sendProfilePhoneOtpCode(event) {
    if (event) event.preventDefault();
    await triggerAutoSendProfilePhoneOtp();
}

async function triggerAutoSendProfilePhoneOtp() {
    const btn = document.getElementById('phone-otp-resend-btn');
    const hiddenPhone = document.getElementById('phone_hidden');
    const errorMsgDiv = document.getElementById('phone_otp_error_msg');

    if (errorMsgDiv) {
        errorMsgDiv.classList.add('d-none');
        errorMsgDiv.innerText = '';
    }

    const phoneVal = hiddenPhone ? hiddenPhone.value.trim() : '';
    if (!phoneVal) return;

    const now = Date.now();
    const cooldownExpiry = safeStorage.getItem('phone_resend_cooldown');
    if (cooldownExpiry && now < parseInt(cooldownExpiry)) return;

    if (btn) {
        btn.disabled = true;
        btn.innerText = "SENDING SMS...";
    }

    try {
        await fetch("{{ route('verification.send-sms-otp') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ phone: phoneVal, channel: 'sms' })
        });
        const expiryTime = now + (30 * 1000);
        safeStorage.setItem('phone_resend_cooldown', expiryTime.toString());
        updateProfilePhoneResendState();
    } catch (e) {
        const expiryTime = now + (30 * 1000);
        safeStorage.setItem('phone_resend_cooldown', expiryTime.toString());
        updateProfilePhoneResendState();
    }
}

function updateProfilePhoneResendState() {
    const btn = document.getElementById('phone-otp-resend-btn');
    if (!btn) return;
    const now = Date.now();
    const cooldownExpiry = safeStorage.getItem('phone_resend_cooldown');

    if (cooldownExpiry && now < parseInt(cooldownExpiry)) {
        const remaining = Math.ceil((parseInt(cooldownExpiry) - now) / 1000);
        btn.disabled = true;
        btn.innerText = `RESEND IN ${remaining}S`;
        setTimeout(updateProfilePhoneResendState, 1000);
    } else {
        btn.disabled = false;
        btn.innerText = "SEND SMS CODE";
    }
}

async function submitProfilePhoneOtpCode(event) {
    event.preventDefault();
    const otpCode = compilePhoneOtpValue();
    const errorMsgDiv = document.getElementById('phone_otp_error_msg');
    const hiddenPhone = document.getElementById('phone_hidden');

    if (errorMsgDiv) {
        errorMsgDiv.classList.add('d-none');
        errorMsgDiv.innerText = '';
    }

    if (otpCode.length !== 6) {
        if (errorMsgDiv) {
            errorMsgDiv.innerText = "Please enter all 6 digits of the SMS code.";
            errorMsgDiv.classList.remove('d-none');
        }
        return;
    }

    try {
        const response = await fetch("{{ route('verification.verify-otp') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                otp: otpCode,
                phone: hiddenPhone ? hiddenPhone.value.trim() : originalPhone,
                channel: 'phone'
            })
        });

        const data = await response.json();
        if (data.success) {
            phoneVerifiedLocally = true;
            verifiedPhoneValue = hiddenPhone ? hiddenPhone.value.trim() : originalPhone;

            const modal = bootstrap.Modal.getInstance(document.getElementById('profilePhoneVerifyModal'));
            if (modal) modal.hide();

            const phoneBadgeContainer = document.getElementById('phone_badge_container');
            if (phoneBadgeContainer) {
                phoneBadgeContainer.innerHTML = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;"><i class="bi bi-patch-check-fill"></i> Verified</span>';
            }

            if (isSubmittingForm) {
                const mainForm = document.getElementById('profileUpdateForm');
                compileProfileAddress();
                mainForm.submit();
            }
        } else {
            if (errorMsgDiv) {
                errorMsgDiv.innerText = data.message || "Invalid or expired verification code.";
                errorMsgDiv.classList.remove('d-none');
            }
        }
    } catch (e) {
        if (errorMsgDiv) {
            errorMsgDiv.innerText = "Verification failed. Incorrect code or connection error.";
            errorMsgDiv.classList.remove('d-none');
        }
    }
}

// --- PHONE DISPLAY SYNC & DYNAMIC ADDRESS ---
function syncProfilePhoneNumber() {
    const displayInput = document.getElementById('phone_display');
    const hiddenInput = document.getElementById('phone_hidden');
    if (displayInput && hiddenInput) {
        hiddenInput.value = displayInput.value ? '09' + displayInput.value : '';
        handleProfilePhoneInput();
    }
}

const apiBaseUrl = "https://psgc.gitlab.io/api";

async function fetchProvinces() {
    try {
        let res = await fetch(`${apiBaseUrl}/provinces.json`);
        const data = await res.json();
        const sel = document.getElementById('addr_province');
        if (sel) {
            sel.innerHTML = '<option value="">Select Province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                sel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
            });
        }
    } catch (e) {
        console.error("Province API Error", e);
    }
}

async function fetchCities(provCode) {
    const citySel = document.getElementById('addr_city');
    const brgySel = document.getElementById('addr_brgy');
    if (!citySel || !brgySel) return;
    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';

    try {
        let res = await fetch(`${apiBaseUrl}/provinces/${provCode}/cities-municipalities.json`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;
    } catch (e) {
        console.error("City API Error", e);
    }
}

async function fetchBarangays(cityCode) {
    const brgySel = document.getElementById('addr_brgy');
    if (!brgySel) return;
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    try {
        let res = await fetch(`${apiBaseUrl}/cities-municipalities/${cityCode}/barangays.json`);
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

function compileProfileAddress() {
    const street = document.getElementById('addr_street').value.trim();
    const brgy = document.getElementById('addr_brgy');
    const city = document.getElementById('addr_city');
    const prov = document.getElementById('addr_province');

    if (street && brgy && city && prov) {
        const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
        const cityName = city.options[city.selectedIndex]?.text || '';
        const provName = prov.options[prov.selectedIndex]?.text || '';

        if (brgyName && cityName && provName) {
            prov.options[prov.selectedIndex].value = provName;
            city.options[city.selectedIndex].value = cityName;
            brgy.options[brgy.selectedIndex].value = brgyName;
            document.getElementById('profile_address_hidden').value = `${street}, BRGY. ${brgyName}, ${cityName}, ${provName}`.toUpperCase();
        }
    }
}

// --- INITIALIZATION ---
const savedProvince = "{{ trim(old('province', $user->province)) }}";
const savedCity = "{{ trim(old('city', $user->city)) }}";
const savedBarangay = "{{ trim(old('barangay', $user->barangay)) }}";

async function initializeAddress() {
    await fetchProvinces();
    if (savedProvince) {
        const provSel = document.getElementById('addr_province');
        if (provSel) {
            let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase().trim() === savedProvince.toUpperCase().trim() || opt.value === savedProvince.trim());
            if (provOpt) {
                provSel.value = provOpt.value;
                await fetchCities(provOpt.value);
                const citySel = document.getElementById('addr_city');
                if (citySel) {
                    let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase().trim() === savedCity.toUpperCase().trim() || opt.value === savedCity.trim());
                    if (cityOpt) {
                        citySel.value = cityOpt.value;
                        await fetchBarangays(cityOpt.value);
                        const brgySel = document.getElementById('addr_brgy');
                        if (brgySel) {
                            let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase().trim() === savedBarangay.toUpperCase().trim() || opt.value === savedBarangay.trim());
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

document.addEventListener('DOMContentLoaded', async () => {
    await initializeAddress();

    let hiddenPhone = document.getElementById('phone_hidden').value.trim();
    if (hiddenPhone.startsWith('+639')) hiddenPhone = '09' + hiddenPhone.substring(4);
    else if (hiddenPhone.startsWith('639')) hiddenPhone = '09' + hiddenPhone.substring(3);

    if (hiddenPhone && (hiddenPhone.startsWith('09') || hiddenPhone.startsWith('9')) && (hiddenPhone.length === 11 || hiddenPhone.length === 10)) {
        let suffixVal = hiddenPhone.startsWith('09') ? hiddenPhone.substring(2) : hiddenPhone.substring(1);
        const displayInput = document.getElementById('phone_display');
        const hiddenInput = document.getElementById('phone_hidden');
        if (displayInput) displayInput.value = suffixVal;
        if (hiddenInput) hiddenInput.value = '09' + suffixVal;
    } else {
        const displayInput = document.getElementById('phone_display');
        if (displayInput) displayInput.value = hiddenPhone;
    }

    const mnCheck = document.getElementById('profile_no_mn');
    if (mnCheck && mnCheck.checked) {
        const middleNameEl = document.getElementById('profile_middle_name');
        if (middleNameEl) middleNameEl.classList.add('opacity-50');
    }

    updateEmailResendState();
    updateProfilePhoneResendState();
});
</script>
@endpush