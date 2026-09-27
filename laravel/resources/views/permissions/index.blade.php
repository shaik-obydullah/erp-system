@extends('roles.layout')

@section('title', 'Permissions')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Permissions</h2>
        <span style="font-size: 14px; color: var(--text-secondary);">{{ $permissions->flatten()->count() }} permissions across {{ $permissions->count() }} modules</span>
    </div>
    <div class="card-body">
        <div class="permissions-grid">
            @foreach($rolePermissions as $group => $groupPermissions)
                <div class="permission-group">
                    <div class="permission-group-title">{{ $group }}</div>
                    <table style="margin-top: 8px;">
                        <thead>
                            <tr>
                                <th>Permission</th>
                                <th>Roles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupPermissions as $permission)
                                <tr>
                                    <td style="font-size: 13px;">{{ $permission->name }}</td>
                                    <td>
                                        @foreach($permission->roles as $role)
                                            <span class="badge badge-blue">{{ $role->name }}</span>
                                        @endforeach
                                        @if($permission->roles->isEmpty())
                                            <span style="font-size: 12px; color: var(--text-disabled);">None</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
