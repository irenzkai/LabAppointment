<!-- resources/views/appointments/queue.blade.php -->
@extends('layouts.app')
@section('title', 'Master Clinical Queue')

@section('content')
<div class="container-fluid text-start animate-page py-2" id="master-queue-root">
    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom pb-3 gap-2" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 text-uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                Master Clinical Queue
            </h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- Toggle to view portal as a patient --}}
            <a href="{{ route('appointments.index', ['view' => 'patient']) }}" class="btn-custom btn-outline-accent px-3 py-2 d-inline-flex align-items-center gap-2 text-decoration-none" style="font-size: 0.78rem;">
                <i class="bi bi-person-bounding-box"></i>
                <span>PATIENT VIEW</span>
            </a>
        </div>
    </div>

    {{-- QUICK TRIAGE STATUS STRIP (With Enhanced Horizontal Scroll) --}}
    @php
        $currentStatus = request('status', '');
    @endphp
    <div class="queue-triage-container mb-3 position-relative">
        <div class="d-flex align-items-center gap-1 overflow-auto custom-scroll pb-2 queue-triage-strip" id="queueTriageStrip" style="white-space: nowrap; flex-wrap: nowrap;">
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === '' ? 'active' : '' }}" onclick="selectTriageStatus('')">
                ALL RECORDS
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill triage-pill-action {{ $currentStatus === 'needs_action' ? 'active' : '' }}" onclick="selectTriageStatus('needs_action')">
                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> NEEDS ACTION
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'pending' ? 'active' : '' }}" onclick="selectTriageStatus('pending')">
                PENDING APPROVAL
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'approved' ? 'active' : '' }}" onclick="selectTriageStatus('approved')">
                APPROVED
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'retest' ? 'active' : '' }}" onclick="selectTriageStatus('retest')">
                <i class="bi bi-arrow-repeat text-danger me-1"></i> RETEST
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'tested' ? 'active' : '' }}" onclick="selectTriageStatus('tested')">
                TESTED
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'encoded' ? 'active' : '' }}" onclick="selectTriageStatus('encoded')">
                ENCODED
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'released' ? 'active' : '' }}" onclick="selectTriageStatus('released')">
                RELEASED
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'needs_review' ? 'active' : '' }}" onclick="selectTriageStatus('needs_review')">
                PENDING REVIEW
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ in_array($currentStatus, ['expired', 'voided']) ? 'active' : '' }}" onclick="selectTriageStatus('expired')">
                VOIDED (NO-SHOW)
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'canceled' ? 'active' : '' }}" onclick="selectTriageStatus('canceled')">
                CANCELED
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold triage-pill {{ $currentStatus === 'no_action' ? 'active' : '' }}" onclick="selectTriageStatus('no_action')">
                NO ACTION NEEDED
            </button>
        </div>
    </div>

    {{-- Main Split Pane Layout --}}
    <div class="row g-4 position-relative">
        {{-- LEFT PANEL: Queue Controls & Appointment List --}}
        <div class="col-12 col-lg-5 col-xl-4" id="left-queue-panel">
            {{-- Staff Toolbar Form --}}
            <form action="{{ route('appointments.index') }}" method="GET" class="mb-3" id="queueFilterForm" onsubmit="event.preventDefault(); triggerDynamicQueueFilter();">
                <input type="hidden" name="view" value="queue">
                <input type="hidden" name="status" id="filterStatusInput" value="{{ request('status') }}">

                {{-- Row 1: Search Box with Clear Button --}}
                <div class="mb-2">
                    <div class="input-group input-group-sm border rounded-3 overflow-hidden shadow-sm" style="border-color: var(--border-color) !important;">
                        <span class="input-group-text border-0 text-muted" style="background-color: var(--bg-card); color: var(--text-muted) !important;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" id="queueSearchInput" class="form-control border-0 shadow-none py-2" style="background-color: var(--bg-card); color: var(--text-main);" placeholder="Search patient, ID, batch, company..." value="{{ request('search') }}" oninput="debounceQueueSearch()">
                        <button type="button" class="btn btn-link text-muted p-1 pe-2 {{ request('search') ? '' : 'd-none' }}" id="clearSearchBtn" onclick="clearQueueSearch()" style="background-color: var(--bg-card);">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                </div>

                {{-- Row 2: Secondary Filters (Booking Type without Emojis, Date, Sort By, Sort Order) --}}
                <div class="row g-2">
                    {{-- Booking Type Filter (Clean without Emojis) --}}
                    <div class="col-6">
                        <select name="app_type" id="filterAppTypeSelect" class="form-select form-select-sm shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" onchange="triggerDynamicQueueFilter()">
                            <option value="all" {{ request('app_type', 'all') === 'all' ? 'selected' : '' }}>All Booking Types</option>
                            <option value="bulk" {{ request('app_type') === 'bulk' ? 'selected' : '' }}>Corporate / Bulk</option>
                            <option value="self" {{ request('app_type') === 'self' ? 'selected' : '' }}>Personal (Self)</option>
                            <option value="dependent" {{ request('app_type') === 'dependent' ? 'selected' : '' }}>Family Dependent</option>
                        </select>
                    </div>

                    {{-- Schedule Date Filter --}}
                    <div class="col-6">
                        <input type="date" name="date" id="filterDateInput" class="form-control form-control-sm shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" value="{{ request('date') }}" placeholder="Filter by date..." onchange="triggerDynamicQueueFilter()" title="Filter by appointment schedule date">
                    </div>

                    {{-- Sort By --}}
                    <div class="col-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text py-1 px-2 border text-muted smaller" style="background-color: var(--bg-card); border-color: var(--border-color); color: var(--text-muted) !important;">
                                <i class="bi bi-sort-down"></i>
                            </span>
                            <select name="sort_by" id="filterSortBySelect" class="form-select form-select-sm shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" onchange="triggerDynamicQueueFilter()">
                                <option value="date" {{ request('sort_by', 'date') === 'date' ? 'selected' : '' }}>Sort by Date</option>
                                <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Sort by Name</option>
                                <option value="submitted" {{ request('sort_by') === 'submitted' ? 'selected' : '' }}>Sort by Submitted</option>
                            </select>
                        </div>
                    </div>

                    {{-- Sort Order --}}
                    <div class="col-5">
                        <select name="order" id="filterOrderSelect" class="form-select form-select-sm shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color);" onchange="triggerDynamicQueueFilter()">
                            <option value="desc" {{ request('order', 'desc') === 'desc' ? 'selected' : '' }}>Newest (Desc)</option>
                            <option value="asc" {{ request('order') === 'asc' ? 'selected' : '' }}>Oldest (Asc)</option>
                        </select>
                    </div>
                </div>

                {{-- Filter Active Status Indicator & Reset Button --}}
                @if(request('search') || request('status') || (request('app_type') && request('app_type') !== 'all') || request('date'))
                    <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                        <span class="smaller text-muted">
                            <i class="bi bi-funnel-fill text-accent me-1"></i> Filter active
                        </span>
                        <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="smaller text-danger text-decoration-none fw-bold">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset All Filters
                        </a>
                    </div>
                @endif
            </form>

            {{-- Queue Cards Deck --}}
            <div id="listContent" class="position-relative">
                <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" id="staff-queue-deck" style="max-height: 680px;">
                    @forelse($staffQueue as $batchId => $group)
                        @php 
                            $isGroup = $group instanceof \Illuminate\Support\Collection;
                            $first = $isGroup ? $group->first() : $group;
                        @endphp
                        @include('appointments.partials.list-card', [
                            'app' => $first, 
                            'groupCount' => $isGroup ? $group->count() : 1, 
                            'batchId' => $batchId
                        ])
                    @empty
                        <div class="card p-5 text-center text-muted border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 12px;">
                            <i class="bi bi-folder-x text-accent fs-1 mb-3 opacity-75" style="color: var(--brand-accent) !important;"></i>
                            <h6 class="text-main fw-bold uppercase mb-1">Queue Empty</h6>
                            <p class="small mb-3 text-muted" style="color: var(--text-muted) !important;">No appointments match your active search or filter criteria.</p>
                            <button type="button" class="btn-custom btn-outline-accent py-1.5 px-3 smaller" onclick="resetAllQueueFilters()">Clear Filters</button>
                        </div>
                    @endforelse
                </div>

                {{-- Staff Pagination --}}
                <div class="mt-3 d-flex justify-content-center" id="staff-pagination-wrapper">
                    {{ $staffPaginator->links('appointments.partials.pagination') }}
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: Active Clinical Workspace --}}
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
                    <h5 class="text-main fw-bold mb-2 text-uppercase" style="color: var(--text-main) !important;">Clinical Detail Workspace</h5>
                    <p class="text-muted small mb-0" style="max-width: 440px; color: var(--text-muted) !important;">
                        Select any appointment card from the queue directory on the left to review tests requested, process payments, edit clinical notes, or authorize laboratory sign-offs.
                    </p>
                </div>

                {{-- Render Hidden Detail Workspace Panes --}}
                @foreach($allApps as $app)
                    @include('appointments.partials.detail-card', ['app' => $app, 'is_staff' => true])
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- MULTI-FORMAT LIGHTBOX OVERLAY --}}
@include('layouts.partials.lightbox-overlay')

