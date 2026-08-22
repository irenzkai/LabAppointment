@extends('layouts.app')

@section('title', 'Encode Results')

@section('content')
@php
    $res = $appointment->result;
    $selectedTypes = $autoReportTypes;
    // Defined $isReadonly at the hub level so the main page and nested dynamic partials know when the folder is locked
    $isReadonly = ($appointment->status === 'released');

    // Clean associative status themes mapping to prevent compiler syntax conflicts
    $statusThemes = [
        'verified' => [
            'style' => 'background-color: rgba(25, 211, 140, 0.12) !important; color: #15b376 !important;', 
            'border' => '#19d38c', 
            'label' => 'VERIFIED'
        ],
        'encoded' => [
            'style' => 'background-color: rgba(13, 202, 240, 0.12) !important; color: #0b93b8 !important;', 
            'border' => '#0dcaf0', 
            'label' => 'READY FOR SIGN OFF'
        ],
        'encoding' => [
            'style' => 'background-color: rgba(255, 193, 7, 0.15) !important; color: #b58105 !important;', 
            'border' => '#ffc107', 
            'label' => 'IN PROGRESS'
        ],
        'returned' => [
            'style' => 'background-color: rgba(220, 53, 69, 0.12) !important; color: #b02a37 !important;', 
            'border' => '#dc3545', 
            'label' => 'RE-EDIT REQUIRED'
        ],
        'pending' => [
            'style' => 'background-color: rgba(108, 117, 125, 0.1) !important; color: var(--text-muted) !important;', 
            'border' => 'var(--border-color)', 
            'label' => 'PENDING'
        ]
    ];

    // Global Release Logic
    $readyToRelease = true;
    foreach($selectedTypes as $type) {
        $p = ($type == 'med_cert' ? 'med' : $type);
        $statusKey = $p . '_status';
        if(($res->$statusKey ?? 'pending') !== 'verified') {
            $readyToRelease = false;
        }
    }

    // Also block final release if any of the dynamic custom worksheets are not verified yet
    foreach($appointment->result->customWorkstationResults as $custom) {
        if ($custom->status !== 'verified') {
            $readyToRelease = false;
        }
    }
@endphp

