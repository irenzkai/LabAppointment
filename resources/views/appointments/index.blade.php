@extends('layouts.app')

@section('title', 'Laboratory Appointments')

@section('content')
<div class="container-fluid text-start animate-page">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                {{ $is_staff ? 'Master Clinical Queue' : 'My Appointments' }}
            </h2>
            <p class="text-muted mb-0 small">
                {{ $is_staff ? 'Monitor, approve, and track real-time clinical workflows.' : 'Track your diagnostic bookings and access clinical results.' }}
            </p>
        </div>
        @if(!$is_staff)
            <a href="{{ route('patient.history') }}" class="btn-custom btn-outline-accent px-3 py-2">
                <i class="bi bi-clock-history me-2"></i> VIEW ARCHIVED RECORDS
            </a>
        @endif
    </div>

    {{-- Split Pane Grid Layout --}}
    <div class="row g-4">

        {{-- LEFT PANEL: Card Lists & Filters --}}
        <div class="col-lg-5 col-xl-4">

            {{-- Navigation Tabs (Patient Only) --}}
            @if(!$is_staff)
                <ul class="nav nav-pills mb-3 gap-1 bg-secondary bg-opacity-10 p-1.5 rounded-3 border border-secondary border-opacity-25" id="appTabs" role="tablist">
                    <li class="nav-item flex-grow-1">
                        <button class="nav-link active w-100 fs-x-small fw-bold uppercase py-2" data-bs-toggle="pill" data-bs-target="#pane-self" onclick="resetActiveDetail()">Myself</button>
                    </li>
                    <li class="nav-item flex-grow-1">
                        <button class="nav-link w-100 fs-x-small fw-bold uppercase py-2" data-bs-toggle="pill" data-bs-target="#pane-family" onclick="resetActiveDetail()">Family</button>
                    </li>
                    <li class="nav-item flex-grow-1">
                        <button class="nav-link w-100 fs-x-small fw-bold uppercase py-2" data-bs-toggle="pill" data-bs-target="#pane-bulk" onclick="resetActiveDetail()">Bulk</button>
                    </li>
                </ul>
            @else
                {{-- Server-side Search & Configuration Filter Toolbar for Staff --}}
                <form action="{{ route('appointments.index') }}" method="GET" class="mb-4">
                    <div class="row g-2">
                        <div class="col-12 mb-2">
                            <div class="input-group input-group-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card);">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-0 shadow-none py-2" style="background-color: var(--bg-card); color: var(--text-main);" placeholder="Search patient, ID, or batch..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <select name="status" class="form-select form-select-sm" style="background-color: var(--bg-card); color: var(--text-main); border-color: rgba(255,255,255,0.15);" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="needs_action" {{ request('status') === 'needs_action' ? 'selected' : '' }}>Needs Action</option>
                                <option value="no_action" {{ request('status') === 'no_action' ? 'selected' : '' }}>No Action Needed</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="retest" {{ request('status') === 'retest' ? 'selected' : '' }}>Retest Required</option>
                                <option value="tested" {{ request('status') === 'tested' ? 'selected' : '' }}>Tested</option>
                                <option value="encoded" {{ request('status') === 'encoded' ? 'selected' : '' }}>Encoded</option>
                                <option value="released" {{ request('status') === 'released' ? 'selected' : '' }}>Released</option>
                                <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Canceled</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <select name="sort_by" class="form-select form-select-sm" style="background-color: var(--bg-card); color: var(--text-main); border-color: rgba(255,255,255,0.15);" onchange="this.form.submit()">
                                <option value="date" {{ request('sort_by', 'date') === 'date' ? 'selected' : '' }}>Date</option>
                                <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Name</option>
                                <option value="submitted" {{ request('sort_by') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <select name="order" class="form-select form-select-sm" style="background-color: var(--bg-card); color: var(--text-main); border-color: rgba(255,255,255,0.15);" onchange="this.form.submit()">
                                <option value="desc" {{ request('order', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
                                <option value="asc" {{ request('order') === 'asc' ? 'selected' : '' }}>Asc</option>
                            </select>
                        </div>
                    </div>
                </form>
            @endif

            {{-- Main List Wrapper --}}
            <div class="tab-content" id="listContent">

                {{-- STAFF MAIN QUEUE --}}
                @if($is_staff)
                    <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                        @forelse($staffQueue as $batchId => $group)
                            @php 
                                $isGroup = $group instanceof \Illuminate\Support\Collection;
                                $first = $isGroup ? $group->first() : $group;
                            @endphp
                            @include('appointments.partials.list-card', ['app' => $first, 'groupCount' => $isGroup ? $group->count() : 1, 'batchId' => $batchId])
                        @empty
                            <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                <i class="bi bi-folder-x text-accent fs-1 mb-3 opacity-75"></i>
                                <p class="small mb-0">No appointments in queue.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Staff Pagination Links --}}
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $staffPaginator->links() }}
                    </div>
                @else
                    {{-- PATIENT: MYSELF --}}
                    <div class="tab-pane fade show active" id="pane-self">
                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                            @forelse($self as $app)
                                @include('appointments.partials.list-card', ['app' => $app, 'groupCount' => 1])
                            @empty
                                <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                    <i class="bi bi-calendar-x text-accent fs-1 mb-3 opacity-75"></i>
                                    <p class="small mb-0">No personal bookings found.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Myself Pagination Links --}}
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $self->links() }}
                        </div>
                    </div>

                    {{-- PATIENT: FAMILY DEPENDENTS --}}
                    <div class="tab-pane fade" id="pane-family">
                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                            @forelse($dependents as $app)
                                @include('appointments.partials.list-card', ['app' => $app, 'groupCount' => 1])
                            @empty
                                <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                    <i class="bi bi-people text-accent fs-1 mb-3 opacity-75"></i>
                                    <p class="small mb-0">No dependent bookings found.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Family Pagination Links --}}
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $dependents->links() }}
                        </div>
                    </div>

                    {{-- PATIENT: ORGANIZATIONAL BULK --}}
                    <div class="tab-pane fade" id="pane-bulk">
                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                            @forelse($bulkGroups as $batchId => $group)
                                @php $first = $group->first(); @endphp
                                @include('appointments.partials.list-card', ['app' => $first, 'groupCount' => $group->count(), 'batchId' => $batchId])
                            @empty
                                <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                    <i class="bi bi-buildings text-accent fs-1 mb-3 opacity-75"></i>
                                    <p class="small mb-0">No corporate groups found.</p>
                                </div>
                            @endforelse
                        </div>

                        {{-- Bulk Pagination Links --}}
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $bulkPaginator->links() }}
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- RIGHT PANEL: Active Clinical Sheet Workspace --}}
        <div class="col-lg-7 col-xl-8 " style="top: 100px; align-self: flex-start; {{ !$is_staff ? 'margin-top: 52px;' : '' }}">
            <div id="workspace-container" class="h-100">

                {{-- Default Empty State Placeholder --}}
                <div id="details-placeholder" class="card p-5 text-center border-secondary bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 420px; background-color: var(--bg-card);">
                    <div class="bg-secondary bg-opacity-10 rounded-circle p-3 mb-4 text-accent d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="bi bi-clipboard2-pulse-fill fs-1 text-accent"></i>
                    </div>
                    <h4 class="text-main fw-bold mb-2 uppercase">Clinical Detail Workspace</h4>
                    <p class="text-muted small mb-0" style="max-width: 380px;">Select any clinical entry from the left-hand panel to review its test breakdowns, billing summaries, and context actions.</p>
                </div>

                {{-- Render Hidden Detail Panels --}}
                @foreach($allApps as $app)
                    @include('appointments.partials.detail-card', ['app' => $app])
                @endforeach

            </div>
        </div>

    </div>
