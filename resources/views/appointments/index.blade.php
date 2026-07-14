@extends('layouts.app')

@section('content')
<div class="container-fluid text-start animate-page">
 
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                {{ $is_staff ? 'Master Clinical Queue' : 'My Appointments' }}
            </h2>
            <p class="text-muted mb-0 small">
                {{ $is_staff ? 'Monitor, approve, and track real-time clinical workflows.' : 'Track your diagnostic bookings and access clinical results.' }}
            </p>
        </div>
        @if(!$is_staff)
            <a href="{{ route('patient.history') }}" class="btn-custom btn-outline-accent px-3 py-2">
                <i class="bi bi-clock-history me-2"></i> VIEW ARCHIVED RECORDS
            </a>
        @endif
    </div>

    {{-- Split Pane Grid Layout --}}
    <div class="row g-4">
 
        {{-- LEFT PANEL: Card Lists & Filters --}}
        <div class="col-lg-5 col-xl-4">
 
            {{-- Navigation Tabs (Patient Only) --}}
            @if(!$is_staff)
                <ul class="nav nav-pills mb-3 gap-1 bg-secondary bg-opacity-10 p-1.5 rounded-3 border border-secondary border-opacity-25" id="appTabs" role="tablist">
                    <li class="nav-item flex-grow-1">
                        <button class="nav-link active w-100 fs-x-small fw-bold uppercase py-2" data-bs-toggle="pill" data-bs-target="#pane-self" onclick="resetActiveDetail()">Myself</button>
                    </li>
                    <li class="nav-item flex-grow-1">
                        <button class="nav-link w-100 fs-x-small fw-bold uppercase py-2" data-bs-toggle="pill" data-bs-target="#pane-family" onclick="resetActiveDetail()">Family</button>
                    </li>
                    <li class="nav-item flex-grow-1">
                        <button class="nav-link w-100 fs-x-small fw-bold uppercase py-2" data-bs-toggle="pill" data-bs-target="#pane-bulk" onclick="resetActiveDetail()">Bulk</button>
                    </li>
                </ul>
            @else
                {{-- NEW: Clinical search input for staff and above to filter master queue records dynamically --}}
                <div class="mb-3">
                    <div class="input-group input-group-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                        <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card); border-right: none;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="queueSearch" class="form-control border-0 shadow-none py-2" style="background-color: var(--bg-card); color: var(--text-main);" placeholder="Search patient name, ID, or batch...">
                    </div>
                </div>
            @endif

            {{-- Main List Wrapper --}}
            <div class="tab-content" id="listContent">
 
                {{-- STAFF MAIN QUEUE --}}
                @if($is_staff)
                    <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                        @forelse($staffQueue as $batchId => $group)
                            @php 
                            $isGroup = $group instanceof \Illuminate\Support\Collection;
                            $first = $isGroup ? $group->first() : $group;
                            @endphp
                            @include('appointments.partials.list-card', ['app' => $first, 'groupCount' => $isGroup ? $group->count() : 1, 'batchId' => $batchId])
                        @empty
                            <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                <i class="bi bi-folder-x text-accent fs-1 mb-3 opacity-75"></i>
                                <p class="small mb-0">No appointments in queue.</p>
                            </div>
                        @endforelse
                    </div>
                @else
                    {{-- PATIENT: MYSELF --}}
                    <div class="tab-pane fade show active" id="pane-self">
                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                            @forelse($self as $app)
                                @include('appointments.partials.list-card', ['app' => $app, 'groupCount' => 1])
                            @empty
                                <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                    <i class="bi bi-calendar-x text-accent fs-1 mb-3 opacity-75"></i>
                                    <p class="small mb-0">No personal bookings found.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- PATIENT: FAMILY DEPENDENTS --}}
                    <div class="tab-pane fade" id="pane-family">
                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                            @forelse($dependents as $app)
                                @include('appointments.partials.list-card', ['app' => $app, 'groupCount' => 1])
                            @empty
                                <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                    <i class="bi bi-people text-accent fs-1 mb-3 opacity-75"></i>
                                    <p class="small mb-0">No dependent bookings found.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- PATIENT: ORGANIZATIONAL BULK --}}
                    <div class="tab-pane fade" id="pane-bulk">
                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                            @forelse($bulkGroups as $batchId => $group)
                                @php $first = $group->first(); @endphp
                                @include('appointments.partials.list-card', ['app' => $first, 'groupCount' => $group->count(), 'batchId' => $batchId])
                            @empty
                                <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                    <i class="bi bi-buildings text-accent fs-1 mb-3 opacity-75"></i>
                                    <p class="small mb-0">No corporate groups found.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- RIGHT PANEL: Active Clinical Sheet Workspace --}}
        <div class="col-lg-7 col-xl-8" style="{{ !$is_staff ? 'margin-top: 52px;' : '' }}">
            <div id="workspace-container" class="h-100">
 
                {{-- Default Empty State Placeholder --}}
                <div id="details-placeholder" class="card p-5 text-center border-secondary bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 420px; background-color: var(--bg-card);">
                    <div class="bg-secondary bg-opacity-10 rounded-circle p-3 mb-4 text-accent d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="bi bi-clipboard2-pulse-fill fs-1 text-accent"></i>
                    </div>
                    <h4 class="text-main fw-bold mb-2 uppercase">Clinical Detail Workspace</h4>
                    <p class="text-muted small mb-0" style="max-width: 380px;">Select any clinical entry from the left-hand panel to review its test breakdowns, billing summaries, and context actions.</p>
                </div>

                {{-- Render Hidden Detail Panels --}}
                @php 
                $allApps = $is_staff ? $staffQueue->flatten() : $self->concat($dependents)->concat($bulkGroups->flatten());
                @endphp
                @foreach($allApps as $app)
                    @include('appointments.partials.detail-card', ['app' => $app])
                @endforeach

            </div>
        </div>

    </div>
