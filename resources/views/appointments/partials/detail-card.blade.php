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
 'tested' => 4,
 'encoded' => 5,
 'released' => 6,
 default => 7
 };
 });
$batchTotal = $batchAppointments->sum(fn($a) => $a->totalPrice());
$paymentProviders = $paymentProviders ?? \App\Models\PaymentProvider::where('is_active', true)->get();
$anyApproved = $batchAppointments->contains(fn($appointment) => in_array($appointment->status, ['approved', 'tested', 'encoded', 'released']));
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
 </div>

 {{-- Consolidated Batch Level Controls for Staff --}}
 @can('isStaff')
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
 {{-- Consolidated single cashless receipt preview --}}
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
 <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-1" style="cursor: pointer; max-width: 260px;" onclick="openFilePreview('{{ Storage::url($app->payment_receipt) }}', 'Batch Proof of Payment')" title="Click to view full screen">
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
 <div class="d-flex gap-2">
 @if($showConfirm)
 <button type="button" class="btn-custom btn-accent py-2 fw-bold uppercase shadow-sm small flex-grow-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#confirmBatchPaymentModal{{ $app->id }}">
 <i class="bi bi-patch-check-fill me-1"></i> CONFIRM BATCH PAYMENT
 </button>
 @endif
 @if($showRevoke)
 <button type="button" class="btn-custom btn-outline-danger py-2 fw-bold uppercase shadow-sm small flex-grow-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#revokeBatchPaymentModal{{ $app->id }}">
 <i class="bi bi-arrow-counterclockwise me-1"></i> REVOKE BATCH PAYMENT
 </button>
 @endif
 @if($showReturn)
 <button type="button" class="btn-custom btn-danger-custom py-2 px-3 fw-bold uppercase small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#returnBatchModal{{ $app->id }}">
 <i class="bi bi-x-circle me-1"></i> RETURN ENTIRE BATCH
 </button>
 @endif
 @if($showApprove)
 <button type="button" class="btn-custom btn-neon py-2 px-3 fw-bold uppercase small {{ $isBatchApproveDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isBatchApproveDisabled ? 'disabled title="Batch cashless payment must be confirmed before approval"' : '' }} data-bs-toggle="modal" data-bs-target="#approveBatchModal{{ $app->id }}" style="font-size: 0.75rem;">
 <i class="bi bi-check-circle me-1"></i> APPROVE BATCH
 </button>
 @endif
 </div>
 </div>
 @endif
 @endcan

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
 $isCoordinatorOnly = (auth()->id() === $app->user_id && $subApp->patient_email !== auth()->user()->email);
 @endphp
 @if($isCoordinatorOnly)
 <button class="btn-custom btn-accent btn-sm py-1.5 px-4" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}">
 <i class="bi bi-send me-1.5"></i>FORWARD RESULT
 </button>
 @else
 <div class="d-flex gap-1.5 align-items-center">
 @if(auth()->user()->isEmployee())
 <button type="button" class="btn-custom btn-outline-accent btn-sm py-1.5 px-3 fw-bold" onclick="promptAccess('{{ $subApp->id }}', 'hub', 'edit')">
 <i class="bi bi-shield-lock-fill me-1"></i>VIEW RESULTS HUB
 </button>
 <button class="btn-custom btn-outline-accent btn-sm py-1.5 px-2.5" data-bs-toggle="modal" data-bs-target="#forwardModal{{ $subApp->id }}">
 <i class="bi bi-send"></i>
 </button>
 @else
 <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase hover-dark-text" style="font-size: 0.7rem; border-color: var(--brand-accent) !important;">
 <i class="bi bi-file-earmark-pdf"></i> PREVIEW
 </a>
 <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'download']) }}" class="btn-custom btn-accent btn-sm py-1.5 px-2.5" style="font-size: 0.7rem; color: #1c232d !important;">DOWNLOAD</a>
 @endif
 </div>
 @endif
 @else
 @include('appointments.partials.actions', ['app' => $subApp])
 @endif
 </div>
 </div>
 </div>
 @endforeach
 </div>
</div>
@else
{{-- =========================================================================
B. INDIVIDUAL / FAMILY WORKSPACE
========================================================================= --}}
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
 <span class="text-muted">{{ number_format($service->price, 2) }}</span>
 </li>
 @endforeach
 <li class="list-group-item text-accent fw-bold d-flex justify-content-between border-top border-secondary border-opacity-25 py-2.5" style="background-color: rgba(25, 211, 140, 0.05) !important;">
 <span>TOTAL BILLING</span>
 <span>{{ number_format($app->totalPrice(), 2) }}</span>
 </li>
 </ul>

 {{-- Released Clinical Results Links --}}
 @if($app->status == 'released' && $app->result && auth()->user()->isPatient())
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

 {{-- Right Grid column: Actions --}}
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

 {{-- Actions Panel (Includes Referral Note & Payment Receipt Preview Cards + Controls) --}}
 <div class="border-top border-secondary border-opacity-25 pt-3 mt-3">
 @include('appointments.partials.actions', ['app' => $app])

 {{-- Prominent review button shortcut visible strictly to employee dashboards --}}
 @if(auth()->user()->isEmployee() && $app->status === 'released')
 <div class="mt-3">
 <button type="button" class="btn btn-sm btn-accent w-100 py-2 fw-bold text-uppercase" onclick="promptAccess('{{ $app->id }}', 'hub', 'edit')">
 <i class="bi bi-shield-check me-1.5"></i> Review / Edit Results Hub
 </button>
 </div>
 @endif

 {{-- Forward results to patient email safely using automated compiler --}}
 @if($app->status == 'released' && auth()->user()->isPatient())
 <div class="mt-3">
 <form action="{{ route('appointments.forward-email', $app->id) }}" method="POST" class="m-0">
 @csrf
 <button type="submit" class="btn btn-sm btn-outline-accent w-100 py-2 fw-bold text-uppercase hover-dark-text" style="border-color: var(--brand-accent) !important;">
 <i class="bi bi-envelope-at me-1.5"></i> Forward Results to Email
 </button>
 </form>
 </div>
 @endif
 </div>
 </div>
 </div>
</div>
@endif