{{-- STAFF ACTION MODALS WRAPPER --}}
<div id="queue-modals-container">
    @foreach($allApps as $app)
        @php
            $patientSex = strtolower($app->patient_sex ?? 'both');
        @endphp

        {{-- 1. UNIFIED RETURN MODAL (Option A: Reason Only, Option B: With Suggestions) --}}
        <div class="modal fade action-teleport-modal" id="retModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" style="z-index: 1060 !important;">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content shadow-lg border" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color) !important; color: var(--text-main); border-radius: 14px;">
                    <div class="modal-header py-3 border-bottom" style="border-color: var(--border-color) !important;">
                        <div>
                            <h5 class="modal-title text-danger fw-bold text-uppercase small m-0 d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-return-left fs-5"></i>
                                <span>Return Appointment &bull; Ref #{{ $app->id }}</span>
                            </h5>
                            <small class="text-secondary smaller">Patient: <strong>{{ strtoupper($app->patient_name) }}</strong></small>
                        </div>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    {{-- Return Mode Switcher Pills --}}
                    <div class="p-3 bg-secondary bg-opacity-10 border-bottom border-secondary border-opacity-10">
                        <ul class="nav nav-pills gap-2" role="tablist">
                            <li class="nav-item flex-grow-1">
                                <button class="nav-link active w-100 fw-bold small text-uppercase py-2" data-bs-toggle="pill" data-bs-target="#ret-pane-standard-{{ $app->id }}" type="button" role="tab" style="font-size: 0.75rem;">
                                    <i class="bi bi-exclamation-octagon me-1"></i> Standard Return (Reason Only)
                                </button>
                            </li>
                            <li class="nav-item flex-grow-1">
                                <button class="nav-link w-100 fw-bold small text-uppercase py-2" data-bs-toggle="pill" data-bs-target="#ret-pane-suggest-{{ $app->id }}" type="button" role="tab" style="font-size: 0.75rem;" onclick="recalculateSuggestDependencies('{{ $app->id }}')">
                                    <i class="bi bi-magic me-1"></i> Return With Suggestions
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        {{-- WAY 1: STANDARD RETURN (Reason Only) --}}
                        <div class="tab-pane fade show active" id="ret-pane-standard-{{ $app->id }}" role="tabpanel">
                            <form action="{{ route('appointments.status', $app->id) }}" id="returnFormStandard{{$app->id}}" method="POST" class="return-form-element m-0" data-app-id="{{$app->id}}" onsubmit="handleAjaxActionSubmit(event, this)">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="returned">
                                <div class="modal-body p-4 text-start">
                                    <p class="small text-muted mb-3">
                                        Return this appointment back to the patient for correction without attaching suggested edits. The patient will reschedule manually.
                                    </p>
                                    <div class="mb-3">
                                        <label for="return_reason_select_{{$app->id}}" class="smaller fw-bold mb-2 text-uppercase d-block" style="color: var(--text-muted);">Reason for Return</label>
                                        <select id="return_reason_select_{{$app->id}}" name="return_reason" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="window.toggleReturnReasonField('{{ $app->id }}', this)" required>
                                            <option value="" disabled selected>-- Select a return reason --</option>
                                            <option value="Mismatched identification documents">Mismatched identification documents</option>
                                            <option value="Incorrect or incomplete personal details">Incorrect or incomplete personal details</option>
                                            <option value="No payment received / pending verification">No payment received / pending verification</option>
                                            <option value="Invalid test selection for patient demographics">Invalid test selection for patient demographics</option>
                                            <option value="Discrepancy in schedule / date selection">Discrepancy in schedule / date selection</option>
                                            <option value="Patient requested for rescheduling/cancellation">Patient requested for rescheduling/cancellation</option>
                                            <option value="Others">Others (Specify details below)</option>
                                        </select>
                                    </div>

                                    <div id="custom_return_reason_wrapper_{{$app->id}}" class="mb-3 d-none">
                                        <label for="return_reason_{{$app->id}}" class="smaller fw-bold mb-2 text-uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                                        <textarea id="return_reason_{{$app->id}}" class="form-control shadow-none return-reason-textarea" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="3" placeholder="Identify the specific correction needed..."></textarea>
                                    </div>
                                    <div class="mt-2"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
                                </div>
                                <div class="modal-footer p-3 border-top d-flex gap-2 justify-content-end" style="border-color: var(--border-color) !important;">
                                    <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">
                                        <i class="bi bi-arrow-return-left me-1"></i> Send Return
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- WAY 2: RETURN WITH SUGGESTIONS (Full Edit Proposal with Anti-Conflict Checks) --}}
                        <div class="tab-pane fade" id="ret-pane-suggest-{{ $app->id }}" role="tabpanel">
                            <form action="{{ route('appointments.suggest-changes', $app->id) }}" id="returnFormSuggest{{$app->id}}" method="POST" class="suggest-form-element m-0" data-app-id="{{$app->id}}" onsubmit="handleAjaxActionSubmit(event, this)">
                                @csrf
                                <div class="modal-body p-4 text-start">
                                    <div class="alert alert-clinical border-accent bg-accent bg-opacity-10 p-2.5 rounded-3 mb-3 smaller">
                                        <i class="bi bi-magic text-accent me-1"></i>
                                        <strong>Propose Resubmission Adjustments:</strong> The patient will be notified with your recommendations and can 1-click apply them into their resubmission form.
                                    </div>

                                    {{-- Reason / Justification Dropdown with "Others" Textarea --}}
                                    <div class="mb-3">
                                        <label for="suggest_reason_select_{{$app->id}}" class="smaller fw-bold text-uppercase mb-1 d-block text-muted">Reason for Return & Adjustments</label>
                                        <select id="suggest_reason_select_{{$app->id}}" name="reason" class="form-select shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="window.toggleSuggestReasonField('{{ $app->id }}', this)" required>
                                            <option value="" disabled selected>-- Select a return reason --</option>
                                            <option value="Discrepancy in schedule / date selection">Discrepancy in schedule / date selection</option>
                                            <option value="Invalid test selection for patient demographics">Invalid test selection for patient demographics</option>
                                            <option value="Patient requested for rescheduling/cancellation">Patient requested for rescheduling/cancellation</option>
                                            <option value="Mismatched identification documents">Mismatched identification documents</option>
                                            <option value="Incorrect or incomplete personal details">Incorrect or incomplete personal details</option>
                                            <option value="No payment received / pending verification">No payment received / pending verification</option>
                                            <option value="Others">Others (Specify details below)</option>
                                        </select>
                                    </div>

                                    <div id="custom_suggest_reason_wrapper_{{$app->id}}" class="mb-3 d-none">
                                        <label for="suggest_reason_{{$app->id}}" class="smaller fw-bold mb-2 text-uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                                        <textarea id="suggest_reason_{{$app->id}}" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="3" placeholder="Provide details regarding the recommended adjustments..."></textarea>
                                        <div class="mt-1"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
                                    </div>

                                    {{-- Schedule Proposal --}}
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="smaller fw-bold text-uppercase mb-1 d-block text-muted">Suggested Visit Date</label>
                                            <input type="date" name="suggested_date" class="form-control" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" min="{{ date('Y-m-d') }}" value="{{ $app->appointment_date ? $app->appointment_date->format('Y-m-d') : date('Y-m-d') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="smaller fw-bold text-uppercase mb-1 d-block text-muted">Suggested Time Slot</label>
                                            <input type="time" name="suggested_time" class="form-control" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" value="{{ $app->time_slot }}" required>
                                        </div>
                                    </div>

                                    {{-- Tests & Packages Checklist --}}
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="smaller fw-bold text-uppercase mb-0 text-muted">
                                                <i class="bi bi-check2-square text-accent me-1"></i>Suggested Tests & Packages:
                                            </label>
                                            <small class="text-secondary smaller" id="sug_counter_{{ $app->id }}"></small>
                                        </div>

                                        <div class="border rounded p-2 custom-scroll" style="max-height: 220px; overflow-y: auto; background-color: var(--bg-main) !important; border-color: var(--border-color) !important;">
                                            {{-- Packages --}}
                                            @if(isset($packages) && $packages->isNotEmpty())
                                                <small class="text-accent fw-bold d-block mb-1 fs-x-small text-uppercase">Health Packages</small>
                                                @foreach($packages as $pkg)
                                                    @php $pkgGender = strtolower($pkg->gender_restriction ?? 'both'); @endphp
                                                    @if($pkgGender !== 'both' && $pkgGender !== $patientSex)
                                                        @continue
                                                    @endif
                                                    <div class="form-check p-2 mb-1 rounded bg-card border d-flex align-items-center justify-content-between sug-pkg-item" id="sug_pkg_wrap_{{ $app->id }}_{{ $pkg->id }}" style="border-color: var(--border-color) !important; transition: all 0.2s ease;">
                                                        <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                            <input class="form-check-input ms-0 me-1 sug-pkg-cb" type="checkbox" name="package_ids[]" value="{{ $pkg->id }}" id="sug_pkg_{{ $app->id }}_{{ $pkg->id }}" data-services="{{ json_encode($pkg->services->pluck('id')->toArray()) }}" {{ $app->packages->contains($pkg->id) ? 'checked' : '' }} onchange="handleSuggestCatalogChange('{{ $app->id }}')">
                                                            <label class="form-check-label text-main small cursor-pointer fw-bold mb-0 text-truncate" for="sug_pkg_{{ $app->id }}_{{ $pkg->id }}">
                                                                <span class="badge bg-success bg-opacity-15 text-accent py-0.5 px-1 me-1" style="font-size: 0.58rem;">PKG</span>
                                                                {{ strtoupper($pkg->name) }}
                                                            </label>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-danger bg-opacity-15 text-white border border-danger border-opacity-30 sug-pkg-conflict-badge d-none smaller" style="font-size: 0.58rem;">Conflicting</span>
                                                            <span class="text-accent smaller fw-bold text-nowrap">₱{{ number_format($pkg->price, 2) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Individual Services --}}
                                            @if(isset($services) && $services->isNotEmpty())
                                                <small class="text-muted fw-bold d-block mt-2 mb-1 fs-x-small text-uppercase">Individual Tests</small>
                                                @foreach($services as $svc)
                                                    @php $svcGender = strtolower($svc->gender_restriction ?? 'both'); @endphp
                                                    @if($svcGender !== 'both' && $svcGender !== $patientSex)
                                                        @continue
                                                    @endif
                                                    <div class="form-check p-2 mb-1 rounded bg-card border d-flex align-items-center justify-content-between sug-svc-item" id="sug_svc_wrap_{{ $app->id }}_{{ $svc->id }}" style="border-color: var(--border-color) !important; transition: all 0.2s ease;">
                                                        <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                            <input class="form-check-input ms-0 me-1 sug-svc-cb" type="checkbox" name="service_ids[]" value="{{ $svc->id }}" id="sug_svc_{{ $app->id }}_{{ $svc->id }}" {{ $app->services->contains($svc->id) ? 'checked' : '' }} onchange="handleSuggestCatalogChange('{{ $app->id }}')">
                                                            <label class="form-check-label text-main small cursor-pointer mb-0 text-truncate" for="sug_svc_{{ $app->id }}_{{ $svc->id }}">
                                                                {{ strtoupper($svc->name) }}
                                                            </label>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-warning bg-opacity-15 text-secondary border border-warning border-opacity-30 sug-pkg-covered-badge d-none smaller" style="font-size: 0.58rem;">In Package</span>
                                                            <span class="text-muted smaller fw-bold text-nowrap">₱{{ number_format($svc->price, 2) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <div class="invalid-feedback d-none text-danger fw-bold smaller mt-1" id="err_suggest_tests_{{ $app->id }}"></div>
                                    </div>
                                </div>
                                <div class="modal-footer p-3 border-top d-flex gap-2 justify-content-end" style="border-color: var(--border-color) !important;">
                                    <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold">
                                        <i class="bi bi-send-check-fill me-1"></i> Return With Suggestions
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Payment Confirmation Modal --}}
        <div class="modal fade" id="confirmPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    <div class="modal-header border-bottom py-3" style="border-color: var(--border-color) !important;">
                        <h5 class="modal-title text-accent fw-bold text-uppercase small m-0" style="color: var(--brand-accent) !important;">Confirm Payment Receipt?</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small mb-0" style="color: var(--text-main) !important;">
                            Are you sure you want to flag this appointment as <strong class="fw-bold text-success">PAID</strong>? This will confirm manual receipt of funds for patient <strong class="fw-bold" style="color: var(--text-main) !important;">{{ $app->patient_name }}</strong>.
                        </p>
                    </div>
                    <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                        <div class="d-flex w-100">
                            <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold text-uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                            <form action="{{ route('appointments.confirm-payment', $app->id) }}" method="POST" class="w-50 m-0" onsubmit="handleAjaxActionSubmit(event, this)">
                                @csrf
                                <input type="hidden" name="payment_status" value="paid">
                                <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold text-uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Confirm</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Revoke Payment Modal --}}
        <div class="modal fade" id="revokePaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    <div class="modal-header border-bottom py-3 border-danger" style="border-color: #ff4d4d !important;">
                        <h5 class="modal-title text-danger fw-bold text-uppercase small m-0">Revoke Payment Status?</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small mb-0" style="color: var(--text-main) !important;">
                            Are you sure you want to revert this appointment's payment status to <strong class="text-danger fw-bold">UNPAID</strong>? This will rollback confirmation details for patient <strong style="color: var(--text-main) !important;">{{ $app->patient_name }}</strong>.
                        </p>
                    </div>
                    <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                        <div class="d-flex w-100">
                            <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold text-uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                            <form action="{{ route('appointments.confirm-payment', $app->id) }}" method="POST" class="w-50 m-0" onsubmit="handleAjaxActionSubmit(event, this)">
                                @csrf
                                <input type="hidden" name="payment_status" value="unpaid">
                                <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold text-uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Yes, Revoke</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Mark Payment as Invalid Modal --}}
        <div class="modal fade" id="invalidPaymentModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <form action="{{ route('appointments.invalid-payment', $app->id) }}" method="POST" class="modal-content shadow-lg border-0" onsubmit="handleAjaxActionSubmit(event, this)" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h5 class="modal-title text-danger fw-bold text-uppercase small m-0">Flag Payment as Invalid?</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small text-muted mb-3">Flag this uploaded proof of payment receipt as invalid. Select a justification:</p>
                        <div class="mb-3">
                            <label class="smaller fw-bold text-secondary text-uppercase mb-1 d-block">Reason for Invalidating</label>
                            <select name="reason" id="invalid_reason_select_{{ $app->id }}" class="form-select py-2.5 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="window.toggleInvalidReasonField('{{ $app->id }}', this)" required>
                                <option value="" disabled selected>-- Select an invalidation reason --</option>
                                <option value="Receipt image is blurry / unreadable">Receipt image is blurry / unreadable</option>
                                <option value="Reference number mismatch / Fake receipt">Reference number mismatch / Fake receipt</option>
                                <option value="Incorrect amount sent">Incorrect amount sent</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                        </div>
                        <div id="invalid_custom_reason_wrapper_{{ $app->id }}" class="mb-3 d-none">
                            <label class="smaller fw-bold text-secondary text-uppercase mb-1 d-block">Specify Custom Reason</label>
                            <textarea name="custom_reason" id="invalid_custom_reason_{{ $app->id }}" class="form-control" rows="3" placeholder="Provide details regarding the invalid payment..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer p-3 border-top d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-custom btn-danger-custom py-2 px-4 fw-bold">Flag as Invalid</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 5. Confirm Refund Modal --}}
        <div class="modal fade" id="confirmRefundModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <form action="{{ route('appointments.refund', $app->id) }}" method="POST" class="modal-content shadow-lg border-0" onsubmit="handleAjaxActionSubmit(event, this)" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h5 class="modal-title text-success fw-bold text-uppercase small m-0">Confirm Refund?</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small text-muted mb-0">Confirm that the refund for this canceled appointment has been manually processed and completed. This action will log your name and the current timestamp as the official processor.</p>
                    </div>
                    <div class="modal-footer p-3 border-top d-flex gap-2 text-center justify-content-center" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-custom btn-accent btn-success py-2 px-4 fw-bold">Confirm Refund</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
