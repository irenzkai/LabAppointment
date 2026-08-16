@php 
// Detect if the appointment is already tested/validated to lock out redundant "Progress to Tested" options
$isAlreadyTested = in_array($app->status, ['tested', 'encoded', 'released']);  

// Calculate confirmed price: Use positive stored payment_amount or fallback to calculated service sum
$confirmedPrice = ($app->payment_amount && floatval($app->payment_amount) > 0) 
    ? floatval($app->payment_amount) 
    : floatval($app->totalPrice());
@endphp

<!-- MAIN SAMPLING & RETEST VERIFICATION MODAL -->
<div class="modal fade" id="testModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index: 1050;">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<form id="markTestedForm_{{ $app->id }}" action="{{ route('appointments.tested', $app->id) }}" method="POST" onsubmit="return validateModalForm({{ $app->id }}, event)" class="modal-content border-secondary bg-card text-start" style="background-color: var(--bg-card); color: var(--text-main);" novalidate>
@csrf
@method('PATCH')

<!-- Hidden action trigger: progresses to 'tested' or 'retest' -->
<input type="hidden" name="action" id="action_{{ $app->id }}" value="{{ $isAlreadyTested ? 'retest' : 'tested' }}"> 

<!-- Hidden snapshot inputs to satisfy controller validation while keeping modal UI read-only -->
<input type="hidden" name="patient_first_name" value="{{ $app->patient_first_name }}">
<input type="hidden" name="patient_middle_name" value="{{ $app->patient_middle_name }}">
<input type="hidden" name="patient_last_name" value="{{ $app->patient_last_name }}">
<input type="hidden" name="patient_suffix" value="{{ $app->patient_suffix }}">
<input type="hidden" name="patient_birthdate" value="{{ $app->patient_birthdate ? $app->patient_birthdate->format('Y-m-d') : '' }}">
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

<div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3 d-flex flex-column align-items-stretch">
<div class="d-flex justify-content-between align-items-center {{ !$isAlreadyTested ? 'mb-3' : '' }}">
<h5 class="modal-title text-accent fw-bold uppercase small m-0" id="modal_title_{{ $app->id }}">
<i class="bi bi-shield-check-fill me-2 fs-5"></i>{{ $isAlreadyTested ? 'Clinical Retest Request Hub' : 'Clinical Sampling & Verification Hub' }}
</h5>
<button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<!-- MAIN WORKFLOW TABS (No default blue styling) -->
@if(!$isAlreadyTested)
<ul class="nav nav-pills nav-fill gap-2" id="mainTab_{{ $app->id }}" role="tablist">
<li class="nav-item" role="presentation">
<button class="nav-link active fw-bold text-uppercase py-2 rounded-3" id="tested-tab-btn-{{ $app->id }}" data-bs-toggle="pill" type="button" onclick="setModalMainAction({{ $app->id }}, 'tested')" style="font-size: 0.8rem;">
<i class="bi bi-person-check-fill me-1"></i> 1. Progress to Tested
</button>
</li>
<li class="nav-item" role="presentation">
<button class="nav-link fw-bold text-uppercase py-2 rounded-3" id="retest-tab-btn-{{ $app->id }}" data-bs-toggle="pill" type="button" onclick="setModalMainAction({{ $app->id }}, 'retest')" style="font-size: 0.8rem;">
<i class="bi bi-arrow-repeat me-1"></i> 2. Return for Retest
</button>
</li>
</ul>
@endif
</div>

<div class="modal-body p-4">

<!-- READ-ONLY "CONFIRM DETAILS" SUMMARY PANEL WITH SERVICE PRICE -->
<div class="card p-3 border-secondary border-opacity-25 bg-secondary bg-opacity-10 mb-4 text-start" style="background-color: rgba(0, 0, 0, 0.02) !important;">
<div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary border-opacity-10 pb-2">
<h6 class="text-accent fw-bold mb-0 uppercase small">
<i class="bi bi-person-check-fill me-1.5"></i>Confirm Details
</h6>
<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 uppercase" style="font-size: 0.65rem;">
Ref: #{{ $app->id }}
</span>
</div>

<div class="row g-2 text-start">
<div class="col-md-6">
<small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Patient Name</small>
<div class="text-main fw-bold small mb-2">{{ strtoupper($app->patient_name) }}</div>

<small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Age / Sex / Birthdate</small>
<div class="text-main small mb-2">
{{ $app->patient_age }} Yrs <span class="mx-1">|</span> {{ strtoupper($app->patient_sex) }} <span class="mx-1">|</span> {{ $app->patient_birthdate ? $app->patient_birthdate->format('M d, Y') : 'N/A' }}
</div>

