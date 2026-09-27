@extends('customer-portal.layout')

@section('title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        </div>
        <div class="stat-info">
            <h3>{{ $totalOrders }}</h3>
            <p>Total Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <h3>${{ number_format($totalSpent, 2) }}</h3>
            <p>Total Spent</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <h3>{{ $pendingOrders }}</h3>
            <p>Pending Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div class="stat-info">
            <h3>${{ number_format($balance, 2) }}</h3>
            <p>Account Balance</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Orders</h2>
        <a href="{{ route('portal.orders') }}" class="btn btn-secondary">View All</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Invoice ID</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentOrders as $order)
                <tr>
                    <td><strong>{{ $order->invoice_id }}</strong></td>
                    <td>{{ $order->created_at ? $order->created_at->format('M d, Y') : '-' }}</td>
                    <td>${{ number_format($order->grand_total, 2) }}</td>
                    <td>
                        <span class="badge {{ $order->status === 'completed' ? 'badge-green' : ($order->status === 'pending' ? 'badge-orange' : 'badge-blue') }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('portal.order', $order->id) }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">No orders yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
