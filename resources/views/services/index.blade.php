@extends('layouts.app')
@section('title', 'Laboratory Services')

@section('content')
<div class="container text-start animate-page">
 
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-secondary border-opacity-25 pb-4">
        <div>
            <h2 class="text-main fw-800 mb-1 uppercase tracking-tight">Laboratory Services</h2>
            <p class="text-muted mb-0 small">Browse our comprehensive list of clinical examinations and diagnostic packages.</p>
        </div>
        <div>
            <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent px-4 py-2">
                <i class="bi bi-calendar-plus me-2"></i> BOOK APPOINTMENT
            </a>
        </div>
    </div>

    {{-- Category Navigation Tabs --}}
    <ul class="nav nav-pills mb-5 gap-2" id="serviceTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fs-x-small fw-800 uppercase px-4 py-2" data-bs-toggle="pill" data-bs-target="#tab-individual">
                Individual Tests
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fs-x-small fw-800 uppercase px-4 py-2" data-bs-toggle="pill" data-bs-target="#tab-package">
                Health Packages
            </button>
        </li>
    </ul>

    {{-- Services Listings --}}
    <div class="tab-content" id="serviceTabsContent">
        @foreach(['individual', 'package'] as $cat)
        <div class="tab-pane fade {{ $cat == 'individual' ? 'show active' : '' }}" id="tab-{{ $cat }}">
            <div class="row g-4">
                @php $filtered = $services->where('category', $cat); @endphp
                @forelse($filtered as $service)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-light bg-card">
                        <div class="card-body d-flex flex-column p-4">
                            {{-- Name & Pricing --}}
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="text-main fw-800 mb-0 lh-sm">{{ strtoupper($service->name) }}</h5>
                                <div class="text-accent fw-800 fs-5 ps-3">₱{{ number_format($service->price, 2) }}</div>
                            </div>

                            {{-- Info Badges --}}
                            <div class="mb-3 d-flex flex-wrap gap-2">
                                @if($service->gender_restriction == 'male')
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem;">Male Only</span>
                                @elseif($service->gender_restriction == 'female')
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem;">Female Only</span>
                                @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem;">All Genders</span>
                                @endif
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock me-1"></i>{{ $service->formatted_time }}
                                </span>
                            </div>

                            {{-- Description --}}
                            <p class="text-muted small flex-grow-1 mb-4">
                                {{ $service->description }}
                            </p>

                            {{-- Preparation Instructions --}}
                            <div class="alert-clinical d-flex flex-column mb-3">
                                <label class="text-accent fw-800 fs-x-small uppercase mb-1">Preparation Required:</label>
                                <span class="text-main small">{{ $service->preparation }}</span>
                            </div>

                            {{-- Booking CTA --}}
                            <a href="{{ route('appointments.create') }}" class="btn-custom btn-outline-accent w-100 py-2 mt-auto text-decoration-none">
                                Select & Book Test
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-4">
                    <i class="bi bi-flask fs-1 d-block mb-3 opacity-25"></i>
                    No diagnostics services available in this category.
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
<style>
/* High-Contrast Nav Pills Styling (Eliminates Default Bootstrap Blue) */
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