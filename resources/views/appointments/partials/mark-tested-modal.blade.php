@php 
    // Detect if the appointment is already tested/validated to lock out redundant "Progress to Tested" options
    $isAlreadyTested = in_array($app->status, ['tested', 'encoded', 'released']); 
@endphp

<!-- MAIN SAMPLING & RETEST VERIFICATION MODAL -->
<div class="modal fade" id="testModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1050;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <!-- FIXED: novalidate disables native browser tooltips so our custom error modal shows up properly -->
        <form id="markTestedForm_{{ $app->id }}" action="{{ route('appointments.tested', $app->id) }}" method="POST" onsubmit="return validateModalForm({{ $app->id }}, event)" class="modal-content border-secondary bg-card text-start" style="background-color: var(--bg-card); color: var(--text-main);" novalidate>
            @csrf
            @method('PATCH')
            
            <!-- Hidden action triggers: progresses to tested or retest -->
            <input type="hidden" name="action" id="action_{{ $app->id }}" value="{{ $isAlreadyTested ? 'retest' : 'tested' }}"> 

            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3 d-flex flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title text-accent fw-bold uppercase small m-0" id="modal_title_{{ $app->id }}">
                        <i class="bi bi-shield-check-fill me-2 fs-5"></i>{{ $isAlreadyTested ? 'Clinical Retest Request Hub' : 'Clinical Sampling & Verification Hub' }}
                    </h5>
                    <div class="d-flex gap-2 align-items-center ms-auto">
                        {{-- Distinctive Solid-Style Reset Button matching Resubmit modal layout --}}
                        <button type="button" class="btn btn-sm btn-warning text-dark fw-extrabold py-1.5 px-3 d-flex align-items-center gap-1.5 shadow" style="font-size: 0.725rem; border-radius: 6px; letter-spacing: 0.5px; border: 1px solid #d39e00;" onclick="resetMarkTestedForm('{{ $app->id }}')">
                            <i class="bi bi-arrow-counterclockwise fs-6"></i> RESET EDITS
                        </button>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- MAIN WORKFLOW TABS (Hidden entirely if already tested) -->
                @if(!$isAlreadyTested)
                    <ul class="nav nav-tabs nav-fill border-secondary border-opacity-25" id="mainTab_{{ $app->id }}" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-uppercase py-2 rounded-top border-bottom-0" id="tested-tab-btn-{{ $app->id }}" data-bs-toggle="tab" type="button" onclick="setModalMainAction({{ $app->id }}, 'tested')" style="font-size: 0.8rem;">
                                <i class="bi bi-person-check-fill me-1"></i> 1. Progress to Tested
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-uppercase py-2 text-warning rounded-top border-bottom-0" id="retest-tab-btn-{{ $app->id }}" data-bs-toggle="tab" type="button" onclick="setModalMainAction({{ $app->id }}, 'retest')" style="font-size: 0.8rem;">
                                <i class="bi bi-arrow-repeat me-1"></i> 2. Return for Retest
                            </button>
                        </li>
                    </ul>
                @endif
            </div>

            <div class="modal-body p-4">
                <!-- STEPPER CONTROLS -->
                <ul class="nav nav-pills justify-content-center mb-4 gap-1.5" id="subTabs_{{ $app->id }}" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active btn-sm modal-sub-tab-btn-{{ $app->id }}" id="sub-btn-1-{{ $app->id }}" type="button" onclick="switchModalSubTab({{ $app->id }}, 1)">1. Identity</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm modal-sub-tab-btn-{{ $app->id }}" id="sub-btn-2-{{ $app->id }}" type="button" onclick="switchModalSubTab({{ $app->id }}, 2)">2. Address</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm modal-sub-tab-btn-{{ $app->id }}" id="sub-btn-3-{{ $app->id }}" type="button" onclick="switchModalSubTab({{ $app->id }}, 3)">3. Tests & Billing</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm modal-sub-tab-btn-{{ $app->id }}" id="sub-btn-4-{{ $app->id }}" type="button" onclick="switchModalSubTab({{ $app->id }}, 4)">4. Processing</button>
                    </li>
                </ul>

                <!-- SUB-TAB PANES -->
                <!-- SUB-TAB 1: IDENTITY -->
                <div class="modal-step-pane-{{ $app->id }}" id="modal-pane-1-{{ $app->id }}">
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">1. Demographics Snapshot</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
                            <input type="text" name="patient_first_name" class="form-control py-2 uppercase fw-bold" value="{{ $app->patient_first_name }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Middle Name</label>
                            <input type="text" name="patient_middle_name" class="form-control py-2 uppercase fw-bold" value="{{ $app->patient_middle_name ?? 'N/A' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
                            <input type="text" name="patient_last_name" class="form-control py-2 uppercase fw-bold" value="{{ $app->patient_last_name }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
                            <input type="text" name="patient_suffix" id="modal_suffix_{{ $app->id }}" list="suffix_options" class="form-control py-2 uppercase fw-bold" value="{{ $app->patient_suffix }}">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1">Birthdate</label>
                            <input type="date" name="patient_birthdate" id="bday_{{ $app->id }}" class="form-control py-3" value="{{ $app->patient_birthdate ? $app->patient_birthdate->format('Y-m-d') : '' }}" required max="{{ date('Y-m-d') }}" onchange="calculateModalAge({{ $app->id }})">
                            <small class="text-muted mt-1 d-block">Age calculated on site: <span id="age_val_{{ $app->id }}" class="fw-bold text-accent">{{ $app->patient_age }}</span> Years Old</small>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
                            <select name="patient_sex" class="form-select py-2" required>
                                <option value="Male" {{ $app->patient_sex === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ $app->patient_sex === 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Contact Phone</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                                <input type="text" id="modal_phone_display_{{ $app->id }}" class="form-control py-2 shadow-none" placeholder="171234567" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncModalPhone('{{ $app->id }}');" required>
                            </div>
                            <input type="hidden" name="patient_phone" id="modal_in_phone_{{ $app->id }}" value="{{ $app->patient_phone }}">
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 2: ADDRESS -->
                <div class="modal-step-pane-{{ $app->id }} d-none" id="modal-pane-2-{{ $app->id }}">
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">2. Verify Home Address (PSGC Mapping)</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1">Province</label>
                            <select id="prov_{{ $app->id }}" name="patient_province" class="form-select py-2" required onchange="fetchModalCities({{ $app->id }}, this.value)">
                                <option value="">Loading Provinces...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1">City / Municipality</label>
                            <select id="city_{{ $app->id }}" name="patient_city" class="form-select py-2" disabled required onchange="fetchModalBarangays({{ $app->id }}, this.value)">
                                <option value="">Select Province First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1">Barangay</label>
                            <select id="brgy_{{ $app->id }}" name="patient_barangay" class="form-select py-2" disabled required>
                                <option value="">Select City First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-secondary fw-bold mb-1">Street / House No.</label>
                            <input type="text" name="patient_street" class="form-control py-2 uppercase" value="{{ $app->patient_street }}" required>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 3: TESTS & BILLING -->
                <div class="modal-step-pane-{{ $app->id }} d-none" id="modal-pane-3-{{ $app->id }}">
                    <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">3. Selected Examinations & Real-Time Bill</h6>
                    <div class="row g-3">
                        <!-- Left checklist column -->
                        <div class="col-md-7">
                            <div class="mb-2">
                                <input type="text" id="test_search_{{ $app->id }}" class="form-control form-control-sm" placeholder="Search / filter diagnostic tests..." onkeyup="filterModalTests({{ $app->id }})">
                            </div>
                            <div class="row g-2 overflow-auto p-2 border border-secondary border-opacity-10 rounded bg-black bg-opacity-25" id="test_list_{{ $app->id }}" style="max-height: 250px;">
                                @php $linkedTests = $app->services->pluck('id')->toArray(); @endphp
                                @foreach($services as $s)
                                    <div class="col-12 modal-test-item-{{ $app->id }}" data-name="{{ strtoupper($s->name) }}">
                                        <div class="form-check p-2 rounded test-checkbox-item border border-secondary border-opacity-10 bg-secondary bg-opacity-5">
                                            <input class="form-check-input ms-0 me-2 test-checkbox-{{ $app->id }}" type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="chk_{{ $app->id }}_{{ $s->id }}" data-name="{{ strtoupper($s->name) }}" data-price="{{ $s->price }}" {{ in_array($s->id, $linkedTests) ? 'checked' : '' }} onchange="calculateModalTotal({{ $app->id }})">
                                            <label class="form-check-label text-main smaller" for="chk_{{ $app->id }}_{{ $s->id }}">
                                                {{ strtoupper($s->name) }} ( {{ number_format($s->price, 2) }})
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Right billing column -->
                        <div class="col-md-5 d-flex flex-column justify-content-between">
                            <div>
                                <label class="small text-secondary fw-bold mb-1 uppercase">Selected Examinations</label>
                                <div id="modal_selected_tests_list_{{ $app->id }}" class="overflow-auto p-2 border border-secondary border-opacity-10 rounded custom-scroll" style="max-height: 190px;">
                                    <!-- Populated dynamically via JS -->
                                </div>
                            </div>
                            <div class="d-flex justify-content-between p-2.5 rounded bg-secondary bg-opacity-10 mt-2">
                                <span class="fw-bold uppercase small text-secondary">Estimated Bill:</span>
                                <span class="text-accent fw-bold fs-6"> <span id="lbl_total_{{ $app->id }}">{{ number_format($app->totalPrice(), 2) }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 4: PROCESSING -->
                <div class="modal-step-pane-{{ $app->id }} d-none" id="modal-pane-4-{{ $app->id }}">
                    <!-- Progress to Tested Pane -->
                    <div id="tested_process_fields_{{ $app->id }}">
                        <h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">4. Processing Parameters</h6>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Confirmed Price (PHP)</label>
                                <input type="number" step="0.01" name="payment_amount" id="val_total_{{ $app->id }}" class="form-control py-2 fw-bold text-accent" value="{{ $app->payment_amount ?? $app->totalPrice() }}" required min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Estimated Hours</label>
                                <input type="number" name="est_hours" id="est_hours_{{ $app->id }}" class="form-control py-2" min="0" placeholder="0" value="{{ $app->result_estimated_at ? Carbon\Carbon::parse($app->tested_at)->diffInHours($app->result_estimated_at) : '0' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Estimated Minutes</label>
                                <input type="number" name="est_minutes" id="est_mins_{{ $app->id }}" class="form-control py-2" min="0" max="59" placeholder="0" value="{{ $app->result_estimated_at ? (Carbon\Carbon::parse($app->tested_at)->diffInMinutes($app->result_estimated_at) % 60) : '0' }}">
                            </div>
                            <div class="col-12 mt-2">
                                <div class="alert alert-clinical p-2 border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.03);">
                                    <i class="bi bi-info-circle-fill text-accent me-1.5"></i>
                                    <small class="text-muted"><strong>Instant Results Note:</strong> If this is a rapid test, keep the duration values as <strong>0 hours & 0 minutes</strong> to display <strong>"Please Wait / Processing"</strong> on the patient's dashboard.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Retest Request Pane -->
                    <div id="retest_process_fields_{{ $app->id }}" class="d-none">
                        <h6 class="text-warning mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">4. Retesting Criteria</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Reason for Return</label>
                                <select id="retest_reason_select_{{ $app->id }}" name="retest_reason" class="form-select py-2" onchange="toggleModalCustomRetest({{ $app->id }}, this.value)">
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
                                <textarea name="retest_custom_reason" id="retest_custom_reason_{{ $app->id }}" class="form-control shadow-none" rows="3" placeholder="Provide clinical context for the recollect request..."></textarea>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="alert alert-clinical p-2 border-warning border-opacity-10" style="background-color: rgba(255, 193, 7, 0.03);">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1.5"></i>
                                    <small class="text-muted"><strong>Retesting Protocol:</strong> Flagging this appointment resets the sampling sequence. The system will trigger a notification instructing the patient to return to the laboratory for a recollect.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase" id="modal_submit_btn_{{ $app->id }}">APPROVE & RECORD SAMPLING</button>
            </div>
        </form>
    </div>
</div>

<!-- LOCAL VALIDATION ERROR MODAL FOR LACKING INPUTS -->
<div class="modal fade" id="modalValidationError_{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1060;">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-danger bg-card text-center p-4" style="background-color: var(--bg-card); border: 1.5px solid #dc3545; color: var(--text-main);">
            <div class="mb-3">
                <i class="bi bi-exclamation-triangle-fill text-danger display-4 d-block animate-pulse"></i>
            </div>
            <h5 class="text-danger fw-bold mb-2 uppercase tracking-tighter">Requirements Lacking</h5>
            <div id="modal_validation_error_msg_{{ $app->id }}" class="text-secondary small mb-4 text-start">
                Please complete all required fields before completing sampling.
            </div>
            <button type="button" class="btn btn-danger w-100 py-2.5 uppercase fw-bold" onclick="bootstrap.Modal.getInstance(document.getElementById('modalValidationError_{{ $app->id }}')).hide()">Understood</button>
        </div>
    </div>
</div>

<script>
(function() {
    const appId = {{ $app->id }};
    const savedProv = "{{ $app->patient_province }}";
    const savedCity = "{{ $app->patient_city }}";
    const savedBrgy = "{{ $app->patient_barangay }}";

    document.addEventListener('DOMContentLoaded', async () => {
        const modalEl = document.getElementById(`testModal${appId}`);
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', async () => {
                await fetchModalProvinces(appId, savedProv, savedCity, savedBrgy);
                calculateModalTotal(appId);

                // Initialize phone display prefix on load safely
                let phoneVal = '{{ $app->patient_phone }}'.trim();
                if (phoneVal.startsWith('+639')) phoneVal = '09' + phoneVal.substring(4);
                if (phoneVal.startsWith('639')) phoneVal = '09' + phoneVal.substring(3);
                const displayInput = document.getElementById(`modal_phone_display_${appId}`);
                const hiddenInput = document.getElementById(`modal_in_phone_${appId}`);
                if (displayInput && hiddenInput) {
                    if (phoneVal.startsWith('09') && phoneVal.length === 11) {
                        displayInput.value = phoneVal.substring(2);
                        hiddenInput.value = phoneVal;
                    } else {
                        displayInput.value = phoneVal;
                        hiddenInput.value = phoneVal;
                    }
                }

                // Dynamic initialization check if the appointment status is already tested
                @if($isAlreadyTested)
                    setModalMainAction(appId, 'retest');
                @endif
            });
        }
    });
})();

