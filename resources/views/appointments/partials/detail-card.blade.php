@php
$statusPriority = [
 'expired' => 1,
 'returned' => 2,
 'retest' => 3,
 'canceled' => 4,
 'pending' => 5,
 'approved' => 6,
 'tested' => 7,
 'encoded' => 8,
 'released' => 9,
];
if ($app->batch_id) {
 $batchAppsQuery = \App\Models\Appointment::where('batch_id', $app->batch_id);
 if (auth()->check() && auth()->user()->isPatient()) {
 $batchAppsQuery->where('deleted_by_patient', false);
 }
 $batchApps = $batchAppsQuery->get();
 $lowestPriority = 999;
 $lowestStatus = $app->status;
 foreach ($batchApps as $subApp) {
 $effStatus = $subApp->isExpired() ? 'expired' : $subApp->status;
 $priority = $statusPriority[$effStatus] ?? 99;
 if ($priority < $lowestPriority) {
 $lowestPriority = $priority;
 $lowestStatus = $effStatus;
 }
 }
 $isExpired = ($lowestStatus === 'expired');
 $finalStatus = $lowestStatus;
} else {
 $isExpired = $app->isExpired();
 $finalStatus = $isExpired ? 'expired' : $app->status;
}
$statusColor = match($finalStatus) {
 'expired' => 'danger',
 'pending' => 'warning',
 'approved' => 'info',
 'tested' => 'info',
 'encoded' => 'info',
 'released' => 'accent',
 'returned' => 'danger',
 'retest' => 'danger',
 'canceled' => 'danger',
 default => 'secondary'
};
$statusLabel = strtoupper($finalStatus);
@endphp
@if($app->batch_id)
{{-- =========================================================================
 A. BULK BATCH WORKSPACE
========================================================================= --}}
@php
$batchAppointmentsQuery = \App\Models\Appointment::with(['services', 'result', 'user'])
 ->where('batch_id', $app->batch_id);
if (auth()->check() && auth()->user()->isPatient()) {
 $batchAppointmentsQuery->where('deleted_by_patient', false);
}
$batchAppointments = $batchAppointmentsQuery->get()
 ->sortBy(function($appointment) {
 return match($appointment->status) {
 'returned' => 1,
 'pending' => 2,
 'approved' => 3,
 'retest' => 4,
 'tested' => 5,
 'encoded' => 6,
 'released' => 7,
 default => 8
 };
 });
$batchTotal = $batchAppointments->sum(fn($a) => $a->totalPrice());
$paymentProviders = $paymentProviders ?? \App\Models\PaymentProvider::where('is_active', true)->get();
$anyApproved = $batchAppointments->contains(fn($appointment) => in_array($appointment->status, ['approved', 'retest', 'tested', 'encoded', 'released']));
// Cancel entire batch is only permitted if pending/approved/returned AND no patient has progressed to retest/tested/encoded/released
$canCancelBatch = $batchAppointments->contains(fn($a) => in_array($a->status, ['pending', 'approved', 'returned'])) 
 && !$batchAppointments->contains(fn($a) => in_array($a->status, ['retest', 'tested', 'encoded', 'released']));
