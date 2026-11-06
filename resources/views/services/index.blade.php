<!-- resources/views/services/index.blade.php -->
@extends('layouts.app')
@section('title', 'Laboratory Services & Packages')

@section('content')
<div class="container text-start animate-page py-2 px-3 px-sm-4" id="services-index-root">
    {{-- Header Section --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 mb-sm-5 border-bottom border-secondary border-opacity-25 pb-3 pb-sm-4" style="border-color: var(--border-color) !important;">
        <div class="me-sm-3 flex-grow-1">
            <h2 class="text-main fw-800 mb-1 uppercase tracking-tight fs-3 fs-md-2" style="letter-spacing: 1px;">Laboratory Services & Packages</h2>
            <p class="text-muted mb-0 small text-break">Browse our comprehensive catalog of standalone clinical examinations and discounted health bundles.</p>
        </div>
        <div class="flex-shrink-0 book-btn-box">
            <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent px-3.5 px-sm-4 py-2 text-decoration-none d-inline-flex align-items-center justify-content-center text-nowrap" style="font-size: 0.78rem;">
                <i class="bi bi-calendar-plus me-2"></i> BOOK APPOINTMENT
            </a>
        </div>
    </div>

    {{-- Category Navigation Tabs --}}
    <ul class="nav nav-pills mb-4 mb-sm-5 gap-2 flex-wrap" id="serviceTabs" role="tablist">
        <li class="nav-item flex-grow-1 flex-sm-grow-0">
            <button class="nav-link active fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-individual" type="button" role="tab" aria-controls="tab-individual" aria-selected="true">
                <i class="bi bi-flask-fill me-1.5"></i> Individual Tests ({{ $services->count() }})
            </button>
        </li>
        <li class="nav-item flex-grow-1 flex-sm-grow-0">
            <button class="nav-link fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-package" type="button" role="tab" aria-controls="tab-package" aria-selected="false">
                <i class="bi bi-box-seam-fill me-1.5"></i> Health Packages ({{ $packages->count() }})
            </button>
        </li>
    </ul>

    {{-- Tab Content Panes --}}
    <div class="tab-content" id="serviceTabsContent">
        {{-- ========================================================================= --}}
        {{-- 1. INDIVIDUAL TESTS TAB --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade show active" id="tab-individual" role="tabpanel">
            <div class="row g-3 g-sm-4">
                @forelse($services as $service)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border bg-card service-card" style="border-color: var(--border-color) !important; border-radius: 14px;">
                            <div class="card-body d-flex flex-column p-3 p-sm-4">
                                {{-- Name & Pricing --}}
                                <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                                    <h5 class="text-main fw-800 mb-0 lh-sm text-break fs-6 fs-sm-5">{{ strtoupper($service->name) }}</h5>
                                    <div class="text-accent fw-800 fs-5 ps-2 text-nowrap">₱{{ number_format($service->price, 2) }}</div>
                                </div>

                                {{-- Info Badges (Gender, Specimen, & Duration) --}}
                                <div class="mb-3 d-flex flex-wrap gap-1">
                                    {{-- Gender Restriction Badge --}}
                                    @if($service->gender_restriction === 'male')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-gender-male me-1"></i>Male Only
                                        </span>
                                    @elseif($service->gender_restriction === 'female')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-gender-female me-1"></i>Female Only
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-people me-1"></i>All Genders
                                        </span>
                                    @endif

                                    {{-- Specimen / Sample Required Badge --}}
                                    @if($service->sample_required && $service->sample_required !== 'N/A')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-droplet-half text-danger me-1"></i>{{ $service->sample_required }}
                                        </span>
                                    @endif

                                    {{-- Duration Badge --}}
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $service->formatted_time }}
                                    </span>
                                </div>

                                {{-- Description --}}
                                <p class="text-muted small flex-grow-1 mb-3 text-break" style="line-height: 1.5;">
                                    {{ $service->description }}
                                </p>

                                {{-- Preparation Instructions --}}
                                @if(!empty($service->preparation))
                                    <div class="alert-clinical d-flex flex-column mb-3 text-break p-2.5 rounded-3">
                                        <label class="text-accent fw-800 fs-x-small uppercase mb-1">
                                            <i class="bi bi-info-circle me-1"></i>Preparation Required:
                                        </label>
                                        <span class="text-main small" style="font-size: 0.78rem;">{{ $service->preparation }}</span>
                                    </div>
                                @endif

                                {{-- Booking CTA --}}
                                <a href="{{ route('appointments.create') }}" class="btn-custom btn-outline-accent w-100 py-2 mt-auto text-decoration-none text-center">
                                    Select & Book Test
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-4 p-4">
                        <i class="bi bi-flask fs-1 d-block mb-3 opacity-25"></i>
                        No individual diagnostic tests are currently available in the catalog.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 2. HEALTH PACKAGES TAB --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade" id="tab-package" role="tabpanel">
            <div class="row g-3 g-sm-4">
                @forelse($packages as $package)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border bg-card package-card position-relative" style="border-color: var(--border-color) !important; border-radius: 14px;">
                            <div class="card-body d-flex flex-column p-3 p-sm-4">
                                {{-- Name & Pricing --}}
                                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                    <div>
                                        <h5 class="text-main fw-800 mb-0 lh-sm text-break fs-6 fs-sm-5">{{ strtoupper($package->name) }}</h5>
                                    </div>
                                    <div class="text-accent fw-800 fs-5 ps-2 text-nowrap">₱{{ number_format($package->price, 2) }}</div>
                                </div>

                                {{-- Info Badges (Gender, Specimen, & Duration) --}}
                                <div class="mb-3 d-flex flex-wrap gap-1">
                                    {{-- Gender Restriction Badge --}}
                                    @if($package->gender_restriction === 'male')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-gender-male me-1"></i>Male Only
                                        </span>
                                    @elseif($package->gender_restriction === 'female')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-gender-female me-1"></i>Female Only
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-people me-1"></i>All Genders
                                        </span>
                                    @endif

                                    {{-- Biological Specimens Required Badge --}}
                                    @if($package->sample_required && $package->sample_required !== 'N/A')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                            <i class="bi bi-droplet-half text-danger me-1"></i>{{ $package->sample_required }}
                                        </span>
                                    @endif

                                    {{-- Duration Badge --}}
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.68rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $package->formatted_time }}
                                    </span>
                                </div>

                                {{-- Description --}}
                                @if(!empty($package->description))
                                    <p class="text-muted small mb-3 text-break" style="line-height: 1.5;">
                                        {{ $package->description }}
                                    </p>
                                @endif

                                {{-- Included Constituent Services Breakdown --}}
                                @if($package->services && $package->services->isNotEmpty())
                                <div class="mb-3 p-3 rounded border border-secondary border-opacity-15 flex-grow-1"
                                    style="background-color: rgba(25, 211, 140, 0.03); padding: 0.875rem;">
                                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                                        <label class="text-accent fw-800 fs-x-small uppercase mb-0"
                                            style="font-size: 0.68rem; letter-spacing: 0.5px;">
                                            <i class="bi bi-check2-all me-1"></i>Includes {{ $package->services->count() }} Tests:
                                        </label>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($package->services as $svc)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded fw-semibold text-wrap text-start"
                                            style="font-size: 0.68rem; line-height: 1.25;">
                                            {{ strtoupper($svc->name) }}
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif   

                                {{-- Preparation Instructions --}}
                                @if(!empty($package->preparation))
                                    <div class="alert-clinical d-flex flex-column mb-3 text-break p-2.5 rounded-3">
                                        <label class="text-accent fw-800 fs-x-small uppercase mb-1">
                                            <i class="bi bi-info-circle me-1"></i>Preparation Required:
                                        </label>
                                        <span class="text-main small" style="font-size: 0.78rem;">{{ $package->preparation }}</span>
                                    </div>
                                @endif

                                {{-- Booking CTA --}}
                                <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent w-100 py-2 mt-auto text-decoration-none text-center">
                                    Select & Book Package
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-4 p-4">
                        <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-25"></i>
                        No health diagnostic packages are currently available.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Header Action Sizing */
.book-btn-box a {
    width: auto !important;
}

@media (max-width: 575.98px) {
    .book-btn-box {
        width: 100% !important;
    }
    .book-btn-box a {
        width: 100% !important;
        justify-content: center !important;
    }
}

/* Card Subtle Hover Elevation */
.service-card, .package-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.service-card:hover, .package-card:hover {
    transform: translateY(-2px);
    border-color: var(--brand-accent) !important;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
}

/* High-Contrast Nav Pills Styling */
.nav-pills .nav-link {
    color: var(--text-muted) !important;
    border: 1.5px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    font-weight: 700 !important;
    transition: all 0.2s ease-in-out;
}
.nav-pills .nav-link:hover {
    color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
}
.nav-pills .nav-link.active,
.nav-pills .show > .nav-link {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 10px rgba(25, 211, 140, 0.2) !important;
}
</style>
@endpush