function syncModalPhone(appId) {
    const displayInput = document.getElementById(`modal_phone_display_${appId}`);
    const hiddenInput = document.getElementById(`modal_in_phone_${appId}`);
    if (displayInput && hiddenInput) {
        hiddenInput.value = displayInput.value ? '09' + displayInput.value : '';
    }
}

/**
 * Reverts all edited fields inside the clinical mark-tested modal back to originally saved server values
 */
window.resetMarkTestedForm = async function(appId) {
    if (!confirm('Revert all changes back to originally saved values?')) return;

    const form = document.getElementById(`markTestedForm_${appId}`);
    
    // Reset standard demographics
    form.querySelector('[name="patient_first_name"]').value = '{{ $app->patient_first_name }}';
    form.querySelector('[name="patient_middle_name"]').value = '{{ $app->patient_middle_name ?? "N/A" }}';
    form.querySelector('[name="patient_last_name"]').value = '{{ $app->patient_last_name }}';
    
    const suffixInput = document.getElementById(`modal_suffix_${appId}`);
    if (suffixInput) suffixInput.value = '{{ $app->patient_suffix }}';

    form.querySelector('[name="patient_sex"]').value = '{{ $app->patient_sex }}';
    form.querySelector('[name="patient_birthdate"]').value = '{{ $app->patient_birthdate ? $app->patient_birthdate->format("Y-m-d") : "" }}';

    // Recalculate age snapshot display
    calculateModalAge(appId);

    // Reset phone view display
    let originalPhone = '{{ $app->patient_phone }}'.trim();
    if (originalPhone.startsWith('+639')) originalPhone = '09' + originalPhone.substring(4);
    if (originalPhone.startsWith('639')) originalPhone = '09' + originalPhone.substring(3);
    const displayInput = document.getElementById(`modal_phone_display_${appId}`);
    const hiddenInput = document.getElementById(`modal_in_phone_${appId}`);
    if (displayInput && hiddenInput) {
        if (originalPhone.startsWith('09') && originalPhone.length === 11) {
            displayInput.value = originalPhone.substring(2);
            hiddenInput.value = originalPhone;
        } else {
            displayInput.value = originalPhone;
            hiddenInput.value = originalPhone;
        }
    }

    // Reset cascading address layers using fixed json static endpoints
    await fetchModalProvinces(appId, '{{ $app->patient_province }}', '{{ $app->patient_city }}', '{{ $app->patient_barangay }}');
    form.querySelector('[name="patient_street"]').value = '{{ $app->patient_street }}';

    // Reset tests checklist
    const originalTests = @json($linkedTests);
    document.querySelectorAll(`.test-checkbox-${appId}`).forEach(cb => {
        cb.checked = originalTests.includes(parseInt(cb.value));
    });
    calculateModalTotal(appId);

    // Reset processing estimations
    const estHours = document.getElementById(`est_hours_${appId}`);
    if (estHours) {
        estHours.value = '{{ $app->result_estimated_at ? Carbon\Carbon::parse($app->tested_at)->diffInHours($app->result_estimated_at) : "0" }}';
    }
    const estMins = document.getElementById(`est_mins_${appId}`);
    if (estMins) {
        estMins.value = '{{ $app->result_estimated_at ? (Carbon\Carbon::parse($app->tested_at)->diffInMinutes($app->result_estimated_at) % 60) : "0" }}';
    }
    
    const payAmount = document.getElementById(`val_total_${appId}`);
    if (payAmount) {
        payAmount.value = '{{ $app->payment_amount ?? $app->totalPrice() }}';
    }

    // Reset workflow selection triggers
    @if($isAlreadyTested)
        setModalMainAction(appId, 'retest');
    @else
        setModalMainAction(appId, 'tested');
    @endif

    // Switch view back to first tab
    switchModalSubTab(appId, 1);
}

