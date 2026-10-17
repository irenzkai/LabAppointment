@extends('layouts.app')
@section('title', 'Laboratory Historical Archive')
@section('content')
@php
// Automatically fetch active services if a controller forgot to pass them
if (!isset($availableServices)) {
    $availableServices = \App\Models\Service::where('is_available', true)->orderBy('category')->orderBy('name')->get();
}

$existingRecords = $existingRecords ?? (is_array($labHistory->dynamic_data) ? array_reverse($labHistory->dynamic_data) : []);
$status = $labHistory->permission_status ?? 'none';

// Check if viewing self
$isSelf = (Auth::id() == $targetUser->id);
$isPatientView = Auth::user()->isPatient() || $isSelf;
$isStaffViewingPatient = (Auth::user()->isEmployee() && !$isSelf);

// Server-Side Active Tab Determination
$requestedTab = request('tab');
$activeTab = ($requestedTab === 'lab' || $requestedTab === 'lab-history' || session('success') || session('error')) ? 'lab' : 'app';

if ($activeTab === 'app' && $status !== 'granted' && $appointments->isEmpty() && count($existingRecords) > 0) {
    $activeTab = 'lab';
}

// Preserve Add Record form state on reload
$requestedAction = request('action');
$shouldOpenAddRecordByDefault = ($activeTab === 'lab' && $isStaffViewingPatient && ($requestedAction === 'add' || count($existingRecords) === 0));
@endphp

{{-- CRITICAL ANTI-FOUC SHIELD (Blocks unstyled flash before CSS is evaluated) --}}
<style>
    #patient-archive-page {
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.12s ease-in;
    }
    #patient-archive-page.fouc-ready {
        visibility: visible !important;
        opacity: 1 !important;
    }
</style>
<noscript>
    <style>
        #patient-archive-page { visibility: visible !important; opacity: 1 !important; }
    </style>
</noscript>

