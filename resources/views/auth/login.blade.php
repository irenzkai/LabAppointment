<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #000; height: 100vh; display: flex; align-items: center; }
        .card { background-color: #111; border: 1px solid #333; width: 100%; max-width: 400px; margin: auto; }
        .form-control { background-color: #222; border: 1px solid #444; color: white; }
    </style>
</head>
<body>
    <div class="card p-4 shadow-lg">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-primary">LABSYSTEM</h4>
            <p class="text-secondary small">Sign in to your account</p>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small text-secondary">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small text-secondary">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label small" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Login</button>
            
            <div class="text-center mt-3">
                <a href="{{ route('register') }}" class="small text-secondary text-decoration-none">Don't have an account? Register</a>
            </div>
        </form>
    </div>
</body>
</html>