// 1. CHANNELS TAB CONTROLLER
function setModalMainAction(appId, action) {
    document.getElementById(`action_${appId}`).value = action;
    const testPane = document.getElementById(`tested_process_fields_${appId}`);
    const retestPane = document.getElementById(`retest_process_fields_${appId}`);
    const submitBtn = document.getElementById(`modal_submit_btn_${appId}`);
    const selectRetest = document.getElementById(`retest_reason_select_${appId}`);

    if (action === 'tested') {
        testPane.classList.remove('d-none');
        retestPane.classList.add('d-none');
        submitBtn.innerText = "APPROVE & RECORD SAMPLING";
        submitBtn.className = "btn-custom btn-accent py-2 px-4 fw-bold uppercase";
        selectRetest.removeAttribute('required');
    } else {
        testPane.classList.add('d-none');
        retestPane.classList.remove('d-none');
        submitBtn.innerText = "CONFIRM RETEST EXCEPTION";
        submitBtn.className = "btn-custom btn-outline-danger py-2 px-4 fw-bold uppercase";
        selectRetest.setAttribute('required', 'required');
    }
}

// 2. MODAL STEPPER NAVIGATION
function switchModalSubTab(appId, step) {
    // Toggle Active Nav Classes
    document.querySelectorAll(`.modal-sub-tab-btn-${appId}`).forEach((btn, idx) => {
        if (idx + 1 === step) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Toggle Pane Visibility
    document.querySelectorAll(`.modal-step-pane-${appId}`).forEach((pane, idx) => {
        if (idx + 1 === step) {
            pane.classList.remove('d-none');
        } else {
            pane.classList.add('d-none');
        }
    });
}

// 3. DYNAMIC AGE DETECTOR
function calculateModalAge(appId) {
    const bdayInput = document.getElementById(`bday_${appId}`).value;
    if (!bdayInput) return;
    const bday = new Date(bdayInput);
    const today = new Date();
    let age = today.getFullYear() - bday.getFullYear();
    const m = today.getMonth() - bday.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < bday.getDate())) {
        age--;
    }
    document.getElementById(`age_val_${appId}`).innerText = age;
}

