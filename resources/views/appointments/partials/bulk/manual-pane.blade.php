<div id="pane-manual">
    <div class="card border-secondary p-0 shadow-lg overflow-hidden bg-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 1140px;">
                {{-- FIXED: Added strict width and min-width properties to headers to block browser auto-squeezing --}}
                <thead class="bg-black text-secondary uppercase small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4" style="width: 320px; min-width: 320px;">Patient Info</th>
                        <th style="width: 300px; min-width: 300px;">Address</th>
                        <th style="width: 180px; min-width: 180px;">Tests</th>
                        <th style="width: 220px; min-width: 220px;">Schedule Slot</th>
                        <th class="pe-4 text-center" style="width: 120px; min-width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody id="rowContainer">
                    {{-- Row entries added dynamically via JavaScript addRow() --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- Spreadsheet Actions --}}
    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
        <button type="button" class="btn-custom btn-outline-accent px-4" onclick="addRow()">
            <i class="bi bi-plus-lg me-1"></i> ADD PATIENT
        </button>
        <button type="button" id="smartSchedBtn" class="btn-custom btn-outline-accent px-4" onclick="runSmartScheduler()">
            <i class="bi bi-cpu me-1"></i> SMART AUTO-TIME
        </button>
    </div>
</div>