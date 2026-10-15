@extends('layouts.app')
@section('title', Auth::user()->role === 'admin' ? 'User Directory' : 'User List')

@section('content')
<div class="container-fluid text-start animate-page py-2">
    @php
        $isAdmin = (Auth::user()->role === 'admin');
    @endphp

    {{-- 1. CONTROL HEADER WITH CONDITIONAL CREATE USER BUTTON --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
        <div>
            <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                User Directory
            </h2>
            <p class="text-muted small mb-0" style="color: var(--text-muted) !important;">
                {{ $isAdmin 
                    ? 'Manage system profiles, assign roles, and audit clinical patient records across all accounts.' 
                    : 'Search user accounts across all roles to review, audit, and digitize clinical laboratory histories.' }}
            </p>
        </div>
        @if($isAdmin)
            <div>
                <a href="{{ route('admin.users.create') }}" class="btn-custom btn-accent fw-bold btn-sm py-2 px-3 uppercase shadow-sm text-decoration-none">
                    <i class="bi bi-person-plus-fill me-1.5"></i> CREATE USER
                </a>
            </div>
        @else
            <div>
                <span class="badge border border-info text-info bg-info bg-opacity-10 px-3 py-2 uppercase small">
                    <i class="bi bi-shield-check me-1"></i> Staff Clinical Lab History Access
                </span>
            </div>
        @endif
    </div>

    {{-- 2. SEARCH & ROLE FILTER TOOLBAR (All role buttons accessible so staff can find anyone) --}}
    <div class="row g-3 mb-4 align-items-center">
        <div class="col-md-6 col-lg-5">
            <div class="input-group input-group-sm border rounded-3 overflow-hidden" style="border-color: var(--border-color) !important;">
                <span class="input-group-text border-0 text-muted" style="background-color: var(--bg-card); color: var(--text-muted) !important;">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="userDirectorySearch" class="form-control border-0 shadow-none py-2" style="background-color: var(--bg-card); color: var(--text-main);" placeholder="Search name, email, or phone...">
            </div>
        </div>

        <div class="col-md-6 col-lg-7 text-md-end">
            <div class="btn-group btn-group-sm shadow-sm" role="group">
                <button type="button" class="btn btn-accent filter-role-btn active" data-role="all">All</button>
                <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="user">Patients</button>
                <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="staff">Staff</button>
                <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="lab_tech">Lab Tech</button>
                <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="admin">Admins</button>
            </div>
        </div>
    </div>

    {{-- 3. DIRECTORY TABLE CARD --}}
    <div class="card p-0 border overflow-hidden shadow-sm" style="background-color: var(--bg-card); border-color: var(--border-color) !important; border-radius: 14px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-directory-table" style="color: var(--text-main);">
                <thead class="text-muted small uppercase border-bottom" style="background-color: rgba(0, 0, 0, 0.03); border-color: var(--border-color) !important; font-size: 0.72rem;">
                    <tr>
                        <th class="ps-4 py-3" style="width: 38%;">User Profile</th>
                        <th style="width: 18%;">Role</th>
                        <th style="width: 18%;">Account Status</th>
                        <th class="text-end pe-4" style="width: 26%;">Clinical Action</th>
                    </tr>
                </thead>
                <tbody id="directoryTableBody">
                    @forelse($users as $user)
                        @php
                            $words = explode(' ', $user->name);
                            $initials = strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                            
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
                            $isTargetAdmin = ($user->role === 'admin');
                        @endphp
                        <tr class="border-bottom directory-row" 
                            data-user-id="{{ $user->id }}" 
                            data-role="{{ $user->role }}" 
                            data-searchable="{{ strtolower($user->name) }} {{ strtolower($user->email) }} {{ $user->phone }}"
                            style="border-color: var(--border-color) !important;">
                            
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle rounded-circle d-flex align-items-center justify-content-center text-accent fw-bold" 
                                         style="width: 42px; height: 42px; font-size: 0.85rem; background-color: rgba(25, 211, 140, 0.1); border: 1.5px solid var(--brand-accent);">
                                        {{ $initials ?: 'U' }}
                                    </div>
                                    <div>
                                        <div class="fw-bold h6 mb-0" style="color: var(--text-main) !important;">{{ strtoupper($user->name) }}</div>
                                        <div class="text-muted small" style="font-size: 0.72rem; color: var(--text-muted) !important;">
                                            {{ $user->email }}
                                            @if($user->phone)
                                                <span class="mx-1">&bull;</span> {{ $user->phone }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge border {{ $roleClass }} fw-bold small uppercase px-2.5 py-1.5 rounded" style="font-size: 0.65rem;">
                                    {{ $roleLabel }}
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="status-indicator rounded-circle {{ $user->trashed() ? 'bg-danger' : 'bg-success' }}" style="width: 8px; height: 8px; display: inline-block;"></span>
                                    <span class="text-{{ $user->trashed() ? 'danger' : 'success' }} fw-bold small" style="font-size: 0.75rem;">
                                        {{ $user->trashed() ? 'DEACTIVATED' : 'ACTIVE' }}
                                    </span>
                                </div>
                            </td>

                            <td class="text-end pe-4">
                                <div class="d-flex gap-2 justify-content-end align-items-center">
                                    {{-- CLINICAL LAB HISTORY ACCESS: Accessible by all employees (Staff, Lab Tech, Admin) for ANY profile (including Admins) --}}
                                    @if(Auth::user()->isEmployee())
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-accent py-1.5 px-3 fw-bold uppercase d-inline-flex align-items-center gap-2 shadow-sm" 
                                                title="Access Clinical & Laboratory History" 
                                                onclick="promptAccess('{{ $user->id }}', 'all', 'history', true, '{{ addslashes($user->name) }}')"
                                                style="font-size: 0.75rem;">
                                            <i class="bi bi-folder2-open text-accent"></i>
                                            <span>LAB HISTORY</span>
                                        </button>
                                    @endif

                                    {{-- ADMINISTRATIVE CREDENTIAL MANAGEMENT: Strictly Admin Only --}}
                                    @if($isAdmin)
                                        @if(!$isTargetAdmin)
                                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-secondary py-1.5 px-2.5 fw-bold uppercase" title="Edit Account Details" style="font-size: 0.75rem;">
                                                <i class="bi bi-pencil-square me-1"></i>EDIT
                                            </a>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25 py-1.5 px-2" title="Admin accounts cannot be modified by other administrators">
                                                <i class="bi bi-shield-lock-fill me-1"></i>PROTECTED
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted small italic" style="color: var(--text-muted) !important;">
                                <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                No registered user accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- REASON-GATE ACCESS MODAL (Republic Act No. 10173 DPA Compliance) --}}
<div class="modal fade" id="reasonGateModal" tabindex="-1" aria-labelledby="reasonGateModalLabel" aria-hidden="true" data-bs-backdrop="static" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border shadow-lg" style="background-color: var(--bg-card); color: var(--text-main); border-color: var(--border-color) !important; border-radius: 16px;">
            <div class="modal-header border-bottom py-3" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title fw-bold uppercase small text-accent m-0 d-flex align-items-center gap-2" id="reasonGateModalLabel" style="color: var(--brand-accent) !important;">
                    <i class="bi bi-shield-lock-fill fs-5"></i>
                    <span>Clinical Authorization Required</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('history.log-access') }}" onsubmit="return handleGateSubmit(event)">
                @csrf
                <input type="hidden" name="target_user_id" id="gate_target_user_id">
                <input type="hidden" name="access_reason" id="gate_access_reason">

                <div class="modal-body p-4 text-start">
                    <p class="small mb-3" style="color: var(--text-muted) !important;">
                        You are requesting access to the clinical and laboratory records of <strong id="gate_patient_name" style="color: var(--text-main) !important;"></strong>. In compliance with <strong>Republic Act No. 10173 (Data Privacy Act of 2012)</strong>, please indicate your clinical justification:
                    </p>
                    
                    {{-- Reason Dropdown Selector --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold uppercase" style="color: var(--text-muted); font-size: 0.72rem;">Clinical Justification *</label>
                        <select id="gate_reason_select" class="form-select form-select-sm py-2 shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" required onchange="handleReasonChange(this.value)">
                            <option value="" disabled selected>-- Select Justification --</option>
                            <option value="Patient Consultation & Historical Review">Patient Consultation & Historical Review</option>
                            <option value="Laboratory Results Verification & Quality Check">Laboratory Results Verification & Quality Check</option>
                            <option value="Physician Diagnostic Evaluation">Physician Diagnostic Evaluation</option>
                            <option value="Patient-Requested Record Retrieval / Release">Patient-Requested Record Retrieval / Release</option>
                            <option value="Historical Laboratory Record Digitization & Archiving">Historical Laboratory Record Digitization & Archiving</option>
                            <option value="Clinical Audit & Medical Record Inspection">Clinical Audit & Medical Record Inspection</option>
                            <option value="Others">Others (Specify below)</option>
                        </select>
                    </div>

                    {{-- Dynamic "Others" Custom Text Box --}}
                    <div class="mb-3 d-none" id="gate_custom_reason_wrapper">
                        <label class="form-label small fw-bold uppercase" style="color: var(--text-muted); font-size: 0.72rem;">Specify Custom Reason *</label>
                        <textarea id="gate_custom_reason" class="form-control form-control-sm shadow-none" style="background-color: var(--bg-card); color: var(--text-main); border: 1.5px solid var(--border-color);" rows="3" placeholder="Enter detailed clinical justification (min. 5 characters)..."></textarea>
                        <div class="mt-1"><small class="text-muted smaller italic">Minimum 5 characters required for validation.</small></div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5 p-3 d-flex gap-2" style="border-color: var(--border-color) !important;">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm fw-bold uppercase px-4 py-2 shadow-sm d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-unlock-fill"></i>
                        <span>Authorize & Open Records</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
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
            const searchableText = row.getAttribute('data-searchable') || '';
            const matchesRole = (currentRoleFilter === 'all' || role === currentRoleFilter);
            const matchesSearch = !currentSearchQuery || searchableText.includes(currentSearchQuery);
            row.classList.toggle('d-none', !(matchesRole && matchesSearch));
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentSearchQuery = this.value.trim().toLowerCase();
            applyFilters();
        });
    }

    if (filterButtons.length > 0) {
        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                filterButtons.forEach(b => {
                    b.classList.remove('btn-accent', 'active');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-accent', 'active');
                currentRoleFilter = this.getAttribute('data-role');
                applyFilters();
            });
        });
    }
});

