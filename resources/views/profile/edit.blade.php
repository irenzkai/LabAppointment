@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo" style="height: 50px; width: 50px; border-radius: 50%;">
            <h3 class="text-neon fw-bold mb-0 uppercase" style="letter-spacing: 2px;">Account Settings</h3>
        </div>

        {{-- 1. PERSONAL INFORMATION --}}
        <div class="card p-4 mb-4 shadow-lg border-secondary">
            <h5 class="text-white fw-bold mb-4 border-bottom border-secondary pb-2 uppercase" style="letter-spacing: 1px;">Personal Information</h5>
            <form method="post" action="{{ route('profile.update') }}">
                @csrf @method('patch')
                <div class="row g-3 text-start">
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                        <input type="date" name="birthdate" class="form-control" value="{{ $user->birthdate ? $user->birthdate->format('Y-m-d') : '' }}" required>
                    </div>
                    <div class="col-12">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Address</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address', $user->address) }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn-custom btn-neon mt-4 px-4">SAVE DETAILS</button>
            </form>
        </div>

        {{-- 2. UPDATE PASSWORD --}}
        <div class="card p-4 mb-4 shadow-lg border-secondary text-start">
            <h5 class="text-white fw-bold mb-4 border-bottom border-secondary pb-2 uppercase" style="letter-spacing: 1px;">Update Password</h5>
            
            @if($errors->updatePassword->any())
                <div class="alert bg-black border-danger text-danger small py-2">
                    {{ $errors->updatePassword->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('password.update') }}">
                @csrf @method('put')
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Current Password</label>
                        <div class="password-container">
                            <input type="password" name="current_password" id="curr_pass" class="form-control" required>
                            <i class="bi bi-eye password-toggle text-neon" id="toggleCurrPass"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">New Password</label>
                        <div class="password-container">
                            <input type="password" name="password" id="new_pass" class="form-control" required>
                            <i class="bi bi-eye password-toggle text-neon" id="toggleNewPass"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Confirm New Password</label>
                        <div class="password-container">
                            <input type="password" name="password_confirmation" id="conf_pass" class="form-control" required>
                            <i class="bi bi-eye password-toggle text-neon" id="toggleConfPass"></i>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-custom btn-neon mt-4 px-4">UPDATE PASSWORD</button>
            </form>
        </div>

        {{-- 3. DANGER ZONE --}}
        <div class="card p-4 border-danger shadow-sm mb-5 text-start">
            <h5 class="text-danger fw-bold mb-3 border-bottom border-danger pb-2 uppercase" style="letter-spacing: 1px;">Danger Zone</h5>
            <p class="text-secondary smaller mb-4">Deleting your account is permanent and cannot be undone.</p>
            
            <button type="button" class="btn-custom btn-danger-custom py-2 px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#confirmSelfDelete">
                DELETE ACCOUNT PERMANENTLY
            </button>
        </div>
    </div>
</div>

{{-- MODAL: Self Account Deletion --}}
<div class="modal fade" id="confirmSelfDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger bg-black shadow-lg">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf @method('delete')
                
                <div class="modal-header border-danger bg-dark p-3">
                    <h5 class="modal-title text-danger fw-bold uppercase small">Confirm Permanent Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <p class="text-white small mb-4 text-center">Please enter your password to confirm account deletion.</p>
                    
                    <div class="password-container">
                        <label class="smaller text-secondary fw-bold mb-2 uppercase">Password</label>
                        <input type="password" name="password" id="del_pass" class="form-control border-danger" placeholder="••••••••" required>
                        <i class="bi bi-eye password-toggle text-danger" id="toggleDelPass"></i>
                    </div>
                </div>
                
                <div class="modal-footer border-danger bg-dark">
                    <button type="button" class="btn-custom btn-outline-neon py-2 px-3" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">DELETE NOW</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Eye toggles for Password Section
    setupPasswordToggle('#curr_pass', '#toggleCurrPass');
    setupPasswordToggle('#new_pass', '#toggleNewPass');
    setupPasswordToggle('#conf_pass', '#toggleConfPass');
    
    // Eye toggle for Delete Modal (RED)
    setupPasswordToggle('#del_pass', '#toggleDelPass');
</script>
@endpush