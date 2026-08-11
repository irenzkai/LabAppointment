<!-- PAGE 4: SCHEDULE -->
<div class="wiz-section d-none text-start animate-page" id="page-4">
    {{-- Step Title --}}
    <div class="mb-4">
        <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 4: Select Schedule</h3>
        <p class="text-secondary small">Choose your preferred date and time for the laboratory visit.</p>
    </div>

    <div class="row g-4">
        {{-- Date Selection (Col 5) --}}
        <div class="col-md-5 border-end border-secondary border-opacity-25 pe-md-4">
            <label class="small text-accent fw-bold mb-2 d-block uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">1. Pick a Date</label>
            <input type="date" name="appointment_date" id="wiz_date" class="form-control py-3 fw-bold shadow-none" min="{{ date('Y-m-d') }}" onchange="fetchTimeSlots()">

            {{-- Clinic Hours Information --}}
            <div class="mt-4">
                <h6 class="text-main smaller fw-bold uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Clinic Hours</h6>
                <ul class="list-unstyled smaller text-secondary mb-0" style="font-size: 0.8rem;">
                    <li class="mb-1.5 d-flex justify-content-between border-bottom border-secondary border-opacity-10 pb-1">
                        <span>Mon - Sat:</span>
                        <span class="text-main fw-bold">08:00 AM - 05:00 PM</span>
                    </li>
                    <li class="mb-1.5 d-flex justify-content-between border-bottom border-secondary border-opacity-10 pb-1">
                        <span>Lunch Break:</span>
                        <span class="text-warning italic fw-bold">12:00 PM - 01:00 PM</span>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Sunday:</span>
                        <span class="text-danger uppercase fw-bold">Closed</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Time Slot Selection (Col 7) --}}
        <div class="col-md-7 ps-md-4">
            <label class="small text-accent fw-bold mb-2 d-block uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">2. Choose Time Block</label>
            
            <div id="wiz_slots_container" class="row g-2 overflow-auto" style="max-height: 400px;">
                {{-- Initial Placeholder --}}
                <div class="col-12 py-5 text-center text-secondary border border-secondary border-opacity-25 border-dashed rounded" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                    <i class="bi bi-calendar-event fs-1 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">Please select a preferred date first<br>to view available time slots.</p>
                </div>
            </div>

            {{-- Legend --}}
            <div id="slot_legend" class="mt-3 d-none animate-fade-in">
                <div class="d-flex gap-3 justify-content-center">
                    <div class="smaller text-muted" style="font-size: 0.75rem;">
                        <span class="d-inline-block rounded bg-accent border border-accent me-1" style="width:10px; height:10px;"></span> Available
                    </div>
                    <div class="smaller text-muted" style="font-size: 0.75rem;">
                        <span class="d-inline-block rounded bg-danger opacity-25 me-1" style="width:10px; height:10px;"></span> Fully Booked
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="d-flex gap-2 mt-5">
        <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(3)">
            <i class="bi bi-arrow-left me-2"></i> BACK
        </button>
        <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="btn-to-page5" onclick="validateStep4()">
            NEXT: FINAL CHECKOUT <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</div>

<style>
    /* Radio Button Slot Styling */
    #wiz_slots_container .btn-check + .btn {
        border-color: var(--border-color);
        color: var(--text-muted);
        background-color: transparent;
        transition: 0.2s;
    }
    #wiz_slots_container .btn-check:not(:disabled) + .btn:hover {
        border-color: var(--brand-accent) !important;
        color: var(--brand-accent) !important;
        background-color: rgba(25, 211, 140, 0.05);
    }
    #wiz_slots_container .btn-check:checked + .btn {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        color: #1c232d !important;
        box-shadow: 0 0 10px var(--brand-accent);
    }
    #wiz_slots_container .btn:disabled {
        cursor: not-allowed;
        opacity: 0.2;
    }
</style>

