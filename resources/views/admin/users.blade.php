@extends('layouts.app')
@section('title', 'User Directory')

@section('content')
<div class="container-fluid text-start animate-page">
 {{-- 1. CONTROL HEADER WITH CREATE USER BUTTON --}}
 <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3" style="border-color: var(--border-color) !important;">
     <div>
         <h2 class="text-accent fw-bold mb-0 uppercase tracking-tighter">User Directory</h2>
         <p class="text-secondary small mb-0">Manage system profiles, assign roles, and audit access credentials.</p>
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
         </div>
     </div>
 </div>

 {{-- 3. DIRECTORY TABLE CARD --}}
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
                             <button type="button" class="btn btn-sm btn-outline-neon py-1 px-2 fw-bold" title="View Patient Medical Archive" onclick="promptAccess('{{ $user->id }}', 'all', 'history', true)">
                                 <i class="bi bi-folder2-open me-1"></i>RECORDS
                             </button>
                             @endif
                             @if(Auth::user()->role === 'admin')
                             <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fw-bold" title="Edit Account Details">
                                 <i class="bi bi-pencil-square me-1"></i>EDIT
                             </a>
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
</script>
@endpush