<div class="text-start" id="patient-archive-page" style="max-width: 1240px; margin: 0 auto; padding-top: 1.5rem; padding-bottom: 2rem;">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 page-header-archive" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">PATIENT ARCHIVE</h2>
            <p class="text-secondary mb-0 small">Records for: <span class="text-accent fw-bold uppercase">{{ $targetUser->name }}</span></p>
        </div>
        <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-back-appointments px-3 py-2 fw-bold text-uppercase">
            <i class="bi bi-arrow-left me-1.5"></i> BACK TO APPOINTMENTS
        </a>
    </div>

    {{-- Top-Level Notification Alerts --}}
    @if(session('success'))
        <div class="alert alert-clinical alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert" style="background-color: rgba(25, 211, 140, 0.08); border-left: 4px solid var(--brand-accent) !important; border-radius: 8px;">
            <i class="bi bi-check-circle-fill me-3 fs-4 text-accent"></i>
            <div class="text-start">
                <div class="fw-800 uppercase fs-x-small text-accent" style="font-size: 0.75rem; letter-spacing: 0.5px;">Success Notification</div>
                <div class="small text-main" style="color: var(--text-main) !important; font-size: 0.88rem; line-height: 1.4;">
                    {{ session('success') }}
                </div>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-clinical alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert" style="background-color: rgba(220, 53, 69, 0.08); border-left: 4px solid #dc3545 !important; border-radius: 8px;">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-danger"></i>
            <div class="text-start">
                <div class="fw-800 uppercase fs-x-small text-danger" style="font-size: 0.75rem; letter-spacing: 0.5px;">Notice</div>
                <div class="small text-main" style="color: var(--text-main) !important; font-size: 0.88rem; line-height: 1.4;">
                    {{ session('error') }}
                </div>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-clinical alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert" style="background-color: rgba(13, 202, 240, 0.08); border-left: 4px solid #0dcaf0 !important; border-radius: 8px;">
            <i class="bi bi-info-circle-fill me-3 fs-4 text-info"></i>
            <div class="text-start">
                <div class="fw-800 uppercase fs-x-small text-info" style="font-size: 0.75rem; letter-spacing: 0.5px;">Information</div>
                <div class="small text-main" style="color: var(--text-main) !important; font-size: 0.88rem; line-height: 1.4;">
                    {{ session('info') }}
                </div>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Nav Tabs (Instant Rendering) --}}
    <ul class="nav nav-pills mb-4 border-bottom pb-3 gap-2" id="historyTabs" role="tablist" style="border-color: var(--border-color) !important;">
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === 'app' ? 'active' : '' }} fw-bold px-4" id="tab-btn-app-history" data-bs-toggle="pill" data-bs-target="#app-history" onclick="switchArchiveTab('app')">APPOINTMENT HISTORY</button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === 'lab' ? 'active' : '' }} fw-bold px-4" id="tab-btn-lab-history" data-bs-toggle="pill" data-bs-target="#lab-history" onclick="switchArchiveTab('lab')">LABORATORY RECORDS</button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- TAB 1: APPOINTMENT HISTORY (Split-Pane Grid Layout) --}}
        <div class="tab-pane {{ $activeTab === 'app' ? 'show active' : '' }}" id="app-history">
            <div class="row g-4 align-items-start">
                {{-- Left Side: Historical Appointment Deck --}}
                <div class="col-lg-5 col-xl-4">
                    <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 520px;">
                        @forelse($appointments as $app)
                            @include('appointments.partials.list-card', ['app' => $app, 'groupCount' => 1, 'isHistory' => true])
                        @empty
                            <div class="card p-4 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 260px; background-color: var(--bg-card); border-radius: 14px;">
                                <i class="bi bi-calendar-x text-accent fs-2 mb-2 opacity-75"></i>
                                <p class="small mb-0">No past bookings found.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Right Side: Selected Archive Detail Workspace --}}
                <div class="col-lg-7 col-xl-8">
                    <div id="workspace-container">
                        {{-- Default Empty State Placeholder (Always visible when none is selected) --}}
                        <div id="details-placeholder" class="card p-4 text-center border-secondary bg-card d-flex flex-column align-items-center justify-content-center placeholder-compact" style="min-height: 280px; background-color: var(--bg-card); border-radius: 14px;">
                            <div class="bg-secondary bg-opacity-10 rounded-circle p-2.5 mb-3 text-accent d-inline-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                                <i class="bi bi-clipboard2-pulse-fill fs-2 text-accent"></i>
                            </div>
                            <h5 class="text-main fw-bold mb-1 uppercase" style="font-size: 1.15rem;">Archive Workspace</h5>
                            <p class="text-muted small mb-0" style="max-width: 380px; font-size: 0.83rem;">Select any clinical entry from the left-hand panel to review its test breakdowns, billing summaries, and context actions.</p>
                        </div>

                        {{-- Hidden Detail Panels --}}
                        @foreach($appointments as $app)
                            @include('appointments.partials.detail-card', ['app' => $app, 'is_staff' => false, 'isHistory' => true])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: LABORATORY RECORDS (Full-Width Workspace with Split-Pane Layout) --}}
        <div class="tab-pane {{ $activeTab === 'lab' ? 'show active' : '' }}" id="lab-history">
            @if($status == 'granted' || $isStaffViewingPatient)
                <div class="row g-4 align-items-start">
                    {{-- Left Side: Historical Records List --}}
                    <div class="col-lg-5 col-xl-4">
                        {{-- Staff can ADD RECORD on other patients --}}
                        @if($isStaffViewingPatient)
                            <button type="button" class="btn-custom btn-accent w-100 py-2.5 mb-2 fw-bold uppercase shadow-sm" onclick="showAddRecordForm()">
                                <i class="bi bi-plus-lg me-1"></i> ADD RECORD
                            </button>
                        @endif

                        {{-- "Notify Patient" action button for staff --}}
                        @if($isStaffViewingPatient && count($existingRecords) > 0)
                            <form action="{{ route('history.notify-encoded', $targetUser->id) }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="btn-custom btn-outline-accent w-100 py-2 fw-bold uppercase shadow-sm">
                                    <i class="bi bi-bell-fill me-1"></i> Notify Patient (Encoded)
                                </button>
                            </form>
                        @endif

                        <div class="d-flex flex-column gap-2 overflow-auto custom-scroll" style="max-height: 520px;">
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
                                <div class="card p-4 text-center text-muted border-secondary border-dashed d-flex flex-column align-items-center justify-content-center" style="min-height: 260px; background-color: var(--bg-card); border-radius: 14px;">
                                    <i class="bi bi-file-earmark-lock2 fs-2 mb-2 opacity-50"></i>
                                    <p class="small mb-0">No historical records digitized yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Right Side: Active Workspace --}}
                    <div class="col-lg-7 col-xl-8">
                        <div id="lab-workspace-container">
                            {{-- Default Placeholder (Visible if no record is selected and not adding record) --}}
                            <div id="record-placeholder" class="card p-4 text-center border-secondary bg-card d-flex flex-column align-items-center justify-content-center placeholder-compact {{ $shouldOpenAddRecordByDefault ? 'd-none' : '' }}" style="min-height: 280px; background-color: var(--bg-card); border-radius: 14px;">
                                <div class="bg-secondary bg-opacity-10 rounded-circle p-2.5 mb-3 text-accent d-inline-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                                    <i class="bi bi-file-earmark-medical fs-2 text-accent"></i>
                                </div>
                                <h5 class="text-main fw-bold mb-1 uppercase" style="font-size: 1.15rem;">Laboratory Records Workspace</h5>
                                <p class="text-muted small mb-0" style="max-width: 380px; font-size: 0.83rem;">Select a digitized laboratory record from the left-hand sidebar directory to review files and download reports.</p>
                            </div>

                            {{-- Digitize New Record Form Panel (Staff only) --}}
                            @if($isStaffViewingPatient)
                                @include('appointments.partials.digitize-records', ['isOpen' => $shouldOpenAddRecordByDefault])
                            @endif

                            {{-- Records Detail Workspace --}}
                            @foreach($existingRecords as $record)
                                <div id="record-details-{{ $record['id'] }}" class="record-detail-pane card border-secondary bg-card p-4 d-none" style="background-color: var(--bg-card); color: var(--text-main); border-radius: 14px;">
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

                                    <div class="row g-4 text-start">
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

                                        <div class="col-md-6">
                                            <h6 class="text-accent small fw-bold uppercase mb-3"><i class="bi bi-file-earmark-pdf-fill me-1"></i> Attached Scanned Reports</h6>
                                            <div class="d-flex flex-column gap-2 mb-3">
                                                @foreach($record['scans'] ?? [] as $fileIdx => $scan)
                                                    <div class="d-flex justify-content-between align-items-center p-2.5 border rounded" style="background-color: rgba(25, 211, 140, 0.05); border-color: rgba(25, 211, 140, 0.15) !important;">
                                                        <div class="d-flex flex-column pe-2 text-truncate">
                                                            <span class="fw-bold small text-truncate" style="color: var(--text-main) !important; font-size: 0.85rem;">
                                                                <i class="bi bi-file-earmark-medical text-accent me-1"></i> {{ $scan['label'] }}
                                                            </span>
                                                            @if(!empty($scan['certificate_no']))
                                                                <div class="mt-1">
                                                                    <span class="badge border border-accent text-accent px-2 py-0.5 uppercase fw-bold" style="font-size: 0.68rem; background-color: rgba(25, 211, 140, 0.1); letter-spacing: 0.5px;">
                                                                        <i class="bi bi-patch-check-fill me-1"></i>Cert #: {{ $scan['certificate_no'] }}
                                                                    </span>
                                                                </div>
                                                            @else
                                                                <div class="mt-1">
                                                                    <span class="badge border border-secondary text-secondary px-2 py-0.5 uppercase" style="font-size: 0.65rem;">
                                                                        No Cert Number
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex gap-2 flex-shrink-0 align-items-center">
                                                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" style="font-size: 0.75rem;" onclick="previewHistFile('{{ $record['id'] }}', '{{ Storage::url($scan['file_path']) }}', '{{ $scan['label'] }}', '{{ $scan['certificate_no'] ?? '' }}')">PREVIEW</button>
                                                            <a href="{{ Storage::url($scan['file_path']) }}" download class="btn btn-sm btn-accent py-1 px-3 fw-bold" style="font-size: 0.75rem;">DOWNLOAD</a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="col-12 border-top border-secondary border-opacity-25 pt-4 d-none" id="preview-area-{{ $record['id'] }}">
                                            <div class="bg-warning text-dark p-2 px-3 fw-bold small uppercase rounded-top d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-eye-fill me-2"></i>PREVIEWING ATTACHED FILE: <span id="preview-title-{{ $record['id'] }}"></span></span>
                                                <button type="button" class="btn p-0 text-dark border-0 d-flex align-items-center" onclick="closeHistPreview('{{ $record['id'] }}')" title="Close Preview">
                                                    <i class="bi bi-x-lg" style="font-weight: 800; font-size: 0.8rem;"></i>
                                                </button>
                                            </div>
                                            <div class="card border-warning border-top-0 rounded-0 rounded-bottom overflow-hidden bg-card p-4 d-flex justify-content-center align-items-center" style="min-height: 480px; background-color: var(--bg-card);">
                                                <div id="image-container-{{ $record['id'] }}" class="position-relative d-inline-block image-preview-wrapper d-none" style="cursor: zoom-in;" onclick="window.zoomQR(this.dataset.url)">
                                                    <img id="image-img-{{ $record['id'] }}" src="" class="img-fluid rounded shadow-sm mx-auto d-block" style="max-height: 500px; object-fit: contain; border: 1px solid var(--border-color);">
                                                    <div class="position-absolute top-50 start-50 translate-middle zoom-overlay d-flex flex-column align-items-center justify-content-center text-white">
                                                        <i class="bi bi-zoom-in fs-1"></i>
                                                        <span class="fw-bold mt-2" style="font-size: 0.8rem;">CLICK TO ZOOM FULLSCREEN</span>
                                                    </div>
                                                </div>

                                                <div id="pdf-container-{{ $record['id'] }}" class="w-100 h-100 d-none d-flex justify-content-center align-items-center">
                                                    <iframe id="pdf-iframe-{{ $record['id'] }}" class="w-100 rounded mx-auto" style="min-height: 480px; border: none;"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                {{-- FULL-WIDTH HANDSHAKE / REQUEST DATA IMPORT BOX CONTAINER --}}
                <div class="w-100 my-2" id="handshake-box-container">
                    <div class="handshake-box-wrapper w-100">
                        @if(view()->exists('appointments.partials.handshake-panel'))
                            @include('appointments.partials.handshake-panel', ['isSelf' => $isSelf, 'isPatientView' => $isPatientView])
                        @else
                            <div class="card p-4 p-md-5 text-center border-secondary shadow-sm w-100" style="background-color: var(--bg-card); border-radius: 16px;">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 p-2.5 text-accent mx-auto" style="background-color: rgba(25, 211, 140, 0.08); width: 58px; height: 58px;">
                                    <i class="bi bi-shield-lock-fill fs-2 text-accent"></i>
                                </div>
                                <h5 class="text-main fw-bold mb-2 uppercase" style="font-size: 1.25rem;">Historical Data Connection</h5>
                                <p class="text-muted small mb-3.5 mx-auto" style="max-width: 540px; font-size: 0.88rem; line-height: 1.5;">
                                    Your physical clinic records have not been digitized yet. Submit a request to have our laboratory team initialize your archival folder.
                                </p>
                                @if($status === 'pending_staff')
                                    <div class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-2 px-3 uppercase fw-bold" style="font-size: 0.72rem;">
                                        <i class="bi bi-hourglass-split me-1"></i> Status: Pending Clinical Review
                                    </div>
                                @elseif($status === 'pending_patient')
                                    <form action="{{ route('history.accept', ['user' => $targetUser->id]) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn-custom btn-accent py-2.5 px-5 fw-bold uppercase shadow-sm" style="font-size: 0.82rem;">
                                            <i class="bi bi-check-circle-fill me-1.5"></i> Allow Digitization
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('history.request') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn-custom btn-accent py-2.5 px-5 fw-bold uppercase shadow-sm" style="font-size: 0.82rem;">
                                            <i class="bi bi-send-fill me-1.5"></i> Request Data Import
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MULTI-FORMAT LIGHTBOX OVERLAY --}}
    <div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox(event)">
        <div class="text-center p-3 w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="max-width: 95vw; max-height: 95vh;">
            <div id="lightbox_viewer_container" class="position-relative d-flex align-items-center justify-content-center bg-white rounded p-2 border border-secondary shadow-lg" style="max-width: 85vw; max-height: 80vh; overflow: auto; min-width: 300px; min-height: 300px;">
                <img src="" id="lightbox_qr_img" alt="Zoomed Asset" class="img-fluid rounded transition-all" style="max-height: 75vh; max-width: 80vw; object-fit: contain; transform: scale(1); transform-origin: center; cursor: grab;">
                <iframe id="lightbox_pdf_viewer" class="d-none rounded" style="width: 80vw; height: 75vh; border: none;"></iframe>
            </div>

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
    @foreach($appointments as $app)
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
    @endforeach
