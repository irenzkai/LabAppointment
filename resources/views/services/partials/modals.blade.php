{{-- 1. EDIT SERVICE MODAL (Symmetric Contrast Fix) --}}
<div class="modal fade" id="editModal{{$service->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('services.update', $service->id) }}" method="POST" class="modal-content border-secondary bg-card shadow-lg">
            @csrf
            @method('PUT')
            
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-main fw-bold uppercase small">
                    <i class="bi bi-pencil-square me-2"></i>Edit Service: {{ $service->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start">
                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Service Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $service->name }}" required>
                    </div>

                    {{-- Price & Category --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Price (PHP)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="{{ $service->price }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Category</label>
                        <select name="category" class="form-select">
                            <option value="individual" {{ $service->category == 'individual' ? 'selected' : '' }}>Individual Test</option>
                            <option value="package" {{ $service->category == 'package' ? 'selected' : '' }}>Test Package</option>
                        </select>
                    </div>

                    {{-- Gender Restriction --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Gender Restriction</label>
                        <select name="gender_restriction" class="form-select">
                            <option value="both" {{ $service->gender_restriction == 'both' ? 'selected' : '' }}>All Genders</option>
                            <option value="male" {{ $service->gender_restriction == 'male' ? 'selected' : '' }}>Male Only</option>
                            <option value="female" {{ $service->gender_restriction == 'female' ? 'selected' : '' }}>Female Only</option>
                        </select>
                    </div>

                    {{-- Estimated Time --}}
                    <div class="col-md-6">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Est. Duration (Minutes)</label>
                        <input type="number" name="estimated_time" class="form-control" value="{{ $service->estimated_time }}" required>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Description</label>
                        <textarea name="description" class="form-control" rows="2" required>{{ $service->description }}</textarea>
                    </div>

                    {{-- Preparation --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-1 uppercase">Preparation Instructions</label>
                        <textarea name="preparation" class="form-control" rows="2" required>{{ $service->preparation }}</textarea>
                    </div>

                    {{-- Samples Required Section --}}
                    <div class="col-12">
                        <label class="small text-secondary fw-bold mb-2 uppercase d-block">Samples Required</label>
                        <div class="p-3 border border-secondary border-opacity-25 rounded" style="background-color: rgba(108, 117, 125, 0.05) !important;">
                            <div id="sample-container-{{ $service->id }}" class="d-flex flex-wrap gap-3 mb-3">
                                @php 
                                    $defaults = ['Blood', 'Urine', 'Stool', 'Swab', 'N/A'];
                                    $currentSamples = explode(',', $service->sample_required);
                                @endphp

                                {{-- Render Default Options --}}
                                @foreach($defaults as $sample)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $sample }}" id="check-{{ $sample }}-{{ $service->id }}" {{ in_array($sample, $currentSamples) ? 'checked' : '' }}>
                                        <label class="form-check-label text-main smaller" for="check-{{ $sample }}-{{ $service->id }}">{{ $sample }}</label>
                                    </div>
                                @endforeach

                                {{-- Render Custom Existing Options --}}
                                @foreach($currentSamples as $current)
                                    @if(!in_array($current, $defaults) && !empty($current))
                                        <div class="form-check d-flex align-items-center gap-2 custom-sample-item">
                                            <input class="form-check-input" type="checkbox" name="samples[]" value="{{ $current }}" checked>
                                            <span class="text-neon fw-bold smaller">{{ $current }}</span>
                                            <button type="button" class="btn btn-link text-danger p-0" onclick="this.parentElement.remove()"><i class="bi bi-x-circle"></i></button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Add Custom Sample Input --}}
                            <div class="input-group input-group-sm" style="max-width: 300px;">
                                <input type="text" id="custom-input-{{ $service->id }}" class="form-control bg-card text-main" placeholder="Add custom type...">
                                <button class="btn btn-outline-accent" type="button" onclick="addCustomSample('{{ $service->id }}')">ADD</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FIXED: Changed bg-secondary bg-opacity-5 to bg-transparent --}}
            <div class="modal-footer border-secondary border-top border-secondary border-opacity-10 bg-transparent p-3">
                <button type="button" class="btn-custom btn-outline-secondary py-2" data-bs-dismiss="modal">CANCEL</button>
                <button type="submit" class="btn-custom btn-accent py-2 px-4 fw-bold uppercase">UPDATE SERVICE</button>
            </div>
        </form>
    </div>
</div>

{{-- 2. DELETE SERVICE MODAL (Symmetric Contrast Fix) --}}
<div class="modal fade" id="delModal{{$service->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-danger bg-card shadow-lg text-center p-4">
            <div class="mb-3">
                <i class="bi bi-exclamation-triangle text-danger display-4"></i>
            </div>
            <h5 class="text-main fw-bold mb-1 uppercase">Remove Service?</h5>
            <p class="text-secondary small mb-4">You are about to delete <strong>{{ $service->name }}</strong>. This action cannot be undone.</p>
            
            <div class="d-grid gap-2">
                <form action="{{ route('services.destroy', $service->id) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-custom btn-danger-custom w-100 py-2 fw-bold uppercase">PERMANENTLY DELETE</button>
                </form>
                <button type="button" class="btn btn-link text-secondary text-decoration-none smaller" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>