@extends('layouts.app')
@section('title', 'Edit Account - ' . $user->name)
@section('content')
<div class="container text-start animate-page py-4" id="editUserContainer">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card p-4 p-md-5 border-secondary bg-card shadow-lg">
                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary border-opacity-25 pb-3">
                    <div>
                        <h2 class="text-accent fw-bold mb-0 uppercase tracking-tight" style="font-size: 1.85rem; letter-spacing: 1px;">
                            <i class="bi bi-pencil-square me-2"></i>Edit Account: {{ strtoupper($user->name) }}
                        </h2>
                        <p class="text-secondary mb-0 small">Update identity, PSGC address, access roles, security overrides, and family dependents.</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="btn-custom btn-cancel-custom px-4 py-2 fw-bold text-uppercase text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Back to Directory
                    </a>
                </div>

                @if ($errors->any())
                <div class="alert alert-clinical border-danger bg-danger bg-opacity-10 text-danger p-3 mb-4 rounded-3">
                    <div class="d-flex align-items-center mb-1 fw-bold uppercase small">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Validation Errors
                    </div>
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Tabs: Main Profile vs Family Dependents --}}
                <ul class="nav nav-pills mb-4 gap-2" id="userEditTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-2 small uppercase fw-bold" id="btn-profile" data-bs-toggle="pill" data-bs-target="#tab-main-profile" type="button">
                            Account Profile & Security
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link px-4 py-2 small uppercase fw-bold" id="btn-dependents" data-bs-toggle="pill" data-bs-target="#tab-dependents" type="button">
                            Family Dependents ({{ $user->dependents->count() }})
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="userEditTabsContent">
                    {{-- TAB 1: MAIN PROFILE & SECURITY (Continuous Form) --}}
                    <div class="tab-pane fade show active" id="tab-main-profile">
                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" id="editUserForm" onsubmit="return validateAdminEditUserForm(event)">
                            @csrf
                            @method('PUT')

                            {{-- 1. Identity --}}
                            <h6 class="text-accent mb-3 small fw-bold uppercase">1. Personal Identity</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">First Name</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control uppercase" value="{{ old('first_name', $user->first_name) }}" required>
                                    <div class="invalid-feedback d-none" id="err_first_name"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name" class="form-control uppercase" value="{{ old('middle_name', $user->middle_name) }}">
                                    <div class="invalid-feedback d-none" id="err_middle_name"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" class="form-control uppercase" value="{{ old('last_name', $user->last_name) }}" required>
                                    <div class="invalid-feedback d-none" id="err_last_name"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Suffix (Opt.)</label>
                                    <input type="text" name="suffix" id="suffix" class="form-control uppercase" value="{{ old('suffix', $user->suffix) }}" placeholder="e.g. JR">
                                    <div class="invalid-feedback d-none" id="err_suffix"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Birthdate</label>
                                    <input type="date" name="birthdate" id="birthdate" class="form-control" value="{{ $user->birthdate ? $user->birthdate->format('Y-m-d') : '' }}" required max="{{ date('Y-m-d') }}">
                                    <div class="invalid-feedback d-none" id="err_birthdate"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Sex</label>
                                    <select name="sex" id="sex" class="form-select" required>
                                        <option value="Male" {{ $user->sex === 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ $user->sex === 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                    <div class="invalid-feedback d-none" id="err_sex"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Contact Phone</label>
                                    <div class="input-group">
                                        <span class="input-group-text border-secondary bg-secondary bg-opacity-25 text-main fw-bold">09</span>
                                        @php
                                        $rawPhone = old('phone', $user->phone);
                                        $displayPhone = str_starts_with($rawPhone, '09') ? substr($rawPhone, 2) : $rawPhone;
                                        @endphp
                                        <input type="text" id="phone_display" class="form-control" placeholder="171234567" maxlength="9" value="{{ $displayPhone }}" oninput="this.value = this.value.replace(/[^0-9]/g, ''); syncEditPhone();" required>
                                    </div>
                                    <input type="hidden" name="phone" id="in_phone" value="{{ $rawPhone }}">
                                    <div class="invalid-feedback d-none" id="err_phone"></div>
                                </div>
                            </div>

                            {{-- 2. Address --}}
                            <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">2. PSGC Residential Address</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Province</label>
                                    <select id="edit_prov" name="province" class="form-select" onchange="fetchEditCities(this.value)" required>
                                        <option value="">Select Province</option>
                                    </select>
                                    <div class="invalid-feedback d-none" id="err_province"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">City / Municipality</label>
                                    <select id="edit_city" name="city" class="form-select" onchange="fetchEditBarangays(this.value)" disabled required>
                                        <option value="">Select Province First</option>
                                    </select>
                                    <div class="invalid-feedback d-none" id="err_city"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Barangay</label>
                                    <select id="edit_brgy" name="barangay" class="form-select" disabled required>
                                        <option value="">Select City First</option>
                                    </select>
                                    <div class="invalid-feedback d-none" id="err_barangay"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Street / House No.</label>
                                    <input type="text" name="street" id="edit_street" class="form-control uppercase" value="{{ old('street', $user->street) }}" required>
                                    <div class="invalid-feedback d-none" id="err_street"></div>
                                </div>
                            </div>

                            {{-- 3. Access & Security --}}
                            <h6 class="text-accent mb-3 small fw-bold uppercase border-top border-secondary border-opacity-10 pt-3">3. Access Role & Email Verification</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Role / Access Level</label>
                                    <select name="role" id="role" class="form-select" required>
                                        <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>Patient / User</option>
                                        <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>Clinic Staff</option>
                                        <option value="lab_tech" {{ $user->role === 'lab_tech' ? 'selected' : '' }}>Laboratory Technician</option>
                                    </select>
                                    <div class="invalid-feedback d-none" id="err_role"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                    <div class="invalid-feedback d-none" id="err_email"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Email Verification Option</label>
                                    <select name="email_action" class="form-select">
                                        <option value="" selected>-- Keep Current Verification Status --</option>
                                        <option value="verify_now">Mark Email as Verified Immediately</option>
                                        <option value="send_notification">Trigger Email Verification Notification</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-bold mb-1 uppercase">Password Modifications</label>
                                    <select name="password_option" id="pass_opt" class="form-select" onchange="toggleManualPass(this.value)">
                                        <option value="" selected>-- Choose Option (Optional) --</option>
                                        <option value="send_link">Send Password Reset Link</option>
                                        <option value="manual">Manually Set Password</option>
                                    </select>
                                </div>
                                <div id="manual_pass_wrapper" class="col-12 d-none">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input type="password" name="password" id="password" class="form-control" placeholder="New temporary password">
                                            <div class="invalid-feedback d-none" id="err_password"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm temporary password">
                                            <div class="invalid-feedback d-none" id="err_password_confirmation"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 4. Justification --}}
                            <div class="border-top border-secondary border-opacity-10 pt-3 mb-4">
                                <h6 class="text-danger mb-2 small fw-bold uppercase"><i class="bi bi-shield-exclamation me-1"></i>4. Administrative Justification</h6>
                                <div class="mb-3">
                                    <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Select Administrative Justification</label>
                                    <select name="reason" id="reason_select" class="form-select" onchange="toggleEditReason(this.value)" required>
                                        <option value="" disabled {{ old('reason') ? '' : 'selected' }}>-- Select a valid justification --</option>
                                        <option value="Routine administrative profile update" {{ old('reason') == 'Routine administrative profile update' ? 'selected' : '' }}>Routine administrative profile update</option>
                                        <option value="Official patient request for credential/demographic revision" {{ old('reason') == 'Official patient request for credential/demographic revision' ? 'selected' : '' }}>Official patient request for credential/demographic revision</option>
                                        <option value="Security override and manual password reset" {{ old('reason') == 'Security override and manual password reset' ? 'selected' : '' }}>Security override and manual password reset</option>
                                        <option value="Others">Others (Specify below)</option>
                                    </select>
                                    <div class="invalid-feedback d-none" id="err_reason"></div>
                                </div>
                                <div id="reason_custom_wrapper" class="mb-3 d-none">
                                    <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Specify Custom Reason</label>
                                    <textarea id="reason_custom" class="form-control" rows="2" placeholder="Provide details regarding the profile update..."></textarea>
                                </div>
                            </div>

                            <div class="d-flex gap-3">
                                <a href="{{ route('admin.users.index') }}" class="btn-custom btn-cancel-custom w-50 py-3 fw-bold uppercase text-decoration-none text-center">Cancel</a>
                                <button type="submit" class="btn-custom btn-accent w-50 py-3 fw-bold uppercase shadow-sm">SAVE PROFILE CHANGES</button>
                            </div>
                        </form>

                        {{-- Separate Deactivation / Reactivation Action Card with Warning Modal --}}
                        <div class="card p-4 border-danger shadow-sm mt-5 text-start" style="background-color: rgba(220, 53, 69, 0.04) !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-danger fw-bold uppercase mb-1">
                                        <i class="bi bi-shield-exclamation me-1"></i>Account Status Management
                                    </h6>
                                    <p class="text-secondary small mb-0">Current Status: <strong class="{{ $user->trashed() ? 'text-danger' : 'text-accent' }}">{{ $user->trashed() ? 'DEACTIVATED / SUSPENDED' : 'ACTIVE' }}</strong></p>
                                </div>
                                <div>
                                    @if($user->trashed())
                                    <button type="button" class="btn btn-success fw-bold uppercase px-4 py-2" data-bs-toggle="modal" data-bs-target="#statusModal">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> REACTIVATE ACCOUNT
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-outline-danger fw-bold uppercase px-4 py-2" data-bs-toggle="modal" data-bs-target="#statusModal">
                                        <i class="bi bi-slash-circle me-1"></i> DEACTIVATE ACCOUNT
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: FAMILY DEPENDENTS --}}
                    <div class="tab-pane fade" id="tab-dependents">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="text-accent fw-bold mb-0 uppercase">Dependents linked to {{ $user->name }}</h6>
                                <small class="text-muted">Add, update, or archive family dependents registered under this patient account.</small>
                            </div>
                            <a href="{{ route('admin.users.dependents.create', $user->id) }}" class="btn btn-accent btn-sm fw-bold uppercase px-3 py-2">
                                <i class="bi bi-plus-lg me-1"></i> ADD DEPENDENT
                            </a>
                        </div>

                        {{-- Active Dependents Grid --}}
                        <div class="row g-3 mb-4">
                            @forelse($user->dependents->where('deleted_at', null) as $dep)
                            @php $isOver18 = $dep->birthdate->age >= 18; @endphp
                            <div class="col-md-6 text-start">
                                <div class="p-3 rounded border {{ $isOver18 ? 'border-warning' : 'border-secondary border-opacity-20' }} d-flex justify-content-between align-items-center h-100 bg-card">
                                    <div>
                                        <div class="fw-bold text-main small">{{ strtoupper($dep->name) }}</div>
                                        <small class="text-secondary">{{ strtoupper($dep->relationship) }} | {{ strtoupper($dep->sex) }} | {{ $dep->birthdate->age }} YRS OLD</small>
                                        @if($isOver18) <span class="badge bg-warning text-dark ms-1">18+ (Promotion Eligible)</span> @endif
                                        <div class="text-accent smaller mt-1"><i class="bi bi-geo-alt-fill me-1"></i>{{ $dep->address }}</div>
                                    </div>

                                    {{-- 3-Dot Dropdown Menu --}}
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-1 px-2.5" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow bg-card border-secondary">
                                            <li>
                                                <a class="dropdown-item small py-2" href="{{ route('admin.users.dependents.edit', [$user->id, $dep->id]) }}">
                                                    <i class="bi bi-pencil-square me-2 text-info"></i>Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item small py-2" data-bs-toggle="modal" data-bs-target="#promoteDepModal{{ $dep->id }}">
                                                    <i class="bi bi-arrow-up-circle me-2 text-accent"></i>Promote Account
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider border-secondary border-opacity-50"></li>
                                            <li>
                                                <button type="button" class="dropdown-item small py-2 text-danger" data-bs-toggle="modal" data-bs-target="#archiveDepModal{{ $dep->id }}">
                                                    <i class="bi bi-trash me-2"></i>Archive
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            {{-- Promote Dependent Modal with Reason Dropdown Confirmation --}}
                            <div class="modal fade" id="promoteDepModal{{ $dep->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form action="{{ route('admin.users.dependents.promote', [$user->id, $dep->id]) }}" method="POST" class="modal-content border-secondary bg-card text-start p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                                        @csrf
                                        <h5 class="text-accent fw-bold uppercase mb-2">
                                            <i class="bi bi-arrow-up-circle me-2"></i>Promote Dependent Account
                                        </h5>
                                        <p class="small text-muted mb-3">
                                            Promoting <strong>{{ strtoupper($dep->name) }}</strong> will directly redirect to create an independent user account with historical medical records automatically transitioned.
                                        </p>
                                        <div class="mb-3">
                                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Reason for Promotion</label>
                                            <select name="reason" id="promote_reason_select_{{ $dep->id }}" class="form-select" onchange="togglePromoteReason('{{ $dep->id }}', this.value)" required>
                                                <option value="" disabled selected>-- Select a valid justification --</option>
                                                <option value="Minor reached adulthood / account promotion">Minor reached adulthood / account promotion</option>
                                                <option value="Requested by parent / account owner">Requested by parent / account owner</option>
                                                <option value="Administrative independent account creation">Administrative independent account creation</option>
                                                <option value="Others">Others (Specify below)</option>
                                            </select>
                                        </div>
                                        <div id="promote_custom_reason_wrapper_{{ $dep->id }}" class="mb-3 d-none">
                                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Specify Custom Reason</label>
                                            <textarea id="promote_custom_reason_{{ $dep->id }}" class="form-control" rows="2" placeholder="Provide details regarding the account promotion..."></textarea>
                                        </div>
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="button" class="btn btn-outline-secondary w-50 py-2 fw-bold uppercase" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-accent w-50 py-2 fw-bold uppercase shadow-sm">Confirm & Create</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Archive Dependent Modal with Reason --}}
                            <div class="modal fade" id="archiveDepModal{{ $dep->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form action="{{ route('admin.users.dependents.destroy', [$user->id, $dep->id]) }}" method="POST" class="modal-content border-danger bg-card text-start p-4">
                                        @csrf 
                                        @method('DELETE')
                                        <h5 class="text-danger fw-bold uppercase mb-2">Archive Dependent: {{ $dep->name }}</h5>
                                        <p class="small text-muted mb-3">This dependent profile will be soft-deleted and moved to the archive directory.</p>
                                        
                                        <div class="mb-3">
                                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Reason for Archiving</label>
                                            <select name="reason" class="form-select" required>
                                                <option value="Minor reached adulthood / account promotion">Minor reached adulthood / account promotion</option>
                                                <option value="Requested by parent / account owner">Requested by parent / account owner</option>
                                                <option value="Duplicate dependent profile cleanup">Duplicate dependent profile cleanup</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-danger w-100 py-2 fw-bold uppercase">CONFIRM ARCHIVE</button>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <div class="col-12 py-4 text-center text-muted italic">No active dependents linked to this user profile.</div>
                            @endforelse
                        </div>

                        {{-- Archived Dependents Directory --}}
                        @php $archivedDeps = $user->dependents->where('deleted_at', '!=', null); @endphp
                        @if($archivedDeps->count() > 0)
                        <hr class="border-secondary border-opacity-25 my-4">
                        <h6 class="text-warning fw-bold mb-3 uppercase small"><i class="bi bi-archive me-1"></i>Archived Dependents (Directory)</h6>
                        <div class="row g-3">
                            @foreach($archivedDeps as $archived)
                            <div class="col-md-6 text-start">
                                <div class="p-3 rounded border border-warning border-opacity-30 d-flex justify-content-between align-items-center bg-card">
                                    <div>
                                        <div class="text-warning fw-bold small">{{ strtoupper($archived->name) }}</div>
                                        <small class="text-secondary">{{ strtoupper($archived->relationship) }} | {{ $archived->birthdate->age }} YRS OLD (ARCHIVED)</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-warning py-1 px-3 fw-bold uppercase" data-bs-toggle="modal" data-bs-target="#restoreDepModal{{ $archived->id }}">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> RESTORE
                                    </button>
                                </div>
                            </div>

                            {{-- Restore Dependent Modal with Reason Dropdown --}}
                            <div class="modal fade" id="restoreDepModal{{ $archived->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form action="{{ route('admin.users.dependents.restore', [$user->id, $archived->id]) }}" method="POST" class="modal-content border-warning bg-card text-start p-4" style="background-color: var(--bg-card); border: 1.5px solid var(--border-color); color: var(--text-main);">
                                        @csrf
                                        <h5 class="text-warning fw-bold uppercase mb-2">
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Dependent Profile?
                                        </h5>
                                        <p class="small text-muted mb-3">
                                            You are about to reactivate <strong>{{ strtoupper($archived->name) }}</strong> and restore them to active family dependents.
                                        </p>
                                        <div class="mb-3">
                                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Reason for Restoration</label>
                                            <select name="reason" id="restore_reason_select_{{ $archived->id }}" class="form-select" onchange="toggleRestoreReason('{{ $archived->id }}', this.value)" required>
                                                <option value="" disabled selected>-- Select a valid justification --</option>
                                                <option value="Restored per parent/guardian request">Restored per parent/guardian request</option>
                                                <option value="Administrative correction of accidental archival">Administrative correction of accidental archival</option>
                                                <option value="Re-activation of family dependent profile">Re-activation of family dependent profile</option>
                                                <option value="Others">Others (Specify below)</option>
                                            </select>
                                        </div>
                                        <div id="restore_custom_reason_wrapper_{{ $archived->id }}" class="mb-3 d-none">
                                            <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Specify Custom Reason</label>
                                            <textarea id="restore_custom_reason_{{ $archived->id }}" class="form-control" rows="2" placeholder="Provide justification for restoring this profile..."></textarea>
                                        </div>
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="button" class="btn btn-outline-secondary w-50 py-2 fw-bold uppercase" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning w-50 py-2 fw-bold uppercase text-dark">Restore Dependent</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Deactivation / Reactivation Confirmation Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="modal-content border-secondary bg-card text-start p-4">
            @csrf 
            @method('PATCH')
            <h5 class="{{ $user->trashed() ? 'text-success' : 'text-danger' }} fw-bold uppercase mb-2">
                {{ $user->trashed() ? 'Reactivate Account?' : 'Deactivate Account?' }}
            </h5>
            <p class="small text-muted mb-3">
                @if($user->trashed())
                Reactivating this account will restore full portal access for <strong>{{ $user->name }}</strong>.
                @else
                Deactivating <strong>{{ $user->name }}</strong> will suspend login access. Under medical regulations, patient files are safely retained in restricted archive for 10 years before destruction.
                @endif
            </p>
            <div class="mb-3">
                <label class="smaller fw-bold text-secondary uppercase mb-1 d-block">Select Reason</label>
                <select name="reason" class="form-select" required>
                    <option value="Administrative policy compliance adjustment">Administrative policy compliance adjustment</option>
                    <option value="User requested account status modification">User requested account status modification</option>
                    <option value="Security precaution / identity verification">Security precaution / identity verification</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            <button type="submit" class="btn {{ $user->trashed() ? 'btn-success' : 'btn-danger' }} w-100 py-2 fw-bold uppercase">
                CONFIRM {{ $user->trashed() ? 'REACTIVATION' : 'DEACTIVATION' }}
            </button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.btn-cancel-custom {
    color: var(--text-muted) !important;
    border: 1.5px solid var(--border-color) !important;
    background-color: transparent !important;
    transition: all 0.2s ease-in-out;
}
.btn-cancel-custom:hover, .btn-cancel-custom:focus {
    color: var(--text-main) !important;
    background-color: rgba(255, 255, 255, 0.08) !important;
    border-color: var(--border-color) !important;
    box-shadow: none !important;
}
#editUserContainer .form-control:focus,
#editUserContainer .form-select:focus {
    border-color: var(--brand-accent) !important;
    box-shadow: 0 0 0 3px rgba(25, 211, 140, 0.15) !important;
}
#editUserContainer .is-invalid {
    border-color: #ff4d4d !important;
    box-shadow: 0 0 0 3px rgba(255, 77, 77, 0.15) !important;
}
</style>
@endpush

