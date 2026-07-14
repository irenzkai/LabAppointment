<!-- PAGE 2: PATIENT DETAILS -->
<div class="wiz-section d-none text-start animate-page" id="page-2">
    <div class="mb-4">
        <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 2: Patient Information</h3>
        <p class="text-secondary small">Please verify or enter the details for this medical record.</p>
    </div>

    <div class="row g-3">
        {{-- Basic Identity --}}
        <div class="col-md-4">
            <label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
            <input type="text" name="patient_first_name" id="in_first_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="First Name" oninput="updateSummary()" required>
        </div>
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="small text-secondary fw-bold mb-0 uppercase">Middle Name</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="profile_no_mn" onclick="toggleProfileMN(this)">
                    <label class="smaller text-muted" style="font-size: 0.65rem;" for="profile_no_mn">None</label>
                </div>
            </div>
            <input type="text" name="patient_middle_name" id="in_middle_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Middle Name" oninput="updateSummary()">
        </div>
        <div class="col-md-4">
            <label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
            <input type="text" name="patient_last_name" id="in_last_name" class="form-control py-3 shadow-none uppercase fw-bold" placeholder="Last Name" oninput="updateSummary()" required>
        </div>

        {{-- Sex Selector --}}
        <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
            <select name="patient_sex" id="in_sex" class="form-select py-3 shadow-none" required>
                <option value="">Select Sex</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>

        {{-- Birthdate Input --}}
        <div class="col-md-6">
            <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
            <input type="date" name="patient_birthdate" id="in_bday" class="form-control py-3 shadow-none" required max="{{ date('Y-m-d') }}">
        </div>

        {{-- Contact --}}
        <div class="col-12">
            <label class="small text-secondary fw-bold mb-1 uppercase">Contact Number</label>
            <input type="text" name="patient_phone" id="in_phone" class="form-control py-3 shadow-none" placeholder="09xxxxxxxxx" required>
            <div class="mt-1">
                <small class="text-muted smaller">
                    <i class="bi bi-info-circle me-1"></i> For dependents, the guardian's contact number is used for notifications.
                </small>
            </div>
        </div>

        {{-- Doctor's Referral / Note (Optional - File Input) --}}
        <div class="col-12 mt-2">
            <label class="small text-secondary fw-bold mb-1 uppercase">Doctor's Referral / Note (Optional)</label>
            <input type="file" name="referral_note" id="in_referral" class="form-control py-3 shadow-none" accept="image/*, application/pdf">
            <div class="mt-1">
                <small class="text-muted smaller">
                    <i class="bi bi-file-earmark-plus me-1"></i> Upload a PDF or image of your doctor's written referral or laboratory request note.
                </small>
            </div>
        </div>

        {{-- Residential Address via PSGC API --}}
        <div class="col-12 mt-4">
            <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">Residential Address</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
                    <select id="addr_province" name="patient_province" class="form-select py-3 shadow-none" onchange="fetchCities(this.value)" required>
                        <option value="">Select Province</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                    <select id="addr_city" name="patient_city" class="form-select py-3 shadow-none" onchange="fetchBarangays(this.value)" disabled required>
                        <option value="">Select Province First</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
                    <select id="addr_brgy" name="patient_barangay" class="form-select py-3 shadow-none" onchange="updateCompiledAddress()" disabled required>
                        <option value="">Select City First</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                    {{-- FIXED: Changed name="street" to name="patient_street" to align with database columns and controllers --}}
                    <input type="text" id="addr_street" name="patient_street" class="form-control py-3 shadow-none uppercase" placeholder="House/Lot/Block/Street" oninput="updateCompiledAddress()" required>
                </div>
            </div>
        </div>

        {{-- Complete Address Compiled Live Preview --}}
        <div class="col-12 mt-2">
            <div id="compiled_address_container" class="alert alert-clinical p-2.5 d-none text-start" style="background-color: rgba(25, 211, 140, 0.03);">
                <small class="text-accent fw-bold uppercase d-block mb-1" style="font-size: 0.65rem;">Compiled Residential Address Preview</small>
                <div id="compiled_address_text" class="text-main small"></div>
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

<script>
function toggleProfileMN(checkbox) {
    const input = document.getElementById('in_middle_name');
    if (checkbox.checked) {
        input.value = "N/A";
        input.readOnly = true;
        input.classList.add('opacity-50');
    } else {
        input.value = "";
        input.readOnly = false;
        input.classList.remove('opacity-50');
    }
    updateSummary();
}
</script>

<style>
.is-invalid {
    border-color: #ff4d4d !important;
    background-image: none !important;
}
</style>