<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #000; min-height: 100vh; display: flex; align-items: center; padding: 40px 0; }
        .card { background-color: #111; border: 1px solid #333; width: 100%; max-width: 600px; margin: auto; }
        .form-control, .form-select { background-color: #222; border: 1px solid #444; color: white; }
    </style>
</head>
<body>
    <div class="card p-4 shadow-lg">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-primary">CREATE ACCOUNT</h4>
            <p class="text-secondary small">Enter your laboratory patient details</p>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf <!-- VERY IMPORTANT: Form won't work without this -->

            <!-- Error Display (Add this to see why it fails) -->
            @if ($errors->any())
                <div class="alert alert-danger text-danger small">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="mb-3">
                <label class="small text-secondary">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="small text-secondary">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="small text-secondary">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-secondary">Birthdate</label>
                    <input type="date" name="birthdate" value="{{ old('birthdate') }}" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="small text-secondary">Sex</label>
                <select name="sex" class="form-select" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="small text-secondary">Address</label>
                <textarea name="address" class="form-control" required>{{ old('address') }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="small text-secondary">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="small text-secondary">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">REGISTER</button>
        </form>
    </div>
</body>
</html>