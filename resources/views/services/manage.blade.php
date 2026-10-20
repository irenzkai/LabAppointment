@extends('layouts.app')

@section('title', 'Manage Services')

@section('content')
<div class="container text-start animate-page py-2 px-3 px-sm-4" id="manage-services-root">
 
    {{-- Header --}}
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 mb-sm-5 border-bottom border-secondary border-opacity-25 pb-3 pb-sm-4" style="border-color: var(--border-color) !important;">
        <div class="me-sm-3 flex-grow-1">
            <h2 class="text-accent fw-800 mb-1 uppercase tracking-tight fs-3 fs-md-2" style="letter-spacing: 1px;">
                <i class="bi bi-list-stars me-2"></i>Manage Laboratory Services
            </h2>
            <p class="text-muted mb-0 small text-break">Create new diagnostic tests, edit pricing, toggle availability, or manage archives.</p>
        </div>
        <div class="flex-shrink-0 add-service-btn-box">
            <button class="btn-custom btn-accent px-3.5 px-sm-4 py-2 shadow-sm text-nowrap d-inline-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#addServiceModal" style="font-size: 0.78rem;">
                <i class="bi bi-plus-lg me-2"></i> CREATE NEW SERVICE
            </button>
        </div>
    </div>

    {{-- Category Tabs --}}
    <ul class="nav nav-pills mb-4 gap-2 flex-wrap" id="serviceTabs" role="tablist">
        <li class="nav-item flex-grow-1 flex-sm-grow-0">
            <button class="nav-link active fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-individual">
                Individual Tests
            </button>
        </li>
        <li class="nav-item flex-grow-1 flex-sm-grow-0">
            <button class="nav-link fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-package">
                Health Packages
            </button>
        </li>
        @if(count($archivedServices) > 0)
            <li class="nav-item ms-sm-auto flex-grow-1 flex-sm-grow-0">
                <button class="nav-link fs-x-small fw-800 uppercase px-3 px-sm-4 py-2 text-warning border-warning w-100 text-center" data-bs-toggle="pill" data-bs-target="#tab-archive">
                    <i class="bi bi-archive me-1"></i> Archived Services ({{ count($archivedServices) }})
                </button>
            </li>
        @endif
    </ul>

    {{-- Listings --}}
    <div class="tab-content" id="serviceTabsContent">
        @foreach(['individual', 'package'] as $cat)
            <div class="tab-pane fade {{ $cat == 'individual' ? 'show active' : '' }}" id="tab-{{ $cat }}">
                <div class="row g-3 g-sm-4">
                    @php $filtered = $services->where('category', $cat); @endphp
                    @forelse($filtered as $service)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm {{ !$service->is_available ? 'opacity-50 border-dashed' : 'border-light' }} bg-card">
                                <div class="card-body d-flex flex-column p-3 p-sm-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                                        <h5 class="text-main fw-800 mb-0 lh-sm text-break fs-6 fs-sm-5">{{ strtoupper($service->name) }}</h5>
                                        <div class="text-accent fw-800 fs-5 ps-2 text-nowrap">₱{{ number_format($service->price, 2) }}</div>
                                    </div>

                                    <div class="mb-3 d-flex flex-wrap gap-1.5">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.7rem;">
                                            {{ strtoupper($service->gender_restriction) }}
                                        </span>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold text-nowrap" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock me-1"></i>{{ $service->formatted_time }}
                                        </span>
                                    </div>

                                    <p class="text-muted small flex-grow-1 mb-4 text-break">{{ $service->description }}</p>

                                    {{-- Management Action Buttons --}}
                                    <div class="mt-auto pt-3 border-top border-secondary border-opacity-10 d-flex gap-2">
                                        @if($service->is_available)
                                            <button class="btn btn-outline-secondary btn-sm flex-grow-1 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#editModal{{$service->id}}">
                                                EDIT
                                            </button>
                                            <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1 m-0">
                                                @csrf 
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold py-2">
                                                    DISABLE
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1 m-0">
                                                @csrf 
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-accent btn-sm w-100 fw-bold py-2">
                                                    ENABLE
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#delModal{{$service->id}}" title="Delete Service">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-4 p-3">
                            <i class="bi bi-flask fs-1 d-block mb-3 opacity-25"></i> No services logged.
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{-- Archived Tab --}}
        @if(count($archivedServices) > 0)
            <div class="tab-pane fade" id="tab-archive">
                <div class="row g-3 g-sm-4">
                    @foreach($archivedServices as $service)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-dashed border-warning opacity-75 bg-card">
                                <div class="card-body d-flex flex-column p-3 p-sm-4">
                                    <h5 class="text-warning fw-800 mb-2 text-break fs-6 fs-sm-5">{{ strtoupper($service->name) }}</h5>
                                    <p class="text-muted small flex-grow-1 mb-4 text-break">{{ $service->description }}</p>
                                    <form action="{{ route('services.restore', $service->id) }}" method="POST" class="w-100 m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning btn-sm w-100 fw-bold py-2">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> RESTORE
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

    {{-- Modals Inclusion --}}
    @include('services.partials.add-modal')

    @foreach($services as $service)
        @include('services.partials.modals', ['service' => $service])
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