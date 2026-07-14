<style>
/* 1. Scope input elements, groups, and selections strictly to avoid global layout leaks */
#schedule-manager-page .form-control, 
#schedule-manager-page .form-select, 
#schedule-manager-page .input-group-text, 
#schedule-manager-page .form-control:focus, 
#schedule-manager-page .form-select:focus {
    background-color: var(--bg-card) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}

/* Scope switch containers and background classes */
#schedule-manager-page .form-check, 
#schedule-manager-page #openStatusContainer, 
#schedule-manager-page #lunchFields, 
#schedule-manager-page .bg-black, 
#schedule-manager-page .bg-dark, 
#schedule-manager-page .bg-opacity-50, 
#schedule-manager-page .bg-opacity-25, 
#schedule-manager-page .bg-secondary, 
#schedule-manager-page .bg-opacity-10 {
    background-color: rgba(25, 211, 140, 0.05) !important;
    border-color: var(--border-color) !important;
    color: var(--text-main) !important;
}

#schedule-manager-page .form-check-label, 
#schedule-manager-page .form-control::placeholder, 
#schedule-manager-page .text-white, 
#schedule-manager-page .text-secondary {
    color: var(--text-main) !important;
}

#schedule-manager-page .text-muted, 
#schedule-manager-page .smaller.text-muted {
    color: var(--text-muted) !important;
}

#schedule-manager-page .progress {
    background-color: var(--border-color) !important;
}

#schedule-manager-page .progress-bar {
    box-shadow: 0 0 10px var(--brand-accent);
}

#schedule-manager-page .btn-check + label {
    border-color: var(--border-color) !important;
    color: var(--text-main) !important;
    background-color: var(--bg-card) !important;
}

#schedule-manager-page .btn-check:checked + label {
    border-color: var(--brand-accent) !important;
    color: #1c232d !important;
    background-color: var(--brand-accent) !important;
    font-weight: bold;
    box-shadow: 0 0 8px rgba(25, 211, 140, 0.2);
}

/* Calendar Widget Specific Styles */
#schedule-manager-page .calendar-day-cell:hover {
    background-color: rgba(25, 211, 140, 0.15) !important;
    color: var(--brand-accent) !important;
    font-weight: bold;
}

#schedule-manager-page .shadow-neon { box-shadow: 0 0 15px var(--neon); }
#schedule-manager-page .cursor-help { cursor: help; }
#schedule-manager-page .x-small { font-size: 0.65rem; }
#schedule-manager-page .custom-scroll::-webkit-scrollbar { width: 4px; }
#schedule-manager-page .custom-scroll::-webkit-scrollbar-thumb { background: var(--neon); border-radius: 10px; }
#schedule-manager-page .nav-pills .nav-link { color: #888; transition: 0.3s; }
#schedule-manager-page .nav-pills .nav-link.active { background-color: var(--neon) !important; color: #000 !important; }
</style>