</div>

{{-- MULTI-FORMAT LIGHTBOX OVERLAY WITH SECURE PREVIEW GATES --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox(event)">
    <div class="text-center p-3 animate-fade-in w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="max-width: 95vw; max-height: 95vh;">

        {{-- Floating File Canvas --}}
        <div id="lightbox_viewer_container" class="position-relative d-flex align-items-center justify-content-center bg-white rounded p-2 border border-secondary shadow-lg" style="max-width: 85vw; max-height: 80vh; overflow: auto; min-width: 300px; min-height: 300px;">
            <!-- Render Image Scan -->
            <img src="" id="lightbox_qr_img" alt="Zoomed Asset" class="img-fluid rounded transition-all" style="max-height: 75vh; max-width: 80vw; object-fit: contain; transform: scale(1); transform-origin: center; cursor: grab;">

            <!-- Render PDF Document Scan -->
            <iframe id="lightbox_pdf_viewer" class="d-none rounded" style="width: 80vw; height: 75vh; border: none;"></iframe>
        </div>

        {{-- Interactive Document Control Toolbar --}}
        <div id="lightbox_zoom_controls" class="mt-3 d-flex gap-3 align-items-center bg-dark bg-opacity-75 px-4 py-2 rounded-pill border border-secondary">
            <button type="button" class="btn-custom btn-outline-light rounded-circle px-2.5 py-1" onclick="zoomImage(-0.15, event)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
            <span id="zoom_percent" class="text-white small fw-bold">100%</span>
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="zoomImage(0.15, event)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="toggleFullscreen(event)" title="Toggle Fullscreen"><i class="bi bi-fullscreen" id="fullscreen_icon"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2.5 py-1" onclick="resetZoom(event)" title="Reset Zoom"><i class="bi bi-arrow-counterclockwise"></i></button>
        </div>

        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the dark overlay boundary to close preview</p>
    </div>
</div>

{{-- 5. THEME-ADAPTIVE MODALS LOOP --}}
@foreach($allApps as $app)
    @php
        $isExpired = $app->isExpired();
    @endphp

    {{-- A. DELETE EXPIRED APPOINTMENT MODAL --}}
    @if(Auth::id() == $app->user_id)
        <div class="modal fade" id="deleteExpiredModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-danger bg-card shadow-lg text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-trash text-danger display-4 d-block"></i>
                    </div>
                    <h5 class="text-main fw-bold mb-1 uppercase">Delete Expired Record?</h5>
                    <p class="text-secondary small mb-4">Are you sure you want to remove this expired appointment from your dashboard? This action will hide the record from your view.</p>
                    <div class="d-grid gap-2">
                        <form action="{{ route('appointments.soft-delete', $app->id) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100 py-2 fw-bold uppercase text-white">DELETE RECORD</button>
                        </form>
                        <button type="button" class="btn btn-link text-secondary text-decoration-none smaller" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- B. RESUBMIT PATIENT MODAL (Enabled in returned, as well as canceled/expired states for correction) --}}
    @if(($app->status == 'returned' || $app->status == 'canceled' || $isExpired) && Auth::id() == $app->user_id)
        @include('appointments.partials.resubmit-modal', ['app' => $app])
    @endif

    {{-- C. CANCEL APPOINTMENT MODAL (Placed outside the staff-only block so patients can access it) --}}
    @if(Auth::id() == $app->user_id)
        <div class="modal fade" id="cancelAppointmentModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <form action="{{ route('appointments.cancel', $app->id) }}" method="POST" class="modal-content shadow-lg border-0" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h5 class="text-danger fw-bold uppercase small m-0">Cancel Appointment?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small text-muted mb-0">Are you sure you want to cancel this appointment? This action is irreversible, but you can reschedule/resubmit it later if needed.</p>
                    </div>
                    <div class="modal-footer p-3 border-top border-secondary border-opacity-10 d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card);">
                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Go Back</button>
                        <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">Cancel Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- D. STAFF/ADMIN CLINICAL CONTROLS WORKFLOW MODALS --}}
    @can('isStaff')
        {{-- Return to Patient Modal --}}
        <div class="modal fade" id="retModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('appointments.status', $app->id) }}" id="returnForm{{$app->id}}" method="POST" class="modal-content shadow-lg return-form-element" data-app-id="{{$app->id}}" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf 
                    @method('PATCH')
                    <input type="hidden" name="status" value="returned">
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h5 class="modal-title text-danger fw-bold uppercase small m-0">Return to Patient</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label for="return_reason_select_{{$app->id}}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Reason for Return</label>
                            <select id="return_reason_select_{{$app->id}}" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required>
                                <option value="" disabled selected>-- Select a return reason --</option>
                                <option value="Mismatched identification documents">Mismatched identification documents</option>
                                <option value="Incorrect or incomplete personal details">Incorrect or incomplete personal details</option>
                                <option value="No payment received / pending verification">No payment received / pending verification</option>
                                <option value="Invalid test selection for patient demographics">Invalid test selection for patient demographics</option>
                                <option value="Discrepancy in schedule / date selection">Discrepancy in schedule / date selection</option>
                                <option value="Others">Others (Specify details below)</option>
                            </select>
                        </div>
                        <div id="custom_return_reason_wrapper_{{$app->id}}" class="mb-3 d-none">
                            <label for="return_reason_{{$app->id}}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                            <textarea name="return_reason" id="return_reason_{{$app->id}}" class="form-control shadow-none return-reason-textarea" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="4" placeholder="Identify the specific correction needed..."></textarea>
                        </div>
                        <div class="mt-2"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
                    </div>
                    <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
                        <div class="d-flex w-100">
                            <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Send Return</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Payment Confirmation Handshake Modal --}}
        <div class="modal fade" id="confirmPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    <div class="modal-header border-secondary border-bottom border-opacity-10 py-3">
                        <h5 class="modal-title text-accent fw-bold uppercase small m-0">Confirm Payment Receipt?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

        {{-- Revoke Payment Confirmation Modal --}}
        <div class="modal fade" id="revokePaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    <div class="modal-header border-danger border-bottom border-opacity-10 py-3">
                        <h5 class="modal-title text-danger fw-bold uppercase small m-0">Revoke Payment Status?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

        {{-- Mark Payment as Invalid Modal --}}
        <div class="modal fade" id="invalidPaymentModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <form action="{{ route('appointments.invalid-payment', $app->id) }}" method="POST" class="modal-content shadow-lg border-0" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h5 class="modal-title text-danger fw-bold uppercase small m-0">Flag Payment as Invalid?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small text-muted mb-3">Flag this uploaded proof of payment receipt as invalid. Select a justification:</p>
                        <div class="mb-3">
                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Reason for Invalidating</label>
                            <select name="reason" id="invalid_reason_select_{{ $app->id }}" class="form-select py-2.5 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="window.toggleInvalidReasonField('{{ $app->id }}', this)" required>
                                <option value="" disabled selected>-- Select an invalidation reason --</option>
                                <option value="Receipt image is blurry / unreadable">Receipt image is blurry / unreadable</option>
                                <option value="Reference number mismatch / Fake receipt">Reference number mismatch / Fake receipt</option>
                                <option value="Incorrect amount sent">Incorrect amount sent</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                        </div>
                        <div id="invalid_custom_reason_wrapper_{{ $app->id }}" class="mb-3 d-none">
                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Specify Custom Reason</label>
                            <textarea name="custom_reason" id="invalid_custom_reason_{{ $app->id }}" class="form-control" rows="3" placeholder="Provide details regarding the invalid payment..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer p-3 border-top border-secondary border-opacity-10 d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card);">
                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-custom btn-accent btn-danger py-2 px-4 fw-bold">Flag as Invalid</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Confirm Refund Modal --}}
        <div class="modal fade" id="confirmRefundModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <form action="{{ route('appointments.refund', $app->id) }}" method="POST" class="modal-content shadow-lg border-0" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h5 class="modal-title text-success fw-bold uppercase small m-0">Confirm Refund?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small text-muted mb-0">Confirm that the refund for this canceled appointment has been manually processed and completed. This action will log your name and the current timestamp as the official processor.</p>
                    </div>
                    <div class="modal-footer p-3 border-top border-secondary border-opacity-10 d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card);">
                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-custom btn-accent btn-success py-2 px-4 fw-bold">Confirm Refund</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
