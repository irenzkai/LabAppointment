@extends('layouts.app')

@section('content')
<h2 class="text-neon fw-bold mb-4 uppercase">Appointment Management</h2>

<div class="card p-0 border-0 shadow-lg" style="background: transparent;">
    <div class="table-responsive" style="overflow: visible;"> {{-- Changed to visible --}}
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-black text-secondary smaller fw-bold">
                <tr>
                    <th class="px-4 py-3">PATIENT / SERVICE</th>
                    <th>DATE & TIME</th>
                    <th>STATUS</th>
                    @can('isStaff') <th class="text-end px-4">ACTIONS</th> @endcan
                </tr>
            </thead>
            <tbody class="text-white">
                @foreach($appointments as $app)
                <tr style="border-bottom: 1px solid var(--border-color); background-color: var(--card);">
                    <td class="px-4 py-3">
                        <div class="fw-bold text-white small">{{ strtoupper($app->user->name) }}</div>
                        <div class="text-neon smaller fw-bold">{{ strtoupper($app->service->name) }}</div>
                    </td>
                    <td>
                        <div class="small">{{ $app->appointment_date->format('M d, Y') }}</div>
                        <div class="text-neon fw-bold small"><i class="bi bi-clock me-1"></i>{{ date('h:i A', strtotime($app->time_slot)) }}</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge border py-2 px-3 {{ $app->status == 'pending' ? 'text-warning border-warning' : ($app->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}">
                                {{ strtoupper($app->status) }}
                            </span>
                            @if($app->status == 'returned' && Auth::id() == $app->user_id)
                                <button class="btn-custom btn-neon py-1 px-2 fw-bold" style="font-size: 0.65rem;" data-bs-toggle="modal" data-bs-target="#resubmitModal{{$app->id}}">RESUBMIT</button>
                            @endif
                        </div>
                        @if($app->return_reason) 
                            <div class="text-danger smaller italic mt-2"><i class="bi bi-info-circle me-1"></i>REASON: {{ $app->return_reason }}</div> 
                        @endif
                    </td>
                    @can('isStaff')
                    <td class="text-end px-4">
                        @if($app->status == 'pending')
                            <div class="btn-group">
                                <form action="{{ route('appointments.status', $app->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn btn-sm btn-success fw-bold px-3 py-2">APPROVE</button>
                                </form>
                                <button class="btn btn-sm btn-outline-danger fw-bold ms-1 px-3 py-2" data-bs-toggle="modal" data-bs-target="#retModal{{$app->id}}">RETURN</button>
                            </div>
                        @endif
                    </td>
                    @endcan
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- MODALS SECTION - MOVED OUTSIDE THE TABLE --}}
@foreach($appointments as $app)
    @can('isStaff')
    {{-- MODAL: Return (Staff) --}}
    <div class="modal fade" id="retModal{{$app->id}}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger bg-black" style="box-shadow: 0 0 20px rgba(255,0,0,0.2);">
                <form action="{{ route('appointments.status', $app->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-header border-danger bg-dark">
                        <h5 class="modal-title text-danger fw-bold uppercase">Return to Patient</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="status" value="returned">
                        <label class="text-secondary smaller fw-bold mb-2 uppercase">Reason for return</label>
                        <textarea name="return_reason" class="form-control bg-dark text-white border-secondary" rows="4" placeholder="Explain what needs to be fixed..." required style="resize: none;"></textarea>
                    </div>
                    <div class="modal-footer border-danger bg-dark">
                        <button type="button" class="btn-custom btn-outline-neon py-2 px-3" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn-custom btn-danger-custom py-2 px-4">CONFIRM RETURN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    @if($app->status == 'returned' && Auth::id() == $app->user_id)
    {{-- MODAL: Resubmit (User) --}}
    <div class="modal fade" id="resubmitModal{{$app->id}}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-neon bg-black">
                <form action="{{ route('appointments.update', $app->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header border-neon bg-dark">
                        <h5 class="modal-title text-neon fw-bold uppercase">Update and Resubmit</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-5 mb-3 border-end border-secondary">
                                <label class="text-secondary smaller fw-bold mb-2">NEW DATE</label>
                                <input type="date" name="appointment_date" class="form-control date-selector" 
                                       data-service="res{{$app->id}}" value="{{ $app->appointment_date->format('Y-m-d') }}" 
                                       required min="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-7">
                                <label class="text-secondary smaller fw-bold mb-2">NEW TIME SLOT</label>
                                <div class="row g-2 time-grid-res{{$app->id}}" style="max-height: 250px; overflow-y: auto;">
                                    @for($i = 0; $i < 24; $i++)
                                        @php $t = sprintf('%02d:00', $i); @endphp
                                        <div class="col-4 time-slot-item" data-hour="{{$i}}">
                                            <input type="radio" class="btn-check" name="time_slot" id="rs{{$app->id}}_{{$i}}" value="{{$t}}" required>
                                            <label class="btn btn-outline-secondary w-100 btn-sm fw-bold" for="rs{{$app->id}}_{{$i}}">{{ date('h:i A', strtotime($t)) }}</label>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-neon bg-dark">
                        <button type="submit" class="btn-custom btn-neon w-100 py-3 fw-bold">RESUBMIT FOR APPROVAL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

@endsection