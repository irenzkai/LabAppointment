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
                    <input type="radio" class="btn-check text-main" name="payment_method" id="pay_cash" value="Cash" checked>
                    <label class="btn btn-outline-accent w-100 p-4 text-center hover-bg h-100 d-flex flex-column align-items-center justify-content-center" for="pay_cash">
                        <i class="bi bi-cash-stack fs-1 mb-2"></i>
                        <div class="fw-bold uppercase">Cash on Site</div>
                        <div class="smaller opacity-75 mt-1">Pay at the reception desk upon arrival.</div>
                    </label>
                </div>

                {{-- Option 2: Cashless (Enabled) --}}
                <div class="col-md-6">
                    <input type="radio" class="btn-check text-main" name="payment_method" id="pay_cashless" value="Cashless">
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
                            <input type="radio" class="btn-check provider-radio text-main" name="payment_provider_id" id="provider_{{ $provider->id }}" value="{{ $provider->id }}" data-qr="{{ Storage::url($provider->qr_code) }}" data-name="{{ $provider->name }}">
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

        {{-- QR Code Display Box --}}
        <div id="qr_section" class="col-12 d-none animate-fade-in mt-4">
            <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Scan to Pay (<span id="selected_provider_name" class="text-accent"></span>)</h6>
                <div class="d-flex justify-content-center">
                    <div id="qr_zoom_wrapper" class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" onclick="zoomQR(document.getElementById('selected_provider_qr').src)" title="Click to view full screen">
                        <img src="" id="selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;" onclick="zoomQR(this.src)">
                    </div>
                </div>
                <p class="text-muted smaller mt-3 mb-0 italic" style="font-size: 0.7rem;"><i class="bi bi-zoom-in me-1 text-accent"></i> Click the QR code image to view it full screen.<br>Please take a screenshot of your successful transaction to present upon arrival.</p>
            </div>
        </div>

        {{-- Proof of payment receipt container --}}
        <div id="receipt_upload_container" class="col-12 d-none mt-4 animate-fade-in">
            <label class="small text-secondary fw-bold mb-1 uppercase">Upload Proof of Payment / Receipt</label>

            <div id="receipt_input_wrapper">
                <input type="file" name="payment_receipt" id="in_receipt" class="form-control py-3 shadow-none" accept="image/*, application/pdf">
            </div>

            <div class="mt-1" id="receipt_help_text">
                <small class="text-muted smaller">
                    <i class="bi bi-info-circle me-1"></i> Required: Upload a PDF or image copy of your GCash/Maya transaction receipt to finalize.
                </small>
            </div>

            {{-- Polished Receipt Preview Card (Borderless matching Step 2) --}}
            <div id="receipt_preview_container" class="d-none mt-3 p-2.5 rounded" style="background-color: rgba(25, 211, 140, 0.03);">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-accent fw-semibold" id="receipt_file_label">
                        <i class="bi bi-file-earmark-check-fill me-1"></i>Selected File
                    </span>
                    <div class="d-flex gap-1.5">
                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-2.5 fw-bold" id="btn_view_receipt">View</button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold" onclick="removeUploadedReceipt()">Remove</button>
                    </div>
                </div>
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
                    <h6 class="text-warning fw-bold mb-1 uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Important Reminder:
                    </h6>
                    <p class="text-muted smaller mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                        For Blood Chemistry (FBS, Lipid Profile, etc.), please ensure you have undergone 10-12 hours of fasting for accurate results.
                    </p>
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

    @push('scripts')
    <script>
    /**
     * Globally hoisted QR zoom trigger
     */
    window.zoomQR = function(src) {
        const qrImg = document.getElementById('selected_provider_qr');
        let qrSrc = (typeof src === 'string' && src.length > 0) ? src : (qrImg ? qrImg.src : '');
        
        if (!qrSrc || qrSrc === '#' || qrSrc === 'about:blank' || qrSrc === window.location.href || qrSrc.endsWith('/appointments/create') || qrSrc.endsWith('/appointments/bulk')) {
            return;
        }

        const providerNameEl = document.getElementById('selected_provider_name');
        const titleName = (providerNameEl && providerNameEl.innerText.trim()) 
            ? `${providerNameEl.innerText.trim()} Payment QR Code` 
            : "Payment QR Code";

        if (typeof openFilePreview === 'function') {
            openFilePreview(qrSrc, titleName);
        }
    };

    /**
     * Isolated Step 5 UI Controller logic utilizing secure IIFE scope
     */
    (function() {
        document.addEventListener('DOMContentLoaded', () => {
            const payCash = document.getElementById('pay_cash');
            const payCashless = document.getElementById('pay_cashless');
            const providerContainer = document.getElementById('provider_selection_container');
            const receiptContainer = document.getElementById('receipt_upload_container');
            const receiptInput = document.getElementById('in_receipt');
            const receiptInputWrapper = document.getElementById('receipt_input_wrapper');
            const receiptHelpText = document.getElementById('receipt_help_text');
            const receiptPreviewContainer = document.getElementById('receipt_preview_container');
            const receiptFileLabel = document.getElementById('receipt_file_label');
            const viewReceiptBtn = document.getElementById('btn_view_receipt');

            const qrSection = document.getElementById('qr_section');
            const providerRadios = document.querySelectorAll('.provider-radio');
            const qrImage = document.getElementById('selected_provider_qr');
            const qrLabel = document.getElementById('selected_provider_name');

            const agreeCheckbox = document.getElementById('agree_terms');
            const submitBtn = document.getElementById('final_submit_btn');

            /**
             * Prevents changing providers or payment methods while a receipt is active.
             */
            function updateFieldLockState() {
                const hasReceipt = (receiptInput && receiptInput.files && receiptInput.files.length > 0) || localStorage.getItem('receipt_base64') !== null;
                
                if (hasReceipt) {
                    if (payCash) {
                        payCash.disabled = !payCash.checked;
                        const label = document.querySelector(`label[for="${payCash.id}"]`);
                        if (label) {
                            label.style.opacity = payCash.checked ? '1' : '0.5';
                            label.style.pointerEvents = 'none';
                        }
                    }
                    if (payCashless) {
                        payCashless.disabled = !payCashless.checked;
                        const label = document.querySelector(`label[for="${payCashless.id}"]`);
                        if (label) {
                            label.style.opacity = payCashless.checked ? '1' : '0.5';
                            label.style.pointerEvents = 'none';
                        }
                    }

                    providerRadios.forEach(radio => {
                        radio.disabled = !radio.checked;
                        const label = document.querySelector(`label[for="${radio.id}"]`);
                        if (label) {
                            label.style.opacity = radio.checked ? '1' : '0.4';
                            label.style.pointerEvents = 'none';
                        }
                    });
                } else {
                    if (payCash) {
                        payCash.disabled = false;
                        const label = document.querySelector(`label[for="${payCash.id}"]`);
                        if (label) {
                            label.style.opacity = '1';
                            label.style.pointerEvents = 'auto';
                        }
                    }
                    if (payCashless) {
                        payCashless.disabled = false;
                        const label = document.querySelector(`label[for="${payCashless.id}"]`);
                        if (label) {
                            label.style.opacity = '1';
                            label.style.pointerEvents = 'auto';
                        }
                    }

                    providerRadios.forEach(radio => {
                        radio.disabled = false;
                        const label = document.querySelector(`label[for="${radio.id}"]`);
                        if (label) {
                            label.style.opacity = '1';
                            label.style.pointerEvents = 'auto';
                        }
                    });
                }
            }

            function togglePaymentFields() {
                const activeRadio = document.querySelector('.provider-radio:checked');
                
                if (payCashless && payCashless.checked) {
                    if (providerContainer) providerContainer.classList.remove('d-none');
                    
                    if (activeRadio) {
                        if (qrSection) qrSection.classList.remove('d-none');
                        if (receiptContainer) receiptContainer.classList.remove('d-none');
                        if (receiptInput) receiptInput.setAttribute('required', 'required');
                    } else {
                        if (qrSection) qrSection.classList.add('d-none');
                        if (receiptContainer) receiptContainer.classList.add('d-none');
                        if (receiptInput) receiptInput.removeAttribute('required');
                    }
                } else {
                    if (providerContainer) providerContainer.classList.add('d-none');
                    if (receiptContainer) receiptContainer.classList.add('d-none');
                    if (receiptInput) receiptInput.removeAttribute('required');
                    if (qrSection) qrSection.classList.add('d-none');
                    providerRadios.forEach(radio => radio.checked = false);
                }
                updateFieldLockState();
                toggleSubmitButton();
            }

            // Register toggle listeners
            [payCash, payCashless].forEach(input => {
                if (input) {
                    input.addEventListener('change', function() {
                        localStorage.setItem('saved_payment_method', this.value);
                        togglePaymentFields();
                    });
                }
            });

            providerRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        localStorage.setItem('saved_payment_provider_id', this.value);
                        if (qrImage) {
                            qrImage.src = this.dataset.qr;
                            qrImage.setAttribute('src', this.dataset.qr);
                        }
                        if (qrLabel) qrLabel.innerText = this.dataset.name;
                        togglePaymentFields();
                    }
                });
            });

            function toggleSubmitButton() {
                if (agreeCheckbox && submitBtn) {
                    const isCashless = payCashless && payCashless.checked;
                    const hasReceiptFile = (receiptInput && receiptInput.files && receiptInput.files.length > 0) || localStorage.getItem('receipt_base64') !== null;
                    const isTermsAgreed = agreeCheckbox.checked;

                    let isFormValid = false;
                    if (isCashless) {
                        isFormValid = isTermsAgreed && hasReceiptFile;
                    } else {
                        isFormValid = isTermsAgreed;
                    }

                    if (isFormValid) {
                        submitBtn.removeAttribute('disabled');
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        submitBtn.style.pointerEvents = 'auto';
                    } else {
                        submitBtn.setAttribute('disabled', 'disabled');
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        submitBtn.style.pointerEvents = 'none';
                    }
                }
            }

            function showReceiptPreview(fileName) {
                if (receiptPreviewContainer) receiptPreviewContainer.classList.remove('d-none');
                if (receiptInputWrapper) receiptInputWrapper.classList.add('d-none');
                if (receiptHelpText) receiptHelpText.classList.add('d-none');
                if (receiptFileLabel) {
                    receiptFileLabel.innerHTML = `<i class="bi bi-file-earmark-check-fill me-1"></i>Selected File: ${fileName}`;
                }
                if (viewReceiptBtn) {
                    viewReceiptBtn.onclick = function() {
                        if (typeof openFilePreview === 'function' && window.receiptLocalData) {
                            openFilePreview(window.receiptLocalData, "Proof of Payment Receipt");
                        }
                    };
                }
                updateFieldLockState();
                toggleSubmitButton();
            }

            if (receiptInput) {
                receiptInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            window.receiptLocalData = e.target.result;
                            localStorage.setItem('receipt_base64', e.target.result);
                            localStorage.setItem('receipt_name', file.name);
                            showReceiptPreview(file.name);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        localStorage.removeItem('receipt_base64');
                        localStorage.removeItem('receipt_name');
                        updateFieldLockState();
                        toggleSubmitButton();
                    }
                });
            }

            if (agreeCheckbox) {
                agreeCheckbox.addEventListener('change', toggleSubmitButton);
            }

            if (payCash) {
                payCash.addEventListener('change', () => {
                    localStorage.removeItem('receipt_base64');
                    localStorage.removeItem('receipt_name');
                    localStorage.removeItem('saved_payment_provider_id');
                    if (receiptInput) receiptInput.value = '';
                    if (receiptInputWrapper) receiptInputWrapper.classList.remove('d-none');
                    if (receiptHelpText) receiptHelpText.classList.remove('d-none');
                    if (receiptPreviewContainer) receiptPreviewContainer.classList.add('d-none');
                    updateFieldLockState();
                    toggleSubmitButton();
                });
            }

            window.removeUploadedReceipt = function() {
                if (receiptInput) receiptInput.value = '';
                if (receiptInputWrapper) receiptInputWrapper.classList.remove('d-none');
                if (receiptHelpText) receiptHelpText.classList.remove('d-none');
                if (receiptPreviewContainer) receiptPreviewContainer.classList.add('d-none');
                
                window.receiptLocalData = null;
                localStorage.removeItem('receipt_base64');
                localStorage.removeItem('receipt_name');
                updateFieldLockState();
                togglePaymentFields();
            };

            const savedReceiptBase64 = localStorage.getItem('receipt_base64');
            if (savedReceiptBase64) {
                toggleSubmitButton();
            }

            const savedMethod = localStorage.getItem('saved_payment_method') || 'Cash';
            const savedProviderId = localStorage.getItem('saved_payment_provider_id');
            const savedReceiptName = localStorage.getItem('receipt_name');

            if (savedMethod === 'Cashless') {
                if (payCashless) payCashless.checked = true;
                if (providerContainer) providerContainer.classList.remove('d-none');
                
                if (savedProviderId) {
                    const targetRadio = document.getElementById(`provider_${savedProviderId}`);
                    if (targetRadio) {
                        targetRadio.checked = true;
                        if (qrImage) {
                            qrImage.src = targetRadio.dataset.qr;
                            qrImage.setAttribute('src', targetRadio.dataset.qr);
                        }
                        if (qrLabel) qrLabel.innerText = targetRadio.dataset.name;
                        if (qrSection) qrSection.classList.remove('d-none');
                        if (receiptContainer) receiptContainer.classList.remove('d-none');
                    }
                }

                if (savedReceiptBase64 && savedReceiptName) {
                    window.receiptLocalData = savedReceiptBase64;
                    showReceiptPreview(savedReceiptName);
                }
            } else {
                if (payCash) payCash.checked = true;
            }

            togglePaymentFields();

            const wizardForm = document.getElementById('appointmentWizard');
            if (wizardForm) {
                wizardForm.addEventListener('submit', () => {
                    if (submitBtn) {
                        setTimeout(() => {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = 'SUBMITTING... <span class="spinner-border spinner-border-sm ms-2"></span>';
                        }, 0);
                    }
                });
            }
        });
    })();
    </script>
    @endpush
</div>