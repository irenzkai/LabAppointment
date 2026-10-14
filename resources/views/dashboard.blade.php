@extends('layouts.app')
@section('title', 'Main Menu')

@section('content')
<div class="container-fluid text-start animate-page py-2" id="dashboard-root">
    {{-- 1. UNIFORM HEADER SECTION FOR ALL USERS --}}
    <div class="row mb-4 align-items-center g-3">
        <div class="col-12 col-md-8">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2 border border-accent border-opacity-25" style="background-color: rgba(25, 211, 140, 0.08);">
                <i class="bi bi-shield-check text-accent fs-6"></i>
                <span class="text-accent fw-bold uppercase tracking-wider" style="font-size: 0.7rem; letter-spacing: 0.5px;">Patient Care Portal</span>
            </div>
            <h1 class="text-main fw-bold mb-1 tracking-tight" style="font-size: clamp(1.5rem, 3vw, 2.2rem);">
                Welcome back, <span class="text-accent" style="color: var(--brand-accent) !important;">{{ Auth::user()->first_name }}</span>
            </h1>
            <p class="text-muted mb-0 small" style="color: var(--text-muted) !important;">
                Manage diagnostic bookings, track appointment workflow statuses, and access your clinical history.
            </p>
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <div class="card py-2.5 px-3 border d-inline-flex flex-row align-items-center justify-content-between gap-3 shadow-sm rounded-3" style="background-color: var(--bg-card); border-color: var(--border-color) !important;">
                <div class="text-start">
                    <small class="text-muted d-block fw-bold uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ now()->format('l, j F Y') }}</small>
                    <span class="badge border border-accent text-accent uppercase" style="font-size: 0.6rem; color: var(--brand-accent) !important; border-color: var(--brand-accent) !important; background-color: rgba(25, 211, 140, 0.08);">LIVE TIME</span>
                </div>
                <div class="text-main fs-4 fw-bold font-monospace ps-2 border-start" id="live-clock" style="border-color: var(--border-color) !important; color: var(--text-main) !important;">
                    00:00:00
                </div>
            </div>
        </div>
    </div>

    {{-- 2. QUICK ACTION CARDS --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: New Booking --}}
        <div class="col-12 col-md-4">
            <div class="card p-4 h-100 text-center shadow-sm d-flex flex-column justify-content-between action-gateway-card border" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 14px;">
                <div>
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; background-color: rgba(25, 211, 140, 0.1); border: 1.5px solid var(--brand-accent);">
                        <i class="bi bi-calendar-plus-fill fs-3 text-accent" style="color: var(--brand-accent) !important;"></i>
                    </div>
                    <h5 class="fw-bold mb-1" style="color: var(--text-main) !important;">New Booking</h5>
                    <p class="text-muted small mb-4" style="color: var(--text-muted) !important;">Schedule clinical laboratory tests and choose available clinic visit slots.</p>
                </div>
                <a href="{{ route('appointments.create') }}" class="btn-custom btn-accent w-100 py-2.5 mt-auto text-decoration-none shadow-sm fw-bold uppercase" style="border-radius: 8px;">
                    <i class="bi bi-plus-lg me-1.5"></i> Book Appointment
                </a>
            </div>
        </div>

        {{-- Card 2: Result Archive --}}
        <div class="col-12 col-md-4">
            <div class="card p-4 h-100 text-center shadow-sm d-flex flex-column justify-content-between action-gateway-card border" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 14px;">
                <div>
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; background-color: rgba(13, 202, 240, 0.1); border: 1.5px solid #0dcaf0;">
                        <i class="bi bi-file-earmark-medical-fill fs-3 text-info"></i>
                    </div>
                    <h5 class="fw-bold mb-1" style="color: var(--text-main) !important;">Result Archive</h5>
                    <p class="text-muted small mb-4" style="color: var(--text-muted) !important;">Securely preview, verify, or download your official digitized medical records.</p>
                </div>
                <a href="{{ route('patient.history') }}" class="btn-custom btn-outline-accent w-100 py-2.5 mt-auto text-decoration-none fw-bold uppercase" style="border-radius: 8px;">
                    <i class="bi bi-folder2-open me-1.5"></i> Access Records
                </a>
            </div>
        </div>

        {{-- Card 3: Family Dependents --}}
        <div class="col-12 col-md-4">
            <div class="card p-4 h-100 text-center shadow-sm d-flex flex-column justify-content-between action-gateway-card border" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 14px;">
                <div>
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; background-color: rgba(255, 193, 7, 0.1); border: 1.5px solid #ffc107;">
                        <i class="bi bi-people-fill fs-3 text-warning"></i>
                    </div>
                    <h5 class="fw-bold mb-1" style="color: var(--text-main) !important;">Dependent Records</h5>
                    <p class="text-muted small mb-4" style="color: var(--text-muted) !important;">Register and manage account details and bookings for your minor family members.</p>
                </div>
                <a href="{{ route('profile.edit') }}#tab-dependents" class="btn-custom btn-outline-accent w-100 py-2.5 mt-auto text-decoration-none fw-bold uppercase" style="border-radius: 8px;">
                    <i class="bi bi-person-gear me-1.5"></i> Manage Dependents
                </a>
            </div>
        </div>
    </div>

    {{-- 3. MAIN ACTION GRID --}}
    <div class="row g-4">
        {{-- LEFT PANEL: Recent Inquiries (Dynamically Updating Container) --}}
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border overflow-hidden h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 14px;">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center border-bottom" style="background-color: var(--brand-dark); border-color: rgba(255, 255, 255, 0.08) !important;">
                    <h6 class="mb-0 small uppercase fw-bold tracking-wider text-white d-flex align-items-center">
                        <i class="bi bi-activity text-accent me-2 fs-5" style="color: var(--brand-accent) !important;"></i>
                        <span>My Recent Inquiries</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <span id="live-sync-indicator" class="badge bg-secondary bg-opacity-25 text-white-50 border border-secondary border-opacity-25" style="font-size: 0.65rem;">
                            <i class="bi bi-broadcast text-accent me-1"></i>LIVE SYNC
                        </span>
                        <a href="{{ route('appointments.index') }}" class="text-accent small text-decoration-none fw-bold ms-2 hover-accent" style="color: var(--brand-accent) !important; font-size: 0.75rem;">
                            VIEW ALL &rarr;
                        </a>
                    </div>
                </div>

                <div class="card-body p-0" id="recent-inquiries-container">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="color: var(--text-main);">
                            <thead class="small uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px; background-color: rgba(0, 0, 0, 0.03); border-bottom: 1.5px solid var(--border-color);">
                                <tr>
                                    <th class="ps-4 py-3" style="color: var(--text-muted) !important;">Patient / Entity</th>
                                    <th style="color: var(--text-muted) !important;">Examinations</th>
                                    <th style="color: var(--text-muted) !important;">Schedule</th>
                                    <th class="text-end pe-4" style="color: var(--text-muted) !important;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="recent-inquiries-body">
                                @forelse($recentAppointments as $app)
                                    @php
                                        $isExpired = $app->isExpired();
                                        $statusColor = $isExpired ? 'danger' : match($app->status) {
                                            'pending' => 'warning',
                                            'approved' => 'info',
                                            'tested' => 'info',
                                            'encoded' => 'info',
                                            'released' => 'accent',
                                            'returned' => 'danger',
                                            'retest' => 'danger',
                                            'canceled' => 'danger',
                                            default => 'secondary'
                                        };
                                        $statusLabel = $isExpired ? 'EXPIRED' : strtoupper($app->status);

                                        $isMaker = (Auth::id() == $app->user_id);
                                        $targetRoute = ($app->batch_id && !$isMaker)
                                            ? route('patient.history', ['id' => $app->id])
                                            : route('appointments.index', ['id' => $app->id]);
                                    @endphp
                                    <tr class="border-bottom align-middle appointment-row-hover" style="cursor: pointer; border-color: var(--border-color) !important;" onclick="window.location.href='{{ $targetRoute }}'">
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="fw-bold small" style="color: var(--text-main) !important;">
                                                    {{ strtoupper($app->patient_name) }}
                                                </span>
                                                @if($app->batch_id)
                                                    <span class="badge border border-secondary border-opacity-25 px-1.5 py-0.5 rounded uppercase" style="font-size: 0.6rem; color: var(--text-muted) !important; background-color: rgba(108, 117, 125, 0.1);">BULK</span>
                                                @elseif($app->dependent_id)
                                                    <span class="badge border border-secondary border-opacity-25 px-1.5 py-0.5 rounded uppercase" style="font-size: 0.6rem; color: var(--text-muted) !important; background-color: rgba(108, 117, 125, 0.1);">DEPENDENT</span>
                                                @endif
                                            </div>
                                            <div class="smaller mt-0.5" style="font-size: 0.68rem; color: var(--text-muted) !important;">
                                                Ref: #{{ $app->id }}
                                                @if($app->batch_id)
                                                    <span class="mx-1">&bull;</span>
                                                    <span class="font-monospace">BATCH #{{ $app->batch_id }}</span>
                                                    @if($app->organization_name && $app->patient_name && $app->patient_name !== $app->organization_name)
                                                        <span class="mx-1">&bull;</span>
                                                        <span>{{ strtoupper($app->organization_name) }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                        <td class="small" style="color: var(--text-main) !important;">
                                            {{ Str::limit($app->services->pluck('name')->implode(', '), 38) }}
                                        </td>
                                        <td class="small">
                                            <div class="fw-semibold" style="color: var(--text-main) !important;">
                                                {{ $app->appointment_date ? $app->appointment_date->format('M d, Y') : 'N/A' }}
                                            </div>
                                            <small class="text-accent" style="color: var(--brand-accent) !important; font-size: 0.72rem;">
                                                <i class="bi bi-clock me-1"></i>{{ $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : '--' }}
                                            </small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} uppercase px-2 py-1" style="font-size: 0.65rem; letter-spacing: 0.5px; background-color: rgba(25, 211, 140, 0.08);">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted small italic" style="color: var(--text-muted) !important;">
                                            <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-50"></i>
                                            No recent appointment inquiries found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: Recommended Tests --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border overflow-hidden h-100" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 14px;">
                <div class="card-header py-3 px-4 border-bottom" style="background-color: var(--brand-dark); border-color: rgba(255, 255, 255, 0.08) !important;">
                    <h6 class="mb-0 small uppercase fw-bold tracking-wider text-white d-flex align-items-center">
                        <i class="bi bi-star-fill text-accent me-2 fs-5" style="color: var(--brand-accent) !important;"></i>
                        <span>Recommended Tests</span>
                    </h6>
                </div>
                <div class="card-body p-4 custom-scroll" style="max-height: 480px; overflow-y: auto;">
                    @foreach($popularServices as $service)
                        <div class="mb-3 pb-3 border-bottom text-start" style="border-color: var(--border-color) !important;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div class="fw-bold small uppercase" style="color: var(--text-main) !important;">{{ $service->name }}</div>
                                <div class="text-accent small fw-bold" style="color: var(--brand-accent) !important;">₱{{ number_format($service->price, 2) }}</div>
                            </div>
                            <div class="smaller mb-2" style="font-size: 0.72rem; color: var(--text-muted) !important;">
                                {{ Str::limit($service->description, 65) }}
                            </div>
                            <a href="{{ route('appointments.create') }}" class="text-accent small fw-bold text-decoration-none d-inline-flex align-items-center gap-1 hover-accent" style="color: var(--brand-accent) !important; font-size: 0.75rem;">
                                <span>BOOK TEST</span>
                                <i class="bi bi-chevron-right" style="font-size: 0.7rem;"></i>
                            </a>
                        </div>
                    @endforeach
                    <div class="text-center mt-3 pt-2">
                        <a href="{{ route('services.index') }}" class="small fw-semibold text-decoration-none hover-accent" style="color: var(--text-muted) !important;">
                            Browse full catalog &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     SCOPED STYLES
