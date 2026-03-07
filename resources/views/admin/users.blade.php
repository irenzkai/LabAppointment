@extends('layouts.app')

@section('content')
<h3 class="text-white mb-4">System Accounts</h3>

<div class="card p-0 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>User Details</th>
                <th>Access Level</th>
                <th>Location</th>
                <th class="text-end">Management</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>
                    <div class="text-white">{{ $user->name }}</div>
                    <small class="text-secondary">{{ $user->email }}</small>
                </td>
                <td>
                    <span class="badge {{ $user->role == 'admin' ? 'bg-danger' : ($user->role == 'staff' ? 'bg-primary' : 'bg-dark border') }}">
                        {{ strtoupper($user->role) }}
                    </span>
                </td>
                <td class="small text-secondary">{{ $user->address }}</td>
                <td class="text-end px-3">
                    @if($user->role == 'user')
                        <form action="{{ url('admin/users/'.$user->id.'/staff') }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-info">Make Staff</button>
                        </form>
                    @elseif($user->role == 'staff')
                        <form action="{{ url('admin/users/'.$user->id.'/user') }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-warning">Make User</button>
                        </form>
                    @endif
                    <form action="{{ url('admin/users/'.$user->id) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger ms-2" onclick="return confirm('Confirm Deletion?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection