{{-- REUSABLE UNIFIED MULTI-FORMAT FILE PREVIEW LIGHTBOX OVERLAY --}}
<div id="qr_lightbox" class="d-none fixed inset-0 w-100 h-100 d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 3000; background-color: rgba(0, 0, 0, 0.85); cursor: zoom-out;" onclick="closeQRLightbox(event)">
    <div class="text-center p-3 animate-fade-in w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="max-width: 95vw; max-height: 95vh;">
        
        {{-- Optional File Header Title --}}
        <h5 class="text-white mb-2 fw-bold text-uppercase" id="lightbox_title" style="letter-spacing: 0.5px; font-size: 1.1rem; display: none;">Document Preview</h5>

        {{-- Floating Canvas Container --}}
        <div id="lightbox_viewer_container" class="position-relative d-flex align-items-center justify-content-center bg-white rounded p-2 border border-secondary shadow-lg" style="max-width: 85vw; max-height: 80vh; overflow: auto; min-width: 300px; min-height: 300px;" onclick="event.stopPropagation()">
            <!-- Image Viewer Node -->
            <img src="" id="lightbox_qr_img" alt="Document Asset" class="img-fluid rounded transition-all d-none" style="max-height: 75vh; max-width: 80vw; object-fit: contain; transform: scale(1); transform-origin: center; cursor: grab;">
            
            <!-- PDF Viewer Node -->
            <iframe id="lightbox_pdf_viewer" class="d-none rounded" style="width: 80vw; height: 75vh; border: none;"></iframe>
        </div>

        {{-- Standardized Image Control Toolbar --}}
        <div id="lightbox_zoom_controls" class="mt-3 d-flex gap-3 align-items-center bg-dark bg-opacity-75 px-4 py-2 rounded-pill border border-secondary" onclick="event.stopPropagation()">
            <button type="button" class="lightbox-btn" onclick="zoomImage(-0.15, event)" title="Zoom Out">
                <i class="bi bi-zoom-out fs-6"></i>
            </button>
            <span id="zoom_percent" class="text-white small fw-bold" style="min-width: 45px; display: inline-block;">100%</span>
            <button type="button" class="lightbox-btn" onclick="zoomImage(0.15, event)" title="Zoom In">
                <i class="bi bi-zoom-in fs-6"></i>
            </button>
            <button type="button" class="lightbox-btn" onclick="toggleFullscreen(event)" title="Toggle Fullscreen">
                <i class="bi bi-fullscreen fs-6" id="fullscreen_icon"></i>
            </button>
            <button type="button" class="lightbox-btn lightbox-btn-danger" onclick="resetZoom(event)" title="Reset Zoom & Pan">
                <i class="bi bi-arrow-counterclockwise fs-6"></i>
            </button>
        </div>

        <p class="text-white-50 mt-3 small mb-0">
            <i class="bi bi-x-circle me-1"></i> Click anywhere on the dark backdrop to close preview
        </p>
    </div>
</div>

<style>
    /* Scoped Uniform Circular Lightbox Buttons */
    .lightbox-btn {
        background: transparent !important;
        border: 1.5px solid rgba(255, 255, 255, 0.3) !important;
        color: #ffffff !important;
        width: 36px !important;
        height: 36px !important;
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
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: #ffffff !important;
        transform: scale(1.05);
    }
    .lightbox-btn-danger {
        border-color: rgba(220, 53, 69, 0.4) !important;
        color: #ff4d4d !important;
    }
    .lightbox-btn-danger:hover {
        background: rgba(220, 53, 69, 0.15) !important;
        border-color: #ff4d4d !important;
    }
</style>

@push('scripts')
<script>
/**
 * UNIFIED GLOBAL FILE PREVIEW LIGHTBOX CONTROLLER
 */
window.currentScale = 1;
window.translateX = 0;
window.translateY = 0;
window.isDragging = false;
window.startX = 0;
window.startY = 0;

