@extends('layouts.app')

@section('content')
<div class="row g-4 text-start">
    <div class="col-12">
        <h2 class="text-neon fw-bold mb-0 uppercase">APPOINTMENT TIME CONFIGURATION</h2>
        <p class="text-secondary small">Set custom hours and limits for each day of the week.</p>
    </div>

    {{-- LEFT: TABBED RULES --}}
    <div class="col-lg-5">
        <div class="card p-0 border-secondary overflow-hidden">
            <ul class="nav nav-pills bg-black p-2 gap-1 border-bottom border-secondary" id="dayTabs">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $idx => $name)
                    <li class="nav-item flex-fill text-center">
                        <button class="nav-link w-100 py-2 small text-white fw-bold {{ $idx == 1 ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#day-{{$idx}}">{{$name}}</button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content p-4">
                @foreach($allConfigs as $conf)
                <div class="tab-pane fade {{ $conf->day_of_week == 1 ? 'show active' : '' }}" id="day-{{$conf->day_of_week}}">
                    <form action="{{ route('admin.appointment-settings.update', $conf->id) }}" method="POST">
                        @csrf @method('PUT')
                        
                        <div class="form-check form-switch mb-4 p-3 bg-dark rounded border border-secondary">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_open" id="open-{{$conf->id}}" value="1" {{ $conf->is_open ? 'checked' : '' }}>
                            <label class="form-check-label text-neon fw-bold small uppercase" for="open-{{$conf->id}}">Laboratory is Open</label>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Opening Time</label>
                                <input type="time" name="opening_time" class="form-control" value="{{ substr($conf->opening_time, 0, 5) }}">
                            </div>
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Closing Time</label>
                                <input type="time" name="closing_time" class="form-control" value="{{ substr($conf->closing_time, 0, 5) }}">
                            </div>
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Time Slot (Mins)</label>
                                <select name="slot_duration" class="form-select">
                                    <option value="15" {{ $conf->slot_duration == 15 ? 'selected' : '' }}>15</option>
                                    <option value="30" {{ $conf->slot_duration == 30 ? 'selected' : '' }}>30</option>
                                    <option value="60" {{ $conf->slot_duration == 60 ? 'selected' : '' }}>60</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Max Patients Per Slot</label>
                                <input type="number" name="max_patients_per_slot" class="form-control" value="{{ $conf->max_patients_per_slot }}" min="1">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top border-secondary">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="has_lunch_break" value="1" id="lunch-{{$conf->id}}" {{ $conf->has_lunch_break ? 'checked' : '' }}>
                                <label class="form-check-label text-white small" for="lunch-{{$conf->id}}">Lunch Break</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><input type="time" name="lunch_start" class="form-control form-control-sm" value="{{ $conf->lunch_start ? substr($conf->lunch_start,0,5) : '' }}"></div>
                                <div class="col-6"><input type="time" name="lunch_end" class="form-control form-control-sm" value="{{ $conf->lunch_end ? substr($conf->lunch_end,0,5) : '' }}"></div>
                            </div>
                        </div>

                        <button type="submit" class="btn-custom btn-neon w-100 mt-4 py-3">SAVE {{ strtoupper(date('l', strtotime("Sunday +{$conf->day_of_week} days"))) }}</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- RIGHT: VISUALIZER --}}
    <div class="col-lg-7">
        <div class="card p-4 border-neon shadow-lg">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                <h5 class="text-white fw-bold mb-0">OCCUPANCY VISUALIZER</h5>
                <form action="{{ route('admin.appointment-settings') }}" method="GET">
                    <input type="date" name="date" class="form-control form-control-sm bg-black border-neon text-neon fw-bold" value="{{ $selectedDate }}" onchange="this.form.submit()">
                </form>
            </div>

            @if(!$config->is_open)
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-danger" style="font-size: 3rem;"></i>
                    <h5 class="text-danger fw-bold mt-2">CLINIC CLOSED</h5>
                    <p class="text-secondary small">No slots are generated for this day.</p>
                </div>
            @else
                <div class="row g-2 overflow-auto" style="max-height: 500px;">
                    @foreach($slots as $slot)
                        <div class="col-md-4">
                            <div class="p-3 rounded border text-center {{ $slot['is_full'] ? 'border-danger bg-dark' : 'border-secondary' }}">
                                <div class="small fw-bold text-white mb-1">{{ date('h:i A', strtotime($slot['time'])) }}</div>
                                <div class="progress" style="height: 4px; background: #222;">
                                    <div class="progress-bar {{ $slot['is_full'] ? 'bg-danger' : 'bg-neon' }}" style="width: {{ ($slot['booked']/$slot['capacity'])*100 }}%"></div>
                                </div>
                                <div class="smaller mt-2 {{ $slot['is_full'] ? 'text-danger' : 'text-secondary' }}">{{ $slot['booked'] }} / {{ $slot['capacity'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection