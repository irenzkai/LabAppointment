<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Medscreen | @yield('title', 'Diagnostic Laboratory')</title>

    <!-- Global Favicon Link -->
    <link rel="shortcut icon" href="{{ asset('images/logo.jpg') }}" type="image/x-icon">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Core Assets (Bootstrap 5 & Icons) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Redesigned Custom Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/custom-style.css') }}">

    @stack('styles')
</head>
<body class="animate-page">

    {{-- 1. GLOBAL NAVIGATION --}}
    @include('layouts.partials.navigation')

    {{-- 2. MAIN CONTENT AREA --}}
    <div class="d-flex flex-column">

        <main class="container py-4 mt-1" style="min-height: 65vh;">

            {{-- Global Success Message (suppressed on routes with dedicated in-page banners to prevent duplicate alerts) --}}
            @if((session('success') || request()->has('verified')) && !Route::is('login', 'register', 'password.request', 'password.reset', 'verification.notice', 'patient.history', 'admin.users.history'))
            <div class="alert alert-clinical alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-3 fs-4 text-accent"></i>
                <div class="text-start">
                    <div class="fw-800 uppercase fs-x-small text-accent" style="font-size: 0.75rem; letter-spacing: 0.5px;">Success</div>
                    <div class="small text-main">
                        {{-- Dynamically toggle message if arriving via live verification redirect --}}
                        @if(request()->has('verified'))
                        Your email address has been successfully verified! Welcome to Medscreen.
                        @else
                        {{ session('success') }}
                        @endif
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            {{-- Global Error Banner (suppressed on routes with dedicated in-page banners to prevent duplicate alerts) --}}
            @if((session('error') || $errors->any()) && !Route::is('login', 'register', 'password.request', 'password.reset', 'verification.notice', 'patient.history', 'admin.users.history'))
            <div class="alert alert-clinical alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                <div class="text-start">
                    <div class="fw-800 uppercase fs-x-small text-danger" style="font-size: 0.75rem; letter-spacing: 0.5px;">Action Required</div>
                    <div class="small text-main">
                        @if(session('error'))
                        {{ session('error') }}
                        @elseif($errors->any())
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @else
                        Please check the input fields for errors.
                        @endif
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            {{-- Page Content Injected Here --}}
            @yield('content')

        </main>

        {{-- 3. GLOBAL FOOTER (Capstone Disclaimer) --}}
        @include('layouts.partials.footer')

    </div>

    {{-- 4. FLOATING UI CONTROLS (Theme Switcher, Back to Top) --}}
    @include('layouts.partials.footer-controls')

    {{-- 5. GLOBAL MODALS (Reason-Gate, etc.) --}}
    @include('layouts.partials.modals')

    {{-- 6. CORE SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global JS Functions -->
    @include('layouts.partials.scripts')

    <!-- Page Specific Scripts -->
    @stack('scripts')

</body>
</html>