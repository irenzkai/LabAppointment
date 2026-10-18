@extends('legal.layout')
@section('title', 'Cookie Preferences')

@section('legal-content')
<div class="animate-page text-start">
    {{-- Header --}}
    <div class="mb-4">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2 border border-accent border-opacity-25" style="background-color: rgba(25, 211, 140, 0.08);">
            <i class="bi bi-cookie text-accent fs-6"></i>
            <span class="text-accent fw-bold uppercase tracking-wider" style="font-size: 0.7rem; letter-spacing: 0.5px;">Browser Storage & Tracking Policy</span>
        </div>
        <h2 class="fw-800 text-main mb-1 tracking-tight" style="font-size: 1.85rem;">Cookie Policy & Preferences</h2>
        <p class="text-muted small mb-0">Manage technical, functional, and analytical browser storage &bull; NPC Advisory Compliant</p>
    </div>
    <hr class="my-4" style="border-color: var(--border-color) !important;">

    <div class="lh-lg text-muted" style="color: var(--text-muted) !important;">
        {{-- Section 1: Overview --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                1. Overview & Regulatory Disclosure
            </h5>
            <p class="mb-3">
                <strong>Medscreen Diagnostic Laboratory</strong> uses cookies, web storage (HTML5 <code class="text-accent">localStorage</code>), and secure session tokens to ensure the reliability, cryptographic security, and optimal performance of our online scheduling portal.
            </p>
            <p class="mb-0">
                In adherence to the principles of transparency and proportionality under the <strong>Philippine Data Privacy Act of 2012 (RA 10173)</strong>, this policy explains the categories of cookies we employ, their operational lifespans, and how you can exercise granular control over non-essential browser tracking.
            </p>
        </section>

        {{-- Section 2: Interactive Preference Console --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                2. Local Storage Preference Console
            </h5>
            <p class="small mb-3">Customize your browser storage permissions below. Essential security cookies remain active to protect patient sessions from cross-site exploits.</p>

            <div class="card p-4 border mb-4 shadow-sm" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2" style="border-color: var(--border-color) !important;">
                    <span class="text-main fw-800 uppercase small" style="font-size: 0.78rem; letter-spacing: 0.5px;">Storage Permissions</span>
                    <span class="badge border border-accent text-accent uppercase" style="font-size: 0.62rem; background-color: rgba(25, 211, 140, 0.08);">SESSION ACTIVE</span>
                </div>

                {{-- 1. Essential Switch (Disabled - Always Active) --}}
                <div class="form-check form-switch p-3 border rounded-3 mb-3" style="background-color: rgba(25, 211, 140, 0.03); border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <input class="form-check-input ms-0 me-3 mt-1" type="checkbox" id="cookie-essentials" checked disabled>
                            <label class="form-check-label text-main fw-bold small uppercase" for="cookie-essentials">
                                Strictly Necessary Cookies
                            </label>
                            <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25 ms-2 smaller uppercase" style="font-size: 0.6rem;">Required</span>
                        </div>
                    </div>
                    <p class="text-muted smaller mb-0 mt-2 ps-4 ms-2" style="font-size: 0.75rem; line-height: 1.45;">
                        Mandatory for patient authentication, Cross-Site Request Forgery (CSRF) token defense, Reason-Gate authorization, and encrypted transaction state tracking. Cannot be deactivated.
                    </p>
                </div>

                {{-- 2. Functional & Theme Switch (Interactive) --}}
                <div class="form-check form-switch p-3 border rounded-3 mb-3" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <input class="form-check-input ms-0 me-3 mt-1" type="checkbox" id="cookie-functional">
                            <label class="form-check-label text-main fw-bold small uppercase" for="cookie-functional">
                                Functional & Interface Preferences
                            </label>
                        </div>
                    </div>
                    <p class="text-muted smaller mb-0 mt-2 ps-4 ms-2" style="font-size: 0.75rem; line-height: 1.45;">
                        Retains your interface appearance preferences (Dark Mode vs. Light Mode) and caches Philippine Standard Geographic Code (PSGC) regions to accelerate appointment wizard loading.
                    </p>
                </div>

                {{-- 3. Performance / Analytics Switch (Interactive) --}}
                <div class="form-check form-switch p-3 border rounded-3 mb-3" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <input class="form-check-input ms-0 me-3 mt-1" type="checkbox" id="cookie-analytics">
                            <label class="form-check-label text-main fw-bold small uppercase" for="cookie-analytics">
                                Analytical & Telemetry Cookies
                            </label>
                        </div>
                    </div>
                    <p class="text-muted smaller mb-0 mt-2 ps-4 ms-2" style="font-size: 0.75rem; line-height: 1.45;">
                        Collects anonymized page response latencies, server error counts, and booking completion drop-offs. Strictly prohibited from accessing or transmitting patient health information.
                    </p>
                </div>

                {{-- Save & Reset Actions --}}
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn-custom btn-accent flex-grow-1 py-2.5 fw-bold uppercase shadow-sm" style="font-size: 0.78rem;" onclick="saveCookiePreferences()">
                        <i class="bi bi-check2-circle me-1.5"></i> Save Configuration
                    </button>
                    <button type="button" class="btn-custom btn-outline-secondary py-2.5 px-3 fw-bold uppercase" style="font-size: 0.78rem;" onclick="resetCookiePreferences()">
                        Reset Defaults
                    </button>
                </div>
            </div>

            {{-- Interactive Success Alert --}}
            <div id="cookie-alert" class="alert alert-clinical d-none align-items-center mb-4 p-3 rounded-3" role="alert" style="border-left: 4px solid var(--brand-accent) !important; background-color: rgba(25, 211, 140, 0.08);">
                <i class="bi bi-check-circle-fill me-3 text-accent fs-5"></i>
                <span class="small text-main fw-semibold">Cookie and storage preferences updated successfully for this browser session.</span>
            </div>
        </section>

        {{-- Section 3: Technical Cookie Inventory Table --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                3. Technical Cookie Inventory & Lifecycles
            </h5>
            <p class="mb-3">The table below details all first-party cookies deposited by the Medscreen application domain:</p>

            <div class="table-responsive mb-0">
                <table class="table table-bordered small mb-0" style="border-color: var(--border-color) !important; color: var(--text-main);">
                    <thead style="background-color: rgba(0,0,0,0.03);">
                        <tr>
                            <th class="py-2.5 px-3">Cookie Identifier</th>
                            <th class="py-2.5 px-3">Category</th>
                            <th class="py-2.5 px-3">Duration</th>
                            <th class="py-2.5 px-3">Technical Purpose & Security Attributes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-monospace fw-bold text-accent px-3 py-2.5">medscreen_session</td>
                            <td class="px-3 py-2.5">Essential</td>
                            <td class="px-3 py-2.5">120 Minutes</td>
                            <td class="px-3 py-2.5">Maintains active authenticated session. Flags: <code class="text-accent">HttpOnly</code>, <code class="text-accent">SameSite=Lax</code>, <code class="text-accent">Secure</code>.</td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-accent px-3 py-2.5">XSRF-TOKEN</td>
                            <td class="px-3 py-2.5">Essential</td>
                            <td class="px-3 py-2.5">Session</td>
                            <td class="px-3 py-2.5">Cryptographic token preventing unauthorized Cross-Site Request Forgery attacks on patient forms.</td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-accent px-3 py-2.5">remember_web_*</td>
                            <td class="px-3 py-2.5">Functional</td>
                            <td class="px-3 py-2.5">5 Years</td>
                            <td class="px-3 py-2.5">Stores an encrypted identifier to keep patients signed in when "Remember Me" is explicitly toggled.</td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-accent px-3 py-2.5">data-bs-theme</td>
                            <td class="px-3 py-2.5">Preference</td>
                            <td class="px-3 py-2.5">Persistent</td>
                            <td class="px-3 py-2.5">Stored in <code class="text-accent">localStorage</code> to remember your chosen high-contrast dark or light interface theme.</td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-accent px-3 py-2.5">consent_analytics</td>
                            <td class="px-3 py-2.5">Preference</td>
                            <td class="px-3 py-2.5">1 Year</td>
                            <td class="px-3 py-2.5">Records your opt-in or opt-out decision for non-essential performance telemetry.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Section 4: Browser Management Guidance --}}
        <section class="mb-5">
            <h5 class="fw-800 text-main mb-3 uppercase tracking-tight" style="font-size: 1.15rem;">
                4. Managing Cookies in Your Web Browser
            </h5>
            <p class="mb-3">
                In addition to our preference console, all major desktop and mobile web browsers allow you to block, purge, or inspect stored cookies:
            </p>
            <ul class="ps-3 mb-0 small">
                <li class="mb-1.5"><strong>Google Chrome:</strong> Settings &rarr; Privacy and security &rarr; Third-party cookies &rarr; See all site data and permissions.</li>
                <li class="mb-1.5"><strong>Microsoft Edge:</strong> Settings &rarr; Cookies and site permissions &rarr; Manage and delete cookies and site data.</li>
                <li class="mb-1.5"><strong>Mozilla Firefox:</strong> Settings &rarr; Privacy & Security &rarr; Cookies and Site Data &rarr; Clear Data.</li>
                <li class="mb-0"><strong>Apple Safari (iOS/macOS):</strong> Settings &rarr; Safari &rarr; Advanced &rarr; Website Data &rarr; Remove All Website Data.</li>
            </ul>
        </section>

        {{-- Section 5: Security Advisory --}}
        <section class="p-4 rounded-3 border" style="background-color: rgba(25, 211, 140, 0.04); border-color: var(--border-color) !important;">
            <h5 class="fw-800 text-main mb-2 uppercase tracking-tight" style="font-size: 1.1rem;">
                5. Third-Party Tracking Prohibition
            </h5>
            <p class="small text-muted mb-0">
                Medscreen strictly prohibits third-party behavioral advertising pixels, behavioral marketing scripts, or third-party social media data mining trackers. Your presence, appointment inquiries, and clinical diagnostic requisitions remain strictly confidential within our accredited healthcare boundary.
            </p>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Load existing preferences from localStorage
    const analyticalSaved = localStorage.getItem('consent_analytics') === 'true';
    const functionalSaved = localStorage.getItem('consent_functional') !== 'false'; // Default to true

    const analyticsToggle = document.getElementById('cookie-analytics');
    const functionalToggle = document.getElementById('cookie-functional');

    if (analyticsToggle) analyticsToggle.checked = analyticalSaved;
    if (functionalToggle) functionalToggle.checked = functionalSaved;
});

function saveCookiePreferences() {
    const analyticsChecked = document.getElementById('cookie-analytics')?.checked || false;
    const functionalChecked = document.getElementById('cookie-functional')?.checked || false;

    localStorage.setItem('consent_analytics', analyticsChecked);
    localStorage.setItem('consent_functional', functionalChecked);

    // Visual feedback notification
    const alertBox = document.getElementById('cookie-alert');
    if (alertBox) {
        alertBox.classList.remove('d-none');
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        setTimeout(() => {
            alertBox.classList.add('d-none');
        }, 4000);
    }
}

function resetCookiePreferences() {
    localStorage.removeItem('consent_analytics');
    localStorage.removeItem('consent_functional');

    const analyticsToggle = document.getElementById('cookie-analytics');
    const functionalToggle = document.getElementById('cookie-functional');

    if (analyticsToggle) analyticsToggle.checked = false;
    if (functionalToggle) functionalToggle.checked = true;

    saveCookiePreferences();
}
</script>
@endpush