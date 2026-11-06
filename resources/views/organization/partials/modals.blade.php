<!-- resources/views/organization/partials/modals.blade.php -->

{{-- =========================================================================
     MODAL 1: PROPOSE CUSTOM PACKAGE (PATIENT/REP - TESTS & PURPOSE ONLY)
     ========================================================================= --}}
@if(!$isStaffView && $currentOrg && $currentOrg->isApproved())
<div class="modal fade" id="createPackageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" id="createPackageForm" action="{{ route('organization.package.store') }}" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            <input type="hidden" name="organization_id" id="prop_org_id" value="{{ $currentOrg->id }}">
            
            <div class="modal-header border-bottom py-3" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam fs-5"></i>
                    <span id="prop_modal_title">Propose Package for {{ $currentOrg->name }}</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <p class="small text-muted mb-4">
                    Select the examinations needed for your cohort. Clinic management will evaluate your proposal and assign your discounted institutional fee, duration, and clinical preparation requirements.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="small text-secondary fw-bold uppercase mb-1">Package Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control uppercase fw-bold" placeholder="e.g. ANNUAL FACULTY HEALTH SCREEN" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold uppercase mb-1">Gender Restriction <span class="text-danger">*</span></label>
                        <select name="gender_restriction" id="prop_gender_restriction" class="form-select fw-semibold" onchange="filterProposalServices()" required>
                            <option value="both" selected>BOTH GENDERS</option>
                            <option value="male">MALE ONLY</option>
                            <option value="female">FEMALE ONLY</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small text-secondary fw-bold uppercase mb-1">Clinical Screening Purpose</label>
                        <textarea name="description" class="form-control small" rows="2" placeholder="Brief outline of package scope or student/employee requirement..."></textarea>
                    </div>
                </div>

                {{-- Bundled Services Checkboxes with Search and Live Base Total Calculator --}}
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <label class="small text-accent fw-bold uppercase m-0">Select Constituent Examinations (At least 1 required):</label>
                    <span class="small fw-bold text-muted">Standard Value: <span class="text-accent" id="prop_base_total">₱0.00</span></span>
                </div>

                {{-- Search Function --}}
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-secondary bg-opacity-10 border text-secondary" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="propServiceSearch" class="form-control shadow-none" placeholder="Search examination by name..." oninput="filterProposalServices()">
                </div>

                <div class="row g-2 p-3 rounded border bg-main" id="propServicesContainer" style="max-height: 250px; overflow-y: auto; border-color: var(--border-color) !important;">
                    @foreach($availableServices as $svc)
                        <div class="col-md-6 prop-service-item" data-name="{{ strtoupper($svc->name) }}" data-gender="{{ strtolower($svc->gender_restriction) }}">
                            <div class="form-check p-2.5 rounded border bg-card h-100" style="border-color: var(--border-color) !important;">
                                <input class="form-check-input ms-0 me-2 prop-svc-chk" type="checkbox" name="service_ids[]" value="{{ $svc->id }}" data-price="{{ $svc->price }}" id="prop_svc_{{ $svc->id }}" onchange="recalculateProposalTotal()">
                                <label class="form-check-label text-main small cursor-pointer" for="prop_svc_{{ $svc->id }}">
                                    <span class="fw-bold d-block text-truncate">{{ strtoupper($svc->name) }}</span>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="text-accent smaller">₱{{ number_format($svc->price, 2) }}</span>
                                        <span class="badge bg-secondary text-white uppercase" style="font-size: 0.6rem;">
                                            {{ strtoupper($svc->gender_restriction) }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-3 rounded border bg-main mt-3 text-muted smaller" style="border-color: var(--border-color) !important;">
                    <i class="bi bi-info-circle text-accent me-1"></i>
                    <strong>Clinical Policy:</strong> Pricing, processing duration, and patient fasting/preparation rules are designated by medical laboratory staff during proposal evaluation.
                </div>
            </div>
            
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">Submit Proposal</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL 2: EDIT / RESUBMIT CUSTOM PACKAGE PROPOSAL
     ========================================================================= --}}
