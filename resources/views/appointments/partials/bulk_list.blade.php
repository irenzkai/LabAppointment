<!-- resources/views/appointments/partials/bulk_list.blade.php -->

<div class="accordion" id="bulkAccordion">
    @forelse($groups as $batchId => $apps)
        @php 
            $first = $apps->first(); 
            $batchGrandTotal = $apps->sum(fn($a) => $a->totalPrice());
            $isRecognizedOrg = !empty($first->organization_id);
            $currentUser = Auth::user();
            $isBatchMaker = ($first->user_id === $currentUser->id);

            // Payment issue check
            $paymentStatuses = $apps->pluck('payment_status')->toArray();
            $hasPaymentIssue = in_array('invalid', $paymentStatuses) || in_array('refunded', $paymentStatuses);

            // Filter active non-voided/non-canceled appointments
            $activeApps = $apps->reject(fn($a) => $a->isVoided() || $a->status === 'canceled');

            // Active milestone priority (lowest active bottleneck first)
            $activePriority = [
                'returned'     => 1,
                'needs_review' => 2,
                'retest'       => 3,
                'pending'      => 4,
                'approved'     => 5,
                'tested'       => 6,
                'encoded'      => 7,
                'released'     => 8,
            ];

            if ($activeApps->isNotEmpty()) {
                $lowestPriority = 999;
                $lowestStatus = 'released';

                foreach ($activeApps as $subApp) {
                    $priority = $activePriority[$subApp->status] ?? 99;
                    if ($priority < $lowestPriority) {
                        $lowestPriority = $priority;
                        $lowestStatus = $subApp->status;
                    }
                }
                $finalBatchStatus = $lowestStatus;
                $isBatchVoided = false;
            } else {
                $allCanceled = $apps->every(fn($a) => $a->status === 'canceled');
                $finalBatchStatus = $allCanceled ? 'canceled' : 'voided';
                $isBatchVoided = ($finalBatchStatus === 'voided');
            }

            $batchBadgeColor = match($finalBatchStatus) {
                'voided', 'expired' => 'danger',
                'pending'           => 'warning',
                'needs_review'      => 'warning',
                'approved'          => 'info',
                'tested'            => 'info',
                'encoded'           => 'info',
                'released'          => 'success',
                'returned'          => 'danger',
                'retest'            => 'danger',
                'canceled'          => 'danger',
                default             => 'secondary'
            };

            $batchStatusLabel = match($finalBatchStatus) {
                'voided', 'expired' => 'VOIDED',
                'needs_review'      => 'REVIEW NEEDED',
                default             => strtoupper(str_replace('_', ' ', $finalBatchStatus))
            };
        @endphp

        <div class="accordion-item mb-3 bg-card border rounded-3 overflow-hidden shadow-sm" style="border-color: var(--border-color) !important;">
            <h2 class="accordion-header" id="heading-{{ $batchId }}">
                <button class="accordion-button collapsed bg-card text-main py-3.5 px-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#batch-{{ $batchId }}" aria-expanded="false" aria-controls="batch-{{ $batchId }}">
                    <div class="row w-100 align-items-center g-2 text-start">
                        {{-- Entity Name, Type Badge & Batch Metrics --}}
                        <div class="col-12 col-md-5">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="fw-bold text-main fs-6 uppercase tracking-tight">{{ $first->organization_name }}</span>
                                @if($isRecognizedOrg)
                                    <span class="badge bg-success bg-opacity-15 text-accent border border-success border-opacity-30 uppercase" style="font-size: 0.6rem;">
                                        <i class="bi bi-patch-check-fill me-0.5"></i> RECOGNIZED PARTNER
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase" style="font-size: 0.6rem;">
                                        STANDARD BULK
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <small class="text-secondary fw-semibold uppercase" style="font-size: 0.72rem;">
                                    <i class="bi bi-people-fill me-1"></i>{{ $apps->count() }} Patients
                                </small>
                                <small class="text-accent fw-bold uppercase" style="font-size: 0.75rem;">
                                    Batch Total: ₱{{ number_format($batchGrandTotal, 2) }}
                                </small>
                            </div>
                        </div>

                        {{-- Submission Date & Synchronized Milestone Status --}}
                        <div class="col-6 col-md-4 text-start text-md-center">
                            <div class="text-secondary smaller fw-bold uppercase mb-0.5" style="font-size: 0.65rem;">
                                Submitted {{ $first->created_at->format('M d, Y') }}
                            </div>
                            <span class="badge bg-{{ $batchBadgeColor }} bg-opacity-15 text-{{ $batchBadgeColor }} border border-{{ $batchBadgeColor }} border-opacity-30 uppercase px-2.5 py-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                                {{ $batchStatusLabel }}
                            </span>
                        </div>

                        {{-- Batch Identifier & Accordion Indicator --}}
                        <div class="col-6 col-md-3 text-end d-flex align-items-center justify-content-end gap-2">
                            <span class="font-monospace text-muted smaller d-none d-sm-inline">#{{ $batchId }}</span>
                            <i class="bi bi-chevron-down text-accent fs-5"></i>
                        </div>
                    </div>
                </button>
            </h2>

            <div id="batch-{{ $batchId }}" class="accordion-collapse collapse" data-bs-parent="#bulkAccordion">
                <div class="accordion-body p-0 border-top" style="border-color: var(--border-color) !important; background-color: var(--bg-card);">
                    
                    {{-- Cashless Payment Resubmission Notice --}}
                    @if($hasPaymentIssue && $isBatchMaker)
                        <div class="p-3 border-bottom bg-danger bg-opacity-10 text-start d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-color: rgba(220, 53, 69, 0.25) !important;">
                            <div class="small text-danger">
                                <i class="bi bi-exclamation-triangle-fill me-1.5"></i>
                                <strong>Payment Issue:</strong> Your uploaded transaction receipt was flagged as invalid by clinic staff. Please upload a verified proof of payment.
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold uppercase" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#batchReceiptModal-{{ $batchId }}">
                                <i class="bi bi-upload me-1"></i> Resubmit Receipt
                            </button>
                        </div>

                        {{-- Batch Receipt Resubmission Modal --}}
                        <div class="modal fade" id="batchReceiptModal-{{ $batchId }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
                                <form method="POST" action="{{ route('appointments.batch.resubmit-receipt', $batchId) }}" enctype="multipart/form-data" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
                                    @csrf
                                    <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                                        <h5 class="modal-title text-accent fw-bold uppercase small m-0">Resubmit Payment Receipt</h5>
                                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p class="small text-muted mb-3">Upload a fresh PDF or image of your transaction receipt for Batch #{{ $batchId }}.</p>
                                        <input type="file" name="payment_receipt" class="form-control" accept="image/*, application/pdf" required>
                                    </div>
                                    <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn-custom btn-accent py-2 px-3 fw-bold uppercase">Submit Receipt</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Standard Bulk Patient Confidentiality Notice --}}
                    @if(!$isRecognizedOrg && $isBatchMaker)
                        <div class="p-3 border-bottom text-start bg-main" style="border-color: var(--border-color) !important;">
                            <div class="small text-muted d-flex align-items-center gap-2">
                                <i class="bi bi-shield-lock-fill text-warning fs-5 flex-shrink-0"></i>
                                <span>
                                    <strong>Patient Confidentiality Protection:</strong> Under Standard Bulk booking, medical test results are strictly private and dispatched directly to each participant's personal account/email. Batch coordinators cannot view or download individual member results.
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" style="color: var(--text-main); min-width: 900px;">
                            <thead class="bg-brand-dark text-secondary small uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                <tr>
                                    <th class="ps-4 py-3" style="width: 25%;">Patient Details</th>
                                    <th style="width: 20%;">Contact Info</th>
                                    <th style="width: 20%;">Schedule</th>
                                    <th style="width: 15%;">Status</th>
                                    <th class="text-end" style="width: 10%;">Total</th>
                                    <th class="text-end pe-4" style="width: 10%;">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($apps as $app)
                                    @php
                                        $isVoided = $app->isVoided();
                                        $statusClass = match($app->status) {
                                            'released'              => 'bg-success text-success border-success',
                                            'tested', 'encoded'     => 'bg-primary text-primary border-primary',
                                            'approved'              => 'bg-info text-info border-info',
                                            'pending', 'needs_review' => 'bg-warning text-warning border-warning',
                                            default                 => 'bg-danger text-danger border-danger'
                                        };
                                        if ($isVoided) {
                                            $statusClass = 'bg-danger text-danger border-danger';
                                        }
                                        $isPatientSelf = ($app->patient_email && strtolower($currentUser->email) === strtolower($app->patient_email));
                                        $canAccessResults = $currentUser->isEmployee() || $isPatientSelf || ($isBatchMaker && $isRecognizedOrg);
                                    @endphp

                                    <tr class="border-secondary border-opacity-10 align-middle">
                                        {{-- Patient Details --}}
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-main uppercase fs-6">{{ $app->patient_name }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-0.5">
                                                <span class="badge {{ $app->patient_sex === 'Male' ? 'bg-info text-info border-info' : 'bg-danger text-danger border-danger' }} bg-opacity-10 border border-opacity-25 uppercase" style="font-size: 0.62rem;">
                                                    {{ strtoupper($app->patient_sex ?: 'N/A') }}
                                                </span>
                                                <span class="text-secondary smaller fw-semibold">{{ $app->patient_age }} YRS</span>
                                            </div>
                                        </td>

                                        {{-- Contact Info --}}
                                        <td class="small">
                                            <div><i class="bi bi-telephone text-accent me-1"></i>{{ $app->patient_phone }}</div>
                                            <div class="text-muted smaller text-truncate" style="max-width: 180px;"><i class="bi bi-envelope text-accent me-1"></i>{{ $app->patient_email }}</div>
                                        </td>

                                        {{-- Schedule --}}
                                        <td>
                                            <div class="small text-main fw-semibold">{{ $app->appointment_date ? \Carbon\Carbon::parse($app->appointment_date)->format('M d, Y') : 'N/A' }}</div>
                                            <div class="text-accent smaller font-monospace fw-bold">{{ $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : '--' }}</div>
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            @if($isVoided)
                                                <span class="badge {{ $statusClass }} bg-opacity-15 border border-opacity-30 uppercase px-2 py-1" style="font-size: 0.68rem;">
                                                    VOIDED
                                                </span>
                                                <small class="text-danger d-block mt-0.5" style="font-size: 0.62rem;">No-show within schedule window.</small>
                                            @else
                                                <span class="badge {{ $statusClass }} bg-opacity-15 border border-opacity-30 uppercase px-2 py-1" style="font-size: 0.68rem;">
                                                    {{ strtoupper(str_replace('_', ' ', $app->status)) }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Patient Total --}}
                                        <td class="text-end text-accent fw-bold small">
                                            ₱{{ number_format($app->totalPrice(), 2) }}
                                        </td>

                                        {{-- Details Action --}}
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-accent py-1 px-2.5 fw-bold uppercase text-nowrap" style="font-size: 0.7rem; border-radius: 6px;" data-bs-toggle="collapse" data-bs-target="#details-{{ $app->id }}">
                                                View Tests
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Expanded Row: Tests Breakdown & Clinical Result Actions --}}
                                    <tr id="details-{{ $app->id }}" class="collapse bg-main text-start">
                                        <td colspan="6" class="p-4 border-0">
                                            <div class="row g-4 align-items-start">
                                                {{-- Left Column: Diagnostic Tests & Packages Itemization --}}
                                                <div class="col-12 col-md-7">
                                                    <h6 class="text-accent small fw-bold uppercase mb-2.5 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                                                        <i class="bi bi-flask me-1.5"></i> Tests Requested for {{ $app->patient_name }}
                                                    </h6>
                                                    <div class="p-2.5 rounded bg-card border" style="border-color: var(--border-color) !important;">
                                                        {{-- Bundled Packages --}}
                                                        @foreach($app->packages as $pkg)
                                                            <div class="small d-flex justify-content-between align-items-start p-2 border-bottom" style="border-color: var(--border-color) !important;">
                                                                <div>
                                                                    <div class="d-flex align-items-center gap-1">
                                                                        <span class="badge bg-success bg-opacity-15 text-accent border border-success border-opacity-30 uppercase" style="font-size: 0.58rem;">PKG</span>
                                                                        <strong class="text-main">{{ strtoupper($pkg->name) }}</strong>
                                                                    </div>
                                                                    <div class="text-muted smaller mt-0.5">Includes: {{ $pkg->services->pluck('name')->implode(', ') }}</div>
                                                                </div>
                                                                <span class="text-accent fw-bold text-nowrap">₱{{ number_format($pkg->price, 2) }}</span>
                                                            </div>
                                                        @endforeach

                                                        {{-- Standalone Services --}}
                                                        @foreach($app->services as $s)
                                                            <div class="small text-main d-flex justify-content-between align-items-center p-2 border-bottom" style="border-color: var(--border-color) !important;">
                                                                <span>{{ strtoupper($s->name) }}</span>
                                                                <span class="text-accent fw-bold">₱{{ number_format($s->price, 2) }}</span>
                                                            </div>
                                                        @endforeach

                                                        {{-- Subtotal --}}
                                                        <div class="d-flex justify-content-between align-items-center p-2 mt-1 fw-bold small text-main">
                                                            <span class="uppercase">Patient Subtotal</span>
                                                            <span class="text-accent fs-6">₱{{ number_format($app->totalPrice(), 2) }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Right Column: Clinical Action Controls & Privacy Enforcement --}}
                                                <div class="col-12 col-md-5">
                                                    <h6 class="text-secondary small fw-bold uppercase mb-2.5 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                                                        <i class="bi bi-shield-check me-1.5"></i> Clinical File & Result
                                                    </h6>

                                                    @if($app->status === 'released')
                                                        @if($canAccessResults)
                                                            <div class="p-3 rounded border bg-card text-center" style="border-color: var(--border-color) !important;">
                                                                <i class="bi bi-file-earmark-medical-fill fs-2 text-accent d-block mb-2"></i>
                                                                <span class="small fw-bold text-main d-block mb-1">Diagnostic Report Available</span>
                                                                <small class="text-muted d-block mb-3">Clinically verified results ready for review.</small>
                                                                
                                                                <div class="d-flex gap-2 justify-content-center">
                                                                    <a href="{{ route('appointments.result.access', ['appointment' => $app->id, 'type' => 'lab', 'mode' => 'preview']) }}" target="_blank" class="btn btn-sm btn-accent py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-eye me-1"></i> View Result
                                                                    </a>
                                                                    <a href="{{ route('appointments.result.access', ['appointment' => $app->id, 'type' => 'lab', 'mode' => 'download']) }}" class="btn btn-sm btn-outline-accent py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-download me-1"></i> Download
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="p-3 rounded border bg-card text-center text-muted small" style="border-color: var(--border-color) !important;">
                                                                <i class="bi bi-lock-fill fs-2 text-warning d-block mb-2"></i>
                                                                <strong class="text-main d-block mb-1">Results Released Directly to Patient</strong>
                                                                <span>Per Standard Bulk policy, confidential diagnostic results are dispatched directly to <strong>{{ $app->patient_email }}</strong> and cannot be viewed by the booking coordinator.</span>
                                                            </div>
                                                        @endif
                                                    @elseif($isVoided)
                                                        {{-- Patient-facing Voided Notice in Accordion Details --}}
                                                        <div class="p-3 rounded border bg-card text-center text-muted small" style="border-color: var(--border-color) !important;">
                                                            <i class="bi bi-calendar-x fs-2 text-danger d-block mb-1"></i>
                                                            <strong class="text-danger d-block mb-1">Appointment Voided (No-Show)</strong>
                                                            <span class="d-block mb-2">This appointment was marked as <strong class="text-danger">VOIDED</strong> due to non-arrival within the 24-hour schedule window without prior notice.</span>
                                                            @if(!$currentUser->isEmployee())
                                                                <span class="smaller text-muted d-block">You may update your details to reschedule, or delete this record from your appointments view.</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        {{-- Clinical Processing State --}}
                                                        <div class="p-3 rounded border bg-card text-center text-muted small" style="border-color: var(--border-color) !important;">
                                                            <i class="bi bi-hourglass-split fs-2 text-accent opacity-75 d-block mb-1"></i>
                                                            <strong class="text-main d-block mb-1">In Processing</strong>
                                                            <span>Examinations are currently undergoing clinical sampling, analysis, and validation.</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card p-5 text-center text-secondary border-dashed bg-card small italic" style="border-color: var(--border-color) !important; border-radius: 12px;">
            <i class="bi bi-collection fs-1 text-muted d-block mb-2 opacity-50"></i>
            No bulk appointment records found.
        </div>
    @endforelse
</div>