<!-- FLOATING UI CONTROLS -->
<div class="floating-controls">
    
    <!-- 1. BACK TO TOP BUTTON -->
    <button type="button" 
            id="btn-back-to-top" 
            class="btn-float shadow-sm" 
            title="Scroll to Top"
            style="display: none;">
        <i class="bi bi-arrow-up-short fs-4"></i>
    </button>

    <!-- 2. THEME SWITCHER -->
    <button type="button" 
            id="theme-toggle" 
            class="btn-float shadow-sm" 
            title="Switch Color Mode">
        <i class="bi bi-sun-fill fs-5" id="theme-icon"></i>
    </button>
</div>

<style>
    /* Floating Controls Container */
    .floating-controls {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        z-index: 2000;
    }

    /* Base Button Style */
    .btn-float {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    /* Back to Top Styling */
    #btn-back-to-top {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        color: var(--brand-accent) !important;
    }
    #btn-back-to-top:hover {
        background-color: var(--brand-accent);
        color: var(--brand-dark) !important;
    }

    /* Theme Toggle Styling */
    #theme-toggle {
        background-color: var(--brand-dark);
        color: var(--brand-accent) !important;
    }
    #theme-toggle:hover {
        background-color: var(--brand-accent);
        color: var(--brand-dark) !important;
    }

    /* Mobile Adjustment */
    @media (max-width: 576px) {
        .floating-controls { bottom: 20px; right: 20px; }
        .btn-float { width: 40px; height: 40px; }
    }
</style>