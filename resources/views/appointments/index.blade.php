@extends('layouts.app')

@section('content')
<h2 class="text-neon fw-bold mb-4 uppercase" style="letter-spacing: 2px;">LABORATORY APPOINTMENTS</h2>

@if($is_staff)
    @include('appointments.partials.list', ['apps' => $staffQueue, 'type' => 'queue'])
@else
<ul class="nav nav-pills mb-5 border-bottom border-secondary pb-3 gap-2" id="appTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active small fw-bold uppercase px-4 text-white" data-bs-toggle="pill" data-bs-target="#pane-self">
            <i class="bi bi-person me-2"></i> My Appointments
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link small fw-bold uppercase px-4 text-white" data-bs-toggle="pill" data-bs-target="#pane-family">
            <i class="bi bi-people me-2"></i> Family / Dependents
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link small fw-bold uppercase px-4 text-white" data-bs-toggle="pill" data-bs-target="#pane-bulk">
            <i class="bi bi-buildings me-2"></i> Organization / Bulk
        </button>
    </li>
</ul>

<div class="tab-content">
    {{-- 1. SELF SECTION --}}
    <div class="tab-pane fade show active" id="pane-self">
        @include('appointments.partials.list', ['apps' => $self, 'type' => 'self'])
    </div>

    {{-- 2. FAMILY SECTION --}}
    <div class="tab-pane fade" id="pane-family">
        @include('appointments.partials.list', ['apps' => $dependents, 'type' => 'family'])
    </div>

    {{-- 3. BULK SECTION --}}
    <div class="tab-pane fade" id="pane-bulk">
        {{-- Here we pass bulkGroups into a variable named 'groups' --}}
        @include('appointments.partials.bulk_list', ['groups' => $bulkGroups])
    </div>
</div>
@endif

{{-- --- MODALS MASTER LOOP --- --}}
@php $flatApps = $is_staff ? $staffQueue->flatten() : $self->concat($dependents)->concat($bulkGroups->flatten()); @endphp

@foreach($flatApps as $app)
    {{-- MODAL: Return (Staff) --}}
    @can('isStaff')
    <div class="modal fade" id="retModal{{$app->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content border-danger bg-black">
                @csrf @method('PATCH')
                <div class="modal-header border-danger bg-dark"><h5 class="modal-title text-danger fw-bold uppercase small">Return to Patient</h5></div>
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="status" value="returned">
                    <label class="text-secondary smaller fw-bold mb-2 uppercase">Reason for return</label>
                    <textarea name="return_reason" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer border-danger bg-dark">
                    <button type="button" class="btn-custom btn-outline-neon" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn-danger-custom px-4 py-2 border-0">SEND RETURN</button>
                </div>
            </form>
        </div>
    </div>
    {{-- MODAL: MARK AS TESTED --}}
    <div class="modal fade" id="testModal{{$app->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('appointments.tested', $app->id) }}" method="POST" class="modal-content border-neon bg-black shadow-lg">
                @csrf @method('PATCH')
                <div class="modal-header border-neon bg-dark py-3">
                    <h6 class="modal-title text-neon fw-bold uppercase">Patient Sampling Completed</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4 text-start">
                    <p class="text-white small mb-4">Confirm that the patient has completed the testing process. Set how long until the results are ready.</p>
                    
                    <label class="text-secondary small fw-bold mb-2 uppercase">ESTIMATED PROCESSING TIME</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="input-group">
                                <input type="number" name="est_hours" class="form-control bg-dark border-secondary text-white fw-bold" placeholder="0" min="0">
                                <span class="input-group-text bg-black border-secondary text-secondary small uppercase" style="font-size: 0.6rem;">Hrs</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group">
                                <input type="number" name="est_minutes" class="form-control bg-dark border-secondary text-white fw-bold" placeholder="0" min="0" max="59">
                                <span class="input-group-text bg-black border-secondary text-secondary small uppercase" style="font-size: 0.6rem;">Mins</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary smaller mt-2 italic">
                        <i class="bi bi-info-circle me-1"></i> Leave blank to use the default 4-hour "Please wait" message.
                    </div>
                </div>

                <div class="modal-footer border-neon bg-dark p-0">
                    <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold fs-6">CONFIRM & NOTIFY PATIENT</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    {{-- MODAL: Resubmit (User) --}}
    @if($app->status == 'returned' && Auth::id() == $app->user_id)
    <div class="modal fade resubmit-modal" id="resubmitModal{{$app->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('appointments.update', $app->id) }}" method="POST" class="modal-content border-neon bg-black">
                @csrf @method('PUT')
                <div class="modal-header border-neon bg-dark"><h5 class="modal-title text-neon fw-bold uppercase small">Resubmit Appointment #{{ $app->id }}</h5></div>
                
                <div class="modal-body p-4 text-start text-white">
                    
                    {{-- SECTION A: IDENTITY EDITING (Only for Bulk) --}}
                    @if($app->batch_id)
                    <div class="mb-4 pb-4 border-bottom border-secondary">
                        <h6 class="text-white fw-bold mb-3 small uppercase text-neon">Correct Patient Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Full Name</label>
                                <input type="text" name="patient_name" class="form-control" value="{{ $app->patient_name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Email Address</label>
                                <input type="email" name="patient_email" class="form-control" value="{{ $app->patient_email }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Phone Number</label>
                                <input type="text" name="patient_phone" class="form-control" value="{{ $app->patient_phone }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                                <select name="patient_sex" class="form-select">
                                    <option value="Male" {{ $app->patient_sex == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ $app->patient_sex == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                                {{-- We add ?->format('Y-m-d') to ensure the browser can read it --}}
                                <input type="date" name="patient_birthdate" class="form-control" 
                                    value="{{ $app->patient_birthdate ? $app->patient_birthdate->format('Y-m-d') : '' }}" 
                                    required>
                            </div>
                            <div class="col-12">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Address</label>
                                <textarea name="patient_address" class="form-control" rows="2" required>{{ $app->patient_address }}</textarea>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- SECTION B: NEW SCHEDULE (Strict) --}}
                    <div class="row">
                        <div class="col-md-5 mb-3 border-end border-secondary">
                            <label class="text-secondary smaller fw-bold mb-2 uppercase">Date</label>
                            {{-- Added class 'resubmit-date' and data-app-id --}}
                            <input type="date" name="appointment_date" 
                                class="form-control resubmit-date" 
                                data-app-id="{{$app->id}}" 
                                value="{{ $app->appointment_date->format('Y-m-d') }}" 
                                required min="{{ date('Y-m-d') }}" 
                                onchange="updateResubmitSlots(this)">
                        </div>
                        <div class="col-md-7">
                            <label class="text-secondary smaller fw-bold mb-2 uppercase">Select New Time Slot</label>
                            <select name="time_slot" id="ts-{{$app->id}}" class="form-select border-neon text-white fw-bold py-3" required>
                                <option value="">Loading schedule...</option>
                            </select>
                            <small class="text-white mt-2 d-block" style="font-size: 0.6rem;">* Only available clinic hours are shown.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-neon bg-dark">
                    <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold">UPDATE AND RESUBMIT FOR APPROVAL</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach

<style>
    .accordion-button::after { display: none; }
    .accordion-button:not(.collapsed) { background-color: #050505; color: var(--neon); }
    .nav-pills .nav-link.active { background-color: var(--neon) !important; color: #000 !important; }
</style>

@push('scripts')
<script>
// --- AUTOMATIC TRIGGER WHEN MODAL OPENS ---
document.addEventListener('show.bs.modal', function (event) {
    // Check if the opened modal is a resubmit modal
    const modal = event.target;
    if (modal.classList.contains('resubmit-modal')) {
        // Find the date input inside this specific modal
        const dateInput = modal.querySelector('.resubmit-date');
        if (dateInput) {
            console.log("Modal opened, auto-loading slots for date:", dateInput.value);
            updateResubmitSlots(dateInput);
        }
    }
});

// --- THE CORE LOGIC ---
async function updateResubmitSlots(input) {
    const date = input.value;
    const appId = input.dataset.appId;
    const select = document.getElementById(`ts-${appId}`);
    
    if(!date || !select) return;

    select.innerHTML = '<option value="">Checking slots...</option>';
    select.disabled = true;

    try {
        const res = await fetch(`/api/check-slots?date=${date}&exclude_id=${appId}`);
        const data = await res.json();
        
        // Log to console so you can verify the array is no longer empty
        console.log("Excluded slots for this date:", data.full_slots);

        if(data.is_closed) {
            select.innerHTML = '<option value="">CLINIC CLOSED</option>';
            return;
        }

        const config = data.config;
        let html = '<option value="">Choose Available Time</option>';
        
        let start = new Date(`2000-01-01 ${config.opening_time}`);
        let end = new Date(`2000-01-01 ${config.closing_time}`);
        let availableCount = 0;

        while(start < end) {
            // Generate HH:MM:SS manually to match MySQL exactly
            let hours = start.getHours().toString().padStart(2, '0');
            let minutes = start.getMinutes().toString().padStart(2, '0');
            let tStr = `${hours}:${minutes}:00`; 
            
            // Generate comparison variables
            let isFull = data.full_slots.includes(tStr);
            let isLunch = (config.has_lunch_break && tStr >= config.lunch_start && tStr < config.lunch_end);
            let isPast = (date === new Date().toLocaleDateString('en-CA') && start.getHours() <= new Date().getHours());

            // THE REMOVAL LOGIC:
            // If any condition is met, skip this slot and go to the next increment
            if (isFull || isLunch || isPast) {
                start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
                continue; 
            }

            // If we are here, the slot is truly available
            let disp = start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            html += `<option value="${tStr}">${disp}</option>`;
            availableCount++;
            
            start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
        }
        
        select.innerHTML = availableCount > 0 ? html : '<option value="">NO SLOTS AVAILABLE</option>';
        select.disabled = availableCount === 0;

    } catch (e) {
        console.error("Fetch error:", e);
        select.innerHTML = '<option value="">Error syncing schedule</option>';
    }
}
</script>
@endpush
@endsection