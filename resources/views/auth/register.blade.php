@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card p-4 shadow-lg border-0 mb-5">
            <div class="text-center mb-4">
                {{-- Circular Logo --}}
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo mx-auto mb-3" style="height: 70px; width: 70px;">
                <h3 class="text-neon fw-bold mb-1">ACCOUNT REGISTRATION</h3>
            </div>

            {{-- Error Handling --}}
            @if ($errors->any())
                <div class="alert bg-black border border-danger text-danger small mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="row g-3">
                    {{-- Full Name --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1">FULL NAME</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter your full name" value="{{ old('name') }}" required>
                    </div>

                    {{-- Email & Phone --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1">EMAIL ADDRESS</label>
                        <input type="email" name="email" class="form-control" placeholder="name@email.com" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1">PHONE NUMBER</label>
                        <input type="text" name="phone" class="form-control" placeholder="09xxxxxxxxx" value="{{ old('phone') }}" required>
                    </div>

                    {{-- Birthdate & Sex --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1">BIRTHDATE</label>
                        <input type="date" name="birthdate" class="form-control" value="{{ old('birthdate') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1">SEX</label>
                        <select name="sex" class="form-select" required>
                            <option value="" disabled {{ old('sex') ? '' : 'selected' }}>Select Sex</option>
                            <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    {{-- Address --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1">HOME ADDRESS</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street, Brgy, City, Province" required>{{ old('address') }}</textarea>
                    </div>

                    {{-- Password Fields with Eye Toggles --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1">PASSWORD</label>
                        <div class="password-container">
                            <input type="password" name="password" id="reg_pass" class="form-control" placeholder="Min. 8 characters" required>
                            <i class="bi bi-eye password-toggle" id="toggleRegPass"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1">CONFIRM PASSWORD</label>
                        <div class="password-container">
                            <input type="password" name="password_confirmation" id="reg_pass_conf" class="form-control" placeholder="Repeat password" required>
                            <i class="bi bi-eye password-toggle" id="toggleRegPassConf"></i>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold">CREATE ACCOUNT</button>
                    <p class="text-center text-secondary small mt-4">
                        Already have an account? <a href="{{ route('login') }}" class="text-neon fw-bold">Log in here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Individual toggles for both fields
    setupPasswordToggle('#reg_pass', '#toggleRegPass');
    setupPasswordToggle('#reg_pass_conf', '#toggleRegPassConf');
</script>
@endpush