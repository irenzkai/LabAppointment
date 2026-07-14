@extends('layouts.app')

@section('content')
<div id="schedule-manager-page" class="row g-4 text-start animate-page">
    
    {{-- Header Section --}}
    <div class="col-12 mb-2">
        <div class="d-flex justify-content-between align-items-end border-bottom pb-3" style="border-color: var(--border-color) !important;">
            <div>
                <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">Clinical Schedule Manager</h2>
                <p class="text-secondary small mb-0">Control clinic operating hours, capacity, and monitor live bookings.</p>
            </div>
        </div>
    </div>

    {{-- LEFT COLUMN: OCCUPANCY VISUALIZER & DYNAMIC CALENDAR --}}
    <div class="col-lg-7">
        <div class="card border-secondary shadow-lg h-100" style="background-color: var(--bg-card); color: var(--text-main);">
            <div class="card-header border-bottom border-secondary border-opacity-25 p-4" style="background-color: rgba(0, 0, 0, 0.02);">
                <div class="row align-items-center">
                    <div class="col-md-6 col-6">
                        <h5 class="fw-bold mb-0 uppercase small text-accent">
                            <i class="bi bi-calendar3 me-2"></i>Daily Occupancy Grid
                        </h5>
                    </div>
                    
                    {{-- Date Selector Form --}}
                    <div class="col-md-6 col-6 text-end">
                        <form action="{{ route('admin.appointment-settings') }}" method="GET" id="dateSelectorForm" class="d-inline-block">
                            <div class="input-group input-group-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                                <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card);">DATE:</span>
                                <input type="date" name="date" id="selectedDateInput" class="form-control border-0 shadow-none fw-bold" style="background-color: var(--bg-card); color: var(--text-main);" value="{{ $selectedDate }}" onchange="this.form.submit()">
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 overflow-auto custom-scroll" style="max-height: 650px;">
                {{-- Embedded Interactive Calendar Widget --}}
                <div id="calendar-widget-wrapper" class="mb-4"></div>

                {{-- Time Slots Grid --}}
                @if($config && $config->is_open)
                    <div class="row g-3">
                        @forelse($slots as $s)
                            <div class="col-md-4 col-sm-6">
                                <div class="p-3 rounded border transition-all h-100 {{ $s['booked_count'] >= $s['capacity'] ? 'border-danger bg-danger bg-opacity-10 text-danger' : 'border-secondary bg-secondary bg-opacity-10' }} cursor-help"
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-html="true"
                                    title="Bookings: {{ date('h:i A', strtotime($s['time'])) }}"
                                    data-bs-content='
                                        <div class="p-1 text-start">
                                            @foreach($s['patients'] as $p)
                                                <div class="smaller mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                                    <div class="fw-bold text-white uppercase">{{ $p->patient_name }}</div>
                                                    <div class="text-neon x-small">REF: #{{ $p->id }} | <span class="text-secondary">{{ strtoupper($p->status) }}</span></div>
                                                </div>
                                            @endforeach
                                            @if($s['booked_count'] == 0) <span class="text-muted italic">No appointments for this slot.</span> @endif
                                        </div>
                                    '>
                                    
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="fw-bold small" style="color: var(--text-main);">{{ date('h:i A', strtotime($s['time'])) }}</span>
                                        <span class="badge {{ $s['booked_count'] >= $s['capacity'] ? 'bg-danger text-white' : 'bg-neon text-dark' }} smaller px-2">
                                            {{ $s['booked_count'] }}/{{ $s['capacity'] }}
                                        </span>
                                    </div>
                                    <div class="progress border border-secondary border-opacity-25" style="height: 6px; background-color: rgba(0,0,0,0.15);">
                                        <div class="progress-bar {{ $s['booked_count'] >= $s['capacity'] ? 'bg-danger' : 'bg-neon' }} shadow-neon" 
                                            style="width: {{ ($s['booked_count']/$s['capacity'])*100 }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded">
                                <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                                No slots generated. Check configuration for this date.
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="display-1 text-danger opacity-25 mb-3"><i class="bi bi-calendar-x"></i></div>
                        <h4 class="text-danger fw-bold uppercase">Clinic is Closed</h4>
                        <p class="text-secondary">No appointments can be made for {{ date('M d, Y', strtotime($selectedDate)) }}.</p>
                        @if($config && $config->specific_date)
                            <span class="badge bg-danger px-3 py-2">MANUAL OVERRIDE / HOLIDAY</span>
                        @endif
                    </div>
                @endif
            </div>
            
            <div class="card-footer p-3 border-top border-secondary border-opacity-25">
                <div class="d-flex justify-content-center gap-4 smaller text-muted uppercase fw-bold">
                    <span><i class="bi bi-square-fill text-neon me-1"></i> Available</span>
                    <span><i class="bi bi-square-fill text-danger me-1"></i> Full / Closed</span>
                    <span><i class="bi bi-info-circle-fill text-info me-1"></i> Hover for details</span>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT COLUMN: CONFIGURATION PANEL --}}
    <div class="col-lg-5">
        <div class="card border-secondary shadow-lg sticky-top" style="top: 100px; background-color: var(--bg-card); color: var(--text-main);">
            <div class="card-header p-0 border-bottom border-secondary border-opacity-25" style="background-color: rgba(0,0,0,0.02);">
                <ul class="nav nav-pills nav-fill" id="configTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-3 small fw-bold uppercase rounded-0 border-end border-secondary border-opacity-25" data-bs-toggle="tab" data-bs-target="#tab-weekly">
                            <i class="bi bi-arrow-repeat me-2"></i>Weekly Rules
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 small fw-bold uppercase rounded-0" data-bs-toggle="tab" data-bs-target="#tab-override">
                            <i class="bi bi-calendar-plus me-2"></i>Date Override
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content">
                    
                    {{-- TAB 1: WEEKLY RECURRING RULES --}}
                    <div class="tab-pane fade show active" id="tab-weekly">
                        <form action="{{ route('admin.appointment-settings.store') }}" method="POST" id="weeklyRulesForm">
                            @csrf
                            <input type="hidden" name="mode" id="weeklyModeInput" value="day">
                            
                            <div class="mb-4">
                                <label class="smaller text-muted fw-bold mb-2 uppercase d-block">Select Day to Edit</label>
                                <div class="row g-1">
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $idx => $dayName)
                                        <div class="col">
                                            <input type="radio" class="btn-check" name="day_of_week" id="day_{{$idx}}" value="{{$idx}}" {{ date('w', strtotime($selectedDate)) == $idx ? 'checked' : '' }}>
                                            <label class="btn btn-outline-secondary btn-sm w-100 fw-bold border-opacity-25" for="day_{{$idx}}">{{$dayName}}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @include('admin.partials.config-form-fields')

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn-custom btn-accent py-3 fw-bold">SAVE RECURRING RULE</button>
                                <button type="button" class="btn btn-link text-secondary smaller text-decoration-none hover-neon" data-bs-toggle="modal" data-bs-target="#applyAllModal">
                                    <i class="bi bi-stars me-1"></i> Apply to all 7 days
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- TAB 2: SPECIFIC DATE OVERRIDE --}}
                    <div class="tab-pane fade" id="tab-override">
                        <form action="{{ route('admin.appointment-settings.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="date">
                            
                            <div class="alert bg-info bg-opacity-10 border-info text-info smaller mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i> Overrides take priority over weekly rules. Use this for holidays or one-off schedule changes.
                            </div>

                            <div class="mb-4">
                                <label class="smaller text-muted fw-bold mb-1 uppercase">Target Date for Override</label>
                                <input type="date" name="specific_date" class="form-control py-3 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" value="{{ $selectedDate }}" required>
                            </div>

                            @include('admin.partials.config-form-fields')

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn-custom btn-accent py-3 fw-bold shadow-neon">ACTIVATE OVERRIDE</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>

{{-- 3. BOOTSTRAP MODALS SECTION --}}
<div class="modal fade" id="applyAllModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="modal-header py-3" style="border-bottom: 1px solid var(--border-color);">
                <h6 class="modal-title text-accent fw-bold uppercase d-flex align-items-center m-0">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-accent"></i>
                    Apply to All Operating Days?
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <p class="small mb-0 text-muted">
                    This action will replicate your current schedule configurations (Opening Time, Closing Time, Slot Duration, Slot Capacity, and Lead-Time Buffer) to <strong style="color: var(--text-main);">EVERY standard operating day</strong> (Monday through Sunday).
                </p>
            </div>
            <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                <div class="d-flex w-100">
                    <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" id="confirmApplyAllBtn" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">
                        Confirm & Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Load separated Styles & Scripts Partials --}}
@include('admin.partials.schedule-styles')
@include('admin.partials.schedule-scripts')

@endsection