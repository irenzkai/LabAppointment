<!-- resources/views/organization/partials/staff-console.blade.php -->

{{-- =========================================================================
     1. APPROVAL AREAS (APPLICATIONS & PACKAGE PROPOSALS)
     ========================================================================= --}}
<div class="row g-4 mb-4">
    {{-- 1. Pending Organization Applications --}}
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-warning bg-card shadow-sm h-100 rounded-3 text-start" style="border-left: 4px solid #ffc107 !important;">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: var(--border-color) !important;">
                <h6 class="text-warning fw-bold uppercase mb-0 tracking-wider small">
                    <i class="bi bi-building-check me-1.5"></i> Pending Organization Applications
                </h6>
                <span class="badge bg-warning text-dark px-2.5 py-1 fs-x-small fw-bold">
                    {{ $pendingApplications->count() }} PENDING
                </span>
            </div>

            <div class="custom-scroll" style="max-height: 290px; overflow-y: auto; overflow-x: hidden;">
                @forelse($pendingApplications as $appUser)
                    <div class="p-3.5 rounded border bg-main mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3" style="border-color: var(--border-color) !important;">
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <span class="fw-bold text-main small text-truncate uppercase fs-6">{{ $appUser->name }}</span>
                                @if($appUser->hasPendingNameChange())
                                    <span class="badge bg-warning text-dark uppercase px-2 py-0.5 fs-x-small fw-bold" title="Clinic staff review in progress">
                                        <i class="bi bi-hourglass-split me-1"></i> Name Change: "{{ $appUser->pending_name }}"
                                    </span>
                                @elseif($appUser->status === 'pending_reactivation')
                                    <span class="badge bg-info text-white uppercase px-2 py-0.5 fs-x-small fw-bold">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivation Request
                                    </span>
                                @endif
                            </div>

                            <div class="text-muted smaller mt-1">
                                Rep: <strong class="text-main">{{ $appUser->representative_name ?: ($appUser->user?->name ?? 'N/A') }}</strong> &bull; 
                                Phone: <strong class="text-main font-monospace">{{ $appUser->representative_phone ?: ($appUser->user?->phone ?? 'N/A') }}</strong>
                            </div>
                            <div class="text-secondary smaller text-truncate">{{ $appUser->user?->email ?? 'N/A' }}</div>
                            <small class="text-muted text-opacity-75 fs-x-small d-block mt-1">Submitted {{ $appUser->updated_at ? $appUser->updated_at->diffForHumans() : '' }}</small>
                        </div>

                        <div class="d-flex gap-2 flex-shrink-0 justify-content-end align-self-end align-self-sm-start mt-2 mt-sm-0">
                            @if($appUser->hasPendingNameChange())
                                <form method="POST" action="{{ route('organization.name.approve', $appUser->id) }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-accent py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem;">
                                        Approve Name
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem;" onclick="openDeclineNameChangeModal({{ $appUser->id }}, '{{ addslashes($appUser->name) }}')">
                                    Decline
                                </button>
                            @else
                                <form method="POST" action="{{ route('organization.approve', $appUser->id) }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-accent py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem;">
                                        Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1.5 px-3 fw-bold uppercase" style="font-size: 0.72rem;" onclick="openDeclineOrgModal({{ $appUser->id }}, '{{ addslashes($appUser->name) }}')">
                                    Decline
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted small italic">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success opacity-75"></i>
                        All organization applications have been reviewed.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 2. Pending Custom Package Proposals --}}
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-warning bg-card shadow-sm h-100 rounded-3 text-start" style="border-left: 4px solid #ffc107 !important;">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: var(--border-color) !important;">
                <h6 class="text-warning fw-bold uppercase mb-0 tracking-wider small">
                    <i class="bi bi-box-seam me-1.5"></i> Pending Package Proposals
                </h6>
                <span class="badge bg-warning text-dark px-2.5 py-1 fs-x-small fw-bold">
                    {{ $pendingPackages->count() }} PENDING
                </span>
            </div>

            <div class="custom-scroll" style="max-height: 290px; overflow-y: auto; overflow-x: hidden;">
                @forelse($pendingPackages as $pkg)
                    @php
                        $basePrice = $pkg->services->sum('price');
                    @endphp
                    <div class="p-3.5 rounded border bg-main mb-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <div class="fw-bold text-main small text-truncate uppercase fs-6" title="{{ $pkg->name }}">{{ $pkg->name }}</div>
                            <span class="badge bg-secondary text-white px-2 py-1 smaller text-nowrap flex-shrink-0">
                                Base Value: ₱{{ number_format($basePrice, 2) }}
                            </span>
                        </div>
                        <div class="text-muted smaller">
                            Entity: <strong class="text-main">{{ $pkg->organization?->name ?? 'Organization' }}</strong>
                        </div>
                        <div class="text-secondary smaller text-truncate mt-0.5" title="{{ $pkg->services->pluck('name')->implode(', ') }}">
                            Tests ({{ $pkg->services->count() }}): {{ $pkg->services->pluck('name')->implode(', ') }}
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 pt-2.5 border-top" style="border-color: var(--border-color) !important;">
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold uppercase text-nowrap" style="font-size: 0.72rem;" onclick="openDeclinePkgModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}')">
                                Decline
                            </button>
                            <button type="button" class="btn btn-sm btn-accent py-1 px-3 fw-bold uppercase text-nowrap" style="font-size: 0.72rem;" onclick="openApprovePkgModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}', '{{ addslashes($pkg->organization?->name ?? '') }}', {{ $basePrice }})">
                                Set Price & Approve
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted small italic">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success opacity-75"></i>
                        All custom package proposals have been reviewed.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- =========================================================================
     2. DIRECTORY OF APPROVED RECOGNIZED ORGANIZATIONS
     ========================================================================= --}}
