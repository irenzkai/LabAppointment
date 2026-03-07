<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LabSystem | Laboratory Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #000000; color: #e0e0e0; }
        .card { background-color: #121212; border: 1px solid #333; }
        .navbar { background-color: #000000 !important; border-bottom: 1px solid #333; }
        .table { --bs-table-bg: #121212; --bs-table-color: #e0e0e0; border-color: #333; }
        .btn-primary { background-color: #0d6efd; border: none; }
        .modal-content { background-color: #121212; color: white; border: 1px solid #333; }
        .form-control, .form-select { background-color: #1b1b1b; border: 1px solid #444; color: white; }
        .form-control:focus { background-color: #252525; color: white; border-color: #0d6efd; box-shadow: none; }
        .bg-white { background-color: #111 !important; }
        .text-gray-900 { color: #fff !important; }
        .text-gray-600 { color: #aaa !important; }
        .shadow { box-shadow: 0 .5rem 1rem rgba(0,0,0,.5)!important; }
        hr { border-color: #333; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-white" href="{{ route('dashboard') }}">LAB<span class="text-primary">SYSTEM</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('services.index') }}">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('appointments.index') }}">Appointments</a></li>
                    @can('isAdmin')
                        <li class="nav-item"><a class="nav-link text-info" href="{{ url('/admin/users') }}">Manage Accounts</a></li>
                    @endcan
                </ul>
                <div class="dropdown">
                    <button class="btn btn-dark dropdown-toggle" data-bs-toggle="dropdown">
                        {{ Auth::user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Account Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger">Sign Out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        @if(session('success'))
            <div class="alert alert-primary bg-primary text-white border-0">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>