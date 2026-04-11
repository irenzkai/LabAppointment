<div class="accordion" id="bulkAccordion">
    @forelse($groups as $batchId => $apps)
        @php 
            $first = $apps->first(); 
            $batchGrandTotal = $apps->sum(fn($a) => $a->totalPrice());
        @endphp
        
        <div class="accordion-item border-info mb-1 bg-black rounded overflow-hidden shadow-lg" style="border-width: 1px;">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed bg-black text-white py-4 px-4 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#batch-{{ $batchId }}">
                    <div class="row w-100 align-items-center g-0">
                        <div class="col-md-5 text-start">
                            <div class="fw-bold text-white fs-5 uppercase">{{ $first->organization_name }}</div>
                            <div class="d-flex gap-3 mt-1">
                                <small class="text-secondary fw-bold uppercase" style="font-size: 0.8rem;">Count: {{ $apps->count() }} patients</small>
                                <small class="text-neon fw-bold uppercase" style="font-size: 0.8rem;">Batch Total: ₱{{ number_format($batchGrandTotal, 2) }}</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="text-secondary small fw-bold mb-1">DATE SUBMITTED</div>
                            <div class="text-white small fw-bold">{{ $first->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="col-md-3 text-end"><i class="bi bi-chevron-down text-info fs-4"></i></div>
                    </div>
                </button>
            </h2>

            <div id="batch-{{ $batchId }}" class="accordion-collapse collapse" data-bs-parent="#bulkAccordion">
                <div class="accordion-body bg-black border-top border-info p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead class="bg-dark text-secondary small uppercase" style="font-size: 0.8rem; letter-spacing: 1px;">
                                <tr>
                                    <th class="ps-4">Patient Details</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th class="text-end">Patient Total</th>
                                    <th class="text-end pe-4">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($apps as $app)
                                <tr class="border-secondary border-opacity-25">
                                    <td class="ps-4">
                                        <div class="fw-bold text-white">{{ strtoupper($app->patient_name) }}</div>
                                        <div class="text-neon fw-bold" style="font-size: 0.8rem;">{{ strtoupper($app->patient_sex) }} | {{ $app->patient_age }} YRS OLD</div>
                                    </td>
                                    <td>
                                        <div class="small text-white">{{ $app->appointment_date->format('M d, Y') }}</div>
                                        <div class="text-neon small fw-bold">{{ date('h:i A', strtotime($app->time_slot)) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge border py-1 px-2 {{ $app->status == 'pending' ? 'text-warning border-warning' : ($app->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}" style="font-size: 0.8rem;">{{ strtoupper($app->status) }}</span>
                                    </td>
                                    <td class="text-end text-white fw-bold small">₱{{ number_format($app->totalPrice(), 2) }}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn-custom btn-outline-neon btn-sm py-1 px-3" data-bs-toggle="collapse" data-bs-target="#details-{{ $app->id }}">VIEW TESTS</button>
                                    </td>
                                </tr>
                                <tr id="details-{{ $app->id }}" class="collapse bg-dark bg-opacity-50 text-start">
                                    <td colspan="5" class="p-4 border-0">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <h6 class="text-neon small fw-bold uppercase mb-3 border-bottom border-secondary pb-2">Tests Requested for {{ $app->patient_name }}</h6>
                                                <div class="p-1 rounded bg-dark border border-secondary">
                                                    @foreach($app->services as $s)
                                                        <div class="small text-white d-flex justify-content-between p-2 border-bottom border-secondary border-opacity-25">
                                                            <span>{{ strtoupper($s->name) }}</span>
                                                            <span class="text-white">₱{{ number_format($s->price, 2) }}</span>
                                                        </div>
                                                    @endforeach
                                                    <div class="d-flex justify-content-between p-2 mt-1 bg-dark text-neon fw-bold small"><span>SUBTOTAL</span><span>₱{{ number_format($app->totalPrice(), 2) }}</span></div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">@include('appointments.partials.actions', ['app' => $app])</div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card p-5 text-center text-secondary border-dashed border-secondary small italic">No bulk records found.</div>
    @endforelse
</div>