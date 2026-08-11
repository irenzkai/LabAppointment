{{-- Bypassed bg-danger with explicit inline RGBA background for excellent contrast --}}
<div class="card p-4 p-md-5 border-danger shadow-lg animate-page text-start" style="background-color: rgba(220, 53, 69, 0.05) !important;">

    {{-- Card Header --}}
    <h5 class="text-danger fw-bold mb-3 border-bottom border-danger border-opacity-25 pb-2 uppercase" style="letter-spacing: 1px;">
        Delete Account
    </h5>

    {{-- Warning Description --}}
    <p class="text-muted small mb-4" style="line-height: 1.6;">
        Once you submit an account deactivation request, your profile will be immediately deactivated and placed in a secure, restricted-access archive. In compliance with medical record-keeping and audit regulations, all associated appointment files, family dependent records, and diagnostic histories will be safely retained for <strong>10 years</strong> before being permanently purged from our databases. If your account is not reactivated during this 10-year deactivation window, all records will be irreversibly destroyed.
    </p>

    {{-- Permanent Deletion Trigger Button --}}
    <button type="button" class="btn-custom btn-danger-custom py-3 px-4 fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#confirmSelfDelete">
        DELETE ACCOUNT PERMANENTLY
    </button>
</div>

{{-- SELF ACCOUNT PERMANENT DELETION MODAL --}}
<div class="modal fade" id="confirmSelfDelete" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <form method="POST" id="confirmSelfDeleteForm" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')

                <div class="modal-header border-danger border-bottom border-secondary border-opacity-10 p-3">
                    <h5 class="modal-title text-danger fw-bold uppercase small" style="letter-spacing: 0.5px;">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Confirm Permanent Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 text-start">
                    <p class="text-main small mb-4 text-center">Please enter your password to confirm account deletion.</p>

                    {{-- Dynamic AJAX Password Error Message Container --}}
                    <div id="modal_del_error_container" class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger py-2 px-3 small mb-3 d-flex align-items-center gap-2 d-none">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span id="modal_del_error_text"></span>
                    </div>

                    <label class="smaller text-secondary fw-bold mb-2 uppercase">Password</label>

                    {{-- Input group wrapper matching global theme inputs --}}
                    <div class="input-group">
                        <input type="password" name="password" id="del_pass" class="form-control border-danger" placeholder="Enter password" required style="border-right: none !important;">
                        <span class="input-group-text input-group-text-password border-danger">
                            <i class="bi bi-eye text-danger" id="toggleDelPass" style="cursor: pointer;"></i>
                        </span>
                    </div>
                </div>

                <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3" style="border-top: 1px solid var(--border-color); width: 100% !important;">
                    <div class="row g-2 w-100 m-0" style="display: flex !important; flex-direction: row !important; width: 100% !important;">
                        <div class="col-6 p-1">
                            <button type="button" class="btn btn-outline-secondary w-100 py-3" data-bs-dismiss="modal" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">CANCEL</button>
                        </div>
                        <div class="col-6 p-1">
                            <button type="submit" id="modal_del_submit_btn" class="btn-custom btn-danger-custom w-100 py-3" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">DELETE NOW</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        setupPasswordToggle('#del_pass', '#toggleDelPass');

        const deleteForm = document.getElementById('confirmSelfDeleteForm');
        const submitBtn = document.getElementById('modal_del_submit_btn');
        const errorContainer = document.getElementById('modal_del_error_container');
        const errorText = document.getElementById('modal_del_error_text');
        const passwordInput = document.getElementById('del_pass');

        if (deleteForm) {
            deleteForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Clear previous error messages & set loading state
                errorContainer.classList.add('d-none');
                errorText.textContent = '';
                passwordInput.classList.remove('is-invalid');
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'DELETING... <span class="spinner-border spinner-border-sm ms-2"></span>';

                try {
                    const response = await fetch(deleteForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new FormData(deleteForm)
                    });

                    if (response.status === 422) {
                        const data = await response.json();
                        // Extract password error from Laravel's error bag structure
                        const passwordError = data.errors?.password ? data.errors.password[0] : 'Validation failed. Please try again.';
                        
                        passwordInput.classList.add('is-invalid');
                        errorText.textContent = passwordError;
                        errorContainer.classList.remove('d-none');
                    } else if (response.ok) {
                        // Successfully deleted, redirect to home page cleanly
                        window.location.href = '/';
                    } else {
                        errorText.textContent = 'An unexpected server error occurred. Please try again.';
                        errorContainer.classList.remove('d-none');
                    }
                } catch (err) {
                    console.error(err);
                    errorText.textContent = 'Server connection error. Please try again.';
                    errorContainer.classList.remove('d-none');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'DELETE NOW';
                }
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
    /* Prevent native browser password reveal icons from interfering with our custom eye icon */
    #confirmSelfDelete input[type="password"]::-ms-reveal,
    #confirmSelfDelete input[type="password"]::-ms-clear {
        display: none !important;
    }

    #confirmSelfDelete .input-group-text-password {
        border-color: #ff4d4d !important;
        background-color: var(--bg-card) !important;
        border-left: none !important;
        cursor: pointer;
        padding: 0 16px;
        border-top-right-radius: 10px !important;
        border-bottom-right-radius: 10px !important;
    }

    #confirmSelfDelete .form-control:focus {
        border-color: #ff4d4d !important;
        box-shadow: 0 0 0 4px rgba(255, 77, 77, 0.15) !important;
    }

    #confirmSelfDelete .form-control:focus + .input-group-text-password {
        border-color: #ff4d4d !important;
    }
</style>
@endpush