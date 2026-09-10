<div id="pane-excel" style="display: none;">
    <div class="row g-4 text-start">
        
        {{-- Step A: Download Template --}}
        <div class="col-md-5">
            <div class="card p-4 h-100 border-secondary bg-card shadow-lg d-flex flex-column justify-content-between">
                <div>
                    <h5 class="text-main fw-bold mb-2 small uppercase" style="letter-spacing: 1px;">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-accent me-2"></i>DOWNLOAD TEMPLATE
                    </h5>
                    <p class="text-muted small mb-3">Download our pre-formatted Microsoft Excel template equipped with built-in data validations and cell guidance prompts.</p>
                    
                    {{-- Reminder / Duplicate Row Guidance Notice --}}
                    <div class="alert alert-clinical p-3 mb-3 border border-secondary border-opacity-10 rounded-3 text-start" style="background-color: rgba(25, 211, 140, 0.04);">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-copy text-accent fs-6"></i>
                            <strong class="text-accent small uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Formatting & Row Copy Reminder:</strong>
                        </div>
                        <p class="small text-muted mb-0" style="font-size: 0.75rem; line-height: 1.45;">
                            When entering multiple patients, please <strong>copy and duplicate the sample guide row</strong> down across your spreadsheet. This ensures all cell dropdowns, date formatting rules (YYYY-MM-DD), and phone number leading zeros are strictly preserved.
                        </p>
                    </div>
                </div>
                
                <div class="d-grid">
                    <a href="{{ route('appointments.bulk.template') }}" class="btn-custom btn-accent py-3 fw-bold uppercase shadow-sm">
                        <i class="bi bi-download me-2"></i> DOWNLOAD .XLSX TEMPLATE
                    </a>
                </div>
            </div>
        </div>

        {{-- Step B: Upload File --}}
        <div class="col-md-7">
            <div class="card p-4 h-100 border-secondary bg-card shadow-lg d-flex flex-column justify-content-between">
                <div>
                    <h5 class="text-main fw-bold mb-2 small uppercase" style="letter-spacing: 1px;">
                        <i class="bi bi-cloud-arrow-up-fill text-accent me-2"></i>IMPORT DATA TO FORM
                    </h5>
                    <p class="text-muted small mb-3">Upload your populated <code>.xlsx</code> or <code>.xls</code> spreadsheet to automatically render and compile all patient rows into the manual table.</p>
                    
                    <div class="p-5 text-center border border-secondary border-opacity-25 border-dashed rounded mb-4 bg-secondary bg-opacity-5" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                        <i class="bi bi-file-earmark-excel text-accent display-4 mb-3 d-block"></i>
                        
                        <input type="file" id="excel_file_input" class="form-control bg-card text-main mx-auto shadow-none mb-2" style="max-width: 340px;" accept=".xlsx, .xls">
                        <p class="text-secondary smaller mb-0 italic" style="font-size: 0.75rem;">Supported format: Microsoft Excel (.xlsx, .xls)</p>
                    </div>
                </div>

                <button type="button" id="importBtn" onclick="importExcelData()" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase shadow-sm">
                    LOAD DATA INTO MANUAL FORM <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </div>
        </div>

    </div>
</div>