@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Bootstrap Popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // 2. Dynamic Lunch Field Toggle
    const lunchSwitch = document.getElementById('lunchSwitch');
    const lunchInputs = document.getElementById('lunchFields');
    
    function toggleLunch() {
        if (lunchSwitch && lunchInputs) {
            lunchInputs.style.opacity = lunchSwitch.checked ? '1' : '0.35';
            lunchInputs.querySelectorAll('input').forEach(i => i.disabled = !lunchSwitch.checked);
        }
    }
    
    if (lunchSwitch) {
        lunchSwitch.addEventListener('change', toggleLunch);
        toggleLunch(); // Initialize on page load
    }

    // 3. Render High-Contrast Monthly Calendar Widget
    const selectedDateStr = "{{ $selectedDate }}"; // YYYY-MM-DD
    const selectedDate = new Date(selectedDateStr);
    let calendarYear = selectedDate.getFullYear();
    let calendarMonth = selectedDate.getMonth();

    function renderCalendar() {
        const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const daysInMonth = new Date(calendarYear, calendarMonth + 1, 0).getDate();
        const firstDayIndex = new Date(calendarYear, calendarMonth, 1).getDay(); // 0 = Sun, 1 = Mon ...
        
        let html = `
        <div class="calendar-widget border border-secondary border-opacity-25 rounded-3 p-3 mb-3" style="background-color: var(--bg-card); color: var(--text-main);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5" onclick="changeMonth(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <h6 class="mb-0 fw-bold uppercase tracking-wider" style="font-size: 0.85rem; color: var(--text-main);">${months[calendarMonth]} ${calendarYear}</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5" onclick="changeMonth(1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-grid d-grid" style="grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center;">
        `;
        
        const weekDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        weekDays.forEach(day => {
            html += `<div class="fw-bold text-muted smaller uppercase py-1" style="font-size: 0.65rem;">${day}</div>`;
        });
        
        // Preceding month blank spacer offsets
        for (let i = 0; i < firstDayIndex; i++) {
            html += `<div class="py-2 text-muted opacity-25" style="font-size: 0.8rem;"></div>`;
        }
        
        const todayStr = new Date().toISOString().split('T')[0];
        
        for (let day = 1; day <= daysInMonth; day++) {
            const monthPad = String(calendarMonth + 1).padStart(2, '0');
            const dayPad = String(day).padStart(2, '0');
            const dateStr = `${calendarYear}-${monthPad}-${dayPad}`;
            
            const isSelected = (dateStr === selectedDateStr);
            const isToday = (dateStr === todayStr);
            
            let dayStyle = "cursor: pointer; border-radius: 6px; font-size: 0.8rem;";
            let dayClass = "py-2 calendar-day-cell transition-all";
            
            if (isSelected) {
                dayStyle += " background-color: var(--brand-accent); color: #0f172a !important; font-weight: 800; box-shadow: 0 2px 8px rgba(25, 211, 140, 0.3);";
            } else if (isToday) {
                dayStyle += " border: 1.5px solid var(--brand-accent); color: var(--brand-accent); font-weight: 700;";
            } else {
                dayStyle += " color: var(--text-main);";
            }
            
            html += `<div class="${dayClass}" style="${dayStyle}" onclick="selectCalendarDate('${dateStr}')">${day}</div>`;
        }
        
        html += `
            </div>
        </div>
        `;
        
        const calendarWrapper = document.getElementById('calendar-widget-wrapper');
        if (calendarWrapper) {
            calendarWrapper.innerHTML = html;
        }
    }

    window.changeMonth = function(direction) {
        calendarMonth += direction;
        if (calendarMonth < 0) {
            calendarMonth = 11;
            calendarYear--;
        } else if (calendarMonth > 11) {
            calendarMonth = 0;
            calendarYear++;
        }
        renderCalendar();
    };

    window.selectCalendarDate = function(dateStr) {
        const input = document.getElementById('selectedDateInput');
        const form = document.getElementById('dateSelectorForm');
        if (input && form) {
            input.value = dateStr;
            form.submit();
        }
    };

    // Modal Trigger Configuration Linkage ("Apply to all 7 days")
    const confirmBtn = document.getElementById('confirmApplyAllBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            const modeInput = document.getElementById('weeklyModeInput');
            const weeklyForm = document.getElementById('weeklyRulesForm');
            if (modeInput && weeklyForm) {
                modeInput.value = 'all';
                weeklyForm.submit();
            }
        });
    }

    renderCalendar();
});

// Toggle schedule custom reason field dynamically
window.toggleScheduleReasonField = function(selectEl) {
    const form = selectEl.closest('form');
    if (!form) return;
    const wrapper = form.querySelector('.schedule-custom-reason-wrapper');
    const textarea = form.querySelector('.schedule-custom-reason');
    if (wrapper && textarea) {
        if (selectEl.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
            textarea.focus();
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
            textarea.value = '';
        }
    }
};
</script>
@endpush