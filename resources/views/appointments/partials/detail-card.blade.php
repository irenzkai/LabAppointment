@php
// Declared globally at the top so it is safely defined for both Bulk and Individual workspaces
$statusColor = match($app->status) {
    'pending' => 'warning',
    'approved' => 'info',
    'tested' => 'info',
    'encoded' => 'info',
    'released' => 'accent',
    'returned' => 'danger',
    default => 'secondary'
};
@endphp

@if($app->batch_id)
    {{-- ==========================================================================
    A. BULK BATCH WORKSPACE
    ========================================================================== --}}
    @php
    // Fetch all patients in batch and sort so 'returned' status floats to the top
    $batchAppointments = \App\Models\Appointment::with(['services', 'result', 'user'])
        ->where('batch_id', $app->batch_id)
        ->get()
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

    // Check if any individual bulk appointment is already approved
    $anyApproved = $batchAppointments->contains(fn($appointment) => in_array($appointment->status, ['approved', 'tested', 'encoded', 'released']));
    @endphp

    <div id="details-{{ $app->id }}" class="appointment-detail-pane card border-secondary bg-card p-4 d-none animate-page">
        
        {{-- Batch Header --}}
        <div class="border-bottom border-secondary border-opacity-25 pb-3 mb-4 text-start">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    {{-- Switched static class 'text-white' to dynamic theme variable color matching --}}
                    <h4 class="fw-bold mb-1 uppercase tracking-tighter" style="font-size: 1.4rem; color: var(--text-main) !important;">
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
                        {{ $app->status }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Consolidated Batch Level Controls for Staff (Unified Single Payment & Return) --}}
        @can('isStaff')
            @php
                // Evaluate visibility conditions for each batch-level button
                $showConfirm = ($app->payment_status === 'unpaid' && $app->payment_method === 'Cashless');
                $showRevoke = ($app->status === 'pending' && $app->payment_method === 'Cashless' && $app->payment_status === 'paid');
                $showReturn = ($app->status === 'pending' && !$anyApproved);
                $showApprove = ($app->status === 'pending');

                // Batch controls are only shown if at least one of these actions is available
                $showBatchControls = $showConfirm || $showRevoke || $showReturn || $showApprove;
            @endphp

            @if($showBatchControls)
                <div class="batch-controls-panel card p-4 mb-4 text-start animate-page">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-accent fw-bold small uppercase mb-0">
                            <i class="bi bi-shield-lock-fill me-1.5"></i>Batch Controls (One-Time Payment)
                        </h6>
                    </div>

                    {{-- Consolidated single cashless receipt preview displayed prominently at the batch level --}}
                    @if($app->payment_method === 'Cashless')
                        <div class="border rounded p-3 mb-3 text-start border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.03);">
                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                <span class="small text-muted fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Batch Proof of Payment:</span>
                                <span class="badge {{ $app->payment_status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }} px-2 py-1 small">
                                    {{ strtoupper($app->payment_status) }}
                                </span>
                            </div>
                            
                            @if($app->payment_receipt)
                                {{-- Click-to-zoom thumbnail of proof of payment receipt file --}}
                                <div class="d-flex align-items-center gap-3 bg-white p-2 rounded mb-1" style="cursor: zoom-in; max-width: 260px;" onclick="window.zoomQR('{{ Storage::url($app->payment_receipt) }}')" title="Click to view full screen">
                                    <i class="bi bi-file-earmark-image-fill text-accent display-6"></i>
                                    <div class="text-start">
                                        <div class="fw-bold small text-dark text-truncate" style="max-width: 140px;">proof_of_payment.png</div>
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
                        {{-- One-Time Payment Confirmation only appears for Cashless/Online payments --}}
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
                        
                        {{-- Return Entire Batch button disappears once any bulk appointment is approved --}}
                        @if($showReturn)
                            <button type="button" class="btn-custom btn-danger-custom py-2 px-3 fw-bold uppercase small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#returnBatchModal{{ $app->id }}">
                                <i class="bi bi-x-circle me-1"></i> RETURN ENTIRE BATCH
                            </button>
                        @endif
                        
                        {{-- Batch Approval triggers a modal confirmation --}}
                        @if($showApprove)
                            <button type="button" class="btn-custom btn-neon py-2 px-3 fw-bold uppercase small {{ $isBatchApproveDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isBatchApproveDisabled ? 'disabled title="Batch cashless payment must be confirmed before approval"' : '' }} data-bs-toggle="modal" data-bs-target="#approveBatchModal{{ $app->id }}" style="font-size: 0.75rem;">
                                <i class="bi bi-check-circle me-1"></i> APPROVE BATCH
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        @endcan

        {{-- Patient Batch Payment Resubmission Interface --}}
        @if(!auth()->user()->isEmployee() && $app->status === 'returned')
            <div class="card p-4 mb-4 batch-resubmit-card animate-page text-start">
                <h5 class="text-accent fw-bold mb-2 uppercase small">
                    <i class="bi bi-shield-lock-fill me-2"></i>Resubmit Batch Payment
                </h5>
                <p class="small text-muted mb-4">
                    This bulk booking has been flagged for payment issues. Please upload a revised proof of transaction receipt or select "Cash on Site" to resubmit the entire batch.
                </p>

                <form action="{{ route('appointments.resubmit-batch', $app->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="payment_method" id="batch_pay_cash_{{ $app->id }}" value="Cash" checked onchange="toggleBatchPaymentFields('{{ $app->id }}')">
                            <label class="btn payment-method-card w-100 p-4 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="batch_pay_cash_{{ $app->id }}">
                                <i class="bi bi-cash-stack fs-2 mb-2"></i>
                                <div class="fw-bold uppercase payment-title">Cash on Site</div>
                                <div class="smaller text-muted mt-1">Settle at the reception counter.</div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <input type="radio" class="btn-check" name="payment_method" id="batch_pay_cashless_{{ $app->id }}" value="Cashless" onchange="toggleBatchPaymentFields('{{ $app->id }}')">
                            <label class="btn payment-method-card w-100 p-4 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="batch_pay_cashless_{{ $app->id }}">
                                <i class="bi bi-qr-code-scan fs-2 mb-2"></i>
                                <div class="fw-bold uppercase payment-title">Online / E-Wallet</div>
                                <div class="smaller text-muted mt-1">Re-upload digital payment receipt.</div>
                            </label>
                        </div>

                        {{-- Dynamic E-Wallet Selector Grid --}}
                        <div id="batch_provider_container_{{ $app->id }}" class="col-12 d-none mt-3">
                            <label class="text-accent smaller fw-bold uppercase d-block mb-3">Choose E-Wallet Provider</label>
                            <div class="row g-3">
                                @foreach($paymentProviders as $provider)
                                    <div class="col-md-4 col-6">
                                        <input type="radio" class="btn-check batch-provider-radio-{{ $app->id }}" name="payment_provider_id" id="batch_prov_{{ $app->id }}_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}" onchange="updateBatchQR('{{ $app->id }}', this)">
                                        <label class="btn payment-method-card w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="batch_prov_{{ $app->id }}_{{ $provider->id }}">
                                            @if($provider->logo)
                                                <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" class="mb-2" style="height: 32px; object-fit: contain;">
                                            @else
                                                <i class="bi bi-wallet2 fs-3 mb-2 text-secondary"></i>
                                            @endif
                                            <div class="small fw-bold uppercase payment-title">{{ $provider->name }}</div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- QR Code Display Container with Proper Lightbox Linkage --}}
                        <div id="batch_qr_section_{{ $app->id }}" class="col-12 d-none mt-3">
                            <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                                <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Scan to Pay (<span id="batch_selected_provider_name_{{ $app->id }}" class="text-accent"></span>)</h6>
                                <div class="d-flex justify-content-center">
                                    {{-- Triggers zoomQR lightbox properly passing the correct image source --}}
                                    <div style="cursor: zoom-in;" onclick="window.zoomQR(document.getElementById('batch_selected_provider_qr_{{ $app->id }}').src)" title="Click to view full screen">
                                        <img src="" id="batch_selected_provider_qr_{{ $app->id }}" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
                                    </div>
                                </div>
                                <p class="text-muted smaller mt-3 mb-0 italic" style="font-size: 0.7rem;">
                                    <i class="bi bi-zoom-in me-1 text-accent"></i> Click the QR code image to view it full screen.<br>
                                    Please take a screenshot of your successful transaction to present upon arrival.
                                </p>
                            </div>
                        </div>

                        {{-- File Upload --}}
                        <div id="batch_receipt_container_{{ $app->id }}" class="col-12 d-none mt-3">
                            <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>
                            <input type="file" name="payment_receipt" id="batch_in_receipt_{{ $app->id }}" class="form-control py-3 shadow-none" accept="image/*, application/pdf">
                            <div class="mt-1">
                                <small class="text-muted smaller">
                                    <i class="bi bi-info-circle me-1"></i> Required: Upload a PDF or image copy of your GCash/Maya transaction receipt to finalize.
                                </small>
                            </div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn-custom btn-accent px-5 py-3 fw-bold uppercase shadow-lg">
                                RESUBMIT BATCH PAYMENT
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <script>
                function toggleBatchPaymentFields(appId) {
                    const payCashless = document.getElementById(`batch_pay_cashless_${appId}`);
                    const providerContainer = document.getElementById(`batch_provider_container_${appId}`);
                    const receiptContainer = document.getElementById(`batch_receipt_container_${appId}`);
                    const receiptInput = document.getElementById(`batch_in_receipt_${appId}`);
                    const qrSection = document.getElementById(`batch_qr_section_${appId}`);
                    const radios = document.querySelectorAll(`.batch-provider-radio-${appId}`);

                    if (payCashless && payCashless.checked) {
                        if (providerContainer) providerContainer.classList.remove('d-none');
                        if (receiptContainer) receiptContainer.classList.remove('d-none');
                        if (receiptInput) receiptInput.setAttribute('required', 'required');

                        const activeRadio = document.querySelector(`.batch-provider-radio-${appId}:checked`);
                        if (activeRadio) {
                            if (qrSection) qrSection.classList.remove('d-none');
                        } else {
                            if (qrSection) qrSection.classList.add('d-none');
                        }
                    } else {
                        if (providerContainer) providerContainer.classList.add('d-none');
                        if (receiptContainer) receiptContainer.classList.add('d-none');
                        if (receiptInput) receiptInput.removeAttribute('required');
                        if (qrSection) qrSection.classList.add('d-none');
                        radios.forEach(radio => radio.checked = false);
                    }
                }

                function updateBatchQR(appId, radio) {
                    if (radio.checked) {
                        document.getElementById(`batch_selected_provider_qr_${appId}`).src = radio.dataset.qr;
                        document.getElementById(`batch_selected_provider_name_${appId}`).innerText = radio.dataset.name;
                        document.getElementById(`batch_qr_section_${appId}`).classList.remove('d-none');
                        toggleBatchPaymentFields(appId);
                    }
                }
            </script>
        @endif

        {{-- Batch Modals --}}
        
        {{-- Safe text color styles applied to guarantee readability on light/dark themes --}}
        <!-- Confirm Batch Payment Modal -->
        <div class="modal fade" id="confirmBatchPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    <div class="modal-header border-secondary border-bottom border-opacity-10 py-3">
                        <h5 class="modal-title text-accent fw-bold uppercase small m-0">Confirm Batch Payment?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small mb-0 text-muted" style="color: var(--text-muted) !important;">
                            Are you sure you want to flag this entire batch as <span class="fw-bold text-accent">PAID</span>? This will confirm payment receipt for all <span class="fw-bold text-accent">{{ $batchAppointments->count() }}</span> patients in the batch <span class="fw-bold text-accent">{{ $app->organization_name }}</span>.
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

        @if($app->payment_method === 'Cashless')
            <!-- Revoke Batch Payment Modal -->
            <div class="modal fade" id="revokeBatchPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                        <div class="modal-header border-danger border-bottom border-opacity-10 py-3">
                            <h5 class="modal-title text-danger fw-bold uppercase small m-0">Revoke Batch Payment?</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            <p class="small mb-0 text-muted" style="color: var(--text-muted) !important;">
                                ? This will rollback payment confirmation details for all <span class="fw-bold text-danger">{{ $batchAppointments->count() }}</span> patients in the batch <span class="fw-bold text-danger">{{ $app->organization_name }}</span>.
                            </p>
                        </div>
                        <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                            <div class="d-flex w-100">
                                <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                                <form action="{{ route('appointments.confirm-payment', $app->id) }}" method="POST" class="m-0">
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

        {{-- Added Approve All confirmation modal --}}
        <!-- Approve Batch Modal -->
        <div class="modal fade" id="approveBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    <div class="modal-header border-secondary border-bottom border-opacity-10 py-3">
                        <h5 class="modal-title text-accent fw-bold uppercase small m-0">Confirm Batch Approval?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small mb-0 text-muted" style="color: var(--text-muted) !important;">
                            Are you sure you want to approve the entire batch <span class="fw-bold text-accent">{{ $app->organization_name }}</span>? This will approve all <span class="fw-bold text-accent">{{ $batchAppointments->count() }}</span> appointments in this batch and notify the patients.
                        </p>
                    </div>
                    <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                        <div class="d-flex w-100">
                            <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                            <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="w-50 m-0">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Approve All</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Return Entire Batch Modal featuring more detailed return reasons --}}
        <div class="modal fade" id="returnBatchModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="returned">
                    <div class="modal-header border-danger border-bottom border-opacity-10 py-3">
                        <h5 class="modal-title text-danger fw-bold uppercase small m-0">Return Entire Batch?</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small mb-3 text-muted" style="color: var(--text-muted) !important;">
                            Are you sure you want to return the entire batch <span class="fw-bold text-danger">{{ $app->organization_name }}</span>? This will reject all <span class="fw-bold text-danger">{{ $batchAppointments->count() }}</span> appointments.
                        </p>
                        
                        <div class="mb-3">
                            <label class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Reason for Return</label>
                            <select id="batch_return_reason_select_{{ $app->id }}" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                                <option value="No payment received / incomplete payment">No payment received / incomplete payment</option>
                                <option value="Blurry or unreadable payment receipt file">Blurry or unreadable payment receipt file</option>
                                <option value="Mismatched transaction reference number or sender name">Mismatched transaction reference number or sender name</option>
                                <option value="Incorrect billing amount paid">Incorrect billing amount paid</option>
                                <option value="Others">Others (Specify details below)</option>
                            </select>
                        </div>
                        
                        {{-- Hidden custom reason textbox --}}
                        <div id="batch_custom_return_reason_wrapper_{{ $app->id }}" class="mb-3 d-none">
                            <label for="batch_return_reason_{{ $app->id }}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                            <textarea name="return_reason" id="batch_return_reason_{{ $app->id }}" class="form-control shadow-none" rows="3" placeholder="Identify the specific payment correction needed..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                        <div class="d-flex w-100">
                            <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Return All</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Live Search & Batch Statistics --}}
        <div class="row g-3 mb-4 align-items-center text-start">
            <div class="col-md-7">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Search patient name in this batch..." oninput="filterBulkPatients('{{ $app->batch_id }}', this.value)">
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="text-accent fw-bold small" style="font-size: 0.85rem;">
                    BATCH TOTAL: {{ number_format($batchTotal, 2) }}
                </div>
            </div>
        </div>

        {{-- Scrollable Patient Deck --}}
        <div class="d-flex flex-column gap-3 overflow-auto custom-scroll pe-1 mb-2" style="max-height: 480px;">
            @foreach($batchAppointments as $subApp)
                @php
                $subStatusColor = match($subApp->status) {
                    'pending' => 'warning',
                    'approved' => 'info',
                    'tested' => 'info',
                    'encoded' => 'info',
                    'released' => 'accent',
                    'returned' => 'danger',
                    default => 'secondary'
                };
                @endphp

                {{-- Individual Patient Card --}}
                <div class="card p-3 border-secondary border-opacity-25 bg-card bulk-patient-row-{{ $app->batch_id }}" data-name="{{ strtoupper($subApp->patient_name) }}">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2 text-start">
                        <div>
                            <span class="text-main fw-bold small uppercase me-2" style="font-size: 0.9rem;">{{ $subApp->patient_name }}</span>
                            <small class="text-secondary" style="font-size: 0.65rem;">
                                {{ $subApp->patient_age }} YRS <span class="mx-1">|</span> 
                                {{ strtoupper($subApp->patient_sex) }} <span class="mx-1">|</span> 
                                ID: #{{ $subApp->id }}
                            </small>
                        </div>
                        <span class="badge border border-{{ $subStatusColor }} text-{{ $subStatusColor == 'accent' ? 'success' : $subStatusColor }} uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                            {{ $subApp->status }}
                        </span>
                    </div>

                    {{-- Patient Tests List --}}
                    <div class="text-start border-bottom border-secondary border-opacity-10 pb-2 mb-3">
                        <small class="text-accent fw-bold d-block mb-1 uppercase" style="font-size: 0.6rem;">Tests Requested:</small>
                        <div class="text-main small" style="font-size: 0.8rem;">
                            {{ $subApp->services->pluck('name')->implode(', ') }} 
                            <span class="text-muted ms-2">({{ number_format($subApp->totalPrice(), 2) }})</span>
                        </div>
                    </div>

                    {{-- Individual Patient Context Workflows --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted" style="font-size: 0.7rem;">
                            <i class="bi bi-clock-history me-1"></i>Scheduled: {{ date('h:i A', strtotime($subApp->time_slot)) }}
                        </div>
                        <div class="bulk-actions-container">
                            @if($subApp->status == 'released')
                                <div class="d-flex gap-1.5 align-items-center">
                                    <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'preview']) }}" target="_blank" class="btn-custom btn-outline-accent btn-sm py-1 px-2.5" style="font-size: 0.7rem;">PREVIEW</a>
                                    <a href="{{ route('appointments.result.access', [$subApp->id, 'lab', 'download']) }}" class="btn-custom btn-accent btn-sm py-1 px-2.5" style="font-size: 0.7rem;">DOWNLOAD</a>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 cursor-not-allowed border-secondary border-opacity-25" style="font-size: 0.7rem;" disabled title="Email triggers are disabled in demo mode.">
                                        <i class="bi bi-envelope-at"></i>
                                    </button>
                                </div>
                            {{-- Automatically hides individual resubmit buttons if there is an active batch resubmit payment form --}}
                            @elseif($app->status === 'returned' && !auth()->user()->isEmployee())
                                <span class="text-warning small fw-bold"><i class="bi bi-info-circle-fill me-1"></i> Settle Batch Payment Above</span>
                            @else
                                {{-- Dynamically render active status transition controllers --}}
                                @include('appointments.partials.actions', ['app' => $subApp])
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@else
    {{-- ==========================================================================
    B. INDIVIDUAL / FAMILY WORKSPACE
    ========================================================================== --}}
    <div id="details-{{ $app->id }}" class="appointment-detail-pane card border-secondary bg-card p-4 d-none animate-page">
        
        {{-- Detailed Header Section --}}
        <div class="border-bottom border-secondary border-opacity-25 pb-3 mb-4 text-start">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="text-main fw-bold mb-1 uppercase tracking-tighter" style="font-size: 1.4rem;">
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
                        {{ $app->status }}
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
                            <span class="text-muted"> {{ number_format($service->price, 2) }}</span>
                        </li>
                    @endforeach
                    <li class="list-group-item text-accent fw-bold d-flex justify-content-between border-top border-secondary border-opacity-25 py-2.5" style="background-color: rgba(25, 211, 140, 0.05) !important;">
                        <span>TOTAL BILLING</span>
                        <span> {{ number_format($app->totalPrice(), 2) }}</span>
                    </li>
                </ul>
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
                    
                    <div class="mb-4">
                        <small class="text-muted fw-bold d-block mb-1 uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Contact Number:</small>
                        <div class="text-main small">{{ $app->patient_phone }}</div>
                    </div>
                </div>

                {{-- Actions Panel --}}
                <div class="border-top border-secondary border-opacity-25 pt-3 mt-3">
                    @include('appointments.partials.actions', ['app' => $app])
                </div>
            </div>

        </div>
    </div>
