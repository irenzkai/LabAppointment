@extends('layouts.app')

@section('title', 'Payment Gateways')

@section('content')
<div class="row g-4 text-start animate-page">
    <div class="col-12 mb-2">
        <div class="d-flex justify-content-between align-items-end border-bottom pb-3" style="border-color: var(--border-color) !important;">
            <div>
                <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">Manage Payment Gateways</h2>
                <p class="text-secondary small mb-0">Configure cashless providers, corporate logotypes, and payment collect QR scans.</p>
            </div>
        </div>
    </div>

    {{-- LEFT PANE: CONFIGURATION FORM --}}
    <div class="col-lg-4">
        <div class="card p-4 border-secondary bg-card shadow-lg">
            <h5 class="text-main fw-bold mb-3 uppercase small">Configure Provider</h5>
            
            <form action="{{ route('admin.payment-providers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Provider Name</label>
                    <input type="text" name="name" class="form-control uppercase" placeholder="e.g. GCash, Maya" value="{{ old('name') }}" required>
                    @error('name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Provider Logo (Optional)</label>
                    <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg, image/jpg">
                    @error('logo')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Payment QR Code Scan</label>
                    <input type="file" name="qr_code" class="form-control" accept="image/png, image/jpeg, image/jpg" required>
                    @error('qr_code')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Action Audit Logger --}}
                <div class="mb-3">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Reason for Configuration</label>
                    <select name="reason" id="add_reason_select" class="form-select" onchange="toggleReasonField('add', this)" required>
                        <option value="" disabled selected>-- Select a valid justification --</option>
                        <option value="Adding newly supported cashless payment channel">Adding newly supported cashless payment channel</option>
                        <option value="Temporary/promo seasonal merchant channel setup">Temporary/promo seasonal payment merchant setup</option>
                        <option value="Re-configuring merchant gateway parameters">Re-configuring merchant gateway parameters</option>
                        <option value="Others">Others (Specify below)</option>
                    </select>
                </div>
                <div id="add_custom_reason_wrapper" class="mb-4 d-none">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Specify Custom Reason</label>
                    <textarea name="custom_reason" id="add_custom_reason" class="form-control" rows="2" placeholder="Explain the configuration justification..."></textarea>
                </div>

                <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase">Configure Gateway</button>
            </form>
        </div>
    </div>

    {{-- RIGHT PANE: GATEWAY DIRECTORY --}}
    <div class="col-lg-8">
        <div class="card p-4 border-secondary bg-card shadow-lg h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-main fw-bold mb-0 uppercase small" id="gateway-panel-title">Active Gateways</h5>
                
                @if(count($archivedProviders) > 0)
                <ul class="nav nav-pills" id="gatewayTabs" role="tablist">
                    <li class="nav-item me-2">
                        <button class="nav-link active fs-x-small fw-800 uppercase px-3 py-1.5" id="tab-btn-active" data-bs-toggle="pill" data-bs-target="#tab-active-gateways" type="button" role="tab">
                            Active
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fs-x-small fw-800 uppercase px-3 py-1.5 text-warning border-warning" id="tab-btn-archived" data-bs-toggle="pill" data-bs-target="#tab-archived-gateways" type="button" role="tab">
                            Archived ({{ count($archivedProviders) }})
                        </button>
                    </li>
                </ul>
                @endif
            </div>

            <div class="tab-content" id="gatewayTabsContent">
                {{-- TAB A: ACTIVE GATEWAYS --}}
                <div class="tab-pane fade show active" id="tab-active-gateways" role="tabpanel">
                    <div class="row g-3">
                        @forelse($providers as $provider)
                        <div class="col-md-6 col-12">
                            {{-- Translucent green-themed card container --}}
                            <div class="border rounded p-3 text-start" style="background-color: rgba(25, 211, 140, 0.05); border-color: rgba(25, 211, 140, 0.15) !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($provider->logo)
                                        <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" style="height: 24px; object-fit: contain;">
                                        @else
                                        <i class="bi bi-wallet2 text-accent"></i>
                                        @endif
                                        <span class="fw-bold text-main small uppercase" style="color: var(--text-main) !important;">{{ $provider->name }}</span>
                                    </div>
                                    
                                    @if($provider->is_active)
                                    <span class="badge px-2 py-1 small" style="background-color: rgba(25, 211, 140, 0.15); color: var(--brand-accent) !important; border: 1px solid rgba(25, 211, 140, 0.25);">
                                        ACTIVE
                                    </span>
                                    @else
                                    <span class="badge px-2 py-1 small" style="background-color: rgba(108, 117, 125, 0.15); color: var(--text-muted) !important; border: 1px solid rgba(108, 117, 125, 0.25);">
                                        INACTIVE
                                    </span>
                                    @endif
                                </div>

                                {{-- Click to zoom on QR code thumbnails --}}
                                <div class="text-center bg-white p-2 rounded mb-3" style="max-width: 140px; margin: 0 auto; cursor: zoom-in;" onclick="zoomQR('{{ Storage::url($provider->qr_code) }}')" title="Click to view full screen">
                                    <img src="{{ Storage::url($provider->qr_code) }}" alt="QR" style="height: 120px; width: 120px; object-fit: contain;">
                                </div>

                                <div class="d-flex gap-2 align-items-center">
                                    {{-- State Toggle Button (Modal trigger) --}}
                                    <button class="btn btn-custom flex-grow-1 py-1.5 px-2.5 fw-bold text-uppercase {{ $provider->is_active ? 'btn-outline-secondary' : 'btn-accent' }}" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#toggleProviderModal{{ $provider->id }}" title="Toggle Active State">
                                        {{ $provider->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    
                                    {{-- Edit Modal Trigger Button --}}
                                    <button class="btn btn-sm btn-outline-secondary py-1.5 px-2.5" data-bs-toggle="modal" data-bs-target="#editProviderModal{{ $provider->id }}" title="Edit Details">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    {{-- Archive Trigger Button (Modal trigger) --}}
                                    <button type="button" class="btn btn-sm btn-outline-danger py-1.5 px-2.5" data-bs-toggle="modal" data-bs-target="#archiveProviderModal{{ $provider->id }}" title="Archive Gateway">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- DYNAMIC GATEWAY STATE TOGGLE MODAL --}}
                        <div class="modal fade" id="toggleProviderModal{{ $provider->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                                <form action="{{ route('admin.payment-providers.toggle', $provider->id) }}" method="POST" class="modal-content border-secondary bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                                        <h5 class="modal-title text-accent fw-bold uppercase small">
                                            {{ $provider->is_active ? 'Disable' : 'Enable' }} Gateway?
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p class="small text-muted mb-4">
                                            You are about to toggle the status of <strong>{{ $provider->name }}</strong> to <strong>{{ $provider->is_active ? 'INACTIVE' : 'ACTIVE' }}</strong>. Please provide a reason to update the system audit log.
                                        </p>
                                        <div class="mb-3">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Reason for State Toggle</label>
                                            <select name="reason" id="toggle_reason_select_{{ $provider->id }}" class="form-select" onchange="toggleReasonField('toggle_{{ $provider->id }}', this)" required>
                                                <option value="" disabled selected>-- Select a valid justification --</option>
                                                <option value="Temporary maintenance on the cashless provider system">Temporary maintenance on payment provider system</option>
                                                <option value="Merchant requests deactivation of payment method">Merchant requests deactivation of payment method</option>
                                                <option value="Gateway connectivity issues detected">Gateway connectivity issues detected</option>
                                                <option value="Others">Others (Specify below)</option>
                                            </select>
                                        </div>
                                        <div id="toggle_{{ $provider->id }}_custom_reason_wrapper" class="mb-0 d-none">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Specify Custom Reason</label>
                                            <textarea name="custom_reason" id="toggle_{{ $provider->id }}_custom_reason" class="form-control" rows="2" placeholder="Explain the deactivation/activation details..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">Confirm Toggle</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- DYNAMIC GATEWAY EDIT MODAL --}}
                        <div class="modal fade" id="editProviderModal{{ $provider->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                                <form action="{{ route('admin.payment-providers.update', $provider->id) }}" method="POST" enctype="multipart/form-data" class="modal-content border-secondary bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                                        <h5 class="modal-title text-accent fw-bold uppercase small">Edit Provider: {{ $provider->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Provider Name</label>
                                            <input type="text" name="name" class="form-control uppercase" value="{{ old('name', $provider->name) }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Replace Logo (Optional)</label>
                                            <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg, image/jpg">
                                        </div>
                                        <div class="mb-3">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Replace QR Code (Optional)</label>
                                            <input type="file" name="qr_code" class="form-control" accept="image/png, image/jpeg, image/jpg">
                                        </div>
                                        <div class="mb-3">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Reason for Editing</label>
                                            <select name="reason" id="edit_reason_select_{{ $provider->id }}" class="form-select" onchange="toggleReasonField('edit_{{ $provider->id }}', this)" required>
                                                <option value="" disabled selected>-- Select a valid justification --</option>
                                                <option value="Updating QR code asset with new merchant details">Updating QR code asset with new merchant details</option>
                                                <option value="Updating gateway branding/logo asset">Updating gateway branding/logo asset</option>
                                                <option value="Correction of typos/configuration mistake">Correction of typos/configuration mistake</option>
                                                <option value="Others">Others (Specify below)</option>
                                            </select>
                                        </div>
                                        <div id="edit_{{ $provider->id }}_custom_reason_wrapper" class="mb-0 d-none">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Specify Custom Reason</label>
                                            <textarea name="custom_reason" id="edit_{{ $provider->id }}_custom_reason" class="form-control" rows="2" placeholder="Explain the edit details..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ARCHIVE GATEWAY MODAL (Soft Delete) --}}
                        <div class="modal fade" id="archiveProviderModal{{ $provider->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                                <form action="{{ route('admin.payment-providers.destroy', $provider->id) }}" method="POST" class="modal-content border-warning bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                                    @csrf
                                    @method('DELETE')
                                    <div class="modal-header border-warning bg-warning bg-opacity-10 py-3">
                                        <h5 class="modal-title text-warning fw-bold uppercase small">Archive Gateway?</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p class="text-secondary small mb-4">You are about to archive the payment gateway <strong>{{ $provider->name }}</strong>. Its configuration and logo assets will be retained so you can reactivate it at any time. Please provide a reason to update the system audit log.</p>
                                        
                                        <div class="mb-3">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Reason for Archiving</label>
                                            <select name="reason" id="archive_reason_select_{{ $provider->id }}" class="form-select" onchange="toggleReasonField('archive_{{ $provider->id }}', this)" required>
                                                <option value="" disabled selected>-- Select a valid justification --</option>
                                                <option value="Permanent termination of service agreement">Permanent termination of service agreement</option>
                                                <option value="Merchant account closed or disabled">Merchant account closed or disabled</option>
                                                <option value="Consolidating with alternative payment channels">Consolidating with alternative payment channels</option>
                                                <option value="Others">Others (Specify below)</option>
                                            </select>
                                        </div>
                                        <div id="archive_{{ $provider->id }}_custom_reason_wrapper" class="mb-0 d-none">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Specify Custom Reason</label>
                                            <textarea name="custom_reason" id="archive_{{ $provider->id }}_custom_reason" class="form-control" rows="2" placeholder="Explain the archiving reasons..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning py-2 px-4 fw-bold uppercase text-dark">Archive Gateway</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center py-5 text-secondary italic">
                            <i class="bi bi-qr-code-scan d-block fs-2 mb-2"></i>
                            No payment gateways configured yet.
                        </div>
                        @endforelse
                    </div>
                </div>

                {{-- TAB B: ARCHIVED GATEWAYS --}}
                @if(count($archivedProviders) > 0)
                <div class="tab-pane fade" id="tab-archived-gateways" role="tabpanel">
                    <div class="row g-3">
                        @foreach($archivedProviders as $provider)
                        <div class="col-md-6 col-12">
                            <div class="border border-dashed border-warning rounded p-3 text-start opacity-75" style="background-color: rgba(255, 193, 7, 0.03); border-color: rgba(255, 193, 7, 0.25) !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($provider->logo)
                                        <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" style="height: 24px; object-fit: contain;">
                                        @else
                                        <i class="bi bi-wallet2 text-warning"></i>
                                        @endif
                                        <span class="fw-bold text-warning small uppercase">{{ $provider->name }}</span>
                                    </div>
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 small border border-warning border-opacity-25">ARCHIVED</span>
                                </div>
                                
                                <div class="text-center bg-white p-2 rounded mb-3" style="max-width: 140px; margin: 0 auto; cursor: zoom-in;" onclick="zoomQR('{{ Storage::url($provider->qr_code) }}')" title="Click to view full screen">
                                    <img src="{{ Storage::url($provider->qr_code) }}" alt="QR" style="height: 120px; width: 120px; object-fit: contain;">
                                </div>

                                <button class="btn btn-outline-warning btn-sm w-100 fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#restoreProviderModal{{ $provider->id }}">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivate / Restore
                                </button>
                            </div>
                        </div>

                        {{-- RESTORE GATEWAY MODAL --}}
                        <div class="modal fade" id="restoreProviderModal{{ $provider->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                                <form action="{{ route('admin.payment-providers.restore', $provider->id) }}" method="POST" class="modal-content border-success bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                                    @csrf
                                    <div class="modal-header border-success bg-success bg-opacity-10 py-3">
                                        <h5 class="modal-title text-success fw-bold uppercase small">Restore Gateway?</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p class="text-secondary small mb-4">You are about to reactivate and restore the payment gateway <strong>{{ $provider->name }}</strong>. Please provide a reason to update the system audit log.</p>
                                        
                                        <div class="mb-3">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Reason for Restoration</label>
                                            <select name="reason" id="restore_reason_select_{{ $provider->id }}" class="form-select" onchange="toggleReasonField('restore_{{ $provider->id }}', this)" required>
                                                <option value="" disabled selected>-- Select a valid justification --</option>
                                                <option value="Re-activated service agreement with provider">Re-activated service agreement with provider</option>
                                                <option value="Resolved technical/connectivity merchant gateway issues">Resolved technical/connectivity merchant gateway issues</option>
                                                <option value="Re-opening promo payment merchant window">Re-opening promo payment merchant window</option>
                                                <option value="Others">Others (Specify below)</option>
                                            </select>
                                        </div>
                                        <div id="restore_{{ $provider->id }}_custom_reason_wrapper" class="mb-0 d-none">
                                            <label class="small text-secondary fw-bold mb-1 uppercase">Specify Custom Reason</label>
                                            <textarea name="custom_reason" id="restore_{{ $provider->id }}_custom_reason" class="form-control" rows="2" placeholder="Explain the restoration reason..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                                        <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success py-2 px-4 fw-bold uppercase text-dark">Restore Gateway</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- FULLSCREEN QR LIGHTBOX OVERLAY --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox()">
    <div class="text-center p-3 animate-fade-in">
        <img src="" id="lightbox_qr_img" alt="Zoomed QR" class="img-fluid rounded border border-secondary p-3 bg-white" style="max-height: 75vh; max-width: 90vw; object-fit: contain;">
        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the screen to close preview</p>
    </div>
</div>

<script>
window.zoomQR = function(qrSrc) {
    if (qrSrc) {
        document.getElementById('lightbox_qr_img').src = qrSrc;
        document.getElementById('qr_lightbox').classList.remove('d-none');
        document.getElementById('qr_lightbox').classList.add('d-flex');
    }
}

window.closeQRLightbox = function() {
    document.getElementById('qr_lightbox').classList.add('d-none');
    document.getElementById('qr_lightbox').classList.remove('d-flex');
}

// Toggle reason text area visibility and requirements dynamically
window.toggleReasonField = function(prefix, select) {
    const wrapper = document.getElementById(`${prefix}_custom_reason_wrapper`);
    const textarea = document.getElementById(`${prefix}_custom_reason`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Toggle panel title based on active tab view
    document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(button => {
        button.addEventListener('shown.bs.tab', event => {
            const titleEl = document.getElementById('gateway-panel-title');
            if (titleEl) {
                if (event.target.id === 'tab-btn-archived') {
                    titleEl.innerText = 'Archived Gateways';
                } else {
                    titleEl.innerText = 'Active Gateways';
                }
            }
        });
    });
});
</script>

<style>
.border-dashed { border-style: dashed !important; }
.nav-pills .nav-link {
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    transition: 0.3s;
}
.nav-pills .nav-link.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent);
}
</style>
@endsection