<small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Contact Phone</small>
<div class="text-main small mb-2">{{ $app->patient_phone }}</div>
</div>

<div class="col-md-6">
<small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Residential Address</small>
<div class="text-main small mb-2">{{ $app->patient_address }}</div>

<small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Requested Examinations</small>
<div class="d-flex flex-wrap gap-1.5 mb-2">
@foreach($app->services as $s)
<span class="badge border border-secondary border-opacity-25 text-accent uppercase px-2 py-1" style="background-color: rgba(25, 211, 140, 0.05); font-size: 0.7rem;">
{{ $s->name }}
</span>
@endforeach
</div>

{{-- Confirmed Price fetched dynamically from Service Price --}}
<small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Confirmed Price / Total Bill</small>
<div class="text-accent fw-bold fs-6 mb-2">
₱{{ number_format($confirmedPrice, 2) }}
</div>
</div>
</div>

{{-- Redirection Button to Edit Patient Details on a Dedicated Page --}}
<div class="mt-3 pt-2 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center flex-wrap gap-2">
<small class="text-muted italic" style="font-size: 0.75rem;">Need to revise patient demographics, address, or tests?</small>
<a href="{{ route('appointments.edit-details', $app->id) }}" class="btn btn-sm btn-outline-accent fw-bold uppercase py-1 px-3" style="font-size: 0.75rem;">
<i class="bi bi-pencil-square me-1"></i> Edit Details (Identity / Address / Tests)
</a>
</div>
</div>

<!-- PROCESSING PARAMETERS INPUT SECTION -->

<!-- PANE 1: PROGRESS TO TESTED (TIME ESTIMATION ONLY) -->
<div id="tested_process_fields_{{ $app->id }}">
<h6 class="text-accent mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">Processing Time Estimation</h6>
<div class="row g-3 align-items-center">
<div class="col-md-6">
<label class="small text-secondary fw-bold mb-1 uppercase">Estimated Hours</label>
<input type="number" name="est_hours" id="est_hours_{{ $app->id }}" class="form-control py-2" min="0" placeholder="0" value="{{ $app->result_estimated_at ? Carbon\Carbon::parse($app->tested_at)->diffInHours($app->result_estimated_at) : '0' }}">
</div>
<div class="col-md-6">
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

<!-- PANE 2: RETEST REQUEST -->
<div id="retest_process_fields_{{ $app->id }}" class="d-none">
<h6 class="text-warning mb-3 small fw-bold uppercase border-bottom border-secondary border-opacity-10 pb-2">Retesting Criteria</h6>
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

document.addEventListener('DOMContentLoaded', () => {
const modalEl = document.getElementById(`testModal${appId}`);
if (modalEl) {
modalEl.addEventListener('show.bs.modal', () => {
@if($isAlreadyTested)
setModalMainAction(appId, 'retest');
@endif
});
}
});
})();

// CHANNELS TAB CONTROLLER (Tested vs Retest)
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
if (selectRetest) selectRetest.removeAttribute('required');
} else {
testPane.classList.add('d-none');
retestPane.classList.remove('d-none');
submitBtn.innerText = "CONFIRM RETEST EXCEPTION";
submitBtn.className = "btn-custom btn-outline-danger py-2 px-4 fw-bold uppercase";
if (selectRetest) selectRetest.setAttribute('required', 'required');
}
}

// TOGGLE CUSTOM RETEST DETAILS
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

// CLIENT-SIDE VALIDATION EXCEPTION CHECKER
function validateModalForm(appId, event) {
const form = document.getElementById(`markTestedForm_${appId}`);
const action = document.getElementById(`action_${appId}`).value;
let errors = [];

const payAmount = form.querySelector('[name="payment_amount"]').value;
if (!payAmount || parseFloat(payAmount) < 0) {
errors.push("Confirmed Collection Amount must be a valid positive number.");
}

if (action === 'retest') {
const reason = document.getElementById(`retest_reason_select_${appId}`).value;
if (!reason) {
errors.push("Please select a valid return justification for retesting.");
} else if (reason === 'Others') {
const customReason = document.getElementById(`retest_custom_reason_${appId}`).value.trim();
if (!customReason || customReason.length < 5) {
errors.push("Custom Retest Reason is required (min 5 characters).");
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
const errModalEl = document.getElementById(`modalValidationError_${appId}`);
const errModal = new bootstrap.Modal(errModalEl);
errModal.show();

return false;
}

return true;
}
</script>

<style>
/* High-contrast Header Tab Button Overrides (Removes Default Bootstrap Blue) */
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