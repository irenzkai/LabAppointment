{{-- resources/views/services/partials/modals.blade.php --}}

{{-- 1. EDIT INDIVIDUAL TEST MODAL --}}
<div class="modal fade" id="editModal{{ $service->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('services.update', $service->id) }}" method="POST" class="modal-content border bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color) !important; border-radius: 14px;">
            @csrf
            @method('PUT')
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3" style="border-bottom: 1px solid var(--border-color) !important;">
                <h5 class="modal-title text-main fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-accent fs-5"></i>
                    <span>Edit Diagnostic Test: {{ $service->name }}</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start">
                <div class="row g-3">
                    {{-- Service Name --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Test / Procedure Name</label>
                        <input type="text" name="name" class="form-control uppercase fw-bold" value="{{ old('name', $service->name) }}" required>
                        <small class="text-muted smaller">Must remain unique across all individual diagnostic tests.</small>
                    </div>

                    {{-- Price (PHP) --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Price (PHP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-secondary bg-opacity-10 text-accent fw-bold border-secondary border-opacity-25">₱</span>
                            <input type="number" step="0.01" min="0" name="price" class="form-control fw-bold" value="{{ old('price', $service->price) }}" required>
                        </div>
                    </div>

                    {{-- Estimated Duration --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Est. Duration (Mins)</label>
                        <input type="number" min="1" name="estimated_time" class="form-control fw-bold" value="{{ old('estimated_time', $service->estimated_time ?: 5) }}" required>
                    </div>

                    {{-- Gender Restriction --}}
                    <div class="col-md-4">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Gender Eligibility</label>
                        <select name="gender_restriction" class="form-select fw-bold" required>
                            <option value="both" {{ old('gender_restriction', $service->gender_restriction) === 'both' ? 'selected' : '' }}>All Genders</option>
                            <option value="male" {{ old('gender_restriction', $service->gender_restriction) === 'male' ? 'selected' : '' }}>Male Only</option>
                            <option value="female" {{ old('gender_restriction', $service->gender_restriction) === 'female' ? 'selected' : '' }}>Female Only</option>
                        </select>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Clinical Description</label>
                        <textarea name="description" class="form-control" rows="2" required>{{ old('description', $service->description) }}</textarea>
                    </div>

                    {{-- Preparation Instructions --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Patient Preparation Protocol</label>
                        <textarea name="preparation" class="form-control" rows="2" required>{{ old('preparation', $service->preparation) }}</textarea>
                    </div>

                    {{-- Samples Required Section --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-2 uppercase d-block">Biological Specimens Required</label>
                        <div class="p-3 border rounded-3" style="background-color: var(--bg-main); border-color: var(--border-color) !important;">
                            <div class="d-flex flex-wrap gap-3 mb-3" id="sample-container-{{ $service->id }}">
                                @php 
                                    $defaults = ['Blood', 'Urine', 'Stool', 'Swab', 'N/A'];
                                    $currentSamples = array_filter(array_map('trim', explode(',', $service->sample_required ?? '')));
                                @endphp

                                {{-- Render Default Options --}}
                                @foreach($defaults as $sample)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $sample }}" id="check-{{ Str::slug($sample) }}-{{ $service->id }}" {{ in_array($sample, $currentSamples) ? 'checked' : '' }}>
                                        <label class="form-check-label text-main small cursor-pointer" for="check-{{ Str::slug($sample) }}-{{ $service->id }}">
                                            @if($sample === 'Blood')
                                                <i class="bi bi-droplet-half text-danger me-1"></i>
                                            @elseif($sample === 'Urine')
                                                <i class="bi bi-cup-straw text-warning me-1"></i>
                                            @endif
                                            {{ $sample }}
                                        </label>
                                    </div>
                                @endforeach

                                {{-- Render Custom Existing Options --}}
                                @foreach($currentSamples as $current)
                                    @if(!in_array($current, $defaults) && !empty($current))
                                        <div class="form-check d-flex align-items-center gap-2 custom-sample-item">
                                            <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $current }}" id="check-custom-{{ Str::slug($current) }}-{{ $service->id }}" checked>
                                            <label class="form-check-label text-accent fw-bold small cursor-pointer" for="check-custom-{{ Str::slug($current) }}-{{ $service->id }}">{{ $current }}</label>
                                            <button type="button" class="btn btn-link text-danger p-0 ms-1" onclick="this.parentElement.remove()" title="Remove Specimen"><i class="bi bi-x-circle"></i></button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Add Custom Sample Input --}}
                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                <input type="text" id="custom-input-{{ $service->id }}" class="form-control" placeholder="Add custom specimen type...">
                                <button class="btn btn-outline-accent fw-bold" type="button" onclick="addCustomSample('{{ $service->id }}')">
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
                    <i class="bi bi-check2-circle me-1"></i>UPDATE TEST
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 2. ARCHIVE SERVICE CONFIRMATION MODAL --}}
<div class="modal fade" id="delModal{{ $service->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-warning bg-card shadow-lg text-center p-4" style="background-color: var(--bg-card); border-color: rgba(255, 193, 7, 0.4) !important; border-radius: 14px;">
            <div class="mb-3">
                <i class="bi bi-archive text-warning display-4"></i>
            </div>
            <h5 class="text-main fw-bold mb-1 uppercase">Archive Service?</h5>
            <p class="text-secondary small mb-4">You are about to archive <strong>{{ $service->name }}</strong>. This test will be hidden from patient bookings but can be reactivated at any time from the archived services directory on this page.</p>
            <div class="d-grid gap-2">
                <form action="{{ route('services.destroy', $service->id) }}" method="POST" class="m-0">
                    @csrf 
                    @method('DELETE')
                    <button type="submit" class="btn btn-warning w-100 py-2 fw-bold uppercase text-dark">
                        <i class="bi bi-archive me-1"></i>ARCHIVE SERVICE
                    </button>
                </form>
                <button type="button" class="btn btn-link text-secondary text-decoration-none smaller" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof window.addCustomSample !== 'function') {
    window.addCustomSample = function(id) {
        const input = document.getElementById('custom-input-' + id);
        const container = document.getElementById('sample-container-' + id);
        if (!input || !container) return;
        const val = input.value.trim();
        if (!val) return;
        
        const customId = 'custom_sample_' + id + '_' + Date.now();
        const div = document.createElement('div');
        div.className = 'form-check d-flex align-items-center gap-2 custom-sample-item';
        div.innerHTML = `
            <input class="form-check-input" type="checkbox" name="samples[]" value="${val}" id="${customId}" checked>
            <label class="form-check-label text-accent fw-bold small cursor-pointer" for="${customId}">${val}</label>
            <button type="button" class="btn btn-link text-danger p-0 ms-1" onclick="this.parentElement.remove()" title="Remove Specimen"><i class="bi bi-x-circle"></i></button>
        `;
        container.appendChild(div);
        input.value = '';
    };
}
</script>