@endif

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

/* Re-designed clinical workspace panel styles to enhance contrast and typography flow */
.batch-controls-panel {
    border-left: 4px solid var(--brand-accent) !important;
    background-color: var(--bg-card) !important;
    border-top: 1px solid var(--border-color) !important;
    border-right: 1px solid var(--border-color) !important;
    border-bottom: 1px solid var(--border-color) !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03) !important;
}

/* Clean high-contrast custom alert style for batch resubmission */
.batch-resubmit-card {
    border-left: 4px solid var(--brand-accent) !important;
    background-color: rgba(25, 211, 140, 0.04) !important;
    border-color: rgba(25, 211, 140, 0.15) !important;
}
[data-bs-theme="dark"] .batch-resubmit-card {
    background-color: rgba(25, 211, 140, 0.02) !important;
}

/* Payment Method Selection Cards Style adjustments */
.payment-method-card {
    border: 1.5px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}
.payment-method-card i {
    color: var(--text-muted) !important;
    transition: all 0.2s ease;
}

/* Highlights selected payment method cleanly with themed border-accent glow */
.btn-check:checked + .payment-method-card {
    background-color: rgba(25, 211, 140, 0.07) !important;
    border-color: var(--brand-accent) !important;
    border-width: 2px !important;
    box-shadow: 0 0 12px rgba(25, 211, 140, 0.15) !important;
}
.btn-check:checked + .payment-method-card i,
.btn-check:checked + .payment-method-card .payment-title {
    color: var(--brand-accent) !important;
}
[data-bs-theme="light"] .btn-check:checked + .payment-method-card i,
[data-bs-theme="light"] .btn-check:checked + .payment-method-card .payment-title {
    color: #15b376 !important;
}
</style>

