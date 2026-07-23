<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ERP Admin') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
        <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body>
        <div class="login-container">
            <div class="login-card">
                <div class="login-logo">
                    <h2 class="logo-text">
                        <span class="logo-erp">ERP</span><span class="logo-admin">Admin</span>
                    </h2>
                </div>
                {{ $slot }}
            </div>
            <div class="login-footer">
                <p>ERP Business Intelligence System</p>
            </div>
        </div>
    </body>
</html>