<script>
    /**
     * Logic for visibility of the legend
     */
    function showSlotUI(hasSlots) {
        const legend = document.getElementById('slot_legend');
        if (legend) {
            if (hasSlots) {
                legend.classList.remove('d-none');
            } else {
                legend.classList.add('d-none');
            }
        }
    }

    /**
     * Triggered on clock slot radio change
     */
    function handleSlotSelection() {
        const selectedRadio = document.querySelector('input[name="time_slot"]:checked');
        if (selectedRadio) {
            const date = document.getElementById('wiz_date').value;
            const timeLabel = selectedRadio.nextElementSibling.innerText;
            setSchedule(date, timeLabel);
        }
    }

    /**
     * Overridden robust slot-fetching with dynamic validation and Step 4 redirect logic
     */
    async function fetchTimeSlots() {
        const dateInput = document.getElementById('wiz_date');
        if (!dateInput) return;
        
        const date = dateInput.value;
        const container = document.getElementById('wiz_slots_container');
        if (!date || !container) return;

        container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-neon"></div></div>';

        try {
            const res = await fetch(`/api/check-slots?date=${date}`);
            const data = await res.json();
            
            if (data.is_closed) {
                container.innerHTML = '<div class="col-12 py-5 text-center text-danger border border-danger border-dashed rounded">Clinic Closed</div>';
                showSlotUI(false);
                return;
            }

            let html = '';
            let start = new Date(`2000-01-01 ${data.config.opening_time}`);
            let end = new Date(`2000-01-01 ${data.config.closing_time}`);
            let availableCount = 0;
            const now = new Date();
            const todayLocal = now.toLocaleDateString('en-CA');

            // Retrieve draft properties to map selection states
            const draft = JSON.parse(localStorage.getItem('appointment_draft') || '{}');
            const savedSlot = draft['time_slot'] || '';

            while (start < end) {
                let tStr = start.toTimeString().split(' ')[0];
                let disp = start.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});

                let isFull = (data.full_slots || []).includes(tStr);
                let isLunch = (data.config.has_lunch_break && tStr >= data.config.lunch_start && tStr < data.config.lunch_end);

                let isPast = false;
                if (date === todayLocal) {
                    const leadTimeMs = (parseInt(data.config.lead_time_hours) || 0) * 3600 * 1000;
                    const cutoffTime = now.getTime() + leadTimeMs;
                    const slotDate = new Date(`${date}T${tStr}`);
                    isPast = slotDate.getTime() < cutoffTime;
                }

                if (!isLunch && !isPast) {
                    const isChecked = (tStr === savedSlot) ? 'checked' : '';
                    html += `<div class="col-4">
                        <input type="radio" class="btn-check" name="time_slot" id="slot_${tStr}" value="${tStr}" ${isFull ? 'disabled' : ''} ${isChecked} onchange="handleSlotSelection()">
                        <label class="btn ${isFull ? 'btn-danger opacity-25' : 'btn-outline-neon'} btn-sm w-100 py-2 fw-bold" for="slot_${tStr}">${disp}</label>
                    </div>`;
                    availableCount++;
                }
                start.setMinutes(start.getMinutes() + data.config.slot_duration);
            }

            if (availableCount > 0) {
                container.innerHTML = html;
                showSlotUI(true);

                // Auto-evaluate restored slot persistence
                const selectedRadio = container.querySelector('input[name="time_slot"]:checked');
                if (selectedRadio) {
                    handleSlotSelection();
                } else if (savedSlot) {
                    // Previously saved slot has been booked or expired. Clear state and redirect back to Step 4
                    draft['time_slot'] = '';
                    localStorage.setItem('appointment_draft', JSON.stringify(draft));
                    
                    const sumSchedule = document.getElementById('sum_schedule');
                    if (sumSchedule) sumSchedule.classList.add('d-none');
                    
                    if (typeof goToPage === 'function') goToPage(4);
                }
            } else {
                container.innerHTML = '<div class="col-12 py-5 text-center text-warning border border-warning border-dashed rounded"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>No available slots remaining for today. Please pick another date.</div>';
                showSlotUI(false);
                
                if (savedSlot) {
                    draft['time_slot'] = '';
                    localStorage.setItem('appointment_draft', JSON.stringify(draft));
                    if (typeof goToPage === 'function') goToPage(4);
                }
            }
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="col-12 text-center py-4 text-danger">Error loading slots.</div>';
            showSlotUI(false);
        }
    }

    // Bind overridden method globally
    window.fetchTimeSlots = fetchTimeSlots;

    /**
     * Defensive Lifecycle Sync Engine (Guarantees robust draft state validation on page reload)
     */
    document.addEventListener('DOMContentLoaded', async () => {
        const draft = JSON.parse(localStorage.getItem('appointment_draft') || '{}');
        const savedDate = draft['appointment_date'] || '';
        const savedSlot = draft['time_slot'] || '';
        const savedStep = parseInt(localStorage.getItem('appointment_step') || '1');

        if (savedDate && savedSlot) {
            try {
                // Perform a strict background validation of the selected slot
                const res = await fetch(`/api/check-slots?date=${savedDate}`);
                const data = await res.json();
                
                const isClosed = data.is_closed || false;
                const isFull = (data.full_slots || []).includes(savedSlot);
                
                let isPast = false;
                const now = new Date();
                const todayLocal = now.toLocaleDateString('en-CA');
                if (savedDate === todayLocal && data.config) {
                    const leadTimeMs = (parseInt(data.config.lead_time_hours) || 0) * 3600 * 1000;
                    const cutoffTime = now.getTime() + leadTimeMs;
                    const slotDate = new Date(`${savedDate}T${savedSlot}`);
                    isPast = slotDate.getTime() < cutoffTime;
                }

                const isLunch = data.config && data.config.has_lunch_break && savedSlot >= data.config.lunch_start && savedSlot < data.config.lunch_end;
                const isSlotStillAvailable = !isClosed && !isFull && !isPast && !isLunch;

                if (!isSlotStillAvailable) {
                    // Slot is invalid or no longer available! Reset values
                    draft['time_slot'] = '';
                    localStorage.setItem('appointment_draft', JSON.stringify(draft));
                    
                    const sumSchedule = document.getElementById('sum_schedule');
                    if (sumSchedule) sumSchedule.classList.add('d-none');
                    
                    // Force the step back to Step 4
                    localStorage.setItem('appointment_step', '4');
                    if (typeof goToPage === 'function') {
                        goToPage(4);
                    }
                } else {
                    // Slot remains valid. Populate display summary immediately to prevent flickering
                    if (typeof setSchedule === 'function') {
                        const tempDate = new Date(`2000-01-01 ${savedSlot}`);
                        const timeLabel = tempDate.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                        setSchedule(savedDate, timeLabel);
                    }
                    // Retain user's deep-linked page position safely
                    if (savedStep && typeof goToPage === 'function') {
                        goToPage(savedStep);
                    }
                }
            } catch (err) {
                console.error("Background slot validation failed:", err);
            }
        } else if (savedStep > 3) {
            // Force return to Step 4 if step parameter exists but details are missing
            localStorage.setItem('appointment_step', '4');
            if (typeof goToPage === 'function') {
                goToPage(4);
            }
        }
    });
</script>