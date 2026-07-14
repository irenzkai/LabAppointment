@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center min-vh-75 animate-page">
    <div class="col-md-10 col-lg-7 text-center">
        
        {{-- Central Clinical Verification Card --}}
        <div class="card p-5 border-secondary bg-card shadow-lg mx-auto" style="max-width: 620px; background-color: var(--bg-card); color: var(--text-main);">
            
            {{-- Security Verification Header --}}
            <div class="mb-4">
                <div class="display-3 text-accent mb-2">
                    <i class="bi bi-shield-fill-check shadow-neon" style="border-radius: 50%;"></i>
                </div>
                <h3 class="fw-bold uppercase tracking-wider text-main mb-1" style="font-size: 1.5rem;">Clinical Record Verified</h3>
                <span class="badge border border-success text-success bg-success bg-opacity-10 px-3 py-2 fw-bold uppercase rounded-pill" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <i class="bi bi-patch-check-fill me-1"></i>Verified Authentic Database Entry
                </span>
            </div>

            <p class="small text-muted mb-4" style="color: var(--text-muted) !important;">
                Medscreen Diagnostic Laboratory confirms that the following clinical examination record matches our master database entry and has been validated by authorized clinical personnel.
            </p>

            {{-- Patient Demographic Snapshot Table --}}
            {{-- FIXED: Removed blocky bg-secondary gray backgrounds to restore a clean, high-contrast bordered layout --}}
            <div class="border border-secondary border-opacity-25 rounded-3 overflow-hidden mb-4">
                <table class="table table-hover align-middle mb-0 text-start" style="color: var(--text-main);">
                    <tbody>
                        <tr>
                            <td class="fw-bold uppercase p-3" style="width: 35%; font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">Patient Name</td>
                            <td class="fw-bold text-main p-3" style="font-size: 0.9rem;">{{ strtoupper($appointment->patient_name) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold uppercase p-3" style="font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">Reference ID</td>
                            <td class="font-monospace text-accent p-3" style="font-size: 0.9rem;">#{{ $appointment->id }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold uppercase p-3" style="font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">Date of Exam</td>
                            <td class="text-main p-3" style="font-size: 0.9rem;">{{ \Carbon\Carbon::parse($appointment->tested_at ?? $appointment->appointment_date)->format('F d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold uppercase p-3" style="font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">Account Type</td>
                            <td class="text-main p-3" style="font-size: 0.9rem;">
                                @if($appointment->batch_id)
                                    BULK BATCH ({{ strtoupper($appointment->organization_name) }})
                                @elseif($appointment->dependent_id)
                                    FAMILY DEPENDENT ({{ strtoupper($appointment->dependent->relationship ?? 'DEPENDENT') }})
                                @else
                                    PERSONAL ACCOUNT
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Certified Workstation Verification Steps --}}
            <div class="text-start">
                <h6 class="text-accent smaller fw-bold uppercase tracking-wider mb-3">Workstation Verification States</h6>
                
                @php
                    $components = $res->included_reports ?? ['lab'];
                @endphp

                <div class="d-flex flex-column gap-2.5">
                    @foreach($components as $type)
                        @php
                            $prefix = ($type == 'med_cert') ? 'med' : $type;
                            $statusField = $prefix . '_status';
                            $currentStatus = $res->$statusField ?? 'pending';

                            $isVerifiedComponent = ($currentStatus === 'verified');
                            $badgeClass = $isVerifiedComponent 
                                ? 'bg-success bg-opacity-10 text-success border-success' 
                                : 'bg-warning bg-opacity-10 text-warning border-warning';

                            $labelName = match($type) {
                                'lab' => 'Laboratory Result Findings',
                                'med_cert' => 'Medical Certificate Clearance',
                                'radio' => 'Radiologic Report Findings',
                                'drug' => 'Drug Test Screening Result',
                                default => strtoupper($type) . ' Worksheet'
                            };
                        @endphp

                        <div class="d-flex justify-content-between align-items-center p-3 border border-secondary border-opacity-10 rounded" style="background-color: rgba(0,0,0,0.015);">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $isVerifiedComponent ? 'bi-check-circle-fill text-success' : 'bi-hourglass-split text-warning' }} fs-5"></i>
                                <span class="fw-bold text-main small">{{ $labelName }}</span>
                            </div>
                            <span class="badge border {{ $badgeClass }} uppercase font-monospace" style="font-size: 0.65rem;">
                                {{ $isVerifiedComponent ? 'Verified' : strtoupper($currentStatus) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Final Clinical Folder Status --}}
            <div class="mt-4 pt-4 border-top border-secondary border-opacity-10">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-secondary small fw-bold uppercase">Clinical Folder Status:</span>
                    <span class="badge {{ $appointment->status === 'released' ? 'bg-neon text-dark' : 'bg-secondary bg-opacity-10 text-secondary' }} px-3 py-2 uppercase fs-6 shadow-sm border border-secondary border-opacity-25">
                        {{ strtoupper($appointment->status) }}
                    </span>
                </div>
            </div>

        </div>

    </div>
</div>

<style>
/* Verification indicator glowing effects */
.shadow-neon {
    box-shadow: 0 0 15px var(--brand-accent);
}

.min-vh-75 {
    min-height: 75vh;
}

/* Metadata snap details adjustments */
.table th, .table td {
    border-color: rgba(255, 255, 255, 0.05) !important;
}
[data-bs-theme="light"] .table th, [data-bs-theme="light"] .table td {
    border-color: rgba(0, 0, 0, 0.05) !important;
}
</style>
@endsection