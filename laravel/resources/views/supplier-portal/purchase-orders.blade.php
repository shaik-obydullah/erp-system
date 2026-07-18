@extends('supplier-portal.layout')

@section('title', 'Purchase Orders')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Purchase Orders</h2>
    </div>

    <!-- Filters -->
    <div style="padding: 16px;">
        <form method="GET" action="{{ route('supplier-portal.orders') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <select name="status" class="form-input" style="max-width: 180px;">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Payment</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->has('status'))
                <a href="{{ route('supplier-portal.orders') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>PO Number</th>
                <th>Total Amount</th>
                <th>Due Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td><strong>{{ $order->order_number }}</strong></td>
                    <td>${{ number_format($order->total_amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $order->due_amount > 0 ? 'badge-orange' : 'badge-green' }}">
                            ${{ number_format($order->due_amount, 2) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('supplier-portal.order', $order->id) }}" class="btn btn-ghost btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">No purchase orders found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($orders->hasPages())
    <div style="padding: 16px;">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
