@extends('layouts.app')

@section('title', 'Verify Account')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">

                {{-- LEFT PANEL: SECURED CHANNEL --}}
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 550px;">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>

                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-5 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Finalize your registration.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Please activate your secure account to book diagnostic examinations, access schedules, and view clinical history logs.</p>
                    </div>

                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Protected Channel
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: SELECTION & ACTION FORM --}}
                <div class="col-lg-7 d-flex flex-column justify-content-center p-4 p-md-5 bg-card text-start">
                    <div class="w-100 py-3" style="max-width: 440px; margin: 0 auto;">

                        {{-- Icon & Heading --}}
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 p-3 text-accent" style="background-color: rgba(25, 211, 140, 0.08); width: 64px; height: 64px;">
                                <i class="bi bi-envelope-open-fill fs-2"></i>
                            </div>
                            <h2 class="text-main fw-800 mb-2 uppercase tracking-tighter" style="font-size: 1.85rem;">Account Activation</h2>
                            <p class="text-muted small mb-0">Choose how you would like to securely verify and unlock your clinical profile.</p>
                        </div>

                        {{-- Success Notification with Dynamic Status Labels --}}
                        @if (session('status'))
                            <div class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm" style="background-color: rgba(25, 211, 140, 0.05); border-left: 4px solid #19D38C !important; border-radius: 8px;">
                                <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                                <div class="text-start">
                                    <div class="fw-800 uppercase fs-x-small text-success" style="font-size: 0.75rem; letter-spacing: 0.5px;">Notification Dispatched</div>
                                    <div class="small text-main" style="color: var(--text-main) !important; font-size: 0.85rem; line-height: 1.4;">
                                        @if(session('status') === 'verification-link-sent')
                                            A fresh verification link has been successfully dispatched to your email address.
                                        @elseif(session('status') === 'verification-code-sent')
                                            A secure 6-digit One-Time Password has been dispatched to your email address.
                                        @else
                                            {{ session('status') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Dynamic Lockout Warning Box --}}
                        <div id="lockout-warning" class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm d-none" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid #dc3545 !important; border-radius: 8px;">
                            <!-- Populated dynamically via JS -->
                        </div>

                        {{-- Error Notification --}}
                        @if($errors->any())
                            <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                                <div>
                                    <div class="fw-800 uppercase fs-x-small text-danger">Verification Failed</div>
                                    <div class="small text-main">{{ $errors->first() }}</div>
                                </div>
                            </div>
                        @endif

                        <!-- PATHWAY SELECTOR CARDS -->
                        <div class="row g-2.5 mb-4">
                            <!-- Pathway 1: Secure Link -->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="verify_pathway" id="path_link" checked onchange="toggleVerifyForm('link')">
                                <label class="btn verify-card w-100 p-3 text-start hover-bg" for="path_link">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-6 uppercase">1. Secure Email Link</div>
                                            <div class="x-small text-muted">Receive a cryptographic, one-click sign-off URL.</div>
                                        </div>
                                        <i class="bi bi-link-45deg fs-3"></i>
                                    </div>
                                </label>
                            </div>

                            <!-- Pathway 2: Email OTP -->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="verify_pathway" id="path_email_otp" onchange="toggleVerifyForm('email_otp')">
                                <label class="btn verify-card w-100 p-3 text-start hover-bg" for="path_email_otp">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-6 uppercase">2. Secure Email OTP</div>
                                            <div class="x-small text-muted">Submit a dynamic, 6-digit One-Time Password.</div>
                                        </div>
                                        <i class="bi bi-shield-check fs-3"></i>
                                    </div>
                                </label>
                            </div>

                            <!-- Pathway 3: Phone OTP (COMING SOON / DISABLED CARD) -->
                            <div class="col-12 opacity-50 cursor-not-allowed">
                                <input type="radio" class="btn-check" name="verify_pathway" id="path_phone_otp" disabled>
                                <label class="btn verify-card w-100 p-3 text-start" for="path_phone_otp" style="pointer-events: none;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-6 uppercase text-muted">3. Mobile Phone OTP</div>
                                            <div class="x-small text-muted">Verify via text message credentials.</div>
                                        </div>
                                        <span class="badge bg-secondary text-white uppercase px-2 py-1 smaller">Coming Soon</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- DYNAMIC PATHWAY FORMS -->
                        <!-- FORM A: LINK DISPATCH -->
                        <div id="form_link_container">
                            <div class="d-grid gap-3">
                                <form id="resend-form" method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <button id="resend-btn" type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm">
                                        SEND ACTIVATION EMAIL LINK
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- FORM B: EMAIL OTP SUBMISSION -->
                        <div id="form_otp_container" class="d-none">
                            <form method="POST" action="{{ route('verification.verify-otp') }}">
                                @csrf
                                <div class="mb-3 text-center">
                                    <label class="small text-muted fw-bold mb-1 uppercase d-block text-start">Enter 6-Digit Verification Code</label>
                                    
                                    <!-- 6 Separated OTP Input Nodes -->
                                    <div class="d-flex justify-content-between gap-2 my-3 mx-auto" style="max-width: 320px;">
                                        <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="0" oninput="handleOtpInput(this, event)" onkeydown="handleOtpKeydown(this, event)">
                                        <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="1" oninput="handleOtpInput(this, event)" onkeydown="handleOtpKeydown(this, event)">
                                        <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="2" oninput="handleOtpInput(this, event)" onkeydown="handleOtpKeydown(this, event)">
                                        <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="3" oninput="handleOtpInput(this, event)" onkeydown="handleOtpKeydown(this, event)">
                                        <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="4" oninput="handleOtpInput(this, event)" onkeydown="handleOtpKeydown(this, event)">
                                        <input type="text" class="form-control otp-box text-center fw-bold fs-3" maxlength="1" data-index="5" oninput="handleOtpInput(this, event)" onkeydown="handleOtpKeydown(this, event)">
                                    </div>
                                    
                                    <!-- Hidden target input compiling code values for submit -->
                                    <input type="hidden" name="otp" id="otp_hidden">
                                </div>
                                <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm">
                                    SUBMIT VERIFICATION CODE
                                </button>
                            </form>

                            <div class="mt-3 text-center">
                                <!-- FIXED: Point resend form to a dedicated email OTP route -->
                                <form id="otp-resend-form" method="POST" action="{{ route('verification.send-otp') }}">
                                    @csrf
                                    <span class="small text-muted">Didn't receive the code?</span>
                                    <button id="otp-resend-btn" type="submit" class="btn btn-link text-accent fw-bold text-decoration-none p-0 small ms-1 align-baseline" style="font-size: 0.85rem;">
                                        SEND CODE TO EMAIL
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- FORM C: DYNAMIC EMAIL CORRECTIONS PANEL (Hidden initially) -->
                        <div id="form_change_email_container" class="d-none mt-4 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.02);">
                            <form method="POST" action="{{ route('verification.change-email') }}" novalidate id="changeEmailForm">
                                @csrf
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold mb-1 uppercase">Enter Correct Email Address</label>
                                    <input type="email" name="email" id="new_email_input" class="form-control py-2" placeholder="correct-email@example.com" value="{{ old('email', Auth::user()->email) }}" required>
                                    <small class="text-muted mt-1 d-block" style="font-size:0.65rem;">Correcting your email updates your clinical profile, resets resend thresholds, and dispatches a new code.</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn-custom btn-outline-secondary w-50 py-2.5 small" onclick="toggleChangeEmailForm(false)">CANCEL</button>
                                    <button type="submit" class="btn-custom btn-accent w-50 py-2.5 small">UPDATE EMAIL</button>
                                </div>
                            </form>
                        </div>

                        <hr class="border-secondary border-opacity-25 my-4">

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            {{-- Logout Option --}}
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-link text-secondary text-decoration-none small p-0">
                                    <i class="bi bi-box-arrow-left me-1"></i>Logout
                                </button>
                            </form>

                            {{-- Change Email Trigger Link --}}
                            <button type="button" class="btn btn-link text-accent text-decoration-none small p-0" onclick="toggleChangeEmailForm(true)">
                                <i class="bi bi-envelope-plus me-1"></i>Change Email
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- OMISSION VALIDATOR MODAL -->
<div class="modal fade" id="regValidationErrorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1060;">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-danger bg-card text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid #dc3545; color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-exclamation-triangle-fill text-danger display-4 d-block animate-pulse"></i>
            </div>
            <h5 class="text-danger fw-bold mb-2 uppercase tracking-tighter">Omissions Found</h5>
            <div id="reg_validation_error_msg" class="text-secondary small mb-4 text-start">
                Please complete all required fields before proceeding.
            </div>
            <button type="button" class="btn btn-danger w-100 py-2.5 uppercase fw-bold" onclick="bootstrap.Modal.getInstance(document.getElementById('regValidationErrorModal')).hide()">Understood</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 1. FORM PANELS TOGGLER
    function toggleVerifyForm(type) {
        const linkForm = document.getElementById('form_link_container');
        const otpForm = document.getElementById('form_otp_container');
        const changeEmailContainer = document.getElementById('form_change_email_container');

        changeEmailContainer.classList.add('d-none');

        if (type === 'link') {
            linkForm.classList.remove('d-none');
            otpForm.classList.add('d-none');
        } else {
            linkForm.classList.add('d-none');
            otpForm.classList.remove('d-none');
            // Auto-focus first OTP block when switching view
            const firstBox = document.querySelector('.otp-box[data-index="0"]');
            if (firstBox) setTimeout(() => firstBox.focus(), 150);
        }
    }

    // 2. TOGGLE CHANGE EMAIL ACCORDION Form
    function toggleChangeEmailForm(show = true) {
        const changeEmailContainer = document.getElementById('form_change_email_container');
        const linkForm = document.getElementById('form_link_container');
        const otpForm = document.getElementById('form_otp_container');

        if (show) {
            changeEmailContainer.classList.remove('d-none');
            linkForm.classList.add('d-none');
            otpForm.classList.add('d-none');
            document.querySelectorAll('input[name="verify_pathway"]').forEach(radio => radio.checked = false);
            const emailInput = document.getElementById('new_email_input');
            if (emailInput) setTimeout(() => emailInput.focus(), 150);
        } else {
            changeEmailContainer.classList.add('d-none');
            document.getElementById('path_link').checked = true;
            toggleVerifyForm('link');
        }
    }

    // 3. 6-DIGIT OTP FIELDS CONTROLLERS
    function handleOtpInput(input, event) {
        input.value = input.value.replace(/[^0-9]/g, '');
        
        if (input.value.length === 1) {
            const nextIndex = parseInt(input.dataset.index) + 1;
            const nextInput = document.querySelector(`.otp-box[data-index="${nextIndex}"]`);
            if (nextInput) {
                nextInput.focus();
            }
        }
        compileOtpValue();
    }

    function handleOtpKeydown(input, event) {
        if (event.key === 'Backspace') {
            if (input.value === '') {
                const prevIndex = parseInt(input.dataset.index) - 1;
                const prevInput = document.querySelector(`.otp-box[data-index="${prevIndex}"]`);
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
        document.querySelectorAll('.otp-box').forEach(box => {
            compiled += box.value;
        });
        document.getElementById('otp_hidden').value = compiled;
    }

    // 4. PERSISTENT RETRY THROTTLER (30s delay -> 3 attempts limit -> 10m lockout block)
    const cooldownTime = 30; // 30 seconds delay
    const lockoutTime = 600; // 10 minutes (600 seconds) lockout
    const maxAttempts = 3;

    const resendBtn = document.getElementById('resend-btn');
    const otpResendBtn = document.getElementById('otp-resend-btn');
    const lockoutWarning = document.getElementById('lockout-warning');

    function updateResendState() {
        const now = Date.now();
        
        // A. EVALUATE LOCKOUT LIFECYCLE
        const lockoutExpiry = localStorage.getItem('resend_lockout_expiry');
        if (lockoutExpiry && now < parseInt(lockoutExpiry)) {
            const remainingLockout = Math.ceil((parseInt(lockoutExpiry) - now) / 1000);
            const minutes = Math.floor(remainingLockout / 60);
            const seconds = remainingLockout % 60;
            
            const label = `LOCKED OUT (${minutes}m ${seconds}s)`;
            
            if (resendBtn) {
                resendBtn.disabled = true;
                resendBtn.classList.add('opacity-50', 'cursor-not-allowed');
                resendBtn.innerHTML = label;
            }
            if (otpResendBtn) {
                otpResendBtn.disabled = true;
                otpResendBtn.classList.add('opacity-50', 'cursor-not-allowed');
                otpResendBtn.innerHTML = label;
            }
            
            if (lockoutWarning) {
                lockoutWarning.classList.remove('d-none');
                lockoutWarning.innerHTML = `<i class="bi bi-shield-fill-exclamation text-danger me-2 fs-5"></i><div><strong class="uppercase text-danger d-block mb-0.5" style="font-size:0.75rem;">Action Blocked</strong><span class="small" style="font-size:0.8rem;">Too many attempts. Please try another verification method or wait ${minutes}m ${seconds}s.</span></div>`;
            }
            
            setTimeout(updateResendState, 1000);
            return;
        }

        // Auto-cleanup on lockout expiration
        if (lockoutExpiry && now >= parseInt(lockoutExpiry)) {
            localStorage.removeItem('resend_lockout_expiry');
            localStorage.setItem('resend_attempts', '0');
            if (lockoutWarning) lockoutWarning.classList.add('d-none');
        }

        // B. EVALUATE DELAY COOLDOWN LIFECYCLE
        const cooldownExpiry = localStorage.getItem('resend_cooldown_expiry');
        if (cooldownExpiry && now < parseInt(cooldownExpiry)) {
            const remainingCooldown = Math.ceil((parseInt(cooldownExpiry) - now) / 1000);
            
            if (resendBtn) {
                resendBtn.disabled = true;
                resendBtn.classList.add('opacity-50', 'cursor-not-allowed');
                resendBtn.innerHTML = `RESEND LINK IN ${remainingCooldown}S`;
            }
            if (otpResendBtn) {
                otpResendBtn.disabled = true;
                otpResendBtn.classList.add('opacity-50', 'cursor-not-allowed');
                otpResendBtn.innerHTML = `RESEND CODE IN ${remainingCooldown}S`;
            }
            
            setTimeout(updateResendState, 1000);
            return;
        }

        // C. READY STATE: RE-ENABLE TRIGGERS
        if (resendBtn) {
            resendBtn.disabled = false;
            resendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            resendBtn.innerHTML = 'SEND ACTIVATION EMAIL LINK';
        }
        if (otpResendBtn) {
            otpResendBtn.disabled = false;
            otpResendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            otpResendBtn.innerHTML = 'SEND CODE TO EMAIL';
        }
        if (lockoutWarning && !localStorage.getItem('resend_lockout_expiry')) {
            lockoutWarning.classList.add('d-none');
        }
    }

    // Submit listener logging timestamps to local storage
    const resendForm = document.getElementById('resend-form');
    const otpResendForm = document.getElementById('otp-resend-form');

    function handleResendSubmit() {
        const now = Date.now();
        
        let attempts = parseInt(localStorage.getItem('resend_attempts') || '0');
        attempts++;
        localStorage.setItem('resend_attempts', attempts.toString());

        if (attempts >= maxAttempts) {
            // Set 10m lockout expiry timestamp
            const lockoutExpiryTime = now + (lockoutTime * 1000);
            localStorage.setItem('resend_lockout_expiry', lockoutExpiryTime.toString());
            localStorage.removeItem('resend_cooldown_expiry');
        } else {
            // Set 30s delay cooldown expiry timestamp
            const cooldownExpiryTime = now + (cooldownTime * 1000);
            localStorage.setItem('resend_cooldown_expiry', cooldownExpiryTime.toString());
        }
    }

    if (resendForm) {
        resendForm.addEventListener('submit', handleResendSubmit);
    }
    if (otpResendForm) {
        otpResendForm.addEventListener('submit', handleResendSubmit);
    }

    // 5. EMBEDDED DYNAMIC RE-SUBMIT FORM VALIDATOR
    const changeEmailForm = document.getElementById('changeEmailForm');
    if (changeEmailForm) {
        changeEmailForm.addEventListener('submit', function(e) {
            const emailInput = document.getElementById('new_email_input');
            let valid = true;
            let errors = [];

            if (emailInput) {
                if (!emailInput.value.trim()) {
                    valid = false; emailInput.classList.add('is-invalid');
                    errors.push("Email Address is required.");
                } else {
                    const atCount = (emailInput.value.match(/@/g) || []).length;
                    if (atCount !== 1) {
                        valid = false; emailInput.classList.add('is-invalid');
                        errors.push("The email address must contain exactly one @ symbol.");
                    }
                }
            }

            if (!valid || errors.length > 0) {
                e.preventDefault();
                e.stopPropagation();

                let errorHtml = '<div class="text-start mb-3 small text-white-50">Please correct the following omissions to proceed:</div>';
                errorHtml += '<ul class="mb-0 ps-3 text-danger d-flex flex-column gap-1.5" style="list-style-type: disc;">';
                errors.forEach(err => {
                    errorHtml += `<li class="small font-semibold">${err}</li>`;
                });
                errorHtml += '</ul>';

                document.getElementById('reg_validation_error_msg').innerHTML = errorHtml;

                const errModal = new bootstrap.Modal(document.getElementById('regValidationErrorModal'));
                errModal.show();
            }
        });
    }

    // Run evaluations on load
    updateResendState();
</script>
@endpush

<style>
    /* Selection Cards High Contrast Base Layout and Selection States */
    .verify-card {
        background-color: var(--bg-card) !important;
        border: 1.5px solid var(--border-color) !important;
        color: var(--text-main) !important;
        transition: all 0.2s ease-in-out;
        border-radius: 8px;
        cursor: pointer;
    }
    .verify-card:hover {
        border-color: var(--brand-accent) !important;
        background-color: rgba(25, 211, 140, 0.02) !important;
    }
    .btn-check:checked + label.verify-card {
        background-color: rgba(25, 211, 140, 0.08) !important;
        border-color: var(--brand-accent) !important;
        box-shadow: 0 0 10px rgba(25, 211, 140, 0.15);
    }
    .btn-check:checked + label.verify-card .fs-6 {
        color: var(--brand-accent) !important;
        font-weight: 700;
    }

    /* 6-Digit tall input box parameters */
    .otp-box {
        width: 50px;
        height: 60px;
        font-size: 1.85rem !important;
        border-radius: 8px;
        background-color: var(--bg-card) !important;
        border: 1.5px solid var(--border-color) !important;
        color: var(--text-main) !important;
        transition: all 0.2s ease-in-out;
    }
    .otp-box:focus {
        border-color: var(--brand-accent) !important;
        box-shadow: 0 0 10px rgba(25, 211, 140, 0.2) !important;
    }

    /* Frosted-glass backdrop-blur for custom modal alignment */
    #regValidationErrorModal.show {
        background-color: rgba(0, 0, 0, 0.5) !important;
        backdrop-filter: blur(2px);
    }
</style>