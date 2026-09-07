<div id="pane-manual">
    <div class="card border-secondary p-0 shadow-lg overflow-hidden bg-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%; min-width: 1050px;">
                <thead class="bg-black text-secondary uppercase small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4" style="width: 38%;">Patient Information & Address</th>
                        <th style="width: 22%;">Tests</th>
                        <th style="width: 22%;">Schedule Slot</th>
                        <th class="pe-4 text-center" style="width: 18%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="rowContainer">
                    {{-- Row entries added dynamically via JavaScript addRow() --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- Spreadsheet Actions --}}
    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <button type="button" class="btn-custom btn-accent px-4 py-2.5 fw-bold uppercase shadow-sm" onclick="openCreatePatientModal()">
                <i class="bi bi-person-plus-fill me-1.5"></i> ADD PATIENT
            </button>
            <button type="button" class="btn-custom btn-outline-danger px-3 py-2.5 fw-bold uppercase shadow-sm" data-bs-toggle="modal" data-bs-target="#clearAllBulkModal" title="Clear all patient entries">
                <i class="bi bi-trash3 me-1"></i> CLEAR ALL
            </button>
        </div>
        <button type="button" id="smartSchedBtn" class="btn-custom btn-outline-accent px-4 py-2.5 fw-bold uppercase shadow-sm" onclick="runSmartScheduler()">
            <i class="bi bi-cpu me-1.5"></i> SMART AUTO-TIME
        </button>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 1. PATIENT INFORMATION & ADDRESS MODAL --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="bulkPatientModal" tabindex="-1" aria-labelledby="bulkPatientModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-secondary bg-card text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);">
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3" style="border-bottom: 1px solid var(--border-color);">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2" id="bulkPatientModalLabel">
                    <i class="bi bi-person-bounding-box fs-5"></i>
                    <span id="modal_patient_mode_title">Add Patient Profile</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start">
                <div id="modalPatientForm">
                    <input type="hidden" id="modal_row_idx" value="">

                    {{-- 1. PERSONAL IDENTITY --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">
                        <i class="bi bi-person-vcard me-2"></i>1. Personal Identity
                    </h6>
                    <div class="row g-3 mb-4">
                        {{-- First Name --}}
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
                            <input type="text" id="modal_first_name" class="form-control uppercase fw-bold" placeholder="First Name" required>
                            <div class="invalid-feedback d-none" id="err_modal_first_name"></div>
                        </div>

                        {{-- Middle Name --}}
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-center mb-1" style="height: 22px;">
                                <label class="small text-secondary fw-bold mb-0 uppercase">Middle Name</label>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="modal_no_mn" onclick="toggleModalMN(this)" style="margin-top: 0.15rem;">
                                    <label class="smaller text-muted" for="modal_no_mn" style="font-size: 0.65rem;">None</label>
                                </div>
                            </div>
                            <input type="text" id="modal_middle_name" class="form-control uppercase fw-bold" placeholder="Middle Name">
                            <div class="invalid-feedback d-none" id="err_modal_middle_name"></div>
                        </div>

                        {{-- Last Name --}}
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
                            <input type="text" id="modal_last_name" class="form-control uppercase fw-bold" placeholder="Last Name" required>
                            <div class="invalid-feedback d-none" id="err_modal_last_name"></div>
                        </div>

                        {{-- Suffix --}}
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
                            <input type="text" id="modal_suffix" list="bulk_suffix_options" class="form-control uppercase fw-bold" placeholder="e.g. JR" maxlength="10">
                            <datalist id="bulk_suffix_options">
                                <option value="JR">
                                <option value="SR">
                                <option value="II">
                                <option value="III">
                                <option value="IV">
                                <option value="V">
                            </datalist>
                            <div class="invalid-feedback d-none" id="err_modal_suffix"></div>
                        </div>
                    </div>

                    {{-- 2. DEMOGRAPHICS & CONTACT --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">
                        <i class="bi bi-telephone-plus me-2"></i>2. Demographics & Contact
                    </h6>
                    <div class="row g-3 mb-4">
                        {{-- Sex --}}
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
                            <select id="modal_sex" class="form-select fw-semibold" required>
                                <option value="Male">MALE</option>
                                <option value="Female">FEMALE</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_modal_sex"></div>
                        </div>

                        {{-- Birthdate (Strictly 18+ years old) --}}
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                            <input type="date" id="modal_birthdate" class="form-control fw-semibold" max="{{ now()->subYears(18)->format('Y-m-d') }}" required>
                            <div class="invalid-feedback d-none" id="err_modal_birthdate"></div>
                            <small class="text-muted smaller d-block mt-1" style="font-size: 0.65rem;">Must be at least 18 years of age.</small>
                        </div>

                        {{-- Phone --}}
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Contact Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                                <input type="text" id="modal_phone_display" class="form-control" placeholder="171234567" maxlength="9" required>
                            </div>
                            <input type="hidden" id="modal_phone">
                            <div class="invalid-feedback d-none" id="err_modal_phone"></div>
                        </div>

                        {{-- Email --}}
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Email Address</label>
                            <input type="email" id="modal_email" class="form-control" placeholder="name@domain.com" required>
                            <div class="invalid-feedback d-none" id="err_modal_email"></div>
                        </div>
                    </div>

                    {{-- 3. RESIDENTIAL ADDRESS (PSGC API) --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">
                        <i class="bi bi-geo-alt-fill me-2"></i>3. Residential Address
                    </h6>
                    <div class="row g-3">
                        {{-- Province --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
                            <select id="modal_province" class="form-select" onchange="fetchModalCities(this.value)" required>
                                <option value="">Select Province</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_modal_province"></div>
                        </div>

                        {{-- City / Municipality --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                            <select id="modal_city" class="form-select" onchange="fetchModalBarangays(this.value)" disabled required>
                                <option value="">Select Province First</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_modal_city"></div>
                        </div>

                        {{-- Barangay --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
                            <select id="modal_barangay" class="form-select" disabled required>
                                <option value="">Select City First</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_modal_barangay"></div>
                        </div>

                        {{-- Street / House No. --}}
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                            <input type="text" id="modal_street" class="form-control uppercase" placeholder="House/Lot/Block/Street" required>
                            <div class="invalid-feedback d-none" id="err_modal_street"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-secondary border-top border-opacity-10 bg-transparent p-3 d-flex gap-2">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase shadow-sm" onclick="savePatientModal()">
                    SAVE PATIENT DETAILS
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 2. CLEAR ALL CONFIRMATION MODAL --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="clearAllBulkModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-danger bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-trash3 text-danger display-4 d-block"></i>
            </div>
            <h5 class="text-main fw-bold mb-2 uppercase tracking-tight">Clear All Patients?</h5>
            <p class="text-secondary small mb-4">Are you sure you want to remove all compiled patient records from the spreadsheet? This will reset your table and active draft.</p>
            <div class="d-flex gap-2">
                <button type="button" class="btn-custom btn-outline-secondary w-50 py-2.5 fw-bold uppercase" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-custom btn-danger-custom w-50 py-2.5 fw-bold uppercase" onclick="confirmClearAllBulkRows()">Clear All</button>
            </div>
        </div>
    </div>
</div>