@push('scripts')
<script>
const savedProv = "{{ $user->province }}";
const savedCity = "{{ $user->city }}";
const savedBrgy = "{{ $user->barangay }}";

function syncEditPhone() {
    const display = document.getElementById('phone_display');
    const hidden = document.getElementById('in_phone');
    if (display && hidden) {
        hidden.value = display.value ? '09' + display.value.trim() : '';
        clearFieldError(display);
    }
}

function toggleEditReason(val) {
    const wrapper = document.getElementById('reason_custom_wrapper');
    const customInput = document.getElementById('reason_custom');
    const selectEl = document.getElementById('reason_select');
    if (val === 'Others') {
        wrapper.classList.remove('d-none');
        customInput.setAttribute('required', 'required');
        customInput.setAttribute('name', 'reason');
        selectEl.removeAttribute('name');
    } else {
        wrapper.classList.add('d-none');
        customInput.removeAttribute('required');
        customInput.removeAttribute('name');
        selectEl.setAttribute('name', 'reason');
    }
    clearFieldError(selectEl);
}

function togglePromoteReason(depId, val) {
    const wrapper = document.getElementById(`promote_custom_reason_wrapper_${depId}`);
    const customInput = document.getElementById(`promote_custom_reason_${depId}`);
    const selectEl = document.getElementById(`promote_reason_select_${depId}`);
    if (val === 'Others') {
        wrapper.classList.remove('d-none');
        customInput.setAttribute('required', 'required');
        customInput.setAttribute('name', 'reason');
        selectEl.removeAttribute('name');
    } else {
        wrapper.classList.add('d-none');
        customInput.removeAttribute('required');
        customInput.removeAttribute('name');
        selectEl.setAttribute('name', 'reason');
    }
}

