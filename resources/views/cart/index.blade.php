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

                    <div class="text-center pt-4 border-top border-secondary">
                        <p class="text-white smaller mb-2 italic">Booking for a company or large group?</p>
                        <a href="{{ route('appointments.bulk') }}" class="btn-custom text-neon fw-bold small text-decoration-none">
                            BOOK FOR AN ORGANIZATION INSTEAD <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
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
            @foreach($services as $item) <input type="hidden" name="service_ids[]" value="{{ $item->id }}"> @endforeach

            <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold uppercase small">Schedule Booking</h5></div>
            
            <div class="modal-body p-4 text-white text-start">
                {{-- Patient Selector --}}
                <div class="mb-4 pb-4 border-bottom border-secondary">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <label class="fw-bold text-neon small mb-0 uppercase">Who is this booking for?</label>
                        {{-- Quick Switch to Bulk --}}
                        <a href="{{ route('appointments.bulk') }}" class="text-neon smaller fw-bold text-decoration-none uppercase">
                            <i class="bi bi-buildings me-1"></i> Switch to Bulk Booking
                        </a>
                    </div>
                    
                    <select name="dependent_id" class="form-select bg-dark border-neon text-white fw-bold py-3 shadow-none">
                        <option value="">SELF ({{ strtoupper(Auth::user()->name) }})</option>
                        @foreach(Auth::user()->dependents as $dep)
                            <option value="{{ $dep->id }}">{{ strtoupper($dep->name) }} ({{ strtoupper($dep->relationship) }})</option>
                        @endforeach
                    </select>
                    
                    <div class="text-white mt-2 d-flex align-items-center" style="font-size: 0.65rem;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span>Results will be issued under the selected patient's name.</span>
                    </div>
                </div>

                <div id="gender_error" class="alert bg-black border-danger text-danger small py-3 mb-4" style="display:none; border-style: dashed;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-gender-ambiguous fs-4 me-3"></i>
                        <div>
                            <div class="fw-bold uppercase">Gender Incompatibility Detected</div>
                            <div class="smaller opacity-75">One or more tests in your list are restricted. Please check patient selection or remove the restricted test.</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-5 mb-4 border-end border-secondary">
                        <label class="fw-bold text-neon small mb-2 uppercase">SELECT DATE</label>
                        <input type="date" name="appointment_date" id="checkout_date" class="form-control" required min="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-7">
                        <label class="fw-bold text-neon small mb-2 uppercase">AVAILABLE TIMES</label>
                        <div id="time_slot_container" class="row g-2" style="max-height: 280px; overflow-y: auto;"></div>
                        <div id="slot_placeholder" class="text-center py-5 text-secondary border border-secondary border-dashed rounded bg-dark">
                            <i class="bi bi-calendar-check fs-2 d-block mb-2 opacity-50"></i>
                            Select a date first
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
const dateInput = document.getElementById('checkout_date');
const patientSelect = document.querySelector('select[name="dependent_id"]');
const genderError = document.getElementById('gender_error');
const confirmBtn = document.getElementById('confirm_btn');
const container = document.getElementById('time_slot_container');
const placeholder = document.getElementById('slot_placeholder');

// Pass cart data from PHP to JS
const cartItems = @json(session('cart', []));

function updateSchedule() {
    const selectedDate = dateInput.value;
    const dependentId = patientSelect.value;

    if (!selectedDate) return;

    // 1. Improved UI: Smaller spinner, don't clear everything immediately
    container.style.opacity = '0.5'; 
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>VALIDATING SCHEDULE...';

    fetch(`/api/check-slots?date=${selectedDate}&dependent_id=${dependentId}`)
        .then(res => res.json())
        .then(data => {
            container.style.opacity = '1';
            placeholder.style.display = 'none';
            genderError.style.display = 'none';

            // 2. Gender Validation (Instant)
            let patientGender = data.patient_gender;
            let hasConflict = false;
            Object.values(cartItems).forEach(item => {
                if (item.gender !== 'both' && item.gender !== patientGender) hasConflict = true;
            });

            if (hasConflict) {
                showErrorState('bi-person-x', 'Gender Incompatibility', 'danger');
                genderError.style.display = 'block';
                return;
            }

            // 3. Closed Check
            if (data.is_closed) {
                showErrorState('bi-moon-stars', 'Laboratory Closed', 'warning');
                return;
            }

            // 4. Fast Render
            renderSlots(data, selectedDate);
            
            confirmBtn.innerHTML = 'CONFIRM RESERVATION';
            confirmBtn.disabled = false;
        })
        .catch(err => {
            confirmBtn.innerHTML = 'RETRY';
            confirmBtn.disabled = false;
            console.error(err);
        });
}