$isStaff = !empty($is_staff) && auth()->check() && auth()->user()->isEmployee();
@endphp
<div id="details-{{ $app->id }}" class="appointment-detail-pane card border-secondary bg-card p-4 d-none animate-page">
 {{-- Batch Header --}}
 <div class="border-bottom border-secondary border-opacity-25 pb-3 mb-4 text-start">
 <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
 <div>
 <h4 class="fw-bold mb-1 uppercase tracking-tighter" style="color: var(--text-main) !important;">
 {{ $app->organization_name }}
 </h4>
 <div class="text-secondary smaller fw-bold uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
 Batch ID: #{{ $app->batch_id }} <span class="mx-2">|</span> 
 Schedule: {{ $app->appointment_date->format('M d, Y') }} <span class="mx-2">|</span> 
 Total PAX: {{ $batchAppointments->count() }}
 </div>
 </div>
 <div class="text-end">
 <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} border border-{{ $statusColor }} border-opacity-25 px-3 py-2 fs-6 uppercase">
 {{ $statusLabel }}
 </span>
 </div>
 </div>
 {{-- Patient-Side Batch Cancel Trigger (Only if not in retest/tested/encoded/released) --}}
 @if(!$isStaff && $canCancelBatch && Auth::id() == $app->user_id)
 <div class="mt-3 text-end">
 <button type="button" class="btn btn-outline-danger btn-sm fw-bold uppercase py-2 px-3" data-bs-toggle="modal" data-bs-target="#cancelBatchModal{{ $app->id }}">
 <i class="bi bi-x-circle me-1"></i> Cancel Entire Batch
 </button>
 </div>
 @endif
 </div>
 {{-- Consolidated Batch Level Controls for Staff (Master Queue Only) --}}
 @if($isStaff)
 @php
 $showConfirm = ($app->payment_status === 'unpaid' && $app->payment_method === 'Cashless');
 $showRevoke = ($app->status === 'pending' && $app->payment_method === 'Cashless' && $app->payment_status === 'paid');
 $showReturn = ($app->status === 'pending' && !$anyApproved);
 $showApprove = ($app->status === 'pending');
 $isBatchApproveDisabled = ($app->payment_method === 'Cashless' && $app->payment_status !== 'paid');
 $showBatchControls = $showConfirm || $showRevoke || $showReturn || $showApprove;
 @endphp
 @if($showBatchControls)
 <div class="batch-controls-panel card p-4 mb-4 text-start animate-page">
 <div class="d-flex justify-content-between align-items-center mb-3">
 <h6 class="text-accent fw-bold small uppercase mb-0">
 <i class="bi bi-shield-lock-fill me-1.5"></i>Batch Controls (One-Time Payment)
 </h6>
 </div>
 {{-- Consolidated Single Cashless Receipt Preview --}}
 @if($app->payment_method === 'Cashless')
 <div class="border rounded p-3 mb-3 text-start border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.03);">
 <div class="d-flex justify-content-between align-items-center mb-2.5">
 <span class="small text-muted fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Batch Proof of Payment:</span>
 <span class="badge {{ $app->payment_status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }} px-2 py-1 small">
 {{ strtoupper($app->payment_status) }}
 </span>
 </div>
 @if($app->payment_receipt)
 @php $batchPayExt = pathinfo($app->payment_receipt, PATHINFO_EXTENSION); @endphp
 <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-1 cursor-pointer" style="cursor: pointer; max-width: 260px;" onclick="openFilePreview('{{ Storage::url($app->payment_receipt) }}', 'Batch Proof of Payment')" title="Click to view full screen">
 <i class="bi bi-file-earmark-image-fill text-accent display-6"></i>
 <div class="text-start">
 <div class="fw-bold small text-dark text-truncate" style="max-width: 180px;">Batch_Proof_of_Payment{{ $batchPayExt ? '.'.strtolower($batchPayExt) : '' }}</div>
 <span class="text-muted smaller"><i class="bi bi-zoom-in text-accent"></i> Click to Zoom</span>
 </div>
 </div>
 @else
 <div class="alert alert-clinical border-danger text-danger text-center p-2 small mb-0">
 <i class="bi bi-exclamation-triangle-fill me-1"></i> No payment receipt uploaded.
 </div>
 @endif
 </div>
 @endif
 <div class="d-flex gap-2 flex-wrap">
 @if($showConfirm)
 <button type="button" class="btn-custom btn-accent py-2 fw-bold uppercase shadow-sm small flex-grow-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#confirmBatchPaymentModal{{ $app->id }}">
 <i class="bi bi-patch-check-fill me-1"></i> Confirm Batch Payment
 </button>
 @endif
 @if($showRevoke)
 <button type="button" class="btn-custom btn-outline-danger py-2 fw-bold uppercase shadow-sm small flex-grow-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#revokeBatchPaymentModal{{ $app->id }}">
 <i class="bi bi-arrow-counterclockwise me-1"></i> Revoke Batch Payment
 </button>
 @endif
 @if($showReturn)
 <button type="button" class="btn-custom btn-danger-custom py-2 px-3 fw-bold uppercase small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#returnBatchModal{{ $app->id }}">
 <i class="bi bi-x-circle me-1"></i> Return Entire Batch
 </button>
 @endif
 @if($showApprove)
 <button type="button" class="btn-custom btn-neon py-2 px-3 fw-bold uppercase small {{ $isBatchApproveDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isBatchApproveDisabled ? 'disabled title="Batch cashless payment must be confirmed before approval"' : '' }} data-bs-toggle="modal" data-bs-target="#approveBatchModal{{ $app->id }}" style="font-size: 0.75rem;">
 <i class="bi bi-check-circle me-1"></i> Approve Batch
 </button>
 @endif
 </div>
 </div>
 @endif
 @endif
 {{-- Live Search & Batch Statistics --}}
 <div class="row g-3 mb-4 align-items-center text-start">
 <div class="col-md-7">
 <div class="input-group input-group-sm">
 <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary">
 <i class="bi bi-search"></i>
 </span>
 <input type="text" class="form-control" placeholder="Search patient name in this batch..." oninput="filterBatchPatients('{{ $app->batch_id }}', this.value)">
 </div>
 </div>
 <div class="col-md-5 text-md-end">
 <div class="text-accent fw-bold small" style="font-size: 0.85rem;">BATCH TOTAL: {{ number_format($batchTotal, 2) }} PHP</div>
 </div>
 </div>
 {{-- Scrollable Patient Deck --}}
 <div class="d-flex flex-column gap-3 overflow-auto custom-scroll pe-1 mb-2" style="max-height: 480px;">
 @foreach($batchAppointments as $subApp)
 @php
 $isSubExpired = $subApp->isExpired();
 $subBadgeColor = $isSubExpired ? 'danger' : match($subApp->status) {
 'pending' => 'warning',
 'approved' => 'info',
 'tested' => 'info',
 'encoded' => 'info',
 'released' => 'accent',
 'returned' => 'danger',
 'retest' => 'danger',
 default => 'secondary'
 };
 $subBadgeLabel = $isSubExpired ? 'EXPIRED' : strtoupper($subApp->status);
 @endphp
 <div class="card p-3 border-secondary border-opacity-25 bg-card bulk-patient-row-{{ $app->batch_id }} text-start" data-name="{{ strtoupper($subApp->patient_name) }}">
 <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
 <div>
 <span class="text-main fw-bold small uppercase me-2" style="font-size: 0.9rem;">{{ $subApp->patient_name }}</span>
 <small class="text-secondary" style="font-size: 0.65rem;">
 {{ $subApp->patient_age }} YRS <span class="mx-1">|</span> {{ strtoupper($subApp->patient_sex) }} <span class="mx-1">|</span> ID: #{{ $subApp->id }}
 </small>
 </div>
 <div class="d-flex gap-1.5 align-items-center">
 <span class="badge border border-{{ $subBadgeColor }} text-{{ $subBadgeColor == 'accent' ? 'success' : $subBadgeColor }} uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
 {{ $subBadgeLabel }}
 </span>
 </div>
 </div>
 {{-- Patient Tests List --}}
 <div class="text-start border-bottom border-secondary border-opacity-10 pb-2 mb-3">
 <small class="text-accent fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem;">Tests Requested:</small>
 <div class="text-main small" style="font-size: 0.8rem;">
 {{ $subApp->services->pluck('name')->implode(', ') }} 
 <span class="text-muted ms-2">({{ number_format($subApp->totalPrice(), 2) }} PHP)</span>
 </div>
 </div>
 {{-- Individual Patient Workflows --}}
 <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
 <div class="small text-muted" style="font-size: 0.7rem;">
 <i class="bi bi-clock-history me-1"></i>Scheduled: {{ $subApp->appointment_date->format('M d, Y') }} | {{ date('h:i A', strtotime($subApp->time_slot)) }}
 </div>
 <div class="bulk-actions-container">
 @if($subApp->status == 'released')
 @php
 $isCoordinatorOnly = (!$isStaff && auth()->id() === $app->user_id && $subApp->patient_email !== auth()->user()->email);
 @endphp
 @if($isCoordinatorOnly)
 <button class="btn-custom btn-accent btn-sm py-1.5 px-4" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}">
 <i class="bi bi-send me-1.5"></i>FORWARD RESULT
 </button>
 @else
 <div class="d-flex gap-1.5 align-items-center">
 @if($isStaff)
 <button type="button" class="btn-custom btn-outline-accent btn-sm py-1.5 px-3 fw-bold" onclick="promptAccess('{{ $subApp->id }}', 'hub', 'edit')">
 <i class="bi bi-shield-lock-fill me-1"></i>VIEW RESULTS HUB
 </button>
 <button class="btn-custom btn-outline-accent btn-sm py-1.5 px-2.5" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}" title="Forward Results to Email">
 <i class="bi bi-send"></i>
 </button>
 @else
 <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase hover-dark-text" style="font-size: 0.7rem; border-color: var(--brand-accent) !important;">
 <i class="bi bi-file-earmark-pdf"></i> PREVIEW
 </a>
 <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'download']) }}" class="btn-custom btn-accent btn-sm py-1.5 px-2.5" style="font-size: 0.7rem; color: #1c232d !important;">DOWNLOAD</a>
 <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase hover-dark-text" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}" title="Forward Results to Email">
 <i class="bi bi-send me-1"></i> FORWARD
 </button>
 @endif
 </div>
 @endif
 @else
 @include('appointments.partials.actions', ['app' => $subApp, 'is_staff' => $isStaff])
 @endif
 </div>
 </div>
 </div>
 @endforeach
 </div>
