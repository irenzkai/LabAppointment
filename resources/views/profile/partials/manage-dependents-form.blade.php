<div class="card p-4 p-md-5 border-secondary shadow-lg animate-page" style="background-color: var(--bg-card); color: var(--text-main);">
    {{-- Header with Dynamic "Add New" Modal Trigger --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-25 pb-2 text-start">
        <div>
            <h5 class="text-main fw-bold mb-0 uppercase small" style="letter-spacing: 0.5px;">Manage Dependents</h5>
            <small class="text-muted d-block mt-1">This portal supports registered minor dependents (under 18 years of age) only.</small>
        </div>
        <button class="btn-custom btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#addDepModal">
            <i class="bi bi-person-plus-fill me-1"></i> ADD NEW
        </button>
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
                            {{ strtoupper($dep->relationship) }} <span class="mx-1">|</span> {{ strtoupper($dep->sex) }} <span class="mx-1">|</span> {{ $dep->birthdate->age }} YRS OLD
                            @if($isOver18)
                                <span class="badge bg-warning bg-opacity-10 text-warning ms-1" style="font-size: 0.6rem;">Deactivated (18+ Years Old)</span>
                            @endif
                        </div>
                        <div class="text-accent smaller mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-geo-alt-fill me-1"></i> {{ $dep->address }}
                        </div>
                    </div>

                    {{-- Action Control Buttons --}}
                    <div class="d-flex gap-1.5">
                        {{-- Edit Button (Hidden for Over-18 Dependents to block access) --}}
                        @if(!$isOver18)
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2.5" data-bs-toggle="modal" data-bs-target="#editDepModal{{ $dep->id }}" style="color: var(--text-muted) !important; border-color: var(--border-color) !important;" title="Edit Details">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        @endif

                        {{-- Independent Account Promotion Button (Only available when minor status expires at 18+) --}}
                        @if($isOver18)
                            <button type="button" class="btn btn-sm btn-outline-neon py-1 px-2.5" data-bs-toggle="modal" data-bs-target="#promoteModal{{ $dep->id }}" title="Promote to Independent Account">
                                <i class="bi bi-arrow-up-circle"></i>
                            </button>
                        @endif

                        {{-- Delete Trigger Button --}}
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2.5 border-0" data-bs-toggle="modal" data-bs-target="#deleteDepModal{{ $dep->id }}" title="Remove Dependent">
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

{{-- MODALS LOOP FOR EACH DEPENDENT RECORD --}}
@foreach($user->dependents as $dep)
    @php
        $isOver18 = $dep->birthdate->age >= 18;
        $hasNoMiddleName = empty($dep->middle_name) || $dep->middle_name === 'N/A';
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
                        Are you sure you want to deactivate and remove <strong style="color: var(--text-main);">{{ $dep->name }}</strong> from your family dependents list? In compliance with minor clinical archiving regulations, their profile and associated historical lab records will be safely retained for <strong>25 years</strong> before being permanently purged. If you do not reactivate this dependent profile during this 25-year deactivation window, all records will be irreversibly destroyed.
                    </p>
                </div>

                {{-- Updated to grid alignment layout matching save panels --}}
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

    {{-- 2. EDIT DEPENDENT RECORD MODAL (Locked completely if turned 18) --}}
    @if(!$isOver18)
        <div class="modal fade" id="editDepModal{{ $dep->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form action="{{ route('dependents.update', $dep->id) }}" method="POST" id="editDependentForm{{ $dep->id }}" class="modal-content shadow-lg edit-dep-form" data-dep-id="{{ $dep->id }}" onsubmit="compileEditDepAddress('{{ $dep->id }}')">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                        <h5 class="modal-title text-main fw-bold small">EDIT FAMILY MEMBER RECORD</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4 text-start" style="color: var(--text-main);">
                        {{-- 1. Split Name Fields --}}
                        <h6 class="text-accent mb-3 small fw-bold uppercase">Personal Identity</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">First Name</label>
                                <input type="text" name="first_name" class="form-control uppercase" value="{{ $dep->first_name }}" oninput="this.value = this.value.replace(/[^a-zA-Z \s.\'-]/g, '')" required>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="text-secondary smaller fw-bold mb-0 uppercase" style="color: var(--text-muted);">Middle Name</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_dep_no_mn_{{ $dep->id }}" onclick="toggleEditDepMN('{{ $dep->id }}', this)" {{ $hasNoMiddleName ? 'checked' : '' }}>
                                        <label class="smaller text-secondary" style="font-size: 0.65rem;" for="edit_dep_no_mn_{{ $dep->id }}">None</label>
                                    </div>
                                </div>
                                <input type="text" name="middle_name" id="edit_dep_middle_name_{{ $dep->id }}" class="form-control uppercase {{ $hasNoMiddleName ? 'opacity-50' : '' }}" value="{{ $dep->middle_name ?? 'N/A' }}" oninput="this.value = this.value.replace(/[^a-zA-Z \s.\'-]/g, '')" {{ $hasNoMiddleName ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Last Name</label>
                                <input type="text" name="last_name" class="form-control uppercase" value="{{ $dep->last_name }}" oninput="this.value = this.value.replace(/[^a-zA-Z \s.\'-]/g, '')" required>
                            </div>
                            <div class="col-md-3">
                                <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Suffix (Opt.)</label>
                                <input type="text" name="suffix" id="edit_dep_suffix_{{ $dep->id }}" list="suffix_options" class="form-control uppercase" value="{{ old('suffix', $dep->suffix) }}" placeholder="e.g. JR" maxlength="10">
                            </div>
                        </div>

                        {{-- 2. Demographics --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Birthdate</label>
                                <input type="date" name="birthdate" class="form-control" value="{{ $dep->birthdate->format('Y-m-d') }}" required min="{{ now()->subYears(18)->addDay()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                                <small class="text-muted smaller">Dependents must be minors under 18 years of age.</small>
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
                                <select name="relationship" class="form-select" required>
                                    <option value="Son" {{ strtoupper($dep->relationship) === 'SON' ? 'selected' : '' }}>Son</option>
                                    <option value="Daughter" {{ strtoupper($dep->relationship) === 'DAUGHTER' ? 'selected' : '' }}>Daughter</option>
                                </select>
                            </div>
                        </div>

                        {{-- 3. Address Section --}}
                        <div class="mb-3 border-top border-secondary border-opacity-25 pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-accent small fw-bold uppercase mb-0">Home Address</h6>
                                <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="fetchParentAddress('edit_dep_', '{{ $dep->id }}')">
                                    <i class="bi bi-geo-alt-fill me-1.5"></i>Use Parent's Address
                                </button>
                            </div>

                            <div id="manual_edit_dep_address_wrapper_{{ $dep->id }}" class="row g-3">
                                <div class="col-md-6">
                                    <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Province</label>
                                    <select id="edit_dep_province_{{ $dep->id }}" name="province" class="form-select" onchange="fetchEditDepCities('{{ $dep->id }}', this.value, '', '')" required>
                                        <option value="">Select Province</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">City / Municipality</label>
                                    <select id="edit_dep_city_{{ $dep->id }}" name="city" class="form-select" onchange="fetchEditDepBarangays('{{ $dep->id }}', this.value, '')" disabled required>
                                        <option value="">Select Province First</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Barangay</label>
                                    <select id="edit_dep_barangay_{{ $dep->id }}" name="barangay" class="form-select" disabled required>
                                        <option value="">Select City First</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Street / House No.</label>
                                    <input type="text" id="edit_dep_street_{{ $dep->id }}" name="street" class="form-control uppercase" value="{{ $dep->street }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3" style="border-top: 1px solid var(--border-color); width: 100% !important;">
                        <div class="row g-2 w-100 m-0" style="display: flex !important; flex-direction: row !important; width: 100% !important;">
                            <div class="col-6 p-1">
                                <button type="button" class="btn btn-outline-secondary w-100 py-3" data-bs-dismiss="modal" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">Cancel</button>
                            </div>
                            <div class="col-6 p-1">
                                <button type="submit" class="btn btn-accent w-100 py-3" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">Update Family Record</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- 3. THEMED INDEPENDENT ACCOUNT PROMOTION MODAL --}}
    <div class="modal fade" id="promoteModal{{ $dep->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        @php
            $promoUrl = route('register', ['promote' => \Illuminate\Support\Facades\Crypt::encryptString($dep->id)]);
        @endphp
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
                        <strong>Clinical History Retention:</strong> All historic appointment results, files, and diagnostics will be safely transitioned to their new independent account automatically.
                    </div>

                    {{-- Shareable Link Input with Inline success tracking messages --}}
                    <div class="mb-4">
                        <label class="smaller text-secondary fw-bold mb-1 uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Shareable Registration Link</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm" value="{{ $promoUrl }}" id="link-{{ $dep->id }}" style="background-color: var(--bg-main) !important; color: var(--text-main) !important; border-color: var(--border-color) !important;" readonly>
                            <button class="btn btn-outline-accent btn-sm" onclick="copyPromoLink('{{ $dep->id }}')">Copy</button>
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

{{-- A. ADD DEPENDENT RECORD MODAL --}}
<div class="modal fade" id="addDepModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('dependents.store') }}" method="POST" id="addDependentForm" class="modal-content border-secondary bg-card shadow-lg" onsubmit="compileDependentAddress()">
            @csrf
            <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                <h5 class="modal-title text-main fw-bold small">ADD CHILD / DEPENDENT RECORD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-start" style="color: var(--text-main);">
                {{-- 1. Split Name Fields --}}
                <h6 class="text-accent mb-3 small fw-bold uppercase">Personal Identity</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">First Name</label>
                        <input type="text" name="first_name" class="form-control uppercase" placeholder="Given Name" oninput="this.value = this.value.replace(/[^a-zA-Z \s.\'-]/g, '')" required>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="text-secondary smaller fw-bold mb-0 uppercase" style="color: var(--text-muted);">Middle Name</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="dep_no_mn" onclick="toggleDepMN(this)">
                                <label class="smaller text-secondary" style="font-size: 0.65rem;" for="dep_no_mn">None</label>
                            </div>
                        </div>
                        <input type="text" name="middle_name" id="dep_middle_name" class="form-control uppercase" placeholder="Middle Name" oninput="this.value = this.value.replace(/[^a-zA-Z \s.\'-]/g, '')">
                    </div>
                    <div class="col-md-3">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Last Name</label>
                        <input type="text" name="last_name" class="form-control uppercase" placeholder="Surname" oninput="this.value = this.value.replace(/[^a-zA-Z \s.\'-]/g, '')" required>
                    </div>
                    <div class="col-md-3">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Suffix (Opt.)</label>
                        <input type="text" name="suffix" id="dep_suffix" list="suffix_options" class="form-control uppercase" placeholder="e.g. JR" maxlength="10">
                    </div>
                </div>

                {{-- 2. Demographics --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Birthdate</label>
                        <input type="date" name="birthdate" class="form-control" required min="{{ now()->subYears(18)->addDay()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                        <small class="text-muted smaller">Dependents must be minors under 18 years of age.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Sex</label>
                        <select name="sex" class="form-select" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="text-secondary smaller fw-bold mb-1 uppercase" style="color: var(--text-muted);">Relationship</label>
                        <select name="relationship" class="form-select" required>
                            <option value="" disabled selected>-- Select Relationship --</option>
                            <option value="Son">Son</option>
                            <option value="Daughter">Daughter</option>
                        </select>
                    </div>
                </div>

                {{-- 3. Address Section --}}
                <div class="mb-3 border-top border-secondary border-opacity-25 pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-accent small fw-bold uppercase mb-0">Home Address</h6>
                        <button type="button" class="btn btn-sm btn-outline-accent py-1 px-3 fw-bold" onclick="fetchParentAddress('dep_')">
                            <i class="bi bi-geo-alt-fill me-1.5"></i>Use Parent's Address
                        </button>
                    </div>

                    <div id="manual_dep_address_wrapper" class="row g-3">
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Province</label>
                            <select id="dep_province" name="province" class="form-select" onchange="fetchDepCities(this.value)" required>
                                <option value="">Select Province</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">City / Municipality</label>
                            <select id="dep_city" name="city" class="form-select" onchange="fetchDepBarangays(this.value)" disabled required>
                                <option value="">Select Province First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Barangay</label>
                            <select id="dep_barangay" name="barangay" class="form-select" disabled required>
                                <option value="">Select City First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="smaller text-secondary fw-bold mb-1 uppercase" style="color: var(--text-muted);">Street / House No.</label>
                            <input type="text" id="dep_street" name="street" class="form-control uppercase" placeholder="House/Lot/Block/Street" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3" style="border-top: 1px solid var(--border-color); width: 100% !important;">
                <div class="row g-2 w-100 m-0" style="display: flex !important; flex-direction: row !important; width: 100% !important;">
                    <div class="col-6 p-1">
                        <button type="button" class="btn btn-outline-secondary w-100 py-3" data-bs-dismiss="modal" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">Cancel</button>
                    </div>
                    <div class="col-6 p-1">
                        <button type="submit" class="btn btn-accent w-100 py-3" style="border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase !important;">Save to Family List</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Global Suffix Datalist --}}
<datalist id="suffix_options">
    <option value="JR">
    <option value="SR">
    <option value="II">
    <option value="III">
    <option value="IV">
    <option value="V">
</datalist>

@push('scripts')
    <script>
        // --- SHARABLE PROMOTION LINK INLINE SUCCESS COPYS ---
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

        // --- MIDDLE-NAME TOGGLE LOGIC FOR DEPENDENTS ---
        function toggleDepMN(checkbox) {
            const input = document.getElementById('dep_middle_name');
            if (input) {
                if (checkbox.checked) {
                    input.value = "N/A";
                    input.readOnly = true;
                    input.classList.add('opacity-50');
                } else {
                    input.value = "";
                    input.readOnly = false;
                    input.classList.remove('opacity-50');
                }
            }
        }

        function toggleEditDepMN(depId, checkbox) {
            const input = document.getElementById(`edit_dep_middle_name_${depId}`);
            if (input) {
                if (checkbox.checked) {
                    input.value = "N/A";
                    input.readOnly = true;
                    input.classList.add('opacity-50');
                } else {
                    input.value = "";
                    input.readOnly = false;
                    input.classList.remove('opacity-50');
                }
            }
        }

        // --- FULL DYNAMIC ADDRESS API CASCADE CORES ---
        async function fetchDepProvinces() {
            const provSel = document.getElementById('dep_province');
            if (!provSel) return;
            try {
                let res = await fetch('https://psgc.gitlab.io/api/provinces/');
                if (!res.ok) res = await fetch('https://psgc.gitlab.io/api/provinces');
                const data = await res.json();
                provSel.innerHTML = '<option value="">Select Province</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                    provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
                });
            } catch (e) {
                console.error("Dependent Province API Error", e);
            }
        }

        async function fetchDepCities(provCode) {
            const citySel = document.getElementById('dep_city');
            const brgySel = document.getElementById('dep_barangay');
            if (!citySel || !brgySel) return;

            citySel.disabled = true;
            brgySel.disabled = true;
            citySel.innerHTML = '<option value="">Loading Cities...</option>';

            try {
                let res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
                if (!res.ok) res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities`);
                const data = await res.json();
                citySel.innerHTML = '<option value="">Select City</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                    citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
                });
                citySel.disabled = false;
                brgySel.innerHTML = '<option value="">Select City First</option>';
            } catch (e) {
                console.error("Dependent City API Error", e);
            }
        }

        async function fetchDepBarangays(cityCode) {
            const brgySel = document.getElementById('dep_barangay');
            if (!brgySel) return;

            brgySel.disabled = true;
            brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

            try {
                let res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                if (!res.ok) res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays`);
                const data = await res.json();
                brgySel.innerHTML = '<option value="">Select Barangay</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                    brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
                });
                brgySel.disabled = false;
            } catch (e) {
                console.error("Dependent Barangay API Error", e);
            }
        }

        async function fetchEditDepCities(depId, provCode, savedCity, savedBrgy) {
            const citySel = document.getElementById(`edit_dep_city_${depId}`);
            const brgySel = document.getElementById(`edit_dep_barangay_${depId}`);
            if (!citySel || !brgySel) return;

            citySel.disabled = true;
            brgySel.disabled = true;
            citySel.innerHTML = '<option value="">Loading Cities...</option>';

            try {
                let res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
                if (!res.ok) res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities`);
                const data = await res.json();
                citySel.innerHTML = '<option value="">Select City</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                    citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
                });
                citySel.disabled = false;
                brgySel.innerHTML = '<option value="">Select City First</option>';

                if (savedCity) {
                    const selectedCityOpt = Array.from(citySel.options).find(opt => 
                        opt.text.toUpperCase().trim() === savedCity.toUpperCase().trim() || 
                        opt.value === savedCity.trim()
                    );
                    if (selectedCityOpt) {
                        citySel.value = selectedCityOpt.value;
                        await fetchEditDepBarangays(depId, selectedCityOpt.value, savedBrgy);
                    }
                }
            } catch (e) {
                console.error("Edit dependent city fetch failed:", e);
            }
        }

        async function fetchEditDepBarangays(depId, cityCode, savedBrgy) {
            const brgySel = document.getElementById(`edit_dep_barangay_${depId}`);
            if (!brgySel) return;

            brgySel.disabled = true;
            brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

            try {
                let res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                if (!res.ok) res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays`);
                const data = await res.json();
                brgySel.innerHTML = '<option value="">Select Barangay</option>';
                data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                    brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
                });
                brgySel.disabled = false;

                if (savedBrgy) {
                    const selectedBrgyOpt = Array.from(brgySel.options).find(opt => 
                        opt.text.toUpperCase().trim() === savedBrgy.toUpperCase().trim() || 
                        opt.value === savedBrgy.trim()
                    );
                    if (selectedBrgyOpt) {
                        brgySel.value = selectedBrgyOpt.value;
                    }
                }
            } catch (e) {
                console.error("Edit dependent barangay fetch failed:", e);
            }
        }

        // --- PARENT'S ADDRESS AUTO-FILL ("USE PARENT'S ADDRESS" TRIGGER) ---
        async function fetchParentAddress(prefix, depId = '') {
            const suffix = depId ? `_${depId}` : '';
            const provSel = document.getElementById(`${prefix}province${suffix}`);
            const citySel = document.getElementById(`${prefix}city${suffix}`);
            const brgySel = document.getElementById(`${prefix}barangay${suffix}`);
            const streetInput = document.getElementById(`${prefix}street${suffix}`);

            if (!provSel || !citySel || !brgySel || !streetInput) return;

            const parentProvName = "{{ trim($user->province) }}";
            const parentCityName = "{{ trim($user->city) }}";
            const parentBrgyName = "{{ trim($user->barangay) }}";
            const parentStreet = "{{ trim($user->street) }}";

            // Set loading states
            provSel.innerHTML = '<option value="">Loading Provinces...</option>';
            citySel.innerHTML = '<option value="">Loading Cities...</option>';
            brgySel.innerHTML = '<option value="">Loading Barangays...</option>';
            provSel.disabled = true;
            citySel.disabled = true;
            brgySel.disabled = true;

            try {
                // 1. Fetch Provinces
                let res = await fetch('https://psgc.gitlab.io/api/provinces/');
                if (!res.ok) res = await fetch('https://psgc.gitlab.io/api/provinces');
                const provinces = await res.json();

                provSel.innerHTML = '<option value="">Select Province</option>';
                provinces.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                    provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
                });
                provSel.disabled = false;

                // Match parent province using text or code
                let provOpt = Array.from(provSel.options).find(opt => 
                    opt.text.toUpperCase().trim() === parentProvName.toUpperCase().trim() || 
                    opt.value === parentProvName.trim()
                );

                if (provOpt) {
                    provSel.value = provOpt.value;

                    // 2. Fetch Cities
                    let cityRes = await fetch(`https://psgc.gitlab.io/api/provinces/${provOpt.value}/cities-municipalities/`);
                    if (!cityRes.ok) cityRes = await fetch(`https://psgc.gitlab.io/api/provinces/${provOpt.value}/cities-municipalities`);
                    const cities = await cityRes.json();

                    citySel.innerHTML = '<option value="">Select City</option>';
                    cities.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                        citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
                    });
                    citySel.disabled = false;

                    // Match parent city using text or code
                    let cityOpt = Array.from(citySel.options).find(opt => 
                        opt.text.toUpperCase().trim() === parentCityName.toUpperCase().trim() || 
                        opt.value === parentCityName.trim()
                    );

                    if (cityOpt) {
                        citySel.value = cityOpt.value;

                        // 3. Fetch Barangays
                        let brgyRes = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityOpt.value}/barangays/`);
                        if (!brgyRes.ok) brgyRes = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityOpt.value}/barangays`);
                        const barangays = await brgyRes.json();

                        brgySel.innerHTML = '<option value="">Select Barangay</option>';
                        barangays.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
                        });
                        brgySel.disabled = false;

                        // Match parent barangay using text or code
                        let brgyOpt = Array.from(brgySel.options).find(opt => 
                            opt.text.toUpperCase().trim() === parentBrgyName.toUpperCase().trim() || 
                            opt.value === parentBrgyName.trim()
                        );
                        if (brgyOpt) {
                            brgySel.value = brgyOpt.value;
                        }
                    }
                }

                // 4. Fill Street
                streetInput.value = parentStreet;
                streetInput.disabled = false;

            } catch (e) {
                console.error("Failed to fetch parent's address:", e);
                // Fallback representation
                provSel.innerHTML = `<option value="${parentProvName}" selected>${parentProvName}</option>`;
                citySel.innerHTML = `<option value="${parentCityName}" selected>${parentCityName}</option>`;
                brgySel.innerHTML = `<option value="${parentBrgyName}" selected>${parentBrgyName}</option>`;
                streetInput.value = parentStreet;
                provSel.disabled = false;
                citySel.disabled = false;
                brgySel.disabled = false;
                streetInput.disabled = false;
            }
        }

        // --- FULL DYNAMIC EDIT METHOD INITIALIZER ---
        async function initEditDepAddress(depId, savedProv, savedCity, savedBrgy, savedStreet) {
            const provSel = document.getElementById(`edit_dep_province_${depId}`);
            const citySel = document.getElementById(`edit_dep_city_${depId}`);
            const brgySel = document.getElementById(`edit_dep_barangay_${depId}`);
            const streetInput = document.getElementById(`edit_dep_street_${depId}`);

            if (!provSel || !citySel || !brgySel || !streetInput) return;

            citySel.disabled = true;
            brgySel.disabled = true;

            try {
                // Fetch Provinces
                let res = await fetch('https://psgc.gitlab.io/api/provinces/');
                if (!res.ok) res = await fetch('https://psgc.gitlab.io/api/provinces');
                const provinces = await res.json();

                provSel.innerHTML = '<option value="">Select Province</option>';
                provinces.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
                    provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
                });

                if (savedProv) {
                    let provOpt = Array.from(provSel.options).find(opt => 
                        opt.text.toUpperCase().trim() === savedProv.toUpperCase().trim() || 
                        opt.value === savedProv.trim()
                    );
                    if (provOpt) {
                        provSel.value = provOpt.value;

                        // Fetch Cities
                        let cityRes = await fetch(`https://psgc.gitlab.io/api/provinces/${provOpt.value}/cities-municipalities/`);
                        if (!cityRes.ok) cityRes = await fetch(`https://psgc.gitlab.io/api/provinces/${provOpt.value}/cities-municipalities`);
                        const cities = await cityRes.json();

                        citySel.innerHTML = '<option value="">Select City</option>';
                        cities.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
                            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
                        });
                        citySel.disabled = false;

                        if (savedCity) {
                            let cityOpt = Array.from(citySel.options).find(opt => 
                                opt.text.toUpperCase().trim() === savedCity.toUpperCase().trim() || 
                                opt.value === savedCity.trim()
                            );
                            if (cityOpt) {
                                citySel.value = cityOpt.value;

                                // Fetch Barangays
                                let brgyRes = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityOpt.value}/barangays/`);
                                if (!brgyRes.ok) brgyRes = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityOpt.value}/barangays`);
                                const barangays = await brgyRes.json();

                                brgySel.innerHTML = '<option value="">Select Barangay</option>';
                                barangays.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
                                    brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
                                });
                                brgySel.disabled = false;

                                if (savedBrgy) {
                                    let brgyOpt = Array.from(brgySel.options).find(opt => 
                                        opt.text.toUpperCase().trim() === savedBrgy.toUpperCase().trim() || 
                                        opt.value === savedBrgy.trim()
                                    );
                                    if (brgyOpt) {
                                        brgySel.value = brgyOpt.value;
                                    }
                                }
                            }
                        }
                    }
                }
                if (savedStreet) {
                    streetInput.value = savedStreet;
                }
            } catch (e) {
                console.error("Edit dependent address initialization failed:", e);
            }
        }

        // --- SUBMIT COMPILATION HELPERS ---
        function compileDependentAddress() {
            const street = document.getElementById('dep_street');
            const brgy = document.getElementById('dep_barangay');
            const city = document.getElementById('dep_city');
            const prov = document.getElementById('dep_province');

            if (street && brgy && city && prov) {
                const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
                const cityName = city.options[city.selectedIndex]?.text || '';
                const provName = prov.options[prov.selectedIndex]?.text || '';

                if (provName && cityName && brgyName) {
                    prov.options[prov.selectedIndex].value = provName;
                    city.options[city.selectedIndex].value = cityName;
                    brgy.options[brgy.selectedIndex].value = brgyName;
                }
            }
        }

        function compileEditDepAddress(depId) {
            const prov = document.getElementById(`edit_dep_province_${depId}`);
            const city = document.getElementById(`edit_dep_city_${depId}`);
            const brgy = document.getElementById(`edit_dep_barangay_${depId}`);

            if (prov && city && brgy) {
                const brgyName = brgy.options[brgy.selectedIndex]?.text || '';
                const cityName = city.options[city.selectedIndex]?.text || '';
                const provName = prov.options[prov.selectedIndex]?.text || '';

                if (provName && cityName && brgyName) {
                    prov.options[prov.selectedIndex].value = provName;
                    city.options[city.selectedIndex].value = cityName;
                    brgy.options[brgy.selectedIndex].value = brgyName;
                }
            }
        }

        // --- PAGE-LOAD BOOTSTRAP LISTENER ---
        document.addEventListener('DOMContentLoaded', async () => {
            // Automatically auto-populate the Add Dependent form with the parent's actual address by default on page load
            await fetchParentAddress('dep_');

            // Loop through dependents and initialize cascading selects for non-inherited ones
            @foreach($user->dependents as $dep)
                @php 
                    $isInherited = ($dep->street === $user->street && $dep->barangay === $user->barangay && $dep->city === $user->city && $dep->province === $user->province);
                    $depOver18 = $dep->birthdate->age >= 18;
                @endphp
                @if(!$depOver18)
                    @if(!$isInherited)
                        await initEditDepAddress('{{ $dep->id }}', '{{ $dep->province }}', '{{ $dep->city }}', '{{ $dep->barangay }}', '{{ $dep->street }}');
                    @else
                        // Pre-populate with parent's values if inherited directly
                        await fetchParentAddress('edit_dep_', '{{ $dep->id }}');
                    @endif
                @endif
            @endforeach
        });
    </script>
@endpush