@if(!$isStaffView && $currentOrg)
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" id="editPackageForm" action="" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_pkg_id" value="">
            
            <div class="modal-header border-bottom py-3" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square fs-5"></i>
                    <span id="edit_pkg_modal_title">Edit Custom Package Proposal</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-dark p-3 rounded-3 mb-4 small" id="edit_pkg_warning_box">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                    <span id="edit_pkg_warning_text">
                        <strong>Re-approval Notice:</strong> Editing or resubmitting a package resets its status to <strong>Pending Clinic Review</strong>. Clinic staff will re-evaluate pricing, duration, and patient instructions before reactivation.
                    </span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="small text-secondary fw-bold uppercase mb-1">Package Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_pkg_name" class="form-control uppercase fw-bold" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold uppercase mb-1">Gender Restriction <span class="text-danger">*</span></label>
                        <select name="gender_restriction" id="edit_pkg_gender_restriction" class="form-select fw-semibold" onchange="filterEditProposalServices()" required>
                            <option value="both">BOTH GENDERS</option>
                            <option value="male">MALE ONLY</option>
                            <option value="female">FEMALE ONLY</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small text-secondary fw-bold uppercase mb-1">Clinical Screening Purpose</label>
                        <textarea name="description" id="edit_pkg_description" class="form-control small" rows="2" placeholder="Brief outline of package scope..."></textarea>
                    </div>
                </div>

                {{-- Bundled Services Checkboxes with Search and Live Base Total Calculator --}}
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <label class="small text-accent fw-bold uppercase m-0">Select Constituent Examinations (At least 1 required):</label>
                    <span class="small fw-bold text-muted">Standard Value: <span class="text-accent" id="edit_prop_base_total">₱0.00</span></span>
                </div>

                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-secondary bg-opacity-10 border text-secondary" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="editPkgServiceSearch" class="form-control shadow-none" placeholder="Search examination by name..." oninput="filterEditProposalServices()">
                </div>

                <div class="row g-2 p-3 rounded border bg-main" id="editPropServicesContainer" style="max-height: 250px; overflow-y: auto; border-color: var(--border-color) !important;">
                    @foreach($availableServices as $svc)
                        <div class="col-md-6 edit-prop-service-item" data-name="{{ strtoupper($svc->name) }}" data-gender="{{ strtolower($svc->gender_restriction) }}">
                            <div class="form-check p-2.5 rounded border bg-card h-100" style="border-color: var(--border-color) !important;">
                                <input class="form-check-input ms-0 me-2 edit-prop-svc-chk" type="checkbox" name="service_ids[]" value="{{ $svc->id }}" data-price="{{ $svc->price }}" id="edit_prop_svc_{{ $svc->id }}" onchange="recalculateEditProposalTotal()">
                                <label class="form-check-label text-main small cursor-pointer" for="edit_prop_svc_{{ $svc->id }}">
                                    <span class="fw-bold d-block text-truncate">{{ strtoupper($svc->name) }}</span>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="text-accent smaller">₱{{ number_format($svc->price, 2) }}</span>
                                        <span class="badge bg-secondary text-white uppercase" style="font-size: 0.6rem;">
                                            {{ strtoupper($svc->gender_restriction) }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase" id="edit_pkg_submit_btn">Save & Submit for Re-approval</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================================
     MODAL: DELETE CUSTOM PACKAGE CONFIRMATION
     ========================================================================= --}}
<div class="modal fade" id="deletePackageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form id="deletePackageForm" method="POST" action="" class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            @method('DELETE')
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-trash3 fs-5"></i>
                    <span>Delete Custom Package</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-2">
                    Are you sure you want to remove package <strong class="text-main" id="del_pkg_name"></strong>?
                </p>
                <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-0 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    This package proposal will be permanently removed from your active custom packages catalog.
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL 3: EDIT ORGANIZATION NAME (SUBMITTED FOR STAFF APPROVAL)
     ========================================================================= --}}
@if(!$isStaffView && $currentOrg && $currentOrg->isApproved())
<div class="modal fade" id="editOrgNameModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <form method="POST" id="editOrgNameForm" action="" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square fs-5"></i>
                    <span>Edit Organization Name</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Submit a revised entity name for clinic verification. Your current organization name remains active in clinical records until the change is approved by staff.
                </p>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold uppercase mb-1 d-block">New Organization Name <span class="text-danger">*</span></label>
                    <input type="text" name="new_organization_name" id="new_org_name_input" class="form-control uppercase fw-bold" placeholder="e.g. Dole Philippines Inc." required maxlength="150">
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">Submit for Review</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL 4: DEACTIVATE ORGANIZATION (VOLUNTARY BY REPRESENTATIVE)
     ========================================================================= --}}