</div>

{{-- INSTANT ANTI-FOUC HYDRATION TRIGGER --}}
<script>
    (function() {
        const root = document.getElementById('patient-archive-page');
        if (root) {
            root.classList.add('fouc-ready');
        }
    })();
</script>

@push('scripts')
<script>
// --- GLOBAL WORKSPACE HANDLERS ---

function switchArchiveTab(tabKey) {
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        history.replaceState(null, null, url.toString());
        sessionStorage.setItem("archive_tab_{{ $targetUser->id }}", tabKey);
    } catch (e) {}
}

function showAppointmentDetails(appId) {
    const placeholder = document.getElementById('details-placeholder');
    if (placeholder) {
        placeholder.classList.add('d-none');
    }
    document.querySelectorAll('.appointment-detail-pane').forEach(el => {
        el.classList.add('d-none');
    });
    document.querySelectorAll('.app-list-card').forEach(el => {
        el.classList.remove('border-accent', 'shadow-neon');
    });

    const detailPanel = document.getElementById(`details-${appId}`);
    if (detailPanel) {
        detailPanel.classList.remove('d-none');
    }
    const listCard = document.getElementById(`card-${appId}`);
    if (listCard) {
        listCard.classList.add('border-accent', 'shadow-neon');
    }
}

function showRecordDetails(recordId) {
    const recordPlaceholder = document.getElementById('record-placeholder');
    if (recordPlaceholder) {
        recordPlaceholder.classList.add('d-none');
    }
    document.querySelectorAll('.record-detail-pane').forEach(el => {
        el.classList.add('d-none');
    });
    document.querySelectorAll('.app-list-card').forEach(el => {
        el.classList.remove('border-accent', 'shadow-neon');
    });

    const addRecordPanel = document.getElementById('add-record-panel');
    if (addRecordPanel) {
        addRecordPanel.classList.add('d-none');
    }
    const detailPanel = document.getElementById(`record-details-${recordId}`);
    if (detailPanel) {
        detailPanel.classList.remove('d-none');
    }
    const listCard = document.getElementById(`record-card-${recordId}`);
    if (listCard) {
        listCard.classList.add('border-accent', 'shadow-neon');
    }

    try {
        const url = new URL(window.location.href);
        url.searchParams.delete('action');
        history.replaceState(null, null, url.toString());
        sessionStorage.removeItem("archive_action_{{ $targetUser->id }}");
    } catch (e) {}
}

