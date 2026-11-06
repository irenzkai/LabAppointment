{{-- resources/views/services/partials/add-modal.blade.php --}}
{{-- CREATE NEW ATOMIC SERVICE MODAL --}}
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('services.store') }}" method="POST" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color) !important; border-radius: 14px;">
            @csrf
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3" style="border-bottom: 1px solid var(--border-color) !important;">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <span>Create Individual Diagnostic Test</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 text-start">
                <div class="row g-3">
                    {{-- Service Name --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Test / Procedure Name</label>
                        <input type="text" name="name" class="form-control uppercase fw-bold" placeholder="e.g. Complete Blood Count (CBC) with Platelet" value="{{ old('name') }}" required>
                        <small class="text-muted smaller">Must be unique across all individual diagnostic tests.</small>
                    </div>

                    {{-- Price (PHP) --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Price (PHP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-secondary bg-opacity-10 text-accent fw-bold border-secondary border-opacity-25">₱</span>
                            <input type="number" step="0.01" min="0" name="price" class="form-control fw-bold" placeholder="0.00" value="{{ old('price') }}" required>
                        </div>
                    </div>

                    {{-- Estimated Duration --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Est. Duration (Mins)</label>
                        <input type="number" min="1" name="estimated_time" class="form-control fw-bold" placeholder="e.g. 5" value="{{ old('estimated_time', 5) }}" required>
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
                        <label class="small text-secondary fw-bold mb-1 uppercase">Clinical Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of clinical examination scope, method, and diagnostics..." required>{{ old('description') }}</textarea>
                    </div>

                    {{-- Preparation Instructions --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Patient Preparation Protocol</label>
                        <textarea name="preparation" class="form-control" rows="2" placeholder="e.g. 8-10 hours fasting required before blood draw; avoid greasy meals..." required>{{ old('preparation', 'No special preparation required.') }}</textarea>
                    </div>

                    {{-- Samples Required Section --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-2 uppercase d-block">Biological Specimens Required</label>
                        <div class="p-3 border rounded-3" style="background-color: var(--bg-main); border-color: var(--border-color) !important;">
                            @php
                                $sampleCatalog = \Illuminate\Support\Facades\Schema::hasTable('samples') 
                                    ? \Illuminate\Support\Facades\DB::table('samples')->pluck('name')->toArray() 
                                    : ['Blood', 'Urine', 'Stool', 'Swab', 'N/A'];
                                if (empty($sampleCatalog)) {
                                    $sampleCatalog = ['Blood', 'Urine', 'Stool', 'Swab', 'N/A'];
                                }
                            @endphp
                            <div class="d-flex flex-wrap gap-3 mb-3" id="sample-container-new">
                                @foreach($sampleCatalog as $sample)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $sample }}" id="check-new-{{ Str::slug($sample) }}" {{ $sample === 'Blood' ? 'checked' : '' }}>
                                        <label class="form-check-label text-main small cursor-pointer" for="check-new-{{ Str::slug($sample) }}">
                                            @if($sample === 'Blood')
                                                <i class="bi bi-droplet-half text-danger me-1"></i>
                                            @elseif($sample === 'Urine')
                                                <i class="bi bi-cup-straw text-warning me-1"></i>
                                            @endif
                                            {{ $sample }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            
                            {{-- Add Custom Sample Input --}}
                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                <input type="text" id="custom-input-new" class="form-control" placeholder="Add custom specimen type...">
                                <button class="btn btn-outline-accent fw-bold" type="button" onclick="addCustomSampleNew()">
                                    <i class="bi bi-plus"></i> ADD
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2" style="border-color: var(--border-color) !important; background-color: var(--bg-card);">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i>CREATE TEST
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function addCustomSampleNew() {
    const input = document.getElementById('custom-input-new');
    const container = document.getElementById('sample-container-new');
    if (!input || !container) return;
    const val = input.value.trim();
    if (!val) return;
    
    const id = 'custom_sample_' + Date.now();
    const div = document.createElement('div');
    div.className = 'form-check d-flex align-items-center gap-2 custom-sample-item';
    div.innerHTML = `
        <input class="form-check-input" type="checkbox" name="samples[]" value="${val}" id="${id}" checked>
        <label class="form-check-label text-accent fw-bold small cursor-pointer" for="${id}">${val}</label>
        <button type="button" class="btn btn-link text-danger p-0 ms-1" onclick="this.parentElement.remove()" title="Remove Specimen"><i class="bi bi-x-circle"></i></button>
    `;
    container.appendChild(div);
    input.value = '';
}
</script>