function toggleRestoreReason(depId, val) {
    const wrapper = document.getElementById(`restore_custom_reason_wrapper_${depId}`);
    const customInput = document.getElementById(`restore_custom_reason_${depId}`);
    const selectEl = document.getElementById(`restore_reason_select_${depId}`);
    if (val === 'Others') {
        wrapper.classList.remove('d-none');
        customInput.setAttribute('required', 'required');
        customInput.setAttribute('name', 'reason');
        selectEl.removeAttribute('name');
    } else {
        wrapper.classList.add('d-none');
        customInput.removeAttribute('required');
        customInput.removeAttribute('name');
        selectEl.setAttribute('name', 'reason');
    }
}

function toggleManualPass(val) {
    const wrapper = document.getElementById('manual_pass_wrapper');
    if(val === 'manual') wrapper.classList.remove('d-none');
    else wrapper.classList.add('d-none');
}

function setFieldError(input, errDivId, message) {
    if (!input) return;
    input.classList.add('is-invalid');
    const errDiv = document.getElementById(errDivId);
    if (errDiv) {
        errDiv.innerText = message;
        errDiv.classList.add('d-block');
        errDiv.classList.remove('d-none');
    }
}

function clearFieldError(input) {
    if (!input) return;
    input.classList.remove('is-invalid');
    let errDiv = document.getElementById('err_' + input.id) || document.getElementById('err_' + input.name);
    if (errDiv) {
        errDiv.innerText = '';
        errDiv.classList.add('d-none');
        errDiv.classList.remove('d-block');
    }
}

