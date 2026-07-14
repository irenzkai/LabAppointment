{{-- data-bs-theme="dark" is required to keep text-muted readable in light mode --}}
<footer class="main-footer mt-auto" data-bs-theme="dark">
    <div class="container">
        <div class="row g-4 py-5">
            {{-- Column 1: Identity & Academic Disclaimer --}}
            <div class="col-lg-5">
                {{-- FIXED: Encased brand logo and title in an anchor tag linking directly to the welcome page --}}
                <a href="{{ url('/') }}" class="text-decoration-none d-flex align-items-center gap-3 mb-4">
                    <img src="{{ asset('images/logo.jpg') }}" alt="Logo" class="nav-logo" style="filter: grayscale(1) brightness(2); width: 35px; height: 35px; border-color: rgba(255,255,255,0.3) !important;">
                    <span class="text-white fw-800 fs-5 uppercase tracking-tight">MED<span class="text-accent">SCREEN</span></span>
                </a>
 
                <div class="disclaimer-box p-4 rounded-4 border border-white border-opacity-10" style="background: rgba(0,0,0,0.15);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="capstone-badge uppercase">Academic Project</span>
                        <i class="bi bi-info-circle text-accent"></i>
                    </div>
                    <p class="small text-white-50 mb-0 lh-base">
                        <strong>Important Notice:</strong> This platform is a student-led <strong>Capstone Project</strong> for demonstration purposes. It is <u>not</u> the official website of Medscreen Diagnostic Laboratory. No actual medical transactions are processed here.
                    </p>
                </div>
            </div>

            {{-- Column 2: Navigation --}}
            <div class="col-6 col-lg-2 offset-lg-1">
                <h6 class="text-white fw-800 fs-x-small uppercase tracking-widest mb-4">Navigation</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ url('/') }}" class="footer-link">Home</a></li>
                    <li><a href="{{ route('services.index') }}" class="footer-link">Service Catalog</a></li>
                    {{-- FIXED: Added direct link to the secure clinical results verification page --}}
                    <li><a href="{{ route('result.verify-search') }}" class="footer-link">Verify Result</a></li>
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="footer-link">Dashboard</a></li>
                        <li><a href="{{ route('patient.history') }}" class="footer-link">Medical History</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="footer-link">Patient Login</a></li>
                        <li><a href="{{ route('register') }}" class="footer-link">Create Account</a></li>
                    @endauth
                </ul>
            </div>

            {{-- Column 3: Legal & Privacy --}}
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-800 fs-x-small uppercase tracking-widest mb-4">Compliance</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('legal.privacy') }}" class="footer-link">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.terms') }}" class="footer-link">Terms of Service</a></li>
                    <li><a href="{{ route('legal.dpa') }}" class="footer-link">Data Privacy Act</a></li>
                    <li><a href="{{ route('legal.cookies') }}" class="footer-link">Cookie Settings</a></li>
                </ul>
            </div>

            {{-- Column 4: Contact & Socials --}}
            <div class="col-lg-2">
                <h6 class="text-white fw-800 fs-x-small uppercase tracking-widest mb-4">Connect</h6>
                <div class="d-flex gap-3 mb-4">
                    <a href="https://web.facebook.com/medscreendiagnosticlab/" target="_blank" class="text-white-50 hover-accent fs-4"><i class="bi bi-facebook"></i></a>
                    <a href="mailto:medscreen.lab@gmail.com" class="text-white-50 hover-accent fs-4"><i class="bi bi-envelope-at"></i></a>
                </div>
                <div class="pt-2">
                    <small class="text-muted d-block uppercase fw-bold mb-1" style="font-size: 0.6rem;">Developed By</small>
                    <span class="text-white-50 small">R.MAMON</span>
                </div>
            </div>
        </div>

        {{-- Bottom Copyright Strip --}}
        <div class="py-4 border-top border-white border-opacity-10 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <p class="text-muted small mb-0">
                &copy; {{ date('Y') }} <strong>Medscreen</strong>. Not an official commercial portal.
            </p>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted" style="font-size: 0.65rem;">System Version 1.0.4-Beta</span>
                <div class="vr bg-white opacity-25" style="height: 15px;"></div>
                <i class="bi bi-shield-lock-fill text-accent" title="Secured Demo Environment"></i>
            </div>
        </div>
    </div>
</footer>

<style>
.main-footer {
    background-color: var(--brand-dark);
    border-top: 2px solid var(--brand-accent);
}

.footer-link {
    color: rgba(255, 255, 255, 0.5) !important;
    font-size: 0.85rem;
    display: block;
    margin-bottom: 0.75rem;
    transition: 0.2s ease;
}

.footer-link:hover {
    color: var(--brand-accent) !important;
    transform: translateX(5px);
}

.capstone-badge {
    background: rgba(25, 211, 140, 0.15);
    color: var(--brand-accent);
    font-size: 0.6rem;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 4px;
    border: 1px solid var(--brand-accent);
}

.hover-accent:hover {
    color: var(--brand-accent) !important;
}
</style>