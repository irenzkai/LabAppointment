<!-- resources/views/appointments/index.blade.php -->
@extends('layouts.app')
@section('title', 'My Appointments')

@section('content')
<div class="container-fluid text-start animate-page py-2" id="patient-appointments-root">
    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 border-bottom pb-3 gap-2" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 text-uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                My Appointments
            </h2>
            <p class="text-muted mb-0 small" style="color: var(--text-muted) !important;">
                Track your diagnostic bookings, review schedule details, and access released clinical records.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('patient.history') }}" class="btn-custom btn-outline-accent px-3 py-2 d-inline-flex align-items-center gap-2 text-decoration-none" style="font-size: 0.8rem;">
                <i class="bi bi-clock-history"></i>
                <span>VIEW ARCHIVED RECORDS</span>
            </a>
            <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent px-3 py-2 d-inline-flex align-items-center gap-2 text-decoration-none" style="font-size: 0.8rem;">
                <i class="bi bi-plus-circle-fill"></i>
                <span>NEW BOOKING</span>
            </a>
        </div>
    </div>

    {{-- Split Pane Grid Layout --}}
    <div class="row g-4 position-relative">
        {{-- LEFT PANEL: Navigation Tabs & Appointment Cards --}}
        <div class="col-12 col-lg-5 col-xl-4" id="left-queue-panel">
            {{-- Navigation Tabs --}}
            <ul class="nav nav-pills mb-3 gap-1 bg-secondary bg-opacity-10 p-1.5 rounded-3 border" style="border-color: var(--border-color) !important;" id="patientAppTabs" role="tablist">
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link active w-100 fs-x-small fw-bold text-uppercase py-2" id="tab-self" data-bs-toggle="pill" data-bs-target="#pane-self" type="button" role="tab" aria-controls="pane-self" aria-selected="true" onclick="resetActiveDetail()">
                        <i class="bi bi-person-fill me-1"></i> Myself ({{ $self->total() }})
                    </button>
                </li>
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link w-100 fs-x-small fw-bold text-uppercase py-2" id="tab-family" data-bs-toggle="pill" data-bs-target="#pane-family" type="button" role="tab" aria-controls="pane-family" aria-selected="false" onclick="resetActiveDetail()">
                        <i class="bi bi-people-fill me-1"></i> Family ({{ $dependents->total() }})
                    </button>
                </li>
                <li class="nav-item flex-grow-1" role="presentation">
                    <button class="nav-link w-100 fs-x-small fw-bold text-uppercase py-2" id="tab-bulk" data-bs-toggle="pill" data-bs-target="#pane-bulk" type="button" role="tab" aria-controls="pane-bulk" aria-selected="false" onclick="resetActiveDetail()">
                        <i class="bi bi-buildings-fill me-1"></i> Bulk Batches ({{ $bulkPaginator->total() }})
                    </button>
                </li>
            </ul>

            {{-- Tab Content Lists --}}
            <div class="tab-content position-relative" id="listContent">
                {{-- 1. PATIENT: MYSELF TAB --}}
                <div class="tab-pane fade show active" id="pane-self" role="tabpanel" aria-labelledby="tab-self">
                    <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 680px;">
                        @forelse($self as $app)
                            @include('appointments.partials.list-card', ['app' => $app, 'groupCount' => 1])
                        @empty
                            <div class="card p-5 text-center text-muted border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 12px;">
                                <i class="bi bi-calendar-x text-accent fs-1 mb-3 opacity-75" style="color: var(--brand-accent) !important;"></i>
                                <h6 class="text-main fw-bold uppercase mb-1">No Personal Appointments</h6>
                                <p class="small mb-3 text-muted" style="color: var(--text-muted) !important;">You have not booked any appointments for yourself yet.</p>
                                <a href="{{ route('appointments.create') }}" class="btn-custom btn-outline-accent py-1.5 px-3 smaller text-decoration-none">Book Appointment</a>
                            </div>
                        @endforelse
                    </div>
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $self->links('appointments.partials.pagination') }}
                    </div>
                </div>

                {{-- 2. PATIENT: FAMILY DEPENDENTS TAB --}}
                <div class="tab-pane fade" id="pane-family" role="tabpanel" aria-labelledby="tab-family">
                    <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 680px;">
                        @forelse($dependents as $app)
                            @include('appointments.partials.list-card', ['app' => $app, 'groupCount' => 1])
                        @empty
                            <div class="card p-5 text-center text-muted border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 12px;">
                                <i class="bi bi-people text-accent fs-1 mb-3 opacity-75" style="color: var(--brand-accent) !important;"></i>
                                <h6 class="text-main fw-bold uppercase mb-1">No Family Appointments</h6>
                                <p class="small mb-3 text-muted" style="color: var(--text-muted) !important;">No appointments have been booked for family dependents.</p>
                                <a href="{{ route('dependents.create') }}" class="btn-custom btn-outline-accent py-1.5 px-3 smaller text-decoration-none">Add Dependent</a>
                            </div>
                        @endforelse
                    </div>
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $dependents->links('appointments.partials.pagination') }}
                    </div>
                </div>

                {{-- 3. PATIENT: CORPORATE / BULK TAB --}}
                <div class="tab-pane fade" id="pane-bulk" role="tabpanel" aria-labelledby="tab-bulk">
                    <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 680px;">
                        @forelse($bulkGroups as $batchId => $group)
                            @php $first = $group->first(); @endphp
                            @include('appointments.partials.list-card', [
                                'app' => $first, 
                                'groupCount' => $group->count(), 
                                'batchId' => $batchId
                            ])
                        @empty
                            <div class="card p-5 text-center text-muted border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 12px;">
                                <i class="bi bi-buildings text-accent fs-1 mb-3 opacity-75" style="color: var(--brand-accent) !important;"></i>
                                <h6 class="text-main fw-bold uppercase mb-1">No Bulk Batches</h6>
                                <p class="small mb-3 text-muted" style="color: var(--text-muted) !important;">No corporate or organization group bookings found.</p>
                                <a href="{{ route('appointments.bulk') }}" class="btn-custom btn-outline-accent py-1.5 px-3 smaller text-decoration-none">Create Bulk Request</a>
                            </div>
                        @endforelse
                    </div>
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $bulkPaginator->links('appointments.partials.pagination') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: Active Clinical Sheet Workspace --}}
        <div class="col-12 col-lg-7 col-xl-8 sticky-lg-top" style="top: 90px; align-self: flex-start;" id="right-workspace-col">
            {{-- Mobile Return to Cards Button --}}
            <div class="d-lg-none mb-3 d-none" id="mobile-back-to-queue-btn">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 py-2 fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2" onclick="scrollToQueueList()">
                    <i class="bi bi-arrow-up-circle"></i>
                    <span>Back to Appointment List</span>
                </button>
            </div>

            <div id="workspace-container" class="h-100">
                {{-- Default Empty State Placeholder --}}
                <div id="details-placeholder" class="card p-5 text-center border bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 480px; background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 14px;">
                    <div class="rounded-circle p-3 mb-4 text-accent d-flex align-items-center justify-content-center shadow-sm" style="width: 76px; height: 76px; background-color: rgba(25, 211, 140, 0.1); border: 1.5px solid var(--brand-accent);">
                        <i class="bi bi-clipboard2-pulse-fill fs-1 text-accent" style="color: var(--brand-accent) !important;"></i>
                    </div>
                    <h5 class="text-main fw-bold mb-2 text-uppercase" style="color: var(--text-main) !important;">Appointment Information</h5>
                    <p class="text-muted small mb-0" style="max-width: 420px; color: var(--text-muted) !important;">
                        Select any appointment from the directory on the left to inspect scheduled date and time, requested diagnostic examinations, payment details, or download released clinical results.
                    </p>
                </div>

                {{-- Render Hidden Detail Workspace Panes --}}
                @foreach($allApps as $app)
                    @include('appointments.partials.detail-card', ['app' => $app, 'is_staff' => false])
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- MULTI-FORMAT LIGHTBOX OVERLAY --}}
@include('layouts.partials.lightbox-overlay')

