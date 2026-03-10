@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9 text-start">
        <h3 class="text-neon fw-bold mb-4 uppercase" style="letter-spacing: 2px;">TEST SUMMARY</h3>

        @if(count($services) > 0)
        <div class="row g-4">
            {{-- LEFT COLUMN: LIST OF TESTS --}}
            <div class="col-lg-7">
                <div class="card p-0 overflow-hidden shadow-lg border-secondary">
                    <table class="table table-dark mb-0 align-middle">
                        <thead class="bg-black text-secondary smaller fw-bold">
                            <tr>
                                <th class="px-4 py-3">SERVICE NAME</th>
                                <th class="text-end px-4">PRICE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $item)
                            <tr class="border-secondary" style="background-color: var(--card);">
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

                                        {{-- Service Details --}}
                                        <div>
                                            <div class="text-white fw-bold small" style="letter-spacing: 0.5px;">
                                                {{ strtoupper($item->name) }}
                                            </div>
                                            <div class="text-neon fw-bold" style="font-size: 0.6rem;">
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

                {{-- Combined Preparation Checklist --}}
                <div class="card p-4 mt-4 border-warning shadow-sm" style="background-color: rgba(255, 193, 7, 0.05);">
                    <h6 class="text-warning fw-bold mb-3 small uppercase">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Preparation Checklist
                    </h6>
                    <ul class="text-white-50 small mb-0 ps-3">
                        @foreach($services as $item)
                            <li class="mb-2">
                                <strong class="text-white">{{ strtoupper($item->name) }}:</strong> 
                                {{ $item->preparation }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- RIGHT COLUMN: TOTAL & PROCEED --}}
            <div class="col-lg-5">
                <div class="card p-4 shadow-lg sticky-top border-neon" style="top: 100px;">
                    <h5 class="text-white fw-bold mb-4 border-bottom border-secondary pb-2 uppercase small">Payment Summary</h5>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-secondary small fw-bold">TOTAL AMOUNT</span>
                        <h3 class="text-neon fw-bold mb-0">₱{{ number_format($totalPrice, 2) }}</h3>
                    </div>

                    <p class="smaller text-secondary mb-4 italic">Next step: Choose your appointment date and available time block.</p>
                    
                    <button class="btn-custom btn-neon w-100 py-3 fw-bold fs-6 mb-2" data-bs-toggle="modal" data-bs-target="#finalCheckoutModal">
                        PICK SCHEDULE
                    </button>
                    
                    <a href="{{ route('services.index') }}" class="btn-custom btn-outline-neon w-100 py-2 small">
                        <i class="bi bi-plus-lg me-1"></i> ADD MORE TESTS
                    </a>
                </div>
            </div>
        </div>
        @else
            {{-- EMPTY STATE --}}
            <div class="card p-5 text-center border-secondary shadow-lg">
                <i class="bi bi-cart-x text-secondary" style="font-size: 4rem;"></i>
                <h5 class="text-white fw-bold mt-3">YOUR LIST IS EMPTY</h5>
                <p class="text-secondary small">You haven't selected any laboratory services yet.</p>
                <div class="mt-4">
                    <a href="{{ route('services.index') }}" class="btn-custom btn-neon px-5 py-3">BROWSE SERVICES</a>
                </div>
            </div>
        @endif
    </div>
</div>

