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

    {{-- 2. ONLINE PAYMENT RECEIPT AUDIT TRAIL (Cashless verification - Visible to Everyone) --}}
    {{-- Only display individual receipt container if the appointment is NOT part of a bulk batch --}}
    @if($app->payment_method === 'Cashless' && !$app->batch_id)
        <div class="border rounded p-3 mb-3" style="background-color: rgba(25, 211, 140, 0.05); border-color: rgba(25, 211, 140, 0.15) !important;">
            <div class="d-flex justify-content-between align-items-center mb-2.5">
                <span class="small text-secondary fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">proof of payment:</span>
                <span class="badge {{ $app->payment_status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }} px-2 py-1 small">
                    {{ strtoupper($app->payment_status) }}
                </span>
            </div>

            @if($app->payment_receipt)
                {{-- Click-to-zoom thumbnail of proof of payment receipt file --}}
                <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-3 border" style="cursor: zoom-in;" onclick="zoomQR('{{ Storage::url($app->payment_receipt) }}')" title="Click to view full screen">
                    <i class="bi bi-file-earmark-image-fill text-accent display-6"></i>
                    <div class="text-start">
                        <div class="fw-bold small text-dark text-truncate" style="max-width: 140px;">proof_of_payment.png</div>
                        <span class="text-muted smaller" style="font-size: 0.75rem;"><i class="bi bi-zoom-in text-accent"></i> Click to Zoom</span>
                    </div>
                </div>
            @else
                <div class="alert alert-clinical border-danger text-danger text-center p-2 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> No payment receipt uploaded.
                </div>
            @endif
        </div>
    @endif

    {{-- Staff manual verification & rollback controls --}}
    @can('isStaff')
        @if(!$isExpired)
            @if($app->payment_method === 'Cashless')
                @if($app->payment_status === 'unpaid')
                    {{-- Double-checking validation trigger --}}
                    <button type="button" class="btn-custom btn-accent w-100 py-2 fw-bold uppercase shadow-sm small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#confirmPaymentModal{{ $app->id }}">
                        <i class="bi bi-patch-check-fill me-1"></i> CONFIRM PAYMENT
                    </button>
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

    {{-- 3. INTERNAL PERSONNEL CONTROLS (Staff, Lab Tech, Admin) --}}
    @can('isStaff')
        <div class="staff-action-container">
            @if(!$isExpired)
                {{-- STEP A: PENDING -> APPROVE or RETURN (Administrative) --}}
                {{-- Only display individual approval forms if the appointment is NOT part of a bulk batch --}}
                @php
                    // Enable individual actions for single appointments, or bulk records once payment is confirmed [263]
                    $allowIndividualActions = !$app->batch_id || ($app->payment_method === 'Cash' || $app->payment_status === 'paid');
                @endphp

                @if($app->status == 'pending' && $allowIndividualActions)
                    <div class="d-flex gap-2 justify-content-end">
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

                {{-- STEP B: APPROVED -> MARK AS TESTED (Clinical Sampling - Lab Tech Only) --}}
                @if($app->status == 'approved')
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

                {{-- STEP C: TESTED -> ENCODE RESULTS (Direct Hub Link) --}}
                @if($app->status == 'tested')
                    <div class="d-grid">
                        <a href="{{ route('appointments.encode', $app->id) }}" class="btn-custom btn-outline-accent py-2 fw-bold text-center text-decoration-none shadow">
                            <i class="bi bi-pencil-square me-1"></i> ENCODE RESULTS
                        </a>
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
                        @endcan
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

    {{-- 4. USER/PATIENT CONTROL (RESUBMIT & SOFT DELETE) --}}
    @if(Auth::id() == $app->user_id)
        {{-- Allow resubmission/rescheduling for both returned and dynamically expired unreleased records --}}
        @if(($app->status == 'returned' || $isExpired) && $app->status !== 'released')
            <div class="d-flex gap-2">
                <button type="button" class="btn-custom btn-accent flex-grow-1 py-3 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#resubmitModal{{$app->id}}">
                    <i class="bi bi-arrow-repeat me-2"></i> UPDATE & RESUBMIT APPOINTMENT
                </button>
            </div>
            <div class="text-center mt-2">
                <small class="text-secondary" style="font-size: 0.65rem;">* Edit your details and pick a new schedule to reactivate.</small>
            </div>
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