{{-- resources/views/services/partials/add-package-modal.blade.php --}}
{{-- CREATE NEW HEALTH PACKAGE MODAL --}}
<div class="modal fade" id="addPackageModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="{{ route('packages.store') }}" method="POST" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color) !important; border-radius: 14px;">
            @csrf
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3" style="border-bottom: 1px solid var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam-fill fs-5"></i>
                    <span>Create Health Diagnostic Package</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 text-start">
                <div class="row g-3">
                    {{-- Package Name --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Title</label>
                        <input type="text" name="name" class="form-control uppercase fw-bold" placeholder="e.g. COMPREHENSIVE EXECUTIVE WELLNESS PANEL" value="{{ old('name') }}" required>
                        <small class="text-muted smaller">Must be unique across all diagnostic packages.</small>
                    </div>

                    {{-- Price (PHP) --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Price (PHP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-secondary bg-opacity-10 text-accent fw-bold border-secondary border-opacity-25">₱</span>
                            <input type="number" step="0.01" min="0" name="price" id="pkg_new_price" class="form-control fw-bold" placeholder="0.00" value="{{ old('price') }}" required oninput="calculateNewPkgSavings()">
                        </div>
                    </div>

                    {{-- Estimated Duration --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Est. Duration (Mins)</label>
                        <input type="number" min="1" name="estimated_time" class="form-control fw-bold" placeholder="e.g. 15" value="{{ old('estimated_time', 15) }}">
                    </div>

                    {{-- Gender Restriction --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Gender Eligibility</label>
                        <select name="gender_restriction" class="form-select fw-bold" required>
                            <option value="both" {{ old('gender_restriction', 'both') === 'both' ? 'selected' : '' }}>All Genders</option>
                            <option value="male" {{ old('gender_restriction') === 'male' ? 'selected' : '' }}>Male Only</option>
                            <option value="female" {{ old('gender_restriction') === 'female' ? 'selected' : '' }}>Female Only</option>
                        </select>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Summary of what this healthcare package screens for...">{{ old('description') }}</textarea>
                    </div>

                    {{-- Preparation Instructions --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Package Patient Preparation Protocol</label>
                        <textarea name="preparation" class="form-control" rows="2" placeholder="e.g. 10-12 hours fasting required. Drink water only...">{{ old('preparation', 'Follow individual test preparation guidelines.') }}</textarea>
                    </div>

                    {{-- Constituent Individual Services Selection --}}
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1.5 flex-wrap gap-2">
                            <label class="small text-secondary fw-bold uppercase mb-0">
                                <i class="bi bi-check2-square text-accent me-1"></i>Select Included Tests (Required)
                            </label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-link text-accent p-0 smaller text-decoration-none fw-bold" onclick="toggleAllPkgServicesNew(true)">Select All</button>
                                <span class="text-muted smaller">|</span>
                                <button type="button" class="btn btn-link text-muted p-0 smaller text-decoration-none fw-bold" onclick="toggleAllPkgServicesNew(false)">Clear</button>
                            </div>
                        </div>

                        {{-- Search Filter & Price Metric Bar --}}
                        <div class="p-3 border rounded-3" style="background-color: var(--bg-main); border-color: var(--border-color) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div class="input-group input-group-sm" style="max-width: 280px;">
                                    <span class="input-group-text bg-card border-secondary border-opacity-25 text-muted">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" id="pkgNewServiceSearch" class="form-control shadow-none" placeholder="Search tests to include..." oninput="filterPkgServicesNew(this.value)">
                                </div>
                                <div class="small fw-bold text-end">
                                    <span class="text-muted" id="pkgNewSelectedCount">0 tests selected</span>
                                    <span class="mx-1 text-muted">&bull;</span>
                                    <span class="text-accent" id="pkgNewStandaloneSum">Standalone Sum: ₱0.00</span>
                                </div>
                            </div>

                            {{-- Scrollable Services Grid --}}
                            <div class="row g-2 overflow-auto custom-scroll" id="pkgNewServicesContainer" style="max-height: 240px;">
                                @php
                                    $allIndividualServices = $availableServices ?? $services ?? \App\Models\Service::where('is_available', true)->orderBy('name')->get();
                                @endphp
                                @forelse($allIndividualServices as $svc)
                                    <div class="col-12 col-md-6 pkg-svc-item-row" data-name="{{ strtoupper($svc->name) }}">
                                        <div class="form-check p-2 rounded border bg-card d-flex align-items-center justify-content-between gap-2 h-100" style="border-color: var(--border-color) !important;">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate">
                                                <input class="form-check-input ms-0 me-1 pkg-new-svc-chk" type="checkbox" name="service_ids[]" value="{{ $svc->id }}" data-price="{{ $svc->price }}" id="chk_pkg_svc_{{ $svc->id }}" onchange="calculateNewPkgSavings()">
                                                <label class="form-check-label text-main small cursor-pointer text-truncate" for="chk_pkg_svc_{{ $svc->id }}">
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
                    <i class="bi bi-box-seam me-1.5"></i>CREATE PACKAGE
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function filterPkgServicesNew(query) {
    const q = (query || '').toUpperCase().trim();
    document.querySelectorAll('#pkgNewServicesContainer .pkg-svc-item-row').forEach(row => {
        const name = row.dataset.name || '';
        row.classList.toggle('d-none', q !== '' && !name.includes(q));
    });
}

function toggleAllPkgServicesNew(checkAll) {
    document.querySelectorAll('.pkg-new-svc-chk').forEach(chk => {
        const row = chk.closest('.pkg-svc-item-row');
        if (!row.classList.contains('d-none')) {
            chk.checked = checkAll;
        }
    });
    calculateNewPkgSavings();
}

function calculateNewPkgSavings() {
    let totalStandalone = 0;
    let count = 0;
    document.querySelectorAll('.pkg-new-svc-chk:checked').forEach(chk => {
        totalStandalone += parseFloat(chk.dataset.price || 0);
        count++;
    });

    const countEl = document.getElementById('pkgNewSelectedCount');
    const sumEl = document.getElementById('pkgNewStandaloneSum');
    if (countEl) countEl.innerText = `${count} test${count === 1 ? '' : 's'} selected`;
    if (sumEl) sumEl.innerText = `Standalone Sum: ₱` + totalStandalone.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>