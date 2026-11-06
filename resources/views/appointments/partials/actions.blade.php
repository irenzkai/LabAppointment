<!-- resources/views/appointments/partials/actions.blade.php -->
@php
    // Evaluates if the current view context is the Staff Master Queue
    $isStaffView = !empty($is_staff) && auth()->check() && auth()->user()->isEmployee();
    $isVoided = $app->isVoided();
    $statusColor = $isVoided ? 'danger' : match($app->status) {
        'pending' => 'warning',
        'needs_review' => 'warning',
        'approved' => 'info',
        'tested' => 'info',
        'encoded' => 'info',
        'released' => 'accent',
        'returned' => 'danger',
        'retest' => 'danger',
        'canceled' => 'danger',
        default => 'secondary'
    };
    $statusLabel = $isVoided ? 'VOIDED' : strtoupper($app->status);

    // Calculate worksheet progress metrics for encoding and verification across services & packages
    $res = $app->result;
    $includedReports = $res ? ($res->included_reports ?? []) : [];
    if (empty($includedReports)) {
        $allServices = $app->allServices();
        $serviceNames = $allServices->pluck('name')->map(fn($n) => strtoupper($n))->toArray();
        $autoReports = [];
        foreach ($serviceNames as $name) {
            if (str_contains($name, 'DRUG TEST')) $autoReports[] = 'drug';
            elseif (str_contains($name, 'XRAY') || str_contains($name, 'X-RAY')) $autoReports[] = 'radio';
            elseif (str_contains($name, 'MEDICAL CERTIFICATE')) $autoReports[] = 'med_cert';
            else $autoReports[] = 'lab';
        }
        $includedReports = array_values(array_unique($autoReports));
    }
    if (empty($includedReports)) {
        $includedReports = ['lab'];
    }

    $customCount = 0;
    $customEncoded = 0;
    $customVerified = 0;
    if ($res) {
        $customWorksheets = $res->relationLoaded('customWorkstationResults')
            ? $res->customWorkstationResults
            : $res->customWorkstationResults()->get();
        $customCount = $customWorksheets->count();
        foreach ($customWorksheets as $cw) {
            if ($cw->status === 'verified') {
                $customEncoded++;
                $customVerified++;
            } elseif ($cw->status === 'encoded') {
                $customEncoded++;
            }
        }
    }

    $totalWorksheets = count($includedReports) + $customCount;
    $encodedCount = $customEncoded;
    $verifiedCount = $customVerified;

    foreach ($includedReports as $repType) {
        $field = match($repType) {
            'med_cert' => 'med_status',
            'radio' => 'radio_status',
            'drug' => 'drug_status',
            default => 'lab_status',
        };
        $st = $res ? ($res->$field ?? 'pending') : 'pending';
        if ($st === 'verified') {
            $encodedCount++;
            $verifiedCount++;
        } elseif ($st === 'encoded') {
            $encodedCount++;
        }
    }

    $toEncodeCount = max(0, $totalWorksheets - $encodedCount);
    $toVerifyCount = max(0, $encodedCount - $verifiedCount);
@endphp

