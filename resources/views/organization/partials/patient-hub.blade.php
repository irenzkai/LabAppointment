<!-- resources/views/organization/partials/patient-hub.blade.php -->

{{-- =========================================================================
     1. MULTI-ORGANIZATION SELECTOR BAR & NEW PARTNERSHIP APPLICATION
     ========================================================================= --}}
@if($userOrganizations->isNotEmpty())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 p-3 rounded-3 border bg-card shadow-sm" style="border-color: var(--border-color) !important;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="small fw-bold text-secondary uppercase tracking-wider me-1">
                <i class="bi bi-buildings-fill text-accent me-1"></i> My Organizations:
            </span>
            @foreach($userOrganizations as $org)
                @php
                    $isOrgActive = $currentOrg && ($currentOrg->id === $org->id);
                    $badgeClass = match($org->status) {
                        'approved' => 'bg-success text-white',
                        'pending', 'pending_reactivation' => 'bg-warning text-dark',
                        'deactivated' => 'bg-secondary text-white',
                        'rejected' => 'bg-danger text-white',
                        default => 'bg-secondary text-white'
                    };
                @endphp
                <a href="{{ route('organization.index', ['view' => 'patient', 'org_id' => $org->id]) }}" 
                   class="btn btn-sm {{ $isOrgActive ? 'btn-accent fw-bold shadow-sm' : 'btn-outline-secondary' }} px-3 py-1.5 uppercase tracking-wider text-nowrap d-inline-flex align-items-center gap-1" 
                   style="font-size: 0.72rem; border-radius: 8px;">
                    <span>{{ $org->name }}</span>
                    <span class="badge {{ $badgeClass }} px-2 py-0.5" style="font-size: 0.58rem;">
                        {{ strtoupper(str_replace('_', ' ', $org->status)) }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Patient can apply for as many organizations as they want --}}
        <button type="button" class="btn btn-sm btn-outline-accent py-1.5 px-3 fw-bold uppercase flex-shrink-0" style="font-size: 0.72rem; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#applyOrgModal">
            <i class="bi bi-building-add me-1"></i> Apply for Another Organization
        </button>
    </div>
@endif

{{-- =========================================================================
     2. STATUS CARDS & MANAGEMENT FOR ACTIVE SELECTED ORGANIZATION
     ========================================================================= --}}
