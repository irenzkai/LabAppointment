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

/**
 * 7. UNIFIED PSGC ADDRESS CASCADING ENGINE
 * Global helper to manage Province -> City -> Barangay PSGC fetches across forms.
 */
window.initUnifiedAddressCascade = async function(config) {
    const {
        provEl,
        cityEl,
        brgyEl,
        streetEl,
        savedProv,
        savedCity,
        savedBrgy,
        onCompiled
    } = config;

    if (!provEl || !cityEl || !brgyEl) return;

    const PSGC_BASE = 'https://psgc.gitlab.io/api';

    function normalizeName(str) {
        if (!str) return '';
        return str.toString().toUpperCase()
            .replace(/\b(CITY|MUNICIPALITY|PROVINCE) OF\b/g, '')
            .replace(/[^A-Z0-9]/g, '')
            .trim();
    }

    function matchOption(select, value) {
        if (!select || !value) return null;
        const targetNorm = normalizeName(value);
        const targetRaw = value.toString().trim().toUpperCase();

        return Array.from(select.options).find(opt => {
            if (opt.value.toString().trim().toUpperCase() === targetRaw) return true;
            if (opt.text.toString().trim().toUpperCase() === targetRaw) return true;
            if (opt.dataset.code === targetRaw) return true;
            const optNorm = normalizeName(opt.text);
            return optNorm && optNorm === targetNorm;
        });
    }

    // Load Provinces
    provEl.innerHTML = '<option value="">Loading Provinces...</option>';
    try {
        let res = await fetch(`${PSGC_BASE}/provinces.json`);
        if (!res.ok) res = await fetch(`${PSGC_BASE}/provinces/`);
        const provinces = await res.json();

        provEl.innerHTML = '<option value="">Select Province</option>';
        provinces.sort((a,b) => a.name.localeCompare(b.name)).forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.dataset.code = p.code;
            opt.textContent = p.name;
            provEl.appendChild(opt);
        });
        provEl.disabled = false;
    } catch (e) {
        console.error('PSGC Provinces Error:', e);
        if (savedProv) {
            provEl.innerHTML = `<option value="${savedProv}" selected>${savedProv}</option>`;
        }
    }

    async function loadCities(provCode, targetCity) {
        cityEl.disabled = true;
        brgyEl.disabled = true;
        cityEl.innerHTML = '<option value="">Loading Cities...</option>';
        brgyEl.innerHTML = '<option value="">Select City First</option>';

        if (!provCode) {
            cityEl.innerHTML = '<option value="">Select Province First</option>';
            return;
        }

        try {
            let res = await fetch(`${PSGC_BASE}/provinces/${provCode}/cities-municipalities.json`);
            if (!res.ok) res = await fetch(`${PSGC_BASE}/provinces/${provCode}/cities-municipalities/`);
            const cities = await res.json();

            cityEl.innerHTML = '<option value="">Select City</option>';
            cities.sort((a,b) => a.name.localeCompare(b.name)).forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.name;
                opt.dataset.code = c.code;
                opt.textContent = c.name;
                cityEl.appendChild(opt);
            });
            cityEl.disabled = false;

            if (targetCity) {
                const matched = matchOption(cityEl, targetCity);
                if (matched) {
                    cityEl.value = matched.value;
                    await loadBarangays(matched.dataset.code, savedBrgy);
                }
            }
        } catch (e) {
            console.error('PSGC Cities Error:', e);
            if (targetCity) {
                cityEl.innerHTML = `<option value="${targetCity}" selected>${targetCity}</option>`;
                cityEl.disabled = false;
            }
        }
    }

    async function loadBarangays(cityCode, targetBrgy) {
        brgyEl.disabled = true;
        brgyEl.innerHTML = '<option value="">Loading Barangays...</option>';

        if (!cityCode) {
            brgyEl.innerHTML = '<option value="">Select City First</option>';
            return;
        }

        try {
            let res = await fetch(`${PSGC_BASE}/cities-municipalities/${cityCode}/barangays.json`);
            if (!res.ok) res = await fetch(`${PSGC_BASE}/cities-municipalities/${cityCode}/barangays/`);
            const barangays = await res.json();

            brgyEl.innerHTML = '<option value="">Select Barangay</option>';
            barangays.sort((a,b) => a.name.localeCompare(b.name)).forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.name;
                opt.dataset.code = b.code;
                opt.textContent = b.name;
                brgyEl.appendChild(opt);
            });
            brgyEl.disabled = false;

            if (targetBrgy) {
                const matched = matchOption(brgyEl, targetBrgy);
                if (matched) {
                    brgyEl.value = matched.value;
                }
            }
        } catch (e) {
            console.error('PSGC Barangays Error:', e);
            if (targetBrgy) {
                brgyEl.innerHTML = `<option value="${targetBrgy}" selected>${targetBrgy}</option>`;
                brgyEl.disabled = false;
            }
        }
    }

    provEl.addEventListener('change', function() {
        const selectedOpt = provEl.options[provEl.selectedIndex];
        const code = selectedOpt?.dataset.code;
        loadCities(code, null);
        if (onCompiled) onCompiled();
    });

    cityEl.addEventListener('change', function() {
        const selectedOpt = cityEl.options[cityEl.selectedIndex];
        const code = selectedOpt?.dataset.code;
        loadBarangays(code, null);
        if (onCompiled) onCompiled();
    });

    brgyEl.addEventListener('change', function() {
        if (onCompiled) onCompiled();
    });

    if (streetEl) {
        streetEl.addEventListener('input', function() {
            if (onCompiled) onCompiled();
        });
    }

    if (savedProv) {
        const matchedProv = matchOption(provEl, savedProv);
        if (matchedProv) {
            provEl.value = matchedProv.value;
            await loadCities(matchedProv.dataset.code, savedCity);
        }
    }
};

// Run conversion and initializations on DOM load
document.addEventListener('DOMContentLoaded', () => {
    convertTimestamps();

    // 8. AUTO-HIDE DISMISSABLE CLINICAL ALERTS Smoothly after 10 seconds
    document.querySelectorAll('.alert-clinical').forEach(alert => {
        if (alert.querySelector('.btn-close')) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out, padding 0.5s ease-out, margin 0.5s ease-out, height 0.5s ease-out';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                
                setTimeout(() => { 
                    alert.style.height = '0';
                    alert.style.paddingTop = '0';
                    alert.style.paddingBottom = '0';
                    alert.style.marginTop = '0';
                    alert.style.marginBottom = '0';
                    
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 800);
            }, 10000);
        }
    });
});
</script>