{{-- CREATE NEW SERVICE MODAL --}}
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('services.store') }}" method="POST" class="modal-content border-secondary bg-card shadow-lg text-start" style="background-color: var(--bg-card); color: var(--text-main);">
            @csrf
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-accent fw-bold uppercase small m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <span>Create New Diagnostic Service</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 text-start">
                <div class="row g-3">
                    {{-- Service Name --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Service Name</label>
                        <input type="text" name="name" class="form-control uppercase fw-bold" placeholder="e.g. Complete Blood Count (CBC)" value="{{ old('name') }}" required>
                    </div>

                    {{-- Price & Category --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Price (PHP)</label>
                        <input type="number" step="0.01" min="0" name="price" class="form-control fw-bold" placeholder="0.00" value="{{ old('price') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Category</label>
                        <select name="category" class="form-select fw-bold" required>
                            <option value="individual" {{ old('category') == 'individual' ? 'selected' : '' }}>Individual Test</option>
                            <option value="package" {{ old('category') == 'package' ? 'selected' : '' }}>Test Package</option>
                        </select>
                    </div>

                    {{-- Gender Restriction & Estimated Duration --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Gender Restriction</label>
                        <select name="gender_restriction" class="form-select fw-bold" required>
                            <option value="both" {{ old('gender_restriction', 'both') == 'both' ? 'selected' : '' }}>All Genders</option>
                            <option value="male" {{ old('gender_restriction') == 'male' ? 'selected' : '' }}>Male Only</option>
                            <option value="female" {{ old('gender_restriction') == 'female' ? 'selected' : '' }}>Female Only</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Est. Duration (Minutes)</label>
                        <input type="number" min="1" name="estimated_time" class="form-control fw-bold" placeholder="e.g. 15" value="{{ old('estimated_time', 15) }}" required>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of clinical examination scope..." required>{{ old('description') }}</textarea>
                    </div>

                    {{-- Preparation Instructions --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Preparation Instructions</label>
                        <textarea name="preparation" class="form-control" rows="2" placeholder="e.g. 8-10 hours fasting required before blood draw..." required>{{ old('preparation', 'No special preparation required.') }}</textarea>
                    </div>

                    {{-- Samples Required Section --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-2 uppercase d-block">Samples Required</label>
                        <div class="p-3 border border-secondary border-opacity-25 rounded-3" style="background-color: var(--bg-main);">
                            <div class="d-flex flex-wrap gap-3 mb-3" id="sample-container-new">
                                @php $defaultSamples = ['Blood', 'Urine', 'Stool', 'Swab', 'N/A']; @endphp
                                @foreach($defaultSamples as $sample)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $sample }}" id="check-new-{{ $sample }}" {{ $sample == 'Blood' ? 'checked' : '' }}>
                                        <label class="form-check-label text-main small cursor-pointer" for="check-new-{{ $sample }}">{{ $sample }}</label>
                                    </div>
                                @endforeach
                            </div>
                            
                            {{-- Add Custom Sample Input --}}
                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                <input type="text" id="custom-input-new" class="form-control" placeholder="Add custom specimen type...">
                                <button class="btn btn-outline-accent fw-bold" type="button" onclick="addCustomSampleNew()">ADD SPECIMEN</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-secondary border-top border-opacity-10 bg-transparent p-3">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i>CREATE SERVICE
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
        <button type="button" class="btn btn-link text-danger p-0 ms-1" onclick="this.parentElement.remove()"><i class="bi bi-x-circle"></i></button>
    `;
    container.appendChild(div);
    input.value = '';
}
</script>