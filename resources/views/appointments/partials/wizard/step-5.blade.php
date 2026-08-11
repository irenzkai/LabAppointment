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

        {{-- QR Code Display Box --}}
        <div id="qr_section" class="col-12 d-none animate-fade-in mt-4">
            <div class="p-4 border border-secondary border-opacity-25 rounded text-center" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                <h6 class="text-main fw-bold mb-3 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Scan to Pay (<span id="selected_provider_name" class="text-accent"></span>)</h6>
                <div class="d-flex justify-content-center">
                    <div class="bg-white p-2 rounded shadow-sm border border-secondary border-opacity-10" style="cursor: zoom-in;" onclick="zoomQR()">
                        <img src="" id="selected_provider_qr" alt="Scan QR" style="width: 180px; height: 180px; object-fit: contain;">
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

{{-- FULLSCREEN MULTI-FORMAT LIGHTBOX OVERLAY --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox(event)">
    <div class="text-center p-3 animate-fade-in w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="max-width: 95vw; max-height: 95vh;">
        
        {{-- Floating File Canvas --}}
        <div id="lightbox_viewer_container" class="position-relative d-flex align-items-center justify-content-center bg-white rounded p-2 border border-secondary shadow-lg" style="max-width: 85vw; max-height: 80vh; overflow: auto; min-width: 300px; min-height: 300px;">
            <!-- Render Image Scan -->
            <img src="" id="lightbox_qr_img" alt="Zoomed Asset" class="img-fluid rounded transition-all" style="max-height: 75vh; max-width: 80vw; object-fit: contain; transform: scale(1); transform-origin: center; cursor: grab;">
            
            <!-- Render PDF Document Scan -->
            <iframe id="lightbox_pdf_viewer" class="d-none rounded" style="width: 80vw; height: 75vh; border: none;"></iframe>
        </div>

        {{-- Interactive Document Control Toolbar (Zoom button styling fully aligned) --}}
        <div id="lightbox_zoom_controls" class="mt-3 d-flex gap-3 align-items-center bg-dark bg-opacity-75 px-4 py-2 rounded-pill border border-secondary" onclick="event.stopPropagation()">
            <button type="button" class="lightbox-btn" onclick="zoomImage(-0.15, event)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
            <span id="zoom_percent" class="text-white small fw-bold">100%</span>
            <button type="button" class="lightbox-btn" onclick="zoomImage(0.15, event)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
            <button type="button" class="lightbox-btn" onclick="toggleFullscreen(event)" title="Toggle Fullscreen"><i class="bi bi-fullscreen" id="fullscreen_icon"></i></button>
            <button type="button" class="lightbox-btn lightbox-btn-danger" onclick="resetZoom(event)" title="Reset Zoom"><i class="bi bi-arrow-counterclockwise"></i></button>
        </div>

        <p class="text-white-50 mt-3 small mb-0"><i class="bi bi-x-circle me-1"></i> Click anywhere on the dark overlay boundary to close preview</p>
    </div>
</div>

<style>
    /* Scoped Uniform Circular Lightbox Buttons */
    .lightbox-btn {
        background: transparent !important;
        border: 1.5px solid rgba(255, 255, 255, 0.3) !important;
        color: #ffffff !important;
        width: 34px !important;
        height: 34px !important;
        border-radius: 50% !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        transition: all 0.2s ease-in-out !important;
        cursor: pointer !important;
        box-shadow: none !important;
    }
    .lightbox-btn:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: #ffffff !important;
        transform: scale(1.05);
    }
    .lightbox-btn-danger {
        border-color: rgba(220, 53, 69, 0.4) !important;
        color: #ff4d4d !important;
    }
    .lightbox-btn-danger:hover {
        background: rgba(220, 53, 69, 0.1) !important;
        border-color: #ff4d4d !important;
    }
</style>

<script>
    /**
     * Defensive Global Lightbox Controller Hoisting (Resolves Step 2 & Step 5 controls, Zooming, and Fullscreen functionality)
     */
    if (typeof window.zoomImage === 'undefined') {
        window.zoomImage = function(amount, event) {
            if (event) event.stopPropagation();
            currentScale = (window.currentScale || 1) + amount;
            currentScale = Math.max(0.5, Math.min(3, currentScale));
            window.currentScale = currentScale;

            const img = document.getElementById('lightbox_qr_img');
            if (img) {
                const tx = window.translateX || 0;
                const ty = window.translateY || 0;
                img.style.transform = `translate(${tx}px, ${ty}px) scale(${window.currentScale})`;
                img.style.cursor = window.currentScale > 1 ? 'grab' : 'default';
            }

            const percentEl = document.getElementById('zoom_percent');
            if (percentEl) {
                percentEl.innerText = `${Math.round(window.currentScale * 100)}%`;
            }
        };
    }

    if (typeof window.resetZoom === 'undefined') {
        window.resetZoom = function(event) {
            if (event) event.stopPropagation();
            window.currentScale = 1;
            window.translateX = 0;
            window.translateY = 0;
            window.isDragging = false;

            const img = document.getElementById('lightbox_qr_img');
            if (img) {
                img.style.transform = 'translate(0px, 0px) scale(1)';
                img.style.cursor = 'default';
            }

            const percentEl = document.getElementById('zoom_percent');
            if (percentEl) {
                percentEl.innerText = '100%';
            }
        };
    }

    if (typeof window.closeQRLightbox === 'undefined') {
        window.closeQRLightbox = function(event) {
            if (event) {
                const container = document.getElementById('lightbox_viewer_container');
                const controls = document.getElementById('lightbox_zoom_controls');
                if (container && container.contains(event.target)) return;
                if (controls && controls.contains(event.target)) return;
            }
            const lightbox = document.getElementById('qr_lightbox');
            if (lightbox) {
                lightbox.classList.add('d-none');
                lightbox.classList.remove('d-flex');
            }
            if (document.fullscreenElement || document.webkitFullscreenElement) {
                const exitFS = document.exitFullscreen || document.webkitExitFullscreen;
                if (exitFS) exitFS.call(document);
            }
            window.resetZoom();
        };
    }

    if (typeof window.toggleFullscreen === 'undefined') {
        window.toggleFullscreen = function(event) {
            if (event) event.stopPropagation();

            const container = document.getElementById('lightbox_viewer_container');
            const icon = document.getElementById('fullscreen_icon');

            if (!container) return;

            // Cross-browser secure Fullscreen API matching (Resolves native fullscreen display issues)
            const requestFS = container.requestFullscreen 
                || container.webkitRequestFullscreen 
                || container.mozRequestFullScreen 
                || container.msRequestFullscreen;

            const exitFS = document.exitFullscreen 
                || document.webkitExitFullscreen 
                || document.mozCancelFullScreen 
                || document.msExitFullscreen;

            if (!document.fullscreenElement && 
                !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && 
                !document.msFullscreenElement) {
                
                if (requestFS) {
                    requestFS.call(container).then(() => {
                        if (icon) {
                            icon.classList.remove('bi-fullscreen');
                            icon.classList.add('bi-fullscreen-exit');
                        }
                    }).catch(err => {
                        console.error("Error attempting to enable fullscreen mode:", err);
                    });
                }
            } else {
                if (exitFS) {
                    exitFS.call(document).then(() => {
                        if (icon) {
                            icon.classList.remove('bi-fullscreen-exit');
                            icon.classList.add('bi-fullscreen');
                        }
                    }).catch(err => {
                        console.error("Error attempting to exit fullscreen mode:", err);
                    });
                }
            }
        };
    }

    if (typeof window.viewReferralFile === 'undefined') {
        window.viewReferralFile = function(fileSrc) {
            if (!fileSrc) return;
            const isPdf = fileSrc.toLowerCase().endsWith('.pdf') || fileSrc.startsWith('data:application/pdf');
            const img = document.getElementById('lightbox_qr_img');
            const iframe = document.getElementById('lightbox_pdf_viewer');
            const controls = document.getElementById('lightbox_zoom_controls');
            const lightbox = document.getElementById('qr_lightbox');

            if (!lightbox) return;

            window.resetZoom();

            if (isPdf) {
                if (img) img.classList.add('d-none');
                if (controls) controls.classList.add('d-none');
                if (iframe) {
                    iframe.src = fileSrc;
                    iframe.classList.remove('d-none');
                }
            } else {
                if (iframe) {
                    iframe.classList.add('d-none');
                    iframe.src = '';
                }
                if (img) {
                    img.src = fileSrc;
                    img.classList.remove('d-none');
                }
                if (controls) controls.classList.remove('d-none');
            }

            lightbox.classList.remove('d-none');
            lightbox.classList.add('d-flex');
        };
    }

    // Attach event listeners for cross-browser escape / standard navigation exit sync
    document.addEventListener('fullscreenchange', () => {
        const icon = document.getElementById('fullscreen_icon');
        if (icon) {
            if (document.fullscreenElement) {
                icon.classList.remove('bi-fullscreen');
                icon.classList.add('bi-fullscreen-exit');
            } else {
                icon.classList.remove('bi-fullscreen-exit');
                icon.classList.add('bi-fullscreen');
            }
        }
    });

    document.addEventListener('webkitfullscreenchange', () => {
        const icon = document.getElementById('fullscreen_icon');
        if (icon) {
            if (document.webkitFullscreenElement) {
                icon.classList.remove('bi-fullscreen');
                icon.classList.add('bi-fullscreen-exit');
            } else {
                icon.classList.remove('bi-fullscreen-exit');
                icon.classList.add('bi-fullscreen');
            }
        }
    });

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

            const img = document.getElementById('lightbox_qr_img');

            function togglePaymentFields() {
                if (payCashless && payCashless.checked) {
                    if (providerContainer) providerContainer.classList.remove('d-none');
                    if (receiptContainer) receiptContainer.classList.remove('d-none');
                    if (receiptInput) receiptInput.setAttribute('required', 'required');

                    const activeRadio = document.querySelector('.provider-radio:checked');
                    if (activeRadio) {
                        if (qrSection) qrSection.classList.remove('d-none');
                    } else {
                        if (qrSection) qrSection.classList.add('d-none');
                    }
                } else {
                    if (providerContainer) providerContainer.classList.add('d-none');
                    if (receiptContainer) receiptContainer.classList.add('d-none');
                    if (receiptInput) receiptInput.removeAttribute('required');
                    if (qrSection) qrSection.classList.add('d-none');
                    
                    providerRadios.forEach(radio => radio.checked = false);
                }
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
                        if (qrImage) qrImage.src = this.dataset.qr;
                        if (qrLabel) qrLabel.innerText = this.dataset.name;
                        togglePaymentFields();
                    }
                });
            });

            /**
             * Programmatic Submit Clickable State Logic (Enforces receipt before Cashless confirmation)
             */
            function toggleSubmitButton() {
                if (agreeCheckbox && submitBtn) {
                    const isCashless = payCashless && payCashless.checked;
                    const hasReceiptFile = (receiptInput && receiptInput.files && receiptInput.files.length > 0) || localStorage.getItem('receipt_base64') !== null;
                    const isTermsAgreed = agreeCheckbox.checked;

                    let isFormValid = false;
                    if (isCashless) {
                        // Cashless validation requires both terms and receipt upload
                        isFormValid = isTermsAgreed && hasReceiptFile;
                    } else {
                        // Cash validation only requires terms agreement
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
                        window.viewReferralFile(window.receiptLocalData);
                    };
                }
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
                        toggleSubmitButton();
                    }
                });
            }

            if (agreeCheckbox) {
                agreeCheckbox.addEventListener('change', toggleSubmitButton);
            }

            // Reset file and validation on switching back to Cash payment method
            if (payCash) {
                payCash.addEventListener('change', () => {
                    localStorage.removeItem('receipt_base64');
                    localStorage.removeItem('receipt_name');
                    localStorage.removeItem('saved_payment_provider_id');
                    if (receiptInput) receiptInput.value = '';
                    if (receiptInputWrapper) receiptInputWrapper.classList.remove('d-none');
                    if (receiptHelpText) receiptHelpText.classList.remove('d-none');
                    if (receiptPreviewContainer) receiptPreviewContainer.classList.add('d-none');
                    toggleSubmitButton();
                });
            }

            // Restore saved receipt state on page load
            const savedReceiptBase64 = localStorage.getItem('receipt_base64');
            if (savedReceiptBase64) {
                toggleSubmitButton();
            }

            /**
             * Restore saved draft and open panels dynamically on load/refresh
             */
            const savedMethod = localStorage.getItem('saved_payment_method') || 'Cash';
            const savedProviderId = localStorage.getItem('saved_payment_provider_id');
            const savedReceiptName = localStorage.getItem('receipt_name');

            if (savedMethod === 'Cashless') {
                if (payCashless) payCashless.checked = true;
                if (providerContainer) providerContainer.classList.remove('d-none');
                if (receiptContainer) receiptContainer.classList.remove('d-none');
                
                if (savedProviderId) {
                    const targetRadio = document.getElementById(`provider_${savedProviderId}`);
                    if (targetRadio) {
                        targetRadio.checked = true;
                        if (qrImage) qrImage.src = targetRadio.dataset.qr;
                        if (qrLabel) qrLabel.innerText = targetRadio.dataset.name;
                        if (qrSection) qrSection.classList.remove('d-none');
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

            window.removeUploadedReceipt = function() {
                if (receiptInput) receiptInput.value = '';
                if (receiptInputWrapper) receiptInputWrapper.classList.remove('d-none');
                if (receiptHelpText) receiptHelpText.classList.remove('d-none');
                if (receiptPreviewContainer) receiptPreviewContainer.classList.add('d-none');
                
                window.receiptLocalData = null;
                localStorage.removeItem('receipt_base64');
                localStorage.removeItem('receipt_name');
                toggleSubmitButton();
            };

            /**
             * Bind wheel zoom listener to backdrop & image container for perfect scroll-to-zoom support (even in fullscreen)
             */
            const attachWheelZoom = () => {
                const elements = [
                    document.getElementById('lightbox_viewer_container'),
                    document.getElementById('lightbox_qr_img'),
                    document.getElementById('qr_lightbox')
                ];
                elements.forEach(el => {
                    if (el) {
                        el.addEventListener('wheel', (e) => {
                            const lightbox = document.getElementById('qr_lightbox');
                            if (lightbox && !lightbox.classList.contains('d-none')) {
                                e.preventDefault();
                                const amount = e.deltaY < 0 ? 0.15 : -0.15;
                                window.zoomImage(amount, e);
                            }
                        }, { passive: false });
                    }
                });
            };

            attachWheelZoom();
        });

        // Hoist standard Step 5 QR trigger globally
        window.zoomQR = function() {
            const qrSrc = document.getElementById('selected_provider_qr').src;
            if (qrSrc) {
                window.viewReferralFile(qrSrc);
            }
        };
    })();
</script>