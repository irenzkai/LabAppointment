@extends('layouts.app')

@section('title', 'Laboratory Historical Archive')

@section('content')
@php
    // SECURITY GUARD: Automatically fetch active services if a controller forgot to pass them
    if (!isset($availableServices)) {
        $availableServices = \App\Models::Service::where('is_available', true)->orderBy('category')->orderBy('name')->get();
    }

    // Dynamically group legacy appointments by batch_id to consolidate multi-pax corporate bookings
    $groupedAppointments = $appointments->groupBy(fn($item) => $item->batch_id ?? 'single_' . $item->id);

    // FIXED: Protect controller-passed $existingRecords from being clobbered by empty dynamic_data array
    $existingRecords = $existingRecords ?? (is_array($labHistory->dynamic_data) ? array_reverse($labHistory->dynamic_data) : []);
@endphp

<div class="container text-start animate-page" id="patient-archive-page">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">PATIENT ARCHIVE</h2>
            <p class="text-secondary mb-0 small">Records for: <span class="text-accent fw-bold uppercase">{{ $targetUser->name }}</span></p>
        </div>
        <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-outline-secondary px-4 py-2 fw-bold text-uppercase" style="color: var(--text-muted) !important; border-color: var(--border-color) !important; border-radius: 8px;">
            <i class="bi bi-arrow-left me-2"></i> BACK TO APPOINTMENTS
        </a>
    </div>

    {{-- Nav Tabs --}}
    <ul class="nav nav-pills mb-4 border-bottom pb-3 gap-2" id="historyTabs" role="tablist" style="border-color: var(--border-color) !important;">
        <li class="nav-item">
            <button class="nav-link active fw-bold px-4" data-bs-toggle="pill" data-bs-target="#app-history" onclick="resetActiveDetail()">APPOINTMENT HISTORY</button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold px-4" data-bs-toggle="pill" data-bs-target="#lab-history" onclick="resetActiveDetail()">LABORATORY RECORDS</button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- TAB 1: APPOINTMENT HISTORY (Split-Pane Grid Layout) --}}
        <div class="tab-pane fade show active" id="app-history">
            <div class="row g-4">

                {{-- Left Side: Historical Appointment Deck --}}
                <div class="col-lg-5 col-xl-4">
                    <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 650px;">
                        @forelse($groupedAppointments as $item)
                            @php
                                $first = $item->first();
                                $groupCount = $item->count();
                            @endphp
                            @include('appointments.partials.list-card', ['app' => $first, 'groupCount' => $groupCount])
                        @empty
                            <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 420px; background-color: var(--bg-card);">
                                <i class="bi bi-calendar-x text-accent fs-1 mb-3 opacity-75"></i>
                                <p class="small mb-0">No past bookings found.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Right Side: Selected Archive Detail Workspace --}}
                <div class="col-lg-7 col-xl-8">
                    <div id="workspace-container" class="h-100">
                        {{-- Default Empty State Placeholder --}}
                        <div id="details-placeholder" class="card p-5 text-center border-secondary bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 420px; background-color: var(--bg-card);">
                            <div class="bg-secondary bg-opacity-10 rounded-circle p-3 mb-4 text-accent d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-clipboard2-pulse-fill fs-1 text-accent"></i>
                            </div>
                            <h4 class="text-main fw-bold mb-2 uppercase">Archive Workspace</h4>
                            <p class="text-muted small mb-0" style="max-width: 380px;">Select any clinical entry from the left-hand panel to review its test breakdowns, billing summaries, and context actions.</p>
                        </div>

                        {{-- Hidden Detail Panels --}}
                        @foreach($groupedAppointments as $item)
                            @php $first = $item->first(); @endphp
                            @include('appointments.partials.detail-card', ['app' => $first])
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        {{-- TAB 2: LABORATORY RECORDS (Full-Width Workspace with Split-Pane Layout) --}}
        <div class="tab-pane fade" id="lab-history">
            @php $status = $labHistory->permission_status; @endphp
            @if($status == 'granted')
                <div class="row g-4">

                    {{-- Left Side: Historical Records List --}}
                    <div class="col-lg-5 col-xl-4">
                        @if(Auth::user()->isEmployee())
                            <button type="button" class="btn-custom btn-accent w-100 py-2.5 mb-2 fw-bold uppercase shadow-sm" onclick="showAddRecordForm()">
                                <i class="bi bi-plus-lg me-1"></i> ADD RECORD
                            </button>
                        @endif

                        {{-- Added "Notify Patient" action button for staff inside the sidebar --}}
                        @if(Auth::user()->isEmployee() && count($existingRecords) > 0)
                            <form action="{{ route('history.notify-encoded', $targetUser->id) }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="btn-custom btn-outline-accent w-100 py-2 fw-bold uppercase shadow-sm">
                                    <i class="bi bi-bell-fill me-1"></i> Notify Patient (Encoded)
                                </button>
                            </form>
                        @endif

                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 580px;">
                            @forelse($existingRecords as $record)
                                <div class="card app-list-card bg-card border-secondary p-3 text-start mb-2" id="record-card-{{ $record['id'] }}" onclick="showRecordDetails('{{ $record['id'] }}')">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-bold text-main fs-6 text-truncate" style="max-width: 180px; color: var(--text-main) !important;">
                                            {{ $record['requested_by'] ?? 'INDIVIDUAL' }}
                                        </div>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 uppercase px-2 py-1" style="font-size: 0.65rem;">
                                            {{ count($record['scans'] ?? []) }} {{ count($record['scans'] ?? []) === 1 ? 'FILE' : 'FILES' }}
                                        </span>
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        <i class="bi bi-calendar2 me-1"></i> {{ date('M d, Y', strtotime($record['date_of_record'])) }}
                                    </div>
                                    <div class="text-accent smaller fw-bold mt-1 text-truncate">
                                        <i class="bi bi-flask-fill me-1"></i> {{ Str::limit(implode(', ', $record['tests_requested'] ?? []), 30) }}
                                    </div>
                                </div>
                            @empty
                                <div class="card p-5 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 350px; background-color: var(--bg-card);">
                                    <i class="bi bi-file-earmark-lock2 fs-2 mb-2 opacity-50"></i>
                                    <p class="small mb-0">No historical records digitized yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Right Side: Active Workspace --}}
                    <div class="col-lg-7 col-xl-8">
                        <div id="lab-workspace-container" class="h-100">
                            {{-- Default Placeholder --}}
                            <div id="record-placeholder" class="card p-5 text-center border-secondary bg-card d-flex flex-column align-items-center justify-content-center h-100" style="min-height: 420px; background-color: var(--bg-card);">
                                <div class="bg-secondary bg-opacity-10 rounded-circle p-3 mb-4 text-accent d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="bi bi-file-earmark-medical fs-1 text-accent"></i>
                                </div>
                                <h4 class="text-main fw-bold mb-2 uppercase">Laboratory Records Workspace</h4>
                                <p class="text-muted small mb-0" style="max-width: 380px;">Select a digitized laboratory record from the left-hand sidebar directory to review files and download reports.</p>
                            </div>

                            {{-- Digitize New Record Form Panel --}}
                            @if(Auth::user()->isEmployee())
                                <div id="add-record-panel" class="d-none animate-page">
                                    @include('appointments.partials.digitize-records')
                                </div>
                            @endif

                            {{-- Records Detail Workspace --}}
                            @foreach($existingRecords as $record)
                                <div id="record-details-{{ $record['id'] }}" class="record-detail-pane card border-secondary bg-card p-4 d-none animate-page" style="background-color: var(--bg-card); color: var(--text-main);">
                                    {{-- Document Title --}}
                                    <div class="border-bottom border-secondary border-opacity-25 pb-3 mb-4 text-start">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                            <div>
                                                <h4 class="text-main fw-bold mb-1 uppercase tracking-tighter" style="font-size: 1.4rem; color: var(--text-main) !important;">
                                                    DIGITIZED REPORT
                                                </h4>
                                                <div class="text-secondary smaller fw-bold uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                                    Date of Record: {{ date('M d, Y', strtotime($record['date_of_record'])) }} <span class="mx-2">|</span> 
                                                    Requested By: {{ $record['requested_by'] ?? 'INDIVIDUAL' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Form Layout --}}
                                    <div class="row g-4 text-start">
                                        {{-- Profile --}}
                                        <div class="col-md-6 border-end border-secondary border-opacity-25">
                                            <h6 class="text-accent small fw-bold uppercase mb-3"><i class="bi bi-person-bounding-box me-1"></i> Patient Demographics</h6>
                                            <div class="mb-2"><strong>Patient Name:</strong> {{ $record['patient_name'] }}</div>
                                            <div class="mb-2"><strong>Age / Sex:</strong> {{ $record['age'] }} Years Old / {{ $record['sex'] }}</div>
                                            <div class="mb-3"><strong>Residential Address:</strong> {{ $record['address'] }}</div>

                                            <h6 class="text-accent small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3 mb-3"><i class="bi bi-list-check me-1"></i> Requested Procedures</h6>
                                            <div class="d-flex flex-wrap gap-1.5">
                                                @foreach($record['tests_requested'] ?? [] as $test)
                                                    <span class="badge border border-secondary border-opacity-25 text-secondary uppercase px-2 py-1.5 rounded" style="background-color: rgba(0,0,0,0.02); font-size: 0.7rem;">{{ $test }}</span>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Attached Files --}}
                                        <div class="col-md-6">
                                            <h6 class="text-accent small fw-bold uppercase mb-3"><i class="bi bi-file-earmark-pdf-fill me-1"></i> Attached Scanned Reports</h6>
                                            <div class="d-flex flex-column gap-2 mb-3">
                                                @foreach($record['scans'] ?? [] as $fileIdx => $scan)
                                                    <div class="d-flex justify-content-between align-items-center p-2.5 border rounded" style="background-color: rgba(25, 211, 140, 0.05); border-color: rgba(25, 211, 140, 0.15) !important;">
                                                        <span class="fw-bold small text-truncate pe-2" style="color: var(--text-main) !important; font-size: 0.85rem;">
                                                            <i class="bi bi-file-earmark-medical text-accent me-1"></i> {{ $scan['label'] }}
                                                        </span>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" style="font-size: 0.75rem;" onclick="previewHistFile('{{ $record['id'] }}', '{{ Storage::url($scan['file_path']) }}', '{{ $scan['label'] }}')">
                                                                PREVIEW
                                                            </button>
                                                            <a href="{{ Storage::url($scan['file_path']) }}" download class="btn btn-sm btn-accent py-1 px-3 fw-bold" style="font-size: 0.75rem;">
                                                                DOWNLOAD
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Embedded iframe --}}
                                        <div class="col-12 border-top border-secondary border-opacity-25 pt-4 d-none" id="preview-area-{{ $record['id'] }}">
                                            <div class="bg-warning text-dark p-2 px-3 fw-bold small uppercase rounded-top">
                                                <i class="bi bi-eye-fill me-2"></i>PREVIEWING ATTACHED FILE: <span id="preview-title-{{ $record['id'] }}"></span>
                                            </div>
                                            <iframe id="viewer-iframe-{{ $record['id'] }}" class="w-100 bg-dark" style="min-height: 480px; border: none; border-radius: 0 0 8px 8px;"></iframe>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            @else
                {{-- Handshake alerts --}}
                @include('appointments.partials.handshake-panel')
            @endif
        </div>
    </div>
