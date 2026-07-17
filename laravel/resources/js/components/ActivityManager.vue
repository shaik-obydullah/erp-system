<template>
    <div class="activity-manager">
        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Activities</span>
                    <span class="stat-value">{{ stats.total || 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Today</span>
                    <span class="stat-value">{{ stats.today || 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Logins Today</span>
                    <span class="stat-value">{{ stats.logins || 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Errors Today</span>
                    <span class="stat-value">{{ stats.errors || 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Activity Log</h2>
                <div class="filters">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search activities..."
                        class="filter-input"
                        @input="debouncedFetch"
                    />
                    <select v-model="filters.type" class="filter-select" @change="fetchActivities">
                        <option value="">All Types</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                    <select v-model="filters.admin_id" class="filter-select" @change="fetchActivities">
                        <option value="">All Admins</option>
                        <option v-for="admin in stats.admins" :key="admin.id" :value="admin.id">
                            {{ admin.first_name }} {{ admin.last_name }}
                        </option>
                    </select>
                    <input
                        v-model="filters.from"
                        type="date"
                        class="filter-input"
                        @change="fetchActivities"
                    />
                    <input
                        v-model="filters.to"
                        type="date"
                        class="filter-input"
                        @change="fetchActivities"
                    />
                    <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                    <button class="btn btn-danger" @click="clearActivities">Clear Old</button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="loading">
                <div class="spinner"></div>
                <span>Loading activities...</span>
            </div>

            <!-- Table -->
            <table v-else>
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
                    <tr v-if="activities.data && activities.data.length === 0">
                        <td colspan="8" class="empty-state">No activities found.</td>
                    </tr>
                    <tr v-for="activity in activities.data" :key="activity.id">
                        <td>{{ activity.id }}</td>
                        <td>
                            <span class="badge" :class="'badge-' + activity.type">
                                {{ activity.type }}
                            </span>
                        </td>
                        <td><strong>{{ activity.name }}</strong></td>
                        <td>{{ activity.description || '-' }}</td>
                        <td>{{ activity.admin ? activity.admin.first_name + ' ' + activity.admin.last_name : 'System' }}</td>
                        <td><code>{{ activity.ip_address }}</code></td>
                        <td>{{ formatDate(activity.created_at) }}</td>
                        <td>
                            <button
                                v-if="activity.old_data || activity.new_data"
                                class="btn btn-ghost btn-sm"
                                @click="showChanges(activity)"
                            >
                                View
                            </button>
                            <span v-else class="text-muted">-</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="activities.last_page > 1" class="pagination">
                <button
                    class="btn btn-secondary btn-sm"
                    :disabled="activities.current_page === 1"
                    @click="goToPage(activities.current_page - 1)"
                >
                    Previous
                </button>
                <span class="page-info">
                    Page {{ activities.current_page }} of {{ activities.last_page }}
                    ({{ activities.total }} total)
                </span>
                <button
                    class="btn btn-secondary btn-sm"
                    :disabled="activities.current_page === activities.last_page"
                    @click="goToPage(activities.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Changes Modal -->
        <div v-if="modal.show" class="modal-overlay" @click.self="modal.show = false">
            <div class="modal">
                <div class="modal-header">
                    <h3>{{ modal.activity?.name }} — Changes</h3>
                    <button class="modal-close" @click="modal.show = false">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="modal.activity?.old_data" class="diff-section">
                        <h4>Old Values</h4>
                        <pre class="diff-old">{{ JSON.stringify(modal.activity.old_data, null, 2) }}</pre>
                    </div>
                    <div v-if="modal.activity?.new_data" class="diff-section">
                        <h4>New Values</h4>
                        <pre class="diff-new">{{ JSON.stringify(modal.activity.new_data, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            activities: {},
            stats: {},
            loading: true,
            filters: {
                search: '',
                type: '',
                admin_id: '',
                from: '',
                to: '',
            },
            modal: {
                show: false,
                activity: null,
            },
            debounceTimer: null,
        };
    },
    mounted() {
        this.fetchStats();
        this.fetchActivities();
    },
    methods: {
        async fetchStats() {
            try {
                const res = await fetch('/api/activities/stats', {
                    headers: { 'Accept': 'application/json' },
                });
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

                const res = await fetch(`/api/activities?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });
                this.activities = await res.json();
            } catch (e) {
                console.error('Failed to load activities', e);
            } finally {
                this.loading = false;
            }
        },
        debouncedFetch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.fetchActivities(), 300);
        },
        goToPage(page) {
            this.fetchActivities(page);
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
    },
};
</script>

<style scoped>
.activity-manager { font-family: 'Figtree', Roboto, Arial, sans-serif; }

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
    .stats-row { grid-template-columns: 1fr; }
    .filters { flex-direction: column; }
    .filter-input, .filter-select { width: 100%; }
}
</style>
