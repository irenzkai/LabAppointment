@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center min-vh-75 animate-page">
    <div class="col-md-10 col-lg-8 text-center">
        
        {{-- Central Historical Archive Verification Card --}}
        <div class="card p-5 border-secondary bg-card shadow-lg mx-auto" style="max-width: 720px; background-color: var(--bg-card); color: var(--text-main);">
            
            {{-- Security Verification Header --}}
            <div class="mb-4">
                <div class="display-3 text-accent mb-2">
                    <i class="bi bi-shield-fill-check shadow-neon" style="border-radius: 50%;"></i>
                </div>
                <h3 class="fw-bold uppercase tracking-wider text-main mb-1" style="font-size: 1.5rem;">Clinical Archive Verified</h3>
                <span class="badge border border-success text-success bg-success bg-opacity-10 px-3 py-2 fw-bold uppercase rounded-pill" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <i class="bi bi-patch-check-fill me-1"></i>Verified Authentic Historical Archive
                </span>
            </div>

            <p class="small text-muted mb-4" style="color: var(--text-muted) !important;">
                Medscreen Diagnostic Laboratory confirms that the following digitized medical archive records correspond to historical physical reports on file and are fully validated in our database.
            </p>

            {{-- Patient Demographic Snapshot Table --}}
            <div class="border border-secondary border-opacity-25 rounded-3 overflow-hidden mb-5">
                <table class="table table-hover align-middle mb-0 text-start" style="color: var(--text-main);">
                    <tbody>
                        <tr>
                            <td class="fw-bold uppercase p-3" style="width: 35%; font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">Patient Name</td>
                            <td class="fw-bold text-main p-3" style="font-size: 0.9rem;">{{ strtoupper($user->name) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold uppercase p-3" style="font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">Age / Sex</td>
                            <td class="text-main p-3" style="font-size: 0.9rem;">{{ $user->birthdate ? \Carbon\Carbon::parse($user->birthdate)->age : 'N/A' }} Yrs / {{ strtoupper($user->sex ?? 'N/A') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold uppercase p-3" style="font-size: 0.75rem; color: var(--text-muted); border-right: 1px solid var(--border-color);">Archive Source</td>
                            <td class="text-main p-3" style="font-size: 0.9rem;">DIGITIZED PHYSICAL DATABASE SNAPSHOT</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Historical Timeline --}}
            <div class="text-start">
                <h6 class="text-accent smaller fw-bold uppercase tracking-wider mb-3">Digitized Historical Timeline</h6>
                
                <div class="d-flex flex-column gap-4">
                    @forelse($existingRecords as $record)
                        <div class="p-4 border border-secondary border-opacity-10 rounded bg-secondary bg-opacity-5">
                            <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-10 pb-2 mb-3">
                                <span class="fw-bold text-accent" style="font-size: 0.95rem;">
                                    <i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($record->date_of_record)->format('F d, Y') }}
                                </span>
                                <span class="small text-muted">Req: {{ strtoupper($record->requested_by) }}</span>
                            </div>

                            {{-- Procedures --}}
                            <div class="mb-3">
                                <small class="text-accent fw-bold d-block mb-2 uppercase" style="font-size: 0.65rem;">Procedures Tagged:</small>
                                <div class="d-flex flex-wrap gap-1.5">
                                    @foreach($record->procedures as $p)
                                        <span class="badge border border-secondary border-opacity-25 text-secondary uppercase px-2 py-1.5 rounded" style="font-size: 0.7rem; background-color: rgba(0,0,0,0.02);">
                                            {{ $p->procedure_name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Scans & Cert numbers --}}
                            <div>
                                <small class="text-accent fw-bold d-block mb-2 uppercase" style="font-size: 0.65rem;">Attached Certified Scans:</small>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($record->scans as $scan)
                                        <div class="d-flex justify-content-between align-items-center p-2.5 border rounded border-secondary border-opacity-10" style="background-color: var(--bg-card);">
                                            <span class="fw-bold small text-truncate pe-2" style="color: var(--text-main);">
                                                <i class="bi bi-file-earmark-medical text-accent me-1"></i>{{ $scan->label }}
                                            </span>
                                            @if(!empty($scan->certificate_no))
                                                <span class="badge border border-success text-success bg-success bg-opacity-10 font-monospace" style="font-size: 0.65rem; padding: 4px 8px;">
                                                    Cert ID: {{ $scan->certificate_no }}
                                                </span>
                                            @else
                                                <span class="text-muted small italic" style="font-size: 0.75rem;">Verified on File</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-secondary italic">
                            <i class="bi bi-folder-x d-block fs-2 mb-2"></i>
                            No digitized historical records are currently registered for this profile.
                        </div>
                    @endforelse
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