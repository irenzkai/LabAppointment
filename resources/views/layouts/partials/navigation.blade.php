@php
    $notifCount = auth()->check() ? auth()->user()->unreadNotifications->count() : 0;
    $recentNotifications = auth()->check() ? auth()->user()->notifications()->take(6)->get() : collect();
@endphp

<!-- =========================================================================
     TOP MAIN NAVBAR (CENTERED CONTAINER)
========================================================================= -->
<nav class="navbar navbar-expand-lg sticky-top shadow-sm main-top-navbar" id="mainMedscreenNavbar" style="z-index: 1050 !important;">
    <div class="container">
        <!-- 1. BRANDING & LOGO -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ Auth::check() ? route('main') : url('/') }}">
            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 40px; width: 40px; border-radius: 50%; border: 2px solid var(--brand-accent); object-fit: cover;">
            <span class="text-white uppercase fw-800 tracking-tight" style="font-size: 1.25rem;">
                MED<span class="text-accent" style="color: var(--brand-accent) !important;">SCREEN</span>
            </span>
        </a>

        <!-- 2. DESKTOP NAVIGATION LINKS (SCREENS >= 992px) -->
        <div class="collapse navbar-collapse d-none d-lg-flex align-items-center flex-grow-1 justify-content-between ms-4" id="desktopNavMenu">
            <ul class="navbar-nav align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}" href="{{ route('services.index') }}">
                        SERVICES
                    </a>
                </li>

                @auth
                    @if(Auth::user()->isEmployee())
                        {{-- Patient Side: Personal Appointments for Staff/Admin --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('appointments.*') && request('view') === 'patient' ? 'active' : '' }}" href="{{ route('appointments.index', ['view' => 'patient']) }}">
                                MY APPOINTMENTS
                            </a>
                        </li>

                        {{-- Admin/Staff Side: Master Clinical Queue --}}
                        <li class="nav-item">
                            <a class="nav-link {{ (request()->routeIs('appointments.index') && (request('view') === 'queue' || (!request('view') && !request()->routeIs('appointments.create')))) || request()->routeIs('appointments.encode') || request()->routeIs('appointments.edit-details') ? 'active' : '' }}" href="{{ route('appointments.index', ['view' => 'queue']) }}">
                                MASTER QUEUE
                            </a>
                        </li>
                    @else
                        {{-- Patient Side: Standard Appointments for Patients --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                                APPOINTMENTS
                            </a>
                        </li>
                    @endif

                    {{-- Admin Dropdown --}}
                    @if(Auth::user()->isAdmin())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') || request()->routeIs('services.manage') ? 'active' : '' }}" href="#" id="adminMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ADMIN PANEL
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-secondary mt-2" aria-labelledby="adminMenuDropdown">
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.panel') ? 'active' : '' }}" href="{{ route('admin.panel') }}">
                                        <i class="bi bi-speedometer2 me-2 text-accent"></i>Panel Overview
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                        <i class="bi bi-person-gear me-2 text-accent"></i>Manage Users
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('appointments.index') && request('view') === 'queue' ? 'active' : '' }}" href="{{ route('appointments.index', ['view' => 'queue']) }}">
                                        <i class="bi bi-kanban me-2 text-accent"></i>Master Queue
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('services.manage') ? 'active' : '' }}" href="{{ route('services.manage') }}">
                                        <i class="bi bi-list-stars me-2 text-accent"></i>Services List
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.payment-providers.*') ? 'active' : '' }}" href="{{ route('admin.payment-providers.index') }}">
                                        <i class="bi bi-qr-code-scan me-2 text-accent"></i>Payment QRs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.appointment-settings') ? 'active' : '' }}" href="{{ route('admin.appointment-settings') }}">
                                        <i class="bi bi-clock-history me-2 text-accent"></i>Schedule Editor
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.logs') ? 'active' : '' }}" href="{{ route('admin.logs') }}">
                                        <i class="bi bi-shield-check me-2 text-danger"></i>Audit Logs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                                        <i class="bi bi-file-earmark-bar-graph me-2 text-accent"></i>Reports & Exports
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @elseif(Auth::user()->isEmployee())
                        {{-- Staff Dropdown --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('staff.panel') || request()->routeIs('services.manage') || request()->routeIs('admin.payment-providers.*') || request()->routeIs('admin.appointment-settings') || request()->routeIs('admin.users.*') ? 'active' : '' }}" href="#" id="staffMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                STAFF PANEL
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-secondary mt-2" aria-labelledby="staffMenuDropdown">
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('staff.panel') ? 'active' : '' }}" href="{{ route('staff.panel') }}">
                                        <i class="bi bi-speedometer2 me-2 text-accent"></i>Panel Overview
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                        <i class="bi bi-people me-2 text-accent"></i>User List
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('appointments.index') && request('view') === 'queue' ? 'active' : '' }}" href="{{ route('appointments.index', ['view' => 'queue']) }}">
                                        <i class="bi bi-kanban me-2 text-accent"></i>Master Queue
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('services.manage') ? 'active' : '' }}" href="{{ route('services.manage') }}">
                                        <i class="bi bi-list-stars me-2 text-accent"></i>Services List
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.payment-providers.*') ? 'active' : '' }}" href="{{ route('admin.payment-providers.index') }}">
                                        <i class="bi bi-qr-code-scan me-2 text-accent"></i>Payment QRs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 {{ request()->routeIs('admin.appointment-settings') ? 'active' : '' }}" href="{{ route('admin.appointment-settings') }}">
                                        <i class="bi bi-clock-history me-2 text-accent"></i>Schedule Editor
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                @endauth
            </ul>

            <!-- Desktop Right Side Actions -->
            <div class="d-flex align-items-center gap-3">
                @guest
                    <a href="{{ route('login') }}" class="btn-custom btn-outline-accent d-inline-flex align-items-center gap-2 py-2 px-3">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>LOGIN</span>
                    </a>
                    <a href="{{ route('register') }}" class="btn-custom btn-accent d-inline-flex align-items-center gap-2 py-2 px-3">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>REGISTER</span>
                    </a>
                @else
                    {{-- DESKTOP NOTIFICATION BELL WITH DROPDOWN --}}
                    <div class="dropdown">
                        <button class="btn btn-link text-white position-relative p-2 text-decoration-none dropdown-toggle no-caret" 
                                id="desktopNotifDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                title="Notifications">
                            <i class="bi bi-bell-fill fs-5" style="color: var(--brand-accent) !important;"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white border border-dark nav-notif-badge {{ $notifCount > 0 ? '' : 'd-none' }}" style="font-size: 0.62rem; margin-top: 8px; margin-left: -10px;">
                                {{ $notifCount }}
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-secondary mt-2 p-0 notif-dropdown-menu" 
                             aria-labelledby="desktopNotifDropdown" 
                             style="width: 360px; max-width: 90vw; border-radius: 12px; overflow: hidden; background-color: var(--bg-card); border: 1.5px solid var(--border-color) !important;">
                            
                            {{-- Dropdown Header: Swapped to View All Navigation --}}
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="background-color: var(--brand-dark); border-color: var(--border-color) !important;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold small text-white uppercase tracking-wider" style="font-size: 0.75rem;">Notifications</span>
                                    @if($notifCount > 0)
                                        <span class="badge bg-danger rounded-pill smaller" style="font-size: 0.62rem;">{{ $notifCount }} new</span>
                                    @endif
                                </div>
                                <a href="{{ route('notifications.index') }}" class="text-accent smaller fw-bold text-decoration-none hover-accent d-inline-flex align-items-center gap-1" style="font-size: 0.72rem; color: var(--brand-accent) !important;">
                                    <span>View all</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>

                            {{-- Notification Items List --}}
                            <div class="custom-scroll" style="max-height: 360px; overflow-y: auto;">
                                @forelse($recentNotifications as $notif)
                                    @php
                                        $isUnread = is_null($notif->read_at);
                                        $type = $notif->data['type'] ?? 'info';
                                        $icon = match($type) {
                                            'success' => 'bi-check-circle-fill text-success',
                                            'danger' => 'bi-exclamation-octagon-fill text-danger',
                                            'warning' => 'bi-exclamation-triangle-fill text-warning',
                                            default => 'bi-info-circle-fill text-accent'
                                        };
                                    @endphp
                                    <a href="{{ route('notifications.markAsRead', $notif->id) }}" 
                                       class="d-flex gap-3 align-items-start p-3 border-bottom text-decoration-none notif-item {{ $isUnread ? 'bg-unread' : '' }}" 
                                       style="border-color: var(--border-color) !important;">
                                        <i class="bi {{ $icon }} fs-5 mt-0.5 flex-shrink-0" @if($type === 'info') style="color: var(--brand-accent) !important;" @endif></i>
                                        <div class="flex-grow-1 min-w-0 text-start">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold small text-truncate {{ $isUnread ? 'text-accent' : '' }}" style="font-size: 0.8rem; color: {{ $isUnread ? 'var(--brand-accent)' : 'var(--text-main)' }} !important;">
                                                    {{ $notif->data['title'] ?? 'Notification' }}
                                                </span>
                                                @if($isUnread)
                                                    <span class="rounded-pill p-1 ms-2" title="Unread" style="background-color: var(--brand-accent); width: 7px; height: 7px; display: inline-block;"></span>
                                                @endif
                                            </div>
                                            <p class="text-muted mb-1 text-truncate-2" style="font-size: 0.72rem; line-height: 1.35; color: var(--text-muted) !important;">
                                                {{ $notif->data['message'] ?? '' }}
                                            </p>
                                            <small class="text-muted text-opacity-75" style="font-size: 0.65rem;">
                                                {{ $notif->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-muted small" style="color: var(--text-muted) !important;">
                                        <i class="bi bi-bell-slash fs-3 d-block mb-2 opacity-50"></i>
                                        <span>No notifications right now</span>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Dropdown Footer: Swapped to Mark All Read Action Button --}}
                            <div class="p-2.5 text-center border-top" style="border-color: var(--border-color) !important; background-color: var(--brand-dark);">
                                @if($notifCount > 0)
                                    <a href="{{ route('notifications.clearAll') }}" class="text-accent small fw-bold text-decoration-none d-block hover-accent py-1" style="font-size: 0.75rem; color: var(--brand-accent) !important;">
                                        <i class="bi bi-check2-all me-1"></i> MARK ALL AS READ
                                    </a>
                                @else
                                    <span class="text-muted smaller d-block py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-check2 me-1"></i> All caught up
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- BOOK NOW CTA BUTTON --}}
                    <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent px-3 py-2 text-decoration-none">
                        <i class="bi bi-plus-lg me-1"></i> BOOK NOW
                    </a>

                    {{-- USER PROFILE DROPDOWN --}}
                    <div class="dropdown">
                        <button class="btn-custom btn-outline-accent py-2 dropdown-toggle d-inline-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span>{{ strtoupper(Auth::user()->first_name) }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-secondary mt-2">
                            @if(Auth::user()->isAdmin())
                                <li class="px-3 py-2 border-bottom border-secondary mb-1 bg-brand-dark bg-opacity-25">
                                    <div class="text-white-50 fs-x-small uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Role / Access</div>
                                    <div class="text-accent small fw-800" style="font-size: 0.8rem;">SYSTEM ADMINISTRATOR</div>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 text-accent fw-bold" href="{{ route('admin.panel') }}">
                                        <i class="bi bi-speedometer2 me-2"></i> Admin Panel
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
                            @elseif(Auth::user()->isEmployee())
                                <li class="px-3 py-2 border-bottom border-secondary mb-1 bg-brand-dark bg-opacity-25">
                                    <div class="text-white-50 fs-x-small uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Role / Access</div>
                                    <div class="text-accent small fw-800" style="font-size: 0.8rem;">{{ strtoupper(Auth::user()->role) }}</div>
                                </li>
                                <li>
                                    <a class="dropdown-item small py-2 text-accent fw-bold" href="{{ route('staff.panel') }}">
                                        <i class="bi bi-speedometer2 me-2"></i> Staff Panel
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
                            @endif
                            <li>
                                <a class="dropdown-item small py-2" href="{{ route('main') }}">
                                    <i class="bi bi-house me-2"></i> Main Menu
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item small py-2 {{ request()->routeIs('appointments.index') && request('view') === 'patient' ? 'active' : '' }}" href="{{ route('appointments.index', Auth::user()->isEmployee() ? ['view' => 'patient'] : []) }}">
                                    <i class="bi bi-calendar-check me-2"></i> My Appointments
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item small py-2" href="{{ route('patient.history') }}">
                                    <i class="bi bi-clock-history me-2"></i> Medical History
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item small py-2" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person-gear me-2"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item small py-2 text-danger fw-bold">
                                        <i class="bi bi-power me-2"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endguest
            </div>
        </div>

        <!-- 3. MOBILE & TABLET RIGHT-SIDE ACTIONS (SCREENS < 992px) -->
        <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
            @auth
                {{-- MOBILE NOTIFICATION BELL: OPENS RIGHT HALF-PANEL OFFCANVAS --}}
                <button class="btn btn-link position-relative p-2 text-decoration-none border-0 shadow-none" 
                        type="button" 
                        data-bs-toggle="offcanvas" 
                        data-bs-target="#mobileNotificationsOffcanvas" 
                        aria-controls="mobileNotificationsOffcanvas" 
                        title="Notifications">
                    <i class="bi bi-bell-fill fs-5" style="color: var(--brand-accent) !important;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white border border-dark nav-notif-badge {{ $notifCount > 0 ? '' : 'd-none' }}" style="font-size: 0.6rem; margin-top: 8px; margin-left: -10px;">
                        {{ $notifCount }}
                    </span>
                </button>
            @endauth

            <!-- Hamburger Button toggling Navigation Offcanvas -->
            <button class="navbar-toggler border-0 shadow-none p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavOffcanvas" aria-controls="mobileNavOffcanvas" aria-label="Toggle navigation">
                <i class="bi bi-list text-accent fs-1" style="color: var(--brand-accent) !important; line-height: 1;"></i>
            </button>
        </div>
    </div>
