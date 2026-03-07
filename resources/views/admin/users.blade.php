@extends('layouts.app')

@section('content')
<h3 class="text-neon fw-bold mb-4 uppercase" style="letter-spacing: 2px;">User Management</h3>

<div class="card p-0 border-0 shadow-lg overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-black text-secondary smaller fw-bold">
                <tr>
                    <th class="px-4 py-3">NAME & CONTACT</th>
                    <th>ROLE</th>
                    <th>ACCOUNT DETAILS</th>
                    <th class="text-end px-4">ACTION</th>
                </tr>
            </thead>
            <tbody class="text-white">
                @foreach($users as $user)
                <tr style="border-bottom: 1px solid var(--border-color); background-color: var(--card);">
                    <td class="px-4 py-3">
                        <div class="fw-bold text-white small">{{ strtoupper($user->name) }}</div>
                        <div class="text-secondary smaller fw-bold">{{ $user->email }}</div>
                        <div class="text-neon smaller fw-bold">{{ $user->phone }}</div>
                    </td>
                    <td>
                        <span class="badge border py-2 px-3 {{ $user->role == 'admin' ? 'text-danger border-danger' : ($user->role == 'staff' ? 'text-info border-info' : 'text-secondary border-secondary') }}">
                            {{ strtoupper($user->role) }}
                        </span>
                    </td>
                    <td>
                        <div class="smaller text-white-50">
                            {{ $user->sex }} | 
                            {{ $user->birthdate ? \Carbon\Carbon::parse($user->birthdate)->age : 'N/A' }} yrs old<br>
                            <span class="text-secondary">{{ $user->address }}</span>
                        </div>
                    </td>
                    <td class="text-end px-4">
                        @if($user->id !== Auth::id())
                            <div class="d-flex justify-content-end gap-2">
                                @if($user->role == 'user')
                                    <form action="{{ url('admin/users/'.$user->id.'/staff') }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-custom btn-outline-neon text-info py-1 px-3" style="font-size: 0.65rem;">PROMOTE</button>
                                    </form>
                                @elseif($user->role == 'staff')
                                    <form action="{{ url('admin/users/'.$user->id.'/user') }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-custom btn-outline-neon text-warning py-1 px-3" style="font-size: 0.65rem;">DEMOTE</button>
                                    </form>
                                @endif
                                
                                <button class="btn-custom btn-danger-custom py-1 px-2" data-bs-toggle="modal" data-bs-target="#delUser{{$user->id}}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @else
                            <span class="text-secondary smaller italic">CURRENT ADMIN</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- MODALS SECTION --}}
@foreach($users as $user)
    @if($user->id !== Auth::id())
    <div class="modal fade" id="delUser{{$user->id}}" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-danger bg-black p-4 text-center shadow-lg">
                <i class="bi bi-person-x text-danger fs-1 mb-2"></i>
                <h6 class="text-white fw-bold uppercase">Delete Account?</h6>
                <p class="text-secondary smaller">This will permanently remove <strong>{{ $user->name }}</strong> and all their appointments.</p>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn-custom btn-outline-neon flex-grow-1" data-bs-dismiss="modal">CANCEL</button>
                    <form action="{{ url('admin/users/'.$user->id) }}" method="POST" class="flex-grow-1">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-custom btn-danger-custom w-100">DELETE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection