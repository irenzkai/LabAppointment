<div class="accordion" id="acc-{{ $type }}">
    @forelse($apps as $key => $item)
    @php 
        $isGroup = $item instanceof \Illuminate\Support\Collection;
        $first = $isGroup ? $item->first() : $item;
        $isBulkBatch = ($first->batch_id && $isGroup);
        $uniqueId = $type . '-' . ($isGroup ? str_replace('single_', '', $key) : $first->id);
    @endphp

    <div class="accordion-item border-secondary mb-3 bg-black rounded overflow-hidden shadow-lg">
        {{-- --- ACCORDION HEADER --- --}}
        <h2 class="accordion-header">
            <button class="accordion-button collapsed bg-black text-white py-4 px-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#col-{{ $uniqueId }}">
                <div class="row w-100 align-items-center g-0">
                    
                    {{-- Column 1: Patient / Entity Info --}}
                    <div class="col-md-4 text-start">
                        <div class="fw-bold text-white uppercase">
                            {{ $isBulkBatch ? $first->organization_name : $first->patient_name }}
                        </div>
                        
                        @if(!$isBulkBatch)
                            <div class="text-neon fw-bold" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                {{ strtoupper($first->patient_sex) }} | {{ $first->patient_age }} YRS OLD
                            </div>
                        @else
                            <div class="text-neon fw-bold" style="font-size: 0.8rem;">
                                {{ $item->count() }} PATIENTS IN BATCH
                            </div>
                        @endif

                        @if(isset($is_staff) && $is_staff)
                            <div class="mt-2" style="font-size: 0.8rem; opacity: 1; letter-spacing: 1px;">
                                @if($first->batch_id) <span class="text-info border border-info px-2 rounded">BULK</span>
                                @elseif($first->dependent_id) <span class="text-white border border-white px-2 rounded">FAMILY</span>
                                @else <span class="text-neon border border-neon px-2 rounded">INDIVIDUAL</span> @endif
                                <span class="ms-2 small text-white">REF: #{{ $first->id }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Column 2: Schedule --}}
                    <div class="col-md-4 text-center">
                        <div class="small text-white">{{ $first->appointment_date->format('M d, Y') }}</div>
                        <div class="text-neon fw-bold small">
                            @if($isBulkBatch) <i class="bi bi-calendar-range me-1"></i> MULTIPLE SLOTS
                            @else <i class="bi bi-clock me-1"></i> {{ date('h:i A', strtotime($first->time_slot)) }} @endif
                        </div>
                    </div>

                    {{-- Column 3: Status --}}
                    <div class="col-md-4 text-end d-flex justify-content-end align-items-center gap-3">
                        @php
                            $badgeClass = match($first->status) {
                                'pending' => 'text-warning border-warning',
                                'approved' => 'text-success border-success',
                                'tested' => 'text-info border-info',
                                'released' => 'text-neon border-success',
                                'returned' => 'text-danger border-danger',
                                default => 'text-secondary border-secondary'
                            };
                        @endphp
                        <span class="badge border py-2 px-3 {{ $badgeClass }}">
                            {{ strtoupper($first->status) }}
                        </span>
                        <i class="bi bi-chevron-down text-secondary fs-5"></i>
                    </div>
                </div>
            </button>
        </h2>

        {{-- --- ACCORDION BODY --- --}}
        <div id="col-{{ $uniqueId }}" class="accordion-collapse collapse" data-bs-parent="#acc-{{ $type }}">
            <div class="accordion-body bg-black border-top border-secondary p-4 text-start">

                {{-- A. ESTIMATED TIME ALERT (For Patients) --}}
                @if($first->status == 'tested' && !Auth::user()->isStaff())
                    <div class="alert bg-dark border-info text-info mb-4 d-flex align-items-center shadow-sm">
                        <i class="bi bi-hourglass-split fs-3 me-3"></i>
                        <div>
                            <div class="fw-bold uppercase small">Processing Results</div>
                            <div class="smaller">
                                @if($first->result_estimated_at)
                                    Estimated ready by: {{ $first->result_estimated_at->format('h:i A') }} ({{ $first->result_estimated_at->diffForHumans() }})
                                @else
                                    Sampling completed. Please wait while we process your results.
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- B. VIEW RESULTS BUTTON (If Released) --}}
                @if($first->status == 'released' && $first->result)
                    <div class="dropdown w-100 mb-3">
                        <button class="btn-custom btn-neon w-100 py-3 fw-bold dropdown-toggle shadow" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                {{-- ADD THIS: Prevents Popper from moving the menu --}}
                                data-bs-display="static" 
                                aria-expanded="false">
                            <i class="bi bi-download me-2"></i> GET DIGITAL RESULTS
                        </button>
                        
                        <ul class="dropdown-menu bg-black border-neon w-100 dropdown-menu-scrollable shadow-lg">
                            <li class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-2 bg-dark">
                                <small class="text-white fw-bold uppercase" style="font-size: 1rem;">Available Documents:</small>
                            </li>

                            @foreach(($first->result->included_reports ?? []) as $report)
                                <li>
                                    <a class="dropdown-item text-white py-2 d-flex align-items-center" href="{{ route('appointments.download', [$first->id, $report]) }}">
                                        <i class="bi bi-file-earmark-pdf-fill me-2 text-neon"></i> 
                                        <span class="small fw-bold">DOWNLOAD {{ strtoupper(str_replace('_', ' ', $report)) }}</span>
                                    </a>
                                </li>
                            @endforeach
                            
                            @if($first->result->xray_image)
                                <li>
                                    <a class="dropdown-item text-white py-2 d-flex align-items-center" href="{{ route('appointments.download', [$first->id, 'xray']) }}">
                                        <i class="bi bi-image-fill me-2 text-neon"></i> 
                                        <span class="small fw-bold">DOWNLOAD X-RAY SCAN</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
                
                @if($isBulkBatch)
                    {{-- BULK BATCH VIEW --}}
                    <div class="table-responsive rounded border border-secondary overflow-hidden">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="bg-black text-secondary small uppercase">
                                <tr style="font-size: 0.8rem;">
                                    <th class="ps-3">Patient Info</th>
                                    <th>Schedule</th>
                                    <th style="width: 25%;">Tests and Payment</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($item as $subApp)
                                <tr class="border-secondary border-opacity-25">
                                    {{-- 1. Patient Details --}}
                                    <td class="ps-3 py-3">
                                        <div class="text-white fw-bold small">{{ strtoupper($subApp->patient_name) }}</div>
                                        <div class="text-neon fw-bold" style="font-size: 0.7rem;">
                                            {{ strtoupper($subApp->patient_sex) }} | {{ $subApp->patient_age }} YRS OLD
                                        </div>
                                    </td>

                                    {{-- 2. Schedule (Date + Time) --}}
                                    <td>
                                        <div class="text-white small">{{ $subApp->appointment_date->format('M d, Y') }}</div>
                                        <div class="text-neon small fw-bold">{{ date('h:i A', strtotime($subApp->time_slot)) }}</div>
                                    </td>

                                    {{-- 3. TESTS PER PATIENT --}}
                                    <td>
                                        <div class="text-white fw-bold" style="font-size: 0.7rem;">
                                            @foreach($subApp->services as $service)
                                                {{ ($service->name) }}{{ !$loop->last ? ',' : '' }}
                                            @endforeach
                                        </div>
                                        <div class="text-neon fw-bold" style="font-size: 0.7rem;">
                                            SUBTOTAL: ₱{{ number_format($subApp->totalPrice(), 2) }}
                                        </div>
                                    </td>

                                    {{-- 4. Status --}}
                                    <td class="text-center">
                                        <span class="badge border py-1 px-2 {{ $subApp->status == 'pending' ? 'text-warning border-warning' : ($subApp->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}" style="font-size: 0.7rem;">
                                            {{ strtoupper($subApp->status) }}
                                        </span>
                                    </td>

                                    {{-- 5. Actions --}}
                                    <td class="text-end pe-3">
                                        @include('appointments.partials.actions', ['app' => $subApp])
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- INDIVIDUAL / FAMILY VIEW --}}
                    <div class="row g-4">
                        <div class="col-md-7 border-end border-secondary border-opacity-25">
                            <h6 class="text-neon small fw-bold uppercase mb-3">Laboratory Request Breakdown</h6>
                            <ul class="list-group list-group-flush border border-secondary rounded bg-black">
                                @foreach($first->services as $service)
                                <li class="list-group-item bg-black border-secondary text-white small d-flex justify-content-between">
                                    <span>{{ strtoupper($service->name) }}</span>
                                    <span class="text-secondary">₱{{ number_format($service->price, 2) }}</span>
                                </li>
                                @endforeach
                                <li class="list-group-item bg-dark border-secondary text-neon fw-bold d-flex justify-content-between">
                                    <span>TOTAL BILLING</span>
                                    <span>₱{{ number_format($first->totalPrice(), 2) }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-5">
                            <h6 class="text-neon small fw-bold uppercase mb-3">Record Information</h6>
                            <div class="p-3 bg-dark bg-opacity-50 rounded border border-secondary mb-3">
                                <small class="text-secondary fw-bold d-block mb-1 uppercase" style="font-size: 0.6rem;">Patient Home Address:</small>
                                <div class="text-white small mb-3">{{ $first->patient_address }}</div>
                                @if(isset($is_staff) && $is_staff)
                                    <small class="text-secondary fw-bold d-block mb-1 uppercase" style="font-size: 0.6rem;">Inquiry Logged By:</small>
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