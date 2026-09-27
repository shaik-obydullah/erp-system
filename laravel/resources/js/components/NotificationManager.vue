<template>
    <div class="notification-manager">
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total</span>
                    <span class="stat-value">{{ notifications.total || 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Unread</span>
                    <span class="stat-value">{{ unreadCount }}</span>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Notifications</h2>
                <div class="toolbar">
                    <input v-model="filters.search" type="text" placeholder="Search..." class="filter-input" @input="debouncedFetch" />
                    <select v-model="filters.view_status" class="filter-select" @change="fetchNotifications">
                        <option value="">All</option>
                        <option value="unseen">Unread</option>
                        <option value="seen">Read</option>
                    </select>
                    <select v-model="filters.type" class="filter-select" @change="fetchNotifications">
                        <option value="">All Types</option>
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                    <button class="btn btn-secondary" @click="markAllSeen">Mark All Read</button>
                </div>
            </div>

            <div v-if="loading" class="loading"><div class="spinner"></div></div>

            <div v-else>
                <div v-if="!notifications.data || notifications.data.length === 0" class="empty-state">No notifications.</div>

                <div v-for="n in notifications.data" :key="n.id" class="notif-item" :class="{ unread: n.view_status === 'unseen' }">
                    <div class="notif-icon" :class="'icon-' + n.type">
                        <svg v-if="n.type === 'info'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <svg v-else-if="n.type === 'success'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <svg v-else-if="n.type === 'warning'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <div class="notif-body">
                        <div class="notif-header">
                            <strong>{{ n.title }}</strong>
                            <span class="notif-time">{{ formatTime(n.created_at) }}</span>
                        </div>
                        <p class="notif-text">{{ n.notification }}</p>
                        <div class="notif-actions">
                            <a v-if="n.link" :href="n.link" class="btn btn-ghost btn-sm" @click="markSeen(n.id)">View</a>
                            <button v-if="n.view_status === 'unseen'" class="btn btn-ghost btn-sm" @click="markSeen(n.id)">Mark Read</button>
                            <button class="btn btn-ghost btn-sm btn-delete" @click="deleteNotif(n.id)">Delete</button>
                        </div>
                    </div>
                    <div v-if="n.view_status === 'unseen'" class="unread-dot"></div>
                </div>
            </div>

            <div v-if="notifications.last_page > 1" class="pagination">
                <button class="btn btn-secondary btn-sm" :disabled="notifications.current_page === 1" @click="fetchNotifications(notifications.current_page - 1)">Previous</button>
                <span class="page-info">Page {{ notifications.current_page }} of {{ notifications.last_page }}</span>
                <button class="btn btn-secondary btn-sm" :disabled="notifications.current_page === notifications.last_page" @click="fetchNotifications(notifications.current_page + 1)">Next</button>
            </div>
        </div>

        <!-- Toast -->
        <transition name="toast">
            <div v-if="toast.show" class="toast" :class="'toast-' + toast.type">
                {{ toast.message }}
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    data() {
        return {
            notifications: {},
            unreadCount: 0,
            loading: true,
            filters: { search: '', view_status: '', type: '' },
            debounceTimer: null,
            toast: { show: false, message: '', type: 'success' },
        };
    },
    mounted() {
        this.fetchNotifications();
        this.fetchUnreadCount();
    },
    methods: {
        async fetchNotifications(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.set('page', page);
                params.set('per_page', 20);
                if (this.filters.search) params.set('search', this.filters.search);
                if (this.filters.view_status) params.set('view_status', this.filters.view_status);
                if (this.filters.type) params.set('type', this.filters.type);

                const res = await fetch(`/api/notifications?${params}`, {
                    headers: { 'Accept': 'application/json' },
                });
                this.notifications = await res.json();
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },
        async fetchUnreadCount() {
            try {
                const res = await fetch('/api/notifications/unread-count', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.unreadCount = data.count;
            } catch (e) {
                console.error(e);
            }
        },
        debouncedFetch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.fetchNotifications(), 300);
        },
        async markSeen(id) {
            try {
                await fetch(`/api/notifications/${id}/seen`, {
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
                await fetch(`/api/notifications/${id}`, {
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
    },
};
</script>

<style scoped>
.notification-manager { font-family: 'Figtree', Roboto, Arial, sans-serif; }

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
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(20px); }
</style>
