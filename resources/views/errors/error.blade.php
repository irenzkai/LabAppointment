<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medscreen | Notice</title>

    <!-- Global Favicon Link -->
    <link rel="shortcut icon" href="{{ asset('images/logo.jpg') }}" type="image/x-icon">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Core Assets (Bootstrap 5 & Icons) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --brand-dark: #1c232d;
            --brand-accent: #19d38c;
            --brand-accent-hover: #15b376;
            --bg-main: #f4f7f9;
            --bg-card: #ffffff;
            --text-main: #1c232d;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        [data-bs-theme="dark"] {
            --brand-dark: #1c232d;
            --brand-accent: #19d38c;
            --brand-accent-hover: #15b376;
            --bg-main: #0a1016;
            --bg-card: #1c232d;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #2d3748;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            transition: background-color 0.3s ease;
            -webkit-font-smoothing: antialiased;
        }

        .error-card {
            background-color: var(--bg-card);
            border: 1.5px solid var(--border-color);
            border-radius: 20px;
            max-width: 580px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .btn-custom {
            padding: 11px 24px;
            border-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }

        .btn-accent {
            background-color: var(--brand-accent);
            color: #1c232d !important;
            border: 2px solid var(--brand-accent);
        }

        .btn-accent:hover {
            background-color: var(--brand-accent-hover);
            border-color: var(--brand-accent-hover);
            transform: translateY(-1px);
        }

        .btn-outline-custom {
            background-color: transparent;
            border: 1.5px solid var(--border-color);
            color: var(--text-muted) !important;
        }

        .btn-outline-custom:hover {
            border-color: var(--brand-accent);
            color: var(--brand-accent) !important;
            background-color: rgba(25, 211, 140, 0.05);
        }

        .btn-float-theme {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: var(--bg-card);
            border: 1.5px solid var(--border-color);
            color: var(--brand-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease;
        }

        .btn-float-theme:hover {
            border-color: var(--brand-accent);
            transform: scale(1.05);
        }
    </style>
</head>
<body>

@php
    $resolvedStatus = isset($status) ? (int)$status : (isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500);
    $customMessage = isset($exception) && $exception->getMessage() ? $exception->getMessage() : null;

    $errorInfo = match($resolvedStatus) {
        401 => [
            'code' => '401',
            'title' => 'Authentication Required',
            'desc' => $customMessage ?: 'You must be securely authenticated to access this medical portal resource.',
            'icon' => 'bi-shield-lock-fill',
            'badge' => 'UNAUTHORIZED',
            'badge_color' => 'warning',
        ],
        402 => [
            'code' => '402',
            'title' => 'Payment Settlement Required',
            'desc' => $customMessage ?: 'Diagnostic appointment processing requires verified payment settlement.',
            'icon' => 'bi-credit-card-2-front-fill',
            'badge' => 'PAYMENT REQUIRED',
            'badge_color' => 'warning',
        ],
        403 => [
            'code' => '403',
            'title' => 'Access Restricted',
            'desc' => $customMessage ?: 'You do not hold the authorized clinical or administrative role required to access this resource.',
            'icon' => 'bi-shield-slash-fill',
            'badge' => 'ACCESS FORBIDDEN',
            'badge_color' => 'danger',
        ],
        404 => [
            'code' => '404',
            'title' => 'Resource Not Found',
            'desc' => $customMessage ?: 'The clinical record, medical report, or page you are requesting could not be located in our database.',
            'icon' => 'bi-file-earmark-x-fill',
            'badge' => 'NOT FOUND',
            'badge_color' => 'info',
        ],
        419 => [
            'code' => '419',
            'title' => 'Session Timeout',
            'desc' => $customMessage ?: 'Your secure session has expired due to inactivity. Please refresh and resubmit your request.',
            'icon' => 'bi-hourglass-split',
            'badge' => 'CSRF / TIMEOUT',
            'badge_color' => 'warning',
        ],
        429 => [
            'code' => '429',
            'title' => 'Rate Limit Reached',
            'desc' => $customMessage ?: 'Too many requests were sent in a short window. Please wait a brief moment before trying again.',
            'icon' => 'bi-speedometer2',
            'badge' => 'TOO MANY ATTEMPTS',
            'badge_color' => 'warning',
        ],
        503 => [
            'code' => '503',
            'title' => 'Maintenance In Progress',
            'desc' => $customMessage ?: 'The Medscreen laboratory portal is currently undergoing scheduled maintenance. Services will resume shortly.',
            'icon' => 'bi-tools',
            'badge' => 'SERVICE UNAVAILABLE',
            'badge_color' => 'warning',
        ],
        default => [
            'code' => (string)($resolvedStatus ?: '500'),
            'title' => 'System Malfunction',
            'desc' => $customMessage ?: 'An unexpected technical exception occurred while processing this request. The system audit logger has recorded this incident.',
            'icon' => 'bi-exclamation-triangle-fill',
            'badge' => ($resolvedStatus >= 500 ? 'SERVER EXCEPTION' : 'CLIENT ERROR'),
            'badge_color' => 'danger',
        ],
    };

    $safeHomeUrl = url('/');
    try {
        if (\Illuminate\Support\Facades\Auth::check()) {
            $safeHomeUrl = \Illuminate\Support\Facades\Route::has('main') ? route('main') : url('/');
        }
    } catch (\Throwable $th) {
        $safeHomeUrl = url('/');
    }
@endphp

<div class="error-card text-center p-4 p-md-5">
    {{-- Header Logo & Identity --}}
    <div class="d-flex justify-content-center align-items-center gap-2 mb-4">
        <img src="{{ asset('images/logo.jpg') }}" alt="Medscreen Logo" class="rounded-circle" style="width: 42px; height: 42px; border: 2px solid var(--brand-accent); object-fit: cover;">
        <span class="text-white uppercase fw-bold fs-4 tracking-tight" style="color: var(--text-main) !important;">
            MED<span style="color: var(--brand-accent);">SCREEN</span>
        </span>
    </div>

    {{-- Error Icon --}}
    <div class="my-3">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background-color: rgba(25, 211, 140, 0.08); border: 1.5px solid var(--border-color);">
            <i class="bi {{ $errorInfo['icon'] }} fs-1" style="color: var(--brand-accent);"></i>
        </div>
    </div>

    {{-- Code & Badge --}}
    <div class="mb-2">
        <span class="badge border border-{{ $errorInfo['badge_color'] }} text-{{ $errorInfo['badge_color'] }} bg-{{ $errorInfo['badge_color'] }} bg-opacity-10 px-3 py-1.5 fw-bold uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
            {{ $errorInfo['code'] }} &bull; {{ $errorInfo['badge'] }}
        </span>
    </div>

    {{-- Title & Description --}}
    <h3 class="fw-bold uppercase mb-2" style="color: var(--text-main); font-size: 1.5rem; letter-spacing: -0.02em;">
        {{ $errorInfo['title'] }}
    </h3>
    <p class="text-muted small mx-auto mb-4" style="max-width: 440px; line-height: 1.6;">
        {{ $errorInfo['desc'] }}
    </p>

    {{-- Quick Action Buttons --}}
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center pt-2 mb-4">
        <button type="button" class="btn-custom btn-outline-custom w-100 w-sm-auto" onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ $safeHomeUrl }}'">
            <i class="bi bi-arrow-left me-1.5"></i> Previous Page
        </button>
        <a href="{{ $safeHomeUrl }}" class="btn-custom btn-accent w-100 w-sm-auto">
            <i class="bi bi-house-door-fill me-1.5"></i> Main Menu
        </a>
    </div>

    {{-- Footer Info --}}
    <div class="pt-3 border-top" style="border-color: var(--border-color) !important;">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-1 text-muted" style="font-size: 0.7rem;">
            <span>Support: <a href="mailto:medscreen.lab@gmail.com" class="text-decoration-none" style="color: var(--brand-accent);">medscreen.lab@gmail.com</a></span>
            <span>&copy; {{ date('Y') }} Medscreen Laboratory</span>
        </div>
    </div>
</div>

{{-- Floating Theme Switcher --}}
<button type="button" class="btn-float-theme" id="theme-btn" title="Toggle Color Theme" onclick="toggleTheme()">
    <i class="bi bi-sun-fill" id="theme-btn-icon"></i>
</button>

<script>
    function applySavedTheme() {
        const saved = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', saved);
        const icon = document.getElementById('theme-btn-icon');
        if (icon) {
            icon.className = saved === 'light' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
        }
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-bs-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
        const icon = document.getElementById('theme-btn-icon');
        if (icon) {
            icon.className = next === 'light' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
        }
    }

    applySavedTheme();
</script>

</body>
</html>