<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>FEU Cavite - Master Unified Portal</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/admin/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/polish.css') }}">
    @stack('styles')
</head>

<body class="admin-shell">

    <header class="navbar">
        <div class="logo-section" style="display: flex; align-items: center;">
            <button class="nav-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false"
                data-sidebar-toggle>
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="logo-badge">FEU</div>
            <span class="logo-text"> ADMIN PORTAL</span>
        </div>
        <div class="header-right">
            <div class="user-profile-trigger" onclick="toggleDropdown()">
                <div class="user-meta">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>

                <div id="user-dropdown" class="profile-dropdown">
                    <form action="{{ route('user.logout') }}" method="POST">
                        @csrf
                        <button type="submit" onclick="clearPrivacySession()"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="wrapper">
        <div class="sidebar-scrim" data-sidebar-close></div>
        <nav class="sidebar" id="admin-sidebar">
            <div class="sidebar-label">Analytics & Reports</div>
            <a href="{{ route('admin.dashboard') }}"
                class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.sentiment') }}"
                class="menu-item {{ request()->routeIs('admin.sentiment') ? 'active' : '' }}">
                Sentiment Feed
            </a>

            <div class="sidebar-label">Academic Management</div>
            <a href="{{ route('admin.faculty') }}"
                class="menu-item {{ request()->routeIs('admin.faculty') ? 'active' : '' }}">
                Faculty Directory
            </a>
            <a href="{{ route('admin.students') }}"
                class="menu-item {{ request()->routeIs('admin.students') ? 'active' : '' }}">
                Student Directory
            </a>
            <a href="{{ route('admin.mapping') }}"
                class="menu-item {{ request()->routeIs('admin.mapping') ? 'active' : '' }}">
                Faculty-Course Mapping
            </a>

            <div class="sidebar-label">Evaluation Engine</div>
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.forms') }}"
                    class="menu-item {{ request()->routeIs('admin.forms') ? 'active' : '' }}">
                    Form Builder
                </a>
            @endif

            <div class="sidebar-label">System Control</div>
            <a href="{{ route('admin.security') }}"
                class="menu-item {{ request()->routeIs('admin.security') ? 'active' : '' }}">
                Security Logs
            </a>
            <a href="{{ route('admin.settings') }}"
                class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                Settings
            </a>
        </nav>

        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('js/shared/core.js') }}"></script>
    @stack('scripts') 
</body>

</html>
