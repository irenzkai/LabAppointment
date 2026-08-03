@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid text-start animate-page">

    {{-- 1. CONTROL HEADER & SEARCH BAR --}}
    <div class="row g-4 align-items-center mb-4">
        <div class="col-md-5 col-lg-5">
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">User Directory</h2>
            <p class="text-secondary small mb-0">Manage system profiles, assign roles, and audit access credentials.</p>
        </div>

        <div class="col-md-7 col-lg-7">
            <div class="row g-2 justify-content-md-end">
                <div class="col-sm-6 col-md-5">
                    <div class="input-group input-group-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                        <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card); border-right: none;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="userDirectorySearch" class="form-control border-0 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border-left: none;" placeholder="Search name or email...">
                    </div>
                </div>

                <div class="col-sm-6 col-md-7 col-lg-6">
                    <div class="btn-group btn-group-sm w-100 shadow-sm" role="group">
                        <button type="button" class="btn btn-neon filter-role-btn active" data-role="all">All</button>
                        <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="user">Patients</button>
                        <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="staff">Staff</button>
                        <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="lab_tech">Lab Tech</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. DIRECTORY TABLE CARD --}}
    <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="background-color: var(--bg-card);">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-directory-table" style="color: var(--text-main);">
                <thead class="text-secondary small uppercase border-bottom border-secondary border-opacity-25" style="background-color: rgba(0, 0, 0, 0.05);">
                    <tr>
                        <th class="ps-4 py-3" style="width: 40%;">User Profile</th>
                        <th style="width: 20%;">Role</th>
                        <th style="width: 20%;">Account Status</th>
                        <th class="text-end pe-4" style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="directoryTableBody">
                    @forelse($users as $user)
                    @php
                        $words = explode(' ', $user->name);
                        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        
                        $roleLabel = match($user->role) {
                            'admin' => 'ADMIN',
                            'lab_tech' => 'LAB TECH',
                            'staff' => 'STAFF',
                            default => 'PATIENT'
                        };

                        $roleClass = match($user->role) {
                            'admin' => 'border-danger text-danger bg-danger bg-opacity-10',
                            'lab_tech' => 'border-warning text-warning bg-warning bg-opacity-10',
                            'staff' => 'border-info text-info bg-info bg-opacity-10',
                            default => 'border-secondary text-secondary bg-secondary bg-opacity-10'
                        };
                    @endphp

                    <tr class="border-secondary border-opacity-10 directory-row" data-role="{{ $user->role }}" data-searchable="{{ strtolower($user->name) }} {{ strtolower($user->email) }}">
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle rounded-circle bg-secondary bg-opacity-10 border border-secondary border-opacity-20 d-flex align-items-center justify-content-center text-accent fw-bold" style="width: 42px; height: 42px; font-size: 0.85rem;">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <div class="fw-bold h6 mb-0" style="color: var(--text-main);">{{ strtoupper($user->name) }}</div>
                                    <div class="text-muted small fs-x-small">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge border {{ $roleClass }} fw-bold small uppercase px-2.5 py-1.5 rounded">
                                {{ $roleLabel }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-indicator rounded-circle {{ $user->trashed() ? 'bg-danger' : 'bg-neon' }} shadow-neon" style="width: 8px; height: 8px; display: inline-block;"></span>
                                <span class="text-{{ $user->trashed() ? 'danger' : 'neon' }} fw-bold small">
                                    {{ $user->trashed() ? 'DEACTIVATED' : 'ACTIVE' }}
                                </span>
                            </div>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex gap-1.5 justify-content-end align-items-center">
                                @if(Auth::user()->isEmployee())
                                {{-- Launch the clinical archive Reason-Gate modal --}}
                                <button type="button" class="btn btn-sm btn-outline-neon py-1 px-2 fw-bold" title="View Patient Medical Archive" onclick="promptAccess('{{ $user->id }}', 'all', 'history', true)">
                                    <i class="bi bi-folder2-open me-1"></i>RECORDS
                                </button>
                                @endif

                                @if(Auth::user()->role === 'admin')
                                {{-- Consolidated administrative Edit Account button --}}
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}" title="Edit Account Details">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary italic">
                            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                            No active registry accounts found in search results.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 3. UNIFIED TABBED ADMIN EDIT ACCOUNT MODALS --}}
    @if(Auth::user()->role === 'admin')
        @foreach($users as $user)
        <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="modal-content border-secondary bg-card shadow-lg text-start" onsubmit="compileUserEditAddress('{{ $user->id }}')" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                    @csrf 
                    @method('PUT')
                    
                    <div class="modal-header border-secondary bg-secondary bg-opacity-10 py-3">
                        <h5 class="modal-title text-accent fw-bold small"><i class="bi bi-pencil-square me-2"></i>EDIT ACCOUNT: {{ strtoupper($user->name) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        {{-- Horizontal Step Navigation --}}
                        <ul class="nav nav-pills mb-4 justify-content-center" id="editTabs{{ $user->id }}" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active btn-sm uppercase px-3 py-1.5" id="demographics-tab-{{ $user->id }}" data-bs-toggle="pill" data-bs-target="#tab-demo-{{ $user->id }}" type="button" role="tab">1. Identity</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link btn-sm uppercase px-3 py-1.5" id="address-tab-{{ $user->id }}" data-bs-toggle="pill" data-bs-target="#tab-addr-{{ $user->id }}" type="button" role="tab" onclick="initUserEditAddress('{{ $user->id }}', '{{ $user->province }}', '{{ $user->city }}', '{{ $user->barangay }}')">2. Address</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link btn-sm uppercase px-3 py-1.5" id="roles-tab-{{ $user->id }}" data-bs-toggle="pill" data-bs-target="#tab-role-{{ $user->id }}" type="button" role="tab">3. Roles & Status</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link btn-sm uppercase px-3 py-1.5" id="security-tab-{{ $user->id }}" data-bs-toggle="pill" data-bs-target="#tab-sec-{{ $user->id }}" type="button" role="tab">4. Security</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="editTabsContent{{ $user->id }}">
                            {{-- TAB 1: DEMOGRAPHICS --}}
                            <div class="tab-pane fade show active" id="tab-demo-{{ $user->id }}" role="tabpanel">
                                <h6 class="text-accent mb-3 small fw-bold uppercase">Personal Identity</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">First Name</label>
                                        <input type="text" name="first_name" class="form-control uppercase" value="{{ $user->first_name }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="smaller text-secondary fw-bold mb-1">Middle Name</label>
                                        <input type="text" name="middle_name" class="form-control uppercase" value="{{ $user->middle_name }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Last Name</label>
                                        <input type="text" name="last_name" class="form-control uppercase" value="{{ $user->last_name }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                                        <input type="date" name="birthdate" class="form-control" value="{{ $user->birthdate->format('Y-m-d') }}" required max="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Sex</label>
                                        <select name="sex" class="form-select" required>
                                            <option value="Male" {{ $user->sex === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ $user->sex === 'Female' ? 'selected' : '' }}>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Contact Phone</label>
                                        <input type="text" name="phone" class="form-control" value="{{ $user->phone }}" required>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 2: RESIDENTIAL ADDRESS (PSGC API DRIVEN) --}}
                            <div class="tab-pane fade" id="tab-addr-{{ $user->id }}" role="tabpanel">
                                <h6 class="text-accent mb-3 small fw-bold uppercase">Residential Address</h6>
                                <div class="alert alert-clinical p-2.5 mb-3 border border-secondary border-opacity-10 text-start" style="background-color: rgba(0,0,0,0.015); border-radius: 8px;">
                                    <div class="text-accent fw-bold fs-x-small uppercase tracking-wider mb-1" style="font-size: 0.65rem;">Saved Address on File:</div>
                                    <div class="text-main small">{{ $user->address }}</div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Province</label>
                                        <select id="edit_user_province_{{ $user->id }}" name="province" class="form-select" onchange="fetchUserEditCities('{{ $user->id }}', this.value)" required>
                                            <option value="">Select Province</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                                        <select id="edit_user_city_{{ $user->id }}" name="city" class="form-select" onchange="fetchUserEditBarangays('{{ $user->id }}', this.value)" disabled required>
                                            <option value="">Select Province First</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Barangay</label>
                                        <select id="edit_user_brgy_{{ $user->id }}" name="barangay" class="form-select" disabled required>
                                            <option value="">Select City First</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                                        <input type="text" id="edit_user_street_{{ $user->id }}" name="street" class="form-control uppercase" value="{{ $user->street }}" required>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 3: ROLES & STATUS --}}
                            <div class="tab-pane fade" id="tab-role-{{ $user->id }}" role="tabpanel">
                                <h6 class="text-accent mb-3 small fw-bold uppercase">System Access & Status</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Access Level / Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>Patient / User</option>
                                            <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>Clinic Staff</option>
                                            <option value="lab_tech" {{ $user->role === 'lab_tech' ? 'selected' : '' }}>Laboratory Technician</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Account Activity State</label>
                                        <div class="form-check form-switch p-2 border border-secondary border-opacity-25 rounded bg-dark bg-opacity-25 text-center d-flex align-items-center justify-content-center">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="deactivate" id="deact-{{ $user->id }}" value="1" {{ $user->trashed() ? 'checked' : '' }}>
                                            <label class="form-check-label text-white smaller fw-bold uppercase" for="deact-{{ $user->id }}">Deactivate & Suspend Account</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 4: SECURITY --}}
                            <div class="tab-pane fade" id="tab-sec-{{ $user->id }}" role="tabpanel">
                                <h6 class="text-accent mb-3 small fw-bold uppercase">Security Overrides</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1">Email Address (Triggers re-verification if changed)</label>
                                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="smaller text-secondary fw-bold mb-1 uppercase">Password Modifications</label>
                                        <select name="password_option" id="passOpt{{ $user->id }}" class="form-select" onchange="togglePassOption('{{ $user->id }}', this)">
                                            <option value="" selected>-- Choose Option (Optional) --</option>
                                            <option value="send_link">Send Password Reset Link</option>
                                            <option value="manual">Manually Set Password (Forces Change on Next Login)</option>
                                        </select>
                                    </div>

                                    {{-- Hidden Manual Password fields --}}
                                    <div id="pass-manual-wrapper-{{ $user->id }}" class="col-12 d-none">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Set Temporary Password</label>
                                                <input type="password" name="password" id="pass-input-{{ $user->id }}" class="form-control" placeholder="Min. 8 characters">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Confirm Temporary Password</label>
                                                <input type="password" name="password_confirmation" id="pass-conf-input-{{ $user->id }}" class="form-control" placeholder="Repeat password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Consolidated Action Audit Logger (Visible on all tabs) --}}
                        <div class="mt-4 pt-3 border-top border-secondary border-opacity-10">
                            <h6 class="text-danger mb-2 small fw-bold uppercase"><i class="bi bi-shield-exclamation me-1"></i>5. Administrative Justification</h6>
                            <div class="mb-3">
                                <label class="smaller text-muted d-block mb-2">Please specify the official reason for modifying this user profile. This action is permanently logged in the audit trail.</label>
                                <select name="reason" id="user_reason_select_{{ $user->id }}" class="form-select" onchange="toggleUserReason('{{ $user->id }}', this)" required>
                                    <option value="" disabled selected>-- Select a valid justification --</option>
                                    <option value="Routine administrative update / profile maintenance">Routine administrative update / profile maintenance</option>
                                    <option value="Official request for email or credential correction">Official request for email or credential correction</option>
                                    <option value="Deactivation or suspension of account for compliance">Deactivation or suspension of account for compliance</option>
                                    <option value="Manual override of password / force password change">Manual override of password / force password change</option>
                                    <option value="Correction of typological / data entry error">Correction of typological / data entry error</option>
                                    <option value="Others">Others (Specify below)</option>
                                </select>
                            </div>
                            <div id="user_custom_reason_wrapper_{{ $user->id }}" class="mb-0 d-none">
                                <label class="smaller text-secondary fw-bold mb-1 uppercase">Specify Custom Reason</label>
                                <textarea name="custom_reason" id="user_custom_reason_{{ $user->id }}" class="form-control" rows="2" placeholder="Explain the profile edit justification..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary bg-secondary bg-opacity-10 p-3">
                        <button type="submit" class="btn-custom btn-accent w-100 py-3 fw-bold uppercase">SAVE ACCOUNT CHANGES</button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('userDirectorySearch');
    const tableRows = document.querySelectorAll('.directory-row');
    const filterButtons = document.querySelectorAll('.filter-role-btn');
    let currentRoleFilter = 'all';
    let currentSearchQuery = '';

    function applyFilters() {
        tableRows.forEach(row => {
            const role = row.getAttribute('data-role');
            const searchableText = row.getAttribute('data-searchable');
            const matchesRole = (currentRoleFilter === 'all' || role === currentRoleFilter);
            const matchesSearch = searchableText.includes(currentSearchQuery);
            row.classList.toggle('d-none', !(matchesRole && matchesSearch));
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentSearchQuery = this.value.trim().toLowerCase();
            applyFilters();
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.replace('btn-neon', 'btn-outline-secondary'));
            this.classList.replace('btn-outline-secondary', 'btn-neon');
            this.classList.add('active');
            currentRoleFilter = this.getAttribute('data-role');
            applyFilters();
        });
    });
});

