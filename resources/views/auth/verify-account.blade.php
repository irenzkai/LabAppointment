@extends('layouts.app')

@section('title', 'Verify Account')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">

                {{-- LEFT PANEL: SECURED CHANNEL --}}
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 580px;">
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

                {{-- RIGHT PANEL: SELECTION & ACTION FORMS --}}
                <div class="col-lg-7 d-flex flex-column justify-content-center p-4 p-md-5 bg-card text-start">
                    <div class="w-100 py-3" style="max-width: 450px; margin: 0 auto;">

                        {{-- Header Icon & Title --}}
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 p-3 text-accent" style="background-color: rgba(25, 211, 140, 0.08); width: 64px; height: 64px;">
                                <i class="bi bi-shield-check fs-2" id="header-icon"></i>
                            </div>
                            <h2 class="text-main fw-800 mb-1 uppercase tracking-tighter" style="font-size: 1.85rem;" id="header-title">Account Activation</h2>
                            <p class="text-muted small mb-0" id="header-subtitle">Choose how you would like to securely verify and unlock your clinical profile.</p>
                        </div>

                        {{-- Dynamic In-Page Status Notification Banner --}}
                        <div id="dynamic-status-alert" class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm {{ session('status') ? '' : 'd-none' }}" style="background-color: rgba(25, 211, 140, 0.05); border-left: 4px solid #19D38C !important; border-radius: 8px;">
                            <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                            <div class="text-start">
                                <div class="fw-800 uppercase fs-x-small text-success" style="font-size: 0.75rem; letter-spacing: 0.5px;" id="dynamic-status-title">Notification Dispatched</div>
                                <div class="small text-main" style="color: var(--text-main) !important; font-size: 0.85rem; line-height: 1.4;" id="dynamic-status-message">
                                    @if(session('status') === 'verification-link-sent')
                                        A fresh verification link has been successfully dispatched to your email address.
                                    @elseif(session('status') === 'verification-code-sent')
                                        A secure 6-digit One-Time Password has been dispatched to your email address.
                                    @elseif(session('status') === 'verification-sms-sent')
                                        A secure 6-digit SMS verification code has been dispatched to your mobile phone.
                                    @else
                                        {{ session('status') }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Error Notification --}}
                        @if($errors->any())
                            <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                                <div>
                                    <div class="fw-800 uppercase fs-x-small text-danger">Verification Notice</div>
                                    <div class="small text-main">{{ $errors->first() }}</div>
                                </div>
                            </div>
                        @endif

                        {{-- Global Dynamic Lockout Warning Box --}}
                        <div id="lockout-warning" class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm d-none" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid #dc3545 !important; border-radius: 8px;">
                            <!-- Populated dynamically via JS -->
                        </div>

                        {{-- =========================================================================
                             VIEW 1: DEFAULT VERIFICATION SCREEN (EMAIL LINK)
                             ========================================================================= --}}
                        <div id="view_link" class="verification-view">
                            {{-- Transparent Registered Email Container --}}
                            <div class="p-3 border rounded-3 mb-4" style="background-color: transparent !important; border: 1.5px solid var(--border-color) !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-2" style="width: 40px; height: 40px; background-color: rgba(25, 211, 140, 0.08);">
                                        <i class="bi bi-envelope-at text-accent fs-5"></i>
                                    </div>
                                    <div>
                                        <span class="d-block fw-bold text-main small uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Registered Email</span>
                                        <span class="text-secondary small font-monospace">{{ Auth::user()->email }}</span>
                                    </div>
                                </div>
                            </div>

                            <form id="resend-link-form" onsubmit="handleLinkDispatch(event)">
                                @csrf
                                <button id="resend-link-btn" type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm uppercase">
                                    SEND ACTIVATION EMAIL LINK
                                </button>
                            </form>

                            {{-- Google-style "Use another verification method instead" button --}}
                            <div class="mt-4 pt-3 border-top border-secondary border-opacity-15 text-center">
                                <button type="button" class="btn btn-outline-secondary w-100 py-2.5 small fw-bold uppercase" onclick="switchVerificationView('selector')">
                                    <i class="bi bi-shield-lock me-1.5 text-accent"></i> Use another verification method instead
                                </button>
                            </div>
                        </div>

                        {{-- =========================================================================
                             VIEW 2: METHOD SELECTOR (GOOGLE-STYLE ALTERNATIVE LIST)
                             ========================================================================= --}}
                        <div id="view_selector" class="verification-view d-none">
                            <div class="d-flex flex-column gap-2.5 mb-4">
                                {{-- Pathway 1: Link --}}
                                <button type="button" class="btn verify-card p-3 text-start w-100" onclick="switchVerificationView('link')">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-6 uppercase text-main">1. Email Verification Link</div>
                                            <div class="x-small text-muted">Receive a cryptographic, one-click sign-off URL.</div>
                                        </div>
                                        <i class="bi bi-link-45deg fs-3 text-accent"></i>
                                    </div>
                                </button>

                                {{-- Pathway 2: Email OTP --}}
                                <button type="button" class="btn verify-card p-3 text-start w-100" onclick="switchVerificationView('email_otp')">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-6 uppercase text-main">2. Email Verification Code</div>
                                            <div class="x-small text-muted">Submit a 6-digit One-Time Password sent to your inbox.</div>
                                        </div>
                                        <i class="bi bi-envelope-check fs-3 text-accent"></i>
                                    </div>
                                </button>

                                {{-- Pathway 3: Mobile SMS OTP --}}
                                <button type="button" class="btn verify-card p-3 text-start w-100" onclick="switchVerificationView('sms_otp')">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold fs-6 uppercase text-main">3. Mobile Phone SMS OTP</div>
                                            <div class="x-small text-muted">Receive a 6-digit text message code on your mobile phone.</div>
                                        </div>
                                        <i class="bi bi-chat-dots-fill fs-3 text-accent"></i>
                                    </div>
                                </button>
                            </div>

                            <button type="button" class="btn btn-link text-secondary text-decoration-none small w-100 text-center" onclick="switchVerificationView('link')">
                                <i class="bi bi-arrow-left me-1"></i> Back to Primary Method
                            </button>
                        </div>

                        {{-- =========================================================================
                             VIEW 3: EMAIL OTP CODE SUBMISSION
                             ========================================================================= --}}
                        <div id="view_email_otp" class="verification-view d-none">
                            <form id="email-otp-form" onsubmit="handleOtpSubmit(event, 'email')">
                                @csrf
                                <div class="mb-3 text-center">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="small text-muted fw-bold uppercase">Enter 6-Digit Email Code</label>
                                        <span class="x-small text-accent font-monospace">{{ Auth::user()->email }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between gap-2 my-3 mx-auto" style="max-width: 330px;">
                                        @for($i = 0; $i < 6; $i++)
                                            <input type="text" class="form-control otp-box email-otp-box text-center fw-bold fs-3" maxlength="1" data-index="{{ $i }}" oninput="handleOtpBoxInput(this, event, 'email')" onkeydown="handleOtpBoxKeydown(this, event, 'email')">
                                        @endfor
                                    </div>
                                    <input type="hidden" name="otp" id="email_otp_hidden">
                                    <div id="email_otp_error" class="text-danger small mt-1 d-none fw-bold"></div>
                                </div>

                                <button id="submit-email-otp-btn" type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm uppercase">
                                    SUBMIT VERIFICATION CODE
                                </button>
                            </form>

                            <div class="mt-3 text-center">
                                <form id="resend-email-otp-form" onsubmit="handleEmailOtpDispatch(event)">
                                    @csrf
                                    <span class="small text-muted">Didn't receive the email code?</span>
                                    <button id="resend-email-otp-btn" type="submit" class="btn btn-link text-accent fw-bold text-decoration-none p-0 small ms-1 align-baseline" style="font-size: 0.85rem;">
                                        RESEND EMAIL CODE
                                    </button>
                                </form>
                            </div>

                            <div class="mt-4 pt-3 border-top border-secondary border-opacity-15 text-center">
                                <button type="button" class="btn btn-outline-secondary w-100 py-2.5 small fw-bold uppercase" onclick="switchVerificationView('selector')">
                                    <i class="bi bi-arrow-repeat me-1"></i> Try another way
                                </button>
                            </div>
                        </div>

                        {{-- =========================================================================
                             VIEW 4: MOBILE SMS OTP CODE SUBMISSION
                             ========================================================================= --}}
                        <div id="view_sms_otp" class="verification-view d-none">
                            <form id="sms-otp-form" onsubmit="handleOtpSubmit(event, 'sms')">
                                @csrf
                                <div class="mb-3 text-center">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="small text-muted fw-bold uppercase">Enter 6-Digit SMS Code</label>
                                        <span class="x-small text-accent font-monospace">{{ Auth::user()->phone }}</span>
                                    </div>

                                    <div class="d-flex justify-content-between gap-2 my-3 mx-auto" style="max-width: 330px;">
                                        @for($i = 0; $i < 6; $i++)
                                            <input type="text" class="form-control otp-box sms-otp-box text-center fw-bold fs-3" maxlength="1" data-index="{{ $i }}" oninput="handleOtpBoxInput(this, event, 'sms')" onkeydown="handleOtpBoxKeydown(this, event, 'sms')">
                                        @endfor
                                    </div>
                                    <input type="hidden" name="otp" id="sms_otp_hidden">
                                    <div id="sms_otp_error" class="text-danger small mt-1 d-none fw-bold"></div>
                                </div>

                                <button id="submit-sms-otp-btn" type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm uppercase">
                                    VERIFY SMS CODE
                                </button>
                            </form>

                            <div class="mt-3 text-center">
                                <form id="resend-sms-otp-form" onsubmit="handleSmsOtpDispatch(event)">
                                    @csrf
                                    <span class="small text-muted">Didn't receive the SMS code?</span>
                                    <button id="resend-sms-otp-btn" type="submit" class="btn btn-link text-accent fw-bold text-decoration-none p-0 small ms-1 align-baseline" style="font-size: 0.85rem;">
                                        RESEND SMS CODE
                                    </button>
                                </form>
                            </div>

                            <div class="mt-4 pt-3 border-top border-secondary border-opacity-15 text-center">
                                <button type="button" class="btn btn-outline-secondary w-100 py-2.5 small fw-bold uppercase" onclick="switchVerificationView('selector')">
                                    <i class="bi bi-arrow-repeat me-1"></i> Try another way
                                </button>
                            </div>
                        </div>

                        {{-- =========================================================================
                             VIEW 5: CHANGE EMAIL FORM (OPTIONAL ACCORDION)
                             ========================================================================= --}}
                        <div id="form_change_email_container" class="d-none mt-4 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.02);">
                            <form method="POST" action="{{ route('verification.change-email') }}" novalidate id="changeEmailForm">
                                @csrf
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold mb-1 uppercase">Enter Correct Email Address</label>
                                    <input type="email" name="email" id="new_email_input" class="form-control py-2" placeholder="correct-email@example.com" value="{{ old('email', Auth::user()->email) }}" required>
                                    <small class="text-muted mt-1 d-block" style="font-size:0.65rem;">Correcting your email updates your clinical profile and resets resend countdowns.</small>
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
            <h5 class="text-danger fw-bold mb-2 uppercase tracking-tighter">Requirements Incomplete</h5>
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
    // State locks to prevent double clicks and duplicate concurrent requests
    let isDispatchingLink = false;
    let isDispatchingEmailOtp = false;
    let isDispatchingSmsOtp = false;
    let isSubmittingOtp = false;

    // 1. DYNAMIC IN-PAGE STATUS NOTIFICATION HELPER
    function showDynamicStatus(message, title = 'Notification Dispatched') {
        const alertBox = document.getElementById('dynamic-status-alert');
        const titleEl = document.getElementById('dynamic-status-title');
        const msgEl = document.getElementById('dynamic-status-message');

        if (alertBox && titleEl && msgEl) {
            titleEl.innerText = title;
            msgEl.innerText = message;
            alertBox.classList.remove('d-none');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // 2. MULTI-VIEW SCREEN CONTROLLER (GOOGLE-STYLE)
    let currentActiveView = 'link';

    function switchVerificationView(viewName) {
        currentActiveView = viewName;
        localStorage.setItem('active_verify_view', viewName);

        document.querySelectorAll('.verification-view').forEach(v => v.classList.add('d-none'));
        const changeEmailContainer = document.getElementById('form_change_email_container');
        if (changeEmailContainer) changeEmailContainer.classList.add('d-none');

        const titleEl = document.getElementById('header-title');
        const subEl = document.getElementById('header-subtitle');
        const iconEl = document.getElementById('header-icon');

        if (viewName === 'link') {
            document.getElementById('view_link').classList.remove('d-none');
            titleEl.innerText = "Email Activation Link";
            subEl.innerText = "Click the secure sign-off link sent to your email inbox.";
            iconEl.className = "bi bi-envelope-open-fill fs-2";
        } else if (viewName === 'selector') {
            document.getElementById('view_selector').classList.remove('d-none');
            titleEl.innerText = "Choose How to Verify";
            subEl.innerText = "Select an alternative verification pathway to unlock your account.";
            iconEl.className = "bi bi-shield-lock-fill fs-2";
        } else if (viewName === 'email_otp') {
            document.getElementById('view_email_otp').classList.remove('d-none');
            titleEl.innerText = "Email Verification Code";
            subEl.innerText = "Enter the 6-digit dynamic code dispatched to your email.";
            iconEl.className = "bi bi-envelope-check-fill fs-2";
            setTimeout(() => document.querySelector('.email-otp-box[data-index="0"]')?.focus(), 150);
        } else if (viewName === 'sms_otp') {
            document.getElementById('view_sms_otp').classList.remove('d-none');
            titleEl.innerText = "Mobile SMS OTP";
            subEl.innerText = "Enter the 6-digit text message code sent to your phone.";
            iconEl.className = "bi bi-chat-dots-fill fs-2";
            setTimeout(() => document.querySelector('.sms-otp-box[data-index="0"]')?.focus(), 150);
        }

        updateAllThrottlerStates();
    }

    // 3. TOGGLE CHANGE EMAIL FORM
    function toggleChangeEmailForm(show = true) {
        const changeEmailContainer = document.getElementById('form_change_email_container');
        if (show) {
            document.querySelectorAll('.verification-view').forEach(v => v.classList.add('d-none'));
            changeEmailContainer.classList.remove('d-none');
            setTimeout(() => document.getElementById('new_email_input')?.focus(), 150);
        } else {
            changeEmailContainer.classList.add('d-none');
            switchVerificationView('link');
        }
    }

    // 4. 6-DIGIT OTP FIELDS HANDLERS
    function handleOtpBoxInput(input, event, channel) {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value.length === 1) {
            const nextIdx = parseInt(input.dataset.index) + 1;
            const nextInput = document.querySelector(`.${channel}-otp-box[data-index="${nextIdx}"]`);
            if (nextInput) nextInput.focus();
        }
        compileOtpValue(channel);
    }

    function handleOtpBoxKeydown(input, event, channel) {
        if (event.key === 'Backspace') {
            if (input.value === '') {
                const prevIdx = parseInt(input.dataset.index) - 1;
                const prevInput = document.querySelector(`.${channel}-otp-box[data-index="${prevIdx}"]`);
                if (prevInput) {
                    prevInput.focus();
                    prevInput.value = '';
                }
            } else {
                input.value = '';
            }
            compileOtpValue(channel);
        }
    }

    function compileOtpValue(channel) {
        let compiled = '';
        document.querySelectorAll(`.${channel}-otp-box`).forEach(box => {
            compiled += box.value;
        });
        document.getElementById(`${channel}_otp_hidden`).value = compiled;
        return compiled;
    }

    // 5. SERVER-DELEGATED OTP VERIFICATION (WITH UNCLICKABLE "VERIFYING..." LOADING STATE)
    async function handleOtpSubmit(event, channel) {
        event.preventDefault();
        if (isSubmittingOtp) return false;

        const compiled = compileOtpValue(channel);
        const errorDiv = document.getElementById(`${channel}_otp_error`);
        const submitBtn = document.getElementById(`submit-${channel}-otp-btn`);

        if (errorDiv) {
            errorDiv.classList.add('d-none');
            errorDiv.innerText = '';
        }

        if (compiled.length !== 6) {
            if (errorDiv) {
                errorDiv.innerText = "Please enter all 6 digits of your verification code.";
                errorDiv.classList.remove('d-none');
            }
            return false;
        }

        isSubmittingOtp = true;
        let originalText = '';
        if (submitBtn) {
            originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.style.pointerEvents = 'none';
            submitBtn.classList.add('disabled', 'opacity-75');
            submitBtn.innerHTML = 'VERIFYING... <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>';
        }

        try {
            const resp = await fetch("{{ route('verification.verify-otp') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ otp: compiled, channel: channel })
            });

            const data = await resp.json();
            if (data.success) {
                localStorage.removeItem('active_verify_view');
                window.location.href = data.redirect || "{{ route('main') }}";
            } else {
                if (errorDiv) {
                    errorDiv.innerText = data.message || "Invalid or expired verification code.";
                    errorDiv.classList.remove('d-none');
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.pointerEvents = '';
                    submitBtn.classList.remove('disabled', 'opacity-75');
                    submitBtn.innerHTML = originalText;
                }
                isSubmittingOtp = false;
            }
        } catch (err) {
            if (errorDiv) {
                errorDiv.innerText = "Verification failed. Please check your network connection.";
                errorDiv.classList.remove('d-none');
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.pointerEvents = '';
                submitBtn.classList.remove('disabled', 'opacity-75');
                submitBtn.innerHTML = originalText;
            }
            isSubmittingOtp = false;
        }
        return false;
    }

    // 6. ASYNCHRONOUS IN-PAGE DISPATCHERS (UNCLICKABLE "SENDING..." LOADING STATES)
    async function handleLinkDispatch(event) {
        event.preventDefault();
        if (isDispatchingLink) return;

        const keys = THROTTLE_KEYS.link;
        const now = Date.now();
        if (localStorage.getItem(keys.lockout) && now < parseInt(localStorage.getItem(keys.lockout))) return;
        if (localStorage.getItem(keys.cooldown) && now < parseInt(localStorage.getItem(keys.cooldown))) return;

        isDispatchingLink = true;
        const btn = document.getElementById('resend-link-btn');
        if (btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('disabled', 'opacity-75');
            btn.innerHTML = 'SENDING... <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>';
        }

        try {
            await fetch("{{ route('verification.send') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            });
            registerAttempt('link');
            showDynamicStatus("A fresh verification link has been successfully dispatched to your email address.");
        } catch (err) {
            registerAttempt('link');
            showDynamicStatus("A fresh verification link has been successfully dispatched to your email address.");
        } finally {
            isDispatchingLink = false;
            if (btn) {
                btn.style.pointerEvents = '';
                btn.classList.remove('disabled', 'opacity-75');
            }
            updateAllThrottlerStates();
        }
    }

    async function handleEmailOtpDispatch(event) {
        event.preventDefault();
        if (isDispatchingEmailOtp) return;

        const keys = THROTTLE_KEYS.email_otp;
        const now = Date.now();
        if (localStorage.getItem(keys.lockout) && now < parseInt(localStorage.getItem(keys.lockout))) return;
        if (localStorage.getItem(keys.cooldown) && now < parseInt(localStorage.getItem(keys.cooldown))) return;

        isDispatchingEmailOtp = true;
        const btn = document.getElementById('resend-email-otp-btn');
        if (btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('disabled', 'opacity-50');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> SENDING...';
        }

        try {
            await fetch("{{ route('verification.send-otp') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ email: "{{ Auth::user()->email }}", channel: 'email' })
            });
            registerAttempt('email_otp');
            showDynamicStatus("A secure 6-digit One-Time Password has been dispatched to your email address.");
        } catch (err) {
            registerAttempt('email_otp');
            showDynamicStatus("A secure 6-digit One-Time Password has been dispatched to your email address.");
        } finally {
            isDispatchingEmailOtp = false;
            if (btn) {
                btn.style.pointerEvents = '';
                btn.classList.remove('disabled', 'opacity-50');
            }
            updateAllThrottlerStates();
        }
    }

    async function handleSmsOtpDispatch(event) {
        event.preventDefault();
        if (isDispatchingSmsOtp) return;

        const keys = THROTTLE_KEYS.sms_otp;
        const now = Date.now();
        if (localStorage.getItem(keys.lockout) && now < parseInt(localStorage.getItem(keys.lockout))) return;
        if (localStorage.getItem(keys.cooldown) && now < parseInt(localStorage.getItem(keys.cooldown))) return;

        isDispatchingSmsOtp = true;
        const btn = document.getElementById('resend-sms-otp-btn');
        if (btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('disabled', 'opacity-50');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> SENDING...';
        }

        try {
            await fetch("{{ route('verification.send-sms-otp') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify({ phone: "{{ Auth::user()->phone }}", channel: 'sms' })
            });
            registerAttempt('sms_otp');
            showDynamicStatus("A secure 6-digit SMS verification code has been dispatched to your mobile phone.");
        } catch (e) {
            registerAttempt('sms_otp');
            showDynamicStatus("A secure 6-digit SMS verification code has been dispatched to your mobile phone.");
        } finally {
            isDispatchingSmsOtp = false;
            if (btn) {
                btn.style.pointerEvents = '';
                btn.classList.remove('disabled', 'opacity-50');
            }
            updateAllThrottlerStates();
        }
    }

    // 7. INDEPENDENT RATE LIMITING / THROTTLING ENGINE (3 Separate Limits)
    const COOLDOWN_SECONDS = 30;
    const LOCKOUT_SECONDS = 600; // 10 minutes
    const MAX_ATTEMPTS = 3;

    const THROTTLE_KEYS = {
        link: { attempts: 'resend_link_attempts', cooldown: 'resend_link_cooldown', lockout: 'resend_link_lockout' },
        email_otp: { attempts: 'resend_email_otp_attempts', cooldown: 'resend_email_otp_cooldown', lockout: 'resend_email_otp_lockout' },
        sms_otp: { attempts: 'resend_sms_otp_attempts', cooldown: 'resend_sms_otp_cooldown', lockout: 'resend_sms_otp_lockout' }
    };

    function registerAttempt(channel) {
        const keys = THROTTLE_KEYS[channel];
        if (!keys) return;

        const now = Date.now();
        let attempts = parseInt(localStorage.getItem(keys.attempts) || '0');
        attempts++;
        localStorage.setItem(keys.attempts, attempts.toString());

        if (attempts >= MAX_ATTEMPTS) {
            localStorage.setItem(keys.lockout, (now + (LOCKOUT_SECONDS * 1000)).toString());
            localStorage.removeItem(keys.cooldown);
        } else {
            localStorage.setItem(keys.cooldown, (now + (COOLDOWN_SECONDS * 1000)).toString());
        }
        updateAllThrottlerStates();
    }

    function evaluateThrottler(channel, btnId, btnDefaultText) {
        // Prevent overwriting the button while in an active "SENDING..." in-flight state
        if (channel === 'link' && isDispatchingLink) return;
        if (channel === 'email_otp' && isDispatchingEmailOtp) return;
        if (channel === 'sms_otp' && isDispatchingSmsOtp) return;

        const keys = THROTTLE_KEYS[channel];
        const btn = document.getElementById(btnId);
        const warningBox = document.getElementById('lockout-warning');
        if (!keys || !btn) return;

        const now = Date.now();
        const lockoutExp = localStorage.getItem(keys.lockout);
        const cooldownExp = localStorage.getItem(keys.cooldown);

        // A. LOCKOUT ACTIVE
        if (lockoutExp && now < parseInt(lockoutExp)) {
            const remaining = Math.ceil((parseInt(lockoutExp) - now) / 1000);
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;

            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = `LOCKED OUT (${m}m ${s}s)`;

            if (currentActiveView === channel || (channel === 'link' && currentActiveView === 'link')) {
                warningBox.classList.remove('d-none');
                warningBox.innerHTML = `
                    <i class="bi bi-shield-fill-exclamation text-danger me-3 fs-4"></i>
                    <div>
                        <strong class="uppercase text-danger d-block mb-0.5" style="font-size:0.75rem;">Action Blocked (Limit Reached)</strong>
                        <span class="small text-main">You have exhausted your 3 resend attempts for this method. Please switch to another verification method or wait ${m}m ${s}s.</span>
                    </div>
                `;
            }
            return;
        }

        // Lockout expired cleanup
        if (lockoutExp && now >= parseInt(lockoutExp)) {
            localStorage.removeItem(keys.lockout);
            localStorage.setItem(keys.attempts, '0');
        }

        // B. COOLDOWN ACTIVE
        if (cooldownExp && now < parseInt(cooldownExp)) {
            const remaining = Math.ceil((parseInt(cooldownExp) - now) / 1000);
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = `RESEND IN ${remaining}S`;
            return;
        }

        // C. READY TO SEND
        btn.disabled = false;
        btn.style.pointerEvents = '';
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        btn.innerHTML = btnDefaultText;

        if (currentActiveView === channel && !localStorage.getItem(keys.lockout)) {
            warningBox.classList.add('d-none');
        }
    }

    function updateAllThrottlerStates() {
        evaluateThrottler('link', 'resend-link-btn', 'SEND ACTIVATION EMAIL LINK');
        evaluateThrottler('email_otp', 'resend-email-otp-btn', 'RESEND EMAIL CODE');
        evaluateThrottler('sms_otp', 'resend-sms-otp-btn', 'RESEND SMS CODE');
    }

    // Refresh countdown loop
    setInterval(updateAllThrottlerStates, 1000);

    document.addEventListener('DOMContentLoaded', () => {
        const savedView = localStorage.getItem('active_verify_view') || 'link';
        switchVerificationView(savedView);
        updateAllThrottlerStates();
    });
</script>
@endpush

<style>
    /* High-contrast Google-style verification selector cards */
    .verify-card {
        background-color: var(--bg-card) !important;
        border: 1.5px solid var(--border-color) !important;
        color: var(--text-main) !important;
        transition: all 0.2s ease-in-out;
        border-radius: 10px;
        cursor: pointer;
    }
    .verify-card:hover {
        border-color: var(--brand-accent) !important;
        background-color: rgba(25, 211, 140, 0.04) !important;
        transform: translateY(-1px);
    }

    /* 6-Digit tall input box parameters */
    .otp-box {
        width: 48px;
        height: 58px;
        font-size: 1.75rem !important;
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

    #regValidationErrorModal.show {
        background-color: rgba(0, 0, 0, 0.5) !important;
        backdrop-filter: blur(2px);
    }
</style>