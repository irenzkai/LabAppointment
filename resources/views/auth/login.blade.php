@extends('layouts.app')

@section('title', 'Login')

@php
    $backendLockoutSeconds = 0;
    if ($errors->has('email')) {
        $emailErr = $errors->first('email');
        if (preg_match('/(\d+)\s+seconds?/', $emailErr, $matches)) {
            $backendLockoutSeconds = (int) $matches[1];
        }
    }
@endphp

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">

                {{-- LEFT PANEL: SECURED CHANNEL (Always Dark for consistent brand presence) --}}
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 580px;">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>

                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-5 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Welcome.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Sign in using your verified email address or verified mobile phone number to manage diagnostic bookings, monitor test statuses, and access your encrypted clinical results.</p>
                    </div>

                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Protected Channel
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: LOGIN CREDENTIALS FORM --}}
                <div class="col-lg-7 d-flex flex-column justify-content-center p-4 p-md-5 bg-card text-start">
                    <div class="w-100 py-3" style="max-width: 440px; margin: 0 auto;">

                        {{-- Icon & Heading --}}
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 p-3 text-accent" style="background-color: rgba(25, 211, 140, 0.08); width: 64px; height: 64px;">
                                <i class="bi bi-person-check-fill fs-2"></i>
                            </div>
                            <h2 class="text-main fw-800 mb-1 uppercase tracking-tighter" style="font-size: 1.85rem;">Account Login</h2>
                            <p class="text-muted small mb-0">Enter your verified login credentials to unlock your clinical portal.</p>
                        </div>

                        {{-- Session Status Notice --}}
                        @if (session('status'))
                            <div class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm" style="background-color: rgba(25, 211, 140, 0.05); border-left: 4px solid #19D38C !important; border-radius: 8px;">
                                <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                                <div class="small text-main" style="line-height: 1.4;">{{ session('status') }}</div>
                            </div>
                        @endif

                        {{-- Anti-Brute Force Lockout Alert Banner --}}
                        <div id="lockout-warning" class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm d-none" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid #dc3545 !important; border-radius: 8px;">
                            <i class="bi bi-shield-fill-exclamation text-danger me-3 fs-4"></i>
                            <div>
                                <strong class="uppercase text-danger d-block mb-0.5" style="font-size: 0.75rem;">Security Rate-Limit Active</strong>
                                <span class="small text-main" id="lockout-message">Too many failed login attempts detected. Please wait before retrying.</span>
                            </div>
                        </div>

                        {{-- Server Validation Errors (Non-throttle or Initial Notices) --}}
                        @if ($errors->any() && $backendLockoutSeconds === 0)
                            <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                                <div>
                                    <div class="fw-800 uppercase fs-x-small text-danger">Sign-In Failed</div>
                                    <div class="small text-main">{{ $errors->first() }}</div>
                                </div>
                            </div>
                        @endif

                        {{-- Form: Sign In --}}
                        <form method="POST" action="{{ route('login') }}" id="loginForm" onsubmit="return handleLoginSubmit(event)" novalidate>
                            @csrf

                            {{-- Credential Input: Email or Mobile Phone --}}
                            <div class="mb-3">
                                <label for="email" class="small text-secondary fw-bold mb-1 uppercase d-block text-start">
                                    Email Address or Mobile Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text border-secondary bg-secondary bg-opacity-10 text-secondary" id="login-icon-wrapper">
                                        <i class="bi bi-person-fill" id="credential-icon"></i>
                                    </span>
                                    <input id="email" 
                                           type="text" 
                                           name="email" 
                                           value="{{ old('email') }}" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           placeholder="name@domain.com or 09XXXXXXXXX" 
                                           required 
                                           autofocus 
                                           autocomplete="username"
                                           oninput="detectCredentialType(this.value)">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted" style="font-size: 0.68rem;">Verified Email or 11-Digit Mobile (09XXXXXXXXX)</small>
                                    <span id="detected-type-badge" class="badge px-1.5 py-0.5 d-none" style="font-size: 0.62rem;"></span>
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block fw-bold small mt-1" id="server_email_err">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password Input --}}
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="small text-secondary fw-bold mb-0 uppercase">Password</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="small text-accent text-decoration-none fw-bold" style="font-size: 0.75rem;">
                                            Forgot Password?
                                        </a>
                                    @endif
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text border-secondary bg-secondary bg-opacity-10 text-secondary">
                                        <i class="bi bi-key-fill"></i>
                                    </span>
                                    <input id="password" 
                                           type="password" 
                                           name="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           placeholder="Enter account password" 
                                           required 
                                           autocomplete="current-password">
                                    <button class="btn btn-outline-secondary border-secondary" type="button" onclick="togglePasswordVisibility('password', this)" title="Show/Hide Password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block fw-bold small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Remember Device Checkbox --}}
                            <div class="mb-4 form-check text-start">
                                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                <label for="remember_me" class="form-check-label small text-secondary fw-bold">
                                    Remember this device
                                </label>
                            </div>

                            {{-- Submit Button --}}
                            <button id="login-submit-btn" type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm uppercase">
                                <i class="bi bi-box-arrow-in-right me-1"></i> SIGN IN
                            </button>

                            {{-- Register New Account Link --}}
                            @if (Route::has('register'))
                                <hr class="border-secondary border-opacity-25 my-4">
                                <div class="text-center">
                                    <span class="small text-muted">Don't have an account yet?</span>
                                    <a href="{{ route('register') }}" class="small text-accent fw-bold text-decoration-none ms-1">
                                        Register a New Account
                                    </a>
                                </div>
                            @endif
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
    const LOCKOUT_KEY = 'medscreen_login_lockout_expiry';
    const serverLockoutSeconds = {{ $backendLockoutSeconds }};
    let isSubmitting = false;

    // 1. Initialize Server Lockout on Page Load
    if (serverLockoutSeconds > 0) {
        localStorage.setItem(LOCKOUT_KEY, (Date.now() + (serverLockoutSeconds * 1000)).toString());
    }

    // 2. Anti-Brute Force Lockout & Throttler Engine
    function updateLockoutUI() {
        const btn = document.getElementById('login-submit-btn');
        const emailInput = document.getElementById('email');
        const passInput = document.getElementById('password');
        const warningBox = document.getElementById('lockout-warning');
        const messageEl = document.getElementById('lockout-message');
        if (!btn) return;

        const now = Date.now();
        const lockoutExpiry = localStorage.getItem(LOCKOUT_KEY);

        if (lockoutExpiry && now < parseInt(lockoutExpiry)) {
            const remaining = Math.ceil((parseInt(lockoutExpiry) - now) / 1000);
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            const timeString = m > 0 ? `${m}m ${s}s` : `${s}s`;

            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = `<i class="bi bi-clock-history me-1.5"></i>LOCKED OUT (${timeString})`;

            if (emailInput) emailInput.disabled = true;
            if (passInput) passInput.disabled = true;

            if (warningBox && messageEl) {
                warningBox.classList.remove('d-none');
                messageEl.innerText = `Too many failed login attempts detected. To protect your account from brute-force attacks, sign-in has been temporarily paused. Please retry in ${timeString}.`;
            }
            return;
        }

        // Lockout expired: clean up storage and re-enable inputs
        if (lockoutExpiry && now >= parseInt(lockoutExpiry)) {
            localStorage.removeItem(LOCKOUT_KEY);
        }

        if (!isSubmitting) {
            btn.disabled = false;
            btn.style.pointerEvents = '';
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i>SIGN IN';
            if (emailInput) emailInput.disabled = false;
            if (passInput) passInput.disabled = false;
            if (warningBox) warningBox.classList.add('d-none');
        }
    }

    // 3. Form Submission Pacing & Double-Submit Protection
    function handleLoginSubmit(event) {
        const now = Date.now();
        const lockoutExpiry = localStorage.getItem(LOCKOUT_KEY);
        if (lockoutExpiry && now < parseInt(lockoutExpiry)) {
            event.preventDefault();
            return false;
        }

        if (isSubmitting) {
            event.preventDefault();
            return false;
        }

        isSubmitting = true;
        const btn = document.getElementById('login-submit-btn');
        if (btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('disabled', 'opacity-75');
            btn.innerHTML = 'AUTHENTICATING... <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>';
        }
        return true;
    }

    // 4. Dynamic Credential Detection (Email vs Philippine Mobile)
    function detectCredentialType(val) {
        const icon = document.getElementById('credential-icon');
        const badge = document.getElementById('detected-type-badge');
        if (!icon) return;

        const clean = val.trim();
        const digits = clean.replace(/[\s\-\(\)]/g, '');

        if (!clean) {
            icon.className = 'bi bi-person-fill';
            if (badge) badge.classList.add('d-none');
        } else if (clean.includes('@')) {
            icon.className = 'bi bi-envelope-fill text-accent';
            if (badge) {
                badge.classList.remove('d-none');
                badge.innerText = 'EMAIL';
                badge.className = 'badge bg-success bg-opacity-10 text-accent border border-accent border-opacity-25 px-1.5 py-0.5';
            }
        } else if (/^(\+?63|0)?9\d*/.test(digits)) {
            icon.className = 'bi bi-phone-fill text-accent';
            if (badge) {
                badge.classList.remove('d-none');
                badge.innerText = 'MOBILE';
                badge.className = 'badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-1.5 py-0.5';
            }
        } else {
            icon.className = 'bi bi-person-fill';
            if (badge) badge.classList.add('d-none');
        }
    }

    // 5. Password Visibility Toggle
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (!input || !icon) return;
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const emailInput = document.getElementById('email');
        if (emailInput && emailInput.value) {
            detectCredentialType(emailInput.value);
        }
        updateLockoutUI();
        setInterval(updateLockoutUI, 1000);
    });
</script>
@endpush

@push('styles')
<style>
    .cursor-not-allowed {
        cursor: not-allowed !important;
    }
</style>
@endpush