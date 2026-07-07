<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $portalSettings['school_name'] ?? 'FEU Cavite' }} - {{ $portalSettings['portal_name'] ?? 'Admin Portal' }}</title>
    @if(($portalImage)('favicon_path'))
        <link rel="icon" href="{{ ($portalImage)('favicon_path') }}">
    @endif

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
            @include('layouts.branding-logo', ['imageKey' => 'header_logo_path', 'text' => $portalSettings['portal_name'] ?? 'Admin Portal', 'class' => 'navbar-brand-lockup'])
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
            <div class="sidebar-brand">
                @include('layouts.branding-logo', ['imageKey' => 'sidebar_logo_path', 'text' => $portalSettings['portal_name'] ?? 'Admin Portal', 'class' => 'sidebar-brand-lockup'])
            </div>
            <div class="sidebar-label">Analytics & Reports</div>
            <a href="{{ route('admin.dashboard') }}"
                class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.sentiment') }}"
                class="menu-item {{ request()->routeIs('admin.sentiment') || request()->routeIs('admin.performance-feed.*') ? 'active' : '' }}">
                Performance Feed
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
            <details class="sidebar-dropdown {{ request()->routeIs('admin.settings') ? 'active' : '' }}" {{ request()->routeIs('admin.settings') ? 'open' : '' }}>
                <summary class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    Settings
                </summary>
                <div class="sidebar-submenu">
                    <a href="{{ route('admin.settings') }}#settings-branding">Branding</a>
                    <a href="{{ route('admin.settings') }}#settings-evaluation">Evaluation Controls</a>
                    <a href="{{ route('admin.settings') }}#settings-reports">Reports</a>
                    <a href="{{ route('admin.settings') }}#settings-pdf">PDF Configuration</a>
                    <a href="{{ route('admin.settings') }}#settings-performance">Performance Scale</a>
                    <a href="{{ route('admin.settings') }}#settings-student">Student Display</a>
                    <a href="{{ route('admin.settings') }}#settings-security">Security</a>
                    <a href="{{ route('admin.settings') }}#settings-profile">Admin Profile</a>
                </div>
            </details>
        </nav>

        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('js/shared/core.js') }}"></script>
    @stack('scripts') 
</body>

</html>
