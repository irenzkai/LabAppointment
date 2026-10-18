@extends('layouts.app')

@section('content')
<div class="row g-4 text-start mt-2">
    {{-- Left Column: Active Legal & Information Sidebar --}}
    <div class="col-12 col-lg-3">
        <div class="card p-3 shadow-sm sticky-top" style="top: 100px; background-color: var(--bg-card); border: 1.5px solid var(--border-color) !important; border-radius: 12px;">
            <h6 class="text-muted fw-800 uppercase tracking-widest mb-3 fs-x-small" style="font-size: 0.68rem; letter-spacing: 1px; color: var(--text-muted) !important;">
                Information Center
            </h6>
            <div class="list-group list-group-flush">
                <a href="{{ Route::has('legal.about') ? route('legal.about') : url('/legal/about') }}" 
                   class="list-group-item list-group-item-action py-2.5 px-3 border-0 small rounded-2 mb-1 bg-transparent {{ request()->routeIs('legal.about') || request()->is('legal/about*') ? 'text-accent fw-bold active-legal-link' : 'text-main' }}"
                   style="color: {{ request()->routeIs('legal.about') || request()->is('legal/about*') ? 'var(--brand-accent)' : 'var(--text-main)' }} !important;">
                    <i class="bi bi-info-circle me-2 text-accent"></i> About & FAQ
                </a>
                <a href="{{ route('legal.privacy') }}" 
                   class="list-group-item list-group-item-action py-2.5 px-3 border-0 small rounded-2 mb-1 bg-transparent {{ request()->routeIs('legal.privacy') ? 'text-accent fw-bold active-legal-link' : 'text-main' }}"
                   style="color: {{ request()->routeIs('legal.privacy') ? 'var(--brand-accent)' : 'var(--text-main)' }} !important;">
                    <i class="bi bi-shield-lock me-2 text-accent"></i> Privacy Policy
                </a>
                <a href="{{ route('legal.terms') }}" 
                   class="list-group-item list-group-item-action py-2.5 px-3 border-0 small rounded-2 mb-1 bg-transparent {{ request()->routeIs('legal.terms') ? 'text-accent fw-bold active-legal-link' : 'text-main' }}"
                   style="color: {{ request()->routeIs('legal.terms') ? 'var(--brand-accent)' : 'var(--text-main)' }} !important;">
                    <i class="bi bi-file-earmark-text me-2 text-accent"></i> Terms of Service
                </a>
                <a href="{{ route('legal.dpa') }}" 
                   class="list-group-item list-group-item-action py-2.5 px-3 border-0 small rounded-2 mb-1 bg-transparent {{ request()->routeIs('legal.dpa') ? 'text-accent fw-bold active-legal-link' : 'text-main' }}"
                   style="color: {{ request()->routeIs('legal.dpa') ? 'var(--brand-accent)' : 'var(--text-main)' }} !important;">
                    <i class="bi bi-people me-2 text-accent"></i> Data Privacy Act
                </a>
                <a href="{{ route('legal.cookies') }}" 
                   class="list-group-item list-group-item-action py-2.5 px-3 border-0 small rounded-2 mb-1 bg-transparent {{ request()->routeIs('legal.cookies') ? 'text-accent fw-bold active-legal-link' : 'text-main' }}"
                   style="color: {{ request()->routeIs('legal.cookies') ? 'var(--brand-accent)' : 'var(--text-main)' }} !important;">
                    <i class="bi bi-cookie me-2 text-accent"></i> Cookie Settings
                </a>
            </div>
        </div>
    </div>

    {{-- Right Column: Main Legal Content Card --}}
    <div class="col-12 col-lg-9 animate-page">
        <div class="card p-4 p-md-5 shadow-sm" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color) !important; border-radius: 14px;">
            {{-- Academic Capstone Protection Banner --}}
            <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 d-flex align-items-center mb-5" role="alert" style="border-left: 4px solid #ffc107 !important;">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-warning"></i>
                <div>
                    <strong class="d-block uppercase fs-x-small text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Academic Prototype Disclaimer</strong>
                    <span class="small text-main" style="color: var(--text-main) !important; font-size: 0.8rem; line-height: 1.45;">
                        This document and system are prepared solely for the assessment of an academic IT/CS Capstone Project. It is mock documentation and does not constitute a legally binding service contract for real patient operations.
                    </span>
                </div>
            </div>

            @yield('legal-content')
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.active-legal-link {
    background-color: rgba(25, 211, 140, 0.08) !important;
    border-left: 3px solid var(--brand-accent) !important;
}

.list-group-item-action:hover {
    background-color: rgba(25, 211, 140, 0.05) !important;
    color: var(--brand-accent) !important;
}
</style>
@endpush