<!-- PAGE 3: SELECT TESTS -->
<div class="wiz-section d-none text-start animate-page" id="page-3">
    
    {{-- Step Header & Search --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-end mb-1">
            <div>
                {{-- FIXED: Changed text-white to text-main to support light background modes --}}
                <h3 class="text-main fw-bold mb-0 uppercase tracking-tighter">Step 3: Select Tests</h3>
                <p class="text-secondary small">Choose the laboratory examinations requested by your physician.</p>
            </div>
        </div>
        
        {{-- Search Bar --}}
        <div class="mt-3">
            <div class="input-group">
                {{-- FIXED: Removed bg-dark and text-white to match theme variables --}}
                <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="testSearch" class="form-control shadow-none" placeholder="Type test name (e.g. CBC, Lipid Profile, X-Ray)...">
            </div>
        </div>
    </div>

    {{-- Scrollable Test List --}}
    {{-- FIXED: Changed bg-black bg-opacity-50 to bg-card --}}
    <div class="test-list-container border border-secondary border-opacity-25 rounded bg-card overflow-hidden" style="max-height: 480px; overflow-y: auto;">
        @foreach($services as $s)
        <div class="test-item border-bottom border-secondary border-opacity-10 transition-all">
            <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="test_{{ $s->id }}" class="btn-check test-checkbox" data-name="{{ $s->name }}" data-price="{{ $s->price }}" data-sample="{{ $s->sample_required ?? 'N/A' }}" data-time="{{ $s->estimated_time ?? 0 }}" onchange="updateSummary(); updateTestBadge();">
            
            <label class="d-flex align-items-center justify-content-between p-3 cursor-pointer w-100" for="test_{{ $s->id }}">
                <div class="d-flex align-items-center me-3">
                    
                    {{-- Check Icon Indicator --}}
                    <div class="check-indicator rounded border border-secondary me-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; flex-shrink: 0;">
                        <i class="bi bi-check-lg text-dark d-none"></i>
                    </div>
                    
                    <div>
                        {{-- FIXED: Changed text-white to text-main --}}
                        <div class="text-main fw-bold small uppercase mb-1">{{ $s->name }}</div>
                        <div class="d-flex gap-2">
                            {{-- FIXED: Removed bg-dark and border-secondary to support dynamic themes --}}
                            <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.6rem;">
                                <i class="bi bi-droplet-fill text-danger me-1"></i>{{ $s->sample_required ?? 'N/A' }}
                            </span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.6rem;">
                                <i class="bi bi-clock me-1"></i>{{ $s->formatted_time }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    {{-- FIXED: Changed text-neon to text-accent --}}
                    <div class="text-accent fw-bold">&#x20B1;{{ number_format($s->price, 2) }}</div>
                </div>
            </label>
        </div>
        @endforeach
    </div>

    {{-- Navigation --}}
    <div class="d-flex gap-2 mt-5">
        <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(2)">
            <i class="bi bi-arrow-left me-2"></i> BACK
        </button>
        {{-- FIXED: Changed btn-neon to btn-accent, removed disabled state, and assigned validateStep3() handler --}}
        <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="btn-to-page4" onclick="validateStep3()">
            NEXT: CHOOSE SCHEDULE <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
    
</div>

<script>
// Local script for search filtering within Step 3
document.getElementById('testSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.test-item').forEach(item => {
        const name = item.querySelector('.text-main').innerText.toLowerCase();
        if (name.includes(query)) {
            item.classList.remove('d-none');
        } else {
            item.classList.add('d-none');
        }
    });
});
</script>

<style>
/* Styling for the custom list checkboxes */
.test-item:hover {
    background-color: rgba(25, 211, 140, 0.03);
}
.test-checkbox:checked + label {
    background-color: rgba(25, 211, 140, 0.08);
}
.test-checkbox:checked + label .check-indicator {
    background-color: var(--brand-accent);
    border-color: var(--brand-accent) !important;
}
.test-checkbox:checked + label .check-indicator i {
    display: block !important;
}
.test-item.d-none {
    display: none !important;
}
</style>