@extends('layouts.app')

@section('title', 'Forgot Password')

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
                
                {{-- LEFT PANEL: SECURED RECOVERY (Always Dark for high-contrast presentation) --}}
                <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 550px;">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>
                    
                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-4 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Secure password recovery.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Retrieve access to your personal portal safely. Password reset links are transmitted exclusively to verified email addresses.</p>
                    </div>
                    
                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Protected Recovery Channel
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: EMAIL RESET LINK FORM --}}
                <div class="col-lg-6 d-flex flex-column justify-content-center p-5 bg-card">
                    <div class="w-100 py-3" style="max-width: 400px; margin: 0 auto;">
                        
                        {{-- Header --}}
                        <div class="mb-4 text-start">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 p-3 text-accent" style="background-color: rgba(25, 211, 140, 0.08); width: 64px; height: 64px;">
                                <i class="bi bi-key-fill fs-2"></i>
                            </div>
                            <h2 class="text-main fw-800 mb-1 uppercase tracking-tighter" style="font-size: 1.85rem;">Forgot Password</h2>
                            <p class="text-muted small mb-0">Enter your registered verified email address below and we'll dispatch a secure reset link to your inbox.</p>
                        </div>

                        {{-- Session Status Alert (Success Banner) --}}
                        @if (session('status'))
                            <div class="alert alert-clinical border-success bg-success bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                                <div class="text-start">
                                    <div class="fw-800 uppercase fs-x-small text-success" style="font-size: 0.75rem;">Link Dispatched</div>
                                    <div class="small text-main">{{ session('status') }}</div>
                                </div>
                            </div>
                        @endif

                        {{-- Anti-Brute Force Lockout Banner --}}
                        <div id="lockout-warning" class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm d-none" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid #dc3545 !important; border-radius: 8px;">
                            <i class="bi bi-shield-fill-exclamation text-danger me-3 fs-4"></i>
                            <div class="text-start">
                                <strong class="uppercase text-danger d-block mb-0.5" style="font-size: 0.75rem;">Security Limit Active</strong>
                                <span class="small text-main" id="lockout-message">Too many password reset requests detected. Please wait before retrying.</span>
                            </div>
                        </div>

                        {{-- Dynamic Validation Errors --}}
                        @if ($errors->any() && $backendLockoutSeconds === 0)
                            <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                                <div class="text-start">
                                    <div class="fw-800 uppercase fs-x-small text-danger">Request Failed</div>
                                    <div class="small text-main">{{ $errors->first() }}</div>
                                </div>
                            </div>
                        @endif

                        {{-- Reset Link Form --}}
                        <form id="forgot-password-form" method="POST" action="{{ route('password.email') }}" onsubmit="return handleForgotSubmit(event)" novalidate>
                            @csrf

                            {{-- Email Input --}}
                            <div class="mb-4 text-start">
                                <label for="email" class="small text-secondary fw-bold mb-1 uppercase d-block">Verified Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text border-secondary bg-secondary bg-opacity-10 text-secondary">
                                        <i class="bi bi-envelope-at-fill"></i>
                                    </span>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           style="font-size: 0.95rem; padding: 12px;" 
                                           placeholder="name@domain.com" 
                                           value="{{ old('email') }}" 
                                           required 
                                           autofocus>
                                </div>
                                <small class="text-muted mt-1 d-block" style="font-size: 0.68rem;">
                                    Reset links can only be dispatched to verified account emails.
                                </small>
                                @error('email')
                                    <div class="invalid-feedback d-block fw-bold small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <button id="reset-link-btn" type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm mb-4" style="font-size: 0.9rem; letter-spacing: 0.5px;">
                                SEND RESET LINK
                            </button>

                            {{-- Return to login option --}}
                            <div class="text-center pt-2 border-top border-secondary border-opacity-15">
                                <p class="text-muted small mb-0">Remembered your password? 
                                    <a href="{{ route('login') }}" class="text-accent fw-bold text-decoration-none ms-1">
                                        Back to Login
                                    </a>
                                </p>
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
    const THROTTLE_KEYS = {
        attempts: 'medscreen_forgot_attempts',
        cooldown: 'medscreen_forgot_cooldown',
        lockout: 'medscreen_forgot_lockout'
    };

    const COOLDOWN_SECONDS = 60; // 1-minute cooldown per dispatch
    const LOCKOUT_SECONDS = 600; // 10-minute lockout after 3 consecutive attempts
    const MAX_ATTEMPTS = 3;

    let isSubmitting = false;
    const serverLockoutSec = {{ $backendLockoutSeconds }};

    // 1. Sync Backend Throttler on Page Load
    if (serverLockoutSec > 0) {
        localStorage.setItem(THROTTLE_KEYS.cooldown, (Date.now() + (serverLockoutSec * 1000)).toString());
    }

    // 2. Evaluate Anti-Brute Force Limits
    function updateThrottlerState() {
        const btn = document.getElementById('reset-link-btn');
        const emailInput = document.getElementById('email');
        const warningBox = document.getElementById('lockout-warning');
        const messageEl = document.getElementById('lockout-message');
        if (!btn) return;

        const now = Date.now();
        const lockoutExp = localStorage.getItem(THROTTLE_KEYS.lockout);
        const cooldownExp = localStorage.getItem(THROTTLE_KEYS.cooldown);

        // A. Extended Lockout Active (Exhausted Attempts)
        if (lockoutExp && now < parseInt(lockoutExp)) {
            const remaining = Math.ceil((parseInt(lockoutExp) - now) / 1000);
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            const timeString = m > 0 ? `${m}m ${s}s` : `${s}s`;

            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = `<i class="bi bi-clock-history me-1.5"></i>LOCKED OUT (${timeString})`;

            if (emailInput) emailInput.disabled = true;

            if (warningBox && messageEl) {
                warningBox.classList.remove('d-none');
                messageEl.innerText = `You have made multiple reset requests. To protect account integrity, requests are paused. Please wait ${timeString} before trying again.`;
            }
            return;
        }

        // Lockout expired: clean up
        if (lockoutExp && now >= parseInt(lockoutExp)) {
            localStorage.removeItem(THROTTLE_KEYS.lockout);
            localStorage.setItem(THROTTLE_KEYS.attempts, '0');
        }

        // B. Standard Send Cooldown Active
        if (cooldownExp && now < parseInt(cooldownExp)) {
            const remaining = Math.ceil((parseInt(cooldownExp) - now) / 1000);

            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = `<i class="bi bi-hourglass-split me-1.5"></i>RESEND IN ${remaining}S`;
            if (emailInput) emailInput.disabled = false;
            if (warningBox) warningBox.classList.add('d-none');
            return;
        }

        // Cooldown expired
        if (cooldownExp && now >= parseInt(cooldownExp)) {
            localStorage.removeItem(THROTTLE_KEYS.cooldown);
        }

        // C. Ready for Submission
        if (!isSubmitting) {
            btn.disabled = false;
            btn.style.pointerEvents = '';
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = 'SEND RESET LINK';
            if (emailInput) emailInput.disabled = false;
            if (warningBox) warningBox.classList.add('d-none');
        }
    }

    // 3. Register Submission Attempt & Prevent Double-Submissions
    function handleForgotSubmit(event) {
        const now = Date.now();
        const lockoutExp = localStorage.getItem(THROTTLE_KEYS.lockout);
        const cooldownExp = localStorage.getItem(THROTTLE_KEYS.cooldown);

        if ((lockoutExp && now < parseInt(lockoutExp)) || (cooldownExp && now < parseInt(cooldownExp))) {
            event.preventDefault();
            return false;
        }

        if (isSubmitting) {
            event.preventDefault();
            return false;
        }

        const emailInput = document.getElementById('email');
        if (!emailInput || !emailInput.value.trim()) {
            return true; // Let standard HTML5 validation handle empty state
        }

        isSubmitting = true;
        const btn = document.getElementById('reset-link-btn');
        if (btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.classList.add('disabled', 'opacity-75');
            btn.innerHTML = 'SENDING LINK... <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>';
        }

        // Track attempts in localStorage
        let attempts = parseInt(localStorage.getItem(THROTTLE_KEYS.attempts) || '0');
        attempts++;
        localStorage.setItem(THROTTLE_KEYS.attempts, attempts.toString());

        if (attempts >= MAX_ATTEMPTS) {
            localStorage.setItem(THROTTLE_KEYS.lockout, (now + (LOCKOUT_SECONDS * 1000)).toString());
            localStorage.removeItem(THROTTLE_KEYS.cooldown);
        } else {
            localStorage.setItem(THROTTLE_KEYS.cooldown, (now + (COOLDOWN_SECONDS * 1000)).toString());
        }

        return true;
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateThrottlerState();
        setInterval(updateThrottlerState, 1000);
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