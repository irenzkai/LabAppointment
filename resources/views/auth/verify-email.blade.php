@extends('layouts.app')

@section('title', 'Email Verification')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">
                
                {{-- LEFT PANEL: SECURED CHANNEL (Always Dark for consistent brand presence) --}}
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 550px;">
                    {{-- Soft backdrop overlay and dark brand styling --}}
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>
                    
                    {{-- Brand Content --}}
                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-5 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Finalize your registration.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Please activate your secure account to book diagnostic examinations, access schedules, and view clinical history logs.</p>
                    </div>
                    
                    {{-- Bottom Information --}}
                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Protected Channel
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: INTERACTIVE FORM (Responsive Theme Surface) --}}
                <div class="col-lg-7 d-flex flex-column justify-content-center p-4 p-md-5 bg-card">
                    <div class="w-100 py-3" style="max-width: 440px; margin: 0 auto;">
                        
                        {{-- Icon & Heading --}}
                        <div class="text-start mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 p-3 text-accent" style="background-color: rgba(25, 211, 140, 0.08); width: 64px; height: 64px;">
                                <i class="bi bi-envelope-open-fill fs-2"></i>
                            </div>
                            <h2 class="text-main fw-800 mb-2 uppercase tracking-tighter" style="font-size: 1.85rem;">Verify Your Email</h2>
                            <p class="text-muted small mb-0">We sent a secure validation link to your registered email address. Please click the link to activate your medical profile.</p>
                        </div>

                        {{-- Success Notification (When link is resent) --}}
                        @if (session('status'))
                            <div class="alert alert-clinical border-success bg-success bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert" style="background-color: rgba(25, 211, 140, 0.05); border-left: 4px solid #19D38C !important; border-radius: 8px;">
                                <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                                <div class="text-start">
                                    <div class="fw-800 uppercase fs-x-small text-success" style="font-size: 0.75rem; letter-spacing: 0.5px;">Link Dispatched</div>
                                    <div class="small text-main" style="color: var(--text-main) !important; font-size: 0.85rem; line-height: 1.4;">
                                        A fresh activation link has been sent to your email inbox.
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="d-grid gap-3">
                            <form id="resend-form" method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button id="resend-btn" type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm">
                                    RESEND ACTIVATION EMAIL
                                </button>
                            </form>

                            <hr class="border-secondary border-opacity-25 my-2">

                            {{-- Logout / Exit Options --}}
                            <form method="POST" action="{{ route('logout') }}" class="text-center">
                                @csrf
                                <button type="submit" class="btn btn-link text-secondary text-decoration-none small hover-accent p-0">
                                    <i class="bi bi-box-arrow-left me-2"></i>Logout and try again later
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
        const resendForm = document.getElementById('resend-form');
        const resendBtn = document.getElementById('resend-btn');
        const cooldownTime = 60; // Cooldown duration in seconds

        function startCooldown(remainingSeconds) {
            resendBtn.disabled = true;
            resendBtn.classList.add('opacity-50', 'cursor-not-allowed');
            
            const timer = setInterval(() => {
                remainingSeconds--;
                if (remainingSeconds <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    resendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    resendBtn.innerHTML = 'RESEND ACTIVATION EMAIL';
                    localStorage.removeItem('resend_cooldown_timestamp');
                } else {
                    resendBtn.innerHTML = `RESEND IN ${remainingSeconds}S`;
                }
            }, 1000);
        }

        // Check if there is an active cooldown saved on page load
        const savedTimestamp = localStorage.getItem('resend_cooldown_timestamp');
        if (savedTimestamp) {
            const secondsPassed = Math.floor((Date.now() - parseInt(savedTimestamp)) / 1000);
            if (secondsPassed < cooldownTime) {
                startCooldown(cooldownTime - secondsPassed);
            } else {
                localStorage.removeItem('resend_cooldown_timestamp');
            }
        }

        // On form submit, capture timestamp and begin cooldown
        if (resendForm) {
            resendForm.addEventListener('submit', () => {
                localStorage.setItem('resend_cooldown_timestamp', Date.now());
            });
        }

        // Poll the server status endpoint every 3 seconds to check if they completed verification elsewhere
        const checkVerificationStatus = setInterval(async () => {
            try {
                const response = await fetch("{{ route('verification.status') }}");
                if (response.ok) {
                    const data = await response.json();
                    if (data.verified) {
                        clearInterval(checkVerificationStatus);
                        // FIXED: Append verified parameter to trigger the success banner [509]
                        window.location.href = "{{ route('dashboard') }}?verified=1";
                    }
                }
            } catch (error) {
                console.error('Verification poll error:', error);
            }
        }, 3000);
    });
</script>
@endpush