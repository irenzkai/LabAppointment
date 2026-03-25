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

    {{-- 2. STAFF CONTROLS (APPROVE / RETURN) --}}
    @can('isStaff')
        @if($app->status == 'pending')
            <div class="d-flex gap-2 justify-content-end">
                <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="flex-grow-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn-custom btn-neon w-100 py-2">APPROVE</button>
                </form>
                
                {{-- Red Return Button with White Text --}}
                <button type="button" class="btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{$app->id}}">
                    RETURN
                </button>
            </div>
        @endif
    @endcan

    {{-- 3. USER CONTROL (RESUBMIT) --}}
    @if($app->status == 'returned' && Auth::id() == $app->user_id)
        <button class="btn-custom btn-neon w-100 py-3 fw-bold shadow" 
                data-bs-toggle="modal" 
                data-bs-target="#resubmitModal{{$app->id}}">
            <i class="bi bi-pencil-square me-2"></i> UPDATE & RESUBMIT APPOINTMENT
        </button>
    @endif
</div>