@extends('layouts.app')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <h2 class="text-neon fw-bold mb-0">APPOINTMENT CONFIGURATION</h2>
        <p class="text-secondary small">Manage operational hours and scheduling logic</p>
    </div>

    {{-- LEFT: CONFIGURATION FORM --}}
    <div class="col-lg-4">
        <div class="card p-4 border-secondary h-100">
            <h5 class="text-white fw-bold mb-4 uppercase">Global Rules</h5>
            <form action="{{ route('admin.appointment-settings.update') }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="small text-secondary fw-bold mb-1">OPENING TIME</label>
                    <input type="time" name="opening_time" class="form-control" value="{{ substr($config->opening_time, 0, 5) }}" required>
                </div>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold mb-1">CLOSING TIME</label>
                    <input type="time" name="closing_time" class="form-control" value="{{ substr($config->closing_time, 0, 5) }}" required>
                </div>
                <div class="mb-3">
                    <label class="small text-secondary fw-bold mb-1">SLOT DURATION (MINUTES)</label>
                    <select name="slot_duration" class="form-select">
                        <option value="15" {{ $config->slot_duration == 15 ? 'selected' : '' }}>15 Mins</option>
                        <option value="30" {{ $config->slot_duration == 30 ? 'selected' : '' }}>30 Mins</option>
                        <option value="60" {{ $config->slot_duration == 60 ? 'selected' : '' }}>60 Mins (1 Hour)</option>
                    </select>
                </div>
                <div class="mb-3 border-top border-secondary pt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="has_lunch_break" value="1" id="lunchToggle" {{ $config->has_lunch_break ? 'checked' : '' }}>
                        <label class="form-check-label text-white small fw-bold" for="lunchToggle">ENABLE LUNCH BREAK</label>
                    </div>
                </div>

                <div id="lunchInputs" style="display: {{ $config->has_lunch_break ? 'block' : 'none' }}">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="smaller text-secondary small mb-1">LUNCH START</label>
                            <input type="time" name="lunch_start" class="form-control" value="{{ $config->lunch_start ? substr($config->lunch_start, 0, 5) : '' }}">
                        </div>
                        <div class="col-6">
                            <label class="smaller text-secondary small mb-1">LUNCH END</label>
                            <input type="time" name="lunch_end" class="form-control" value="{{ $config->lunch_end ? substr($config->lunch_end, 0, 5) : '' }}">
                        </div>
                    </div>
                </div>

                <script>
                    document.getElementById('lunchToggle').addEventListener('change', function() {
                        document.getElementById('lunchInputs').style.display = this.checked ? 'block' : 'none';
                    });
                </script>
                <div class="mb-4">
                    <label class="small text-secondary fw-bold mb-1">PATIENTS PER SLOT</label>
                    <input type="number" name="max_patients_per_slot" class="form-control" value="{{ $config->max_patients_per_slot }}" min="1" required>
                </div>
                <button type="submit" class="btn-custom btn-neon w-100 py-3">SAVE CONFIGURATION</button>
            </form>
        </div>
    </div>

    {{-- RIGHT: VISUAL CALENDAR / OCCUPANCY --}}
    <div class="col-lg-8">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-white fw-bold mb-0 uppercase">Occupancy Visualizer</h5>
                <form action="{{ route('admin.appointment-settings') }}" method="GET" class="d-flex gap-2">
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" onchange="this.form.submit()">
                </form>
            </div>

            <div class="row g-2">
                @foreach($slots as $slot)
                    <div class="col-md-4 col-6">
                        <div class="p-3 rounded border text-center {{ $slot['is_full'] ? 'border-danger bg-dark opacity-75' : 'border-neon bg-black' }}">
                            <div class="text-white fw-bold small">{{ date('h:i A', strtotime($slot['time'])) }}</div>
                            
                            {{-- Visual Bar --}}
                            <div class="progress mt-2" style="height: 6px; background: #222;">
                                @php $percent = ($slot['booked'] / $slot['capacity']) * 100; @endphp
                                <div class="progress-bar {{ $slot['is_full'] ? 'bg-danger' : 'bg-neon' }}" style="width: {{ $percent }}%"></div>
                            </div>
                            
                            <div class="mt-2 smaller {{ $slot['is_full'] ? 'text-danger' : 'text-secondary' }}">
                                {{ $slot['booked'] }} / {{ $slot['capacity'] }} Patients
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 p-3 bg-black rounded border border-secondary d-flex gap-4 justify-content-center">
                <div class="small text-secondary"><i class="bi bi-square-fill text-neon me-1"></i> Available</div>
                <div class="small text-secondary"><i class="bi bi-square-fill text-danger me-1"></i> Fully Occupied</div>
            </div>
        </div>
    </div>
</div>
@endsection