@if(!$isStaffView && $currentOrg && $currentOrg->isApproved())
<div class="modal fade" id="deactivateOrgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form id="deactivateOrgForm" method="POST" action="" class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-slash-circle fs-5"></i>
                    <span>Deactivate Organization Partnership</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-3 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Important:</strong> Deactivating this organization will immediately disable all custom diagnostic packages and lock member rosters. Once deactivated, only clinic staff can re-authorize activation upon your request.
                </div>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold uppercase mb-1 d-block">Reason for Deactivation (Optional)</label>
                    <textarea name="reason" class="form-control small" rows="2" placeholder="e.g. Project or cohort screening completed..."></textarea>
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase">Confirm Deactivation</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL: REQUEST ORGANIZATION REACTIVATION
     ========================================================================= --}}
@if(!$isStaffView && $currentOrg && $currentOrg->isDeactivated())
<div class="modal fade" id="reactivateOrgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <form method="POST" action="{{ route('organization.reactivate', $currentOrg->id) }}" class="modal-content border-secondary bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-counterclockwise fs-5"></i>
                    <span>Request Reactivation</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Submit a reactivation request for <strong>{{ $currentOrg->name }}</strong>. Clinic staff will evaluate the request and restore your partner privileges, custom packages, and participant rosters.
                </p>
                <div class="alert alert-clinical border-info bg-info bg-opacity-10 text-main p-3 rounded-3 mb-0 small">
                    <i class="bi bi-info-circle text-info me-1"></i>
                    Once approved, your custom packages and rosters will automatically re-enable for bulk bookings.
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-3.5 fw-bold uppercase shadow-sm">Confirm Request</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL: DELETE DECLINED ORGANIZATION APPLICATION
     ========================================================================= --}}
@if(!$isStaffView && $currentOrg && $currentOrg->status === 'rejected')
<div class="modal fade" id="deleteRejectedOrgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="POST" action="{{ route('organization.destroy', $currentOrg->id) }}" class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            @method('DELETE')
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-trash3 fs-5"></i>
                    <span>Delete Application</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Are you sure you want to remove the declined application for <strong>{{ $currentOrg->name }}</strong>?
                </p>
                <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-0 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    This will permanently clear this record from your organization list. You can apply again anytime.
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase">Delete Application</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL 5: ADD / EDIT ROSTER MEMBER (ADDRESS STRICTLY REQUIRED)
     ========================================================================= --}}
@if(!$isStaffView && $currentOrg)
<div class="modal fade" id="rosterMemberModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="rosterMemberForm" onsubmit="handleRosterMemberSubmit(event)" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            <input type="hidden" id="rm_id" value="">
            <input type="hidden" id="rm_org_id" value="{{ $currentOrg->id ?? '' }}">
            <input type="hidden" id="rm_group_name" value="">

            <div class="modal-header border-bottom py-3" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-person-plus-fill fs-5"></i>
                    <span id="rm_modal_title">Add Roster Member</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                {{-- IN-MODAL DYNAMIC VALIDATION & DUPLICATE ALERT BANNER --}}
                <div class="alert alert-danger d-none mb-3 py-2.5 px-3 small border-0 shadow-sm" id="rm_modal_error_alert" style="border-radius: 8px;"></div>

                <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    1. Member Identity
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="rm_first_name" class="form-control uppercase fw-bold" placeholder="First Name" required>
                        <div class="invalid-feedback d-none" id="err_rm_first_name"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Middle Name</label>
                        <input type="text" id="rm_middle_name" class="form-control uppercase fw-bold" placeholder="N/A">
                        <div class="invalid-feedback d-none" id="err_rm_middle_name"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="rm_last_name" class="form-control uppercase fw-bold" placeholder="Last Name" required>
                        <div class="invalid-feedback d-none" id="err_rm_last_name"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Suffix</label>
                        <input type="text" id="rm_suffix" class="form-control uppercase fw-bold" placeholder="e.g. JR">
                        <div class="invalid-feedback d-none" id="err_rm_suffix"></div>
                    </div>
                </div>

                <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    2. Demographics & Contact
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Sex <span class="text-danger">*</span></label>
                        <select id="rm_sex" class="form-select fw-semibold" required>
                            <option value="Male">MALE</option>
                            <option value="Female">FEMALE</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_rm_sex"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">
                            Birthdate (18+) <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="rm_birthdate" class="form-control fw-semibold" max="{{ date('Y-m-d', strtotime('-18 years')) }}" required>
                        <div class="invalid-feedback d-none" id="err_rm_birthdate"></div>
                    </div>
                    {{-- Mandatory Contact Number (starts with 09) --}}
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Contact Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                            <input type="text" id="rm_phone_display" class="form-control shadow-none" placeholder="171234567" maxlength="9" required>
                        </div>
                        <input type="hidden" id="rm_phone">
                        <div class="invalid-feedback d-none" id="err_rm_phone"></div>
                    </div>
                    {{-- Mandatory Email Address --}}
                    <div class="col-md-3">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="rm_email" class="form-control" placeholder="name@domain.com" required>
                        <div class="invalid-feedback d-none" id="err_rm_email"></div>
                    </div>
                </div>

                <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    3. Residential Address (PSGC) - All Fields Mandatory
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Province <span class="text-danger">*</span></label>
                        <select id="rm_province" class="form-select" onchange="fetchRosterCities(this.value)" required>
                            <option value="">Select Province</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_rm_province"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality <span class="text-danger">*</span></label>
                        <select id="rm_city" class="form-select" onchange="fetchRosterBarangays(this.value)" disabled required>
                            <option value="">Select Province First</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_rm_city"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Barangay <span class="text-danger">*</span></label>
                        <select id="rm_barangay" class="form-select" disabled required>
                            <option value="">Select City First</option>
                        </select>
                        <div class="invalid-feedback d-none" id="err_rm_barangay"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No. <span class="text-danger">*</span></label>
                        <input type="text" id="rm_street" class="form-control uppercase" placeholder="Street / House / Building" required>
                        <div class="invalid-feedback d-none" id="err_rm_street"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="rm_submit_btn" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase shadow-sm">Save Member</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: DELETE ROSTER MEMBER CONFIRMATION --}}
