@extends('layouts.app')

@section('content')
<div class="animate-page pb-5">
 
    {{-- 1. INTRO CARD (Seamless Full-Height Image Split) --}}
    <div class="card border-0 shadow-lg overflow-hidden mb-4" style="border-radius: 24px; min-height: 520px; display: flex;">
        <div class="row g-0 flex-grow-1 align-items-stretch">
            {{-- Left Column: Content --}}
            <div class="col-lg-7 d-flex align-items-center bg-brand-dark p-4 p-md-5 text-start" style="z-index: 2;">
                <div class="p-md-3">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(25, 211, 140, 0.1); border: 1px solid var(--brand-accent);">
                        <span class="text-accent fw-800 fs-x-small uppercase tracking-widest">DOH Accredited Laboratory</span>
                    </div>
                    
                    <h1 class="display-4 fw-800 text-white mb-3 tracking-tight">
                        Medscreen <br><span class="text-accent">Diagnostic</span><br>Laboratory.
                    </h1>
                    
                    <p class="text-white-50 fs-5 mb-5" style="max-width: 500px;">
                        Experience clinical excellence with accurate, reliable, and digital results delivered straight to you.
                    </p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="btn-custom btn-accent px-5 py-3">GET STARTED NOW</a>
                        <a href="{{ route('services.index') }}" class="btn-custom btn-outline-accent px-5 py-3">VIEW TEST MENU</a>
                    </div>
                </div>
            </div>

            {{-- Right Column: Full Bleed fb_cover.jpg Background --}}
            <div class="col-lg-5 d-none d-lg-block position-relative">
                <div style="background: url('{{ asset('images/fb_cover.jpg') }}') center/cover no-repeat; 
                            position: absolute; 
                            top: 0; 
                            right: 0; 
                            bottom: 0; 
                            left: 0;
                            height: 100%;">
                </div>
                {{-- Subtle overlay matching the Oxford Blue theme --}}
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(90deg, var(--brand-dark) 0%, transparent 15%);"></div>
            </div>
        </div>
    </div>

    {{-- 2. CLINICAL VALUE CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 p-4 border-light shadow-sm text-center border-top-accent">
                <i class="bi bi-shield-check text-accent fs-1 mb-2"></i>
                <h6 class="fw-800 text-main uppercase small">ISO Certified</h6>
                <p class="text-muted small mb-0">Adhering to strict international standard laboratory practices.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 border-light shadow-sm text-center border-top-accent">
                <i class="bi bi-lightning-charge text-accent fs-1 mb-2"></i>
                <h6 class="fw-800 text-main uppercase small">Fast Turnaround</h6>
                <p class="text-muted small mb-0">Results compiled and processed securely in hours, not days.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 border-light shadow-sm text-center border-top-accent">
                <i class="bi bi-phone text-accent fs-1 mb-2"></i>
                <h6 class="fw-800 text-main uppercase small">Digital First</h6>
                <p class="text-muted small mb-0">Access your historical records securely through our patient portal.</p>
            </div>
        </div>
    </div>

    {{-- 3. MOBILE APK CARD --}}
    <div class="card bg-brand-dark border-0 shadow-lg mb-4" style="border-radius: 20px;">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-md-8 text-start">
                    <h3 class="text-white fw-800 mb-2">Health tracking in your pocket.</h3>
                    <p class="text-white-50 mb-0">The Medscreen Android Experience is currently in internal beta testing. Real-time notifications and instant history access coming soon.</p>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <div class="d-inline-flex align-items-center gap-3 p-3 rounded-4 bg-black bg-opacity-25 border border-secondary border-opacity-25">
                        <i class="bi bi-android2 text-accent fs-2"></i>
                        <div class="text-start pe-3">
                            <div class="text-white fw-bold small">Android APK</div>
                            <span class="badge bg-secondary-subtle text-secondary fs-x-small">COMING SOON</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. CONTACT & MAP CARD (Seamless Split) --}}
    <div class="card border-0 shadow-lg overflow-hidden mb-4" style="border-radius: 20px; display: flex;">
        <div class="row g-0 flex-grow-1 align-items-stretch">
            {{-- Contact Information --}}
            <div class="col-lg-5 bg-brand-dark p-4 p-md-5 text-white text-start">
                <h3 class="fw-800 mb-5 tracking-tight">Get in Touch</h3>
                
                <div class="mb-4">
                    <label class="text-accent fs-x-small fw-800 uppercase d-block mb-1">Clinic Location</label>
                    <p class="fw-bold opacity-75 small">Atis St, General Santos City (Dadiangas),<br>9500 South Cotabato</p>
                </div>

                <div class="mb-4">
                    <label class="text-accent fs-x-small fw-800 uppercase d-block mb-1">Email Support</label>
                    <p class="fw-bold opacity-75 small">medscreen.lab@gmail.com</p>
                </div>

                <div class="mb-5">
                    <label class="text-accent fs-x-small fw-800 uppercase d-block mb-2">Follow Us</label>
                    <a href="https://web.facebook.com/medscreendiagnosticlab/" target="_blank" class="text-white text-decoration-none d-flex align-items-center gap-2 hover-accent small fw-bold">
                        <i class="bi bi-facebook fs-5 text-accent"></i> Medscreen Diagnostic Laboratory
                    </a>
                </div>

                <a href="{{ route('services.index') }}" class="btn-custom btn-outline-accent w-100 py-3">Browse Test Menu</a>
            </div>

            {{-- Seamless Google Map --}}
            <div class="col-lg-7 position-relative" style="min-height: 450px;">
                {{-- FIXED: Replaced platform-rejected expired GMB link with clean query-based embed URL --}}
                <iframe 
                    src="https://maps.google.com/maps?q=Atis%20Street%2C%20General%20Santos%20City%2C%20Philippines&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                    width="100%" height="100%" style="border:0; position: absolute; top: 0; left: 0;" allowfullscreen="" loading="lazy">
                </iframe>
            </div>
        </div>
    </div>

    {{-- FIXED: 5. SECURE ONLINE RESULTS VERIFICATION CARD (Now positioned at the very bottom) --}}
    <div class="card bg-brand-dark border-0 shadow-lg" style="border-radius: 20px;">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-md-8 text-start">
                    <h3 class="text-white fw-800 mb-2">Secure Online Result Verification</h3>
                    <p class="text-white-50 mb-0">Medscreen is equipped with secure cryptographic QR and ID verification. Third parties (such as employers, universities, or agencies) can instantly verify the authenticity of any clinical record.</p>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <a href="{{ route('result.verify-search') }}" class="btn-custom btn-accent px-5 py-3 text-decoration-none">
                        <i class="bi bi-shield-check-fill me-2"></i>VERIFY CLINICAL RECORD
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.border-top-accent { border-top: 4px solid var(--brand-accent) !important; }
.hover-accent:hover { color: var(--brand-accent) !important; }
</style>
@endsection