let searchDebounceTimer = null;
const packagesMap = @json($packages->keyBy('id'));
const servicesMap = @json($services->keyBy('id'));

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

/* 1-Click Triage Pill Selection */
function selectTriageStatus(statusValue) {
    const statusInput = document.getElementById('filterStatusInput');
    if (statusInput) {
        statusInput.value = statusValue;
    }
    document.querySelectorAll('.triage-pill').forEach(pill => pill.classList.remove('active'));
    event.currentTarget.classList.add('active');
    triggerDynamicQueueFilter();
}

function resetAllQueueFilters() {
    const form = document.getElementById('queueFilterForm');
    if (form) {
        form.reset();
        const statusInput = document.getElementById('filterStatusInput');
        if (statusInput) statusInput.value = '';
        const searchInput = document.getElementById('queueSearchInput');
        if (searchInput) searchInput.value = '';
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.classList.add('d-none');
        triggerDynamicQueueFilter();
    }
}

async function fetchQueueWithParams(urlOverride = null) {
    const form = document.getElementById('queueFilterForm');
    let url;
    
    if (urlOverride) {
        url = urlOverride;
    } else if (form) {
        const formData = new FormData(form);
        const searchParams = new URLSearchParams();
        searchParams.set('view', 'queue');
        
        for (const [key, value] of formData.entries()) {
            if (value && value.toString().trim() !== '' && key !== 'staff_page') {
                searchParams.set(key, value.toString().trim());
            }
        }
        url = form.action + '?' + searchParams.toString();
    } else {
        url = "{{ route('appointments.index', ['view' => 'queue']) }}";
    }

    const parsedUrl = new URL(url, window.location.origin);
    parsedUrl.searchParams.set('view', 'queue');
    const finalUrl = parsedUrl.toString();

    try {
        const response = await fetch(finalUrl, {
            headers: {
                'Accept': 'text/html, application/xhtml+xml',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) return;

        const html = await response.text();
        if (html.trim().startsWith('{') || html.trim().startsWith('[')) {
            window.location.href = finalUrl;
            return;
        }

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const activePane = document.querySelector('.appointment-detail-pane:not(.d-none)');
        const activeId = activePane ? activePane.id : null;

        const newContent = doc.getElementById('listContent');
        const oldContent = document.getElementById('listContent');
        if (newContent && oldContent) {
            oldContent.innerHTML = newContent.innerHTML;
        }

        const newWorkspace = doc.getElementById('workspace-container');
        const oldWorkspace = document.getElementById('workspace-container');
        if (newWorkspace && oldWorkspace) {
            oldWorkspace.innerHTML = newWorkspace.innerHTML;
        }

        const newModals = doc.getElementById('queue-modals-container');
        const oldModals = document.getElementById('queue-modals-container');
        if (newModals && oldModals) {
            oldModals.innerHTML = newModals.innerHTML;
        }

        window.history.pushState({ path: finalUrl }, '', finalUrl);
        syncFormWithUrl(parsedUrl);
        rebindQueueEvents();
        initModalListeners();

        // Calculate dependencies for restored modals
        document.querySelectorAll('[id^="ret-pane-suggest-"]').forEach(pane => {
            const appId = pane.id.replace('ret-pane-suggest-', '');
            if (appId) {
                window.recalculateSuggestDependencies(appId);
            }
        });

        let restored = false;
        if (activeId) {
            const recordId = activeId.replace('details-', '');
            const cardEl = document.getElementById(`card-${recordId}`);
            const paneEl = document.getElementById(activeId);
            if (cardEl && paneEl) {
                showAppointmentDetails(recordId);
                restored = true;
            }
        }

        if (!restored) {
            const firstCard = document.querySelector('#staff-queue-deck .app-list-card');
            if (firstCard && firstCard.id) {
                const firstId = firstCard.id.replace('card-', '');
                showAppointmentDetails(firstId);
            } else {
                resetActiveDetail();
            }
        }
    } catch (err) {
        console.error("Dynamic Queue Fetch failed:", err);
    }
}

function syncFormWithUrl(urlObj) {
    const searchInput = document.getElementById('queueSearchInput');
    const statusInput = document.getElementById('filterStatusInput');
    const appTypeSelect = document.getElementById('filterAppTypeSelect');
    const dateInput = document.getElementById('filterDateInput');
    const sortBySelect = document.getElementById('filterSortBySelect');
    const orderSelect = document.getElementById('filterOrderSelect');
    const clearBtn = document.getElementById('clearSearchBtn');

    const statusVal = urlObj.searchParams.get('status') || '';
    if (statusInput) statusInput.value = statusVal;

    document.querySelectorAll('.triage-pill').forEach(pill => {
        pill.classList.remove('active');
        if ((pill.getAttribute('onclick') || '').includes(`'${statusVal}'`)) {
            pill.classList.add('active');
        }
    });

    if (searchInput) {
        searchInput.value = urlObj.searchParams.get('search') || '';
        if (clearBtn) clearBtn.classList.toggle('d-none', !searchInput.value);
    }
    if (appTypeSelect) {
        appTypeSelect.value = urlObj.searchParams.get('app_type') || 'all';
    }
    if (dateInput) {
        dateInput.value = urlObj.searchParams.get('date') || '';
    }
    if (sortBySelect) {
        sortBySelect.value = urlObj.searchParams.get('sort_by') || 'date';
    }
    if (orderSelect) {
        orderSelect.value = urlObj.searchParams.get('order') || 'desc';
    }
}

function triggerDynamicQueueFilter() {
    fetchQueueWithParams();
}

function debounceQueueSearch() {
    const input = document.getElementById('queueSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) {
        clearBtn.classList.toggle('d-none', !input.value);
    }
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        triggerDynamicQueueFilter();
    }, 350);
}

