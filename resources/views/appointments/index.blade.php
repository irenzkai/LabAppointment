@extends('layouts.app')

@section('content')
<h3 class="text-white mb-4">Manage Appointments</h3>

<div class="card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    @can('isStaff') <th>Patient Info</th> @endcan
                    <th>Service</th>
                    <th>Date</th>
                    <th>Status</th>
                    @can('isStaff') <th>Actions</th> @endcan
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $app)
                <tr>
                    @can('isStaff')
                        <td>
                            <div class="fw-bold text-white">{{ $app->user->name }}</div>
                            <small class="text-secondary">{{ $app->user->phone }} | {{ $app->user->sex }}</small>
                        </td>
                    @endcan
                    
                    <td class="text-white">{{ $app->service->name }}</td>
                    
                    <td class="text-secondary">{{ $app->appointment_date->format('M d, Y') }}</td>
                    
                    <td>
                        <!-- Flex Container to hold Badge and Button side-by-side -->
                        <div class="d-flex align-items-center gap-2">
                            <!-- Status Badge -->
                            <span class="badge border {{ $app->status == 'pending' ? 'text-warning border-warning' : ($app->status == 'approved' ? 'text-success border-success' : 'text-danger border-danger') }}">
                                {{ strtoupper($app->status) }}
                            </span>

                            <!-- Resubmit Button (Only shows if status is returned and user owns the appointment) -->
                            @if($app->status == 'returned' && Auth::user()->id == $app->user_id)
                                <button class="btn btn-sm btn-primary fw-bold py-0 px-2" style="font-size: 0.75rem;"
                                        data-bs-toggle="modal" data-bs-target="#resubmitModal{{$app->id}}">
                                    Resubmit
                                </button>

                                <!-- MODAL: Resubmit -->
                                <div class="modal fade" id="resubmitModal{{$app->id}}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="{{ route('appointments.update', $app->id) }}" method="POST" class="modal-content border-primary shadow-lg">
                                            @csrf 
                                            @method('PUT')
                                            <div class="modal-header bg-dark border-primary">
                                                <h5 class="modal-title text-white">Resubmit Appointment</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body bg-black text-start">
                                                <p class="text-secondary small">Update your appointment date to resubmit for approval.</p>
                                                <div class="mb-3">
                                                    <label class="text-white small mb-1">New Appointment Date</label>
                                                    <input type="date" name="appointment_date" class="form-control" 
                                                        value="{{ $app->appointment_date->format('Y-m-d') }}" required 
                                                        min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-dark border-primary">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary px-4">Resubmit Now</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Reason Display (Appears below the horizontal row) -->
                        @if($app->return_reason)
                            <div class="mt-2 p-2 rounded border border-danger bg-dark" style="font-size: 0.85rem;">
                                <span class="text-danger fw-bold">Return Reason:</span>
                                <div class="text-white opacity-75 italic">{{ $app->return_reason }}</div>
                            </div>
                        @endif
                    </td>

                    @can('isStaff')
                    <td>
                        <div class="btn-group">
                            <!-- APPROVE BUTTON -->
                            <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-sm btn-success fw-bold">Approve</button>
                            </form>

                            <!-- RETURN BUTTON (Triggers Staff Modal) -->
                            <button type="button" class="btn btn-sm btn-outline-danger fw-bold ms-1" 
                                    data-bs-toggle="modal" data-bs-target="#returnModal{{$app->id}}">
                                Return
                            </button>
                        </div>

                        <!-- MODAL: Return Reason (For Staff) -->
                        <div class="modal fade" id="returnModal{{$app->id}}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('appointments.status', $app->id) }}" method="POST" class="modal-content border-danger shadow-lg">
                                    @csrf @method('PATCH')
                                    <div class="modal-header bg-dark border-danger">
                                        <h5 class="modal-title text-danger">Return Appointment</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body bg-black text-start">
                                        <input type="hidden" name="status" value="returned">
                                        <div class="mb-3">
                                            <label class="text-white small mb-1">Reason for Return (Required)</label>
                                            <textarea name="return_reason" class="form-control" rows="3" required placeholder="Tell the patient why this is being returned..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-dark border-danger">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger fw-bold px-4">Confirm Return</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                    @endcan
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection