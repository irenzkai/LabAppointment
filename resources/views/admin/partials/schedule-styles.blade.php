<style>
/* 1. Scope input elements, groups, and selections strictly to avoid global layout leaks */
#schedule-manager-page .form-control, 
#schedule-manager-page .form-select, 
#schedule-manager-page .input-group-text {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
    font-size: 0.85rem;
}

#schedule-manager-page .form-control:focus, 
#schedule-manager-page .form-select:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}

/* Native date/time picker calendar icon inversion based on theme mode */
[data-bs-theme="dark"] #schedule-manager-page input[type="time"]::-webkit-calendar-picker-indicator,
[data-bs-theme="dark"] #schedule-manager-page input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

[data-bs-theme="light"] #schedule-manager-page input[type="time"]::-webkit-calendar-picker-indicator,
[data-bs-theme="light"] #schedule-manager-page input[type="date"]::-webkit-calendar-picker-indicator {
    filter: none;
    cursor: pointer;
}

/* 2. Container background & border styling */
#schedule-manager-page #openStatusContainer, 
#schedule-manager-page #lunchFields {
    background-color: var(--bg-main) !important;
    border-color: var(--border-color) !important;
}

/* 3. Slot card micro-interactions and status themes */
#schedule-manager-page .slot-card {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: var(--bg-card) !important;
}

#schedule-manager-page .slot-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* High-Contrast Slot PAX Badges */
#schedule-manager-page .slot-pax-badge.badge-available {
    background-color: rgba(25, 211, 140, 0.15) !important;
    color: var(--brand-accent) !important;
    border: 1px solid rgba(25, 211, 140, 0.3) !important;
}

#schedule-manager-page .slot-pax-badge.badge-full {
    background-color: rgba(220, 53, 69, 0.15) !important;
    color: #dc3545 !important;
    border: 1px solid rgba(220, 53, 69, 0.3) !important;
}

/* 4. Progress bar styling */
#schedule-manager-page .progress {
    background-color: var(--border-color) !important;
}

#schedule-manager-page .progress-bar.bg-accent {
    background-color: var(--brand-accent) !important;
    box-shadow: 0 0 8px rgba(25, 211, 140, 0.4);
}

/* 5. Day selector radio pills */
#schedule-manager-page .btn-check + label {
    border-color: var(--border-color) !important;
    color: var(--text-muted) !important;
    background-color: var(--bg-card) !important;
    transition: all 0.2s ease;
}

#schedule-manager-page .btn-check + label:hover {
    color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
}

#schedule-manager-page .btn-check:checked + label {
    border-color: var(--brand-accent) !important;
    color: #0f172a !important;
    background-color: var(--brand-accent) !important;
    font-weight: 800 !important;
    box-shadow: 0 2px 8px rgba(25, 211, 140, 0.3) !important;
}

#schedule-manager-page .btn-check:checked + label i {
    color: inherit !important;
}

/* 6. Calendar Widget Specific Styles */
#schedule-manager-page .calendar-day-cell {
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
}

#schedule-manager-page .calendar-day-cell:hover {
    background-color: rgba(25, 211, 140, 0.15) !important;
    color: var(--brand-accent) !important;
    font-weight: 800 !important;
}

/* 7. Button & Icon Color Inheritances for Dark Mode Blending */
#schedule-manager-page .btn-accent,
#schedule-manager-page .btn-custom.btn-accent {
    background-color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
    color: #0f172a !important;
}

#schedule-manager-page .btn-accent i,
#schedule-manager-page .btn-custom.btn-accent i {
    color: inherit !important;
}

#schedule-manager-page .btn-accent:hover {
    background-color: var(--brand-accent-hover) !important;
    border-color: var(--brand-accent-hover) !important;
    color: #0f172a !important;
}

/* 8. Full-Height Scrollbar covering the entire Daily Occupancy Grid card body */
#schedule-manager-page .custom-scroll::-webkit-scrollbar { 
    width: 6px; 
}
#schedule-manager-page .custom-scroll::-webkit-scrollbar-track { 
    background: rgba(0, 0, 0, 0.02); 
}
#schedule-manager-page .custom-scroll::-webkit-scrollbar-thumb { 
    background: var(--border-color); 
    border-radius: 10px; 
}
#schedule-manager-page .custom-scroll::-webkit-scrollbar-thumb:hover { 
    background: var(--brand-accent); 
}

/* 9. Navigation Pills Overrides */
#schedule-manager-page .nav-pills .nav-link { 
    color: var(--text-muted) !important; 
    transition: all 0.2s ease-in-out; 
}
#schedule-manager-page .nav-pills .nav-link i {
    color: inherit !important;
}
#schedule-manager-page .nav-pills .nav-link:hover { 
    color: var(--brand-accent) !important; 
}
#schedule-manager-page .nav-pills .nav-link.active { 
    background-color: var(--brand-accent) !important; 
    color: #0f172a !important; 
    box-shadow: 0 2px 10px rgba(25, 211, 140, 0.25) !important;
}
#schedule-manager-page .nav-pills .nav-link.active i {
    color: #0f172a !important;
}

/* 10. Utility helper classes */
#schedule-manager-page .fw-extrabold { font-weight: 800 !important; }
#schedule-manager-page .cursor-help { cursor: help; }
#schedule-manager-page .x-small { font-size: 0.65rem !important; }
</style>