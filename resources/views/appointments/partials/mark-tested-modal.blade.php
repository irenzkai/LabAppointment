@php 
// Detect if the appointment is already tested/validated to lock out redundant "Progress to Tested" options
$isAlreadyTested = in_array($app->status, ['tested', 'encoded', 'released']);  

// Calculate confirmed price: Use positive stored payment_amount or fallback to calculated service sum
$confirmedPrice = ($app->payment_amount && floatval($app->payment_amount) > 0) 
    ? floatval($app->payment_amount) 
    : floatval($app->totalPrice());

$patientBirthdateStr = $app->patient_birthdate 
    ? \Carbon\Carbon::parse($app->patient_birthdate)->format('Y-m-d') 
    : '';
@endphp

<!-- MAIN SAMPLING & RETEST VERIFICATION MODAL -->
<div class="modal fade test-modal-container" id="testModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1065;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="markTestedForm_{{ $app->id }}" 
              action="{{ route('appointments.tested', $app->id) }}" 
              method="POST" 
              onsubmit="return validateAndSubmitModalForm({{ $app->id }}, event)" 
              class="modal-content border-secondary bg-card text-start shadow-lg" 
              style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 14px;" 
              novalidate>
            @csrf
            @method('PATCH')

            <!-- Hidden action trigger: progresses to 'tested' or 'retest' -->
            <input type="hidden" name="action" id="action_{{ $app->id }}" value="{{ $isAlreadyTested ? 'retest' : 'tested' }}"> 

            <!-- Hidden snapshot inputs to satisfy controller validation while keeping modal UI read-only -->
            <input type="hidden" name="patient_first_name" value="{{ $app->patient_first_name }}">
            <input type="hidden" name="patient_middle_name" value="{{ $app->patient_middle_name }}">
            <input type="hidden" name="patient_last_name" value="{{ $app->patient_last_name }}">
            <input type="hidden" name="patient_suffix" value="{{ $app->patient_suffix }}">
            <input type="hidden" name="patient_birthdate" value="{{ $patientBirthdateStr }}">
            <input type="hidden" name="patient_sex" value="{{ $app->patient_sex }}">
            <input type="hidden" name="patient_phone" value="{{ $app->patient_phone }}">
            <input type="hidden" name="patient_street" value="{{ $app->patient_street }}">
            <input type="hidden" name="patient_barangay" value="{{ $app->patient_barangay }}">
            <input type="hidden" name="patient_city" value="{{ $app->patient_city }}">
            <input type="hidden" name="patient_province" value="{{ $app->patient_province }}">
            <input type="hidden" name="payment_amount" id="val_total_{{ $app->id }}" value="{{ $confirmedPrice }}">
            @foreach($app->services as $s)
                <input type="hidden" name="service_ids[]" value="{{ $s->id }}">
            @endforeach

            <div class="modal-header border-bottom py-3 d-flex flex-column align-items-stretch" style="border-color: var(--border-color) !important; background-color: var(--bg-card);">
                <div class="d-flex justify-content-between align-items-center {{ !$isAlreadyTested ? 'mb-3' : '' }}">
                    <h5 class="modal-title {{ $isAlreadyTested ? 'text-warning' : 'text-accent' }} fw-bold uppercase small m-0" id="modal_title_{{ $app->id }}">
                        <i class="bi {{ $isAlreadyTested ? 'bi-arrow-repeat text-warning' : 'bi-shield-check text-accent' }} me-2 fs-5"></i>
                        {{ $isAlreadyTested ? 'Clinical Retest Request Hub' : 'Clinical Sampling & Verification Hub' }}
                    </h5>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- MAIN WORKFLOW TABS (Displayed only if not yet tested) -->
                @if(!$isAlreadyTested)
                    <ul class="nav nav-pills nav-fill gap-2" id="mainTab_{{ $app->id }}" role="list">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-uppercase py-2 rounded-3" id="tested-tab-btn-{{ $app->id }}" type="button" onclick="setModalMainAction({{ $app->id }}, 'tested')" style="font-size: 0.8rem;">
                                <i class="bi bi-person-check-fill me-1"></i> 1. Progress to Tested
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-uppercase py-2 rounded-3" id="retest-tab-btn-{{ $app->id }}" type="button" onclick="setModalMainAction({{ $app->id }}, 'retest')" style="font-size: 0.8rem;">
                                <i class="bi bi-arrow-repeat me-1"></i> 2. Return for Retest
                            </button>
                        </li>
                    </ul>
                @endif
            </div>

            <div class="modal-body p-4 custom-scroll">
                <!-- READ-ONLY "CONFIRM DETAILS" SUMMARY PANEL WITH SERVICE PRICE -->
                <div class="card p-3 border mb-4 text-start" style="background-color: rgba(25, 211, 140, 0.03); border-color: var(--border-color) !important; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <h6 class="text-accent fw-bold mb-0 uppercase small" style="color: var(--brand-accent) !important;">
                            <i class="bi bi-person-check-fill me-1.5"></i>Confirm Details
                        </h6>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 uppercase" style="font-size: 0.65rem;">
                            Ref: #{{ $app->id }}
                        </span>
                    </div>

                    <div class="row g-2 text-start">
                        <div class="col-md-6">
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Patient Name</small>
                            <div class="text-main fw-bold small mb-2" style="color: var(--text-main) !important;">{{ strtoupper($app->patient_name) }}</div>

                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Age / Sex / Birthdate</small>
                            <div class="text-main small mb-2" style="color: var(--text-main) !important;">
                                {{ $app->patient_age }} Yrs <span class="mx-1">|</span> {{ strtoupper($app->patient_sex) }} <span class="mx-1">|</span> {{ $app->patient_birthdate ? \Carbon\Carbon::parse($app->patient_birthdate)->format('M d, Y') : 'N/A' }}
                            </div>

                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Contact Phone</small>
                            <div class="text-main small mb-2" style="color: var(--text-main) !important;">{{ $app->patient_phone }}</div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Residential Address</small>
                            <div class="text-main small mb-2" style="color: var(--text-main) !important;">{{ $app->patient_address }}</div>

                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Requested Examinations</small>
                            <div class="d-flex flex-wrap gap-1.5 mb-2">
                                @foreach($app->services as $s)
                                    <span class="badge border border-secondary border-opacity-25 text-accent uppercase px-2 py-1" style="background-color: rgba(25, 211, 140, 0.05); font-size: 0.7rem; color: var(--brand-accent) !important;">
                                        {{ $s->name }}
                                    </span>
                                @endforeach
                            </div>

                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Confirmed Price / Total Bill</small>
                            <div class="text-accent fw-bold fs-6 mb-2" style="color: var(--brand-accent) !important;">
                                ₱{{ number_format($confirmedPrice, 2) }}
                            </div>
                        </div>
                    </div>

                    {{-- Redirection Button to Edit Patient Details on a Dedicated Page --}}
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-color: var(--border-color) !important;">
                        <small class="text-muted italic" style="font-size: 0.75rem;">Need to revise patient demographics, address, or tests?</small>
                        <a href="{{ route('appointments.edit-details', $app->id) }}" class="btn btn-sm btn-outline-accent fw-bold uppercase py-1 px-3" style="font-size: 0.75rem;">
                            <i class="bi bi-pencil-square me-1"></i> Edit Details (Identity / Address / Tests)
                        </a>
                    </div>
                </div>

                <!-- PROCESSING PARAMETERS INPUT SECTION -->

                <!-- PANE 1: PROGRESS TO TESTED (TIME ESTIMATION ONLY) - Hidden if already tested -->
                <div id="tested_process_fields_{{ $app->id }}" class="{{ $isAlreadyTested ? 'd-none' : '' }}">
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom pb-2" style="border-color: var(--border-color) !important; color: var(--brand-accent) !important;">
                        Processing Time Estimation
                    </h6>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Estimated Hours</label>
                            <input type="number" name="est_hours" id="est_hours_{{ $app->id }}" class="form-control py-2 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" min="0" placeholder="0" value="{{ $app->result_estimated_at && $app->tested_at ? Carbon\Carbon::parse($app->tested_at)->diffInHours($app->result_estimated_at) : '0' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Estimated Minutes</label>
                            <input type="number" name="est_minutes" id="est_mins_{{ $app->id }}" class="form-control py-2 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" min="0" max="59" placeholder="0" value="{{ $app->result_estimated_at && $app->tested_at ? (Carbon\Carbon::parse($app->tested_at)->diffInMinutes($app->result_estimated_at) % 60) : '0' }}">
                        </div>
                        <div class="col-12 mt-2">
                            <div class="alert alert-clinical p-2.5 border" style="background-color: rgba(25, 211, 140, 0.03); border-color: var(--border-color) !important; border-radius: 8px;">
                                <i class="bi bi-info-circle-fill text-accent me-1.5" style="color: var(--brand-accent) !important;"></i>
                                <small class="text-muted"><strong>Instant Results Note:</strong> If this is a rapid test, keep the duration values as <strong>0 hours & 0 minutes</strong> to display <strong>"Please Wait / Processing"</strong> on the patient's dashboard.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANE 2: RETEST REQUEST - Shown by default if already tested -->
                <div id="retest_process_fields_{{ $app->id }}" class="{{ $isAlreadyTested ? '' : 'd-none' }}">
                    <h6 class="text-warning mb-3 small fw-bold uppercase border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        Retesting Criteria
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Reason for Return</label>
                            <select id="retest_reason_select_{{ $app->id }}" name="retest_reason" class="form-select py-2 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="toggleModalCustomRetest({{ $app->id }}, this.value)" {{ $isAlreadyTested ? 'required' : '' }}>
                                <option value="" disabled selected>-- Select a return justification --</option>
                                <option value="Hemolyzed specimen (unsuitable for testing)">Hemolyzed specimen (unsuitable for testing)</option>
                                <option value="Incomplete or clotted blood sample">Incomplete or clotted blood sample</option>
                                <option value="Contaminated or compromised specimen">Contaminated or compromised specimen</option>
                                <option value="Insufficient specimen volume (QNS)">Insufficient specimen volume (QNS)</option>
                                <option value="Mismatched or unlabeled specimen container">Mismatched or unlabeled specimen container</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                        </div>
                        <div id="retest_custom_reason_wrapper_{{ $app->id }}" class="col-12 d-none">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Custom Reason</label>
                            <textarea name="retest_custom_reason" id="retest_custom_reason_{{ $app->id }}" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="3" placeholder="Provide clinical context for the recollect request..."></textarea>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="alert alert-clinical p-2.5 border" style="background-color: rgba(255, 193, 7, 0.04); border-color: rgba(255, 193, 7, 0.2) !important; border-radius: 8px;">
                                <i class="bi bi-exclamation-triangle-fill text-warning me-1.5"></i>
                                <small class="text-muted"><strong>Retesting Protocol:</strong> Flagging this appointment resets the sampling sequence. The system will trigger a notification instructing the patient to return to the laboratory for a recollect.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top bg-transparent p-3" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" 
                        class="btn-custom {{ $isAlreadyTested ? 'btn-outline-danger' : 'btn-accent' }} py-2 px-4 fw-bold uppercase shadow-sm" 
                        id="modal_submit_btn_{{ $app->id }}">
                    {{ $isAlreadyTested ? 'CONFIRM RETEST EXCEPTION' : 'APPROVE & RECORD SAMPLING' }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- LOCAL VALIDATION ERROR MODAL FOR LACKING INPUTS -->
<div class="modal fade" id="modalValidationError_{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1075;">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-danger bg-card text-center p-4 shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid #dc3545; color: var(--text-main); border-radius: 14px;">
            <div class="mb-3">
                <i class="bi bi-exclamation-triangle-fill text-danger display-4 d-block"></i>
            </div>
            <h5 class="text-danger fw-bold mb-2 uppercase tracking-tight">Requirements Lacking</h5>
            <div id="modal_validation_error_msg_{{ $app->id }}" class="text-secondary small mb-4 text-start">
                Please complete all required fields before completing sampling.
            </div>
            <button type="button" class="btn btn-danger w-100 py-2.5 uppercase fw-bold" onclick="bootstrap.Modal.getInstance(document.getElementById('modalValidationError_{{ $app->id }}'))?.hide()">
                Understood
            </button>
        </div>
    </div>
</div>

<script>
if (!window.setModalMainAction) {
    window.setModalMainAction = function(appId, action) {
        const actionInput = document.getElementById(`action_${appId}`);
        if (actionInput) actionInput.value = action;
        
        const testPane = document.getElementById(`tested_process_fields_${appId}`);
        const retestPane = document.getElementById(`retest_process_fields_${appId}`);
        const submitBtn = document.getElementById(`modal_submit_btn_${appId}`);
        const selectRetest = document.getElementById(`retest_reason_select_${appId}`);
        const modalTitle = document.getElementById(`modal_title_${appId}`);

        if (action === 'tested') {
            if (testPane) testPane.classList.remove('d-none');
            if (retestPane) retestPane.classList.add('d-none');
            if (submitBtn) {
                submitBtn.innerText = "APPROVE & RECORD SAMPLING";
                submitBtn.className = "btn-custom btn-accent py-2 px-4 fw-bold uppercase shadow-sm";
            }
            if (modalTitle) {
                modalTitle.className = "modal-title text-accent fw-bold uppercase small m-0";
                modalTitle.innerHTML = '<i class="bi bi-shield-check text-accent me-2 fs-5"></i>Clinical Sampling & Verification Hub';
            }
            if (selectRetest) selectRetest.removeAttribute('required');
        } else {
            if (testPane) testPane.classList.add('d-none');
            if (retestPane) retestPane.classList.remove('d-none');
            if (submitBtn) {
                submitBtn.innerText = "CONFIRM RETEST EXCEPTION";
                submitBtn.className = "btn-custom btn-outline-danger py-2 px-4 fw-bold uppercase shadow-sm";
            }
            if (modalTitle) {
                modalTitle.className = "modal-title text-warning fw-bold uppercase small m-0";
                modalTitle.innerHTML = '<i class="bi bi-arrow-repeat text-warning me-2 fs-5"></i>Clinical Retest Request Hub';
            }
            if (selectRetest) selectRetest.setAttribute('required', 'required');
        }
    };
}

if (!window.toggleModalCustomRetest) {
    window.toggleModalCustomRetest = function(appId, val) {
        const wrapper = document.getElementById(`retest_custom_reason_wrapper_${appId}`);
        const textarea = document.getElementById(`retest_custom_reason_${appId}`);

        if (wrapper && textarea) {
            if (val === 'Others') {
                wrapper.classList.remove('d-none');
                textarea.setAttribute('required', 'required');
                textarea.focus();
            } else {
                wrapper.classList.add('d-none');
                textarea.removeAttribute('required');
            }
        }
    };
}

if (!window.validateAndSubmitModalForm) {
    window.validateAndSubmitModalForm = async function(appId, event) {
        event.preventDefault();
        event.stopPropagation();

        const form = document.getElementById(`markTestedForm_${appId}`);
        if (!form) return false;

        const actionInput = document.getElementById(`action_${appId}`);
        const action = actionInput ? actionInput.value : 'tested';
        let errors = [];

        const payAmount = form.querySelector('[name="payment_amount"]')?.value;
        if (!payAmount || parseFloat(payAmount) < 0) {
            errors.push("Confirmed Collection Amount must be a valid positive number.");
        }

        if (action === 'retest') {
            const reason = document.getElementById(`retest_reason_select_${appId}`)?.value;
            if (!reason) {
                errors.push("Please select a valid return justification for retesting.");
            } else if (reason === 'Others') {
                const customReason = document.getElementById(`retest_custom_reason_${appId}`)?.value.trim();
                if (!customReason || customReason.length < 5) {
                    errors.push("Custom Retest Reason is required (min 5 characters).");
                }
            }
        }

        if (errors.length > 0) {
            let errorHtml = '<ul class="mb-0 ps-3 text-danger">';
            errors.forEach(err => {
                errorHtml += `<li class="mb-1 small font-semibold">${err}</li>`;
            });
            errorHtml += '</ul>';

            document.getElementById(`modal_validation_error_msg_${appId}`).innerHTML = errorHtml;
            const errModalEl = document.getElementById(`modalValidationError_${appId}`);
            const errModal = bootstrap.Modal.getInstance(errModalEl) || new bootstrap.Modal(errModalEl);
            errModal.show();
            return false;
        }

        // Avoid property shadowing from <input name="action">
        const formActionUrl = form.getAttribute('action');

        const submitBtn = document.getElementById(`modal_submit_btn_${appId}`);
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
        }

        try {
            const formData = new FormData(form);
            const csrfToken = form.querySelector('input[name="_token"]')?.value 
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || '';

            const response = await fetch(formActionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            // Dismiss the modal cleanly
            const mainModalEl = document.getElementById(`testModal${appId}`);
            if (mainModalEl && typeof bootstrap !== 'undefined') {
                const mainModal = bootstrap.Modal.getInstance(mainModalEl);
                if (mainModal) mainModal.hide();
            }

            if (response.ok) {
                // Refresh master queue in place without full page reload
                if (typeof fetchQueueWithParams === 'function') {
                    await fetchQueueWithParams(window.location.href);
                } else if (typeof syncMasterQueue === 'function') {
                    syncMasterQueue();
                }
            } else {
                const errData = await response.json();
                let errMsg = errData.message || "An error occurred while recording sampling.";
                if (errData.errors) {
                    errMsg = '<ul class="mb-0 ps-3 text-danger">' + Object.values(errData.errors).flat().map(e => `<li class="mb-1">${e}</li>`).join('') + '</ul>';
                }
                document.getElementById(`modal_validation_error_msg_${appId}`).innerHTML = errMsg;
                const errModalEl = document.getElementById(`modalValidationError_${appId}`);
                const errModal = bootstrap.Modal.getInstance(errModalEl) || new bootstrap.Modal(errModalEl);
                errModal.show();
            }
        } catch (error) {
            console.error("Sampling submission error:", error);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        return false;
    };
}

// Global modal opener delegator to switch to retest automatically when required
if (!window.retestModalWatcherRegistered) {
    window.retestModalWatcherRegistered = true;
    document.addEventListener('show.bs.modal', function(e) {
        if (e.target && e.target.id && e.target.id.startsWith('testModal')) {
            const appId = e.target.id.replace('testModal', '');
            const actionInput = document.getElementById(`action_${appId}`);
            if (actionInput && actionInput.value === 'retest') {
                if (typeof setModalMainAction === 'function') {
                    setModalMainAction(appId, 'retest');
                }
            }
        }
    });
}
</script>

<style>
/* Position modal container comfortably below the 72px fixed navbar across all zoom levels */
#testModal{{ $app->id }}.modal {
    padding-top: 85px !important;
}

#testModal{{ $app->id }} .modal-dialog {
    margin-top: 0 !important;
    margin-bottom: 2rem !important;
}

/* Tab button overrides */
#mainTab_{{ $app->id }} .nav-link {
    color: var(--text-muted) !important;
    background-color: transparent !important;
    border: 1.5px solid var(--border-color) !important;
    transition: all 0.2s ease-in-out;
    font-size: 0.8rem;
}

#mainTab_{{ $app->id }} .nav-link:hover {
    color: var(--text-main) !important;
    border-color: var(--brand-accent) !important;
}

#mainTab_{{ $app->id }} #tested-tab-btn-{{ $app->id }}.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 10px rgba(25, 211, 140, 0.25) !important;
}

#mainTab_{{ $app->id }} #retest-tab-btn-{{ $app->id }}.active {
    background-color: #ffc107 !important;
    color: #1c232d !important;
    border-color: #ffc107 !important;
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.25) !important;
}

#testModal{{ $app->id }}.show {
    background-color: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(4px);
}

#modalValidationError_{{ $app->id }}.show {
    background-color: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(2px);
}
</style>