@extends('supplier.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
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
                <h3>${{ $totalPaid }}</h3>
                <p>Total Paid</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-info">
                <h3>{{ $pendingPayments }}</h3>
                <p>Pending Payments</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Welcome back, {{ $supplier->name }}!</h2>
        </div>
        <div class="card-body">
            <p>This is your supplier portal dashboard. From here you can:</p>
            <ul style="margin-top: 12px; padding-left: 20px; color: var(--text-secondary);">
                <li>View your supply orders and invoices</li>
                <li>Track payment status and history</li>
                <li>Manage your account information</li>
            </ul>
        </div>
    </div>
@endsection
