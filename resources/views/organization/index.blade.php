@extends('layouts.app')
@section('title', $isStaffView ? 'Organization Management Console' : 'Organization Hub')

@section('content')
<div class="container-fluid text-start animate-page py-4 px-3 px-sm-4 px-xl-5" id="org-hub-root">

    {{-- =========================================================================
         1. TOP HEADER (CLEAN & SPACIOUS)
         ========================================================================= --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
        <div class="me-sm-3 flex-grow-1">
            <h2 class="text-main fw-bold mb-1 uppercase tracking-tight fs-3 fs-md-2" style="letter-spacing: 1px;">
                {{ $isStaffView ? 'Organization Management Console' : 'Organization Hub' }}
            </h2>
            <p class="text-secondary small mb-0">
                @if($isStaffView)
                    Review partner applications, evaluate custom package proposals, and inspect recognized organization rosters.
                @else
                    Manage your institutional profile, propose tailored diagnostic packages, and maintain reusable participant rosters.
                @endif
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap flex-shrink-0">
            @if($isEmployee)
                @if($isStaffView)
                    {{-- Staff Switch to Personal Patient Testing Mode --}}
                    <a href="{{ route('organization.index', ['view' => 'patient']) }}" class="btn btn-outline-accent btn-sm fw-bold uppercase px-3 py-2 text-decoration-none d-inline-flex align-items-center gap-1" style="border-radius: 8px; font-size: 0.75rem;">
                        <i class="bi bi-person-badge"></i>
                        <span>Switch to Patient View</span>
                    </a>
                    <a href="{{ $user->isAdmin() ? route('admin.panel') : route('staff.panel') }}" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2 text-decoration-none d-inline-flex align-items-center gap-1" style="border-radius: 8px; font-size: 0.75rem;">
                        <i class="bi bi-arrow-left"></i>
                        <span>Back to Panel</span>
                    </a>
                @else
                    {{-- Switch Back to Administrative Management Console --}}
                    <a href="{{ route('organization.index', ['view' => 'staff']) }}" class="btn btn-accent btn-sm fw-bold uppercase px-3 py-2 text-decoration-none d-inline-flex align-items-center gap-1 shadow-sm" style="border-radius: 8px; font-size: 0.75rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Staff Management Console</span>
                    </a>
                @endif
            @else
                @if($user->isRecognizedOrg())
                    <a href="{{ route('appointments.bulk') }}" class="btn-custom btn-accent py-2.5 px-4 d-inline-flex align-items-center gap-2 text-decoration-none shadow-sm fw-bold uppercase" style="border-radius: 8px; font-size: 0.78rem;">
                        <i class="bi bi-calendar-plus-fill"></i>
                        <span>Launch Bulk Booking</span>
                    </a>
                @endif
                <a href="{{ route('main') }}" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2 text-decoration-none d-inline-flex align-items-center gap-1" style="border-radius: 8px;">
                    <i class="bi bi-arrow-left"></i>
                    <span>Main Menu</span>
                </a>
            @endif
        </div>
    </div>

    {{-- =========================================================================
         2. CORE WORKSPACE ROUTING (STAFF CONSOLE VS PATIENT SELF-SERVICE)
         ========================================================================= --}}
    @if($isStaffView)
        @include('organization.partials.staff-console')
    @else
        @include('organization.partials.patient-hub')
    @endif

</div>

{{-- =========================================================================
     3. MODAL DIALOGS (PROPOSALS, ROSTERS, APPROVALS & REJECTIONS)
     ========================================================================= --}}
@include('organization.partials.modals')

<style>
.cursor-pointer { cursor: pointer; }
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

{{-- =========================================================================
     4. JAVASCRIPT CONTROLLERS (PSGC, PRICING, SEARCH & PHONE NORMALIZER)
     ========================================================================= --}}
@include('organization.partials.scripts')
@endsection