function clearQueueSearch() {
    const input = document.getElementById('queueSearchInput');
    if (input) {
        input.value = '';
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.classList.add('d-none');
        triggerDynamicQueueFilter();
    }
}

function rebindQueueEvents() {
    document.querySelectorAll('#listContent .pagination a, #staff-pagination-wrapper .pagination a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (link.href && link.href !== '#' && !link.closest('.disabled')) {
                fetchQueueWithParams(link.href);
            }
        });
    });
}

window.jumpToQueuePage = function(inputEl) {
    if (!inputEl) return;
    const pageParam = inputEl.dataset.pageName || 'staff_page';
    const maxPage = parseInt(inputEl.dataset.maxPage, 10) || 1;
    let page = parseInt(inputEl.value.trim(), 10);
    
    if (isNaN(page) || page < 1) return;
    if (page > maxPage) page = maxPage;
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set(pageParam, page);
    
    inputEl.value = '';
    fetchQueueWithParams(currentUrl.toString());
};

/* -------------------------------------------------------------------------- */
/* STAFF SUGGESTIONS CONFLICT CHECK ENGINE                                    */
/* -------------------------------------------------------------------------- */
window.recalculateSuggestDependencies = function(appId) {
    const pane = document.getElementById(`ret-pane-suggest-${appId}`);
    if (!pane) return;

    const coveredByCheckedPackages = new Set();
    const activePackages = Array.from(pane.querySelectorAll('.sug-pkg-cb:checked'));

    activePackages.forEach(pkgCb => {
        const pkgId = pkgCb.value;
        const pkg = packagesMap[pkgId];
        if (pkg && pkg.services) {
            pkg.services.forEach(svc => coveredByCheckedPackages.add(svc.id.toString()));
        } else if (pkgCb.dataset.services) {
            try {
                const sArr = JSON.parse(pkgCb.dataset.services);
                sArr.forEach(id => coveredByCheckedPackages.add(id.toString()));
            } catch(e) {}
        }
    });

    const checkedIndividualServices = new Set();
    pane.querySelectorAll('.sug-svc-cb:checked').forEach(svcCb => {
        checkedIndividualServices.add(svcCb.value.toString());
    });

    pane.querySelectorAll('.sug-pkg-cb').forEach(pkgCb => {
        const pkgId = pkgCb.value;
        const pkg = packagesMap[pkgId];
        let pkgServiceIds = [];
        if (pkg && pkg.services) {
            pkgServiceIds = pkg.services.map(s => s.id.toString());
        } else if (pkgCb.dataset.services) {
            try {
                pkgServiceIds = JSON.parse(pkgCb.dataset.services).map(String);
            } catch(e) {}
        }

        const wrap = document.getElementById(`sug_pkg_wrap_${appId}_${pkgId}`);
        const conflictBadge = wrap ? wrap.querySelector('.sug-pkg-conflict-badge') : null;

        if (pkgCb.checked) {
            pkgCb.disabled = false;
            if (wrap) {
                wrap.classList.remove('opacity-50');
                wrap.style.pointerEvents = 'auto';
                wrap.style.cursor = 'default';
                wrap.removeAttribute('title');
            }
            if (conflictBadge) conflictBadge.classList.add('d-none');
            return;
        }

        const conflictsWithPackage = pkgServiceIds.some(id => coveredByCheckedPackages.has(id));
        const conflictsWithIndividual = pkgServiceIds.some(id => checkedIndividualServices.has(id));
        const isConflicting = conflictsWithPackage || conflictsWithIndividual;

        if (isConflicting) {
            pkgCb.disabled = true;
            if (wrap) {
                wrap.classList.add('opacity-50');
                wrap.style.pointerEvents = 'none';
                wrap.style.cursor = 'not-allowed';
                if (conflictsWithPackage) {
                    wrap.setAttribute('title', 'Conflicting package: shares tests with an already selected package.');
                } else {
                    wrap.setAttribute('title', 'Conflicting package: includes an individual test that is already selected.');
                }
            }
            if (conflictBadge) conflictBadge.classList.remove('d-none');
        } else {
            pkgCb.disabled = false;
            if (wrap) {
                wrap.classList.remove('opacity-50');
                wrap.style.pointerEvents = 'auto';
                wrap.style.cursor = 'default';
                wrap.removeAttribute('title');
            }
            if (conflictBadge) conflictBadge.classList.add('d-none');
        }
    });

    pane.querySelectorAll('.sug-svc-cb').forEach(svcCb => {
        const isCovered = coveredByCheckedPackages.has(svcCb.value.toString());
        const wrap = document.getElementById(`sug_svc_wrap_${appId}_${svcCb.value}`);
        const coveredBadge = wrap ? wrap.querySelector('.sug-pkg-covered-badge') : null;

        if (isCovered) {
            svcCb.checked = false;
            svcCb.disabled = true;
            if (wrap) {
                wrap.classList.add('opacity-50');
                wrap.style.pointerEvents = 'none';
                wrap.style.cursor = 'not-allowed';
                wrap.setAttribute('title', 'Already included in a selected package.');
            }
            if (coveredBadge) coveredBadge.classList.remove('d-none');
        } else {
            svcCb.disabled = false;
            if (wrap) {
                wrap.classList.remove('opacity-50');
                wrap.style.pointerEvents = 'auto';
                wrap.style.cursor = 'default';
                wrap.removeAttribute('title');
            }
            if (coveredBadge) coveredBadge.classList.add('d-none');
        }
    });

    const countEl = document.getElementById(`sug_counter_${appId}`);
    if (countEl) {
        const total = pane.querySelectorAll('.sug-pkg-cb:checked').length + pane.querySelectorAll('.sug-svc-cb:checked').length;
        countEl.innerText = `${total} Selected`;
    }
};

