<div class="card p-4 p-md-5 border-secondary shadow-lg animate-page" style="background-color: var(--bg-card); color: var(--text-main);">
    {{-- Header with Page Link Trigger --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-25 pb-2 text-start">
        <div>
            <h5 class="text-main fw-bold mb-0 uppercase small" style="letter-spacing: 0.5px;">Manage Dependents</h5>
            <small class="text-muted d-block mt-1">This portal supports registered minor dependents (under 18 years of age) only.</small>
        </div>
        <a href="{{ route('dependents.create') }}" class="btn-custom btn-accent btn-sm text-decoration-none">
            <i class="bi bi-person-plus-fill me-1"></i> ADD NEW
        </a>
    </div>

    {{-- Family Dependents Deck --}}
    <div class="row g-3">
        @forelse($user->dependents as $dep)
        @php
            $isOver18 = $dep->birthdate->age >= 18;
        @endphp
        <div class="col-md-6 text-start">
            <div class="p-3 rounded border {{ $isOver18 ? 'border-warning border-opacity-40' : 'border-secondary border-opacity-20' }} d-flex justify-content-between align-items-center h-100" style="background-color: var(--bg-card); color: var(--text-main);">
                <div>
                    <div class="text-main fw-bold small">{{ strtoupper($dep->name) }}</div>
                    <div class="text-secondary mt-0.5" style="font-size: 0.65rem;">
                        {{ strtoupper($dep->relationship) }} <span class="mx-1">|</span> 
                        {{ strtoupper($dep->sex) }} <span class="mx-1">|</span> {{ $dep->birthdate->age }} YRS OLD
                        @if($isOver18)
                        <span class="badge bg-warning bg-opacity-10 text-warning ms-1" style="font-size: 0.6rem;">Deactivated (18+ Years Old)</span>
                        @endif
                    </div>
                    <div class="text-accent smaller mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-geo-alt-fill me-1"></i> {{ $dep->address }}
                    </div>
                </div>

                {{-- 3-Dot Action Dropdown Menu --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-1 px-2.5" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow bg-card border-secondary">
                        @if(!$isOver18)
                        <li>
                            <a class="dropdown-item small py-2" href="{{ route('dependents.edit', $dep->id) }}">
                                <i class="bi bi-pencil-square me-2 text-info"></i>Edit Details
                            </a>
                        </li>
                        @endif
                        <li>
                            <button type="button" class="dropdown-item small py-2 text-accent" data-bs-toggle="modal" data-bs-target="#promoteModal{{ $dep->id }}">
                                <i class="bi bi-arrow-up-circle me-2"></i>Promote Account
                            </button>
                        </li>
                        <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
                        <li>
                            <button type="button" class="dropdown-item small py-2 text-danger" data-bs-toggle="modal" data-bs-target="#deleteDepModal{{ $dep->id }}">
                                <i class="bi bi-trash me-2"></i>Remove
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @empty
        {{-- Unified Empty State Placeholder --}}
        <div class="col-12 text-center py-5">
            <i class="bi bi-people text-muted fs-1 mb-3 opacity-25 d-block"></i>
            <p class="text-secondary small italic mb-0">No family members registered yet.</p>
        </div>
        @endforelse
    </div>

    {{-- Archived Dependents Directory (Soft-delete recovery directory) --}}
    @php
        $archivedDeps = auth()->user()->dependents()->onlyTrashed()->get();
    @endphp
    @if(count($archivedDeps) > 0)
    <hr class="border-secondary border-opacity-25 my-4">
    <h6 class="text-warning fw-bold mb-3 uppercase small">Archived Dependents (Deactivated profiles folder)</h6>
    <div class="row g-3">
        @foreach($archivedDeps as $archived)
        <div class="col-md-6 text-start">
            <div class="p-3 rounded border border-warning border-opacity-20 d-flex justify-content-between align-items-center h-100" style="background-color: var(--bg-card); color: var(--text-main); border-style: dashed !important;">
                <div>
                    <div class="text-warning fw-bold small">{{ strtoupper($archived->name) }}</div>
                    <div class="text-secondary mt-0.5" style="font-size: 0.65rem;">
                        {{ strtoupper($archived->relationship) }} <span class="mx-1">|</span> {{ strtoupper($archived->sex) }} <span class="mx-1">|</span> {{ $archived->birthdate->age }} YRS OLD (ARCHIVED)
                    </div>
                </div>
                <form action="{{ route('dependents.restore', $archived->id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning py-1 px-2.5 fw-bold uppercase">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> RESTORE
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- MODALS LOOP FOR EACH DEPENDENT RECORD (Delete Confirmation & Promotion Modals) --}}
@foreach($user->dependents as $dep)
    @php
        $isOver18 = $dep->birthdate->age >= 18;
        $promoUrl = route('register', ['promote' => \Illuminate\Support\Facades\Crypt::encryptString($dep->id)]);
    @endphp

    {{-- 1. THEMED DELETE CONFIRMATION MODAL --}}
    <div class="modal fade" id="deleteDepModal{{ $dep->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                <div class="modal-header py-3" style="border-bottom: 1px solid var(--border-color);">
                    <h6 class="modal-title text-danger fw-bold m-0">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Remove Family Member?
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="small mb-0 text-muted">
                        Are you sure you want to deactivate and remove <strong style="color: var(--text-main);">{{ $dep->name }}</strong> from your family dependents list? In compliance with minor clinical archiving regulations, their profile and associated historical lab records will be safely retained for <strong>25 years</strong> before being permanently purged.
                    </p>
                </div>
                <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3" style="border-top: 1px solid var(--border-color); width: 100% !important;">
                    <div class="row g-2 w-100 m-0" style="display: flex !important; flex-direction: row !important; width: 100% !important;">
                        <div class="col-6 p-1">
                            <button type="button" class="btn btn-outline-secondary w-100 py-3" data-bs-dismiss="modal" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">Cancel</button>
                        </div>
                        <div class="col-6 p-1">
                            <form action="{{ route('dependents.destroy', $dep->id) }}" method="POST" class="m-0 w-100">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-accent w-100 py-3" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. THEMED INDEPENDENT ACCOUNT PROMOTION MODAL --}}
    <div class="modal fade" id="promoteModal{{ $dep->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
            <div class="modal-content border-secondary bg-card shadow-lg text-start" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                    <h5 class="modal-title text-accent fw-bold small"><i class="bi bi-arrow-up-circle me-2"></i>PROMOTE CHILD ACCOUNT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Promoting <strong>{{ strtoupper($dep->name) }}</strong> will allow them to manage their own logins, schedule records, and profile options independently.
                    </p>
                    <div class="alert alert-clinical border-secondary bg-dark bg-opacity-25 p-3 rounded mb-4" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle text-accent me-2"></i>
                        <strong>Clinical History Retention:</strong> All historic appointment results, files, and diagnostics will be safely transitioned to their new independent account automatically once registered.
                    </div>

                    {{-- Shareable Link Input with Inline success tracking messages --}}
                    <div class="mb-4">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Shareable Registration Link</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm" value="{{ $promoUrl }}" id="link-{{ $dep->id }}" style="background-color: var(--bg-main) !important; color: var(--text-main) !important; border-color: var(--border-color) !important;" readonly>
                            <button class="btn btn-outline-accent btn-sm fw-bold" onclick="copyPromoLink('{{ $dep->id }}')">Copy Link</button>
                        </div>
                        <div id="copy-success-{{ $dep->id }}" class="text-success small mt-1 d-none fw-bold" style="font-size: 0.75rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> Successfully copied to clipboard!
                        </div>
                    </div>

                    {{-- Logout & Register Button --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ $promoUrl }}">
                        <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase">Logout & Register Them Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
<script>
// --- SHAREABLE PROMOTION LINK INLINE SUCCESS COPYS ---
function copyPromoLink(depId) {
    const input = document.getElementById(`link-${depId}`);
    if (input) {
        navigator.clipboard.writeText(input.value).then(() => {
            const successMsg = document.getElementById(`copy-success-${depId}`);
            if (successMsg) {
                successMsg.classList.remove('d-none');
                setTimeout(() => {
                    successMsg.classList.add('d-none');
                }, 3000);
            }
        }).catch(err => {
            console.error("Failed to copy link:", err);
        });
    }
}
</script>
@endpush