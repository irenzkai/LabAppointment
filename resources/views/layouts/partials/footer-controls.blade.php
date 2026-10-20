<!-- FLOATING UI CONTROLS -->
<div class="floating-controls">
    
    <!-- 1. BACK TO TOP BUTTON -->
    <button type="button" 
            id="btn-back-to-top" 
            class="btn-float shadow" 
            title="Scroll to Top"
            style="display: none;">
        <i class="bi bi-arrow-up-short fs-4"></i>
    </button>

    <!-- 2. THEME SWITCHER -->
    <button type="button" 
            id="theme-toggle" 
            class="btn-float shadow" 
            title="Switch Color Mode">
        <i class="bi bi-moon-stars-fill fs-5" id="theme-icon"></i>
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
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    /* =========================================================================
       THEME CONTRAST: LIGHT MODE (Default)
       Controls circles use dark Oxford Blue backgrounds with vibrant Shamrock 
       Green icons/accents to stand out distinctly against light surfaces.
       ========================================================================= */
    #btn-back-to-top,
    #theme-toggle {
        background-color: var(--brand-dark);
        border: 1.5px solid rgba(25, 211, 140, 0.3);
        color: var(--brand-accent) !important;
        box-shadow: 0 4px 14px rgba(28, 35, 45, 0.25);
    }
    #btn-back-to-top:hover,
    #theme-toggle:hover {
        background-color: var(--brand-accent);
        border-color: var(--brand-accent);
        color: var(--brand-dark) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(25, 211, 140, 0.35);
    }

    /* =========================================================================
       THEME CONTRAST: DARK MODE OVERRIDES
       Controls circles use elevated deep card tone with crisp luminous Shamrock 
       Green borders and soft glow to stand out against deep black/dark surfaces.
       ========================================================================= */
    [data-bs-theme="dark"] #btn-back-to-top,
    [data-bs-theme="dark"] #theme-toggle {
        background-color: #222d3b;
        border: 1.5px solid var(--brand-accent);
        color: var(--brand-accent) !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6), 0 0 10px rgba(25, 211, 140, 0.2);
    }
    [data-bs-theme="dark"] #btn-back-to-top:hover,
    [data-bs-theme="dark"] #theme-toggle:hover {
        background-color: var(--brand-accent);
        border-color: var(--brand-accent);
        color: #1c232d !important;
        transform: translateY(-2px);
        box-shadow: 0 0 20px rgba(25, 211, 140, 0.5);
    }

    /* Mobile Adjustment */
    @media (max-width: 576px) {
        .floating-controls { 
            bottom: 20px; 
            right: 20px; 
        }
        .btn-float { 
            width: 42px; 
            height: 42px; 
        }
    }
</style>