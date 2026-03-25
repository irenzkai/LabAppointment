<div class="mt-2 text-start">
    {{-- 1. SHOW RETURN REASON (ONLY IF STATUS IS RETURNED) --}}
    @if($app->status == 'returned' && $app->return_reason)
        <div class="p-3 bg-black border border-danger rounded mb-3 shadow-sm">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
                <small class="text-danger fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                    Staff Feedback / Reason for Return:
                </small>
            </div>
            <p class="text-white small mb-0 italic" style="line-height: 1.4;">
                "{{ $app->return_reason }}"
            </p>
        </div>
    @endif

    {{-- 2. STAFF CONTROLS (THE CLINICAL WORKFLOW) --}}
    @can('isStaff')
        {{-- STEP A: PENDING -> APPROVE or RETURN --}}
        @if($app->status == 'pending')
            <div class="d-flex gap-2 justify-content-end">
                <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="flex-grow-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn-custom btn-neon w-100 py-2 fw-bold">
                        <i class="bi bi-check-circle me-1"></i> APPROVE
                    </button>
                </form>
                
                <button type="button" class="btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{$app->id}}">
                    RETURN
                </button>
            </div>
        @endif

        {{-- STEP B: APPROVED -> MARK AS TESTED --}}
        @if($app->status == 'approved')
            <button class="btn-custom btn-neon w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#testModal{{$app->id}}">
                <i class="bi bi-person-check me-1"></i> MARK PATIENT AS TESTED
            </button>
        @endif

        {{-- STEP C: TESTED -> ENCODE RESULTS --}}
        @if($app->status == 'tested')
            <div class="d-grid">
                <a href="{{ route('appointments.encode', $app->id) }}" class="btn-custom btn-neon py-2 fw-bold text-center text-decoration-none shadow">
                    <i class="bi bi-pencil-square me-1"></i> ENCODE & RELEASE RESULTS
                </a>
            </div>
        @endif

        {{-- STEP D: RELEASED -> VIEW/RE-EDIT --}}
        @if($app->status == 'released')
            <div class="d-flex gap-2">
                <a href="#" class="btn-custom btn-outline-neon flex-grow-1 py-2 text-center text-decoration-none small fw-bold">
                    <i class="bi bi-file-earmark-pdf me-1"></i> VIEW REPORT
                </a>
                {{-- Staff can potentially re-encode if there's a mistake --}}
                <a href="{{ route('appointments.encode', $app->id) }}" class="btn-custom btn-outline-neon border-secondary text-secondary py-2 px-3 text-decoration-none">
                    <i class="bi bi-gear"></i>
                </a>
            </div>
        @endif
    @endcan

    {{-- 3. USER CONTROL (RESUBMIT) --}}
    @if($app->status == 'returned' && Auth::id() == $app->user_id)
        <button class="btn-custom btn-neon w-100 py-3 fw-bold shadow" 
                data-bs-toggle="modal" 
                data-bs-target="#resubmitModal{{$app->id}}">
            <i class="bi bi-arrow-repeat me-2"></i> UPDATE & RESUBMIT APPOINTMENT
        </button>
        <div class="text-center mt-2">
            <small class="text-secondary" style="font-size: 0.6rem;">* Edit your details and pick a new schedule to continue.</small>
        </div>
    @endif
</div>