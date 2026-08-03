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
            <a href="{{ route('appointments.index') }}" class="text-secondary small text-decoration-none hover-neon d-flex align-items-center"><i class="bi bi-arrow-left me-1"></i> BACK TO MASTER QUEUE</a>
        </div>
    </div>

    {{-- 2. PATIENT CONTEXT BAR (Theme-Aware Details Card) --}}
    <div class="mb-5 p-4 rounded border border-secondary border-opacity-25 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
        <div class="row align-items-center">
            <div class="col-md-8 text-start">
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
            
            <div class="col-md-4 mt-3 mt-md-0 d-flex justify-content-end align-items-center gap-3">
                <div class="text-end">
                    <label class="text-secondary smaller fw-bold uppercase d-block mb-1">Folder Status</label>
                    <span class="badge {{ $appointment->status == 'released' ? 'bg-neon text-dark' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' }} px-3 py-2 fs-6 uppercase">
                        {{ strtoupper($appointment->status) }}
                    </span>
                </div>
                
                {{-- Edit details button placed adjacent to Folder Status with flex layouts to prevent overlaps --}}
                @if(!$isReadonly && auth()->user()->isEmployee())
                    <button type="button" class="btn btn-sm btn-outline-info hover-neon mt-4" style="border-radius: 6px; padding: 6px 10px;" data-bs-toggle="modal" data-bs-target="#editAppointmentDetailsModal" title="Revise Details">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                @endif
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
                
                // FIXED: Changed suffix from '_v2_at' to '_verified_at' to match active model attributes
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
                                <span class="text-warning ms-1" style="font-size: 0.65rem; font-weight: 800;">(EDITED)</span>
                            @endif

                            {{-- Option to delete original workstation --}}
                            @if(!$isReadonly && auth()->user()->isEmployee())
                                <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-none" style="border-radius:6px;" data-bs-toggle="modal" data-bs-target="#deleteOriginalWorkstationModal_{{ $type }}">
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

                    {{-- Dynamic Workstation Navigation Link (Custom themed Review Button) --}}
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

                            {{-- Delete Custom Worksheet with Confirmation Modal --}}
                            @if(!$isReadonly && auth()->user()->isEmployee())
                                <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 shadow-none" style="border-radius:6px;" data-bs-toggle="modal" data-bs-target="#deleteCustomWorksheetModal_{{ $custom->id }}">
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

                    {{-- Action Controls --}}
                    <div class="mt-auto d-flex gap-2">
                        @if($hasFile)
                            <button type="button" class="btn btn-sm btn-outline-accent text-accent flex-grow-1 py-2 fw-bold small uppercase" style="color: var(--brand-accent) !important; border-color: var(--brand-accent) !important;" onclick="zoomQR('{{ Storage::url($custom->scan_path) }}')">
                                <i class="bi bi-eye-fill me-1"></i> VIEW ATTACHED
                            </button>
                        @endif

                        @if(auth()->user()->isEmployee())
                            {{-- Verify action is only active if the overall folder is not released yet --}}
                            @if(!$isReadonly && $custom->status === 'encoded')
                                @can('isLabTech')
                                    <button type="button" class="btn btn-sm btn-accent flex-grow-1 py-2 fw-bold small uppercase" data-bs-toggle="modal" data-bs-target="#verifyCustomModal{{ $custom->id }}">
                                        <i class="bi bi-shield-check me-1"></i> VERIFY & APPROVE
                                    </button>
                                @endcan
                            @endif

                            {{-- Always permit returning back to encoder even if the worksheet status is verified/approved (even if folder is released) --}}
                            @if(in_array($custom->status, ['encoded', 'verified']))
                                @can('isLabTech')
                                    <button type="button" class="btn btn-sm btn-outline-danger px-3 py-2 fw-bold small uppercase" data-bs-toggle="modal" data-bs-target="#returnCustomModal{{ $custom->id }}">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                @endcan
                            @endif

                            {{-- Edit Trigger (Only visible on returned or pending worksheets when the folder is not released) --}}
                            @if(!$isReadonly && ($custom->status === 'returned' || $custom->status === 'pending'))
                                <button type="button" class="btn btn-sm btn-outline-info px-3" data-bs-toggle="modal" data-bs-target="#editCustomWorksheetModal{{ $custom->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            @endif
                        @endif
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