{{-- PATIENT-ONLY ACTION MODALS (Delete Voided & Cancellation) --}}
<div id="patient-modals-container">
    @foreach($allApps as $app)
        @if(Auth::id() == $app->user_id)
            {{-- A. DELETE VOIDED APPOINTMENT MODAL --}}
            <div class="modal fade" id="deleteExpiredModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content border-danger bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border-color: #ff4d4d !important; color: var(--text-main); border-radius: 14px;">
                        <div class="mb-3">
                            <i class="bi bi-trash text-danger display-4 d-block"></i>
                        </div>
                        <h5 class="text-main fw-bold mb-1 text-uppercase" style="color: var(--text-main) !important;">Delete Voided Record?</h5>
                        <p class="text-muted small mb-4" style="color: var(--text-muted) !important;">
                            This appointment was updated to <strong>VOIDED</strong> due to non-arrival within 24 hours of scheduled time. Are you sure you want to remove this record from your dashboard?
                        </p>
                        <div class="d-grid gap-2">
                            <form action="{{ route('appointments.soft-delete', $app->id) }}" method="POST" class="m-0" onsubmit="handleAjaxActionSubmit(event, this)">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100 py-2 fw-bold text-uppercase text-white">DELETE RECORD</button>
                            </form>
                            <button type="button" class="btn btn-link text-muted text-decoration-none smaller" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- B. CANCEL APPOINTMENT MODAL --}}
            <div class="modal fade" id="cancelAppointmentModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
                    <form action="{{ route('appointments.cancel', $app->id) }}" method="POST" class="modal-content shadow-lg cancel-appointment-form" data-app-id="{{ $app->id }}" onsubmit="handleAjaxActionSubmit(event, this)" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main); border-radius: 14px;">
                        @csrf
                        <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                            <h5 class="text-danger fw-bold text-uppercase small m-0 d-flex align-items-center gap-2">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                                <span>Cancel Appointment?</span>
                            </h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            <p class="small mb-3" style="color: var(--text-main) !important;">Are you sure you want to cancel this appointment?</p>
                            @php
                                $scheduledAt = \Carbon\Carbon::parse($app->appointment_date->format('Y-m-d') . ' ' . $app->time_slot);
                                $hoursUntil = \Carbon\Carbon::now()->diffInHours($scheduledAt, false);
                                $isWithin24Hours = ($hoursUntil >= 0 && $hoursUntil < 24);
                                $isPaid = ($app->payment_status === 'paid');
                            @endphp
                            @if($app->payment_method === 'Cashless')
                                @if($isPaid)
                                    @if($isWithin24Hours)
                                        <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-warning p-3 rounded-3 mb-3 smaller">
                                            <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
                                                <i class="bi bi-patch-check-fill text-success"></i>
                                                <span>Payment Status: Confirmed Paid</span>
                                            </div>
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            <strong>50% Administrative Fee:</strong> Because cancellation is requested within 24 hours of scheduled slot ({{ $scheduledAt->format('M d, Y h:i A') }}), a 50% fee applies. 50% will be refunded to your account.
                                        </div>
                                    @else
                                        <div class="alert alert-clinical border-success bg-success bg-opacity-10 text-success p-3 rounded-3 mb-3 smaller">
                                            <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
                                                <i class="bi bi-patch-check-fill text-success"></i>
                                                <span>Payment Status: Confirmed Paid</span>
                                            </div>
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                            <strong>100% Full Refund Eligible:</strong> You are canceling more than 24 hours in advance. Your paid cashless booking is eligible for a full refund.
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-clinical border-info bg-info bg-opacity-10 text-info p-3 rounded-3 mb-3 smaller">
                                        <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
                                            <i class="bi bi-clock-history text-info"></i>
                                            <span>Payment Status: Not Confirmed Yet</span>
                                        </div>
                                        <i class="bi bi-info-circle-fill me-1"></i>
                                        <span>Your cashless transaction is pending confirmation. Canceling will void this request.</span>
                                    </div>
                                @endif
                            @else
                                @if($isWithin24Hours)
                                    <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-warning p-3 rounded-3 mb-3 smaller">
                                        <i class="bi bi-exclamation-triangle-fill me-1.5"></i>
                                        <strong>Notice:</strong> Canceling within 24 hours of scheduled appointment ({{ $scheduledAt->format('M d, Y h:i A') }}).
                                    </div>
                                @else
                                    <div class="alert alert-clinical border-secondary bg-secondary bg-opacity-10 text-secondary p-3 rounded-3 mb-3 smaller">
                                        <i class="bi bi-info-circle-fill me-1.5"></i>
                                        <span>Free cancellation available for this cash booking (more than 24 hours in advance).</span>
                                    </div>
                                @endif
                            @endif

                            {{-- Cancellation Reason Dropdown --}}
                            <div class="mb-3">
                                <label for="cancel_reason_select_{{$app->id}}" class="smaller fw-bold mb-2 text-uppercase d-block" style="color: var(--text-muted);">Reason for Cancellation</label>
                                <select id="cancel_reason_select_{{$app->id}}" name="cancellation_reason" class="form-select shadow-none cancel-reason-select" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="window.toggleCancelReasonField('{{ $app->id }}', this)" required>
                                    <option value="" disabled selected>-- Select a cancellation reason --</option>
                                    <option value="Change of schedule / Unforeseen personal conflict">Change of schedule / Unforeseen personal conflict</option>
                                    <option value="Doctor advised to postpone or cancel test">Doctor advised to postpone or cancel test</option>
                                    <option value="Patient feeling unwell / Unable to visit clinic">Patient feeling unwell / Unable to visit clinic</option>
                                    <option value="Found alternative clinic / duplicate booking">Found alternative clinic / duplicate booking</option>
                                    <option value="Financial or budget constraints">Financial or budget constraints</option>
                                    <option value="Others">Others (Specify below)</option>
                                </select>
                            </div>

                            <div id="custom_cancel_reason_wrapper_{{$app->id}}" class="mb-3 d-none">
                                <label for="custom_cancel_reason_{{$app->id}}" class="smaller fw-bold mb-2 text-uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                                <textarea id="custom_cancel_reason_{{$app->id}}" class="form-control shadow-none cancel-reason-textarea" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="3" placeholder="Provide details regarding the cancellation..."></textarea>
                                <div class="mt-1"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
                            </div>

                            <input type="hidden" name="reason" id="cancel_reason_hidden_{{$app->id}}" value="">
                        </div>
                        <div class="modal-footer p-3 border-top d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                            <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Go Back</button>
                            <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">Cancel Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection

@push('scripts')
<script>
function showAppointmentDetails(appId) {
    const placeholder = document.getElementById('details-placeholder');
    if (placeholder) placeholder.classList.add('d-none');

    document.querySelectorAll('.appointment-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));
    
    const detailPanel = document.getElementById(`details-${appId}`);
    if (detailPanel) {
        detailPanel.classList.remove('d-none');
    }
    const listCard = document.getElementById(`card-${appId}`);
    if (listCard) {
        listCard.classList.add('border-accent', 'shadow-neon');
    }

    if (window.innerWidth < 992) {
        const backBtn = document.getElementById('mobile-back-to-queue-btn');
        if (backBtn) backBtn.classList.remove('d-none');
        const workspaceCol = document.getElementById('right-workspace-col');
        if (workspaceCol) {
            workspaceCol.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

function scrollToQueueList() {
    const leftPanel = document.getElementById('left-queue-panel');
    if (leftPanel) {
        leftPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function resetActiveDetail() {
    const placeholder = document.getElementById('details-placeholder');
    if (placeholder) placeholder.classList.remove('d-none');
    document.querySelectorAll('.appointment-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));
    const backBtn = document.getElementById('mobile-back-to-queue-btn');
    if (backBtn) backBtn.classList.add('d-none');
}

window.toggleCancelReasonField = function(appId, select) {
    const wrapper = document.getElementById(`custom_cancel_reason_wrapper_${appId}`);
    const textarea = document.getElementById(`custom_cancel_reason_${appId}`);
    const hiddenReason = document.getElementById(`cancel_reason_hidden_${appId}`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
            textarea.setAttribute('name', 'cancellation_reason');
            select.removeAttribute('name');
            textarea.value = '';
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
    // Bind Cancel form validation
    document.querySelectorAll('.cancel-appointment-form').forEach(form => {
        if (form.dataset.bound === 'true') return;
        form.dataset.bound = 'true';
        const appId = form.dataset.appId;
        const select = document.getElementById(`cancel_reason_select_${appId}`);
        const textarea = document.getElementById(`custom_cancel_reason_${appId}`);
        const hiddenReason = document.getElementById(`cancel_reason_hidden_${appId}`);

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
                    alert('Please select or specify a valid cancellation reason of at least 5 characters.');
                    if (activeVal) activeVal.focus();
                    return false;
                }
                if (hiddenReason) {
                    hiddenReason.value = activeVal.value.trim();
                }
            });
        }
    });

    // Check URL parameters for direct deep-link to an appointment ID
    const urlParams = new URLSearchParams(window.location.search);
    const selectId = urlParams.get('id');
    if (selectId) {
        const card = document.getElementById(`card-${selectId}`);
        if (card) {
            const tabPane = card.closest('.tab-pane');
            if (tabPane) {
                const tabId = tabPane.id;
                const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                if (tabButton) {
                    const tab = bootstrap.Tab.getInstance(tabButton) || new bootstrap.Tab(tabButton);
                    tab.show();
                }
            }
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            showAppointmentDetails(selectId);
        }
    } else {
        // Automatically select the first visible card in the active tab on desktop
        const firstCard = document.querySelector('#listContent .tab-pane.active .app-list-card');
        if (firstCard && firstCard.id && window.innerWidth >= 992) {
            const firstId = firstCard.id.replace('card-', '');
            showAppointmentDetails(firstId);
        }
    }
});
</script>
@endpush

@push('styles')
<style>
#patientAppTabs .nav-link {
    color: var(--text-muted) !important;
    border: 1px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    border-radius: 8px;
    transition: 0.2s ease;
}
#patientAppTabs .nav-link:hover {
    border-color: var(--brand-accent) !important;
    color: var(--brand-accent) !important;
}
#patientAppTabs .nav-link.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
}

#listContent .custom-scroll {
    padding: 6px 12px 6px 6px !important;
}
.app-list-card {
    transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer;
}
.app-list-card:hover {
    border-color: var(--brand-accent) !important;
}
.border-accent {
    border-color: var(--brand-accent) !important;
    border-width: 2px !important;
}
.shadow-neon {
    box-shadow: 0 0 12px rgba(25, 211, 140, 0.25) !important;
}
.app-list-card.border-accent {
    background-color: rgba(25, 211, 140, 0.04) !important;
}
.btn-danger-custom {
    background-color: #ff4d4d !important;
    color: #ffffff !important;
    border: 2px solid #ff4d4d !important;
}
.btn-danger-custom:hover {
    background-color: #e03b3b !important;
    border-color: #e03b3b !important;
    transform: translateY(-1px);
}
.border-dashed { border-style: dashed !important; }
</style>
@endpush