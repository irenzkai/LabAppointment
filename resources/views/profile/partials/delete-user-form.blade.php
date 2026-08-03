{{-- Bypassed bg-danger with explicit inline RGBA background for excellent contrast --}}
<div class="card p-4 p-md-5 border-danger shadow-lg animate-page text-start" style="background-color: rgba(220, 53, 69, 0.05) !important;">
    
    {{-- Card Header (Fully visible now) --}}
    <h5 class="text-danger fw-bold mb-3 border-bottom border-danger border-opacity-25 pb-2 uppercase" style="letter-spacing: 1px;">
        Delete Account
    </h5>

    {{-- Warning Description --}}
    <p class="text-muted small mb-4" style="line-height: 1.6;">
        Once you submit an account deletion request, your profile will be immediately deactivated and placed in a secure, restricted-access archive. In compliance with medical record-keeping and audit regulations, all associated appointment files, family dependent records, and diagnostic histories will be safely retained for <strong>10 years</strong> before being permanently purged from our databases. If your account is not reactivated during this 10-year deactivation window, all records will be irreversibly destroyed.
    </p>

    {{-- Permanent Deletion Trigger Button --}}
    <button type="button" class="btn-custom btn-danger-custom py-3 px-4 fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#confirmSelfDelete">
        DELETE ACCOUNT PERMANENTLY
    </button>
</div>