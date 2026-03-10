@extends('layouts.app')

@section('content')
<h2 class="text-neon fw-bold mb-4 uppercase">LABORATORY APPOINTMENTS</h2>

<div class="accordion" id="appAccordion">
    @forelse($appointments as $app)
    <div class="accordion-item border-secondary mb-3 bg-black rounded overflow-hidden shadow-lg">
        {{-- Accordion Header --}}
        <h2 class="accordion-header">
            <button class="accordion-button collapsed bg-black text-white py-4 px-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $app->id }}">
                <div class="row w-100 align-items-center g-0">
                    <div class="col-md-4 text-start">
                        <span class="text-neon fw-bold small">Appointment {{ $app->id }}</span>
                        <div class="fw-bold text-white small uppercase">
                            @if(Auth::user()->isStaff()) {{ $app->user->name }} @else {{ $app->services->count() }} TEST(S) SELECTED @endif
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="small">{{ $app->appointment_date->format('M d, Y') }}</div>
                        <div class="text-neon fw-bold small"><i class="bi bi-clock me-1"></i>{{ date('h:i A', strtotime($app->time_slot)) }}</div>
                    </div>
                    <div class="col-md-4 text-end d-flex justify-content-end align-items-center gap-3">
                        <span class="badge border py-2 px-3 {{ $app->status == 'pending' ? 'text-warning border-warning' : ($app->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}">
                            {{ strtoupper($app->status) }}
                        </span>
                        <i class="bi bi-chevron-down text-secondary"></i>
                    </div>
                </div>
            </button>
        </h2>

        {{-- Expanded Body --}}
        <div id="collapse{{ $app->id }}" class="accordion-collapse collapse" data-bs-parent="#appAccordion">
            <div class="accordion-body bg-black border-top border-secondary p-4 text-start">
                <div class="row g-4">
                    {{-- Left Side: Tests List --}}
                    <div class="col-md-7">
                        <h6 class="text-neon fw-bold mb-3 small uppercase">Included Laboratory Tests</h6>
                        <ul class="list-group list-group-flush border border-secondary rounded">
                            @foreach($app->services as $service)
                            <li class="list-group-item bg-black border-secondary text-white small d-flex justify-content-between">
                                <span>{{ strtoupper($service->name) }}</span>
                                <span class="text-secondary">₱{{ number_format($service->price, 2) }}</span>
                            </li>
                            @endforeach
                            <li class="list-group-item bg-dark border-secondary text-neon fw-bold d-flex justify-content-between">
                                <span>TOTAL AMOUNT</span>
                                <span>₱{{ number_format($app->totalPrice(), 2) }}</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Right Side: Notes & Actions --}}
                    <div class="col-md-5">
                        <h6 class="text-neon fw-bold mb-3 small uppercase">Details & Actions</h6>
                        @if($app->return_reason)
                            <div class="p-3 bg-dark border border-danger rounded mb-3">
                                <small class="text-danger fw-bold d-block mb-1">STAFF MESSAGE:</small>
                                <p class="text-white small mb-0">{{ $app->return_reason }}</p>
                            </div>
                        @endif

                        <div class="mt-4 pt-3 border-top border-secondary">
                            @can('isStaff')
                                @if($app->status == 'pending')
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="flex-grow-1">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button class="btn-custom btn-neon w-100">APPROVE</button>
                                        </form>
                                        <button class="btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{$app->id}}">RETURN</button>
                                    </div>
                                @endif
                            @endcan

                            @if($app->status == 'returned' && Auth::id() == $app->user_id)
                                <button class="btn-custom btn-neon w-100" data-bs-toggle="modal" data-bs-target="#resubmitModal{{$app->id}}">UPDATE & RESUBMIT</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card p-5 text-center text-secondary border-secondary">No appointment records found.</div>
    @endforelse
</div>

{{-- MODALS OUTSIDE ACCORDION TO PREVENT TYPING ISSUES --}}
@foreach($appointments as $app)
    @can('isStaff')
    <div class="modal fade" id="retModal{{$app->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content border-danger bg-black">
                @csrf @method('PATCH')
                <div class="modal-header border-danger bg-dark"><h5 class="modal-title text-danger fw-bold uppercase">Return to Patient</h5></div>
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="status" value="returned">
                    <label class="text-secondary smaller fw-bold mb-2 uppercase">Reason for return</label>
                    <textarea name="return_reason" class="form-control" rows="4" placeholder="Explain what the patient needs to change..." required></textarea>
                </div>
                <div class="modal-footer border-danger bg-dark">
                    <button type="button" class="btn-custom btn-outline-neon" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn-custom btn-danger-custom px-4">SEND RETURN</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    @if($app->status == 'returned' && Auth::id() == $app->user_id)
    <div class="modal fade" id="resubmitModal{{$app->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('appointments.update', $app->id) }}" method="POST" class="modal-content border-neon bg-black">
                @csrf @method('PUT')
                <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold uppercase">Resubmit Appointment</h5></div>
                <div class="modal-body p-4 text-start text-white">
                    <div class="row">
                        <div class="col-md-5 mb-3 border-end border-secondary">
                            <label class="text-secondary smaller fw-bold mb-2 uppercase">Select New Date</label>
                            <input type="date" name="appointment_date" class="form-control date-selector" 
                                   data-service="res{{$app->id}}" value="{{ $app->appointment_date->format('Y-m-d') }}" 
                                   required min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-7">
                            <label class="text-secondary smaller fw-bold mb-2 uppercase">Select New Time Slot</label>
                            <div class="row g-2 time-grid-res{{$app->id}}" style="max-height: 250px; overflow-y: auto;">
                                @for($i = 0; $i < 24; $i++)
                                    @php $t = sprintf('%02d:00', $i); @endphp
                                    <div class="col-4 time-slot-item" data-hour="{{$i}}">
                                        <input type="radio" class="btn-check" name="time_slot" id="rs{{$app->id}}_{{$i}}" value="{{$t}}" required>
                                        <label class="btn btn-outline-secondary w-100 btn-sm fw-bold" for="rs{{$app->id}}_{{$i}}">{{ date('h:i A', strtotime($t)) }}</label>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-neon bg-dark">
                    <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold">RESUBMIT FOR APPROVAL</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach

<style>
    .accordion-button::after { display: none; }
    .accordion-button:not(.collapsed) { background-color: #050505; color: var(--neon); }
</style>
@endsection