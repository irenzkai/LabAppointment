@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center min-vh-75 animate-page">
    <div class="col-md-10 col-lg-5 text-center">
        
        {{-- Central Search Card --}}
        <div class="card p-5 border-secondary bg-card shadow-lg mx-auto" style="max-width: 500px; background-color: var(--bg-card); color: var(--text-main);">
            
            {{-- Shield Icon Indicator --}}
            <div class="display-3 text-accent mb-3">
                <i class="bi bi-shield-lock-fill shadow-neon" style="border-radius: 50%;"></i>
            </div>
            
            <h3 class="fw-bold uppercase tracking-wider text-main mb-2" style="font-size: 1.5rem;">Result Verification</h3>
            <p class="small text-muted mb-4" style="color: var(--text-muted) !important;">
                {{-- FIXED: Updated description to reflect that raw, sequential patient Reference IDs are excluded for security --}}
                Enter the Medical Certificate ID, Laboratory Case Number, Radiology Case Number, or Digitized Archive Certificate Number below to securely verify its clinical authenticity.
            </p>

            {{-- Search Verification Form --}}
            <form action="{{ route('result.verify-search') }}" method="GET" class="m-0">
                <div class="mb-4">
                    <label class="text-secondary smaller fw-bold uppercase d-block text-start mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Verification ID / Document Code</label>
                    {{-- FIXED: Updated placeholder to match secure clinical document IDs only, excluding incremental integers --}}
                    <input type="text" name="query" class="form-control py-3 text-center fw-bold shadow-none" style="background-color: rgba(0,0,0,0.015); border: 1.5px solid var(--border-color); color: var(--text-main);" placeholder="e.g., 01261065, 2345345, MDL-0225806" required autofocus>
                </div>
                
                <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase shadow-sm">
                    <i class="bi bi-patch-check-fill me-1"></i>Verify Record
                </button>
            </form>
        </div>

    </div>
</div>

<style>
/* Glowing security icon effects */
.shadow-neon {
    box-shadow: 0 0 15px var(--brand-accent);
}

.min-vh-75 {
    min-height: 75vh;
}
</style>
@endsection