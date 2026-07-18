@extends('roles.layout')

@section('title', 'Reports')

@section('content')
<div style="margin-bottom: 24px;">
    <div style="display: flex; gap: 4px; border-bottom: 1px solid var(--border); padding: 0 16px;">
        <a href="{{ route('reports.index') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px; background: var(--bg-white); color: var(--primary); border-bottom: 2px solid var(--primary);">
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
        <a href="{{ route('reports.customers') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Customers
        </a>
        <a href="{{ route('reports.suppliers') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            Suppliers
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px;">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Sales</span>
            <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalSales, 2) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Income</span>
            <span class="stat-value">{{ $currencySymbol }}{{ number_format($income, 2) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Expenses</span>
            <span class="stat-value">{{ $currencySymbol }}{{ number_format($expenses, 2) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #e6f4ea; color: {{ $netProfit >= 0 ? '#1e8e3e' : 'var(--error)' }};">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Net Profit</span>
            <span class="stat-value" style="color: {{ $netProfit >= 0 ? 'var(--success, #1e8e3e)' : 'var(--error)' }};">{{ $currencySymbol }}{{ number_format($netProfit, 2) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Products</span>
            <span class="stat-value">{{ number_format($totalProducts) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Customers</span>
            <span class="stat-value">{{ number_format($totalCustomers) }}</span>
        </div>
    </div>
</div>

<div style="margin-bottom: 28px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Monthly Sales & Expenses</h2>
        </div>
        <div class="card-body">
            <canvas id="monthlySalesChart" height="100"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Top 5 Selling Products</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Qty Sold</th>
                </tr>
            </thead>
            <tbody>
                @forelse($top5Products as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $product->name }}</strong></td>
                        <td>{{ number_format($product->total_sold) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="empty-state">No product data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Sales</h2>
            <a href="{{ route('reports.sales') }}" class="view-all">View All</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSales as $sale)
                    <tr>
                        <td><strong>{{ $sale->invoice_id }}</strong></td>
                        <td>{{ $sale->transaction->date ?? '—' }}</td>
                        <td>{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</td>
                        <td>
                            <span class="badge {{ $sale->status === 'completed' ? 'badge-green' : ($sale->status === 'partial' ? 'badge-orange' : 'badge-blue') }}">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">No recent sales.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('monthlySalesChart');
        if (!ctx) return;

        var labels = {!! $chartLabelsJson !!};
        var salesData = {!! $monthlySalesJson !!};
        var expenseData = {!! $monthlyExpensesJson !!};

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales',
                    data: salesData,
                    backgroundColor: 'rgba(26, 115, 232, 0.6)',
                    borderColor: '#1a73e8',
                    borderWidth: 1,
                    borderRadius: 4,
                }, {
                    label: 'Expenses',
                    data: expenseData,
                    backgroundColor: 'rgba(234, 67, 53, 0.4)',
                    borderColor: '#ea4335',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        backgroundColor: '#202124',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': {{ $currencySymbol }}' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#5f6368' } },
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { font: { size: 11 }, color: '#5f6368' } }
                }
            }
        });
    });
</script>
@endsection