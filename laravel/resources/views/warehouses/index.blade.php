@extends('roles.layout')

@section('title', 'Warehouses')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Warehouses</h2>
            <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Warehouse
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Capacity</th>
                    <th>Location</th>
                    <th>Contact Number</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                    <tr id="row-{{ $warehouse->id }}">
                        <td><strong>{{ $warehouse->name }}</strong></td>
                        <td>{{ $warehouse->capacity ?? '—' }}</td>
                        <td>{{ $warehouse->location ?? '—' }}</td>
                        <td>{{ $warehouse->contact_number ?? '—' }}</td>
                        <td>{{ $warehouse->email ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No warehouses found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($warehouses->hasPages())
        <div style="padding: 16px;">
            {{ $warehouses->links() }}
        </div>
        @endif
    </div>
@endsection
