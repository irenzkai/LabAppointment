<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} | Medscreen Diagnostic Laboratory</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Core Assets (Bootstrap 5.3 & Icons) -->
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
    {{-- We use a wrapper to ensure footer is always at the bottom if content is short --}}
    <div class="d-flex flex-column min-vh-100">
        
        <main class="container py-5 mt-2 flex-grow-1">
            
            {{-- Global Success Message --}}
            @if(session('success'))
                <div class="alert alert-clinical d-flex align-items-center mb-4 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-3 fs-4 text-accent"></i>
                    <div>
                        <div class="fw-800 uppercase fs-x-small">Success</div>
                        <div class="small">{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- FIXED: Global Error/Validation Message modified to display exact validation errors dynamically --}}
            @if(session('error') || $errors->any())
                <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 d-flex align-items-center mb-4 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
                    <div>
                        <div class="fw-800 uppercase fs-x-small text-danger">Action Required</div>
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
                    <button type="button" class="btn-close ms-auto shadow-none" data-bs-dismiss="alert"></button>
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