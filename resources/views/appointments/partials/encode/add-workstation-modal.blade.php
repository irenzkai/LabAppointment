@php
// Defensive initialization to resolve IDE and static analysis undefined variable warnings
$selectedTypes = $selectedTypes ?? $autoReportTypes ?? [];
$isReadonly = $isReadonly ?? false;
@endphp

{{-- B. UNIFIED ADD WORKSTATION MODAL (Prevents adding existing worksheets) --}}
@if(!$isReadonly && auth()->user()->isEmployee())
<div class="modal fade" id="addWorkstationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.add', $appointment->id) }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            
            <h5 class="text-accent fw-bold mb-1 uppercase">
                <i class="bi bi-plus-circle me-2"></i>Add Workstation
            </h5>
            <p class="text-secondary small mb-4">Attach an original clinical workstation or a custom dynamic worksheet scoped to this folder.</p>
            
            <div class="mb-3 text-start">
                <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Workstation Type</label>
                <select name="workstation_type" id="add_workstation_type" class="form-select" required>
                    <option value="" disabled selected>-- Select Workstation Type --</option>
                    <option value="lab" {{ in_array('lab', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>
                        Laboratory Result {{ in_array('lab', $selectedTypes) ? '(Already Active)' : '' }}
                    </option>
                    <option value="radio" {{ in_array('radio', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>
                        Radiology Report {{ in_array('radio', $selectedTypes) ? '(Already Active)' : '' }}
                    </option>
                    <option value="drug" {{ in_array('drug', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>
                        Drug Test Result {{ in_array('drug', $selectedTypes) ? '(Already Active)' : '' }}
                    </option>
                    <option value="med_cert" {{ in_array('med_cert', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>
                        Medical Certificate {{ in_array('med_cert', $selectedTypes) ? '(Already Active)' : '' }}
                    </option>
                    <option value="custom">Custom Worksheet</option>
                </select>
            </div>

            {{-- Custom Dynamic Worksheet fields (Hidden initially) --}}
            <div id="custom_fields_container" class="d-none">
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Worksheet Name</label>
                    <input type="text" name="custom_name" id="add_custom_name" class="form-control" placeholder="e.g. Dental Clearance, ECG">
                </div>
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Certificate/Reference No.</label>
                    <input type="text" name="cert_no" id="add_cert_no" class="form-control" placeholder="Enter reference tracking ID">
                </div>
                
                {{-- Scan File Upload with View & Remove Capabilities --}}
                <div class="mb-4 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Scan File Upload</label>
                    
                    <div id="add_scan_input_wrapper">
                        <input type="file" name="scan_file" id="add_scan_file" class="form-control" accept="image/*, application/pdf" onchange="handleAddScanUpload(this)">
                    </div>

                    {{-- File Preview Card --}}
                    <div id="add_scan_preview_container" class="d-none mt-2 p-2.5 rounded border border-secondary border-opacity-10" style="background-color: rgba(25, 211, 140, 0.03);">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="small text-accent fw-bold text-truncate pe-2" id="add_scan_file_label">
                                <i class="bi bi-file-earmark-check-fill me-1"></i>Selected File
                            </span>
                            <div class="d-flex gap-1.5 flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 fw-bold" id="btn_view_add_scan" style="font-size: 0.75rem;">View</button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold" onclick="removeAddScanUpload()" style="font-size: 0.75rem;">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn btn-accent flex-grow-1 fw-bold uppercase">CONFIRM ADD WORKSTATION</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
let addScanLocalData = null;

function handleAddScanUpload(input) {
    const file = input.files[0];
    const inputWrapper = document.getElementById('add_scan_input_wrapper');
    const previewContainer = document.getElementById('add_scan_preview_container');
    const label = document.getElementById('add_scan_file_label');
    const viewBtn = document.getElementById('btn_view_add_scan');

    if (!file) {
        removeAddScanUpload();
        return;
    }

    if (inputWrapper) inputWrapper.classList.add('d-none');
    if (previewContainer) previewContainer.classList.remove('d-none');
    if (label) label.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>${file.name}`;

    const reader = new FileReader();
    reader.onload = function(e) {
        addScanLocalData = e.target.result;
        if (viewBtn) {
            viewBtn.onclick = function() {
                if (typeof openFilePreview === 'function') {
                    openFilePreview(addScanLocalData, file.name);
                } else if (typeof window.zoomQR === 'function') {
                    window.zoomQR(addScanLocalData);
                }
            };
        }
    };
    reader.readAsDataURL(file);
}

function removeAddScanUpload() {
    const input = document.getElementById('add_scan_file');
    const inputWrapper = document.getElementById('add_scan_input_wrapper');
    const previewContainer = document.getElementById('add_scan_preview_container');
    
    if (input) input.value = '';
    if (inputWrapper) inputWrapper.classList.remove('d-none');
    if (previewContainer) previewContainer.classList.add('d-none');
    addScanLocalData = null;
}

document.addEventListener('DOMContentLoaded', () => {
    const selectElement = document.getElementById('add_workstation_type');
    const customContainer = document.getElementById('custom_fields_container');

    if (selectElement && customContainer) {
        selectElement.addEventListener('change', function() {
            const type = this.value;
            const customInputs = customContainer.querySelectorAll('input:not([type="file"])');
            
            if (type === 'custom') {
                customContainer.classList.remove('d-none');
                customInputs.forEach(input => {
                    input.setAttribute('required', 'required');
                    input.disabled = false;
                });
            } else {
                customContainer.classList.add('d-none');
                customInputs.forEach(input => {
                    input.removeAttribute('required');
                    input.disabled = true;
                    input.value = ''; // Reset input value to prevent stale data
                });
                removeAddScanUpload();
            }
        });
    }
});
</script>
@endpush