<div class="modal fade" id="deleteRosterMemberModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-trash3 fs-5"></i>
                    <span>Remove Roster Member</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-0">
                    Are you sure you want to remove <strong class="text-main" id="del_rm_name">this member</strong> from the cohort roster?
                </p>
                <input type="hidden" id="del_rm_id" value="">
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase" id="confirm_del_rm_btn" onclick="executeDeleteRosterMember()">Confirm Delete</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 6: CREATE NEW ROSTER GROUP CATEGORY --}}
<div class="modal fade" id="newGroupModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-folder-plus fs-5"></i>
                    <span>Create New Roster Category</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <label class="small text-secondary fw-bold uppercase mb-1 d-block">Group Name <span class="text-danger">*</span></label>
                <input type="text" id="quick_new_group_name" class="form-control uppercase fw-bold" placeholder="e.g. OJT BATCH 2026" required>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-custom btn-accent py-2 px-3 fw-bold uppercase" onclick="startNewRosterGroup()">Continue</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 7: EDIT ROSTER GROUP CATEGORY NAME --}}
<div class="modal fade" id="editGroupModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="POST" action="{{ route('organization.roster.group.update') }}" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            @method('PUT')
            <input type="hidden" name="old_group_name" id="edit_old_group_name" value="">
            
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square fs-5"></i>
                    <span>Edit Roster Group Name</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <label class="small text-secondary fw-bold uppercase mb-1 d-block">Roster Group Name <span class="text-danger">*</span></label>
                <input type="text" name="new_group_name" id="edit_new_group_name" class="form-control uppercase fw-bold" placeholder="e.g. OJT BATCH 2026" required>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-3.5 fw-bold uppercase">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: DELETE ROSTER GROUP CONFIRMATION --}}
<div class="modal fade" id="deleteRosterGroupModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <form method="POST" action="{{ route('organization.roster.group.destroy') }}" class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="group_name" id="del_group_name_input" value="">
            
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-trash3 fs-5"></i>
                    <span>Delete Roster Group</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-2">
                    Are you sure you want to delete the entire roster group <strong class="text-main" id="del_group_name_display"></strong>?
                </p>
                <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-0 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    This will permanently delete this cohort and all <span id="del_group_member_count" class="fw-bold">0</span> registered participant records within it.
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase">Delete Entire Group</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL 8: APPLY FOR RECOGNIZED ORGANIZATION (DIRECT ENTITY NAME ENTRY)
     ========================================================================= --}}
