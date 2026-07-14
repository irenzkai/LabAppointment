@php
    // Determine badge color mappings based on clinical status
    $statusColor = match($app->status) {
        'pending' => 'warning',
        'approved' => 'info',
        'tested' => 'info',
        'encoded' => 'info',
        'released' => 'accent',
        'returned' => 'danger',
        default => 'secondary'
    };
@endphp

<div class="card app-list-card bg-card border-secondary border-opacity-50 p-3 text-start" id="card-{{ $app->id }}" onclick="showAppointmentDetails('{{ $app->id }}')">
    {{-- Card Header: Patient Name & Status Badge --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold text-main fs-6 text-truncate" style="max-width: 180px;">
            {{ $groupCount > 1 ? $app->organization_name : $app->patient_name }}
        </div>
        <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
            {{ $app->status }}
        </span>
    </div>

    {{-- Card Body: Date, Time Slot, and Account Type Badge --}}
    <div class="d-flex justify-content-between align-items-end mt-1 text-muted" style="font-size: 0.75rem;">
        <div>
            <div><i class="bi bi-calendar2 me-1"></i> {{ $app->appointment_date->format('M d, Y') }}</div>
            <div class="text-accent mt-0.5"><i class="bi bi-clock me-1"></i> {{ date('h:i A', strtotime($app->time_slot)) }}</div>
        </div>
        <div class="text-end">
            @if($app->batch_id)
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">BULK ({{ $groupCount }} PAX)</span>
            @elseif($app->dependent_id)
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">DEPENDENT</span>
            @else
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">PERSONAL</span>
            @endif
        </div>
    </div>
</div>