</div>

{{-- FULLSCREEN QR LIGHTBOX OVERLAY --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox()">
    <div class="text-center p-3 animate-fade-in">
        <img src="" id="lightbox_qr_img" alt="Zoomed QR" class="img-fluid rounded border border-secondary p-3 bg-white" style="max-height: 75vh; max-width: 90vw; object-fit: contain;">
        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the screen to close preview</p>
    </div>
</div>

{{-- 4. THEME-ADAPTIVE MODALS LOOP (No Static Grays/Blacks) --}}
@foreach($allApps as $app)
 
    {{-- Return to Patient Modal (Enhanced with dropdown + custom textarea toggle) --}}
    @can('isStaff')
        <div class="modal fade" id="retModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('appointments.status', $app->id) }}" id="returnForm{{$app->id}}" method="POST" class="modal-content shadow-lg return-form-element" data-app-id="{{$app->id}}" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="returned">
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h5 class="modal-title text-danger fw-bold uppercase small m-0">Return to Patient</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4 text-start">
                        {{-- Predefined Return Reasons Dropdown (Updated with payment options) --}}
                        <div class="mb-3">
                            <label for="return_reason_select_{{$app->id}}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Reason for Return</label>
                            <select id="return_reason_select_{{$app->id}}" class="form-select shadow-none return-reason-select" data-app-id="{{$app->id}}" required>
                                <option value="" disabled selected>-- Select a return reason --</option>
                                <option value="Mismatched identification documents">Mismatched identification documents</option>
                                <option value="Incorrect or incomplete personal details">Incorrect or incomplete personal details</option>
                                <option value="No payment received / pending verification">No payment received / pending verification</option>
                                <option value="Invalid test selection for patient demographics">Invalid test selection for patient demographics</option>
                                <option value="Discrepancy in schedule / date selection">Discrepancy in schedule / date selection</option>
                                <option value="Others">Others (Specify details below)</option>
                            </select>
                        </div>

                        {{-- Hidden custom reason textbox, shown if "Others" is selected --}}
                        <div id="custom_return_reason_wrapper_{{$app->id}}" class="mb-3 d-none">
                            <label for="return_reason_{{$app->id}}" class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                            <textarea name="return_reason" id="return_reason_{{$app->id}}" class="form-control shadow-none return-reason-textarea" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="4" placeholder="Identify the specific correction needed..."></textarea>
                            <div class="mt-2">
                                <small class="text-muted smaller italic">Minimum 5 characters required for validation.</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
                        <div class="d-flex w-100">
                            <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Send Return</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- Mark as Tested Modal --}}
    @can('isLabTech')
        <div class="modal fade" id="testModal{{$app->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('appointments.tested', $app->id) }}" method="POST" class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf @method('PATCH')
                    <div class="modal-header py-3" style="background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
                        <h6 class="modal-title text-accent fw-bold uppercase m-0">Patient Sampling Completed</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small mb-4" style="color: var(--text-main) !important;">Confirm sampling process completion. Estimated processing time:</p>
                        <label class="smaller fw-bold mb-2 uppercase" style="color: var(--text-muted);">Estimated Processing Time</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="est_hours" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" placeholder="0" min="0">
                                    <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card);">Hrs</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="est_minutes" class="form-control shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" placeholder="0" min="0" max="59">
                                    <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card);">Mins</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer p-0" style="background-color: var(--bg-card); border-top: 1px solid var(--border-color);">
                        <div class="d-flex w-100">
                            <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Confirm & Notify</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- Resubmit Patient Modal --}}
    @if($app->status == 'returned' && Auth::id() == $app->user_id)
        @include('appointments.partials.resubmit-modal', ['app' => $app])
    @endif

