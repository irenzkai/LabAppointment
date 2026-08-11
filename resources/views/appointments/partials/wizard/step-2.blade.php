<!-- PAGE 2: PATIENT DETAILS -->
<div class="wiz-section d-none text-start animate-page" id="page-2">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-10 pb-2">
        <div>
            <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Patient Information</h3>
            <p class="text-secondary small">Please verify or enter the details for this medical record.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-accent py-2 px-3 fw-bold" onclick="resetPatientDetails()">
            <i class="bi bi-arrow-counterclockwise me-1"></i> RESET INFO
        </button>
    </div>

    <div class="row g-3">
        {{-- Basic Identity Row with Fixed-Height Label Wrappers --}}
        <div class="col-md-3">
            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">First Name</label>
            </div>
            <input type="text" name="patient_first_name" id="in_first_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="First Name" oninput="updateSummary(); clearInlineError(this)" required>
            <div class="invalid-feedback" id="err_first_name"></div>
        </div>

        <div class="col-md-3">
            <div class="d-flex justify-content-between align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">Middle Name</label>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="profile_no_mn" onclick="toggleProfileMN(this)" style="margin-top: 0.15rem;">
                    <label class="smaller text-muted" style="font-size: 0.65rem; line-height: 1;" for="profile_no_mn">None</label>
                </div>
            </div>
            <input type="text" name="patient_middle_name" id="in_middle_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Middle Name" oninput="updateSummary(); clearInlineError(this)">
            <div class="invalid-feedback" id="err_middle_name"></div>
        </div>

        <div class="col-md-3">
            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">Last Name</label>
            </div>
            <input type="text" name="patient_last_name" id="in_last_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Last Name" oninput="updateSummary(); clearInlineError(this)" required>
            <div class="invalid-feedback" id="err_last_name"></div>
        </div>

        <div class="col-md-3">
            <div class="d-flex align-items-center mb-1" style="height: 22px;">
                <label class="small text-secondary fw-bold mb-0 uppercase">Suffix (Opt.)</label>
            </div>
            <input type="text" name="patient_suffix" id="in_suffix" list="suffix_options" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="e.g. JR" oninput="updateSummary(); clearInlineError(this)">
            <div class="invalid-feedback" id="err_suffix"></div>
        </div>

        {{-- Sex Selector --}}
        <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
            <select name="patient_sex" id="in_sex" class="form-select py-3 shadow-none" onchange="clearInlineError(this)" required>
                <option value="">Select Sex</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
            <div class="invalid-feedback" id="err_sex"></div>
        </div>

        {{-- Birthdate Input --}}
        <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
            <input type="date" name="patient_birthdate" id="in_bday" class="form-control py-3 shadow-none" onchange="clearInlineError(this)" required max="{{ date('Y-m-d') }}">
            <div class="invalid-feedback" id="err_birthdate"></div>
        </div>

        {{-- Contact Number --}}
        <div class="col-12 mt-2">
            <label class="small text-secondary fw-bold mb-1 uppercase">Contact Number</label>
            <div class="input-group">
                <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                <input type="text" id="phone_display" class="form-control py-3 shadow-none" placeholder="171234567" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncWizardPhone(); clearInlineError(document.getElementById('in_phone'))" required>
            </div>
            <input type="hidden" name="patient_phone" id="in_phone" required>
            <div class="invalid-feedback d-block" id="err_phone"></div>
            <div class="mt-1">
                <small class="text-muted smaller">
                    <i class="bi bi-info-circle me-1"></i> For dependents, the guardian's contact number is used for notifications.
                </small>
            </div>
        </div>

        {{-- Doctor's Referral File Upload --}}
        <div class="col-12 mt-2">
            <label class="small text-secondary fw-bold mb-1 uppercase">Doctor's Referral / Note (Optional)</label>
            
            <div id="referral_input_wrapper">
                <input type="file" name="referral_note" id="in_referral" class="form-control py-3 shadow-none" accept="image/*, application/pdf" onchange="handleReferralUpload(this)">
            </div>

            <div class="mt-1">
                <small class="text-muted smaller">
                    <i class="bi bi-file-earmark-plus me-1"></i> Upload a PDF or image of your doctor's written referral or laboratory request note.
                </small>
            </div>

            {{-- Polished Referral Preview Card (Stroke border removed safely) --}}
            <div id="referral_preview_container" class="d-none mt-3 p-2.5 rounded" style="background-color: rgba(25, 211, 140, 0.03);">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-accent fw-semibold" id="referral_file_label">
                        <i class="bi bi-file-earmark-check-fill me-1"></i>Selected File
                    </span>
                    <div class="d-flex gap-1.5">
                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 fw-bold" id="btn_view_referral">View</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold" onclick="removeUploadedReferral()">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Residential Address --}}
        <div class="col-12 mt-4">
            <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Residential Address</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
                    <select id="addr_province" name="patient_province" class="form-select py-3 shadow-none" onchange="fetchCities(this.value); clearInlineError(this)" required>
                        <option value="">Select Province</option>
                    </select>
                    <div class="invalid-feedback" id="err_province"></div>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                    <select id="addr_city" name="patient_city" class="form-select py-3 shadow-none" onchange="fetchBarangays(this.value); clearInlineError(this)" disabled required>
                        <option value="">Select Province First</option>
                    </select>
                    <div class="invalid-feedback" id="err_city"></div>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
                    <select id="addr_brgy" name="patient_barangay" class="form-select py-3 shadow-none" onchange="updateCompiledAddress(); clearInlineError(this)" disabled required>
                        <option value="">Select City First</option>
                    </select>
                    <div class="invalid-feedback" id="err_barangay"></div>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                    <input type="text" id="addr_street" name="patient_street" class="form-control py-3 shadow-none uppercase" placeholder="House/Lot/Block/Street" oninput="updateCompiledAddress(); clearInlineError(this)" required>
                    <div class="invalid-feedback" id="err_street"></div>
                </div>
            </div>

            {{-- Complete Address Compiled Live Preview --}}
            <div class="col-12 mt-3">
                <div id="compiled_address_container" class="alert alert-clinical p-2.5 d-none text-start" style="background-color: rgba(25, 211, 140, 0.03);">
                    <small class="text-accent fw-bold uppercase d-block mb-1" style="font-size: 0.65rem;">Compiled Residential Address Preview</small>
                    <div id="compiled_address_text" class="text-main small"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="d-flex gap-2 mt-5">
        <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(1)">
            <i class="bi bi-arrow-left me-2"></i> BACK
        </button>
        <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" onclick="validateStep2()">
            NEXT: SELECT TESTS <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</div>

