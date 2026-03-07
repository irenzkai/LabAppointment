@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-5">
    <div>
        <h2 class="text-neon fw-bold mb-0">LABORATORY SERVICES</h2>
        <p class="text-secondary small">Medscreen Quality & Affordable Tests</p>
    </div>
    @can('isStaff')
        <button class="btn-custom btn-neon px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="bi bi-plus-lg me-1"></i> ADD TEST
        </button>
    @endcan
</div>

<div class="row g-4">
    @foreach($services as $service)
        {{-- Logic: Staff sees everything, Patients/Guests only see enabled tests --}}
        @if((Auth::check() && Auth::user()->isStaff()) || $service->is_available)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow {{ !$service->is_available ? 'opacity-50 border-secondary' : 'border-neon' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="text-white fw-bold mb-0">{{ strtoupper($service->name) }}</h5>
                            <span class="btn-custom btn-outline-neon py-1 px-2 border-neon" style="font-size: 0.7rem; cursor: default;">
                                ₱{{ number_format($service->price, 2) }}
                            </span>
                        </div>
                        <p class="small text-secondary mb-4">{{ $service->description }}</p>
                        
                        <div class="p-2 bg-black rounded border border-secondary mb-2">
                            <label class="text-neon fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">PREPARATION:</label>
                            <p class="text-white-50 small mb-0">{{ $service->preparation }}</p>
                        </div>
                    </div>

                    <div class="card-footer bg-transparent border-0 pb-4 px-3">
                        @auth
                            @if(Auth::user()->role == 'user')
                                <button class="btn-custom btn-neon w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#bookModal{{$service->id}}">
                                    BOOK APPOINTMENT
                                </button>
                            @else
                                {{-- Staff/Admin Controls --}}
                                <div class="d-flex gap-2 text-center">
                                    <button class="btn-custom btn-outline-neon flex-grow-1 fw-bold" data-bs-toggle="modal" data-bs-target="#editModal{{$service->id}}">EDIT</button>
                                    <form action="{{ route('services.toggle', $service->id) }}" method="POST" class="flex-grow-1">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-custom w-100 fw-bold {{ $service->is_available ? 'border-warning text-warning' : 'btn-outline-neon' }}">
                                            {{ $service->is_available ? 'DISABLE' : 'ENABLE' }}
                                        </button>
                                    </form>
                                    <button class="btn-custom btn-danger-custom" data-bs-toggle="modal" data-bs-target="#delModal{{$service->id}}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-custom btn-outline-neon w-100 py-2 text-center fw-bold">LOGIN TO BOOK</a>
                        @endauth
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

{{-- --- MODALS SECTION --- --}}

{{-- Add Service Modal (Global) --}}
@can('isStaff')
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('services.store') }}" method="POST" class="modal-content border-neon bg-black">
            @csrf
            <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold">ADD NEW TEST</h5></div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Test Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Price (₱)</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Description</label><textarea name="description" class="form-control" rows="2" required></textarea></div>
                <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Preparation</label><textarea name="preparation" class="form-control" rows="2" required></textarea></div>
            </div>
            <div class="modal-footer border-neon bg-dark"><button type="submit" class="btn-custom btn-neon w-100 py-3">SAVE SERVICE</button></div>
        </form>
    </div>
</div>
@endcan

@foreach($services as $service)
    {{-- Booking Modal (User) --}}
    @auth
    <div class="modal fade" id="bookModal{{$service->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('appointments.store') }}" method="POST" class="modal-content border-neon bg-black">
                @csrf
                <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold">BOOK {{ strtoupper($service->name) }}</h5></div>
                <div class="modal-body p-4 text-white">
                    <input type="hidden" name="service_id" value="{{ $service->id }}">
                    <div class="row">
                        <div class="col-md-5 mb-4 border-end border-secondary">
                            <label class="fw-bold text-neon small mb-2 uppercase">1. Select Date</label>
                            <input type="date" name="appointment_date" class="form-control date-selector" 
                                   data-service="{{$service->id}}" required min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-7">
                            <label class="fw-bold text-neon small mb-2 uppercase">2. Select Time Slot</label>
                            <div class="row g-2 time-grid-{{$service->id}}" style="max-height: 250px; overflow-y: auto;">
                                @for($i = 0; $i < 24; $i++)
                                    @php $val = sprintf('%02d:00', $i); @endphp
                                    <div class="col-4 time-slot-item" data-hour="{{$i}}">
                                        <input type="radio" class="btn-check" name="time_slot" id="t{{$service->id}}_{{$i}}" value="{{$val}}" required>
                                        <label class="btn btn-outline-secondary w-100 btn-sm py-2 fw-bold" for="t{{$service->id}}_{{$i}}">
                                            {{ date('h:i A', strtotime($val)) }}
                                        </label>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-neon bg-dark"><button type="submit" class="btn-custom btn-neon w-100 py-3">CONFIRM RESERVATION</button></div>
            </form>
        </div>
    </div>
    @endauth

    {{-- Edit Service Modal (Staff) --}}
    @can('isStaff')
    <div class="modal fade" id="editModal{{$service->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('services.update', $service->id) }}" method="POST" class="modal-content border-neon bg-black">
                @csrf @method('PUT')
                <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold uppercase">Edit {{ $service->name }}</h5></div>
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Name</label><input type="text" name="name" class="form-control" value="{{ $service->name }}" required></div>
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Price</label><input type="number" step="0.01" name="price" class="form-control" value="{{ $service->price }}" required></div>
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Description</label><textarea name="description" class="form-control" rows="2" required>{{ $service->description }}</textarea></div>
                    <div class="mb-3"><label class="text-secondary small fw-bold uppercase">Preparation</label><textarea name="preparation" class="form-control" rows="2" required>{{ $service->preparation }}</textarea></div>
                </div>
                <div class="modal-footer border-neon bg-dark"><button type="submit" class="btn-custom btn-neon w-100 py-3">UPDATE SERVICE</button></div>
            </form>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade" id="delModal{{$service->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-danger bg-black text-center p-4">
                <i class="bi bi-exclamation-octagon text-danger fs-1 mb-2"></i>
                <h6 class="text-white fw-bold">DELETE SERVICE?</h6>
                <p class="text-secondary smaller">Deleting <strong>{{ $service->name }}</strong> is permanent.</p>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn-custom btn-outline-neon flex-grow-1" data-bs-dismiss="modal">NO</button>
                    <form action="{{ route('services.destroy', $service->id) }}" method="POST" class="flex-grow-1">
                        @csrf @method('DELETE')
                        <button class="btn-custom btn-danger-custom w-100">YES</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endcan
@endforeach

@push('scripts')
<script>
    // past hour logic
    document.querySelectorAll('.date-selector').forEach(input => {
        input.addEventListener('change', function() {
            const serviceId = this.dataset.service;
            const selectedDate = this.value;
            const today = new Date().toISOString().split('T')[0];
            const currentHour = new Date().getHours();

            document.querySelectorAll(`.time-grid-${serviceId} .time-slot-item`).forEach(item => {
                const slotHour = parseInt(item.dataset.hour);
                if (selectedDate === today && slotHour <= currentHour) {
                    item.style.display = 'none';
                    item.querySelector('input').disabled = true;
                } else {
                    item.style.display = 'block';
                    item.querySelector('input').disabled = false;
                }
            });
        });
    });
</script>
@endpush
@endsection