<div id="results-hub-page" class="container text-start animate-page">
    {{-- 1. HUB HEADER --}}
    <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">Results Management Hub</h2>
            <p class="text-secondary small mb-0 uppercase fw-bold" style="letter-spacing: 1px;">Clinical Validation Workflow | Ref: #{{ $appointment->id }}</p>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('appointments.index', ['view' => 'queue']) }}" class="text-secondary small text-decoration-none hover-neon d-flex align-items-center">
                <i class="bi bi-arrow-left me-1"></i> BACK TO MASTER QUEUE
            </a>
        </div>
    </div>

    {{-- 2. PATIENT CONTEXT BAR (Theme-Aware Details Card with Accordion) --}}
    <div class="mb-5 p-4 rounded border border-secondary border-opacity-25 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
        <div class="row align-items-center">
            <div class="col-md-7 text-start">
                <h4 class="fw-bold mb-1" style="color: var(--text-main) !important;">{{ strtoupper($appointment->patient_name) }}</h4>
                <div class="text-secondary smaller fw-bold uppercase">
                    {{ $appointment->patient_age }} Years Old <span class="mx-2">|</span> 
                    {{ strtoupper($appointment->patient_sex) }} <span class="mx-2">|</span> 
                    Tested: {{ $appointment->tested_at ? $appointment->tested_at->format('M d, Y') : 'Processing' }}
                </div>
                <div class="text-accent smaller fw-bold uppercase mt-2">
                    <i class="bi bi-flask-fill me-1"></i> Tests Requested: 
                    <span style="color: var(--text-main);">{{ $appointment->services->pluck('name')->implode(', ') }}</span>
                </div>
            </div>
            
            <div class="col-md-5 mt-3 mt-md-0 d-flex justify-content-end align-items-center gap-3 flex-wrap">
                <div class="text-end me-2">
                    <label class="text-secondary smaller fw-bold uppercase d-block mb-1">Folder Status</label>
                    <span class="badge {{ $appointment->status == 'released' ? 'bg-neon text-dark' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' }} px-3 py-2 fs-6 uppercase">
                        {{ strtoupper($appointment->status) }}
                    </span>
                </div>
                
                {{-- Accordion Toggle Button (Arrow Icon Only) --}}
                <button class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center fw-bold uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#patientDetailsAccordion" aria-expanded="false" aria-controls="patientDetailsAccordion" style="border-radius: 8px; width: 38px; height: 38px; padding: 0;" title="Toggle Details">
                    <i class="bi bi-chevron-down transition-all" id="accordionChevron"></i>
                </button>
            </div>
        </div>

        {{-- Collapsible Accordion Drawer for Extended Appointment Details & Edit Action --}}
        <div class="collapse mt-4 pt-3 border-top border-secondary border-opacity-25" id="patientDetailsAccordion">
            <div class="row g-4 text-start">
                {{-- Schedule & Contact Details --}}
                <div class="col-md-4 border-end border-secondary border-opacity-10 pe-md-3">
                    <h6 class="text-accent fw-bold small uppercase mb-3" style="letter-spacing: 0.5px;">
                        <i class="bi bi-calendar-event me-1.5"></i>Schedule & Contact
                    </h6>
                    <div class="mb-2">
                        <small class="text-secondary d-block smaller fw-bold uppercase">Schedule Date & Time</small>
                        <span class="small fw-semibold">{{ $appointment->appointment_date ? $appointment->appointment_date->format('M d, Y') : 'N/A' }} | {{ date('h:i A', strtotime($appointment->time_slot)) }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-secondary d-block smaller fw-bold uppercase">Contact Phone</small>
                        <span class="small fw-semibold">{{ $appointment->patient_phone }}</span>
                    </div>
                    <div>
                        <small class="text-secondary d-block smaller fw-bold uppercase">Email Address</small>
                        <span class="small fw-semibold">{{ $appointment->patient_email ?? $appointment->user?->email ?? 'N/A' }}</span>
                    </div>
                </div>

                {{-- Address & Organization --}}
                <div class="col-md-4 border-end border-secondary border-opacity-10 px-md-3">
                    <h6 class="text-accent fw-bold small uppercase mb-3" style="letter-spacing: 0.5px;">
                        <i class="bi bi-geo-alt me-1.5"></i>Location & Entity
                    </h6>
                    <div class="mb-2">
                        <small class="text-secondary d-block smaller fw-bold uppercase">Residential Address</small>
                        <span class="small fw-semibold">{{ $appointment->patient_address }}</span>
                    </div>
                    @if($appointment->organization_name)
                    <div>
                        <small class="text-secondary d-block smaller fw-bold uppercase">Organization / Batch</small>
                        <span class="small fw-semibold">{{ $appointment->organization_name }} (Batch #{{ $appointment->batch_id }})</span>
                    </div>
                    @endif
                </div>

                {{-- Billing & Referral Details --}}
                <div class="col-md-4 ps-md-3 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="text-accent fw-bold small uppercase mb-3" style="letter-spacing: 0.5px;">
                            <i class="bi bi-credit-card me-1.5"></i>Billing & Referral
                        </h6>
                        <div class="mb-2">
                            <small class="text-secondary d-block smaller fw-bold uppercase">Payment Settlement</small>
                            <span class="small fw-semibold">{{ $appointment->payment_method }} ({{ strtoupper($appointment->payment_status) }})</span>
                        </div>
                        <div class="mb-2">
                            <small class="text-secondary d-block smaller fw-bold uppercase">Confirmed Price</small>
                            <span class="small fw-bold text-accent">₱{{ number_format($appointment->payment_amount ?: $appointment->totalPrice(), 2) }}</span>
                        </div>
                        @if($appointment->referral_note)
                        <div class="mb-2">
                            <small class="text-secondary d-block smaller fw-bold uppercase">Doctor's Referral</small>
                            <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 small uppercase fw-bold" onclick="openFilePreview('{{ Storage::url($appointment->referral_note) }}', 'Doctor\'s Referral Note')" style="font-size: 0.7rem;">
                                <i class="bi bi-file-earmark-medical me-1"></i> View Referral Note
                            </button>
                        </div>
                        @endif
                    </div>

                    {{-- Relocated Edit Patient Details Button --}}
                    @if(!$isReadonly && auth()->user()->isEmployee())
                    <div class="mt-3 pt-3 border-top border-secondary border-opacity-10 text-end">
                        <a href="{{ route('appointments.edit-details', $appointment->id) }}?from=hub" class="btn btn-sm btn-accent fw-bold uppercase py-2 px-3 shadow-sm w-100" style="border-radius: 6px; font-size: 0.75rem;" title="Revise Details">
                            <i class="bi bi-pencil-square me-1.5"></i> Edit Patient Details & Services
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 3. MODULAR PROGRESS GRID --}}
    <div class="row g-4">
        {{-- A. Original Workstations Loop --}}
        @foreach($selectedTypes as $type)
        @php
            $prefix = ($type == 'med_cert' ? 'med' : $type);
            $statusField = $prefix . '_status';
            $currentStatus = $res->$statusField ?? 'pending';
            $theme = $statusThemes[$currentStatus] ?? $statusThemes['pending'];

            // Audit Names (System account names)
            $v1NameField = $prefix . '_v1_by_name';
            $v2NameField = $prefix . '_v2_by_name';
            $encoderName = $res->$v1NameField ?? '---';
            $verifierName = $res->$v2NameField ?? '---';

            // Formatted Timestamps
            $v1AtField = $prefix . '_v1_at';
            $v2AtField = ($type == 'lab' ? 'lab_v2_at' : $prefix . '_verified_at');

            $encodedAt = ($res && $res->$v1AtField) ? \Carbon\Carbon::parse($res->$v1AtField) : null;
            $verifiedAt = ($res && $res->$v2AtField) ? \Carbon\Carbon::parse($res->$v2AtField) : null;

            // Map route identifier correctly
            $routeAction = match($type) {
                'radio' => 'radiology',
                default => $type
            };

            // Check if this specific workstation result has been edited/corrected
            $audit = $res->audits->where('workstation_type', $type)->first();
            $isEdited = $appointment->results_released_at && $audit && \Carbon\Carbon::parse($audit->updated_at)->gt(\Carbon\Carbon::parse($appointment->results_released_at));

            // Return reason value
            $returnReasonField = $prefix . '_return_reason';
            $returnReasonValue = $res->$returnReasonField ?? '';
        @endphp
        <div class="col-md-6">
            <div class="card p-4 shadow-sm workstation-card h-100" style="background-color: var(--bg-card); color: var(--text-main); border-left: 4px solid {{ $theme['border'] }} !important; border-top-color: var(--border-color); border-right-color: var(--border-color); border-bottom-color: var(--border-color);">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h5 class="fw-bold mb-0 uppercase" style="color: var(--text-main);">
                            @if($type == 'lab') Laboratory Result
                            @elseif($type == 'radio') Radiology Report
                            @elseif($type == 'med_cert') Medical Certificate
                            @else Drug Test Result @endif
                        </h5>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge px-3 py-2 uppercase small border" style="{{ $theme['style'] }}">
                            {{ $theme['label'] }}
                        </span>
                        @if($isEdited)
                        <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1.5 uppercase small border border-warning border-opacity-25">(EDITED)</span>
                        @endif
                        @if(!$isReadonly && auth()->user()->isEmployee())
                        <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-none" style="border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#deleteOriginalWorkstationModal_{{ $type }}">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Return Reason Alert (Specific to this form) --}}
                @if($currentStatus == 'returned' && $returnReasonValue)
                <div class="alert bg-danger bg-opacity-10 border-danger text-danger py-2 px-3 small mb-3">
                    <div class="fw-bold uppercase smaller mb-1" style="font-size: 0.65rem;">Correction Reason:</div>
                    <div class="italic">"{{ $returnReasonValue }}"</div>
                </div>
                @endif

                {{-- System Audit Trail (Theme-Aware Container) --}}
                <div class="mb-4 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(0, 0, 0, 0.02);">
                    <div class="row g-0">
                        <div class="col-6 pe-2">
                            <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Encoder</label>
                            <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;">{{ $encoderName }}</span>
                            @if($encodedAt)
                            <span class="text-secondary smaller italic" style="font-size: 0.65rem;">
                                {{ $encodedAt->format('M d, Y | h:i A') }}
                                @if($isEdited) <span class="text-warning fw-bold">(Edited)</span> @endif
                            </span>
                            @endif
                        </div>
                        <div class="col-6 ps-3 border-start border-secondary border-opacity-25">
                            <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Verifier</label>
                            <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;">{{ $verifierName }}</span>
                            @if($verifiedAt)
                            <span class="text-secondary smaller italic" style="font-size: 0.65rem;">{{ $verifiedAt->format('M d, Y | h:i A') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Dynamic Workstation Navigation Link --}}
                <div class="mt-auto">
                    <a href="{{ route('workstation.' . $routeAction, $appointment->id) }}" class="btn btn-custom {{ $currentStatus == 'verified' ? 'btn-outline-accent text-accent' : 'btn-accent' }} w-100 py-2 fw-bold small uppercase" style="color: {{ $currentStatus == 'verified' ? 'var(--brand-accent)' : '#1c232d' }} !important; border-color: var(--brand-accent) !important;">
                        @if($currentStatus == 'verified') Review Data @elseif($currentStatus == 'encoded') Verify & Sign @else Open Workstation @endif
                    </a>
                </div>
            </div>
        </div>
        @endforeach

        {{-- B. Dynamic/Custom Worksheets Loop --}}
        @foreach($appointment->result->customWorkstationResults as $custom)
        @php
            $customAudit = $appointment->result->audits->where('workstation_type', "custom_{$custom->id}")->first();
            $customEncoderName = $customAudit->v1_by_name ?? '---';
            $customVerifierName = $customAudit->v2_by_name ?? '---';
            $customEncodedAt = $customAudit && $customAudit->v1_at ? \Carbon\Carbon::parse($customAudit->v1_at) : null;
            $customVerifiedAt = $customAudit && $customAudit->v2_at ? \Carbon\Carbon::parse($customAudit->v2_at) : null;

            $customStatus = $custom->status ?? 'pending';
            $customTheme = $statusThemes[$customStatus] ?? $statusThemes['pending'];
            $hasFile = !empty($custom->scan_path);
        @endphp
        <div class="col-md-6">
            <div class="card p-4 shadow-sm workstation-card h-100" style="background-color: var(--bg-card); color: var(--text-main); border-left: 4px solid {{ $customStatus === 'verified' ? '#19d38c' : ($customStatus === 'returned' ? '#dc3545' : '#0dcaf0') }} !important; border-top-color: var(--border-color); border-right-color: var(--border-color); border-bottom-color: var(--border-color);">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h5 class="fw-bold mb-0 uppercase" style="color: var(--text-main);">{{ $custom->name }}</h5>
                        <small class="text-secondary x-small mt-0.5 d-block">Cert ID: <span class="text-accent font-monospace">#{{ $custom->cert_no }}</span></small>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge px-3 py-2 uppercase small border" style="{{ $customTheme['style'] }}">
                            {{ $customTheme['label'] }}
                        </span>
                        @if(!$isReadonly && auth()->user()->isEmployee())
                        <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-none" style="border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#deleteCustomWorksheetModal_{{ $custom->id }}">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Return Reason Alert --}}
                @if($custom->status === 'returned' && $custom->return_reason)
                <div class="alert bg-danger bg-opacity-10 border-danger text-danger py-2 px-3 small mb-3">
                    <div class="fw-bold uppercase smaller mb-1" style="font-size: 0.65rem;">Correction Reason:</div>
                    <div class="italic">"{{ $custom->return_reason }}"</div>
                </div>
                @endif

                {{-- System Audit Trail --}}
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
                            <span class="text-secondary smaller italic" style="font-size: 0.65rem;">{{ $customVerifiedAt->format('M d, Y | h:i A') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Custom Workstation Dedicated Page Link --}}
                <div class="mt-auto">
                    <a href="{{ route('workstation.custom', [$appointment->id, $custom->id]) }}" class="btn btn-custom {{ $customStatus == 'verified' ? 'btn-outline-accent text-accent' : 'btn-accent' }} w-100 py-2 fw-bold small uppercase" style="color: {{ $customStatus == 'verified' ? 'var(--brand-accent)' : '#1c232d' }} !important; border-color: var(--brand-accent) !important;">
                        @if($customStatus == 'verified') Review Data @elseif($customStatus == 'encoded') Verify & Sign @else Open Workstation @endif
                    </a>
                </div>
            </div>
        </div>
        @endforeach

        {{-- C. ADD WORKSTATION TRIGGER CARD --}}
        @if(!$isReadonly && auth()->user()->isEmployee())
        <div class="col-md-6">
            <div class="card p-4 shadow-sm h-100 d-flex flex-column align-items-center justify-content-center border-dashed border-secondary" style="background-color: rgba(25, 211, 140, 0.015); min-height: 250px; border-style: dashed !important; border-width: 2px !important;">
                <button type="button" class="btn-custom btn-accent px-4 py-2" data-bs-toggle="modal" data-bs-target="#addWorkstationModal">
                    <i class="bi bi-plus-circle me-1.5"></i>ADD WORKSTATION
                </button>
                <small class="text-muted smaller mt-2 text-center">Attach other clinical results (e.g., Lab, Radiology, Drug, MedCert, or Custom Worksheets) scoped to this folder.</small>
            </div>
        </div>
        @endif
    </div>

    {{-- 4. FINAL RELEASE SECTION --}}
    <div class="mt-5 pt-5 border-top text-center" style="border-color: var(--border-color) !important;">
        @if($readyToRelease && $appointment->status !== 'released')
        <div class="mb-4">
            <h4 class="text-accent fw-bold mb-1 uppercase">Ready for Clinical Release</h4>
            <p class="text-secondary small">All internal clinical verifications are verified and signed. Proceed to finalize the patient folder.</p>
        </div>
        <button type="button" class="btn-custom btn-accent px-5 py-3 fs-5 shadow-lg fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#releaseConfirmModal">
            Execute Final Release
        </button>

        <!-- RELEASE CONFIRMATION MODAL -->
        <div class="modal fade" id="releaseConfirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg p-4 text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    <h4 class="fw-bold uppercase mb-2" style="color: var(--text-main);">Confirm Final Release?</h4>
                    <p class="text-secondary small mb-4">Finalizing will merge all forms into a single folder and release them to the patient's portal. This action locks all data for editing. Proceed?</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                        <form action="{{ route('appointments.status', $appointment->id) }}" method="POST" class="flex-grow-1 m-0">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="released">
                            <button type="submit" class="btn btn-accent w-100 fw-bold uppercase">Confirm Release</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @elseif($appointment->status === 'released')
        <div class="p-4 rounded border border-accent" style="background-color: rgba(25, 211, 140, 0.05); max-width: 600px; margin: 0 auto;">
            <p class="text-accent fw-bold mb-1 uppercase small">Folder Release Complete</p>
            <p class="small mb-0" style="color: var(--text-main);">Patient notified and record locked on {{ $appointment->updated_at->format('M d, Y | h:i A') }}</p>
        </div>
        @else
        <div class="p-4 rounded border border-secondary border-dashed" style="max-width: 600px; margin: 0 auto; background-color: rgba(0, 0, 0, 0.02);">
            <p class="text-warning fw-bold mb-1 uppercase small">Final Release Action Locked</p>
            <p class="text-secondary mb-0 smaller">The system is waiting for all required worksheets to be verified before the release button is enabled.</p>
        </div>
        @endif
    </div>
