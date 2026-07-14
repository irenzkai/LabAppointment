@if($status == 'none')
    {{-- CASE 1: INITIAL STATE (No request made yet) --}}
    <div class="card p-5 text-center border-secondary bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 420px;">
        <div class="bg-secondary bg-opacity-10 rounded-circle p-3 mb-4 text-accent d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="bi bi-shield-lock-fill fs-1 text-accent"></i>
        </div>
        {{-- FIXED: Changed text-white to text-main to ensure contrast in light mode --}}
        <h4 class="text-main uppercase fw-bold mb-2" style="font-size: 1.35rem; letter-spacing: 0.5px;">Historical Data Connection</h4>
        
        @if(Auth::user()->isPatient())
            <p class="text-muted small mb-4" style="max-width: 420px;">Your physical lab records are not digitized yet. Request our laboratory staff to initialize your legacy database.</p>
            <form action="{{ route('history.request') }}" method="POST">
                @csrf
                <button class="btn-custom btn-accent px-5 py-2.5 fw-bold uppercase">REQUEST DATA IMPORT</button>
            </form>
        @else
            <p class="text-muted small mb-4" style="max-width: 420px;">This patient has not requested a historical data import yet. You can manually request permission.</p>
            <form action="{{ route('history.staff-trigger', $targetUser->id) }}" method="POST">
                @csrf
                <button class="btn-custom btn-outline-accent px-5 py-2.5 fw-bold uppercase">ASK PATIENT FOR PERMISSION</button>
            </form>
        @endif
    </div>

@elseif($status == 'pending_staff')
    {{-- CASE 2: PENDING STAFF REVIEW (Patient requested) --}}
    <div class="card p-5 text-center border-info bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 420px;">
        <div class="bg-info bg-opacity-10 rounded-circle p-3 mb-4 text-info d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="bi bi-person-check fs-1 text-info"></i>
        </div>
        
        @if(Auth::user()->isEmployee())
            {{-- FIXED: Changed text-white to text-main --}}
            <h4 class="text-main uppercase fw-bold mb-2" style="font-size: 1.35rem; letter-spacing: 0.5px;">Import Request Received</h4>
            <p class="text-muted small mb-4" style="max-width: 420px;">The patient is asking to have their physical lab history digitized. Proceed to activate the manual entry board.</p>
            <form action="{{ route('history.accept', ['user' => $targetUser->id]) }}" method="POST">
                @csrf
                <button class="btn-custom btn-accent px-5 py-2.5 fw-bold uppercase">ACCEPT & OPEN IMPORT TOOL</button>
            </form>
        @else
            {{-- FIXED: Changed text-white to text-main --}}
            <h4 class="text-main uppercase fw-bold mb-2" style="font-size: 1.35rem; letter-spacing: 0.5px;">Awaiting Laboratory Review</h4>
            <p class="text-muted small mb-0" style="max-width: 420px;">Your request was successfully dispatched. Please wait while a laboratory technician initializes your profile.</p>
        @endif
    </div>

@elseif($status == 'pending_patient')
    {{-- CASE 3: AWAITING PATIENT HANDSHAKE (Staff requested) --}}
    <div class="card p-5 text-center border-warning bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 420px;">
        <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-4 text-warning d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="bi bi-shield-exclamation fs-1 text-warning"></i>
        </div>
        
        @if(Auth::user()->isPatient())
            {{-- FIXED: Changed text-white to text-main --}}
            <h4 class="text-main uppercase fw-bold mb-2" style="font-size: 1.35rem; letter-spacing: 0.5px;">Permission Required</h4>
            <p class="text-muted small mb-4" style="max-width: 420px;">The laboratory requests permission to digitize your previous physical records. Do you authorize this request?</p>
            <form action="{{ route('history.accept') }}" method="POST">
                @csrf
                <button class="btn-custom btn-accent px-5 py-2.5 fw-bold uppercase">ALLOW DIGITIZATION</button>
            </form>
        @else
            {{-- FIXED: Changed text-white to text-main --}}
            <h4 class="text-main uppercase fw-bold mb-2" style="font-size: 1.35rem; letter-spacing: 0.5px;">Awaiting Patient Consent</h4>
            <p class="text-muted small mb-0" style="max-width: 420px;">A request has been sent to the patient's portal. Awaiting user handshake authorization.</p>
        @endif
    </div>
@endif