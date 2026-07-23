<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'ERP') }} - Supplier Portal</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
        <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body>
        <div class="layout">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h2 class="sidebar-logo"><span class="logo-erp">ERP</span><span class="logo-admin">Supplier</span></h2>
                </div>
                <nav class="sidebar-nav">
                    <a href="{{ route('supplier.dashboard') }}" class="nav-item {{ request()->routeIs('supplier.dashboard') ? 'active' : '' }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Dashboard
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
