@extends('customer-portal.layout')

@section('title', 'My Orders')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">My Orders</h2>
    </div>

    <!-- Filters -->
    <div style="padding: 16px;">
        <form method="GET" action="{{ route('portal.orders') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <select name="status" class="form-input" style="max-width: 180px;">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->has('status'))
                <a href="{{ route('portal.orders') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice ID</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td><strong>{{ $order->invoice_id }}</strong></td>
                    <td>{{ $order->created_at ? $order->created_at->format('M d, Y') : '-' }}</td>
                    <td>{{ $order->details_count ?? $order->details->count() }}</td>
                    <td>${{ number_format($order->grand_total, 2) }}</td>
                    <td>${{ number_format($order->paid_amount, 2) }}</td>
                    <td>${{ number_format($order->sale_due, 2) }}</td>
                    <td>
                        <span class="badge {{ $order->status === 'completed' ? 'badge-green' : ($order->status === 'pending' ? 'badge-orange' : 'badge-blue') }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('portal.order', $order->id) }}" class="btn btn-ghost btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-state">No orders found.</td>
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
