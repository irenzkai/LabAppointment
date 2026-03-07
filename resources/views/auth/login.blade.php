@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 75vh;">
    <div class="col-md-5 col-lg-4">
        <div class="card p-4 shadow-lg border-0">
            <div class="text-center mb-4">
                {{-- Circular Logo --}}
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo mx-auto mb-3" style="height: 70px; width: 70px;">
                <h3 class="text-neon fw-bold mb-1">LOGIN ACCOUNT</h3>
            </div>

            {{-- Custom Error Alert --}}
            @if ($errors->any())
                <div class="alert bg-black border border-danger text-danger py-2 px-3 small mb-4 d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <span>Invalid email or password.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="small text-secondary mb-1 fw-bold">EMAIL ADDRESS</label>
                    <input type="email" name="email" class="form-control" placeholder="example@gmail.com" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-4">
                    <label class="small text-secondary mb-1 fw-bold">PASSWORD</label>
                    <div class="password-container">
                        <input type="password" name="password" id="login_pass" class="form-control" placeholder="••••••••" required>
                        <i class="bi bi-eye password-toggle" id="toggleLoginPass"></i>
                    </div>
                </div>

                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label small text-secondary" for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn-custom btn-neon w-100 py-3 shadow-sm">LOG IN</button>
                
                <div class="text-center mt-4">
                    <p class="text-secondary small">Don't have an account? 
                        <a href="{{ route('register') }}" class="text-neon fw-bold">Register here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Uses the function defined in app.blade.php
    setupPasswordToggle('#login_pass', '#toggleLoginPass');
</script>
@endpush