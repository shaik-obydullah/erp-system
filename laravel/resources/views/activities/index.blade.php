<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ERP Admin') }} - Activity Manager</title>
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
        .nav-item.active { background: #e8f0fe; color: var(--primary); }
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

        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon.blue { background: #e8f0fe; color: #1a73e8; }
        .stat-icon.green { background: #e6f4ea; color: #1e8e3e; }
        .stat-icon.purple { background: #f3e8fd; color: #9334e6; }
        .stat-icon.orange { background: #fef7e0; color: #e37400; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-label { font-size: 12px; color: #5f6368; margin-bottom: 2px; }
        .stat-value { font-size: 24px; font-weight: 600; color: #202124; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid #dadce0; }
        .card-title { font-size: 16px; font-weight: 500; margin: 0 0 16px; }

        .filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-input, .filter-select { padding: 8px 12px; border: 1px solid #dadce0; border-radius: 6px; font-size: 13px; color: #202124; outline: none; }
        .filter-input:focus, .filter-select:focus { border-color: #1a73e8; }
        .filter-input { min-width: 180px; }
        .filter-select { min-width: 140px; }

        .loading { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 48px; color: #5f6368; }
        .spinner { width: 24px; height: 24px; border: 3px solid #dadce0; border-top-color: #1a73e8; border-radius: 50%; animation: spin 0.6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #5f6368; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #dadce0; background: #f8f9fa; }
        td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #f1f3f4; }
        tr:hover { background: #f8f9fa; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: capitalize; }
        .badge-success { background: #e6f4ea; color: #1e8e3e; }
        .badge-warning { background: #fef7e0; color: #e37400; }
        .badge-error { background: #fce8e6; color: #d93025; }

        .text-muted { color: #9aa0a6; font-size: 12px; }
        code { font-size: 12px; background: #f1f3f4; padding: 2px 6px; border-radius: 4px; }

        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-primary { background: #1a73e8; color: #fff; }
        .btn-primary:hover { background: #1557b0; }
        .btn-secondary { background: #fff; color: #202124; border: 1px solid #dadce0; }
        .btn-secondary:hover { background: #f8f9fa; }
        .btn-danger { background: #d93025; color: #fff; }
        .btn-danger:hover { background: #c5221f; }
        .btn-ghost { background: transparent; color: #1a73e8; padding: 4px 10px; }
        .btn-ghost:hover { background: #e8f0fe; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .pagination { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; border-top: 1px solid #dadce0; }
        .page-info { font-size: 13px; color: #5f6368; }

        .empty-state { text-align: center; padding: 48px; color: #5f6368; }

        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 200; }
        .modal { background: #fff; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow: hidden; }
        .modal-header { padding: 16px 24px; border-bottom: 1px solid #dadce0; display: flex; align-items: center; justify-content: space-between; }
        .modal-header h3 { margin: 0; font-size: 16px; font-weight: 500; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #5f6368; padding: 0 4px; }
        .modal-body { padding: 24px; overflow-y: auto; max-height: 60vh; }
        .diff-section { margin-bottom: 16px; }
        .diff-section h4 { font-size: 13px; font-weight: 600; margin: 0 0 8px; color: #5f6368; }
        .diff-old, .diff-new { font-size: 12px; padding: 12px; border-radius: 6px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
        .diff-old { background: #fce8e6; border: 1px solid #f5c6cb; }
        .diff-new { background: #e6f4ea; border: 1px solid #ceead6; }

        @media (max-width: 1024px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; } .sidebar.open { transform: translateX(0); } .sidebar-close { display: block; } .main { margin-left: 0; } .content { padding: 16px; } .menu-toggle { display: block; }
            .stats-row { grid-template-columns: 1fr; }
            .filters { flex-direction: column; }
            .filter-input, .filter-select { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="layout" x-data="activityManager()">
        @include('layouts.sidebar')

        <main class="main">
            <header class="header">
                <button class="menu-toggle" id="menuToggle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="page-title">Activity Manager</h1>
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
                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Activities</span>
                            <span class="stat-value" x-text="stats.total || 0"></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Today</span>
                            <span class="stat-value" x-text="stats.today || 0"></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Logins Today</span>
                            <span class="stat-value" x-text="stats.logins || 0"></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Errors Today</span>
                            <span class="stat-value" x-text="stats.errors || 0"></span>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Activity Log</h2>
                        <div class="filters">
                            <input x-model="filters.search" type="text" placeholder="Search activities..." class="filter-input" @input.debounce.300ms="fetchActivities()">
                            <select x-model="filters.type" class="filter-select" @change="fetchActivities()">
                                <option value="">All Types</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                            </select>
                            <select x-model="filters.admin_id" class="filter-select" @change="fetchActivities()">
                                <option value="">All Admins</option>
                                <template x-for="admin in stats.admins || []" :key="admin.id">
                                    <option :value="admin.id" x-text="admin.first_name + ' ' + admin.last_name"></option>
                                </template>
                            </select>
                            <input x-model="filters.from" type="date" class="filter-input" @change="fetchActivities()">
                            <input x-model="filters.to" type="date" class="filter-input" @change="fetchActivities()">
                            <button class="btn btn-secondary" @click="resetFilters()">Reset</button>
                            <button class="btn btn-danger" @click="clearActivities()">Clear Old</button>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div x-show="loading" class="loading">
                        <div class="spinner"></div>
                        <span>Loading activities...</span>
                    </div>

                    <!-- Table -->
                    <table x-show="!loading">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 100px;">Type</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Admin</th>
                                <th>IP Address</th>
                                <th style="width: 160px;">Date & Time</th>
                                <th style="width: 80px;">Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr x-show="!activities.data || activities.data.length === 0">
                                <td colspan="8" class="empty-state">No activities found.</td>
                            </tr>
                            <template x-for="activity in activities.data || []" :key="activity.id">
                                <tr>
                                    <td x-text="activity.id"></td>
                                    <td>
                                        <span class="badge" :class="'badge-' + activity.type" x-text="activity.type"></span>
                                    </td>
                                    <td><strong x-text="activity.name"></strong></td>
                                    <td x-text="activity.description || '-'"></td>
                                    <td x-text="activity.admin ? activity.admin.first_name + ' ' + activity.admin.last_name : 'System'"></td>
                                    <td><code x-text="activity.ip_address"></code></td>
                                    <td x-text="formatDate(activity.created_at)"></td>
                                    <td>
                                        <button
                                            x-show="activity.old_data || activity.new_data"
                                            class="btn btn-ghost btn-sm"
                                            @click="showChanges(activity)"
                                        >View</button>
                                        <span x-show="!activity.old_data && !activity.new_data" class="text-muted">-</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div x-show="activities.last_page > 1" class="pagination">
                        <button class="btn btn-secondary btn-sm" :disabled="activities.current_page === 1" @click="fetchActivities(activities.current_page - 1)">Previous</button>
                        <span class="page-info">Page <span x-text="activities.current_page"></span> of <span x-text="activities.last_page"></span> (<span x-text="activities.total"></span> total)</span>
                        <button class="btn btn-secondary btn-sm" :disabled="activities.current_page === activities.last_page" @click="fetchActivities(activities.current_page + 1)">Next</button>
                    </div>
                </div>

                <!-- Changes Modal -->
                <div x-show="modal.show" x-cloak class="modal-overlay" @click.self="modal.show = false">
                    <div class="modal" @click.stop>
                        <div class="modal-header">
                            <h3 x-text="(modal.activity?.name || '') + ' — Changes'"></h3>
                            <button class="modal-close" @click="modal.show = false">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div x-show="modal.activity?.old_data" class="diff-section">
                                <h4>Old Values</h4>
                                <pre class="diff-old" x-text="JSON.stringify(modal.activity?.old_data, null, 2)"></pre>
                            </div>
                            <div x-show="modal.activity?.new_data" class="diff-section">
                                <h4>New Values</h4>
                                <pre class="diff-new" x-text="JSON.stringify(modal.activity?.new_data, null, 2)"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    @include('layouts.header-actions-js')
    <script>
    function activityManager() {
        return {
            activities: {},
            stats: {},
            loading: true,
            filters: { search: '', type: '', admin_id: '', from: '', to: '' },
            modal: { show: false, activity: null },

            init() {
                this.fetchStats();
                this.fetchActivities();
            },

            async fetchStats() {
                try {
                    const res = await fetch('/api/activities/stats', { headers: { 'Accept': 'application/json' } });
                    this.stats = await res.json();
                } catch (e) {
                    console.error('Failed to load stats', e);
                }
            },

            async fetchActivities(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    params.set('page', page);
                    params.set('per_page', 25);
                    if (this.filters.search) params.set('search', this.filters.search);
                    if (this.filters.type) params.set('type', this.filters.type);
                    if (this.filters.admin_id) params.set('admin_id', this.filters.admin_id);
                    if (this.filters.from) params.set('from', this.filters.from);
                    if (this.filters.to) params.set('to', this.filters.to);

                    const res = await fetch('/api/activities?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                    this.activities = await res.json();
                } catch (e) {
                    console.error('Failed to load activities', e);
                } finally {
                    this.loading = false;
                }
            },

            resetFilters() {
                this.filters = { search: '', type: '', admin_id: '', from: '', to: '' };
                this.fetchActivities();
            },

            async clearActivities() {
                if (!confirm('Clear all old activities? This cannot be undone.')) return;
                try {
                    await fetch('/api/activities/clear', {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    this.fetchActivities();
                    this.fetchStats();
                } catch (e) {
                    console.error('Failed to clear', e);
                }
            },

            showChanges(activity) {
                this.modal = { show: true, activity };
            },

            formatDate(date) {
                if (!date) return '-';
                return new Date(date).toLocaleString('en-US', {
                    year: 'numeric', month: 'short', day: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                });
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
