<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $portalSettings['portal_name'] ?? 'FEU Portal' }} | SuperAdmin - @yield('title')</title>
    @if(($portalImage)('favicon_path'))
        <link rel="icon" href="{{ ($portalImage)('favicon_path') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/superadmin/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/polish.css') }}">
</head>
<body class="superadmin-shell">

    <div class="navbar">
        <div class="logo-section" style="display: flex; align-items: center;">
            @include('layouts.branding-logo', ['imageKey' => 'header_logo_path', 'text' => $portalSettings['portal_name'] ?? 'Admin Portal', 'class' => 'navbar-brand-lockup'])
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
