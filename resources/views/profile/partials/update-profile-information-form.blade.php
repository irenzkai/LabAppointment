@php
$emailVerifiedElsewhere = \App\Models\User::where('id', '!=', $user->id)
    ->where('email', strtolower(trim($user->email)))
    ->whereNotNull('email_verified_at')
    ->exists();

$phoneVerifiedElsewhere = !empty($user->phone) && \App\Models\User::where('id', '!=', $user->id)
    ->where('phone', trim($user->phone))
    ->whereNotNull('phone_verified_at')
    ->exists();

$cleanPhoneSuffix = '';
if (!empty($user->phone)) {
    $rawPhone = trim($user->phone);
    if (str_starts_with($rawPhone, '+639') && strlen($rawPhone) === 13) {
        $cleanPhoneSuffix = substr($rawPhone, 4);
    } elseif (str_starts_with($rawPhone, '639') && strlen($rawPhone) === 12) {
        $cleanPhoneSuffix = substr($rawPhone, 3);
    } elseif (str_starts_with($rawPhone, '09') && strlen($rawPhone) === 11) {
        $cleanPhoneSuffix = substr($rawPhone, 2);
    } elseif (str_starts_with($rawPhone, '9') && strlen($rawPhone) === 10) {
        $cleanPhoneSuffix = substr($rawPhone, 1);
    } else {
        $cleanPhoneSuffix = $rawPhone;
    }
}
@endphp

<div class="card p-4 p-md-5 border-secondary shadow-lg">
    <h5 class="text-main fw-bold mb-4 border-bottom border-secondary border-opacity-25 pb-2 uppercase" style="letter-spacing: 1px;">Personal Information</h5>

    {{-- Active Address on File --}}
    <div class="alert alert-clinical p-3 mb-4 text-start">
        <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Registered Address on File:</div>
        <div class="text-main small" id="profile_address_on_file">{{ $user->address }}</div>
    </div>

    {{-- Notification Toast for In-Place Email/Phone Updates --}}
    <div id="contact_update_alert" class="alert alert-clinical alert-dismissible fade show d-none align-items-center mb-4 shadow-sm" style="background-color: rgba(25, 211, 140, 0.08); border-left: 4px solid var(--brand-accent) !important; border-radius: 8px;">
        <i class="bi bi-check-circle-fill me-3 fs-5 text-accent"></i>
        <div class="text-start">
            <div class="fw-800 uppercase fs-x-small text-accent" style="font-size: 0.72rem; letter-spacing: 0.5px;">Update Successful</div>
            <div class="small text-main" id="contact_update_alert_msg" style="color: var(--text-main) !important; font-size: 0.85rem;"></div>
        </div>
        <button type="button" class="btn-close ms-auto" onclick="document.getElementById('contact_update_alert').classList.add('d-none')"></button>
    </div>

    <form method="post" action="{{ route('profile.update') }}" onsubmit="return validateProfileForm(event)" novalidate id="profileUpdateForm">
        @csrf
        @method('patch')

        {{-- Hidden fields keep email, phone, and address populated to satisfy ProfileUpdateRequest without dirty changes --}}
        <input type="hidden" name="email" id="profile_email_hidden" value="{{ $user->email }}">
        <input type="hidden" name="phone" id="profile_phone_hidden" value="{{ $user->phone }}">
        <input type="hidden" name="address" id="profile_address_hidden" value="{{ $user->address }}">

        <div class="row g-3 text-start">
            {{-- Name Separation --}}
            <div class="col-md-3">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">First Name <span class="text-danger">*</span></label>
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
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Last Name <span class="text-danger">*</span></label>
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

            {{-- Demographics --}}
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate <span class="text-danger">*</span></label>
                <input type="date" name="birthdate" class="form-control" value="{{ $user->birthdate ? $user->birthdate->format('Y-m-d') : '' }}" required max="{{ now()->subYears(18)->format('Y-m-d') }}">
            </div>

            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex <span class="text-danger">*</span></label>
                <select name="sex" class="form-select" required>
                    <option value="Male" {{ old('sex', $user->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('sex', $user->sex) == 'Female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>

            {{-- SEPARATED CONTACT INFORMATION (Readonly in Save Details, Modified independently via Modal) --}}
            <div class="col-12 mt-4">
                <div class="d-flex justify-content-between align-items-center border-top border-secondary border-opacity-10 pt-3 mb-2">
                    <h6 class="text-accent smaller fw-bold mb-0 uppercase tracking-wider">Account Credentials & Verification</h6>
                    <small class="text-secondary" style="font-size: 0.7rem;"><i class="bi bi-shield-check text-accent me-1"></i>Verified credentials grant portal access</small>
                </div>
            </div>

            {{-- Readonly Email Field --}}
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="smaller text-secondary fw-bold mb-0 uppercase">Email Address</label>
                    <div id="email_badge_container" class="d-flex align-items-center gap-1.5">
                        @if($user->email_verified_at)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                                <i class="bi bi-patch-check-fill"></i> Verified
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-accent py-0.5 px-2 fw-bold text-uppercase ms-1" onclick="openChangeEmailModal()" style="font-size:0.68rem;">
                                <i class="bi bi-pencil-square me-1"></i> Change
                            </button>
                        @elseif($emailVerifiedElsewhere)
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                                <i class="bi bi-exclamation-octagon-fill"></i> In Use Elsewhere
                            </span>
                            <button type="button" class="btn btn-sm btn-accent py-0.5 px-2 text-dark fw-bold text-uppercase ms-1" onclick="openChangeEmailModal()" style="font-size:0.68rem;">
                                Update
                            </button>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Unverified
                            </span>
                            <button type="button" class="btn btn-sm btn-accent py-0.5 px-2 text-dark fw-bold text-uppercase ms-1" onclick="openVerifyCurrentEmailModal()" style="font-size:0.68rem;">
                                Verify Now
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-accent py-0.5 px-2 fw-bold text-uppercase ms-1" onclick="openChangeEmailModal()" style="font-size:0.68rem;">
                                Change
                            </button>
                        @endif
                    </div>
                </div>
                <div class="input-group">
                    <span class="input-group-text border-secondary bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-envelope-fill"></i></span>
                    <input type="email" id="prof_email_display" class="form-control" value="{{ $user->email }}" readonly style="background-color: rgba(0, 0, 0, 0.03); cursor: default;">
                </div>
            </div>

            {{-- Readonly Phone Field --}}
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="smaller text-secondary fw-bold mb-0 uppercase">Mobile Phone</label>
                    <div id="phone_badge_container" class="d-flex align-items-center gap-1.5">
                        @if($user->phone_verified_at)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                                <i class="bi bi-patch-check-fill"></i> Verified
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-accent py-0.5 px-2 fw-bold text-uppercase ms-1" onclick="openChangePhoneModal()" style="font-size:0.68rem;">
                                <i class="bi bi-pencil-square me-1"></i> Change
                            </button>
                        @elseif($phoneVerifiedElsewhere)
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                                <i class="bi bi-exclamation-octagon-fill"></i> In Use Elsewhere
                            </span>
                            <button type="button" class="btn btn-sm btn-accent py-0.5 px-2 text-dark fw-bold text-uppercase ms-1" onclick="openChangePhoneModal()" style="font-size:0.68rem;">
                                Update
                            </button>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Unverified
                            </span>
                            <button type="button" class="btn btn-sm btn-accent py-0.5 px-2 text-dark fw-bold text-uppercase ms-1" onclick="openVerifyCurrentPhoneModal()" style="font-size:0.68rem;">
                                Verify Now
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-accent py-0.5 px-2 fw-bold text-uppercase ms-1" onclick="openChangePhoneModal()" style="font-size:0.68rem;">
                                Change
                            </button>
                        @endif
                    </div>
                </div>
                <div class="input-group">
                    <span class="input-group-text border-secondary bg-secondary bg-opacity-10 text-secondary fw-bold">09</span>
                    <input type="text" id="phone_display_readonly" class="form-control" value="{{ $cleanPhoneSuffix }}" readonly style="background-color: rgba(0, 0, 0, 0.03); cursor: default;">
                </div>
            </div>

            {{-- Interactive Address Selection --}}
            <div class="col-12 border-top border-secondary border-opacity-10 pt-3 mt-4">
                <h6 class="text-accent smaller fw-bold mb-3 uppercase">Update Home Address</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Province <span class="text-danger">*</span></label>
                        <select id="addr_province" name="province" class="form-select" onchange="fetchCities(this.value)" required>
                            <option value="">Select Province</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">City / Municipality <span class="text-danger">*</span></label>
                        <select id="addr_city" name="city" class="form-select" onchange="fetchBarangays(this.value)" disabled required>
                            <option value="">Select Province First</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Barangay <span class="text-danger">*</span></label>
                        <select id="addr_brgy" name="barangay" class="form-select" disabled required>
                            <option value="">Select City First</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Street / House No. <span class="text-danger">*</span></label>
                        <input type="text" id="addr_street" name="street" class="form-control uppercase" value="{{ old('street', $user->street) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center mt-4 pt-2">
            <button type="submit" class="btn-custom btn-accent px-4 uppercase fw-bold shadow-sm">SAVE DETAILS</button>
            <button type="reset" class="btn-custom btn-outline-secondary px-4 uppercase fw-bold ms-2" onclick="resetProfileForm(event)">RESET</button>
        </div>
    </form>
</div>

<!-- =========================================================================
     MODAL 1: CONFIRM SAVE PERSONAL DETAILS
     ========================================================================= -->
<div class="modal fade" id="confirmUpdateProfileModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-secondary bg-card text-start shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            <div class="modal-header border-secondary border-opacity-15 bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0">
                    <i class="bi bi-shield-lock-fill me-2 fs-5"></i>Confirm Details Update
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 48px; height: 48px; background-color: rgba(25, 211, 140, 0.1);">
                        <i class="bi bi-person-check-fill fs-3 text-accent"></i>
                    </div>
                    <div>
                        <h6 class="text-main fw-bold mb-1 uppercase" style="font-size: 0.95rem;">Save Personal Information?</h6>
                        <p class="text-muted small mb-0" style="font-size: 0.8rem;">Update your name, birthdate, sex, and residential address details.</p>
                    </div>
                </div>
                <p class="text-muted small mb-0" style="font-size: 0.82rem; line-height: 1.5;">
                    Your revised demographics will be synchronized with your active appointment orders and future test files.
                </p>
            </div>
            <div class="modal-footer border-secondary border-opacity-15 p-3 d-flex gap-2">
                <button type="button" class="btn-custom btn-outline-secondary w-50 py-2.5 small fw-bold uppercase" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn-custom btn-accent w-50 py-2.5 small fw-bold uppercase shadow-sm" onclick="proceedWithProfileUpdate()">CONFIRM & SAVE</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: DEDICATED CHANGE & VERIFY EMAIL MODAL (IN-PLACE OTP)
     ========================================================================= -->
<div class="modal fade" id="profileEmailVerifyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content border-secondary bg-card text-start shadow-lg" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0" id="emailModalTitle">
                    <i class="bi bi-envelope-check-fill me-2 fs-5"></i>Change & Verify Email
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="smaller text-secondary fw-bold mb-1 uppercase d-block text-start">Email Address</label>
                    <div class="input-group">
                        <input type="email" id="modal_email_input" class="form-control" placeholder="new.email@example.com" value="{{ $user->email }}">
                        <button type="button" id="modal_email_send_btn" class="btn btn-outline-accent fw-bold uppercase px-3" onclick="sendModalEmailOtp()" style="font-size: 0.75rem;">
                            SEND CODE
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Enter your email and click Send Code to receive a 6-digit verification OTP.</small>
                </div>

                {{-- OTP Input Area --}}
                <div id="modal_email_otp_section" class="mt-4 pt-3 border-top border-secondary border-opacity-15 text-center">
                    <label class="small text-muted fw-bold mb-1 uppercase d-block text-start">Enter 6-Digit Email Code</label>
                    <div class="d-flex justify-content-between gap-2 my-3 mx-auto" style="max-width: 350px;">
                        @for($i = 0; $i < 6; $i++)
                            <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="{{ $i }}" oninput="handleModalEmailOtpInput(this)" onkeydown="handleModalEmailOtpKeydown(this, event)">
                        @endfor
                    </div>
                    <div id="modal_email_error" class="text-danger small mt-2 d-none fw-bold text-center"></div>

                    <button type="button" id="modal_email_confirm_btn" class="btn-custom btn-accent w-100 py-2.5 fw-bold shadow-sm uppercase mt-2" onclick="submitModalEmailOtp()">
                        VERIFY & UPDATE EMAIL
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 3: DEDICATED CHANGE & VERIFY PHONE MODAL (IN-PLACE SMS OTP)
     ========================================================================= -->
<div class="modal fade" id="profilePhoneVerifyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content border-secondary bg-card text-start shadow-lg" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0" id="phoneModalTitle">
                    <i class="bi bi-chat-dots-fill me-2 fs-5"></i>Change & Verify Mobile Phone
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="smaller text-secondary fw-bold mb-1 uppercase d-block text-start">Mobile Number</label>
                    <div class="input-group">
                        <span class="input-group-text border-secondary bg-secondary bg-opacity-10 text-secondary fw-bold">09</span>
                        <input type="text" id="modal_phone_input" class="form-control" placeholder="171234567" maxlength="9" value="{{ $cleanPhoneSuffix }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <button type="button" id="modal_phone_send_btn" class="btn btn-outline-accent fw-bold uppercase px-3" onclick="sendModalPhoneOtp()" style="font-size: 0.75rem;">
                            SEND SMS
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Enter your 9-digit mobile number after 09 to receive an SMS verification code.</small>
                </div>

                {{-- SMS OTP Input Area --}}
                <div id="modal_phone_otp_section" class="mt-4 pt-3 border-top border-secondary border-opacity-15 text-center">
                    <label class="small text-muted fw-bold mb-1 uppercase d-block text-start">Enter 6-Digit SMS Code</label>
                    <div class="d-flex justify-content-between gap-2 my-3 mx-auto" style="max-width: 350px;">
                        @for($i = 0; $i < 6; $i++)
                            <input type="text" class="form-control otp-box modal-phone-otp-box text-center fw-bold fs-3" maxlength="1" data-index="{{ $i }}" oninput="handleModalPhoneOtpInput(this)" onkeydown="handleModalPhoneOtpKeydown(this, event)">
                        @endfor
                    </div>
                    <div id="modal_phone_error" class="text-danger small mt-2 d-none fw-bold text-center"></div>

                    <button type="button" id="modal_phone_confirm_btn" class="btn-custom btn-accent w-100 py-2.5 fw-bold shadow-sm uppercase mt-2" onclick="submitModalPhoneOtp()">
                        VERIFY & UPDATE PHONE
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentEmail = "{{ strtolower(trim($user->email)) }}";
let currentPhone = "{{ trim($user->phone) }}";
let isProfileFormConfirmed = false;

// Cooldown trackers
let emailCooldownTimer = null;
let phoneCooldownTimer = null;

// --- 1. OPEN MODAL TRIGGERS ---
function openChangeEmailModal() {
    const modalEl = document.getElementById('profileEmailVerifyModal');
    document.getElementById('emailModalTitle').innerHTML = '<i class="bi bi-envelope-check-fill me-2 fs-5"></i>Change & Verify Email';
    document.getElementById('modal_email_input').value = '';
    document.getElementById('modal_email_input').readOnly = false;
    document.getElementById('modal_email_error').classList.add('d-none');
    clearModalEmailOtp();
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    setTimeout(() => document.getElementById('modal_email_input').focus(), 150);
}

function openVerifyCurrentEmailModal() {
    const modalEl = document.getElementById('profileEmailVerifyModal');
    document.getElementById('emailModalTitle').innerHTML = '<i class="bi bi-patch-check-fill me-2 fs-5"></i>Verify Registered Email';
    document.getElementById('modal_email_input').value = currentEmail;
    document.getElementById('modal_email_input').readOnly = true;
    document.getElementById('modal_email_error').classList.add('d-none');
    clearModalEmailOtp();
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    sendModalEmailOtp();
}

function openChangePhoneModal() {
    const modalEl = document.getElementById('profilePhoneVerifyModal');
    document.getElementById('phoneModalTitle').innerHTML = '<i class="bi bi-chat-dots-fill me-2 fs-5"></i>Change & Verify Phone';
    document.getElementById('modal_phone_input').value = '';
    document.getElementById('modal_phone_input').readOnly = false;
    document.getElementById('modal_phone_error').classList.add('d-none');
    clearModalPhoneOtp();
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    setTimeout(() => document.getElementById('modal_phone_input').focus(), 150);
}

function openVerifyCurrentPhoneModal() {
    const modalEl = document.getElementById('profilePhoneVerifyModal');
    document.getElementById('phoneModalTitle').innerHTML = '<i class="bi bi-patch-check-fill me-2 fs-5"></i>Verify Registered Phone';
    let suffix = currentPhone.startsWith('09') ? currentPhone.substring(2) : currentPhone;
    document.getElementById('modal_phone_input').value = suffix;
    document.getElementById('modal_phone_input').readOnly = true;
    document.getElementById('modal_phone_error').classList.add('d-none');
    clearModalPhoneOtp();
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    sendModalPhoneOtp();
}

// --- 2. MODAL EMAIL OTP CONTROLLERS ---
function clearModalEmailOtp() {
    document.querySelectorAll('.otp-box:not(.modal-phone-otp-box)').forEach(el => el.value = '');
}

function getModalEmailOtp() {
    let code = '';
    document.querySelectorAll('.otp-box:not(.modal-phone-otp-box)').forEach(el => code += el.value.trim());
    return code;
}

function handleModalEmailOtpInput(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value.length === 1) {
        const nextIndex = parseInt(input.dataset.index) + 1;
        const next = document.querySelector(`.otp-box:not(.modal-phone-otp-box)[data-index="${nextIndex}"]`);
        if (next) next.focus();
    }
}

function handleModalEmailOtpKeydown(input, event) {
    if (event.key === 'Backspace' && input.value === '') {
        const prevIndex = parseInt(input.dataset.index) - 1;
        const prev = document.querySelector(`.otp-box:not(.modal-phone-otp-box)[data-index="${prevIndex}"]`);
        if (prev) {
            prev.focus();
            prev.value = '';
        }
    }
}

async function sendModalEmailOtp() {
    const emailVal = document.getElementById('modal_email_input').value.trim().toLowerCase();
    const btn = document.getElementById('modal_email_send_btn');
    const errEl = document.getElementById('modal_email_error');

    errEl.classList.add('d-none');
    if (!emailVal || !/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(emailVal)) {
        errEl.innerText = 'Please enter a valid email address.';
        errEl.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerText = 'SENDING...';

    try {
        const res = await fetch("{{ route('verification.send-otp') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: emailVal, channel: 'email' })
        });

        const data = await res.json();
        if (!res.ok) {
            errEl.innerText = data.message || 'Failed to dispatch code. Please try again.';
            errEl.classList.remove('d-none');
            btn.disabled = false;
            btn.innerText = 'SEND CODE';
            return;
        }

        // Start 30s Cooldown
        let remaining = 30;
        btn.innerText = `${remaining}S`;
        clearInterval(emailCooldownTimer);
        emailCooldownTimer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(emailCooldownTimer);
                btn.disabled = false;
                btn.innerText = 'SEND CODE';
            } else {
                btn.innerText = `${remaining}S`;
            }
        }, 1000);

        const firstOtp = document.querySelector('.otp-box:not(.modal-phone-otp-box)[data-index="0"]');
        if (firstOtp) firstOtp.focus();
    } catch (e) {
        btn.disabled = false;
        btn.innerText = 'SEND CODE';
        errEl.innerText = 'Network error occurred. Please try again.';
        errEl.classList.remove('d-none');
    }
}