// 4. MULTI-SELECT BILL COMPILER WITH EMBEDDED LISTING
function calculateModalTotal(appId) {
    let sum = 0;
    const listContainer = document.getElementById(`modal_selected_tests_list_${appId}`);
    listContainer.innerHTML = '';

    document.querySelectorAll(`.test-checkbox-${appId}:checked`).forEach(cb => {
        const price = parseFloat(cb.dataset.price || 0);
        sum += price;
        
        listContainer.innerHTML += `
            <div class="d-flex justify-content-between align-items-center mb-1 py-1 border-bottom border-secondary border-opacity-5">
                <span class="small text-main font-semibold" style="font-size:0.75rem;"><i class="bi bi-check2 text-accent me-1.5"></i>${cb.dataset.name}</span>
                <span class="small text-accent" style="font-size:0.75rem;"> ${price.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
            </div>
        `;
    });

    if (listContainer.innerHTML === '') {
        listContainer.innerHTML = '<div class="text-center py-4 text-muted small italic">No tests selected yet.</div>';
    }

    document.getElementById(`lbl_total_${appId}`).innerText = sum.toLocaleString(undefined, { minimumFractionDigits: 2 });
    document.getElementById(`val_total_${appId}`).value = sum;
}

// 5. DIAGNOSTICS SEARCH FILTER
function filterModalTests(appId) {
    const query = document.getElementById(`test_search_${appId}`).value.toUpperCase();
    document.querySelectorAll(`.modal-test-item-${appId}`).forEach(item => {
        const name = item.dataset.name || '';
        item.style.display = name.includes(query) ? '' : 'none';
    });
}