</div>
{{-- =========================================================================
 BATCH MODALS (Patient Cancel & Staff Action Modals)
========================================================================= --}}
{{-- 1. Patient Batch Cancel Modal --}}
@if(!$isStaff && $canCancelBatch && Auth::id() == $app->user_id)
<div class="modal fade" id="cancelBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
 <form action="{{ route('appointments.cancel', $app->id) }}" method="POST" class="modal-content shadow-lg border-0" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
 @csrf
 <input type="hidden" name="batch" value="true">
 <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
 <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
 <i class="bi bi-x-circle-fill fs-5"></i>
 <span>Cancel Entire Batch?</span>
 </h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body p-4 text-start">
 <p class="small text-main mb-3">Are you sure you want to cancel all appointments in <strong>{{ $app->organization_name }} (Batch #{{ $app->batch_id }})</strong>?</p>
 <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-warning p-3 rounded-3 mb-0 smaller">
 <i class="bi bi-info-circle-fill me-1"></i> This action will cancel all active patient appointments registered under this corporate batch.
 </div>
 </div>
 <div class="modal-footer p-3 border-top border-secondary border-opacity-10 d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card);">
 <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Go Back</button>
 <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">Cancel Entire Batch</button>
 </div>
 </form>
 </div>
</div>
@endif
@if($isStaff)
{{-- 2. Staff Confirm Batch Payment Modal --}}
<div class="modal fade" id="confirmBatchPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered">
 <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
 <div class="modal-header border-secondary border-bottom border-opacity-10 py-3">
 <h5 class="modal-title text-accent fw-bold uppercase small m-0">Confirm Batch Payment?</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body p-4 text-start">
 <p class="small mb-0 text-muted" style="color: var(--text-main) !important;">
 Confirm manual receipt of full batch payment for <strong>{{ $app->organization_name }} (Batch #{{ $app->batch_id }})</strong>? This will flag all <strong>{{ $batchAppointments->count() }} patients</strong> as <strong class="text-success">PAID</strong>.
 </p>
 </div>
 <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
 <div class="d-flex w-100">
 <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
 <form action="{{ route('appointments.confirm-payment', $app->id) }}" method="POST" class="w-50 m-0">
 @csrf
 <input type="hidden" name="payment_status" value="paid">
 <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Confirm Batch</button>
 </form>
 </div>
 </div>
 </div>
 </div>
</div>
{{-- 3. Staff Revoke Batch Payment Modal --}}
<div class="modal fade" id="revokeBatchPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered">
 <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
 <div class="modal-header border-danger border-bottom border-opacity-10 py-3">
 <h5 class="modal-title text-danger fw-bold uppercase small m-0">Revoke Batch Payment?</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body p-4 text-start">
 <p class="small mb-0 text-muted" style="color: var(--text-main) !important;">
 Revert payment status for <strong>{{ $app->organization_name }} (Batch #{{ $app->batch_id }})</strong> back to <strong class="text-danger fw-bold">UNPAID</strong>?
 </p>
 </div>
 <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
 <div class="d-flex w-100">
 <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
 <form action="{{ route('appointments.confirm-payment', $app->id) }}" method="POST" class="w-50 m-0">
 @csrf
 <input type="hidden" name="payment_status" value="unpaid">
 <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Revoke Batch</button>
 </form>
 </div>
 </div>
 </div>
 </div>
</div>
{{-- 4. Staff Return Entire Batch Modal --}}
<div class="modal fade" id="returnBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered">
 <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content shadow-lg return-form-element" data-app-id="{{ $app->id }}" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
 @csrf
 @method('PATCH')
 <input type="hidden" name="status" value="returned">
 <input type="hidden" name="batch" value="true">
 <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
 <h5 class="modal-title text-danger fw-bold uppercase small m-0">Return Entire Batch</h5>
 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body p-4 text-start">
 <div class="mb-3">
 <label for="return_reason_select_batch_{{ $app->id }}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Reason for Return</label>
 <select id="return_reason_select_batch_{{ $app->id }}" name="return_reason" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
 <option value="" disabled selected>-- Select a return reason --</option>
 <option value="Mismatched identification documents">Mismatched identification documents</option>
 <option value="Incorrect or incomplete personal details">Incorrect or incomplete personal details</option>
 <option value="No payment received / pending verification">No payment received / pending verification</option>
 <option value="Invalid test selection for patient demographics">Invalid test selection for patient demographics</option>
 <option value="Discrepancy in schedule / date selection">Discrepancy in schedule / date selection</option>
 <option value="Others">Others (Specify details below)</option>
 </select>
 </div>
 <div id="custom_return_reason_wrapper_batch_{{ $app->id }}" class="mb-3 d-none">
 <label for="return_reason_batch_{{ $app->id }}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
 <textarea name="return_reason" id="return_reason_batch_{{ $app->id }}" class="form-control shadow-none return-reason-textarea" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="4" placeholder="Identify the specific correction needed..."></textarea>
 </div>
 <div class="mt-2"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
 </div>
 <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
 <div class="d-flex w-100">
 <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
 <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Send Batch Return</button>
 </div>
 </div>
 </form>
 </div>
</div>
{{-- 5. Staff Approve Entire Batch Modal --}}
<div class="modal fade" id="approveBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered">
 <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
 @csrf
 @method('PATCH')
 <input type="hidden" name="status" value="approved">
 <input type="hidden" name="batch" value="true">
 <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
 <h5 class="modal-title text-accent fw-bold uppercase small m-0">Approve Entire Batch?</h5>
 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body p-4 text-start">
 <p class="small text-main mb-0">
 Approve all <strong>{{ $batchAppointments->count() }} patient appointments</strong> for <strong>{{ $app->organization_name }} (Batch #{{ $app->batch_id }})</strong>? This will progress all pending records to approved status.
 </p>
 </div>
 <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
 <div class="d-flex w-100">
 <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
 <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Approve Batch</button>
 </div>
 </div>
 </form>
 </div>
</div>
@endif
{{-- 6. Forward Result Modals for All Sub-Appointments in Batch --}}
@foreach($batchAppointments as $subApp)
 @if($subApp->status == 'released')
 @php
 $subTargetEmail = $subApp->patient_email ?: ($subApp->user?->email ?? '');
 @endphp
 <div class="modal fade" id="forwardModal{{ $subApp->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
 <form action="{{ route('appointments.forward-email', $subApp->id) }}" method="POST" class="modal-content shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);" id="forwardForm{{ $subApp->id }}">
 @csrf
 <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
 <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
 <i class="bi bi-send-fill text-accent fs-5"></i>
 <span>Forward Clinical Results</span>
 </h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body p-4">
 <p class="small text-muted mb-3">
 Send encrypted, password-protected PDF results for <strong>{{ strtoupper($subApp->patient_name) }}</strong> (Ref #{{ $subApp->id }}).
 </p>
 {{-- Recipient Email Display Box --}}
 <div class="p-3 rounded border border-secondary border-opacity-15 mb-3" style="background-color: rgba(25, 211, 140, 0.04);">
 <div class="d-flex justify-content-between align-items-center">
 <small class="text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Recipient Email Address:</small>
 @if($isStaff)
 <button type="button" class="btn btn-link text-accent fw-bold p-0 text-decoration-none smaller" onclick="toggleForwardEmailEdit('{{ $subApp->id }}')">
 <i class="bi bi-pencil-square me-1"></i>Change Email
 </button>
 @endif
 </div>
 <div class="fw-bold text-main fs-6 mt-1 text-truncate" id="forward_email_display_{{ $subApp->id }}">
 {{ $subTargetEmail ?: 'No email on file' }}
 </div>
 </div>
 @if($isStaff)
 {{-- Hidden Email Editing & Reason Section for Admin/Staff in Master Queue --}}
 <div id="forward_email_edit_section_{{ $subApp->id }}" class="d-none border-top border-secondary border-opacity-15 pt-3 mb-3">
 <div class="mb-3">
 <label class="smaller text-secondary fw-bold mb-1 uppercase d-block">New Target Email Address</label>
 <input type="email" name="target_email" id="target_email_{{ $subApp->id }}" class="form-control" value="{{ $subTargetEmail }}" placeholder="recipient@example.com">
 </div>
 <div class="mb-3">
 <label class="smaller text-secondary fw-bold mb-1 uppercase d-block">Reason for Email Update (Audit Log)</label>
 <select name="reason" id="forward_reason_select_{{ $subApp->id }}" class="form-select" onchange="toggleForwardReason('{{ $subApp->id }}', this.value)">
 <option value="" disabled selected>-- Select a reason --</option>
 <option value="Patient requested delivery to an alternate email address">Patient requested delivery to an alternate email address</option>
 <option value="Correction of typographical email error">Correction of typographical email error</option>
 <option value="Updating batch member direct contact email">Updating batch member direct contact email</option>
 <option value="Others">Others (Specify below)</option>
 </select>
 </div>
 <div id="forward_custom_reason_wrapper_{{ $subApp->id }}" class="mb-3 d-none">
 <label class="smaller text-secondary fw-bold mb-1 uppercase d-block">Specify Custom Reason</label>
 <textarea name="custom_reason" id="forward_custom_reason_{{ $subApp->id }}" class="form-control" rows="2" placeholder="Provide clinical justification for email update..."></textarea>
 </div>
 </div>
 @endif
 {{-- Security Disclaimer Box --}}
 <div class="alert alert-clinical border-secondary bg-secondary bg-opacity-10 p-2.5 rounded-3 mb-0 smaller text-muted">
 <div class="d-flex align-items-center gap-2 mb-1 text-main fw-bold">
 <i class="bi bi-shield-lock-fill text-accent"></i>
 <span>Protected Medical Document</span>
 </div>
 The attached PDFs are protected by encryption. The password pattern is <strong>MMDDYYYY + Capitalized Initials</strong>.
 </div>
 </div>
 <div class="modal-footer p-3 border-top border-secondary border-opacity-10 d-flex gap-2">
 <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold uppercase" data-bs-dismiss="modal">Cancel</button>
 <button type="submit" class="btn btn-accent flex-grow-1 py-2 fw-bold uppercase shadow-sm">
 <i class="bi bi-send-fill me-1"></i> Confirm & Send
 </button>
 </div>
 </form>
 </div>
 </div>
 @endif
@endforeach
@else
{{-- =========================================================================
 B. INDIVIDUAL / FAMILY WORKSPACE
========================================================================= --}}
@php
$isStaff = !empty($is_staff) && auth()->check() && auth()->user()->isEmployee();
@endphp
<div id="details-{{ $app->id }}" class="appointment-detail-pane card border-secondary bg-card p-4 d-none animate-page">
 {{-- Detailed Header Section --}}
 <div class="border-bottom border-secondary border-opacity-25 pb-3 mb-4 text-start">
 <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 text-start">
 <div>
 <h4 class="fw-bold mb-1 uppercase tracking-tighter" style="color: var(--text-main) !important;">
 {{ $app->patient_name }}
 </h4>
 <div class="text-secondary smaller fw-bold uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
 {{ $app->patient_age }} Years Old <span class="mx-2">|</span> 
 {{ strtoupper($app->patient_sex) }} <span class="mx-2">|</span>
 REF: #{{ $app->id }}
 </div>
 </div>
 <div class="text-end">
 <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} border border-{{ $statusColor }} border-opacity-25 px-3 py-2 fs-6 uppercase">
 {{ $statusLabel }}
 </span>
 </div>
 </div>
 </div>
 <div class="row g-4 text-start">
 {{-- Left Grid column: Items breakdown --}}
 <div class="col-md-7 border-end border-secondary border-opacity-25">
 <h6 class="text-accent small fw-bold uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;"><i class="bi bi-flask me-2"></i>Laboratory Request Breakdown</h6>
 <ul class="list-group list-group-flush border border-secondary border-opacity-25 rounded bg-transparent mb-3">
 @foreach($app->services as $service)
 <li class="list-group-item bg-transparent text-main small d-flex justify-content-between border-secondary border-opacity-10 py-2.5">
 <span>{{ strtoupper($service->name) }}</span>
 <span class="text-muted">₱{{ number_format($service->price, 2) }}</span>
 </li>
 @endforeach
 <li class="list-group-item text-accent fw-bold d-flex justify-content-between border-top border-secondary border-opacity-25 py-2.5" style="background-color: rgba(25, 211, 140, 0.05) !important;">
 <span>TOTAL BILLING</span>
 <span>₱{{ number_format($app->totalPrice(), 2) }}</span>
 </li>
 </ul>
 {{-- Released Clinical Results Links (Patient View Only) --}}
 @if($app->status == 'released' && $app->result && !$isStaff)
 <div class="mt-4 border-top border-secondary border-opacity-10 pt-3 text-start animate-page">
 <h6 class="text-accent small fw-bold uppercase mb-3"><i class="bi bi-file-earmark-medical me-2"></i>Released Clinical Results</h6>
 <div class="d-flex flex-column gap-2">
 @foreach($app->result->included_reports ?? ['lab'] as $type)
 @php
 $label = match($type) {
 'lab' => 'Laboratory Result Findings',
 'radio' => 'Radiology Report Findings',
 'drug' => 'Drug Test Screening Result',
 'med_cert' => 'Medical Certificate Clearance',
 default => strtoupper($type) . ' Result'
 };
 @endphp
 <div class="d-flex justify-content-between align-items-center p-2.5 border border-secondary border-opacity-10 rounded" style="background-color: rgba(0,0,0,0.015);">
 <span class="small text-main fw-semibold"><i class="bi bi-file-earmark-pdf text-accent me-1.5"></i>{{ $label }}</span>
 <div class="d-flex gap-1.5">
 <a href="{{ route('appointments.result.access', [$app->id, $type, 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase hover-dark-text" style="font-size: 0.7rem; border-color: var(--brand-accent) !important;">
 <i class="bi bi-file-earmark-pdf"></i> PREVIEW
 </a>
 <a href="{{ route('appointments.result.access', [$app->id, $type, 'download']) }}" class="btn btn-sm btn-accent py-1 px-2.5 small uppercase" style="font-size: 0.7rem; color: #1c232d !important;">
 <i class="bi bi-download"></i> DOWNLOAD
 </a>
 </div>
 </div>
 @endforeach
 @foreach($app->result->customWorkstationResults as $custom)
 @if($custom->status === 'verified')
 <div class="d-flex justify-content-between align-items-center p-2.5 border border-secondary border-opacity-10 rounded" style="background-color: rgba(0,0,0,0.015);">
 <span class="small text-main fw-semibold"><i class="bi bi-file-earmark-pdf text-accent me-1.5"></i>{{ $custom->name }}</span>
 <div class="d-flex gap-1.5">
 <a href="{{ route('appointments.result.access', [$app->id, 'custom_' . $custom->id, 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase hover-dark-text" style="font-size: 0.7rem; border-color: var(--brand-accent) !important;">
 <i class="bi bi-file-earmark-pdf"></i> PREVIEW
 </a>
 <a href="{{ route('appointments.result.access', [$app->id, 'custom_' . $custom->id, 'download']) }}" class="btn btn-sm btn-accent py-1 px-2.5 small uppercase" style="font-size: 0.7rem; color: #1c232d !important;">
 <i class="bi bi-download"></i> DOWNLOAD
 </a>
 </div>
 </div>
 @endif
 @endforeach
 </div>
 </div>
 @endif
 </div>
 {{-- Right Grid column: Metadata & Actions --}}
 <div class="col-md-5 d-flex flex-column justify-content-between">
 <div>
 <h6 class="text-accent small fw-bold uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;"><i class="bi bi-info-circle me-2"></i>Record Metadata</h6>
 <div class="mb-3">
 <small class="text-muted fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Schedule Date & Time:</small>
 <div class="text-main small">
 <i class="bi bi-calendar-event text-accent me-1"></i> {{ $app->appointment_date->format('M d, Y') }}<br>
 <i class="bi bi-clock text-accent me-1"></i> {{ date('h:i A', strtotime($app->time_slot)) }}
 </div>
 </div>
 <div class="mb-3">
 <small class="text-muted fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Patient Address:</small>
 <div class="text-main small">{{ $app->patient_address }}</div>
 </div>
 <div class="mb-3">
 <small class="text-muted fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Contact Number:</small>
 <div class="text-main small">{{ $app->patient_phone }}</div>
 </div>
 </div>
 {{-- Actions Panel --}}
 <div class="border-top border-secondary border-opacity-25 pt-3 mt-3">
 @include('appointments.partials.actions', ['app' => $app, 'is_staff' => $isStaff])
 {{-- Prominent Review & Forward Buttons for Staff (Master Queue Only) --}}
 @if($isStaff && $app->status === 'released')
 <div class="mt-3 d-flex gap-2">
 <button type="button" class="btn btn-sm btn-accent flex-grow-1 py-2 fw-bold text-uppercase" onclick="promptAccess('{{ $app->id }}', 'hub', 'edit')">
 <i class="bi bi-shield-check me-1.5"></i> Review / Edit Results Hub
 </button>
 <button type="button" class="btn btn-sm btn-outline-accent px-3 py-2 fw-bold text-uppercase hover-dark-text" style="border-color: var(--brand-accent) !important;" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $app->id }}" title="Forward Results to Email">
 <i class="bi bi-send"></i>
 </button>
 </div>
 @endif
 {{-- Forward Results to Email (Patient View) --}}
 @if(!$isStaff && $app->status == 'released' && (Auth::id() == $app->user_id || ($app->patient_email && strtolower(Auth::user()->email) === strtolower($app->patient_email))))
 <div class="mt-3">
 <button type="button" class="btn btn-sm btn-outline-accent w-100 py-2 fw-bold text-uppercase hover-dark-text" style="border-color: var(--brand-accent) !important;" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $app->id }}">
 <i class="bi bi-envelope-at me-1.5"></i> Forward Results to Email
 </button>
 </div>
 @endif
 </div>
 </div>
 </div>
