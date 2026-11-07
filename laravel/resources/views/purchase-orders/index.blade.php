@extends('roles.layout')

@section('title', 'Purchase Orders')

@section('content')
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Purchase Orders</h2>
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create PO
            </a>
        </div>

        <div style="padding: 16px;">
            <form method="GET" action="{{ route('purchase-orders.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Search by order number..." style="flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary">Search</button>
                @if(request()->has('search'))
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Need</th>
                    <th>Supplier</th>
                    <th>Total Amount</th>
                    <th>Due Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $po)
                    <tr id="row-{{ $po->id }}">
                        <td><strong>{{ $po->order_number }}</strong></td>
                        <td>{{ $po->need->description ?? '—' }}</td>
                        <td>{{ $po->supplier->name ?? '—' }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($po->total_amount, 2) }}</td>
                        <td>
                            <span style="color: {{ $po->due_amount > 0 ? 'var(--error)' : 'var(--success, #22c55e)' }}; font-weight: 600;">
                                {{ $currencySymbol }}{{ number_format($po->due_amount, 2) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('purchase-orders.edit', $po) }}" class="btn btn-ghost btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">No purchase orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($purchaseOrders->hasPages())
        <div style="padding: 16px;">
            {{ $purchaseOrders->links() }}
        </div>
        @endif
    </div>

@endsection
