{{-- Include Pusher Client Library safely at the bottom --}}
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
<script>
/**
 * 1. THEME SWITCHER LOGIC
 * Handles switching between Dark and Light modes and persists choice in LocalStorage.
 */ 
const themeToggle = document.getElementById('theme-toggle');
const themeIcon = document.getElementById('theme-icon');
const htmlElement = document.documentElement;

// Load saved theme on refresh
const savedTheme = localStorage.getItem('theme') || 'dark';
htmlElement.setAttribute('data-bs-theme', savedTheme);
updateIcon(savedTheme);

themeToggle?.addEventListener('click', () => {
    let currentTheme = htmlElement.getAttribute('data-bs-theme');
    let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    htmlElement.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateIcon(newTheme);
});

function updateIcon(theme) {
    if (!themeIcon) return;
    themeIcon.className = theme === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5';
}

/**
 * 2. LIVE CLINICAL CLOCK
 * Displays a real-time clock in the dashboard/header.
 */
function updateLiveClock() {
    const clockEl = document.getElementById('live-clock'); 
    if (!clockEl) return;
    
    const now = new Date();
    clockEl.innerText = now.toLocaleTimeString('en-US', { 
        hour12: true, 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
}
setInterval(updateLiveClock, 1000);
updateLiveClock();

/**
 * 3. BACK TO TOP LOGIC
 */
const backToTopBtn = document.getElementById("btn-back-to-top");
window.onscroll = function() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        if (backToTopBtn) backToTopBtn.style.display = "flex";
    } else {
        if (backToTopBtn) backToTopBtn.style.display = "none";
    }
};
backToTopBtn?.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

/**
 * 4. PASSWORD VISIBILITY TOGGLE
 * Helper to show/hide password text.
 */
function setupPasswordToggle(inputId, toggleId) {
    const toggle = document.querySelector(toggleId);
    const input = document.querySelector(inputId);
    if(toggle && input) {
        toggle.addEventListener('click', function() {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            // Toggle icon class on the element itself or its nested <i> tag
            const icon = this.tagName === 'I' ? this : this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            }
        });
    }
}

/**
 * 5. LOCAL TIMESTAMP CONVERTER
 * Converts UTC strings from the database into the user's local device time.
 * FIXED: Uses CSS variables to automatically adjust contrast in both light and dark mode themes.
 */
function convertTimestamps() {
    document.querySelectorAll('.local-time-trigger').forEach(el => {
        const utcStr = el.dataset.utc; 
        if (!utcStr) return;

        const dateObj = new Date(utcStr);
        const localDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        const localTime = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

        el.innerHTML = `
            <div class="small fw-bold" style="color: var(--text-main);">${localDate}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">${localTime}</div>
        `;
    });
}

/**
 * 6. CLINICAL ACCESS MODAL (REASON-GATE)
 * Centralized function to trigger the "Access Reason" modal for staff.
 */
function promptAccess(id, type, mode, isHistory = false) {
    const form = document.getElementById('accessReasonForm');
    if (!form) return;
    
    // Match standard web.php routing
    if (isHistory) {
        form.action = '/internal/archive-log-access'; 
        document.getElementById('target_user_id').value = id;
    } else {
        form.action = '/internal/appointment-log-access/' + id;
        document.getElementById('target_user_id').value = '';
    }

    // Set type (e.g., 'hub', 'lab', 'radio') and mode ('preview' or 'edit')
    document.getElementById('access_type').value = type; 
    document.getElementById('access_mode').value = mode;

    // Reset textarea and show modal
    form.querySelector('textarea').value = '';
    const accessModal = new bootstrap.Modal(document.getElementById('accessReasonModal'));
    accessModal.show();
}

// Run conversion and initializations on DOM load
document.addEventListener('DOMContentLoaded', () => {
    convertTimestamps();

    // 8. AUTO-HIDE DISMISSABLE CLINICAL ALERTS Smoothly after 10 seconds
    document.querySelectorAll('.alert-clinical').forEach(alert => {
        // FIXED: Only auto-hide alerts containing a close button to prevent standard Safety Notices from fading
        if (alert.querySelector('.btn-close')) {
            setTimeout(() => {
                // Apply inline transition rules for a complete, fluid exit path
                alert.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out, padding 0.5s ease-out, margin 0.5s ease-out, height 0.5s ease-out';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                
                // Collapse spatial metrics immediately after opacity fades out
                setTimeout(() => { 
                    alert.style.height = '0';
                    alert.style.paddingTop = '0';
                    alert.style.paddingBottom = '0';
                    alert.style.marginTop = '0';
                    alert.style.marginBottom = '0';
                    
                    // Safely remove the element from the DOM once all spatial transitions conclude
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 800);
            }, 10000); // 10-second delay threshold before initiating exit
        }
    });
});

/**
 * 7. REAL-TIME BROADCAST LISTENER
 */
const currentUserId = "{{ auth()->check() ? auth()->id() : '' }}";
if (currentUserId) {
    // Initialize Pusher Client Connection
    const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
        cluster: "{{ env('PUSHER_APP_CLUSTER') }}"
    });

    // Subscribe to the public notifications channel
    const channel = pusher.subscribe('user-notifications');

    // Bind the custom notification event
    channel.bind('notification.created', function(data) {
        // Ensure payload contains data and is targeted to this user session
        if (data && data.data && data.data.user_id == currentUserId) {
            
            // A. Update unread notification bell badge 
            const badge = document.getElementById('notif-badge');
            if (badge) {
                let currentCount = parseInt(badge.innerText) || 0;
                currentCount++;
                badge.innerText = currentCount;
                badge.style.display = 'inline-block';
            }

            // B. Remove empty list state placeholder if visible
            const placeholder = document.getElementById('no-notifs-placeholder');
            if (placeholder) {
                placeholder.remove();
            }

            // C. Prepend the new notification item into dropdown list
            const listContainer = document.getElementById('notif-list-container');
            if (listContainer) {
                const newCardHTML = `
                <li>
                    <a class="dropdown-item p-3 border-bottom border-secondary border-opacity-25 bg-dark border-start border-4 border-accent" href="#" style="opacity: 0; transition: opacity 0.5s ease-in-out;">
                        <div class="fw-bold fs-x-small text-accent mb-1 uppercase">${data.data.title}</div>
                        <div class="text-wrap small text-white-50">${data.data.message}</div>
                        <div class="mt-2 text-muted" style="font-size: 0.65rem;">Just now</div>
                    </a>
                </li>`;
                listContainer.insertAdjacentHTML('afterbegin', newCardHTML);
                
                // Execute subtle CSS fade-in
                const newElement = listContainer.querySelector('a');
                setTimeout(() => {
                    newElement.style.opacity = '1';
                }, 50);
            }
        }
    });
}

/**
 * 9. GLOBAL MULTI-FORMAT LIGHTBOX CONTROLLER
 * Explicitly exposes zoom, panning, and fullscreen features globally for all application files
 */
window.currentScale = 1;
window.translateX = 0;
window.translateY = 0;
window.isDragging = false;
window.startX = 0;
window.startY = 0;

window.zoomImage = function(amount, event) {
    if (event) event.stopPropagation();
    window.currentScale = (window.currentScale || 1) + amount;
    window.currentScale = Math.max(0.5, Math.min(3, window.currentScale));

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

// Standard cross-browser fullscreen state event sync
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

// Initialize dragging, wheel zoom, and standard dragging listeners defensively on load
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

    /**
     * Modern Pointer Events Drag-and-Pan Panning Engine (Directly bound to the container for secure fullscreen execution)
     */
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
                
                // Binds focus to image so drag movements are captured on all coordinate spaces
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
        
        // Window level fallback to support standard panning outside fullscreen
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