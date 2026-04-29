<div class="mt-2 text-start">

    {{-- 1. FEEDBACK: SHOW RETURN REASON (Visible to Everyone) --}}
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

    {{-- 2. INTERNAL PERSONNEL CONTROLS (Staff, Lab Tech, Admin) --}}
    @can('isStaff')
        <div class="staff-action-container">
            
            {{-- STEP A: PENDING -> APPROVE or RETURN (Administrative) --}}
            @if($app->status == 'pending')
                <div class="d-flex gap-2 justify-content-end">
                    <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="flex-grow-1">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn-custom btn-neon w-100 py-2 fw-bold">
                            <i class="bi bi-check-circle me-1"></i> APPROVE
                        </button>
                    </form>
                    <button type="button" class="btn-custom btn-danger-custom px-4" data-bs-toggle="modal" data-bs-target="#retModal{{$app->id}}">
                        RETURN
                    </button>
                </div>
            @endif

            {{-- STEP B: APPROVED -> MARK AS TESTED (Clinical - Lab Tech Only) --}}
            @if($app->status == 'approved')
                @can('isLabTech')
                    <button type="button" class="btn-custom btn-neon w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#testModal{{$app->id}}">
                        <i class="bi bi-person-check me-1"></i> MARK PATIENT AS TESTED
                    </button>
                @else
                    <div class="alert bg-dark border-secondary text-secondary small py-2 mb-0 text-center">
                        <i class="bi bi-hourglass-split me-1"></i> Awaiting Clinical Sampling (Lab Tech Only)
                    </div>
                @endcan
            @endif

            {{-- STEP C: TESTED -> ENCODE RESULTS (Triggers Reason Modal) --}}
            @if($app->status == 'tested')
                <div class="d-grid">
                    <a href="{{ route('appointments.encode', $app->id) }}" class="btn-custom btn-neon py-2 fw-bold text-center text-decoration-none shadow">
                        <i class="bi bi-pencil-square me-1"></i> ENCODE RESULTS
                    </a>
                </div>
            @endif

            {{-- STEP D: ENCODED -> VERIFICATION TRACKER (Triggers Reason Modal for Review) --}}
            @if($app->status == 'encoded')
                <div class="p-3 bg-dark border border-info rounded mb-2 shadow-sm">
                    @php 
                        $res = $app->result; 
                        $reports = $res->included_reports ?? [];
                    @endphp

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-info small fw-bold uppercase mb-0">Verification Progress</h6>
                        <span class="badge bg-info text-dark" style="font-size: 0.6rem;">PENDING</span>
                    </div>
                    
                    <div class="verification-steps">
                        {{-- Lab Report 0/2 Check --}}
                        @if(in_array('lab', $reports))
                            <div class="d-flex justify-content-between smaller border-bottom border-secondary border-opacity-25 pb-1">
                                <span class="text-white">Laboratory:</span>
                                <span class="fw-bold {{ $res->lab_v2_at ? 'text-neon' : 'text-warning' }}">
                                    @if($res->lab_v2_at) 2/2 Verified
                                    @elseif($res->lab_v1_at) 1/2 Verified
                                    @else 0/2 Pending @endif
                                </span>
                            </div>
                        @endif

                        {{-- Other Reports 0/1 Check --}}
                        @foreach(['med_cert' => 'Med Cert', 'drug' => 'Drug Test', 'radio' => 'Radiology'] as $key => $label)
                            @if(in_array($key, $reports))
                                @php $field = ($key == 'med_cert' ? 'med' : $key) . '_verified_at'; @endphp
                                <div class="d-flex justify-content-between smaller pt-1">
                                    <span class="text-white">{{ $label }}:</span>
                                    <span class="fw-bold {{ $res->$field ? 'text-neon' : 'text-warning' }}">
                                        {{ $res->$field ? '1/1 Verified' : '0/1 Pending' }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @can('isLabTech')
                        <button type="button" class="btn-custom btn-neon w-100 mt-3 py-2" 
                                onclick="promptAccess('{{$app->id}}', 'edit', 'edit')">
                            <i class="bi bi-shield-check me-1"></i> REVIEW & VERIFY
                        </button>
                    @else
                        <div class="text-center text-secondary smaller italic mt-2">
                            Awaiting Lab Technician Verification...
                        </div>
                    @endcan
                </div>
            @endif

            {{-- STEP E: RELEASED -> VIEW & PROTECTED EDIT --}}
            @if($app->status == 'released')
                <div class="d-flex gap-2">
                    <button type="button" class="btn-custom btn-outline-neon flex-grow-1 py-2 text-center small fw-bold" 
                            onclick="promptAccess('{{$app->id}}', 'lab', 'preview')">
                        <i class="bi bi-file-earmark-pdf me-1"></i> VIEW REPORT
                    </button>

                    @can('isLabTech')
                        <button type="button" class="btn-custom btn-outline-neon border-secondary text-secondary py-2 px-3" 
                                onclick="promptAccess('{{$app->id}}', 'edit', 'edit')" title="Modify Data">
                            <i class="bi bi-gear"></i>
                        </button>
                    @endcan
                </div>
            @endif

        </div>
    @endcan

    {{-- 3. USER/PATIENT CONTROL (RESUBMIT) --}}
    @if($app->status == 'returned' && Auth::id() == $app->user_id)
        <button type="button" class="btn-custom btn-neon w-100 py-3 fw-bold shadow" 
                data-bs-toggle="modal" 
                data-bs-target="#resubmitModal{{$app->id}}">
            <i class="bi bi-arrow-repeat me-2"></i> UPDATE & RESUBMIT APPOINTMENT
        </button>
        <div class="text-center mt-2">
            <small class="text-secondary" style="font-size: 0.6rem;">* Edit your details and pick a new schedule to continue.</small>
        </div>
    @endif

</div>