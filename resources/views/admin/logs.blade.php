@extends('layouts.app')

@section('content')
<style>
    /* Custom pagination styling to match the neon theme */
    .pagination { margin-bottom: 0; gap: 5px; }
    .page-item .page-link { 
        background-color: var(--bg-card) !important; 
        border-color: var(--border-color) !important; 
        color: var(--text-main) !important; 
        padding: 0.5rem 1rem;
        border-radius: 6px;
    }
    .page-item.active .page-link { 
        background-color: var(--brand-accent) !important; 
        border-color: var(--brand-accent) !important; 
        color: #1c232d !important; 
        font-weight: bold;
    }
    .page-item.disabled .page-link { 
        background-color: var(--bg-card) !important; 
        opacity: 0.5; 
        color: var(--text-muted) !important; 
    }
    .page-link:hover { 
        background-color: rgba(25, 211, 140, 0.08) !important; 
        color: var(--brand-accent) !important; 
    }
</style>

<div class="row mb-4 align-items-end text-start animate-page">
    <div class="col-12 mb-2">
        <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter" style="font-size: 1.85rem; letter-spacing: 1px;">System Logs</h2>
        <p class="text-secondary small mb-0">Comprehensive history of all internal activities, clinical verifications, and patient handshakes.</p>
    </div>
</div>

{{-- Search and Filter Controls Panel --}}
<div class="row g-3 mb-4 align-items-center text-start animate-page">
    <div class="col-md-5">
        {{-- Live search input --}}
        <div class="input-group input-group-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden">
            <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card); border-right: none;">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="logSearch" class="form-control border-0 shadow-none py-2" style="background-color: var(--bg-card); color: var(--text-main);" placeholder="Search performer, patient, action, or logs...">
        </div>
    </div>
    
    <div class="col-md-7 text-md-end">
        {{-- High-contrast, theme-appropriate filter badges --}}
        <div class="d-inline-flex gap-1.5 flex-wrap">
            <a href="{{ route('admin.logs') }}" class="btn-custom {{ !request('role') ? 'btn-accent' : 'btn-outline-accent' }} btn-sm uppercase px-3 py-1.5 fw-bold">ALL</a>
            <a href="{{ route('admin.logs', ['role' => 'admin']) }}" class="btn-custom {{ request('role') == 'admin' ? 'btn-accent' : 'btn-outline-accent' }} btn-sm uppercase px-3 py-1.5 fw-bold">ADMIN</a>
            <a href="{{ route('admin.logs', ['role' => 'lab_tech']) }}" class="btn-custom {{ request('role') == 'lab_tech' ? 'btn-accent' : 'btn-outline-accent' }} btn-sm uppercase px-3 py-1.5 fw-bold">LAB TECH</a>
            <a href="{{ route('admin.logs', ['role' => 'staff']) }}" class="btn-custom {{ request('role') == 'staff' ? 'btn-accent' : 'btn-outline-accent' }} btn-sm uppercase px-3 py-1.5 fw-bold">STAFF</a>
            <a href="{{ route('admin.logs', ['role' => 'user']) }}" class="btn-custom {{ request('role') == 'user' ? 'btn-accent' : 'btn-outline-accent' }} btn-sm uppercase px-3 py-1.5 fw-bold">PATIENTS</a>
        </div>
    </div>
</div>

{{-- Main Logs Table Card --}}
<div class="card p-0 border-secondary overflow-hidden shadow-lg animate-page">
    <div class="table-responsive">
        {{-- Removed generic Bootstrap 'table-dark' and replaced with dynamic theme variable mappings --}}
        <table class="table table-hover mb-0 align-middle" style="color: var(--text-main);">
            <thead class="small uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                <tr>
                    <th class="ps-4 py-3" style="width: 20%;">PERFORMER</th>
                    <th style="width: 15%;">ACTION TYPE</th>
                    <th style="width: 20%;">PATIENT / ENTITY</th>
                    <th style="width: 30%;">REASON / AUDIT LOG</th>
                    <th class="pe-4 text-end" style="width: 15%;">DATE & TIME</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr class="border-secondary border-opacity-10">
                        <td class="ps-4 py-3">
                            {{-- Changed hardcoded 'text-white' to match active theme variable --}}
                            <div class="fw-bold uppercase" style="color: var(--text-main);">{{ $log->user->name ?? 'System/Deleted' }}</div>
                            @if($log->user)
                                @php
                                // Role color mapping with light-mode contrast fallback safety
                                $roleClass = match($log->user->role) {
                                    'admin' => 'border-danger text-danger bg-danger bg-opacity-10',
                                    'lab_tech' => 'border-warning text-warning bg-warning bg-opacity-10',
                                    'staff' => 'border-info text-info bg-info bg-opacity-10',
                                    default => 'border-secondary text-secondary bg-secondary bg-opacity-10'
                                };
                                @endphp
                                <span class="badge border {{ $roleClass }} mt-1" style="font-size: 0.65rem; padding: 2px 4px;">
                                    {{ strtoupper(str_replace('_', ' ', $log->user->role)) }}
                                </span>
                            @endif
                        </td>
                        
                        <td>
                            @php
                            $action = $log->action;
                            
                            // FIXED: Explicitly map all action types to standard high-contrast Bootstrap color classes
                            $c = match(true) {
                                Str::contains($action, 'VERIFIED') => 'success',
                                Str::contains($action, 'ENCODED') => 'info',
                                Str::contains($action, 'TESTED') => 'info',
                                Str::contains($action, 'BOOKED') => 'success',
                                Str::contains($action, 'RESUBMIT') => 'warning',
                                Str::contains($action, 'RETURNED') => 'danger',
                                Str::contains($action, 'ACCESS') => 'info',
                                Str::contains($action, 'ROLE') => 'warning',
                                Str::contains($action, 'STATUS') => 'warning',
                                Str::contains($action, 'DELET') => 'danger',
                                Str::contains($action, 'REGISTER') => 'success',
                                default => 'secondary'
                            };
                            @endphp
                            <span class="badge border border-{{$c}} text-{{$c}} fw-bold small uppercase px-2 py-1">
                                {{ $action }}
                            </span>
                        </td>
                        
                        {{-- Switched text-white to responsive text-main --}}
                        <td class="small fw-bold uppercase" style="color: var(--text-main);">{{ $log->patient_name }}</td>
                        
                        {{-- Switched text-white to responsive text-main --}}
                        <td class="small italic" style="word-break: break-word; color: var(--text-main);">
                            "{{ $log->reason }}"
                        </td>
                        
                        {{-- Removed static text-white constraints on timestamps --}}
                        <td class="text-end pe-4 local-time-trigger" data-utc="{{ $log->created_at->toIso8601String() }}">
                            <div class="small fw-bold" style="color: var(--text-main);">{{ $log->created_at->format('M d, Y') }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $log->created_at->format('h:i A') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr id="no-logs-row">
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

{{-- Pagination Footer --}}
<div class="mt-4 d-flex flex-column align-items-center animate-page">
    <div class="text-secondary smaller mb-3">
        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
    </div>
    {{ $logs->links() }}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const logSearch = document.getElementById('logSearch');
    if (logSearch) {
        // High-speed, client-side, full-text real-time search
        logSearch.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                // Keep the 'no logs' default empty-state row untouched
                if (row.id === 'no-logs-row') return;

                const rowText = row.innerText.toLowerCase();
                if (rowText.includes(query)) {
                    row.classList.remove('d-none');
                } else {
                    row.classList.add('d-none');
                }
            });
        });
    }
});
</script>
@endpush