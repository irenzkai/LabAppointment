@extends('layouts.app')

@section('title', 'Settings')

@push('styles')
<style>
    /* Scope sidebar refinements specifically to the settings view */
    .settings-sidebar-container {
        border-color: var(--border-color) !important;
        background-color: var(--bg-card) !important;
        border-radius: 16px !important;
        overflow: hidden;
    }

    .settings-sidebar .list-group-item {
        color: var(--text-muted) !important;
        background-color: transparent !important;
        border: 1px solid transparent !important;
        border-radius: 8px !important;
        padding: 12px 18px !important;
        font-weight: 600 !important;
        transition: all 0.25s ease-in-out !important;
        cursor: pointer;
    }

    .settings-sidebar .list-group-item:hover {
        color: var(--brand-accent) !important;
        background-color: rgba(25, 211, 140, 0.05) !important;
        border-color: rgba(25, 211, 140, 0.1) !important;
    }

    .settings-sidebar .list-group-item.active {
        color: #1c232d !important;
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 12px rgba(25, 211, 140, 0.2) !important;
    }

    /* Elegant stylistic distinction for destructive panel buttons */
    .settings-sidebar .list-group-item.text-danger:hover {
        color: #ff4d4d !important;
        background-color: rgba(255, 77, 77, 0.05) !important;
        border-color: rgba(255, 77, 77, 0.15) !important;
    }

    .settings-sidebar .list-group-item.text-danger.active {
        color: #ffffff !important;
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2) !important;
    }

    /* Sub-card consistency across active form sections */
    .settings-tab-content-wrapper {
        animation: fadeIn 0.35s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="container text-start animate-page" id="settings-page-wrapper">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-5 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo" style="height: 50px; width: 50px; border-radius: 50%; border: 2px solid var(--brand-accent);">
        <h2 class="text-accent fw-bold mb-0 uppercase" style="letter-spacing: 2px;">ACCOUNT SETTINGS</h2>
    </div>

    {{-- Split Pane Settings Layout --}}
    <div class="row g-4">

        {{-- LEFT PANEL: Selection Sidebar --}}
        <div class="col-lg-4 col-xl-3">
            <div class="card p-3 settings-sidebar-container shadow-lg sticky-top" style="top: 100px;">
                <h6 class="text-muted fw-800 uppercase tracking-widest mb-3 fs-x-small px-2" style="font-size: 0.7rem; letter-spacing: 1px;">Settings Panel</h6>
                <div class="list-group list-group-flush settings-sidebar gap-1.5" id="settingsTabs" role="tablist">
                    <button class="list-group-item active d-flex align-items-center gap-2 text-start" id="btn-personal" data-bs-toggle="pill" data-bs-target="#tab-personal" role="tab" aria-controls="tab-personal" aria-selected="true">
                        <i class="bi bi-person-circle fs-5"></i> Personal Info
                    </button>
                    <button class="list-group-item d-flex align-items-center gap-2 text-start" id="btn-password" data-bs-toggle="pill" data-bs-target="#tab-password" role="tab" aria-controls="tab-password" aria-selected="false">
                        <i class="bi bi-shield-lock fs-5"></i> Update Password
                    </button>

                    {{-- Only display family dependents tab button if email is verified --}}
                    @if($user->hasVerifiedEmail())
                        <button class="list-group-item d-flex align-items-center gap-2 text-start" id="btn-dependents" data-bs-toggle="pill" data-bs-target="#tab-dependents" role="tab" aria-controls="tab-dependents" aria-selected="false">
                            <i class="bi bi-people fs-5"></i> Family Dependents
                        </button>
                    @endif

                    <button class="list-group-item d-flex align-items-center gap-2 text-start text-danger" id="btn-danger" data-bs-toggle="pill" data-bs-target="#tab-danger" role="tab" aria-controls="tab-danger" aria-selected="false">
                        <i class="bi bi-exclamation-triangle fs-5"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: Active Setting Form Content (Includes Partials) --}}
        <div class="col-lg-8 col-xl-9">
            <div class="tab-content settings-tab-content-wrapper" id="settingsContent">
                {{-- Tab 1: Personal Info --}}
                <div class="tab-pane fade show active" id="tab-personal" role="tabpanel" aria-labelledby="btn-personal">
                    @include('profile.partials.update-profile-information-form')
                </div>

                {{-- Tab 2: Security --}}
                <div class="tab-pane fade" id="tab-password" role="tabpanel" aria-labelledby="btn-password">
                    @include('profile.partials.update-password-form')
                </div>

                {{-- Tab 3: Dependents - Only load if verified --}}
                @if($user->hasVerifiedEmail())
                    <div class="tab-pane fade" id="tab-dependents" role="tabpanel" aria-labelledby="btn-dependents">
                        @include('profile.partials.manage-dependents-form')
                    </div>
                @endif

                {{-- Tab 4: Danger Zone --}}
                <div class="tab-pane fade" id="tab-danger" role="tabpanel" aria-labelledby="btn-danger">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Clear session storage if entering from a completely different page (fresh access)
        if (document.referrer && !document.referrer.includes('/profile')) {
            sessionStorage.removeItem('active_profile_tab');
        }

        // 1. Determine the target tab based on priority
        let targetTabId = null;

        // Priority A: Server-side indicators (errors or success flashes)
        @if($errors->updatePassword->any() || session('status') === 'password-updated')
            targetTabId = 'tab-password';
        @elseif($errors->userDeletion->any())
            targetTabId = 'tab-danger';
        @elseif(session('success') && str_contains(session('success'), 'Dependent'))
            targetTabId = 'tab-dependents';
        @endif

        // Priority B: URL hash (deep linking)
        if (!targetTabId && window.location.hash) {
            targetTabId = window.location.hash.replace('#', '');
        }

        // Priority C: Session Storage (persists manual reloads within the same session)
        if (!targetTabId) {
            targetTabId = sessionStorage.getItem('active_profile_tab');
        }

        // Fallback: Default to personal tab if still not resolved
        if (!targetTabId) {
            targetTabId = 'tab-personal';
        }

        // Ensure the resolved tab button exists in the DOM and activate it
        const tabButton = document.querySelector(`[data-bs-target="#${targetTabId}"]`);
        if (tabButton) {
            const tabInstance = bootstrap.Tab.getInstance(tabButton) || new bootstrap.Tab(tabButton);
            tabInstance.show();
        }

        // 2. Track tab changes dynamically to persist selection across page refreshes
        const tabTriggerList = document.querySelectorAll('#settingsTabs button');
        tabTriggerList.forEach(tabTriggerEl => {
            tabTriggerEl.addEventListener('shown.bs.tab', event => {
                const activeTabId = event.target.getAttribute('data-bs-target').replace('#', '');
                // Save to Session Storage
                sessionStorage.setItem('active_profile_tab', activeTabId);
                // Update URL Hash without triggering scroll jumps
                history.replaceState(null, null, '#' + activeTabId);
            });
        });
    });
</script>
@endpush