@if(!$isStaffView)
<div class="modal fade" id="applyOrgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <form action="{{ route('organization.apply') }}" method="POST" class="modal-content border-secondary bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-building-add fs-5"></i>
                    <span>Recognized Organization Application</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Apply as an authorized entity (school, hospital, corporate). Approved partners unlock custom package proposals, reusable rosters, and streamlined bulk bookings.
                </p>

                <div class="mb-3">
                    <label class="small text-secondary fw-bold uppercase mb-1 d-block">Entity Name <span class="text-danger">*</span></label>
                    <input type="text" name="organization_name" class="form-control uppercase fw-bold" placeholder="e.g. Notre Dame University, Dole Philippines" required maxlength="150">
                </div>

                <div class="p-3 rounded border bg-main text-muted smaller" style="border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-person-badge text-accent fs-6"></i>
                        <strong class="text-main small uppercase">Authorized Representative:</strong>
                    </div>
                    <div>Name: <span class="text-main fw-semibold">{{ auth()->user()->name }}</span></div>
                    <div>Phone: <span class="text-main font-monospace">{{ auth()->user()->phone }}</span></div>
                    <div>Email: <span class="text-main">{{ auth()->user()->email }}</span></div>
                    <small class="text-muted d-block mt-1">This account will serve as the official liaison and coordinator for the organization.</small>
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">Submit Application</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- =========================================================================
     MODAL 9: STAFF APPROVE PACKAGE (PRICE, DISCOUNT, DURATION & PREPARATION)
     ========================================================================= --}}
@if($isStaffView)
<div class="modal fade" id="approvePackageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <form id="approvePackageForm" method="POST" action="" class="modal-content border-success bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0">
                    <i class="bi bi-patch-check-fill me-1.5"></i> Set Price & Approve Package
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <span class="text-muted smaller uppercase fw-bold d-block">Package Proposal</span>
                    <h6 class="fw-bold text-main uppercase mb-0" id="app_pkg_title">PACKAGE NAME</h6>
                    <small class="text-secondary" id="app_pkg_org">Requesting Organization</small>
                </div>

                <div class="p-3 rounded border bg-main mb-3" style="border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">Baseline Standard Value:</span>
                        <strong class="text-main fs-6" id="app_pkg_base_price">₱0.00</strong>
                    </div>
                    <small class="text-secondary smaller">Apply an institutional discount percentage or enter the negotiated flat fee.</small>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="small text-secondary fw-bold uppercase mb-1">Discount (%)</label>
                        <div class="input-group">
                            <input type="number" id="app_discount_pct" class="form-control fw-bold" placeholder="0" min="0" max="100" oninput="calculateDiscountByPercent(this.value)">
                            <span class="input-group-text bg-main border text-secondary">%</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="small text-secondary fw-bold uppercase mb-1">Final Fee (PHP) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price" id="app_final_price" class="form-control fw-bold text-accent" placeholder="0.00" required oninput="calculateDiscountByPrice(this.value)">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="small text-secondary fw-bold uppercase mb-1">Estimated Examination Duration (Mins) <span class="text-danger">*</span></label>
                        <input type="number" name="estimated_time" id="app_estimated_time" class="form-control fw-bold" min="1" placeholder="15" required>
                    </div>
                    <div class="col-12">
                        <label class="small text-secondary fw-bold uppercase mb-1">Patient Preparation Guidelines</label>
                        <textarea name="preparation" id="app_preparation" class="form-control small" rows="2" placeholder="e.g. 10 hours fasting required for blood tests. Drink plenty of water."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">Activate Package</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================================
     MODAL 10: STAFF DISABLE RECOGNIZED ORG
     ========================================================================= --}}
