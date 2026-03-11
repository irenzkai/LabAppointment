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
        .nav-logo { height: 42px; width: 42px; object-fit: cover; border-radius: 50%; }

        .dropdown-menu {
            z-index: 1060 !important;
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
                            
                            @can('isAdmin')
                                <li><a class="dropdown-item text-neon" href="{{ url('/admin/users') }}"><i class="bi bi-people-fill me-2"></i> MANAGE USERS</a></li>
                            @endcan

                            @can('isStaff')
                                <li><a class="dropdown-item text-neon" href="{{ route('admin.appointment-settings') }}">
                                    <i class="bi bi-calendar-range me-2"></i> APPOINTMENT SETTINGS
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
    @stack('scripts')
</body>
</html>
