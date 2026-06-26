<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEU Portal | SuperAdmin - @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/superadmin/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/polish.css') }}">
</head>
<body class="superadmin-shell">

    <div class="navbar">
        <div class="logo-section" style="display: flex; align-items: center;">
            <div class="logo-badge">FEU</div>
            <span class="logo-text"> ADMIN PORTAL</span>
        </div>
        
        <div class="nav-actions">
            <form action="{{ route('superadmin.logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn-primary" style="background: transparent; border: 1px solid white; padding: 5px 15px;">
                    Logout
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
