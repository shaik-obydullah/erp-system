@extends('roles.layout')

@section('title', 'Shipments')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Shipments</h2>
            <a href="{{ route('shipments.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Shipment
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('shipments.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by tracking number..." style="flex: 1; min-width: 200px;">
                <select name="status" class="form-input" style="max-width: 180px;">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                    <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Canceled</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('shipments.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Tracking Number</th>
                    <th>PO Number</th>
                    <th>Warehouse</th>
                    <th>Received Date</th>
                    <th>Landed Cost</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $shipment)
                    <tr id="row-{{ $shipment->id }}">
                        <td><strong>{{ $shipment->tracking_number }}</strong></td>
                        <td>{{ $shipment->purchaseOrder->order_number ?? '—' }}</td>
                        <td>{{ $shipment->warehouse->name ?? '—' }}</td>
                        <td>{{ $shipment->received_date ? \Carbon\Carbon::parse($shipment->received_date)->format('d M Y') : '—' }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($shipment->costs->sum('amount'), 2) }}</td>
                        <td>
                            <span class="badge {{ $shipment->status === 'delivered' ? 'badge-green' : ($shipment->status === 'in_transit' ? 'badge-orange' : ($shipment->status === 'canceled' ? 'badge-red' : '')) }}">
                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('shipments.show', $shipment) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </a>
                                <a href="{{ route('shipments.edit', $shipment) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">No shipments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($shipments->hasPages())
        <div style="padding: 16px;">
            {{ $shipments->links() }}
        </div>
        @endif
    </div>
@endsection