@if($currentOrg)
    @if($currentOrg->isApproved())
        {{-- Clean Separated Partner Status Card --}}
        <div class="card p-4 border bg-card shadow-sm rounded-3 mb-4 text-start" style="border-color: var(--border-color) !important;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-accent flex-shrink-0" style="width: 54px; height: 54px; background-color: rgba(25, 211, 140, 0.12); border: 1.5px solid var(--brand-accent);">
                        <i class="bi bi-patch-check-fill fs-3 text-accent"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h4 class="fw-bold text-main mb-0 uppercase tracking-tight fs-4">{{ $currentOrg->name }}</h4>
                            <span class="badge bg-success text-white px-2.5 py-1 small fw-bold">
                                RECOGNIZED PARTNER
                            </span>
                            @if($currentOrg->hasPendingNameChange())
                                <span class="badge bg-warning text-dark px-2.5 py-1 smaller fw-bold" title="Clinic staff review in progress">
                                    <i class="bi bi-hourglass-split me-1"></i> NAME CHANGE PENDING: "{{ $currentOrg->pending_name }}"
                                </span>
                            @endif
                        </div>
                        <div class="small text-muted mt-1">
                            Authorized Representative: <strong class="text-main">{{ $currentOrg->representative_name ?: $user->name }}</strong> &bull;
                            Phone: <strong class="text-main font-monospace">{{ $currentOrg->representative_phone ?: $user->phone }}</strong> &bull;
                            Managing Account: <strong class="text-main">{{ $user->email }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Representative Management Controls (Edit Name, Deactivate Org) --}}
                <div class="d-flex gap-2 flex-wrap flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-accent py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem; border-radius: 8px;" onclick="openEditOrgNameModal({{ $currentOrg->id }}, '{{ addslashes($currentOrg->name) }}')">
                        <i class="bi bi-pencil-square me-1"></i> Edit Name
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem; border-radius: 8px;" onclick="openDeactivateOrgModal({{ $currentOrg->id }}, '{{ addslashes($currentOrg->name) }}')">
                        <i class="bi bi-slash-circle me-1"></i> Deactivate Org
                    </button>
                </div>
            </div>

            @if($currentOrg->hasPendingNameChange())
                <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-dark p-3 rounded-3 mb-0 mt-3 small text-start">
                    <i class="bi bi-info-circle me-1.5"></i>
                    Your requested organization name change to <strong>"{{ $currentOrg->pending_name }}"</strong> is currently under review by clinic staff. Your existing name <strong>"{{ $currentOrg->name }}"</strong> remains active until approved.
                </div>
            @endif
        </div>

    @elseif($currentOrg->isDeactivated())
        <div class="card p-4 border bg-card shadow-sm rounded-3 mb-4 text-start" style="border-color: var(--border-color) !important;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-secondary flex-shrink-0" style="width: 54px; height: 54px; background-color: rgba(108, 117, 125, 0.15); border: 1.5px solid var(--border-color);">
                        <i class="bi bi-pause-circle fs-3 text-secondary"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h4 class="fw-bold text-main mb-0 uppercase tracking-tight fs-4">{{ $currentOrg->name }}</h4>
                            <span class="badge bg-secondary text-white px-2.5 py-1 small fw-bold">
                                ORGANIZATION DEACTIVATED
                            </span>
                        </div>
                        <p class="small text-muted mb-0 mt-1" style="line-height: 1.5;">
                            This organization is currently deactivated. All custom diagnostic packages and participant rosters are locked from bulk bookings.
                            @if($currentOrg->status_reason)
                                <br><span class="text-secondary fw-semibold">Note:</span> {{ $currentOrg->status_reason }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Reactivation Request Action --}}
                <div class="flex-shrink-0">
                    @if($currentOrg->isPendingReactivation())
                        <span class="badge bg-warning text-dark px-3 py-2 small fw-bold uppercase">
                            <i class="bi bi-hourglass-split me-1"></i> Reactivation Under Review
                        </span>
                    @else
                        <button type="button" class="btn btn-sm btn-accent py-2 px-3.5 fw-bold uppercase shadow-sm" style="font-size: 0.75rem; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#reactivateOrgModal">
                            <i class="bi bi-arrow-counterclockwise me-1.5"></i> Request Reactivation
                        </button>
                    @endif
                </div>
            </div>
        </div>

    @elseif($currentOrg->isPending())
        <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 text-dark p-4 rounded-3 mb-4 text-start">
            <div class="d-flex align-items-center gap-2 mb-1 fw-bold fs-6">
                <i class="bi bi-hourglass-split fs-4 text-warning"></i>
                <span>Partnership Application Under Review</span>
            </div>
            <p class="mb-0 text-muted small" style="line-height: 1.6;">
                Your partnership application for <strong>{{ $currentOrg->name }}</strong> (Representative: {{ $currentOrg->representative_name }}, Phone: {{ $currentOrg->representative_phone }}) has been submitted and is currently undergoing clinical verification. Once approved, custom package proposals and reusable rosters will activate automatically.
            </p>
        </div>

    @elseif($currentOrg->status === 'rejected')
        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-4 rounded-3 mb-4 text-start d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 fw-bold fs-6 text-danger">
                    <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                    <span>Partnership Application Declined</span>
                </div>
                <p class="mb-0 text-muted small" style="line-height: 1.5;">
                    Your partnership request for <strong>{{ $currentOrg->name }}</strong> was declined by clinic staff.
                    @if($currentOrg->status_reason)
                        <br><strong class="text-danger">Reason:</strong> {{ $currentOrg->status_reason }}
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-danger py-2 px-3 fw-bold uppercase d-inline-flex align-items-center gap-1" style="border-radius: 8px; font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#deleteRejectedOrgModal">
                    <i class="bi bi-trash3"></i>
                    <span>Delete Application</span>
                </button>

                <button type="button" class="btn btn-sm btn-accent py-2 px-3 fw-bold uppercase d-inline-flex align-items-center gap-1 shadow-sm" style="border-radius: 8px; font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#applyOrgModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Submit New Application</span>
                </button>
            </div>
        </div>
    @endif

@else
    {{-- Initial State: Patient has not registered any organization yet --}}
    <div class="card p-4 p-md-5 border bg-card shadow-sm rounded-3 mb-4 text-start" style="border-color: var(--border-color) !important;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">
            <div>
                <h4 class="fw-bold text-main mb-2 uppercase tracking-tight fs-4">Become a Recognized Partner Organization</h4>
                <p class="text-secondary small mb-0" style="max-width: 780px; line-height: 1.6;">
                    Schools, companies, hospitals, and government agencies can register as Recognized Organizations to propose exclusive diagnostic packages, create reusable student/employee rosters, and streamline institutional health screening. A patient can apply for multiple organizations.
                </p>
            </div>
            <button type="button" class="btn-custom btn-accent py-3 px-4 fw-bold uppercase shadow-sm flex-shrink-0" data-bs-toggle="modal" data-bs-target="#applyOrgModal">
                <i class="bi bi-building-add me-1.5"></i> Apply for Partnership
            </button>
        </div>
    </div>
