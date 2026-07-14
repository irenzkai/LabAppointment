@extends('layouts.app')

@section('content')
<div class="container text-start animate-page">
    {{-- 1. HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-secondary border-opacity-25 pb-4">
        <div>
            <h2 class="text-main fw-800 mb-1 uppercase tracking-tight">Laboratory Services</h2>
            <p class="text-muted mb-0 small">Browse our comprehensive list of clinical examinations and diagnostic packages.</p>
        </div>
        <div class="d-flex gap-2">
            @can('isStaff')
            <button class="btn-custom btn-accent px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-lg me-2"></i> CREATE SERVICE
            </button>
            @endcan
            @if(Auth::check() && Auth::user()->isPatient())
            <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent px-4">
                <i class="bi bi-calendar-plus me-2"></i> BOOK APPOINTMENT
            </a>
            @endif
        </div>
    </div>

    {{-- 2. CATEGORY NAVIGATION TABS --}}
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

    {{-- 3. SERVICES LISTINGS --}}
    <div class="tab-content" id="serviceTabsContent">
        @foreach(['individual', 'package'] as $cat)
        <div class="tab-pane fade {{ $cat == 'individual' ? 'show active' : '' }}" id="tab-{{ $cat }}">
            <div class="row g-4">
                @php $filtered = $services->where('category', $cat); @endphp

                @forelse($filtered as $service)
                    {{-- Logic check: Staff sees everything; Patients see active only --}}
                    @if((Auth::check() && Auth::user()->isStaff()) || $service->is_available)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm {{ !$service->is_available ? 'opacity-50 grayscale border-dashed' : 'border-light' }}">
                            <div class="card-body d-flex flex-column p-4">
                                {{-- Name & Pricing --}}
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="text-main fw-800 mb-0 lh-sm">{{ strtoupper($service->name) }}</h5>
                                    <div class="text-accent fw-800 fs-5 ps-3"> {{ number_format($service->price, 2) }}</div>
                                </div>

                                {{-- Info Badges --}}
                                <div class="mb-3 d-flex flex-wrap gap-2">
                                    @if($service->gender_restriction == 'male')
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Male Only</span>
                                    @elseif($service->gender_restriction == 'female')
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Female Only</span>
                                    @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">All Genders</span>
                                    @endif

                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 small rounded uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
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

                                {{-- Staff Administration Panel --}}
                                @can('isStaff')
                                <div class="mt-auto pt-3 border-top border-secondary border-opacity-10 d-flex gap-2">
                                    @if($service->is_available)
                                        {{-- Actions for Available Services --}}
                                        <button class="btn btn-outline-secondary btn-sm flex-grow-1 fw-bold" data-bs-toggle="modal" data-bs-target="#editModal{{$service->id}}">
                                            EDIT
                                        </button>
                                        <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1 m-0">
                                            @csrf 
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold">
                                                DISABLE
                                            </button>
                                        </form>
                                    @else
                                        {{-- Actions for Disabled Services: ENABLE and DELETE --}}
                                        <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1 m-0">
                                            @csrf 
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-accent btn-sm w-100 fw-bold">
                                                ENABLE
                                            </button>
                                        </form>
                                        {{-- Triggers system delete confirmation modal --}}
                                        <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#delModal{{$service->id}}" title="Delete Service">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endif
                @empty
                <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-4">
                    <i class="bi bi-flask fs-1 d-block mb-3 opacity-25"></i>
                    No diagnostics services have been logged in this category.
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    {{-- 4. ADMINISTRATIVE MODALS --}}
    @can('isStaff')
        @foreach($services as $service)
            @include('services.partials.modals', ['service' => $service])
        @endforeach

        {{-- CREATE SERVICE MODAL --}}
        <div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form action="{{ route('services.store') }}" method="POST" class="modal-content border-secondary bg-card shadow-lg" id="addServiceForm">
                    @csrf
                    <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                        <h5 class="modal-title text-main fw-bold uppercase small">
                            <i class="bi bi-plus-circle me-2"></i>Create New Service
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 text-start">
                        
                        {{-- High-Contrast Validation Error Listing Container --}}
                        @if ($errors->any())
                        <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger small mb-4 p-3 rounded d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5 mt-0.5"></i>
                            <div>
                                <strong class="uppercase d-block mb-1 text-danger">Validation Failure</strong>
                                <ul class="mb-0 ps-3 text-secondary">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3">
                            {{-- Name --}}
                            <div class="col-12">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Service Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. FBS / RBS" value="{{ old('name') }}" required>
                            </div>

                            {{-- Price & Category --}}
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Price (PHP)</label>
                                <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" value="{{ old('price') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="individual" {{ old('category') == 'individual' ? 'selected' : '' }}>Individual Test</option>
                                    <option value="package" {{ old('category') == 'package' ? 'selected' : '' }}>Test Package</option>
                                </select>
                            </div>

                            {{-- Gender Restriction & Estimated Time --}}
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Gender Restriction</label>
                                <select name="gender_restriction" class="form-select" required>
                                    <option value="both" {{ old('gender_restriction') == 'both' ? 'selected' : '' }}>All Genders</option>
                                    <option value="male" {{ old('gender_restriction') == 'male' ? 'selected' : '' }}>Male Only</option>
                                    <option value="female" {{ old('gender_restriction') == 'female' ? 'selected' : '' }}>Female Only</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Est. Duration (Minutes)</label>
                                <input type="number" name="estimated_time" class="form-control" placeholder="e.g. 5" value="{{ old('estimated_time') }}" required>
                            </div>

                            {{-- Description --}}
                            <div class="col-12">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Describe the service..." required>{{ old('description') }}</textarea>
                            </div>

                            {{-- Preparation --}}
                            <div class="col-12">
                                <label class="small text-secondary fw-bold mb-1 uppercase">Preparation Instructions</label>
                                <textarea name="preparation" class="form-control" rows="2" placeholder="e.g. 8-10 hours fasting..." required>{{ old('preparation') }}</textarea>
                            </div>

                            {{-- Samples Required Section --}}
                            <div class="col-12">
                                <label class="small text-secondary fw-bold mb-2 uppercase d-block">Samples Required</label>
                                <div class="p-3 border border-secondary border-opacity-25 rounded" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                                    <div class="d-flex flex-wrap gap-3" id="sample-container-add">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="samples[]" value="Blood" id="add-check-Blood" {{ is_array(old('samples')) && in_array('Blood', old('samples')) ? 'checked' : '' }}>
                                            <label class="form-check-label text-main smaller" for="add-check-Blood">Blood</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="samples[]" value="Urine" id="add-check-Urine" {{ is_array(old('samples')) && in_array('Urine', old('samples')) ? 'checked' : '' }}>
                                            <label class="form-check-label text-main smaller" for="add-check-Urine">Urine</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="samples[]" value="Stool" id="add-check-Stool" {{ is_array(old('samples')) && in_array('Stool', old('samples')) ? 'checked' : '' }}>
                                            <label class="form-check-label text-main smaller" for="add-check-Stool">Stool</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="samples[]" value="Swab" id="add-check-Swab" {{ is_array(old('samples')) && in_array('Swab', old('samples')) ? 'checked' : '' }}>
                                            <label class="form-check-label text-main smaller" for="add-check-Swab">Swab</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="samples[]" value="N/A" id="add-check-NA" {{ is_array(old('samples')) && in_array('N/A', old('samples')) ? 'checked' : (old('samples') === null ? 'checked' : '') }}>
                                            <label class="form-check-label text-main smaller" for="add-check-NA">N/A</label>
                                        </div>
                                    </div>

                                    {{-- Custom Sample input --}}
                                    <div class="input-group input-group-sm mt-3" style="max-width: 300px;">
                                        <input type="text" id="custom-input-add" class="form-control bg-card text-main" placeholder="Add custom type...">
                                        <button class="btn btn-outline-accent" type="button" onclick="addCustomSample('add')">ADD</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">CREATE SERVICE</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</div>

<style>
.grayscale { filter: grayscale(1); }
.border-dashed { border-style: dashed !important; }

.nav-pills .nav-link {
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    transition: 0.3s;
}
.nav-pills .nav-link.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent);
}
</style>

@push('scripts')
<script>
@if ($errors->any())
    // Automatically re-open the creation modal if any validation errors are returned
    document.addEventListener('DOMContentLoaded', () => {
        const addModalEl = document.getElementById('addServiceModal');
        if (addModalEl) {
            const modal = new bootstrap.Modal(addModalEl);
            modal.show();
            
            // Highlight each invalid input inside the form
            @foreach ($errors->keys() as $key)
                const input = addModalEl.querySelector(`[name="{{ $key }}"]`);
                if (input) {
                    input.classList.add('is-invalid');
                }
            @endforeach
        }
    });
@endif
</script>
@endpush
@endsection