// 6. TOGGLE CUSTOM RETEST DETAILS
function toggleModalCustomRetest(appId, val) {
    const wrapper = document.getElementById(`retest_custom_reason_wrapper_${appId}`);
    const textarea = document.getElementById(`retest_custom_reason_${appId}`);

    if (val === 'Others') {
        wrapper.classList.remove('d-none');
        textarea.setAttribute('required', 'required');
        textarea.focus();
    } else {
        wrapper.classList.add('d-none');
        textarea.removeAttribute('required');
    }
}

// 7. CLIENT-SIDE VALIDATION EXCEPTION CHECKER
function validateModalForm(appId, event) {
    const form = document.getElementById(`markTestedForm_${appId}`);
    const action = document.getElementById(`action_${appId}`).value;
    let errors = [];

    // Tab 1: Validate Demographics
    const firstName = form.querySelector('[name="patient_first_name"]').value.trim();
    const lastName = form.querySelector('[name="patient_last_name"]').value.trim();
    const birthdate = form.querySelector('[name="patient_birthdate"]').value;

    if (!firstName) errors.push("First Name is missing on Tab 1.");
    if (!lastName) errors.push("Last Name is missing on Tab 1.");
    
    // Birthdate & Age Rules validation matching user policy guidelines
    if (!birthdate) {
        errors.push("Birthdate is missing on Tab 1.");
    } else {
        const age = Math.floor((new Date() - new Date(birthdate)) / (1000 * 60 * 60 * 24 * 365.25));
        const isDependent = @json($app->dependent_id !== null);
        if (age < 0) {
            errors.push("Birthdate cannot be in the future.");
        } else if (isDependent) {
            if (age >= 18) {
                errors.push("Dependents must be minors (under 18 years of age).");
            }
        } else {
            if (age < 18) {
                errors.push("You must be at least 18 years old to book a personal appointment.");
            }
        }
    }

    // Contact Phone validation
    const phone = document.getElementById(`modal_in_phone_${appId}`).value;
    const displayPhone = document.getElementById(`modal_phone_display_${appId}`).value;
    const phoneRegex = /^09\d{9}$/;
    if (!displayPhone) {
        errors.push("Contact Phone is missing on Tab 1.");
    } else if (!phoneRegex.test(phone)) {
        errors.push("Phone number must start with 09 and contain exactly 11 digits.");
    }

    // Tab 2: Validate Address
    const prov = form.querySelector('#prov_' + appId).value;
    const city = form.querySelector('#city_' + appId).value;
    const brgy = form.querySelector('#brgy_' + appId).value;
    const street = form.querySelector('[name="patient_street"]').value.trim();

    if (!prov) errors.push("Province selection is missing on Tab 2.");
    if (!city) errors.push("City/Municipality selection is missing on Tab 2.");
    if (!brgy) errors.push("Barangay selection is missing on Tab 2.");
    if (!street) errors.push("Street Address is missing on Tab 2.");

    // Tab 3: Validate Tests Checklist
    const checkedTests = form.querySelectorAll(`.test-checkbox-${appId}:checked`);
    if (checkedTests.length === 0) {
        errors.push("At least one diagnostic test must be checked on Tab 3.");
    }

    // Tab 4: Validate Workflow Parameters
    if (action === 'tested') {
        const payAmount = form.querySelector('[name="payment_amount"]').value;
        if (!payAmount || parseFloat(payAmount) < 0) {
            errors.push("Confirmed Collection Amount must be a valid positive number on Tab 4.");
        }
    } else if (action === 'retest') {
        const reason = document.getElementById(`retest_reason_select_${appId}`).value;
        if (!reason) {
            errors.push("Please select a valid return justification on Tab 4.");
        } else if (reason === 'Others') {
            const customReason = document.getElementById(`retest_custom_reason_${appId}`).value.trim();
            if (!customReason || customReason.length < 5) {
                errors.push("Custom Retest Reason is required (min 5 characters) on Tab 4.");
            }
        }
    }

    if (errors.length > 0) {
        event.preventDefault();
        event.stopPropagation();

        let errorHtml = '<ul class="mb-0 ps-3 text-danger">';
        errors.forEach(err => {
            errorHtml += `<li class="mb-1 small font-semibold">${err}</li>`;
        });
        errorHtml += '</ul>';

        document.getElementById(`modal_validation_error_msg_${appId}`).innerHTML = errorHtml;
        
        // Trigger local validation modal
        const errModalEl = document.getElementById(`modalValidationError_${appId}`);
        const errModal = new bootstrap.Modal(errModalEl);
        errModal.show();

        return false;
    }

    // Convert option codes to their text labels before submit to preserve high contrast display text in database
    compileModalAddress(appId);
    return true;
}