</div>
{{-- Single Appointment Forward Modal --}}
@if($app->status == 'released')
@php
 $singleTargetEmail = $app->patient_email ?: ($app->user?->email ?? '');
@endphp
<div class="modal fade" id="forwardModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
 <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
 <form action="{{ route('appointments.forward-email', $app->id) }}" method="POST" class="modal-content shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);" id="forwardForm{{ $app->id }}">
 @csrf
 <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
 <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
 <i class="bi bi-send-fill text-accent fs-5"></i>
 <span>Forward Clinical Results</span>
 </h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body p-4">
 <p class="small text-muted mb-3">
 Send encrypted, password-protected PDF results for <strong>{{ strtoupper($app->patient_name) }}</strong> (Ref #{{ $app->id }}).
 </p>
 {{-- Recipient Email Display Box --}}
 <div class="p-3 rounded border border-secondary border-opacity-15 mb-3" style="background-color: rgba(25, 211, 140, 0.04);">
 <div class="d-flex justify-content-between align-items-center">
 <small class="text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Recipient Email Address:</small>
 @if($isStaff)
 <button type="button" class="btn btn-link text-accent fw-bold p-0 text-decoration-none smaller" onclick="toggleForwardEmailEdit('{{ $app->id }}')">
 <i class="bi bi-pencil-square me-1"></i>Change Email
 </button>
 @endif
 </div>
 <div class="fw-bold text-main fs-6 mt-1 text-truncate" id="forward_email_display_{{ $app->id }}">
 {{ $singleTargetEmail ?: 'No email on file' }}
 </div>
 </div>
 @if($isStaff)
 {{-- Hidden Email Editing & Reason Section for Admin/Staff in Master Queue --}}
 <div id="forward_email_edit_section_{{ $app->id }}" class="d-none border-top border-secondary border-opacity-15 pt-3 mb-3">
 <div class="mb-3">
 <label class="smaller text-secondary fw-bold mb-1 uppercase d-block">New Target Email Address</label>
 <input type="email" name="target_email" id="target_email_{{ $app->id }}" class="form-control" value="{{ $singleTargetEmail }}" placeholder="recipient@example.com">
 </div>
 <div class="mb-3">
 <label class="smaller text-secondary fw-bold mb-1 uppercase d-block">Reason for Email Update (Audit Log)</label>
 <select name="reason" id="forward_reason_select_{{ $app->id }}" class="form-select" onchange="toggleForwardReason('{{ $app->id }}', this.value)">
 <option value="" disabled selected>-- Select a reason --</option>
 <option value="Patient requested delivery to an alternate email address">Patient requested delivery to an alternate email address</option>
 <option value="Correction of typographical email error">Correction of typographical email error</option>
 <option value="Updating patient direct contact email">Updating patient direct contact email</option>
 <option value="Others">Others (Specify below)</option>
 </select>
 </div>
 <div id="forward_custom_reason_wrapper_{{ $app->id }}" class="mb-3 d-none">
 <label class="smaller text-secondary fw-bold mb-1 uppercase d-block">Specify Custom Reason</label>
 <textarea name="custom_reason" id="forward_custom_reason_{{ $app->id }}" class="form-control" rows="2" placeholder="Provide clinical justification for email update..."></textarea>
 </div>
 </div>
 @endif
 {{-- Security Disclaimer Box --}}
 <div class="alert alert-clinical border-secondary bg-secondary bg-opacity-10 p-2.5 rounded-3 mb-0 smaller text-muted">
 <div class="d-flex align-items-center gap-2 mb-1 text-main fw-bold">
 <i class="bi bi-shield-lock-fill text-accent"></i>
 <span>Protected Medical Document</span>
 </div>
 The attached PDFs are protected by encryption. The password pattern is <strong>MMDDYYYY + Capitalized Initials</strong>.
 </div>
 </div>
 <div class="modal-footer p-3 border-top border-secondary border-opacity-10 d-flex gap-2">
 <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold uppercase" data-bs-dismiss="modal">Cancel</button>
 <button type="submit" class="btn btn-accent flex-grow-1 py-2 fw-bold uppercase shadow-sm">
 <i class="bi bi-send-fill me-1"></i> Confirm & Send
 </button>
 </div>
 </form>
 </div>
</div>
@endif
@endif
<script>
window.toggleForwardEmailEdit = window.toggleForwardEmailEdit || function(id) {
 const section = document.getElementById(`forward_email_edit_section_${id}`);
 const input = document.getElementById(`target_email_${id}`);
 const select = document.getElementById(`forward_reason_select_${id}`);
 if (section) {
 const isHidden = section.classList.contains('d-none');
 section.classList.toggle('d-none', !isHidden);
 if (input && select) {
 if (isHidden) {
 input.setAttribute('required', 'required');
 select.setAttribute('required', 'required');
 input.focus();
 } else {
 input.removeAttribute('required');
 select.removeAttribute('required');
 }
 }
 }
};
window.toggleForwardReason = window.toggleForwardReason || function(id, val) {
 const wrapper = document.getElementById(`forward_custom_reason_wrapper_${id}`);
 const textarea = document.getElementById(`forward_custom_reason_${id}`);
 if (wrapper && textarea) {
 if (val === 'Others') {
 wrapper.classList.remove('d-none');
 textarea.setAttribute('required', 'required');
 textarea.focus();
 } else {
 wrapper.classList.add('d-none');
 textarea.removeAttribute('required');
 textarea.value = '';
 }
 }
};
</script>