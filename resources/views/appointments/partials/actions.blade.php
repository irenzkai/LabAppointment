@php
    // Intercept status metrics on-the-fly to represent unprogressed expired records safely
    $isExpired = $app->isExpired();

    $statusColor = $isExpired ? 'danger' : match($app->status) {
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

    $statusLabel = $isExpired ? 'EXPIRED' : strtoupper($app->status);
@endphp

<div class="mt-2 text-start">

    {{-- 1. FEEDBACK: SHOW RETURN REASON (Visible to Everyone) --}}
    @if($app->status == 'returned' && $app->return_reason)
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

    {{-- 2. RETEST NOTICE (Context-Aware: Distinguishes Patient vs Staff) --}}
    @if($app->status == 'retest')
        @if(auth()->user()->isEmployee())
            {{-- Staff View: Shows strictly the retest reason cleanly --}}
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
            {{-- Patient View: Shows actionable instructions and the reason --}}
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

    {{-- 3. CANCELED & REFUND HANDSHAKE NOTICES (Visible to Everyone) --}}
    @if($app->status == 'canceled')
        @if($app->payment_method === 'Cashless')
            @if(auth()->user()->isEmployee())
                {{-- Staff View: Informative Refund Pending alert --}}
                @if($app->payment_status === 'paid')
                    <div class="alert-clinical p-3 mb-3 text-warning border-warning" style="background-color: rgba(255, 193, 7, 0.05); border-left: 4px solid var(--bs-warning) !important; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-hourglass-split text-warning me-2"></i>
                            <small class="text-warning fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Refund Action Pending</small>
                        </div>
                        <p class="small mb-0 text-main mt-1">This canceled appointment has a confirmed cashless payment. Please manually process and confirm the refund below.</p>
                    </div>
                @endif
            @else
                {{-- Patient View: Dynamic Cashless Refund Handshake --}}
                @if($app->payment_status === 'paid')
                    <div class="alert-clinical p-3 mb-3 text-warning border-warning" style="background-color: rgba(255, 193, 7, 0.05); border-left: 4px solid var(--bs-warning) !important; border-radius: 8px;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-hourglass-split text-warning me-2"></i>
                            <small class="text-warning fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Please wait for your payment to be refunded</small>
                        </div>
                        <p class="small mb-0 italic" style="line-height: 1.4; color: var(--text-main);">
                            Your appointment has been canceled and your cashless payment is confirmed. Please wait for our clinical team to process and finalize your refund.
                        </p>
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

    {{-- 4. ONLINE PAYMENT RECEIPT AUDIT TRAIL (Cashless verification - Visible to Everyone) --}}
    {{-- Only display individual receipt container if the appointment is NOT part of a bulk batch --}}
    @if($app->payment_method === 'Cashless' && !$app->batch_id && $app->payment_status !== 'refunded')
        <div class="border rounded p-3 mb-3" style="background-color: rgba(25, 211, 140, 0.05); border-color: rgba(25, 211, 140, 0.15) !important;">
            <div class="d-flex justify-content-between align-items-center mb-2.5">
                <span class="small text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">proof of payment:</span>
                <span class="badge {{ $app->payment_status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }} px-2 py-1 small">
                    {{ strtoupper($app->payment_status) }}
                </span>
            </div>

            @if($app->payment_receipt)
                {{-- Click-to-zoom thumbnail of proof of payment receipt file --}}
                <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-3 border" style="cursor: zoom-in;" onclick="window.zoomQR('{{ Storage::url($app->payment_receipt) }}')" title="Click to view full screen">
                    <i class="bi bi-file-earmark-image-fill text-accent display-6"></i>
                    <div class="text-start">
                        <div class="fw-bold small text-dark text-truncate" style="max-width: 140px;">proof_of_payment.png</div>
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
        {{-- Unified View: Your payment is refunded --}}
        <div class="alert-clinical p-3 mb-3 text-success border-success" style="background-color: rgba(25, 211, 140, 0.05); border-left: 4px solid var(--bs-success) !important; border-radius: 8px;">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-patch-check-fill text-success me-2"></i>
                <small class="text-success fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Your payment is refunded</small>
            </div>
            <p class="small mb-2 italic" style="line-height: 1.4; color: var(--text-main);">
                Your payment transaction has been officially refunded.
            </p>
            <div class="pt-2 border-top border-success border-opacity-25 mt-2">
                <small class="text-success fw-bold uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Refund Audit Log:</small>
                <p class="small mb-2 text-main mt-1 fw-bold">{{ $app->return_reason }}</p>
                <p class="small mb-0 text-muted" style="font-size: 0.72rem; line-height: 1.5;">
                    If there are any issues, please contact us at <strong>medscreen.lab@gmail.com</strong> or call <strong>(083) 823 8754</strong>.
                </p>
            </div>
        </div>
    @endif

    {{-- Staff manual verification & rollback controls --}}
    @can('isStaff')
        @if(!$isExpired)
            @if($app->payment_method === 'Cashless')
                @if($app->payment_status === 'unpaid')
                    {{-- Double-checking validation trigger --}}
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-custom btn-accent flex-grow-1 py-2 fw-bold uppercase shadow-sm small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#confirmPaymentModal{{ $app->id }}">
                            <i class="bi bi-patch-check-fill me-1"></i> CONFIRM PAYMENT
                        </button>
                        @if($app->status === 'canceled')
                            {{-- Mark as Invalid replaces the traditional return block for canceled files --}}
                            <button type="button" class="btn-custom btn-danger-custom px-3 py-2 fw-bold small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#invalidPaymentModal{{ $app->id }}">
                                MARK INVALID
                            </button>
                        @endif
                    </div>
                @else
                    {{-- Rollback trigger button (Only renders if appointment status is still pending) --}}
                    @if($app->status === 'pending')
                        <button type="button" class="btn-custom btn-outline-danger w-100 py-2 fw-bold uppercase shadow-sm small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#revokePaymentModal{{ $app->id }}">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> REVOKE PAYMENT
                        </button>
                    @endif
                @endif
            @endif
        @endif
    @endcan

    {{-- 5. INTERNAL PERSONNEL CONTROLS (Staff, Lab Tech, Admin) --}}
    @can('isStaff')
        <div class="staff-action-container">
            @if(!$isExpired)
                {{-- STEP A: PENDING -> APPROVE, RETURN, or CANCEL (Administrative) --}}
                {{-- Only display individual approval forms if the appointment is NOT part of a bulk batch --}}
                @php
                    // Enable individual actions for single appointments, or bulk records once payment is confirmed [263]
                    $allowIndividualActions = !$app->batch_id || ($app->payment_method === 'Cash' || $app->payment_status === 'paid');
                @endphp

                @if($app->status == 'pending' && $allowIndividualActions)
                    <div class="d-flex gap-2 justify-content-end mb-2">
                        <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="flex-grow-1 m-0">
                            @csrf 
                            @method('PATCH')
                            <input type="hidden" name="status" value="approved">

                            {{-- Disabled only if payment is Cashless AND remains unpaid [263] --}}
                            @php
                                $isApproveDisabled = ($app->payment_method === 'Cashless' && $app->payment_status !== 'paid');
                            @endphp
                            <button type="submit" class="btn-custom btn-accent w-100 py-2 fw-bold {{ $isApproveDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isApproveDisabled ? 'disabled title="Cashless payment must be confirmed before approval"' : '' }}>
                                <i class="bi bi-check-circle me-1"></i> APPROVE
                            </button>
                        </form>
                        <button type="button" class="btn-custom btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{$app->id}}">
                            RETURN
                        </button>
                    </div>
                @endif

                {{-- Note: CANCEL APPOINTMENT action for staff has been removed from this block --}}

                {{-- Confirm Refund option on Canceled, Paid folders --}}
                @if($app->status === 'canceled' && $app->payment_status === 'paid')
                    <button type="button" class="btn-custom btn-accent btn-success w-100 py-2 fw-bold text-center" data-bs-toggle="modal" data-bs-target="#confirmRefundModal{{ $app->id }}">
                        <i class="bi bi-cash-stack me-1"></i> CONFIRM REFUND
                    </button>
                @endif

                {{-- STEP B: APPROVED/RETEST -> MARK AS TESTED (Clinical Sampling - Lab Tech Only) --}}
                @if($app->status == 'approved' || $app->status == 'retest')
                    @can('isLabTech')
                        <button type="button" class="btn-custom btn-accent w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#testModal{{$app->id}}">
                            <i class="bi bi-person-check me-1"></i> MARK PATIENT AS TESTED
                        </button>
                    @else
                        {{-- Fixed "Awaiting Lab Sampling" banner --}}
                        <div class="alert small py-2.5 mb-0 text-center" style="background-color: rgba(25, 211, 140, 0.05); color: var(--text-main); border: 1.5px solid var(--border-color); border-radius: 8px;">
                            <i class="bi bi-hourglass-split me-1 text-warning"></i> Awaiting Clinical Sampling (Lab Tech Only)
                        </div>
                    @endcan
                @endif

                {{-- STEP C: TESTED -> ENCODE RESULTS & OPTIONAL RETEST (Direct Hub Link) --}}
                @if($app->status == 'tested')
                    <div class="d-grid gap-2">
                        <a href="{{ route('appointments.encode', $app->id) }}" class="btn-custom btn-outline-accent py-2 fw-bold text-center text-decoration-none shadow">
                            <i class="bi bi-pencil-square me-1"></i> ENCODE RESULTS
                        </a>
                        <button type="button" class="btn-custom btn-outline-danger py-2 fw-bold text-center" data-bs-toggle="modal" data-bs-target="#testModal{{$app->id}}">
                            <i class="bi bi-arrow-repeat me-1"></i> MARK FOR RETEST
                        </button>
                    </div>
                @endif

                {{-- STEP D: ENCODED -> VERIFICATION TRACKER (For Reviewers) --}}
                @if($app->status == 'encoded')
                    <div class="d-grid">
                        @can('isLabTech')
                            <button type="button" class="btn-custom btn-accent w-100 py-2" onclick="promptAccess('{{$app->id}}', 'hub', 'edit')">
                                <i class="bi bi-shield-check me-1"></i> REVIEW & VERIFY
                            </button>
                        @else
                            <div class="text-center text-secondary smaller italic mt-2">
                                Awaiting Lab Tech Verification...
                            </div>
                        @endif
                    </div>
                @endif
            @else
                {{-- Lock actions for clinicians if the unprogressed record is expired --}}
                <div class="alert alert-clinical border-danger text-danger text-center p-2.5 small mb-0" style="background-color: rgba(220, 53, 69, 0.05);">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> This clinical appointment has expired (24-hour unprogressed rule). Awaiting patient rescheduling.
                </div>
            @endif
        </div>
    @endcan

    {{-- 6. USER/PATIENT CONTROL (RESUBMIT & CANCELLATION) --}}
    @if(Auth::id() == $app->user_id)
        {{-- Allow resubmission/rescheduling strictly for returned, canceled, and dynamically expired unreleased records --}}
        @if(($app->status == 'returned' || $app->status == 'canceled' || $isExpired) && $app->status !== 'released')
            <div class="d-flex gap-2">
                <button type="button" class="btn-custom btn-accent flex-grow-1 py-3 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#resubmitModal{{$app->id}}">
                    <i class="bi bi-arrow-repeat me-2"></i> UPDATE & RESUBMIT APPOINTMENT
                </button>
            </div>
            <div class="text-center mt-2">
                <small class="text-secondary" style="font-size: 0.65rem;">* Edit your details and pick a new schedule to reactivate.</small>
            </div>
        @endif

        {{-- Patient self-cancellation button available before tested (sampling completed) stage --}}
        @if(in_array($app->status, ['pending', 'approved', 'returned']))
            <button type="button" class="btn-custom btn-outline-danger w-100 py-2 fw-bold text-center mt-2" data-bs-toggle="modal" data-bs-target="#cancelAppointmentModal{{ $app->id }}">
                <i class="bi bi-x-circle me-1"></i> CANCEL APPOINTMENT
            </button>
        @endif

        {{-- OPTIONAL SOFT-DELETE ACTION FOR EXPIRED RECORDS --}}
        @if($isExpired && !$app->deleted_by_patient)
            <div class="border-top border-secondary border-opacity-10 mt-3 pt-3">
                @if($app->canBeDeletedByPatient())
                    <button type="button" class="btn-custom btn-outline-danger w-100 py-2 fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#deleteExpiredModal{{ $app->id }}">
                        <i class="bi bi-trash me-1"></i> Delete Expired Record
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