// 8. COMPILE ADDRESS VALUES ON SUBMIT
function compileModalAddress(appId) {
    const prov = document.getElementById(`prov_${appId}`);
    const city = document.getElementById(`city_${appId}`);
    const brgy = document.getElementById(`brgy_${appId}`);

    if (prov && city && brgy) {
        const provName = prov.options[prov.selectedIndex]?.text || '';
        const cityName = city.options[city.selectedIndex]?.text || '';
        const brgyName = brgy.options[brgy.selectedIndex]?.text || '';

        if (provName && !provName.includes('Select') &&
            cityName && !cityName.includes('Select') &&
            brgyName && !brgyName.includes('Select')) {
            prov.options[prov.selectedIndex].value = provName;
            city.options[city.selectedIndex].value = cityName;
            brgy.options[brgy.selectedIndex].value = brgyName;
        }
    }
}

// 9. PSGC CASCADING ENGINE
async function fetchModalProvinces(appId, savedProv, savedCity, savedBrgy) {
    const provSel = document.getElementById(`prov_${appId}`);
    if (provSel && provSel.options.length > 1) return; // Prevent double load
    try {
        // FIXED: Requests standardize static JSON routes to prevent trailing slash redirect parse blocks
        const res = await fetch('https://psgc.gitlab.io/api/provinces.json');
        const data = await res.json();
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });

        if (savedProv) {
            // FIXED: Support matching by either text or numeric code format safely
            let provOpt = Array.from(provSel.options).find(opt => 
                opt.text.toUpperCase() === savedProv.toUpperCase() || 
                opt.value === savedProv
            );
            if (provOpt) {
                provSel.value = provOpt.value;
                await fetchModalCities(appId, provOpt.value, savedCity, savedBrgy);
            }
        }
    } catch (e) {
        console.error("Provinces fetch failed:", e);
    }
}

