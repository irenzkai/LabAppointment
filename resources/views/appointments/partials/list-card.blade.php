@php
// Determine badge color mappings based on clinical and expiration status
$statusPriority = [
    'expired' => 1,
    'returned' => 2,
    'pending' => 3,
    'approved' => 4,
    'tested' => 5,
    'encoded' => 6,
    'released' => 7,
];

if ($app->batch_id) {
    $batchAppsQuery = \App\Models\Appointment::where('batch_id', $app->batch_id);
    if (auth()->check() && auth()->user()->isPatient()) {
        $batchAppsQuery->where('deleted_by_patient', false);
    }
    $batchApps = $batchAppsQuery->get();

    $lowestPriority = 999;
    $lowestStatus = $app->status;

    foreach ($batchApps as $subApp) {
        $effStatus = $subApp->isExpired() ? 'expired' : $subApp->status;
        $priority = $statusPriority[$effStatus] ?? 99;
        if ($priority < $lowestPriority) {
            $lowestPriority = $priority;
            $lowestStatus = $effStatus;
        }
    }

    $isExpired = ($lowestStatus === 'expired');
    $finalStatus = $lowestStatus;
} else {
    $isExpired = $app->isExpired();
    $finalStatus = $isExpired ? 'expired' : $app->status;
}

$statusColor = match($finalStatus) {
    'expired' => 'danger',
    'pending' => 'warning',
    'approved' => 'info',
    'tested' => 'info',
    'encoded' => 'info',
    'released' => 'accent',
    'returned' => 'danger',
    default => 'secondary'
};

$statusLabel = strtoupper($finalStatus);

// Determine the latest card edit timestamp in the queue list (ONLY if edited after clinical release)
$latestCardEditTimestamp = null;
if ($app->batch_id) {
    $groupedApps = \App\Models\Appointment::where('batch_id', $app->batch_id)->get();
    foreach ($groupedApps as $groupedApp) {
        if ($groupedApp->results_released_at && $groupedApp->result && $groupedApp->result->audits->isNotEmpty()) {
            foreach ($groupedApp->result->audits as $audit) {
                if (\Carbon\Carbon::parse($audit->updated_at)->gt(\Carbon\Carbon::parse($groupedApp->results_released_at))) {
                    if (!$latestCardEditTimestamp || \Carbon\Carbon::parse($audit->updated_at)->gt($latestCardEditTimestamp)) {
                        $latestCardEditTimestamp = \Carbon\Carbon::parse($audit->updated_at);
                    }
                }
            }
        }
    }
} else {
    if ($app->results_released_at && $app->result && $app->result->audits->isNotEmpty()) {
        foreach ($app->result->audits as $audit) {
            if (\Carbon\Carbon::parse($audit->updated_at)->gt(\Carbon\Carbon::parse($app->results_released_at))) {
                if (!$latestCardEditTimestamp || \Carbon\Carbon::parse($audit->updated_at)->gt($latestCardEditTimestamp)) {
                    $latestCardEditTimestamp = \Carbon\Carbon::parse($audit->updated_at);
                }
            }
        }
    }
}
@endphp

<div class="card app-list-card bg-card border-secondary border-opacity-50 p-3 text-start" id="card-{{ $app->id }}" onclick="showAppointmentDetails('{{ $app->id }}')">
    {{-- Card Header: Patient Name & Status Badge --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold text-main fs-6 text-truncate" style="max-width: 180px;">
            {{ $groupCount > 1 ? $app->organization_name : $app->patient_name }}
        </div>
        <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
            {{ $statusLabel }}
        </span>
    </div>

    {{-- Card Body: Date, Time Slot, and Account Type Badge --}}
    <div class="d-flex justify-content-between align-items-end mt-1 text-muted" style="font-size: 0.75rem;">
        <div>
            <div><i class="bi bi-calendar2 me-1"></i> {{ $app->appointment_date->format('M d, Y') }}</div>
            <div class="text-accent mt-0.5"><i class="bi bi-clock me-1"></i> {{ date('h:i A', strtotime($app->time_slot)) }}</div>
            @if($latestCardEditTimestamp)
            <div class="text-warning fw-bold mt-0.5" style="font-size: 0.7rem;">
                <i class="bi bi-pencil-square me-1"></i>Edited: {{ $latestCardEditTimestamp->format('M d, Y | h:i A') }}
            </div>
            @endif
        </div>
        <div class="text-end">
            @if($app->batch_id)
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">
                BULK ({{ $groupCount }} PAX)
            </span>
            @elseif($app->dependent_id)
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">
                DEPENDENT
            </span>
            @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">
                PERSONAL
            </span>
            @endif
        </div>
    </div>
</div>