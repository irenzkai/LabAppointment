@php
    $currentStatus = $status ?? ($labHistory->permission_status ?? 'none');
@endphp

@if($currentStatus == 'none' || empty($currentStatus))
    {{-- CASE 1: INITIAL STATE (No request made yet) --}}
    <div class="card p-4 p-md-5 text-center border-secondary bg-card shadow-sm w-100" style="border-radius: 16px; background-color: var(--bg-card);">
        <div class="bg-secondary bg-opacity-10 rounded-circle p-2.5 mb-3 text-accent d-inline-flex align-items-center justify-content-center mx-auto" style="width: 58px; height: 58px;">
            <i class="bi bi-shield-lock-fill fs-2 text-accent"></i>
        </div>
        
        <h5 class="text-main uppercase fw-bold mb-2" style="font-size: 1.25rem; letter-spacing: 0.5px;">Historical Data Connection</h5>
        
        @if(Auth::user()->isPatient())
            <p class="text-muted small mb-3.5 mx-auto" style="max-width: 540px; font-size: 0.88rem; line-height: 1.5;">
                Your physical clinic records have not been digitized yet. Submit a request to have our laboratory team initialize your archival folder.
            </p>
            <form action="{{ route('history.request') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn-custom btn-accent py-2.5 px-5 fw-bold uppercase shadow-sm" style="font-size: 0.82rem;">
                    <i class="bi bi-send-fill me-1.5"></i> REQUEST DATA IMPORT
                </button>
            </form>
        @else
            <p class="text-muted small mb-3.5 mx-auto" style="max-width: 540px; font-size: 0.88rem; line-height: 1.5;">
                This patient has not requested a historical data import yet. Clinical protocol requires patient consent before historical physical records can be digitized. Send an authorization request to the patient's portal.
            </p>
            <form action="{{ route('history.staff-trigger', $targetUser->id) }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn-custom btn-accent py-2.5 px-5 fw-bold uppercase shadow-sm" style="font-size: 0.82rem;">
                    <i class="bi bi-envelope-paper-fill me-1.5"></i> ASK PATIENT FOR PERMISSION
                </button>
            </form>
        @endif
    </div>

@elseif($currentStatus == 'pending_staff')
    {{-- CASE 2: PENDING STAFF REVIEW (Patient requested) --}}
    <div class="card p-4 p-md-5 text-center border-info bg-card shadow-sm w-100" style="border-radius: 16px; background-color: var(--bg-card); border-color: rgba(13, 202, 240, 0.4) !important;">
        <div class="bg-info bg-opacity-10 rounded-circle p-2.5 mb-3 text-info d-inline-flex align-items-center justify-content-center mx-auto" style="width: 58px; height: 58px;">
            <i class="bi bi-person-check-fill fs-2 text-info"></i>
        </div>
        
        @if(Auth::user()->isEmployee())
            <h5 class="text-main uppercase fw-bold mb-2" style="font-size: 1.25rem; letter-spacing: 0.5px;">Import Request Received</h5>
            <p class="text-muted small mb-3.5 mx-auto" style="max-width: 540px; font-size: 0.88rem; line-height: 1.5;">
                The patient has authorized and requested to have their physical laboratory history digitized. You may now initialize and open the manual entry compilation tool.
            </p>
            <form action="{{ route('history.accept', ['user' => $targetUser->id]) }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn-custom btn-accent py-2.5 px-5 fw-bold uppercase shadow-sm" style="font-size: 0.82rem;">
                    <i class="bi bi-check-circle-fill me-1.5"></i> ACCEPT & OPEN IMPORT TOOL
                </button>
            </form>
        @else
            <h5 class="text-main uppercase fw-bold mb-2" style="font-size: 1.25rem; letter-spacing: 0.5px;">Awaiting Laboratory Review</h5>
            <p class="text-muted small mb-3 mx-auto" style="max-width: 540px; font-size: 0.88rem; line-height: 1.5;">
                Your request was successfully dispatched. Please wait while a laboratory technologist reviews and initializes your profile archive.
            </p>
            <div class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-2 px-3 uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                <i class="bi bi-hourglass-split me-1"></i> STATUS: PENDING CLINICAL REVIEW
            </div>
        @endif
    </div>

@elseif($currentStatus == 'pending_patient')
    {{-- CASE 3: AWAITING PATIENT HANDSHAKE (Staff requested) --}}
    <div class="card p-4 p-md-5 text-center border-warning bg-card shadow-sm w-100" style="border-radius: 16px; background-color: var(--bg-card); border-color: rgba(255, 193, 7, 0.4) !important;">
        <div class="bg-warning bg-opacity-10 rounded-circle p-2.5 mb-3 text-warning d-inline-flex align-items-center justify-content-center mx-auto" style="width: 58px; height: 58px;">
            <i class="bi bi-shield-exclamation fs-2 text-warning"></i>
        </div>
        
        @if(Auth::user()->isPatient())
            <h5 class="text-main uppercase fw-bold mb-2" style="font-size: 1.25rem; letter-spacing: 0.5px;">Permission Requested</h5>
            <p class="text-muted small mb-3.5 mx-auto" style="max-width: 540px; font-size: 0.88rem; line-height: 1.5;">
                The laboratory is requesting your authorization to digitize and link your previous physical clinical examination records. Do you authorize this request?
            </p>
            <form action="{{ route('history.accept') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn-custom btn-accent py-2.5 px-5 fw-bold uppercase shadow-sm" style="font-size: 0.82rem;">
                    <i class="bi bi-check2-circle me-1.5"></i> ALLOW & UNLOCK DIGITIZATION
                </button>
            </form>
        @else
            <h5 class="text-main uppercase fw-bold mb-2" style="font-size: 1.25rem; letter-spacing: 0.5px;">Awaiting Patient Consent</h5>
            <p class="text-muted small mb-3 mx-auto" style="max-width: 540px; font-size: 0.88rem; line-height: 1.5;">
                An authorization request has been dispatched to the patient's portal. Awaiting patient handshake consent before opening records.
            </p>
            <div class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-2 px-3 uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                <i class="bi bi-clock-history me-1"></i> STATUS: AWAITING USER CONSENT
            </div>
        @endif
    </div>
@endif