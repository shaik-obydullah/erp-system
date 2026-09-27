<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'ERP Admin') }} - @yield('title', 'Roles')</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
        <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        @stack('head')
    </head>
    <body>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="layout">
            @include('layouts.sidebar')

            <main class="main">
                <header class="header">
                    <button class="menu-toggle" id="menuToggle">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <h1 class="page-title">@yield('title', 'Roles & Permissions')</h1>
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
                    @include('layouts.header-actions')
                </header>

                <div class="content">
                    @yield('content')
                </div>
            </main>
        </div>

        @include('layouts.header-actions-js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var sidebar = document.getElementById('sidebar');
                var overlay = document.getElementById('sidebarOverlay');
                var menuToggle = document.getElementById('menuToggle');
                var sidebarClose = document.getElementById('sidebarClose');

                function openSidebar() {
                    sidebar.classList.add('open');
                    overlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }

                function closeSidebar() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }

                if (menuToggle) menuToggle.addEventListener('click', openSidebar);
                if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
                if (overlay) overlay.addEventListener('click', closeSidebar);

                document.querySelectorAll('[data-select-all]').forEach(function(el) {
                    el.addEventListener('change', function() {
                        var group = this.dataset.group;
                        document.querySelectorAll('[data-group="' + group + '"]').forEach(function(cb) {
                            if (cb !== el) cb.checked = el.checked;
                        });
                    });
                });
            });
        </script>
        @stack('scripts')
    </body>
</html>
