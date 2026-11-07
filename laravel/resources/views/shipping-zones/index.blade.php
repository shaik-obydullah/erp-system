@extends('roles.layout')

@section('title', 'Shipping Zones')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Shipping Zones</h2>
            <a href="{{ route('shipping-zones.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Zone
            </a>
        </div>

        <!-- Filters & Actions -->
        <div style="padding: 16px;">
            <form method="GET" action="{{ route('shipping-zones.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by name..." style="flex: 1; min-width: 200px;">
                <select name="status" class="form-input" style="max-width: 180px;">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('shipping-zones.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Countries</th>
                    <th>Methods</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($zones as $zone)
                    <tr id="row-{{ $zone->id }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; flex-shrink: 0;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                                </div>
                                <div>
                                    <strong>{{ $zone->name }}</strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $list = $zone->country_list;
                            @endphp
                            @if(in_array('*', $list, true))
                                <span class="badge badge-blue">Rest of World</span>
                            @elseif(count($list) > 0)
                                <span class="text-sm">{{ count($list) }} country(ies)</span>
                            @else
                                <span class="text-sm text-muted">No countries</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $zone->methods_count > 0 ? 'badge-green' : 'badge-gray' }}">
                                {{ $zone->methods_count }} method(s)
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $zone->status === 'active' ? 'badge-green' : 'badge-orange' }}">
                                {{ ucfirst($zone->status) }}
                            </span>
                        </td>
                        <td class="text-sm">{{ $zone->sort_order }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('shipping-zones.edit', $zone) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Manage
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No shipping zones found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($zones->hasPages())
        <div style="padding: 16px;">
            {{ $zones->links() }}
        </div>
        @endif
    </div>
@endsection