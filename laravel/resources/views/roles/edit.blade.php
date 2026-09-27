@extends('roles.layout')

@section('title', 'Edit Role')

@section('content')
<div class="card" x-data="roleForm()">
    <div class="card-header">
        <h2 class="card-title">Edit Role: {{ $role->name }}</h2>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back to Roles</a>
    </div>
    <div class="card-body">
        <form action="{{ route('roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Role Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" value="{{ old('description', $role->description) }}">
                </div>
            </div>

            <div class="form-group">
                <label>Permissions *</label>
                @error('permissions') <span class="form-error">{{ $message }}</span> @enderror
                <div class="permissions-grid">
                    @foreach($permissions as $group => $groupPermissions)
                        @php $slug = Str::slug($group); @endphp
                        <div class="permission-group">
                            <div class="permission-item" style="margin-bottom: 8px;">
                                <input type="checkbox" 
                                    :checked="allChecked('{{ $slug }}')" 
                                    @change="toggleGroup('{{ $slug }}', $event.target.checked)">
                                <label style="font-weight: 600;">{{ $group }}</label>
                            </div>
                            @foreach($groupPermissions as $permission)
                                <div class="permission-item">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                        data-group="{{ $slug }}" 
                                        @change="updateGroup('{{ $slug }}')"
                                        {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>
                                    <label>{{ $permission->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">Update Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function roleForm() {
    return {
        toggleGroup(group, checked) {
            document.querySelectorAll('[data-group="' + group + '"]').forEach(cb => {
                cb.checked = checked;
            });
        },
        allChecked(group) {
            const cbs = document.querySelectorAll('[data-group="' + group + '"]');
            if (cbs.length === 0) return false;
            return Array.from(cbs).every(cb => cb.checked);
        },
        updateGroup(group) {
            this.$nextTick(() => {});
        }
    };
}
</script>
@endsection