function validateNameString(val, fieldName) {
    if (!val || val === 'N/A') return null;
    const charRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc \s.\'-]+$/;
    const startRegex = /^[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc ]/;
    const consecutiveRegex = /[.\'-]{2,}/;
    const letterRegex = /[a-zA-Z\u00f1\u00d1\u00e1\u00c1\u00e9\u00c9\u00ed\u00cd\u00f3\u00d3\u00fa\u00da\u00fc\u00dc]/;

    if (!charRegex.test(val)) return `${fieldName} may only contain letters, spaces, periods, hyphens, and apostrophes.`;
    if (!startRegex.test(val)) return `${fieldName} must start with a letter.`;
    if (!letterRegex.test(val)) return `${fieldName} must contain at least one letter.`;
    if (consecutiveRegex.test(val)) return `${fieldName} cannot contain consecutive punctuation marks.`;
    if (val.length > 60) return `${fieldName} cannot exceed 60 characters.`;
    return null;
}

function validateAdminEditUserForm(e) {
    let isValid = true;
    let firstInvalidInput = null;

    document.querySelectorAll('#editUserForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#editUserForm .invalid-feedback').forEach(el => {
        el.innerText = '';
        el.classList.add('d-none');
        el.classList.remove('d-block');
    });

    function markInvalid(input, errId, msg) {
        setFieldError(input, errId, msg);
        isValid = false;
        if (!firstInvalidInput) firstInvalidInput = input;
    }

    const fName = document.getElementById('first_name');
    const fErr = validateNameString(fName ? fName.value.trim() : '', 'First Name');
    if (!fName || !fName.value.trim()) markInvalid(fName, 'err_first_name', 'First Name is required.');
    else if (fErr) markInvalid(fName, 'err_first_name', fErr);

    const mName = document.getElementById('middle_name');
    const mErr = validateNameString(mName ? mName.value.trim() : '', 'Middle Name');
    if (mErr) markInvalid(mName, 'err_middle_name', mErr);

    const lName = document.getElementById('last_name');
    const lErr = validateNameString(lName ? lName.value.trim() : '', 'Last Name');
    if (!lName || !lName.value.trim()) markInvalid(lName, 'err_last_name', 'Last Name is required.');
    else if (lErr) markInvalid(lName, 'err_last_name', lErr);

    const bday = document.getElementById('birthdate');
    if (!bday || !bday.value) {
        markInvalid(bday, 'err_birthdate', 'Birthdate is required.');
    } else {
        const dob = new Date(bday.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        if (age < 18) markInvalid(bday, 'err_birthdate', 'Administrative Policy: Users must be at least 18 years old.');
    }

    const sex = document.getElementById('sex');
    if (!sex || !sex.value) markInvalid(sex, 'err_sex', 'Please select a gender.');

    const displayPhone = document.getElementById('phone_display');
    const hiddenPhone = document.getElementById('in_phone');
    const phoneRegex = /^09\d{9}$/;
    if (!displayPhone || !displayPhone.value.trim()) {
        markInvalid(displayPhone, 'err_phone', 'Contact phone number is required.');
    } else if (!phoneRegex.test(hiddenPhone.value.trim())) {
        markInvalid(displayPhone, 'err_phone', 'Phone number must contain exactly 11 digits (09 + 9 digits).');
    }

    const prov = document.getElementById('edit_prov');
    const city = document.getElementById('edit_city');
    const brgy = document.getElementById('edit_brgy');
    const street = document.getElementById('edit_street');

    if (!prov || !prov.value) markInvalid(prov, 'err_province', 'Province selection is required.');
    if (!city || !city.value) markInvalid(city, 'err_city', 'City selection is required.');
    if (!brgy || !brgy.value) markInvalid(brgy, 'err_barangay', 'Barangay selection is required.');
    if (!street || !street.value.trim()) markInvalid(street, 'err_street', 'Street address is required.');

    const email = document.getElementById('email');
    const emailRegex = /^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!email || !email.value.trim()) {
        markInvalid(email, 'err_email', 'Email Address is required.');
    } else if (!emailRegex.test(email.value.trim())) {
        markInvalid(email, 'err_email', 'Please enter a valid email address with a domain.');
    }

    const reasonSel = document.getElementById('reason_select');
    const customReason = document.getElementById('reason_custom');
    const activeReasonVal = reasonSel.value === 'Others' ? customReason.value.trim() : reasonSel.value;
    if (!activeReasonVal || activeReasonVal.length < 5) {
        markInvalid(reasonSel.value === 'Others' ? customReason : reasonSel, 'err_reason', 'Administrative justification is required.');
    }

    if (!isValid) {
        e.preventDefault();
        e.stopPropagation();
        if (firstInvalidInput) {
            firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidInput.focus();
        }
        return false;
    }

    compileAdminUserAddress();
    return true;
}

async function fetchEditProvinces() {
    const provSel = document.getElementById('edit_prov');
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces/');
        const data = await res.json();
        provSel.innerHTML = '<option value="">Select Province</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(p => {
            provSel.innerHTML += `<option value="${p.code}">${p.name}</option>`;
        });
        if(savedProv) {
            let opt = Array.from(provSel.options).find(o => o.text.toUpperCase() === savedProv.toUpperCase());
            if(opt) { provSel.value = opt.value; await fetchEditCities(opt.value); }
        }
    } catch(e){}
}

