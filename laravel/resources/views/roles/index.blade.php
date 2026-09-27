@extends('roles.layout')

@section('title', 'Roles & Permissions')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Roles</h2>
        <a href="{{ route('roles.create') }}" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Role
        </a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Role</th>
                <th>Description</th>
                <th>Permissions</th>
                <th>Users</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($roles as $role)
                <tr>
                    <td><strong>{{ $role->name }}</strong></td>
                    <td>{{ $role->description ?? '-' }}</td>
                    <td>
                        @foreach($role->permissions->take(3) as $permission)
                            <span class="badge badge-blue">{{ $permission->name }}</span>
                        @endforeach
                        @if($role->permissions->count() > 3)
                            <span class="badge badge-purple">+{{ $role->permissions->count() - 3 }} more</span>
                        @endif
                    </td>
                    <td>{{ $role->admins_count ?? $role->admins->count() }}</td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-ghost btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </a>
                            @if($role->name !== 'super-admin')
                                <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--error);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">No roles found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