<script>
    // Global variable to hold active details selection
    let activeRowIdx = null;

    /**
     * Reveal clinical details workspace card smoothly [257]
     */
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
    }

    /**
     * Reset active detail workspace view to default placeholder state [257]
     */
    function resetActiveDetail() {
        const placeholder = document.getElementById('details-placeholder');
        if (placeholder) placeholder.classList.remove('d-none');

        document.querySelectorAll('.appointment-detail-pane').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));
    }

    /**
     * DYNAMIC SYNC QUEUE: Silently fetches updated HTML, updates containers,
     * and preserves the employee's active focused record card and workspace view [253, 254].
     */
    function syncMasterQueue() {
        fetch("{{ route('appointments.index') }}")
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // 1. Sync active queue lists (Myself, Family, Bulk, or Staff main queue) [253]
                const newContent = doc.getElementById('listContent');
                const oldContent = doc.getElementById('listContent');
                if (newContent && oldContent) {
                    oldContent.innerHTML = newContent.innerHTML;
                }

                // 2. Sync pre-rendered workspace cards silently [254]
                const newWorkspace = doc.getElementById('workspace-container');
                const oldWorkspace = doc.getElementById('workspace-container');
                if (newWorkspace && oldWorkspace) {
                    // Record currently open card id so we don't close it on them
                    const activePane = document.querySelector('.appointment-detail-pane:not(.d-none)');
                    const activeId = activePane ? activePane.id : null;

                    oldWorkspace.innerHTML = newWorkspace.innerHTML;

                    // Restore active card view if it existed before sync
                    if (activeId) {
                        const placeholder = document.getElementById('details-placeholder');
                        if (placeholder) placeholder.classList.add('d-none');

                        const restoredPane = document.getElementById(activeId);
                        if (restoredPane) restoredPane.classList.remove('d-none');

                        // Re-highlight the list card
                        const recordId = activeId.split('-')[1];
                        const listCard = document.getElementById(`card-${recordId}`);
                        if (listCard) listCard.classList.add('border-accent', 'shadow-neon');
                    }
                }

                // 3. Re-initialize filters and input listeners on the new DOM elements [257]
                initializeFilters();
            })
            .catch(error => console.error('Queue sync failed:', error));
    }

    /**
     * Initializes filter listeners for the live search input [257]
     */
    function initializeFilters() {
        const queueSearch = document.getElementById('queueSearch');
        if (queueSearch) {
            queueSearch.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                document.querySelectorAll('.app-list-card').forEach(card => {
                    const cardText = card.innerText.toLowerCase();
                    card.classList.toggle('d-none', !cardText.includes(query));
                });
            });
        }
    }

    // Fullscreen wheel-to-zoom mapping (Fixed: triggers whenever the modal overlay is active)
    const lightbox = document.getElementById('qr_lightbox');
    const container = document.getElementById('lightbox_viewer_container');
    if (container && lightbox) {
        container.addEventListener('wheel', (e) => {
            if (!lightbox.classList.contains('d-none')) {
                e.preventDefault();
                const amount = e.deltaY < 0 ? 0.15 : -0.15;
                zoomImage(amount, e);
            }
        }, { passive: false });
    }