<div class="card p-0 border bg-card shadow-sm rounded-3 mb-4 text-start overflow-hidden" style="border-color: var(--border-color) !important;">
    <div class="p-4 bg-brand-dark border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-color: var(--border-color) !important;">
        <div>
            <h6 class="text-white fw-bold mb-1 uppercase small tracking-wider">
                <i class="bi bi-buildings me-2 text-accent"></i> Approved Recognized Organizations Directory ({{ $recognizedOrgs->count() }})
            </h6>
            <small class="text-secondary">Select an organization below to audit and inspect their custom packages and participant rosters.</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="color: var(--text-main); min-width: 860px;">
            <thead class="small uppercase bg-black text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                <tr>
                    <th class="ps-4 py-3">Organization Entity</th>
                    <th>Authorized Representative</th>
                    <th>Contact Info</th>
                    <th class="text-center">Custom Packages</th>
                    <th class="text-center">Roster Members</th>
                    <th class="pe-4 text-end">Management Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recognizedOrgs as $org)
                    @php
                        $isSelected = ($selectedOrg && $selectedOrg->id === $org->id);
                    @endphp
                    <tr class="border-secondary border-opacity-10 {{ $isSelected ? 'bg-accent bg-opacity-10' : '' }}">
                        <td class="ps-4 py-3.5">
                            <div class="fw-bold uppercase text-main fs-6">{{ $org->name }}</div>
                            <small class="text-muted fs-x-small">ID #{{ $org->id }} &bull; Partner since {{ $org->updated_at ? $org->updated_at->format('M Y') : 'N/A' }}</small>
                        </td>
                        <td>
                            <div class="small fw-semibold text-main">{{ $org->representative_name ?: ($org->user?->name ?? 'N/A') }}</div>
                        </td>
                        <td class="small">
                            <div><i class="bi bi-telephone text-accent me-1"></i>{{ $org->representative_phone ?: ($org->user?->phone ?? 'N/A') }}</div>
                            <div class="text-muted smaller"><i class="bi bi-envelope text-accent me-1"></i>{{ $org->user?->email ?? 'N/A' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary text-white px-2.5 py-1.5 small fw-bold">
                                {{ $org->packages_count ?? ($org->packages ? $org->packages->count() : 0) }} Packages
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary text-white px-2.5 py-1.5 small fw-bold">
                                {{ $org->members_count ?? ($org->members ? $org->members->count() : 0) }} Members
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('organization.index', ['view' => 'staff', 'org_id' => $org->id]) }}#inspected-org-section" class="btn btn-sm {{ $isSelected ? 'btn-accent' : 'btn-outline-accent' }} fw-bold uppercase py-1.5 px-3" style="font-size: 0.72rem;">
                                    <i class="bi bi-search me-1"></i> {{ $isSelected ? 'Inspecting' : 'Inspect' }}
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold uppercase py-1.5 px-3" style="font-size: 0.72rem;" onclick="openDisableOrgModal({{ $org->id }}, '{{ addslashes($org->name) }}')">
                                    <i class="bi bi-slash-circle me-1"></i> Disable
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted small italic">
                            No approved recognized organizations on file.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- =========================================================================
     3. INSPECTED ORGANIZATION DETAILS (PACKAGES & ROSTERS READ-ONLY AUDIT)
     ========================================================================= --}}
@if($selectedOrg)
    @php
        $isStaffRosterActive = !empty($activeGroup) || request('tab') === 'rosters';
    @endphp
    <div id="inspected-org-section" class="card p-4 p-md-5 border bg-card shadow-sm rounded-3 mb-5 text-start" style="border-color: var(--border-color) !important;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
            <div>
                <span class="badge bg-secondary text-white uppercase px-2.5 py-1 fs-x-small fw-bold mb-1.5 d-inline-block">
                    ORGANIZATION AUDIT FILE
                </span>
                <h4 class="fw-bold text-main mb-1 uppercase tracking-tight fs-3">{{ $selectedOrg->name }}</h4>
                <div class="small text-muted">
                    Authorized Representative: <strong class="text-main">{{ $selectedOrg->representative_name ?: ($selectedOrg->user?->name ?? 'N/A') }}</strong> &bull;
                    Contact: <strong class="text-main font-monospace">{{ $selectedOrg->representative_phone ?: ($selectedOrg->user?->phone ?? 'N/A') }}</strong> &bull;
                    Account: <strong class="text-main">{{ $selectedOrg->user?->email ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>

        {{-- Inspection Sub-Tabs --}}
        <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="staffInspectTabs" style="border-color: var(--border-color) !important;">
            <li class="nav-item">
                <button class="nav-link {{ !$isStaffRosterActive ? 'active' : '' }} fw-bold px-4 py-2 uppercase btn-sm" id="insp-packages-btn" data-bs-toggle="pill" data-bs-target="#insp-packages">
                    <i class="bi bi-box-seam me-1.5"></i> Custom Packages ({{ $selectedOrgPackages->count() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $isStaffRosterActive ? 'active' : '' }} fw-bold px-4 py-2 uppercase btn-sm" id="insp-rosters-btn" data-bs-toggle="pill" data-bs-target="#insp-rosters">
                    <i class="bi bi-people-fill me-1.5"></i> Participant Rosters ({{ $selectedOrgRosterGroups->count() }} Groups)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="staffInspectTabContent">
            {{-- 1. Inspected Organization Custom Packages (Differentiating Declined vs Disabled vs Archived) --}}
            <div class="tab-pane fade {{ !$isStaffRosterActive ? 'show active' : '' }}" id="insp-packages">
                <div class="row g-3">
                    @forelse($selectedOrgPackages as $pkg)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card p-4 border h-100 rounded-3 bg-main d-flex flex-column justify-content-between" style="border-color: var(--border-color) !important;">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <h6 class="fw-bold text-main mb-0 uppercase text-truncate fs-6" title="{{ $pkg->name }}">{{ $pkg->name }}</h6>
                                        
                                        @if($pkg->isDeclined())
                                            <span class="fs-5 fw-bold text-danger text-nowrap">Declined</span>
                                        @elseif($pkg->is_available && $pkg->price > 0)
                                            <span class="fs-5 fw-bold text-accent text-nowrap">₱{{ number_format($pkg->price, 2) }}</span>
                                        @else
                                            <span class="fs-5 fw-bold text-accent text-nowrap">Under Review</span>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                                        @if($pkg->trashed())
                                            {{-- Soft deleted package: Archived after disable --}}
                                            <span class="badge bg-secondary text-white uppercase fs-x-small fw-bold">ARCHIVED</span>
                                        @elseif($pkg->isDeclined())
                                            {{-- Declined package proposal --}}
                                            <span class="badge bg-danger text-white uppercase fs-x-small fw-bold">DECLINED</span>
                                        @elseif($pkg->is_available)
                                            {{-- Active and clinically approved --}}
                                            <span class="badge bg-success text-white uppercase fs-x-small fw-bold">ACTIVE</span>
                                        @elseif($pkg->isDisabled())
                                            {{-- Explicitly disabled by staff --}}
                                            <span class="badge bg-secondary text-white uppercase fs-x-small fw-bold">DISABLED</span>
                                        @else
                                            {{-- Pending evaluation --}}
                                            <span class="badge bg-warning text-dark uppercase fs-x-small fw-bold">PENDING REVIEW</span>
                                        @endif

                                        <span class="badge bg-secondary text-white uppercase fs-x-small">
                                            {{ strtoupper($pkg->gender_restriction) }}
                                        </span>

                                        @if($pkg->is_available && $pkg->estimated_time)
                                            <span class="smaller text-muted">{{ $pkg->estimated_time }} mins</span>
                                        @endif
                                    </div>

                                    {{-- Rejection Justification if Proposal was Declined --}}
                                    @if($pkg->isDeclined() && $pkg->status_reason)
                                        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-2.5 rounded-3 mb-3 smaller text-start">
                                            <i class="bi bi-exclamation-octagon-fill me-1"></i>
                                            <strong>Decline Reason:</strong> {{ $pkg->status_reason }}
                                        </div>
                                    @endif

                                    {{-- Deactivation Justification if Package was Disabled --}}
                                    @if($pkg->isDisabled() && $pkg->status_reason)
                                        <div class="alert alert-clinical border-secondary bg-secondary bg-opacity-10 text-secondary p-2.5 rounded-3 mb-3 smaller text-start">
                                            <i class="bi bi-info-circle me-1"></i>
                                            <strong>Disable Reason:</strong> {{ $pkg->status_reason }}
                                        </div>
                                    @endif

                                    @if($pkg->description)
                                        <p class="text-secondary smaller mb-3 text-truncate-2">{{ $pkg->description }}</p>
                                    @endif

                                    <div class="p-3 rounded bg-card border smaller mb-3" style="border-color: var(--border-color) !important;">
                                        <span class="text-muted fw-bold uppercase d-block mb-1 fs-x-small">Bundled Examinations:</span>
                                        <span class="text-main fw-semibold">{{ $pkg->services->pluck('name')->implode(', ') ?: 'None' }}</span>
                                    </div>
                                </div>

                                {{-- Action Controls: Differentiating Active vs Disabled vs Declined vs Archived --}}
                                @if($pkg->trashed())
                                    {{-- Archived package can be restored by staff --}}
                                    <div class="pt-2 border-top text-end" style="border-color: var(--border-color) !important;">
                                        <form method="POST" action="{{ route('organization.package.restore', $pkg->id) }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold uppercase" style="font-size: 0.7rem;">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Package
                                            </button>
                                        </form>
                                    </div>
                                @elseif($pkg->is_available)
                                    {{-- Active package can be disabled with a reason --}}
                                    <div class="pt-2 border-top text-end" style="border-color: var(--border-color) !important;">
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 fw-bold uppercase" style="font-size: 0.7rem;" onclick="openDisablePkgModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}')">
                                            Disable Package
                                        </button>
                                    </div>
                                @elseif($pkg->isDisabled())
                                    {{-- Disabled package can be archived or re-approved --}}
                                    <div class="pt-2 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                                        @php $baseVal = $pkg->services->sum('price'); @endphp
                                        <button type="button" class="btn btn-sm btn-accent py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;" onclick="openApprovePkgModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}', '{{ addslashes($pkg->organization?->name ?? '') }}', {{ $baseVal }}, {{ $pkg->estimated_time ?: 15 }}, '{{ addslashes($pkg->preparation ?? '') }}')">
                                            Re-Approve
                                        </button>
                                        <form method="POST" action="{{ route('organization.package.destroy', $pkg->id) }}" onsubmit="return confirm('Archive this disabled package?')" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;">
                                                Archive
                                            </button>
                                        </form>
                                    </div>
                                @elseif($pkg->isPending())
                                    {{-- Pending proposal can be approved or declined --}}
                                    <div class="pt-2 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                                        @php $baseVal = $pkg->services->sum('price'); @endphp
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;" onclick="openDeclinePkgModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}')">
                                            Decline
                                        </button>
                                        <button type="button" class="btn btn-sm btn-accent py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;" onclick="openApprovePkgModal({{ $pkg->id }}, '{{ addslashes($pkg->name) }}', '{{ addslashes($pkg->organization?->name ?? '') }}', {{ $baseVal }})">
                                            Set Price & Approve
                                        </button>
                                    </div>
                                @elseif($pkg->isDeclined())
                                    {{-- Declined proposal can be archived if needed --}}
                                    <div class="pt-2 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important;">
                                        <form method="POST" action="{{ route('organization.package.destroy', $pkg->id) }}" onsubmit="return confirm('Archive this declined package proposal?')" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold uppercase" style="font-size: 0.7rem;">
                                                Archive
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted small italic">
                            No custom diagnostic packages proposed by this organization.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 2. Inspected Organization Rosters --}}
            <div class="tab-pane fade {{ $isStaffRosterActive ? 'show active' : '' }}" id="insp-rosters">
                <div class="mb-4">
                    <span class="text-secondary small fw-bold uppercase d-block mb-2">Available Roster Categories:</span>
                    @if($selectedOrgRosterGroups->isNotEmpty())
                        <div class="d-flex gap-2 flex-wrap pb-2 border-bottom" style="border-color: var(--border-color) !important;">
                            @foreach($selectedOrgRosterGroups as $grp)
                                <a href="{{ route('organization.index', ['view' => 'staff', 'org_id' => $selectedOrg->id, 'group' => $grp]) }}#insp-rosters" 
                                   class="btn btn-sm {{ $activeGroup === $grp ? 'btn-accent fw-bold shadow-sm' : 'btn-outline-secondary' }} px-3.5 py-2 uppercase tracking-wider" 
                                   style="font-size: 0.75rem; border-radius: 8px;">
                                    <i class="bi bi-people-fill me-1.5"></i> {{ $grp }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded border bg-main text-center text-muted small italic" style="border-color: var(--border-color) !important;">
                            No roster groups registered by this organization yet.
                        </div>
                    @endif
                </div>

                {{-- Table strictly rendered only when an active group is clicked --}}
                @if($activeGroup)
                    <div class="table-responsive rounded border bg-card" style="border-color: var(--border-color) !important;">
                        <div class="p-3 bg-brand-dark border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                            <span class="fw-bold text-white small uppercase">
                                <i class="bi bi-person-lines-fill text-accent me-1.5"></i> Cohort Members &bull; {{ $activeGroup }} ({{ $selectedOrgRosterMembers->count() }})
                            </span>
                        </div>
                        <table class="table table-hover align-middle mb-0" style="color: var(--text-main); min-width: 820px;">
                            <thead class="small uppercase bg-black text-secondary" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-4 py-3">Participant Name</th>
                                    <th>Age / Sex</th>
                                    <th>Contact Phone & Email</th>
                                    <th class="pe-4">Residential Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($selectedOrgRosterMembers as $member)
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
                                        <td class="pe-4 small text-secondary text-truncate" style="max-width: 280px;" title="{{ $member->address }}">
                                            {{ $member->address !== 'N/A' ? $member->address : 'Address on file' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted small italic">
                                            No participants registered in roster group "{{ $activeGroup }}".
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    @if($selectedOrgRosterGroups->isNotEmpty())
                        <div class="p-5 rounded border bg-main text-center text-muted small" style="border-color: var(--border-color) !important;">
                            <i class="bi bi-cursor-fill fs-2 d-block mb-2 text-accent opacity-75"></i>
                            Please select a roster category above to view its participant records.
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endif