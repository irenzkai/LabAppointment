<!-- resources/views/appointments/partials/list-card.blade.php -->
@php
$groupCount = $groupCount ?? 1;
$isStaff = !empty($is_staff) && auth()->check() && auth()->user()->isEmployee();
$isHistoryView = !empty($isHistory);

// A card is rendered as a consolidated bulk batch if it has a batch_id, not in History view,
// and the user is either Staff or the Creator/Maker of that bulk batch.
$isBulkCard = ($app->batch_id && !$isHistoryView && ($isStaff || (auth()->check() && auth()->id() === $app->user_id)));

$batchApps = null;

if ($isBulkCard) {
    $batchAppsQuery = \App\Models\Appointment::with(['result.audits'])->where('batch_id', $app->batch_id);
    if (auth()->check() && auth()->user()->isPatient()) {
        $batchAppsQuery->where('deleted_by_patient', false);
    }
    $batchApps = $batchAppsQuery->get();
    $groupCount = $batchApps->count();

    // Filter out voided and canceled for active milestone triage
    $activeApps = $batchApps->reject(fn($a) => $a->isVoided() || $a->status === 'canceled');

    // Active milestone priority (lowest active bottleneck first, strictly non-voided/non-canceled)
    $activePriority = [
        'returned'     => 1,
        'needs_review' => 2,
        'retest'       => 3,
        'pending'      => 4,
        'approved'     => 5,
        'tested'       => 6,
        'encoded'      => 7,
        'released'     => 8,
    ];

    if ($activeApps->isNotEmpty()) {
        $lowestPriority = 999;
        $lowestStatus = 'released';

        foreach ($activeApps as $subApp) {
            $priority = $activePriority[$subApp->status] ?? 99;
            if ($priority < $lowestPriority) {
                $lowestPriority = $priority;
                $lowestStatus = $subApp->status;
            }
        }
        $finalStatus = $lowestStatus;
        $isVoided = false;
    } else {
        // All appointments in batch are either voided or canceled
        $allCanceled = $batchApps->every(fn($a) => $a->status === 'canceled');
        $finalStatus = $allCanceled ? 'canceled' : 'voided';
        $isVoided = ($finalStatus === 'voided');
    }
} else {
    $isVoided = $app->isVoided();
    $finalStatus = $isVoided ? 'voided' : $app->status;
}

$statusColor = match($finalStatus) {
    'voided', 'expired' => 'danger',
    'pending'           => 'warning',
    'needs_review'      => 'warning',
    'approved'          => 'info',
    'tested'            => 'info',
    'encoded'           => 'info',
    'released'          => 'accent',
    'returned'          => 'danger',
    'retest'            => 'danger',
    'canceled'          => 'danger',
    default             => 'secondary'
};

$statusLabel = match($finalStatus) {
    'voided', 'expired' => 'VOIDED',
    'needs_review'      => 'REVIEW NEEDED',
    default             => strtoupper(str_replace('_', ' ', $finalStatus))
};

// Determine the latest card edit timestamp in the queue list (ONLY if edited after clinical release)
$latestCardEditTimestamp = null;
if ($isBulkCard && $batchApps) {
    foreach ($batchApps as $groupedApp) {
        if ($groupedApp->results_released_at && $groupedApp->result && $groupedApp->result->audits && $groupedApp->result->audits->isNotEmpty()) {
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
    if ($app->results_released_at && $app->result && $app->result->audits && $app->result->audits->isNotEmpty()) {
        foreach ($app->result->audits as $audit) {
            if (\Carbon\Carbon::parse($audit->updated_at)->gt(\Carbon\Carbon::parse($app->results_released_at))) {
                if (!$latestCardEditTimestamp || \Carbon\Carbon::parse($audit->updated_at)->gt($latestCardEditTimestamp)) {
                    $latestCardEditTimestamp = \Carbon\Carbon::parse($audit->updated_at);
                }
            }
        }
    }
}

$formattedDate = $app->appointment_date ? \Carbon\Carbon::parse($app->appointment_date)->format('M d, Y') : 'N/A';
$formattedTime = $app->time_slot ? date('h:i A', strtotime($app->time_slot)) : 'N/A';
@endphp

<div class="card app-list-card bg-card border-secondary border-opacity-50 p-3 text-start" 
     id="card-{{ $app->id }}" 
     data-app-id="{{ $app->id }}"
     @if($app->batch_id) data-batch-id="{{ $app->batch_id }}" @endif
     onclick="showAppointmentDetails('{{ $app->id }}')">

    {{-- Card Header: Patient / Organization Name & Clean Synchronized Status Badge --}}
    <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
        <div class="fw-bold text-main fs-6 text-truncate" style="max-width: 180px;">
            {{ ($isBulkCard && $groupCount > 1) ? ($app->organization_name ?: $app->patient_name) : $app->patient_name }}
        </div>
        <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} uppercase text-nowrap" 
              style="font-size: 0.65rem; letter-spacing: 0.5px; background-color: rgba(25, 211, 140, 0.08);"
              @if($isVoided)
                  data-bs-toggle="tooltip" 
                  data-bs-placement="top" 
                  title="Appointment voided due to patient not arriving on scheduled time without prior notice."
              @endif>
            {{ $statusLabel }}
        </span>
    </div>

    {{-- Card Body: Date, Time Slot, and Account Type Badge --}}
    <div class="d-flex justify-content-between align-items-end mt-1 text-muted" style="font-size: 0.75rem;">
        <div>
            <div><i class="bi bi-calendar2 me-1"></i> {{ $formattedDate }}</div>
            <div class="text-accent mt-0.5"><i class="bi bi-clock me-1"></i> {{ $formattedTime }}</div>
            
            @if($latestCardEditTimestamp)
                <div class="text-warning fw-bold mt-0.5" style="font-size: 0.7rem;">
                    <i class="bi bi-pencil-square me-1"></i>Edited: {{ $latestCardEditTimestamp->format('M d, Y | h:i A') }}
                </div>
            @endif

            @if(!$isBulkCard)
                @if($finalStatus == 'needs_review')
                    <div class="text-warning fw-bold mt-1" style="font-size: 0.72rem;">
                        <i class="bi bi-pencil-square me-1"></i>Action: Review Adjustments
                    </div>
                @endif

                @if($finalStatus == 'retest')
                    <div class="text-danger fw-bold mt-1" style="font-size: 0.72rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Retest Required
                    </div>
                @endif

                @if($finalStatus == 'returned')
                    <div class="text-danger fw-bold mt-1" style="font-size: 0.72rem;">
                        <i class="bi bi-arrow-return-left me-1"></i>Corrections Needed
                    </div>
                @endif

                @if($finalStatus == 'canceled')
                    <div class="text-danger fw-bold mt-1" style="font-size: 0.72rem;">
                        <i class="bi bi-x-circle-fill me-1"></i>Appointment Canceled
                    </div>
                @endif

                @if($isVoided)
                    <div class="text-muted fst-italic mt-1" style="font-size: 0.72rem;" title="Voided due to no-show without prior notice">
                        <i class="bi bi-info-circle me-1"></i>Voided (No-Show)
                    </div>
                @endif
            @endif
        </div>

        <div class="text-end">
            @if($isBulkCard)
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">
                    BULK ({{ $groupCount }} PAX)
                </span>
            @elseif($app->batch_id)
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">
                    BULK
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