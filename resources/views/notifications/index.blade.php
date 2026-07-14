@extends('layouts.app')

@section('content')
<div class="row justify-content-center text-start animate-page">
    <div class="col-md-10 col-lg-8">
        
        {{-- Header Section --}}
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-25 pb-3">
            <h2 class="text-neon fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                NOTIFICATIONS
            </h2>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <a href="{{ route('notifications.clearAll') }}" class="btn-custom btn-outline-accent btn-sm">
                    <i class="bi bi-check-all me-1"></i> MARK ALL AS READ
                </a>
            @endif
        </div>

        {{-- Notifications Card Container --}}
        <div class="card p-0 border-secondary overflow-hidden shadow-lg bg-card mb-4">
            <div class="list-group list-group-flush">
                @forelse($notifications as $notif)
                    <a href="{{ route('notifications.markAsRead', $notif->id) }}" 
                       class="list-group-item list-group-item-action bg-transparent border-secondary border-opacity-10 py-4 px-4 transition-all {{ $notif->read_at ? 'opacity-75' : 'bg-secondary bg-opacity-5 border-start border-4 border-accent' }}"
                       style="border-left-width: {{ $notif->read_at ? '1px' : '4px !important' }};">
                        
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="pe-3">
                                {{-- Notification Title --}}
                                <h6 class="fw-bold mb-1 {{ $notif->read_at ? 'text-main' : 'text-accent' }} uppercase small" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                    {{ $notif->data['title'] }}
                                </h6>
                                {{-- Notification Message --}}
                                <p class="text-main small mb-2" style="line-height: 1.5; font-size: 0.85rem;">
                                    {{ $notif->data['message'] }}
                                </p>
                                {{-- Relative Timestamp --}}
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock me-1"></i>{{ $notif->created_at->diffForHumans() }}
                                </small>
                            </div>
                            
                            {{-- Unread Badge Indicator --}}
                            @if(!$notif->read_at)
                                <span class="badge bg-accent text-dark rounded-pill smaller fw-bold px-2.5 py-1.5 uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                                    NEW
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    {{-- Unified Empty State Placeholder --}}
                    <div class="p-5 text-center text-muted border-secondary border-dashed bg-card d-flex flex-column align-items-center justify-content-center" style="min-height: 250px;">
                        <i class="bi bi-bell-slash text-accent display-4 d-block mb-3 opacity-75"></i>
                        <p class="small mb-0">No notifications found in your history.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Theme-compatible Pagination Controls --}}
        <div class="mt-4 d-flex justify-content-center">
            {{ $notifications->links() }}
        </div>

    </div>
</div>

<style>
    /* Theme-compatible Pagination Styling Overrides */
    .pagination {
        gap: 4px;
    }
    .pagination .page-item .page-link {
        background-color: var(--bg-card) !important;
        border-color: var(--border-color) !important;
        color: var(--text-main) !important;
        border-radius: 6px;
        padding: 8px 16px;
        font-weight: 600;
        transition: 0.2s ease;
    }
    .pagination .page-item.active .page-link {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        color: #1c232d !important;
        font-weight: 700;
    }
    .pagination .page-item .page-link:hover {
        background-color: rgba(25, 211, 140, 0.08) !important;
        border-color: var(--brand-accent) !important;
        color: var(--brand-accent) !important;
    }
    .border-secondary.border-dashed {
        border-style: dashed !important;
    }
</style>
@endsection