function showAddRecordForm() {
    const recordPlaceholder = document.getElementById('record-placeholder');
    if (recordPlaceholder) {
        recordPlaceholder.classList.add('d-none');
    }
    document.querySelectorAll('.record-detail-pane').forEach(el => {
        el.classList.add('d-none');
    });
    document.querySelectorAll('.app-list-card').forEach(el => {
        el.classList.remove('border-accent', 'shadow-neon');
    });

    const addRecordPanel = document.getElementById('add-record-panel');
    if (addRecordPanel) {
        addRecordPanel.classList.remove('d-none');
        if (typeof initializeDigAddress === 'function') {
            initializeDigAddress();
        }
    }

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'lab');
        url.searchParams.set('action', 'add');
        history.replaceState(null, null, url.toString());
        sessionStorage.setItem("archive_action_{{ $targetUser->id }}", "add");
    } catch (e) {}
}

function hideAddRecordForm() {
    const addRecordPanel = document.getElementById('add-record-panel');
    if (addRecordPanel) {
        addRecordPanel.classList.add('d-none');
    }
    const recordPlaceholder = document.getElementById('record-placeholder');
    if (recordPlaceholder) {
        recordPlaceholder.classList.remove('d-none');
    }

    try {
        const url = new URL(window.location.href);
        url.searchParams.delete('action');
        history.replaceState(null, null, url.toString());
        sessionStorage.removeItem("archive_action_{{ $targetUser->id }}");
    } catch (e) {}
}

