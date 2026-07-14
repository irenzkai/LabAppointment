{{-- RIGHT: STICKY BATCH SUMMARY SIDEBAR --}}
<div class="sticky-top text-start" style="top: 100px;">

    {{-- Header --}}
    <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div class="bg-secondary bg-opacity-10 rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-neon" style="width:42px; height:42px;">
            <i class="bi bi-buildings fs-5 text-accent"></i>
        </div>
        <div>
            <h5 class="text-main fw-bold mb-0 uppercase tracking-tighter" style="font-size: 1.1rem; letter-spacing: 0.5px;">Batch Summary</h5>
            <small class="text-secondary smaller uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Live Preview</small>
        </div>
    </div>

    {{-- Organization Context --}}
    <div class="mb-3.5">
        <label class="smaller text-muted d-block uppercase fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Requesting Entity</label>
        <div class="text-main fw-bold fs-5 text-truncate" id="sum_org">---</div>
        <div class="text-accent smaller fw-bold uppercase" id="sum_pax_count" style="font-size: 0.75rem;">0 PATIENTS ADDED</div>
    </div>

    {{-- FIXED: Interactive scrollable directory layout --}}
    <div id="sum_pax_list_container" class="mt-3 mb-3 d-none animate-fade-in">
        <label class="smaller text-muted d-block uppercase fw-bold mb-1.5" style="font-size: 0.7rem; letter-spacing: 1px;">Patient Directory</label>
        <div id="sum_pax_list" class="overflow-auto custom-scroll p-2 border border-secondary border-opacity-10 rounded" style="max-height: 140px; background-color: rgba(0, 0, 0, 0.05);">
            <!-- Populated dynamically via updateBulkSummary() -->
        </div>
    </div>

    {{-- Schedule Details (Hidden until Step 1 date is picked) --}}
    <div id="sum_schedule" class="mb-4 d-none animate-fade-in">
        <label class="smaller text-muted d-block uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">Batch Start Date</label>
        <div class="p-3 border border-secondary border-opacity-25 rounded" style="background-color: rgba(108, 117, 125, 0.05) !important;">
            <div class="text-accent fw-bold small">
                <i class="bi bi-calendar3 me-2"></i><span id="sum_date">---</span>
            </div>
        </div>
    </div>

    <hr class="border-secondary border-opacity-25 my-4">

    {{-- Financial Footer --}}
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <span class="text-main fw-bold uppercase smaller d-block" style="font-size: 0.75rem; letter-spacing: 1px;">Batch Total Bill</span>
            <small class="text-muted smaller" style="font-size: 0.65rem;">Estimated Collective Charge</small>
        </div>
        <div class="text-end">
            <span class="text-accent fs-2 fw-bold tracking-tighter">&#x20B1;<span id="sum_total">0.00</span></span>
        </div>
    </div>

    {{-- Step Helper --}}
    <div class="mt-4 pt-4 border-top border-secondary border-opacity-25">
        <div class="d-flex align-items-center text-secondary smaller" style="font-size: 0.75rem;">
            <i class="bi bi-shield-lock-fill me-2 text-muted"></i>
            <span>Secure collective reservation</span>
        </div>
    </div>

</div>

<style>
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