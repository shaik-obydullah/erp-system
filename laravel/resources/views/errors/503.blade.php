<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Maintenance - {{ config('app.name', 'ERP Admin') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
        @php
            $maintenanceMessage = \App\Models\Configuration::get('maintenance_message', 'We are currently performing scheduled maintenance. Please try again later.');
        @endphp
        <style>
            .maintenance-page {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--bg-gray, #f8f9fa);
                padding: 20px;
            }
            .maintenance-card {
                width: 100%;
                max-width: 500px;
                background: var(--bg-white, #fff);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08);
                padding: 48px 40px;
                text-align: center;
            }
            .maintenance-icon {
                width: 80px;
                height: 80px;
                background: #fef7e0;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
            }
            .maintenance-icon svg {
                color: #e37400;
            }
            .maintenance-title {
                font-size: 24px;
                font-weight: 500;
                color: var(--text-primary, #202124);
                margin: 0 0 12px;
            }
            .maintenance-message {
                font-size: 16px;
                color: var(--text-secondary, #5f6368);
                line-height: 1.6;
                margin: 0 0 32px;
            }
            .maintenance-footer {
                font-size: 13px;
                color: var(--text-disabled, #9aa0a6);
            }
            .maintenance-logo {
                font-size: 28px;
                font-weight: 500;
                letter-spacing: -0.5px;
                margin-bottom: 32px;
            }
            .logo-erp { color: var(--text-secondary, #5f6368); }
            .logo-admin { color: var(--primary, #1a73e8); }
        </style>
    </head>
    <body>
        <div class="maintenance-page">
            <div class="maintenance-card">
                <div class="maintenance-logo">
                    <span class="logo-erp">ERP</span><span class="logo-admin">Admin</span>
                </div>
                <div class="maintenance-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </div>
                <h1 class="maintenance-title">Under Maintenance</h1>
                <p class="maintenance-message">
                    {{ $maintenanceMessage }}
                </p>
                <p class="maintenance-footer">
                    We appreciate your patience.
                </p>
            </div>
        </div>
    </body>
</html>
