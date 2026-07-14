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
     */
    function convertTimestamps() {
        document.querySelectorAll('.local-time-trigger').forEach(el => {
            const utcStr = el.dataset.utc;
            if (!utcStr) return;

            const dateObj = new Date(utcStr);
            const localDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const localTime = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

            el.innerHTML = `
                <div class="text-white small fw-bold">${localDate}</div>
                <div class="text-white" style="font-size: 0.8rem;">${localTime}</div>
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
    });
</script>