// Trigger Reason-Gate Modal
function promptAccess(userId, mode, type, isHistory, patientName = '') {
    const targetUserIdInput = document.getElementById('gate_target_user_id');
    const patientNameEl = document.getElementById('gate_patient_name');

    if (targetUserIdInput) targetUserIdInput.value = userId;

    if (patientNameEl) {
        if (patientName) {
            patientNameEl.innerText = patientName;
        } else {
            const row = document.querySelector(`.directory-row[data-user-id="${userId}"]`);
            const nameEl = row ? row.querySelector('.fw-bold.h6') : null;
            patientNameEl.innerText = nameEl ? nameEl.innerText.trim() : 'this patient';
        }
    }

    // Reset input fields
    const select = document.getElementById('gate_reason_select');
    const customBox = document.getElementById('gate_custom_reason_wrapper');
    const customInput = document.getElementById('gate_custom_reason');
    if (select) select.value = '';
    if (customBox) customBox.classList.add('d-none');
    if (customInput) {
        customInput.value = '';
        customInput.removeAttribute('required');
    }

    const modalEl = document.getElementById('reasonGateModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

// Handle Dropdown Change for "Others"
function handleReasonChange(value) {
    const customWrapper = document.getElementById('gate_custom_reason_wrapper');
    const customInput = document.getElementById('gate_custom_reason');
    if (value === 'Others') {
        if (customWrapper) customWrapper.classList.remove('d-none');
        if (customInput) {
            customInput.setAttribute('required', 'required');
            customInput.focus();
        }
    } else {
        if (customWrapper) customWrapper.classList.add('d-none');
        if (customInput) {
            customInput.removeAttribute('required');
            customInput.value = '';
        }
    }
}

// Compile Reason on Submission
function handleGateSubmit(e) {
    const select = document.getElementById('gate_reason_select');
    const customInput = document.getElementById('gate_custom_reason');
    const hiddenInput = document.getElementById('gate_access_reason');

    if (!select || !select.value) {
        e.preventDefault();
        alert('Please select a clinical justification.');
        return false;
    }

    if (select.value === 'Others') {
        const trimmed = (customInput ? customInput.value : '').trim();
        if (trimmed.length < 5) {
            e.preventDefault();
            alert('Please provide a specific clinical reason of at least 5 characters.');
            if (customInput) customInput.focus();
            return false;
        }
        if (hiddenInput) hiddenInput.value = trimmed;
    } else {
        if (hiddenInput) hiddenInput.value = select.value;
    }
    return true;
}
</script>
@endpush