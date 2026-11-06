{{-- resources/views/services/partials/package-modals.blade.php --}}

{{-- 1. EDIT HEALTH PACKAGE MODAL --}}
<div class="modal fade" id="editPackageModal{{ $package->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="{{ route('packages.update', $package->id) }}" method="POST" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color) !important; border-radius: 14px;">
            @csrf
            @method('PUT')
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3" style="border-bottom: 1px solid var(--border-color) !important;">
                <h5 class="modal-title text-main fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-accent fs-5"></i>
                    <span>Edit Package: {{ $package->name }}</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 text-start">
                <div class="row g-3">
                    {{-- Package Name --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Title</label>
                        <input type="text" name="name" class="form-control uppercase fw-bold" value="{{ old('name', $package->name) }}" required>
                        <small class="text-muted smaller">Must remain unique across all diagnostic packages.</small>
                    </div>

                    {{-- Price (PHP) --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Price (PHP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-secondary bg-opacity-10 text-accent fw-bold border-secondary border-opacity-25">₱</span>
                            <input type="number" step="0.01" min="0" name="price" id="pkg_edit_price_{{ $package->id }}" class="form-control fw-bold" value="{{ old('price', $package->price) }}" required oninput="calculateEditPkgSavings('{{ $package->id }}')">
                        </div>
                    </div>

                    {{-- Estimated Duration --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Est. Duration (Mins)</label>
                        <input type="number" min="1" name="estimated_time" class="form-control fw-bold" value="{{ old('estimated_time', $package->estimated_time ?: 15) }}">
                    </div>

                    {{-- Gender Restriction --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Gender Eligibility</label>
                        <select name="gender_restriction" class="form-select fw-bold" required>
                            <option value="both" {{ old('gender_restriction', $package->gender_restriction) === 'both' ? 'selected' : '' }}>All Genders</option>
                            <option value="male" {{ old('gender_restriction', $package->gender_restriction) === 'male' ? 'selected' : '' }}>Male Only</option>
                            <option value="female" {{ old('gender_restriction', $package->gender_restriction) === 'female' ? 'selected' : '' }}>Female Only</option>
                        </select>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Summary of what this healthcare package screens for...">{{ old('description', $package->description) }}</textarea>
                    </div>

                    {{-- Preparation Instructions --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Patient Preparation Protocol</label>
                        <textarea name="preparation" class="form-control" rows="2" placeholder="e.g. 10-12 hours fasting required. Drink water only...">{{ old('preparation', $package->preparation) }}</textarea>
                    </div>

                    {{-- Constituent Individual Services Selection --}}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1.5 flex-wrap gap-2">
                            <label class="small text-secondary fw-bold uppercase mb-0">
                                <i class="bi bi-check2-square text-accent me-1"></i>Bundled Individual Tests (Required)
                            </label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-link text-accent p-0 smaller text-decoration-none fw-bold" onclick="toggleAllPkgServicesEdit('{{ $package->id }}', true)">Select All</button>
                                <span class="text-muted smaller">|</span>
                                <button type="button" class="btn btn-link text-muted p-0 smaller text-decoration-none fw-bold" onclick="toggleAllPkgServicesEdit('{{ $package->id }}', false)">Clear</button>
                            </div>
                        </div>

                        {{-- Search Filter & Price Metric Bar --}}
                        <div class="p-3 border rounded-3" style="background-color: var(--bg-main); border-color: var(--border-color) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div class="input-group input-group-sm" style="max-width: 280px;">
                                    <span class="input-group-text bg-card border-secondary border-opacity-25 text-muted">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" id="pkgEditServiceSearch_{{ $package->id }}" class="form-control shadow-none" placeholder="Search tests..." oninput="filterPkgServicesEdit('{{ $package->id }}', this.value)">
                                </div>
                                <div class="small fw-bold text-end">
                                    <span class="text-muted" id="pkgEditSelectedCount_{{ $package->id }}">
                                        {{ $package->services->count() }} {{ Str::plural('test', $package->services->count()) }} selected
                                    </span>
                                    <span class="mx-1 text-muted">&bull;</span>
                                    <span class="text-accent" id="pkgEditStandaloneSum_{{ $package->id }}">
                                        Standalone Sum: ₱{{ number_format($package->services->sum('price'), 2) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Scrollable Services Grid --}}
                            <div class="row g-2 overflow-auto custom-scroll" id="pkgEditServicesContainer_{{ $package->id }}" style="max-height: 240px;">
                                @php
                                    $allIndividualServices = $availableServices ?? $services ?? \App\Models\Service::where('is_available', true)->orderBy('name')->get();
                                    $activePkgServiceIds = $package->services->pluck('id')->toArray();
                                @endphp
                                @forelse($allIndividualServices as $svc)
                                    <div class="col-12 col-md-6 pkg-edit-svc-row-{{ $package->id }}" data-name="{{ strtoupper($svc->name) }}">
                                        <div class="form-check p-2 rounded border bg-card d-flex align-items-center justify-content-between gap-2 h-100" style="border-color: var(--border-color) !important;">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                <input class="form-check-input ms-0 me-1 pkg-edit-svc-chk-{{ $package->id }}" type="checkbox" name="service_ids[]" value="{{ $svc->id }}" data-price="{{ $svc->price }}" id="chk_pkg_edit_svc_{{ $package->id }}_{{ $svc->id }}" {{ in_array($svc->id, $activePkgServiceIds) ? 'checked' : '' }} onchange="calculateEditPkgSavings('{{ $package->id }}')">
                                                <label class="form-check-label text-main small cursor-pointer text-truncate" for="chk_pkg_edit_svc_{{ $package->id }}_{{ $svc->id }}">
                                                    {{ strtoupper($svc->name) }}
                                                </label>
                                            </div>
                                            <span class="text-muted smaller fw-bold text-nowrap">₱{{ number_format($svc->price, 2) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center text-muted small py-3">
                                        No active individual services found to bundle.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important; background-color: var(--bg-card);">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i>UPDATE PACKAGE
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 2. ARCHIVE HEALTH PACKAGE CONFIRMATION MODAL --}}
<div class="modal fade" id="delPackageModal{{ $package->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-warning bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border-color: rgba(255, 193, 7, 0.4) !important; border-radius: 14px;">
            <div class="mb-3">
                <i class="bi bi-archive text-warning display-4"></i>
            </div>
            <h5 class="text-main fw-bold mb-1 uppercase">Archive Package?</h5>
            <p class="text-secondary small mb-4">You are about to archive <strong>{{ $package->name }}</strong>. This package will be hidden from patient bookings but can be reactivated at any time from the archived catalog directory.</p>
            <div class="d-grid gap-2">
                <form action="{{ route('packages.destroy', $package->id) }}" method="POST" class="m-0">
                    @csrf 
                    @method('DELETE')
                    <button type="submit" class="btn btn-warning w-100 py-2 fw-bold uppercase text-dark">
                        <i class="bi bi-archive me-1"></i>ARCHIVE PACKAGE
                    </button>
                </form>
                <button type="button" class="btn btn-link text-secondary text-decoration-none smaller" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof window.filterPkgServicesEdit !== 'function') {
    window.filterPkgServicesEdit = function(pkgId, query) {
        const q = (query || '').toUpperCase().trim();
        document.querySelectorAll(`#pkgEditServicesContainer_${pkgId} .pkg-edit-svc-row-${pkgId}`).forEach(row => {
            const name = row.dataset.name || '';
            row.classList.toggle('d-none', q !== '' && !name.includes(q));
        });
    };
}

if (typeof window.toggleAllPkgServicesEdit !== 'function') {
    window.toggleAllPkgServicesEdit = function(pkgId, checkAll) {
        document.querySelectorAll(`.pkg-edit-svc-chk-${pkgId}`).forEach(chk => {
            const row = chk.closest(`.pkg-edit-svc-row-${pkgId}`);
            if (!row.classList.contains('d-none')) {
                chk.checked = checkAll;
            }
        });
        calculateEditPkgSavings(pkgId);
    };
}

if (typeof window.calculateEditPkgSavings !== 'function') {
    window.calculateEditPkgSavings = function(pkgId) {
        let totalStandalone = 0;
        let count = 0;
        document.querySelectorAll(`.pkg-edit-svc-chk-${pkgId}:checked`).forEach(chk => {
            totalStandalone += parseFloat(chk.dataset.price || 0);
            count++;
        });

        const countEl = document.getElementById(`pkgEditSelectedCount_${pkgId}`);
        const sumEl = document.getElementById(`pkgEditStandaloneSum_${pkgId}`);
        if (countEl) countEl.innerText = `${count} test${count === 1 ? '' : 's'} selected`;
        if (sumEl) sumEl.innerText = `Standalone Sum: ₱` + totalStandalone.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
}
</script>