<!-- resources/views/services/manage.blade.php -->
@extends('layouts.app')

@section('title', 'Manage Services & Packages')

@section('content')
<div class="container text-start animate-page py-2 px-3 px-sm-4" id="manage-services-root">
 
    {{-- Header --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 mb-sm-5 border-bottom border-secondary border-opacity-25 pb-3 pb-sm-4" style="border-color: var(--border-color) !important;">
        <div class="me-sm-3 flex-grow-1">
            <h2 class="text-accent fw-800 mb-1 uppercase tracking-tight fs-3 fs-md-2" style="letter-spacing: 1px;">
                <i class="bi bi-list-stars me-2"></i>Manage Clinical Catalog
            </h2>
            <p class="text-muted mb-0 small text-break">Create new diagnostic tests, bundle health packages, edit pricing, toggle availability, or manage archives.</p>
        </div>
        <div class="flex-shrink-0 add-service-btn-box d-flex gap-2 flex-wrap">
            <button type="button" class="btn-custom btn-accent px-3.5 px-sm-4 py-2 shadow-sm text-nowrap d-inline-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#addServiceModal" style="font-size: 0.78rem;">
                <i class="bi bi-plus-lg me-1.5"></i> NEW INDIVIDUAL TEST
            </button>
            <button type="button" class="btn-custom btn-outline-accent px-3.5 px-sm-4 py-2 shadow-sm text-nowrap d-inline-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#addPackageModal" style="font-size: 0.78rem;">
                <i class="bi bi-box-seam me-1.5"></i> NEW PACKAGE
            </button>
        </div>
    </div>

    {{-- Category Tabs --}}
    <ul class="nav nav-pills mb-4 gap-2 flex-wrap" id="serviceTabs" role="tablist">
        <li class="nav-item flex-grow-1 flex-sm-grow-0">
            <button class="nav-link active fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-individual" type="button" role="tab" aria-controls="tab-individual" aria-selected="true">
                <i class="bi bi-flask me-1.5"></i> Individual Tests ({{ $services->count() }})
            </button>
        </li>
        <li class="nav-item flex-grow-1 flex-sm-grow-0">
            <button class="nav-link fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-package" type="button" role="tab" aria-controls="tab-package" aria-selected="false">
                <i class="bi bi-box-seam me-1.5"></i> Health Packages ({{ $packages->count() }})
            </button>
        </li>
        @if($archivedServices->isNotEmpty() || $archivedPackages->isNotEmpty())
            <li class="nav-item ms-sm-auto flex-grow-1 flex-sm-grow-0">
                <button class="nav-link fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 text-warning border-warning w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-archive" type="button" role="tab" aria-controls="tab-archive" aria-selected="false">
                    <i class="bi bi-archive me-1"></i> Archived Items ({{ $archivedServices->count() + $archivedPackages->count() }})
                </button>
            </li>
        @endif
    </ul>

    {{-- Listings --}}
    <div class="tab-content" id="serviceTabsContent">
        {{-- ========================================================================= --}}
        {{-- 1. INDIVIDUAL TESTS MANAGEMENT --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade show active" id="tab-individual" role="tabpanel">
            <div class="row g-3 g-sm-4">
                @forelse($services as $service)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm {{ !$service->is_available ? 'opacity-75 border-dashed' : 'border' }} bg-card" style="border-color: var(--border-color) !important; border-radius: 14px;">
                            <div class="card-body d-flex flex-column p-3 p-sm-4">
                                {{-- Name & Pricing --}}
                                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                    <h5 class="text-main fw-800 mb-0 lh-sm text-break fs-6 fs-sm-5">{{ strtoupper($service->name) }}</h5>
                                    <div class="text-accent fw-800 fs-5 ps-2 text-nowrap">₱{{ number_format($service->price, 2) }}</div>
                                </div>

                                {{-- Badges --}}
                                <div class="mb-3 d-flex flex-wrap gap-1">
                                    {{-- Active State Badge --}}
                                    <span class="badge {{ $service->is_available ? 'bg-success bg-opacity-15 text-accent border border-success border-opacity-30' : 'bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25' }} px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                        {{ $service->is_available ? 'ACTIVE' : 'DISABLED' }}
                                    </span>

                                    {{-- Gender Restriction Badge --}}
                                    @if($service->gender_restriction === 'male')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-gender-male me-1"></i>MALE ONLY
                                        </span>
                                    @elseif($service->gender_restriction === 'female')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-gender-female me-1"></i>FEMALE ONLY
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-people me-1"></i>ALL GENDERS
                                        </span>
                                    @endif

                                    {{-- Specimen Badge --}}
                                    @if($service->sample_required && $service->sample_required !== 'N/A')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-droplet-half text-danger me-1"></i>{{ $service->sample_required }}
                                        </span>
                                    @endif

                                    {{-- Turnaround Time Badge --}}
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $service->formatted_time }}
                                    </span>

                                    {{-- In Bundles Count --}}
                                    @if($service->packages->count() > 0)
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-box-seam me-1"></i>IN {{ $service->packages->count() }} {{ Str::plural('PACKAGE', $service->packages->count()) }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Description --}}
                                <p class="text-muted small flex-grow-1 mb-3 text-break" style="line-height: 1.5;">
                                    {{ $service->description }}
                                </p>

                                {{-- Preparation Instructions --}}
                                @if(!empty($service->preparation))
                                    <div class="alert-clinical d-flex flex-column mb-3 text-break p-2 rounded-3">
                                        <label class="text-accent fw-800 fs-x-small uppercase mb-0.5" style="font-size: 0.65rem;">Preparation Required:</label>
                                        <span class="text-main small" style="font-size: 0.75rem;">{{ $service->preparation }}</span>
                                    </div>
                                @endif

                                {{-- Management Action Buttons --}}
                                <div class="mt-auto pt-3 border-top border-secondary border-opacity-10 d-flex gap-2">
                                    @if($service->is_available)
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $service->id }}">
                                            <i class="bi bi-pencil-square"></i> EDIT
                                        </button>
                                        <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1 m-0">
                                            @csrf 
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1">
                                                <i class="bi bi-slash-circle"></i> DISABLE
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1 m-0">
                                            @csrf 
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-accent btn-sm w-100 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1">
                                                <i class="bi bi-check-circle"></i> ENABLE
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#delModal{{ $service->id }}" title="Archive Service">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-4 p-4">
                        <i class="bi bi-flask fs-1 d-block mb-3 opacity-25"></i> No individual diagnostic services recorded.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 2. HEALTH PACKAGES MANAGEMENT --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade" id="tab-package" role="tabpanel">
            <div class="row g-3 g-sm-4">
                @forelse($packages as $package)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm {{ !$package->is_available ? 'opacity-75 border-dashed' : 'border' }} bg-card" style="border-color: var(--border-color) !important; border-radius: 14px;">
                            <div class="card-body d-flex flex-column p-3 p-sm-4">
                                {{-- Package Badge & Name --}}
                                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                    <div>
                                        <h5 class="text-main fw-800 mb-0 lh-sm text-break fs-6 fs-sm-5">{{ strtoupper($package->name) }}</h5>
                                    </div>
                                    <div class="text-accent fw-800 fs-5 ps-2 text-nowrap">₱{{ number_format($package->price, 2) }}</div>
                                </div>

                                {{-- Badges --}}
                                <div class="mb-3 d-flex flex-wrap gap-1">
                                    {{-- Active State Badge --}}
                                    <span class="badge {{ $package->is_available ? 'bg-success bg-opacity-15 text-accent border border-success border-opacity-30' : 'bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25' }} px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                        {{ $package->is_available ? 'ACTIVE' : 'DISABLED' }}
                                    </span>

                                    {{-- Gender Restriction Badge --}}
                                    @if($package->gender_restriction === 'male')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-gender-male me-1"></i>MALE ONLY
                                        </span>
                                    @elseif($package->gender_restriction === 'female')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-gender-female me-1"></i>FEMALE ONLY
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-people me-1"></i>ALL GENDERS
                                        </span>
                                    @endif

                                    {{-- Specimen Required Badge --}}
                                    @if($package->sample_required && $package->sample_required !== 'N/A')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-droplet-half text-danger me-1"></i>{{ $package->sample_required }}
                                        </span>
                                    @endif

                                    {{-- Turnaround Time Badge --}}
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $package->formatted_time }}
                                    </span>

                                    {{-- Private Org Badge --}}
                                    @if($package->isCustomOrgPackage())
                                        <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-30 px-2 py-1 small rounded uppercase fw-bold" style="font-size: 0.65rem;">
                                            <i class="bi bi-building me-1"></i>{{ $package->organization->organization_name ?? 'PRIVATE ORG' }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Description --}}
                                @if(!empty($package->description))
                                    <p class="text-muted small mb-3 text-break" style="line-height: 1.5;">
                                        {{ $package->description }}
                                    </p>
                                @endif

                                {{-- Bundled Services Matrix --}}
                                @if($package->services && $package->services->isNotEmpty())
                                    <div class="mb-3 rounded border border-secondary border-opacity-15 flex-grow-1"
                                        style="background-color: rgba(25, 211, 140, 0.03); padding: 0.875rem;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="text-accent fw-800 fs-x-small uppercase mb-0"
                                                style="font-size: 0.68rem; letter-spacing: 0.5px;">
                                                <i class="bi bi-check2-all me-1"></i>Includes {{ $package->services->count() }} Tests:
                                            </label>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @foreach($package->services as $svc)
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0.5 rounded fw-semibold text-wrap text-start"
                                                    style="font-size: 0.68rem;">
                                                    {{ strtoupper($svc->name) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif   

                                {{-- Management Action Buttons --}}
                                <div class="mt-auto pt-3 border-top border-secondary border-opacity-10 d-flex gap-2">
                                    @if($package->is_available)
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#editPackageModal{{ $package->id }}">
                                            <i class="bi bi-pencil-square"></i> EDIT
                                        </button>
                                        <form action="{{ route('packages.toggle', $package->id) }}" method="POST" class="flex-grow-1 m-0">
                                            @csrf 
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1">
                                                <i class="bi bi-slash-circle"></i> DISABLE
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('packages.toggle', $package->id) }}" method="POST" class="flex-grow-1 m-0">
                                            @csrf 
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-accent btn-sm w-100 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1">
                                                <i class="bi bi-check-circle"></i> ENABLE
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#delPackageModal{{ $package->id }}" title="Archive Package">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-4 p-4">
                        <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-25"></i> No diagnostic packages created yet.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 3. ARCHIVED SERVICES & PACKAGES TAB --}}
        {{-- ========================================================================= --}}
        @if($archivedServices->isNotEmpty() || $archivedPackages->isNotEmpty())
            <div class="tab-pane fade" id="tab-archive" role="tabpanel">
                {{-- Archived Individual Tests --}}
                @if($archivedServices->isNotEmpty())
                    <div class="mb-5">
                        <h5 class="text-warning fw-800 uppercase mb-3 d-flex align-items-center gap-2 fs-6">
                            <i class="bi bi-flask"></i> Archived Individual Tests ({{ $archivedServices->count() }})
                        </h5>
                        <div class="row g-3 g-sm-4">
                            @foreach($archivedServices as $service)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100 shadow-sm border-dashed border-warning opacity-75 bg-card" style="border-radius: 14px;">
                                        <div class="card-body d-flex flex-column p-3 p-sm-4">
                                            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                                <h5 class="text-warning fw-800 mb-0 text-break fs-6">{{ strtoupper($service->name) }}</h5>
                                                <div class="text-muted fw-bold small text-nowrap">₱{{ number_format($service->price, 2) }}</div>
                                            </div>
                                            <p class="text-muted small flex-grow-1 mb-3 text-break">{{ $service->description }}</p>
                                            <form action="{{ route('services.restore', $service->id) }}" method="POST" class="w-100 m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning btn-sm w-100 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1">
                                                    <i class="bi bi-arrow-counterclockwise"></i> RESTORE SERVICE
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Archived Health Packages --}}
                @if($archivedPackages->isNotEmpty())
                    <div class="mb-4">
                        <h5 class="text-warning fw-800 uppercase mb-3 d-flex align-items-center gap-2 fs-6">
                            <i class="bi bi-box-seam"></i> Archived Health Packages ({{ $archivedPackages->count() }})
                        </h5>
                        <div class="row g-3 g-sm-4">
                            @foreach($archivedPackages as $package)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100 shadow-sm border-dashed border-warning opacity-75 bg-card" style="border-radius: 14px;">
                                        <div class="card-body d-flex flex-column p-3 p-sm-4">
                                            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                                <h5 class="text-warning fw-800 mb-0 text-break fs-6">{{ strtoupper($package->name) }}</h5>
                                                <div class="text-muted fw-bold small text-nowrap">₱{{ number_format($package->price, 2) }}</div>
                                            </div>
                                            <p class="text-muted small flex-grow-1 mb-3 text-break">{{ $package->description }}</p>
                                            @if($package->services && $package->services->isNotEmpty())
                                                <div class="small text-muted mb-3" style="font-size: 0.72rem;">
                                                    <strong>Bundled:</strong> {{ $package->services->pluck('name')->implode(', ') }}
                                                </div>
                                            @endif
                                            <form action="{{ route('packages.restore', $package->id) }}" method="POST" class="w-100 m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning btn-sm w-100 fw-bold py-2 d-inline-flex align-items-center justify-content-center gap-1">
                                                    <i class="bi bi-arrow-counterclockwise"></i> RESTORE PACKAGE
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Individual Service Modals Inclusion --}}
    @include('services.partials.add-modal')

    @foreach($services as $service)
        @include('services.partials.modals', ['service' => $service])
    @endforeach

    {{-- Health Package Modals Inclusion --}}
    @includeIf('services.partials.add-package-modal', ['availableServices' => $services])

    @foreach($packages as $package)
        @includeIf('services.partials.package-modals', ['package' => $package, 'availableServices' => $services])
    @endforeach
</div>
@endsection

@push('styles')
<style>
/* Header Action Sizing */
.add-service-btn-box button {
    width: auto !important;
}

@media (max-width: 575.98px) {
    .add-service-btn-box {
        width: 100% !important;
    }
    .add-service-btn-box button {
        width: 100% !important;
        justify-content: center !important;
    }
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