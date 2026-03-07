<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | LABSYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #000; color: white; font-family: 'Inter', sans-serif; }
        .hero { height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; }
        .btn-primary { background-color: #0d6efd; border: none; padding: 12px 30px; }
        .btn-outline-light { padding: 12px 30px; }
        .logo { font-weight: 800; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="hero">
        <div class="container">
            <h1 class="logo mb-3">LAB<span class="text-primary">SYSTEM</span></h1>
            <p class="lead text-secondary mb-5">Professional Laboratory Appointment & Management System</p>
            
            @if (Route::has('login'))
                <div>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary me-2">Login to Account</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-outline-light">Register Now</a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </div>
</body>
</html>