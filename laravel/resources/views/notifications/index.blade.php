<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ERP Admin') }} - Notifications</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/css/app.css?v={{ md5_file(public_path('css/app.css')) }}">
    <script src="/js/app.js?v={{ md5_file(public_path('js/app.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --primary: #1a73e8; --primary-hover: #1557b0;
            --error: #d93025; --text-primary: #202124; --text-secondary: #5f6368;
            --border: #dadce0; --bg-white: #ffffff; --bg-gray: #f8f9fa; --transition: 0.2s ease;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Figtree', Roboto, Arial, sans-serif; color: var(--text-primary); background: var(--bg-gray); line-height: 1.5; margin: 0; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--bg-white); border-right: 1px solid var(--border); position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .sidebar-logo { font-size: 28px; font-weight: 500; letter-spacing: -0.5px; margin: 0; }
        .logo-erp { color: var(--text-secondary); } .logo-admin { color: var(--primary); }
        .sidebar-nav { padding: 16px 12px; flex: 1; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; color: var(--text-secondary); font-size: 14px; font-weight: 500; transition: all var(--transition); text-decoration: none; margin-bottom: 4px; }
        .nav-item:hover { background: var(--bg-gray); color: var(--text-primary); text-decoration: none; }
        .nav-dropdown { position: relative; }
        .nav-dropdown-toggle { width: 100%; display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; color: var(--text-secondary); font-size: 14px; font-weight: 500; transition: all var(--transition); border: none; background: none; cursor: pointer; text-align: left; font-family: inherit; margin-bottom: 4px; }
        .nav-dropdown-toggle:hover { background: var(--bg-gray); color: var(--text-primary); }
        .nav-dropdown-toggle .dropdown-chevron { margin-left: auto; transition: transform var(--transition); }
        .nav-dropdown.open .nav-dropdown-toggle .dropdown-chevron { transform: rotate(180deg); }
        .nav-dropdown-menu { display: none; padding: 0 8px; }
        .nav-dropdown.open .nav-dropdown-menu { display: block; }
        .nav-dropdown-item { display: block; padding: 8px 16px 8px 48px; border-radius: 6px; color: var(--text-secondary); font-size: 13px; font-weight: 500; text-decoration: none; transition: all var(--transition); }
        .nav-dropdown-item:hover { background: var(--bg-gray); color: var(--text-primary); text-decoration: none; }
        .nav-dropdown-item.active { background: #e8f0fe; color: var(--primary); }
        .main { flex: 1; margin-left: 260px; }
        .header { background: var(--bg-white); border-bottom: 1px solid var(--border); padding: 16px 32px; display: flex; align-items: center; gap: 16px; position: sticky; top: 0; z-index: 50; }
        .page-title { font-size: 22px; font-weight: 500; color: var(--text-primary); flex: 1; margin: 0; }
        .user-menu { position: relative; display: flex; align-items: center; gap: 10px; padding: 6px 12px; border-radius: 20px; cursor: pointer; }
        .user-menu:hover { background: var(--bg-gray); }
        .user-avatar { width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; }
        .user-name { font-size: 14px; font-weight: 500; }
        .content { padding: 32px; }
        .header-flash { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .header-flash .alert { margin-bottom: 0; padding: 6px 14px; font-size: 13px; border-radius: 20px; white-space: nowrap; display: flex; align-items: center; gap: 8px; }

        .stats-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon.blue { background: #e8f0fe; color: #1a73e8; }
        .stat-icon.red { background: #fce8e6; color: #d93025; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-label { font-size: 12px; color: #5f6368; margin-bottom: 2px; }
        .stat-value { font-size: 24px; font-weight: 600; color: #202124; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card-header { padding: 20px 24px; border-bottom: 1px solid #dadce0; }
        .card-title { font-size: 16px; font-weight: 500; margin: 0 0 16px; }

        .toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-input, .filter-select { padding: 8px 12px; border: 1px solid #dadce0; border-radius: 6px; font-size: 13px; outline: none; }
        .filter-input:focus, .filter-select:focus { border-color: #1a73e8; }
        .filter-input { min-width: 200px; }

        .loading { display: flex; justify-content: center; padding: 48px; }
        .spinner { width: 24px; height: 24px; border: 3px solid #dadce0; border-top-color: #1a73e8; border-radius: 50%; animation: spin 0.6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .empty-state { text-align: center; padding: 48px; color: #5f6368; }

        .notif-item { display: flex; align-items: flex-start; gap: 14px; padding: 16px 24px; border-bottom: 1px solid #f1f3f4; position: relative; transition: background 0.2s; }
        .notif-item:hover { background: #f8f9fa; }
        .notif-item.unread { background: #f0f6ff; }
        .notif-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
        .icon-info { background: #e8f0fe; color: #1a73e8; }
        .icon-success { background: #e6f4ea; color: #1e8e3e; }
        .icon-warning { background: #fef7e0; color: #e37400; }
        .icon-error { background: #fce8e6; color: #d93025; }

        .notif-body { flex: 1; min-width: 0; }
        .notif-header { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
        .notif-header strong { font-size: 14px; }
        .notif-time { font-size: 12px; color: #9aa0a6; margin-left: auto; flex-shrink: 0; }
        .notif-text { font-size: 13px; color: #5f6368; margin: 0 0 6px; line-height: 1.4; }
        .notif-actions { display: flex; gap: 4px; }
        .unread-dot { width: 8px; height: 8px; border-radius: 50%; background: #1a73e8; margin-top: 6px; flex-shrink: 0; }

        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-secondary { background: #fff; color: #202124; border: 1px solid #dadce0; }
        .btn-secondary:hover { background: #f8f9fa; }
        .btn-ghost { background: transparent; color: #1a73e8; padding: 4px 10px; }
        .btn-ghost:hover { background: #e8f0fe; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn-delete { color: #d93025; }
        .btn-delete:hover { background: #fce8e6; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .pagination { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; border-top: 1px solid #dadce0; }
        .page-info { font-size: 13px; color: #5f6368; }

        .toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; z-index: 300; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .toast-success { background: #1e8e3e; color: #fff; }
        .toast-error { background: #d93025; color: #fff; }

        @media (max-width: 768px) {
            .sidebar { display: none; } .main { margin-left: 0; } .content { padding: 16px; }
            .stats-row { grid-template-columns: 1fr; }
            .toolbar { flex-direction: column; }
            .filter-input { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="layout" x-data="notificationManager()">
        @include('layouts.sidebar')

        <main class="main">
            <header class="header">
                <button class="menu-toggle" id="menuToggle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="page-title">Notifications</h1>
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
                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total</span>
                            <span class="stat-value" x-text="notifications.total || 0"></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Unread</span>
                            <span class="stat-value" x-text="unreadCount"></span>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Notifications</h2>
                        <div class="toolbar">
                            <input x-model="filters.search" type="text" placeholder="Search..." class="filter-input" @input.debounce.300ms="fetchNotifications()">
                            <select x-model="filters.view_status" class="filter-select" @change="fetchNotifications()">
                                <option value="">All</option>
                                <option value="unseen">Unread</option>
                                <option value="seen">Read</option>
                            </select>
                            <select x-model="filters.type" class="filter-select" @change="fetchNotifications()">
                                <option value="">All Types</option>
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                            </select>
                            <button class="btn btn-secondary" @click="markAllSeen()">Mark All Read</button>
                        </div>
                    </div>

                    <div x-show="loading" class="loading"><div class="spinner"></div></div>

                    <div x-show="!loading">
                        <div x-show="!notifications.data || notifications.data.length === 0" class="empty-state">No notifications.</div>

                        <template x-for="n in notifications.data || []" :key="n.id">
                            <div class="notif-item" :class="{ 'unread': n.view_status === 'unseen' }">
                                <div class="notif-icon" :class="'icon-' + n.type">
                                    <template x-if="n.type === 'info'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                    </template>
                                    <template x-if="n.type === 'success'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    </template>
                                    <template x-if="n.type === 'warning'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    </template>
                                    <template x-if="n.type === 'error'">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                    </template>
                                </div>
                                <div class="notif-body">
                                    <div class="notif-header">
                                        <strong x-text="n.title"></strong>
                                        <span class="notif-time" x-text="formatTime(n.created_at)"></span>
                                    </div>
                                    <p class="notif-text" x-text="n.notification"></p>
                                    <div class="notif-actions">
                                        <a x-show="n.link" :href="n.link" class="btn btn-ghost btn-sm" @click="markSeen(n.id)">View</a>
                                        <button x-show="n.view_status === 'unseen'" class="btn btn-ghost btn-sm" @click="markSeen(n.id)">Mark Read</button>
                                        <button class="btn btn-ghost btn-sm btn-delete" @click="deleteNotif(n.id)">Delete</button>
                                    </div>
                                </div>
                                <div x-show="n.view_status === 'unseen'" class="unread-dot"></div>
                            </div>
                        </template>
                    </div>

                    <div x-show="notifications.last_page > 1" class="pagination">
                        <button class="btn btn-secondary btn-sm" :disabled="notifications.current_page === 1" @click="fetchNotifications(notifications.current_page - 1)">Previous</button>
                        <span class="page-info">Page <span x-text="notifications.current_page"></span> of <span x-text="notifications.last_page"></span></span>
                        <button class="btn btn-secondary btn-sm" :disabled="notifications.current_page === notifications.last_page" @click="fetchNotifications(notifications.current_page + 1)">Next</button>
                    </div>
                </div>

                <!-- Toast -->
                <div x-show="toast.show" x-transition x-cloak class="toast" :class="'toast-' + toast.type" x-text="toast.message"></div>
            </div>
        </main>
    </div>

    @include('layouts.header-actions-js')
    <script>
    function notificationManager() {
        return {
            notifications: {},
            unreadCount: 0,
            loading: true,
            filters: { search: '', view_status: '', type: '' },
            toast: { show: false, message: '', type: 'success' },

            init() {
                this.fetchNotifications();
                this.fetchUnreadCount();
            },

            async fetchNotifications(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    params.set('page', page);
                    params.set('per_page', 20);
                    if (this.filters.search) params.set('search', this.filters.search);
                    if (this.filters.view_status) params.set('view_status', this.filters.view_status);
                    if (this.filters.type) params.set('type', this.filters.type);

                    const res = await fetch('/api/notifications?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                    this.notifications = await res.json();
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },

            async fetchUnreadCount() {
                try {
                    const res = await fetch('/api/notifications/unread-count', { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.unreadCount = data.count;
                } catch (e) {
                    console.error(e);
                }
            },

            async markSeen(id) {
                try {
                    await fetch('/api/notifications/' + id + '/seen', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    this.fetchNotifications();
                    this.fetchUnreadCount();
                } catch (e) { console.error(e); }
            },

            async markAllSeen() {
                try {
                    await fetch('/api/notifications/mark-all-seen', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    this.showToast('All marked as read', 'success');
                    this.fetchNotifications();
                    this.fetchUnreadCount();
                } catch (e) { console.error(e); }
            },

            async deleteNotif(id) {
                try {
                    await fetch('/api/notifications/' + id, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    this.showToast('Notification deleted', 'success');
                    this.fetchNotifications();
                    this.fetchUnreadCount();
                } catch (e) { console.error(e); }
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 3000);
            },

            formatTime(date) {
                if (!date) return '';
                const d = new Date(date);
                const now = new Date();
                const diff = Math.floor((now - d) / 1000);
                if (diff < 60) return 'Just now';
                if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
                if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            },
        };
    }
    </script>
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
        });
    </script>
</body>
</html>
