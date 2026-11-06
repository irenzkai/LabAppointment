<!-- resources/views/appointments/partials/wizard/summary.blade.php -->
{{-- RIGHT: STICKY SUMMARY SIDEBAR --}}
<div class="sticky-top text-start" style="top: 90px;">
    {{-- Header --}}
    <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25" style="border-color: var(--border-color) !important;">
        <div class="bg-secondary bg-opacity-10 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; border: 1.5px solid var(--brand-accent);">
            <i class="bi bi-journal-check fs-5 text-accent"></i>
        </div>
        <div>
            <h5 class="text-main fw-bold mb-0 uppercase tracking-tighter" style="font-size: 1.1rem; letter-spacing: 0.5px;">Appointment Summary</h5>
            <small class="text-secondary smaller uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Live Verification</small>
        </div>
    </div>

    {{-- Patient Context --}}
    <div class="mb-4">
        <label class="smaller text-muted d-block uppercase fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.5px;">Patient</label>
        <div class="text-main fw-bold fs-5 text-truncate" id="sum_name">Not specified</div>
        <div class="text-accent smaller fw-bold uppercase mt-0.5" id="sum_patient_type" style="font-size: 0.72rem; letter-spacing: 0.5px;">Personal Account</div>
    </div>

    {{-- Selected Tests & Packages List --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="smaller text-muted uppercase fw-bold mb-0" style="font-size: 0.68rem; letter-spacing: 0.5px;">Selected Tests & Packages</label>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 0.65rem; padding: 4px 8px;" id="test_count_badge">0</span>
        </div>
        {{-- Populated dynamically by updateSummary() JS --}}
        <div id="sum_tests" class="text-main small custom-scroll pe-1" style="max-height: 220px; overflow-y: auto;">
            <div class="italic text-muted opacity-50 small">No tests or packages selected yet.</div>
        </div>
    </div>

    {{-- Schedule Details (Revealed in Step 4) --}}
    <div id="sum_schedule" class="mb-4 d-none animate-fade-in">
        <label class="smaller text-muted d-block uppercase fw-bold mb-2" style="font-size: 0.68rem; letter-spacing: 0.5px;">Reserved Schedule</label>
        <div class="p-3 border rounded-3" style="background-color: rgba(25, 211, 140, 0.04); border: 1.5px solid var(--border-color) !important;">
            <div class="text-main fw-bold small mb-1.5 d-flex align-items-center">
                <i class="bi bi-calendar3 text-accent me-2"></i><span id="sum_date">---</span>
            </div>
            <div class="text-accent fw-bold small d-flex align-items-center">
                <i class="bi bi-clock text-accent me-2"></i><span id="sum_time">---</span>
            </div>
        </div>
    </div>

    <hr class="border-secondary border-opacity-25 my-4" style="border-color: var(--border-color) !important;">

    {{-- Financial Footer --}}
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <span class="text-main fw-bold uppercase smaller d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Billing</span>
            <small class="text-muted smaller" style="font-size: 0.65rem;">Estimated Diagnostic Fee</small>
        </div>
        <div class="text-end">
            <span class="text-accent fs-2 fw-bold tracking-tighter">₱<span id="sum_total">0.00</span></span>
        </div>
    </div>

    {{-- Security Assurance Footer --}}
    <div class="mt-4 pt-3 border-top border-secondary border-opacity-15" style="border-color: var(--border-color) !important;">
        <div class="d-flex align-items-center text-muted smaller" style="font-size: 0.72rem;">
            <i class="bi bi-shield-lock-fill me-2 text-accent"></i>
            <span>DOH & RA 10173 Clinical Privacy Secured</span>
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
    background: rgba(0, 0, 0, 0.05);
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: var(--brand-accent);
    border-radius: 10px;
}
.animate-fade-in {
    animation: fadeIn 0.35s ease-in;
}
@keyframes fadeIn { 
    from { opacity: 0; transform: translateY(4px); } 
    to { opacity: 1; transform: translateY(0); } 
}
</style>

<script>
/**
 * Safe helper to recount active tests/packages if invoked independently.
 */
function updateTestBadge() {
    const checkedCount = document.querySelectorAll('.pkg-checkbox:checked, .service-checkbox:checked, .test-checkbox:checked').length;
    const badge = document.getElementById('test_count_badge');
    if (badge) {
        badge.innerText = checkedCount;
    }
}
</script>