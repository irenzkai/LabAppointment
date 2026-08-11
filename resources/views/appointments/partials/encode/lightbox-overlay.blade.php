{{-- E. MULTI-FORMAT LIGHTBOX OVERLAY WITH SECURE PREVIEW GATES --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox(event)">
    <div class="text-center p-3 animate-fade-in w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="max-width: 95vw; max-height: 95vh;">
        
        {{-- Floating File Canvas --}}
        <div id="lightbox_viewer_container" class="position-relative d-flex align-items-center justify-content-center bg-white rounded p-2 border border-secondary shadow-lg" style="max-width: 85vw; max-height: 80vh; overflow: auto; min-width: 300px; min-height: 300px;">
            <!-- Render Image Scan -->
            <img src="" id="lightbox_qr_img" alt="Zoomed Asset" class="img-fluid rounded transition-all" style="max-height: 75vh; max-width: 80vw; object-fit: contain; transform: scale(1); transform-origin: center; cursor: grab;">
            
            <!-- Render PDF Document Scan -->
            <iframe id="lightbox_pdf_viewer" class="d-none rounded" style="width: 80vw; height: 75vh; border: none;"></iframe>
        </div>

        {{-- Interactive Document Control Toolbar --}}
        <div id="lightbox_zoom_controls" class="mt-3 d-flex gap-3 align-items-center bg-dark bg-opacity-75 px-4 py-2 rounded-pill border border-secondary" onclick="event.stopPropagation()">
            <button type="button" class="lightbox-btn" onclick="zoomImage(-0.15, event)" title="Zoom Out">
                <i class="bi bi-zoom-out"></i>
            </button>
            <span id="zoom_percent" class="text-white small fw-bold">100%</span>
            <button type="button" class="lightbox-btn" onclick="zoomImage(0.15, event)" title="Zoom In">
                <i class="bi bi-zoom-in"></i>
            </button>
            <button type="button" class="lightbox-btn" onclick="toggleFullscreen(event)" title="Toggle Fullscreen">
                <i class="bi bi-fullscreen" id="fullscreen_icon"></i>
            </button>
            <button type="button" class="lightbox-btn lightbox-btn-danger" onclick="resetZoom(event)" title="Reset Zoom">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </div>

        <p class="text-white-50 mt-3 small mb-0">
            <i class="bi bi-x-circle me-1"></i> Click anywhere on the dark overlay boundary to close preview
        </p>
    </div>
</div>

@push('scripts')
<script>
    // Global properties used for tracking zooming and canvas panning
    window.currentScale = 1;
    window.translateX = 0;
    window.translateY = 0;
    window.isDragging = false;
    window.startX = 0;
    window.startY = 0;

    function zoomImage(amount, event) {
        if (event) event.stopPropagation();
        window.currentScale = (window.currentScale || 1) + amount;
        window.currentScale = Math.max(0.5, Math.min(3, window.currentScale)); // Cap zoom scale between 50% and 300%

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
    }

    function resetZoom(event) {
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
    }

    function zoomFile(fileSrc) {
        if (!fileSrc) return;
        const isPdf = fileSrc.toLowerCase().endsWith('.pdf') || fileSrc.startsWith('data:application/pdf');
        const img = document.getElementById('lightbox_qr_img');
        const iframe = document.getElementById('lightbox_pdf_viewer');
        const controls = document.getElementById('lightbox_zoom_controls');
        const lightbox = document.getElementById('qr_lightbox');

        if (!lightbox) return;

        resetZoom();

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
    }
    window.zoomQR = zoomFile;

    function closeQRLightbox(event) {
        if (event) {
            const container = document.getElementById('lightbox_viewer_container');
            const controls = document.getElementById('lightbox_zoom_controls');
            if ((container && container.contains(event.target)) || (controls && controls.contains(event.target))) {
                return;
            }
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
        resetZoom();
    }
    window.closeQRLightbox = closeQRLightbox;

    function toggleFullscreen(event) {
        if (event) event.stopPropagation();

        const container = document.getElementById('lightbox_viewer_container');
        const icon = document.getElementById('fullscreen_icon');

        if (!container) return;

        // Cross-browser secure Fullscreen API matching (resolves native fullscreen display bugs)
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
    }
    window.toggleFullscreen = toggleFullscreen;

    // Listeners for cross-browser escape or standard navigation exit sync
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

    // Panning & Dragging event trackers for zooming
    document.addEventListener('DOMContentLoaded', () => {
        const img = document.getElementById('lightbox_qr_img');
        const container = document.getElementById('lightbox_viewer_container');

        if (img && container) {
            const updateCursor = () => {
                const scale = window.currentScale || 1;
                img.style.cursor = scale > 1 ? 'grab' : 'default';
            };

            img.addEventListener('pointerdown', (e) => {
                const scale = window.currentScale || 1;
                if (scale > 1) {
                    window.isDragging = true;
                    img.style.cursor = 'grabbing';
                    window.startX = e.clientX;
                    window.startY = e.clientY;
                    img.setPointerCapture(e.pointerId);
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            container.addEventListener('pointermove', (e) => {
                const scale = window.currentScale || 1;
                if (window.isDragging && scale > 1) {
                    const dx = e.clientX - window.startX;
                    const dy = e.clientY - window.startY;
                    
                    window.translateX = (window.translateX || 0) + dx;
                    window.translateY = (window.translateY || 0) + dy;
                    
                    window.startX = e.clientX;
                    window.startY = e.clientY;
                    
                    img.style.transform = `translate(${window.translateX}px, ${window.translateY}px) scale(${scale})`;
                }
            });

            const endDrag = (e) => {
                if (window.isDragging) {
                    window.isDragging = false;
                    try {
                        img.releasePointerCapture(e.pointerId);
                    } catch(err) {}
                    updateCursor();
                }
            };

            container.addEventListener('pointerup', endDrag);
            container.addEventListener('pointercancel', endDrag);

            window.addEventListener('pointermove', (e) => {
                const scale = window.currentScale || 1;
                if (window.isDragging && scale > 1 && !document.fullscreenElement && !document.webkitFullscreenElement) {
                    const dx = e.clientX - window.startX;
                    const dy = e.clientY - window.startY;
                    window.translateX = (window.translateX || 0) + dx;
                    window.translateY = (window.translateY || 0) + dy;
                    window.startX = e.clientX;
                    window.startY = e.clientY;
                    img.style.transform = `translate(${window.translateX}px, ${window.translateY}px) scale(${scale})`;
                }
            });
            window.addEventListener('pointerup', endDrag);
        }
    });
</script>
@endpush

@push('styles')
<style>
    /* Scoped Uniform Circular Lightbox Controls */
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
@endpush