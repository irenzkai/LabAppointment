@extends('layouts.app')

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
                                    <span class="status-indicator rounded-circle {{ $user->is_active ? 'bg-neon' : 'bg-danger' }} shadow-neon" style="width: 8px; height: 8px; display: inline-block;"></span>
                                    <span class="text-{{ $user->is_active ? 'neon' : 'danger' }} fw-bold small">
                                        {{ $user->is_active ? 'ACTIVE' : 'SUSPENDED' }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-1.5 justify-content-end align-items-center">
                                    @if(Auth::user()->isEmployee())
                                        <button type="button" class="btn btn-sm btn-outline-neon py-1 px-2 fw-bold" title="View Patient Medical Archive" onclick="promptAccess('{{$user->id}}', 'all', 'history', true)">
                                            <i class="bi bi-folder2-open me-1"></i>RECORDS
                                        </button>
                                    @endif

                                    @if(Auth::user()->role === 'admin')
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-1" type="button" id="roleDrop-{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-shield-shaded me-1"></i>Role
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-dark bg-black border-secondary" aria-labelledby="roleDrop-{{ $user->id }}">
                                                <li><h6 class="dropdown-header text-accent">Modify Authority</h6></li>
                                                <form action="{{ route('admin.users.updateRole', $user->id) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <li>
                                                        <button type="submit" name="role" value="user" class="dropdown-item small d-flex justify-content-between align-items-center" {{ $user->role == 'user' ? 'disabled' : '' }}>
                                                            Patient @if($user->role == 'user')<i class="bi bi-check text-neon"></i>@endif
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="submit" name="role" value="staff" class="dropdown-item small d-flex justify-content-between align-items-center" {{ $user->role == 'staff' ? 'disabled' : '' }}>
                                                            Staff @if($user->role == 'staff')<i class="bi bi-check text-neon"></i>@endif
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="submit" name="role" value="lab_tech" class="dropdown-item small d-flex justify-content-between align-items-center" {{ $user->role == 'lab_tech' ? 'disabled' : '' }}>
                                                            Lab Tech @if($user->role == 'lab_tech')<i class="bi bi-check text-neon"></i>@endif
                                                        </button>
                                                    </li>
                                                </form>
                                            </ul>
                                        </div>
                                    @endif

                                    <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST" class="m-0">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-neon' }} py-1 px-2.5 fw-bold" title="{{ $user->is_active ? 'Suspend Account' : 'Re-activate Account' }}">
                                            <i class="bi {{ $user->is_active ? 'bi-lock-fill' : 'bi-unlock-fill' }}"></i>
                                        </button>
                                    </form>

                                    @if(!$user->is_active)
                                        <button class="btn btn-sm btn-danger-custom py-1" data-bs-toggle="modal" data-bs-target="#delModal{{$user->id}}" title="Purge Record">
                                            <i class="bi bi-trash3-fill"></i>
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
</div>

{{-- 3. PERMANENT DELETE AUDIT MODALS --}}
@if(Auth::user()->role === 'admin')
    @foreach($users as $user)
        @if(!$user->is_active)
            <div class="modal fade" id="delModal{{$user->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="modal-content shadow-lg border-danger delete-audit-form" data-app-id="{{$user->id}}" style="background-color: var(--bg-card); color: var(--text-main);">
                        @csrf @method('DELETE')
                        
                        <div class="modal-header border-danger bg-danger bg-opacity-10 py-3">
                            <h5 class="modal-title text-danger fw-bold uppercase small m-0">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>Critical: Permanent Deletion
                            </h5>
                            {{-- FIXED: Removed btn-close-white to ensure visibility on light backgrounds --}}
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="modal-body p-4 text-start">
                            <p class="small mb-4" style="color: var(--text-muted) !important;">
                                You are about to permanently purge the profile registry for <strong style="color: var(--text-main) !important;">{{ $user->name }}</strong>. 
                                Relational dependencies will be destroyed. This action is irreversible.
                            </p>

                            {{-- FIXED: Added reason dropdown with dynamic "Others" toggle --}}
                            <div class="mb-3">
                                <label class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Reason for Deletion</label>
                                <select class="form-select delete-reason-select" data-user-id="{{$user->id}}" required>
                                    <option value="" disabled selected>-- Select a valid reason --</option>
                                    <option value="Duplicate profile identified">Duplicate profile identified</option>
                                    <option value="Patient explicitly requested account closure">Patient explicitly requested account closure</option>
                                    <option value="Administrative security/compliance mandate">Administrative security/compliance mandate</option>
                                    <option value="Incorrect identity information provided during registration">Incorrect identity information provided during registration</option>
                                    <option value="Others">Others (Please specify below)</option>
                                </select>
                            </div>

                            <div id="custom_delete_reason_wrapper_{{$user->id}}" class="mb-2 d-none">
                                <label class="smaller fw-bold mb-2 uppercase d-block" style="color: var(--text-muted);">Specify Custom Reason</label>
                                <textarea name="reason" id="delete_reason_text_{{$user->id}}" class="form-control delete-reason-textarea" style="background-color: rgba(0,0,0,0.015); border: 1.5px solid var(--border-color); color: var(--text-main);" rows="3" placeholder="Identify the specific administrative mandate..."></textarea>
                                <div class="mt-2">
                                    <small class="text-muted smaller italic">Minimum 5 characters required for audit validation.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer p-0" style="border-top: 1px solid var(--border-color);">
                            <div class="d-flex w-100">
                                <button type="button" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase smaller" style="color: var(--text-muted); border-right: 1px solid var(--border-color) !important; border-radius: 0;" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-link text-decoration-none w-50 py-3 fw-bold uppercase text-danger smaller hover-bg-danger" style="border-radius: 0;">Purge Permanently</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Directory Search & Filters
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

    // 2. FIXED: Delete Modal Reason Dropdown Toggle Logic
    document.querySelectorAll('.delete-reason-select').forEach(select => {
        select.addEventListener('change', function() {
            const userId = this.dataset.userId;
            const wrapper = document.getElementById(`custom_delete_reason_wrapper_${userId}`);
            const textarea = document.getElementById(`delete_reason_text_${userId}`);
            
            if (this.value === 'Others') {
                wrapper.classList.remove('d-none');
                textarea.setAttribute('required', 'required');
                textarea.value = '';
            } else {
                wrapper.classList.add('d-none');
                textarea.removeAttribute('required');
                textarea.value = this.value; // Sync select value to textarea for submission
            }
        });
    });

    // Form submission validation for reason length
    document.querySelectorAll('.delete-audit-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const textarea = this.querySelector('.delete-reason-textarea');
            if (textarea.value.trim().length < 5) {
                e.preventDefault();
                alert('A valid deletion reason of at least 5 characters is required for the clinical audit trail.');
            }
        });
    });
});
</script>
@endpush