</div>

{{-- MULTI-FORMAT LIGHTBOX OVERLAY WITH SECURE PREVIEW GATES --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox(event)">
    <div class="text-center p-3 animate-fade-in w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="max-width: 95vw; max-height: 95vh;">
        
        {{-- Floating File Canvas --}}
        <div id="lightbox_viewer_container" class="position-relative d-flex align-items-center justify-content-center bg-white rounded p-2 border border-secondary shadow-lg" style="max-width: 85vw; max-height: 80vh; overflow: auto; min-width: 300px; min-height: 300px;">
            <!-- Render Image Scan -->
            <img src="" id="lightbox_qr_img" alt="Zoomed Asset" class="img-fluid rounded transition-all" style="max-height: 75vh; max-width: 80vw; object-fit: contain; transform: scale(1); transform-origin: center; cursor: grab;">
            
            <!-- Render PDF Document Scan -->
            <iframe id="lightbox_pdf_viewer" class="d-none rounded" style="width: 80vw; height: 75vh; border: none;"></iframe>
        </div>

        {{-- Interactive Document Control Toolbar --}}
        <div id="lightbox_zoom_controls" class="mt-3 d-flex gap-3 align-items-center bg-dark bg-opacity-75 px-4 py-2 rounded-pill border border-secondary">
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="zoomImage(-0.15, event)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
            <span id="zoom_percent" class="text-white small fw-bold">100%</span>
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="zoomImage(0.15, event)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
            <button type="button" class="btn btn-sm btn-outline-light rounded-circle px-2.5 py-1" onclick="toggleFullscreen(event)" title="Toggle Fullscreen"><i class="bi bi-fullscreen" id="fullscreen_icon"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle px-2.5 py-1" onclick="resetZoom(event)" title="Reset Zoom"><i class="bi bi-arrow-counterclockwise"></i></button>
        </div>

        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the dark overlay boundary to close preview</p>
    </div>
</div>

{{-- 5. THEME-ADAPTIVE MODALS LOOP --}}
@foreach($groupedAppointments as $item)
    @php
        $app = $item->first();
        $isExpired = $app->isExpired();
    @endphp

    {{-- A. DELETE EXPIRED APPOINTMENT MODAL --}}
    @if(Auth::id() == $app->user_id)
        <div class="modal fade" id="deleteExpiredModal{{ $app->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-danger bg-card shadow-lg text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-trash text-danger display-4 d-block"></i>
                    </div>
                    <h5 class="text-main fw-bold mb-1 uppercase">Delete Expired Record?</h5>
                    <p class="text-secondary small mb-4">Are you sure you want to remove this expired appointment from your dashboard? This action will hide the record from your view.</p>
                    <div class="d-grid gap-2">
                        <form action="{{ route('appointments.soft-delete', $app->id) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100 py-2 fw-bold uppercase text-white">DELETE RECORD</button>
                        </form>
                        <button type="button" class="btn btn-link text-secondary text-decoration-none smaller" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- B. RESUBMIT PATIENT MODAL --}}
    @if(($app->status == 'returned' || $isExpired) && Auth::id() == $app->user_id)
        @include('appointments.partials.resubmit-modal', ['app' => $app])
    @endif