<datalist id="suffix_options">
    <option value="JR">
    <option value="SR">
    <option value="II">
    <option value="III">
    <option value="IV">
    <option value="V">
</datalist>

<style>
.is-invalid {
    border-color: #ff4d4d !important;
    background-image: none !important;
}
</style>

<script>
/**
 * Local Draft State Recovery for Step 2 Referral File
 */
document.addEventListener('DOMContentLoaded', () => {
    const referralInput = document.getElementById('in_referral');
    const previewContainer = document.getElementById('referral_preview_container');
    const inputWrapper = document.getElementById('referral_input_wrapper');
    const fileLabel = document.getElementById('referral_file_label');
    const viewBtn = document.getElementById('btn_view_referral');

    // Restore file state on load
    const savedReferralBase64 = localStorage.getItem('referral_base64');
    const savedReferralName = localStorage.getItem('referral_name');

    if (savedReferralBase64 && savedReferralName) {
        window.referralLocalData = savedReferralBase64;
        if (previewContainer) previewContainer.classList.remove('d-none');
        if (inputWrapper) inputWrapper.classList.add('d-none');
        if (fileLabel) {
            fileLabel.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${savedReferralName}`;
        }
        if (viewBtn) {
            viewBtn.onclick = function() {
                window.viewReferralFile(window.referralLocalData);
            };
        }
    }

    if (referralInput) {
        referralInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    window.referralLocalData = e.target.result;
                    localStorage.setItem('referral_base64', e.target.result);
                    localStorage.setItem('referral_name', file.name);

                    if (previewContainer) previewContainer.classList.remove('d-none');
                    if (inputWrapper) inputWrapper.classList.add('d-none');
                    if (fileLabel) {
                        fileLabel.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${file.name}`;
                    }
                    if (viewBtn) {
                        window.viewReferralFile = window.viewReferralFile || function() {};
                        viewBtn.onclick = function() {
                            window.viewReferralFile(window.referralLocalData);
                        };
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

/**
 * When referral is removed
 */
window.removeUploadedReferral = function() {
    const input = document.getElementById('in_referral');
    const inputWrapper = document.getElementById('referral_input_wrapper');
    const previewContainer = document.getElementById('referral_preview_container');
    
    if (input) input.value = '';
    if (inputWrapper) inputWrapper.classList.remove('d-none');
    if (previewContainer) previewContainer.classList.add('d-none');

    window.referralLocalData = null;
    localStorage.removeItem('referral_base64');
    localStorage.removeItem('referral_name');
};
</script>