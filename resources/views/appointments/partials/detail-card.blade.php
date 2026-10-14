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
$isStaff = !empty($is_staff) && auth()->check() && auth()->user()->isEmployee();
$isHistoryView = !empty($isHistory);

// Only render as a Bulk Batch Workspace if this is a bulk appointment AND we are NOT in History View AND the user is either Staff or the Creator/Maker of that bulk batch.
$isBulkBatchWorkspace = ($app->batch_id && !$isHistoryView && ($isStaff || (auth()->check() && auth()->id() === $app->user_id)));

if ($isBulkBatchWorkspace) {
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

@if($isBulkBatchWorkspace)
{{-- =========================================================================
     A. BULK BATCH WORKSPACE (Visible only to Batch Creator & Clinic Staff)
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

// Cancel entire batch is only permitted if pending/returned AND no patient has progressed to approved/retest/tested/encoded/released
$canCancelBatch = $batchAppointments->contains(fn($a) => in_array($a->status, ['pending', 'returned'])) 
    && !$batchAppointments->contains(fn($a) => in_array($a->status, ['approved', 'retest', 'tested', 'encoded', 'released']));
@endphp

<div id="details-{{ $app->id }}" class="appointment-detail-pane card border bg-card p-4 d-none animate-page" style="background-color: var(--bg-card); border-color: var(--border-color) !important; color: var(--text-main); border-radius: 14px;">
    {{-- Batch Header --}}
    <div class="border-bottom pb-3 mb-4 text-start" style="border-color: var(--border-color) !important;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1 uppercase tracking-tight" style="color: var(--text-main) !important;">
                    {{ $app->organization_name }}
                </h4>
                <div class="smaller fw-bold uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">
                    Batch ID: #{{ $app->batch_id }} <span class="mx-2">&bull;</span> 
                    Schedule: {{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }} <span class="mx-2">&bull;</span> 
                    Total PAX: {{ $batchAppointments->count() }}
                </div>
            </div>
            <div class="text-end">
                <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} px-3 py-2 fs-6 uppercase rounded-pill" style="background-color: rgba(25, 211, 140, 0.08);">
                    {{ $statusLabel }}
                </span>
            </div>
        </div>

        {{-- Patient-Side Batch Cancel Trigger --}}
        @if(!$isStaff && $canCancelBatch && Auth::id() == $app->user_id)
            <div class="mt-3 text-end">
                <button type="button" class="btn btn-outline-danger btn-sm fw-bold uppercase py-2 px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#cancelBatchModal{{ $app->id }}">
                    <i class="bi bi-x-circle"></i>
                    <span>Cancel Entire Batch</span>
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
            <div class="batch-controls-panel card p-4 mb-4 text-start animate-page border" style="background-color: rgba(25, 211, 140, 0.03); border-color: var(--border-color) !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-accent fw-bold small uppercase mb-0 d-flex align-items-center gap-2" style="color: var(--brand-accent) !important;">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                        <span>Batch Controls (One-Time Payment)</span>
                    </h6>
                </div>

                {{-- Consolidated Single Cashless Receipt Preview --}}
                @if($app->payment_method === 'Cashless')
                    <div class="border rounded p-3 mb-3 text-start" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">Batch Proof of Payment:</span>
                            <span class="badge {{ $app->payment_status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }} px-2 py-1 small">
                                {{ strtoupper($app->payment_status) }}
                            </span>
                        </div>
                        @if($app->payment_receipt)
                            @php $batchPayExt = pathinfo($app->payment_receipt, PATHINFO_EXTENSION); @endphp
                            <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-1 border cursor-pointer" style="cursor: pointer; max-width: 280px; border-color: var(--border-color) !important;" onclick="openFilePreview('{{ Storage::url($app->payment_receipt) }}', 'Batch Proof of Payment')" title="Click to view full screen">
                                <i class="bi bi-file-earmark-image-fill text-accent display-6" style="color: var(--brand-accent) !important;"></i>
                                <div class="text-start">
                                    <div class="fw-bold small text-dark text-truncate" style="max-width: 190px;">Batch_Proof_of_Payment{{ $batchPayExt ? '.'.strtolower($batchPayExt) : '' }}</div>
                                    <span class="text-muted smaller d-flex align-items-center gap-1"><i class="bi bi-zoom-in text-accent"></i> Click to Zoom</span>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-clinical border-danger text-danger text-center p-2 small mb-0" style="background-color: rgba(220, 53, 69, 0.05);">
                                <i class="bi bi-exclamation-triangle-fill me-1.5"></i> No payment receipt uploaded.
                            </div>
                        @endif
                    </div>
                @endif

                <div class="d-flex gap-2 flex-wrap">
                    @if($showConfirm)
                        <button type="button" class="btn-custom btn-accent py-2 fw-bold uppercase shadow-sm small flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#confirmBatchPaymentModal{{ $app->id }}">
                            <i class="bi bi-patch-check-fill"></i>
                            <span>Confirm Batch Payment</span>
                        </button>
                    @endif
                    @if($showRevoke)
                        <button type="button" class="btn-custom btn-outline-danger py-2 fw-bold uppercase shadow-sm small flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#revokeBatchPaymentModal{{ $app->id }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Revoke Batch Payment</span>
                        </button>
                    @endif
                    @if($showReturn)
                        <button type="button" class="btn-custom btn-danger-custom py-2 px-3 fw-bold uppercase small d-inline-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#returnBatchModal{{ $app->id }}">
                            <i class="bi bi-x-circle"></i>
                            <span>Return Entire Batch</span>
                        </button>
                    @endif
                    @if($showApprove)
                        <button type="button" class="btn-custom btn-accent py-2 px-3 fw-bold uppercase small d-inline-flex align-items-center justify-content-center gap-1.5 {{ $isBatchApproveDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isBatchApproveDisabled ? 'disabled title="Batch cashless payment must be confirmed before approval"' : '' }} data-bs-toggle="modal" data-bs-target="#approveBatchModal{{ $app->id }}" style="font-size: 0.75rem;">
                            <i class="bi bi-check-circle"></i>
                            <span>Approve Batch</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- Live Search & Batch Statistics --}}
    <div class="row g-3 mb-4 align-items-center text-start">
        <div class="col-12 col-md-7">
            <div class="input-group input-group-sm border rounded-3 overflow-hidden" style="border-color: var(--border-color) !important;">
                <span class="input-group-text border-0" style="background-color: var(--bg-main); color: var(--text-muted) !important;">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control border-0 shadow-none py-2" style="background-color: var(--bg-main); color: var(--text-main);" placeholder="Search patient name in this batch..." oninput="filterBatchPatients('{{ $app->batch_id }}', this.value)">
            </div>
        </div>
        <div class="col-12 col-md-5 text-md-end">
            <div class="text-accent fw-bold small" style="font-size: 0.85rem; color: var(--brand-accent) !important;">
                <i class="bi bi-cash-stack me-1.5"></i>BATCH TOTAL: {{ number_format($batchTotal, 2) }} PHP
            </div>
        </div>
    </div>

    {{-- Scrollable Patient Deck --}}
    <div class="d-flex flex-column gap-3 overflow-auto custom-scroll pe-1 mb-2" style="max-height: 520px;">
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
            <div class="card p-3 border bg-card bulk-patient-row-{{ $app->batch_id }} text-start shadow-sm" data-name="{{ strtoupper($subApp->patient_name) }}" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div>
                        <span class="fw-bold small uppercase me-2" style="font-size: 0.9rem; color: var(--text-main) !important;">{{ $subApp->patient_name }}</span>
                        <small class="text-muted" style="font-size: 0.68rem; color: var(--text-muted) !important;">
                            {{ $subApp->patient_age }} YRS <span class="mx-1">&bull;</span> {{ strtoupper($subApp->patient_sex) }} <span class="mx-1">&bull;</span> ID: #{{ $subApp->id }}
                        </small>
                    </div>
                    <div class="d-flex gap-1.5 align-items-center">
                        <span class="badge border border-{{ $subBadgeColor }} text-{{ $subBadgeColor == 'accent' ? 'success' : $subBadgeColor }} uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; background-color: rgba(25, 211, 140, 0.08);">
                            {{ $subBadgeLabel }}
                        </span>
                    </div>
                </div>

                {{-- Patient Tests List --}}
                <div class="text-start border-bottom pb-2 mb-3" style="border-color: var(--border-color) !important;">
                    <small class="text-accent fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; color: var(--brand-accent) !important;">
                        <i class="bi bi-flask-fill me-1"></i>Tests Requested:
                    </small>
                    <div class="small" style="font-size: 0.8rem; color: var(--text-main) !important;">
                        {{ $subApp->services->pluck('name')->implode(', ') }} 
                        <span class="text-muted ms-2" style="color: var(--text-muted) !important;">({{ number_format($subApp->totalPrice(), 2) }} PHP)</span>
                    </div>
                </div>

                {{-- Individual Patient Workflows --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted d-flex align-items-center gap-1.5" style="font-size: 0.72rem; color: var(--text-muted) !important;">
                        <i class="bi bi-clock-history text-accent" style="color: var(--brand-accent) !important;"></i>
                        <span>{{ $subApp->appointment_date ? $subApp->appointment_date->format('M d, Y') : 'N/A' }} | {{ $subApp->time_slot ? date('h:i A', strtotime($subApp->time_slot)) : '--' }}</span>
                    </div>
                    <div class="bulk-actions-container">
                        @if($subApp->status == 'released')
                            @php
                                $isCoordinatorOnly = (!$isStaff && auth()->id() === $app->user_id && $subApp->patient_email !== auth()->user()->email);
                            @endphp
                            @if($isCoordinatorOnly)
                                <button class="btn btn-sm btn-accent py-1 px-3 fw-bold uppercase d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}" style="font-size: 0.72rem;">
                                    <i class="bi bi-send"></i>
                                    <span>FORWARD RESULT</span>
                                </button>
                            @else
                                <div class="d-flex gap-2 align-items-center">
                                    @if($isStaff)
                                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 fw-bold d-inline-flex align-items-center gap-1.5" onclick="promptAccess('{{ $subApp->id }}', 'hub', 'edit')" style="font-size: 0.72rem;">
                                            <i class="bi bi-shield-lock-fill"></i>
                                            <span>VIEW RESULTS HUB</span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-accent py-1 px-2.5 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}" title="Forward Results to Email" style="font-size: 0.72rem;">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    @else
                                        <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                            <span>PREVIEW</span>
                                        </a>
                                        <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'download']) }}" class="btn btn-sm btn-accent py-1 px-2.5 small uppercase fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-download"></i>
                                            <span>DOWNLOAD</span>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}" title="Forward Results to Email" style="font-size: 0.72rem;">
                                            <i class="bi bi-send"></i>
                                            <span>FORWARD</span>
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

