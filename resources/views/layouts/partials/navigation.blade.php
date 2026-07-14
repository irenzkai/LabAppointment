{{-- Enforces navigation to render on top of page animation stacking contexts --}}
<nav class="navbar navbar-expand-lg sticky-top shadow-sm" style="z-index: 1050 !important;">
    <div class="container">
        <!-- 1. BRANDING & LOGO -->
        <a class="navbar-brand" href="{{ Auth::check() ? route('dashboard') : url('/') }}">
            <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="nav-logo">
            <span class="text-white uppercase fw-800 tracking-tight">MED<span class="text-accent">SCREEN</span></span>
        </a>

        <!-- 2. MOBILE TOGGLER -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="bi bi-list text-white fs-2"></i>
        </button>

        <!-- 3. NAVIGATION LINKS -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}">
                        SERVICES
                    </a>
                </li>
                @auth
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                        APPOINTMENTS
                    </a>
                </li>
                @endauth
            </ul>

            <!-- 4. RIGHT SIDE ACTIONS -->
            <div class="d-flex align-items-center gap-2">
                @guest
                <a href="{{ route('login') }}" class="btn-custom btn-outline-accent">LOGIN</a>
                <a href="{{ route('register') }}" class="btn-custom btn-accent">REGISTER</a>
                @else
                
                {{-- A. NOTIFICATION BELL --}}
                <div class="dropdown me-1">
                    @php $notifCount = auth()->user()->unreadNotifications->count(); @endphp
                    <button class="btn btn-link text-white position-relative p-2 border-0" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill fs-5"></i>
                        @if($notifCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark" style="font-size: 0.6rem; margin-top: 8px; margin-left: -8px;">
                            {{ $notifCount }}
                        </span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-secondary mt-2 py-0 overflow-hidden" style="width: 320px;">
                        <li class="bg-brand-dark p-3 border-bottom border-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-x-small fw-800 uppercase text-white">Recent Notifications</span>
                                <a href="{{ route('notifications.index') }}" class="text-accent fs-x-small fw-bold">VIEW ALL</a>
                            </div>
                        </li>
                        <div class="overflow-auto" style="max-height: 350px;">
                            @forelse(auth()->user()->notifications->take(5) as $notification)
                            <li>
                                <a class="dropdown-item p-3 border-bottom border-secondary border-opacity-25 {{ $notification->read_at ? 'opacity-50' : 'bg-dark border-start border-accent' }}" href="{{ route('notifications.markAsRead', $notification->id) }}">
                                    <div class="fw-bold fs-x-small text-accent mb-1 uppercase">{{ $notification->data['title'] }}</div>
                                    <div class="text-wrap small text-white-50">{{ $notification->data['message'] }}</div>
                                    <div class="mt-2 text-muted" style="font-size: 0.65rem;">{{ $notification->created_at->diffForHumans() }}</div>
                                </a>
                            </li>
                            @empty
                            <li class="text-center py-5 text-secondary opacity-50 small">
                                <i class="bi bi-bell-slash d-block fs-3 mb-2"></i>
                                No new notifications
                            </li>
                            @endforelse
                        </div>
                    </ul>
                </div>

                {{-- B. QUICK ACTION: BOOK NOW (Patient Only) --}}
                @if(Auth::user()->isPatient())
                <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent px-3 py-2 d-none d-sm-flex me-2">
                    <i class="bi bi-plus-lg me-1"></i> BOOK NOW
                </a>
                @endif

                {{-- C. USER PROFILE DROPDOWN --}}
                <div class="dropdown">
                    <button class="btn-custom btn-outline-accent py-2 dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i>{{ strtoupper(Auth::user()->first_name) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-secondary mt-2">
                        
                        {{-- Wrapped inside Employee check to hide entirely for Patients --}}
                        @if(Auth::user()->isEmployee())
                        <li class="px-3 py-2 border-bottom border-secondary mb-2 bg-brand-dark bg-opacity-25">
                            <div class="text-white-50 fs-x-small uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Access Level</div>
                            <div class="text-accent small fw-800" style="font-size: 0.8rem;">{{ strtoupper(Auth::user()->role) }}</div>
                        </li>
                        @endif

                        <li>
                            <a class="dropdown-item small py-2" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2 me-2"></i> 
                                {{ Auth::user()->isPatient() ? 'Main Menu' : 'Dashboard' }}
                            </a>
                        </li>

                        @if(Auth::user()->isPatient())
                        <li>
                            <a class="dropdown-item small py-2" href="{{ route('patient.history') }}">
                                <i class="bi bi-clock-history me-2"></i> Medical History
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->isEmployee())
                        <li>
                            <a class="dropdown-item small py-2 text-accent" href="{{ route('admin.users.index') }}">
                                <i class="bi bi-people-fill me-2"></i> Manage Users
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small py-2 text-accent" href="{{ route('admin.payment-providers.index') }}">
                                <i class="bi bi-qr-code-scan me-2"></i> Manage Payment QRs
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small py-2 text-accent" href="{{ route('admin.appointment-settings') }}">
                                <i class="bi bi-gear-fill me-2"></i> Clinical Schedule
                            </a>
                        </li>
                        @endif

                        @can('isAdmin')
                        <li>
                            <a class="dropdown-item small py-2 text-danger" href="{{ route('admin.logs') }}">
                                <i class="bi bi-shield-lock-fill me-2"></i> System Security
                            </a>
                        </li>
                        @endcan

                        <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>

                        <li>
                            <a class="dropdown-item small py-2" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-gear me-2"></i> Account Settings
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
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
    </div>
</nav>

<style>
/* Targeted refinements for nav specific items */
.nav-link {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 0.5rem 1rem !important;
}

.dropdown-menu-dark .dropdown-item {
    font-weight: 500;
    transition: 0.2s;
}

.dropdown-menu-dark .dropdown-item:hover {
    background-color: rgba(25, 211, 140, 0.1);
    color: var(--brand-accent) !important;
}

.navbar-toggler:focus {
    box-shadow: none;
}
</style>