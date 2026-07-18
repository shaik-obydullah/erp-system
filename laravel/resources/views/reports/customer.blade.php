@extends('roles.layout')

@section('title', 'Customer Report')

@section('content')
<div style="margin-bottom: 24px;">
    <div style="display: flex; gap: 4px; border-bottom: 1px solid var(--border); padding: 0 16px;">
        <a href="{{ route('reports.index') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Overview
        </a>
        <a href="{{ route('reports.sales') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Sales
        </a>
        <a href="{{ route('reports.income') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            Income
        </a>
        <a href="{{ route('reports.expense') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
            Expense
        </a>
        <a href="{{ route('reports.stock') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Stock
        </a>
        <a href="{{ route('reports.customers') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px; background: var(--bg-white); color: var(--primary); border-bottom: 2px solid var(--primary);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Customers
        </a>
        <a href="{{ route('reports.suppliers') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            Suppliers
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px;">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Customers</span>
            <span class="stat-value">{{ number_format($totalCustomers) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Active</span>
            <span class="stat-value" style="color: var(--success, #1e8e3e);">{{ number_format($activeCustomers) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Inactive</span>
            <span class="stat-value" style="color: var(--error);">{{ number_format($inactiveCustomers) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Outstanding Balance</span>
            <span class="stat-value" style="color: {{ $totalBalance > 0 ? 'var(--error)' : 'var(--success, #1e8e3e)' }};">{{ $currencySymbol }}{{ number_format($totalBalance, 2) }}</span>
        </div>
    </div>
</div>

<div style="margin-bottom: 28px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Top 10 Customers</h2>
        </div>
        <div class="card-body">
            <canvas id="topCustomersChart" height="100"></canvas>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 28px;">
    <div class="card-header">
        <h2 class="card-title">Top 10 Customers</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Order Count</th>
                <th>Total Spent</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topCustomers as $customer)
                <tr>
                    <td><strong>{{ $customer->name }}</strong></td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ number_format($customer->order_count) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($customer->total_spent, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">No customer data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Customers</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->name }}</strong></td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>
                        <span style="color: {{ $customer->balance > 0 ? 'var(--error)' : ($customer->balance < 0 ? 'var(--success, #1e8e3e)' : 'inherit') }}; font-weight: 600;">
                            {{ $currencySymbol }}{{ number_format($customer->balance, 2) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $customer->status === 'active' ? 'badge-green' : 'badge-gray' }}">
                            {{ ucfirst($customer->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">No customers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($customers->hasPages())
    <div style="padding: 16px;">
        {{ $customers->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('topCustomersChart');
        if (!ctx) return;

        var labels = {!! $topCustomerLabelsJson !!};
        var values = {!! $topCustomerValuesJson !!};

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Spent',
                    data: values,
                    backgroundColor: 'rgba(26, 115, 232, 0.15)',
                    borderColor: '#1a73e8',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(26, 115, 232, 0.3)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#1a73e8',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#202124',
                        titleFont: { size: 13, weight: '500' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return '{{ $currencySymbol }}' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12 },
                            color: '#5f6368'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.06)' },
                        ticks: {
                            font: { size: 12 },
                            color: '#5f6368',
                            callback: function(value) {
                                return '{{ $currencySymbol }}' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
