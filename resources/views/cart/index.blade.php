@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <h3 class="text-neon fw-bold mb-4 uppercase">Booking Summary</h3>

        @if(count($services) > 0)
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card p-0 overflow-hidden shadow">
                    <table class="table table-dark mb-0 align-middle">
                        <thead class="smaller text-secondary">
                            <tr><th class="px-4 py-3">SERVICE NAME</th><th class="text-end px-4">PRICE</th></tr>
                        </thead>
                        <tbody>
                            @foreach($services as $item)
                            <tr class="border-secondary">
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        {{-- Remove Button on the Left --}}
                                        <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                            @csrf 
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-danger-custom py-1 px-2" title="Remove Test">
                                                <i class="bi bi-trash3-fill" style="font-size: 0.85rem;"></i>
                                            </button>
                                        </form>

                                        {{-- Service Name on the Right of the button --}}
                                        <div>
                                            <div class="text-white fw-bold small" style="letter-spacing: 0.5px;">
                                                {{ strtoupper($item->name) }}
                                            </div>
                                            <div class="text-secondary" style="font-size: 0.65rem;">
                                                {{ strtoupper($item->category) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end px-4 text-neon fw-bold">
                                    ₱{{ number_format($item->price, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Combined Preparation Requirements --}}
                <div class="card p-4 mt-4 border-warning shadow-sm">
                    <h6 class="text-warning fw-bold mb-3 small"><i class="bi bi-exclamation-circle me-2"></i>PREPARATION CHECKLIST</h6>
                    <ul class="text-white-50 small mb-0">
                        @foreach($services as $item)
                            <li class="mb-2"><strong>{{ $item->name }}:</strong> {{ $item->preparation }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Checkout Card --}}
            <div class="col-lg-5">
                <div class="card p-4 shadow-lg sticky-top" style="top: 100px;">
                    <h5 class="text-white fw-bold mb-4 border-bottom border-secondary pb-2">TOTAL PRICE</h5>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-secondary">Subtotal</span>
                        <h4 class="text-neon fw-bold">₱{{ number_format($totalPrice, 2) }}</h4>
                    </div>

                    <p class="smaller text-secondary mb-4 italic">Next step: Choose your appointment date and time block.</p>
                    
                    <button class="btn-custom btn-neon w-100 py-3 fw-bold fs-6" data-bs-toggle="modal" data-bs-target="#finalCheckoutModal">
                        PICK SCHEDULE
                    </button>
                    <a href="{{ route('services.index') }}" class="btn-custom btn-white text-white w-100 mt-2 small text-decoration-none">Add more tests</a>
                </div>
            </div>
        </div>
        @else
            <div class="card p-5 text-center border-secondary">
                <i class="bi bi-cart-x text-secondary fs-1 mb-3"></i>
                <h5 class="text-white">Your list is empty.</h5>
                <a href="{{ route('services.index') }}" class="btn-custom btn-neon mt-3 px-4">BROWSE SERVICES</a>
            </div>
        @endif
    </div>
</div>

{{-- FINAL CHECKOUT MODAL (Date & Time Selection) --}}
@if(count($services) > 0)
<div class="modal fade" id="finalCheckoutModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('appointments.store') }}" method="POST" class="modal-content border-neon bg-black">
            @csrf
            {{-- Pass all service IDs as hidden inputs --}}
            @foreach($services as $item)
                <input type="hidden" name="service_ids[]" value="{{ $item->id }}">
            @endforeach

            <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold">SCHEDULE APPOINTMENT</h5></div>
            <div class="modal-body p-4 text-white">
                <div class="row">
                    <div class="col-md-5 mb-4 border-end border-secondary">
                        <label class="fw-bold text-neon small mb-2 uppercase">1. Select Date</label>
                        <input type="date" name="appointment_date" class="form-control date-selector" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-7">
                        <label class="fw-bold text-neon small mb-2 uppercase">2. Select Time Block</label>
                        {{-- Hourly Grid here --}}
                        <div class="row g-2" style="max-height: 250px; overflow-y: auto;">
                            @for($i = 0; $i < 24; $i++)
                                @php $val = sprintf('%02d:00', $i); @endphp
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="time_slot" id="checkout_{{$i}}" value="{{$val}}" required>
                                    <label class="btn btn-outline-secondary w-100 btn-sm py-2" for="checkout_{{$i}}">{{ date('h:i A', strtotime($val)) }}</label>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-neon bg-dark"><button type="submit" class="btn-custom btn-neon w-100 py-3">CONFIRM ALL TESTS</button></div>
        </form>
    </div>
</div>
@endif
@endsection