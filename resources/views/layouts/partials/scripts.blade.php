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
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
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

    // 7. REAL-TIME BROADCAST LISTENER
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
                        </li>
                    `;
                    
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
});
</script>