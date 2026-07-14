{{-- RIGHT: STICKY SUMMARY SIDEBAR --}}
<div class="sticky-top text-start" style="top: 100px;">

    {{-- Header --}}
    <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div class="bg-secondary bg-opacity-10 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-neon" style="width:42px; height:42px;">
            <i class="bi bi-journal-check fs-5 text-accent"></i>
        </div>
        <div>
            <h5 class="text-main fw-bold mb-0 uppercase tracking-tighter" style="font-size: 1.1rem; letter-spacing: 0.5px;">Summary</h5>
            <small class="text-secondary smaller uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Live Preview</small>
        </div>
    </div>

    {{-- Patient Context --}}
    <div class="mb-4">
        <label class="smaller text-muted d-block uppercase fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Patient</label>
        <div class="text-main fw-bold fs-5 text-truncate" id="sum_name">---</div>
        <div class="text-accent smaller fw-bold uppercase" id="sum_patient_type" style="font-size: 0.75rem;">None selected</div>
    </div>

    {{-- Selected Tests List --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="smaller text-muted uppercase fw-bold mb-0" style="font-size: 0.7rem; letter-spacing: 1px;">Clinical Tests</label>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 0.65rem; padding: 4px 8px;" id="test_count_badge">0</span>
        </div>

        {{-- Populated by updateSummary() JS --}}
        <div id="sum_tests" class="text-main small custom-scroll pe-1" style="max-height: 200px; overflow-y: auto;">
            <div class="italic text-muted opacity-50">No tests selected yet.</div>
        </div>
    </div>

    {{-- Schedule Details (Hidden until Step 4) --}}
    <div id="sum_schedule" class="mb-4 d-none animate-fade-in">
        <label class="smaller text-muted d-block uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">Schedule</label>
        <div class="p-3 border border-secondary border-opacity-25 rounded" style="background-color: rgba(108, 117, 125, 0.05) !important;">
            <div class="text-accent fw-bold small mb-1">
                <i class="bi bi-calendar3 me-2"></i><span id="sum_date">---</span>
            </div>
            <div class="text-accent fw-bold small">
                <i class="bi bi-clock me-2"></i><span id="sum_time">---</span>
            </div>
        </div>
    </div>

    <hr class="border-secondary border-opacity-25 my-4">

    {{-- Financial Footer --}}
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <span class="text-main fw-bold uppercase smaller d-block" style="font-size: 0.75rem; letter-spacing: 1px;">Total Bill</span>
            <small class="text-muted smaller" style="font-size: 0.65rem;">Estimated Charge</small>
        </div>
        <div class="text-end">
            <span class="text-accent fs-2 fw-bold tracking-tighter">&#x20B1;<span id="sum_total">0.00</span></span>
        </div>
    </div>

    {{-- Step Helper --}}
    <div class="mt-4 pt-4 border-top border-secondary border-opacity-25">
        <div class="d-flex align-items-center text-secondary smaller" style="font-size: 0.75rem;">
            <i class="bi bi-shield-lock-fill me-2 text-muted"></i>
            <span>Secure clinical reservation</span>
        </div>
    </div>

</div>

<style>
/* Summary specific styles */
#sum_tests div:last-child {
    margin-bottom: 0 !important;
}
.custom-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.05);
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: var(--brand-accent);
    border-radius: 10px;
}
.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}
@keyframes fadeIn { 
    from { opacity: 0; } 
    to { opacity: 1; } 
}
</style>

<script>
/**
 * Small helper to update the badge count in the summary sidebar.
 */
function updateTestBadge() {
    const count = document.querySelectorAll('.test-checkbox:checked').length;
    const badge = document.getElementById('test_count_badge');
    if(badge) {
        badge.innerText = count;
    }
}
</script>