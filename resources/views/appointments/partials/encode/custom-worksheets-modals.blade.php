{{-- C. DYNAMIC DUAL-MODALS FOR CUSTOM WORKSHEETS --}}
@foreach($appointment->result->customWorkstationResults as $custom)

    {{-- 1. EDIT CUSTOM WORKSHEET DETAILS MODAL --}}
    <div class="modal fade" id="editCustomWorksheetModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.update', [$appointment->id, $custom->id]) }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('PUT')
                
                <h5 class="text-accent fw-bold mb-1 uppercase">Edit Worksheet: {{ $custom->name }}</h5>
                <p class="text-secondary small mb-4">Modify the file or certificate identifier mapped to this folder.</p>
                
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Certificate No.</label>
                    <input type="text" name="cert_no" class="form-control" value="{{ $custom->cert_no }}" required>
                </div>
                
                <div class="mb-4 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Replace Scan File</label>
                    <input type="file" name="scan_file" class="form-control" accept="image/*, application/pdf">
                    <small class="text-muted mt-1 d-block">Leave blank to keep the current file.</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-accent flex-grow-1 fw-bold uppercase">SAVE CHANGES</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. VERIFY / CLINICAL SIGN-OFF MODAL --}}
    <div class="modal fade" id="verifyCustomModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.verify', [$appointment->id, $custom->id]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                
                <h5 class="text-neon fw-bold mb-1 uppercase">Sign-Off: {{ $custom->name }}</h5>
                <p class="text-secondary small mb-4">Enter your name to verify that the uploaded document matches the patient's record details.</p>
                
                <div class="mb-4 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Verifier Full Name</label>
                    <input type="text" name="sig_name" class="form-control" value="{{ auth()->user()->name }}" required>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-neon flex-grow-1 fw-bold uppercase">Approve & Sign</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. RETURN TO ENCODING QUEUE MODAL --}}
    <div class="modal fade" id="returnCustomModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.return', [$appointment->id, $custom->id]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                
                <h5 class="text-danger fw-bold mb-1 uppercase">Return Worksheet: {{ $custom->name }}</h5>
                <p class="text-secondary small mb-4">Describe the necessary corrections for the encoder.</p>
                
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Reason for Return</label>
                    <select id="return_custom_reason_select_{{ $custom->id }}" name="reason" class="form-select" required>
                        <option value="" disabled selected>-- Select a return justification --</option>
                        <option value="Mismatched patient identification or demographic fields">Mismatched patient identification or demographic fields</option>
                        <option value="Unclear, low-quality, or blurry document scan">Unclear, low-quality, or blurry document scan</option>
                        <option value="Incorrect reference value / Certificate No.">Incorrect reference value / Certificate No.</option>
                        <option value="Discrepancies in clinical signature or credentials">Discrepancies in clinical signature or credentials</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                
                <div id="return_custom_custom_reason_wrapper_{{ $custom->id }}" class="mb-4 text-start d-none">
                    <label class="smaller fw-bold uppercase mb-1">Specify Custom Correction Reason</label>
                    <textarea id="return_custom_custom_reason_{{ $custom->id }}" class="form-control" rows="4" placeholder="Identify the specific adjustment required..."></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-danger flex-grow-1 fw-bold uppercase">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 4. THEMED DELETION AUDIT-COMPLIANT MODAL --}}
    <div class="modal fade" id="deleteCustomWorksheetModal_{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.destroy', [$appointment->id, $custom->id]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('DELETE')
                
                <h5 class="text-danger fw-bold mb-1 uppercase">
                    <i class="bi bi-trash me-2"></i>Delete Custom Worksheet?
                </h5>
                <p class="text-secondary small mb-4">You are about to delete the <strong>{{ $custom->name }}</strong> worksheet. This action permanently deletes all associated documents.</p>
                
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1 text-danger">Select Deletion Audit Reason</label>
                    <select id="delete_custom_reason_select_{{ $custom->id }}" name="reason" class="form-select" required>
                        <option value="" disabled selected>-- Select a valid justification --</option>
                        <option value="Accidental creation / Duplicate worksheet">Accidental creation / Duplicate worksheet</option>
                        <option value="Patient requested test cancellation">Patient requested test cancellation</option>
                        <option value="Clinician order revised / Modified billing">Clinician order revised / Modified billing</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                
                <div id="delete_custom_custom_reason_wrapper_{{ $custom->id }}" class="mb-4 text-start d-none">
                    <label class="smaller fw-bold uppercase mb-1 text-danger">Specify Custom Deletion Reason</label>
                    <textarea id="delete_custom_custom_reason_{{ $custom->id }}" class="form-control" rows="2" placeholder="Explain the deletion justification..."></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-danger flex-grow-1 fw-bold uppercase">CONFIRM DELETE</button>
                </div>
            </form>
        </div>
    </div>

@endforeach

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Dynamic Delete Custom Workstation custom reason name-toggling
        document.querySelectorAll('[id^="delete_custom_reason_select_"]').forEach(select => {
            select.addEventListener('change', function() {
                const id = this.id.replace('delete_custom_reason_select_', '');
                const wrapper = document.getElementById(`delete_custom_custom_reason_wrapper_${id}`);
                const textarea = document.getElementById(`delete_custom_custom_reason_${id}`);
                
                if (this.value === 'Others') {
                    if (wrapper) wrapper.classList.remove('d-none');
                    if (textarea) {
                        textarea.setAttribute('required', 'required');
                        textarea.setAttribute('name', 'reason');
                    }
                    this.removeAttribute('name');
                    if (textarea) textarea.value = '';
                } else {
                    if (wrapper) wrapper.classList.add('d-none');
                    if (textarea) {
                        textarea.removeAttribute('required');
                        textarea.removeAttribute('name');
                    }
                    this.setAttribute('name', 'reason');
                }
            });
        });

        // Dynamic Return Custom Workstation custom reason name-toggling
        document.querySelectorAll('[id^="return_custom_reason_select_"]').forEach(select => {
            select.addEventListener('change', function() {
                const id = this.id.replace('return_custom_reason_select_', '');
                const wrapper = document.getElementById(`return_custom_custom_reason_wrapper_${id}`);
                const textarea = document.getElementById(`return_custom_custom_reason_${id}`);
                
                if (this.value === 'Others') {
                    if (wrapper) wrapper.classList.remove('d-none');
                    if (textarea) {
                        textarea.setAttribute('required', 'required');
                        textarea.setAttribute('name', 'reason');
                    }
                    this.removeAttribute('name');
                    if (textarea) textarea.value = '';
                } else {
                    if (wrapper) wrapper.classList.add('d-none');
                    if (textarea) {
                        textarea.removeAttribute('required');
                        textarea.removeAttribute('name');
                    }
                    this.setAttribute('name', 'reason');
                }
            });
        });
    });
</script>
@endpush