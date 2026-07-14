@extends('legal.layout')

@section('legal-content')
    <h2 class="fw-800 text-main mb-4 tracking-tight">Cookie Preferences</h2>
    <p class="text-muted small">Manage technical and analytical browser storage.</p>
    <hr class="my-4">

    <p class="text-muted mb-4">
        We use cookies and similar local storage technologies to ensure our laboratory scheduling portal runs efficiently. You can customize your preferences for non-essential tracking below.
    </p>

    {{-- Interactive Config Box --}}
    <div class="card p-4 border-light mb-4 bg-light bg-opacity-10">
        <h6 class="text-main fw-800 uppercase small mb-4">Local Storage Preference Console</h6>
        
        {{-- 1. Essential Switch (Disabled - always on) --}}
        <div class="form-check form-switch p-3 border rounded mb-3 bg-white bg-opacity-5">
            <input class="form-check-input ms-0 me-3" type="checkbox" id="cookie-essentials" checked disabled>
            <label class="form-check-label text-main fw-bold" for="cookie-essentials">
                Strictly Necessary Cookies
            </label>
            <p class="text-muted small mb-0 mt-1">Required for secure authentication, CSRF token validation, and active session tracking. These cannot be disabled for security purposes.</p>
        </div>

        {{-- 2. Performance Switch (Interactive) --}}
        <div class="form-check form-switch p-3 border rounded mb-3 bg-white bg-opacity-5">
            <input class="form-check-input ms-0 me-3" type="checkbox" id="cookie-analytics">
            <label class="form-check-label text-main fw-bold" for="cookie-analytics">
                Analytical / Performance Cookies
            </label>
            <p class="text-muted small mb-0 mt-1">Allows our student development team to monitor anonymized visitor traffic to optimize loading speeds and clinical dashboard layouts.</p>
        </div>

        {{-- Save Button --}}
        <button type="button" class="btn-custom btn-accent w-100 py-3 mt-3" onclick="saveCookiePreferences()">
            SAVE CONFIGURATION
        </button>
    </div>

    {{-- Interactive Success Alert --}}
    <div id="cookie-alert" class="alert alert-clinical d-none align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-3 text-accent fs-5"></i>
        <span class="small text-main">Cookie configuration applied successfully to this session.</span>
    </div>

    @push('scripts')
    <script>
        // Load configurations if previously saved
        document.addEventListener('DOMContentLoaded', () => {
            const analyticalSaved = localStorage.getItem('consent_analytics') === 'true';
            document.getElementById('cookie-analytics').checked = analyticalSaved;
        });

        // Save preference to localStorage and trigger visual feedback
        function saveCookiePreferences() {
            const consentAnalytics = document.getElementById('cookie-analytics').checked;
            localStorage.setItem('consent_analytics', consentAnalytics);

            // Display alert confirmation
            const alertBox = document.getElementById('cookie-alert');
            alertBox.classList.remove('d-none');
            
            // Auto-hide alert after 3 seconds
            setTimeout(() => {
                alertBox.classList.add('d-none');
            }, 3000);
        }
    </script>
    @endpush
@endsection