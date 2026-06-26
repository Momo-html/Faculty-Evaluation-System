<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEU Evaluation - @yield('role')</title>
    <link rel="stylesheet" href="{{ asset('css/auth/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/polish.css') }}">
</head>

<body class="auth-shell">

    <div class="navbar">
        <div class="logo-section" style="display: flex; align-items: center;">
            <div class="logo-badge">FEU</div>
            <span class="logo-text"> EVALUATION</span>
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
