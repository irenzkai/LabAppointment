@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">
                
                {{-- LEFT PANEL: SECURED RECOVERY (Always Dark for high-contrast presentation) --}}
                <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 550px;">
                    {{-- Soft backdrop overlay and dark brand styling --}}
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.12; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>
                    
                    {{-- Brand Content --}}
                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-4 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Secure password recovery.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Retrieve access to your personal portal safely. We will assist you in setting up a new secure password once we verify your identity.</p>
                    </div>
                    
                    {{-- Bottom Information --}}
                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Protected Recovery Channel
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: EMAIL RESET LINK FORM (Dynamic Background for Theme Compatibility) --}}
                <div class="col-lg-6 d-flex flex-column justify-content-center p-5 bg-card">
                    <div class="w-100 py-3" style="max-width: 380px; margin: 0 auto;">
                        
                        {{-- Header --}}
                        <div class="mb-4 text-start">
                            <h2 class="text-main fw-800 mb-2 uppercase tracking-tighter" style="font-size: 1.85rem;">Forgot Password</h2>
                            <p class="text-muted small mb-0">No problem. Provide your registered email address below, and we'll transmit a secure reset link to your inbox.</p>
                        </div>

                        {{-- Session Status Alert (Dynamic success box) --}}
                        @if (session('status'))
                        <div class="alert alert-clinical border-success bg-success bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-3 fs-4 text-success"></i>
                            <div>
                                <div class="fw-800 uppercase fs-x-small text-success">Success</div>
                                <div class="small text-main">{{ session('status') }}</div>
                            </div>
                        </div>
                        @endif

                        {{-- Validation Errors --}}
                        @if ($errors->any())
                        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                            <div>
                                <div class="fw-800 uppercase fs-x-small text-danger">Action Required</div>
                                <div class="small text-main">We couldn't locate an account with that email address.</div>
                            </div>
                        </div>
                        @endif

                        {{-- Reset Link Form --}}
                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            
                            {{-- Email Input --}}
                            <div class="mb-4 text-start">
                                <label class="small text-muted mb-1 fw-bold">EMAIL ADDRESS</label>
                                <input type="email" name="email" id="email" class="form-control" style="font-size: 0.95rem; padding: 14px;" placeholder="example@gmail.com" value="{{ old('email') }}" required autofocus>
                            </div>

                            {{-- Submit --}}
                            <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm mb-4" style="font-size: 0.9rem; letter-spacing: 0.5px;">
                                SEND RESET LINK
                            </button>

                            {{-- Return to login option --}}
                            <div class="text-center">
                                <p class="text-muted small mb-0">Remembered your credentials? 
                                    <a href="{{ route('login') }}" class="text-accent fw-bold text-decoration-none">Back to Login</a>
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