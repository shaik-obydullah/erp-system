<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ERP') }} - Supplier Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-logo"><span class="logo-erp">ERP</span><span class="logo-admin">Supplier</span></h2>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('supplier-portal.dashboard') }}" class="nav-item {{ request()->routeIs('supplier-portal.dashboard') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('supplier-portal.orders') }}" class="nav-item {{ request()->routeIs('supplier-portal.orders') || request()->routeIs('supplier-portal.order') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Purchase Orders
                </a>
                <a href="{{ route('supplier-portal.products') }}" class="nav-item {{ request()->routeIs('supplier-portal.products') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    My Products
                </a>
                <a href="{{ route('supplier-portal.profile') }}" class="nav-item {{ request()->routeIs('supplier-portal.profile') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profile
                </a>
                <a href="{{ route('store.home') }}" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Back to Store
                </a>
            </nav>
        </aside>

        <main class="main">
            <header class="header">
                <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                @if(session('success') || session('error'))
                <div class="header-flash" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-error">{{ session('error') }}</div>
                    @endif
                </div>
                @endif
                <div class="header-actions">
                    <div class="user-menu">
                        <div class="user-avatar">{{ substr(Auth::guard('supplier')->user()->name, 0, 1) }}</div>
                        <span class="user-name">{{ Auth::guard('supplier')->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('supplier.logout') }}" style="margin-left: 12px;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline">Logout</button>
                    </form>
                </div>
            </header>

            <div class="content">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
