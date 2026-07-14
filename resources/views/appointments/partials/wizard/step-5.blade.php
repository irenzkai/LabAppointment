<!-- PAGE 5: CHECKOUT & PAYMENT -->
<div class="wiz-section d-none text-start animate-page" id="page-5">
    <div class="mb-4">
        <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 5: Payment & Finalize</h3>
        <p class="text-secondary small">Choose how you would like to settle your laboratory fees.</p>
    </div>

    <div class="row g-4">
        {{-- Payment Method Selection --}}
        <div class="col-12">
            <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Select Payment Method</label>
            <div class="row g-3">
                {{-- Option 1: Cash --}}
                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="Cash" checked>
                    <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="pay_cash">
                        <i class="bi bi-cash-stack fs-1 mb-2"></i>
                        <div class="fw-bold uppercase">Cash on Site</div>
                        <div class="smaller opacity-75 mt-1">Pay at the reception desk upon arrival.</div>
                    </label>
                </div>

                {{-- Option 2: Cashless (Enabled) --}}
                <div class="col-md-6">
                    <input type="radio" class="btn-check" name="payment_method" id="pay_cashless" value="Cashless">
                    <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="pay_cashless">
                        <i class="bi bi-qr-code-scan fs-1 mb-2"></i>
                        <div class="fw-bold uppercase">Online / E-Wallet</div>
                        <div class="smaller opacity-75 mt-1">Scan and pay using digital wallets.</div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Dynamic E-Wallet Selector Grid (Hidden until Cashless is selected) --}}
        <div id="provider_selection_container" class="col-12 d-none mt-4 animate-fade-in">
            <label class="text-accent smaller fw-bold uppercase d-block mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Choose E-Wallet Provider</label>
            <div class="row g-3">
                @if(isset($paymentProviders) && $paymentProviders->count() > 0)
                    @foreach($paymentProviders as $index => $provider)
                    <div class="col-md-4 col-6">
                        <input type="radio" class="btn-check provider-radio" name="payment_provider_id" id="provider_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}">
                        <label class="btn btn-outline-secondary w-100 p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center" for="provider_{{ $provider->id }}">
                            @if($provider->logo)
                                <img src="{{ Storage::url($provider->logo) }}" alt="{{ $provider->name }}" class="mb-2" style="height: 32px; object-fit: contain;">
                            @else
                                <i class="bi bi-wallet2 fs-3 mb-2 text-secondary"></i>
                            @endif
                            <div class="small fw-bold uppercase" style="color: var(--text-main) !important;">{{ $provider->name }}</div>
                        </label>
                    </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="alert alert-clinical text-center p-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                            <span>No active payment gateways are configured. Please pay Cash on Site.</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- QR Code Display Container (With zoom trigger on click) --}}
        <div id="qr_section" class="col-12 d-none animate-fade-in mt-4">
            <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Scan to Pay (<span id="selected_provider_name" class="text-accent"></span>)</h6>
                <div class="d-flex justify-content-center">
                    <div class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" onclick="zoomQR()" title="Click to zoom in">
                        <img src="" id="selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
                    </div>
                </div>
                <p class="text-muted smaller mt-3 mb-0 italic" style="font-size: 0.7rem;"><i class="bi bi-zoom-in me-1 text-accent"></i> Click the QR code image to view it full screen.<br>Please take a screenshot of your successful transaction to present upon arrival.</p>
            </div>
        </div>

        {{-- FIXED: Added proof of payment file uploader container (revealed only for cashless) --}}
        <div id="receipt_upload_container" class="col-12 d-none mt-4 animate-fade-in">
            <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>
            <input type="file" name="payment_receipt" id="in_receipt" class="form-control py-3 shadow-none" accept="image/*, application/pdf">
            <div class="mt-1">
                <small class="text-muted smaller">
                    <i class="bi bi-info-circle me-1"></i> Required: Upload a PDF or image copy of your GCash/Maya transaction receipt to finalize.
                </small>
            </div>
        </div>

        {{-- Clinical Agreements --}}
        <div class="col-12">
            <div class="card border-secondary border-opacity-25 bg-card p-4">
                <div class="form-check text-start">
                    <input class="form-check-input" type="checkbox" id="agree_terms" required>
                    <label class="form-check-label text-main small" for="agree_terms" style="font-size: 0.85rem;">
                        I confirm that all information provided is accurate and I agree to the <a href="#" class="text-accent fw-bold text-decoration-none">Clinical Privacy Policy</a>.
                    </label>
                </div>
                <div class="mt-3 p-3 rounded border border-secondary border-opacity-10 text-start" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                    <h6 class="text-warning fw-bold mb-1 smaller uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Important Reminder:
                    </h6>
                    <p class="text-muted smaller mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                        For Blood Chemistry (FBS, Lipid Profile, etc.), please ensure you have undergone 10-12 hours of fasting for accurate results.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="d-flex gap-2 mt-5">
        <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(4)">
            <i class="bi bi-arrow-left me-2"></i> BACK
        </button>
        <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="final_submit_btn">
            CONFIRM & REGISTER <i class="bi bi-check2-circle ms-2"></i>
        </button>
    </div>