</div>

{{-- MODALS AND OVERLAYS SECTION --}}
@if(!$isReadonly && auth()->user()->isEmployee())
    @include('appointments.partials.encode.add-workstation-modal')
    @include('appointments.partials.encode.delete-original-modals')
@endif

{{-- Centralized Universal Lightbox Partial --}}
@include('layouts.partials.lightbox-overlay')
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const appointmentId = "{{ $appointment->id }}";

    // Initialize Pusher Connection for real-time live updates
    const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
        cluster: "{{ env('PUSHER_APP_CLUSTER') }}"
    });

    const channel = pusher.subscribe('clinical-queue');

    channel.bind('queue.updated', function() {
        fetch("{{ route('appointments.encode', $appointment->id) }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const oldContext = document.querySelector('.col-md-4.text-md-end');
            const newContext = doc.querySelector('.col-md-4.text-md-end');
            if (oldContext && newContext) {
                oldContext.innerHTML = newContext.innerHTML;
            }

            const oldGrid = document.querySelector('.row.g-4');
            const newGrid = doc.querySelector('.row.g-4');
            if (oldGrid && newGrid) {
                oldGrid.style.transition = 'opacity 0.2s ease-out';
                oldGrid.style.opacity = '0';

                setTimeout(() => {
                    oldGrid.innerHTML = newGrid.innerHTML;
                    oldGrid.style.opacity = '1';
                }, 200);
            }

            const oldRelease = document.querySelector('.mt-5.pt-5');
            const newRelease = doc.querySelector('.mt-5.pt-5');
            if (oldRelease && newRelease) {
                oldRelease.innerHTML = newRelease.innerHTML;
            }
        })
        .catch(error => console.error('Results Hub sync failed:', error));
    });

    // Dynamic Add Workstation modal toggle handling
    document.getElementById('add_workstation_type')?.addEventListener('change', function() {
        const type = this.value;
        const container = document.getElementById('custom_fields_container');
        if (!container) return;
        const customInputs = container.querySelectorAll('input');

        if (type === 'custom') {
            container.classList.remove('d-none');
            customInputs.forEach(input => input.setAttribute('required', 'required'));
        } else {
            container.classList.add('d-none');
            customInputs.forEach(input => input.removeAttribute('required'));
        }
    });

    // Dynamic Delete Original Workstation custom reason name-toggling
    document.querySelectorAll('[id^="delete_original_reason_select_"]').forEach(select => {
        select.addEventListener('change', function() {
            const type = this.id.replace('delete_original_reason_select_', '');
            const wrapper = document.getElementById(`delete_original_custom_reason_wrapper_${type}`);
            const textarea = document.getElementById(`delete_original_custom_reason_${type}`);

            if (this.value === 'Others') {
                wrapper.classList.remove('d-none');
                textarea.setAttribute('required', 'required');
                textarea.setAttribute('name', 'reason');
                this.removeAttribute('name');
                textarea.value = '';
            } else {
                wrapper.classList.add('d-none');
                textarea.removeAttribute('required');
                textarea.removeAttribute('name');
                this.setAttribute('name', 'reason');
            }
        });
    });
});

