@extends('layouts.app')

@section('content')
<div class="row g-4 text-start mt-2">
    {{-- Left Column: Active Legal Sidebar --}}
    <div class="col-lg-3">
        <div class="card p-3 border-light shadow-sm sticky-top" style="top: 100px;">
            <h6 class="text-muted fw-800 uppercase tracking-widest mb-3 fs-x-small">Legal Center</h6>
            <div class="list-group list-group-flush">
                <a href="{{ route('legal.privacy') }}" class="list-group-item py-2 border-0 small {{ request()->routeIs('legal.privacy') ? 'text-accent fw-bold' : 'text-main' }}">
                    <i class="bi bi-shield-lock me-2"></i> Privacy Policy
                </a>
                <a href="{{ route('legal.terms') }}" class="list-group-item py-2 border-0 small {{ request()->routeIs('legal.terms') ? 'text-accent fw-bold' : 'text-main' }}">
                    <i class="bi bi-file-earmark-text me-2"></i> Terms of Service
                </a>
                <a href="{{ route('legal.dpa') }}" class="list-group-item py-2 border-0 small {{ request()->routeIs('legal.dpa') ? 'text-accent fw-bold' : 'text-main' }}">
                    <i class="bi bi-people me-2"></i> Data Privacy Act
                </a>
                <a href="{{ route('legal.cookies') }}" class="list-group-item py-2 border-0 small {{ request()->routeIs('legal.cookies') ? 'text-accent fw-bold' : 'text-main' }}">
                    <i class="bi bi-cookie me-2"></i> Cookie Settings
                </a>
            </div>
        </div>
    </div>

    {{-- Right Column: Main Legal Content Card --}}
    <div class="col-lg-9 animate-page">
        <div class="card p-4 p-md-5 border-light shadow-sm">
            {{-- Academic Capstone Protection Banner --}}
            <div class="alert alert-clinical border-warning bg-warning bg-opacity-10 d-flex align-items-center mb-5" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-warning"></i>
                <div>
                    <strong class="d-block uppercase fs-x-small text-warning">Academic Prototype Disclaimer</strong>
                    <span class="small text-main">This document is prepared solely for the assessment of an academic IT/CS Capstone Project. It is mock documentation and does not constitute a legally binding service contract for real patient operations.</span>
                </div>
            </div>

            @yield('legal-content')
        </div>
    </div>
</div>
@endsection