@endif

{{-- =========================================================================
     3. WORKSPACE TABS FOR APPROVED (OR DEACTIVATED) ACTIVE ORGANIZATION
     ========================================================================= --}}
@if($currentOrg && ($currentOrg->isApproved() || $currentOrg->isDeactivated()))
    @php
        $isRosterActive = !empty($activeGroup) || request('tab') === 'rosters';
        $isDeactivated = $currentOrg->isDeactivated();
    @endphp

    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3 flex-wrap" id="patientHubTabs" style="border-color: var(--border-color) !important;">
        <li class="nav-item">
            <button class="nav-link {{ !$isRosterActive ? 'active' : '' }} fw-bold px-4 py-2 uppercase btn-sm" id="tab-packages-btn" data-bs-toggle="pill" data-bs-target="#tab-packages">
                <i class="bi bi-box-seam me-1.5"></i> Custom Diagnostic Packages ({{ $orgPackages->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $isRosterActive ? 'active' : '' }} fw-bold px-4 py-2 uppercase btn-sm" id="tab-rosters-btn" data-bs-toggle="pill" data-bs-target="#tab-rosters">
                <i class="bi bi-people-fill me-1.5"></i> Pre-Recorded Rosters ({{ $rosterGroups->count() }} Groups)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="patientHubTabContent">
        {{-- 1. Custom Diagnostic Packages Pane --}}
        <div class="tab-pane fade {{ !$isRosterActive ? 'show active' : '' }}" id="tab-packages">
            <div class="card p-4 p-md-5 border bg-card shadow-sm rounded-3 mb-4" style="border-color: var(--border-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold text-main mb-1 uppercase tracking-tight fs-5">{{ $currentOrg->name }} Diagnostic Packages</h5>
                        <p class="text-secondary small mb-0">Custom clinical packages tailored to your company or institutional screening requirements.</p>
                    </div>
                    @if(!$isDeactivated)
                        <button type="button" class="btn-custom btn-accent py-2.5 px-4 fw-bold uppercase small" onclick="openProposePackageModal({{ $currentOrg->id }}, '{{ addslashes($currentOrg->name) }}')">
                            <i class="bi bi-plus-lg me-1"></i> Propose New Package
                        </button>
                    @endif
                </div>

                @if($isDeactivated)
                    <div class="alert alert-clinical border-secondary bg-secondary bg-opacity-10 text-secondary p-3 rounded-3 mb-4 small">
                        <i class="bi bi-info-circle me-1"></i> Custom packages are disabled because this organization is currently deactivated.
                    </div>
                @endif

                <div class="row g-3">
                    @forelse($orgPackages as $pkg)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card p-4 border h-100 d-flex flex-column justify-content-between rounded-3 position-relative" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <h6 class="fw-bold text-main mb-0 uppercase tracking-tight text-truncate fs-6" title="{{ $pkg->name }}">{{ $pkg->name }}</h6>
                                        
                                        @if($pkg->isDeclined())
                                            <span class="fs-5 fw-bold text-danger text-nowrap">Declined</span>
                                        @elseif($pkg->is_available)
                                            <span class="fs-5 fw-bold text-accent text-nowrap">₱{{ number_format($pkg->price, 2) }}</span>
                                        @else
                                            <span class="fs-5 fw-bold text-accent text-nowrap">Under Review</span>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                                        @if($pkg->trashed())
                                            <span class="badge bg-secondary text-white uppercase fs-x-small fw-bold">ARCHIVED</span>
                                        @elseif($pkg->isDeclined())
                                            <span class="badge bg-danger text-white uppercase fs-x-small fw-bold">DECLINED</span>
                                        @elseif($pkg->is_available)
                                            <span class="badge bg-success text-white uppercase fs-x-small fw-bold">ACTIVE & APPROVED</span>
                                        @elseif($pkg->isDisabled())
                                            <span class="badge bg-secondary text-white uppercase fs-x-small fw-bold">DISABLED BY CLINIC</span>
                                        @else
                                            <span class="badge bg-warning text-dark uppercase fs-x-small fw-bold">PENDING CLINIC REVIEW</span>
                                        @endif

                                        <span class="badge bg-secondary text-white uppercase fs-x-small">
                                            {{ strtoupper($pkg->gender_restriction) }}
                                        </span>

                                        @if($pkg->is_available && $pkg->estimated_time)
                                            <span class="smaller text-muted">{{ $pkg->estimated_time }} mins</span>
                                        @endif
                                    </div>

                                    @if($pkg->isDeclined() && $pkg->status_reason)
                                        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-2.5 rounded-3 mb-3 smaller text-start">
                                            <i class="bi bi-exclamation-octagon-fill me-1"></i>
                                            <strong>Reason:</strong> {{ $pkg->status_reason }}
                                        </div>
                                    @endif

                                    @if($pkg->description)
                                        <p class="text-secondary smaller mb-3 text-truncate-2">{{ $pkg->description }}</p>
                                    @endif

                                    <div class="p-3 rounded bg-main border smaller mb-3" style="border-color: var(--border-color) !important;">
                                        <span class="text-muted fw-bold uppercase d-block mb-1 fs-x-small">Bundled Examinations:</span>
                                        <span class="text-main fw-semibold">{{ $pkg->services->pluck('name')->implode(', ') ?: 'None' }}</span>
                                    </div>
                                </div>

                                {{-- Action Controls: Resubmit, Edit, Delete --}}
                                <div class="d-flex justify-content-end align-items-center gap-2 pt-3 border-top flex-wrap" style="border-color: var(--border-color) !important;">
                                    @if($pkg->isDeclined())
                                        <button type="button" class="btn btn-sm btn-accent py-1 px-2.5 fw-bold uppercase d-inline-flex align-items-center gap-1" style="font-size: 0.7rem;" onclick='openEditPackageModal(@json($pkg), @json($pkg->services->pluck("id")), true)' title="Modify and resubmit package proposal for review">
                                            <i class="bi bi-arrow-repeat"></i> Resubmit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;" onclick="openDeletePackageModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}')">
                                            Delete
                                        </button>
                                    @elseif(!$pkg->trashed())
                                        @if(!$isDeactivated)
                                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;" onclick='openEditPackageModal(@json($pkg), @json($pkg->services->pluck("id")))' title="Edit tests/details (Subject to staff re-approval)">
                                                Edit
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;" onclick="openDeletePackageModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}')">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted small italic">
                            <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-50"></i>
                            No custom packages proposed yet for {{ $currentOrg->name }}. Click <strong>Propose New Package</strong> to submit tailored examination bundles for your cohort.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 2. Pre-Recorded Rosters Pane --}}
        <div class="tab-pane fade {{ $isRosterActive ? 'show active' : '' }}" id="tab-rosters">
            <div class="card p-4 p-md-5 border bg-card shadow-sm rounded-3 mb-4" style="border-color: var(--border-color) !important;">
                <div class="d-flex justify-content-between align-items-start align-items-sm-center mb-4 flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold text-main mb-1 uppercase tracking-tight fs-5">{{ $currentOrg->name }} Member Rosters</h5>
                        <p class="text-secondary small mb-0">Select a roster group to view its participants, or register cohorts for instant loading during bulk bookings.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @if(!$isDeactivated)
                            <button type="button" class="btn-custom btn-outline-accent py-2 px-3 fw-bold uppercase small" data-bs-toggle="modal" data-bs-target="#newGroupModal">
                                <i class="bi bi-folder-plus me-1"></i> New Group
                            </button>
                            @if($rosterGroups->isNotEmpty() && $activeGroup)
                                <button type="button" class="btn-custom btn-accent py-2 px-3 fw-bold uppercase small" onclick="openEditGroupModal('{{ addslashes($activeGroup) }}')">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Group
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                @if($isDeactivated)
                    <div class="alert alert-clinical border-secondary bg-secondary bg-opacity-10 text-secondary p-3 rounded-3 mb-4 small">
                        <i class="bi bi-info-circle me-1"></i> Member rosters are locked because this organization is currently deactivated.
                    </div>
                @endif

                {{-- Roster Groups Selector Tabs --}}
                @if($rosterGroups->isNotEmpty())
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom flex-wrap gap-2" style="border-color: var(--border-color) !important;">
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach($rosterGroups as $group)
                                <a href="{{ route('organization.index', ['view' => 'patient', 'org_id' => $currentOrg->id, 'group' => $group]) }}#tab-rosters" 
                                   class="btn btn-sm {{ $activeGroup === $group ? 'btn-accent fw-bold shadow-sm' : 'btn-outline-secondary' }} px-3.5 py-2 uppercase tracking-wider" 
                                   style="font-size: 0.75rem; border-radius: 8px;">
                                    <i class="bi bi-people-fill me-1.5"></i> {{ $group }}
                                </a>
                            @endforeach
                        </div>
                        @if($activeGroup && !$isDeactivated)
                            <button type="button" class="btn btn-sm btn-outline-danger py-1.5 px-3 fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.7rem; border-radius: 8px;" onclick="openDeleteRosterGroupModal('{{ addslashes($activeGroup) }}', {{ $rosterMembers->count() }})">
                                <i class="bi bi-trash3"></i>
                                <span>Delete Group</span>
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Members Table: Rendered When A Group Is Clicked --}}
                @if($activeGroup)
                    <div class="table-responsive rounded border bg-card" style="border-color: var(--border-color) !important;">
                        <div class="p-3 bg-brand-dark border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                            <span class="fw-bold text-white small uppercase">
                                <i class="bi bi-person-lines-fill text-accent me-1.5"></i> Cohort Members &bull; {{ $activeGroup }} ({{ $rosterMembers->count() }})
                            </span>
                            @if(!$isDeactivated)
                                <button type="button" class="btn btn-sm btn-accent py-1 px-3 fw-bold uppercase d-inline-flex align-items-center gap-1" style="font-size: 0.7rem; border-radius: 6px;" onclick="openAddRosterMemberModal('{{ addslashes($activeGroup) }}', {{ $currentOrg->id }})">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Add Member</span>
                                </button>
                            @endif
                        </div>
                        <table class="table table-hover align-middle mb-0" style="color: var(--text-main); min-width: 860px;">
                            <thead class="bg-black text-secondary uppercase small" style="font-size: 0.72rem; letter-spacing: 0.5px; background-color: var(--brand-dark) !important;">
                                <tr>
                                    <th class="ps-4 py-3" style="width: 28%;">Participant Name</th>
                                    <th>Age & Sex</th>
                                    <th>Contact Info</th>
                                    <th>Residential Address</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rosterMembers as $member)
                                    @php
                                        $isMale = ($member->sex === 'Male');
                                    @endphp
                                    <tr class="border-secondary border-opacity-10 align-middle">
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold uppercase text-main small fs-6">{{ $member->name }}</div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="badge {{ $isMale ? 'bg-info text-white' : 'bg-danger text-white' }} px-2 py-0.5 fw-bold uppercase" style="font-size: 0.65rem;">
                                                    {{ strtoupper($member->sex ?: 'N/A') }}
                                                </span>
                                                <span class="smaller fw-semibold text-secondary">{{ $member->age }} YRS</span>
                                            </div>
                                        </td>
                                        <td class="small">
                                            <div><i class="bi bi-telephone text-accent me-1"></i>{{ $member->phone }}</div>
                                            <div class="text-muted smaller"><i class="bi bi-envelope text-accent me-1"></i>{{ $member->email }}</div>
                                        </td>
                                        <td class="small text-secondary text-truncate" style="max-width: 260px;" title="{{ $member->address }}">
                                            {{ $member->address !== 'N/A' ? $member->address : 'Address on file' }}
                                        </td>
                                        <td class="text-end pe-4">
                                            @if(!$isDeactivated)
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" style="font-size: 0.72rem; border-radius: 6px;" onclick='editRosterMember(@json($member))'>
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold" style="font-size: 0.72rem; border-radius: 6px;" onclick="openDeleteRosterMemberModal({{ $member->id }}, '{{ addslashes($member->name) }}')">
                                                        Delete
                                                    </button>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary text-white px-2 py-0.5 smaller">Locked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted small italic">
                                            <i class="bi bi-people fs-2 d-block mb-2 opacity-50"></i>
                                            <div>No members recorded in group "{{ $activeGroup }}".</div>
                                            @if(!$isDeactivated)
                                                <button type="button" class="btn btn-sm btn-outline-accent mt-3 px-3 py-1.5 fw-bold uppercase" style="font-size: 0.75rem; border-radius: 6px;" onclick="openAddRosterMemberModal('{{ addslashes($activeGroup) }}', {{ $currentOrg->id }})">
                                                    <i class="bi bi-plus-lg me-1"></i> Add First Member
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-5 rounded border bg-main text-center text-muted small" style="border-color: var(--border-color) !important;">
                        <i class="bi bi-folder2-open fs-2 d-block mb-2 text-accent opacity-75"></i>
                        @if($rosterGroups->isEmpty())
                            No rosters registered yet for {{ $currentOrg->name }}.
                            @if(!$isDeactivated)
                                Click <strong>New Group</strong> to create your first cohort category.
                            @endif
                        @else
                            Please select a roster category above to view and manage its participants.
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif