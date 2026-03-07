@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 800px;">
    <h3 class="text-white mb-4">Account Settings</h3>

    <!-- Edit Profile Section -->
    <div class="card shadow-lg mb-5">
        <div class="card-header border-secondary bg-dark">
            <h5 class="mb-0 text-white">Profile Information</h5>
            <small class="text-secondary">Update your account's profile information and details.</small>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small text-secondary">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small text-secondary">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small text-secondary">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small text-secondary">Birthdate</label>
                        <input type="date" name="birthdate" class="form-control" value="{{ old('birthdate', $user->birthdate->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label small text-secondary">Sex</label>
                        <select name="sex" class="form-select">
                            <option value="Male" {{ $user->sex == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ $user->sex == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ $user->sex == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label small text-secondary">Home Address</label>
                        <textarea name="address" class="form-control" rows="2" required>{{ old('address', $user->address) }}</textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Account Section -->
    <div class="card shadow-lg border-danger mb-5">
        <div class="card-header border-danger bg-dark">
            <h5 class="mb-0 text-danger">Danger Zone</h5>
            <small class="text-secondary">Once your account is deleted, all of its resources and data will be permanently deleted.</small>
        </div>
        <div class="card-body">
            <p class="text-secondary small">Please enter your password to confirm you would like to permanently delete your account.</p>
            
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="mb-3" style="max-width: 400px;">
                    <input type="password" name="password" class="form-control" placeholder="Current Password" required>
                    @if($errors->userDeletion->get('password'))
                        <div class="text-danger small mt-1">Incorrect password.</div>
                    @endif
                </div>

                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete your account?')">
                    Delete Account Permanently
                </button>
            </form>
        </div>
    </div>
</div>
@endsection