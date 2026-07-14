@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg animate-page" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">
                
                {{-- LEFT PANEL: CLINICAL GATEWAY (Always Dark for high-contrast presentation) --}}
                <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between p-5 bg-brand-dark position-relative" style="min-height: 600px;">
                    {{-- Soft backdrop overlay and dark brand styling --}}
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; opacity: 0.15; z-index: 1;"></div>
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, var(--brand-dark) 0%, rgba(28, 35, 45, 0.95) 100%); z-index: 2;"></div>
                    
                    {{-- Brand Content --}}
                    <div class="position-relative" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 52px; width: 52px; border-radius: 50%;">
                            <span class="text-white uppercase fw-800 fs-3 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                        </div>
                        <h1 class="display-4 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Clinical diagnostics, simplified.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Securely access your laboratory results, manage your clinical appointments, and view real-time health updates all in one place.</p>
                    </div>
                    
                    {{-- Bottom Information --}}
                    <div class="position-relative mt-auto pt-4" style="z-index: 3;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-25 text-neon border border-neon border-opacity-25 px-3 py-2 uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Secured Demo Environment
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT PANEL: LOGIN FORM (Dynamic Background for Theme Compatibility) --}}
                <div class="col-lg-6 d-flex flex-column justify-content-center p-5 bg-card">
                    <div class="w-100 py-3" style="max-width: 400px; margin: 0 auto;">
                        
                        {{-- Header --}}
                        <div class="mb-4 text-start">
                            <h2 class="text-main fw-800 mb-1 uppercase tracking-tighter" style="font-size: 1.85rem;">Welcome Back</h2>
                            <p class="text-muted small mb-0">Enter your registered credentials to securely log in to your account.</p>
                        </div>

                        {{-- Custom Error Alert --}}
                        @if ($errors->any())
                        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                            <div>
                                <div class="fw-800 uppercase fs-x-small text-danger">Action Required</div>
                                <div class="small text-main">Invalid email or password. Please try again.</div>
                            </div>
                        </div>
                        @endif

                        {{-- Login Form --}}
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            {{-- Email Input --}}
                            <div class="mb-3 text-start">
                                <label class="small text-muted mb-1 fw-bold">EMAIL ADDRESS</label>
                                <input type="email" name="email" class="form-control" style="font-size: 0.95rem; padding: 14px;" placeholder="example@gmail.com" value="{{ old('email') }}" required autofocus>
                            </div>

                            {{-- Password Input --}}
                            <div class="mb-4 text-start">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="small text-muted mb-0 fw-bold">PASSWORD</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-accent small fw-bold text-decoration-none" style="font-size: 0.75rem;">Forgot password?</a>
                                    @endif
                                </div>
                                <div class="password-container position-relative">
                                    <input type="password" name="password" id="login_pass" class="form-control" style="font-size: 0.95rem; padding: 14px; padding-right: 48px;" placeholder="Enter password" required>
                                    <i class="bi bi-eye password-toggle text-accent" id="toggleLoginPass" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                                </div>
                            </div>

                            {{-- Remember Me --}}
                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check text-start">
                                    <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                    <label class="form-check-label small text-muted" for="remember">Remember me</label>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm mb-4" style="font-size: 0.9rem; letter-spacing: 0.5px;">
                                LOG IN
                            </button>

                            {{-- Redirection to register --}}
                            <div class="text-center">
                                <p class="text-muted small mb-0">Don't have an account? 
                                    <a href="{{ route('register') }}" class="text-accent fw-bold text-decoration-none">Register here</a>
                                </p>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    .password-container input {
        padding-right: 45px;
    }
</style>
@endsection

@push('scripts')
<script>
    setupPasswordToggle('#login_pass', '#toggleLoginPass');
</script>
@endpush