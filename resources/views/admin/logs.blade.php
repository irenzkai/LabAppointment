@extends('layouts.app')

@section('content')
<div class="row mb-4 align-items-end text-start">
    <div class="col-md-6">
        <h2 class="text-neon fw-bold mb-0 uppercase" style="letter-spacing: 2px;">SYSTEM LOGS</h2>
        <p class="text-secondary small">Comprehensive history of all system events</p>
    </div>
    
    <div class="col-md-6 text-md-end">
    <div class="btn-group shadow-sm">
        <a href="{{ route('admin.logs') }}" class="btn btn-sm {{ !request('role') ? 'btn-neon' : 'btn-outline-secondary' }} px-3 text-white">ALL</a>
        <a href="{{ route('admin.logs', ['role' => 'admin']) }}" class="btn btn-sm {{ request('role') == 'admin' ? 'btn-neon' : 'btn-outline-secondary' }} px-3 text-white">ADMIN</a>
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
                    <th>ACTION</th>
                    <th>PATIENT / ENTITY</th>
                    <th style="width: 35%;">REASON / LOG</th>
                    <th class="pe-4 text-end">DATE & TIME</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
            <tr class="border-secondary border-opacity-10">
                <td class="ps-4 py-3">
                    {{-- SHOW THE ACTUAL NAME OF THE PERFORMER --}}
                    <div class="text-white fw-bold uppercase">{{ $log->user->name ?? 'System/Deleted' }}</div>
                    
                    {{-- SHOW THE ACTUAL ROLE OF THE PERFORMER --}}
                    @if($log->user)
                        <span class="badge border {{ $log->user->role == 'admin' ? 'border-danger text-danger' : ($log->user->role == 'staff' ? 'border-info text-info' : 'border-neon text-neon') }}" style="font-size: 0.7rem; padding: 2px 4px;">
                            {{ strtoupper($log->user->role) }}
                        </span>
                    @endif
                </td>
                
                <td>
                    @php
                        $c = 'neon';
                        if(Str::contains($log->action, 'RELEASE')) $c = 'neon';
                        if(Str::contains($log->action, 'PREVIEW')) $c = 'info';
                        if(Str::contains($log->action, 'DELETE')) $c = 'danger';
                        if(Str::contains($log->action, 'RESUBMITTED')) $c = 'warning';
                        if(Str::contains($log->action, 'DOWNLOAD')) $c = 'warning';
                    @endphp
                    <span class="badge border border-{{$c}} text-{{$c}} fw-bold small uppercase px-2 py-1">
                        {{ $log->action }}
                    </span>
                </td>
                
                <td class="small fw-bold text-white uppercase">{{ $log->patient_name }}</td>
                
                <td class="text-white small italic">"{{ $log->reason }}"</td>
                
                <td class="text-end pe-4 local-time-trigger" data-utc="{{ $log->created_at->toIso8601String() }}">
                    <div class="text-white small fw-bold">{{ $log->created_at->format('M d, Y') }}</div>
                    <div class="text-white" style="font-size: 0.8rem;">{{ $log->created_at->format('h:i A') }}</div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center py-5 text-secondary italic">No logs found for this role.</td></tr>
            @endforelse
        </tbody>
        </table>
    </div>
</div>
<div class="mt-4 d-flex justify-content-center">
    {{ $logs->links() }}
</div>
@endsection