<div class="mt-2 text-start" id="actions-pane-{{ $app->id }}">
    {{-- 1. PATIENT AGREEMENT BANNER (Staff Returned with Proposed Suggestions) --}}
    @if($app->hasSuggestedChanges() && ($app->status === 'needs_review' || $app->status === 'returned'))
        @php $suggested = $app->suggested_changes; @endphp
        <div class="alert-clinical p-3 mb-3 border-warning" style="background-color: rgba(255, 193, 7, 0.08); border-left: 4px solid var(--bs-warning) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-pencil-square text-warning me-2"></i>
                <small class="text-warning fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                    Staff Proposed Adjustments Awaiting Patient Confirmation
                </small>
            </div>
            <p class="small mb-2 italic" style="line-height: 1.4; color: var(--text-main);">
                "{{ $suggested['reason'] ?? $app->return_reason }}"
            </p>
            <div class="small text-muted mb-2">
                <strong>Proposed Schedule:</strong> {{ !empty($suggested['appointment_date']) ? \Carbon\Carbon::parse($suggested['appointment_date'])->format('M d, Y') : '' }} at {{ !empty($suggested['time_slot']) ? date('h:i A', strtotime($suggested['time_slot'])) : '' }}
            </div>

            {{-- Display Suggested Tests / Packages if specified --}}
            @if(!empty($suggested['package_ids']) || !empty($suggested['service_ids']))
                <div class="mb-3 pt-2 border-top border-secondary border-opacity-10">
                    <small class="text-accent fw-800 d-block mb-1 fs-x-small uppercase"><i class="bi bi-check2-all me-1"></i>Suggested Tests & Packages:</small>
                    <div class="d-flex flex-wrap gap-1">
                        @if(!empty($suggested['package_ids']))
                            @foreach(\App\Models\Package::whereIn('id', (array)$suggested['package_ids'])->get() as $pPkg)
                                <span class="badge bg-success bg-opacity-15 text-accent border border-success border-opacity-30 px-2 py-0.5 rounded fw-semibold" style="font-size: 0.65rem;">
                                    [PKG] {{ strtoupper($pPkg->name) }}
                                </span>
                            @endforeach
                        @endif
                        @if(!empty($suggested['service_ids']))
                            @foreach(\App\Models\Service::whereIn('id', (array)$suggested['service_ids'])->get() as $pSvc)
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0.5 rounded fw-semibold" style="font-size: 0.65rem;">
                                    {{ strtoupper($pSvc->name) }}
                                </span>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif

            @if(!$isStaffView && Auth::id() == $app->user_id)
                <div class="d-flex flex-column flex-sm-row gap-2 mt-2">
                    <a href="{{ route('appointments.resubmit', [$app->id, 'use_suggestions' => 1]) }}" class="btn-custom btn-accent flex-grow-1 py-2 fw-bold uppercase shadow-sm d-inline-flex align-items-center justify-content-center gap-1 text-decoration-none" style="font-size: 0.75rem;">
                        <span>Review Suggestions</span>
                    </a>
                    <a href="{{ route('appointments.resubmit', [$app->id, 'use_suggestions' => 0]) }}" class="btn-custom btn-danger-custom flex-grow-1 py-2 fw-bold uppercase d-inline-flex align-items-center justify-content-center gap-1 text-decoration-none" style="font-size: 0.75rem;">
                        <span>Resubmit Original</span>
                    </a>
                </div>
            @elseif($isStaffView)
                <div class="small text-muted fst-italic mt-2">
                    <i class="bi bi-hourglass-split me-1"></i>Awaiting patient review and resubmission.
                </div>
            @endif
        </div>
    @endif

    {{-- 2. FEEDBACK: SHOW RETURN REASON (Standard Return w/o suggestions) --}}
    @if($app->status == 'returned' && $app->return_reason && !$app->hasSuggestedChanges())
        <div class="alert-clinical p-3 mb-3 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
                <small class="text-danger fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                    Staff Feedback / Reason for Return:
                </small>
            </div>
            <p class="small mb-0 italic" style="line-height: 1.4; color: var(--text-main);">
                "{{ $app->return_reason }}"
            </p>
        </div>
    @endif

    {{-- 3. RETEST NOTICE --}}
    @if($app->status == 'retest')
        @if($isStaffView)
            <div class="alert-clinical p-3 mb-3 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                    <small class="text-danger fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                        Retesting Required
                    </small>
                </div>
                @if($app->return_reason)
                    <p class="small mb-0 text-main mt-1"><strong>Reason:</strong> {{ $app->return_reason }}</p>
                @else
                    <p class="small mb-0 text-main mt-1">Pending recollect / re-sampling.</p>
                @endif
            </div>
        @else
            <div class="alert-clinical p-3 mb-3 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                    <small class="text-danger fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                        Action Required: Retesting Needed
                    </small>
                </div>
                <p class="small mb-2 italic" style="line-height: 1.4; color: var(--text-main);">
                    Please return to the <strong>Medscreen Diagnostic Laboratory</strong> as soon as possible to undergo retesting. Your clinical sample requires a recollect or re-verification.
                </p>
                @if($app->return_reason)
                    <div class="pt-2 border-top border-danger border-opacity-25 mt-2">
                        <small class="text-danger fw-bold uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Reason for Retesting:</small>
                        <p class="small mb-0 text-main mt-1 fw-bold">{{ $app->return_reason }}</p>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- 4. CANCELED & REFUND NOTICES --}}
    @if($app->status == 'canceled')
        @if($app->cancellation_reason || ($app->payment_status !== 'invalid' && $app->return_reason))
            <div class="alert-clinical p-3 mb-3 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                    <small class="text-danger fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                        Reason for Cancellation:
                    </small>
                </div>
                <p class="small mb-0 italic" style="line-height: 1.4; color: var(--text-main);">
                    "{{ $app->cancellation_reason ?: $app->return_reason }}"
                </p>
            </div>
        @endif

        @if($app->payment_method === 'Cashless')
            @if($isStaffView)
                @if($app->payment_status === 'paid')
                    <div class="alert alert-clinical p-3 mb-3 text-warning border-warning" style="background-color: rgba(255, 193, 7, 0.05); border-left: 4px solid var(--bs-warning) !important; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-hourglass-split text-warning me-2"></i>
                            <small class="text-warning fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Refund Action Pending</small>
                        </div>
                        <p class="small mb-0 text-main mt-1">This canceled appointment has a confirmed cashless payment. Please manually process and confirm the refund below.</p>
                    </div>
                @elseif($app->payment_status === 'invalid')
                    <div class="alert-clinical p-3 mb-3 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
                            <small class="text-danger fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Refund Unavailable (Payment Invalid)</small>
                        </div>
                        <p class="small mb-2 italic" style="line-height: 1.4; color: var(--text-main);">
                            This cashless transaction has been flagged as invalid. Refund processing is bypassed as no valid payment was received.
                        </p>
                        <div class="pt-2 border-top border-danger border-opacity-25 mt-2">
                            <small class="text-danger fw-bold uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Invalidation Reason:</small>
                            <p class="small mb-0 text-main mt-1 fw-bold">{{ $app->return_reason }}</p>
                        </div>
                    </div>
                @endif
            @else
                @if($app->payment_status === 'paid')
                    <div class="alert alert-clinical p-3 mb-3 text-warning border-warning" style="background-color: rgba(255, 193, 7, 0.05); border-left: 4px solid var(--bs-warning) !important; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-hourglass-split text-warning me-2"></i>
                            <small class="text-warning fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Please wait for your payment to be refunded</small>
                        </div>
                        <p class="small mb-0 italic" style="line-height: 1.4; color: var(--text-main);">
                            Your appointment has been canceled and your cashless payment is confirmed. Please wait for our clinical team to process and finalize your refund.
                        </p>
                    </div>
                @elseif($app->payment_status === 'invalid')
                    <div class="alert-clinical p-3 mb-3 text-danger border-danger" style="background-color: rgba(220, 53, 69, 0.05); border-left: 4px solid var(--bs-danger) !important; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
                            <small class="text-danger fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Refund Unavailable (Payment Invalid)</small>
                        </div>
                        <p class="small mb-2 italic" style="line-height: 1.4; color: var(--text-main);">
                            Your uploaded proof of payment has been marked as invalid by our staff. Consequently, a refund cannot be processed for this transaction.
                        </p>
                        <div class="pt-2 border-top border-danger border-opacity-25 mt-2">
                            <small class="text-danger fw-bold uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Invalidation Reason:</small>
                            <p class="small mb-2 text-main mt-1 fw-bold">{{ $app->return_reason }}</p>
                            <p class="small mb-0 text-muted" style="font-size: 0.72rem; line-height: 1.5;">
                                If there are any issues, please contact us at <strong>medscreen.lab@gmail.com</strong> or call <strong>(083) 823 8754</strong>.
                            </p>
                        </div>
                    </div>
                @elseif($app->payment_status !== 'refunded')
                    <div class="alert-clinical p-3 mb-3 text-warning border-warning" style="background-color: rgba(255, 193, 7, 0.05); border-left: 4px solid var(--bs-warning) !important; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-hourglass-split text-warning me-2"></i>
                            <small class="text-warning fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Please wait for your payment to be confirmed and refunded</small>
                        </div>
                        <p class="small mb-0 italic" style="line-height: 1.4; color: var(--text-main);">
                            Your appointment has been canceled. Since your cashless transaction verification is still pending, please wait for our team to confirm your payment first, after which your refund will be processed.
                        </p>
                    </div>
                @endif
            @endif
        @endif
    @endif

    {{-- 5. DOCTOR'S REFERRAL NOTE --}}
    @if($app->referral_note)
        @php $refExt = pathinfo($app->referral_note, PATHINFO_EXTENSION); @endphp
        <div class="border rounded p-3 mb-3" style="background-color: rgba(25, 211, 140, 0.05); border-color: rgba(25, 211, 140, 0.15) !important;">
            <div class="d-flex justify-content-between align-items-center mb-2.5">
                <span class="small text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Doctor's Referral Note:</span>
                <span class="badge bg-info text-dark px-2 py-1 small">ATTACHED</span>
            </div>
            <div class="d-flex align-items-center gap-3 bg-white p-2 rounded border cursor-pointer" style="cursor: pointer;" onclick="if(typeof openFilePreview === 'function'){ openFilePreview('{{ Storage::url($app->referral_note) }}', 'Doctor\'s Referral Note'); }" title="Click to view full screen">
                <i class="bi bi-file-earmark-medical-fill text-accent display-6"></i>
                <div class="text-start">
                    <div class="fw-bold small text-dark text-truncate" style="max-width: 180px;">Doctor_Referral_Note{{ $refExt ? '.'.strtolower($refExt) : '' }}</div>
                    <span class="text-muted smaller"><i class="bi bi-zoom-in text-accent"></i> Click to Zoom</span>
                </div>
            </div>
        </div>
    @endif

    {{-- 6. CASHLESS PAYMENT RECEIPT (Single Booking) --}}
    @if($app->payment_method === 'Cashless' && !$app->batch_id && $app->payment_status !== 'refunded')
        <div class="border rounded p-3 mb-3" style="background-color: rgba(25, 211, 140, 0.05); border-color: rgba(25, 211, 140, 0.15) !important;">
            <div class="d-flex justify-content-between align-items-center mb-2.5">
                <span class="small text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Proof of Payment:</span>
                <span class="badge {{ $app->payment_status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }} px-2 py-1 small">
                    {{ strtoupper($app->payment_status) }}
                </span>
            </div>
            @if($app->payment_receipt)
                @php $payExt = pathinfo($app->payment_receipt, PATHINFO_EXTENSION); @endphp
                <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-3 border cursor-pointer" style="cursor: pointer;" onclick="if(typeof openFilePreview === 'function'){ openFilePreview('{{ Storage::url($app->payment_receipt) }}', 'Proof of Payment Receipt'); }" title="Click to view full screen">
                    <i class="bi bi-file-earmark-image-fill text-accent display-6"></i>
                    <div class="text-start">
                        <div class="fw-bold small text-dark text-truncate" style="max-width: 180px;">Proof_of_Payment{{ $payExt ? '.'.strtolower($payExt) : '' }}</div>
                        <span class="text-muted smaller"><i class="bi bi-zoom-in text-accent"></i> Click to Zoom</span>
                    </div>
                </div>
            @else
                <div class="alert alert-clinical border-danger text-danger text-center p-2 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> No payment receipt uploaded.
                </div>
            @endif
        </div>
    @elseif($app->payment_status === 'refunded')
        <div class="alert-clinical p-3 mb-3 text-success border-success" style="background-color: rgba(25, 211, 140, 0.05); border-left: 4px solid var(--bs-success) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-patch-check-fill text-success me-2"></i>
                <small class="text-success fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                    {{ $isStaffView ? 'The payment is refunded' : 'Your payment is refunded' }}
                </small>
            </div>
            <p class="small mb-2 italic" style="line-height: 1.4; color: var(--text-main);">
                {{ $isStaffView ? 'The payment transaction has been officially refunded.' : 'Your payment transaction has been officially refunded.' }}
            </p>
            <div class="pt-2 border-top border-success border-opacity-25 mt-2">
                <small class="text-success fw-bold uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Refund Audit Log:</small>
                <p class="small mb-2 text-main mt-1 fw-bold">{{ $app->return_reason }}</p>
                @if(!$isStaffView)
                    <p class="small mb-0 text-muted" style="font-size: 0.72rem; line-height: 1.5;">
                        If there are any issues, please contact us at <strong>medscreen.lab@gmail.com</strong> or call <strong>(083) 823 8754</strong>.
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- Staff manual verification controls in Master Queue --}}
    @if($isStaffView)
        @if(!$isVoided && !$app->batch_id && $app->payment_method === 'Cashless')
            @if($app->payment_status === 'unpaid')
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn-custom btn-accent flex-grow-1 py-2 fw-bold uppercase shadow-sm small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#confirmPaymentModal{{ $app->id }}">
                        <i class="bi bi-patch-check-fill me-1"></i> CONFIRM PAYMENT
                    </button>
                    @if($app->status === 'canceled')
                        <button type="button" class="btn-custom btn-danger-custom px-3 py-2 fw-bold small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#invalidPaymentModal{{ $app->id }}">
                            MARK INVALID
                        </button>
                    @endif
                </div>
            @elseif($app->status === 'pending')
                <button type="button" class="btn-custom btn-outline-danger w-100 py-2 fw-bold uppercase shadow-sm small mb-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#revokePaymentModal{{ $app->id }}">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> REVOKE PAYMENT
                </button>
            @endif
        @endif
    @endif

    {{-- 7. INTERNAL PERSONNEL CONTROLS (Staff Master Queue Only) --}}
    @if($isStaffView)
        <div class="staff-action-container">
            @if(!$isVoided)
                {{-- STEP A: PENDING -> APPROVE / RETURN (Includes Two-Way Return) --}}
                @if($app->status == 'pending')
                    @php
                        $isApproveDisabled = ($app->payment_method === 'Cashless' && $app->payment_status !== 'paid');
                    @endphp
                    <div class="d-flex gap-2 justify-content-end mb-2">
                        <button type="button" 
                                class="btn-custom btn-accent flex-grow-1 py-2 fw-bold {{ $isApproveDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" 
                                {{ $isApproveDisabled ? 'disabled title="Cashless payment must be confirmed before approval"' : 'data-bs-toggle=modal data-bs-target=#approveModal' . $app->id }}>
                            <i class="bi bi-check-circle me-1"></i> APPROVE
                        </button>
                        <button type="button" class="btn-custom btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{ $app->id }}" title="Return appointment (with or without suggested modifications)">
                            <i class="bi bi-arrow-return-left me-1"></i> RETURN
                        </button>
                    </div>

                    {{-- Single Appointment Approval Confirmation Modal --}}
                    <div class="modal fade action-teleport-modal" id="approveModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="z-index: 1060 !important;">
                        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
                            <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content shadow-lg border-0 ajax-action-form" data-app-id="{{ $app->id }}" onsubmit="handleAjaxActionSubmit(event, this)" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color) !important; color: var(--text-main);">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                                    <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2" style="color: var(--brand-accent) !important;">
                                        <i class="bi bi-check-circle-fill fs-5"></i>
                                        <span>Approve Appointment?</span>
                                    </h5>
                                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4 text-start">
                                    <p class="small mb-3" style="color: var(--text-main) !important;">
                                        Confirm clinical approval for patient <strong class="fw-bold" style="color: var(--brand-accent) !important;">{{ strtoupper($app->patient_name) }}</strong> (Ref #{{ $app->id }})?
                                    </p>
                                    <div class="p-3 rounded border mb-0 smaller text-muted" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
                                        <div class="d-flex justify-content-between mb-1.5">
                                            <span class="fw-semibold">Schedule Date:</span>
                                            <span class="text-main fw-bold">{{ $app->appointment_date ? \Carbon\Carbon::parse($app->appointment_date)->format('M d, Y') : 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1.5">
                                            <span class="fw-semibold">Time Slot:</span>
                                            <span class="text-main fw-bold">{{ $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : '--' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1.5">
                                            <span class="fw-semibold">Payment Method:</span>
                                            <span class="text-main fw-bold">{{ $app->payment_method }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between pt-1 border-top" style="border-color: var(--border-color) !important;">
                                            <span class="fw-semibold">Total Amount:</span>
                                            <span class="text-accent fw-bold" style="color: var(--brand-accent) !important;">₱{{ number_format($app->totalPrice(), 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
                                    <div class="d-flex w-100">
                                        <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Approve</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Confirm Refund on Canceled/Paid --}}
                @if($app->status === 'canceled' && $app->payment_status === 'paid')
                    <button type="button" class="btn-custom btn-accent btn-success w-100 py-2 fw-bold text-center mb-2" data-bs-toggle="modal" data-bs-target="#confirmRefundModal{{ $app->id }}">
                        <i class="bi bi-cash-stack me-1"></i> CONFIRM REFUND
                    </button>
                @endif

                {{-- STEP B: APPROVED / RETEST ONLY (Excludes unapproved 'needs_review') --}}
                @if($app->status == 'approved' || $app->status == 'retest')
                    @can('isLabTech')
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn-custom btn-accent flex-grow-1 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#testModal{{ $app->id }}" onclick="if(typeof setModalMainAction === 'function'){ setModalMainAction({{ $app->id }}, 'tested'); }">
                                <i class="bi bi-person-check me-1"></i> MARK AS TESTED
                            </button>
                            <button type="button" class="btn-custom btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{ $app->id }}" title="Return approved appointment for patient correction/rescheduling">
                                <i class="bi bi-arrow-return-left me-1"></i> RETURN
                            </button>
                        </div>
                        @include('appointments.partials.mark-tested-modal')
                    @else
                        <div class="d-flex gap-2 mb-2">
                            <a href="{{ route('appointments.edit-details', $app->id) }}?from=queue" class="btn-custom btn-outline-accent flex-grow-1 py-2 fw-bold text-center text-decoration-none shadow-sm uppercase" style="font-size: 0.70rem;">
                                <i class="bi bi-pencil-square me-1"></i> Edit Demographics
                            </a>
                            <button type="button" class="btn-custom btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{ $app->id }}" title="Return approved appointment for patient correction/rescheduling">
                                <i class="bi bi-arrow-return-left me-1"></i> RETURN
                            </button>
                        </div>
                        <div class="alert small py-2 mb-2 text-center" style="background-color: rgba(25, 211, 140, 0.05); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 8px;">
                            <i class="bi bi-hourglass-split me-1 text-warning"></i> Awaiting Clinical Sampling (Lab Tech Only)
                        </div>
                    @endcan
                @endif

                {{-- STEP C: TESTED -> ENCODE RESULTS & PROGRESS BREAKDOWN --}}
                @if($app->status == 'tested')
                    <div class="mb-2.5 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 text-muted fw-bold text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            <i class="bi bi-file-earmark-medical text-accent"></i>
                            <span>{{ $totalWorksheets }} {{ $totalWorksheets === 1 ? 'Worksheet' : 'Worksheets' }}</span>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="p-2 rounded border text-center h-100 d-flex flex-column justify-content-center shadow-sm"
                                     style="background-color: {{ $encodedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.08)' : ($encodedCount > 0 ? 'rgba(13, 202, 240, 0.08)' : 'rgba(245, 158, 11, 0.08)') }};
                                            border-color: {{ $encodedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.3)' : ($encodedCount > 0 ? 'rgba(13, 202, 240, 0.3)' : 'rgba(245, 158, 11, 0.25)') }} !important;
                                            border-radius: 10px;">
                                    <div class="fw-bold text-uppercase d-flex align-items-center justify-content-center gap-1"
                                         style="font-size: 0.65rem; letter-spacing: 0.5px; color: {{ $encodedCount == $totalWorksheets ? 'var(--brand-accent-hover, #15b376)' : ($encodedCount > 0 ? '#0dcaf0' : '#d97706') }};">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Encoded</span>
                                    </div>
                                    <div class="fw-bold font-monospace fs-6 mt-0.5" style="color: var(--text-main);">
                                        {{ $encodedCount }}/{{ $totalWorksheets }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded border text-center h-100 d-flex flex-column justify-content-center shadow-sm"
                                     style="background-color: {{ $verifiedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.08)' : ($verifiedCount > 0 ? 'rgba(25, 211, 140, 0.05)' : 'rgba(100, 116, 139, 0.06)') }};
                                            border-color: {{ $verifiedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.3)' : ($verifiedCount > 0 ? 'rgba(25, 211, 140, 0.2)' : 'var(--border-color)') }} !important;
                                            border-radius: 10px;">
                                    <div class="fw-bold text-uppercase d-flex align-items-center justify-content-center gap-1"
                                         style="font-size: 0.65rem; letter-spacing: 0.5px; color: {{ $verifiedCount == $totalWorksheets ? 'var(--brand-accent-hover, #15b376)' : ($verifiedCount > 0 ? 'var(--brand-accent)' : 'var(--text-muted)') }};">
                                        <i class="bi bi-shield-check"></i>
                                        <span>Verified</span>
                                    </div>
                                    <div class="fw-bold font-monospace fs-6 mt-0.5" style="color: var(--text-main);">
                                        {{ $verifiedCount }}/{{ $totalWorksheets }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('appointments.encode', $app->id) }}" class="btn-custom btn-outline-accent py-2 fw-bold text-center text-decoration-none shadow d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-pencil-square me-1.5"></i> ENCODE RESULTS</span>
                            <span class="badge bg-secondary bg-opacity-25 text-main font-monospace px-2 py-1 ms-2" style="font-size: 0.72rem;">
                                {{ $encodedCount }}/{{ $totalWorksheets }}
                            </span>
                        </a>
                        @can('isLabTech')
                            <button type="button" class="btn-custom btn-outline-danger py-2 fw-bold text-center" data-bs-toggle="modal" data-bs-target="#testModal{{ $app->id }}" onclick="if(typeof setModalMainAction === 'function'){ setModalMainAction({{ $app->id }}, 'retest'); }">
                                <i class="bi bi-arrow-repeat me-1"></i> MARK FOR RETEST
                            </button>
                            @include('appointments.partials.mark-tested-modal')
                        @endcan
                    </div>
                @endif

                {{-- STEP D: ENCODED -> VERIFICATION TRACKER --}}
                @if($app->status == 'encoded')
                    <div class="mb-2.5 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 text-muted fw-bold text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            <i class="bi bi-file-earmark-medical text-accent"></i>
                            <span>{{ $totalWorksheets }} {{ $totalWorksheets === 1 ? 'Worksheet' : 'Worksheets' }}</span>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="p-2 rounded border text-center h-100 d-flex flex-column justify-content-center shadow-sm"
                                     style="background-color: {{ $encodedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.08)' : ($encodedCount > 0 ? 'rgba(13, 202, 240, 0.08)' : 'rgba(245, 158, 11, 0.08)') }};
                                            border-color: {{ $encodedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.3)' : ($encodedCount > 0 ? 'rgba(13, 202, 240, 0.3)' : 'rgba(245, 158, 11, 0.25)') }} !important;
                                            border-radius: 10px;">
                                    <div class="fw-bold text-uppercase d-flex align-items-center justify-content-center gap-1"
                                         style="font-size: 0.65rem; letter-spacing: 0.5px; color: {{ $encodedCount == $totalWorksheets ? 'var(--brand-accent-hover, #15b376)' : ($encodedCount > 0 ? '#0dcaf0' : '#d97706') }};">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Encoded</span>
                                    </div>
                                    <div class="fw-bold font-monospace fs-6 mt-0.5" style="color: var(--text-main);">
                                        {{ $encodedCount }}/{{ $totalWorksheets }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded border text-center h-100 d-flex flex-column justify-content-center shadow-sm"
                                     style="background-color: {{ $verifiedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.08)' : ($verifiedCount > 0 ? 'rgba(25, 211, 140, 0.05)' : 'rgba(100, 116, 139, 0.06)') }};
                                            border-color: {{ $verifiedCount == $totalWorksheets ? 'rgba(25, 211, 140, 0.3)' : ($verifiedCount > 0 ? 'rgba(25, 211, 140, 0.2)' : 'var(--border-color)') }} !important;
                                            border-radius: 10px;">
                                    <div class="fw-bold text-uppercase d-flex align-items-center justify-content-center gap-1"
                                         style="font-size: 0.65rem; letter-spacing: 0.5px; color: {{ $verifiedCount == $totalWorksheets ? 'var(--brand-accent-hover, #15b376)' : ($verifiedCount > 0 ? 'var(--brand-accent)' : 'var(--text-muted)') }};">
                                        <i class="bi bi-shield-check"></i>
                                        <span>Verified</span>
                                    </div>
                                    <div class="fw-bold font-monospace fs-6 mt-0.5" style="color: var(--text-main);">
                                        {{ $verifiedCount }}/{{ $totalWorksheets }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($toEncodeCount > 0)
                            <div class="smaller text-danger fw-semibold d-flex align-items-center justify-content-center gap-1 mb-2" style="font-size: 0.72rem;">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span>{{ $toEncodeCount }} {{ $toEncodeCount === 1 ? 'returned result to re-encode' : 'returned results to re-encode' }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="d-grid">
                        @can('isLabTech')
                            <button type="button" class="btn-custom btn-accent w-100 py-2 d-flex justify-content-between align-items-center" onclick="if(typeof promptAccess === 'function'){ promptAccess('{{ $app->id }}', 'hub', 'edit'); }">
                                <span><i class="bi bi-shield-check me-1.5"></i> REVIEW & VERIFY</span>
                                <span class="badge bg-dark bg-opacity-25 text-white font-monospace px-2 py-1 ms-2" style="font-size: 0.72rem;">
                                    {{ $verifiedCount }}/{{ $totalWorksheets }}
                                </span>
                            </button>
                        @else
                            <div class="text-center text-secondary smaller italic mt-2">
                                Awaiting Lab Tech Verification ({{ $verifiedCount }}/{{ $totalWorksheets }} verified)...
                            </div>
                        @endcan
                    </div>
                @endif
            @else
                <div class="alert alert-clinical border-danger text-danger text-center p-3 small mb-0" style="background-color: rgba(220, 53, 69, 0.05);">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> This clinical appointment has been updated to <strong>VOIDED</strong> due to the patient not arriving on scheduled time without prior notice.
                </div>
            @endif
        </div>
    @endif

    {{-- 8. PATIENT CONTROL (RESUBMIT & CANCELLATION) --}}
    @if(Auth::id() == $app->user_id && !$isStaffView)
        {{-- UPDATE & RESUBMIT: Available for standard returned, canceled, or voided (NOT when suggestions are pending review, retest, or released) --}}
        @if((($app->status === 'returned' && !$app->hasSuggestedChanges()) || $app->status === 'canceled' || ($app->status === 'needs_review' && !$app->hasSuggestedChanges()) || $isVoided) && $app->status !== 'released')
            <div class="d-flex gap-2">
                <a href="{{ route('appointments.resubmit', $app->id) }}" class="btn-custom btn-accent flex-grow-1 py-3 fw-bold shadow text-center text-decoration-none">
                    <i class="bi bi-arrow-repeat me-2"></i> UPDATE & RESUBMIT APPOINTMENT
                </a>
            </div>
            <div class="text-center mt-2">
                <small class="text-secondary" style="font-size: 0.65rem;">* Edit your details and pick a new schedule to reactivate.</small>
            </div>
        @endif

        {{-- Show individual cancel button ONLY if pending or needs_review --}}
        @if(($app->status === 'pending' || $app->status === 'needs_review') && !$isVoided)
            <button type="button" class="btn-custom btn-outline-danger w-100 py-2 fw-bold text-center mt-2" data-bs-toggle="modal" data-bs-target="#cancelAppointmentModal{{ $app->id }}">
                <i class="bi bi-x-circle me-1"></i> CANCEL APPOINTMENT
            </button>
        @endif

        @if($isVoided && !$app->deleted_by_patient)
            <div class="border-top border-secondary border-opacity-10 mt-3 pt-3">
                @if($app->canBeDeletedByPatient())
                    <button type="button" class="btn-custom btn-outline-danger w-100 py-2 fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#deleteExpiredModal{{ $app->id }}">
                        <i class="bi bi-trash me-1"></i> Delete Voided Record
                    </button>
                @else
                    <div class="alert alert-clinical border-secondary text-secondary text-center p-2 small mb-0">
                        <i class="bi bi-lock-fill me-1"></i> Financial Record Locked (Paid transactions cannot be deleted).
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>

{{-- AUTOMATIC MODAL TELEPORTATION SCRIPT: Moves modals to <body> to prevent stacking context traps --}}
<script>
(function() {
    function teleportModalsToBody() {
        const pane = document.getElementById('actions-pane-{{ $app->id }}');
        if (pane) {
            pane.querySelectorAll('.modal').forEach(function(m) {
                if (m.parentElement !== document.body) {
                    const stale = document.getElementById(m.id);
                    if (stale && stale !== m && stale.parentElement === document.body) {
                        stale.remove();
                    }
                    document.body.appendChild(m);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', teleportModalsToBody);
    } else {
        teleportModalsToBody();
    }

    if (!window.__modalGlobalTeleportHandlerBound) {
        window.__modalGlobalTeleportHandlerBound = true;
        document.addEventListener('show.bs.modal', function(e) {
            const modal = e.target;
            if (modal && modal.classList && modal.classList.contains('modal')) {
                if (modal.parentElement !== document.body) {
                    if (modal.id) {
                        const stale = document.getElementById(modal.id);
                        if (stale && stale !== modal && stale.parentElement === document.body) {
                            stale.remove();
                        }
                    }
                    document.body.appendChild(modal);
                }
            }
        }, true);
    }
})();
</script>