window.handleSuggestCatalogChange = function(appId) {
    window.recalculateSuggestDependencies(appId);
    const errEl = document.getElementById(`err_suggest_tests_${appId}`);
    if (errEl) {
        errEl.innerText = '';
        errEl.classList.add('d-none');
        errEl.classList.remove('d-block');
    }
};

function initModalListeners() {
    document.querySelectorAll('.return-form-element').forEach(form => {
        if (form.dataset.bound === 'true') return;
        form.dataset.bound = 'true';
        form.addEventListener('submit', function(e) {
            const appId = form.dataset.appId;
            const select = document.getElementById(`return_reason_select_${appId}`);
            const textarea = document.getElementById(`return_reason_${appId}`);
            const activeVal = (select && select.value === 'Others') ? textarea : select;
            if (!activeVal || !activeVal.value || activeVal.value.trim().length < 5) {
                e.preventDefault();
                alert('A valid reason of at least 5 characters is required.');
                if (activeVal) activeVal.focus();
            }
        });
    });

    document.querySelectorAll('.suggest-form-element').forEach(form => {
        if (form.dataset.bound === 'true') return;
        form.dataset.bound = 'true';
        form.addEventListener('submit', function(e) {
            const appId = form.dataset.appId;
            const pane = document.getElementById(`ret-pane-suggest-${appId}`);
            const select = document.getElementById(`suggest_reason_select_${appId}`);
            const textarea = document.getElementById(`suggest_reason_${appId}`);
            const activeVal = (select && select.value === 'Others') ? textarea : select;
            const errEl = document.getElementById(`err_suggest_tests_${appId}`);

            if (!activeVal || !activeVal.value || activeVal.value.trim().length < 5) {
                e.preventDefault();
                alert('Please select or specify a reason of at least 5 characters.');
                if (activeVal) activeVal.focus();
                return false;
            }

            if (pane) {
                const checkedPkgs = Array.from(pane.querySelectorAll('.sug-pkg-cb:checked'));
                const checkedSvcs = Array.from(pane.querySelectorAll('.sug-svc-cb:checked'));

                if (checkedPkgs.length === 0 && checkedSvcs.length === 0) {
                    e.preventDefault();
                    if (errEl) {
                        errEl.innerText = 'Please select at least one individual test or package.';
                        errEl.classList.remove('d-none');
                        errEl.classList.add('d-block');
                    } else {
                        alert('Please select at least one individual test or package.');
                    }
                    return false;
                }

                const coveredServices = new Set();
                for (const pkgCb of checkedPkgs) {
                    const pkg = packagesMap[pkgCb.value];
                    let serviceIds = [];
                    if (pkg && pkg.services) {
                        serviceIds = pkg.services.map(s => s.id.toString());
                    } else if (pkgCb.dataset.services) {
                        try { serviceIds = JSON.parse(pkgCb.dataset.services).map(String); } catch(err) {}
                    }

                    for (const svcId of serviceIds) {
                        if (coveredServices.has(svcId)) {
                            e.preventDefault();
                            if (errEl) {
                                errEl.innerText = 'Conflicting packages detected: Multiple selected packages share identical tests.';
                                errEl.classList.remove('d-none');
                                errEl.classList.add('d-block');
                            } else {
                                alert('Conflicting packages detected: Multiple selected packages share identical tests.');
                            }
                            return false;
                        }
                        coveredServices.add(svcId);
                    }
                }

                for (const svcCb of checkedSvcs) {
                    if (coveredServices.has(svcCb.value.toString())) {
                        e.preventDefault();
                        if (errEl) {
                            errEl.innerText = 'Double selection detected: An individual test is already covered in a selected package.';
                            errEl.classList.remove('d-none');
                            errEl.classList.add('d-block');
                        } else {
                            alert('Double selection detected: An individual test is already covered in a selected package.');
                        }
                        return false;
                    }
                }
            }
        });
    });

    document.querySelectorAll('.action-teleport-modal').forEach(modal => {
        if (modal.dataset.initBound === 'true') return;
        modal.dataset.initBound = 'true';
        
        modal.addEventListener('shown.bs.modal', function () {
            const appId = this.id.replace('retModal', '');
            if (appId && typeof window.recalculateSuggestDependencies === 'function') {
                window.recalculateSuggestDependencies(appId);
            }
        });
        modal.addEventListener('show.bs.modal', function () {
            const appId = this.id.replace('retModal', '');
            if (appId && typeof window.recalculateSuggestDependencies === 'function') {
                window.recalculateSuggestDependencies(appId);
            }
        });
    });
}