</div>

{{-- FULLSCREEN QR LIGHTBOX OVERLAY --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox()">
    <div class="text-center p-3 animate-fade-in">
        <img src="" id="lightbox_qr_img" alt="Zoomed QR" class="img-fluid rounded border border-secondary p-3 bg-white" style="max-height: 75vh; max-width: 90vw; object-fit: contain;">
        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the screen to close preview</p>
    </div>
</div>

<style>
.cursor-not-allowed {
    cursor: not-allowed !important;
}
#appointmentWizard:invalid #final_submit_btn {
    opacity: 0.5;
    pointer-events: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const payCash = document.getElementById('pay_cash');
    const payCashless = document.getElementById('pay_cashless');
    const providerContainer = document.getElementById('provider_selection_container');
    const receiptContainer = document.getElementById('receipt_upload_container');
    const receiptInput = document.getElementById('in_receipt');
    const qrSection = document.getElementById('qr_section');
    const providerRadios = document.querySelectorAll('.provider-radio');
    const qrImage = document.getElementById('selected_provider_qr');
    const qrLabel = document.getElementById('selected_provider_name');

    function togglePaymentFields() {
        if (payCashless.checked) {
            providerContainer.classList.remove('d-none');
            receiptContainer.classList.remove('d-none');
            receiptInput.setAttribute('required', 'required'); // Strictly enforce upload on cashless checkout

            const activeRadio = document.querySelector('.provider-radio:checked');
            if (activeRadio) {
                qrSection.classList.remove('d-none');
            } else {
                qrSection.classList.add('d-none');
            }
        } else {
            providerContainer.classList.add('d-none');
            receiptContainer.classList.add('d-none');
            receiptInput.removeAttribute('required'); // Bypass upload on cash bookings
            qrSection.classList.add('d-none');
            
            // Uncheck provider choices
            providerRadios.forEach(radio => radio.checked = false);
        }
    }

    [payCash, payCashless].forEach(input => {
        if (input) input.addEventListener('change', togglePaymentFields);
    });

    providerRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                qrImage.src = this.dataset.qr;
                qrLabel.innerText = this.dataset.name;
                qrSection.classList.remove('d-none');
            }
        });
    });

    // Scoped form validation to prevent submissions without selecting a cashless provider
    const wizardForm = document.getElementById('appointmentWizard');
    if (wizardForm) {
        wizardForm.addEventListener('submit', function(e) {
            if (payCashless && payCashless.checked) {
                const selectedProvider = document.querySelector('input[name="payment_provider_id"]:checked');
                if (!selectedProvider) {
                    e.preventDefault();
                    showWizardAlert("Please select an E-Wallet provider (e.g. GCash, Maya) to scan the payment QR code before submitting.");
                }
            }
        });
    }
});

// Fullscreen light-box zoom toggles
function zoomQR() {
    const qrSrc = document.getElementById('selected_provider_qr').src;
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