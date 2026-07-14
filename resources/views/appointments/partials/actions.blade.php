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
                <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-3" style="cursor: zoom-in;" onclick="zoomQR('{{ Storage::url($app->payment_receipt) }}')" title="Click to view full screen">
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

            {{-- Staff manual verification & rollback controls --}}
            @can('isStaff')
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

                    {{-- Revoke Confirmation Modal --}}
                    <div class="modal fade" id="revokePaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                                <div class="modal-header border-danger border-bottom border-opacity-10 py-3">
                                    <h5 class="modal-title text-danger fw-bold uppercase small m-0">Revoke Payment Status?</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4 text-start">
                                    <p class="small mb-0 text-muted" style="color: var(--text-main) !important;">
                                        Are you sure you want to revert this appointment's payment status to <strong class="text-danger fw-bold">UNPAID</strong>? This will rollback confirmation details for patient <strong style="color: var(--text-main) !important;">{{ $app->patient_name }}</strong>.
                                    </p>
                                </div>
                                <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                                    <div class="d-flex w-100">
                                        <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                                        <form action="{{ route('appointments.confirm-payment', $app->id) }}" method="POST" class="w-50 m-0">
                                            @csrf
                                            <input type="hidden" name="payment_status" value="unpaid">
                                            <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Revoke</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Payment Confirmation Handshake Modal --}}
                <div class="modal fade" id="confirmPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                            <div class="modal-header border-secondary border-bottom border-opacity-10 py-3">
                                <h5 class="modal-title text-accent fw-bold uppercase small m-0">Confirm Payment Receipt?</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 text-start">
                                <p class="small mb-0 text-muted" style="color: var(--text-main) !important;">
                                    Are you sure you want to flag this appointment as <strong class="fw-bold" style="color: var(--text-main) !important;">PAID</strong>? This will confirm manual receipt of funds for patient <strong class="fw-bold" style="color: var(--text-main) !important;">{{ $app->patient_name }}</strong>.
                                </p>
                            </div>
                            <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                                <div class="d-flex w-100">
                                    <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                                    <form action="{{ route('appointments.confirm-payment', $app->id) }}" method="POST" class="w-50 m-0">
                                        @csrf
                                        <input type="hidden" name="payment_status" value="paid">
                                        <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Confirm</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    @endif

    {{-- 3. INTERNAL PERSONNEL CONTROLS (Staff, Lab Tech, Admin) --}}
    @can('isStaff')
        <div class="staff-action-container">
            
            {{-- STEP A: PENDING -> APPROVE or RETURN (Administrative) --}}
            {{-- Only display individual approval forms if the appointment is NOT part of a bulk batch --}}
            @if($app->status == 'pending' && !$app->batch_id)
                <div class="d-flex gap-2 justify-content-end">
                    <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="flex-grow-1 m-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        
                        {{-- Disabled only if payment is Cashless AND remains unpaid --}}
                        @php
                            $isApproveDisabled = ($app->payment_method === 'Cashless' && $app->payment_status !== 'paid');
                        @endphp
                        <button type="submit" class="btn-custom btn-neon w-100 py-2 fw-bold {{ $isApproveDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isApproveDisabled ? 'disabled title="Cashless payment must be confirmed before approval"' : '' }}>
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
                    <button type="button" class="btn-custom btn-neon w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#testModal{{$app->id}}">
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
                    <a href="{{ route('appointments.encode', $app->id) }}" class="btn-custom btn-neon py-2 fw-bold text-center text-decoration-none shadow">
                        <i class="bi bi-pencil-square me-1"></i> ENCODE RESULTS
                    </a>
                </div>
            @endif

            {{-- STEP D: ENCODED -> VERIFICATION TRACKER (For Reviewers) --}}
            @if($app->status == 'encoded')
                <div class="d-grid">
                    @can('isLabTech')
                        <button type="button" class="btn-custom btn-neon w-100 py-2" onclick="promptAccess('{{$app->id}}', 'hub', 'edit')">
                            <i class="bi bi-shield-check me-1"></i> REVIEW & VERIFY
                        </button>
                    @else
                        <div class="text-center text-secondary smaller italic mt-2">
                            Awaiting Lab Tech Verification...
                        </div>
                    @endcan
                </div>
            @endif

        </div>
    @endcan

    {{-- 4. USER/PATIENT CONTROL (RESUBMIT & SOFT DELETE) --}}
    @if($app->status == 'returned' && Auth::id() == $app->user_id)
        <div class="d-flex gap-2">
            <button type="button" class="btn-custom btn-neon flex-grow-1 py-3 fw-bold shadow" data-bs-toggle="modal" data-bs-target="#resubmitModal{{$app->id}}">
                <i class="bi bi-arrow-repeat me-2"></i> UPDATE & RESUBMIT APPOINTMENT
            </button>
        </div>
        <div class="text-center mt-2">
            <small class="text-secondary" style="font-size: 0.65rem;">* Edit your details and pick a new schedule to continue.</small>
        </div>
    @endif

    {{-- 5. OPTIONAL SOFT-DELETE ACTION FOR EXPIRED RECORDS --}}
    @if($app->isExpired() && !$app->deleted_by_patient && Auth::id() == $app->user_id)
        <div class="border-top border-secondary border-opacity-10 mt-3 pt-3">
            @if($app->canBeDeletedByPatient())
                <form action="{{ route('appointments.soft-delete', $app->id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn-custom btn-outline-danger w-100 py-2 fw-bold uppercase" onclick="return confirm('Are you sure you want to remove this expired appointment from your dashboard?')">
                        <i class="bi bi-trash me-1"></i> Delete Expired Record
                    </button>
                </form>
            @else
                <div class="alert alert-clinical border-secondary text-secondary text-center p-2 small mb-0">
                    <i class="bi bi-lock-fill me-1"></i> Financial Record Locked (Paid transactions cannot be deleted).
                </div>
            @endif
        </div>
    @endif

    {{-- 6. CLINICAL RESULTS FOR RELEASED FOLDERS --}}
    @if($app->status === 'released')
        <div class="mt-3">
            <h6 class="text-accent smaller fw-bold uppercase mb-2">Released Clinical Folders</h6>
            @if(!auth()->user()->isEmployee())
                {{-- FIXED: Dynamically lists and compiles distinct preview/download actions for ALL active workstation results --}}
                @php
                    $includedReports = $app->result->included_reports ?? ['lab'];
                @endphp
                <div class="d-flex flex-column gap-2">
                    @foreach($includedReports as $reportType)
                        @php
                            $reportLabel = match($reportType) {
                                'lab' => 'Laboratory Results',
                                'med_cert' => 'Medical Certificate',
                                'radio' => 'Radiology Report',
                                'drug' => 'Drug Test Screening',
                                default => strtoupper($reportType) . ' Findings'
                            };
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-2.5 border border-secondary border-opacity-10 rounded" style="background-color: rgba(25, 211, 140, 0.03);">
                            <span class="small fw-bold text-main">{{ $reportLabel }}</span>
                            <div class="d-flex gap-1.5">
                                <a href="{{ route('appointments.result.access', [$app->id, $reportType, 'preview']) }}" target="_blank" class="btn btn-sm btn-outline-accent fw-bold" style="font-size: 0.7rem;">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>PREVIEW
                                </a>
                                <a href="{{ route('appointments.result.access', [$app->id, $reportType, 'download']) }}" class="btn btn-sm btn-accent fw-bold" style="font-size: 0.7rem;">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Staff/Technician Secure Access (Requires Reason-Gate Logging) --}}
                <button type="button" class="btn-custom btn-outline-accent w-100 py-2 fw-bold" onclick="promptAccess('{{$app->id}}', 'hub', 'edit')">
                    <i class="bi bi-shield-lock-fill me-1"></i>REVIEW & EDIT CLINICAL FILES
                </button>
            @endif
        </div>
    @endif

</div>

<style>
/* Scoped alignment CSS for inline bulk actions */
[class^="bulk-patient-row-"] .bulk-actions-container > .mt-2,
[class^="bulk-patient-row-"] .bulk-actions-container > .mt-3,
[class^="bulk-patient-row-"] .bulk-actions-container .staff-action-container,
[class^="bulk-patient-row-"] .bulk-actions-container .d-flex {
    margin-top: 0 !important;
    padding-top: 0 !important;
    border-top: none !important;
}
[class^="bulk-patient-row-"] .bulk-actions-container form {
    margin: 0 !important;
}
[class^="bulk-patient-row-"] .bulk-actions-container {
    display: inline-flex;
    align-items: center;
}
</style>