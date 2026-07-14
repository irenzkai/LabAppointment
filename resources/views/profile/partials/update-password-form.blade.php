<div class="card p-4 p-md-5 border-secondary shadow-lg animate-page">
    <h5 class="text-main fw-bold mb-4 border-bottom border-secondary border-opacity-25 pb-2 uppercase" style="letter-spacing: 1px;">Update Password</h5>
    
    {{-- Validation Error Alerts --}}
    @if($errors->updatePassword->any())
        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger py-2 px-3 small mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ $errors->updatePassword->first() }}
        </div>
    @endif

    {{-- Password Update Form --}}
    <form method="post" action="{{ route('profile.password.update') }}">
        @csrf
        @method('put')
        
        <div class="row g-3 text-start">
            {{-- Current Password --}}
            <div class="col-md-12">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Current Password</label>
                <div class="password-container position-relative">
                    <input type="password" name="current_password" id="curr_pass" class="form-control" required placeholder="Enter current password">
                    <i class="bi bi-eye password-toggle text-neon" id="toggleCurrPass" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                </div>
            </div>
            
            {{-- New Password --}}
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">New Password</label>
                <div class="password-container position-relative">
                    <input type="password" name="password" id="new_pass" class="form-control" required placeholder="Min. 8 characters">
                    <i class="bi bi-eye password-toggle text-neon" id="toggleNewPass" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                </div>
            </div>
            
            {{-- Confirm Password --}}
            <div class="col-md-6">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Confirm New Password</label>
                <div class="password-container position-relative">
                    <input type="password" name="password_confirmation" id="conf_pass" class="form-control" required placeholder="Repeat password">
                    <i class="bi bi-eye password-toggle text-neon" id="toggleConfPass" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;"></i>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn-custom btn-accent mt-4 px-4 uppercase fw-bold">UPDATE PASSWORD</button>
    </form>
</div>