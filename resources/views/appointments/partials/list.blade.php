<div class="accordion" id="acc-{{ $type }}">
    @forelse($apps as $key => $item)
    @php 
        $isGroup = $item instanceof \Illuminate\Support\Collection;
        $first = $isGroup ? $item->first() : $item;
        $isBulk = $first->batch_id ? true : false;
        $uniqueId = $type . '-' . ($isGroup ? str_replace('single_', '', $key) : $first->id);
    @endphp

    <div class="accordion-item border-secondary mb-3 bg-black rounded overflow-hidden shadow-lg">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed bg-black text-white py-4 px-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#col-{{ $uniqueId }}">
                <div class="row w-100 align-items-center g-0">
                    
                    {{-- 1. Identification --}}
                    <div class="col-md-4 text-start">
                        <div class="fw-bold text-white small uppercase">
                            {{ ($isBulk && $isGroup) ? $first->organization_name : $first->patient_name }}
                        </div>
                        
                        {{-- NEW: SEX AND AGE BELOW NAME --}}
                        @if(!($isBulk && $isGroup))
                            <div class="text-neon fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                {{ strtoupper($first->patient_sex) }} | {{ $first->patient_age }} YRS OLD
                            </div>
                        @else
                            <div class="text-secondary fw-bold" style="font-size: 0.65rem;">
                                {{ $item->count() }} PATIENTS IN BATCH
                            </div>
                        @endif

                        {{-- Subtle Labels for Staff Only --}}
                        @if(isset($is_staff) && $is_staff)
                            <div class="mt-2" style="font-size: 0.7rem; opacity: 1; letter-spacing: 1px;">
                                @if($isBulk)
                                    <span class="text-info border border-info px-1 rounded">BULK BATCH</span>
                                @elseif($first->dependent_id)
                                    <span class="text-white border border-white px-1 rounded">DEPENDENT</span>
                                @else
                                    <span class="text-neon border border-neon px-1 rounded">INDIVIDUAL</span>
                                @endif
                                <span class="ms-2">REF: #{{ $first->id }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- 2. Schedule --}}
                    <div class="col-md-4 text-center">
                        <div class="small text-white-50">{{ $first->appointment_date->format('M d, Y') }}</div>
                        <div class="text-neon fw-bold small">
                            @if($isBulk && $isGroup) <i class="bi bi-calendar-range me-1"></i> MULTIPLE SLOTS
                            @else <i class="bi bi-clock me-1"></i> {{ date('h:i A', strtotime($first->time_slot)) }} @endif
                        </div>
                    </div>

                    {{-- 3. Status --}}
                    <div class="col-md-4 text-end d-flex justify-content-end align-items-center gap-3">
                        <span class="badge border py-2 px-3 {{ $first->status == 'pending' ? 'text-warning border-warning' : ($first->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}">
                            {{ strtoupper($first->status) }}
                        </span>
                        <i class="bi bi-chevron-down text-secondary fs-5"></i>
                    </div>
                </div>
            </button>
        </h2>

        <div id="col-{{ $uniqueId }}" class="accordion-collapse collapse" data-bs-parent="#acc-{{ $type }}">
            <div class="accordion-body bg-black border-top border-secondary p-4 text-start">
                @if($isBulk && $isGroup)
                    {{-- BATCH SUMMARY --}}
                    <div class="p-3 bg-dark border border-secondary rounded mb-3 d-flex justify-content-between align-items-center shadow-sm">
                        <div class="text-white small fw-bold">ORGANIZATION: <span class="text-neon">{{ strtoupper($first->organization_name) }}</span></div>
                        <div class="text-neon fw-bold small">BATCH TOTAL: ₱{{ number_format($item->sum(fn($a) => $a->totalPrice()), 2) }}</div>
                    </div>

                    <div class="table-responsive rounded border border-secondary">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="bg-black text-secondary small uppercase">
                                <tr style="font-size: 0.65rem;">
                                    <th class="ps-3 py-3">Patient</th>
                                    <th>Schedule</th>
                                    <th>Tests & Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($item as $subApp)
                                <tr class="border-secondary border-opacity-25">
                                    <td class="ps-3">
                                        <div class="text-white fw-bold small">{{ strtoupper($subApp->patient_name) }}</div>
                                        <div class="text-secondary" style="font-size: 0.6rem;">{{ $subApp->patient_sex }} | {{ $subApp->patient_age }} YRS</div>
                                    </td>
                                    <td>
                                        <div class="small text-white-50">{{ $subApp->appointment_date->format('M d') }}</div>
                                        <div class="text-neon small fw-bold">{{ date('h:i A', strtotime($subApp->time_slot)) }}</div>
                                    </td>
                                    <td>
                                        <div class="text-white opacity-75" style="font-size: 0.65rem;">{{ $subApp->services->count() }} Test(s)</div>
                                        <div class="text-neon fw-bold small">₱{{ number_format($subApp->totalPrice(), 2) }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge border py-1 px-2 {{ $subApp->status == 'pending' ? 'text-warning border-warning' : ($subApp->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}" style="font-size: 0.55rem;">{{ strtoupper($subApp->status) }}</span>
                                    </td>
                                    <td class="text-end pe-3">@include('appointments.partials.actions', ['app' => $subApp])</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- SINGLE VIEW --}}
                    <div class="row g-4">
                        <div class="col-md-7 border-end border-secondary border-opacity-25">
                            <h6 class="text-neon small fw-bold uppercase mb-3">Included Tests</h6>
                            <ul class="list-group list-group-flush border border-secondary rounded">
                                @foreach($first->services as $service)
                                <li class="list-group-item bg-black border-secondary text-white small d-flex justify-content-between">
                                    <span>{{ strtoupper($service->name) }}</span>
                                    <span class="text-secondary">₱{{ number_format($service->price, 2) }}</span>
                                </li>
                                @endforeach
                                <li class="list-group-item bg-dark border-secondary text-neon fw-bold d-flex justify-content-between">
                                    <span>TOTAL AMOUNT</span>
                                    <span>₱{{ number_format($first->totalPrice(), 2) }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-5">
                            <h6 class="text-neon small fw-bold uppercase mb-3">Inquiry Details</h6>
                            <div class="p-3 bg-dark rounded border border-secondary mb-3">
                                <small class="text-secondary fw-bold d-block mb-1 uppercase" style="font-size: 0.6rem;">Address:</small>
                                <div class="text-white small mb-3">{{ $first->patient_address }}</div>
                                @if(isset($is_staff) && $is_staff)
                                    <small class="text-secondary fw-bold d-block mb-1 uppercase" style="font-size: 0.6rem;">Booked By:</small>
                                    <div class="text-white fw-bold small">{{ strtoupper($first->user->name) }}</div>
                                @endif
                            </div>
                            @include('appointments.partials.actions', ['app' => $first])
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="card p-5 text-center text-secondary border-secondary border-dashed bg-transparent small italic">No records found.</div>
    @endforelse
</div>