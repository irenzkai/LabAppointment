<!-- PAGE 3: SELECT TESTS -->
<div class="wiz-section d-none text-start animate-page" id="page-3">

    {{-- Step Header & Search --}}
    <div class="mb-4 border-bottom border-secondary border-opacity-10 pb-3">
        <h3 class="text-main fw-bold mb-1 uppercase tracking-tighter">Step 3: Select Tests</h3>
        <p class="text-secondary small">Choose the laboratory examinations requested by your physician.</p>
        
        {{-- Search Bar --}}
        <div class="mt-3">
            <div class="input-group">
                <span class="input-group-text bg-secondary bg-opacity-10 border-secondary border-opacity-25 text-secondary">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="testSearch" class="form-control shadow-none" placeholder="Type test name (e.g. CBC, Lipid Profile, X-Ray)...">
            </div>
        </div>
    </div>

    {{-- Consolidated Test Deck Container --}}
    <div class="test-list-container custom-scroll border border-secondary border-opacity-25 rounded bg-card" style="max-height: 520px; overflow-y: auto;">
        @foreach($services as $s)
            <div class="test-item border-bottom border-secondary border-opacity-10 transition-all p-3" data-name="{{ strtoupper($s->name) }}">
                <div class="d-flex align-items-center justify-content-between">
                    
                    {{-- Checkbox + Summary Info --}}
                    <div class="d-flex align-items-center flex-grow-1">
                        <input type="checkbox" name="service_ids[]" value="{{ $s->id }}" id="test_{{ $s->id }}" class="btn-check test-checkbox" 
                            data-name="{{ $s->name }}" data-price="{{ $s->price }}" data-sample="{{ $s->sample_required ?? 'N/A' }}" data-time="{{ $s->estimated_time ?? 0 }}" onchange="updateSummary();">
                        
                        <label class="d-flex align-items-center cursor-pointer mb-0" for="test_{{ $s->id }}">
                            {{-- Checkbox Indicator --}}
                            <div class="check-indicator rounded border border-secondary me-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; flex-shrink: 0;">
                                <i class="bi bi-check-lg text-dark d-none"></i>
                            </div>
                            
                            <div>
                                <div class="text-main fw-bold small uppercase mb-1">{{ $s->name }}</div>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.6rem;">
                                        <i class="bi bi-droplet-fill text-danger me-1"></i>{{ $s->sample_required ?? 'N/A' }}
                                    </span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary smaller" style="font-size: 0.6rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $s->formatted_time }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- Price & Accordion Toggle Control --}}
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-accent fw-bold" style="font-size: 0.95rem;">&#x20B1;{{ number_format($s->price, 2) }}</div>
                        <button type="button" class="btn btn-sm btn-link p-0 text-secondary" onclick="toggleTestDetails('details_{{ $s->id }}', this)" title="Expand Details">
                            <i class="bi bi-chevron-down fs-5"></i>
                        </button>
                    </div>
                </div>

                {{-- Theme-Aware Accordion Drawer Content --}}
                <div id="details_{{ $s->id }}" class="test-details-drawer d-none mt-3 p-3 rounded text-start animate-page">
                    <p class="mb-2 small text-muted lh-base" style="font-size:0.8rem;">{{ $s->description }}</p>
                    @if($s->preparation)
                        <div class="pt-2 border-top border-secondary border-opacity-25 mt-2">
                            <small class="prep-badge d-block mb-1" style="font-size:0.7rem;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>PREPARATION REQUIRED:
                            </small>
                            <p class="mb-0 small text-muted" style="font-size:0.75rem;">{{ $s->preparation }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Navigation --}}
    <div class="d-flex gap-2 mt-5">
        <button type="button" class="btn-custom btn-outline-secondary w-50 py-3" onclick="goToPage(2)">
            <i class="bi bi-arrow-left me-2"></i> BACK
        </button>
        <button type="button" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm" id="btn-to-page4" onclick="validateStep3()">
            NEXT: CHOOSE SCHEDULE <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </div>
</div>

<style>
    /* Accordion & Selection High-Contrast styling overrides */
    .test-item {
        transition: all 0.2s ease-in-out;
    }
    .test-item:hover {
        background-color: rgba(25, 211, 140, 0.02);
    }
    .selected-test-item {
        background-color: rgba(25, 211, 140, 0.04) !important;
        border-left: 4px solid var(--brand-accent) !important;
    }
    .test-checkbox:checked + label .check-indicator {
        background-color: var(--brand-accent);
        border-color: var(--brand-accent) !important;
    }
    .test-checkbox:checked + label .check-indicator i {
        display: block !important;
    }
    
    /* Theme-Adaptive Drawer Styling */
    .test-details-drawer {
        background-color: rgba(0, 0, 0, 0.02) !important;
        border: 1px solid var(--border-color) !important;
    }
    [data-bs-theme="dark"] .test-details-drawer {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    
    /* Preparation warnings text contrast levels */
    .prep-badge {
        color: #b58105 !important;
        font-weight: 700;
    }
    [data-bs-theme="dark"] .prep-badge {
        color: #ffc107 !important;
    }
    
    .test-list-container.custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .test-list-container.custom-scroll::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.03);
    }
    .test-list-container.custom-scroll::-webkit-scrollbar-thumb {
        background: var(--brand-accent);
        border-radius: 10px;
    }
</style>