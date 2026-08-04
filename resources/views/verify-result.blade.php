@extends('layouts.app')

@section('title', 'Verified Result')

@section('content')
@php
    $res = $appointment->result;
    $components = $res->included_reports ?? ['lab'];
    
    // Dynamically map database codes to elegant clinical designations
    $fileTypes = array_map(function($type) {
        return match($type) {
            'lab' => 'Laboratory Result',
            'med_cert' => 'Medical Certificate',
            'radio' => 'Radiology Report',
            'drug' => 'Drug Test Result',
            default => strtoupper($type) . ' Worksheet'
        };
    }, $components);
    
    $fileTypeLabel = implode(', ', $fileTypes);
@endphp

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
            <div class="border border-secondary border-opacity-25 rounded-3 overflow-hidden">
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
                            <td class="fw-bold uppercase p-3" style="font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">File Type</td>
                            <td class="text-main p-3" style="font-size: 0.9rem;">{{ $fileTypeLabel }}</td>
                        </tr>
                    </tbody>
                </table>
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