async function submitModalEmailOtp() {
    const emailVal = document.getElementById('modal_email_input').value.trim().toLowerCase();
    const otpCode = getModalEmailOtp();
    const errEl = document.getElementById('modal_email_error');
    const confirmBtn = document.getElementById('modal_email_confirm_btn');

    errEl.classList.add('d-none');
    if (otpCode.length !== 6) {
        errEl.innerText = 'Please enter all 6 digits of the verification code.';
        errEl.classList.remove('d-none');
        return;
    }

    confirmBtn.disabled = true;
    confirmBtn.innerText = 'VERIFYING...';

    try {
        const res = await fetch("{{ route('verification.verify-otp') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                otp: otpCode,
                email: emailVal,
                channel: 'email'
            })
        });

        const data = await res.json();
        confirmBtn.disabled = false;
        confirmBtn.innerText = 'VERIFY & UPDATE EMAIL';

        if (data.success) {
            // Update Readonly Display & Hidden Inputs in Main Profile Form
            currentEmail = emailVal;
            document.getElementById('prof_email_display').value = emailVal;
            document.getElementById('profile_email_hidden').value = emailVal;

            // Update Badge to Verified
            document.getElementById('email_badge_container').innerHTML = `
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                    <i class="bi bi-patch-check-fill"></i> Verified
                </span>
                <button type="button" class="btn btn-sm btn-outline-accent py-0.5 px-2 fw-bold text-uppercase ms-1" onclick="openChangeEmailModal()" style="font-size:0.68rem;">
                    <i class="bi bi-pencil-square me-1"></i> Change
                </button>
            `;

            // Close Modal
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('profileEmailVerifyModal'));
            if (modalInstance) modalInstance.hide();

            // Display Toast Alert
            const alertBox = document.getElementById('contact_update_alert');
            const alertMsg = document.getElementById('contact_update_alert_msg');
            alertMsg.innerText = `Your email address has been updated to ${emailVal} and verified successfully.`;
            alertBox.classList.remove('d-none');
        } else {
            errEl.innerText = data.message || 'Incorrect verification code. Please check and try again.';
            errEl.classList.remove('d-none');
        }
    } catch (e) {
        confirmBtn.disabled = false;
        confirmBtn.innerText = 'VERIFY & UPDATE EMAIL';
        errEl.innerText = 'Verification failed. Please check your network connection.';
        errEl.classList.remove('d-none');
    }
}

