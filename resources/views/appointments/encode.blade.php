@extends('layouts.app')

@section('content')
@php
    $res = $appointment->result;
    $selectedTypes = $autoReportTypes;
    
    // Status Theme Mapping (High-Contrast Theme-Safe Colors)
    $getStatusTheme = function($status) {
        return match($status) {
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
            default => [
                'style' => 'background-color: rgba(108, 117, 125, 0.1) !important; color: var(--text-muted) !important;', 
                'border' => 'var(--border-color)', 
                'label' => 'PENDING'
            ]
        };
    };

    // Global Release Logic
    $readyToRelease = true;
    foreach($selectedTypes as $type) {
        $p = ($type == 'med_cert') ? 'med' : $type;
        if(($res->{$p . '_status'} ?? 'pending') !== 'verified') {
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
        <a href="{{ route('appointments.index') }}" class="text-secondary small text-decoration-none hover-neon"><i class="bi bi-arrow-left me-1"></i> BACK TO MASTER QUEUE</a>
    </div>

    {{-- 2. PATIENT CONTEXT BAR (Theme-Aware) --}}
    <div class="mb-5 p-4 rounded border border-secondary border-opacity-25 shadow-sm" style="background-color: var(--bg-card); color: var(--text-main);">
        <div class="row align-items-center">
            <div class="col-md-8 text-start">
                <h4 class="fw-bold mb-1" style="color: var(--text-main);">{{ strtoupper($appointment->patient_name) }}</h4>
                <div class="text-secondary smaller fw-bold uppercase">
                    {{ $appointment->patient_age }} Years Old <span class="mx-2">|</span> 
                    {{ strtoupper($appointment->patient_sex) }} <span class="mx-2">|</span> 
                    Tested: {{ $appointment->tested_at ? $appointment->tested_at->format('M d, Y') : 'Processing' }}
                </div>
                
                {{-- Tests Requested List --}}
                <div class="text-accent smaller fw-bold uppercase mt-2">
                    <i class="bi bi-flask-fill me-1"></i> Tests Requested: 
                    <span style="color: var(--text-main);">{{ $appointment->services->pluck('name')->implode(', ') }}</span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <label class="text-secondary smaller fw-bold uppercase d-block mb-1">Folder Status</label>
                <span class="badge {{ $appointment->status == 'released' ? 'bg-neon text-dark' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' }} px-3 py-2 fs-6">
                    {{ strtoupper($appointment->status) }}
                </span>
            </div>
        </div>
    </div>

    {{-- 3. MODULAR PROGRESS GRID --}}
    <div class="row g-4">
        @foreach($selectedTypes as $type)
            @php 
                $prefix = ($type == 'med_cert' ? 'med' : $type);
                $currentStatus = $res->{$prefix . '_status'} ?? 'pending';
                $theme = $getStatusTheme($currentStatus);
                
                // Audit Names (System account names)
                $encoderName = $res->{$prefix . '_v1_by_name'} ?? '---';
                $verifierName = $res->{$prefix . '_v2_by_name'} ?? '---';
                
                // Formatted Timestamps (Safely parsed)
                $encodedAt = ($res && $res->{$prefix . '_v1_at'}) ? \Carbon\Carbon::parse($res->{$prefix . '_v1_at'}) : null;
                $verifiedAt = ($res && $res->{($type == 'lab' ? 'lab_v2_at' : $prefix . '_verified_at')}) ? \Carbon\Carbon::parse($res->{($type == 'lab' ? 'lab_v2_at' : $prefix . '_verified_at')}) : null;

                // FIXED: Map route identifier correctly ('radio' -> 'workstation.radiology')
                $routeAction = match($type) {
                    'radio' => 'radiology',
                    default => $type
                };
            @endphp
            
            <div class="col-md-6">
                <div class="card p-4 shadow-sm workstation-card" style="background-color: var(--bg-card); color: var(--text-main); border-left: 4px solid {{ $theme['border'] }} !important; border-top-color: var(--border-color); border-right-color: var(--border-color); border-bottom-color: var(--border-color);">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h5 class="fw-bold mb-0 uppercase" style="color: var(--text-main);">
                                @if($type == 'lab') Laboratory Result
                                @elseif($type == 'radio') Radiology Report
                                @elseif($type == 'med_cert') Medical Certificate
                                @else Drug Test Result @endif
                            </h5>
                        </div>
                        <span class="badge px-3 py-2 uppercase small shadow-sm border border-secondary border-opacity-10" style="{{ $theme['style'] }}">
                            {{ $theme['label'] }}
                        </span>
                    </div>

                    {{-- Return Reason Alert (Specific to this form) --}}
                    @if($currentStatus == 'returned' && $res->{$prefix . '_return_reason'})
                        <div class="alert bg-danger bg-opacity-10 border-danger text-danger py-2 px-3 small mb-3">
                            <div class="fw-bold uppercase smaller mb-1" style="font-size: 0.65rem;">Correction Reason:</div>
                            <div class="italic">"{{ $res->{$prefix . '_return_reason'} }}"</div>
                        </div>
                    @endif

                    {{-- System Audit Trail (Theme-Aware Container) --}}
                    <div class="mb-4 p-3 rounded border border-secondary border-opacity-10" style="background-color: rgba(0, 0, 0, 0.02);">
                        <div class="row g-0">
                            <div class="col-6 pe-2">
                                <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Encoder</label>
                                <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;">{{ $encoderName }}</span>
                                @if($encodedAt)
                                    <span class="text-secondary smaller italic" style="font-size: 0.65rem;">{{ $encodedAt->format('h:i A') }}</span>
                                @endif
                            </div>
                            <div class="col-6 ps-3 border-start border-secondary border-opacity-25">
                                <label class="text-secondary smaller fw-bold uppercase d-block mb-1" style="font-size: 0.6rem;">System Verifier</label>
                                <span class="fw-bold d-block text-truncate" style="color: var(--text-main); font-size: 0.85rem;">{{ $verifierName }}</span>
                                @if($verifiedAt)
                                    <span class="text-secondary smaller italic" style="font-size: 0.65rem;">{{ $verifiedAt->format('h:i A') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Dynamic Workstation Navigation Link --}}
                    <div class="mt-auto">
                        <a href="{{ route('workstation.' . $routeAction, $appointment->id) }}" 
                           class="btn-custom {{ $currentStatus == 'verified' ? 'btn-outline-accent' : 'btn-accent' }} w-100 py-2 fw-bold small uppercase">
                            @if($currentStatus == 'verified') Review Data @elseif($currentStatus == 'encoded') Verify & Sign @else Open Workstation @endif
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
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
                            <form action="{{ route('appointments.status', $appointment->id) }}" method="POST" class="flex-grow-1">
                                @csrf @method('PATCH')
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
                <p class="text-secondary mb-0 smaller">The system is waiting for all required workstations to be verified before the release button is enabled.</p>
            </div>
        @endif
    </div>

</div>

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