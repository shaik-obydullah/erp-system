@extends('roles.layout')

@section('title', 'Stock Report')

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
        <a href="{{ route('reports.stock') }}" class="nav-item" style="border-radius: 8px 8px 0 0; margin-bottom: -1px; background: var(--bg-white); color: var(--primary); border-bottom: 2px solid var(--primary);">
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

<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 28px;">
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Stock Value</span>
            <span class="stat-value">{{ $currencySymbol }}{{ number_format($totalStockValue, 2) }}</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Low Stock Items</span>
            <span class="stat-value" style="color: {{ $lowStockItems->total() > 0 ? 'var(--error)' : 'var(--success, #1e8e3e)' }};">{{ $lowStockItems->total() }}</span>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 28px;">
    <div class="card-header">
        <h2 class="card-title">Low Stock Alert</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Buy Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lowStockItems as $item)
                <tr>
                    <td><strong>{{ $item->product->name ?? '—' }}</strong></td>
                    <td>
                        <span class="badge badge-orange">{{ $item->quantity }}</span>
                    </td>
                    <td>{{ $currencySymbol }}{{ number_format($item->buy_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="empty-state">All items are well stocked.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($lowStockItems->hasPages())
    <div style="padding: 16px;">
        {{ $lowStockItems->withQueryString()->links() }}
    </div>
    @endif
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Stock by Category</h2>
        </div>
        <div class="card-body">
            <canvas id="categoryChart" height="100"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Category Breakdown</h2>
        </div>
        <div class="card-body">
            @forelse($stockByCategory as $category)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border, #e0e0e0);">
                    <span style="font-weight: 500;">{{ $category->name }}</span>
                    <span class="badge badge-blue">{{ $category->items_count }} items</span>
                </div>
            @empty
                <p style="color: var(--text-secondary, #5f6368); text-align: center; padding: 20px 0;">No categories found.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Stock List</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Buy Price</th>
                <th>Sale Price</th>
                <th>Total Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockList as $stock)
                <tr>
                    <td><strong>{{ $stock->product->name ?? '—' }}</strong></td>
                    <td>{{ $stock->quantity }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($stock->buy_price, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($stock->sale_price, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($stock->quantity * $stock->buy_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">No stock items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($stockList->hasPages())
    <div style="padding: 16px;">
        {{ $stockList->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        var labels = @json($categoryLabelsJson);
        var values = @json($categoryValuesJson);

        var colors = [
            '#1a73e8', '#34a853', '#ea4335', '#fbbc04', '#46bdc6',
            '#ff6d01', '#ab47bc', '#00acc1', '#8d6e63', '#546e7a'
        ];

        new Chart(ctx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            font: { size: 12 },
                            color: '#5f6368',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#202124',
                        titleFont: { size: 13, weight: '500' },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