/**
 * Toggles manual password inputs visibility
 */
function togglePassOption(userId, select) {
    const wrapper = document.getElementById(`pass-manual-wrapper-${userId}`);
    const passwordInput = document.getElementById(`pass-input-${userId}`);
    const passwordConfInput = document.getElementById(`pass-conf-input-${userId}`);

    if (select.value === 'manual') {
        wrapper.classList.remove('d-none');
        passwordInput.setAttribute('required', 'required');
        passwordConfInput.setAttribute('required', 'required');
    } else {
        wrapper.classList.add('d-none');
        passwordInput.removeAttribute('required');
        passwordConfInput.removeAttribute('required');
    }
}

/**
 * Toggles custom user modification justification reason field
 */
window.toggleUserReason = function(userId, select) {
    const wrapper = document.getElementById(`user_custom_reason_wrapper_${userId}`);
    const textarea = document.getElementById(`user_custom_reason_${userId}`);
    if (wrapper && textarea) {
        if (select.value === 'Others') {
            wrapper.classList.remove('d-none');
            textarea.setAttribute('required', 'required');
        } else {
            wrapper.classList.add('d-none');
            textarea.removeAttribute('required');
        }
    }
}

/**
 * Lazy loads and populates PSGC Cascading Address dropdown elements inside individual edit modals
 */