</script>
<script>
    // Drag/Lightbox Controller handlers (Image dragging & Fullscreen support)
    let currentScale = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX, startY;

    function zoomImage(amount, event) {
        if (event) event.stopPropagation();

        currentScale += amount;
        currentScale = Math.max(0.5, Math.min(3, currentScale)); // Cap zoom between 50% and 300%

        const img = document.getElementById('lightbox_qr_img');
        if (img) {
            img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
            img.style.cursor = currentScale > 1 ? 'grab' : 'default';
        }

        const percentEl = document.getElementById('zoom_percent');
        if (percentEl) {
            percentEl.innerText = `${Math.round(currentScale * 100)}%`;
        }
    }

    function resetZoom(event) {
        if (event) event.stopPropagation();

        currentScale = 1;
        translateX = 0;
        translateY = 0;
        isDragging = false;

        const img = document.getElementById('lightbox_qr_img');
        if (img) {
            img.style.transform = 'translate(0px, 0px) scale(1)';
            img.style.cursor = 'default';
        }

        const percentEl = document.getElementById('zoom_percent');
        if (percentEl) {
            percentEl.innerText = '100%';
        }
    }

    function zoomFile(fileSrc) {
        if (!fileSrc) return;

        const isPdf = fileSrc.toLowerCase().endsWith('.pdf') || fileSrc.startsWith('data:application/pdf');
        const img = document.getElementById('lightbox_qr_img');
        const iframe = document.getElementById('lightbox_pdf_viewer');
        const controls = document.getElementById('lightbox_zoom_controls');

        resetZoom();

        if (isPdf) {
            img.classList.add('d-none');
            controls.classList.add('d-none');
            iframe.src = fileSrc;
            iframe.classList.remove('d-none');
        } else {
            iframe.classList.add('d-none');
            iframe.src = '';
            img.src = fileSrc;
            img.classList.remove('d-none');
            controls.classList.remove('d-none');
        }

        document.getElementById('qr_lightbox').classList.remove('d-none');
        document.getElementById('qr_lightbox').classList.add('d-flex');
    }

    function closeQRLightbox(event) {
        if (event) {
            const container = document.getElementById('lightbox_viewer_container');
            const controls = document.getElementById('lightbox_zoom_controls');
            if (container.contains(event.target) || (controls && controls.contains(event.target))) {
                return;
            }
        }
        document.getElementById('qr_lightbox').classList.add('d-none');
        document.getElementById('qr_lightbox').classList.remove('d-flex');

        if (document.fullscreenElement) {
            document.exitFullscreen().catch(err => console.error("Error exiting fullscreen:", err));
        }
        resetZoom();
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Real-time synchronization
        initializeFilters();

        // Auto-select appointment if 'id' parameter is passed in the URL (e.g. from Dashboard)
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
        }

        // Modal Trigger Configuration Linkage (For Staff Returns)
        document.querySelectorAll('.return-form-element').forEach(form => {
            const appId = form.dataset.appId;
            const select = document.getElementById(`return_reason_select_${appId}`);
            const wrapper = document.getElementById(`custom_return_reason_wrapper_${appId}`);
            const textarea = document.getElementById(`return_reason_${appId}`);

            if (select && wrapper && textarea) {
                select.addEventListener('change', function() {
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

                form.addEventListener('submit', function(e) {
                    const activeVal = select.value === 'Others' ? textarea : select;
                    if (activeVal.value.trim().length < 5) {
                        e.preventDefault();
                        alert('A valid reason of at least 5 characters is required.');
                    }
                });
            }
        });

        // Pusher real-time syncing receiver
        if (typeof Pusher !== 'undefined') {
            const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
                cluster: "{{ env('PUSHER_APP_CLUSTER') }}"
            });
            const syncChannel = pusher.subscribe('clinical-queue');
            syncChannel.bind('queue.updated', function() {
                syncMasterQueue();
            });
        }
    });

    /**
     * Toggles retest justification dropdown field.
     */
    window.toggleRetestReasonField = function(appId, select) {
        const wrapper = document.getElementById(`retest_custom_reason_wrapper_${appId}`);
        const textarea = document.getElementById(`retest_custom_reason_${appId}`);
        if (wrapper && textarea) {
            if (select.value === 'Others') {
                wrapper.classList.remove('d-none');
                textarea.setAttribute('required', 'required');
            } else {
                wrapper.classList.add('d-none');
                textarea.removeAttribute('required');
            }
        }
    };

    /**
     * Toggles invalid payment custom reason dropdown.
     */
    window.toggleInvalidReasonField = function(appId, select) {
        const wrapper = document.getElementById(`invalid_custom_reason_wrapper_${appId}`);
        const textarea = document.getElementById(`invalid_custom_reason_${appId}`);
        if (wrapper && textarea) {
            if (select.value === 'Others') {
                wrapper.classList.remove('d-none');
                textarea.setAttribute('required', 'required');
            } else {
                wrapper.classList.add('d-none');
                textarea.removeAttribute('required');
            }
        }
    };

    window.zoomQR = zoomFile;
    window.closeQRLightbox = closeQRLightbox;
    window.toggleFullscreen = toggleFullscreen;