@endforeach

@endsection

@push('scripts')
<script>
// Toggle active panel details for Appointment History Tab
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

// Reset placeholder when switching tabs with safe conditional null-checks
function resetActiveDetail() {
    const detailsPlaceholder = document.getElementById('details-placeholder');
    if (detailsPlaceholder) {
        detailsPlaceholder.classList.remove('d-none');
    }
    
    const recordPlaceholder = document.getElementById('record-placeholder');
    if (recordPlaceholder) {
        recordPlaceholder.classList.remove('d-none');
    }

    document.querySelectorAll('.appointment-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.record-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));

    const addRecordPanel = document.getElementById('add-record-panel');
    if (addRecordPanel) {
        addRecordPanel.classList.add('d-none');
    }
}

// Show Digitize New Record Form Panel
function showAddRecordForm() {
    const recordPlaceholder = document.getElementById('record-placeholder');
    if (recordPlaceholder) {
        recordPlaceholder.classList.add('d-none');
    }
    document.querySelectorAll('.record-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));

    const addRecordPanel = document.getElementById('add-record-panel');
    if (addRecordPanel) {
        addRecordPanel.classList.remove('d-none');
    }
}

// Show Specific Digitized Record Detailed Workspace
function showRecordDetails(recordId) {
    const recordPlaceholder = document.getElementById('record-placeholder');
    if (recordPlaceholder) {
        recordPlaceholder.classList.add('d-none');
    }
    document.querySelectorAll('.record-detail-pane').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.app-list-card').forEach(el => el.classList.remove('border-accent', 'shadow-neon'));

    const addRecordPanel = document.getElementById('add-record-panel');
    if (addRecordPanel) {
        addRecordPanel.classList.add('d-none');
    }

    const detailPanel = document.getElementById(`record-details-${recordId}`);
    if(detailPanel) {
        detailPanel.classList.remove('d-none');
    }

    const listCard = document.getElementById(`record-card-${recordId}`);
    if(listCard) {
        listCard.classList.add('border-accent', 'shadow-neon');
    }
}

// Dynamically preview and render historical PDF inside the workspace frame
function previewHistFile(recordId, fileUrl, labelName) {
    const previewArea = document.getElementById(`preview-area-${recordId}`);
    const iframe = document.getElementById(`viewer-iframe-${recordId}`);
    const title = document.getElementById(`preview-title-${recordId}`);

    if (previewArea && iframe && title) {
        title.innerText = labelName.toUpperCase();
        iframe.src = fileUrl;
        previewArea.classList.remove('d-none');
    }
}

// Zoom/Lightbox Controller handlers (Image dragging & Fullscreen support)
let currentScale = 1;
let translateX = 0;
let translateY = 0;
let isDragging = false;
let startX, startY;

function zoomImage(amount, event) {
    if (event) event.stopPropagation();

    currentScale += amount;
    currentScale = Math.max(0.5, Math.min(3, currentScale)); // Cap zoom between 50% and 300%

    const img = document.getElementById('lightbox_qr_img');
    if (img) {
        img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
        img.style.cursor = currentScale > 1 ? 'grab' : 'default';
    }

    const percentEl = document.getElementById('zoom_percent');
    if (percentEl) {
        percentEl.innerText = `${Math.round(currentScale * 100)}%`;
    }
}
window.zoomImage = zoomImage;

function resetZoom(event) {
    if (event) event.stopPropagation();

    currentScale = 1;
    translateX = 0;
    translateY = 0;
    isDragging = false;
    
    const img = document.getElementById('lightbox_qr_img');
    if (img) {
        img.style.transform = `translate(0px, 0px) scale(1)`;
        img.style.cursor = 'default';
    }

    const percentEl = document.getElementById('zoom_percent');
    if (percentEl) {
        percentEl.innerText = '100%';
    }
}
window.resetZoom = resetZoom;

function zoomFile(fileSrc) {
    if (!fileSrc) return;

    const isPdf = fileSrc.toLowerCase().endsWith('.pdf') || fileSrc.startsWith('data:application/pdf');
    const img = document.getElementById('lightbox_qr_img');
    const iframe = document.getElementById('lightbox_pdf_viewer');
    const controls = document.getElementById('lightbox_zoom_controls');

    resetZoom();

    if (isPdf) {
        img.classList.add('d-none');
        controls.classList.add('d-none');
        iframe.src = fileSrc;
        iframe.classList.remove('d-none');
    } else {
        iframe.classList.add('d-none');
        iframe.src = '';
        img.src = fileSrc;
        img.classList.remove('d-none');
        controls.classList.remove('d-none');
    }

    document.getElementById('qr_lightbox').classList.remove('d-none');
    document.getElementById('qr_lightbox').classList.add('d-flex');
}
window.zoomQR = zoomFile;

function closeQRLightbox(event) {
    if (event) {
        const container = document.getElementById('lightbox_viewer_container');
        const controls = document.getElementById('lightbox_zoom_controls');
        if (container.contains(event.target) || (controls && controls.contains(event.target))) {
            return;
        }
    }
    document.getElementById('qr_lightbox').classList.add('d-none');
    document.getElementById('qr_lightbox').classList.remove('d-flex');

    if (document.fullscreenElement) {
        document.exitFullscreen().catch(err => console.error("Error exiting fullscreen:", err));
    }
    resetZoom();
}
window.closeQRLightbox = closeQRLightbox;

function toggleFullscreen(event) {
    if (event) event.stopPropagation();

    const container = document.getElementById('lightbox_viewer_container');
    const icon = document.getElementById('fullscreen_icon');

    if (!document.fullscreenElement) {
        container.requestFullscreen().then(() => {
            if (icon) {
                icon.classList.remove('bi-fullscreen');
                icon.classList.add('bi-fullscreen-exit');
            }
        }).catch(err => {
            console.error("Error attempting to enable fullscreen mode:", err);
        });
    } else {
        document.exitFullscreen().then(() => {
            if (icon) {
                icon.classList.remove('bi-fullscreen-exit');
                icon.classList.add('bi-fullscreen');
            }
        }).catch(err => {
            console.error("Error attempting to exit fullscreen mode:", err);
        });
    }
}
window.toggleFullscreen = toggleFullscreen;

document.addEventListener('fullscreenchange', () => {
    const icon = document.getElementById('fullscreen_icon');
    if (icon) {
        if (document.fullscreenElement) {
            icon.classList.remove('bi-fullscreen');
            icon.classList.add('bi-fullscreen-exit');
        } else {
            icon.classList.remove('bi-fullscreen-exit');
            icon.classList.add('bi-fullscreen');
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Draggable canvas functionality for zoomed-in images
    const img = document.getElementById('lightbox_qr_img');
    if (img) {
        img.addEventListener('mousedown', (e) => {
            if (currentScale > 1) {
                isDragging = true;
                img.style.cursor = 'grabbing';
                startX = e.clientX;
                startY = e.clientY;
                e.preventDefault();
            }
        });

        window.addEventListener('mousemove', (e) => {
            if (isDragging && currentScale > 1) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                translateX += dx;
                translateY += dy;
                startX = e.clientX;
                startY = e.clientY;
                img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
            }
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
            if (img) img.style.cursor = currentScale > 1 ? 'grab' : 'default';
        });

        // Mobile touch swipe gestures
        img.addEventListener('touchstart', (e) => {
            if (currentScale > 1 && e.touches.length === 1) {
                isDragging = true;
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            }
        }, { passive: true });

        img.addEventListener('touchmove', (e) => {
            if (isDragging && currentScale > 1 && e.touches.length === 1) {
                const dx = e.touches[0].clientX - startX;
                const dy = e.touches[0].clientY - startY;
                translateX += dx;
                translateY += dy;
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
            }
        }, { passive: true });

        img.addEventListener('touchend', () => {
            isDragging = false;
        });
    }

    // Fullscreen wheel-to-zoom mapping
    const container = document.getElementById('lightbox_viewer_container');
    if (container) {
        container.addEventListener('wheel', (e) => {
            if (document.fullscreenElement) {
                e.preventDefault();
                const amount = e.deltaY < 0 ? 0.15 : -0.15;
                zoomImage(amount);
            }
        }, { passive: false });
    }
});
</script>
@endpush

<style>
/* Scoped alignment styles */
#historyTabs .nav-link {
    color: var(--text-muted) !important;
    border: 1px solid var(--border-color);
    background-color: var(--bg-card) !important;
    border-radius: 8px;
    transition: 0.2s ease;
}
#historyTabs .nav-link:hover {
    border-color: var(--brand-accent) !important;
    color: var(--brand-accent) !important;
}
#historyTabs .nav-link.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
}

.shadow-neon { box-shadow: 0 0 10px rgba(25, 211, 140, 0.15) !important; }
.app-list-card { transition: all 0.2s ease; cursor: pointer; }
.app-list-card:hover { border-color: var(--brand-accent) !important; transform: translateX(2px); }
</style>
@endsection