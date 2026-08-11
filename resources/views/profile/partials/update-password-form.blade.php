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
    <form method="post" action="{{ route('profile.password.update') }}" id="passwordUpdateForm">
        @csrf
        @method('put')

        <div class="row g-3 text-start">
            {{-- Current Password --}}
            <div class="col-md-12 mb-2">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Current Password</label>
                <div class="input-group">
                    <input type="password" name="current_password" id="curr_pass" class="form-control" required placeholder="Enter current password">
                    <span class="input-group-text input-group-text-password">
                        <i class="bi bi-eye text-main" id="toggleCurrPass" style="cursor: pointer;"></i>
                    </span>
                </div>
            </div>

            {{-- New Password --}}
            <div class="col-md-6 mb-2">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">New Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="new_pass" class="form-control" required placeholder="Min. 8 characters">
                    <span class="input-group-text input-group-text-password">
                        <i class="bi bi-eye text-main" id="toggleNewPass" style="cursor: pointer;"></i>
                    </span>
                </div>
            </div>

            {{-- Confirm Password --}}
            <div class="col-md-6 mb-2">
                <label class="smaller text-secondary fw-bold mb-1 uppercase">Confirm New Password</label>
                <div class="input-group">
                    <input type="password" name="password_confirmation" id="conf_pass" class="form-control" required placeholder="Repeat password">
                    <span class="input-group-text input-group-text-password">
                        <i class="bi bi-eye text-main" id="toggleConfPass" style="cursor: pointer;"></i>
                    </span>
                </div>
            </div>

            {{-- Symmetrical Guidelines Spanning the Row --}}
            <div class="col-12 mt-2">
                <!-- Styled Password Requirement guidelines block -->
                <div class="p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.02); border-color: var(--border-color) !important;">
                    <small class="text-accent fw-bold uppercase d-block mb-1.5">
                        <i class="bi bi-shield-lock-fill me-1.5"></i>Password Guidelines:
                    </small>
                    <ul class="mb-0 ps-3 text-muted d-flex flex-column gap-1" style="font-size: 0.65rem; list-style-type: disc;">
                        <li>Minimum length of <strong>8 characters</strong>.</li>
                        <li>Include both <strong>uppercase</strong> and <strong>lowercase</strong> characters.</li>
                        <li>Include at least <strong>one number</strong>.</li>
                        <li>Include at least <strong>one special character</strong> (e.g. !@#$%^&*).</li>
                    </ul>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-custom btn-accent mt-4 px-4 uppercase fw-bold">UPDATE PASSWORD</button>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pwdForm = document.getElementById('passwordUpdateForm');

        if (pwdForm) {
            // Initialize eye toggle controllers using global setupPasswordToggle helper
            setupPasswordToggle('#curr_pass', '#toggleCurrPass');
            setupPasswordToggle('#new_pass', '#toggleNewPass');
            setupPasswordToggle('#conf_pass', '#toggleConfPass');

            pwdForm.addEventListener('submit', function(e) {
                let hasErrors = false;

                const currPass = document.getElementById('curr_pass');
                const newPass = document.getElementById('new_pass');
                const confPass = document.getElementById('conf_pass');

                // Clear existing errors
                clearPasswordErrors();

                // 1. Current Password Validation
                if (!currPass.value.trim()) {
                    showPasswordError(currPass, 'Current Password is required.');
                    hasErrors = true;
                }

                // 2. New Password Validation
                const val = newPass.value;
                if (!val.trim()) {
                    showPasswordError(newPass, 'New Password is required.');
                    hasErrors = true;
                } else {
                    if (val.length < 8) {
                        showPasswordError(newPass, 'Password must be at least 8 characters long.');
                        hasErrors = true;
                    } else if (!/[A-Z]/.test(val) || !/[a-z]/.test(val)) {
                        showPasswordError(newPass, 'Password must contain both uppercase and lowercase characters.');
                        hasErrors = true;
                    } else if (!/[0-9]/.test(val)) {
                        showPasswordError(newPass, 'Password must include at least one number.');
                        hasErrors = true;
                    } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(val)) {
                        showPasswordError(newPass, 'Password must include at least one special character.');
                        hasErrors = true;
                    }
                }

                // 3. Confirm Password Validation
                if (!confPass.value.trim()) {
                    showPasswordError(confPass, 'Confirm Password is required.');
                    hasErrors = true;
                } else if (val !== confPass.value) {
                    showPasswordError(confPass, 'Password confirmation does not match.');
                    hasErrors = true;
                }

                if (hasErrors) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
    });

    function showPasswordError(inputElement, errorMessage) {
        if (!inputElement) return;
        inputElement.classList.add('is-invalid');

        let parent = inputElement.parentElement;
        let targetParent = parent.classList.contains('input-group') ? parent.parentElement : parent;

        let existingError = targetParent.querySelector('.invalid-feedback-inline');
        if (existingError) {
            existingError.innerText = errorMessage;
            existingError.classList.remove('d-none');
        } else {
            let errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback-inline text-danger small mt-1 fw-bold';
            errorDiv.innerText = errorMessage;
            targetParent.appendChild(errorDiv);
        }

        // Dismiss error on input
        const dismissHandler = () => {
            inputElement.classList.remove('is-invalid');
            let errorDiv = targetParent.querySelector('.invalid-feedback-inline');
            if (errorDiv) {
                errorDiv.classList.add('d-none');
                errorDiv.innerText = '';
            }
            inputElement.removeEventListener('input', dismissHandler);
        };
        inputElement.addEventListener('input', dismissHandler);
    }

    function clearPasswordErrors() {
        document.querySelectorAll('#passwordUpdateForm .is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('#passwordUpdateForm .invalid-feedback-inline').forEach(el => {
            el.classList.add('d-none');
            el.innerText = '';
        });
    }
</script>
@endpush

@push('styles')
<style>
    /* Seamless style alignment overrides for input group containers */
    .input-group-text-password {
        background-color: var(--bg-card) !important;
        border: 1.5px solid var(--border-color) !important;
        border-left: none !important;
        color: var(--text-main) !important;
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 0 16px;
        border-top-right-radius: 10px !important;
        border-bottom-right-radius: 10px !important;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .input-group .form-control {
        border-right: none !important;
    }
    
    .input-group .form-control:focus + .input-group-text-password {
        border-color: var(--brand-accent) !important;
    }
    
    .input-group .form-control.is-invalid + .input-group-text-password {
        border-color: #ff4d4d !important;
    }
</style>
@endpush