async function fetchModalCities(appId, provCode, savedCity = '', savedBrgy = '') {
    const citySel = document.getElementById(`city_${appId}`);
    const brgySel = document.getElementById(`brgy_${appId}`);
    if (!citySel || !brgySel) return;

    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';

    try {
        // FIXED: standard static JSON routes
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities.json`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;

        if (savedCity) {
            // FIXED: Support matching by either text or numeric code format safely
            let cityOpt = Array.from(citySel.options).find(opt => 
                opt.text.toUpperCase() === savedCity.toUpperCase() || 
                opt.value === savedCity
            );
            if (cityOpt) {
                citySel.value = cityOpt.value;
                await fetchModalBarangays(appId, cityOpt.value, savedBrgy);
            }
        }
    } catch (e) {
        console.error("Cities fetch failed:", e);
    }
}

async function fetchModalBarangays(appId, cityCode, savedBrgy = '') {
    const brgySel = document.getElementById(`brgy_${appId}`);
    if (!brgySel) return;

    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    try {
        // FIXED: standard static JSON routes
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays.json`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;

        if (savedBrgy) {
            // FIXED: Support matching by either text or numeric code format safely
            let brgyOpt = Array.from(brgySel.options).find(opt => 
                opt.text.toUpperCase() === savedBrgy.toUpperCase() || 
                opt.value === savedBrgy
            );
            if (brgyOpt) {
                brgySel.value = brgyOpt.value;
            }
        }
    } catch (e) {
        console.error("Barangays fetch failed:", e);
    }
}
</script>

