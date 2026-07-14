<!-- GLOBAL ACCESS REASON MODAL (THE REASON-GATE) -->
<div class="modal fade" id="accessReasonModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        
        {{-- The form action is set dynamically via JavaScript (promptAccess function) --}}
        <form id="accessReasonForm" method="POST" class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            
            {{-- Hidden trackers used by ResultController@logAccess --}}
            <input type="hidden" name="type" id="access_type">
            <input type="hidden" name="mode" id="access_mode">
            <input type="hidden" name="target_user_id" id="target_user_id">
            
            {{-- Modal Header --}}
            <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                <h6 class="modal-title text-accent fw-bold uppercase d-flex align-items-center m-0">
                    <i class="bi bi-shield-lock-fill me-2 fs-5 text-accent"></i> 
                    Clinical Access Authorization
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start">
                {{-- Unified Clinical Alert Style --}}
                <div class="alert-clinical p-3 mb-4">
                    <p class="small mb-0" style="color: var(--text-main);">
                        <i class="bi bi-info-circle text-accent me-2"></i>
                        <strong>Security Protocol:</strong> All access to patient clinical data is monitored. Please select or provide a valid justification for this request.
                    </p>
                </div>

                {{-- Reason Dropdown Select --}}
                <div class="mb-3">
                    <label for="access_reason_select" class="smaller fw-bold mb-2 uppercase tracking-wider" style="color: var(--text-muted);">
                        Reason for Access / Audit Note
                    </label>
                    <select id="access_reason_select" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                        <option value="" disabled selected>-- Select a valid justification --</option>
                        <option value="Routine Patient Care / File Retrieval">Routine Patient Care / File Retrieval</option>
                        <option value="Verification & Clinical Sign-off">Verification & Clinical Sign-off</option>
                        <option value="Data Entry & Result Encoding">Data Entry & Result Encoding</option>
                        <option value="Administrative Audit / Security Protocol Review">Administrative Audit / Security Protocol Review</option>
                        <option value="Others">Others (Specify justification below)</option>
                    </select>
                </div>

                {{-- Hidden custom justification field --}}
                <div id="custom_reason_wrapper" class="mb-3 d-none">
                    <label for="access_reason" class="smaller fw-bold mb-2 uppercase tracking-wider" style="color: var(--text-muted);">
                        Specify Custom Justification
                    </label>
                    <textarea 
                        name="access_reason" 
                        id="access_reason" 
                        class="form-control shadow-none" 
                        style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);"
                        rows="4" 
                        placeholder="e.g., Patient explicitly requested record history review..."></textarea>
                    <div class="mt-2">
                        <small class="text-muted smaller italic">
                            Minimum 5 characters required for the audit trail.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
                <div class="d-flex w-100">
                    <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">
                        Authorize & Proceed <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectEl = document.getElementById('access_reason_select');
    const textareaWrapper = document.getElementById('custom_reason_wrapper');
    const textareaEl = document.getElementById('access_reason');
    const formEl = document.getElementById('accessReasonForm');

    if (selectEl && textareaEl && textareaWrapper && formEl) {
        
        // Listen to change events on the dropdown justification menu
        selectEl.addEventListener('change', function() {
            if (this.value === 'Others') {
                textareaWrapper.classList.remove('d-none');
                textareaEl.setAttribute('required', 'required');
                textareaEl.value = ''; // Reset Custom field
            } else {
                textareaWrapper.classList.add('d-none');
                textareaEl.removeAttribute('required');
                textareaEl.value = this.value; // Store standard justification directly
            }
        });

        // Pre-submit validation and value alignment step
        formEl.addEventListener('submit', function(e) {
            if (selectEl.value !== 'Others') {
                textareaEl.value = selectEl.value;
            }
            
            // Validate minimum character audit restriction
            if (textareaEl.value.trim().length < 5) {
                e.preventDefault();
                alert('A valid reason of at least 5 characters is required to generate the audit trail.');
            }
        });
    }
});
</script>

<style>
/* Modal Animation refined transition */
.modal.fade .modal-dialog {
    transform: scale(0.95);
    transition: transform 0.2s ease-out;
}
.modal.show .modal-dialog {
    transform: scale(1);
}

.hover-bg-neon:hover {
    background-color: rgba(25, 211, 140, 0.05);
}

.tracking-wider {
    letter-spacing: 0.5px;
}
</style>