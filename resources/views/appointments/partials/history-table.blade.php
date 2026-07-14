<div class="card p-0 border-secondary overflow-hidden shadow-lg bg-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-black text-secondary x-small uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                <tr>
                    <th class="ps-4">Schedule Date & Time</th>
                    <th>Examinations Requested</th>
                    <th>Total Billing</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $app)
                    @php
                        // Map status to appropriate theme colors
                        $statusColor = match($app->status) {
                            'pending' => 'warning',
                            'approved' => 'info',
                            'tested' => 'info',
                            'encoded' => 'info',
                            'released' => 'accent',
                            'returned' => 'danger',
                            default => 'secondary'
                        };
                    @endphp
                    <tr class="border-secondary border-opacity-10">
                        {{-- Schedule Date & Time --}}
                        <td class="ps-4 py-3">
                            <div class="text-main fw-bold small">
                                <i class="bi bi-calendar-event text-accent me-1"></i> 
                                {{ $app->appointment_date->format('M d, Y') }}
                            </div>
                            <div class="text-muted x-small mt-0.5" style="font-size: 0.65rem;">
                                <i class="bi bi-clock me-1"></i> 
                                {{ date('h:i A', strtotime($app->time_slot)) }}
                            </div>
                        </td>

                        {{-- Examinations Requested --}}
                        <td class="small text-main">
                            {{ $app->services->pluck('name')->implode(', ') }}
                        </td>

                        {{-- Total Billing --}}
                        <td class="small text-main fw-bold">
                            ₱{{ number_format($app->totalPrice(), 2) }}
                        </td>

                        {{-- Status Badge --}}
                        <td>
                            <span class="badge border border-{{ $statusColor }} text-{{ $statusColor == 'accent' ? 'success' : $statusColor }} uppercase x-small" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                {{ $app->status }}
                            </span>
                        </td>

                        {{-- Actions: View Report Link --}}
                        <td class="text-end pe-4">
                            @if($app->status == 'released')
                                <a href="{{ route('appointments.result.access', [$app->id, 'lab', 'preview']) }}" target="_blank" class="btn-custom btn-outline-accent btn-sm py-1.5 px-3" style="font-size: 0.7rem;">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> VIEW REPORT
                                </a>
                            @else
                                <span class="text-muted small italic" style="font-size: 0.7rem;">Processing...</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted small italic">No past appointments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>