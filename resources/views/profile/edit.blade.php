@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo" style="height: 50px; width: 50px; border-radius: 50%;">
            <h3 class="text-neon fw-bold mb-0 uppercase" style="letter-spacing: 2px;">ACCOUNT SETTINGS</h3>
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
                    <div class="col-md-12">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="Male" {{ old('sex', $user->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex', $user->sex) == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
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

            <form method="post" action="{{ route('profile.password.update') }}">
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

        {{-- MANAGE DEPENDENTS SECTION --}}
        <div class="card p-4 mb-4 shadow-lg border-secondary text-start">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-2">
                <h5 class="text-white fw-bold mb-0 uppercase small">Manage Dependents</h5>
                <button class="btn-custom btn-neon btn-sm" data-bs-toggle="modal" data-bs-target="#addDepModal">
                    <i class="bi bi-person-plus-fill me-1"></i> ADD NEW
                </button>
            </div>

            <div class="row g-3">
                @forelse($user->dependents as $dep)
                    <div class="col-md-6">
                        <div class="p-3 rounded border border-secondary bg-black d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-white fw-bold small">{{ strtoupper($dep->name) }}</div>
                                <div class="text-secondary" style="font-size: 0.65rem;">
                                    {{ strtoupper($dep->relationship) }} | {{ $dep->sex }} | {{ $dep->birthdate->age }} YRS OLD
                                </div>
                            </div>
                            <form action="{{ route('dependents.destroy', $dep->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-custom btn-danger-custom py-1 px-2 border-0" onclick="return confirm('Remove member?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="text-secondary small italic">No family members registered yet.</p></div>
                @endforelse
            </div>
        </div>

        <div class="modal fade" id="addDepModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('dependents.store') }}" method="POST" class="modal-content border-neon bg-black">
                    @csrf
                    <div class="modal-header border-neon bg-dark py-3">
                        <h5 class="modal-title text-neon fw-bold small">ADD FAMILY MEMBER RECORD</h5>
                    </div>
                    
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="text-secondary small fw-bold mb-1 uppercase">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter complete name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="text-secondary small fw-bold mb-1 uppercase">Birthdate</label>
                                <input type="date" name="birthdate" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="text-secondary small fw-bold mb-1 uppercase">Sex</label>
                                <select name="sex" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-secondary small fw-bold mb-1 uppercase">Relationship</label>
                            <input type="text" name="relationship" class="form-control" placeholder="e.g. Son, Daughter, Parent" required>
                        </div>

                        <div class="mb-3 border-top border-secondary pt-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="text-secondary small fw-bold uppercase">Home Address</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="inherit_address" id="useMyAddress" value="1" checked>
                                    <label class="form-check-label text-neon fw-bold" style="font-size: 0.6rem;" for="useMyAddress">INHERIT MY ADDRESS</label>
                                </div>
                            </div>
                            <textarea name="address" id="depAddressInput" class="form-control" rows="2" 
                                    placeholder="Enter different address if needed" disabled></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-neon bg-dark">
                        <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold">SAVE TO FAMILY LIST</button>
                    </div>
                </form>
            </div>
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
                    
                    <label class="smaller text-secondary fw-bold mb-2 uppercase">Password</label>
                    
                    <div class="password-container">
                        <input type="password" name="password" id="del_pass" 
                            class="form-control border-danger bg-dark text-white" 
                            placeholder="••••••••" required>
                        
                        <i class="bi bi-eye password-toggle text-danger-neon" id="toggleDelPass"></i>
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

    document.getElementById('useMyAddress').addEventListener('change', function() {
            const input = document.getElementById('depAddressInput');
            if (this.checked) {
                input.disabled = true;
                input.value = "";
                input.placeholder = "Inheriting your address...";
            } else {
                input.disabled = false;
                input.placeholder = "Enter specific address for this person";
            }
        });
</script>
@endpush