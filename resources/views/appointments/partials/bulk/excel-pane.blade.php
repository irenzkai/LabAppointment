<div id="pane-excel" style="display: none;">
    <div class="row g-4 text-start">
        
        {{-- Step A: Download Template --}}
        <div class="col-md-5">
            <div class="card p-4 h-100 border-secondary bg-card shadow-lg">
                <h5 class="text-main fw-bold mb-3 small uppercase" style="letter-spacing: 1px;">DOWNLOAD TEMPLATE</h5>
                <p class="text-muted small mb-4">Download our bulk booking template. Enter patient information and schedule parameters, and save.</p>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('appointments.bulk.template', 'csv') }}" class="btn-custom btn-outline-accent py-3 shadow-none">
                        <i class="bi bi-filetype-csv me-2"></i> DOWNLOAD .CSV
                    </a>
                    <a href="{{ route('appointments.bulk.template', 'xlsx') }}" class="btn-custom btn-outline-accent py-3 shadow-none">
                        <i class="bi bi-filetype-xlsx me-2"></i> DOWNLOAD .XLSX
                    </a>
                </div>
            </div>
        </div>

        {{-- Step B: Upload File --}}
        <div class="col-md-7">
            <div class="card p-4 h-100 border-secondary bg-card shadow-lg">
                <h5 class="text-main fw-bold mb-3 small uppercase" style="letter-spacing: 1px;">IMPORT DATA TO FORM</h5>
                
                <div class="p-5 text-center border border-secondary border-opacity-25 border-dashed rounded mb-4 bg-secondary bg-opacity-5" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                    <i class="bi bi-file-earmark-arrow-up text-accent display-4 mb-3 d-block"></i>
                    
                    <input type="file" id="excel_file_input" class="form-control bg-card text-main mx-auto shadow-none mb-2" style="max-width: 320px;" accept=".xlsx, .xls, .csv">
                    <p class="text-secondary smaller mb-0 italic" style="font-size: 0.75rem;">Supported formats: Excel (.xlsx, .xls) or CSV files.</p>
                </div>

                <button type="button" id="importBtn" onclick="importExcelData()" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase">
                    LOAD DATA INTO MANUAL FORM <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </div>
        </div>
        
    </div>
</div>