<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medscreen Diagnostic Laboratory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Design Variables */
        :root { 
            --neon: #5af781; 
            --dark-pure: #000000; 
            --dark-card: #0a0a0a; 
            --border-color: #1a1a1a; 
        }

        body { 
            background-color: var(--dark-pure); 
            color: #ffffff; 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
        }

        /* Remove underlines from all links */
        a { text-decoration: none !important; }

        /* Branding */
        .navbar { 
            background-color: rgba(0, 0, 0, 0.95); 
            border-bottom: 1px solid var(--border-color); 
            min-height: 75px;
            z-index: 1050 !important; 
            position: sticky;
            top: 0;
        }
        .text-neon { color: var(--neon) !important; }
        .border-neon { border-color: var(--neon) !important; }
        .nav-logo { height: 42px; width: 42px; object-fit: cover; border-radius: 50%; }

        .dropdown-menu {
            z-index: 1060 !important;
        }

        /* FORCE scroll on dropdowns even with Popper.js active */
        .dropdown-menu-scrollable {
            max-height: 200px !important; /* Shorter height to force scroll for testing */
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scrollbar-width: thin;
            scrollbar-color: var(--neon) #000;
        }

        /* Ensure the Accordion doesn't clip the dropdown */
        .accordion-collapse {
            overflow: visible !important;
        }

        .accordion-body {
            overflow: visible !important;
        }

        /* Webkit Scrollbar Styling */
        .dropdown-menu-scrollable::-webkit-scrollbar {
            width: 4px;
        }
        .dropdown-menu-scrollable::-webkit-scrollbar-track {
            background: #000;
        }
        .dropdown-menu-scrollable::-webkit-scrollbar-thumb {
            background-color: var(--neon);
            border-radius: 10px;
        }

        /* Unread notification style */
        .dropdown-item.bg-dark {
            background-color: rgba(90, 247, 129, 0.03) !important;
        }

        /* Hover effect */
        .dropdown-item:hover {
            background-color: rgba(90, 247, 129, 0.1) !important;
            color: white !important;
        }

        /* Red dot for bell when notifications exist */
        .shadow-neon {
            box-shadow: 0 0 10px var(--neon);
        }

        /* Override Bootstrap Nav-Pills Active State */
        .nav-pills .nav-link.active, 
        .nav-pills .show > .nav-link {
            background-color: var(--neon) !important;
            color: #000 !important; /* Black text on green background */
        }

        /* Hover effect for inactive tabs */
        .nav-pills .nav-link:hover:not(.active) {
            color: var(--neon) !important;
            background-color: rgba(90, 247, 129, 0.1);
        }

        /* Ensure the pills look like your custom buttons */
        .nav-pills .nav-link {
            border-radius: 6px;
            transition: 0.2s;
            letter-spacing: 1px;
        }

        /* Unified Button Styling - No Underlines, Sharp Look */
        .btn-custom {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 8px 16px;
            border-radius: 6px;
            text-transform: uppercase;
            border: 1px solid var(--neon);
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none !important;
        }
        .btn-neon { background-color: var(--neon); color: #000 !important; }
        .btn-neon:hover { background-color: #48d66e; transform: translateY(-1px); }
        
        .btn-outline-neon { background: transparent; color: var(--neon) !important; }
        .btn-outline-neon:hover { background: rgba(90, 247, 129, 0.1); }

        .btn-danger-custom {
            background-color: #ff4d4d !important; /* Vibrant Red */
            color: #ffffff !important;           /* Pure White Text */
            border: 1px solid #ff4d4d !important;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-danger-custom:hover {
            background-color: #ff1a1a !important;
            border-color: #ff1a1a !important;
            transform: translateY(-1px);
        }

        /* Tighten the bell notification badge */
        .btn-outline-neon.position-relative i {
            display: inline-block;
            vertical-align: middle;
        }

        /* Ensure the red circle stays perfectly centered and small */
        .bg-danger.rounded-pill {
            line-height: 1;
            min-width: 14px;
            padding: 0;
            box-shadow: 0 0 5px rgba(255, 0, 0, 0.4);
        }

        /* Card System */
        .card { 
            background-color: var(--dark-card); 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            overflow: hidden;
        }

        /* Navbar Logic: Keep buttons outside hamburger */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-toggler { border: none !important; padding: 0; }
        .navbar-toggler:focus { box-shadow: none; }

        /* Form Inputs */
        .form-control, .form-select {
            background-color: var(--dark-pure);
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: var(--neon);
            box-shadow: none;
            background-color: #050505;
            color: #fff;
        }

        /* Password Eye Toggle */
        .password-container { 
            position: relative; 
            display: block;
            width: 100%;
        }

        .password-toggle { 
            position: absolute; 
            right: 15px; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            z-index: 5;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .password-container input {
            padding-right: 45px !important;
        }

        .welcome-img {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('images/fb_cover.jpg');
            background-size: cover;
            background-position: center;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        #rowContainer tr {
            transition: border-color 0.3s ease;
            border-left: 4px solid transparent;
        }

        /* FORCE HIDE the default Bootstrap accordion arrow */
        .accordion-button::after {
            display: none !important;
            content: none !important;
            background-image: none !important;
        }

        /* Optional: Rotate your manual icon when expanded */
        .accordion-button:not(.collapsed) i.bi-chevron-down {
            transform: rotate(180deg);
            transition: 0.3s;
            color: var(--black) !important;
        }

        .accordion-button i.bi-chevron-down {
            transition: 0.3s;
        }

        /* --- THEME TOGGLE & BACK TO TOP STYLING --- */
        .floating-controls {
            position: fixed;
            bottom: 25px;
            right: 25px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 2000;
        }

        .btn-float {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--neon);
            color: #000 !important;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-float:hover { transform: scale(1.1); background: #48d66e; }

        /* Back to Top - Hidden by default */
        #btn-back-to-top { display: none; background: #fff; border: 1px solid var(--neon); }

        /* --- LIGHT MODE SPECIFIC OVERRIDES --- */
        [data-bs-theme="light"] {
            --dark-pure: #f8f9fa;
            --dark-card: #ffffff;
            --border-color: #dee2e6;
        }
        [data-bs-theme="light"] body { background-color: #f4f7f6; color: #1a1a1a; }
        [data-bs-theme="light"] .welcome-img { background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)), url('images/fb_cover.jpg'); background-size: cover; background-position: center; position: absolute; top:0; left:0; width:100%; height:100%; z-index:1; }
        [data-bs-theme="light"] .navbar { background-color: #ffffff !important; border-bottom: 1px solid #ddd; }
        [data-bs-theme="light"] .navbar-brand span { color: #000 !important; }
        [data-bs-theme="light"] .nav-pills .nav-link.active, [data-bs-theme="light"] .nav-pills .show > .nav-link { background-color: #1d7835 !important; color: #ffffff !important; }
        [data-bs-theme="light"] .nav-pills .nav-link:hover:not(.active) { color: #1d7835 !important; background-color: rgba(46, 128, 67, 0.1); }
        [data-bs-theme="light"] .text-white { color: #000 !important; }
        [data-bs-theme="light"] .text-neon { color: #1d7835 !important; }
        [data-bs-theme="light"] .text-secondary { color: #555 !important; }
        [data-bs-theme="light"] .hover-neon:hover { color: #1d7835 !important; }
        [data-bs-theme="light"] .btn-custom { border-color: #1d7835; }
        [data-bs-theme="light"] .btn-neon { background-color: #1d7835; color: #fff !important; }
        [data-bs-theme="light"] .btn-neon:hover { background-color: #166224; }
        [data-bs-theme="light"] .btn-outline-neon { background: transparent; color: #1d7835 !important; }
        [data-bs-theme="light"] .btn-outline-neon:hover { background: rgba(29, 120, 53, 0.1); }
        [data-bs-theme="light"] .card { background-color: #fff; border-color: #ddd; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        [data-bs-theme="light"] .bg-black { background-color: #e9e9e9 !important; }
        [data-bs-theme="light"] .bg-dark { background-color: #f0f0f0 !important; }
        [data-bs-theme="light"] .border-secondary { border-color: #dcdcdc !important; }
        [data-bs-theme="light"] .text-warning { color: #c69501 !important; }
        [data-bs-theme="light"] .border-warning { border-color: #c69501 !important; }
        [data-bs-theme="light"] .text-info { color: #17a2b8 !important; }
        [data-bs-theme="light"] .border-info { border-color: #17a2b8 !important; }
        [data-bs-theme="light"] .border-neon { border-color: #1d7835 !important; }
        [data-bs-theme="light"] .shadow-neon { box-shadow: 0 0 10px #1d7835 !important;;}
        [data-bs-theme="light"] .table { color: #000; }
        [data-bs-theme="light"] .form-control { background-color: #fff; border-color: #ccc; color: #000;}
        [data-bs-theme="light"] .form-select { background-color: #fff; border-color: #ccc; color: #000;}
        [data-bs-theme="light"] .accordion-button { background-color: #fff; color: #000; }
        [data-bs-theme="light"] .accordion-item { background-color: #fff; border-color: #ddd; }
        [data-bs-theme="light"] .card-body { background-color: #f1f1f1; }
        [data-bs-theme="light"] .card-footer { background-color: #f1f1f1; }
        [data-bs-theme="light"] .dropdown-menu { background-color: #fff; border-color: #ddd; }
        [data-bs-theme="light"] .dropdown-menu-scrollable { scrollbar-color: #1d7835 #fff; }
        [data-bs-theme="light"] .dropdown-item:hover { color: #000 !important; }

        [data-bs-theme="light"] .table-dark {
            --bs-table-color: #1a1a1a;
            --bs-table-bg: #ffffff;
            --bs-table-border-color: #dee2e6;
            --bs-table-striped-bg: #f8f9fa;
            --bs-table-active-bg: #e9ecef;
            --bs-table-hover-bg: #f1f3f5;
            color: #1a1a1a !important;
        }

        [data-bs-theme="light"] thead.bg-black {
            background-color: #f1f3f5 !important;
            color: #444 !important;
            border-bottom: 2px solid #ddd !important;
        }

        [data-bs-theme="light"] .table td, [data-bs-theme="light"] .table th {
            color: #1a1a1a !important;
            border-color: #dee2e6 !important;
        }

        [data-bs-theme="light"] #rowContainer input, 
        [data-bs-theme="light"] #rowContainer select, 
        [data-bs-theme="light"] #rowContainer textarea {
            background-color: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            color: #1a1a1a !important;
        }

        [data-bs-theme="light"] #rowContainer .bg-black {
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
        }

        [data-bs-theme="light"] #rowContainer label {
            font-weight: 600;
            color: #000000 !important;
        }

        #patientTable {
            table-layout: fixed;
            width: 100%;
        }

        #patientTable th {
            white-space: nowrap;
        }

    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <!-- Left: Brand -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo">
                <span class="fw-bold text-neon fs-5 mb-0">MEDSCREEN</span>
            </a>

            <!-- Right Side Container (Buttons + Toggler) -->
            <div class="header-actions order-lg-last ms-2">
                @guest
                    <a href="{{ route('login') }}" class="btn-custom btn-outline-neon px-2 px-sm-3">LOGIN</a>
                    <a href="{{ route('register') }}" class="btn-custom btn-neon d-none d-sm-flex">REGISTER</a>
                @else
                @auth
                    <div class="dropdown me-2">
                        <button class="btn-custom btn-outline-neon position-relative px-1 border-0 shadow-none" data-bs-toggle="dropdown">
                            <i class="bi bi-bell-fill fs-5 text-white"></i>
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <span class="position-absolute badge bg-danger border border-black rounded-pill d-flex align-items-center justify-content-center" 
                                    style="top: 2px; right: -2px; width: 14px; height: 14px; font-size: 0.55rem; font-weight: 800;">
                                    {{ auth()->user()->unreadNotifications->count() }}
                                </span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end bg-black border-neon shadow-lg mt-2 dropdown-menu-scrollable" style="width: 320px;">
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li>
                                <a class="dropdown-item text-center text-neon small fw-bold py-2" href="{{ route('notifications.index') }}">
                                    SEE ALL NOTIFICATIONS <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </li>
                            
                            @forelse(auth()->user()->notifications->take(10) as $notification)
                                <li class="border-bottom border-secondary border-opacity-25">
                                    {{-- UPDATED: Points to markAsRead route with the ID --}}
                                    <a class="dropdown-item py-3 {{ $notification->read_at ? 'opacity-50' : 'bg-dark border-start border-neon' }}" 
                                    href="{{ route('notifications.markAsRead', $notification->id) }}">
                                        
                                        <div class="text-{{ $notification->data['type'] ?? 'neon' }} fw-bold small mb-1 uppercase" style="font-size: 0.7rem;">
                                            {{ $notification->data['title'] }}
                                        </div>
                                        <div class="text-white small" style="white-space: normal; line-height: 1.2;">
                                            {{ $notification->data['message'] }}
                                        </div>
                                        <div class="mt-2 d-flex justify-content-between align-items-center">
                                            <small class="text-secondary" style="font-size: 0.6rem;">{{ $notification->created_at->diffForHumans() }}</small>
                                            @if(!$notification->read_at)
                                                <span class="badge bg-neon rounded-pill p-1" style="width: 6px; height: 6px;"> </span>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @empty
                                <li class="text-center py-5 text-secondary small italic">
                                    <i class="bi bi-bell-slash d-block fs-3 mb-2 opacity-25"></i>
                                    No new notifications
                                </li>
                            @endforelse
                        </ul>
                    </div>
                    @endauth
                    <!-- Classic Clickable Name Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="btn-custom btn-outline-neon dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {{ strtoupper(Auth::user()->name) }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end bg-black border-secondary shadow-lg mt-2">
                            <li class="px-3 py-2"><small class="text-neon fw-bold">ROLE: {{ strtoupper(Auth::user()->role) }}</small></li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            
                            <li><a class="dropdown-item text-white" href="{{ route('dashboard') }}">
                                <i class="bi bi-house-door me-2"></i> {{ Auth::user()->role === 'user' ? 'MAIN MENU' : 'DASHBOARD' }}
                            </a></li>
                            <li><a class="dropdown-item text-white" href="{{ route('profile.edit') }}"><i class="bi bi-gear-fill me-2"></i> ACCOUNT SETTINGS</a></li>
                            
                            @if(Auth::user()->role !== 'user')
                                <li><a class="dropdown-item text-neon" href="{{ route('admin.users.index') }}">
                                    <i class="bi bi-people-fill me-2"></i> MANAGE USERS
                                </a></li>
                            @endif

                            @can('isStaff')
                                <li><a class="dropdown-item text-neon" href="{{ route('admin.appointment-settings') }}">
                                    <i class="bi bi-calendar-range me-2"></i> APPOINTMENT SETTINGS
                                </a></li>
                            @endcan

                            @can('isAdmin')
                                <li><a class="dropdown-item text-danger" href="{{ route('admin.logs') }}">
                                    <i class="bi bi-shield-lock-fill me-2"></i> SYSTEM LOGS
                                </a></li>
                            @endcan

                            {{-- Future Features Placeholder --}}
                            <li><a class="dropdown-item text-secondary disabled" href="#"><i class="bi bi-journal-text me-2"></i> MORE SOON</a></li>
                            
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger fw-bold"><i class="bi bi-power me-2"></i> LOGOUT</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endguest

                <!-- Hamburger (For Nav Links) -->
                <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                    <i class="bi bi-list text-white fs-2"></i>
                </button>
            </div>

            <!-- Middle: Collapsible Navigation Links -->
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <!-- Always Visible -->
                    <li class="nav-item">
                        <a class="nav-link text-white small fw-bold px-3" href="{{ route('services.index') }}">SERVICES</a>
                    </li>
                    
                    <!-- Only Visible when Logged In -->
                    @auth
                    <li class="nav-item">
                        <a class="nav-link text-white small fw-bold px-3" href="{{ route('appointments.index') }}">APPOINTMENTS</a>
                    </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4 mt-2">
        @if(session('success'))
            <div class="alert bg-neon text-white fw-bold border-0 shadow-sm mb-4">
                {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </div>

    <div class="floating-controls">
        <!-- Go Back to Top -->
        <button type="button" id="btn-back-to-top" class="btn-float" title="Back to Top">
            <i class="bi bi-arrow-up-short fs-4"></i>
        </button>

        <!-- Theme Switcher -->
        <button type="button" id="theme-toggle" class="btn-float" title="Switch Display Mode">
            <i class="bi bi-sun-fill fs-5" id="theme-icon"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 1. THEME SWITCHER LOGIC
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;

        // Load saved theme on refresh
        const savedTheme = localStorage.getItem('theme') || 'dark';
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            let currentTheme = htmlElement.getAttribute('data-bs-theme');
            let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        });

        function updateIcon(theme) {
            if (theme === 'light') {
                themeIcon.className = 'bi bi-moon-stars-fill fs-5';
            } else {
                themeIcon.className = 'bi bi-sun-fill fs-5';
            }
        }

        // 2. BACK TO TOP LOGIC
        const backToTopBtn = document.getElementById("btn-back-to-top");

        window.onscroll = function() {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTopBtn.style.display = "flex";
            } else {
                backToTopBtn.style.display = "none";
            }
        };

        backToTopBtn.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Password Eye Logic
        function setupPasswordToggle(inputId, toggleId) {
            const toggle = document.querySelector(toggleId);
            const input = document.querySelector(inputId);
            if(toggle && input) {
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
        }

        // Function to convert UTC timestamps to local device time
        function convertTimestamps() {
            document.querySelectorAll('.local-time-trigger').forEach(el => {
                const utcStr = el.dataset.utc;
                if (!utcStr) return;

                const dateObj = new Date(utcStr);
                
                // Format the Date (e.g., Apr 15, 2026)
                const localDate = dateObj.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                });

                // Format the Time (e.g., 04:49 PM)
                const localTime = dateObj.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });

                // Inject the converted time back into the HTML
                el.innerHTML = `
                    <div class="text-white small fw-bold">${localDate}</div>
                    <div class="text-white" style="font-size: 0.8rem;">${localTime}</div>
                `;
            });
        }

        // Run the conversion when the page loads
        document.addEventListener('DOMContentLoaded', convertTimestamps);
    </script>
    @stack('scripts')
</body>
</html>