@endforeach

@push('scripts')
<script>
function showAppointmentDetails(appId) {
    document.getElementById('details-placeholder').classList.add('d-none');
    document.querySelectorAll('.appointment-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));

    const detailPanel = document.getElementById(`details-${appId}`);
    if(detailPanel) {
        detailPanel.classList.remove('d-none');
    }
    
    const listCard = document.getElementById(`card-${appId}`);
    if(listCard) {
        listCard.classList.add('border-accent', 'shadow-neon');
    }
}

function resetActiveDetail() {
    document.getElementById('details-placeholder').classList.remove('d-none');
    document.querySelectorAll('.appointment-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));
}

document.addEventListener('DOMContentLoaded', () => {
    // FIXED: Live-search filtering scoped globally for the staff sidebar list
    const queueSearch = document.getElementById('queueSearch');
    if (queueSearch) {
        queueSearch.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            const cards = document.querySelectorAll('.app-list-card');
            
            cards.forEach(card => {
                const cardText = card.innerText.toLowerCase();
                if (cardText.includes(query)) {
                    card.classList.remove('d-none');
                } else {
                    card.classList.add('d-none');
                }
            });
        });
    }

    document.addEventListener('show.bs.modal', function (event) {
        const modal = event.target;
        if (modal.classList.contains('resubmit-modal')) {
            const dateInput = modal.querySelector('.resubmit-date');
            if (dateInput) {
                updateResubmitSlots(dateInput);
            }
        }
    });
});