<div class="modal fade" id="disableOrgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <form id="disableOrgForm" method="POST" action="" class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0">
                    <i class="bi bi-slash-circle me-1.5"></i> Disable Organization Partner
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3" id="disableOrgPrompt">
                    Disabling this organization will deactivate representative-led delivery and remove custom packages from their bulk portal.
                </p>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold uppercase mb-1">Justification Reason <span class="text-danger">*</span></label>
                    <select name="reason" id="disable_org_reason_select" class="form-select small fw-semibold" onchange="toggleReasonOthers(this, 'disable_org_custom_reason')" required>
                        <option value="">-- Select Justification --</option>
                        <option value="Expired Memorandum of Agreement (MOA)">Expired Memorandum of Agreement (MOA)</option>
                        <option value="Outstanding / Unsettled Financial Obligations">Outstanding / Unsettled Financial Obligations</option>
                        <option value="Temporary Suspension Requested by Organization">Temporary Suspension Requested by Organization</option>
                        <option value="Breach of Clinical or Booking Guidelines">Breach of Clinical or Booking Guidelines</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                <div id="disable_org_custom_reason" class="d-none mb-2">
                    <label class="small text-secondary fw-bold uppercase mb-1">Specify Administrative Reason <span class="text-danger">*</span></label>
                    <textarea name="custom_reason" class="form-control small" rows="2" placeholder="Enter detailed administrative reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase">Confirm Disable</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================================
     MODAL 11: STAFF DISABLE CUSTOM PACKAGE
     ========================================================================= --}}
<div class="modal fade" id="disablePkgModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <form id="disablePkgForm" method="POST" action="" class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0">
                    <i class="bi bi-box-seam me-1.5"></i> Disable Custom Package
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3" id="disablePkgPrompt">
                    Disabling this package will remove it from the organization's active bulk booking selection.
                </p>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold uppercase mb-1">Justification Reason <span class="text-danger">*</span></label>
                    <select name="reason" id="disable_pkg_reason_select" class="form-select small fw-semibold" onchange="toggleReasonOthers(this, 'disable_pkg_custom_reason')" required>
                        <option value="">-- Select Justification --</option>
                        <option value="Reagent Shortage / Test Discontinuation">Reagent Shortage / Test Discontinuation</option>
                        <option value="Pricing Renegotiation Required">Pricing Renegotiation Required</option>
                        <option value="Superseded by Updated Package Scheme">Superseded by Updated Package Scheme</option>
                        <option value="Contract Period Concluded">Contract Period Concluded</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                <div id="disable_pkg_custom_reason" class="d-none mb-2">
                    <label class="small text-secondary fw-bold uppercase mb-1">Specify Administrative Reason <span class="text-danger">*</span></label>
                    <textarea name="custom_reason" class="form-control small" rows="2" placeholder="Enter reason for package deactivation..."></textarea>
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase">Confirm Deactivation</button>
            </div>
        </form>
    </div>
</div>

{{-- =========================================================================
     MODAL 12: STAFF DECLINE APPLICATION / PROPOSAL (DROPDOWN WITH OTHERS)
     ========================================================================= --}}
<div class="modal fade" id="staffDeclineModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <form id="staffDeclineForm" method="POST" action="" class="modal-content border-danger bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
            @csrf
            <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2" id="staffDeclineTitle">
                    <i class="bi bi-x-circle fs-5"></i>
                    <span>Decline Request</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3" id="staffDeclinePrompt">Specify why this submission is being declined.</p>
                
                {{-- Dropdown Justification Reason --}}
                <div class="mb-3">
                    <label class="small text-secondary fw-bold uppercase mb-1 d-block">Justification Reason <span class="text-danger">*</span></label>
                    <select name="reason" id="staff_decline_reason_select" class="form-select small fw-semibold" onchange="toggleReasonOthers(this, 'staff_decline_custom_reason')" required>
                        <option value="">-- Select Justification --</option>
                        <option value="Unverifiable Entity Credentials / Incomplete Information">Unverifiable Entity Credentials / Incomplete Information</option>
                        <option value="Duplicate or Conflicting Organization Record">Duplicate or Conflicting Organization Record</option>
                        <option value="Unauthorized Representative / Missing Official Endorsement">Unauthorized Representative / Missing Official Endorsement</option>
                        <option value="Non-Compliance with Clinic Accreditation / Partnership Terms">Non-Compliance with Clinic Accreditation / Partnership Terms</option>
                        <option value="Capacity Constraint / Unable to Support Requested Cohort Volume">Capacity Constraint / Unable to Support Requested Cohort Volume</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>

                {{-- Conditional "Others" Detailed Reason --}}
                <div id="staff_decline_custom_reason" class="d-none mb-2">
                    <label class="small text-secondary fw-bold uppercase mb-1 d-block">Specify Detailed Reason <span class="text-danger">*</span></label>
                    <textarea name="custom_reason" class="form-control small" rows="2" placeholder="Enter detailed justification reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-custom btn-outline-danger py-2 px-3.5 fw-bold uppercase">Confirm Decline</button>
            </div>
        </form>
    </div>
</div>
@endif