function initTriageStripScroll() {
    const strip = document.getElementById('queueTriageStrip');
    if (!strip) return;

    let isDown = false;
    let startX;
    let scrollLeft;

    // Mouse drag scrolling
    strip.addEventListener('mousedown', (e) => {
        isDown = true;
        strip.style.cursor = 'grabbing';
        startX = e.pageX - strip.offsetLeft;
        scrollLeft = strip.scrollLeft;
    });
    strip.addEventListener('mouseleave', () => {
        isDown = false;
        strip.style.cursor = 'grab';
    });
    strip.addEventListener('mouseup', () => {
        isDown = false;
        strip.style.cursor = 'grab';
    });
    strip.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - strip.offsetLeft;
        const walk = (x - startX) * 1.5;
        strip.scrollLeft = scrollLeft - walk;
    });

    // Horizontal mouse wheel scrolling
    strip.addEventListener('wheel', (e) => {
        if (e.deltaY !== 0) {
            e.preventDefault();
            strip.scrollLeft += e.deltaY;
        }
    }, { passive: false });
}

document.addEventListener('DOMContentLoaded', () => {
    rebindQueueEvents();
    initModalListeners();
    initTriageStripScroll();

    document.querySelectorAll('[id^="ret-pane-suggest-"]').forEach(pane => {
        const appId = pane.id.replace('ret-pane-suggest-', '');
        if (appId && typeof window.recalculateSuggestDependencies === 'function') {
            window.recalculateSuggestDependencies(appId);
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const selectId = urlParams.get('id');
    if (selectId) {
        const card = document.getElementById(`card-${selectId}`);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            showAppointmentDetails(selectId);
        }
    } else {
        const firstCard = document.querySelector('#staff-queue-deck .app-list-card');
        if (firstCard && firstCard.id && window.innerWidth >= 992) {
            const firstId = firstCard.id.replace('card-', '');
            showAppointmentDetails(firstId);
        }
    }

    window.addEventListener('popstate', () => {
        fetchQueueWithParams(window.location.href);
    });
});

window.toggleReturnReasonField = function(appId, select) {
    const wrapper = document.getElementById(`custom_return_reason_wrapper_${appId}`);
    const textarea = document.getElementById(`return_reason_${appId}`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
            textarea.setAttribute('name', 'return_reason');
            select.removeAttribute('name');
            textarea.value = '';
            textarea.focus();
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
            textarea.removeAttribute('name');
            select.setAttribute('name', 'return_reason');
            textarea.value = select.value;
        }
    }
};

window.toggleSuggestReasonField = function(appId, select) {
    const wrapper = document.getElementById(`custom_suggest_reason_wrapper_${appId}`);
    const textarea = document.getElementById(`suggest_reason_${appId}`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
            textarea.setAttribute('name', 'reason');
            select.removeAttribute('name');
            textarea.value = '';
            textarea.focus();
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
            textarea.removeAttribute('name');
            select.setAttribute('name', 'reason');
            textarea.value = select.value;
        }
    }
};

