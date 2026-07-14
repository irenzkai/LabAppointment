{{-- FIXED: Bypassed bg-danger with explicit inline RGBA background for excellent contrast --}}
<div class="card p-4 p-md-5 border-danger shadow-lg animate-page text-start" style="background-color: rgba(220, 53, 69, 0.05) !important;">
    {{-- Card Header (Fully visible now) --}}
    <h5 class="text-danger fw-bold mb-3 border-bottom border-danger border-opacity-25 pb-2 uppercase" style="letter-spacing: 1px;">
        Delete Account
    </h5>
    
    {{-- Warning Description --}}
    <p class="text-muted small mb-4" style="line-height: 1.6;">
        Once your account is deleted, all of its personal records, physical scan databases, family dependent profiles, and transaction histories will be permanently purged from active storage. This action is irreversible.
    </p>
    
    {{-- Permanent Deletion Trigger Button --}}
    <button type="button" class="btn-custom btn-danger-custom py-3 px-4 fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#confirmSelfDelete">
        DELETE ACCOUNT PERMANENTLY
    </button>
</div>