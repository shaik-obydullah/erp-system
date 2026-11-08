@extends('roles.layout')

@section('title', 'Shipment Returns')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Shipment Returns</h2>
            <a href="{{ route('shipment-returns.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Return
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('shipment-returns.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by remark..." style="flex: 1; min-width: 200px;">
                <select name="status" class="form-input" style="max-width: 180px;">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>Processed</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select name="return_reason" class="form-input" style="max-width: 180px;">
                    <option value="">All Reasons</option>
                    <option value="damaged" {{ request('return_reason') === 'damaged' ? 'selected' : '' }}>Damaged</option>
                    <option value="incorrect_item" {{ request('return_reason') === 'incorrect_item' ? 'selected' : '' }}>Incorrect Item</option>
                    <option value="excess_quantity" {{ request('return_reason') === 'excess_quantity' ? 'selected' : '' }}>Excess Quantity</option>
                    <option value="other" {{ request('return_reason') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                @if(request()->hasAny(['search', 'status', 'return_reason']))
                    <a href="{{ route('shipment-returns.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PO Number</th>
                    <th>Invoice Amount</th>
                    <th>Return Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $return)
                    <tr id="row-{{ $return->id }}">
                        <td>{{ $return->id }}</td>
                        <td>{{ $return->purchaseOrder->order_number ?? '—' }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($return->invoice_amount, 2) }}</td>
                        <td>{{ $return->return_reason }}</td>
                        <td>
                            <span class="badge {{ in_array($return->status, ['processed', 'completed'], true) ? 'badge-green' : ($return->status === 'pending' ? 'badge-orange' : 'badge-red') }}">
                                {{ ucwords(str_replace('_', ' ', $return->status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('shipment-returns.edit', $return) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No shipment returns found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($returns->hasPages())
        <div style="padding: 16px;">
            {{ $returns->links() }}
        </div>
        @endif
    </div>
@endsection
