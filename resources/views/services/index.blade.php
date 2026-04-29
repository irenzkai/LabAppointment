@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-5">
    <div>
        <h2 class="text-neon fw-bold mb-0 uppercase">LABORATORY SERVICES</h2>
        <p class="text-secondary small">Quality and affordable diagnostic solutions</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('appointments.bulk') }}" class="btn-custom btn-outline-neon text-neon px-3">
            <i class="bi bi-people-fill me-2"></i> BULK BOOKING
        </a>

        {{-- Cart Button --}}
        <a href="{{ route('cart.index') }}" class="btn-custom btn-outline-neon position-relative px-4">
            <i class="bi bi-cart-check-fill me-2"></i> 
            MY LIST 
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-black" style="font-size: 0.6rem;">
                {{ count(session('cart', [])) }}
            </span>
        </a>
        
        @can('isStaff')
            <button class="btn-custom btn-neon px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-lg me-1"></i> ADD NEW
            </button>
        @endcan
    </div>
</div>

{{-- Category Navigation --}}
<ul class="nav nav-pills mb-5 border-bottom border-secondary pb-3" id="serviceTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active text-white small fw-bold uppercase px-4" data-bs-toggle="pill" data-bs-target="#tab-individual">INDIVIDUAL TESTS</button>
    </li>
    <li class="nav-item">
        <button class="nav-link text-white small fw-bold uppercase px-4" data-bs-toggle="pill" data-bs-target="#tab-package">TEST PACKAGES</button>
    </li>
</ul>

