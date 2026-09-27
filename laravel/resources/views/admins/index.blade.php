@extends('roles.layout')

@section('title', 'Admin Management')

@section('content')
<div class="card" x-data="deleteHandler()" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">All Admins</h2>
        <a href="{{ route('admins.create') }}" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Admin
        </a>
    </div>

    <div x-show="successMessage" x-cloak class="alert alert-success show" style="margin: 16px 16px 0;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <span x-text="successMessage"></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Admin</th>
                <th>Email</th>
                <th>Roles</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($admins as $admin)
                <tr id="row-{{ $admin->id }}">
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                {{ substr($admin->first_name, 0, 1) }}
                            </div>
                            <div>
                                <strong>{{ $admin->first_name }} {{ $admin->last_name }}</strong>
                                @if($admin->mobile)
                                    <div style="font-size: 12px; color: var(--text-secondary);">{{ $admin->mobile }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $admin->email }}</td>
                    <td>
                        @foreach($admin->roles as $role)
                            <span class="badge badge-blue">{{ $role->name }}</span>
                        @endforeach
                        @if($admin->roles->isEmpty())
                            <span style="font-size: 12px; color: var(--text-disabled);">No role</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $admin->status === 'active' ? 'badge-green' : 'badge-orange' }}">
                            {{ ucfirst($admin->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('admins.edit', $admin) }}" class="btn btn-ghost btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </a>
                            @if($admin->id !== auth('admin')->id())
                                <button type="button" class="btn btn-ghost btn-sm" style="color: var(--error);" @click="confirmDelete({{ $admin->id }}, '{{ route('admins.destroy', $admin) }}', 'this admin')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Delete
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">No admins found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    function deleteHandler() {
        return {
            successMessage: '',
            errorMessage: '',
            csrfToken: '',
            init() {
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            },
            async confirmDelete(id, url, label) {
                if (!confirm('Are you sure you want to delete ' + label + '?')) return;

                try {
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (response.ok) {
                        const row = document.getElementById('row-' + id);
                        if (row) row.remove();
                        this.successMessage = data.message;
                        setTimeout(() => { this.successMessage = ''; }, 3000);
                    } else {
                        alert(data.message || 'Failed to delete.');
                    }
                } catch (e) {
                    alert('An unexpected error occurred.');
                }
            },
        };
    }
</script>
@endsection