</nav>

<!-- =========================================================================
     4. MOBILE NOTIFICATIONS RIGHT HALF-PANEL (OFFCANVAS)
========================================================================= -->
<div class="offcanvas offcanvas-end custom-offcanvas-half" tabindex="-1" id="mobileNotificationsOffcanvas" aria-labelledby="mobileNotificationsOffcanvasLabel">
    {{-- Offcanvas Header: With View All link and Close Button --}}
    <div class="offcanvas-header border-bottom py-3 px-4" style="border-color: var(--border-color) !important;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bell-fill text-accent fs-5" style="color: var(--brand-accent) !important;"></i>
            <h5 class="offcanvas-title fw-800 mb-0 uppercase tracking-tight" id="mobileNotificationsOffcanvasLabel" style="font-size: 1.15rem; color: var(--text-main) !important;">
                Notifications
            </h5>
            @if($notifCount > 0)
                <span class="badge bg-danger rounded-pill smaller ms-1" style="font-size: 0.65rem;">{{ $notifCount }} new</span>
            @endif
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('notifications.index') }}" class="text-accent smaller fw-bold text-decoration-none hover-accent" style="font-size: 0.72rem; color: var(--brand-accent) !important;">
                View all &rarr;
            </a>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    </div>

    {{-- Offcanvas Notification List --}}
    <div class="offcanvas-body p-0 d-flex flex-column justify-content-between custom-scroll" style="overflow-y: auto;">
        <div>
            @forelse($recentNotifications as $notif)
                @php
                    $isUnread = is_null($notif->read_at);
                    $type = $notif->data['type'] ?? 'info';
                    $icon = match($type) {
                        'success' => 'bi-check-circle-fill text-success',
                        'danger' => 'bi-exclamation-octagon-fill text-danger',
                        'warning' => 'bi-exclamation-triangle-fill text-warning',
                        default => 'bi-info-circle-fill text-accent'
                    };
                @endphp
                <a href="{{ route('notifications.markAsRead', $notif->id) }}" 
                   class="d-flex gap-3 align-items-start p-3 border-bottom text-decoration-none notif-item {{ $isUnread ? 'bg-unread' : '' }}" 
                   style="border-color: var(--border-color) !important;">
                    <i class="bi {{ $icon }} fs-5 mt-0.5 flex-shrink-0" @if($type === 'info') style="color: var(--brand-accent) !important;" @endif></i>
                    <div class="flex-grow-1 min-w-0 text-start">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold small text-truncate" style="font-size: 0.82rem; color: {{ $isUnread ? 'var(--brand-accent)' : 'var(--text-main)' }} !important;">
                                {{ $notif->data['title'] ?? 'Notification' }}
                            </span>
                            @if($isUnread)
                                <span class="rounded-pill p-1 ms-2" title="Unread" style="background-color: var(--brand-accent); width: 7px; height: 7px; display: inline-block;"></span>
                            @endif
                        </div>
                        <p class="text-muted mb-1 text-truncate-2" style="font-size: 0.75rem; line-height: 1.4; color: var(--text-muted) !important;">
                            {{ $notif->data['message'] ?? '' }}
                        </p>
                        <small class="text-muted text-opacity-75" style="font-size: 0.65rem;">
                            {{ $notif->created_at->diffForHumans() }}
                        </small>
                    </div>
                </a>
            @empty
                <div class="p-5 text-center text-muted small" style="color: var(--text-muted) !important;">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-50"></i>
                    <span>No notifications received yet</span>
                </div>
            @endforelse
        </div>

        {{-- Offcanvas Footer: Swapped to Mark All Read Button --}}
        <div class="p-3 border-top" style="border-color: var(--border-color) !important;">
            @if($notifCount > 0)
                <a href="{{ route('notifications.clearAll') }}" class="btn btn-sm btn-outline-accent w-100 py-2 fw-bold uppercase d-flex align-items-center justify-content-center gap-2 text-decoration-none" style="font-size: 0.78rem;">
                    <i class="bi bi-check2-all"></i>
                    <span>MARK ALL AS READ</span>
                </a>
            @else
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary w-100 py-2 fw-bold uppercase d-flex align-items-center justify-content-center gap-2 text-decoration-none" style="font-size: 0.78rem;">
                    <i class="bi bi-card-checklist"></i>
                    <span>VIEW FULL NOTIFICATIONS PAGE</span>
                </a>
            @endif
        </div>
    </div>
