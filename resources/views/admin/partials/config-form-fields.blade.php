<div class="row g-3">
    {{-- Operational Status --}}
    <div class="col-12 mb-2">
        <div class="form-check form-switch p-3 border border-secondary rounded bg-dark bg-opacity-25 transition-all" id="openStatusContainer">
            <input class="form-check-input ms-0 me-3" type="checkbox" name="is_open" id="is_open" value="1" 
                {{ ($config->is_open ?? true) ? 'checked' : '' }}>
            <label class="form-check-label text-white fw-bold uppercase smaller" for="is_open">
                Clinic Operational for this Day
            </label>
        </div>
    </div>

    {{-- Hours of Operation --}}
    <div class="col-6">
        <label class="smaller text-muted fw-bold mb-1 uppercase d-block">Opening Time</label>
        <input type="time" name="opening_time" class="form-control bg-dark border-secondary text-white py-2" 
            value="{{ isset($config->opening_time) ? date('H:i', strtotime($config->opening_time)) : '08:00' }}" required>
    </div>
    <div class="col-6">
        <label class="smaller text-muted fw-bold mb-1 uppercase d-block">Closing Time</label>
        <input type="time" name="closing_time" class="form-control bg-dark border-secondary text-white py-2" 
            value="{{ isset($config->closing_time) ? date('H:i', strtotime($config->closing_time)) : '17:00' }}" required>
    </div>

    {{-- Lunch Break Configuration --}}
    <div class="col-12 mt-2">
        <div class="p-3 border border-secondary border-opacity-50 rounded bg-black bg-opacity-50">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="has_lunch_break" id="lunchSwitch" value="1"
                    {{ ($config->has_lunch_break ?? false) ? 'checked' : '' }}>
                <label class="form-check-label text-white smaller fw-bold uppercase" for="lunchSwitch">
                    Enable Lunch Break
                </label>
            </div>
            
            <div class="row g-2 transition-all" id="lunchFields">
                <div class="col-6">
                    <label class="smaller text-muted uppercase d-block mb-1" style="font-size: 0.6rem;">Start Time</label>
                    <input type="time" name="lunch_start" class="form-control form-control-sm bg-dark border-secondary text-white" 
                        value="{{ isset($config->lunch_start) ? date('H:i', strtotime($config->lunch_start)) : '12:00' }}">
                </div>
                <div class="col-6">
                    <label class="smaller text-muted uppercase d-block mb-1" style="font-size: 0.6rem;">End Time</label>
                    <input type="time" name="lunch_end" class="form-control form-control-sm bg-dark border-secondary text-white" 
                        value="{{ isset($config->lunch_end) ? date('H:i', strtotime($config->lunch_end)) : '13:00' }}">
                </div>
                <div class="col-12 mt-2">
                    <small class="text-muted italic smaller">Slots within this range will be hidden from patients.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Slot Mechanics --}}
    <div class="col-6 mt-2">
        <label class="smaller text-muted fw-bold mb-1 uppercase d-block">Slot Duration</label>
        <div class="input-group input-group-sm">
            <select name="slot_duration" class="form-select bg-dark border-secondary text-white">
                <option value="15" {{ ($config->slot_duration ?? 60) == 15 ? 'selected' : '' }}>15 Mins</option>
                <option value="30" {{ ($config->slot_duration ?? 60) == 30 ? 'selected' : '' }}>30 Mins</option>
                <option value="45" {{ ($config->slot_duration ?? 60) == 45 ? 'selected' : '' }}>45 Mins</option>
                <option value="60" {{ ($config->slot_duration ?? 60) == 60 ? 'selected' : '' }}>60 Mins</option>
                <option value="120" {{ ($config->slot_duration ?? 60) == 120 ? 'selected' : '' }}>2 Hours</option>
            </select>
        </div>
    </div>

    <div class="col-6 mt-2">
        <label class="smaller text-muted fw-bold mb-1 uppercase d-block">Slot Capacity</label>
        <div class="input-group input-group-sm">
            <input type="number" name="max_patients_per_slot" class="form-control bg-dark border-secondary text-white" 
                min="1" value="{{ $config->max_patients_per_slot ?? 1 }}">
            <span class="input-group-text bg-secondary bg-opacity-25 border-secondary text-secondary small">PAX</span>
        </div>
    </div>

    {{-- Booking Buffer --}}
    <div class="col-12 mt-2">
        <label class="smaller text-muted fw-bold mb-1 uppercase d-block">Lead-Time Buffer</label>
        <div class="input-group">
            <input type="number" name="lead_time_hours" class="form-control bg-dark border-secondary text-white" 
                min="0" value="{{ $config->lead_time_hours ?? 2 }}">
            <span class="input-group-text bg-secondary bg-opacity-25 border-secondary text-secondary small uppercase fw-bold">Hours before booking</span>
        </div>
        <small class="text-muted smaller mt-1 d-block">
            <i class="bi bi-info-circle me-1"></i>Prevents patients from booking slots that occur within this many hours of the current time.
        </small>
    </div>
</div>

<style>
    #lunchFields {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--neon) !important;
        box-shadow: 0 0 5px rgba(90, 247, 129, 0.2);
    }
    input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(1); /* Makes the icon visible on dark background */
        cursor: pointer;
    }
</style>