{{-- ========================================================================= --}}
{{-- MODALS AND OVERLAYS SECTION --}}
{{-- ========================================================================= --}}

{{-- A. REVISE PATIENT DETAILS MODAL (API Address Integration & Service Modification) --}}
@if(!$isReadonly && auth()->user()->isEmployee())
    <div class="modal fade" id="editAppointmentDetailsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('internal.appointment-details.update', $appointment->id) }}" method="POST" id="revise_details_form" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('PUT')
                <h5 class="text-accent fw-bold mb-1 uppercase"><i class="bi bi-pencil-square me-2"></i>Revise Patient Information</h5>
                <p class="text-secondary small mb-4">Correct demographic snapshots or edit requested medical services. Changes are recorded in compliance logs.</p>
                
                <div class="row g-3 text-start mb-3">
                    {{-- 1. Identity --}}
                    <div class="col-md-4">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">First Name</label>
                        <input type="text" name="patient_first_name" class="form-control uppercase" value="{{ $appointment->patient_first_name }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Middle Name</label>
                        <input type="text" name="patient_middle_name" class="form-control uppercase" value="{{ $appointment->patient_middle_name === 'N/A' ? '' : $appointment->patient_middle_name }}">
                    </div>
                    <div class="col-md-4">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Last Name</label>
                        <input type="text" name="patient_last_name" class="form-control uppercase" value="{{ $appointment->patient_last_name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Contact Phone</label>
                        <input type="text" name="patient_phone" class="form-control" value="{{ $appointment->patient_phone }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Gender</label>
                        <select name="patient_sex" class="form-select" required>
                            <option value="Male" {{ $appointment->patient_sex == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ $appointment->patient_sex == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Birthdate</label>
                        <input type="date" name="patient_birthdate" class="form-control" value="{{ $appointment->patient_birthdate ? $appointment->patient_birthdate->format('Y-m-d') : '' }}" required>
                    </div>

                    {{-- 2. API-Integrated Address Panel --}}
                    <div class="col-12 mt-3">
                        <h6 class="text-accent mb-2 small fw-bold uppercase">Residential Address</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">Province</label>
                                <select name="patient_province" id="revise_province" class="form-select" required>
                                    <option value="">Loading Provinces...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">City / Municipality</label>
                                <select name="patient_city" id="revise_city" class="form-select" disabled required>
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">Barangay</label>
                                <select name="patient_barangay" id="revise_barangay" class="form-select" disabled required>
                                    <option value="">Select City First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">Street / House No.</label>
                                <input type="text" name="patient_street" id="revise_street" class="form-control uppercase" value="{{ $appointment->patient_street }}" required>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Editable requested services list --}}
                    <div class="col-12 mt-3">
                        <h6 class="text-accent mb-2 small fw-bold uppercase">Requested Medical Services</h6>
                        
                        {{-- High-Speed Services Search Bar --}}
                        <div class="mb-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="revise_service_search" class="form-control form-control-sm" placeholder="Search services/tests...">
                            </div>
                        </div>

                        {{-- Services Checkbox Container styled elegantly with theme variables --}}
                        @php $currentServiceIds = $appointment->services->pluck('id')->toArray(); @endphp
                        <div class="p-3 border rounded row g-2" style="max-height: 180px; overflow-y: auto; background-color: var(--bg-main) !important; border: 1.5px solid var(--border-color) !important;">
                            @foreach($services as $service)
                                <div class="form-check col-md-6 mb-1 revise-service-item">
                                    <input class="form-check-input" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="revise_service_{{ $service->id }}" {{ in_array($service->id, $currentServiceIds) ? 'checked' : '' }}>
                                    <label class="form-check-label text-main small" for="revise_service_{{ $service->id }}">
                                        {{ $service->name }} (₱{{ number_format($service->price, 2) }})
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 4. Audit Trail Reason Dropdown --}}
                    <div class="col-12 mt-3 border-top border-secondary border-opacity-10 pt-3">
                        <h6 class="text-danger mb-2 small fw-bold uppercase"><i class="bi bi-shield-exclamation me-1"></i>Administrative Justification</h6>
                        <div class="mb-3">
                            <label class="smaller text-muted d-block mb-1">Select the official reason for modifying this patient's details.</label>
                            <select id="revise_reason_select" name="reason" class="form-select" required>
                                <option value="" disabled selected>-- Select a valid justification --</option>
                                <option value="Routine administrative update / profile maintenance">Routine administrative update / profile maintenance</option>
                                <option value="Official request for details correction">Official request for details correction</option>
                                <option value="Correction of typographical / data entry error">Correction of typological / data entry error</option>
                                <option value="Others">Others (Specify below)</option>
                            </select>
                        </div>
                        <div id="revise_custom_reason_wrapper" class="mb-0 d-none">
                            <label class="smaller fw-bold uppercase mb-1">Specify Custom Reason</label>
                            <textarea id="revise_custom_reason" class="form-control" rows="2" placeholder="Explain the profile revision justification..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-accent flex-grow-1 fw-bold uppercase">SAVE REVISIONS</button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- B. UNIFIED ADD WORKSTATION MODAL (Prevents adding existing worksheets) --}}