function showErrorState(icon, message, colorClass) {
    container.innerHTML = '';
    placeholder.innerHTML = `<i class="bi ${icon} text-${colorClass} fs-1 d-block mb-2"></i><span class="text-${colorClass} fw-bold uppercase">${message}</span>`;
    placeholder.style.display = 'block';
    confirmBtn.innerHTML = 'SCHEDULE UNAVAILABLE';
}

function renderSlots(data, selectedDate) {
    let html = '';
    let start = new Date(`2000-01-01 ${data.config.opening_time}`);
    let end = new Date(`2000-01-01 ${data.config.closing_time}`);
    let now = new Date();
    // Format today's date correctly for comparison
    let todayStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
    
    let i = 0;
    while(start < end) {
        let timeStr = start.toTimeString().split(' ')[0].substring(0, 5) + ":00";
        let displayTime = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        let isLunch = (data.config.has_lunch_break && timeStr >= data.config.lunch_start && timeStr < data.config.lunch_end);

        if(!isLunch) {
            let isPast = (selectedDate === todayStr && start.getHours() <= now.getHours());
            let isFull = data.full_slots.includes(timeStr);
            
            html += `
                <div class="col-4">
                    <input type="radio" class="btn-check" name="time_slot" id="s${i}" value="${timeStr}" ${isPast || isFull ? 'disabled' : ''} required>
                    <label class="btn ${isFull ? 'btn-danger opacity-25' : 'btn-outline-secondary'} w-100 btn-sm py-2 fw-bold" for="s${i}">
                        ${displayTime} ${isFull ? '<br>(FULL)' : ''}
                    </label>
                </div>`;
        }
        start.setMinutes(start.getMinutes() + data.config.slot_duration);
        i++;
    }
    container.innerHTML = html;
    container.style.display = 'flex';
}

function generateTimeSlots(data, selectedDate) {
    let html = '';
    let start = new Date(`2000-01-01 ${data.config.opening_time}`);
    let end = new Date(`2000-01-01 ${data.config.closing_time}`);
    let now = new Date();
    let todayStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
    
    let i = 0;
    while(start < end) {
        let timeStr = start.toTimeString().split(' ')[0];
        let displayTime = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        let isLunch = (data.config.has_lunch_break && timeStr >= data.config.lunch_start && timeStr < data.config.lunch_end);

        if(!isLunch) {
            let isPast = (selectedDate === todayStr && start.getHours() <= now.getHours());
            let isFull = data.full_slots.includes(timeStr);
            
            html += `
                <div class="col-4">
                    <input type="radio" class="btn-check" name="time_slot" id="s${i}" value="${timeStr}" ${isPast || isFull ? 'disabled' : ''} required>
                    <label class="btn ${isFull ? 'btn-danger opacity-25' : 'btn-outline-secondary'} w-100 btn-sm py-2 fw-bold" for="s${i}">
                        ${displayTime} ${isFull ? '<br>(FULL)' : ''}
                    </label>
                </div>`;
        }
        start.setMinutes(start.getMinutes() + data.config.slot_duration);
        i++;
    }
    container.innerHTML = html;
    container.style.display = 'flex';
    confirmBtn.disabled = false;
}

dateInput.addEventListener('change', updateSchedule);
patientSelect.addEventListener('change', updateSchedule);
</script>
@endpush
@endsection