</div>

<!-- =========================================================================
     5. RIGHT-SIDE HALF-PANEL (OFFCANVAS FOR MOBILE & TABLETS - NAVIGATION)
========================================================================= -->
<div class="offcanvas offcanvas-end custom-offcanvas-half" tabindex="-1" id="mobileNavOffcanvas" aria-labelledby="mobileNavOffcanvasLabel">
    {{-- Offcanvas Header --}}
    <div class="offcanvas-header border-bottom py-3 px-4" style="border-color: var(--border-color) !important;">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo" style="height: 38px; width: 38px; border-radius: 50%; border: 2px solid var(--brand-accent); object-fit: cover;">
            <h5 class="offcanvas-title fw-800 mb-0 uppercase tracking-tight" id="mobileNavOffcanvasLabel" style="font-size: 1.15rem; color: var(--text-main) !important;">
                MED<span class="text-accent" style="color: var(--brand-accent) !important;">SCREEN</span>
            </h5>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    {{-- Offcanvas Body --}}
    <div class="offcanvas-body p-4 d-flex flex-column justify-content-between custom-scroll" style="overflow-y: auto;">
        <div>
            @auth
                {{-- User Profile Card --}}
                <div class="p-3 rounded-3 mb-3 border" style="background-color: rgba(25, 211, 140, 0.05); border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-accent fw-bold flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(25, 211, 140, 0.15); border: 1.5px solid var(--brand-accent);">
                            <i class="bi bi-person-fill fs-4"></i>
                        </div>
                        <div class="text-truncate">
                            <div class="fw-bold small text-truncate" style="color: var(--text-main) !important;">{{ strtoupper(Auth::user()->name) }}</div>
                            <div class="smaller text-truncate" style="color: var(--text-muted) !important; font-size: 0.72rem;">{{ Auth::user()->email }}</div>
                            <span class="badge border border-accent text-accent mt-1 uppercase" style="font-size: 0.62rem; color: var(--brand-accent) !important; border-color: var(--brand-accent) !important; background-color: rgba(25, 211, 140, 0.1);">
                                {{ strtoupper(Auth::user()->role) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Button: Book Now --}}
                <div class="d-grid mb-4">
                    <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent py-2.5 d-flex align-items-center justify-content-center gap-2 text-decoration-none shadow-sm">
                        <i class="bi bi-calendar-plus-fill"></i>
                        <span>BOOK APPOINTMENT</span>
                    </a>
                </div>
            @else
                {{-- Guest Action Buttons: Login & Register --}}
                <div class="d-grid gap-2 mb-4">
                    <a href="{{ route('login') }}" class="btn-custom btn-outline-accent py-2.5 d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>LOGIN</span>
                    </a>
                    <a href="{{ route('register') }}" class="btn-custom btn-accent py-2.5 d-flex align-items-center justify-content-center gap-2 text-decoration-none shadow-sm">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>REGISTER</span>
                    </a>
                </div>
            @endauth

            {{-- Main Navigation Menu Items --}}
            <div class="text-uppercase fw-bold mb-2 ps-1" style="font-size: 0.68rem; letter-spacing: 1px; color: var(--text-muted) !important;">Navigation</div>
            <ul class="list-unstyled d-flex flex-column gap-1 mb-4">
                <li>
                    <a href="{{ Auth::check() ? route('main') : url('/') }}" class="mobile-nav-link {{ request()->routeIs('main') || request()->is('/') ? 'active' : '' }}">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Home / Main Menu</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('services.index') }}" class="mobile-nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}">
                        <i class="bi bi-list-stars"></i>
                        <span>Laboratory Services</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('result.verify-search') }}" class="mobile-nav-link {{ request()->routeIs('result.verify-search') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i>
                        <span>Verify Result</span>
                    </a>
                </li>

                @auth
                    <li>
                        <a href="{{ route('appointments.index', Auth::user()->isEmployee() ? ['view' => 'patient'] : []) }}" class="mobile-nav-link {{ request()->routeIs('appointments.index') && (request('view') === 'patient' || (!Auth::user()->isEmployee() && !request('view'))) ? 'active' : '' }}">
                            <i class="bi bi-calendar-check"></i>
                            <span>My Appointments</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('patient.history') }}" class="mobile-nav-link {{ request()->routeIs('patient.history') ? 'active' : '' }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Medical History</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('profile.edit') }}" class="mobile-nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                            <i class="bi bi-person-gear"></i>
                            <span>Profile & Settings</span>
                        </a>
                    </li>

                    {{-- Admin Internal Management Sub-list --}}
                    @if(Auth::user()->isAdmin())
                        <li class="mt-3 pt-2 border-top" style="border-color: var(--border-color) !important;">
                            <div class="text-uppercase fw-bold mb-2 ps-1" style="font-size: 0.68rem; letter-spacing: 1px; color: var(--brand-accent) !important;">Admin Controls</div>
                        </li>
                        <li>
                            <a href="{{ route('admin.panel') }}" class="mobile-nav-link {{ request()->routeIs('admin.panel') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>Panel Overview</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.users.index') }}" class="mobile-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="bi bi-people-fill"></i>
                                <span>Manage Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="mobile-nav-link {{ request('view') === 'queue' || (request()->routeIs('appointments.index') && !request('view')) ? 'active' : '' }}">
                                <i class="bi bi-kanban"></i>
                                <span>Master Queue</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('services.manage') }}" class="mobile-nav-link {{ request()->routeIs('services.manage') ? 'active' : '' }}">
                                <i class="bi bi-list-stars"></i>
                                <span>Services List</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.payment-providers.index') }}" class="mobile-nav-link {{ request()->routeIs('admin.payment-providers.*') ? 'active' : '' }}">
                                <i class="bi bi-qr-code-scan"></i>
                                <span>Payment QRs</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.appointment-settings') }}" class="mobile-nav-link {{ request()->routeIs('admin.appointment-settings') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Schedule Editor</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.logs') }}" class="mobile-nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                                <i class="bi bi-shield-check" style="color: #ff4d4d !important;"></i>
                                <span>Audit Logs</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.reports') }}" class="mobile-nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>Reports & Exports</span>
                            </a>
                        </li>
                    @elseif(Auth::user()->isEmployee())
                        {{-- Staff Internal Management Sub-list --}}
                        <li class="mt-3 pt-2 border-top" style="border-color: var(--border-color) !important;">
                            <div class="text-uppercase fw-bold mb-2 ps-1" style="font-size: 0.68rem; letter-spacing: 1px; color: var(--brand-accent) !important;">Staff Controls</div>
                        </li>
                        <li>
                            <a href="{{ route('staff.panel') }}" class="mobile-nav-link {{ request()->routeIs('staff.panel') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>Panel Overview</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.users.index') }}" class="mobile-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="bi bi-people"></i>
                                <span>User List</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="mobile-nav-link {{ request('view') === 'queue' || (request()->routeIs('appointments.index') && !request('view')) ? 'active' : '' }}">
                                <i class="bi bi-kanban"></i>
                                <span>Master Queue</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('services.manage') }}" class="mobile-nav-link {{ request()->routeIs('services.manage') ? 'active' : '' }}">
                                <i class="bi bi-list-stars"></i>
                                <span>Services List</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.payment-providers.index') }}" class="mobile-nav-link {{ request()->routeIs('admin.payment-providers.*') ? 'active' : '' }}">
                                <i class="bi bi-qr-code-scan"></i>
                                <span>Payment QRs</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.appointment-settings') }}" class="mobile-nav-link {{ request()->routeIs('admin.appointment-settings') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Schedule Editor</span>
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>
        </div>

        {{-- Offcanvas Footer: Sign Out --}}
        <div class="pt-3 border-top" style="border-color: var(--border-color) !important;">
            @auth
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100 py-2.5 fw-bold uppercase d-flex align-items-center justify-content-center gap-2" style="font-size: 0.8rem; border-radius: 8px;">
                        <i class="bi bi-power"></i>
                        <span>SIGN OUT</span>
                    </button>
                </form>
            @endauth
            <div class="text-center text-muted mt-3" style="font-size: 0.65rem;">
                &copy; {{ date('Y') }} Medscreen Diagnostic Laboratory
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     SCOPED STYLES FOR NOTIFICATIONS & HALF-PANEL OFFCANVAS
========================================================================= -->
<style>
/* Main Navbar Base */
.main-top-navbar {
    background-color: var(--nav-bg, #1c232d) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    min-height: 72px;
}

/* Remove default caret from dropdown toggle buttons */
.no-caret::after {
    display: none !important;
}

/* Notification Items & Dropdown */
.notif-item {
    transition: background-color 0.15s ease-in-out;
}
.notif-item:hover {
    background-color: rgba(25, 211, 140, 0.08) !important;
}
.notif-item.bg-unread {
    background-color: rgba(25, 211, 140, 0.04);
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Right-Side Half Panel Adaptive Theming */
.custom-offcanvas-half {
    background-color: var(--bg-card, #ffffff) !important;
    color: var(--text-main, #1c232d) !important;
    border-left: 2px solid var(--brand-accent, #19d38c) !important;
    width: 85vw !important;
    max-width: 440px;
    box-shadow: -10px 0 35px rgba(0, 0, 0, 0.35) !important;
    z-index: 2050 !important;
    transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
}

/* Enforce 50% screen width (Half Panel) on screens 576px and up */
@media (min-width: 576px) {
    .custom-offcanvas-half {
        width: 50vw !important;
        max-width: 500px !important;
    }
}

[data-bs-theme="dark"] .custom-offcanvas-half {
    background-color: #1c232d !important;
    color: #f1f5f9 !important;
    box-shadow: -10px 0 35px rgba(0, 0, 0, 0.7) !important;
}

/* Layer backdrop above page components */
.offcanvas-backdrop {
    z-index: 2040 !important;
}

/* Auto-invert close button in dark mode */
[data-bs-theme="dark"] .custom-offcanvas-half .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
[data-bs-theme="light"] .custom-offcanvas-half .btn-close {
    filter: none;
}

/* Offcanvas Navigation Links with Fixed Icon Alignment */
.mobile-nav-link {
    display: flex;
    align-items: center;
    padding: 10px 14px !important;
    border-radius: 8px;
    color: var(--text-main) !important;
    font-size: 0.84rem;
    font-weight: 600;
    text-decoration: none !important;
    transition: all 0.2s ease-in-out;
}

.mobile-nav-link i {
    font-size: 1.15rem;
    width: 24px;
    min-width: 24px;
    text-align: center;
    margin-right: 14px;
    color: var(--brand-accent) !important;
    flex-shrink: 0;
}

.mobile-nav-link:hover {
    color: var(--brand-accent) !important;
    background-color: rgba(25, 211, 140, 0.08) !important;
    transform: translateX(4px);
}

.mobile-nav-link.active {
    color: var(--brand-accent) !important;
    background-color: rgba(25, 211, 140, 0.1) !important;
    font-weight: 700;
    border-left: 3px solid var(--brand-accent) !important;
}

/* Dropdown states */
.dropdown-menu-dark .dropdown-item:active,
.dropdown-menu-dark .dropdown-item:focus,
.dropdown-menu-dark .dropdown-item.active {
    background-color: rgba(25, 211, 140, 0.15) !important;
    color: var(--brand-accent, #19d38c) !important;
    outline: none !important;
}

.dropdown-menu-dark .dropdown-item:hover {
    background-color: rgba(25, 211, 140, 0.1) !important;
    color: var(--brand-accent, #19d38c) !important;
}
</style>

<!-- =========================================================================
     REAL-TIME DYNAMIC BADGE SYNCHRONIZER (PUSHER/WEBSOCKETS)
========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    @auth
        const currentUserId = {{ Auth::id() }};
        if (typeof Pusher !== 'undefined') {
            const pusherKey = "{{ env('PUSHER_APP_KEY') }}";
            const pusherCluster = "{{ env('PUSHER_APP_CLUSTER') }}";
            if (pusherKey) {
                const pusherInstance = new Pusher(pusherKey, {
                    cluster: pusherCluster
                });

                // Listen to notifications channel dynamically
                const notifChannel = pusherInstance.subscribe('notifications.' + currentUserId);
                notifChannel.bind('notification.received', function(data) {
                    document.querySelectorAll('.nav-notif-badge').forEach(badge => {
                        let currentCount = parseInt(badge.innerText || '0');
                        badge.innerText = currentCount + 1;
                        badge.classList.remove('d-none');
                    });
                });
            }
        }
    @endauth
});
</script>