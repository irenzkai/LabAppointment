{{-- dynamic custom worksheets listing --}}
@foreach($appointment->result->customWorkstationResults as $custom)
@php
    $isVerified = ($custom->status === 'verified');
    $hasFile = !empty($custom->scan_path);

    // Fetch audit logs for custom worksheets to render System Encoder/Verifier details
    $customAudit = $appointment->result->audits->where('workstation_type', "custom_{$custom->id}")->first();
    $customEncoderName = $customAudit->v1_by_name ?? '---';
    $customVerifierName = $customAudit->v2_by_name ?? '---';
    $customEncodedAt = $customAudit && $customAudit->v1_at ? \Carbon\Carbon::parse($customAudit->v1_at) : null;
    $customVerifiedAt = $customAudit && $customAudit->v2_at ? \Carbon\Carbon::parse($customAudit->v2_at) : null;

    // Define color states matching high-contrast clinic branding
    $badgeClass = match($custom->status) {
        'verified' => 'bg-success bg-opacity-10 text-success border-success',
        'encoded' => 'bg-info bg-opacity-10 text-info border-info',
        'returned' => 'bg-danger bg-opacity-10 text-danger border-danger',
        default => 'bg-secondary bg-opacity-10 text-secondary border-secondary'
    };
@endphp

<div class="col-md-6">
    <div class="card p-4 shadow-sm workstation-card h-100" style="background-color: var(--bg-card); color: var(--text-main); border-left: 4px solid {{ $custom->status === 'verified' ? '#19d38c' : ($custom->status === 'returned' ? '#dc3545' : '#0dcaf0') }} !important; border-top-color: var(--border-color); border-right-color: var(--border-color); border-bottom-color: var(--border-color);">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h5 class="fw-bold mb-0 uppercase" style="color: var(--text-main);">{{ $custom->name }}</h5>
                <small class="text-secondary x-small mt-0.5 d-block">Cert ID: <span class="text-accent font-monospace">#{{ $custom->cert_no }}</span></small>
            </div>
            <span class="badge px-3 py-2 uppercase small border {{ $badgeClass }}">
                {{ $custom->status === 'encoded' ? 'READY FOR SIGN OFF' : ($custom->status === 'returned' ? 'RE-EDIT REQUIRED' : strtoupper($custom->status)) }}
            </span>
        </div>

        {{-- Return Reason Alert --}}
        @if($custom->status === 'returned' && $custom->return_reason)
            <div class="alert bg-danger bg-opacity-10 border-danger text-danger py-2 px-3 small mb-3">
                <div class="fw-bold uppercase smaller mb-1" style="font-size: 0.65rem;">Correction Reason:</div>
                <div class="italic">"{{ $custom->return_reason }}"</div>
            </div>
        @endif

        {{-- System Audit Trail (Theme-Aware Container) --}}
        <div class="mb-4 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(0, 0, 0, 0.02);">
            <div class="row g-0">
                <div class="col-6 pe-2">
                    <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Encoder</label>
                    <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;">{{ $customEncoderName }}</span>
                    @if($customEncodedAt)
                        <span class="text-secondary smaller italic" style="font-size: 0.65rem;">
                            {{ $customEncodedAt->format('M d, Y | h:i A') }}
                        </span>
                    @endif
                </div>
                <div class="col-6 ps-3 border-start border-secondary border-opacity-25">
                    <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Verifier</label>
                    <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;">{{ $customVerifierName }}</span>
                    @if($customVerifiedAt)
                        <span class="text-secondary smaller italic" style="font-size: 0.65rem;">
                            {{ $customVerifiedAt->format('M d, Y | h:i A') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Action Controls based on Roles & States --}}
        <div class="d-flex gap-2 mt-auto">
            {{-- View Scan Lightbox trigger (Supports high-contrast hover styles) --}}
            @if($hasFile)
                <button type="button" class="btn btn-sm btn-outline-accent text-accent flex-grow-1 py-2 fw-bold small uppercase hover-dark-text" style="border-color: var(--brand-accent) !important;" onclick="zoomQR('{{ Storage::url($custom->scan_path) }}')">
                    <i class="bi bi-eye-fill me-1"></i> VIEW ATTACHED
                </button>
            @endif

            @if(auth()->user()->isEmployee())
                {{-- Verify action is only active if the overall folder is not released yet --}}
                @if(!$isReadonly && $custom->status === 'encoded')
                    @can('isLabTech')
                        <button type="button" class="btn btn-sm btn-accent flex-grow-1" data-bs-toggle="modal" data-bs-target="#verifyCustomModal{{ $custom->id }}">
                            <i class="bi bi-shield-check me-1"></i> VERIFY & APPROVE
                        </button>
                    @endcan
                @endif

                {{-- Always permit returning back to encoder even if the folder has been released --}}
                @if(in_array($custom->status, ['encoded', 'verified']))
                    @can('isLabTech')
                        <button type="button" class="btn btn-sm btn-outline-danger px-3 py-2 fw-bold small uppercase" data-bs-toggle="modal" data-bs-target="#returnCustomModal{{ $custom->id }}" title="Return for Correction">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    @endcan
                @endif

                {{-- Edit Trigger (Only visible if the folder is not released yet) --}}
                @if(!$isReadonly && ($custom->status === 'returned' || $custom->status === 'pending'))
                    <button type="button" class="btn btn-sm btn-outline-info px-3" data-bs-toggle="modal" data-bs-target="#editCustomWorksheetModal{{ $custom->id }}">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>

{{-- MODAL: EDIT CUSTOM WORKSHEET --}}
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

{{-- MODAL: VERIFY CUSTOM WORKSHEET --}}
<div class="modal fade" id="verifyCustomModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('workstation.custom.verify', [$appointment->id, $custom->id]) }}" method="POST" class="modal content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
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

{{-- MODAL: RETURN CUSTOM WORKSHEET --}}
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

{{-- MODAL: DELETE CUSTOM WORKSHEET --}}
<div class="modal fade" id="deleteCustomWorksheetModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-danger bg-card shadow-lg text-center p-4">
            <div class="mb-3">
                <i class="bi bi-trash text-danger display-4 d-block"></i>
            </div>
            <h5 class="text-main fw-bold mb-1 uppercase">Remove Worksheet?</h5>
            <p class="text-secondary small mb-4">Are you sure you want to remove '{{ $custom->name }}' from this folder?</p>
            <div class="d-grid gap-2">
                <form action="{{ route('workstation.custom.destroy', [$appointment->id, $custom->id]) }}" method="POST" class="m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100 py-2 fw-bold uppercase text-white">DELETE RECORD</button>
                </form>
                <button type="button" class="btn btn-link text-secondary text-decoration-none smaller" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endforeach