</script>
@endpush

@push('styles')
<style>
    /* High-contrast overrides for Light Mode Compatibility */
    #appTabs .nav-link,
    .nav-pills .nav-link {
        color: var(--text-muted) !important;
        border: 1px solid var(--border-color) !important;
        background-color: var(--bg-card) !important;
        border-radius: 8px;
        transition: 0.2s ease;
    }
    #appTabs .nav-link:hover,
    .nav-pills .nav-link:hover {
        border-color: var(--brand-accent) !important;
        color: var(--brand-accent) !important;
    }
    #appTabs .nav-link.active,
    .nav-pills .nav-link.active,
    button.nav-link.active {
        background-color: var(--brand-accent) !important;
        color: #1c232d !important;
        border-color: var(--brand-accent) !important;
    }

    .border-dashed { border-style: dashed !important; }
    .min-vh-50 { min-height: 50vh; }

    /* Scroll list padding rules to prevent selected/hover translation clipping */
    #listContent .custom-scroll {
        padding: 6px 12px 6px 6px !important;
    }

    /* High-contrast Selected & Hover Highlights for Queue Cards */
    .app-list-card { 
        transition: all 0.2s ease; 
        cursor: pointer; 
    }
    .app-list-card:hover { 
        border-color: var(--brand-accent) !important; 
        transform: translateX(2px); 
    }
    .border-accent {
        border-color: var(--brand-accent) !important;
        border-width: 2px !important;
    }
    .shadow-neon {
        box-shadow: 0 0 15px rgba(25, 211, 140, 0.35) !important;
    }
    .app-list-card.border-accent {
        background-color: rgba(25, 211, 140, 0.04) !important;
        transform: translateX(4px);
    }
    /* Destructive alert styled cancellation triggers */
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
</style>
@endpush