<div class="tab-content" id="serviceTabsContent">
    @foreach(['individual', 'package'] as $cat)
    <div class="tab-pane fade {{ $cat == 'individual' ? 'show active' : '' }}" id="tab-{{ $cat }}">
        <div class="row g-4 text-start">
            @php $filtered = $services->where('category', $cat); @endphp
            
            @forelse($filtered as $service)
            {{-- Logic: Staff sees everything, Patients/Guests only see enabled tests --}}
            @if((Auth::check() && Auth::user()->isStaff()) || $service->is_available)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-lg {{ !$service->is_available ? 'opacity-50 border-secondary' : 'border-neon' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="text-white fw-bold mb-0 small">{{ strtoupper($service->name) }}</h5>
                            <span class="text-neon fw-bold" style="font-size: 0.9rem;">₱{{ number_format($service->price, 2) }}</span>
                        </div>

                        {{-- Gender Restriction Badge --}}
                        <div class="mb-3">
                            @if($service->gender_restriction == 'male')
                                <span class="badge border border-info text-info smaller"><i class="bi bi-gender-male me-1"></i>MALE ONLY</span>
                            @elseif($service->gender_restriction == 'female')
                                <span class="badge border border-pink text-pink smaller" style="color: #ff69b4; border-color: #ff69b4;"><i class="bi bi-gender-female me-1"></i>FEMALE ONLY</span>
                            @else
                                <span class="badge border border-secondary text-white smaller">ALL GENDERS</span>
                            @endif
                        </div>
                        
                        <p class="smaller text-white mb-4" style="min-height: 70px;">{{ $service->description }}</p>
                        
                        <div class="p-2 bg-black rounded border border-secondary mb-2" style="min-height: 90px;">
                            <label class="text-neon fw-bold mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">PREPARATION REQUIRED:</label>
                            <p class="text-white smaller mb-0">{{ $service->preparation }}</p>
                        </div>

                        <div class="row g-0 py-2 border-top border-bottom border-secondary border-opacity-10">
                            <!-- Left Section: Sample Required -->
                            <div class="col-6 border-end border-secondary border-opacity-25 text-center">
                                <div class="smaller text-white uppercase">
                                    <i class="bi bi-droplet-fill text-danger me-1"></i> Sample Required:
                                </div>
                                <div class="text-white small fw-bold">
                                    {{ $service->sample_required ?? 'N/A' }}
                                </div>
                            </div>

                            <!-- Right Section: Procedure Time -->
                            <div class="col-6 text-center">
                                <div class="smaller text-white uppercase">
                                    <i class="bi bi-clock-history text-info me-1"></i> Procedure Time:
                                </div>
                                <div class="text-white small fw-bold">
                                    {{ $service->formatted_time }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer border-0 pb-4 px-3">
                        @auth
                            @if(Auth::user()->role == 'user')
                                @php 
                                    $genderMismatch = ($service->gender_restriction !== 'both' && Auth::user()->sex !== ucfirst($service->gender_restriction));
                                    $alreadyInCart = isset(session('cart')[$service->id]);
                                @endphp

                                @if($genderMismatch)
                                    <button class="btn-danger-custom btn-outline-danger w-100 py-2 disabled" style="font-size: 0.65rem;">GENDER RESTRICTED</button>
                                @else
                                    <form action="{{ route('cart.add', $service->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn-custom {{ $alreadyInCart ? 'btn-outline-neon' : 'btn-neon' }} w-100 py-2 fw-bold">
                                            {{ $alreadyInCart ? 'ADDED TO LIST' : 'ADD TO LIST' }}
                                        </button>
                                    </form>
                                @endif
                            @else
                                {{-- Staff Controls --}}
                                <div class="d-flex gap-2">
                                    <button class="btn-custom btn-outline-neon flex-grow-1 fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#editModal{{$service->id}}">EDIT</button>
                                    <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-custom w-100 btn-sm fw-bold {{ $service->is_available ? 'btn-custom btn-neon' : 'btn-outline-neon' }}">
                                            {{ $service->is_available ? 'OFF' : 'ON' }}
                                        </button>
                                    </form>
                                    <button class="btn-custom btn-danger-custom btn-sm" data-bs-toggle="modal" data-bs-target="#delModal{{$service->id}}"><i class="bi bi-trash"></i></button>
                                </div>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-custom btn-outline-neon w-100 py-2 text-center fw-bold">LOGIN TO BOOK</a>
                        @endauth
                    </div>
                </div>
            </div>
            @endif
            @empty
                <div class="col-12 py-5 text-center text-secondary">No services available in this category.</div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

{{-- --- MODALS --- --}}

{{-- Add Service Modal --}}
@can('isStaff')
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('services.store') }}" method="POST" class="modal-content border-neon bg-black shadow-lg">
            @csrf
            <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold">CREATE NEW SERVICE</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 text-start">
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Service Name</label><input type="text" name="name" class="form-control" placeholder="e.g. CBC" required></div>
                <div class="row g-2">
                    <div class="col-md-6 mb-3"><label class="text-secondary small fw-bold uppercase">Price (₱)</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="text-secondary small fw-bold uppercase">Category</label><select name="category" class="form-select"><option value="individual">Individual</option><option value="package">Package</option></select></div>
                </div>
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Gender Rule</label><select name="gender_restriction" class="form-select"><option value="both">Both (All)</option><option value="male">Male Only</option><option value="female">Female Only</option></select></div>
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Description</label><textarea name="description" class="form-control" rows="2" required></textarea></div>
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Prep Requirements</label><textarea name="preparation" class="form-control" rows="2" required></textarea></div>
                <div class="col-12 mb-3">
                    <label class="text-secondary small fw-bold uppercase d-block mb-2">Samples Required</label>
                    
                    <div class="p-3 form-control border border-secondary">
                        {{-- Container for Checkboxes (Default & Saved ones) --}}
                        <div id="sample-container-{{ $service->id ?? 'new' }}" class="d-flex flex-wrap gap-3 mb-3">
                            @php 
                                $defaults = ['Blood', 'Urine', 'Stool', 'Swab', 'N/A'];
                                $currentSamples = isset($service) ? explode(',', $service->sample_required) : ['Blood'];
                            @endphp

                            {{-- 1. Render Defaults --}}
                            @foreach($defaults as $sample)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $sample }}" 
                                        id="check-{{ $sample }}-{{ $service->id ?? 'new' }}"
                                        {{ in_array($sample, $currentSamples) ? 'checked' : '' }}>
                                    <label class="form-check-label text-white" for="check-{{ $sample }}-{{ $service->id ?? 'new' }}">
                                        {{ $sample }}
                                    </label>
                                </div>
                            @endforeach

                            {{-- 2. Render Custom Saved Samples (if they aren't in defaults) --}}
                            @foreach($currentSamples as $current)
                                @if(!in_array($current, $defaults) && !empty($current))
                                    <div class="form-check d-flex align-items-center gap-2 custom-sample-item">
                                        <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $current }}" checked>
                                        <span class="text-neon fw-bold">{{ $current }}</span>
                                        <button type="button" class="btn btn-link text-danger p-0" onclick="this.parentElement.remove()"><i class="bi bi-x-circle"></i></button>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        {{-- 3. Add Custom Sample Input --}}
                        <div class="input-group input-group-sm" style="max-width: 300px;">
                            <input type="text" id="custom-input-{{ $service->id ?? 'new' }}" class="form-control bg-black border-secondary text-white" placeholder="New sample type...">
                            <button class="btn btn-outline-secondary" type="button" onclick="addCustomSample('{{ $service->id ?? 'new' }}')">
                                <i class="bi bi-plus-lg"></i> ADD
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Estimated Procedure Time</label><input type="number" name="estimated_time" class="form-control" placeholder="e.g. 30, 60"></div>
            </div>
            <div class="modal-footer border-neon bg-dark"><button type="submit" class="btn-custom btn-neon w-100 py-3">SAVE SERVICE</button></div>
        </form>
    </div>
</div>
@endcan

@foreach($services as $service)
    {{-- Edit Modal --}}
    @can('isStaff')
    <div class="modal fade" id="editModal{{$service->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('services.update', $service->id) }}" method="POST" class="modal-content border-neon bg-black">
                @csrf @method('PUT')
                <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold">EDIT: {{ strtoupper($service->name) }}</h5></div>
                <div class="modal-body p-4 text-start">
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Name</label><input type="text" name="name" class="form-control" value="{{ $service->name }}" required></div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3"><label class="text-secondary small fw-bold uppercase">Price (₱)</label><input type="number" step="0.01" name="price" class="form-control" value="{{ $service->price }}" required></div>
                        <div class="col-md-6 mb-3"><label class="text-secondary small fw-bold uppercase">Category</label><select name="category" class="form-select"><option value="individual" {{ $service->category == 'individual' ? 'selected' : '' }}>Individual</option><option value="package" {{ $service->category == 'package' ? 'selected' : '' }}>Package</option></select></div>
                    </div>
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Gender Rule</label><select name="gender_restriction" class="form-select"><option value="both" {{ $service->gender_restriction == 'both' ? 'selected' : '' }}>Both (All)</option><option value="male" {{ $service->gender_restriction == 'male' ? 'selected' : '' }}>Male Only</option><option value="female" {{ $service->gender_restriction == 'female' ? 'selected' : '' }}>Female Only</option></select></div>
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Description</label><textarea name="description" class="form-control" rows="2" required>{{ $service->description }}</textarea></div>
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Prep Requirements</label><textarea name="preparation" class="form-control" rows="2" required>{{ $service->preparation }}</textarea></div>
                    <div class="col-12 mb-3">
                        <label class="text-secondary small fw-bold uppercase d-block mb-2">Samples Required</label>
                        
                        <div class="p-3 form-control border border-secondary">
                            {{-- Container for Checkboxes (Default & Saved ones) --}}
                            <div id="sample-container-{{ $service->id ?? 'new' }}" class="d-flex flex-wrap gap-3 mb-3">
                                @php 
                                    $defaults = ['Blood', 'Urine', 'Stool', 'Swab', 'N/A'];
                                    $currentSamples = isset($service) ? explode(',', $service->sample_required) : ['Blood'];
                                @endphp

                                {{-- 1. Render Defaults --}}
                                @foreach($defaults as $sample)
                                    <div class="form-check">
                                        <input value="{{ $sample }}" class="form-check-input" type="checkbox" name="samples[]" 
                                            id="check-{{ $sample }}-{{ $service->id ?? 'new' }}"
                                            {{ in_array($sample, $currentSamples) ? 'checked' : '' }}>
                                        <label class="form-check-label text-white" for="check-{{ $sample }}-{{ $service->id ?? 'new' }}">
                                            {{ $sample }}
                                        </label>
                                    </div>
                                @endforeach

                                {{-- 2. Render Custom Saved Samples (if they aren't in defaults) --}}
                                @foreach($currentSamples as $current)
                                    @if(!in_array($current, $defaults) && !empty($current))
                                        <div class="form-check d-flex align-items-center gap-2 custom-sample-item">
                                            <input value="{{ $current }}" class="form-check-input" type="checkbox" name="samples[]" checked>
                                            <span class="text-neon fw-bold">{{ $current }}</span>
                                            <button type="button" class="btn btn-link text-danger p-0" onclick="this.parentElement.remove()"><i class="bi bi-x-circle"></i></button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- 3. Add Custom Sample Input --}}
                            <div class="input-group input-group-sm" style="max-width: 300px;">
                                <input type="text" id="custom-input-{{ $service->id ?? 'new' }}" class="form-control bg-black border-secondary text-white" placeholder="New sample type...">
                                <button class="btn btn-outline-secondary" type="button" onclick="addCustomSample('{{ $service->id ?? 'new' }}')">
                                    <i class="bi bi-plus-lg"></i> ADD
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Estimated Procedure Time</label><input type="number" name="estimated_time" class="form-control" value="{{ $service->estimated_time }}" placeholder="e.g. 30, 60"></div>
                </div>
                <div class="modal-footer border-neon bg-dark"><button type="submit" class="btn-custom btn-neon w-100 py-3">UPDATE DETAILS</button></div>
            </form>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade" id="delModal{{$service->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-danger bg-black text-center p-4">
                <i class="bi bi-trash3 text-danger fs-1 mb-2"></i>
                <h6 class="text-white fw-bold">REMOVE SERVICE?</h6>
                <p class="text-secondary smaller">Deleting <strong>{{ $service->name }}</strong> is permanent.</p>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn-custom btn-outline-neon flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <form action="{{ route('services.destroy', $service->id) }}" method="POST" class="flex-grow-1">
                        @csrf @method('DELETE')
                        <button class="btn-custom btn-danger-custom w-100">DELETE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endcan
@endforeach
<script>
    function addCustomSample(id) {
        const input = document.getElementById(`custom-input-${id}`);
        const container = document.getElementById(`sample-container-${id}`);
        const val = input.value.trim();

        if (val === '') return;

        // Create a new checkbox item on the fly
        const div = document.createElement('div');
        div.className = 'form-check d-flex align-items-center gap-2 custom-sample-item';
        div.innerHTML = `
            <input class="form-check-input" type="checkbox" name="samples[]" value="${val}" checked>
            <span class="text-neon fw-bold">${val}</span>
            <button type="button" class="btn btn-link text-danger p-0" onclick="this.parentElement.remove()">
                <i class="bi bi-x-circle"></i>
            </button>
        `;

        container.appendChild(div);
        input.value = ''; // Clear input
    }
</script>
@endsection