@extends('layouts.app')

@section('content')
<style>
    /* Custom pagination styling to match your neon theme */
    .pagination { margin-bottom: 0; gap: 5px; }
    .page-item .page-link { 
        background-color: #000 !important; 
        border-color: #333 !important; 
        color: #fff !important; 
        padding: 0.5rem 1rem;
    }
    .page-item.active .page-link { 
        background-color: var(--neon) !important; 
        border-color: var(--neon) !important; 
        color: #000 !important; 
        font-weight: bold;
    }
    .page-item.disabled .page-link { background-color: #111 !important; color: #444 !important; }
    .page-link:hover { background-color: #222 !important; color: var(--neon) !important; }
</style>

<div class="row mb-4 align-items-end text-start">
    <div class="col-md-5">
        <h2 class="text-neon fw-bold mb-0 uppercase" style="letter-spacing: 2px;">SYSTEM LOGS</h2>
        <p class="text-secondary small">Comprehensive history of all internal activities</p>
    </div>
    
    <div class="col-md-7 text-md-end">
        <div class="btn-group shadow-sm">
            <a href="{{ route('admin.logs') }}" class="btn btn-sm {{ !request('role') ? 'btn-neon' : 'btn-outline-secondary' }} px-3 text-white">ALL</a>
            <a href="{{ route('admin.logs', ['role' => 'admin']) }}" class="btn btn-sm {{ request('role') == 'admin' ? 'btn-neon' : 'btn-outline-secondary' }} px-3 text-white">ADMIN</a>
            <a href="{{ route('admin.logs', ['role' => 'lab_tech']) }}" class="btn btn-sm {{ request('role') == 'lab_tech' ? 'btn-neon' : 'btn-outline-secondary' }} px-3 text-white">LAB TECH</a>
            <a href="{{ route('admin.logs', ['role' => 'staff']) }}" class="btn btn-sm {{ request('role') == 'staff' ? 'btn-neon' : 'btn-outline-secondary' }} px-3 text-white">STAFF</a>
            <a href="{{ route('admin.logs', ['role' => 'user']) }}" class="btn btn-sm {{ request('role') == 'user' ? 'btn-neon' : 'btn-outline-secondary' }} px-3 text-white">PATIENTS</a>
        </div>
    </div>
</div>

<div class="card p-0 border-secondary overflow-hidden shadow-lg">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="bg-black text-secondary small uppercase">
                <tr>
                    <th class="ps-4">PERFORMER</th>
                    <th>ACTION TYPE</th>
                    <th>PATIENT / ENTITY</th>
                    <th style="width: 35%;">REASON / AUDIT LOG</th>
                    <th class="pe-4 text-end">DATE & TIME</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
            <tr class="border-secondary border-opacity-10">
                <td class="ps-4 py-3">
                    <div class="text-white fw-bold uppercase">{{ $log->user->name ?? 'System/Deleted' }}</div>
                    @if($log->user)
                        @php
                            // Role color mapping
                            $roleClass = match($log->user->role) {
                                'admin' => 'border-danger text-danger',
                                'lab_tech' => 'border-warning text-warning', // Orange for tech
                                'staff' => 'border-info text-info',          // Blue for staff
                                default => 'border-neon text-neon'           // Green for patient
                            };
                        @endphp
                        <span class="badge border {{ $roleClass }}" style="font-size: 0.65rem; padding: 2px 4px;">
                            {{ strtoupper(str_replace('_', ' ', $log->user->role)) }}
                        </span>
                    @endif
                </td>
                
                <td>
                    @php
                        $c = 'neon';
                        $action = $log->action;
                        if(Str::contains($action, 'ACCESS')) $c = 'info';
                        if(Str::contains($action, 'DELETE')) $c = 'danger';
                        if(Str::contains($action, 'GRANTED')) $c = 'neon';
                        if(Str::contains($action, 'REQUEST')) $c = 'warning';
                    @endphp
                    <span class="badge border border-{{$c}} text-{{$c}} fw-bold small uppercase px-2 py-1">
                        {{ $action }}
                    </span>
                </td>
                
                <td class="small fw-bold text-white uppercase">{{ $log->patient_name }}</td>
                
                <td class="text-white small italic" style="word-break: break-word;">
                    "{{ $log->reason }}"
                </td>
                
                <td class="text-end pe-4 local-time-trigger" data-utc="{{ $log->created_at->toIso8601String() }}">
                    <div class="text-white small fw-bold">{{ $log->created_at->format('M d, Y') }}</div>
                    <div class="text-white" style="font-size: 0.8rem;">{{ $log->created_at->format('h:i A') }}</div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-5 text-secondary italic">
                    <i class="bi bi-folder-x d-block fs-2 mb-2"></i>
                    No activities found for this role filter.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Fixed Pagination Section --}}
<div class="mt-4 d-flex flex-column align-items-center">
    <div class="text-secondary smaller mb-3">
        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
    </div>
    {{ $logs->links() }}
</div>
@endsection