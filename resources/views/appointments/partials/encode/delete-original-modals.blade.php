@php
    // Defensive initialization to resolve IDE and static analysis undefined variable warnings
    $selectedTypes = $selectedTypes ?? $autoReportTypes ?? [];
@endphp

{{-- D. DYNAMIC MODALS FOR DELETING ORIGINAL WORKSTATIONS (Requires Audit Reason) --}}
@foreach($selectedTypes as $type)
<div class="modal fade" id="deleteOriginalWorkstationModal_{{ $type }}" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.destroy-original', [$appointment->id, $type]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            @csrf
            @method('DELETE')
            
            <h5 class="text-danger fw-bold mb-1 uppercase">
                <i class="bi bi-trash me-2"></i>Delete Workstation Card?
            </h5>
            <p class="text-secondary small mb-4">
                You are about to delete the 
                <strong>
                    {{ strtoupper($type == 'med_cert' ? 'Medical Certificate' : ($type == 'radio' ? 'Radiology Report' : ($type == 'drug' ? 'Drug Test' : 'Laboratory Result'))) }}
                </strong> 
                workstation. This cleans up all temporary data snapshots.
            </p>
            
            <div class="mb-3 text-start">
                <label class="smaller fw-bold uppercase mb-1 text-danger">Select Deletion Audit Reason</label>
                <select id="delete_original_reason_select_{{ $type }}" name="reason" class="form-select" required>
                    <option value="" disabled selected>-- Select a valid justification --</option>
                    <option value="Accidental creation / Duplicate workstation">Accidental creation / Duplicate workstation</option>
                    <option value="Patient requested test cancellation">Patient requested test cancellation</option>
                    <option value="Clinician order revised / Modified billing">Clinician order revised / Modified billing</option>
                    <option value="Others">Others (Specify below)</option>
                </select>
            </div>
            
            <div id="delete_original_custom_reason_wrapper_{{ $type }}" class="mb-4 text-start d-none">
                <label class="smaller fw-bold uppercase mb-1 text-danger">Specify Custom Deletion Reason</label>
                <textarea id="delete_original_custom_reason_{{ $type }}" class="form-control" rows="2" placeholder="Explain the deletion justification..."></textarea>
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
        // Dynamic Delete Original Workstation custom reason name-toggling
        document.querySelectorAll('[id^="delete_original_reason_select_"]').forEach(select => {
            select.addEventListener('change', function() {
                const type = this.id.replace('delete_original_reason_select_', '');
                const wrapper = document.getElementById(`delete_original_custom_reason_wrapper_${type}`);
                const textarea = document.getElementById(`delete_original_custom_reason_${type}`);
                
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