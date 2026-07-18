@extends('supplier-portal.layout')

@section('title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="stat-info">
            <h3>{{ $totalProducts }}</h3>
            <p>Total Products</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-info">
            <h3>{{ $totalPOs }}</h3>
            <p>Purchase Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <h3>{{ $pendingPOs }}</h3>
            <p>Pending Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <h3>${{ number_format($balance, 2) }}</h3>
            <p>Account Balance</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Purchase Orders</h2>
        <a href="{{ route('supplier-portal.orders') }}" class="btn btn-secondary">View All</a>
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
            @forelse($recentPOs as $po)
                <tr>
                    <td><strong>{{ $po->order_number }}</strong></td>
                    <td>${{ number_format($po->total_amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $po->due_amount > 0 ? 'badge-orange' : 'badge-green' }}">
                            ${{ number_format($po->due_amount, 2) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('supplier-portal.order', $po->id) }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">No purchase orders yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