@if(count($services) > 0)
{{-- FINAL CHECKOUT MODAL --}}
<div class="modal fade" id="finalCheckoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('appointments.store') }}" method="POST" class="modal-content border-neon bg-black shadow-lg">
            @csrf
            {{-- Hidden inputs to pass selected services --}}
            @foreach($services as $item)
                <input type="hidden" name="service_ids[]" value="{{ $item->id }}">
            @endforeach

            <div class="modal-header border-neon bg-dark py-3">
                <h5 class="modal-title text-neon fw-bold uppercase small">Schedule Your Appointment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4 text-white text-start">
                @php 
                    $config = \App\Models\AppointmentConfig::first() ?? (object)[
                        'opening_time' => '08:00', 
                        'closing_time' => '17:00', 
                        'slot_duration' => 60
                    ]; 
                @endphp
                
                <div class="row">
                    {{-- 1. Date Picker --}}
                    <div class="col-md-5 mb-4 border-end border-secondary">
                        <label class="fw-bold text-neon small mb-2 uppercase">1. Select Date</label>
                        <input type="date" name="appointment_date" id="checkout_date" class="form-control mb-2" required min="{{ date('Y-m-d') }}">
                        <div id="date_hint" class="text-secondary smaller italic">Available dates are shown from today onwards.</div>
                    </div>

                    {{-- 2. Dynamic Time Grid --}}
                    <div class="col-md-7">
                        <label class="fw-bold text-neon small mb-2 uppercase">2. Select Time Block</label>
                        <div class="row g-2" id="time_slot_container" style="max-height: 300px; overflow-y: auto; display:none;">
                            @php
                                $start = strtotime($config->opening_time);
                                $end = strtotime($config->closing_time);
                                $i = 0; // Create a manual counter here
                            @endphp

                            @while($start < $end)
                                @php $timeVal = date('H:i:00', $start); @endphp
                                <div class="col-4 time-block-wrapper" data-time="{{ $timeVal }}" data-hour="{{ date('H', $start) }}">
                                    {{-- Use $i instead of $loop->index --}}
                                    <input type="radio" class="btn-check" name="time_slot" id="slot_{{ $i }}" value="{{ $timeVal }}" required>
                                    <label class="btn btn-outline-secondary w-100 btn-sm py-2 fw-bold" for="slot_{{ $i }}">
                                        {{ date('h:i A', $start) }}
                                    </label>
                                </div>
                                @php 
                                    $start = strtotime("+$config->slot_duration minutes", $start); 
                                    $i++; // Increment the counter
                                @endphp
                            @endwhile
                        </div>
                        <div id="slot_placeholder" class="text-center py-5 text-secondary border border-secondary border-dashed rounded bg-dark">
                            <i class="bi bi-calendar-event fs-2 d-block mb-2 opacity-50"></i>
                            Please select a date first
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-neon bg-dark p-0">
                <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold fs-5" id="confirm_btn" disabled>CONFIRM RESERVATION</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
document.getElementById('checkout_date').addEventListener('change', function() {
    const selectedDate = this.value;
    const container = document.getElementById('time_slot_container');
    const placeholder = document.getElementById('slot_placeholder');
    const confirmBtn = document.getElementById('confirm_btn');

    // Show grid, hide placeholder
    container.style.display = 'flex';
    placeholder.style.display = 'none';

    // Fetch Occupancy from API
    fetch(`/api/check-slots?date=${selectedDate}`)
        .then(response => response.json())
        .then(data => {
            const fullSlots = data.full_slots; 
            
            // Get local date/hour instead of UTC to avoid mismatch in Philippines
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const todayLocal = `${year}-${month}-${day}`;
            const currentHour = now.getHours();

            document.querySelectorAll('.time-block-wrapper').forEach(wrapper => {
                const timeValue = wrapper.dataset.time;
                const slotHour = parseInt(wrapper.dataset.hour);
                const input = wrapper.querySelector('input');
                const label = wrapper.querySelector('label');

                // Reset styles
                wrapper.style.display = 'block';
                input.disabled = false;
                input.checked = false;
                label.classList.remove('btn-danger', 'opacity-25');
                label.classList.add('btn-outline-secondary');
                label.innerText = label.innerText.replace(' (FULL)', '');

                // Logic A: Disable past hours for TODAY
                if (selectedDate === todayLocal && slotHour <= currentHour) {
                    wrapper.style.display = 'none';
                    input.disabled = true;
                }

                // Logic B: Disable if FULL
                if (fullSlots.includes(timeValue)) {
                    input.disabled = true;
                    label.classList.remove('btn-outline-secondary');
                    label.classList.add('btn-danger', 'opacity-25');
                    label.innerText += ' (FULL)';
                }
            });

            confirmBtn.disabled = false;
        });
});
</script>
@endpush
@endsection