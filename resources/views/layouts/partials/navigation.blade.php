<nav class="navbar navbar-expand-lg sticky-top shadow-sm" style="z-index: 1050 !important;">
 <div class="container">
 <!-- 1. BRANDING & LOGO -->
 <a class="navbar-brand d-flex align-items-center gap-2" href="{{ Auth::check() ? route('dashboard') : url('/') }}">
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
 <a class="nav-link {{ request()->routeIs('services.index') ? 'active' : '' }}" href="{{ route('services.index') }}">
 SERVICES
 </a>
 </li>
 @auth
 <li class="nav-item">
 <a class="nav-link {{ request()->routeIs('appointments.*') && request('view') !== 'queue' ? 'active' : '' }}" href="{{ route('appointments.index') }}">
 APPOINTMENTS
 </a>
 </li>
 
 {{-- Staff / Admin Dashboard navigation links --}}
 @if(Auth::user()->isAdmin())
 <li class="nav-item">
 <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
 ADMIN DASHBOARD
 </a>
 </li>
 @elseif(Auth::user()->isEmployee())
 <li class="nav-item">
 <a class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}" href="{{ route('staff.dashboard') }}">
 STAFF DASHBOARD
 </a>
 </li>
 @endif
 @endauth
 </ul>

 <!-- 4. RIGHT SIDE ACTIONS -->
 <div class="d-flex align-items-center gap-2 flex-wrap">
 @guest
 <a href="{{ route('login') }}" class="btn-custom btn-outline-accent">LOGIN</a>
 <a href="{{ route('register') }}" class="btn-custom btn-accent">REGISTER</a>
 @else
 
 {{-- NOTIFICATION BELL --}}
 <div class="dropdown me-1">
 @php $notifCount = auth()->user()->unreadNotifications->count(); @endphp
 <button class="btn btn-link text-white position-relative p-2 border-0" data-bs-toggle="dropdown">
 <i class="bi bi-bell-fill fs-5"></i>
 <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark" style="font-size: 0.6rem; margin-top: 8px; margin-left: -8px; {{ $notifCount > 0 ? '' : 'display: none !important;' }}">
 {{ $notifCount }}
 </span>
 </button>
 <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-secondary mt-2 py-0 overflow-hidden" style="width: 320px;">
 <li class="bg-brand-dark p-3 border-bottom border-secondary">
 <div class="d-flex justify-content-between align-items-center">
 <span class="fs-x-small fw-800 uppercase text-white">Recent Notifications</span>
 <a href="{{ route('notifications.index') }}" class="text-accent fs-x-small fw-bold">VIEW ALL</a>
 </div>
 </li>
 <div class="overflow-auto" id="notif-list-container" style="max-height: 350px;">
 @forelse(auth()->user()->notifications->take(5) as $notification)
 <li>
 <a class="dropdown-item p-3 border-bottom border-secondary border-opacity-25 {{ $notification->read_at ? 'opacity-50' : 'bg-dark border-start border-accent' }}" href="{{ route('notifications.markAsRead', $notification->id) }}">
 <div class="fw-bold fs-x-small text-accent mb-1 uppercase">{{ $notification->data['title'] }}</div>
 <div class="text-wrap small text-white-50">{{ $notification->data['message'] }}</div>
 <div class="mt-2 text-muted" style="font-size: 0.65rem;">{{ $notification->created_at->diffForHumans() }}</div>
 </a>
 </li>
 @empty
 <li class="text-center py-5 text-secondary opacity-50 small" id="no-notifs-placeholder">
 <i class="bi bi-bell-slash d-block fs-3 mb-2"></i>
 No new notifications
 </li>
 @endforelse
 </div>
 </ul>
 </div>

 {{-- BOOK NOW CTA BUTTON FOR ALL LOGGED-IN USERS --}}
 <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent px-3 py-2 me-1">
 <i class="bi bi-plus-lg me-1"></i> BOOK NOW
 </a>

 {{-- USER ACCOUNT PROFILE DROPDOWN --}}
 <div class="dropdown">
 <button class="btn-custom btn-outline-accent py-2 dropdown-toggle" data-bs-toggle="dropdown">
 <i class="bi bi-person-circle me-1.5"></i>&nbsp;{{ strtoupper(Auth::user()->first_name) }}
 </button>
 <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-secondary mt-2">
 
 @if(Auth::user()->isAdmin())
 <li class="px-3 py-2 border-bottom border-secondary mb-1 bg-brand-dark bg-opacity-25">
 <div class="text-white-50 fs-x-small uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Role / Access</div>
 <div class="text-accent small fw-800" style="font-size: 0.8rem;">SYSTEM ADMINISTRATOR</div>
 </li>
 <li>
 <a class="dropdown-item small py-2 text-accent fw-bold" href="{{ route('admin.dashboard') }}">
 <i class="bi bi-speedometer2 me-2"></i> Admin Dashboard
 </a>
 </li>
 <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
 @elseif(Auth::user()->isEmployee())
 <li class="px-3 py-2 border-bottom border-secondary mb-1 bg-brand-dark bg-opacity-25">
 <div class="text-white-50 fs-x-small uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Role / Access</div>
 <div class="text-accent small fw-800" style="font-size: 0.8rem;">{{ strtoupper(Auth::user()->role) }}</div>
 </li>
 <li>
 <a class="dropdown-item small py-2 text-accent fw-bold" href="{{ route('staff.dashboard') }}">
 <i class="bi bi-speedometer2 me-2"></i> Staff Dashboard
 </a>
 </li>
 <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
 @endif

 <li>
 <a class="dropdown-item small py-2" href="{{ route('dashboard') }}">
 <i class="bi bi-house me-2"></i> Main Menu
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
 </div>
</nav>

<style>
/* Guarantees no default Bootstrap blue on dropdown items when clicked, held, or focused */
.dropdown-menu-dark .dropdown-item:active,
.dropdown-menu-dark .dropdown-item:focus,
.dropdown-menu-dark .dropdown-item.active {
    background-color: rgba(25, 211, 140, 0.15) !important;
    color: var(--brand-accent) !important;
    outline: none !important;
}

.dropdown-menu-dark .dropdown-item:hover {
    background-color: rgba(25, 211, 140, 0.1) !important;
    color: var(--brand-accent) !important;
}
</style>