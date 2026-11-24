<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    @vite(['resources/css/mainlayout.css', 'resources/js/mainlayout.js'])
    @stack('styles')
</head>
<body class="app-shell">
    @include('alerts.notify')
    <div class="app-layout">
        <aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
            <div class="sidebar-brand">
                <a class="brand-link" href="{{ route('dashboard') }}" id="appSidebarLabel">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 8.5h14l-1.2 8.1a2 2 0 0 1-2 1.7H8.2a2 2 0 0 1-2-1.7L5 8.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M8 8.5 10 5m6 3.5L14 5M8.5 12h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>
                        <strong>Foodo<span>Costing</span></strong>
                        <small>Kitchen management</small>
                    </span>
                </a>
                <button class="btn-close d-lg-none" type="button" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close menu"></button>
            </div>

            <nav class="sidebar-nav" aria-label="Main navigation">
                <div class="nav-caption">OVERVIEW</div>
                <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                        <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                        <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                        <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <div class="nav-caption nav-caption-spaced">COST MANAGEMENT</div>
                <a class="sidebar-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7.5h16M6.5 4.5h11A1.5 1.5 0 0 1 19 6v14H5V6a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="M8 11h8m-8 3.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <span>Inventory</span>
                </a>
                <a class="sidebar-link {{ request()->routeIs('cost-analysis.index', 'cost-analysis.calculate', 'cost-analysis.items.*') ? 'active' : '' }}" href="{{ route('cost-analysis.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3.5v17m5-13H9.5a3 3 0 0 0 0 6H14a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <span>Sales Calculator</span>
                </a>
                <a class="sidebar-link {{ request()->routeIs('cost-analysis.reports.*') ? 'active' : '' }}" href="{{ route('cost-analysis.reports.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M7 3.5h7l4 4v13H7a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="M14 3.5v5h5M8.5 12h7m-7 3.5h7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Saved Reports</span>
                </a>

                <div class="nav-caption nav-caption-spaced">ACCOUNT</div>
                @auth
                    <a class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        <span>My profile</span>
                    </a>
                @endauth
            </nav>

            <div class="sidebar-footer">
                @auth
                    <div class="user-card">
                        <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="user-details">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->email }}</small>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="sidebar-link logout-link" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M10 4.5H6.5A1.5 1.5 0 0 0 5 6v12a1.5 1.5 0 0 0 1.5 1.5H10m4-3 4-4-4-4m4 4H9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Sign out</span>
                        </button>
                    </form>
                @else
                    <a class="sidebar-link" href="{{ route('login') }}">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 4.5H6.5A1.5 1.5 0 0 0 5 6v12a1.5 1.5 0 0 0 1.5 1.5H10m4-3 4-4-4-4m4 4H9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Sign in</span>
                    </a>
                @endauth
                <div class="sidebar-version">FOODO COSTING <span>·</span> v1.0</div>
            </div>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Open navigation">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
                <div class="page-heading">
                    <span class="page-eyebrow">FOODO COSTING</span>
                    <h1>@yield('page-title', 'Dashboard')</h1>
                </div>
                @auth
                    <div class="dropdown topbar-account">
                        <button class="topbar-user" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                aria-label="Account menu for {{ auth()->user()->name }}">
                            <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="topbar-user-name">{{ auth()->user()->name }}</span>
                            <svg class="topbar-user-chevron" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end topbar-account-menu">
                            <div class="topbar-account-summary">
                                <strong>{{ auth()->user()->name }}</strong>
                                <small>{{ auth()->user()->email }}</small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                                My Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item" type="submit">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                        <path d="M10 4.5H6.5A1.5 1.5 0 0 0 5 6v12a1.5 1.5 0 0 0 1.5 1.5H10m4-3 4-4-4-4m4 4H9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </header>

            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>