// --- 3. MODAL PHONE SMS OTP CONTROLLERS ---
function clearModalPhoneOtp() {
    document.querySelectorAll('.modal-phone-otp-box').forEach(el => el.value = '');
}

function getModalPhoneOtp() {
    let code = '';
    document.querySelectorAll('.modal-phone-otp-box').forEach(el => code += el.value.trim());
    return code;
}

function handleModalPhoneOtpInput(input) {
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value.length === 1) {
        const nextIndex = parseInt(input.dataset.index) + 1;
        const next = document.querySelector(`.modal-phone-otp-box[data-index="${nextIndex}"]`);
        if (next) next.focus();
    }
}

function handleModalPhoneOtpKeydown(input, event) {
    if (event.key === 'Backspace' && input.value === '') {
        const prevIndex = parseInt(input.dataset.index) - 1;
        const prev = document.querySelector(`.modal-phone-otp-box[data-index="${prevIndex}"]`);
        if (prev) {
            prev.focus();
            prev.value = '';
        }
    }
}

async function sendModalPhoneOtp() {
    const rawVal = document.getElementById('modal_phone_input').value.trim();
    const btn = document.getElementById('modal_phone_send_btn');
    const errEl = document.getElementById('modal_phone_error');

    errEl.classList.add('d-none');
    if (!rawVal || rawVal.length !== 9) {
        errEl.innerText = 'Please enter exactly 9 digits after 09 (e.g. 171234567).';
        errEl.classList.remove('d-none');
        return;
    }

    const fullPhone = '09' + rawVal;
    btn.disabled = true;
    btn.innerText = 'SENDING...';

    try {
        const res = await fetch("{{ route('verification.send-sms-otp') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ phone: fullPhone, channel: 'sms' })
        });

        const data = await res.json();
        if (!res.ok) {
            errEl.innerText = data.message || 'Failed to dispatch SMS code. Please try again.';
            errEl.classList.remove('d-none');
            btn.disabled = false;
            btn.innerText = 'SEND SMS';
            return;
        }

        let remaining = 30;
        btn.innerText = `${remaining}S`;
        clearInterval(phoneCooldownTimer);
        phoneCooldownTimer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(phoneCooldownTimer);
                btn.disabled = false;
                btn.innerText = 'SEND SMS';
            } else {
                btn.innerText = `${remaining}S`;
            }
        }, 1000);

        const firstOtp = document.querySelector('.modal-phone-otp-box[data-index="0"]');
        if (firstOtp) firstOtp.focus();
    } catch (e) {
        btn.disabled = false;
        btn.innerText = 'SEND SMS';
        errEl.innerText = 'Network error occurred. Please try again.';
        errEl.classList.remove('d-none');
    }
}

async function submitModalPhoneOtp() {
    const rawVal = document.getElementById('modal_phone_input').value.trim();
    const fullPhone = '09' + rawVal;
    const otpCode = getModalPhoneOtp();
    const errEl = document.getElementById('modal_phone_error');
    const confirmBtn = document.getElementById('modal_phone_confirm_btn');

    errEl.classList.add('d-none');
    if (otpCode.length !== 6) {
        errEl.innerText = 'Please enter all 6 digits of the SMS code.';
        errEl.classList.remove('d-none');
        return;
    }

    confirmBtn.disabled = true;
    confirmBtn.innerText = 'VERIFYING...';

    try {
        const res = await fetch("{{ route('verification.verify-otp') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                otp: otpCode,
                phone: fullPhone,
                channel: 'phone'
            })
        });

        const data = await res.json();
        confirmBtn.disabled = false;
        confirmBtn.innerText = 'VERIFY & UPDATE PHONE';

        if (data.success) {
            currentPhone = fullPhone;
            document.getElementById('phone_display_readonly').value = rawVal;
            document.getElementById('profile_phone_hidden').value = fullPhone;

            document.getElementById('phone_badge_container').innerHTML = `
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:0.65rem;">
                    <i class="bi bi-patch-check-fill"></i> Verified
                </span>
                <button type="button" class="btn btn-sm btn-outline-accent py-0.5 px-2 fw-bold text-uppercase ms-1" onclick="openChangePhoneModal()" style="font-size:0.68rem;">
                    <i class="bi bi-pencil-square me-1"></i> Change
                </button>
            `;

            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('profilePhoneVerifyModal'));
            if (modalInstance) modalInstance.hide();

            const alertBox = document.getElementById('contact_update_alert');
            const alertMsg = document.getElementById('contact_update_alert_msg');
            alertMsg.innerText = `Your mobile phone has been updated to ${fullPhone} and verified successfully.`;
            alertBox.classList.remove('d-none');
        } else {
            errEl.innerText = data.message || 'Incorrect verification code. Please try again.';
            errEl.classList.remove('d-none');
        }
    } catch (e) {
        confirmBtn.disabled = false;
        confirmBtn.innerText = 'VERIFY & UPDATE PHONE';
        errEl.innerText = 'Verification failed. Please check your network connection.';
        errEl.classList.remove('d-none');
    }
}