function resetActiveDetail() {}

function closeHistPreview(recordId) {
    const previewArea = document.getElementById(`preview-area-${recordId}`);
    const pdfIframe = document.getElementById(`pdf-iframe-${recordId}`);
    const imgEl = document.getElementById(`image-img-${recordId}`);
    if (previewArea) previewArea.classList.add('d-none');
    if (pdfIframe) pdfIframe.src = '';
    if (imgEl) imgEl.src = '';
}

function previewHistFile(recordId, fileUrl, labelName, certNo = '') {
    const previewArea = document.getElementById(`preview-area-${recordId}`);
    const title = document.getElementById(`preview-title-${recordId}`);
    const imgContainer = document.getElementById(`image-container-${recordId}`);
    const imgEl = document.getElementById(`image-img-${recordId}`);
    const pdfContainer = document.getElementById(`pdf-container-${recordId}`);
    const pdfIframe = document.getElementById(`pdf-iframe-${recordId}`);

    if (!previewArea || !title) return;

    const certDisplay = certNo ? ` (CERT: ${certNo})` : '';
    title.innerText = (labelName + certDisplay).toUpperCase();
    previewArea.classList.remove('d-none');

    const isPdf = fileUrl.toLowerCase().endsWith('.pdf') || fileUrl.startsWith('data:application/pdf');

    if (isPdf) {
        if (imgContainer) imgContainer.classList.add('d-none');
        if (imgEl) imgEl.src = '';
        if (pdfContainer && pdfIframe) {
            pdfIframe.src = fileUrl;
            pdfContainer.classList.remove('d-none');
        }
    } else {
        if (pdfContainer) pdfContainer.classList.add('d-none');
        if (pdfIframe) pdfIframe.src = '';
        if (imgContainer && imgEl) {
            imgEl.src = fileUrl;
            imgContainer.dataset.url = fileUrl;
            imgContainer.classList.remove('d-none');
        }
    }
}

// Zoom/Lightbox Controller handlers
let currentScale = 1;
let translateX = 0;
let translateY = 0;
let isDragging = false;
let startX, startY;

function zoomImage(amount, event) {
    if (event) event.stopPropagation();
    currentScale += amount;
    currentScale = Math.max(0.5, Math.min(3, currentScale));
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

// Hoist functions to window
window.switchArchiveTab = switchArchiveTab;
window.showAppointmentDetails = showAppointmentDetails;
window.showRecordDetails = showRecordDetails;
window.showAddRecordForm = showAddRecordForm;
window.hideAddRecordForm = hideAddRecordForm;
window.resetActiveDetail = resetActiveDetail;
window.closeHistPreview = closeHistPreview;
window.previewHistFile = previewHistFile;
window.zoomImage = zoomImage;
window.resetZoom = resetZoom;
window.zoomQR = zoomFile;
window.closeQRLightbox = closeQRLightbox;
window.toggleFullscreen = toggleFullscreen;

// Check stored tab and action on restore
window.addEventListener('load', () => {
    try {
        const storedAction = sessionStorage.getItem("archive_action_{{ $targetUser->id }}");
        if (storedAction === 'add') {
            showAddRecordForm();
        }
    } catch (e) {}

    const lightbox = document.getElementById('qr_lightbox');
    const container = document.getElementById('lightbox_viewer_container');
    if (container && lightbox) {
        container.addEventListener('wheel', (e) => {
            if (!lightbox.classList.contains('d-none')) {
                e.preventDefault();
                const amount = e.deltaY < 0 ? 0.15 : -0.15;
                zoomImage(amount, e);
            }
        }, { passive: false });
    }
});
</script>
@endpush

@push('styles')
<style>
/* 1. Prevent parent flex from stretching page and pushing footer artificially */
main,
main.container,
body:has(#patient-archive-page) main,
div:has(> #patient-archive-page) {
    flex-grow: 0 !important;
    flex: none !important;
    min-height: auto !important;
}

#patient-archive-page {
    margin-bottom: 2rem !important;
}

/* 2. Standard Spacing */
.page-header-archive {
    margin-bottom: 1.5rem !important;
}
#historyTabs {
    margin-bottom: 1.75rem !important;
}

/* 3. High-Contrast Back to Appointments Button */
.btn-back-appointments {
    color: var(--text-main) !important;
    border: 1.5px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    border-radius: 8px !important;
    font-size: 0.75rem !important;
    letter-spacing: 0.5px !important;
    transition: all 0.2s ease-in-out !important;
}
.btn-back-appointments:hover {
    background-color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
    color: #1c232d !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(25, 211, 140, 0.2) !important;
}

/* 4. Compact Empty State Placeholders */
.placeholder-compact {
    padding: 2.25rem 1.5rem !important;
    border-radius: 14px !important;
}

/* 5. Nav Tabs & Cards */
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

/* 6. Handshake Box Container */
#handshake-box-container {
    width: 100% !important;
    padding: 0.5rem 0 !important;
}
.handshake-box-wrapper {
    width: 100% !important;
}
.handshake-box-wrapper .card,
#handshake-box-container .card {
    width: 100% !important;
    border-radius: 16px !important;
    min-height: auto !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04) !important;
}

/* 7. Custom zoom elements wrapper overrides */
.image-preview-wrapper {
    position: relative;
}
.image-preview-wrapper .zoom-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    background: rgba(0, 0, 0, 0.6);
    transition: opacity 0.22s ease-in-out;
    border-radius: inherit;
}
.image-preview-wrapper:hover .zoom-overlay {
    opacity: 1;
}
</style>
@endpush