{{-- BATCH MODALS (Patient Cancel & Staff Action Modals) --}}
@if(!$isStaff && $canCancelBatch && Auth::id() == $app->user_id)
    <div class="modal fade" id="cancelBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <form action="{{ route('appointments.cancel', $app->id) }}" method="POST" class="modal-content shadow-lg border-0 cancel-batch-form" data-app-id="{{ $app->id }}" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
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
                    <p class="small mb-3" style="color: var(--text-main) !important;">
                        Are you sure you want to cancel all appointments in <strong>{{ $app->organization_name }} (Batch #{{ $app->batch_id }})</strong>?
                    </p>
                    <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-warning p-3 rounded-3 mb-3 smaller">
                        <i class="bi bi-info-circle-fill me-1.5"></i> This action will cancel all active patient appointments registered under this corporate batch.
                    </div>
                    <div class="mb-3">
                        <label for="cancel_batch_reason_select_{{$app->id}}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Reason for Batch Cancellation</label>
                        <select id="cancel_batch_reason_select_{{$app->id}}" name="cancellation_reason" class="form-select shadow-none cancel-batch-reason-select" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="window.toggleCancelBatchReasonField('{{ $app->id }}', this)" required>
                            <option value="" disabled selected>-- Select a cancellation reason --</option>
                            <option value="Corporate schedule postponed / rescheduled">Corporate schedule postponed / rescheduled</option>
                            <option value="Company management request / internal postponement">Company management request / internal postponement</option>
                            <option value="Accidental / duplicate bulk booking submission">Accidental / duplicate bulk booking submission</option>
                            <option value="Budget / procurement review or cancellation">Budget / procurement review or cancellation</option>
                            <option value="Others">Others (Specify details below)</option>
                        </select>
                    </div>
                    <div id="custom_cancel_batch_reason_wrapper_{{$app->id}}" class="mb-3 d-none">
                        <label for="custom_cancel_batch_reason_{{$app->id}}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                        <textarea id="custom_cancel_batch_reason_{{$app->id}}" class="form-control shadow-none cancel-batch-reason-textarea" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="3" placeholder="Identify the specific cancellation reason..."></textarea>
                        <div class="mt-1"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
                    </div>
                    <input type="hidden" name="reason" id="cancel_batch_reason_hidden_{{$app->id}}" value="">
                </div>
                <div class="modal-footer p-3 border-top d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                    <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">Cancel Entire Batch</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if($isStaff)
    {{-- Staff Confirm Batch Payment Modal --}}
    <div class="modal fade" id="confirmBatchPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                <div class="modal-header border-bottom py-3" style="border-color: var(--border-color) !important;">
                    <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2" style="color: var(--brand-accent) !important;">
                        <i class="bi bi-cash-stack fs-5"></i>
                        <span>Confirm Batch Payment?</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="small mb-0" style="color: var(--text-main) !important;">
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

    {{-- Staff Revoke Batch Payment Modal --}}
    <div class="modal fade" id="revokeBatchPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                <div class="modal-header border-bottom py-3 border-danger" style="border-color: #ff4d4d !important;">
                    <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-counterclockwise fs-5"></i>
                        <span>Revoke Batch Payment?</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="small mb-0" style="color: var(--text-main) !important;">
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

    {{-- Staff Return Entire Batch Modal --}}
    <div class="modal fade" id="returnBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content shadow-lg return-form-element" data-app-id="{{ $app->id }}" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="returned">
                <input type="hidden" name="batch" value="true">
                <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title text-danger fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle fs-5"></i>
                        <span>Return Entire Batch</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

    {{-- Staff Approve Entire Batch Modal --}}
    <div class="modal fade" id="approveBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="approved">
                <input type="hidden" name="batch" value="true">
                <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2" style="color: var(--brand-accent) !important;">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <span>Approve Entire Batch?</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="small mb-0" style="color: var(--text-main) !important;">
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

{{-- Forward Result Modals for All Sub-Appointments in Batch --}}
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
                        <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2" style="color: var(--brand-accent) !important;">
                            <i class="bi bi-send-fill fs-5"></i>
                            <span>Forward Clinical Results</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="small text-muted mb-3" style="color: var(--text-muted) !important;">
                            Send encrypted, password-protected PDF results for <strong style="color: var(--text-main) !important;">{{ strtoupper($subApp->patient_name) }}</strong> (Ref #{{ $subApp->id }}).
                        </p>
                        {{-- Recipient Email Display Box --}}
                        <div class="p-3 rounded border mb-3" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">Recipient Email Address:</small>
                                @if($isStaff)
                                    <button type="button" class="btn btn-link text-accent fw-bold p-0 text-decoration-none smaller d-inline-flex align-items-center gap-1" onclick="toggleForwardEmailEdit('{{ $subApp->id }}')">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Change Email</span>
                                    </button>
                                @endif
                            </div>
                            <div class="fw-bold fs-6 mt-1 text-truncate" id="forward_email_display_{{ $subApp->id }}" style="color: var(--text-main) !important;">
                                {{ $subTargetEmail ?: 'No email on file' }}
                            </div>
                        </div>
                        @if($isStaff)
                            <div id="forward_email_edit_section_{{ $subApp->id }}" class="d-none border-top pt-3 mb-3" style="border-color: var(--border-color) !important;">
                                <div class="mb-3">
                                    <label class="smaller fw-bold mb-1 uppercase d-block" style="color: var(--text-muted);">New Target Email Address</label>
                                    <input type="email" name="target_email" id="target_email_{{ $subApp->id }}" class="form-control" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" value="{{ $subTargetEmail }}" placeholder="recipient@example.com">
                                </div>
                                <div class="mb-3">
                                    <label class="smaller fw-bold mb-1 uppercase d-block" style="color: var(--text-muted);">Reason for Email Update (Audit Log)</label>
                                    <select name="reason" id="forward_reason_select_{{ $subApp->id }}" class="form-select" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" onchange="toggleForwardReason('{{ $subApp->id }}', this.value)">
                                        <option value="" disabled selected>-- Select a reason --</option>
                                        <option value="Patient requested delivery to an alternate email address">Patient requested delivery to an alternate email address</option>
                                        <option value="Correction of typographical email error">Correction of typographical email error</option>
                                        <option value="Updating batch member direct contact email">Updating batch member direct contact email</option>
                                        <option value="Others">Others (Specify below)</option>
                                    </select>
                                </div>
                                <div id="forward_custom_reason_wrapper_{{ $subApp->id }}" class="mb-3 d-none">
                                    <label class="smaller fw-bold mb-1 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                                    <textarea name="custom_reason" id="forward_custom_reason_{{ $subApp->id }}" class="form-control" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" rows="2" placeholder="Provide clinical justification for email update..."></textarea>
                                </div>
                            </div>
                        @endif
                        {{-- Security Disclaimer Box --}}
                        <div class="alert alert-clinical border p-2.5 rounded-3 mb-0 smaller text-muted" style="border-color: var(--border-color) !important; background-color: rgba(25, 211, 140, 0.04);">
                            <div class="d-flex align-items-center gap-2 mb-1 fw-bold" style="color: var(--text-main) !important;">
                                <i class="bi bi-shield-lock-fill text-accent"></i>
                                <span>Protected Medical Document</span>
                            </div>
                            The attached PDFs are protected by encryption. The password pattern is <strong>MMDDYYYY + Capitalized Initials</strong>.
                        </div>
                    </div>
                    <div class="modal-footer p-3 border-top d-flex gap-2" style="border-color: var(--border-color) !important;">
                        <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold uppercase" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-accent flex-grow-1 py-2 fw-bold uppercase shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5">
                            <i class="bi bi-send-fill"></i>
                            <span>Confirm & Send</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

@else
{{-- =========================================================================
     B. INDIVIDUAL / FAMILY / ARCHIVE WORKSPACE
========================================================================= --}}
<div id="details-{{ $app->id }}" class="appointment-detail-pane card border bg-card p-4 d-none animate-page" style="background-color: var(--bg-card); border-color: var(--border-color) !important; color: var(--text-main); border-radius: 14px;">
    {{-- Detailed Header Section --}}
    <div class="border-bottom pb-3 mb-4 text-start" style="border-color: var(--border-color) !important;">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 text-start">
            <div>
                <h4 class="fw-bold mb-1 uppercase tracking-tight" style="color: var(--text-main) !important;">
                    {{ $app->patient_name }}
                </h4>
                <div class="smaller fw-bold uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">
                    {{ $app->patient_age }} Years Old <span class="mx-2">&bull;</span> 
                    {{ strtoupper($app->patient_sex) }} <span class="mx-2">&bull;</span>
                    REF: #{{ $app->id }}
                    @if($app->batch_id)
                        <span class="mx-2">&bull;</span> BATCH: #{{ $app->batch_id }}
                    @endif
                </div>
            </div>
            <div class="text-end">
                <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} px-3 py-2 fs-6 uppercase rounded-pill" style="background-color: rgba(25, 211, 140, 0.08);">
                    {{ $statusLabel }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4 text-start">
        {{-- Left Grid column: Items breakdown --}}
        <div class="col-12 col-md-7 border-end-md" style="border-color: var(--border-color) !important;">
            <h6 class="text-accent small fw-bold uppercase mb-3 d-flex align-items-center gap-2" style="font-size: 0.8rem; letter-spacing: 0.5px; color: var(--brand-accent) !important;">
                <i class="bi bi-flask-fill fs-5"></i>
                <span>Laboratory Request Breakdown</span>
            </h6>
            <ul class="list-group list-group-flush border rounded mb-3" style="border-color: var(--border-color) !important; background-color: transparent;">
                @foreach($app->services as $service)
                    <li class="list-group-item bg-transparent small d-flex justify-content-between border-bottom py-2.5" style="border-color: var(--border-color) !important; color: var(--text-main);">
                        <span class="fw-semibold">{{ strtoupper($service->name) }}</span>
                        <span class="text-muted" style="color: var(--text-muted) !important;">₱{{ number_format($service->price, 2) }}</span>
                    </li>
                @endforeach
                <li class="list-group-item text-accent fw-bold d-flex justify-content-between py-2.5" style="background-color: rgba(25, 211, 140, 0.06) !important; border-top: 1.5px solid var(--border-color); color: var(--brand-accent) !important;">
                    <span>TOTAL BILLING</span>
                    <span>₱{{ number_format($app->totalPrice(), 2) }}</span>
                </li>
            </ul>

            {{-- Released Clinical Results Links (Patient View) --}}
            @if($app->status == 'released' && $app->result && !$isStaff)
                <div class="mt-4 border-top pt-3 text-start animate-page" style="border-color: var(--border-color) !important;">
                    <h6 class="text-accent small fw-bold uppercase mb-3 d-flex align-items-center gap-2" style="color: var(--brand-accent) !important;">
                        <i class="bi bi-file-earmark-medical-fill fs-5"></i>
                        <span>Released Clinical Results</span>
                    </h6>
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
                            <div class="d-flex justify-content-between align-items-center p-2.5 border rounded" style="background-color: rgba(25, 211, 140, 0.03); border-color: var(--border-color) !important;">
                                <span class="small fw-semibold d-flex align-items-center gap-2" style="color: var(--text-main) !important;">
                                    <i class="bi bi-file-earmark-pdf-fill text-accent"></i>
                                    <span>{{ $label }}</span>
                                </span>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('appointments.result.access', [$app->id, $type, 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                        <span>PREVIEW</span>
                                    </a>
                                    <a href="{{ route('appointments.result.access', [$app->id, $type, 'download']) }}" class="btn btn-sm btn-accent py-1 px-2.5 small uppercase fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-download"></i>
                                        <span>DOWNLOAD</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                        @foreach($app->result->customWorkstationResults as $custom)
                            @if($custom->status === 'verified')
                                <div class="d-flex justify-content-between align-items-center p-2.5 border rounded" style="background-color: rgba(25, 211, 140, 0.03); border-color: var(--border-color) !important;">
                                    <span class="small fw-semibold d-flex align-items-center gap-2" style="color: var(--text-main) !important;">
                                        <i class="bi bi-file-earmark-pdf-fill text-accent"></i>
                                        <span>{{ $custom->name }}</span>
                                    </span>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('appointments.result.access', [$app->id, 'custom_' . $custom->id, 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                            <span>PREVIEW</span>
                                        </a>
                                        <a href="{{ route('appointments.result.access', [$app->id, 'custom_' . $custom->id, 'download']) }}" class="btn btn-sm btn-accent py-1 px-2.5 small uppercase fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-download"></i>
                                            <span>DOWNLOAD</span>
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
        <div class="col-12 col-md-5 d-flex flex-column justify-content-between">
            <div>
                <h6 class="text-accent small fw-bold uppercase mb-3 d-flex align-items-center gap-2" style="font-size: 0.8rem; letter-spacing: 0.5px; color: var(--brand-accent) !important;">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                    <span>Record Metadata</span>
                </h6>
                <div class="mb-3">
                    <small class="text-muted fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">Schedule Date & Time:</small>
                    <div class="small fw-semibold" style="color: var(--text-main) !important;">
                        <i class="bi bi-calendar-event text-accent me-2"></i>{{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }}<br>
                        <i class="bi bi-clock text-accent me-2"></i>{{ $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : '--' }}
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">Patient Address:</small>
                    <div class="small fw-semibold d-flex align-items-start gap-2" style="color: var(--text-main) !important;">
                        <i class="bi bi-geo-alt-fill text-accent mt-0.5"></i>
                        <span>{{ $app->patient_address }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">Contact Number:</small>
                    <div class="small fw-semibold d-flex align-items-center gap-2" style="color: var(--text-main) !important;">
                        <i class="bi bi-telephone-fill text-accent"></i>
                        <span>{{ $app->patient_phone }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions Panel --}}
            <div class="border-top pt-3 mt-3" style="border-color: var(--border-color) !important;">
                @include('appointments.partials.actions', ['app' => $app, 'is_staff' => $isStaff])

                {{-- Prominent Review & Forward Buttons for Staff (Master Queue Only) --}}
                @if($isStaff && $app->status === 'released')
                    <div class="mt-3 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-accent flex-grow-1 py-2 fw-bold uppercase d-inline-flex align-items-center justify-content-center gap-2" onclick="promptAccess('{{ $app->id }}', 'hub', 'edit')">
                            <i class="bi bi-shield-check"></i>
                            <span>Review / Edit Results Hub</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-accent px-3 py-2 fw-bold uppercase d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $app->id }}" title="Forward Results to Email">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                @endif

                {{-- Forward Results to Email (Patient View) --}}
                @if(!$isStaff && $app->status == 'released' && (Auth::id() == $app->user_id || ($app->patient_email && strtolower(Auth::user()->email) === strtolower($app->patient_email))))
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-accent w-100 py-2 fw-bold uppercase d-inline-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $app->id }}">
                            <i class="bi bi-envelope-at"></i>
                            <span>Forward Results to Email</span>
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
                    <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2" style="color: var(--brand-accent) !important;">
                        <i class="bi bi-send-fill fs-5"></i>
                        <span>Forward Clinical Results</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3" style="color: var(--text-muted) !important;">
                        Send encrypted, password-protected PDF results for <strong style="color: var(--text-main) !important;">{{ strtoupper($app->patient_name) }}</strong> (Ref #{{ $app->id }}).
                    </p>
                    {{-- Recipient Email Display Box --}}
                    <div class="p-3 rounded border mb-3" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; color: var(--text-muted) !important;">Recipient Email Address:</small>
                            @if($isStaff)
                                <button type="button" class="btn btn-link text-accent fw-bold p-0 text-decoration-none smaller d-inline-flex align-items-center gap-1" onclick="toggleForwardEmailEdit('{{ $app->id }}')">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Change Email</span>
                                </button>
                            @endif
                        </div>
                        <div class="fw-bold fs-6 mt-1 text-truncate" id="forward_email_display_{{ $app->id }}" style="color: var(--text-main) !important;">
                            {{ $singleTargetEmail ?: 'No email on file' }}
                        </div>
                    </div>
                    @if($isStaff)
                        <div id="forward_email_edit_section_{{ $app->id }}" class="d-none border-top pt-3 mb-3" style="border-color: var(--border-color) !important;">
                            <div class="mb-3">
                                <label class="smaller fw-bold mb-1 uppercase d-block" style="color: var(--text-muted);">New Target Email Address</label>
                                <input type="email" name="target_email" id="target_email_{{ $app->id }}" class="form-control" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" value="{{ $singleTargetEmail }}" placeholder="recipient@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="smaller fw-bold mb-1 uppercase d-block" style="color: var(--text-muted);">Reason for Email Update (Audit Log)</label>
                                <select name="reason" id="forward_reason_select_{{ $app->id }}" class="form-select" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" onchange="toggleForwardReason('{{ $app->id }}', this.value)">
                                    <option value="" disabled selected>-- Select a reason --</option>
                                    <option value="Patient requested delivery to an alternate email address">Patient requested delivery to an alternate email address</option>
                                    <option value="Correction of typographical email error">Correction of typographical email error</option>
                                    <option value="Updating patient direct contact email">Updating patient direct contact email</option>
                                    <option value="Others">Others (Specify below)</option>
                                </select>
                            </div>
                            <div id="forward_custom_reason_wrapper_{{ $app->id }}" class="mb-3 d-none">
                                <label class="smaller fw-bold mb-1 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                                <textarea name="custom_reason" id="forward_custom_reason_{{ $app->id }}" class="form-control" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" rows="2" placeholder="Provide clinical justification for email update..."></textarea>
                            </div>
                        </div>
                    @endif
                    {{-- Security Disclaimer Box --}}
                    <div class="alert alert-clinical border p-2.5 rounded-3 mb-0 smaller text-muted" style="border-color: var(--border-color) !important; background-color: rgba(25, 211, 140, 0.04);">
                        <div class="d-flex align-items-center gap-2 mb-1 fw-bold" style="color: var(--text-main) !important;">
                            <i class="bi bi-shield-lock-fill text-accent"></i>
                            <span>Protected Medical Document</span>
                        </div>
                        The attached PDFs are protected by encryption. The password pattern is <strong>MMDDYYYY + Capitalized Initials</strong>.
                    </div>
                </div>
                <div class="modal-footer p-3 border-top d-flex gap-2" style="border-color: var(--border-color) !important;">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold uppercase" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent flex-grow-1 py-2 fw-bold uppercase shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5">
                        <i class="bi bi-send-fill"></i>
                        <span>Confirm & Send</span>
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

window.toggleCancelBatchReasonField = window.toggleCancelBatchReasonField || function(appId, select) {
    const wrapper = document.getElementById(`custom_cancel_batch_reason_wrapper_${appId}`);
    const textarea = document.getElementById(`custom_cancel_batch_reason_${appId}`);
    const hiddenReason = document.getElementById(`cancel_batch_reason_hidden_${appId}`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
            textarea.setAttribute('name', 'cancellation_reason');
            select.removeAttribute('name');
            textarea.focus();
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
            textarea.removeAttribute('name');
            select.setAttribute('name', 'cancellation_reason');
            textarea.value = '';
        }
        if (hiddenReason) {
            hiddenReason.value = (select.value === 'Others' ? textarea.value.trim() : select.value) || '';
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.cancel-batch-form').forEach(form => {
        const appId = form.dataset.appId;
        const select = document.getElementById(`cancel_batch_reason_select_${appId}`);
        const textarea = document.getElementById(`custom_cancel_batch_reason_${appId}`);
        const hiddenReason = document.getElementById(`cancel_batch_reason_hidden_${appId}`);
        if (textarea) {
            textarea.addEventListener('input', function() {
                if (hiddenReason) hiddenReason.value = this.value.trim();
            });
        }
        if (select) {
            form.addEventListener('submit', function(e) {
                const activeVal = select.value === 'Others' ? textarea : select;
                if (!activeVal || !activeVal.value || activeVal.value.trim().length < 5) {
                    e.preventDefault();
                    alert('Please select or specify a valid batch cancellation reason of at least 5 characters.');
                    if (activeVal) activeVal.focus();
                    return false;
                }
                if (hiddenReason) {
                    hiddenReason.value = activeVal.value.trim();
                }
            });
        }
    });
});
</script>