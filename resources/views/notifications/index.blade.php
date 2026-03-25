@extends('layouts.app')

@section('content')
<div class="row justify-content-center text-start">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-neon fw-bold mb-0 uppercase" style="letter-spacing: 2px;">NOTIFICATIONS</h2>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <a href="{{ route('notifications.clearAll') }}" class="btn-custom btn-outline-neon btn-sm">MARK ALL AS READ</a>
            @endif
        </div>

        <div class="card bg-black border-secondary shadow-lg">
            <div class="list-group list-group-flush">
                @forelse($notifications as $notif)
                    <a href="{{ route('notifications.markAsRead', $notif->id) }}" 
                       class="list-group-item list-group-item-action bg-black border-secondary py-4 px-4 {{ $notif->read_at ? 'opacity-50' : 'border-start border-neon' }}" 
                       style="border-left-width: 4px !important;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="pe-3">
                                <h6 class="fw-bold mb-1 {{ $notif->read_at ? 'text-white' : 'text-neon' }} uppercase small">{{ $notif->data['title'] }}</h6>
                                <p class="text-white small mb-2" style="line-height: 1.4;">{{ $notif->data['message'] }}</p>
                                <small class="text-secondary">{{ $notif->created_at->diffForHumans() }}</small>
                            </div>
                            @if(!$notif->read_at)
                                <span class="badge bg-neon text-black rounded-pill smaller fw-bold">NEW</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-5 text-center text-secondary italic">
                        <i class="bi bi-bell-slash fs-1 d-block mb-3 opacity-25"></i>
                        No notifications found in your history.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection