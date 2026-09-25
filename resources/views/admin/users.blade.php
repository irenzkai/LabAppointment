@extends('layouts.app')
@section('title', 'User Directory')

@section('content')
<div class="container-fluid text-start animate-page">
 {{-- 1. CONTROL HEADER WITH CREATE USER BUTTON --}}
 <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
     <div>
         <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">User Directory</h2>
         <p class="text-secondary small mb-0">Manage system profiles, assign roles, and audit clinical patient records.</p>
     </div>
     @if(Auth::user()->role === 'admin')
     <div>
         <a href="{{ route('admin.users.create') }}" class="btn btn-accent fw-bold btn-sm py-2 px-3 uppercase shadow-sm">
             <i class="bi bi-person-plus-fill me-1.5"></i> CREATE USER
         </a>
     </div>
     @endif
 </div>

 {{-- 2. SEARCH & ROLE FILTER TOOLBAR --}}
 <div class="row g-3 mb-4 align-items-center">
     <div class="col-md-6 col-lg-5">
         <div class="input-group input-group-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden">
             <span class="input-group-text border-0 text-secondary" style="background-color: var(--bg-card); border-right: none;">
                 <i class="bi bi-search"></i>
             </span>
             <input type="text" id="userDirectorySearch" class="form-control border-0 shadow-none py-2" style="background-color: var(--bg-card); color: var(--text-main);" placeholder="Search name or email...">
         </div>
     </div>
     <div class="col-md-6 col-lg-7 text-md-end">
         <div class="btn-group btn-group-sm shadow-sm" role="group">
             <button type="button" class="btn btn-neon filter-role-btn active" data-role="all">All</button>
             <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="user">Patients</button>
             <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="staff">Staff</button>
             <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="lab_tech">Lab Tech</button>
             <button type="button" class="btn btn-outline-secondary filter-role-btn" data-role="admin">Admins</button>
         </div>
     </div>
 </div>

 {{-- 3. DIRECTORY TABLE CARD --}}
 <div class="card p-0 border-secondary overflow-hidden shadow-lg" style="background-color: var(--bg-card);">
     <div class="table-responsive">
         <table class="table table-hover align-middle mb-0 custom-directory-table" style="color: var(--text-main);">
             <thead class="text-secondary small uppercase border-bottom border-secondary border-opacity-25" style="background-color: rgba(0, 0, 0, 0.05);">
                 <tr>
                     <th class="ps-4 py-3" style="width: 38%;">User Profile</th>
                     <th style="width: 18%;">Role</th>
                     <th style="width: 18%;">Account Status</th>
                     <th class="text-end pe-4" style="width: 26%;">Actions</th>
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
                 $isTargetAdmin = ($user->role === 'admin');
                 @endphp
                 <tr class="border-secondary border-opacity-10 directory-row" data-user-id="{{ $user->id }}" data-role="{{ $user->role }}" data-searchable="{{ strtolower($user->name) }} {{ strtolower($user->email) }}">
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
                             {{-- CLINICAL RECORDS (PATIENT SIDE): Always accessible for all accounts (including Admins & Staff) --}}
                             @if(Auth::user()->isEmployee())
                             <button type="button" class="btn btn-sm btn-outline-neon py-1 px-2 fw-bold" title="View Patient Medical Archive" onclick="promptAccess('{{ $user->id }}', 'all', 'history', true, '{{ addslashes($user->name) }}')">
                                 <i class="bi bi-folder2-open me-1"></i>RECORDS
                             </button>
                             @endif

                             {{-- ACCOUNT CREDENTIAL EDITING: Prohibited for other Administrator profiles --}}
                             @if(Auth::user()->role === 'admin')
                                 @if(!$isTargetAdmin)
                                 <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fw-bold" title="Edit Account Details">
                                     <i class="bi bi-pencil-square me-1"></i>EDIT
                                 </a>
                                 @else
                                 <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25 py-1.5 px-2" title="Admin accounts cannot be edited by other administrators">
                                     <i class="bi bi-shield-lock-fill me-1"></i>PROTECTED
                                 </span>
                                 @endif
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
</div>

{{-- REASON-GATE ACCESS MODAL WITH DROPDOWN & "OTHERS" OPTION --}}
<div class="modal fade" id="reasonGateModal" tabindex="-1" aria-labelledby="reasonGateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-secondary shadow-lg" style="background-color: var(--bg-card); color: var(--text-main); border-radius: 16px;">
            <div class="modal-header border-bottom border-secondary border-opacity-25 py-3">
                <h5 class="modal-title fw-800 uppercase fs-6 text-accent" id="reasonGateModalLabel">
                    <i class="bi bi-shield-lock-fill me-1.5"></i>Clinical Authorization Required
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('history.log-access') }}" onsubmit="return handleGateSubmit(event)">
                @csrf
                <input type="hidden" name="target_user_id" id="gate_target_user_id">
                <input type="hidden" name="access_reason" id="gate_access_reason">

                <div class="modal-body p-4 text-start">
                    <p class="text-secondary small mb-3">
                        You are requesting access to the clinical and laboratory records of <strong id="gate_patient_name" class="text-main"></strong>. In compliance with Republic Act No. 10173 (Philippine Data Privacy Act), please select your clinical justification:
                    </p>
                    
                    {{-- Reason Dropdown Selector --}}
                    <div class="mb-3">
                        <label class="form-label small fw-bold uppercase text-secondary">Clinical Reason *</label>
                        <select id="gate_reason_select" class="form-select form-select-sm" required onchange="handleReasonChange(this.value)">
                            <option value="" disabled selected>-- Select Justification --</option>
                            <option value="Patient Consultation & Historical Review">Patient Consultation & Historical Review</option>
                            <option value="Laboratory Results Verification & Quality Check">Laboratory Results Verification & Quality Check</option>
                            <option value="Physician Diagnostic Evaluation">Physician Diagnostic Evaluation</option>
                            <option value="Patient-Requested Record Retrieval / Release">Patient-Requested Record Retrieval / Release</option>
                            <option value="Clinical Audit & Medical Record Inspection">Clinical Audit & Medical Record Inspection</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    {{-- Dynamic "Others" Custom Text Box --}}
                    <div class="mb-3" id="gate_custom_reason_wrapper" style="display: none;">
                        <label class="form-label small fw-bold uppercase text-secondary">Custom Reason / Specific Details *</label>
                        <textarea id="gate_custom_reason" class="form-control form-control-sm" rows="3" placeholder="Enter detailed clinical justification (min. 5 characters)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25 py-2.5">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold uppercase px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm fw-bold uppercase px-3">
                        <i class="bi bi-unlock-fill me-1"></i>Authorize & View Records
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

// Trigger Reason-Gate Modal
function promptAccess(userId, mode, type, isHistory, patientName = '') {
    document.getElementById('gate_target_user_id').value = userId;

    if (patientName) {
        document.getElementById('gate_patient_name').innerText = patientName;
    } else {
        const row = document.querySelector(`.directory-row[data-user-id="${userId}"]`);
        const nameEl = row ? row.querySelector('.fw-bold.h6') : null;
        document.getElementById('gate_patient_name').innerText = nameEl ? nameEl.innerText.trim() : 'this patient';
    }

    // Reset fields
    const select = document.getElementById('gate_reason_select');
    const customBox = document.getElementById('gate_custom_reason_wrapper');
    const customInput = document.getElementById('gate_custom_reason');
    if (select) select.value = '';
    if (customBox) customBox.style.display = 'none';
    if (customInput) {
        customInput.value = '';
        customInput.removeAttribute('required');
    }

    const modalEl = document.getElementById('reasonGateModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

// Handle Dropdown Change for "Others"
function handleReasonChange(value) {
    const customWrapper = document.getElementById('gate_custom_reason_wrapper');
    const customInput = document.getElementById('gate_custom_reason');
    if (value === 'Others') {
        customWrapper.style.display = 'block';
        customInput.setAttribute('required', 'required');
        customInput.focus();
    } else {
        customWrapper.style.display = 'none';
        customInput.removeAttribute('required');
        customInput.value = '';
    }
}

// Compile Reason on Submission
function handleGateSubmit(e) {
    const select = document.getElementById('gate_reason_select');
    const customInput = document.getElementById('gate_custom_reason');
    const hiddenInput = document.getElementById('gate_access_reason');

    if (!select.value) {
        e.preventDefault();
        alert('Please select a clinical reason.');
        return false;
    }

    if (select.value === 'Others') {
        const trimmed = customInput.value.trim();
        if (trimmed.length < 5) {
            e.preventDefault();
            alert('Please provide a specific clinical reason of at least 5 characters.');
            customInput.focus();
            return false;
        }
        hiddenInput.value = trimmed;
    } else {
        hiddenInput.value = select.value;
    }
    return true;
}
</script>
@endpush