window.toggleInvalidReasonField = function(appId, select) {
    const wrapper = document.getElementById(`invalid_custom_reason_wrapper_${appId}`);
    const textarea = document.getElementById(`invalid_custom_reason_${appId}`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
            textarea.focus();
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
        }
    }
};
</script>
@endpush

@push('styles')
<style>
/* Triage Strip Pills with Active Smooth Scrollbar */
.queue-triage-container {
    position: relative;
    width: 100%;
}
.queue-triage-strip {
    cursor: grab;
    overflow-x: auto !important;
    overflow-y: hidden !important;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--brand-accent) var(--border-color);
}
.queue-triage-strip::-webkit-scrollbar {
    height: 5px;
}
.queue-triage-strip::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 8px;
}
.queue-triage-strip::-webkit-scrollbar-thumb {
    background-color: var(--brand-accent);
    border-radius: 8px;
}

.triage-pill {
    background-color: var(--bg-card);
    border: 1.5px solid var(--border-color);
    color: var(--text-muted);
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex-shrink: 0;
    user-select: none;
    transition: all 0.2s ease-in-out;
}
.triage-pill:hover {
    border-color: var(--brand-accent) !important;
    color: var(--brand-accent) !important;
}
.triage-pill.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 10px rgba(25, 211, 140, 0.25) !important;
}
.triage-pill-action.active {
    background-color: #f59e0b !important;
    border-color: #f59e0b !important;
    color: #ffffff !important;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.3) !important;
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

/* Jump Input Styling */
.queue-jump-input::-webkit-outer-spin-button,
.queue-jump-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.queue-jump-input[type=number] {
    -moz-appearance: textfield;
}
.queue-jump-input {
    width: 38px;
    height: 35px;
    padding: 2px 4px;
    font-weight: 700;
    font-size: 0.78rem;
    border-radius: 6px;
    background-color: var(--bg-card);
    color: var(--text-main);
    border: 1.5px solid var(--border-color);
    transition: all 0.2s ease;
}
.queue-jump-input:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 8px rgba(25, 211, 140, 0.3) !important;
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
}
</style>
@endpush