@extends('layouts.app')

@section('title', 'Password Reset')

@section('content')
<div class="row justify-content-center align-items-center min-vh-80 animate-page">
    <div class="col-12 col-lg-11 col-xl-10">
        <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="border-radius: 20px;">
            <div class="row g-0 align-items-stretch">
                
                {{-- LEFT PANEL: SECURED RECOVERY (Always Dark for consistent brand presence) --}}
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
                        <h1 class="display-4 fw-800 text-white mb-3 mt-4" style="line-height: 1.15;">Secure credential reset.</h1>
                        <p class="text-white-50 fs-5 mb-0" style="line-height: 1.6;">Establish a new secure password for your clinical portal. Ensure your updated password complies with our complexity protocols to protect your health records.</p>
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

                {{-- RIGHT PANEL: PASSWORD RESET FORM (Dynamic Background for Theme Compatibility) --}}
                <div class="col-lg-6 d-flex flex-column justify-content-center p-5 bg-card">
                    <div class="w-100 py-3" style="max-width: 380px; margin: 0 auto;">
                        
                        {{-- Header --}}
                        <div class="mb-4 text-start">
                            <h2 class="text-main fw-800 mb-2 uppercase tracking-tighter" style="font-size: 1.85rem;">Reset Password</h2>
                            <p class="text-muted small mb-0">Establish your new system credentials below to reactivate your clinical access.</p>
                        </div>

                        {{-- Validation Errors --}}
                        @if ($errors->any())
                            <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                                <div class="text-start">
                                    <div class="fw-800 uppercase fs-x-small text-danger">Validation Failure</div>
                                    <ul class="mb-0 ps-3 text-secondary small">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Reset Password Form --}}
                        <form id="reset-password-form" method="POST" action="{{ route('password.store') }}">
                            @csrf

                            <!-- Password Reset Token -->
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            {{-- Email Input --}}
                            <div class="mb-3 text-start">
                                <label class="small text-muted mb-1 fw-bold">EMAIL ADDRESS</label>
                                <input type="email" name="email" id="email" class="form-control" style="font-size: 0.95rem; padding: 14px;" value="{{ old('email', $request->email) }}" required readonly>
                            </div>

                            {{-- Password Input --}}
                            <div class="mb-3 text-start">
                                <label class="small text-muted mb-1 fw-bold">NEW PASSWORD</label>
                                <div class="password-container position-relative">
                                    <input type="password" name="password" id="reset_pass" class="form-control" style="font-size: 0.95rem; padding: 14px; padding-right: 48px;" placeholder="Min. 8 characters" required>
                                    <i class="bi bi-eye password-toggle text-neon" id="toggleResetPass" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                                </div>
                            </div>

                            {{-- Confirm Password Input --}}
                            <div class="mb-4 text-start">
                                <label class="small text-muted mb-1 fw-bold">CONFIRM NEW PASSWORD</label>
                                <div class="password-container position-relative">
                                    <input type="password" name="password_confirmation" id="reset_pass_conf" class="form-control" style="font-size: 0.95rem; padding: 14px; padding-right: 48px;" placeholder="Repeat password" required>
                                    <i class="bi bi-eye password-toggle text-neon" id="toggleResetPassConf" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold shadow-sm mb-4">
                                UPDATE PASSWORD
                            </button>
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
    document.addEventListener('DOMContentLoaded', () => {
        // Link interactive eye toggles for secure input fields [428]
        setupPasswordToggle('#reset_pass', '#toggleResetPass');
        setupPasswordToggle('#reset_pass_conf', '#toggleResetPassConf');
    });
</script>
@endpush