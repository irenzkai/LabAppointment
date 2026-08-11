@extends('layouts.app')

@section('title', 'Reactivate Account')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">
                
                {{-- LEFT PANEL: SECURED CHANNEL (Always Dark for consistent brand presence) --}}
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 550px;">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>

                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-5 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Reactivate your account.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Welcome back! Please verify your email using the 6-digit verification code sent to your registered address to reactivate your portal and restore your clinical history.</p>
                    </div>

                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Protected Channel
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: OTP VERIFICATION FORM --}}
                <div class="col-lg-7 d-flex flex-column justify-content-center p-4 p-md-5 bg-card text-start">
                    <div class="w-100 py-3" style="max-width: 440px; margin: 0 auto;">
                        
                        {{-- Icon & Heading --}}
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 p-3 text-accent" style="background-color: rgba(25, 211, 140, 0.08); width: 64px; height: 64px;">
                                <i class="bi bi-envelope-open-fill fs-2"></i>
                            </div>
                            <h2 class="text-main fw-800 mb-2 uppercase tracking-tighter" style="font-size: 1.85rem;">Account Reactivation</h2>
                            <p class="text-muted small mb-0">Enter the 6-digit verification code sent to your registered email address to unlock your account.</p>
                        </div>

                        {{-- Success Notification with Dynamic Status Labels --}}
                        @if (session('status'))
                            <div class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm" style="background-color: rgba(25, 211, 140, 0.05); border-left: 4px solid #19D38C !important; border-radius: 8px;">
                                <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                                <div class="text-start">
                                    <div class="fw-800 uppercase fs-x-small text-success" style="font-size: 0.75rem; letter-spacing: 0.5px;">Notification Dispatched</div>
                                    <div class="small text-main" style="color: var(--text-main) !important; font-size: 0.85rem; line-height: 1.4;">
                                        A secure 6-digit One-Time Password has been dispatched to your email address.
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Error Notification --}}
                        @if($errors->any())
                            <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                                <div>
                                    <div class="fw-800 uppercase fs-x-small text-danger">Reactivation Failed</div>
                                    <div class="small text-main">{{ $errors->first() }}</div>
                                </div>
                            </div>
                        @endif

                        {{-- FORM: EMAIL OTP SUBMISSION --}}
                        <div id="form_otp_container">
                            <form method="POST" action="{{ route('reactivate.verify-otp') }}">
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
                                <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm mb-3">
                                    SUBMIT VERIFICATION CODE
                                </button>
                            </form>

                            <div class="mt-3 text-center">
                                <form id="otp-resend-form" method="POST" action="{{ route('reactivate.send-otp') }}">
                                    @csrf
                                    <span class="small text-muted">Didn't receive the code?</span>
                                    <button id="otp-resend-btn" type="submit" class="btn btn-link text-accent fw-bold text-decoration-none p-0 small ms-1 align-baseline" style="font-size: 0.85rem;">
                                        SEND CODE TO EMAIL
                                    </button>
                                </form>
                            </div>
                        </div>

                        <hr class="border-secondary border-opacity-25 my-4">

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            {{-- Cancel / Back to Login Option --}}
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-link text-secondary text-decoration-none small p-0">
                                    <i class="bi bi-box-arrow-left me-1"></i>Cancel & Exit
                                </button>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Auto-focus first OTP block on load
        const firstBox = document.querySelector('.otp-box[data-index="0"]');
        if (firstBox) setTimeout(() => firstBox.focus(), 150);
    });

    // --- 6-DIGIT OTP FIELDS CONTROLLERS ---
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
</script>
@endpush

@push('styles')
<style>
    /* 6-Digit tall input box parameters */
    .otp-box {
        width: 50px;
        height: 60px;
        padding: 0 !important;
        text-align: center;
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
</style>
@endpush