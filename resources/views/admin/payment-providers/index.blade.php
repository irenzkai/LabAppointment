@extends('layouts.app')

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

                <div class="mb-4">
                    <label class="small text-secondary fw-bold mb-1 uppercase">Payment QR Code Scan</label>
                    <input type="file" name="qr_code" class="form-control" accept="image/png, image/jpeg, image/jpg" required>
                    @error('qr_code')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase">Configure Gateway</button>
            </form>
        </div>
    </div>

    {{-- RIGHT PANE: GATEWAY DIRECTORY --}}
    <div class="col-lg-8">
        <div class="card p-4 border-secondary bg-card shadow-lg h-100">
            <h5 class="text-main fw-bold mb-4 uppercase small">Active Gateways</h5>

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
                            
                            {{-- FIXED: Added high-contrast, theme-appropriate badge layouts for ACTIVE and INACTIVE states --}}
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
                            <form action="{{ route('admin.payment-providers.toggle', $provider->id) }}" method="POST" class="flex-grow-1 m-0">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-custom w-100 py-1.5 px-2.5 fw-bold text-uppercase {{ $provider->is_active ? 'btn-outline-secondary' : 'btn-accent' }}" style="font-size: 0.75rem;">
                                    {{ $provider->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            
                            {{-- Edit modal trigger button --}}
                            <button class="btn btn-sm btn-outline-secondary py-1.5 px-2.5" data-bs-toggle="modal" data-bs-target="#editProviderModal{{ $provider->id }}" title="Edit Details">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <form action="{{ route('admin.payment-providers.destroy', $provider->id) }}" method="POST" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-1.5 px-2.5" onclick="return confirm('Delete this payment gateway?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
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
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                <div class="mb-0">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Replace QR Code (Optional)</label>
                                    <input type="file" name="qr_code" class="form-control" accept="image/png, image/jpeg, image/jpg">
                                </div>
                            </div>
                            <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">Save Changes</button>
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
function zoomQR(qrSrc) {
    if (qrSrc) {
        document.getElementById('lightbox_qr_img').src = qrSrc;
        document.getElementById('qr_lightbox').classList.remove('d-none');
        document.getElementById('qr_lightbox').classList.add('d-flex');
    }
}

function closeQRLightbox() {
    document.getElementById('qr_lightbox').classList.add('d-none');
    document.getElementById('qr_lightbox').classList.remove('d-flex');
}
</script>
@endsection