// Universal file preview dispatcher for images, PDFs, Base64 strings & S3/local paths
window.openFilePreview = function(fileSrc, title = '') {
    if (!fileSrc) return;

    const lightbox = document.getElementById('qr_lightbox');
    const titleEl = document.getElementById('lightbox_title');
    const img = document.getElementById('lightbox_qr_img');
    const iframe = document.getElementById('lightbox_pdf_viewer');
    const controls = document.getElementById('lightbox_zoom_controls');

    if (!lightbox) return;

    if (typeof resetZoom === 'function') {
        resetZoom();
    }

    if (title && titleEl) {
        titleEl.innerText = title.toUpperCase();
        titleEl.style.display = 'block';
    } else if (titleEl) {
        titleEl.style.display = 'none';
    }

    const cleanUrl = fileSrc.split('?')[0].split('#')[0].toLowerCase();
    const isPdf = cleanUrl.endsWith('.pdf') || fileSrc.startsWith('data:application/pdf');

    if (isPdf) {
        if (img) img.classList.add('d-none');
        if (controls) {
            controls.classList.add('d-none');
            controls.style.setProperty('display', 'none', 'important');
        }
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
        if (controls) {
            controls.classList.remove('d-none');
            controls.style.removeProperty('display');
            controls.style.setProperty('display', 'flex', 'important');
        }
    }

    lightbox.classList.remove('d-none');
    lightbox.classList.add('d-flex');
    lightbox.style.setProperty('display', 'flex', 'important');
};

// Aliases for seamless retro-compatibility across legacy calls
window.zoomFile = window.openFilePreview;
window.viewReferralFile = window.openFilePreview;

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
        lightbox.style.setProperty('display', 'none', 'important');
    }
    if (document.fullscreenElement || document.webkitFullscreenElement) {
        const exitFS = document.exitFullscreen || document.webkitExitFullscreen;
        if (exitFS) exitFS.call(document);
    }
    if (typeof resetZoom === 'function') {
        resetZoom();
    }
};

window.zoomImage = function(amount, event) {
    if (event) event.stopPropagation();
    window.currentScale = (window.currentScale || 1) + amount;
    window.currentScale = Math.max(0.5, Math.min(4, window.currentScale));

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

window.toggleFullscreen = function(event) {
    if (event) event.stopPropagation();

    const container = document.getElementById('lightbox_viewer_container');
    const icon = document.getElementById('fullscreen_icon');

    if (!container) return;

    const requestFS = container.requestFullscreen || container.webkitRequestFullscreen || container.mozRequestFullScreen || container.msRequestFullscreen;
    const exitFS = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;

    if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
        if (requestFS) {
            requestFS.call(container).then(() => {
                if (icon) {
                    icon.classList.remove('bi-fullscreen');
                    icon.classList.add('bi-fullscreen-exit');
                }
            }).catch(err => console.error("Fullscreen error:", err));
        }
    } else {
        if (exitFS) {
            exitFS.call(document).then(() => {
                if (icon) {
                    icon.classList.remove('bi-fullscreen-exit');
                    icon.classList.add('bi-fullscreen');
                }
            }).catch(err => console.error("Exit Fullscreen error:", err));
        }
    }
};

// Bind Mouse Wheel Zooming and Pointer Drag-and-Pan Events
document.addEventListener('DOMContentLoaded', () => {
    const img = document.getElementById('lightbox_qr_img');
    const container = document.getElementById('lightbox_viewer_container');
    const lightbox = document.getElementById('qr_lightbox');

    if (container && lightbox) {
        container.addEventListener('wheel', (e) => {
            if (!lightbox.classList.contains('d-none')) {
                e.preventDefault();
                const amount = e.deltaY < 0 ? 0.15 : -0.15;
                window.zoomImage(amount, e);
            }
        }, { passive: false });
    }

    if (img && container) {
        const updateCursor = () => {
            img.style.cursor = (window.currentScale || 1) > 1 ? 'grab' : 'default';
        };

        img.addEventListener('pointerdown', (e) => {
            if ((window.currentScale || 1) > 1) {
                window.isDragging = true;
                img.style.cursor = 'grabbing';
                window.startX = e.clientX;
                window.startY = e.clientY;
                try { img.setPointerCapture(e.pointerId); } catch(err) {}
                e.preventDefault();
                e.stopPropagation();
            }
        });

        document.addEventListener('pointermove', (e) => {
            if (window.isDragging && (window.currentScale || 1) > 1) {
                const dx = e.clientX - window.startX;
                const dy = e.clientY - window.startY;

                window.translateX = (window.translateX || 0) + dx;
                window.translateY = (window.translateY || 0) + dy;

                window.startX = e.clientX;
                window.startY = e.clientY;

                img.style.transform = `translate(${window.translateX}px, ${window.translateY}px) scale(${window.currentScale})`;
            }
        });

        const endDrag = (e) => {
            if (window.isDragging) {
                window.isDragging = false;
                try { if (e && e.pointerId) img.releasePointerCapture(e.pointerId); } catch(err) {}
                updateCursor();
            }
        };

        document.addEventListener('pointerup', endDrag);
        document.addEventListener('pointercancel', endDrag);
    }
});
</script>
@endpush