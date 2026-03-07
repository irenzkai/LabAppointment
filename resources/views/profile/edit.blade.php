@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo" style="height: 50px; width: 50px;">
            <h3 class="text-neon fw-bold mb-0">ACCOUNT SETTINGS</h3>
        </div>

        {{-- Personal Information Card --}}
        <div class="card p-4 mb-4 shadow-lg border-secondary">
            <h5 class="text-white fw-bold mb-4 border-bottom border-secondary pb-2 uppercase" style="letter-spacing: 1px;">PERSONAL INFORMATION</h5>
            <form method="post" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                <div class="row g-3 text-start">
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                        <input type="date" name="birthdate" class="form-control" value="{{ old('birthdate', $user->birthdate ? $user->birthdate->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                        <select name="sex" class="form-select">
                            <option value="Male" {{ $user->sex == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ $user->sex == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Home Address</label>
                        <textarea name="address" class="form-control" rows="3" required>{{ old('address', $user->address) }}</textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-custom btn-neon px-5 py-2">SAVE CHANGES</button>
                </div>
            </form>
        </div>

        {{-- Danger Zone Card --}}
        <div class="card p-4 border-danger shadow-sm mb-5">
            <h5 class="text-danger fw-bold mb-3 border-bottom border-danger pb-2 uppercase" style="letter-spacing: 1px;">Danger Zone</h5>
            <p class="text-secondary smaller mb-4">Deleting your account is permanent. All of your patient data, appointments, and medical history will be lost forever.</p>
            
            <!-- Trigger Button for Modal -->
            <button type="button" class="btn-custom btn-danger-custom py-2 px-4" data-bs-toggle="modal" data-bs-target="#confirmSelfDelete">
                <i class="bi bi-trash3-fill me-2"></i> DELETE ACCOUNT PERMANENTLY
            </button>
        </div>
    </div>
</div>

{{-- MODAL: Self Account Deletion --}}
<div class="modal fade" id="confirmSelfDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger bg-black shadow-lg">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                
                <div class="modal-header border-danger bg-dark p-4">
                    <h5 class="modal-title text-danger fw-bold uppercase"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 text-center">
                    <p class="text-white mb-4">To confirm you want to delete your account, please enter your current password below.</p>
                    
                    <div class="password-container text-start">
                        <label class="smaller text-secondary fw-bold mb-2 uppercase">Your Password</label>
                        <input type="password" name="password" id="del_pass" class="form-control border-danger mb-2" placeholder="••••••••" required>
                        <i class="bi bi-eye password-toggle" id="toggleDelPass"></i>
                    </div>
                </div>
                
                <div class="modal-footer border-danger bg-dark">
                    <button type="button" class="btn-custom btn-outline-neon py-2 px-3" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">PROCEED TO DELETE</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    setupPasswordToggle('#del_pass', '#toggleDelPass');
</script>
@endpush