// Dynamic Delete Custom Workstation custom reason name-toggling
window.toggleCustomReasonField = function(id, select) {
    const wrapper = document.getElementById(`delete_custom_reason_wrapper_${id}`);
    const textarea = document.getElementById(`delete_custom_reason_${id}`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
            textarea.setAttribute('name', 'reason');
            select.removeAttribute('name');
            textarea.value = '';
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
            textarea.removeAttribute('name');
            select.setAttribute('name', 'reason');
        }
    }
};
</script>
@endpush

@push('styles')
<style>
/* Smooth interactive workstation card transitions */
.workstation-card { 
    transition: all 0.3s ease; 
}
.workstation-card:hover { 
    border-top-color: var(--brand-accent) !important;
    border-right-color: var(--brand-accent) !important;
    border-bottom-color: var(--brand-accent) !important;
    transform: translateY(-2px); 
}
.hover-neon:hover { 
    color: var(--brand-accent) !important; 
    transition: 0.2s; 
}

/* Patient Context Accordion Chevron Rotation */
#patientDetailsAccordion.collapsing ~ .row #accordionChevron,
#patientDetailsAccordion.show ~ .row #accordionChevron,
[aria-expanded="true"] #accordionChevron {
    transform: rotate(180deg);
}
</style>
@endpush