<style>
/* Fixed backdrop positioning bug caused by CSS transforms/stacking context in table lists */
#testModal{{ $app->id }}.show {
    background-color: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(4px);
}
#modalValidationError_{{ $app->id }}.show {
    background-color: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(2px);
}

/* Oxford Blue & Shamrock Green Stepper / Checkbox High-Contrast Theming */

/* Main Navigation Tab overrides */
#mainTab_{{ $app->id }} .nav-link {
    color: var(--text-muted) !important;
    border: 1px solid transparent !important;
    background-color: transparent !important;
    transition: all 0.2s ease-in-out;
}
#mainTab_{{ $app->id }} .nav-link:hover {
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}
#mainTab_{{ $app->id }} .nav-link.active {
    color: var(--brand-accent) !important;
    background-color: rgba(25, 211, 140, 0.05) !important;
    border-color: var(--border-color) !important;
    border-bottom-color: transparent !important;
}
#mainTab_{{ $app->id }} .nav-link.active[onclick*="retest"] {
    color: #ffc107 !important;
    background-color: rgba(255, 193, 7, 0.05) !important;
}

/* Sub-Tabs (Pills) styling */
.modal-sub-tab-btn-{{ $app->id }} {
    color: var(--text-muted) !important;
    border: 1.5px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
}
.modal-sub-tab-btn-{{ $app->id }}:hover {
    color: var(--text-main) !important;
    border-color: var(--brand-accent) !important;
}
.modal-sub-tab-btn-{{ $app->id }}.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
    font-weight: 700;
    box-shadow: 0 0 10px rgba(25, 211, 140, 0.2);
}

/* Test Checklist Container High-Contrast Mapping */
.test-checkbox-item {
    background-color: var(--bg-card) !important;
    border: 1.5px solid var(--border-color) !important;
    color: var(--text-main) !important;
    border-radius: 8px;
    padding: 10px 12px;
    transition: all 0.2s ease-in-out;
}
.test-checkbox-item label {
    color: var(--text-main) !important;
    font-weight: 500;
    cursor: pointer;
}
.test-checkbox-item:hover {
    border-color: var(--brand-accent) !important;
    background-color: rgba(25, 211, 140, 0.03) !important;
}
/* Selected state: mapped text color to high-contrast theme variable */
.test-checkbox-item:has(input:checked) {
    background-color: rgba(25, 211, 140, 0.08) !important;
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 8px rgba(25, 211, 140, 0.15);
}
.test-checkbox-item:has(input:checked) label {
    color: var(--text-main) !important;
    font-weight: 700;
}

/* Selected List High Contrast Display box */
#modal_selected_tests_list_{{ $app->id }} {
    background-color: var(--bg-card) !important;
    border: 1.5px solid var(--border-color) !important;
    color: var(--text-main) !important;
}
</style>