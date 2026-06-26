<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Student Portal | FEU Evaluation')</title>
    <link rel="stylesheet" href="{{ asset('css/user/base.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/shared/polish.css') }}">
</head>

<body class="user-shell">
    <header class="navbar">
        <div class="logo-section" style="display: flex; align-items: center;">
            <div class="logo-badge">FEU</div>
            <span class="logo-text"> STUDENT PORTAL</span>
        </div>

        <div class="header-right">
            <div class="user-profile-trigger" onclick="toggleDropdown()">
                <div class="user-meta">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>

                <div id="user-dropdown" class="profile-dropdown">
                    <hr>
                    <form action="{{ route('user.logout') }}" method="POST">
                        @csrf
                        <button type="submit" onclick="clearPrivacySession()"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        @if(session('success'))
            <div class="alert-success">
                <strong>Success!</strong> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-danger">
                <strong>Error!</strong> {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </div>

    <script src="{{ asset('js/user/layout.js') }}"></script>
    @stack('scripts')
</body>

</html>
