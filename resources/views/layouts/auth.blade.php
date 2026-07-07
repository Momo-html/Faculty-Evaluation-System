<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $portalSettings['portal_name'] ?? 'FEU Evaluation' }} - @yield('role')</title>
    @if(($portalImage)('favicon_path'))
        <link rel="icon" href="{{ ($portalImage)('favicon_path') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/auth/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/polish.css') }}">
</head>

<body class="auth-shell">

    <div class="navbar">
        <div class="logo-section" style="display: flex; align-items: center;">
            @include('layouts.branding-logo', ['imageKey' => 'login_logo_path', 'text' => $portalSettings['portal_name'] ?? 'Evaluation', 'class' => 'navbar-brand-lockup'])
        </div>
    </div>

    <div class="container active">
        <div class="card">
            <h2>@yield('role') Login</h2>
            @yield('content')
        </div>
    </div>

</body>

</html>
