<div class="card p-4 p-md-5 border-secondary shadow-lg animate-page" style="background-color: var(--bg-card); color: var(--text-main);">
    {{-- Header with Dynamic "Add New" Modal Trigger --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-25 pb-2 text-start">
        <h5 class="text-main fw-bold mb-0 uppercase small" style="font-size: 1.1rem; letter-spacing: 0.5px;">Manage Dependents</h5>
        <button class="btn-custom btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#addDepModal">
            <i class="bi bi-person-plus-fill me-1"></i> ADD NEW
        </button>
    </div>

    {{-- Family Dependents Deck --}}
    <div class="row g-3">
        @forelse($user->dependents as $dep)
            <div class="col-md-6 text-start">
                <div class="p-3 rounded border border-secondary border-opacity-20 d-flex justify-content-between align-items-center h-100" style="background-color: var(--bg-card); color: var(--text-main);">
                    <div>
                        <div class="text-main fw-bold small">{{ strtoupper($dep->name) }}</div>
                        <div class="text-secondary mt-0.5" style="font-size: 0.65rem;">
                            {{ strtoupper($dep->relationship) }} <span class="mx-1">|</span> {{ strtoupper($dep->sex) }} <span class="mx-1">|</span> {{ $dep->birthdate->age }} YRS OLD
                        </div>
                        <div class="text-accent smaller mt-1">
                            <i class="bi bi-geo-alt-fill me-1"></i> {{ $dep->address }}
                        </div>
                    </div>
                    
                    {{-- Action Control Buttons --}}
                    <div class="d-flex gap-1.5">
                        {{-- Edit Button --}}
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5" data-bs-toggle="modal" data-bs-target="#editDepModal{{ $dep->id }}" style="color: var(--text-muted) !important; border-color: var(--border-color) !important;">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        
                        {{-- Delete Trigger Button --}}
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 border-0" data-bs-toggle="modal" data-bs-target="#deleteDepModal{{ $dep->id }}">
                            <i class="bi bi-trash"></i>
                        </button>
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
</div>

{{-- MODALS LOOP FOR EACH DEPENDENT RECORD --}}
@foreach($user->dependents as $dep)
    
    {{-- 1. THEMED DELETE CONFIRMATION MODAL --}}
    <div class="modal fade" id="deleteDepModal{{ $dep->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                <div class="modal-header py-3" style="border-bottom: 1px solid var(--border-color);">
                    <h6 class="modal-title text-danger fw-bold uppercase m-0">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Remove Family Member?
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <p class="small mb-0 text-muted">
                        Are you sure you want to remove <strong style="color: var(--text-main);">{{ $dep->name }}</strong> from your family dependents list? This action will delete their profile permanently.
                    </p>
                </div>
                <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                    <div class="d-flex w-100">
                        <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('dependents.destroy', $dep->id) }}" method="POST" class="w-50 m-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-link text-decoration-none w-100 py-3 fw-bold uppercase smaller hover-bg-neon" style="color: var(--brand-accent); border-radius: 0;">Remove</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. EDIT DEPENDENT MODAL --}}
    <div class="modal fade" id="editDepModal{{ $dep->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('dependents.update', $dep->id) }}" method="POST" id="editDependentForm{{ $dep->id }}" class="modal-content shadow-lg edit-dep-form" data-dep-id="{{ $dep->id }}" onsubmit="compileEditDepAddress('{{ $dep->id }}')" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                @csrf @method('PUT')
                <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                    <h5 class="modal-title text-main fw-bold small">EDIT FAMILY MEMBER RECORD</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4 text-start">
                    
                    {{-- Split Name Fields --}}
                    <h6 class="text-accent mb-3 small fw-bold uppercase">Personal Identity</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">First Name</label>
                            <input type="text" name="first_name" class="form-control uppercase" value="{{ $dep->first_name }}" required>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="text-secondary smaller fw-bold mb-0 uppercase" style="color: var(--text-muted);">Middle Name</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_dep_no_mn_{{ $dep->id }}" onclick="toggleEditDepMN('{{ $dep->id }}', this)" {{ $dep->middle_name == 'N/A' ? 'checked' : '' }}>
                                    <label class="smaller text-secondary" style="font-size: 0.6rem;" for="edit_dep_no_mn_{{ $dep->id }}">None</label>
                                </div>
                            </div>
                            <input type="text" name="middle_name" id="edit_dep_middle_name_{{ $dep->id }}" class="form-control uppercase" value="{{ $dep->middle_name }}" {{ $dep->middle_name == 'N/A' ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Last Name</label>
                            <input type="text" name="last_name" class="form-control uppercase" value="{{ $dep->last_name }}" required>
                        </div>
                    </div>

                    {{-- Demographics --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control" value="{{ $dep->birthdate->format('Y-m-d') }}" required max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Sex</label>
                            <select name="sex" class="form-select" required>
                                <option value="Male" {{ $dep->sex == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ $dep->sex == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Relationship</label>
                            <input type="text" name="relationship" class="form-control" value="{{ $dep->relationship }}" required>
                        </div>
                    </div>

                    @php
                        $isInherited = ($dep->street === $user->street && $dep->barangay === $user->barangay && $dep->city === $user->city && $dep->province === $user->province);
                    @endphp

                    <div class="mb-3 border-top border-secondary border-opacity-25 pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-accent small fw-bold uppercase mb-0">Home Address</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="inherit_address" id="editUseMyAddress{{ $dep->id }}" value="1" onchange="toggleEditDepAddress('{{ $dep->id }}', this)" {{ $isInherited ? 'checked' : '' }}>
                                <label class="form-check-label text-neon fw-bold" style="font-size: 0.6rem;" for="editUseMyAddress{{ $dep->id }}">INHERIT MY ADDRESS</label>
                            </div>
                        </div>

                        {{-- 1. Registered Address on File (Always visible) --}}
                        <div class="alert alert-clinical p-2.5 mb-3 border border-secondary border-opacity-10 text-start" style="background-color: rgba(0,0,0,0.015); border-radius: 8px;">
                            <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Registered Address on File:</div>
                            <div class="text-main small" id="dep-reg-address-{{ $dep->id }}">{{ $dep->address }}</div>
                        </div>

                        {{-- 2. Inherited Parent Address Preview Box (Shown if checked) --}}
                        <div id="edit_inherited_address_preview_{{ $dep->id }}" class="alert alert-clinical p-2.5 mb-3 border border-neon border-opacity-25 text-start {{ $isInherited ? '' : 'd-none' }}" style="background-color: rgba(25, 211, 140, 0.03); border-radius: 8px;">
                            <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Inherited Parent Address:</div>
                            <div class="text-main small">{{ $user->address }}</div>
                        </div>

                        {{-- 3. Dynamic cascading selects, revealed if "Inherit my address" is untoggled --}}
                        <div id="manual_edit_dep_address_wrapper_{{ $dep->id }}" class="row g-3 {{ $isInherited ? 'd-none' : '' }}">
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Province</label>
                                <select id="edit_dep_province_{{ $dep->id }}" name="province" class="form-select" onchange="fetchEditDepCities('{{ $dep->id }}', this.value)">
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">City / Municipality</label>
                                <select id="edit_dep_city_{{ $dep->id }}" name="city" class="form-select" onchange="fetchEditDepBarangays('{{ $dep->id }}', this.value)" disabled>
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Barangay</label>
                                <select id="edit_dep_brgy_{{ $dep->id }}" name="barangay" class="form-select" disabled>
                                    <option value="">Select City First</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Street / House No.</label>
                                <input type="text" id="edit_dep_street_{{ $dep->id }}" name="street" class="form-control uppercase" value="{{ $dep->street }}">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3">
                    <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase">UPDATE FAMILY RECORD</button>
                </div>
            </form>
        </div>
    </div>
@endforeach