// --- 4. FORM VALIDATION & SUBMISSION (PERSONAL DETAILS & ADDRESS ONLY) ---
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

function validateProfileForm(event) {
    if (isProfileFormConfirmed) {
        compileProfileAddress();
        return true;
    }

    const form = event.target || document.getElementById('profileUpdateForm');
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

    if (errorsCount > 0) {
        event.preventDefault();
        event.stopPropagation();
        return false;
    }

    // Direct Confirmation Modal
    event.preventDefault();
    event.stopPropagation();
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmUpdateProfileModal'));
    confirmModal.show();
    return false;
}

function proceedWithProfileUpdate() {
    isProfileFormConfirmed = true;
    const confirmModalEl = document.getElementById('confirmUpdateProfileModal');
    const modalInstance = bootstrap.Modal.getInstance(confirmModalEl);
    if (modalInstance) {
        modalInstance.hide();
    }
    compileProfileAddress();
    document.getElementById('profileUpdateForm').submit();
}

function toggleProfileMN(cb) {
    const input = document.getElementById('profile_middle_name');
    if (!input) return;
    if (cb.checked) {
        input.value = 'N/A';
        input.readOnly = true;
        input.classList.add('opacity-50');
    } else {
        input.value = '';
        input.readOnly = false;
        input.classList.remove('opacity-50');
        input.focus();
    }
}

function resetProfileForm(event) {
    setTimeout(async () => {
        const mnCheck = document.getElementById('profile_no_mn');
        const input = document.getElementById('profile_middle_name');
        if (mnCheck && input) {
            input.readOnly = mnCheck.checked;
            input.classList.toggle('opacity-50', mnCheck.checked);
        }

        isProfileFormConfirmed = false;
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

// --- 5. PSGC ADDRESS SELECTION API ---
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
});
</script>
@endpush