@if(!$isReadonly && auth()->user()->isEmployee())
    <div class="modal fade" id="addWorkstationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.add', $appointment->id) }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                <h5 class="text-accent fw-bold mb-1 uppercase"><i class="bi bi-plus-circle me-2"></i>Add Workstation</h5>
                <p class="text-secondary small mb-4">Attach an original clinical workstation or a custom dynamic worksheet scoped to this folder.</p>
                
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Workstation Type</label>
                    <select name="workstation_type" id="add_workstation_type" class="form-select" required>
                        <option value="" disabled selected>-- Select Workstation Type --</option>
                        <option value="lab" {{ in_array('lab', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>Laboratory Result {{ in_array('lab', $selectedTypes) ? '(Already Active)' : '' }}</option>
                        <option value="radio" {{ in_array('radio', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>Radiology Report {{ in_array('radio', $selectedTypes) ? '(Already Active)' : '' }}</option>
                        <option value="drug" {{ in_array('drug', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>Drug Test Result {{ in_array('drug', $selectedTypes) ? '(Already Active)' : '' }}</option>
                        <option value="med_cert" {{ in_array('med_cert', $selectedTypes) ? 'disabled style=color:var(--text-muted);' : '' }}>Medical Certificate {{ in_array('med_cert', $selectedTypes) ? '(Already Active)' : '' }}</option>
                        <option value="custom">Custom Worksheet</option>
                    </select>
                </div>

                {{-- Custom Dynamic Worksheet fields (Hidden initially) --}}
                <div id="custom_fields_container" class="d-none">
                    <div class="mb-3 text-start">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Worksheet Name</label>
                        <input type="text" name="custom_name" class="form-control" placeholder="e.g. Dental Clearance, ECG">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Certificate/Reference No.</label>
                        <input type="text" name="cert_no" class="form-control" placeholder="Enter reference tracking ID">
                    </div>
                    <div class="mb-4 text-start">
                        <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Scan File Upload</label>
                        <input type="file" name="scan_file" class="form-control" accept="image/*, application/pdf">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-accent flex-grow-1 fw-bold uppercase">CONFIRM ADD WORKSTATION</button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- C. DYNAMIC DUAL-MODALS FOR CUSTOM WORKSHEETS --}}
@foreach($appointment->result->customWorkstationResults as $custom)
    {{-- EDIT MODAL --}}
    <div class="modal fade" id="editCustomWorksheetModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.update', [$appointment->id, $custom->id]) }}" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('PUT')
                <h5 class="text-accent fw-bold mb-1 uppercase">Edit Worksheet: {{ $custom->name }}</h5>
                <p class="text-secondary small mb-4">Modify the file or certificate identifier mapped to this folder.</p>
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Certificate No.</label>
                    <input type="text" name="cert_no" class="form-control" value="{{ $custom->cert_no }}" required>
                </div>
                <div class="mb-4 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Replace Scan File</label>
                    <input type="file" name="scan_file" class="form-control" accept="image/*, application/pdf">
                    <small class="text-muted mt-1 d-block">Leave blank to keep the current file.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-accent flex-grow-1 fw-bold uppercase">SAVE CHANGES</button>
                </div>
            </form>
        </div>
    </div>

    {{-- VERIFY MODAL --}}
    <div class="modal fade" id="verifyCustomModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.verify', [$appointment->id, $custom->id]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                <h5 class="text-neon fw-bold mb-1 uppercase">Sign-Off: {{ $custom->name }}</h5>
                <p class="text-secondary small mb-4">Enter your name to verify that the uploaded document matches the patient's record details.</p>
                <div class="mb-4 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Verifier Full Name</label>
                    <input type="text" name="sig_name" class="form-control" value="{{ auth()->user()->name }}" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-neon flex-grow-1 fw-bold uppercase">Approve & Sign</button>
                </div>
            </form>
        </div>
    </div>

    {{-- RETURN MODAL --}}
    <div class="modal fade" id="returnCustomModal{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.return', [$appointment->id, $custom->id]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                <h5 class="text-danger fw-bold mb-1 uppercase">Return Worksheet: {{ $custom->name }}</h5>
                <p class="text-secondary small mb-4">Describe the necessary corrections for the encoder.</p>
                
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1" style="color: var(--text-muted);">Reason for Return</label>
                    <select id="return_custom_reason_select_{{ $custom->id }}" name="reason" class="form-select" required>
                        <option value="" disabled selected>-- Select a return justification --</option>
                        <option value="Mismatched patient identification or demographic fields">Mismatched patient identification or demographic fields</option>
                        <option value="Unclear, low-quality, or blurry document scan">Unclear, low-quality, or blurry document scan</option>
                        <option value="Incorrect reference value / Certificate No.">Incorrect reference value / Certificate No.</option>
                        <option value="Discrepancies in clinical signature or credentials">Discrepancies in clinical signature or credentials</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                
                <div id="return_custom_custom_reason_wrapper_{{ $custom->id }}" class="mb-4 text-start d-none">
                    <label class="smaller fw-bold uppercase mb-1">Specify Custom Correction Reason</label>
                    <textarea id="return_custom_custom_reason_{{ $custom->id }}" class="form-control" rows="4" placeholder="Identify the specific adjustment required..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-danger flex-grow-1 fw-bold uppercase">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>

    {{-- DELETE CUSTOM WORKSHEET MODAL (Requires Audit Reason) --}}
    <div class="modal fade" id="deleteCustomWorksheetModal_{{ $custom->id }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.custom.destroy', [$appointment->id, $custom->id]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('DELETE')
                <h5 class="text-danger fw-bold mb-1 uppercase"><i class="bi bi-trash me-2"></i>Delete Custom Worksheet?</h5>
                <p class="text-secondary small mb-4">You are about to delete the <strong>{{ $custom->name }}</strong> worksheet. This action permanently deletes all associated documents.</p>
                
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1 text-danger">Select Deletion Audit Reason</label>
                    <select id="delete_custom_reason_select_{{ $custom->id }}" name="reason" class="form-select" required>
                        <option value="" disabled selected>-- Select a valid justification --</option>
                        <option value="Accidental creation / Duplicate worksheet">Accidental creation / Duplicate worksheet</option>
                        <option value="Patient requested test cancellation">Patient requested test cancellation</option>
                        <option value="Clinician order revised / Modified billing">Clinician order revised / Modified billing</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                <div id="delete_custom_custom_reason_wrapper_{{ $custom->id }}" class="mb-4 text-start d-none">
                    <label class="smaller fw-bold uppercase mb-1 text-danger">Specify Custom Deletion Reason</label>
                    <textarea id="delete_custom_custom_reason_{{ $custom->id }}" class="form-control" rows="2" placeholder="Explain the deletion justification..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-danger flex-grow-1 fw-bold uppercase">CONFIRM DELETE</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

{{-- D. DYNAMIC MODALS FOR DELETING ORIGINAL WORKSTATIONS (Requires Audit Reason) --}}
@foreach($selectedTypes as $type)
    <div class="modal fade" id="deleteOriginalWorkstationModal_{{ $type }}" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('workstation.destroy-original', [$appointment->id, $type]) }}" method="POST" class="modal-content shadow-lg p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf
                @method('DELETE')
                <h5 class="text-danger fw-bold mb-1 uppercase"><i class="bi bi-trash me-2"></i>Delete Workstation Card?</h5>
                <p class="text-secondary small mb-4">You are about to delete the <strong>{{ strtoupper($type == 'med_cert' ? 'Medical Certificate' : ($type == 'radio' ? 'Radiology Report' : ($type == 'drug' ? 'Drug Test' : 'Laboratory Result'))) }}</strong> workstation. This cleans up all temporary data snapshots.</p>
                
                <div class="mb-3 text-start">
                    <label class="smaller fw-bold uppercase mb-1 text-danger">Select Deletion Audit Reason</label>
                    <select id="delete_original_reason_select_{{ $type }}" name="reason" class="form-select" required>
                        <option value="" disabled selected>-- Select a valid justification --</option>
                        <option value="Accidental creation / Duplicate workstation">Accidental creation / Duplicate workstation</option>
                        <option value="Patient requested test cancellation">Patient requested test cancellation</option>
                        <option value="Clinician order revised / Modified billing">Clinician order revised / Modified billing</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                <div id="delete_original_custom_reason_wrapper_{{ $type }}" class="mb-4 text-start d-none">
                    <label class="smaller fw-bold uppercase mb-1 text-danger">Specify Custom Deletion Reason</label>
                    <textarea id="delete_original_custom_reason_{{ $type }}" class="form-control" rows="2" placeholder="Explain the deletion justification..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-danger flex-grow-1 fw-bold uppercase">CONFIRM DELETE</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

{{-- E. MULTI-FORMAT LIGHTBOX OVERLAY WITH SECURE PREVIEW GATES --}}
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

@push('scripts')
{{-- Include Pusher client SDK --}}
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
<script>
    // Zoom/Lightbox Controller handlers (Image dragging & Fullscreen support)
    let currentScale = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX, startY;

    document.addEventListener('DOMContentLoaded', async () => {
        const appointmentId = "{{ $appointment->id }}";

        // Initialize cascading address selects inside details edit modal
        await initializeReviseAddress();

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
            const customInputs = container.querySelectorAll('input');
            
            if (type === 'custom') {
                container.classList.remove('d-none');
                customInputs.forEach(input => input.setAttribute('required', 'required'));
            } else {
                container.classList.add('d-none');
                customInputs.forEach(input => input.removeAttribute('required'));
            }
        });

        // Dynamic Demographics reason input name-toggling
        const reasonSelect = document.getElementById('revise_reason_select');
        const textareaWrapper = document.getElementById('revise_custom_reason_wrapper');
        const textareaEl = document.getElementById('revise_custom_reason');

        if (reasonSelect && textareaEl && textareaWrapper) {
            reasonSelect.addEventListener('change', function() {
                if (this.value === 'Others') {
                    textareaWrapper.classList.remove('d-none');
                    textareaEl.setAttribute('required', 'required');
                    textareaEl.setAttribute('name', 'reason');
                    reasonSelect.removeAttribute('name');
                    textareaEl.value = '';
                } else {
                    textareaWrapper.classList.add('d-none');
                    textareaEl.removeAttribute('required');
                    textareaEl.removeAttribute('name');
                    reasonSelect.setAttribute('name', 'reason');
                }
            });
        }

        // Services search filter within Revise Demographics Modal
        document.getElementById('revise_service_search')?.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.revise-service-item').forEach(item => {
                const labelText = item.querySelector('label').innerText.toLowerCase();
                if (labelText.includes(query)) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
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

        // Dynamic Delete Custom Workstation custom reason name-toggling
        document.querySelectorAll('[id^="delete_custom_reason_select_"]').forEach(select => {
            select.addEventListener('change', function() {
                const id = this.id.replace('delete_custom_reason_select_', '');
                const wrapper = document.getElementById(`delete_custom_custom_reason_wrapper_${id}`);
                const textarea = document.getElementById(`delete_custom_custom_reason_${id}`);
                
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

        // Dynamic Return Custom Workstation custom reason name-toggling
        document.querySelectorAll('[id^="return_custom_reason_select_"]').forEach(select => {
            select.addEventListener('change', function() {
                const id = this.id.replace('return_custom_reason_select_', '');
                const wrapper = document.getElementById(`return_custom_custom_reason_wrapper_${id}`);
                const textarea = document.getElementById(`return_custom_custom_reason_${id}`);
                
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

        // Intercept form submission to compile cascading address textual literals
        document.getElementById('revise_details_form')?.addEventListener('submit', function() {
            compileReviseAddress();
        });

        // Draggable canvas functionality for zoomed-in images in Lightbox
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
                img.style.cursor = currentScale > 1 ? 'grab' : 'default';
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

    // Cascading PSGC Address logic loaders
    async function initializeReviseAddress() {
        const savedProv = "{{ $appointment->patient_province }}";
        const savedCity = "{{ $appointment->patient_city }}";
        const savedBrgy = "{{ $appointment->patient_barangay }}";

        const provSel = document.getElementById('revise_province');
        if (!provSel) return;

        try {
            const res = await fetch('https://psgc.gitlab.io/api/provinces/');
            const data = await res.json();
            provSel.innerHTML = '<option value="">Select Province</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
            });

            if (savedProv) {
                let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === savedProv.toUpperCase());
                if (provOpt) {
                    provSel.value = provOpt.value;
                    await fetchReviseCities(provOpt.value, savedCity, savedBrgy);
                }
            }
        } catch (e) {
            console.error("Provinces fetch failed:", e);
        }
    }

    async function fetchReviseCities(provCode, savedCity = '', savedBrgy = '') {
        const citySel = document.getElementById('revise_city');
        const brgySel = document.getElementById('revise_barangay');
        if (!citySel || !brgySel) return;

        citySel.disabled = true;
        brgySel.disabled = true;
        citySel.innerHTML = '<option value="">Loading Cities...</option>';

        try {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
            const data = await res.json();
            citySel.innerHTML = '<option value="">Select City</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
            });
            citySel.disabled = false;

            if (savedCity) {
                let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === savedCity.toUpperCase());
                if (cityOpt) {
                    citySel.value = cityOpt.value;
                    await fetchReviseBarangays(cityOpt.value, savedBrgy);
                }
            }
        } catch (e) {
            console.error("Cities fetch failed:", e);
        }
    }

    async function fetchReviseBarangays(cityCode, savedBrgy = '') {
        const brgySel = document.getElementById('revise_barangay');
        if (!brgySel) return;

        brgySel.disabled = true;
        brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

        try {
            const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
            const data = await res.json();
            brgySel.innerHTML = '<option value="">Select Barangay</option>';
            data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
            });
            brgySel.disabled = false;

            if (savedBrgy) {
                let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === savedBrgy.toUpperCase());
                if (brgyOpt) {
                    brgySel.value = brgyOpt.value;
                }
            }
        } catch (e) {
            console.error("Barangays fetch failed:", e);
        }
    }

    function compileReviseAddress() {
        const brgy = document.getElementById('revise_barangay');
        const city = document.getElementById('revise_city');
        const prov = document.getElementById('revise_province');

        if (brgy && city && prov) {
            const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
            const cityName = city.options[city.selectedIndex]?.text || '';
            const provName = prov.options[prov.selectedIndex]?.text || '';

            if (brgyName && cityName && provName) {
                prov.options[prov.selectedIndex].value = provName;
                city.options[city.selectedIndex].value = cityName;
                brgy.options[brgy.selectedIndex].value = brgyName;
            }
        }
    }

    // Fullscreen multi-format lightbox handlers
    function zoomImage(amount, event) {
        if (event) event.stopPropagation(); // Block closing backdrop overlay clicks

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

    // Attach event listeners for address selects
    document.getElementById('revise_province')?.addEventListener('change', function() {
        fetchReviseCities(this.value);
    });
    document.getElementById('revise_city')?.addEventListener('change', function() {
        fetchReviseBarangays(this.value);
    });
</script>
@endpush

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
</style>
@endsection