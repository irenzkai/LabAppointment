@extends('layouts.app')

@section('content')
<h2 class="text-neon fw-bold mb-4 uppercase">USER MANAGEMENT</h2>

<div class="card p-0 border-secondary overflow-hidden shadow-lg">
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead class="bg-black text-secondary small uppercase">
                <tr>
                    <th class="ps-4">USER PROFILE</th>
                    <th>ROLE</th>
                    <th>ACCOUNT STATUS</th>
                    <th class="text-end pe-4">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="border-secondary">
                    <td class="ps-4">
                        <div class="text-white fw-bold">{{ strtoupper($user->name) }}</div>
                        <div class="text-secondary small">{{ $user->email }}</div>
                    </td>
                    <td>
                        <span class="badge border {{ $user->role == 'staff' ? 'border-info text-info' : 'border-neon text-neon' }} fw-bold small uppercase px-2 py-1">
                            {{ strtoupper($user->role) }}
                        </span>
                    </td>
                    <td>
                        <span class="text-{{ $user->is_active ? 'neon' : 'danger' }} fw-bold small">
                            ● {{ $user->is_active ? 'ACTIVE' : 'DISABLED' }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        {{-- EMPLOYEE ACTION --}}
                        @if(Auth::user()->isEmployee()) 
                            <button type="button" class="btn btn-sm btn-neon px-2 fw-bold small" 
                                    onclick="promptAccess('{{$user->id}}', 'all', 'history', true)">
                                VIEW RECORDS
                            </button>
                        @endif

                        {{-- ADMIN ACTIONS --}}
                        @if(Auth::user()->role === 'admin')
                            <div class="btn-group">
                                {{-- Promote/Demote --}}
                                <div class="dropdown d-inline">
                                    <button class="btn btn-sm btn-outline-info dropdown-toggle fw-bold small text-info" type="button" id="roleDropdown-{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        Change Role
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="roleDropdown-{{ $user->id }}">
                                        <form action="{{ route('admin.users.updateRole', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            
                                            <li>
                                                <button type="submit" name="role" value="user" class="dropdown-item small" {{ $user->role == 'user' ? 'disabled' : '' }}>
                                                    Make Patient
                                                </button>
                                            </li>
                                            <li>
                                                <button type="submit" name="role" value="staff" class="dropdown-item small" {{ $user->role == 'staff' ? 'disabled' : '' }}>
                                                    Make Staff
                                                </button>
                                            </li>
                                            <li>
                                                <button type="submit" name="role" value="lab_tech" class="dropdown-item small" {{ $user->role == 'lab_tech' ? 'disabled' : '' }}>
                                                    Make Lab Tech
                                                </button>
                                            </li>
                                        </form>
                                    </ul>
                                </div>

                                {{-- Disable/Enable --}}
                                <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST" class="ms-1">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} px-2 fw-bold">
                                        {{ $user->is_active ? 'DISABLE' : 'ENABLE' }}
                                    </button>
                                </form>

                                {{-- Permanent Delete (Only if disabled) --}}
                                @if(!$user->is_active)
                                    <button class="btn btn-sm btn-danger-custom ms-1" data-bs-toggle="modal" data-bs-target="#delModal{{$user->id}}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- DELETE MODALS --}}
@foreach($users as $user)
    @if(!$user->is_active)
    <div class="modal fade" id="delModal{{$user->id}}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="modal-content border-danger bg-black">
                @csrf @method('DELETE')
                <div class="modal-header border-danger bg-dark"><h6 class="modal-title text-danger fw-bold">PERMANENT DELETION</h6></div>
                <div class="modal-body p-4 text-start">
                    <p class="text-white small">Deleting <strong>{{ $user->name }}</strong> is permanent. Please provide a reason for the audit log.</p>
                    <label class="text-secondary smaller fw-bold uppercase">Reason for deletion</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer border-danger bg-dark">
                    <button type="submit" class="btn-danger-custom w-100 py-2">PURGE ACCOUNT</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endsection