async function fetchEditCities(provCode) {
    const citySel = document.getElementById('edit_city');
    const brgySel = document.getElementById('edit_brgy');
    citySel.disabled = true; brgySel.disabled = true;
    citySel.innerHTML = '<option value="">Loading...</option>';
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provCode}/cities-municipalities/`);
        const data = await res.json();
        citySel.innerHTML = '<option value="">Select City</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(c => {
            citySel.innerHTML += `<option value="${c.code}">${c.name}</option>`;
        });
        citySel.disabled = false;
        if(savedCity) {
            let opt = Array.from(citySel.options).find(o => o.text.toUpperCase() === savedCity.toUpperCase());
            if(opt) { citySel.value = opt.value; await fetchEditBarangays(opt.value); }
        }
    } catch(e){}
}

async function fetchEditBarangays(cityCode) {
    const brgySel = document.getElementById('edit_brgy');
    brgySel.disabled = true;
    brgySel.innerHTML = '<option value="">Loading...</option>';
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
        const data = await res.json();
        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        data.sort((a,b) => a.name.localeCompare(b.name)).forEach(b => {
            brgySel.innerHTML += `<option value="${b.name}">${b.name}</option>`;
        });
        brgySel.disabled = false;
        if(savedBrgy) {
            let opt = Array.from(brgySel.options).find(o => o.text.toUpperCase() === savedBrgy.toUpperCase());
            if(opt) brgySel.value = opt.value;
        }
    } catch(e){}
}

function compileAdminUserAddress() {
    const prov = document.getElementById('edit_prov');
    const city = document.getElementById('edit_city');
    const brgy = document.getElementById('edit_brgy');
    if (prov && city && brgy) {
        if(prov.selectedIndex >= 0) prov.options[prov.selectedIndex].value = prov.options[prov.selectedIndex].text;
        if(city.selectedIndex >= 0) city.options[city.selectedIndex].value = city.options[city.selectedIndex].text;
        if(brgy.selectedIndex >= 0) brgy.options[brgy.selectedIndex].value = brgy.options[brgy.selectedIndex].text;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchEditProvinces();
    syncEditPhone();
    document.querySelectorAll('#editUserForm input, #editUserForm select').forEach(input => {
        input.addEventListener('input', () => clearFieldError(input));
        input.addEventListener('change', () => clearFieldError(input));
    });

    if (window.location.hash === '#tab-dependents') {
        const depTabBtn = document.getElementById('btn-dependents');
        if (depTabBtn) {
            const tab = bootstrap.Tab.getInstance(depTabBtn) || new bootstrap.Tab(depTabBtn);
            tab.show();
        }
    }
});
</script>
@endpush