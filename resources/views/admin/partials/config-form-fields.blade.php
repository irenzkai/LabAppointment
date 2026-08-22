<div class="row g-3">
    {{-- Operational Status Switch --}}
    <div class="col-12 mb-1">
        <div class="form-check form-switch p-3 border rounded-3 transition-all d-flex align-items-center justify-content-between m-0" id="openStatusContainer" style="background-color: var(--bg-main); border-color: var(--border-color) !important;">
            <label class="form-check-label fw-bold uppercase smaller d-flex align-items-center gap-2 m-0" for="is_open" style="color: var(--text-main);">
                <i class="bi bi-power text-accent fs-5"></i>
                <span>Clinic Operational for this Day</span>
            </label>
            <input class="form-check-input m-0 cursor-pointer" type="checkbox" name="is_open" id="is_open" value="1" 
                {{ ($config->is_open ?? true) ? 'checked' : '' }}>
        </div>
    </div>

    {{-- Hours of Operation --}}
    <div class="col-6">
        <label class="smaller text-muted fw-bold mb-1.5 uppercase d-flex align-items-center gap-1.5">
            <i class="bi bi-clock me-1 text-accent"></i>
            <span>Opening Time</span>
        </label>
        <input type="time" name="opening_time" class="form-control py-2.5 fw-semibold" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" 
            value="{{ isset($config->opening_time) ? date('H:i', strtotime($config->opening_time)) : '08:00' }}" required>
    </div>

    <div class="col-6">
        <label class="smaller text-muted fw-bold mb-1.5 uppercase d-flex align-items-center gap-1.5">
            <i class="bi bi-clock-history me-1 text-accent "></i>
            <span>Closing Time</span>
        </label>
        <input type="time" name="closing_time" class="form-control py-2.5 fw-semibold" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" 
            value="{{ isset($config->closing_time) ? date('H:i', strtotime($config->closing_time)) : '17:00' }}" required>
    </div>

    {{-- Lunch Break Configuration Panel --}}
    <div class="col-12 mt-2">
        <div class="p-3 border rounded-3 text-start" style="background-color: var(--bg-main); border-color: var(--border-color) !important;">
            <div class="form-check form-switch d-flex align-items-center justify-content-between mb-3 m-0 p-0">
                <label class="form-check-label smaller fw-bold uppercase d-flex align-items-center gap-2 m-0" for="lunchSwitch" style="color: var(--text-main);">
                    <i class="bi bi-cup-hot-fill text-warning fs-6"></i>
                    <span>Enable Mid-Day Lunch Break</span>
                </label>
                <input class="form-check-input m-0 cursor-pointer" type="checkbox" name="has_lunch_break" id="lunchSwitch" value="1"
                    {{ ($config->has_lunch_break ?? false) ? 'checked' : '' }}>
            </div>
            
            <div class="row g-2 transition-all" id="lunchFields">
                <div class="col-6">
                    <label class="smaller text-muted uppercase d-block mb-1 fw-semibold" style="font-size: 0.65rem;">Start Time</label>
                    <input type="time" name="lunch_start" class="form-control form-control-sm py-2 fw-semibold" style="background-color: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color);" 
                        value="{{ isset($config->lunch_start) ? date('H:i', strtotime($config->lunch_start)) : '12:00' }}">
                </div>
                <div class="col-6">
                    <label class="smaller text-muted uppercase d-block mb-1 fw-semibold" style="font-size: 0.65rem;">End Time</label>
                    <input type="time" name="lunch_end" class="form-control form-control-sm py-2 fw-semibold" style="background-color: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color);" 
                        value="{{ isset($config->lunch_end) ? date('H:i', strtotime($config->lunch_end)) : '13:00' }}">
                </div>
                <div class="col-12 mt-2">
                    <small class="text-muted italic smaller d-flex align-items-center gap-1">
                        <i class="bi bi-info-circle me-1"></i>
                        <span>Slots in this window are hidden from patient booking wizard.</span>
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Slot Mechanics --}}
    <div class="col-6 mt-2">
        <label class="smaller text-muted fw-bold mb-1.5 uppercase d-flex align-items-center gap-2">
            <i class="bi bi-hourglass-split text-accent"></i>
            <span>Slot Duration</span>
        </label>
        <div class="input-group input-group-sm">
            <select name="slot_duration" class="form-select py-2 fw-semibold" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);">
                <option value="15" {{ ($config->slot_duration ?? 60) == 15 ? 'selected' : '' }}>15 Minutes</option>
                <option value="30" {{ ($config->slot_duration ?? 60) == 30 ? 'selected' : '' }}>30 Minutes</option>
                <option value="45" {{ ($config->slot_duration ?? 60) == 45 ? 'selected' : '' }}>45 Minutes</option>
                <option value="60" {{ ($config->slot_duration ?? 60) == 60 ? 'selected' : '' }}>60 Minutes (1 Hr)</option>
                <option value="120" {{ ($config->slot_duration ?? 60) == 120 ? 'selected' : '' }}>120 Minutes (2 Hrs)</option>
            </select>
        </div>
    </div>

    <div class="col-6 mt-2">
        <label class="smaller text-muted fw-bold mb-1.5 uppercase d-flex align-items-center gap-2">
            <i class="bi bi-people-fill text-accent"></i>
            <span>Slot Quota</span>
        </label>
        <div class="input-group input-group-sm">
            <input type="number" name="max_patients_per_slot" class="form-control py-2 fw-semibold" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" 
                min="1" value="{{ $config->max_patients_per_slot ?? 1 }}">
            <span class="input-group-text fw-bold smaller text-secondary" style="background-color: var(--bg-main); border: 1.5px solid var(--border-color);">PAX</span>
        </div>
    </div>

    {{-- Booking Buffer --}}
    <div class="col-12 mt-2">
        <label class="smaller text-muted fw-bold mb-1.5 uppercase d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-accent"></i>
            <span>Lead-Time Buffer</span>
        </label>
        <div class="input-group">
            <input type="number" name="lead_time_hours" class="form-control py-2 fw-semibold" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" 
                min="0" value="{{ $config->lead_time_hours ?? 2 }}">
            <span class="input-group-text fw-bold smaller text-secondary uppercase" style="background-color: var(--bg-main); border: 1.5px solid var(--border-color);">Hours Before Booking</span>
        </div>
        <small class="text-muted smaller mt-1.5 d-flex align-items-start gap-1">
            <i class="bi bi-info-circle me-1 mt-0.5"></i>
            <span>Blocks patients from booking slots that occur within this cutoff buffer window.</span>
        </small>
    </div>

    {{-- Administrative Justification / Reason --}}
    <div class="col-12 mt-3 pt-3 border-top border-secondary border-opacity-25">
        <label class="smaller text-muted fw-bold mb-1.5 uppercase d-flex align-items-center gap-2">
            <i class="bi bi-journal-text text-accent"></i>
            <span>Reason for Schedule Modification</span>
        </label>
        <select name="reason" class="form-select py-2.5 fw-semibold schedule-reason-select" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" onchange="toggleScheduleReasonField(this)" required>
            <option value="" disabled selected>-- Select a valid justification --</option>
            <option value="Routine operational schedule update">Routine operational schedule update</option>
            <option value="Holiday or special clinic closure adjustment">Holiday or special clinic closure adjustment</option>
            <option value="Adjusting patient slot capacity & buffer">Adjusting patient slot capacity & buffer</option>
            <option value="Emergency clinic schedule adjustment">Emergency clinic schedule adjustment</option>
            <option value="Others">Others (Specify below)</option>
        </select>
    </div>

    <div class="col-12 d-none schedule-custom-reason-wrapper">
        <label class="smaller text-muted fw-bold mb-1.5 uppercase d-flex align-items-center gap-1.5">
            <i class="bi bi-pencil-square text-accent"></i>
            <span>Specify Custom Reason</span>
        </label>
        <textarea name="custom_reason" class="form-control schedule-custom-reason" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="2" placeholder="Provide clinical justification for schedule modification..."></textarea>
    </div>
</div>