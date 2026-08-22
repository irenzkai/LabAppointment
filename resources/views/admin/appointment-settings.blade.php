@extends('layouts.app')

@section('title', 'Appointment Settings')

@section('content')
<div id="schedule-manager-page" class="row g-4 text-start animate-page">
 
    {{-- Header Section --}}
    <div class="col-12 mb-2">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom pb-3" style="border-color: var(--border-color) !important;">
            <div>
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background-color: rgba(25, 211, 140, 0.1); border: 1px solid var(--brand-accent);">
                    <i class="bi bi-clock-history text-accent fs-6"></i>
                    <span class="fw-bold uppercase tracking-wider fs-x-small text-accent">Operating Schedule Console</span>
                </div>
                <h2 class="text-accent fw-bold mb-1 uppercase tracking-tighter">Clinical Schedule Manager</h2>
                <p class="text-secondary small mb-0">Control clinic operating hours, capacity quotas, and monitor live slot occupancy.</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="card p-2.5 px-3 border-secondary bg-card shadow-sm d-flex flex-row align-items-center gap-3">
                    <div>
                        <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.65rem;">Active Target Date</small>
                        <span class="fw-bold text-main small">{{ date('F d, Y', strtotime($selectedDate)) }}</span>
                    </div>
                    <span class="badge {{ ($config && $config->is_open) ? 'bg-success bg-opacity-15 text-white border border-success' : 'bg-danger bg-opacity-15 text-white border border-danger' }} px-2.5 py-1.5 uppercase fw-bold">
                        {{ ($config && $config->is_open) ? 'Clinic Open' : 'Clinic Closed' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- LEFT COLUMN: OCCUPANCY VISUALIZER & DYNAMIC CALENDAR --}}
    <div class="col-lg-7">
        <div class="card border-secondary shadow-lg h-100 overflow-hidden" style="background-color: var(--bg-card); color: var(--text-main);">
            <div class="card-header border-bottom border-secondary border-opacity-25 p-3 px-4" style="background-color: rgba(0, 0, 0, 0.02);">
                <div class="row align-items-center g-2">
                    <div class="col-md-6 col-12">
                        <h5 class="fw-bold mb-0 uppercase small text-accent d-flex align-items-center gap-2">
                            <i class="bi bi-calendar3 fs-5"></i>
                            <span>Daily Occupancy Grid</span>
                        </h5>
                    </div>
                    
                    {{-- Date Selector Form --}}
                    <div class="col-md-6 col-12 text-md-end">
                        <form action="{{ route('admin.appointment-settings') }}" method="GET" id="dateSelectorForm" class="d-inline-block w-100 w-md-auto">
                            <div class="input-group input-group-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden shadow-sm">
                                <span class="input-group-text border-0 text-secondary fw-bold" style="background-color: var(--bg-card);">DATE:</span>
                                <input type="date" name="date" id="selectedDateInput" class="form-control border-0 shadow-none fw-bold" style="background-color: var(--bg-card); color: var(--text-main);" value="{{ $selectedDate }}" onchange="this.form.submit()">
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- FIXED: Removed p-4 from card-body so scrollbar spans the entire edge of the card --}}
            <div class="card-body p-0 overflow-auto custom-scroll" style="max-height: 700px;">
                <div class="p-4">
                    {{-- Embedded Interactive Calendar Widget --}}
                    <div id="calendar-widget-wrapper" class="mb-4"></div>

                    {{-- Time Slots Grid Header & Relocated Legend Toolbar --}}
                    <div class="p-3 rounded-3 mb-3 border border-secondary border-opacity-15 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2" style="background-color: var(--bg-main);">
                        <h6 class="text-main fw-bold uppercase smaller tracking-wider mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-grid-3x3-gap-fill text-accent me-1"></i>
                            <span>Time Slots for {{ date('M d, Y', strtotime($selectedDate)) }}</span>
                        </h6>
                        
                        {{-- Clean Inline Legend Toolbar --}}
                        <div class="d-flex align-items-center gap-3 smaller uppercase fw-bold" style="font-size: 0.7rem;">
                            <span class="d-flex align-items-center gap-1.5 text-main">
                                <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: var(--brand-accent);"></span>
                                Available
                            </span>
                            <span class="d-flex align-items-center gap-1.5 text-main">
                                <span class="rounded-circle d-inline-block bg-danger" style="width: 8px; height: 8px;"></span>
                                Full / Closed
                            </span>
                            <span class="d-flex align-items-center gap-1.5 text-muted">
                                <i class="bi bi-info-circle-fill text-info me-1"></i>
                                Hover for Details
                            </span>
                        </div>
                    </div>

                    {{-- Time Slots Grid --}}
                    @if($config && $config->is_open)
                        <div class="row g-3">
                            @forelse($slots as $s)
                                @php
                                    $isFull = $s['booked_count'] >= $s['capacity'];
                                @endphp
                                <div class="col-md-4 col-sm-6">
                                    <div class="p-3 rounded-3 border transition-all h-100 slot-card {{ $isFull ? 'border-danger bg-danger bg-opacity-10 text-danger' : 'border-secondary bg-secondary bg-opacity-10' }} cursor-help position-relative"
                                         data-bs-toggle="popover" 
                                         data-bs-trigger="hover focus" 
                                         data-bs-html="true"
                                         title="<i class='bi bi-people-fill me-1.5'></i> Bookings: {{ date('h:i A', strtotime($s['time'])) }}"
                                         data-bs-content='
                                             <div class="p-1 text-start">
                                                 @foreach($s['patients'] as $p)
                                                     <div class="smaller mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                                                         <div class="fw-bold text-white uppercase">{{ $p->patient_name }}</div>
                                                         <div class="text-neon x-small">REF: #{{ $p->id }} | <span class="text-secondary">{{ strtoupper($p->status) }}</span></div>
                                                     </div>
                                                 @endforeach
                                                 @if($s['booked_count'] == 0) 
                                                     <span class="text-muted italic smaller">No appointments booked for this slot.</span> 
                                                 @endif
                                             </div>
                                         '>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-secondary);">
                                                <i class="bi bi-clock text-accent"></i>
                                                <span>{{ date('h:i A', strtotime($s['time'])) }}</span>
                                            </span>
                                            <span class="badge slot-pax-badge {{ $isFull ? 'badge-full' : 'badge-available' }} smaller px-2.5 py-1 fw-bold text-secondary">
                                                {{ $s['booked_count'] }}/{{ $s['capacity'] }} PAX
                                            </span>
                                        </div>
                                        
                                        <div class="progress border border-secondary border-opacity-25 rounded-pill" style="height: 6px; background-color: rgba(0,0,0,0.15);">
                                            <div class="progress-bar {{ $isFull ? 'bg-danger' : 'bg-accent' }} rounded-pill" 
                                                 style="width: {{ min(100, ($s['booked_count'] / max(1, $s['capacity'])) * 100) }}%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 py-5 text-center text-muted border border-secondary border-dashed rounded-3">
                                    <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                                    <span>No slots generated. Please check operating hours configuration for this date.</span>
                                </div>
                            @endforelse
                        </div>
                    @else
                        <div class="text-center py-5 border border-danger border-opacity-25 bg-danger bg-opacity-10 rounded-3 p-4">
                            <div class="display-3 text-danger opacity-50 mb-3"><i class="bi bi-calendar-x"></i></div>
                            <h5 class="text-danger fw-bold uppercase tracking-tight">Clinic Closed on this Date</h5>
                            <p class="text-secondary small mb-3">No appointments can be scheduled for {{ date('F d, Y', strtotime($selectedDate)) }}.</p>
                            @if($config && $config->specific_date)
                                <span class="badge bg-danger px-3 py-2 uppercase fw-bold shadow-sm d-inline-flex align-items-center gap-1.5">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>Special Date Override / Holiday Active</span>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT COLUMN: CONFIGURATION PANEL --}}
    <div class="col-lg-5">
        <div class="card border-secondary shadow-lg sticky-top" style="top: 90px; background-color: var(--bg-card); color: var(--text-main);">
            <div class="card-header p-0 border-bottom border-secondary border-opacity-25" style="background-color: rgba(0,0,0,0.02);">
                <ul class="nav nav-pills nav-fill" id="configTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-3 small fw-bold uppercase rounded-0 border-end border-secondary border-opacity-25 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="tab" data-bs-target="#tab-weekly">
                            <i class="bi bi-arrow-repeat fs-6"></i>
                            <span>Weekly Rules</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 small fw-bold uppercase rounded-0 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="tab" data-bs-target="#tab-override">
                            <i class="bi bi-calendar-plus fs-6"></i>
                            <span>Date Override</span>
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
                                <label class="smaller text-secondary fw-bold mb-2 uppercase d-block">Select Day to Edit</label>
                                <div class="row g-1">
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $idx => $dayName)
                                        <div class="col">
                                            <input type="radio" class="btn-check" name="day_of_week" id="day_{{$idx}}" value="{{$idx}}" {{ date('w', strtotime($selectedDate)) == $idx ? 'checked' : '' }}>
                                            <label class="btn btn-outline-secondary btn-sm w-100 fw-bold border-opacity-25 py-2" for="day_{{$idx}}">{{$dayName}}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @include('admin.partials.config-form-fields')

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn-custom btn-accent py-3 fw-bold uppercase shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-save-fill fs-6"></i>
                                    <span>Save Recurring Rule</span>
                                </button>
                                <button type="button" class="btn btn-link text-secondary smaller text-decoration-none hover-neon py-1 d-flex align-items-center justify-content-center gap-1.5" data-bs-toggle="modal" data-bs-target="#applyAllModal">
                                    <i class="bi bi-stars text-accent fs-6"></i>
                                    <span>Apply current parameters to all 7 days</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- TAB 2: SPECIFIC DATE OVERRIDE --}}
                    <div class="tab-pane fade" id="tab-override">
                        <form action="{{ route('admin.appointment-settings.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="date">
                            
                            <div class="alert alert-clinical border-info bg-info bg-opacity-10 text-info smaller mb-4 d-flex align-items-start gap-2">
                                <i class="bi bi-info-circle-fill fs-6 mt-0.5"></i>
                                <span>Overrides take immediate priority over weekly recurring rules. Use this for holidays or special schedule changes.</span>
                            </div>

                            <div class="mb-4">
                                <label class="smaller text-secondary fw-bold mb-1.5 uppercase d-flex align-items-center gap-1.5">
                                    <i class="bi bi-calendar-event text-accent"></i>
                                    <span>Target Date for Override</span>
                                </label>
                                <input type="date" name="specific_date" class="form-control py-2.5 shadow-none fw-bold" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" value="{{ $selectedDate }}" required>
                            </div>

                            @include('admin.partials.config-form-fields')

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn-custom btn-accent py-3 fw-bold uppercase shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-calendar-check-fill fs-6"></i>
                                    <span>Activate Date Override</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- BOOTSTRAP MODALS SECTION --}}
<div class="modal fade" id="applyAllModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
            <div class="modal-header py-3" style="border-bottom: 1px solid var(--border-color);">
                <h6 class="modal-title text-accent fw-bold uppercase d-flex align-items-center gap-2 m-0">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-accent"></i>
                    <span>Apply to All Operating Days?</span>
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