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
                    <div class="smaller text-muted" style="font-size: 0.75rem;"><span class="d-inline-block rounded bg-accent border border-accent me-1" style="width:10px; height:10px;"></span> Available</div>
                    <div class="smaller text-muted" style="font-size: 0.75rem;"><span class="d-inline-block rounded bg-danger opacity-25 me-1" style="width:10px; height:10px;"></span> Fully Booked</div>
                </div>
            </div>
        </div>
        
    </div>

    {{-- Navigation --}}
    <div class="d-flex gap-2 mt-5">
        <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(3)">
            <i class="bi bi-arrow-left me-2"></i> BACK
        </button>
        {{-- FIXED: Removed default disabled attribute and pointed click handler to validateStep4() --}}
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
</script>