window.initUserEditAddress = async function(userId, savedProv, savedCity, savedBrgy) {
    const provSel = document.getElementById(`edit_user_province_${userId}`);
    // Bypasses network requests if the provinces have already been fetched for this specific modal
    if (provSel && provSel.options.length > 1) return;

    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces/');
        const data = await res.json();
        
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(p => {
            provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });

        if (savedProv) {
            let provOpt = Array.from(provSel.options).find(opt => opt.text.toUpperCase() === savedProv.toUpperCase());
            if (provOpt) {
                provSel.value = provOpt.value;
                await fetchUserEditCities(userId, provOpt.value, savedCity, savedBrgy);
            }
        }
    } catch (e) {
        console.error("User edit provinces fetch failed:", e);
    }
}

window.fetchUserEditCities = async function(userId, provCode, savedCity, savedBrgy) {
    const citySel = document.getElementById(`edit_user_city_${userId}`);
    const brgySel = document.getElementById(`edit_user_brgy_${userId}`);
    if (!citySel || !brgySel) return;

    citySel.disabled = true;
    brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading Cities...</option>';

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>';
        });
        citySel.disabled = false;

        if (savedCity) {
            let cityOpt = Array.from(citySel.options).find(opt => opt.text.toUpperCase() === savedCity.toUpperCase());
            if (cityOpt) {
                citySel.value = cityOpt.value;
                await fetchUserEditBarangays(userId, cityOpt.value, savedBrgy);
            }
        }
    } catch (e) {
        console.error("User edit cities fetch failed:", e);
    }
}

window.fetchUserEditBarangays = async function(userId, cityCode, savedBrgy) {
    const brgySel = document.getElementById(`edit_user_brgy_${userId}`);
    if (!brgySel) return;

    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading Barangays...</option>';

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a, b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;

        if (savedBrgy) {
            let brgyOpt = Array.from(brgySel.options).find(opt => opt.text.toUpperCase() === savedBrgy.toUpperCase());
            if (brgyOpt) {
                brgySel.value = brgyOpt.value;
            }
        }
    } catch (e) {
        console.error("User edit barangays fetch failed:", e);
    }
}

/**
 * Compiles selected User PSGC inputs to literal string representation before submitting
 */
window.compileUserEditAddress = function(userId) {
    const prov = document.getElementById(`edit_user_province_${userId}`);
    const city = document.getElementById(`edit_user_city_${userId}`);
    const brgy = document.getElementById(`edit_user_brgy_${userId}`);

    if (prov && city && brgy) {
        const provName = prov.options[prov.selectedIndex]?.text || '';
        const cityName = city.options[city.selectedIndex]?.text || '';
        const brgyName = brgy.options[brgy.selectedIndex]?.text || '';

        // Overwrite numerical value with literal string representation before form submission
        if (provName && cityName && brgyName) {
            prov.options[prov.selectedIndex].value = provName;
            city.options[city.selectedIndex].value = cityName;
            brgy.options[brgy.selectedIndex].value = brgyName;
        }
    }
}
</script>

<style>
/* Settings Sidebar customized overrides */
.settings-sidebar .list-group-item {
    color: var(--text-muted);
    border: 1px solid transparent;
    background-color: transparent;
    border-radius: 8px !important;
    padding: 12px 18px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
}
.settings-sidebar .list-group-item:hover {
    color: var(--brand-accent);
    background-color: rgba(25, 211, 140, 0.05);
}
.settings-sidebar .list-group-item.active {
    color: #1c232d !important;
    background-color: var(--brand-accent) !important;
    font-weight: 700;
}
.settings-sidebar .list-group-item.text-danger:hover {
    color: #ff0000 !important;
    background-color: rgba(25, 255, 77, 0.05) !important;
}
.settings-sidebar .list-group-item.text-danger.active {
    color: #ffffff !important;
    background-color: #8a0808 !important;
}
.password-container input {
    padding-right: 45px;
}

/* Specific overrides for edit account modal tab indicators */
.modal-body .nav-pills .nav-link {
    color: var(--text-muted) !important;
    border: 1px solid var(--border-color) !important;
    background-color: var(--bg-card) !important;
    transition: all 0.2s ease;
    font-weight: 700;
}
.modal-body .nav-pills .nav-link:hover {
    color: var(--brand-accent) !important;
    border-color: var(--brand-accent) !important;
}
.modal-body .nav-pills .nav-link.active {
    background-color: var(--brand-accent) !important;
    color: #1c232d !important;
    border-color: var(--brand-accent) !important;
}
</style>
@endpush