@push('scripts')
<script>
/**
 * Small reactive filter helper to search patient cards inside a Bulk Batch detail workspace
 */
function filterBulkPatients(batchId, query) {
    const cleanQuery = query.trim().toUpperCase();
    document.querySelectorAll(`.bulk-patient-row-${batchId}`).forEach(row => {
        const name = row.getAttribute('data-name') || '';
        if (name.includes(cleanQuery)) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
    });
}

// Handle return reason dropdown for batch returns
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.batch-return-reason-select').forEach(selectEl => {
        const appId = selectEl.dataset.appId;
        const wrapper = document.getElementById(`batch_custom_return_reason_wrapper_${appId}`);
        const textarea = document.getElementById(`batch_return_reason_${appId}`);
        const form = selectEl.closest('form');
        
        if (selectEl && textarea && wrapper) {
            selectEl.addEventListener('change', function() {
                if (this.value === 'Others') {
                    wrapper.classList.remove('d-none');
                    textarea.setAttribute('required', 'required');
                    textarea.value = '';
                } else {
                    wrapper.classList.add('d-none');
                    textarea.removeAttribute('required');
                    textarea.value = this.value;
                }
            });
            
            if (form) {
                form.addEventListener('submit', function() {
                    if (selectEl.value !== 'Others') {
                        textarea.value = selectEl.value;
                    }
                });
            }
        }
    });
});
</script>
@endpush