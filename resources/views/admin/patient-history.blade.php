@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4 text-start">
    <div>
        <h2 class="text-neon fw-bold mb-0 uppercase">APPOINTMENT HISTORY</h2>
        <p class="text-secondary small">Patient: <span class="text-white fw-bold">{{ strtoupper($user->name) }}</span></p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn-custom btn-outline-neon border-secondary text-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> BACK TO USERS
    </a>
</div>

{{-- 1. REUSE THE LIST LOGIC --}}
{{-- Note: we pass is_staff => true so the clinical labels appear --}}
@include('appointments.partials.list', ['apps' => $appointments, 'type' => 'history', 'is_staff' => true])

{{-- 2. ACCESS REASON MODAL (Mandatory for HIPAA compliance) --}}
<div class="modal fade" id="accessReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="accessReasonForm" method="POST" class="modal-content border-neon bg-black shadow-lg">
            @csrf
            <input type="hidden" name="type" id="access_type">
            <input type="hidden" name="mode" id="access_mode">
            
            <div class="modal-header border-neon bg-dark py-3">
                <h6 class="modal-title text-neon fw-bold uppercase">
                    <i class="bi bi-shield-lock-fill me-2"></i> Authorized Access Required
                </h6>
            </div>
            <div class="modal-body p-4 text-start">
                <p class="text-white small mb-3">Viewing historic medical data requires a professional reason for the audit log.</p>
                <label class="text-secondary smaller fw-bold mb-2 uppercase">Reason for accessing records</label>
                <textarea name="access_reason" class="form-control bg-dark border-secondary text-white shadow-none" rows="3" required placeholder="e.g., Clinical review of past X-ray results..."></textarea>
            </div>
            <div class="modal-footer border-neon bg-dark">
                <button type="button" class="btn-custom btn-outline-neon border-0 text-white" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn-custom btn-neon px-4 py-2">LOG & VIEW</button>
            </div>
        </form>
    </div>
</div>

{{-- 3. MASTER MODALS LOOP (For Return/Resubmit if needed) --}}
@foreach($appointments as $app)
    @can('isStaff')
        {{-- Standard Return Modal code here --}}
    @endcan
@endforeach

@endsection

@push('scripts')
<script>
    // Logic to bridge the dropdown buttons to the reason modal
    function promptAccess(appId, type, mode) {
        const form = document.getElementById('accessReasonForm');
        form.action = `/appointments/${appId}/log-access`;
        document.getElementById('access_type').value = type;
        document.getElementById('access_mode').value = mode;
        new bootstrap.Modal(document.getElementById('accessReasonModal')).show();
    }
</script>
@endpush