========================================================================= -->
<style>
.action-gateway-card {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease;
}

.action-gateway-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
}

.appointment-row-hover {
    transition: background-color 0.15s ease-in-out;
}

.appointment-row-hover:hover {
    background-color: rgba(25, 211, 140, 0.04) !important;
}

.hover-accent:hover {
    color: var(--brand-accent-hover) !important;
}
</style>

<!-- =========================================================================
     SCRIPTS: REAL-TIME CLOCK & DYNAMIC REFRESH ENGINE
========================================================================= -->
@push('scripts')
<script>
/**
 * 1. REAL-TIME LIVE CLOCK
 */
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { 
        hour12: true, 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
    const clockEl = document.getElementById('live-clock');
    if (clockEl) clockEl.innerText = timeStr;
}
setInterval(updateClock, 1000);
updateClock();

/**
 * 2. DYNAMIC LIVE REFRESH ENGINE (NO PAGE RELOAD REQUIRED)
 */
async function refreshDashboardInquiries() {
    try {
        const response = await fetch("{{ route('main') }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) return;

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const newBody = doc.getElementById('recent-inquiries-body');
        const currentBody = document.getElementById('recent-inquiries-body');

        if (newBody && currentBody) {
            currentBody.style.transition = 'opacity 0.2s ease-out';
            currentBody.style.opacity = '0.3';

            setTimeout(() => {
                currentBody.innerHTML = newBody.innerHTML;
                currentBody.style.opacity = '1';

                // Flash sync indicator
                const syncBadge = document.getElementById('live-sync-indicator');
                if (syncBadge) {
                    syncBadge.classList.replace('text-white-50', 'text-accent');
                    setTimeout(() => syncBadge.classList.replace('text-accent', 'text-white-50'), 1500);
                }
            }, 200);
        }
    } catch (err) {
        console.warn("Live dashboard sync failed:", err);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // A. Real-Time WebSockets Listener via Pusher
    if (typeof Pusher !== 'undefined') {
        const pusherKey = "{{ env('PUSHER_APP_KEY') }}";
        const pusherCluster = "{{ env('PUSHER_APP_CLUSTER') }}";
        if (pusherKey) {
            const pusher = new Pusher(pusherKey, { cluster: pusherCluster });

            // Listen to private notifications channel
            const notifChannel = pusher.subscribe('notifications.{{ Auth::id() }}');
            notifChannel.bind('notification.received', () => {
                refreshDashboardInquiries();
            });

            // Listen to clinical queue updates
            const queueChannel = pusher.subscribe('clinical-queue');
            queueChannel.bind('queue.updated', () => {
                refreshDashboardInquiries();
            });
        }
    }

    // B. Background polling fallback every 20 seconds
    setInterval(refreshDashboardInquiries, 20000);
});
</script>
@endpush
@endsection