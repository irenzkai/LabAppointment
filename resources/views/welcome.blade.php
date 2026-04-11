@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<div class="card p-0 mb-5 border-0 shadow-lg position-relative overflow-hidden" style="min-height: 400px; border-radius: 20px;">
    <!-- Background Image with Overlay -->
    <div class="welcome-img"></div>
    
    <!-- Hero Content -->
    <div class="position-relative d-flex flex-column align-items-center justify-content-center text-center p-5 h-100" style="z-index: 2; min-height: 400px;">
        <h1 class="display-4 fw-bold text-neon mb-3">Medscreen Diagnostic Laboratory</h1>
        <p class="fs-5 text-white mb-5" style="max-width: 650px;">
            We aim to provide our patients quality and affordable laboratory services.
        </p>
        <!-- Single Main Button -->
        <a href="{{ route('register') }}" class="btn-custom btn-neon px-5 py-3 fs-6">GET STARTED</a>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Contact & App Column -->
    <div class="col-md-5">
        <div class="card p-4 h-100 shadow-sm">
            <h5 class="text-neon fw-bold mb-4 border-bottom border-secondary pb-2">CONTACT INFORMATION</h5>
            
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-envelope-fill text-neon me-3 fs-5"></i>
                <span class="text-white">medscreen.lab@gmail.com</span>
            </div>

            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-facebook text-neon me-3 fs-5"></i>
                <a href="https://web.facebook.com/medscreendiagnosticlab/?_rdc=1&_rdr#" target="_blank" class="text-white hover-neon">Medscreen Diagnostic Laboratory</a>
            </div>

            <div class="d-flex align-items-start mb-4">
                <i class="bi bi-geo-alt-fill text-neon me-3 fs-5"></i>
                <span class="text-white small">Atis St, General Santos City (Dadiangas),<br>9500 South Cotabato</span>
            </div>

            <!-- APK Placeholder -->
            <div class="mt-auto pt-4 border-top border-secondary">
                <div class="bg-black p-3 rounded d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-white fw-bold mb-0 small">Download Mobile App</p>
                        <p class="text-secondary mb-0" style="font-size: 0.7rem;">Direct APK Installation</p>
                    </div>
                    <a href="#" class="btn-custom btn-outline-neon btn-sm">STAY TUNED</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Column -->
    <div class="col-md-7">
        <div class="card p-4 h-100 shadow-sm">
            <h5 class="text-neon fw-bold mb-4 border-bottom border-secondary pb-2">OUR LOCATION</h5>
            <div class="rounded-3 overflow-hidden border border-secondary mb-3" style="height: 280px;">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126938.8359218086!2d125.10115024248404!3d6.112111534003441!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f33166d1f03531%3A0xf49d527f1ea60024!2sMedscreen%20Diagnostic%20Laboratory!5e0!3m2!1sen!2sph!4v1709500000000!5m2!1sen!2sph" 
                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                </iframe>
            </div>
            <a href="https://www.google.com/maps/place/Medscreen+Diagnostic+Laboratory/data=!4m2!3m1!1s0x0:0xf49d527f1ea60024?sa=X&ved=1t:2428&ictx=111" 
               target="_blank" class="btn-custom btn-outline-neon w-100 py-2">
                <i class="bi bi-map-fill me-2"></i>OPEN IN GOOGLE MAPS
            </a>
        </div>
    </div>
</div>

<style>
    .hover-neon:hover { color: var(--neon) !important; }
</style>
@endsection