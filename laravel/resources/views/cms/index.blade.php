@extends('roles.layout')

@section('title', 'CMS Content')

@section('content')
<div x-data="deleteHandler()" x-init="init()">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Content Management</h2>
            <a href="{{ route('cms.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Content
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('cms.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by name or slug..." style="flex: 1; min-width: 200px;">
                <select name="type" class="form-input" style="max-width: 180px;">
                    <option value="">All Types</option>
                    <option value="hero" {{ request('type') === 'hero' ? 'selected' : '' }}>Hero Banner</option>
                    <option value="banner" {{ request('type') === 'banner' ? 'selected' : '' }}>Banner</option>
                    <option value="page" {{ request('type') === 'page' ? 'selected' : '' }}>Page</option>
                    <option value="faq" {{ request('type') === 'faq' ? 'selected' : '' }}>FAQ</option>
                    <option value="settings" {{ request('type') === 'settings' ? 'selected' : '' }}>Settings</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'type']))
                    <a href="{{ route('cms.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contents as $item)
                    <tr id="row-{{ $item->id }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                    {{ substr($item->name ?? 'N', 0, 1) }}
                                </div>
                                <div>
                                    <strong>{{ $item->name }}</strong>
                                    @if($item->media)
                                        <div style="font-size: 12px; color: var(--text-secondary);">{{ Str::limit($item->media, 40) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $item->type === 'hero' ? 'badge-green' : ($item->type === 'banner' ? 'badge-orange' : ($item->type === 'page' ? 'badge-blue' : '')) }}">
                                {{ ucfirst($item->type) }}
                            </span>
                        </td>
                        <td><code style="font-size: 12px;">{{ $item->slug }}</code></td>
                        <td>
                            <span class="badge {{ $item->status === 'active' ? 'badge-green' : 'badge-orange' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td>{{ $item->sort_order }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('cms.edit', $item) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                                <button type="button" class="btn btn-ghost btn-sm" style="color: var(--error);" @click="confirmDelete({{ $item->id }}, '{{ route('cms.destroy', $item) }}', '{{ e($item->name) }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No content found. Create your first piece of content to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($contents->hasPages())
        <div style="padding: 16px;">
            {{ $contents->links() }}
        </div>
        @endif
    </div>

    <div x-show="showModal" x-cloak :class="{ 'show': showModal }" class="modal-overlay" @click.self="cancelDelete()">
        <div class="modal" @click.stop>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--error-bg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--error)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 600;">Delete Content</h3>
                </div>
            </div>
            <p style="margin: 0 0 24px; font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
                Are you sure you want to delete <strong x-text="deleteLabel"></strong>? This action cannot be undone.
            </p>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" @click="cancelDelete()">Cancel</button>
                <button type="button" class="btn btn-danger" @click="executeDelete()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