async function updateResubmitSlots(input) {
    const date = input.value;
    const appId = input.dataset.appId;
    const select = document.getElementById(`ts-${appId}`);
    
    if(!date || !select) return;

    select.innerHTML = '<option value="">Checking slots...</option>';
    select.disabled = true;

    try {
        const res = await fetch(`/api/check-slots?date=${date}&exclude_id=${appId}`);
        const data = await res.json();
        
        if(data.is_closed) {
            select.innerHTML = '<option value="">CLINIC CLOSED</option>';
            return;
        }

        const config = data.config;
        let html = '<option value="">Choose Available Time</option>';
        let start = new Date(`2000-01-01 ${config.opening_time}`);
        let end = new Date(`2000-01-01 ${config.closing_time}`);
        let availableCount = 0;

        while(start < end) {
            let hours = start.getHours().toString().padStart(2, '0');
            let minutes = start.getMinutes().toString().padStart(2, '0');
            let tStr = `${hours}:${minutes}:00`; 

            let isFull = data.full_slots.includes(tStr);
            let isLunch = (config.has_lunch_break && tStr >= config.lunch_start && tStr < config.lunch_end);
            let isPast = (date === new Date().toLocaleDateString('en-CA') && start.getHours() <= new Date().getHours());

            if (isFull || isLunch || isPast) {
                start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
                continue; 
            }

            let disp = start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            html += `<option value="${tStr}">${disp}</option>`;
            availableCount++;
            
            start.setMinutes(start.getMinutes() + parseInt(config.slot_duration));
        } 

        select.innerHTML = availableCount > 0 ? html : '<option value="">NO SLOTS AVAILABLE</option>';
        select.disabled = (availableCount === 0);

    } catch (e) {
        console.error("Fetch error:", e);
        select.innerHTML = '<option value="">Error syncing schedule</option>';
    }
}

// Dynamic dropdown + textarea transition logic for Return Modals
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.return-form-element').forEach(form => {
        const appId = form.dataset.appId;
        const selectEl = form.querySelector('.return-reason-select');
        const textareaWrapper = form.querySelector(`#custom_return_reason_wrapper_${appId}`);
        const textareaEl = form.querySelector(`#return_reason_${appId}`);

        if (selectEl && textareaEl && textareaWrapper) {
            selectEl.addEventListener('change', function() {
                if (this.value === 'Others') {
                    textareaWrapper.classList.remove('d-none');
                    textareaEl.setAttribute('required', 'required');
                    textareaEl.value = ''; // Reset Custom field
                } else {
                    textareaWrapper.classList.add('d-none');
                    textareaEl.removeAttribute('required');
                    textareaEl.value = this.value; // Store standard justification directly
                }
            });

            form.addEventListener('submit', function(e) {
                if (selectEl.value !== 'Others') {
                    textareaEl.value = selectEl.value;
                }
                if (textareaEl.value.trim().length < 5) {
                    e.preventDefault();
                    alert('A valid return reason of at least 5 characters is required.');
                }
            });
        }
    });
});

// Fullscreen light-box zoom toggles for payment receipts
function zoomQR(qrSrc) {
    if (qrSrc) {
        document.getElementById('lightbox_qr_img').src = qrSrc;
        document.getElementById('qr_lightbox').classList.remove('d-none');
        document.getElementById('qr_lightbox').classList.add('d-flex');
    }
}

function closeQRLightbox() {
    document.getElementById('qr_lightbox').classList.add('d-none');
    document.getElementById('qr_lightbox').classList.remove('d-flex');
}
</script>

<style>
/* High-contrast overrides for Light Mode Compatibility */
#appTabs .nav-link,
.nav-pills .nav-link {
    color: var(--text-muted) !important;
    border: 1px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    border-radius: 8px;
    transition: 0.2s ease;
}
#appTabs .nav-link:hover,
.nav-pills .nav-link:hover {
    border-color: var(--brand-accent) !important;
    color: var(--brand-accent) !important;
}
#appTabs .nav-link.active,
.nav-pills .nav-link.active,
button.nav-link.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
}

.border-dashed { border-style: dashed !important; }
.shadow-neon { box-shadow: 0 0 10px rgba(25, 211, 140, 0.15) !important; }
.min-vh-50 { min-height: 50vh; }
.app-list-card { transition: all 0.2s ease; cursor: pointer; }
.app-list-card:hover { border-color: var(--brand-accent) !important; transform: translateX(2px); }
</style>
@endpush
@endsection