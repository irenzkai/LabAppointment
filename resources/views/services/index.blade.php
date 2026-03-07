@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-white">Laboratory Services</h3>
    {{-- This check allows both Staff and Admin because of the Gate we defined earlier --}}
    @can('isStaff')
        <button class="btn btn-primary px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            + New Service
        </button>
    @endcan
</div>

<div class="row g-3">
    @foreach($services as $service)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-secondary {{ !$service->is_available ? 'opacity-50' : '' }}">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="text-white mb-0">{{ $service->name }}</h5>
                    <span class="badge bg-dark text-primary border border-primary fs-6">
                        ${{ number_format($service->price, 2) }}
                    </span>
                </div>
                
                <p class="text-secondary small">{{ $service->description }}</p>
                
                <div class="bg-black p-2 rounded mb-3 mt-auto">
                    <small class="text-info fw-bold d-block mb-1">Preparation Requirements:</small>
                    <p class="small text-white mb-0">{{ $service->preparation }}</p>
                </div>

                @if(Auth::user()->role == 'user')
                    <button class="btn btn-primary w-100 fw-bold" {{ !$service->is_available ? 'disabled' : '' }} 
                            data-bs-toggle="modal" data-bs-target="#bookModal{{$service->id}}">
                        {{ $service->is_available ? 'Book Appointment' : 'Currently Unavailable' }}
                    </button>
                @else
                    <div class="d-flex gap-2">
                        {{-- Toggle Button --}}
                        <form action="{{ url('services/'.$service->id.'/toggle') }}" method="POST" class="flex-grow-1">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-{{ $service->is_available ? 'warning' : 'success' }} w-100 fw-bold">
                                {{ $service->is_available ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                        
                        {{-- Edit Button (Triggers Edit Modal) --}}
                        <button class="btn btn-sm btn-outline-info fw-bold" data-bs-toggle="modal" data-bs-target="#editModal{{$service->id}}">
                            Edit
                        </button>

                        {{-- Delete Button --}}
                        <form action="{{ route('services.destroy', $service->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this service?')">Delete</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL: Edit Service (One for each service) -->
    <div class="modal fade" id="editModal{{$service->id}}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('services.update', $service->id) }}" method="POST" class="modal-content border-secondary">
                @csrf @method('PUT')
                <div class="modal-header border-secondary bg-dark">
                    <h5 class="modal-title text-white">Edit {{ $service->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-black">
                    <div class="mb-3">
                        <label class="text-secondary small">Service Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $service->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="text-secondary small">Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="{{ $service->price }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="text-secondary small">Description</label>
                        <textarea name="description" class="form-control" rows="2" required>{{ $service->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="text-secondary small">Preparation Requirements</label>
                        <textarea name="preparation" class="form-control" rows="2" required>{{ $service->preparation }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary bg-dark">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Update Service</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Booking (For Users) -->
    <div class="modal fade" id="bookModal{{$service->id}}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('appointments.store') }}" method="POST" class="modal-content border-secondary">
                @csrf
                <div class="modal-header border-secondary bg-dark">
                    <h5 class="modal-title text-white">Book {{ $service->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-black">
                    <input type="hidden" name="service_id" value="{{ $service->id }}">
                    <p class="text-secondary small">Please select your preferred date for the laboratory test.</p>
                    <div class="mb-3">
                        <label class="text-white small">Appointment Date</label>
                        <input type="date" name="appointment_date" class="form-control mt-1" required min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </div>
                </div>
                <div class="modal-footer border-secondary bg-dark">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Confirm Appointment</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>

<!-- MODAL: Add New Service (Global) -->
@can('isStaff')
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('services.store') }}" method="POST" class="modal-content border-secondary">
            @csrf
            <div class="modal-header border-secondary bg-dark text-white">
                <h5 class="modal-title">Create New Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-black">
                <div class="mb-3">
                    <label class="text-secondary small">Service Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Blood Chemistry" required>
                </div>
                <div class="mb-3">
                    <label class="text-secondary small">Price ($)</label>
                    <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                </div>
                <div class="mb-3">
                    <label class="text-secondary small">Description</label>
                    <textarea name="description" class="form-control" placeholder="Brief details about the test..." rows="2" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="text-secondary small">Preparation Requirements</label>
                    <textarea name="preparation" class="form-control" placeholder="e.g. 12 hours fasting required" rows="2" required></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary bg-dark">
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Service</button>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection